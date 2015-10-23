<?php
/*******************************************************************************
 MLInvoice: web-based invoicing application.
 Copyright (C) 2010-2015 Ere Maijala

 Portions based on:
 PkLasku : web-based invoicing software.
 Copyright (C) 2004-2008 Samu Reinikainen

 This program is free software. See attached LICENSE.

 *******************************************************************************/

/*******************************************************************************
 MLInvoice: web-pohjainen laskutusohjelma.
 Copyright (C) 2010-2015 Ere Maijala

 Perustuu osittain sovellukseen:
 PkLasku : web-pohjainen laskutusohjelmisto.
 Copyright (C) 2004-2008 Samu Reinikainen

 Tämä ohjelma on vapaa. Lue oheinen LICENSE.

 *******************************************************************************/
require_once 'localize_invoice_pdf.php';
require_once 'settings.php';

abstract class InvoicePrinterBase
{
    protected $pdf = null;
    protected $invoiceId = null;
    protected $printStyle = '';
    protected $printLanguage = 'fi';
    protected $senderData = null;
    protected $recipientData = null;
    protected $invoiceData = null;
    protected $invoiceRowData = null;
    protected $separateStatement = false;
    protected $readOnlySafe = false;
    protected $senderAddress = '';
    protected $senderAddressLine = '';
    protected $senderContactInfo = '';
    protected $billingAddress = '';
    protected $refNumber = '';
    protected $recipientName = '';
    protected $recipientAddress = '';
    protected $barcode = '';
    protected $totalSum = 0;
    protected $totalVAT = 0;
    protected $totalSumVAT = 0;
    protected $discountedRows = false;
    protected $groupedVATs = [];
    protected $recipientMaxY = 0;
    protected $invoiceRowMaxY = 150;
    protected $addressXOffset = 0;
    protected $addressYOffset = 0;
    protected $partialPayments = 0;

    public function __construct()
    {
    }

    public function getReadOnlySafe()
    {
        return $this->readOnlySafe;
    }

