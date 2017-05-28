<?php
/*******************************************************************************
 MLInvoice: web-based invoicing application.
 Copyright (C) 2010-2017 Ere Maijala

 This program is free software. See attached LICENSE.

 *******************************************************************************/

/*******************************************************************************
 MLInvoice: web-pohjainen laskutusohjelma.
 Copyright (C) 2010-2017 Ere Maijala

 Tämä ohjelma on vapaa. Lue oheinen LICENSE.

 *******************************************************************************/
require_once 'invoice_printer_base.php';
require_once 'htmlfuncs.php';
require_once 'miscfuncs.php';

class InvoicePrinterFormless extends InvoicePrinterBase
{

    public function printInvoice()
    {
        $this->allowSeparateStatement = false;
        $this->autoPageBreak = 22;
        $this->invoiceRowMaxY = 270;
        if ($this->senderData['bank_iban'] && $this->senderData['bank_swiftbic']) {
            $bank = $this->senderData['bank_iban'] . '/' .
                 $this->senderData['bank_swiftbic'];
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

    /**
     * Gather an array of information to print
     *
     * @param bool $bankInfo Whether to include recipient bank information
     *
     * @return array
     */
    protected function getInfoArray($bankInfo = false)
    {
        return parent::getInfoArray(true);
    }

    protected function printForm()
    {
    }
}