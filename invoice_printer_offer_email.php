<?php
/**
 * Offer email
 *
 * PHP version 5
 *
 * Copyright (C) 2010-2018 Ere Maijala
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
require_once 'invoice_printer_offer.php';
require_once 'invoice_printer_email_trait.php';

/**
 * Offer email
 *
 * @category MLInvoice
 * @package  MLInvoice\Base
 * @author   Ere Maijala <ere@labs.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://labs.fi/mlinvoice.eng.php
 */
class InvoicePrinterOfferEmail extends InvoicePrinterOffer
{
    use InvoicePrinterEmailTrait;

    /**
     * Get default message body
     *
     * @return string
     */
    protected function getDefaultBody()
    {
        return isset($this->senderData['offer_email_body'])
            ? $this->senderData['offer_email_body'] : '';
    }

    /**
     * Get default subject
     *
     * @return string
     */
    protected function getDefaultSubject()
    {
        return isset($this->senderData['offer_email_subject'])
            ? $this->senderData['offer_email_subject'] : '';
    }

    /**
     * Method that is called when the invoice has been sent
     *
     * @return void
     */
    protected function emailSent()
    {
        $rows = dbParamQuery(
            'SELECT invoice_open FROM {prefix}invoice_state WHERE id=?',
            [$this->invoiceData['state_id']]
        );
        $open = $rows && $rows[0]['invoice_open'];
        if ($open) {
            $res = dbQueryCheck(
                'SELECT id FROM {prefix}invoice_state WHERE invoice_open=1'
                . ' AND invoice_offer=1 AND invoice_offer_sent=1'
                . ' ORDER BY order_no'
            );
            $stateId = dbFetchValue($res);
            // Mark invoice offered
            if (null !== $stateId) {
                dbParamQuery(
                    'UPDATE {prefix}invoice SET state_id=? WHERE id=?',
                    [$stateId, $this->invoiceId]
                );
            }
        }
    }
}
