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
require_once 'invoice_printer_order_confirmation.php';
require_once 'invoice_printer_email_trait.php';

class InvoicePrinterOrderConfirmationEmail extends InvoicePrinterOrderConfirmation
{
    use InvoicePrinterEmailTrait;

    protected function getDefaultBody()
    {
        return isset($this->senderData['order_confirmation_email_body'])
            ? $this->senderData['order_confirmation_email_body'] : '';
    }

    protected function getDefaultSubject()
    {
        return isset($this->senderData['order_confirmation_email_subject'])
            ? $this->senderData['order_confirmation_email_subject'] : '';
    }
}
