<?php
/**
 * Order confirmation PDF
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
require_once 'invoice_printer_base.php';
require_once 'htmlfuncs.php';
require_once 'miscfuncs.php';

/**
 * Order confirmation PDF
 *
 * @category MLInvoice
 * @package  MLInvoice\Base
 * @author   Ere Maijala <ere@labs.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://labs.fi/mlinvoice.eng.php
 */
class InvoicePrinterOrderConfirmation extends InvoicePrinterBase
{
    /**
     * Initialize printing
     *
     * @param int    $invoiceId            Invoice ID
     * @param array  $printParameters      Print control parameters
     * @param string $outputFileName       File name template
     * @param array  $senderData           Sender record
     * @param array  $recipientData        Recipient record
     * @param array  $invoiceData          Invoice record
     * @param array  $invoiceRowData       Invoice row records
     * @param array  $recipientContactData Recipient's contact records
     * @param int    $dateOverride         Date override for invoice date
     * @param int    $printTemplateId      Print template ID
     * @param bool   $authenticated        Whether the user is authenticated
     *
     * @return void
     */
    public function init($invoiceId, $printParameters, $outputFileName, $senderData,
        $recipientData, $invoiceData, $invoiceRowData, $recipientContactData,
        $dateOverride, $printTemplateId, $authenticated
    ) {
        parent::init(
            $invoiceId, $printParameters, $outputFileName, $senderData,
            $recipientData, $invoiceData, $invoiceRowData, $recipientContactData,
            $dateOverride, $printTemplateId, $authenticated
        );
        $this->printStyle = 'order_confirmation';

        $this->columnDefs['date']['heading'] = 'invoice::OrderConfirmationRowDate';
        $this->columnDefs['totalvatless']['heading'] = 'invoice::RowTotal';
        $this->columnDefs['vatpercent']['visible'] = false;
        $this->columnDefs['vat']['visible'] = false;
        $this->columnDefs['total']['visible'] = false;
    }

    /**
     * Main method for printing
     *
     * @return void
     */
    public function printInvoice()
    {
        if ($this->senderData['bank_iban'] && $this->senderData['bank_swiftbic']) {
            $bank = $this->senderData['bank_iban'] . '/' .
                 $this->senderData['bank_swiftbic'];
        } else {
            $this->senderData['bank_iban'] . $this->senderData['bank_swiftbic'];
        }
        $this->senderAddressLine .= "\n$bank";

        parent::printInvoice();
    }

    /**
     * Initialize the PDF
     *
     * @return void
     */
    protected function initPDF()
    {
        parent::initPDF();
        $this->pdf->printFooterOnFirstPage = true;
    }

    /**
     * Gather an array of information to print
     *
     * @param bool $bankInfo Whether to include recipient bank information
     *
     * @return array
     */
    protected function getInfoArray($bankInfo = false)
    {
        $invoiceData = $this->invoiceData;
        $recipientData = $this->recipientData;
        $senderData = $this->senderData;

        $data = [];

        if ($recipientData['customer_no'] != 0) {
            $data['invoice::CustomerNumber'] = $recipientData['customer_no'];
        }
        if ($recipientData['company_id']) {
            $data['invoice::ClientVATID'] = $recipientData['company_id'];
        }

        $data['invoice::OrderConfirmationNumber'] = $invoiceData['invoice_no'];
        $strInvoiceDate = ($this->dateOverride)
            ? $this->formatDate($this->dateOverride)
            : $this->formatDate($invoiceData['invoice_date']);
        $data['invoice::OrderConfirmationDate'] = $strInvoiceDate;
        $paymentDays = round(
            dbDate2UnixTime($invoiceData['due_date']) / 3600 / 24 -
                    dbDate2UnixTime($invoiceData['invoice_date']) / 3600 / 24
        );
        if ($paymentDays < 0) {
            // This shouldn't happen, but try to be safe...
            $paymentDays = getPaymentDays($invoiceData['company_id']);
        }
        $data['invoice::TermsOfPayment'] = $this->getTermsOfPayment(
            $paymentDays
        );
        if ($invoiceData['reference']) {
            $data['invoice::YourReference'] = $invoiceData['reference'];
        }
        if ($invoiceData['delivery_terms']) {
            $data['invoice::DeliveryTerms'] = [
                'value' => $invoiceData['delivery_terms'],
                'type' => 'multicell'
            ];
        }
        if ($invoiceData['delivery_method']) {
            $data['invoice::DeliveryMethod'] = [
                'value' => $invoiceData['delivery_method'],
                'type' => 'multicell'
            ];
        }
        if (!empty($invoiceData['info'])) {
            $data['invoice::AdditionalInformation'] = [
                'value' => $this->replacePlaceholders($invoiceData['info']),
                'type' => 'multicell'
            ];
        }

        return $data;
    }

    /**
     * Get a title for the current print style
     *
     * @return string
     */
    protected function getHeaderTitle()
    {
        return Translator::translate('invoice::OrderConfirmationHeader');
    }

    /**
     * Print the invoice form at the end of the first page
     *
     * @return void
     */
    protected function printForm()
    {
    }
}