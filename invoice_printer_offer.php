<?php
/*******************************************************************************
 MLInvoice: web-based invoicing application.
 Copyright (C) 2010-2017 Ere Maijala

 This program is free software. See attached LICENSE.

 *******************************************************************************/

/*******************************************************************************
 MLInvoice: web-pohjainen laskutusohjelma.
 Copyright (C) 2010-2017 Ere Maijala

 Tämä ohjelma on vapaa. Lue oheinen LICENSE.

 *******************************************************************************/
require_once 'invoice_printer_base.php';
require_once 'htmlfuncs.php';
require_once 'miscfuncs.php';

class InvoicePrinterOffer extends InvoicePrinterBase
{

    public function init($invoiceId, $printParameters, $outputFileName, $senderData,
        $recipientData, $invoiceData, $invoiceRowData, $recipientContactData,
        $dateOverride)
    {
        parent::init($invoiceId, $printParameters, $outputFileName, $senderData,
            $recipientData, $invoiceData, $invoiceRowData, $recipientContactData,
            $dateOverride
        );
        $this->printStyle = 'offer';
        $this->columnDefinitions['date']['visible'] = false;
    }

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

    protected function getHeaderTitle()
    {
        return Translator::translate('invoice::OfferHeader');
    }

    protected function printForm()
    {
    }
}
