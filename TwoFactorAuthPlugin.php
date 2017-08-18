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

class TwoFactorAuthPlugin extends Plugin
{
    const VERSION = GNUSOCIAL_VERSION;

    function initialize()
    {
        $dir = dirname(__FILE__);
        foreach (scandir($dir . "/modules") as $module) {
            if ('.php' == substr($module, -4)) {
                $className = basename($module, ".php");
                require_once $dir . "/modules/" . $module;
                $className::init();
            }
        }
    }

    private static $modules = array();
    public static function registerModule($type, $name, $className)
    {
        self::$modules[$type][$name] = $className;
    }
    public static function getModule($type, $name)
    {
        $className = self::$modules[$type][$name];
        return new $className();
    }
    public static function getModules($type)
    {
        return array_keys(self::$modules[$type]);
    }
    public static function getModuleTitles($type)
    {
        $res = array();
        foreach (self::$modules[$type] as $name => $className) {
            $title = self::getModule($type, $name)->title();
            $res[$name] = $title;
        }
        return $res;
    }
    
    static function libPathGet($basename, $fname)
    {
        $dir = dirname(__FILE__);
        
        $candidates = glob($dir . DIRECTORY_SEPARATOR . 'extlib' . DIRECTORY_SEPARATOR . $basename . '-*', GLOB_MARK);
        if ((false === $candidates) || (count($candidates) == 0)) {
            error_log("no candidates for $basename");
            return false;
        }
         
         foreach ((array_reverse($candidates)) as $candidate) {
             if (file_exists($candidate . $fname)) {
                 return $candidate . $fname;
             } else { error_log("no such file: $candidate$fname"); }
         }
         return false;
    }
    
    static $__autoloadableClasses = array(
        'u2flib_server\U2F' => array("php-u2flib-server", "src/u2flib_server/U2F.php"),
        'Base32\Base32' => array("base32", "src/Base32.php"),
        'Endroid\QrCode\QrCode' => array("QrCode", "src/QrCode.php"),
        'OTPHP\TOTP' => array("otphp", "lib/TOTP.php"),
        'OTPHP\TOTPInterface' => array("otphp", "lib/TOTPInterface.php"),
        'OTPHP\OTP' => array("otphp", "lib/OTP.php"),
        'OTPHP\OTPInterface' => array("otphp", "lib/OTPInterface.php"),
        'Phido2\Phido2' => array("Phido2", "Phido2.php")
    );

    function onAutoload($cls)
    {
        if (isset(self::$__autoloadableClasses[$cls])) {
            $params = self::$__autoloadableClasses[$cls];
            $loadFile = self::libPathGet($params[0], $params[1]);
            if (false !== $loadFile) {
                require_once $loadFile;
                return false;        
            }
        }

        return parent::onAutoload($cls);
    }

    public function cleanup()
    {
        return true;
    }

    public function onLoginAction($action, &$login)
    {
        switch ($action) {
        case 'auth_picker':
        case 'xauth':
            $login = true;
            return false;
        default:
            return true;
        }
    }

    public function onRouterInitialized(URLMapper $m)
    {
        $m->connect('settings/2fa', array('action' => 'two_factor_auth_settings'));
        $m->connect('main/2fa/backup/:verb', array(
            'action' => 'backup_codes',
            'verb' => '(show|reset)'
        ));
        $m->connect('main/2fa/u2f/reg', array('action' => 'u2f_reg'));
        $m->connect('main/2fa/u2f/device/:kh/:verb', array(
            'action' => 'u2f_device',
            'kh' => '[-_a-zA-Z0-9]+',
            'verb' => '(detail|revoke)',
        ));
        $m->connect('main/2fa/totp/reg.:form', array(
            'action' => 'totp_reg',
            'form' => '(png|js|txt)'
        ));
        $m->connect('main/2fa/app/:verb', array(
            'action' => 'app_pw',
            'verb' => '(authorize|revoke)'
        ));
        $m->connect('main/2fa/fido2/reg', array('action' => 'fido2_reg'));
        $m->connect('main/2fa/fido2/credential/:id/:verb', array(
            'action' => 'fido2_cred',
            'id' => '[-_a-zA-Z0-9]+',
            'verb' => '(detail|revoke)',
        ));
        $m->connect('main/2fa', array('action' => 'auth_picker'));
        $m->connect('main/2fa/:authenticator/auth', array('action' => 'xauth'));
        return true;
    }

    public function onEndAccountSettingsNav($action)
    {
        $action_name = $action->trimmed('action');
        $action->menuItem(
            common_local_url('two_factor_auth_settings'),
            _m('2FA'),
            _m('Two-Factor Authentication configuration page.'),
            $action_name == 'two_factor_auth_settings'
        );
        return true;
    }

    public function onStartCheckPassword($nickname, $password, &$authenticatedUser)
    {
        if (common_is_email($nickname)) {
            $user = User::getKV('email', common_canonical_email($nickname));
        } else {
            $user = User::getKV('nickname', Nickname::normalize($nickname));
        }
        if (false === $user) {
            return true;
        }
        if (User_2fa_data::check_user_2fa_req($user)) {
            $app = User_2fa_app_pw::check_app_pw($user, $password);
            if (null !== $app) {
                $user->auth_app = $app;
                $authenticatedUser = $user;
                return false;
            }
        }
        return true;
    }

    public function onStartSetApiUser($user)
    {
        if (($user instanceof User) 
                && User_2fa_data::check_user_2fa_req($user) 
                && !isset($user->auth_app)) {
            throw new AuthorizationException(_(
                'User requires two-factor authentication.')
            );
        }

        return true;
    }

    public function onStartSetUser($user)
    {
        if (isset($user->auth_app)) {
            throw new AuthorizationException(
                'Use the account password, not an application password, to login.'
            );
        } else if (isset($_SESSION['auth-stage2-done'])) {
            unset($_SESSION['auth-stage1-user']);
            unset($_SESSION['auth-stage2-done']);
            unset($_SESSION['auth-done-url']);
            return true;
        } else if (User_2fa_data::check_user_2fa_req($user)) {
            $_SESSION['auth-stage1-user'] = $user;
            $_SESSION['auth-done-url'] = common_get_returnto();
            common_redirect(common_local_url("auth_picker"), 303);
            return false;
        }
        return true;
    }

    public function onPluginVersion(array &$versions)
    {
        $versions[] = array('name' => 'TwoFactorAuth',
                            'version' => GNUSOCIAL_VERSION,
                            'author' => 'Saul St John',
                            'homepage' => 'https://git.gnu.io/sstjohn/TwoFactorAuth',
                            'rawdescription' =>
                          // TRANS: Plugin description.
                            _m('Secondary authentication by FIDO U2F device'));
        return true;
    }
}
