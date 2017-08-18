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


class Fido2_regAction extends SettingsAction
{
    public function title()
    {
        return _m('TITLE', 'FIDO 2.0');
    }

    public function getInstructions()
    {
        return _m('FIDO 2.0 registration');
    }

    public function showForm()
    {
        $form  = new Fido2_regForm($this);
        $form->show();
        return;
    }

    public function doPost()
    {
        $credential = json_decode($this->arg("response-input"));
        if (isset($credential->error)) {
            $this->clientError($credential->error->message);
        }

        $phido2 = new Phido2\Phido2(
            common_config('site', 'name'),
            common_config('site', 'server')
        );
        $phido2_params = $_SESSION['phido2-params'];
        unset($_SESSION['phido2-params']);
        $phido2->validateCredential($phido2_params, $credential);
        $credential->enrolled = time();
        User_fido2_cred::add_user_credential(common_current_user(), $credential);
        
        common_redirect(common_local_url('two_factor_auth_settings'), 303);
    }
}

class Fido2_regForm extends Form
{
    public function id()
    {
        return 'fido2_reg_form';
    }

    public function formClass()
    {
        return 'form_settings';
    }

    public function action()
    {
        return common_local_url('fido2_reg');
    }

    public function formData()
    {
        $script = <<<_END_OF_SCRIPT_
var params = %s;

function fido_callback(response)
{
    document.getElementById('response-input').value = response;
    document.getElementById('fido2_reg_form').submit();
}

if (%s) {
    Phido2.makeCredential(params, fido_callback);
}
_END_OF_SCRIPT_;
        $uriCb = function($nick) {
            return User::getByNickname($nick)->getProfile()->avatarUrl();
        };
        $phido2 = new Phido2\Phido2(
            common_config('site', 'name'),
            common_config('site', 'server'),
            $uriCb
        );
        $user = common_current_user();

        $phido2_params = $phido2->getParams($user->nickname,
            User_fido2_cred::get_user_credentials_list($user));
        $_SESSION['phido2-params'] = $phido2_params;
       
        $this->out->element('p', 'form_guide', 'Activate FIDO 2.0 device to continue...');
        $this->out->hidden('response-input', '');

	$this->script(
            Plugin::staticPath('TwoFactorAuth',
                join('/',
                    array_slice(
                        explode('/',
                                TwoFactorAuthPlugin::libPathGet(
                                    'Phido2',
                                    'Phido2.js')),
                        -3))));        
	$this->inlineScript(sprintf(
            $script,
            $phido2_params,
            $this->isPost() ? "0" : "1"
        ));

    }

    public function formActions()
    {
        $this->out->submit('receive-response', "submit", "hidden");
    }
}
