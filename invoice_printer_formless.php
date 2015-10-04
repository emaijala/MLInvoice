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
require_once 'htmlfuncs.php';
require_once 'miscfuncs.php';

class InvoicePrinterFormless extends InvoicePrinterBase
{

    public function printInvoice()
    {
        $this->invoiceRowMaxY = 260;
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

    protected function printInfo()
    {
        $pdf = $this->pdf;
        $senderData = $this->senderData;
        $invoiceData = $this->invoiceData;
        $recipientData = $this->recipientData;
        
        if ($this->printStyle == 'dispatch')
            $locStr = 'DispatchNote';
        elseif ($this->printStyle == 'receipt')
            $locStr = 'Receipt';
        else
            $locStr = 'Invoice';
            
            // Invoice info headers
        $pdf->SetXY(115, 10);
        $pdf->SetFont('Helvetica', 'B', 12);
        if ($this->printStyle == 'dispatch')
            $pdf->Cell(40, 5, $GLOBALS['locPDFDispatchNoteHeader'], 0, 1, 'R');
        elseif ($this->printStyle == 'receipt')
            $pdf->Cell(40, 5, $GLOBALS['locPDFReceiptHeader'], 0, 1, 'R');
        elseif ($invoiceData['state_id'] == 5)
            $pdf->Cell(40, 5, $GLOBALS['locPDFFirstReminderHeader'], 0, 1, 'R');
        elseif ($invoiceData['state_id'] == 6)
            $pdf->Cell(40, 5, $GLOBALS['locPDFSecondReminderHeader'], 0, 1, 'R');
        else
            $pdf->Cell(40, 5, $GLOBALS['locPDFInvoiceHeader'], 0, 1, 'R');
        $pdf->SetFont('Helvetica', '', 10);
        $pdf->SetXY(115, $pdf->GetY() + 5);
        if ($recipientData['customer_no'] != 0) {
            $pdf->Cell(40, 5, $GLOBALS['locPDFCustomerNumber'] . ': ', 0, 0, 'R');
            $pdf->Cell(60, 5, $recipientData['customer_no'], 0, 1);
        }
        if ($recipientData['company_id']) {
            $pdf->SetX(115);
            $pdf->Cell(40, 5, $GLOBALS['locPDFClientVATID'] . ': ', 0, 0, 'R');
            $pdf->Cell(60, 5, $recipientData['company_id'], 0, 1);
        }
        $pdf->SetX(115);
        $pdf->Cell(40, 5, $GLOBALS["locPDF${locStr}Number"] . ': ', 0, 0, 'R');
        $pdf->Cell(60, 5, $invoiceData['invoice_no'], 0, 1);
        $pdf->SetX(115);
        $pdf->Cell(40, 5, $GLOBALS["locPDF${locStr}Date"] . ': ', 0, 0, 'R');
        $strInvoiceDate = $this->_formatDate($invoiceData['invoice_date']);
        $strDueDate = $this->_formatDate($invoiceData['due_date']);
        $pdf->Cell(60, 5, $strInvoiceDate, 0, 1);
        if ($this->printStyle == 'invoice') {
            $pdf->SetX(115);
            $pdf->Cell(40, 5, $GLOBALS['locPDFDueDate'] . ': ', 0, 0, 'R');
            $pdf->Cell(60, 5, $strDueDate, 0, 1);
            $pdf->SetX(115);
            $pdf->Cell(40, 5, $GLOBALS['locPDFTermsOfPayment'] . ': ', 0, 0, 'R');
            $paymentDays = round(
                dbDate2UnixTime($invoiceData['due_date']) / 3600 / 24 -
                     dbDate2UnixTime($invoiceData['invoice_date']) / 3600 / 24);
            if ($paymentDays < 0) {
                // This shouldn't happen, but try to be safe...
                $paymentDays = getPaymentDays($invoiceData['company_id']);
            }
            $pdf->Cell(60, 5, 
                sprintf(getTermsOfPayment($invoiceData['company_id']), $paymentDays), 
                0, 1);
            $pdf->SetX(115);
            $pdf->Cell(40, 5, $GLOBALS['locPDFPeriodForComplaints'] . ': ', 0, 0, 
                'R');
            $pdf->Cell(60, 5, getSetting('invoice_period_for_complaints'), 0, 1);
            $pdf->SetX(115);
            $pdf->Cell(40, 5, $GLOBALS['locPDFPenaltyInterest'] . ': ', 0, 0, 'R');
            $pdf->Cell(60, 5, 
                $this->_formatNumber(getSetting('invoice_penalty_interest'), 1, true) .
                     ' %', 0, 1);
            $pdf->SetX(115);
            $pdf->Cell(40, 5, $GLOBALS['locPDFRecipientBankAccount'] . ': ', 0, 0, 
                'R');
            $pdf->Cell(60, 5, $senderData['bank_iban'], 0, 1);
            $pdf->SetX(115);
            $pdf->Cell(40, 5, $GLOBALS['locPDFRecipientBankBIC'] . ': ', 0, 0, 'R');
            $pdf->Cell(60, 5, $senderData['bank_swiftbic'], 0, 1);
            $pdf->SetX(115);
            if ($this->refNumber) {
                $pdf->Cell(40, 5, $GLOBALS['locPDFInvoiceRefNr'] . ': ', 0, 0, 'R');
                $pdf->Cell(60, 5, $this->refNumber, 0, 1);
            }
        }
        
        if ($invoiceData['reference'] && $this->printStyle != 'dispatch') {
            $pdf->SetX(115);
            $pdf->Cell(40, 5, $GLOBALS['locPDFYourReference'] . ': ', 0, 0, 'R');
            $pdf->Cell(60, 5, $invoiceData['reference'], 0, 1);
        }
        if (isset($invoiceData['info']) && $invoiceData['info']) {
            $pdf->SetX(115);
            $pdf->Cell(40, 5, $GLOBALS['locPDFAdditionalInformation'] . ': ', 0, 0, 
                'R');
            $pdf->MultiCell(50, 5, $invoiceData['info'], 0, 'L', 0);
        }
        
        if ($this->printStyle == 'invoice') {
            if ($invoiceData['refunded_invoice_no']) {
                $pdf->SetX(115);
                $pdf->Cell(40, 5, 
                    sprintf($GLOBALS['locPDFRefundsInvoice'], 
                        $invoiceData['refunded_invoice_no']), 0, 1, 'R');
            }
            
            if ($invoiceData['state_id'] == 5) {
                $pdf->SetX(108);
                $pdf->SetFont('Helvetica', 'B', 10);
                $pdf->MultiCell(98, 5, $GLOBALS['locPDFFirstReminderNote'], 0, 'L', 
                    0);
                $pdf->SetFont('Helvetica', '', 10);
            } elseif ($invoiceData['state_id'] == 6) {
                $pdf->SetX(108);
                $pdf->SetFont('Helvetica', 'B', 10);
                $pdf->MultiCell(98, 5, $GLOBALS['locPDFSecondReminderNote'], 0, 'L', 
                    0);
                $pdf->SetFont('Helvetica', '', 10);
            }
        }
    }

    protected function printForm()
    {
    }
}