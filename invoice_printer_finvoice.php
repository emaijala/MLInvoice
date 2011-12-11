<?php

require_once 'invoice_printer_xslt.php';
require_once 'htmlfuncs.php';
require_once 'miscfuncs.php';

class InvoicePrinter extends InvoicePrinterXSLT
{
  
  public function printInvoice()
  {
    parent::transform('create_finvoice.xsl', 'Finvoice.xsd');
    header('Content-Type: text/xml');
    header('Content-Disposition: inline; filename='. sprintf($this->outputFileName, $this->invoiceData['invoice_no']));
    echo $this->_xml;
  }

}
