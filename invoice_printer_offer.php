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

class InvoicePrinterOffer extends InvoicePrinterBase
{

    public function init($invoiceId, $printParameters, $outputFileName, $senderData,
        $recipientData, $invoiceData, $invoiceRowData, $recipientContactData,
        $dateOverride)
    {
        parent::init($invoiceId, $printParameters, $outputFileName, $senderData,
            $recipientData, $invoiceData, $invoiceRowData, $recipientContactData,
            $dateOverride
        );
        $this->printStyle = 'offer';
    }

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

        // Invoice info headers
        $pdf->SetXY(115, 10);
        $pdf->SetFont('Helvetica', 'B', 12);
        $pdf->Cell(40, 5, Translator::translate('invoice::OfferHeader'), 0, 1, 'R');
        $pdf->SetFont('Helvetica', '', 10);
        $pdf->SetXY(115, $pdf->GetY() + 5);
        if ($recipientData['customer_no'] != 0) {
            $pdf->Cell(40, 4, Translator::translate('invoice::CustomerNumber') . ': ', 0, 0, 'R');
            $pdf->Cell(60, 4, $recipientData['customer_no'], 0, 1);
        }
        if ($recipientData['company_id']) {
            $pdf->SetX(115);
            $pdf->Cell(40, 4, Translator::translate('invoice::ClientVATID') . ': ', 0, 0, 'R');
            $pdf->Cell(60, 4, $recipientData['company_id'], 0, 1);
        }
        $pdf->SetX(115);
        $pdf->Cell(40, 4, Translator::translate('invoice::OfferNumber') . ': ', 0, 0, 'R');
        $pdf->Cell(60, 4, $invoiceData['id'], 0, 1);

        $pdf->SetX(115);
        $pdf->Cell(40, 4, Translator::translate('invoice::OfferDate') . ': ', 0, 0, 'R');
        $strInvoiceDate = $this->_formatDate($invoiceData['invoice_date']);
        $pdf->Cell(60, 4, $strInvoiceDate, 0, 1);

        $strDueDate = $this->_formatDate($invoiceData['due_date']);
        if (!empty(Translator::translate('invoice::ValidUntilSuffix'))) {
            $strDueDate .= ' ' . Translator::translate('invoice::ValidUntilSuffix');
        }
        $pdf->SetX(115);
        $pdf->Cell(40, 4, Translator::translate('invoice::ValidUntil') . ': ', 0, 0, 'R');

        $pdf->Cell(60, 4, $strDueDate, 0, 1);

        if ($invoiceData['reference']) {
            $pdf->SetX(115);
            $pdf->Cell(40, 4, Translator::translate('invoice::YourReference') . ': ', 0, 0, 'R');
            $pdf->MultiCell(50, 4, $invoiceData['reference'], 0, 'L');
        }

        $pdf->SetX(115);
        $pdf->Cell(40, 4, Translator::translate('invoice::TermsOfPayment') . ': ', 0, 0, 'R');
        $paymentDays = getPaymentDays($invoiceData['company_id']);
        $pdf->Cell(60, 4, $this->getTermsOfPayment($paymentDays), 0, 1);

        if ($invoiceData['delivery_terms']) {
            $pdf->SetX(115);
            $pdf->Cell(40, 4, Translator::translate('invoice::DeliveryTerms') . ': ', 0, 0, 'R');
            $pdf->MultiCell(50, 4, $invoiceData['delivery_terms'], 0, 'L', 0);
        }

        if ($invoiceData['delivery_method']) {
            $pdf->SetX(115);
            $pdf->Cell(40, 4, Translator::translate('invoice::DeliveryMethod') . ': ', 0, 0, 'R');
            $pdf->MultiCell(50, 4, $invoiceData['delivery_method'], 0, 'L', 0);
        }
        if ($invoiceData['delivery_time']) {
            $pdf->SetX(115);
            $pdf->Cell(40, 4, Translator::translate('invoice::DeliveryTime') . ': ', 0, 0, 'R');
            $pdf->MultiCell(50, 4, $invoiceData['delivery_time'], 0, 'L', 0);
        }

