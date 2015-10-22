<?php
/*******************************************************************************
 MLInvoice: web-based invoicing application.
 Copyright (C) 2010-2015 Ere Maijala

 This program is free software. See attached LICENSE.

 *******************************************************************************/

/*******************************************************************************
 MLInvoice: web-pohjainen laskutusohjelma.
 Copyright (C) 2010-2015 Ere Maijala

 Tämä ohjelma on vapaa. Lue oheinen LICENSE.

 *******************************************************************************/
require_once 'invoice_printer_base.php';
require_once 'htmlfuncs.php';
require_once 'miscfuncs.php';

class InvoicePrinterXslt extends InvoicePrinterBase
{

    protected function transform($xslt, $xsd = '')
    {
        $xml = new SimpleXMLElement('<?xml version="1.0"?><invoicedata/>');
        $sender = $xml->addChild('sender');
        $this->arrayToXML($this->senderData, $sender);
        $recipient = $xml->addChild('recipient');
        $this->arrayToXML($this->recipientData, $recipient);
        $invoice = $xml->addChild('invoice');
        $invoiceData = $this->invoiceData;
        $invoiceData['totalsum'] = $this->totalSum;
        $invoiceData['totalvat'] = $this->totalVAT;
        $invoiceData['totalsumvat'] = $this->totalSumVAT;
        $invoiceData['paidsum'] = $invoiceData['invoice_unpaid']
            ? $this->partialPayments : $this->totalSumVAT;
        $invoiceData['formatted_ref_number'] = $this->refNumber;
        $invoiceData['barcode'] = $this->barcode;
        $invoiceData['groupedvats'] = $this->groupedVATs;
        $this->arrayToXML($invoiceData, $invoice);

        foreach ($this->invoiceRowData as  &$data) {
            if (isset($GLOBALS["locPDF{$data['type']}"])) {
                $data['type'] = $GLOBALS["locPDF{$data['type']}"];
            }
        }

        $rows = $invoice->addChild('rows');
        $this->arrayToXML($this->invoiceRowData, $rows, 'row');

        require 'settings_def.php';
        $settingsData = [];
        foreach ($arrSettings as $key => $value) {
            if (substr($key, 0, 8) == 'invoice_' && $value['type'] != 'LABEL') {
                switch ($key) {
                case 'invoice_terms_of_payment' :
                    $settingsData[$key] = sprintf(
                        getTermsOfPayment($invoiceData['company_id']),
                        getPaymentDays($invoiceData['company_id']));
                    break;
                case 'invoice_pdf_filename' :
                    $settingsData[$key] = $this->getPrintOutFileName(
                        getSetting('invoice_pdf_filename'));
                    break;
                default :
                    $settingsData[$key] = getSetting($key);
                }
            }
        }
        $settingsData['invoice_penalty_interest_desc'] = $GLOBALS['locPDFPenaltyInterestDesc'] .
             ': ' . miscRound2OptDecim(getSetting('invoice_penalty_interest'), 1) .
             ' %';
        $settingsData['current_time_year'] = date('Y');
        $settingsData['current_time_mon'] = date('m');
        $settingsData['current_time_day'] = date('d');
        $settingsData['current_time_hour'] = date('H');
        $settingsData['current_time_min'] = date('i');
        $settingsData['current_time_sec'] = date('s');
        $settingsData['current_timestamp'] = date('c');
        $settingsData['current_timestamp_utc'] = gmdate('Y-m-d\TH:i:s\Z');
        $settings = $xml->addChild('settings');
        $this->arrayToXML($settingsData, $settings);

        $xsltproc = new XSLTProcessor();
        $xsl = new DOMDocument();
        $xsl->load($xslt);
        $xsltproc->importStylesheet($xsl);
        $xsltproc->setParameter('', 'stylesheet', $this->printStyle);
        $domDoc = dom_import_simplexml($xml)->ownerDocument;
        $this->xml = $xsltproc->transformToXML($domDoc);

        if ($xsd) {
            libxml_use_internal_errors(true);
            $xmlDoc = new DOMDocument();
            $xmlDoc->loadXML($this->xml);
            if (!$xmlDoc->schemaValidate($xsd)) {
                header('Content-Type: text/plain');
                echo "Result XML validation failed:\n\n";
                $errors = libxml_get_errors();
                foreach ($errors as $error) {
                    switch ($error->level) {
                    case LIBXML_ERR_WARNING :
                        $type = 'Warning';
                        break;
                    case LIBXML_ERR_FATAL :
                        $type = 'Fatal';
                        break;
                    default :
                        $type = 'Error';
                    }
                    echo "$type {$error->code}({$error->level}) at {$error->line}:{$error->column}: {$error->message}\n";
                }
                echo "\n\nXML:\n\n";
                $lineno = 1;
                foreach (explode("\n", $this->xml) as $line) {
                    echo "$lineno\t$line\n";
                    ++$lineno;
                }
                exit(1);
            }
        }
    }

    protected function arrayToXML($array, &$xml, $subnodename = '')
    {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                if (!is_numeric($key)) {
                    $node = $xml->addChild($key);
                    $this->arrayToXML($value, $node);
                } else {
                    $node = $xml->addChild($subnodename);
                    $this->arrayToXML($value, $node);
                }
            } else {
                if ($key != 'logo_filedata') {
                    $xml->addChild($key, str_replace('&', '&amp;', $value));
                } else {
                    $xml->addChild($key, base64_encode($value));
                }
            }
        }
    }
}
