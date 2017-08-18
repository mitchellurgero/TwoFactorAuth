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

class Two_factor_auth_settingsAction extends SettingsAction
{
    public function title()
    {
        return _m('TITLE', 'Two-Factor Authentication');
    }

    public function showScripts()
    {
        parent::showScripts();
        $this->script(Plugin::staticPath('TwoFactorAuth', 'js/2fa_settings.js'));
        return true;
    }

    public function showForm()
    {
        $form  = new Two_factor_auth_settingsForm($this);
        $form->show();
        return;
   }

    protected function doPost()
    {
        $user = common_current_user();
        if ($this->arg('save')) {
            User_2fa_data::set_user_2fa_req(
                $user, 
                $this->boolean('required')
            );
            User_2fa_data::set_user_default(
                $user,
                $this->arg('auth_default')
            );
        } else if ($this->arg('reset_totp_key')) {
            User_2fa_data::reset_totp_secret($user);            
        }
    }
}

class Two_factor_auth_settingsForm extends Form
{
    public function id()
    {
        return 'two_factor_auth_settings';
    }

    public function formClass()
    {
        return 'form_settings';
    }

    public function action()
    {
        return common_local_url('two_factor_auth_settings');
    }
    
    private function showAppPwSettings($user)
    {
        $this->elementStart('fieldset', array('id' => 'app_pw'));
        $this->element('legend', null, 'Application Passwords');

        $apps = User_2fa_app_pw::get_user_apps($user);

        if (count($apps) == 0) {
            $this->out->elementStart('h5');
            $this->out->element('span', null, 'Authorized applications:');
            $this->out->element('i', null, 'None');
            $this->out->elementEnd('h5');
        } else {
            $this->out->element('h5', null, 'Authorized applications:');
            $this->elementStart('table', array('style'=>
                'margin-left:auto;margin-right:auto;width:80%;'));
            foreach ($apps as $name => $created) {
                $this->elementStart('tr');
                $this->elementStart('td');
                $this->element('div', array('class' => 'app-name'), $name);
                $this->element('div', null, 
                    'enrolled: ' . common_exact_date("@" . $created));
                $this->elementEnd('td');
                $this->elementStart('td');
                $this->element('a', 
                    array(
                        'href' => common_local_url(
                                    'app_pw', 
                                    array('verb' => 'revoke')),
                        'class' => 'app-revoke'), 
                    'revoke'
                );
                $this->elementEnd('td');
                $this->elementEnd('tr');
            }
            $this->elementStart('tr'); 
            $this->element('td'); $this->element('td'); $this->element('td');
            $this->elementEnd('tr');
            $this->elementEnd('table');
        }

        $this->out->elementStart('a', array(
            'href' => common_local_url(
                'app_pw', 
                array('verb' => 'authorize')),
            'id' => 'new_app_pw',
        ));
        $this->out->element('input', array(
            'type'=>'button',
            'class'=>'submit',
            'value'=>_m('BUTTON', 'Add Application'),
        ));
        $this->out->elementEnd('a');
        $this->elementEnd('fieldset');
    }
    
    private function showBackupCodeSettings($user)
    {
        $this->elementStart('fieldset', array('id' => 'backup_auth'));
        $this->element('legend', null, 'Backup Authentication');

        $this->out->elementStart('div'); 
        $this->out->element('h5', null, 
            'Codes available: ' . User_2fa_data::available_backup_codes($user)
        );
        $this->out->elementEnd('div');
        $this->elementStart('a', array(
            'href' => common_local_url('backup_codes', array('verb'=>'show')),
            'id'=>'show_backup_codes',
        ));
        $this->element('input', array(
            "type"=>"button",
            "class"=>"submit",
            "value"=>_m('BUTTON', "Show Codes"),
        ));
        $this->elementEnd('a');

        $this->elementStart('a', array(
            'href' => common_local_url('backup_codes', array('verb'=>'reset')),
            'id' => 'reset_backup_codes',
        ));
        $this->element('input', array(
            'type'=>'button',
            'class'=>'submit',
            'value'=>_m('BUTTON', 'Reset Codes'),
        ));
        $this->elementEnd('a');

        $this->elementEnd('fieldset');
    }

    private function showFido2Settings($user)
    {   
        $this->out->elementStart('fieldset', array('id' => 'fido2_settings'));
        $this->out->element('legend', null, 'FIDO 2.0 Authentication');
        $creds = User_fido2_cred::get_user_credentials_list($user);
        
        if (count($creds) == 0) {
            $this->out->elementStart('h5');
            $this->out->element('span', null, 'Registered credentials:');
            $this->out->element('i', null, 'None');
            $this->out->elementEnd('h5');
            $this->out->element('p');
        } else {
            $this->out->element('h5', null, 'Registered credentials:');
            $this->out->elementStart('table', array('style'=>
                'margin-left:auto;margin-right:auto;width:80%;'));
            foreach ($creds as $idx => $c_info) {
                $c_id = $c_info['id'];
                $c = User_fido2_cred::get_user_credential($user, $c_id);
                $this->out->elementStart('tr');
                $this->out->elementStart('td');
                $this->out->element('div', '', "ID=" . $c_id);
                $this->out->element('div', '', 
                    'enrolled: ' . common_exact_date("@" . $c->enrolled));
                $this->out->elementEnd('td');
                $this->out->elementStart('td');
                $this->out->element('a', array(
                    'href' => common_local_url(
                                "fido2_cred",
                                array('id' => $c_id, "verb" => "detail")
                            ),
                    'class' => 'fido2-details-link'), 
                'details');
                $this->out->element('br');
                $this->out->element('a', array(
                    'href' => common_local_url(
                                "fido2_cred",
                                array('id' => $c_id, "verb" => "revoke")
                            ),
                    'class' => 'fido2-revoke-link'), 
                    'revoke');
                $this->out->elementEnd('td');
                $this->out->elementEnd('tr');
            }
            $this->out->elementStart('tr'); 
            $this->out->element('td');
            $this->out->element('td');
            $this->out->elementEnd('tr');
            $this->out->elementEnd('table');
        }
        $this->out->elementStart('a', array(
            'href' => common_local_url('fido2_reg')
        ));
        $this->out->element('input', array(
            'type'=>'button',
            'id'=>'new_fido2_cred', 
            'class'=>'submit',
            'value'=>_m('BUTTON', 'Add FIDO 2.0 credential'),
        ));
        $this->out->elementEnd('a');
        
        $this->out->elementEnd('fieldset');
    }

