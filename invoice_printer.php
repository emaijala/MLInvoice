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

class InvoicePrinter extends InvoicePrinterBase
{

    public function __construct()
    {
        parent::__construct();
        
        // The normal invoice can be printed by a read-only user
        $this->readOnlySafe = true;
    }
}

