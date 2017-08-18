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


class Auth_pickerAction extends FormAction
{
    protected $needLogin = false;

    public function title()
    {
        return 'Secondary Login Authentication';
    }
    
    public function prepare($args)
    {
        parent::prepare($args);
        $this->test = !isset($_SESSION['auth-stage1-user']);
        if (!$this->test && !$this->boolean('no_redirect')) {
            $user = $_SESSION['auth-stage1-user'];
            $default = User_2fa_data::get_user_default($user);
            if ('none' != $default) {
                common_redirect(common_local_url('xauth', array(
                    'authenticator' => $default
                )));
                return false;
            }
        }
        return true;
    }

    public function showContent()
    { 
        $this->elementStart('p');
        $this->text( 
            "Two-factor authentication is required for your account.\n"
        );
        $this->elementEnd('p');
        $this->elementStart('p');
        $this->text(
            "Select a secondary authentication mechanism to continue:\n"
        );
        $this->elementEnd('p');
        $this->elementStart('ul');
        $this->elementStart('li');
        $this->element(
            'a', 
            array('href' => common_local_url("xauth", array('authenticator' => 'fido2'))),
            "Fast IDentity Online (FIDO) 2.0"
        );
        $this->elementEnd('li');
            $this->elementStart('li');
            $this->element(
                'a', 
                array('href' => common_local_url("xauth", array('authenticator' => 'totp'))),
                "Time-based One Time Password (TOTP)"
            );
            $this->elementEnd('li');

        
            $this->elementStart('li');
            $this->element(
                'a',
                array('href' => common_local_url('xauth', array('authenticator' => 'u2f'))),
                "Universal 2-factor Authentication (U2F)"
            );
            $this->elementEnd('li');
        
        $this->elementStart('li');
        $this->element(
            'a',
            array('href' => common_local_url('xauth', array('authenticator' => 'backup'))),
            "Backup Authentication"
        );
        $this->elementEnd('li');
        $this->elementEnd('ul'); 
    }
    public function showPrimaryNav() { if ($this->test) parent::showPrimaryNav(); }
    public function showNoticeForm() { }
    public function showLocalNav() { if ($this->test) parent::showLocalNav(); }
    public function showAside() {  }
}
