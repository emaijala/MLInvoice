<?php
/**
 * HMAC utilities
 *
 * PHP version 5
 *
 * Copyright (C) Ere Maijala 2017.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category MLInvoice
 * @package  MLInvoice\Base
 * @author   Ere Maijala <ere@labs.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://labs.fi/mlinvoice.eng.php
 */
require_once 'settings.php';

/**
 * HMAC utility class
 *
 * @category MLInvoice
 * @package  MLInvoice\Base
 * @author   Ere Maijala <ere@labs.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://labs.fi/mlinvoice.eng.php
 */
class HMAC
{
    /**
     * Create a HMAC
     *
     * @param string $key  Key (64 chars preferred)
     * @param string $data Data string
     *
     * @return string
     * @author josefkoh at hotmail dot com
     */
    public static function hmacSha1($key, $data)
    {
        // Adjust key to exactly 64 bytes
        if (strlen($key) > 64) {
            $key = str_pad(sha1($key, true), 64, chr(0));
        }
        if (strlen($key) < 64) {
            $key = str_pad($key, 64, chr(0));
        }

        // Outter and Inner pad
        $opad = str_repeat(chr(0x5C), 64);
        $ipad = str_repeat(chr(0x36), 64);

        // Xor key with opad & ipad
        for ($i = 0; $i < strlen($key); $i++) {
            $opad[$i] = $opad[$i] ^ $key[$i];
            $ipad[$i] = $ipad[$i] ^ $key[$i];
        }

        return sha1($opad . sha1($ipad . $data, true));
    }

    /**
     * Create a HMAC for a given array of strings
     *
     * @param array $data Array of strings
     *
     * @return string
     */
    public static function createHMAC($data)
    {
        $key = getSetting('hmac_key');
        if (!$key) {
            $key = base64_encode(random_bytes(64));
            dbParamQuery(
                'INSERT INTO {prefix}settings (name, value) VALUES (?, ?)',
                ['hmac_key', $key]
            );
        }
        $hmac = '';
        foreach ($data as $item) {
            $hmac = self::hmacSha1($key, $hmac . $item);
        }
        return $hmac;
    }
}
