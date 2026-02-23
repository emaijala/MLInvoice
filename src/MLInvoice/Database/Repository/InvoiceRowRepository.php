<?php
/**
 * InvoiceRow Repository.
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
use MLInvoice\Database\Entity\Invoice;
use MLInvoice\Database\Entity\InvoiceRow;

/**
 * InvoiceRow Repository.
 *
 * @category MLInvoice
 * @package  MLInvoice\Database
 * @author   Ere Maijala <ere@labs.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://labs.fi/mlinvoice.eng.php
 */
class InvoiceRowRepository extends EntityRepository
{
   /**
     * Get next available order number.
     *
     * @param int $invoiceId Invoice ID
     *
     * @return int
     */
    public function getNextAvailableOrderNo(int $invoiceId): int
    {
        $invoice = $this->getEntityManager()->getRepository(Invoice::class)->find($invoiceId);
        $query = $this->getEntityManager()->createQuery(
            'SELECT max(r.orderNo)+1 FROM ' . InvoiceRow::class . ' r WHERE r.deleted=0 AND r.invoice = :invoice'
        )->setParameters(compact('invoice'));
        return (int)$query->getSingleScalarResult();
    }
}
