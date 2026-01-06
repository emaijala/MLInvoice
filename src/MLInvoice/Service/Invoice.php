<?php
/**
 * Invoice service
 *
 * PHP version 8
 *
 * Copyright (C) Ere Maijala 2025.
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

namespace MLInvoice\Service;

/**
 * Invoice service
 *
 * @category MLInvoice
 * @package  MLInvoice\Base
 * @author   Ere Maijala <ere@labs.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://labs.fi/mlinvoice.eng.php
 */
class Invoice
{
    /**
     * Create an invoice from recurring invoice template
     *
     * Also updates the next interval date of the template
     *
     * @param int $templateId Template ID
     *
     * @return int Invoice ID
     */
    public function createFromTemplate(int $templateId): int
    {
        global $dblink;

        if (!($invoiceData = getInvoice($templateId))) {
            throw new \Exception('RecordNotFound');
        }

        $paymentDays = getPaymentDays($invoiceData['company_id']);

        unset($invoiceData['id']);
        unset($invoiceData['invoice_no']);
        $invoiceData['deleted'] = 0;
        $invoiceData['invoice_date'] = date('Ymd');
        $invoiceData['due_date'] = date(
            'Ymd', mktime(0, 0, 0, date('m'), date('d') + $paymentDays, date('Y'))
        );
        $invoiceData['payment_date'] = null;
        $invoiceData['state_id'] = 1;
        $invoiceData['archived'] = false;
        $invoiceData['refunded_invoice_id'] = null;
        $invoiceData['interval_type'] = 0;
        $invoiceData['next_interval_date'] = null;
        $invoiceData['template_invoice_id'] = $templateId;

        dbQueryCheck('SET AUTOCOMMIT = 0');
        dbQueryCheck('BEGIN');

        try {
            $strQuery = 'INSERT INTO {prefix}invoice(' .
                    implode(', ', array_keys($invoiceData)) . ') ' . 'VALUES (' .
                    str_repeat('?, ', count($invoiceData) - 1) . '?)';

            dbParamQuery($strQuery, array_values($invoiceData), 'exception');
            if (!($invoiceId = mysqli_insert_id($dblink))) {
                throw new \Exception('Could not get ID of the new invoice');
            }
            $this->addRows($invoiceId, $templateId);
            $this->updateNextIntervalDate($templateId);
        } catch (\Exception $e) {
            dbQueryCheck('ROLLBACK');
            dbQueryCheck('SET AUTOCOMMIT = 1');
            throw $e;
        }
        dbQueryCheck('COMMIT');
        dbQueryCheck('SET AUTOCOMMIT = 1');

        return $invoiceId;
    }

    /**
     * Create invoice rows from recurring invoice template
     *
     * Also updates the next interval date of the template
     *
     * @param int $invoiceId  Invoice ID
     * @param int $templateId Template ID
     *
     * @return void
     */
    public function addRowsFromTemplate(int $invoiceId, int $templateId): void
    {
        dbQueryCheck('SET AUTOCOMMIT = 0');
        dbQueryCheck('BEGIN');
        try {
            $this->addRows($invoiceId, $templateId);
            $this->updateNextIntervalDate($templateId);
        } catch (\Exception $e) {
            dbQueryCheck('ROLLBACK');
            dbQueryCheck('SET AUTOCOMMIT = 1');
            throw $e;
        }
        dbQueryCheck('COMMIT');
        dbQueryCheck('SET AUTOCOMMIT = 1');
    }

    /**
     * Add rows to invoice from template
     *
     * @param int $invoiceId  Invoice ID
     * @param int $templateId Template ID
     *
     * @return void
     */
    protected function addRows(int $invoiceId, int $templateId): void
    {
        $invoice = getInvoice($invoiceId);
        // Add rows to the invoice:
        $newRowDate = date('Ymd');
        $strQuery = 'SELECT * FROM {prefix}invoice_row WHERE deleted=0 AND invoice_id=?';
        $rows = dbParamQuery($strQuery, [$templateId], 'exception');
        foreach ($rows as $row) {
            unset($row['id']);
            $row['invoice_id'] = $invoiceId;

            // Take price from product, if any:
            if (null === $row['price'] && $row['product_id'] && ($product = getProduct($row['product_id']))) {
                if ($price = getCustomPrice($invoice['company_id'], $row['product_id'])) {
                    $row['price'] = $price['unit_price'];
                    $row['discount'] = $price['discount'];
                    $row['discount_amount'] = $price['discount_amount'];
                } else {
                    $row['price'] = $product['unit_price'];
                    $row['discount'] = $product['discount'];
                    $row['discount_amount'] = $product['discount_amount'];
                }
                $row['vat'] = $product['vat_percent'];
                $row['vat_included'] = $product['vat_included'];
            }

            if ($row['row_date']) {
                $row['row_date'] = $newRowDate;
            }
            $strQuery = 'INSERT INTO {prefix}invoice_row(' .
                    implode(', ', array_keys($row)) . ') ' . 'VALUES (' .
                    str_repeat('?, ', count($row) - 1) . '?)';
            dbParamQuery($strQuery, $row, 'exception');

            // Update product stock balance
            if (null !== $row['product_id']) {
                updateProductStockBalance(null, $row['product_id'], $row['pcs']);
            }
        }
    }

    /**
     * Update next interval date of invoice or template
     *
     * @param int $id Invoice or template ID
     *
     * @return void
     */
    protected function updateNextIntervalDate(int $id): void
    {
        // Update interval of the template:
        $data = getInvoice($id);
        advanceInvoiceIntervalDate($data);
        updateInvoice($data);
    }
}
