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
require_once 'invoice_printer_base.php';
require_once 'invoice_printer_email_trait.php';

class InvoicePrinterEmail extends InvoicePrinterBase
{
    use InvoicePrinterEmailTrait;

    protected function emailPostProcess($success)
    {
        if ($success && $this->invoiceData['state_id'] == 1) {
            // Mark invoice sent
            mysqli_param_query('UPDATE {prefix}invoice SET state_id=2 WHERE id=?',
                [
                    $this->invoiceId
                ]
            );
        }
    }
}
