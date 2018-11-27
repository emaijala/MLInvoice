<?php
/**
 * Order confirmation email
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
require_once 'invoice_printer_order_confirmation.php';
require_once 'invoice_printer_email_trait.php';

/**
 * Order confirmation email
 *
 * @category MLInvoice
 * @package  MLInvoice\Base
 * @author   Ere Maijala <ere@labs.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://labs.fi/mlinvoice.eng.php
 */
class InvoicePrinterOrderConfirmationEmail extends InvoicePrinterOrderConfirmation
{
    use InvoicePrinterEmailTrait;

    /**
     * Get default message body
     *
     * @return string
     */
    protected function getDefaultBody()
    {
        return isset($this->senderData['order_confirmation_email_body'])
            ? $this->senderData['order_confirmation_email_body'] : '';
    }

    /**
     * Get default subject
     *
     * @return string
     */
    protected function getDefaultSubject()
    {
        return isset($this->senderData['order_confirmation_email_subject'])
            ? $this->senderData['order_confirmation_email_subject'] : '';
    }
}
