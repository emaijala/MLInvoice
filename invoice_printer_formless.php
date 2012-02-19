<?php

require_once 'invoice_printer_base.php';
require_once 'htmlfuncs.php';
require_once 'miscfuncs.php';

class InvoicePrinter extends InvoicePrinterBase
{
  public function printInvoice()
  {
    $this->invoiceRowMaxY = 260;
    if ($this->senderData['bank_iban'] && $this->senderData['bank_swiftbic']) {
      $bank = $this->senderData['bank_iban'] . '/' . $this->senderData['bank_swiftbic'];
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

  protected function printForm()
  {
  }
}