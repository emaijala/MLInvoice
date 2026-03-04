<?php
/**
 * Invoice Repository.
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
use MLInvoice\Database\Entity\InvoiceState;

/**
 * Invoice Repository.
 *
 * @category MLInvoice
 * @package  MLInvoice\Database
 * @author   Ere Maijala <ere@labs.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://labs.fi/mlinvoice.eng.php
 */
class InvoiceRepository extends EntityRepository
{
    /**
     * Persist an entity.
     *
     * @param Invoice $entity Entity
     *
     * @return void
     */
    public function persistEntity(Invoice $entity): void
    {
        $this->getEntityManager()->persist($entity);
        $this->getEntityManager()->flush();
    }

    /**
     * Find an invoice by its invoice number.
     *
     * @param string $no Invoice number
     *
     * @return ?Invoice
     */
    public function findByInvoiceNo(string $no): ?Invoice
    {
        return $this->findOneBy(['invoiceNo' => $no, 'deleted' => 0]);
    }

    /**
     * Get any invoice refunding another.
     *
     * @param Invoice $invoice Invoice
     *
     * @return ?Invoice
     */
    public function getRefundingInvoice(Invoice $invoice): ?Invoice
    {
        return $this->findOneBy(['refundedInvoice' => $invoice]);
    }

    /**
     * Get the count of recurring invoice templates that need processing.
     *
     * @return int
     */
    public function getCountOfRecurringInvoiceTemplatesNeedingProcessing(): int
    {
        $subQuery = 'SELECT istate.id FROM ' . InvoiceState::class . ' istate WHERE istate.template = 1';
        $dql = 'SELECT count(i) AS cnt FROM ' . Invoice::class . ' i WHERE i.state IN (:ids)'
            . ' AND i.nextIntervalDate <= :threshold';
        return $this->getEntityManager()->createQuery($dql)
            ->setParameters([
                'threshold' => date('Ymd'),
                'ids' => $this->getEntityManager()->createQuery($subQuery)->getResult()
            ])->getSingleScalarResult();
    }

	/**
	 * Get the number of invoiced linked to a template.
	 *
	 * @param int $invoiceId Invoice ID
	 *
	 * @return int
	 */
	public function getCountByTemplateInvoiceId(int $invoiceId): int
	{
		return $this->countBy(['templateInvoice' => $invoiceId]);
	}
}
