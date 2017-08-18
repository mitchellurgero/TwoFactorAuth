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


class Backup_codesAction extends Action
{
    public function handle()
    {
        $user = common_current_user();
    
        $verb = urldecode($this->trimmed('verb'));

        if ($verb == 'show') {
            header('Content-Type: application/javascript');

            $response_data = array(
                'codes' => User_2fa_data::get_backup_codes($user),
                'user' => $user->nickname,
            );
            $this->text(json_encode($response_data));
        } else if ($verb == 'reset') {
            User_2fa_data::reset_backup_codes($user);
            common_redirect(
                common_local_url('backup_codes',array( 'verb'=>'show')),
                303
            );
        } else {
            common_server_error('Unknown verb: ' . $verb);
        }
    }
}
