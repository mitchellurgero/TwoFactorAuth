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


class XauthAction extends FormAction
{
    protected $needLogin = false;

    public function prepare($args)
    {
        parent::prepare($args);
        if (isset($_SESSION['auth-stage1-user'])) {
            $this->xauth_user = $_SESSION['auth-stage1-user'];
            $this->xauth_login = true;
        } else {
            $this->xauth_user = common_current_user(); 
            $this->xauth_login = false;
        }
        if (empty($this->xauth_user)) {
            throw new Exception('no user context');
        }
        if (!isset($_SESSION['auth-remember-device'])) {
            $_SESSION['auth-remember-device'] = false;
        }

        if (isset($_COOKIE['auth-device-pw'])) {
            if ($this->xauth_login) {
                if (null !== User_2fa_known_device::check_device_remembery(
                        $this->xauth_user,
                        $_COOKIE['auth-device-pw'])) {
                   $url = $_SESSION['auth-done-url'];
                   $_SESSION['auth-stage2-done'] = true;
                   common_set_user($this->xauth_user);
                   common_redirect($url);
                }
            }
        }

        $this->xauth_provider = 
            TwoFactorAuthPlugin::getModule('xauth', $args['authenticator']);
        $this->xauth_provider->prepare($this->xauth_user, $args);
        return true;
    }

    public function title()
    {
        return $this->xauth_provider->title();
    }

    public function showContent()
    {
        $form  = new XauthForm($this, $this->xauth_provider);
        $form->show();
        return;
    }
    
    function showScripts()
    {
        parent::showScripts();
        foreach ($this->xauth_provider->scripts as $script) {
            if (is_callable($script)) {
                $this->inlineScript(call_user_func(
                    $script, 
                    $this->isPost() ? "false" : "true"
                ));
            } else {
                $this->script($script);
            }
        }
        return true;
    }

    public function doPost()
    {
        $_SESSION['auth-remember-device'] = $this->boolean('remember_device');

        try {
            $this->xauth_provider->validate($this->trimmed('response-input'));
        } catch (Exception $e) {
            return "Authentication failed: " . $e->getMessage() . ".";
        }

        if ($_SESSION['auth-remember-device']) {
            if (isset($_COOKIE['auth-device-pw'])) {
                $pw = $_COOKIE['auth-device-pw'];
            } else {
                $pw = User_2fa_known_device::gen_device_pw();
                common_set_cookie('auth-device-pw', $pw, strtotime("+3 months"));
            }
            User_2fa_known_device::remember_device_by_pw(
                $this->xauth_user,
                $pw
            );
        }

        if ($this->xauth_login) {
            $url = $_SESSION['auth-done-url'];
            $_SESSION['auth-stage2-done'] = true;
            common_set_user($this->xauth_user);
            common_redirect($url);
        }

        return "Authentication success!";
    }
    
    public function showPrimaryNav() { if (!$this->xauth_login) parent::showPrimaryNav(); }
    public function showNoticeForm() { }
    public function showLocalNav() { if (!$this->xauth_login) parent::showLocalNav(); }
    public function showAside() { }
}

class XauthForm extends Form
{
    public function __construct($out, $authenticator)
    {
        parent::__construct($out);
        $this->authenticator = $authenticator;
    }

    public function id()
    {
        return 'xauth_form';
    }

    public function action()
    {
        return common_local_url('xauth', array(
            'authenticator' => $this->out->arg('authenticator'),
            'verb' => $this->out->arg('verb')));
    }

    public function formClass()
    {
        return 'form';
    }

    public function formData()
    {
        $this->authenticator->showForm($this->out);
        $this->out->element('br');
        $this->out->elementStart('div', array('style'=>'text-align:center'));
        $this->out->checkbox('remember_device',
            _("Don't ask me again on this device."),
            $_SESSION['auth-remember-device']
        );
        $this->out->element('br');
        $this->out->element(
            'a', 
            array('href' => common_local_url(
                'auth_picker', 
                null, 
                array('no_redirect'=>true))), 
            "Try another way to sign in"
        );
        $this->out->elementEnd('div');
    }
}
