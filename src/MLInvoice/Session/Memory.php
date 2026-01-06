<?php
/**
 * Session Memory Manager
 *
 * PHP version 8
 *
 * Copyright (C) Ere Maijala 2017-2026.
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

namespace MLInvoice\Session;

use Odan\Session\SessionInterface;

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
     * Constructor
     *
     * @param SessionInterface $session Session
     */
    public function __construct(protected SessionInterface $session)
    {
    }

    /**
     * Add an entry to memory
     *
     * @param string $id   Entry ID
     * @param mixed  $data Any data
     *
     * @return void
     */
    public function set(string $id, mixed $data): void
    {
        if (empty($id)) {
            return;
        }

        $memory = $this->session->get('memory') ?? [];
        $memory[$id] = base64_encode(serialize($data));
        $this->session->set('memory', $memory);
    }

    /**
     * Get an entry from memory
     *
     * @param string $id Entry ID
     *
     * @return mixed
     */
    public function get(string $id): mixed
    {
        if (empty($id)) {
            return null;
        }

        $memory = $this->session->get('memory') ?? [];
        return isset($memory[$id]) ? unserialize(base64_decode($memory[$id])) : null;
    }
}
