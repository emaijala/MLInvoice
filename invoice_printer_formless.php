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
require_once 'htmlfuncs.php';
require_once 'miscfuncs.php';

class InvoicePrinterFormless extends InvoicePrinterBase
{

    public function printInvoice()
    {
        $this->allowSeparateStatement = false;
        $this->autoPageBreak = 22;
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
        $pdf->Cell(40, 5, $this->getHeaderTitle(), 0, 1, 'R');
        $pdf->SetFont('Helvetica', '', 10);
        $pdf->SetXY(115, $pdf->GetY() + 5);
        if ($recipientData['customer_no'] != 0) {
            $pdf->Cell(40, 5, Translator::translate('invoice::CustomerNumber') . ': ', 0, 0, 'R');
            $pdf->Cell(60, 5, $recipientData['customer_no'], 0, 1);
        }
        if ($recipientData['company_id']) {
            $pdf->SetX(115);
            $pdf->Cell(40, 5, Translator::translate('invoice::ClientVATID') . ': ', 0, 0, 'R');
            $pdf->Cell(60, 5, $recipientData['company_id'], 0, 1);
        }
        $pdf->SetX(115);
        $pdf->Cell(40, 5, Translator::translate("invoice::${locStr}Number") . ': ', 0, 0, 'R');
        $pdf->Cell(60, 5, $invoiceData['invoice_no'], 0, 1);
        $pdf->SetX(115);
        $pdf->Cell(40, 5, Translator::translate("invoice::${locStr}Date") . ': ', 0, 0, 'R');
        $strInvoiceDate = $this->_formatDate($invoiceData['invoice_date']);
        $strDueDate = $this->_formatDate($invoiceData['due_date']);
        $pdf->Cell(60, 5, $strInvoiceDate, 0, 1);
        if ($this->printStyle == 'invoice') {
            $pdf->SetX(115);
            $pdf->Cell(40, 5, Translator::translate('invoice::DueDate') . ': ', 0, 0, 'R');
            $pdf->Cell(60, 5, $strDueDate, 0, 1);
            $pdf->SetX(115);
            $pdf->Cell(40, 5, Translator::translate('invoice::TermsOfPayment') . ': ', 0, 0, 'R');
            $paymentDays = round(
                dbDate2UnixTime($invoiceData['due_date']) / 3600 / 24 -
                     dbDate2UnixTime($invoiceData['invoice_date']) / 3600 / 24);
            if ($paymentDays < 0) {
                // This shouldn't happen, but try to be safe...
                $paymentDays = getPaymentDays($invoiceData['company_id']);
            }
            $pdf->Cell(60, 5, $this->getTermsOfPayment($paymentDays), 0, 1);
            $pdf->SetX(115);
            $pdf->Cell(40, 5, Translator::translate('invoice::PeriodForComplaints') . ': ', 0, 0,
                'R');
            $pdf->Cell(60, 5, $this->getPeriodForComplaints(), 0, 1);
            $pdf->SetX(115);
            $pdf->Cell(40, 5, Translator::translate('invoice::PenaltyInterest') . ': ', 0, 0, 'R');
            $pdf->Cell(60, 5,
                $this->_formatNumber(getSetting('invoice_penalty_interest'), 1, true) .
                     ' %', 0, 1);
            $pdf->SetX(115);
            $pdf->Cell(40, 5, Translator::translate('invoice::RecipientBankAccount') . ': ', 0, 0,
                'R');
            $pdf->Cell(60, 5, $senderData['bank_iban'], 0, 1);
            $pdf->SetX(115);
            $pdf->Cell(40, 5, Translator::translate('invoice::RecipientBankBIC') . ': ', 0, 0, 'R');
            $pdf->Cell(60, 5, $senderData['bank_swiftbic'], 0, 1);
            $pdf->SetX(115);
            if ($this->refNumber) {
                $pdf->Cell(40, 5, Translator::translate('invoice::InvoiceRefNr') . ': ', 0, 0, 'R');
                $pdf->Cell(60, 5, $this->refNumber, 0, 1);
            }
        }

        if ($invoiceData['reference'] && $this->printStyle != 'dispatch') {
            $pdf->SetX(115);
            $pdf->Cell(40, 5, Translator::translate('invoice::YourReference') . ': ', 0, 0, 'R');
            $pdf->Cell(60, 5, $invoiceData['reference'], 0, 1);
        }
        if (isset($invoiceData['info']) && $invoiceData['info']) {
            $pdf->SetX(115);
            $pdf->Cell(40, 5, Translator::translate('invoice::AdditionalInformation') . ': ', 0, 0,
                'R');
            $pdf->MultiCell(50, 5, $invoiceData['info'], 0, 'L', 0);
        }

        if ($this->printStyle == 'invoice') {
            if ($invoiceData['refunded_invoice_no']) {
                $pdf->SetX(115);
                $pdf->Cell(40, 5,
                    sprintf(Translator::translate('invoice::RefundsInvoice'),
                        $invoiceData['refunded_invoice_no']), 0, 1, 'R');
            }

            if ($invoiceData['state_id'] == 5) {
                $pdf->SetX(108);
                $pdf->SetFont('Helvetica', 'B', 10);
                $pdf->MultiCell(98, 5, Translator::translate('invoice::FirstReminderNote'), 0, 'L',
                    0);
                $pdf->SetFont('Helvetica', '', 10);
            } elseif ($invoiceData['state_id'] == 6) {
                $pdf->SetX(108);
                $pdf->SetFont('Helvetica', 'B', 10);
                $pdf->MultiCell(98, 5, Translator::translate('invoice::SecondReminderNote'), 0, 'L',
                    0);
                $pdf->SetFont('Helvetica', '', 10);
            }
        }
    }

    protected function printForm()
    {
    }
}