<?php
/**
 * XSLT invoice
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
 * XSLT invoice
 *
 * @category MLInvoice
 * @package  MLInvoice\Base
 * @author   Ere Maijala <ere@labs.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://labs.fi/mlinvoice.eng.php
 */
class InvoicePrinterXslt extends InvoicePrinterBase
{
    /**
     * XSLT parameters
     *
     * @var array
     */
    protected $xsltParams = [];

    /**
     * Transform the invoice to XML
     *
     * @param string $xslt XSLT file name
     * @param string $xsd  Optional XSD name used to check the results
     *
     * @return void
     */
    protected function transform($xslt, $xsd = '')
    {
        if (!class_exists('XSLTProcessor')) {
            die(
                <<<EOT
<p>This printout requires the PHP XSL extension, more specifically the XSLTProcessor
class, which seems to be missing. Please install the XSL extension or request
your server administrator to do it.</p>
<p>Many Linux distributions offer the XSL extension in a separate package that can be
installed with a package manager. E.g. in Ubuntu the package might be php5-xsl,
php7.0-xsl or php7.1-xsl depending on the PHP version.</p>
<p>More information about the XSL extension is available in the
<a href="http://php.net/manual/en/book.xsl.php" target="_blank">PHP Manual</a>.
EOT
            );
        }

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
            $data['type'] = Translator::translate("invoice::{$data['type']}");
        }

        $rows = $invoice->addChild('rows');
        $this->arrayToXML($this->invoiceRowData, $rows, 'row');

        include 'settings_def.php';
        $settingsData = [];
        foreach ($arrSettings as $key => $value) {
            if (substr($key, 0, 8) == 'invoice_' && $value['type'] != 'LABEL') {
                switch ($key) {
                case 'invoice_terms_of_payment' :
                    $settingsData[$key] = $this->getTermsOfPayment(
                        getPaymentDays($invoiceData['company_id'])
                    );
                    break;
                case 'invoice_pdf_filename' :
                    $settingsData[$key] = $this->getPrintOutFileName(
                        getSetting('invoice_pdf_filename')
                    );
                    break;
                default :
                    $settingsData[$key] = getSetting($key);
                }
            }
        }
        $settingsData['invoice_penalty_interest_desc']
            = Translator::translate('invoice::PenaltyInterestDesc')
            . ': ' . miscRound2OptDecim(getSetting('invoice_penalty_interest'), 1)
            . ' %';
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
        foreach ($this->xsltParams as $param => $value) {
            $xsltproc->setParameter('', $param, $value);
        }
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

    /**
     * Convert an array to XML
     *
     * @param array            $array       Array
     * @param SimpleXMLElement $xml         XML
     * @param string           $subnodename Sub node
     *
     * @return void
     */
    protected function arrayToXML($array, &$xml, $subnodename = '')
    {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $node = $xml->addChild(
                    '' !== $subnodename ? $subnodename : $key
                );
                if (!is_numeric($key)) {
                    $this->arrayToXML($value, $node);
                } else {
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
