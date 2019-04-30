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
     * @param int    $invoiceId       Invoice ID
     * @param array  $printParameters Print control parameters
     * @param string $outputFileName  File name template
     * @param int    $dateOverride    Date override for invoice date
     * @param int    $printTemplateId Print template ID
     * @param bool   $authenticated   Whether the user is authenticated
     *
     * @return void
     */
    public function init($invoiceId, $printParameters, $outputFileName,
        $dateOverride, $printTemplateId, $authenticated
    ) {

        parent::init(
            $invoiceId, $printParameters, $outputFileName,
            $dateOverride, $printTemplateId, $authenticated
        );
        $this->printStyle = 'offer';
        $this->columnDefinitions['date']['visible'] = false;
        $this->includeBankInFooter = true;
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
            $data['CustomerNumber'] = $recipientData['customer_no'];
        }
        if ($recipientData['company_id']) {
            $data['ClientVATID'] = $recipientData['company_id'];
        }

        $data['OfferNumber'] = $invoiceData['invoice_no'];
        $strInvoiceDate = ($this->dateOverride)
            ? $this->formatDate($this->dateOverride)
            : $this->formatDate($invoiceData['invoice_date']);
        $data['OfferDate'] = $strInvoiceDate;

        $strDueDate = $this->formatDate($invoiceData['due_date']);
        $validUntilSuffix = $this->translate('ValidUntilSuffix');
        if (!empty($validUntilSuffix)) {
            $strDueDate .= " $validUntilSuffix";
        }
        $data['ValidUntil'] = [
            'value' => $strDueDate,
            'type' => 'multicell'
        ];

        $paymentDays = getPaymentDays($invoiceData['company_id']);
        $data['TermsOfPayment'] = [
            'value' => $this->getTermsOfPayment($paymentDays),
            'type' => 'multicell'
        ];
        if ($invoiceData['reference']) {
            $data['YourReference'] = [
                'value' => $invoiceData['reference'],
                'type' => 'multicell'
            ];
        }
        if ($invoiceData['delivery_terms']) {
            $data['DeliveryTerms'] = [
                'value' => $invoiceData['delivery_terms'],
                'type' => 'multicell'
            ];
        }
        if ($invoiceData['delivery_method']) {
            $data['DeliveryMethod'] = [
                'value' => $invoiceData['delivery_method'],
                'type' => 'multicell'
            ];
        }
        if ($invoiceData['delivery_time']) {
            $data['DeliveryTime'] = [
                'value' => $invoiceData['delivery_time'],
                'type' => 'multicell'
            ];
        }

        if (!empty($invoiceData['info'])) {
            $data['AdditionalInformation'] = [
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
    public function getHeaderTitle()
    {
        return $this->translate('OfferHeader');
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
