<?php
/**
 * Encryption utilities
 *
 * PHP version 5
 *
 * Copyright (C) Ere Maijala 2018.
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

use phpseclib\Crypt\AES;
use phpseclib\Crypt\Random;

/**
 * Encryption utility class
 *
 * @category MLInvoice
 * @package  MLInvoice\Base
 * @author   Ere Maijala <ere@labs.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://labs.fi/mlinvoice.eng.php
 */
class Crypt
{
    /**
     * Cipher class
     *
     * @var AES
     */
    protected $cipher;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->cipher = new AES();
        $key = getSetting('encryption_key');
        if (!$key) {
            $key = base64_encode(random_bytes(32));
            dbParamQuery(
                'INSERT INTO {prefix}settings (name, value) VALUES (?, ?)',
                ['encryption_key', $key]
            );
        }
        $this->cipher->setKey($key);
    }

    /**
     * Decrypt a string
     *
     * @param string $string String to decrypt
     *
     * @return string
     */
    public function decrypt($string)
    {
        $result = $this->cipher->decrypt(base64_decode($string));
        if (false === $result) {
            throw new Exception('Failed to decrypt string');
        }
        return $result;
    }

    /**
     * Encrypt a string
     *
     * @param string $string String to encrypt
     *
     * @return string
     */
    public function encrypt($string)
    {
        return base64_encode($this->cipher->encrypt($string));
    }
}
