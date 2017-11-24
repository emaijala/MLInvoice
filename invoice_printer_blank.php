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

class InvoicePrinterBlank extends InvoicePrinterBase
{
    public function printInvoice()
    {
        $this->autoPageBreakMarginFirstPage = $this->autoPageBreakMargin;

        $this->initPDF();
        $this->printSender();
        $this->printRecipient();
        $this->printInfo();
        $this->printSeparatorLine();
        $this->printForeword();
        $this->printOut();
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
        return [];
    }

    /**
     * Get a title for the current print style
     *
     * @return string
     */
    protected function getHeaderTitle()
    {
        return Translator::translate('invoice::CoverLetter');
    }

    protected function printForm()
    {
    }
}