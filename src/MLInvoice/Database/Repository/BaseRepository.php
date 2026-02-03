<?php
/**
 * (Billing) Base repository.
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
use MLInvoice\Database\Entity\Base;

/**
 * (Billing) Base repository.
 *
 * @category MLInvoice
 * @package  MLInvoice\Database
 * @author   Ere Maijala <ere@labs.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://labs.fi/mlinvoice.eng.php
 */
class BaseRepository extends EntityRepository
{
    /**
     * Get a non-deleted Base entity by ID.
     *
     * @param int $id ID
     *
     * @return ?Base
     */
    public function getNonDeletedById(int $id): ?Base
    {
        return $this->findOneBy(['id' => $id, 'deleted' => false]);
    }

    /**
     * Return all non-deleted active bases
     *
     * @return Base[]
     */
    public function findAllNonDeletedActive(): array
    {
        return $this->findBy(['deleted' => false, 'inactive' => false], ['name' => 'ASC', 'id' => 'ASC']);
    }
}
