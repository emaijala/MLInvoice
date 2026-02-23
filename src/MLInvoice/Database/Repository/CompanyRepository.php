<?php
/**
 * Company Repository.
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
use MLInvoice\Database\Entity\Company;

/**
 * Company Repository.
 *
 * @category MLInvoice
 * @package  MLInvoice\Database
 * @author   Ere Maijala <ere@labs.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://labs.fi/mlinvoice.eng.php
 */
class CompanyRepository extends EntityRepository
{
    /**
     * Get payment days for a company.
     *
     * @param ?int $companyId Company ID
     *
     * @return ?int
     */
    public function getPaymentDays(?int $companyId): ?int
    {
        if ($companyId) {
            $company = $this->find($companyId);
            if ($days = $company?->getPaymentDays()) {
                return $days;
            }
        }
        return null;
    }

    /**
     * Get next available customer number.
     *
     * @return int
     */
    public function getNextAvailableCustomerNumber(): int
    {
        $query = $this->getEntityManager()->createQuery(
            'SELECT max(c.customerNo)+1 FROM ' . Company::class . ' c WHERE c.deleted=0'
        );
        return (int)$query->getSingleScalarResult();
    }
}
