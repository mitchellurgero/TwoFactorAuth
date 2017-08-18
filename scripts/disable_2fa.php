#!/usr/bin/env php
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

define('INSTALLDIR', realpath(dirname(__FILE__) . '/../../..'));

$helptext = <<<END_OF_PASSWORD_HELP
disable_2fa.php <username>

Disables two-factor authentication login requirement for user <username>

END_OF_PASSWORD_HELP;

require_once INSTALLDIR.'/scripts/commandline.inc';

if (count($args) < 1) {
    show_help();
}

$nickname = $args[0];

try {
    $user = User::getByNickname($nickname);
    User_2fa_data::set_user_2fa_req($user, false);
} catch (NoSuchUserException $e) {
    print $e->getMessage();
    exit(1);
}

print "Two-factor authentication login requirement for user '$nickname' disabled.\n";