    private function showTotpSettings($user)
    {
        $this->out->elementStart('fieldset', array('id' => 'totp_settings'));
        $this->out->element('legend', null, 'Time-based One-time Password');
        
        $this->out->elementStart('div');
        $this->out->element('input', array(
            "type" => "button", 
            "class"=>"submit", 
            "id"=>"show_totp_qr",
            "value"=>_m('BUTTON', 'Show QR Code')
        ));
        $this->out->element('input', array(
            "type" => "button",
            "class" => "submit",
            "id" => "show_totp_key",
            "value" => _m('BUTTON', 'Show Key')
        ));
        $this->out->element('input', array(
            "type" => "button",
            "class" => "submit",
            "id" => "reset_totp_key",
            "value" => _m('BUTTON', 'Reset Key')
        ));
        $this->out->elementEnd('div');

        $this->out->elementEnd('fieldset');
    }
    
    private function showU2fSettings($user)
    {    
        $this->out->elementStart('fieldset', array('id' => 'u2f_settings'));
        $this->out->element('legend', null, 'Universal 2-Factor Authentication');
        $devices = User_u2f_device::get_user_devices($user);
        if (count($devices) == 0) {
            $this->out->elementStart('h5');
            $this->out->element('span', null, 'Registered devices:');
            $this->out->element('i', null, 'None');
            $this->out->elementEnd('h5');
        } else {
            $this->out->element('h5', null, 'Registered devices:');
            $this->out->elementStart('table', array('style'=>
                'margin-left:auto;margin-right:auto;width:80%;'));
            foreach ($devices as $d) {
                $this->out->elementStart('tr');
                $this->out->elementStart('td');
                $this->out->element('div', '', "CN=" . Certificate::parse_certificate($d->certificate)['subject']['CN']);
                $this->out->element('div', '', 
                    'enrolled: ' . common_exact_date("@" . $d->enrolled));
                $this->out->elementEnd('td');
                $this->out->elementStart('td');
                $this->out->element('a', array(
                    'href' => common_local_url(
                                "u2f_device",
                                array('kh' => $d->keyHandle, "verb" => "detail")
                            ),
                    'class' => 'u2f-details-link'), 
                'details');
                $this->out->element('br');
                $this->out->element('a', array(
                    'href' => common_local_url(
                                "u2f_device",
                                array('kh' => $d->keyHandle, "verb" => "revoke")
                            ),
                    'class' => 'u2f-revoke-link'), 
                    'revoke');
                $this->out->elementEnd('td');
                $this->out->elementEnd('tr');
            }
            $this->out->elementStart('tr'); 
            $this->out->element('td');
            $this->out->element('td');
            $this->out->elementEnd('tr');
            $this->out->elementEnd('table');
        }
        
        $this->out->elementStart('a', array(
            'href' => common_local_url('u2f_reg')
        ));
        $this->out->element('input', array(
            'type'=>'button',
            'id'=>'new_u2f_dev', 
            'class'=>'submit',
            'value'=>_m('BUTTON', 'Add U2F device'),
        ));
        $this->out->elementEnd('a');
        
        $this->out->elementEnd('fieldset');
    }
    
    public function formData()
    {
        $user = common_current_user();
        $requirement = User_2fa_data::check_user_2fa_req($user);

        $this->elementStart('fieldset', array('id' => 'login_requirement'));
        $this->element('legend', null, 'Login');
        $this->out->elementStart("ul", array('class' => 'form_data'));
        $this->li();
        $this->out->checkbox(
            'required', 
            _('Require secondary authentication upon login'), 
            $requirement
        );
        $this->unli();
        $this->li();
        $this->out->dropdown(
            'auth_default',
            'Default provider',
            array('none' => 'None') + TwoFactorAuthPlugin::getModuleTitles('xauth'),
            null,
            false,
            User_2fa_data::get_user_default($user)
        );
        $this->unli();
        $this->out->elementEnd("ul");
        $this->out->submit('save', _m('BUTTON', 'Save'));
        $this->elementEnd('fieldset');


        $this->showAppPwSettings($user);
        $this->showBackupCodeSettings($user);
        $this->showFido2Settings($user);
        $this->showTotpSettings($user);
        $this->showU2fSettings($user);
        
        
    }
}
