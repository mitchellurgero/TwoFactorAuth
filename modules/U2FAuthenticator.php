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
 
class U2FAuthenticator extends Authenticator
{
    protected static $name = 'u2f';
    protected $inputLabel = '';
    public $scripts = array();
    
    public function __construct()
    {
        $this->scripts[] = Plugin::staticPath('TwoFactorAuth', 'js/extlib/u2f-api.js');
        $this->scripts[] = array($this, "signRequest");
    }

    public function title()
    {
        return _m('TITLE', 'Universal Two-factor Authentication');
    }

    public function validate($responseData) 
    {
        $response = json_decode($responseData);
        if (isset($response->errorCode)) {
            throw new Exception($response->errorCode);
        }
        
        $updated = $this->u2f->doAuthenticate(
            $this->requests, 
            $this->registrations, 
            $response);
            
        User_u2f_device::update_counter($this->user, $updated);
        unset($_SESSION['u2f-requests']);
    }
   
    public function prepare($user, $args) 
    {
        if (!isset($this->user)) {
            $this->user = $user;
            $this->u2f = new u2flib_server\U2F(
                "https://" . common_config('site', 'server')
            );
            $this->registrations = User_u2f_device::get_user_devices($user);
            if (isset($_SESSION['u2f-requests'])) {
                $this->requests = json_decode($_SESSION['u2f-requests']);
            } else {
                $this->requests = $this->u2f->getAuthenticateData(
                    $this->registrations
                );
                $_SESSION['u2f-requests'] = json_encode($this->requests);
            }
        }
    }
    
    public function signRequest($autoRun)
    {
        $script = <<<_END_OF_SCRIPT_
var signRequests = %s;
var autoRun = %s;
function u2f_callback(deviceResponse)
{
    document.getElementById('response-input').value = JSON.stringify(deviceResponse);
    document.getElementById('xauth_form').submit();
}

if (autoRun) {
    u2f.sign(signRequests, u2f_callback);
}
_END_OF_SCRIPT_;
        return sprintf($script, json_encode($this->requests), $autoRun);
    }
    
    public function showForm($out)
    {
        if (!$out->isPost()) {
            $out->elementStart('div');
            $out->element('p', null, 'Activate your U2F device to contine...');
            $out->elementEnd('div');
        }
        parent::showForm($out);
    }
}
