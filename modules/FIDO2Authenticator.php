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
 
class FIDO2Authenticator extends Authenticator
{
    protected static $name = 'fido2';
    protected $inputLabel = '';
    public $scripts = array();

    public function __construct()
    {
        $this->scripts[] =
            Plugin::staticPath('TwoFactorAuth',
                join('/',
                    array_slice(
                        explode('/',
                                TwoFactorAuthPlugin::libPathGet(
                                    'Phido2', 
                                    'Phido2.js')),
                        -3)));

        $this->scripts[] = array($this, "assertionRequest");
    }
    
    public function title()
    {
        return _m('TITLE', 'Fast IDentity Online 2.0');
    }

    public function validate($response) {
       $assertion = json_decode($response);
        if (isset($assertion->error)) {
           throw new Exception($assertion->error->message);
        }

        $cred = User_fido2_cred::get_user_credential(
            $this->user, 
            $assertion->id
        );
        
        $this->phido2->validateAssertion(
            $this->params, 
            $assertion, 
            $cred->publicKey
        );
        
        unset($_SESSION['phido2-params']);
    }
   
    public function prepare($user, $args) 
    {
        $this->user = $user;
        $this->phido2 = new Phido2\Phido2(
            common_config('site', 'name'),
            common_config('site', 'server')
        );
        if (isset($_SESSION['phido2-params'])) {
            $this->params = json_decode($_SESSION['phido2-params']);
        } else {
            $this->params = $this->phido2->getParams(
                $user->nickname,
                User_fido2_cred::get_user_credentials_list($user)
            );
            $_SESSION['phido2-params'] = json_encode($this->params);
        }
    }
    
    public function assertionRequest($autoRun)
    {
        $script = <<<_END_OF_SCRIPT_
var params = %s;
var autoRun = %s;
function fido2_callback(response)
{
    document.getElementById('response-input').value = response;
    document.getElementById('xauth_form').submit();
}

if (autoRun) {
    Phido2.getAssertion(params, fido2_callback);
}
_END_OF_SCRIPT_;
        return sprintf($script, $this->params, $autoRun);
    }
}
