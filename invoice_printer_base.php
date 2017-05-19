<?php
/*******************************************************************************
 MLInvoice: web-based invoicing application.
 Copyright (C) 2010-2017 Ere Maijala

 Portions based on:
 PkLasku : web-based invoicing software.
 Copyright (C) 2004-2008 Samu Reinikainen

 This program is free software. See attached LICENSE.

 *******************************************************************************/

/*******************************************************************************
 MLInvoice: web-pohjainen laskutusohjelma.
 Copyright (C) 2010-2017 Ere Maijala

 Perustuu osittain sovellukseen:
 PkLasku : web-pohjainen laskutusohjelmisto.
 Copyright (C) 2004-2008 Samu Reinikainen

 Tämä ohjelma on vapaa. Lue oheinen LICENSE.

 *******************************************************************************/
require_once 'translator.php';
require_once 'settings.php';

abstract class InvoicePrinterBase
{
    protected $pdf = null;
    protected $invoiceId = null;
    protected $printStyle = '';
    protected $printLanguage = 'fi';
    protected $senderData = null;
    protected $recipientData = null;
    protected $recipientContactData = null;
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
    protected $invoiceRowMaxY = 185;
    protected $senderAddressX = 0;
    protected $senderAddressY = 0;
    protected $recipientAddressX = 0;
    protected $recipientAddressY = 0;
    protected $partialPayments = 0;
    protected $dateOverride = false;
    protected $allowSeparateStatement = true;
    protected $autoPageBreak = false;

    public function __construct()
    {
    }

    public function getReadOnlySafe()
    {
        return $this->readOnlySafe;
    }

    public function init($invoiceId, $printParameters, $outputFileName, $senderData,
        $recipientData, $invoiceData, $invoiceRowData, $recipientContactData,
        $dateOverride
    ) {
        if (empty($recipientData)) {
            $recipientData = [
                'company_name' => '',
                'company_id' => '',
                'vat_id' => '',
                'customer_no' => '',
                'street_address' => '',
                'zip_code' => '',
                'city' => '',
                'billing_address' => '',
                'email' => ''
            ];
        }

        $this->dateOverride = $dateOverride;
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
        $this->recipientContactData = $recipientContactData;

        Translator::setActiveLanguage('invoice', $this->printLanguage);

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

            list ($rowSum, $rowVAT, $rowSumVAT) = calculateRowSum($row);
            $this->invoiceRowData[$key]['rowsum'] = $rowSum;
            $this->invoiceRowData[$key]['rowvat'] = $rowVAT;
            $this->invoiceRowData[$key]['rowsumvat'] = $rowSumVAT;
            $this->totalSum += $rowSum;
            $this->totalVAT += $rowVAT;
            $this->totalSumVAT += $rowSumVAT;
            if ($row['discount'] || $row['discount_amount']) {
                $this->discountedRows = true;
            }

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
            $strCompanyID = Translator::translate('invoice::VATID') . ": $strCompanyID";
        if ($strCompanyID)
            $strCompanyID .= ', ';
        if ($senderData['vat_registered'])
            $strCompanyID .= Translator::translate('invoice::VATReg');
        else
            $strCompanyID .= Translator::translate('invoice::NonVATReg');
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
            $this->senderContactInfo = "\n" . Translator::translate('invoice::Phone') . ' ' .
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
            if (ctype_digit($tmpRefNumber) == 0 || (strncmp($tmpRefNumber, 'RF', 2) == 0 &&
                 ctype_digit(substr($tmpRefNumber, 2) == 0))) {
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

        $this->senderAddressX = 10 + getSetting('invoice_address_x_offset', 0);
        $this->senderAddressY = 20 + getSetting('invoice_address_y_offset', 0);
        $this->recipientAddressX = 10 +
            getSetting('invoice_recipient_address_x_offset', 0);
        $this->recipientAddressY = 40 +
            getSetting('invoice_recipient_address_y_offset', 0);

        $this->autoPageBreak = $this->printStyle != 'invoice' ? 22 : 0;
    }

    public function printInvoice()
    {
        $this->initPDF();

        $this->printSender();

        $this->printRecipient();

        $this->printInfo();

        $this->printSeparatorLine();

        $this->printForeword();

        $savePdf = clone($this->pdf);
        if (!$this->separateStatement) {
            $this->printRows();
        } else {
            $this->printSeparateStatementMessage();
        }
        $this->printAfterword();

        if ($this->printStyle == 'invoice') {
            if (!$this->separateStatement && $this->allowSeparateStatement) {
                if ($this->pdf->getY() > $this->invoiceRowMaxY) {
                    $this->pdf = $savePdf;
                    $this->separateStatement = true;
                    $this->printSeparateStatementMessage();
                    $this->printAfterword();
                }
            }
            $this->printForm();
        }

        if ($this->separateStatement) {
            $this->printRows();
        }


        $this->printOut();
    }

    protected function initPDF()
    {
        $pdf = new PDF('P', 'mm', 'A4', _CHARSET_ == 'UTF-8', _CHARSET_, false);
        $pdf->AddPage();
        $pdf->SetAutoPageBreak(
            $this->autoPageBreak ? true : false, $this->autoPageBreak
        );
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

            $pdf->Image('@' . $senderData['logo_filedata'],
                $senderData['logo_left'], $senderData['logo_top'],
                $senderData['logo_width'], 0, '', '', 'N', false, 300, '', false,
                false, 0, true);
        }
        if (!isset($senderData['logo_filedata'])
            || getSetting('invoice_print_senders_logo_and_address')
        ) {
            $width = getSetting('invoice_address_max_width');
            $address = $senderData['street_address'] . "\n" . $senderData['zip_code'] .
                 ' ' . $senderData['city'] . "\n" . $senderData['country'];
            $pdf->SetTextColor(125);
            $pdf->SetFont('Helvetica', 'B', 10);
            $pdf->SetY($this->senderAddressY);
            $pdf->setX($this->senderAddressX);
            $pdf->MultiCell($width, 5, $senderData['name'], 0, 'L');
            $pdf->SetFont('Helvetica', '', 10);
            $pdf->setX($this->senderAddressX);
            $pdf->MultiCell($width, 5, $address, 0, 'L');
        }
    }

