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

class Certificate {
    static public function wrap_pem($txt)
    {
        $cert_txt = "-----BEGIN CERTIFICATE-----\n";
        $lines = str_split($txt, 64);
        foreach ($lines as $line) {
            $cert_txt .= $line . "\n";
        }
        $cert_txt .= "-----END CERTIFICATE-----\n";
        return $cert_txt;
    }

    static public function parse_certificate($txt)
    {
        if (preg_match('/^-----BEGIN CERTIFICATE-----/', $txt)) {
            $cert_txt = $txt;
        } else {
            $cert_txt = self::wrap_pem($txt);
        }
        return openssl_x509_parse($cert_txt);
    }
}