    public function init($invoiceId, $printParameters, $outputFileName, $senderData,
        $recipientData, $invoiceData, $invoiceRowData)
    {
        $this->invoiceId = $invoiceId;
        $parameters = explode(',', $printParameters);
        $this->printStyle = $parameters[0];
        $this->printLanguage = isset($parameters[1]) ? $parameters[1] : 'fi';
        $this->printVirtualBarcode = isset($parameters[2]) ? ($parameters[2] == 'Y') : false;
        $this->outputFileName = $outputFileName;
        $this->senderData = $senderData;
        $this->recipientData = $recipientData;
        $this->invoiceData = $invoiceData;
        $this->invoiceRowData = $invoiceRowData;

        initInvoicePDFLocalizations($this->printLanguage);

        $this->totalSum = 0;
        $this->totalVAT = 0;
        $this->totalSumVAT = 0;
        $this->discountedRows = false;
        $this->partialPayments = 0;
        foreach ($this->invoiceRowData as $key => $row) {
            if ($row['partial_payment']) {
                $this->partialPayments -= $row['price'];
                continue;
            }

            list ($rowSum, $rowVAT, $rowSumVAT) = calculateRowSum($row['price'],
                $row['pcs'], $row['vat'], $row['vat_included'], $row['discount']);
            $this->invoiceRowData[$key]['rowsum'] = $rowSum;
            $this->invoiceRowData[$key]['rowvat'] = $rowVAT;
            $this->invoiceRowData[$key]['rowsumvat'] = $rowSumVAT;
            $this->totalSum += $rowSum;
            $this->totalVAT += $rowVAT;
            $this->totalSumVAT += $rowSumVAT;
            if (isset($row['discount']) && $row['discount'] > 0)
                $this->discountedRows = true;

                // Create array grouped by the VAT base
            $vat = 'vat' . number_format($row['vat'], 2, '', '');
            if (isset($this->groupedVATs[$vat])) {
                $this->groupedVATs[$vat]['totalsum'] += $rowSum;
                $this->groupedVATs[$vat]['totalvat'] += $rowVAT;
                $this->groupedVATs[$vat]['totalsumvat'] += $rowSumVAT;
            } else {
                $this->groupedVATs[$vat]['vat'] = $row['vat'];
                $this->groupedVATs[$vat]['totalsum'] = $rowSum;
                $this->groupedVATs[$vat]['totalvat'] = $rowVAT;
                $this->groupedVATs[$vat]['totalsumvat'] = $rowSumVAT;
            }
        }
        $this->separateStatement = ($this->printStyle == 'invoice') &&
             getSetting('invoice_separate_statement');

        $this->senderAddressLine = $senderData['name'];
        $strCompanyID = trim($senderData['company_id']);
        if ($strCompanyID)
            $strCompanyID = $GLOBALS['locPDFVATID'] . ": $strCompanyID";
        if ($strCompanyID)
            $strCompanyID .= ', ';
        if ($senderData['vat_registered'])
            $strCompanyID .= $GLOBALS['locPDFVATReg'];
        else
            $strCompanyID .= $GLOBALS['locPDFNonVATReg'];
        if ($strCompanyID)
            $this->senderAddressLine .= " ($strCompanyID)";
        $this->senderAddressLine .= "\n" . $senderData['street_address'];
        if ($senderData['street_address'] &&
             ($senderData['zip_code'] || $senderData['city']))
            $this->senderAddressLine .= ', ';
        if ($senderData['zip_code'])
            $this->senderAddressLine .= $senderData['zip_code'] . ' ';
        $this->senderAddressLine .= $senderData['city'];
        if ($senderData['country'] && $this->senderAddressLine) {
            $this->senderAddressLine .= ', ';
        }
        $this->senderAddressLine .= $senderData['country'];

        $this->senderAddress = $senderData['name'] . "\n" .
             $senderData['street_address'] . "\n" . $senderData['zip_code'] . ' ' .
             $senderData['city'];
        if ($senderData['country'] && $this->senderAddress) {
            $this->senderAddress .= ', ';
        }
        $this->senderAddress .= $senderData['country'];

        if ($senderData['phone'])
            $this->senderContactInfo = "\n" . $GLOBALS['locPDFPhone'] . ' ' .
                 $senderData['phone'];
        else
            $this->senderContactInfo = '';

        if ($invoiceData['ref_number'] && strlen($invoiceData['ref_number']) < 4) {
            error_log('Reference number too short, will not be displayed');
            $invoiceData['ref_number'] = '';
        }
        $this->refNumber = formatRefNumber($invoiceData['ref_number']);

        $this->recipientFullAddress = $recipientData['company_name'] . "\n" .
             $recipientData['street_address'] . "\n" . $recipientData['zip_code'] .
             ' ' . $recipientData['city'];
        $this->billingAddress = $recipientData['billing_address'];
        if (!$this->billingAddress || $this->printStyle != 'invoice' || (($invoiceData['state_id'] ==
             5 || $invoiceData['state_id'] == 6) &&
             !getSetting('invoice_send_reminder_to_invoicing_address'))) {
            $this->billingAddress = $this->recipientFullAddress;
        }
        $addressParts = explode("\n", $this->billingAddress, 2);
        $this->recipientName = isset($addressParts[0]) ? $addressParts[0] : '';
        $this->recipientAddress = isset($addressParts[1]) ? $addressParts[1] : '';

        // barcode
        /*
         * 1 Barcode version, this is version 4 or 5
         * 1 Currency (1=FIM, 2=EURO)
         * 16 IBAN without leading country code
         * 6 Euros
         * 2 Cents
         * 3 Spares, contain zeros
         * 20 Reference Number
         * 6 Due Date. Format is YYMMDD.
         */
        $this->barcode = '';
        $paymentAmount = $this->totalSumVAT - $this->partialPayments;
        if ($paymentAmount > 0) {
            $tmpRefNumber = str_replace(' ', '', $this->refNumber);
            $IBAN = str_replace(' ', '', substr($senderData['bank_iban'], 2));
            if (intval($tmpRefNumber) == 0 || (strncmp($tmpRefNumber, 'RF', 2) == 0 &&
                 intval(substr($tmpRefNumber, 2) == 0))) {
                error_log(
                    'Empty or invalid reference number "' . $tmpRefNumber .
                     '", barcode not created');
            } elseif (strlen($IBAN) != 16) {
                error_log(
                    'IBAN length invalid (should be 16 numbers without leading country code and spaces), barcode not created');
            } elseif (strlen($invoiceData['due_date']) != 8) {
                error_log(
                    'Invalid due date \'' . $invoiceData['due_date'] .
                         '\' - barcode not created');
            } elseif ($paymentAmount >= 1000000) {
                error_log('Invoice total too large, barcode not created');
            } else {
                $tmpSum = miscRound2Decim($paymentAmount, 2, '', '');
                $tmpSum = str_repeat('0', 8 - strlen($tmpSum)) . $tmpSum;
                $tmpDueDate = substr($invoiceData['due_date'], 2);

                if (strncmp($tmpRefNumber, 'RF', 2) == 0) {
                    $checkDigits = substr($tmpRefNumber, 2, 2);
                    $tmpRefNumber = substr($tmpRefNumber, 4);
                    $tmpRefNumber = $checkDigits .
                         str_repeat('0', 21 - strlen($tmpRefNumber)) . $tmpRefNumber;
                    $this->barcode = '5' . $IBAN . $tmpSum . $tmpRefNumber .
                         $tmpDueDate;
                } else {
                    $tmpRefNumber = str_repeat('0', 20 - strlen($tmpRefNumber)) .
                         $tmpRefNumber;
                    $this->barcode = '4' . $IBAN . $tmpSum . '000' . $tmpRefNumber .
                         $tmpDueDate;
                }
            }
        }

        $this->addressXOffset = getSetting('invoice_address_x_offset', 0);
        $this->addressYOffset = getSetting('invoice_address_y_offset', 0);
    }

    public function printInvoice()
    {
        $this->initPDF();

        $this->printSender();

        $this->printRecipient();

        $this->printInfo();

        $this->printSeparatorLine();

        if (!$this->separateStatement) {
            $this->printRows();
        } else {
            $this->printSeparateStatementMessage();
        }

        if ($this->printStyle == 'invoice')
            $this->printForm();

        if ($this->separateStatement)
            $this->printRows();

        $this->printOut();
    }

    protected function initPDF()
    {
        $pdf = new PDF('P', 'mm', 'A4', _CHARSET_ == 'UTF-8', _CHARSET_, false);
        $pdf->AddPage();
        $pdf->SetAutoPageBreak(FALSE);
        $pdf->footerLeft = $this->senderAddressLine;
        $pdf->footerCenter = $this->senderContactInfo;
        $pdf->footerRight = $this->senderData['www'] . "\n" .
             $this->senderData['email'];
        $this->pdf = $pdf;
    }

