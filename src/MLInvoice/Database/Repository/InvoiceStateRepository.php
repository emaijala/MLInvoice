<?php
/**
 * InvoiceState Repository.
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

/**
 * InvoiceState Repository.
 *
 * @category MLInvoice
 * @package  MLInvoice\Database
 * @author   Ere Maijala <ere@labs.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://labs.fi/mlinvoice.eng.php
 */
class InvoiceStateRepository extends EntityRepository
{
    /**
     * Find invoice state by its name.
     *
     * @param string $name
     * @return ?InvoiceState
     */
    public function findByName(string $name): ?InvoiceState
    {
        return $this->findOneBy(['name' => $name]);
    }

    /**
     * Return all non-deleted invoice states.
     *
     * @return InvoiceState[]
     */
    public function findAllNonDeleted(): array
    {
        return $this->findBy(['deleted' => false], ['orderNo' => 'ASC']);
    }

    /**
     * Return all invoice states ordered by `order_no`.
     *
     * @return InvoiceState[]
     */
    public function findAllOrdered(): array
    {
        return $this->findBy([], ['orderNo' => 'ASC']);
    }

    /**
     * Return all offer states.
     *
     * @return InvoiceState[]
     */
    public function findAllOfferStates(): array
    {
        return $this->findBy(['offer' => true], ['orderNo' => 'ASC']);
    }
}
