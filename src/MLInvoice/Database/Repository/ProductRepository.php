<?php
/**
 * Product Repository.
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
use MLInvoice\Database\Entity\InvoiceRow;
use MLInvoice\Database\Entity\Product;

/**
 * Product Repository.
 *
 * @category MLInvoice
 * @package  MLInvoice\Database
 * @author   Ere Maijala <ere@labs.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://labs.fi/mlinvoice.eng.php
 */
class ProductRepository extends EntityRepository
{
    /**
     * Update product stock balance for an invoice row
     *
     * @param ?InvoiceRow $invoiceRow Invoice row for returning balance to old product, if any
     * @param Product     $product    Product ID
     * @param ?string     $count      Count of items
     *
     * @return void
     */
    function updateStockBalance(?InvoiceRow $invoiceRow, Product $product, ?string $count)
    {
        // Add any old balance to old product:
        if ($invoiceRow) {
            if ($oldProduct = $invoiceRow->getProduct()) {
                $dql = 'UPDATE ' . Product::class . ' p SET p.stock_balance = IFNULL(stock_balance, 0)+:count'
                    . ' WHERE p.id=:id';
                $query = $this->getEntityManager()->createQuery($dql)
                    ->setParameter('count', $invoiceRow->getPcs())
                    ->setParameter('id', $oldProduct->getId());
                $query->execute();
            }
        }
        // Deduct from new product:
        if ($product) {
            $dql = 'UPDATE ' . Product::class . ' p SET p.stock_balance = IFNULL(stock_balance, 0)-:count'
                . ' WHERE p.id=:id';
            $query = $this->getEntityManager()->createQuery($dql)
                ->setParameter('count', $count)
                ->setParameter('id', $product->getId());
            $query->execute();
        }
    }
}
