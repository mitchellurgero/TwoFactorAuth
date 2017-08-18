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

class User_2fa_known_device
{
    public static $ns = 'user_2fa_known_device';

    public static function remember_device_by_pw($user, $pw)
    {
        $data = json_encode(array("enrolled" => time()));
         Profile_prefs::setData(
            $user->getProfile(),
            self::$ns,
            common_munge_password($pw),
            $data
        );
    }

    public static function gen_device_pw()
    {
        $rs = openssl_random_pseudo_bytes(32);
        foreach (str_split($rs) as $r) {
            $pw .= chr((ord($r) % 26) + ord('a'));
        }
        return $pw;
    }

    public static function forget_device_by_pw_hash($user, $pw_hash)
    {
        Profile_prefs::setData(
            $user->getProfile(),
            self::$ns,
            $pw_hash,
            null
        );
    }

    public static function check_device_remembery($user, $pw)
    {
        try {
            $ns = Profile_prefs::getNamespace(
                $user->getProfile(),
                self::$ns
            );
        } catch (NoResultException $e) {
            return null;
        }

        foreach ($ns as $dev) {
            if ($dev->topic == crypt($pw, $dev->topic)) {
                return $dev->data;
            }
        }

        return null;
    }

    public static function get_known_devices($user)
    {
        try {
            $ns = Profile_prefs::getNamespace(
                $user->getProfile(),
                self::$ns
            );
        } catch (NoResultException $e) {
            return array();
        }

        $devs = array();
        foreach ($ns as $dev) {
            $devs[$dev->topic] = json_decode($dev->data);
        }

        return $devs;
    }
}
