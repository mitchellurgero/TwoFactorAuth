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


class App_pwAction extends Action
{
    protected $format = 'json';
    public function authorize($user, $app) 
    {
        $new_pw = User_2fa_app_pw::new_app_pw($user, $app);
        $this->text(json_encode(array(
            'success' => true,
            'password' => $new_pw,
        )));
    }

    public function revoke($user, $app)
    {
        User_2fa_app_pw::del_app_pw($user, $app);
        $this->text(json_encode(array(
            'success' => true
        )));
    }

    protected function initDocument()
    {
        print('{"success": false, "result": ');
    }
    protected function endDocument()
    {
        print('}');
    }

    public function handle()
    {
        $user = common_current_user();
        if (empty($user)) {
            common_redirect(common_local_url("login"), 307);
            return;
        }

        header('Content-Type: application/javascript');

        try {
            $this->checkSessionToken();
            $verb = $this->trimmed('verb');
            $app = $this->arg('app');
            if ($app == '') {
                $this->clientError('an application must be specified');
            }
            $this->$verb($user, $app);
        } catch (Exception $e) {
            $this->clientError($e->getMessage());
        }
    }
}
