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


class User_2fa_data
{
    public static $ns = 'user_2fa_data';

    public static function get_totp_secret($user)
    {
        $profile = $user->getProfile();

        try {
            return Profile_prefs::getData($profile, self::$ns, 
                "totp_secret", null
            );
        } catch (NoResultException $e) {
            $new_secret =
                Base32\Base32::encode(openssl_random_pseudo_bytes(20));
            Profile_prefs::setData($profile, self::$ns,
                "totp_secret", $new_secret
            );
            return $new_secret;
        }
    }

    public static function reset_totp_secret($user)
    {
        Profile_prefs::setData($user->getProfile(), self::$ns, 
            "totp_secret", null
        );
    }
    
    public static function get_user_default($user)
    {
        return Profile_prefs::getData(
            $user->getProfile(),
            self::$ns,
            'default',
            'none'
        );
    }
    
    public static function set_user_default($user, $default)
    {
        Profile_prefs::SetData(
            $user->getProfile(), 
            self::$ns,
            'default', 
            $default
        );
    }

    public static function set_user_2fa_req(
        $user, 
        $required = null
    )
    {
        $profile = $user->getProfile();

        if (isset($required)) {
            Profile_prefs::setData($profile, self::$ns,
                "required", $required
            );
        }
    }

    public static function check_user_2fa_req($user)
    {
        return isset($user) &&  
            Profile_prefs::getData(
                $user->getProfile(), 
                self::$ns, 
                "required", 
                false)
            ? true : false;
    } 

    public static function reset_backup_codes($user)
    {
       $codes = array();

       for ($i = 0; $i < 10; $i++) {
           $s = openssl_random_pseudo_bytes(4);
           $r = ord($s[0]) 
               + (ord($s[1]) << 8)
               + (ord($s[2]) << 16)
               + (ord($s[3]) << 24);
           $codes[] = str_pad($r % (10 ** 8), 8, "0", STR_PAD_LEFT);
       }

       Profile_prefs::setData(
           $user->getProfile(),
           self::$ns,
           "backup_codes",
           json_encode($codes)
       );

       return $codes;
    }

    public static function get_backup_codes($user)
    {
        try {
            return json_decode(
                Profile_prefs::getData(
                    $user->getProfile(), 
                    self::$ns, 
                    "backup_codes"
            ));
        } catch (NoResultException $e) {
            return self::reset_backup_codes($user);
        }
    }

    public static function available_backup_codes($user)
    {
            return count(json_decode(Profile_prefs::getData(
                $user->getProfile(),
                self::$ns,
                "backup_codes",
                "[]"
            )));
    }

    public static function validate_backup_code($user, $code, $invalidate)
    {
        $codes = json_decode(
            Profile_prefs::getData(
                $user->getProfile(),
                self::$ns,
                "backup_codes"
        ));
        
        $idx = array_search($code, $codes, true);

        if ($idx === false) {
            throw new InvalidArgumentException(
                'invalid backup code entered'
            );
        }

        if ($invalidate) {
            unset($codes[$idx]);

            Profile_prefs::setData(
                $user->getProfile(),
                self::$ns,
                "backup_codes",
                json_encode(array_values($codes))
            );
        }
    }
}
