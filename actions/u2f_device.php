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


class U2f_deviceAction extends Action
{
    private function detail_device($user, $key_handle)
    {
        try {
            $deviceDetails = 
                User_u2f_device::get_user_device(
                    $user, 
                    $key_handle
                );
        } catch (NoResultException $e) { 
            $deviceDetails = array('error' => true, 'exception' => $e);
        }

        $deviceDetails->enrolled_str = common_exact_date("@" . $deviceDetails->enrolled);
        
        $cd = Certificate::parse_certificate($deviceDetails->certificate);
        $cd['validFrom_str'] = date_format(date_create('@' . $cd['validFrom_time_t']), "r");
        $cd['validTo_str'] = date_format(date_create('@' . $cd['validTo_time_t']), "r");
        $deviceDetails->certificateDetails = $cd;

        header("Content-Type: application/javascript");
        $this->text(json_encode($deviceDetails));
    }

    private function revoke_device($user, $key_handle)
    {
        User_u2f_device::del_user_device($user, $key_handle);
        common_redirect(common_local_url('two_factor_auth_settings'), 303);
    }

    public function handle()
    {
        $user = common_current_user();
        if (empty($user)) {
            common_redirect(common_local_url("login"), 307);
            return;
        }

    
        $key_handle = $this->trimmed('kh');
        $verb = $this->trimmed('verb');
        $this->{$verb . "_device"}($user, $key_handle);
    }
}