    protected function printRecipient()
    {
        $pdf = $this->pdf;
        $recipientData = $this->recipientData;

        $width = getSetting('invoice_address_max_width');
        $pdf->SetTextColor(0);
        $pdf->SetFont('Helvetica', '', 12);
        $pdf->SetY($this->recipientAddressY);
        $pdf->setX($this->recipientAddressX);
        $pdf->MultiCell($width, 5, $this->recipientName, 0, 'L');
        $contact = $this->getContactPerson();
        if (!empty($contact['contact_person'])
            && getSetting('invoice_show_recipient_contact_person')
        ) {
            $pdf->setX($this->recipientAddressX);
            $pdf->MultiCell($width, 5, $contact['contact_person'], 0, 'L');
        }
        $pdf->setX($this->recipientAddressX);
        $pdf->MultiCell($width, 5, $this->recipientAddress, 0, 'L');
        if ($recipientData['email'] && getSetting('invoice_show_recipient_email')) {
            $pdf->SetY($pdf->GetY() + 4);
            $pdf->setX($this->recipientAddressX);
            $pdf->MultiCell($width, 5, $recipientData['email'], 0, 'L');
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
        $pdf->Cell(40, 5, $this->getHeaderTitle(), 0, 1, 'R');
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
        $pdf->Cell(40, 4, Translator::translate("invoice::${locStr}Number") . ': ', 0, 0, 'R');
        $pdf->Cell(60, 4, $invoiceData['invoice_no'], 0, 1);
        $pdf->SetX(115);
        $pdf->Cell(40, 4, Translator::translate("invoice::${locStr}Date") . ': ', 0, 0, 'R');
        $strInvoiceDate = ($this->dateOverride)
            ? $this->_formatDate($this->dateOverride)
            : $this->_formatDate($invoiceData['invoice_date']);
        $strDueDate = $this->_formatDate($invoiceData['due_date']);
        $pdf->Cell(60, 4, $strInvoiceDate, 0, 1);
        if ($this->printStyle == 'invoice') {
            $pdf->SetX(115);
            $pdf->Cell(40, 4, Translator::translate('invoice::DueDate') . ': ', 0, 0, 'R');
            $pdf->Cell(60, 4, $strDueDate, 0, 1);
            $pdf->SetX(115);
            $pdf->Cell(40, 4, Translator::translate('invoice::TermsOfPayment') . ': ', 0, 0, 'R');
            $paymentDays = round(
                dbDate2UnixTime($invoiceData['due_date']) / 3600 / 24 -
                     dbDate2UnixTime($invoiceData['invoice_date']) / 3600 / 24);
            if ($paymentDays < 0) {
                // This shouldn't happen, but try to be safe...
                $paymentDays = getPaymentDays($invoiceData['company_id']);
            }
            $pdf->Cell(60, 4, $this->getTermsOfPayment($paymentDays), 0, 1);
            $pdf->SetX(115);
            $pdf->Cell(40, 4, Translator::translate('invoice::PeriodForComplaints') . ': ', 0, 0,
                'R');
            $pdf->Cell(60, 4, $this->getPeriodForComplaints(), 0, 1);
            $pdf->SetX(115);
            $pdf->Cell(40, 4, Translator::translate('invoice::PenaltyInterest') . ': ', 0, 0, 'R');
            $pdf->Cell(60, 4,
                $this->_formatNumber(getSetting('invoice_penalty_interest'), 1, true) .
                     ' %', 0, 1);
            $pdf->SetX(115);
            if ($this->refNumber) {
                $pdf->Cell(40, 4, Translator::translate('invoice::InvoiceRefNr') . ': ', 0, 0, 'R');
                $pdf->Cell(60, 4, $this->refNumber, 0, 1);
            }
        }

        if ($invoiceData['reference']) {
            $pdf->SetX(115);
            $pdf->Cell(40, 4, Translator::translate('invoice::YourReference') . ': ', 0, 0, 'R');
            $pdf->MultiCell(50, 4, $invoiceData['reference'], 0, 'L');
        }
        if (isset($invoiceData['info']) && $invoiceData['info']) {
            $pdf->SetX(115);
            $pdf->Cell(40, 4, Translator::translate('invoice::AdditionalInformation') . ': ', 0, 0,
                'R');
            $pdf->MultiCell(50, 4, $invoiceData['info'], 0, 'L', 0);
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

    protected function printSeparatorLine()
    {
        $pdf = $this->pdf;
        $pdf->SetY(max($pdf->GetY(), $this->recipientMaxY) + 5);
        $pdf->Line(5, $pdf->GetY(), 202, $pdf->GetY());
        $pdf->SetY($pdf->GetY() + 5);
    }

    protected function printForeword()
    {
        if (empty($this->invoiceData['foreword'])) {
            return;
        }

        $pdf = $this->pdf;

        $foreword = $this->replacePlaceholders($this->invoiceData['foreword']);
        $pdf->SetTextColor(0);
        $pdf->SetFont('Helvetica', '', 10);
        $pdf->MultiCell(180, 5, $foreword, 0, 'L', 0);
        $pdf->setY($pdf->getY() + 5);
    }

    protected function printAfterword()
    {
        if (empty($this->invoiceData['afterword'])) {
            return;
        }

        $pdf = $this->pdf;

        $afterword = $this->replacePlaceholders($this->invoiceData['afterword']);
        $pdf->SetTextColor(0);
        $pdf->SetFont('Helvetica', '', 10);
        $pdf->SetY($pdf->GetY() + 5);
        $pdf->MultiCell(180, 5, $afterword, 0, 'L', 0);
    }

    protected function printSeparateStatementMessage()
    {
        $pdf = $this->pdf;
        $pdf->SetFont('Helvetica', 'B', 20);
        $pdf->MultiCell(180, 5, Translator::translate('invoice::SeeSeparateStatement'), 0, 'L', 0);
    }

    protected function printRows()
    {
        $pdf = $this->pdf;
        $invoiceData = $this->invoiceData;

        if ($this->separateStatement) {
            $pdf->AddPage();
            $pdf->SetAutoPageBreak(TRUE, 22);

            $pdf->SetFont('Helvetica', 'B', 20);
            $pdf->SetXY($this->discountedRows ? 4 : 10, $pdf->GetY());
            $pdf->Cell(80, 5, Translator::translate('invoice::InvoiceStatement'), 0, 0, 'L');
            $pdf->SetFont('Helvetica', '', 10);
            $pdf->SetX(115);

            if ($this->printStyle == 'dispatch')
                $locStr = 'DispatchNote';
            elseif ($this->printStyle == 'receipt')
                $locStr = 'Receipt';
            else
                $locStr = 'Invoice';

            $pdf->Cell(
                82, 5,
                Translator::translate("invoice::${locStr}Number") . ': '
                . $invoiceData['invoice_no'],
                0, 0, 'R'
            );
            $pdf->SetXY(7, $pdf->GetY() + 10);
        } elseif ($this->printStyle != 'invoice') {
            $pdf->printFooterOnFirstPage = true;
            $pdf->SetAutoPageBreak(true, 22);
        }

        if ($this->printStyle == 'dispatch')
            $nameColWidth = 120;
        else {
            if ($this->senderData['vat_registered']) {
                $nameColWidth = 77;
            } else {
                $nameColWidth = 127;
            }
        }

        $showDate = getSetting('invoice_show_row_date');
        if ($this->discountedRows) {
            $left = 4;
            $nameColWidth -= 8;
        } else {
            $left = 10;
        }
        $pdf->SetX($left);
        if ($showDate) {
            $pdf->Cell($nameColWidth - 20, 5, Translator::translate('invoice::RowName'), 0, 0, 'L');
            $pdf->Cell(20, 5, Translator::translate('invoice::RowDate'), 0, 0, 'L');
        } else {
            $pdf->Cell($nameColWidth, 5, Translator::translate('invoice::RowName'), 0, 0, 'L');
        }
        if ($this->printStyle != 'dispatch') {
            $pdf->Cell(20, 5, Translator::translate('invoice::RowPrice'), 0, 0, 'R');
            if ($this->discountedRows)
                $pdf->Cell(20, 5, Translator::translate('invoice::RowDiscount'), 0, 0, 'R');
        }
        $pdf->Cell(20, 5, Translator::translate('invoice::RowPieces'), 0, 0, 'R');
        if ($this->printStyle != 'dispatch') {
            if ($this->senderData['vat_registered']) {
                $pdf->MultiCell(20, 5, Translator::translate('invoice::RowTotalVATLess'), 0, 'R', 0,
                    0);
                $pdf->Cell(15, 5, Translator::translate('invoice::RowVATPercent'), 0, 0, 'R');
                $pdf->Cell(15, 5, Translator::translate('invoice::RowTax'), 0, 0, 'R');
            }
            $pdf->Cell(20, 5, Translator::translate('invoice::RowTotal'), 0, 1, 'R');
        } else {
            $pdf->Cell(20, 5, '', 0, 1, 'R'); // line feed
        }

        $descMaxHeight = getSetting('invoice_row_description_first_line_only', false)
            ? 5 : 0;

        $pdf->SetY($pdf->GetY() + 5);
        foreach ($this->invoiceRowData as $row) {
            if (!$this->separateStatement && $this->printStyle == 'invoice'
                && $this->allowSeparateStatement
                && $pdf->GetY() > $this->invoiceRowMaxY
            ) {
                $this->separateStatement = true;
                $this->printInvoice();
                exit();
            }
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
                if ($partial) {
                    $description = Translator::translate('invoice::PartialPaymentDesc');
                } elseif ($row['product_name']) {
                    if ($row['description']) {
                        $description = $row['product_name'] . ' (' .
                             $row['description'] . ')';
                    } else {
                        $description = $row['product_name'];
                    }
                    if (getSetting('invoice_display_product_codes')
                        && $row['product_code']
                    ) {
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
                list ($rowSum, $rowVAT, $rowSumVAT) = calculateRowSum($row);
                if ($row['vat_included']) {
                    $row['price'] /= (1 + $row['vat'] / 100);
                }
            }

            $rowMaxY = $pdf->getY() + 5;
            if ($row['price'] == 0 && $row['pcs'] == 0) {
                $pdf->SetX($left);
                $pdf->MultiCell(0, 5, $description, 0, 'L', false, 1, '', '', true, 0, false, true, $descMaxHeight);
            } else {
                if ($showDate) {
                    $pdf->SetX($nameColWidth - 20 + $left);
                    $pdf->Cell(
                        20, 5, $this->_formatDate($row['row_date']), 0, 0, 'L'
                    );
                } else {
                    $pdf->SetX($nameColWidth + $left);
                }
                if ($this->printStyle != 'dispatch') {
                    $decimals = isset($row['price_decimals']) ? $row['price_decimals'] : 2;
                    $pdf->Cell(
                        20, 5,
                        $partial ? '' : $this->_formatCurrency($row['price'], $decimals), 0, 0, 'R'
                    );
                    if ($this->discountedRows) {
                        $x = $pdf->GetX();
                        if ((float)$row['discount']) {
                            $discount = $this->_formatCurrency(
                                $row['discount'], 2, true
                            ) . '%';
                            $pdf->Cell(20, 5, $discount, 0, 0, 'R');
                        } elseif (!(float)$row['discount_amount']) {
                            $pdf->Cell(20, 5, '', 0, 0, 'R');
                        }
                        if ((float)$row['discount_amount']) {
                            $discount = $this->_formatCurrency(
                                $row['discount_amount'], $decimals
                            );
                            if ((float)$row['discount']) {
                                $pdf->SetXY($x, $pdf->GetY() + 5);
                            }
                            $pdf->Cell(20, 5, $discount, 0, 0, 'R');
                            if ((float)$row['discount']) {
                                $pdf->SetXY($x + 20, $pdf->GetY() - 5);
                                $rowMaxY += 5;
                            }
                        }
                    }
                }
                $pdf->Cell(13, 5, $partial ? '' : $this->_formatNumber($row['pcs'], 2, true), 0, 0,
                    'R');
                $pdf->Cell(7, 5,
                    Translator::translate("invoice::{$row['type']}"),
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

                $currentY = $pdf->getY();

                if ($rowMaxY > $currentY) {
                    $pdf->SetY($rowMaxY);
                }
            }
        }
        if ($this->printStyle != 'dispatch') {
            if ($this->invoiceData['invoice_unpaid']) {
                $unpaidAmount = $this->totalSumVAT - $this->partialPayments;
            } else {
                $unpaidAmount = 0;
            }
            if ($this->senderData['vat_registered']) {
                $pdf->SetFont('Helvetica', '', 10);
                $pdf->SetY($pdf->GetY() + 6);
                $pdf->Cell(162, 5, Translator::translate('invoice::TotalExcludingVAT') . ': ', 0, 0,
                    'R');
                $pdf->SetX(187 - $left);
                $pdf->Cell(20, 5, $this->_formatCurrency($this->totalSum), 0, 0, 'R');

                $pdf->SetFont('Helvetica', '', 10);
                $pdf->SetY($pdf->GetY() + 5);
                $pdf->Cell(162, 5, Translator::translate('invoice::TotalVAT') . ': ', 0, 0, 'R');
                $pdf->SetX(187 - $left);
                $pdf->Cell(20, 5, $this->_formatCurrency($this->totalVAT), 0, 0, 'R');

                $pdf->SetY($pdf->GetY() + 5);
                $pdf->Cell(162, 5, Translator::translate('invoice::TotalIncludingVAT') . ': ', 0, 0,
                    'R');
                $pdf->SetX(187 - $left);
                $pdf->Cell(20, 5, $this->_formatCurrency($this->totalSumVAT), 0, 0,
                    'R');

                $pdf->SetFont('Helvetica', 'B', 10);
                $pdf->SetY($pdf->GetY() + 5);
                $pdf->Cell(162, 5, Translator::translate('invoice::TotalToPay') . ': ', 0, 0, 'R');
                $pdf->SetX(187 - $left);
                $pdf->Cell(20, 5, $this->_formatCurrency($unpaidAmount), 0, 1, 'R');
            } else {
                $pdf->SetY($pdf->GetY() + 5);
                $pdf->Cell(162, 5, Translator::translate('invoice::TotalPrice') . ': ', 0, 0, 'R');
                $pdf->SetX(187 - $left);
                $pdf->Cell(20, 5, $this->_formatCurrency($this->totalSumVAT), 0, 0,
                    'R');

                $pdf->SetFont('Helvetica', 'B', 10);
                $pdf->SetY($pdf->GetY() + 5);
                $pdf->Cell(162, 5, Translator::translate('invoice::TotalToPay') . ': ', 0, 0, 'R');
                $pdf->SetX(187 - $left);
                $pdf->Cell(20, 5, $this->_formatCurrency($unpaidAmount), 0, 1, 'R');
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
                Translator::translate('invoice::VirtualBarcode') . ': ' . $this->barcode, 0, 1, 'L');
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
        $pdf->MultiCell(19, 2.8, Translator::translate('invoice::FormRecipientAccountNumber1'), 0,
            'R', 0);
        $pdf->SetXY($intStartX, $intStartY + 8);
        $pdf->MultiCell(19, 2.8, Translator::translate('invoice::FormRecipientAccountNumber2'), 0,
            'R', 0);
        $pdf->SetXY($intStartX + 21, $intStartY + 0.5);
        $pdf->Cell(10, 2.8, Translator::translate('invoice::FormIBAN'), 0, 1, 'L');
        $pdf->SetXY($intStartX + 112.4, $intStartY + 0.5);
        $pdf->Cell(10, 2.8, Translator::translate('invoice::FormBIC'), 0, 1, 'L');

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
        $pdf->Cell(19, 5, Translator::translate('invoice::FormRecipient1'), 0, 1, 'R');
        $pdf->SetXY($intStartX, $intStartY + 22);
        $pdf->Cell(19, 5, Translator::translate('invoice::FormRecipient2'), 0, 1, 'R');
        $pdf->SetFont('Helvetica', '', 10);
        $pdf->SetXY($intStartX + 21, $intStartY + 17);
        $pdf->MultiCell(100, 4, $this->senderAddress, 0, 1);

        // payer
        $pdf->SetFont('Helvetica', '', 7);
        $pdf->SetXY($intStartX, $intStartY + 35);
        $pdf->MultiCell(19, 2.8, Translator::translate('invoice::FormPayerNameAndAddress1'), 0, 'R',
            0);
        $pdf->SetXY($intStartX, $intStartY + 45);
        $pdf->MultiCell(19, 2.8, Translator::translate('invoice::FormPayernameAndAddress2'), 0, 'R',
            0);
        $pdf->SetFont('Helvetica', '', 10);
        $pdf->SetXY($intStartX + 21, $intStartY + 35);
        $pdf->MultiCell(100, 4, $this->recipientFullAddress, 0, 1);

        // signature
        $pdf->SetFont('Helvetica', '', 7);
        $pdf->SetXY($intStartX, $intStartY + 59);
        $pdf->MultiCell(19, 6, Translator::translate('invoice::FormSignature'), 0, 'R', 0);

        // from account
        $pdf->SetXY($intStartX, $intStartY + 67);
        $pdf->MultiCell(19, 6, Translator::translate('invoice::FormFromAccount'), 0, 'R', 0);

        // info
        $pdf->SetFont('Helvetica', '', 10);
        $pdf->SetXY($intStartX + 112.4, $intStartY + 18);
        $pdf->Cell(
            70, 5,
            sprintf(Translator::translate('invoice::FormInvoiceNumber'), $invoiceData['invoice_no']),
            0, 1, 'L'
        );
        $pdf->SetXY($intStartX + 112.4, $intStartY + 25);
        if (getSetting('invoice_show_info_in_form')
            && $this->invoiceData['info']
        ) {
            $pdf->MultiCell(
                70, 4, $this->invoiceData['info'], 0, 'L', 0, 1, '', '', true, 0,
                false, true, $this->refNumber ? 20 : 30
            );
        }
        if ($this->refNumber) {
            $pdf->SetXY($intStartX + 112.4, $pdf->getY() + 3);
            $pdf->Cell(70, 5, Translator::translate('invoice::FormRefNumberMandatory1'), 0, 1, 'L');
            $pdf->SetX($intStartX + 112.4);
            $pdf->Cell(70, 5, Translator::translate('invoice::FormRefNumberMandatory2'), 0, 1, 'L');
        }

        // terms
        $pdf->SetFont('Helvetica', '', 5);
        $pdf->SetXY($intStartX + 133, $intStartY + 85);
        $pdf->MultiCell(70, 2, Translator::translate('invoice::FormClearingTerms1'), 0, 1);
        $pdf->SetXY($intStartX + 133, $intStartY + 90);
        $pdf->MultiCell(70, 2, Translator::translate('invoice::FormClearingTerms2'), 0, 1);
        $pdf->SetFont('Helvetica', '', 6);
        $pdf->SetXY($intStartX + 133, $intStartY + 95);
        $pdf->Cell(
            $intMaxX + 1 - 133 - $intStartX, 5, Translator::translate('invoice::FormBank'), 0, 1, 'R'
        );

        $pdf->SetFont('Helvetica', '', 7);
        // refnr
        $pdf->SetFont('Helvetica', '', 7);
        $pdf->SetXY($intStartX + 112.4, $intStartY + 58);
        $pdf->MultiCell(15, 6, Translator::translate('invoice::FormReferenceNumber'), 0, 'L', 0);
        if ($this->refNumber) {
            $pdf->SetFont('Helvetica', '', 10);
            $pdf->SetXY($intStartX + 131, $intStartY + 59);
            $pdf->Cell(15, 5, $this->refNumber, 0, 1, 'L');
        }

        // due date
        $pdf->SetFont('Helvetica', '', 7);
        $pdf->SetXY($intStartX + 112.4, $intStartY + 67);
        $pdf->MultiCell(15, 6, Translator::translate('invoice::FormDueDate'), 0, 'L', 0);
        $pdf->SetFont('Helvetica', '', 10);
        $pdf->SetXY($intStartX + 131.4, $intStartY + 68);
        $pdf->Cell(25, 5,
            ($invoiceData['state_id'] == 5 || $invoiceData['state_id'] == 6) ? Translator::translate('invoice::FormDueDateNOW') : $this->_formatDate(
                $invoiceData['due_date']), 0, 1, 'L');

        // amount
        $pdf->SetFont('Helvetica', '', 7);
        $pdf->SetXY($intStartX + 161, $intStartY + 67);
        $pdf->MultiCell(15, 6, Translator::translate('invoice::FormCurrency'), 0, 'L', 0);
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
        return dateConvDBDate2Date($date, Translator::translate('invoice::DateFormat'));
    }

    protected function _formatNumber($value, $decimals = 2, $decimalsOptional = false)
    {
        if ($decimalsOptional) {
            return miscRound2OptDecim($value, $decimals,
                Translator::translate('invoice::DecimalSeparator'),
                Translator::translate('invoice::ThousandSeparator'));
        }
        return miscRound2Decim($value, $decimals, Translator::translate('invoice::DecimalSeparator'),
            Translator::translate('invoice::ThousandSeparator'));
    }

    protected function _formatCurrency($value, $decimals = 2,
        $decimalsOptional = false)
    {
        $number = $this->_formatNumber($value, $decimals, $decimalsOptional);
        return Translator::translate('invoice::CurrencyPrefix') . $number .
             Translator::translate('invoice::CurrencySuffix');
    }

    protected function getPlaceholderData($placeholders)
    {
        $values = [];
        foreach ($placeholders as $placeholder) {
            $placeholder = substr(substr($placeholder, 0, -1), 1);
            $pcparts = explode(':', $placeholder);
            switch ($pcparts[0]) {
            case 'sender':
                $values[] = isset($this->senderData[$pcparts[1]]) ? $this->senderData[$pcparts[1]] : '';
                break;
            case 'recipient':
                $values[] = isset($this->recipientData[$pcparts[1]]) ? $this->recipientData[$pcparts[1]] : '';
                break;
            case 'invoice':
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
                        $str = Translator::translate('invoice::DispatchNote');
                    } elseif ($this->printStyle == 'receipt') {
                        $str = Translator::translate('invoice::Receipt');
                    } elseif ($this->printStyle == 'offer') {
                        $str = Translator::translate('invoice::Offer');
                    } elseif ($this->printStyle == 'order_confirmation') {
                        $str = Translator::translate('invoice::OrderConfirmation');
                    } elseif ($this->invoiceData['state_id'] == 5) {
                        $str = Translator::translate('invoice::FirstReminder');
                    } elseif ($this->invoiceData['state_id'] == 6) {
                        $str = Translator::translate('invoice::SecondReminder');
                    } else {
                        $str = Translator::translate('invoice::Invoice');
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
            case 'config':
                $values[] = getSetting($pcparts[1]);
                break;
            case 'contact':
                $contact = $this->getContactPerson();
                if (!empty($contact[$pcparts[1]])) {
                    $values[] = $contact[$pcparts[1]];
                }
                break;
            case 'contacts':
                $contacts = $this->getContactPersons();
                $contactVals = [];
                foreach ($contacts as $contact) {
                    if (!empty($contact[$pcparts[1]])) {
                        $contactVals[] = $contact[$pcparts[1]];
                    }
                }
                if ($contactVals) {
                    $values[] = implode(
                        isset($pcparts[2]) ? $pcparts[2] : ' ', $contactVals
                    );
                }
                break;
            default :
                error_log(
                    "Unknown placeholder '$placeholder' in invoice email fields");
                $values[] = '';
            }
        }
        return implode(' ', $values);
    }

    protected function replacePlaceholders($string)
    {
        return preg_replace_callback('/\{\w+:\w+(:.+?)?\}/',
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

    protected function getHeaderTitle()
    {
        if ($this->printStyle == 'dispatch') {
            return Translator::translate('invoice::DispatchNoteHeader');
        } elseif ($this->printStyle == 'receipt') {
            return Translator::translate('invoice::ReceiptHeader');
        } elseif ($this->invoiceData['state_id'] == 5) {
            return Translator::translate('invoice::FirstReminderHeader');
        } elseif ($this->invoiceData['state_id'] == 6) {
            return Translator::translate('invoice::SecondReminderHeader');
        }
        return Translator::translate('invoice::InvoiceHeader');
    }

    /**
     * Get first contact person for the printout style
     *
     * @return array
     */
    protected function getContactPerson()
    {
        $contacts = $this->getContactPersons();
        return $contacts ? $contacts[0] : [];
    }

    /**
     * Get all contact persons for the printout style
     *
     * @return array
     */
    protected function getContactPersons()
    {
        $results = [];
        $type = $this->printStyle ? $this->printStyle : 'invoice';
        if ($type == 'invoice' && in_array($this->invoiceData['state_id'], [5, 6])) {
            $type = 'reminder';
        }
        foreach ($this->recipientContactData as $contact) {
            if ($contact['contact_type'] == $type) {
                $results[] = $contact;
            }
        }
        return $results;
    }

    /**
     * Get terms of payment string for the invoice
     *
     * @param int $paymentDays Payment days
     *
     * @return string
     */
    protected function getTermsOfPayment($paymentDays)
    {
        if (!empty($this->recipientData['terms_of_payment'])) {
            $result = $this->recipientData['terms_of_payment'];
        } elseif (!empty($this->senderData['terms_of_payment'])) {
            $result = $this->senderData['terms_of_payment'];
        } else {
            $result = getSetting('invoice_terms_of_payment');
        }
        return sprintf($result, $paymentDays);
    }

    /**
     * Get period for complaints for the invoice
     *
     * @return string
     */
    protected function getPeriodForComplaints()
    {
        if (!empty($this->senderData['period_for_complaints'])) {
            return $this->senderData['period_for_complaints'];
        }
        return getSetting('invoice_period_for_complaints');
    }
}
