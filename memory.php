<?php
/**
 * Session Memory Manager
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
require_once 'sessionfuncs.php';

/**
 * Session Memory Manager
 *
 * Stores data in the session in a format that any PHP session handler can process.
 *
 * @category MLInvoice
 * @package  MLInvoice\Base
 * @author   Ere Maijala <ere@labs.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://labs.fi/mlinvoice.eng.php
 */
class Memory
{
    /**
     * Add an entry to memory
     *
     * @param string $id   Entry ID
     * @param mixed  $data Any data
     *
     * @return void
     */
    public static function set($id, $data)
    {
        if (empty($id)) {
            return;
        }
        if (!session_id()) {
            session_start();
        }

        $_SESSION['memory'][$id] = base64_encode(serialize($data));
    }

    /**
     * Get an entry from memory
     *
     * @param string $id Entry ID
     *
     * @return mixed
     */
    public static function get($id)
    {
        if (empty($id)) {
            return null;
        }

        if (!session_id()) {
            session_start();
        }

        return isset($_SESSION['memory'][$id])
            ? unserialize(base64_decode($_SESSION['memory'][$id])) : null;
    }
}