    protected function printSender()
    {
        $pdf = $this->pdf;
        $senderData = $this->senderData;

        if (isset($senderData['logo_filedata'])) {
            if (!isset($senderData['logo_top']))
                $senderData['logo_top'] = $pdf->GetY() + 5;
            if (!isset($senderData['logo_left']))
                $senderData['logo_left'] = $pdf->GetX();
            if (!isset($senderData['logo_width']) || $senderData['logo_width'] == 0)
                $senderData['logo_width'] = 80;
            if (!isset($senderData['logo_bottom_margin']))
                $senderData['logo_bottom_margin'] = 5;

            $pdf->Image('@' . $senderData['logo_filedata'],
                $senderData['logo_left'], $senderData['logo_top'],
                $senderData['logo_width'], 0, '', '', 'N', false, 300, '', false,
                false, 0, true);
            $pdf->SetY(
                $pdf->GetY() + $senderData['logo_bottom_margin'] +
                     $this->addressYOffset);
        } else {
            $address = $senderData['street_address'] . "\n" . $senderData['zip_code'] .
                 ' ' . $senderData['city'] . "\n" . $senderData['country'];
            $pdf->SetTextColor(125);
            $pdf->SetFont('Helvetica', 'B', 10);
            $pdf->SetY($pdf->GetY() + $this->addressYOffset);
            $pdf->setX($pdf->GetX() + $this->addressXOffset);
            $pdf->Cell(120, 5, $senderData['name'], 0, 1);
            $pdf->SetFont('Helvetica', '', 10);
            $pdf->setX($pdf->GetX() + $this->addressXOffset);
            $pdf->MultiCell(120, 5, $address, 0, 1);
            $pdf->SetY($pdf->GetY() + 5);
        }
    }

    protected function printRecipient()
    {
        $pdf = $this->pdf;
        $recipientData = $this->recipientData;

        $pdf->SetTextColor(0);
        $pdf->SetFont('Helvetica', 'B', 14);
        $pdf->setX($pdf->GetX() + $this->addressXOffset);
        $pdf->Cell(120, 6, $this->recipientName, 0, 1);
        $pdf->SetFont('Helvetica', '', 14);
        $pdf->setX($pdf->GetX() + $this->addressXOffset);
        $pdf->MultiCell(120, 6, $this->recipientAddress, 0, 1);
        $pdf->SetFont('Helvetica', '', 12);
        if ($recipientData['email'] && getSetting('invoice_show_recipient_email')) {
            $pdf->SetY($pdf->GetY() + 4);
            $pdf->setX($pdf->GetX() + $this->addressXOffset);
            $pdf->Cell(120, 6, $recipientData['email'], 0, 1);
        }

        $this->recipientMaxY = $pdf->GetY();
    }

    protected function printInfo()
    {
        $pdf = $this->pdf;
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
            $pdf->Cell(40, 4, $GLOBALS['locPDFCustomerNumber'] . ': ', 0, 0, 'R');
            $pdf->Cell(60, 4, $recipientData['customer_no'], 0, 1);
        }
        if ($recipientData['company_id']) {
            $pdf->SetX(115);
            $pdf->Cell(40, 4, $GLOBALS['locPDFClientVATID'] . ': ', 0, 0, 'R');
            $pdf->Cell(60, 4, $recipientData['company_id'], 0, 1);
        }
        $pdf->SetX(115);
        $pdf->Cell(40, 4, $GLOBALS["locPDF${locStr}Number"] . ': ', 0, 0, 'R');
        $pdf->Cell(60, 4, $invoiceData['invoice_no'], 0, 1);
        $pdf->SetX(115);
        $pdf->Cell(40, 4, $GLOBALS["locPDF${locStr}Date"] . ': ', 0, 0, 'R');
        $strInvoiceDate = $this->_formatDate($invoiceData['invoice_date']);
        $strDueDate = $this->_formatDate($invoiceData['due_date']);
        $pdf->Cell(60, 4, $strInvoiceDate, 0, 1);
        if ($this->printStyle == 'invoice') {
            $pdf->SetX(115);
            $pdf->Cell(40, 4, $GLOBALS['locPDFDueDate'] . ': ', 0, 0, 'R');
            $pdf->Cell(60, 4, $strDueDate, 0, 1);
            $pdf->SetX(115);
            $pdf->Cell(40, 4, $GLOBALS['locPDFTermsOfPayment'] . ': ', 0, 0, 'R');
            $paymentDays = round(
                dbDate2UnixTime($invoiceData['due_date']) / 3600 / 24 -
                     dbDate2UnixTime($invoiceData['invoice_date']) / 3600 / 24);
            if ($paymentDays < 0) {
                // This shouldn't happen, but try to be safe...
                $paymentDays = getPaymentDays($invoiceData['company_id']);
            }
            $pdf->Cell(60, 4,
                sprintf(getTermsOfPayment($invoiceData['company_id']), $paymentDays),
                0, 1);
            $pdf->SetX(115);
            $pdf->Cell(40, 4, $GLOBALS['locPDFPeriodForComplaints'] . ': ', 0, 0,
                'R');
            $pdf->Cell(60, 4, getSetting('invoice_period_for_complaints'), 0, 1);
            $pdf->SetX(115);
            $pdf->Cell(40, 4, $GLOBALS['locPDFPenaltyInterest'] . ': ', 0, 0, 'R');
            $pdf->Cell(60, 4,
                $this->_formatNumber(getSetting('invoice_penalty_interest'), 1, true) .
                     ' %', 0, 1);
            $pdf->SetX(115);
            if ($this->refNumber) {
                $pdf->Cell(40, 4, $GLOBALS['locPDFInvoiceRefNr'] . ': ', 0, 0, 'R');
                $pdf->Cell(60, 4, $this->refNumber, 0, 1);
            }
        }

