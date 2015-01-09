<?php

require_once 'invoice_printer_xslt.php';
require_once 'htmlfuncs.php';
require_once 'miscfuncs.php';

class InvoicePrinterFinvoiceSOAP extends InvoicePrinterXSLT
{
  public function printInvoice()
  {
  	// First create the actual Finvoice
    parent::transform('create_finvoice.xsl', 'Finvoice.xsd');
    $finvoice = $this->xml;

		// Create the SOAP envelope
    parent::transform('create_finvoice_soap_envelope.xsl');

    header('Content-Type: text/xml');
    $filename = $this->getPrintoutFileName();
    if ($this->printStyle)
    {
      header("Content-Disposition: inline; filename=$filename");
    }
    else
    {
      header("Content-Disposition: attachment; filename=$filename");
    }
    echo $this->xml . "\n$finvoice";
  }
}