        if (isset($invoiceData['info']) && $invoiceData['info']) {
            $pdf->SetX(115);
            $pdf->Cell(40, 4, Translator::translate('invoice::AdditionalInformation') . ': ', 0, 0,
                'R');
            $pdf->MultiCell(50, 4, $invoiceData['info'], 0, 'L', 0);
        }
    }

    protected function printForm()
    {
    }

    protected function printRows()
    {
        $pdf = $this->pdf;
        $invoiceData = $this->invoiceData;

        $pdf->printFooterOnFirstPage = true;
        $pdf->SetAutoPageBreak(true, 22);

        $left = 10;
        $nameColWidth = $this->discountedRows ? 118 : 130;
        if ($this->senderData['vat_registered']) {
            $nameColWidth -= 50;
        }

        $pdf->Cell($nameColWidth, 5, Translator::translate('invoice::RowName'), 0, 0, 'L');
        $pdf->Cell(17, 5, Translator::translate('invoice::RowPrice'), 0, 0, 'R');
        if ($this->discountedRows) {
            $pdf->Cell(12, 5, Translator::translate('invoice::RowDiscount'), 0, 0, 'R');
        }
        $pdf->Cell(20, 5, Translator::translate('invoice::RowPieces'), 0, 0, 'R');
        if ($this->senderData['vat_registered']) {
            $pdf->MultiCell(20, 5, Translator::translate('invoice::RowTotalVATLess'), 0, 'R', 0,
                0);
            $pdf->Cell(15, 5, Translator::translate('invoice::RowVATPercent'), 0, 0, 'R');
            $pdf->Cell(15, 5, Translator::translate('invoice::RowTax'), 0, 0, 'R');
        }
        $pdf->Cell(20, 5, Translator::translate('invoice::RowTotal'), 0, 1, 'R');
        $pdf->Cell(20, 5, '', 0, 1, 'R'); // line feed

        foreach ($this->invoiceRowData as $row) {
            $partial = $row['partial_payment'];
            // Product / description
            $description = '';
            switch ($row['reminder_row']) {
            case 1 :
                $description = Translator::translate('invoice::PenaltyInterestDesc');
                break;
            case 2 :
                $description = Translator::translate('invoice::ReminderFeeDesc');
                break;
            default :
                if ($row['product_name']) {
                    if ($row['description'])
                        $description = $row['product_name'] . ' (' .
                             $row['description'] . ')';
                    else
                        $description = $row['product_name'];
                    if (getSetting('invoice_display_product_codes') &&
                         $row['product_code']) {
                        $description = $row['product_code'] . ' ' . $description;
                    }
                } else
                    $description = $row['description'];
            }

            // Sums
            list ($rowSum, $rowVAT, $rowSumVAT) = calculateRowSum($row['price'],
                $row['pcs'], $row['vat'], $row['vat_included'], $row['discount']);
            if ($row['vat_included'])
                $row['price'] /= (1 + $row['vat'] / 100);

            if ($row['price'] == 0 && $row['pcs'] == 0) {
                $pdf->SetX($left);
                $pdf->MultiCell(0, 5, $description, 0, 'L');
            } else {
                $pdf->SetX($nameColWidth + $left);
                $decimals = isset($row['price_decimals']) ? $row['price_decimals'] : 2;
                $pdf->Cell(17, 5, $this->_formatCurrency($row['price'], $decimals),
                    0, 0, 'R');
                if ($this->discountedRows) {
                    $pdf->Cell(12, 5,
                        (isset($row['discount']) && $row['discount'] != '0') ? $this->_formatCurrency(
                            $row['discount'], 2, true) : '', 0, 0, 'R');
                }
                $pdf->Cell(13, 5, $this->_formatNumber($row['pcs'], 2, true), 0, 0,
                    'R');
                $pdf->Cell(7, 5,
                    Translator::translate("invoice::{$row['type']}"),
                    0, 0, 'L');
                if ($this->senderData['vat_registered']) {
                    $pdf->Cell(20, 5, $partial ? '' : $this->_formatCurrency($rowSum), 0, 0, 'R');
                    $pdf->Cell(11, 5,
                        $partial ? '' : $this->_formatNumber($row['vat'], 1, true), 0, 0, 'R');
                    $pdf->Cell(4, 5, '', 0, 0, 'R');
                    $pdf->Cell(15, 5, $partial ? '' : $this->_formatCurrency($rowVAT), 0, 0, 'R');
                }
                $pdf->Cell(20, 5, $this->_formatCurrency($rowSumVAT), 0, 0, 'R');
                $pdf->SetX($left);
                $pdf->MultiCell($nameColWidth, 5, $description, 0, 'L');
            }
        }
        if ($this->senderData['vat_registered']) {
            $pdf->SetFont('Helvetica', '', 10);
            $pdf->SetY($pdf->GetY() + 10);
            $pdf->Cell(162, 5, Translator::translate('invoice::TotalExcludingVAT') . ': ', 0, 0,
                'R');
            $pdf->SetX(187 - $left);
            $pdf->Cell(20, 5, $this->_formatCurrency($this->totalSum), 0, 0, 'R');

            $pdf->SetFont('Helvetica', '', 10);
            $pdf->SetY($pdf->GetY() + 5);
            $pdf->Cell(162, 5, Translator::translate('invoice::TotalVAT') . ': ', 0, 0, 'R');
            $pdf->SetX(187 - $left);
            $pdf->Cell(20, 5, $this->_formatCurrency($this->totalVAT), 0, 0, 'R');

            $pdf->SetFont('Helvetica', 'B', 10);
            $pdf->SetY($pdf->GetY() + 5);
            $pdf->Cell(162, 5, Translator::translate('invoice::TotalIncludingVAT') . ': ', 0, 0,
                'R');
            $pdf->SetX(187 - $left);
            $pdf->Cell(20, 5, $this->_formatCurrency($this->totalSumVAT), 0, 1,
                'R');
            $pdf->SetFont('Helvetica', '', 10);
        } else {
            $pdf->SetFont('Helvetica', 'B', 10);
            $pdf->SetY($pdf->GetY() + 5);
            $pdf->Cell(162, 5, Translator::translate('invoice::TotalPrice') . ': ', 0, 0, 'R');
            $pdf->SetX(187 - $left);
            $pdf->Cell(20, 5, $this->_formatCurrency($this->totalSumVAT), 0, 1,
                'R');
            $pdf->SetFont('Helvetica', '', 10);
        }
    }
}