        if ($invoiceData['reference']) {
            $pdf->SetX(115);
            $pdf->Cell(40, 4, $GLOBALS['locPDFYourReference'] . ': ', 0, 0, 'R');
            $pdf->MultiCell(50, 4, $invoiceData['reference'], 0, 'L');
        }
        if (isset($invoiceData['info']) && $invoiceData['info']) {
            $pdf->SetX(115);
            $pdf->Cell(40, 4, $GLOBALS['locPDFAdditionalInformation'] . ': ', 0, 0,
                'R');
            $pdf->MultiCell(50, 4, $invoiceData['info'], 0, 'L', 0);
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

    protected function printSeparatorLine()
    {
        $pdf = $this->pdf;
        $pdf->SetY(max($pdf->GetY(), $this->recipientMaxY) + 5);
        $pdf->Line(5, $pdf->GetY(), 202, $pdf->GetY());
        $pdf->SetY($pdf->GetY() + 5);
    }

    protected function printSeparateStatementMessage()
    {
        $pdf = $this->pdf;
        $pdf->SetFont('Helvetica', 'B', 20);
        $pdf->SetXY(20, $pdf->GetY() + 40);
        $pdf->MultiCell(180, 5, $GLOBALS['locPDFSeeSeparateStatement'], 0, 'L', 0);
    }

    protected function printRows()
    {
        $pdf = $this->pdf;
        $invoiceData = $this->invoiceData;

        if ($this->separateStatement) {
            $pdf->AddPage();
            $pdf->SetAutoPageBreak(TRUE, 22);

            $pdf->SetFont('Helvetica', 'B', 20);
            $pdf->SetXY(4, $pdf->GetY());
            $pdf->Cell(80, 5, $GLOBALS['locPDFInvoiceStatement'], 0, 0, 'L');
            $pdf->SetFont('Helvetica', '', 10);
            $pdf->SetX(115);

            if ($this->printStyle == 'dispatch')
                $locStr = 'DispatchNote';
            elseif ($this->printStyle == 'receipt')
                $locStr = 'Receipt';
            else
                $locStr = 'Invoice';

            $pdf->Cell(40, 5, $GLOBALS["locPDF${locStr}Number"] . ': ', 0, 0, 'R');
            $pdf->Cell(60, 5, $invoiceData['invoice_no'], 0, 1);
            $pdf->SetXY(7, $pdf->GetY() + 10);
        } elseif ($this->printStyle != 'invoice') {
            $pdf->printFooterOnFirstPage = true;
            $pdf->SetAutoPageBreak(TRUE, $this->printStyle == 'receipt' ? 32 : 22);
        }

        if ($this->printStyle == 'dispatch')
            $nameColWidth = 120;
        else {
            if ($this->senderData['vat_registered'])
                $nameColWidth = 80;
            else
                $nameColWidth = 130;
        }

        $showDate = getSetting('invoice_show_row_date');
        if ($this->discountedRows)
            $left = 4;
        else
            $left = 10;
        $pdf->SetX($left);
        if ($showDate) {
            $pdf->Cell($nameColWidth - 20, 5, $GLOBALS['locPDFRowName'], 0, 0, 'L');
            $pdf->Cell(20, 5, $GLOBALS['locPDFRowDate'], 0, 0, 'L');
        } else {
            $pdf->Cell($nameColWidth, 5, $GLOBALS['locPDFRowName'], 0, 0, 'L');
        }
        if ($this->printStyle != 'dispatch') {
            $pdf->Cell(17, 5, $GLOBALS['locPDFRowPrice'], 0, 0, 'R');
            if ($this->discountedRows)
                $pdf->Cell(12, 5, $GLOBALS['locPDFRowDiscount'], 0, 0, 'R');
        }
        $pdf->Cell(20, 5, $GLOBALS['locPDFRowPieces'], 0, 0, 'R');
        if ($this->printStyle != 'dispatch') {
            if ($this->senderData['vat_registered']) {
                $pdf->MultiCell(20, 5, $GLOBALS['locPDFRowTotalVATLess'], 0, 'R', 0,
                    0);
                $pdf->Cell(15, 5, $GLOBALS['locPDFRowVATPercent'], 0, 0, 'R');
                $pdf->Cell(15, 5, $GLOBALS['locPDFRowTax'], 0, 0, 'R');
            }
            $pdf->Cell(20, 5, $GLOBALS['locPDFRowTotal'], 0, 1, 'R');
        } else {
            $pdf->Cell(20, 5, '', 0, 1, 'R'); // line feed
        }

        $descMaxHeight = getSetting('invoice_row_description_first_line_only', false)
            ? 5 : 0;

        $pdf->SetY($pdf->GetY() + 5);
        foreach ($this->invoiceRowData as $row) {
            if (!$this->separateStatement && $this->printStyle == 'invoice' &&
                 $pdf->GetY() > $this->invoiceRowMaxY) {
                $this->separateStatement = true;
                $this->printInvoice();
                exit();
            }
            $partial = $row['partial_payment'];

            // Product / description
            $description = '';
            switch ($row['reminder_row']) {
            case 1 :
                $description = $GLOBALS['locPDFPenaltyInterestDesc'];
                break;
            case 2 :
                $description = $GLOBALS['locPDFReminderFeeDesc'];
                break;
            default :
                if ($partial) {
                    $description = $GLOBALS['locPDFPartialPaymentDesc'];
                } elseif ($row['product_name']) {
                    if ($row['description'])
                        $description = $row['product_name'] . ' (' .
                             $row['description'] . ')';
                    else
                        $description = $row['product_name'];
                    if (getSetting('invoice_display_product_codes') &&
                         $row['product_code']) {
                        $description = $row['product_code'] . ' ' . $description;
                    }
                } else {
                    $description = $row['description'];
                }
            }

            // Sums
            if ($partial) {
                $rowSum = $rowSumVAT = $row['price'];
                $rowVAT = 0;
            } else {
                list ($rowSum, $rowVAT, $rowSumVAT) = calculateRowSum($row['price'],
                    $row['pcs'], $row['vat'], $row['vat_included'], $row['discount']);
                if ($row['vat_included'])
                    $row['price'] /= (1 + $row['vat'] / 100);
            }

            if ($row['price'] == 0 && $row['pcs'] == 0) {
                $pdf->SetX($left);
                $pdf->MultiCell(0, 5, $description, 0, 'L', false, 1, '', '', true, 0, false, true, $descMaxHeight);
            } else {
                if ($showDate) {
                    $pdf->SetX($nameColWidth - 20 + $left);
                    $pdf->Cell(20, 5, $this->_formatDate($row['row_date']), 0, 0,
                        'L');
                } else {
                    $pdf->SetX($nameColWidth + $left);
                }
                if ($this->printStyle != 'dispatch') {
                    $decimals = isset($row['price_decimals']) ? $row['price_decimals'] : 2;
                    $pdf->Cell(17, 5,
                        $partial ? '' : $this->_formatCurrency($row['price'], $decimals), 0, 0, 'R');
                    if ($this->discountedRows)
                        $pdf->Cell(12, 5,
                            (isset($row['discount']) && $row['discount'] != '0') ? $this->_formatCurrency(
                                $row['discount'], 2, true) : '', 0, 0, 'R');
                }
                $pdf->Cell(13, 5, $partial ? '' : $this->_formatNumber($row['pcs'], 2, true), 0, 0,
                    'R');
                $pdf->Cell(7, 5,
                    isset($GLOBALS["locPDF{$row['type']}"]) ? $GLOBALS["locPDF{$row['type']}"] : $row['type'],
                    0, 0, 'L');
                if ($this->printStyle != 'dispatch') {
                    if ($this->senderData['vat_registered']) {
                        $pdf->Cell(20, 5, $partial ? '' : $this->_formatCurrency($rowSum), 0, 0, 'R');
                        $pdf->Cell(11, 5,
                            $partial ? '' : $this->_formatNumber($row['vat'], 1, true), 0, 0, 'R');
                        $pdf->Cell(4, 5, '', 0, 0, 'R');
                        $pdf->Cell(15, 5, $partial ? '' : $this->_formatCurrency($rowVAT), 0, 0, 'R');
                    }
                    $pdf->Cell(20, 5, $this->_formatCurrency($rowSumVAT), 0, 0, 'R');
                }
                $pdf->SetX($left);
                if ($showDate) {
                    $pdf->MultiCell($nameColWidth - 20, 5, $description, 0, 'L', false, 1, '', '', true, 0, false, true, $descMaxHeight);
                } else {
                    $pdf->MultiCell($nameColWidth, 5, $description, 0, 'L', false, 1, '', '', true, 0, false, true, $descMaxHeight);
                }

                if ($this->printStyle == 'dispatch' &&
                     getSetting('dispatch_note_show_barcodes') &&
                     ((!empty($row['barcode1']) && !empty($row['barcode1_type'])) ||
                     (!empty($row['barcode2']) && !empty($row['barcode2_type'])))) {
                    $style = [
                        'position' => '',
                        'align' => 'L',
                        'stretch' => false,
                        'fitwidth' => true,
                        'cellfitalign' => '',
                        'border' => false,
                        'hpadding' => 'auto',
                        'vpadding' => 'auto',
                        'fgcolor' => [
                            0,
                            0,
                            0
                        ],
                        'bgcolor' => false,
                        'text' => true,
                        'font' => 'helvetica',
                        'fontsize' => 8,
                        'stretchtext' => 4
                    ];
                    //
                    if (!empty($row['barcode1']) && !empty($row['barcode1_type'])) {
                        $pdf->write1DBarcode($row['barcode1'],
                            $row['barcode1_type'], $left, $pdf->getY(), 98, 15, 0.34,
                            $style, 'T');
                    }
                    if (!empty($row['barcode2']) && !empty($row['barcode2_type'])) {
                        $pdf->write1DBarcode($row['barcode2'],
                            $row['barcode2_type'], $left + 98, $pdf->getY(), 105, 15,
                            0.34, $style, 'T');
                    }
                    $pdf->SetY($pdf->GetY() + 18);
                }
            }
        }
        if ($this->printStyle != 'dispatch') {
            if ($this->senderData['vat_registered']) {
                $pdf->SetFont('Helvetica', '', 10);
                $pdf->SetY($pdf->GetY() + 10);
                $pdf->Cell(162, 5, $GLOBALS['locPDFTotalExcludingVAT'] . ': ', 0, 0,
                    'R');
                $pdf->SetX(187 - $left);
                $pdf->Cell(20, 5, $this->_formatCurrency($this->totalSum), 0, 0, 'R');

                $pdf->SetFont('Helvetica', '', 10);
                $pdf->SetY($pdf->GetY() + 5);
                $pdf->Cell(162, 5, $GLOBALS['locPDFTotalVAT'] . ': ', 0, 0, 'R');
                $pdf->SetX(187 - $left);
                $pdf->Cell(20, 5, $this->_formatCurrency($this->totalVAT), 0, 0, 'R');

                $pdf->SetY($pdf->GetY() + 5);
                $pdf->Cell(162, 5, $GLOBALS['locPDFTotalIncludingVAT'] . ': ', 0, 0,
                    'R');
                $pdf->SetX(187 - $left);
                $pdf->Cell(20, 5, $this->_formatCurrency($this->totalSumVAT), 0, 0,
                    'R');

                $pdf->SetFont('Helvetica', 'B', 10);
                $pdf->SetY($pdf->GetY() + 5);
                $pdf->Cell(162, 5, $GLOBALS['locPDFTotalToPay'] . ': ', 0, 0, 'R');
                $pdf->SetX(187 - $left);
                $pdf->Cell(
                    20, 5,
                    $this->_formatCurrency(
                        $this->totalSumVAT - $this->partialPayments
                    ),
                    0, 1, 'R'
                );
            } else {
                $pdf->SetY($pdf->GetY() + 5);
                $pdf->Cell(162, 5, $GLOBALS['locPDFTotalPrice'] . ': ', 0, 0, 'R');
                $pdf->SetX(187 - $left);
                $pdf->Cell(20, 5, $this->_formatCurrency($this->totalSumVAT), 0, 0,
                    'R');

                $pdf->SetFont('Helvetica', 'B', 10);
                $pdf->SetY($pdf->GetY() + 5);
                $pdf->Cell(162, 5, $GLOBALS['locPDFTotalToPay'] . ': ', 0, 0, 'R');
                $pdf->SetX(187 - $left);
                $pdf->Cell(
                    20, 5,
                    $this->_formatCurrency(
                        $this->totalSumVAT - $this->partialPayments
                    ), 0, 1, 'R'
                );
            }
        }
    }

    protected function printForm()
    {
        $pdf = $this->pdf;
        $senderData = $this->senderData;
        $invoiceData = $this->invoiceData;

        $pdf->SetFont('Helvetica', '', 7);
        if ($this->printVirtualBarcode && $this->barcode) {
            $pdf->SetXY(4, 180);
            $pdf->Cell(120, 2.8,
                $GLOBALS['locPDFVirtualBarcode'] . ': ' . $this->barcode, 0, 1, 'L');
        }
        $intStartY = 187;
        $pdf->SetXY(4, $intStartY);
        $pdf->MultiCell(120, 5, $this->senderAddressLine, 0, 'L', 0);
        $pdf->SetXY(75, $intStartY);
        $pdf->MultiCell(65, 5, $this->senderContactInfo, 0, 'C', 0);
        $pdf->SetXY(143, $intStartY);
        $pdf->MultiCell(60, 5, $senderData['www'] . "\n" . $senderData['email'], 0,
            'R', 0);

        // Invoice form
        $intStartY = $intStartY + 8;
        $intStartX = 3.6;

        $intMaxX = 210 - $intStartX;
        // 1. hor.line - full width
        $pdf->SetLineWidth(0.13);
        $pdf->Line($intStartX, $intStartY - 0.5, $intMaxX, $intStartY - 0.5);
        $pdf->SetLineWidth(0.50);
        // 2. hor.line - full width
        $pdf->Line($intStartX, $intStartY + 16, $intMaxX, $intStartY + 16);
        // 3. hor.line - start-half page
        $pdf->Line($intStartX, $intStartY + 32, $intStartX + 111.4, $intStartY + 32);
        // 4. hor.line - half-end page
        $pdf->Line($intStartX + 111.4, $intStartY + 57.5, $intMaxX,
            $intStartY + 57.5);
        // 5. hor.line - full width
        $pdf->Line($intStartX, $intStartY + 66, $intMaxX, $intStartY + 66);
        // 6. hor.line - full width
        $pdf->Line($intStartX, $intStartY + 74.5, $intMaxX, $intStartY + 74.5);

        // 1. ver.line - 1.hor - 3.hor
        $pdf->Line($intStartX + 20, $intStartY, $intStartX + 20, $intStartY + 32);
        // 2. ver.line - 5.hor - 6.hor
        $pdf->Line($intStartX + 20, $intStartY + 66, $intStartX + 20,
            $intStartY + 74.5);
        // 3. ver.line - full height
        $pdf->Line($intStartX + 111.4, $intStartY, $intStartX + 111.4,
            $intStartY + 74.5);
        // 4. ver.line - 4.hor - 6. hor
        $pdf->Line($intStartX + 130, $intStartY + 57.5, $intStartX + 130,
            $intStartY + 74.5);
        // 5. ver.line - 5.hor - 6. hor
        $pdf->Line($intStartX + 160, $intStartY + 66, $intStartX + 160,
            $intStartY + 74.5);

        // signature
        $pdf->SetLineWidth(0.13);
        $pdf->Line($intStartX + 23, $intStartY + 63, $intStartX + 90,
            $intStartY + 63);

        // bank
        $pdf->SetFont('Helvetica', '', 7);
        $pdf->SetXY($intStartX, $intStartY + 1);
        $pdf->MultiCell(19, 2.8, $GLOBALS['locPDFFormRecipientAccountNumber1'], 0,
            'R', 0);
        $pdf->SetXY($intStartX, $intStartY + 8);
        $pdf->MultiCell(19, 2.8, $GLOBALS['locPDFFormRecipientAccountNumber2'], 0,
            'R', 0);
        $pdf->SetXY($intStartX + 21, $intStartY + 0.5);
        $pdf->Cell(10, 2.8, $GLOBALS['locPDFFormIBAN'], 0, 1, 'L');
        $pdf->SetXY($intStartX + 112.4, $intStartY + 0.5);
        $pdf->Cell(10, 2.8, $GLOBALS['locPDFFormBIC'], 0, 1, 'L');

        // account banks
        $bankX = 0;
        $pdf->SetFont('Helvetica', '', 10);

        $pdf->SetXY($intStartX + 21, $intStartY + 3);
        $pdf->Cell(40, 4, $senderData['bank_name'], 0, 0, 'L');

        $pdf->SetXY($intStartX + 21, $intStartY + 7);
        $pdf->Cell(40, 4, $senderData['bank_name2'], 0, 0, 'L');

        $pdf->SetXY($intStartX + 21, $intStartY + 11);
        $pdf->Cell(40, 4, $senderData['bank_name3'], 0, 0, 'L');

        $bankX = max(
            [
                $pdf->getStringWidth($senderData['bank_name']),
                $pdf->getStringWidth($senderData['bank_name2']),
                $pdf->getStringWidth($senderData['bank_name3'])
            ]);

        // account 1
        $bankX += $intStartX + 21 + 4;
        $pdf->SetXY($bankX, $intStartY + 3);
        $pdf->Cell(86, 4, $senderData['bank_iban'], 0, 0, 'L');
        $pdf->SetX($intStartX + 112.4);
        $pdf->Cell(66, 4, $senderData['bank_swiftbic'], 0, 0, 'L');

        // account 2
        $pdf->SetXY($bankX, $intStartY + 7);
        $pdf->Cell(86, 4, $senderData['bank_iban2'], 0, 0, 'L');
        $pdf->SetX($intStartX + 112.4);
        $pdf->Cell(15, 4, $senderData['bank_swiftbic2'], 0, 0, 'L');

        // account 3
        $pdf->SetXY($bankX, $intStartY + 11);
        $pdf->Cell(86, 4, $senderData['bank_iban3'], 0, 0, 'L');
        $pdf->SetX($intStartX + 112.4);
        $pdf->Cell(66, 4, $senderData['bank_swiftbic3'], 0, 0, 'L');

        // payment recipient
        $pdf->SetFont('Helvetica', '', 7);
        $pdf->SetXY($intStartX, $intStartY + 18);
        $pdf->Cell(19, 5, $GLOBALS['locPDFFormRecipient1'], 0, 1, 'R');
        $pdf->SetXY($intStartX, $intStartY + 22);
        $pdf->Cell(19, 5, $GLOBALS['locPDFFormRecipient2'], 0, 1, 'R');
        $pdf->SetFont('Helvetica', '', 10);
        $pdf->SetXY($intStartX + 21, $intStartY + 18);
        $pdf->MultiCell(100, 4, $this->senderAddress, 0, 1);

        // payer
        $pdf->SetFont('Helvetica', '', 7);
        $pdf->SetXY($intStartX, $intStartY + 35);
        $pdf->MultiCell(19, 2.8, $GLOBALS['locPDFFormPayerNameAndAddress1'], 0, 'R',
            0);
        $pdf->SetXY($intStartX, $intStartY + 45);
        $pdf->MultiCell(19, 2.8, $GLOBALS['locPDFFormPayernameAndAddress2'], 0, 'R',
            0);
        $pdf->SetFont('Helvetica', '', 10);
        $pdf->SetXY($intStartX + 21, $intStartY + 35);
        $pdf->MultiCell(100, 4, $this->recipientFullAddress, 0, 1);

        // signature
        $pdf->SetFont('Helvetica', '', 7);
        $pdf->SetXY($intStartX, $intStartY + 59);
        $pdf->MultiCell(19, 6, $GLOBALS['locPDFFormSignature'], 0, 'R', 0);

        // from account
        $pdf->SetXY($intStartX, $intStartY + 67);
        $pdf->MultiCell(19, 6, $GLOBALS['locPDFFormFromAccount'], 0, 'R', 0);

        // info
        $pdf->SetFont('Helvetica', '', 10);
        $pdf->SetXY($intStartX + 112.4, $intStartY + 20);
        $pdf->Cell(70, 5,
            sprintf($GLOBALS['locPDFFormInvoiceNumber'], $invoiceData['invoice_no']),
            0, 1, 'L');
        if ($this->refNumber) {
            $pdf->SetXY($intStartX + 112.4, $intStartY + 30);
            $pdf->Cell(70, 5, $GLOBALS['locPDFFormRefNumberMandatory1'], 0, 1, 'L');
            $pdf->SetXY($intStartX + 112.4, $intStartY + 35);
            $pdf->Cell(70, 5, $GLOBALS['locPDFFormRefNumberMandatory2'], 0, 1, 'L');
        }
        // terms
        $pdf->SetFont('Helvetica', '', 5);
        $pdf->SetXY($intStartX + 133, $intStartY + 85);
        $pdf->MultiCell(70, 2, $GLOBALS['locPDFFormClearingTerms1'], 0, 1);
        $pdf->SetXY($intStartX + 133, $intStartY + 90);
        $pdf->MultiCell(70, 2, $GLOBALS['locPDFFormClearingTerms2'], 0, 1);
        $pdf->SetFont('Helvetica', '', 6);
        $pdf->SetXY($intStartX + 133, $intStartY + 95);
        $pdf->Cell($intMaxX + 1 - 133 - $intStartX, 5, $GLOBALS['locPDFFormBank'], 0,
            1, 'R');

        $pdf->SetFont('Helvetica', '', 7);
        // refnr
        $pdf->SetFont('Helvetica', '', 7);
        $pdf->SetXY($intStartX + 112.4, $intStartY + 58);
        $pdf->MultiCell(15, 6, $GLOBALS['locPDFFormReferenceNumber'], 0, 'L', 0);
        if ($this->refNumber) {
            $pdf->SetFont('Helvetica', '', 10);
            $pdf->SetXY($intStartX + 131, $intStartY + 59);
            $pdf->Cell(15, 5, $this->refNumber, 0, 1, 'L');
        }

        // due date
        $pdf->SetFont('Helvetica', '', 7);
        $pdf->SetXY($intStartX + 112.4, $intStartY + 67);
        $pdf->MultiCell(15, 6, $GLOBALS['locPDFFormDueDate'], 0, 'L', 0);
        $pdf->SetFont('Helvetica', '', 10);
        $pdf->SetXY($intStartX + 131.4, $intStartY + 68);
        $pdf->Cell(25, 5,
            ($invoiceData['state_id'] == 5 || $invoiceData['state_id'] == 6) ? $GLOBALS['locPDFFormDueDateNOW'] : $this->_formatDate(
                $invoiceData['due_date']), 0, 1, 'L');

        // amount
        $pdf->SetFont('Helvetica', '', 7);
        $pdf->SetXY($intStartX + 161, $intStartY + 67);
        $pdf->MultiCell(15, 6, $GLOBALS['locPDFFormCurrency'], 0, 'L', 0);
        $pdf->SetFont('Helvetica', '', 10);
        $pdf->SetXY($intStartX + 151, $intStartY + 68);
        $pdf->Cell(40, 5, $this->_formatNumber($this->totalSumVAT - $this->partialPayments), 0, 1, 'R');

        if (getSetting('invoice_show_barcode') && $this->barcode) {
            $style = [
                'position' => '',
                'align' => 'C',
                'stretch' => true,
                'fitwidth' => true,
                'cellfitalign' => '',
                'border' => false,
                'hpadding' => 'auto',
                'vpadding' => 'auto',
                'fgcolor' => [
                    0,
                    0,
                    0
                ],
                'bgcolor' => false,
                'text' => false,
                'font' => 'helvetica',
                'fontsize' => 8,
                'stretchtext' => 4
            ];
            $pdf->write1DBarcode($this->barcode, 'C128C', 20, 284, 105, 11, 0.34,
                $style, 'N');
        }
    }

    protected function printOut()
    {
        $pdf = $this->pdf;
        $invoiceData = $this->invoiceData;

        $filename = $this->getPrintOutFileName();
        $pdf->Output($filename, 'I');
    }

    protected function _formatDate($date)
    {
        return dateConvDBDate2Date($date, $GLOBALS['locPDFDateFormat']);
    }

    protected function _formatNumber($value, $decimals = 2, $decimalsOptional = false)
    {
        if ($decimalsOptional) {
            return miscRound2OptDecim($value, $decimals,
                $GLOBALS['locPDFDecimalSeparator'],
                $GLOBALS['locPDFThousandSeparator']);
        }
        return miscRound2Decim($value, $decimals, $GLOBALS['locPDFDecimalSeparator'],
            $GLOBALS['locPDFThousandSeparator']);
    }

    protected function _formatCurrency($value, $decimals = 2,
        $decimalsOptional = false)
    {
        $number = $this->_formatNumber($value, $decimals, $decimalsOptional);
        return $GLOBALS['locPDFCurrencyPrefix'] . $number .
             $GLOBALS['locPDFCurrencySuffix'];
    }

    protected function getPlaceholderData($placeholders)
    {
        $values = [];
        foreach ($placeholders as $placeholder) {
            $placeholder = substr(substr($placeholder, 0, -1), 1);
            $pcparts = explode(':', $placeholder);
            switch ($pcparts[0]) {
            case 'sender' :
                $values[] = isset($this->senderData[$pcparts[1]]) ? $this->senderData[$pcparts[1]] : '';
                break;
            case 'recipient' :
                $values[] = isset($this->recipientData[$pcparts[1]]) ? $this->recipientData[$pcparts[1]] : '';
                break;
            case 'invoice' :
                switch ($pcparts[1]) {
                case 'totalsum' :
                    $values[] = $this->_formatCurrency($this->totalSum);
                    break;
                case 'totalvat' :
                    $values[] = $this->_formatCurrency($this->totalVAT);
                    break;
                case 'totalsumvat' :
                    $values[] = $this->_formatCurrency($this->totalSumVAT);
                    break;
                case 'totalunpaid' :
                    $values[] = $this->_formatCurrency($this->totalSumVAT - $this->partialPayments);
                    break;
                case 'ref_number' :
                    $values[] = $this->refNumber;
                    break; // formatted reference number
                case 'barcode' :
                    $values[] = $this->barcode;
                    break;
                case 'printout_type' :
                case 'printout_type_caps' :
                    if ($this->printStyle == 'dispatch') {
                        $str = $GLOBALS['locPDFDispatchNote'];
                    } elseif ($this->printStyle == 'receipt') {
                        $str = $GLOBALS['locPDFReceipt'];
                    } elseif ($this->printStyle == 'order_confirmation') {
                        $str = $GLOBALS['locPDFOrderConfirmation'];
                    } elseif ($this->invoiceData['state_id'] == 5) {
                        $str = $GLOBALS['locPDFFirstReminder'];
                    } elseif ($this->invoiceData['state_id'] == 6) {
                        $str = $GLOBALS['locPDFSecondReminder'];
                    } else {
                        $str = $GLOBALS['locPDFInvoice'];
                    }
                    if ($pcparts[1] == 'printout_type_caps') {
                        $str = ucwords($str);
                    }
                    $values[] = $str;
                    break;
                default :
                    $value = isset($this->invoiceData[$pcparts[1]]) ? $this->invoiceData[$pcparts[1]] : '';
                    if (substr($pcparts[1], -5) == '_date') {
                        $value = $this->_formatDate($value);
                    }
                    $values[] = $value;
                }
                break;
            case 'config' :
                $values[] = getSetting($pcparts[1]);
                break;
            default :
                error_log(
                    "Unknown placeholder '$placeholder' in invoice email fields");
                $values[] = '';
            }
        }
        return implode('', $values);
    }

    protected function replacePlaceholders($string)
    {
        return preg_replace_callback('/\{\w+:\w+\}/',
            [
                $this,
                'getPlaceholderData'
            ], $string);
    }

    protected function getPrintOutFileName($filename = '')
    {
        // Replace the %d style placeholder
        $filename = sprintf($filename ? $filename : $this->outputFileName,
            $this->invoiceData['invoice_no']);
        // Handle additional placeholders
        $filename = $this->replacePlaceholders($filename);
        return $filename;
    }
}
