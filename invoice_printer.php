<?php

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
  
