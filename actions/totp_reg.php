<?php
/**
 * GNU social - a federating social network
 *
 * Plugin for two-factor authentication
 *
 * PHP version 5
 *
 * LICENCE: This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @category  Plugin
 * @author    Saul St John <saul.stjohn@gmail.com>
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 */

if (!defined('STATUSNET')) {
    exit(1);
}


class Totp_regAction extends Action
{
    public static function get_uri($user) 
    {
        $totp_secret = User_2fa_data::get_totp_secret($user);

        $totp = new OTPHP\TOTP();
        $totp->setSecret($totp_secret);
        $totp->setLabel(
            $user->nickname . "@" . common_config('site', 'server')
        );

        return $totp->getProvisioningUri(true);
    }

    public static function get_json($user)
    {
        $data = array(
            "key" => User_2fa_data::get_totp_secret($user),
            "label" => $user->nickname . "@" . common_config('site', 'server')
        );

        return json_encode($data);
    }

    public function handle()
    {
        $user = common_current_user();
        if (empty($user)) {
            common_redirect(common_local_url("login"), 307);
            return;
        }

        if ($this->trimmed('form') == 'png') {
            header("Content-Type: image/png");

            $qrCode = new Endroid\QrCode\QrCode();
            $qrCode
                ->setText(self::get_uri($user))
                ->setSize(300)
                ->setPadding(10)
                ->setErrorCorrection('high')
                ->setForegroundColor(array('r' => 0, 'g' => 0, 'b' => 0, 'a' => 0))
                ->setBackgroundColor(array('r' => 255, 'g' => 255, 'b' => 255, 'a' => 0))
                ->render()
                ;
        } else if ($this->trimmed('form') == 'txt') {
            $this->text(self::get_uri($user));
        } else if ($this->trimmed('form') == 'js') {
            header("Content-Type: application/javascript");

            $this->text(self::get_json($user));
        }           
    }
}
