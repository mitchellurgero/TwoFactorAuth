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


class Fido2_credAction extends Action
{
    private function detail_credential($user, $cred_id)
    {
        try {
            $credDetails = 
                User_fido2_cred::get_user_credential(
                    $user, 
                    $cred_id
                );
        } catch (NoResultException $e) { 
            $credDetails = array('error' => true, 'exception' => $e);
        }

        $credDetails->publicKey = json_decode($credDetails->publicKey);
        $credDetails->enrolled_str = common_exact_date("@" . $credDetails->enrolled);

        header("Content-Type: application/javascript");
        $this->text(json_encode($credDetails));
    }

    private function revoke_credential($user, $cred_id)
    {
        User_fido2_cred::del_user_credential($user, $cred_id);
        common_redirect(common_local_url('two_factor_auth_settings'), 303);
    }

    public function handle()
    {
        $user = common_current_user();
        if (empty($user)) {
            common_redirect(common_local_url("login"), 307);
            return;
        }

    
        $cred_id = $this->trimmed('id');
        $verb = $this->trimmed('verb');
        $this->{$verb . "_credential"}($user, $cred_id);
    }
}
