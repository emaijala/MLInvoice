<?php

require_once 'invoice_printer_base.php';
require_once 'htmlfuncs.php';
require_once 'miscfuncs.php';

class InvoicePrinterXslt extends InvoicePrinterBase
{
  protected function transform($xslt, $xsd = '')
  {
    $xml = new SimpleXMLElement('<?xml version="1.0"?><invoicedata/>');
    $sender = $xml->addChild('sender');
    $this->_arrayToXML($this->senderData, $sender);
    $recipient = $xml->addChild('recipient');
    $this->_arrayToXML($this->recipientData, $recipient);
    $invoice = $xml->addChild('invoice');
    $invoiceData = $this->invoiceData;
    $invoiceData['totalsum'] = $this->totalSum;
    $invoiceData['totalvat'] = $this->totalVAT;
    $invoiceData['totalsumvat'] = $this->totalSumVAT;
    $invoiceData['formatted_ref_number'] = $this->refNumber;
    $invoiceData['barcode'] = $this->barcode;
    $invoiceData['groupedvats'] = $this->groupedVATs;
    $this->_arrayToXML($invoiceData, $invoice);
    $rows = $invoice->addChild('rows');
    $this->_arrayToXML($this->invoiceRowData, $rows, 'row');

    require 'settings_def.php';
    $settingsData = array();
    foreach ($arrSettings as $key => $value) 
    {
      if (substr($key, 0, 8) == 'invoice_' && $value['type'] != 'LABEL') 
      {
        switch ($key)
        {
        case 'invoice_terms_of_payment':
          $settingsData[$key] = sprintf(getSetting('invoice_terms_of_payment'), getSetting('invoice_payment_days'));
          break;
        case 'invoice_pdf_filename':
          $settingsData[$key] = sprintf(getSetting('invoice_pdf_filename'), $invoiceData['invoice_no']);
          break;
        default:
          $settingsData[$key] = getSetting($key);
        }
      }
    }
    $settingsData['invoice_penalty_interest_desc'] = $GLOBALS['locPDFPenaltyInterestDesc'] . ': ' . miscRound2OptDecim(getSetting('invoice_penalty_interest'), 1) . ' %';
    $settings = $xml->addChild('settings');
    $this->_arrayToXML($settingsData, $settings);
    
    $xsltproc = new XSLTProcessor(); 
    $xsl = new DOMDocument();
    $xsl->load($xslt);
    $xsltproc->importStylesheet($xsl);
    $xsltproc->setParameter('', 'stylesheet', $this->printStyle);
    $domDoc = dom_import_simplexml($xml)->ownerDocument;
    $this->_xml = $xsltproc->transformToXML($domDoc); 
    
    if ($xsd)
    {
      libxml_use_internal_errors(true);
      $xmlDoc = new DOMDocument;
      $xmlDoc->loadXML($this->_xml);
      if (!$xmlDoc->schemaValidate($xsd)) 
      {
        header("Content-Type: text/plain");
        echo "Result XML validation failed:\n\n";
        $errors = libxml_get_errors();
        foreach ($errors as $error)
        {
          switch ($error->level) {
          case LIBXML_ERR_WARNING:
            $type = 'Warning';
            break;
          case LIBXML_ERR_FATAL:
            $type = 'Fatal';
            break;
          default:
            $type = 'Error';
          }        
          echo "$type {$error->code}({$error->level}) at {$error->line}:{$error->column}: {$error->message}\n";
        }
        echo "\n\nXML:\n\n";
        $lineno = 1;
        foreach (explode("\n", $this->_xml) as $line) 
        {
          echo "$lineno\t$line\n";
          ++$lineno;
        }
        exit(1);
      }
    }
    //$this->_xml = $xml->asXML();
  }
 
  protected function _arrayToXML($array, &$xml, $subnodename = '') 
  {
    foreach ($array as $key => $value) 
    {
      if (is_array($value)) 
      {
        if (!is_numeric($key))
        {
          $node = $xml->addChild($key);
          $this->_arrayToXML($value, $node);
        }
        else
        {
          $node = $xml->addChild($subnodename);
          $this->_arrayToXML($value, $node);
        }
      }
      else 
      {
        if ($key != 'logo_filedata')
        {
          $xml->addChild($key, $value);
        }
        else
        {
          $xml->addChild($key, base64_encode($value));
        }
      }
    }
  }
}
