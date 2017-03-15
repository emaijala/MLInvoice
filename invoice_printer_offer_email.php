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
require_once 'invoice_printer_offer.php';
require_once 'invoice_printer_email_trait.php';

class InvoicePrinterOfferEmail extends InvoicePrinterOffer
{
    use InvoicePrinterEmailTrait;

    protected function getDefaultBody()
    {
        return isset($this->senderData['offer_email_body'])
            ? $this->senderData['offer_email_body'] : '';
    }

    protected function getDefaultSubject()
    {
        return isset($this->senderData['offer_email_subject'])
            ? $this->senderData['offer_email_subject'] : '';
    }

    protected function emailSent()
    {
        $res = mysqli_param_query(
            'SELECT invoice_open FROM {prefix}invoice_state WHERE id=?',
            [$this->invoiceData['state_id']]
        );
        $open = mysqli_fetch_value($res);
        if ($open) {
            $res = mysqli_query_check(
                'SELECT id FROM {prefix}invoice_state WHERE invoice_open=1'
                . ' AND invoice_offer=1 AND invoice_offer_sent=1'
                . ' ORDER BY order_no'
            );
            $stateId = mysqli_fetch_value($res);
            // Mark invoice offered
            if (null !== $stateId) {
                mysqli_param_query(
                    'UPDATE {prefix}invoice SET state_id=? WHERE id=?',
                    [$stateId, $this->invoiceId]
                );
            }
        }
    }
}
