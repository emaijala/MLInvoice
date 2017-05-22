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

    /**
     * Gather an array of information to print
     *
     * @param bool $bankInfo Whether to include recipient bank information
     *
     * @return array
     */
    protected function getInfoArray($bankInfo = false)
    {
        $invoiceData = $this->invoiceData;
        $recipientData = $this->recipientData;
        $senderData = $this->senderData;

        $data = [];

        if ($recipientData['customer_no'] != 0) {
            $data['invoice::CustomerNumber'] = $recipientData['customer_no'];
        }
        if ($recipientData['company_id']) {
            $data['invoice::ClientVATID'] = $recipientData['company_id'];
        }

        $data['invoice::OfferNumber'] = $invoiceData['invoice_no'];
        $strInvoiceDate = ($this->dateOverride)
            ? $this->_formatDate($this->dateOverride)
            : $this->_formatDate($invoiceData['invoice_date']);
        $data['invoice::OfferDate'] = $strInvoiceDate;


        $strDueDate = $this->_formatDate($invoiceData['due_date']);
        if (!empty(Translator::translate('invoice::ValidUntilSuffix'))) {
            $strDueDate .= ' ' . Translator::translate('invoice::ValidUntilSuffix');
        }
        $data['invoice::ValidUntil'] = $strDueDate;

        $paymentDays = getPaymentDays($invoiceData['company_id']);
        $data['invoice::TermsOfPayment'] = $this->getTermsOfPayment(
            $paymentDays
        );
        if ($invoiceData['reference']) {
            $data['invoice::YourReference'] = $invoiceData['reference'];
        }
        if ($invoiceData['delivery_terms']) {
            $data['invoice::DeliveryTerms'] = [
                'value' => $invoiceData['delivery_terms'],
                'type' => 'multicell'
            ];
        }
        if ($invoiceData['delivery_method']) {
            $data['invoice::DeliveryMethod'] = [
                'value' => $invoiceData['delivery_method'],
                'type' => 'multicell'
            ];
        }
        if ($invoiceData['delivery_time']) {
            $data['invoice::DeliveryTime'] = [
                'value' => $invoiceData['delivery_time'],
                'type' => 'multicell'
            ];
        }

        if (!empty($invoiceData['info'])) {
            $data['invoice::AdditionalInformation'] = [
                'value' => $invoiceData['info'],
                'type' => 'multicell'
            ];
        }

        return $data;
    }

    protected function getHeaderTitle()
    {
        return Translator::translate('invoice::OfferHeader');
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
            list ($rowSum, $rowVAT, $rowSumVAT) = calculateRowSum($row);
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
