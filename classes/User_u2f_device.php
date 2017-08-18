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

class User_u2f_device
{
    public static $ns = 'user_u2f_device';

    public static function add_user_device($user, $registration_result)
    {
        $key_handle = $registration_result->keyHandle;
        Profile_prefs::setData(
            $user->getProfile(), 
            self::$ns,
            $key_handle, 
            json_encode($registration_result)
        );
    }

    public static function del_user_device($user, $key_handle)
    {
        Profile_prefs::setData(
            $user->getProfile(),
            self::$ns,
            $key_handle,
            null
        );
    }

    public static function get_user_device($user, $key_handle)
    {
        return json_decode(
            Profile_prefs::getData(
                $user->getProfile(),
                self::$ns,
                $key_handle
        ));
    }

    public static function get_user_devices($user)
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

        foreach ($ns as $topic) {
            $return[] = json_decode($topic->data);
        }

        return $return;
    }

    public static function update_counter($user, $updated)
    {
        $key_handle = $updated->keyHandle;
        Profile_prefs::setData(
            $user->getProfile(),
            self::$ns,
            $key_handle,
            json_encode($updated)
        );
    }       
}
