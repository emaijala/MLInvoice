<?php
/**
 * Offer PDF
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
 * Offer PDF
 *
 * @category MLInvoice
 * @package  MLInvoice\Base
 * @author   Ere Maijala <ere@labs.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://labs.fi/mlinvoice.eng.php
 */
class InvoicePrinterOffer extends InvoicePrinterBase
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
        $this->printStyle = 'offer';
        $this->columnDefinitions['date']['visible'] = false;
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

        $data['invoice::OfferNumber'] = $invoiceData['invoice_no'];
        $strInvoiceDate = ($this->dateOverride)
            ? $this->formatDate($this->dateOverride)
            : $this->formatDate($invoiceData['invoice_date']);
        $data['invoice::OfferDate'] = $strInvoiceDate;

        $strDueDate = $this->formatDate($invoiceData['due_date']);
        $validUntilSuffix = Translator::translate('invoice::ValidUntilSuffix');
        if (!empty($validUntilSuffix)) {
            $strDueDate .= " $validUntilSuffix";
        }
        $data['invoice::ValidUntil'] = $strDueDate;

        $paymentDays = getPaymentDays($invoiceData['company_id']);
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
        if ($invoiceData['delivery_time']) {
            $data['invoice::DeliveryTime'] = [
                'value' => $invoiceData['delivery_time'],
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
        return Translator::translate('invoice::OfferHeader');
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
