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

class User_fido2_cred
{
    public static $ns = 'user_fido2_cred';

    public static function add_user_credential($user, $registration)
    {
        Profile_prefs::setData(
            $user->getProfile(), 
            self::$ns,
            $registration->id,
            json_encode($registration)
        );
    }

    public static function del_user_credential($user, $cred_id)
    {
        Profile_prefs::setData(
            $user->getProfile(),
            self::$ns,
            $cred_id,
            null
        );
    }
    
    public static function get_user_credential($user, $cred_id)
    {
        return json_decode(
            Profile_prefs::getData(
                $user->getProfile(),
                self::$ns,
                $cred_id
        ));
    }

    public static function get_user_credentials_list($user)
    {
        try {
            $ns = Profile_prefs::getNamespace(
                $user->getProfile(),
                self::$ns
            );
        } catch (NoResultException $e) {
            return array();
        }

        $return = array();

        foreach ($ns as $cred) {
            $return[] = array("type"=>json_decode($cred->data)->type, "id" => $cred->topic);
        }

        return $return;
    }
}
