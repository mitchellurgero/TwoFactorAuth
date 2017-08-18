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


class U2f_regAction extends SettingsAction
{
    public function title()
    {
        return _m('TITLE', 'U2F');
    }

    public function getInstructions()
    {
        return _m('U2F registration');
    }

    public function showForm()
    {
        $form  = new U2f_regForm($this);
        $form->show();
        return;
    }

    public function doPost()
    {
        $app_id = "https://" . common_config('site', 'server');
        $u2f = new u2flib_server\U2F($app_id);

        $user = common_current_user();
        $challenge_msg = $_SESSION['u2f-reg-data'];
        $challenge = json_decode($challenge_msg);

        $response_msg = $this->arg("response-input");
        $response = json_decode($response_msg);

        $result = $u2f->doRegister($challenge, $response);
        $result->applicationId = $app_id;
        $result->enrolled = time();
        User_u2f_device::add_user_device($user, $result);

        common_redirect(common_local_url('two_factor_auth_settings'), 303);
    }
}

class U2f_regForm extends Form
{
    public function id()
    {
        return 'u2f_reg_form';
    }

    public function formClass()
    {
        return 'form_settings';
    }

    public function action()
    {
        return common_local_url('u2f_reg');
    }

    public function formData()
    {
        $u2f = new u2flib_server\U2F(
            "https://" . common_config('site', 'server')
        );
        $script = <<<_END_OF_SCRIPT_

var challenge = %s;
var devices = %s;
u2f.register([challenge], devices,
    function(deviceResponse) {
      document.getElementById('response-input').value = JSON.stringify(deviceResponse);
      document.getElementById('u2f_reg_form').submit();
    }
);
_END_OF_SCRIPT_;


        $user = common_current_user(); 
        list($challenge, $sigs) = $u2f->getRegisterData(User_u2f_device::get_user_devices($user));
        $challenge_msg = json_encode($challenge);
        $_SESSION['u2f-reg-data'] = $challenge_msg;
        $devices_msg = json_encode($sigs);

        $this->out->element('p', 'form_guide', 'Activate U2F device to continue...');
        $this->out->hidden('response-input', '');

        $this->script(Plugin::staticPath('TwoFactorAuth', 'js/extlib/u2f-api.js'));
        $this->inlineScript(sprintf(
            $script,
            $challenge_msg,
            $devices_msg
        ));

    }

    public function formActions()
    {
        $this->out->submit('receive-response', "submit", "hidden");
    }
}
