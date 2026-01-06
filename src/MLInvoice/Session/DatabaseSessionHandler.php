<?php

/**
 * Database Session Handler
 *
 * PHP version 8
 *
 * Copyright (C) Ere Maijala 2026.
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
 * @package  MLInvoice\Session
 * @author   Ere Maijala <ere@labs.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://labs.fi/mlinvoice.eng.php
 */

declare(strict_types = 1);

namespace MLInvoice\Session;

use DateTime;
use DI\Attribute\Inject;
use MLInvoice\Database\Entity\Session;
use MLInvoice\Database\Repository\SessionRepository;
use SessionHandlerInterface;
use SessionUpdateTimestampHandlerInterface;

/**
 * Database Session Handler
 *
 * @category MLInvoice
 * @package  MLInvoice\Session
 * @author   Ere Maijala <ere@labs.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://labs.fi/mlinvoice.eng.php
 */
class DatabaseSessionHandler implements SessionHandlerInterface, SessionUpdateTimestampHandlerInterface
{
    public function __construct(
        protected SessionRepository $sessionRepository
    ) {
    }

    /**
     * Close the session
     *
     * @return bool
     */
    public function close(): bool
    {
        return true;
    }

    /**
     * Destroy a session
     *
     * @param string $id The session ID being destroyed.
     *
     * @return bool
     */
    public function destroy(string $id): bool
    {
        $this->sessionRepository->deleteById($id);
        // Some distributions have gc disabled, need to do it manually:
        $this->gc((int)get_cfg_var('session.gc_maxlifetime'));
        return true;
    }

    /**
     * Cleanup old sessions
     *
     * @param int $maxLifetime Session max lifetime
     *
     * @return int|false
     */
    public function gc(int $maxLifetime): int|false
    {
        $this->sessionRepository->deleteExpired($maxLifetime ?: 900);
        return 1;
    }

    /**
     * Initialize session
     *
     * @param string $path The path where to store/retrieve the session.
     * @param string $name The session name.
     *
     * @return bool
     */
    public function open(
        string $path,
        string $name
    ): bool {
        return true;
    }

    /**
     * Read session data
     *
     * @param string $id The session id to read data for.
     *
     * @return string|false
     */
    public function read(string $id): string|false
    {
        return $this->sessionRepository->findOneById($id)?->getData() ?? '';
    }

    /**
     * Write session data
     *
     * @param string $id The session id.
     * @param string $data Session data.
     *
     * @return bool
     */
    public function write(
        string $id,
        string $data
    ): bool {
        if (!($session = $this->sessionRepository->findOneById($id))) {
            $session = $this->sessionRepository->createEntity();
            $session->setId($id);
        }
        $session->setSessionTimestamp(new DateTime());
        $session->setData($data);
        $this->sessionRepository->persistEntity($session);
        return true;
    }

    /**
     * Validate session id
     *
     * @param string $id The session id
     *
     * @return bool
     */
    public function validateId(string $id): bool
    {
        return (bool)$this->sessionRepository->findOneById($id);
    }

    /**
     * Update timestamp of a session
     *
     * @param string $id The session id
     * @param string $data Session data

     * @return bool
     */
    public function updateTimestamp(string $id, string $data): bool
    {
        if ($session = $this->sessionRepository->findOneById($id)) {
            $session->setSessionTimestamp(new DateTime());
            $this->sessionRepository->persistEntity($session);
            return true;
        }
        return false;
    }
}