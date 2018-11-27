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
require_once 'config.php';

use phpseclib\Crypt\AES;

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
        if (!defined('_ENCRYPTION_KEY_')) {
            throw new Exception('_ENCRYPTION_KEY_ must be defined in config.php');
        }
        if (strlen(_ENCRYPTION_KEY_) < 32) {
            throw new Exception('_ENCRYPTION_KEY_ in config.php too short');
        }
        $this->cipher = new AES();
        $this->cipher->setKey(_ENCRYPTION_KEY_);
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
