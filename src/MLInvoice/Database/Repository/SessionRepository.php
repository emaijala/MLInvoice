<?php
/**
 * Session Repository.
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
 * @package  MLInvoice\Database
 * @author   Ere Maijala <ere@labs.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://labs.fi/mlinvoice.eng.php
 */

namespace MLInvoice\Database\Repository;

use Doctrine\ORM\EntityRepository;
use MLInvoice\Database\Entity\Session;

/**
 * Session Repository.
 *
 * @category MLInvoice
 * @package  MLInvoice\Database
 * @author   Ere Maijala <ere@labs.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://labs.fi/mlinvoice.eng.php
 */
class SessionRepository extends EntityRepository
{
    /**
     * Create a new entity.
     *
     * @return Session
     */
    public function createEntity(): Session
    {
        return new Session();
    }

    /**
     * Persist an entity.
     *
     * @param Session $entity Entity
     *
     * @return void
     */
    public function persistEntity(Session $entity): void
    {
        $this->getEntityManager()->persist($entity);
        $this->getEntityManager()->flush();
    }

    /**
     * Find an entity by ID.
     *
     * @param string $id Entity ID
     *
     * @return ?Session
     */
    public function findOneById(string $id): ?Session
    {
        return $this->find($id);
    }

    /**
     * Delete a session by ID.
     *
     * @param string $id Session ID
     *
     * @return void
     */
    public function deleteById(string $id): void
    {
        $dql = 'DELETE ' . Session::class . ' s WHERE s.id = :id';
        $this->getEntityManager()->createQuery($dql)
            ->setParameter('id', $id)
            ->execute();
    }

    /**
     * Delete expired sessions.
     *
     * @param int $maxLifetime Session max lifetime
     *
     * @return void
     */
    public function deleteExpired(int $maxLifetime): void
    {
        $dql = 'DELETE ' . Session::class . ' s WHERE s.sessionTimestamp < :threshold';
        $this->getEntityManager()->createQuery($dql)
            ->setParameter('threshold', date('Y-m-d H:i:s', time() - $maxLifetime))
            ->execute();
    }
}
