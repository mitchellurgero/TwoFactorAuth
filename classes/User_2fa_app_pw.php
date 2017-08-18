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

class User_2fa_app_pw
{
    public static $ns = 'user_2fa_app_pw';

    public static function new_app_pw($user, $app_name)
    {
        try {
            Profile_prefs::getData(
                        $user->getProfile(),
                        self::$ns,
                        $app_name
                    );
            throw new Exception(
                'Password for application ' . $app_name . ' already exists.'
            );
        } catch (NoResultException $e) { }
        $pw = "";
        $rs = openssl_random_pseudo_bytes(16);
        foreach (str_split($rs) as $r) {
            $pw .= chr((ord($r) % 26) + ord('a'));
        }
        Profile_prefs::setData(
            $user->getProfile(),
            self::$ns,
            $app_name,
            common_munge_password($pw)
        );
        return $pw;
    }

    public static function del_app_pw($user, $app_name)
    {
        Profile_prefs::setData(
            $user->getProfile(),
            self::$ns,
            $app_name,
            null
        );
    }

    public static function check_app_pw($user, $pw)
    {
        try {
            $ns = Profile_prefs::getNamespace(
                $user->getProfile(),
                self::$ns
            );
        } catch (NoResultException $e) {
            return null;
        }

        foreach ($ns as $app) {
            if ($app->data == crypt($pw, $app->data)) {
                return $app->topic;
            }
        }

        return null;
    }

    public static function get_user_apps($user)
    {
        try {
            $ns = Profile_prefs::getNamespace(
                $user->getProfile(),
                self::$ns
            );
        } catch (NoResultException $e) {
            return array();
        }

        $apps = array();
        foreach ($ns as $app) {
            $apps[$app->topic] = strtotime($app->created);
        }

        return $apps;
    }
}
