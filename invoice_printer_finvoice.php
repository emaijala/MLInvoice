<?php

require_once 'invoice_printer_xslt.php';
require_once 'htmlfuncs.php';
require_once 'miscfuncs.php';

class InvoicePrinterFinvoice extends InvoicePrinterXSLT
{
  public function printInvoice()
  {
    parent::transform('create_finvoice.xsl', 'Finvoice.xsd');
    header('Content-Type: text/xml');
    $filename = sprintf($this->outputFileName, $this->invoiceData['invoice_no']);
    if ($this->printStyle)
    {
      header("Content-Disposition: inline; filename=$filename");
    }
    else
    {
      header("Content-Disposition: attachment; filename=$filename");
    }
    echo $this->_xml;
  }
}
