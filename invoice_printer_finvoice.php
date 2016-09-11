<?php
/*******************************************************************************
 MLInvoice: web-based invoicing application.
 Copyright (C) 2010-2016 Ere Maijala

 This program is free software. See attached LICENSE.

 *******************************************************************************/

/*******************************************************************************
 MLInvoice: web-pohjainen laskutusohjelma.
 Copyright (C) 2010-2016 Ere Maijala

 Tämä ohjelma on vapaa. Lue oheinen LICENSE.

 *******************************************************************************/
require_once 'invoice_printer_xslt.php';
require_once 'htmlfuncs.php';
require_once 'miscfuncs.php';

class InvoicePrinterFinvoice extends InvoicePrinterXSLT
{
    public function printInvoice()
    {
        $this->xsltParams['printTransmissionDetails'] = false;
        parent::transform('create_finvoice.xsl', 'Finvoice.xsd');
        header('Content-Type: text/xml; charset=ISO-8859-15');
        $filename = $this->getPrintoutFileName();
        if ($this->printStyle) {
            header("Content-Disposition: inline; filename=$filename");
        } else {
            header("Content-Disposition: attachment; filename=$filename");
        }
        echo $this->xml;
    }
}
