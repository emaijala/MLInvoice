<?php
/**
 * Invoice printer abstract base class
 *
 * PHP version 5
 *
 * Copyright (C) 2004-2008 Samu Reinikainen
 * Copyright (C) Ere Maijala 2010-2018.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category MLInvoice
 * @package  MLInvoice\Base
 * @author   Samu Reinikainen <not-available@ajassa.fi>
 * @author   Ere Maijala <ere@labs.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://labs.fi/mlinvoice.eng.php
 */
require_once 'translator.php';
require_once 'settings.php';

/**
 * Invoice printer abstract base class
 *
 * @category MLInvoice
 * @package  MLInvoice\Base
 * @author   Samu Reinikainen <not-available@ajassa.fi>
 * @author   Ere Maijala <ere@labs.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://labs.fi/mlinvoice.eng.php
 */
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
    protected $roundRowPrices = false;

    /**
     * Left of the main content
     *
     * @value int
     */
    protected $left = 10;

    /**
     * Width of the main content
     *
     * @value int
     */
    protected $width = 190;

    /**
     * Autofit column padding for rows
     *
     * @value int
     */
    protected $columnPadding = 3;

    /**
     * Left coordinate of the info array
     *
     * @param int
     */
    protected $infoLeft = 115;

    /**
     * Info headings column width
     *
     * @param int
     */
    protected $infoHeadingsWidth = 40;

    /**
     * Info text column width
     *
     * @param int
     */
    protected $infoTextWidth = 48;

    /**
     * Bottom margin for auto page break
     *
     * @var int
     */
    protected $autoPageBreakMargin = 19;

    /**
     * Bottom margin for auto page break on first page
     *
     * @var int
     */
    protected $autoPageBreakMarginFirstPage = 19;

    /**
     * Column definitions in the printout. This includes all possible columns and
     * may be modified in the constructor or init method. Do not remove items from
     * the array.
     *
     * Keys in the array:
     *   heading     The heading (to be translated)
     *   valuemethod A method to retrieve the cell content. Current row is given as
     *               parameter to the method.
     *   visible     Whether the column is visible
     *   align       Alignment of the cell ('L', 'C' or 'R')
     *   width       Column width in mm or 'fill' to use maximum left from other
     *               columns
     *   autofir     If true, width will be adjusted to fit the actual content
     *   maxheight   Maximum height of the cell
     *
     * @param array
     */
    protected $columnDefs = [
        'sequence' => [
            'heading' => 'invoice::RowSequenceNumber',
            'valuemethod' => 'getRowSequenceNumber',
            'visible' => true,
            'align' => 'L',
            'width' => 2,
            'autofit' => true,
            'maxheight' => 0
        ],
        'description' => [
            'heading' => 'invoice::RowName',
            'valuemethod' => 'getRowDescription',
            'visible' => true,
            'align' => 'L',
            'width' => 'fill',
            'maxheight' => 0
        ],
        'date' => [
            'heading' => 'invoice::RowDate',
            'valuemethod' => 'getRowDate',
            'visible' => true,
            'align' => 'L',
            'width' => 20,
            'autofit' => true
        ],
        'price' => [
            'heading' => 'invoice::RowPrice',
            'valuemethod' => 'getRowPrice',
            'visible' => true,
            'align' => 'R',
            'width' => 20,
            'autofit' => true
        ],
        'discount' => [
            'heading' => 'invoice::RowDiscount',
            'valuemethod' => 'getRowDiscount',
            'visible' => true,
            'align' => 'R',
            'width' => 20,
            'autofit' => true
        ],
        'pieces' => [
            'heading' => 'invoice::RowPieces',
            'valuemethod' => 'getRowPieces',
            'visible' => true,
            'align' => 'R',
            'width' => 20,
            'autofit' => true
        ],
        'type' => [
            'heading' => '',
            'valuemethod' => 'getRowItemType',
            'visible' => true,
            'align' => 'L',
            'width' => 10,
            'autofit' => true
        ],
        'totalvatless' => [
            'heading' => 'invoice::RowTotalVATLess',
            'valuemethod' => 'getRowTotalVATLess',
            'visible' => true,
            'align' => 'R',
            'width' => 20,
            'autofit' => true
        ],
        'vatpercent' => [
            'heading' => 'invoice::RowVATPercent',
            'valuemethod' => 'getRowVATPercent',
            'visible' => true,
            'align' => 'R',
            'width' => 15,
            'autofit' => true
        ],
        'vat' => [
            'heading' => 'invoice::RowTax',
            'valuemethod' => 'getRowVAT',
            'visible' => true,
            'align' => 'R',
            'width' => 20,
            'autofit' => true
        ],
        'total' => [
            'heading' => 'invoice::RowTotal',
            'valuemethod' => 'getRowTotal',
            'visible' => true,
            'align' => 'R',
            'width' => 20,
            'autofit' => true
        ]
    ];

    /**
     * Any print parameters apart from the first three that have a special meaning
     *
     * @var array
     */
    protected $printParams = [];

    /**
     * Print template ID
     *
     * @var int
     */
    protected $printTemplateId;

    /**
     * Whether the print request is made by an authenticated user
     *
     * @var bool
     */
    protected $authenticated;

    /**
     * Constructor
     */
    public function __construct()
    {
    }

    /**
     * Check if the printout is safe to use for read-only user permissions
     *
     * @return bool
     */
    public function getReadOnlySafe()
    {
        return $this->readOnlySafe;
    }

    /**
     * Initialize printing
     *
     * @param int    $invoiceId            Invoice ID
     * @param array  $printParameters      Print control parameters
     * @param string $outputFileName       File name template
     * @param array  $senderData           Sender record
     * @param array  $recipientData        Recipient record
     * @param array  $invoiceData          Invoice record
     * @param array  $invoiceRowData       Invoice row records
     * @param array  $recipientContactData Recipient's contact records
     * @param int    $dateOverride         Date override for invoice date
     * @param int    $printTemplateId      Print template ID
     * @param bool   $authenticated        Whether the user is authenticated
     *
     * @return void
     */
    public function init($invoiceId, $printParameters, $outputFileName, $senderData,
        $recipientData, $invoiceData, $invoiceRowData, $recipientContactData,
        $dateOverride, $printTemplateId, $authenticated
    ) {
        $this->printTemplateId = $printTemplateId;
        $this->authenticated = $authenticated;
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
        $this->printVirtualBarcode = isset($parameters[2]) ? ($parameters[2] == 'Y')
            : false;
        // Rest of the parameters are key=value style
        if (count($parameters) > 3) {
            $this->printParams = parse_ini_string(
                implode("\n", array_slice($parameters, 3))
            );
        }

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
        $this->groupedVATs = [];
        $sequence = 1;
        foreach ($this->invoiceRowData as $key => &$row) {
            $row['sequence'] = $sequence++;
            if ($row['partial_payment']) {
                $this->partialPayments -= $row['price'];
                continue;
            }

            if ($row['partial_payment']) {
                $rowSum = $rowSumVAT = $row['price'];
                $rowVAT = 0;
            } else {
                list($rowSum, $rowVAT, $rowSumVAT) = calculateRowSum($row);
                if ($row['vat_included']) {
                    $row['price'] /= (1 + $row['vat'] / 100);
                }
            }
            $row['rowsum'] = $rowSum;
            $row['rowvat'] = $rowVAT;
            $row['rowsumvat'] = $rowSumVAT;
            $this->totalSum += $rowSum;
            $this->totalVAT += $rowVAT;
            $this->totalSumVAT += $rowSumVAT;
            $discount = (float)$row['discount'];
            $discountAmount = (float)$row['discount_amount'];
            if ($discount || $discountAmount) {
                $this->discountedRows = true;
            }

            // Create array grouped by the VAT base
            $vat = str_pad(
                number_format($row['vat'], 2, '', ''), 5, '0', STR_PAD_LEFT
            );
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
        ksort($this->groupedVATs);

        $this->separateStatement = ($this->printStyle == 'invoice') &&
             getSetting('invoice_separate_statement');

        $this->senderAddressLine = $senderData['name'];
        $strCompanyID = trim($senderData['company_id']);
        if ($strCompanyID) {
            $strCompanyID = Translator::translate('invoice::VATID')
                . ": $strCompanyID";
        }
        if ($strCompanyID) {
            $strCompanyID .= ', ';
        }
        if ($senderData['vat_registered']) {
            $strCompanyID .= Translator::translate('invoice::VATReg');
        } else {
            $strCompanyID .= Translator::translate('invoice::NonVATReg');
        }
        if ($strCompanyID) {
            $this->senderAddressLine .= " ($strCompanyID)";
        }
        $this->senderAddressLine .= "\n" . $senderData['street_address'];
        if ($senderData['street_address']
            && ($senderData['zip_code'] || $senderData['city'])
        ) {
            $this->senderAddressLine .= ', ';
        }
        if ($senderData['zip_code']) {
            $this->senderAddressLine .= $senderData['zip_code'] . ' ';
        }
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

        if ($senderData['phone']) {
            $this->senderContactInfo = "\n" . Translator::translate('invoice::Phone')
                . ' ' . $senderData['phone'];
        } else {
            $this->senderContactInfo = '';
        }

        if (!empty($invoiceData['ref_number'])
            && strlen($invoiceData['ref_number']) < 4
        ) {
            error_log('Reference number too short, will not be displayed');
            $invoiceData['ref_number'] = '';
        }
        $this->refNumber = isset($invoiceData['ref_number'])
            ? formatRefNumber($invoiceData['ref_number']) : '';

        $this->recipientFullAddress = $recipientData['company_name'] . "\n" .
             $recipientData['street_address'] . "\n" . $recipientData['zip_code'] .
             ' ' . $recipientData['city'];
        $this->billingAddress = $recipientData['billing_address'];
        if (!$this->billingAddress || $this->printStyle != 'invoice'
            || (($invoiceData['state_id'] == 5 || $invoiceData['state_id'] == 6)
            && !getSetting('invoice_send_reminder_to_invoicing_address'))
        ) {
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
            if (ctype_digit($tmpRefNumber) == 0
                || (strncmp($tmpRefNumber, 'RF', 2) == 0
                && ctype_digit(substr($tmpRefNumber, 2) == 0))
            ) {
                error_log(
                    'Empty or invalid reference number "' . $tmpRefNumber
                    . '", barcode not created'
                );
            } elseif (strlen($IBAN) != 16) {
                error_log(
                    'IBAN length invalid (should be 16 numbers without leading'
                    . ' country code and spaces), barcode not created'
                );
            } elseif (strlen($invoiceData['due_date']) != 8) {
                error_log(
                    'Invalid due date \'' . $invoiceData['due_date']
                    . '\' - barcode not created'
                );
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

        if ($this->printStyle === 'invoice') {
            $this->autoPageBreakMarginFirstPage = $this->printVirtualBarcode
                ? 120 : 115;
        }

        if (!getSetting('invoice_show_sequential_number')) {
            $this->columnDefs['sequence']['visible'] = false;
        }

        if (!getSetting('invoice_show_row_date')) {
            $this->columnDefs['date']['visible'] = false;
        }

        if (!$this->discountedRows) {
            $this->columnDefs['discount']['visible'] = false;
        } elseif ('invoice' === $this->printStyle) {
            $this->left -= 5;
            $this->width += 10;
        }

        if ('dispatch' === $this->printStyle || !$this->senderData['vat_registered']
        ) {
            $this->columnDefs['totalvatless']['visible'] = false;
            $this->columnDefs['vatpercent']['visible'] = false;
            $this->columnDefs['vat']['visible'] = false;
        }
        if ('dispatch' === $this->printStyle) {
            $this->columnDefs['price']['visible'] = false;
            $this->columnDefs['discount']['visible'] = false;
            $this->columnDefs['total']['visible'] = false;
        }

        if (getSetting('invoice_row_description_first_line_only', false)) {
            $this->columnDefs['description']['maxheight'] = 5;
        }

        if ('invoice' !== $this->printStyle) {
            $this->invoiceRowMaxY = 270;
        }
    }

    /**
     * Main method for printing
     *
     * @return void
     */
    public function printInvoice()
    {
        $this->initPDF();
        $this->printSender();
        $this->printRecipient();
        $this->printInfo();
        $this->printSeparatorLine();
        $this->printForeword();
        if ($this->printStyle == 'invoice') {
            $this->printForm();
        }

        $savePdf = clone($this->pdf);
        if (!$this->separateStatement) {
            $this->printRows();
            $this->printSummary();
        } else {
            $this->printSeparateStatementMessage();
        }
        $this->printAfterword();

        if ($this->printStyle == 'invoice'
            && !$this->separateStatement
            && $this->allowSeparateStatement
        ) {
            if ($this->pdf->getY() > $this->invoiceRowMaxY
                || $this->pdf->getPage() > 1
            ) {
                $this->pdf = $savePdf;
                $this->separateStatement = true;
                $this->printSeparateStatementMessage();
                $this->printAfterword();
            }
        }

        if ($this->separateStatement) {
            $this->printRows();
            $this->printSummary();
        }

        $this->printOut();
    }

    /**
     * Initialize the PDF
     *
     * @return void
     */
    protected function initPDF()
    {
        $pdf = new PDF('P', 'mm', 'A4', _CHARSET_ == 'UTF-8', _CHARSET_, false);
        $pdf->AddPage();
        $pdf->SetAutoPageBreak(
            true, $this->autoPageBreakMargin, $this->autoPageBreakMarginFirstPage
        );

        $pdf->footerLeft = $this->senderAddressLine;
        $pdf->footerCenter = $this->senderContactInfo;
        $pdf->footerRight = $this->senderData['www'] . "\n" .
             $this->senderData['email'];
        $pdf->headerLeftPos = $pdf->footerLeftPos = $this->left;
        $pdf->headerRightPos = $pdf->footerRightPos = $this->left + $this->width
            - $pdf->headerRightWidth;
        $pdf->markdown = getSetting('printout_markdown');
        $this->pdf = $pdf;
    }

    /**
     * Print sender's logo and/or address
     *
     * @return void
     */
    protected function printSender()
    {
        $pdf = $this->pdf;
        $senderData = $this->senderData;

        if (isset($senderData['logo_filedata'])) {
            if (!isset($senderData['logo_top'])) {
                $senderData['logo_top'] = $pdf->GetY() + 5;
            }
            if (!isset($senderData['logo_left'])) {
                $senderData['logo_left'] = $pdf->GetX();
            }
            if (!isset($senderData['logo_width']) || $senderData['logo_width'] == 0
            ) {
                $senderData['logo_width'] = 80;
            }

            $pdf->Image(
                '@' . $senderData['logo_filedata'],
                $senderData['logo_left'], $senderData['logo_top'],
                $senderData['logo_width'], 0, '', '', 'N', false, 300, '', false,
                false, 0, true
            );
        }
        if (!isset($senderData['logo_filedata'])
            || getSetting('invoice_print_senders_logo_and_address')
        ) {
            $width = getSetting('invoice_address_max_width');
            $address = $senderData['street_address'] . "\n" . $senderData['zip_code']
                 . ' ' . $senderData['city'] . "\n" . $senderData['country'];
            $pdf->SetTextColor(125);
            $pdf->SetFont('Helvetica', 'B', 10);
            $pdf->SetY($this->senderAddressY);
            $pdf->setX($this->senderAddressX);
            $pdf->multiCellMD($width, 5, $senderData['name'], 'L');
            $pdf->SetFont('Helvetica', '', 10);
            $pdf->setX($this->senderAddressX);
            $pdf->multiCellMD($width, 5, $address, 'L');
        }
    }

    /**
     * Print recipient's contact information
     *
     * @return void
     */
    protected function printRecipient()
    {
        $pdf = $this->pdf;
        $recipientData = $this->recipientData;

        $width = getSetting('invoice_address_max_width');
        $pdf->SetTextColor(0);
        $pdf->SetFont('Helvetica', '', 12);
        $pdf->SetY($this->recipientAddressY);
        $pdf->setX($this->recipientAddressX);
        $pdf->multiCellMD($width, 5, $this->recipientName, 'L');
        $contact = $this->getContactPerson();
        if (!empty($contact['contact_person'])
            && getSetting('invoice_show_recipient_contact_person')
        ) {
            $pdf->setX($this->recipientAddressX);
            $pdf->multiCellMD($width, 5, $contact['contact_person'], 'L');
        }
        $pdf->setX($this->recipientAddressX);
        $pdf->multiCellMD($width, 5, $this->recipientAddress, 'L');
        if ($recipientData['email'] && getSetting('invoice_show_recipient_email')) {
            $pdf->SetY($pdf->GetY() + 4);
            $pdf->setX($this->recipientAddressX);
            $pdf->multiCellMD($width, 5, $recipientData['email'], 'L');
        }

        $this->recipientMaxY = $pdf->GetY();
    }

    /**
     * Print info headers
     *
     * @return void
     */
    protected function printInfo()
    {
        $pdf = $this->pdf;

        $pdf->SetXY($this->infoLeft + $this->infoHeadingsWidth, 10);
        $pdf->SetFont('Helvetica', 'B', 12);
        $pdf->Cell($this->infoTextWidth, 5, $this->getHeaderTitle(), 0, 1, 'L');
        $pdf->SetFont('Helvetica', '', 10);
        $pdf->SetXY($this->infoLeft, $pdf->GetY() + 5);

        $data = $this->getInfoArray();
        $this->printInfoArray($data);
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
        if ($this->printStyle == 'dispatch') {
            $locStr = 'DispatchNote';
        } elseif ($this->printStyle == 'receipt') {
            $locStr = 'Receipt';
        } else {
            $locStr = 'Invoice';
        }

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
        $data["invoice::${locStr}Number"] = $invoiceData['invoice_no'];
        $strInvoiceDate = ($this->dateOverride)
            ? $this->formatDate($this->dateOverride)
            : $this->formatDate($invoiceData['invoice_date']);
        $data["invoice::${locStr}Date"] = $strInvoiceDate;
        $strDueDate = $this->formatDate($invoiceData['due_date']);
        if ($this->printStyle == 'invoice') {
            $data['invoice::DueDate'] = $strDueDate;
            $paymentDays = round(
                dbDate2UnixTime($invoiceData['due_date']) / 3600 / 24 -
                     dbDate2UnixTime($invoiceData['invoice_date']) / 3600 / 24
            );
            if ($paymentDays < 0) {
                // This shouldn't happen, but try to be safe...
                $paymentDays = getPaymentDays($invoiceData['company_id']);
            }
            $data['invoice::TermsOfPayment'] = $this->getTermsOfPayment(
                $paymentDays
            );
            $data['invoice::PeriodForComplaints'] = $this->getPeriodForComplaints();
            $data['invoice::PenaltyInterest'] = $this->formatNumber(
                getSetting('invoice_penalty_interest'), 1, true
            ) . ' %';

            if ($bankInfo) {
                $data['invoice::RecipientBankAccount'] = $senderData['bank_iban'];
                $data['invoice::RecipientBankBIC'] = $senderData['bank_swiftbic'];
            }

            if ($this->refNumber) {
                $data['invoice::InvoiceRefNr'] = $this->refNumber;
            }
        }

        if ($invoiceData['reference']) {
            $data['invoice::YourReference'] = $invoiceData['reference'];
        }
        if ($this->printStyle == 'invoice'
            && getSetting('invoice_show_delivery_info_in_invoice')
        ) {
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
        }
        if (!empty($invoiceData['info'])) {
            $data['invoice::AdditionalInformation'] = [
                'value' => $this->replacePlaceholders($invoiceData['info']),
                'type' => 'multicell'
            ];
        }

        if ($this->printStyle == 'invoice') {
            if ($invoiceData['refunded_invoice_no']) {
                $data['invoice::RefundsInvoice'] = [
                    'value' => sprintf(
                        Translator::translate('invoice::RefundsInvoice'),
                        $invoiceData['refunded_invoice_no']
                    ),
                    'type' => 'textonly'
                ];
            }

            if ($invoiceData['state_id'] == 5) {
                $data['invoice::FirstReminderNote'] = [
                    'value' => Translator::translate('invoice::FirstReminderNote'),
                    'type' => 'textonly',
                    'fontweight' => 'B'
                ];
            } elseif ($invoiceData['state_id'] == 6) {
                $data['invoice::SecondReminderNote'] = [
                    'value' => Translator::translate('invoice::SecondReminderNote'),
                    'type' => 'textonly',
                    'fontweight' => 'B'
                ];
            }
        }
        return $data;
    }

    /**
     * Print the info array
     *
     * @param array $data Actual content to print
     *
     * @return void
     */
    protected function printInfoArray($data)
    {
        $pdf = $this->pdf;
        foreach ($data as $key => $current) {
            $value = is_array($current) ? $current['value'] : $current;
            $type = !empty($current['type']) ? $current['type'] : 'normal';
            if ('normal' === $type || 'multicell' === $type) {
                $pdf->SetX($this->infoLeft);
                $pdf->Cell(
                    $this->infoHeadingsWidth,
                    4,
                    Translator::translate($key) . ': ',
                    0,
                    0,
                    'R'
                );
            }
            if (isset($current['fontweight'])) {
                $pdf->SetFont('Helvetica', $current['fontweight'], 10);
            }
            if ('normal' === $type) {
                $pdf->Cell($this->infoTextWidth, 4, $value, 0, 1);
            } elseif ('multicell' === $type) {
                $pdf->multiCellMD($this->infoTextWidth, 4, $value, 'L', 1, 0, true);
            } elseif ('textonly' === $type) {
                $pdf->SetXY($this->infoLeft, $pdf->getY() + 2);
                $pdf->multiCellMD(
                    $this->infoHeadingsWidth + $this->infoTextWidth,
                    4,
                    $value,
                    'L',
                    1,
                    0,
                    true
                );
            }
            if (isset($current['fontweight'])) {
                $pdf->SetFont('Helvetica', '', 10);
            }
        }
    }

    /**
     * Print a line separating header and the content area
     *
     * @return void
     */
    protected function printSeparatorLine()
    {
        $pdf = $this->pdf;
        $pdf->SetY(max($pdf->GetY(), $this->recipientMaxY) + 5);
        $pdf->Line(
            $this->left, $pdf->GetY(), $this->left + $this->width, $pdf->GetY()
        );
        $pdf->SetY($pdf->GetY() + 5);
    }

    /**
     * Print foreword (before rows or a separate statement message)
     *
     * @return void
     */
    protected function printForeword()
    {
        if (empty($this->invoiceData['foreword'])) {
            return;
        }

        $pdf = $this->pdf;

        $foreword = $this->replacePlaceholders($this->invoiceData['foreword']);
        $pdf->setX($this->left);
        $pdf->SetTextColor(0);
        $pdf->SetFont('Helvetica', '', 10);
        $pdf->multiCellMD(200 - $this->left, 5, $foreword, 'L', 1, 0, true);
        $pdf->setY($pdf->getY() + 5);
    }

    /**
     * Print afterword (after rows or a separate statement message)
     *
     * @return void
     */
    protected function printAfterword()
    {
        if (empty($this->invoiceData['afterword'])) {
            return;
        }

        $pdf = $this->pdf;

        $afterword = $this->replacePlaceholders($this->invoiceData['afterword']);
        $pdf->SetTextColor(0);
        $pdf->SetFont('Helvetica', '', 10);
        $pdf->setXY($this->left, $pdf->GetY() + 5);
        $pdf->multiCellMD(200 - $this->left, 5, $afterword, 'L', 1, 0, true);
    }

    /**
     * Print a message about a separate statement
     *
     * @return void
     */
    protected function printSeparateStatementMessage()
    {
        $pdf = $this->pdf;
        $pdf->SetFont('Helvetica', 'B', 12);
        $pdf->setX($this->left);
        $pdf->multiCellMD(
            180,
            5,
            Translator::translate('invoice::SeeSeparateStatement'),
            'L'
        );
    }

    /**
     *  Print all rows
     *
     * @return void
     */
    protected function printRows()
    {
        if (empty($this->invoiceRowData)) {
            return;
        }
        $pdf = $this->pdf;
        $invoiceData = $this->invoiceData;

        if ($this->separateStatement) {
            if ($pdf->getPage() === 1) {
                $pdf->AddPage();

                $pdf->SetFont('Helvetica', 'B', 12);
                $pdf->SetXY($this->left, $pdf->GetY());
                $pdf->Cell(
                    80, 5, Translator::translate('invoice::InvoiceStatement'), 0, 0, 'L'
                );
                $pdf->SetFont('Helvetica', '', 10);

                if ($this->printStyle == 'dispatch') {
                    $locStr = 'DispatchNote';
                } elseif ($this->printStyle == 'receipt') {
                    $locStr = 'Receipt';
                } else {
                    $locStr = 'Invoice';
                }

                $pdf->Cell(
                    $this->left + $this->width - $pdf->getX(), 5,
                    Translator::translate("invoice::${locStr}Number") . ': '
                    . $invoiceData['invoice_no'],
                    0, 0, 'R'
                );
            } else {
                $pdf->SetFont('Helvetica', 'B', 12);
                $pdf->SetXY(10, $pdf->GetY() + 10);
                $pdf->Cell(
                    80, 5, Translator::translate('invoice::InvoiceStatement'), 0, 0, 'L'
                );
            }
            $pdf->SetXY(10, $pdf->GetY() + 10);
        } elseif ($this->printStyle != 'invoice') {
            $pdf->printFooterOnFirstPage = true;
        }

        $pdf->SetFont('Helvetica', '', 10);

        $this->adjustAutoFitColumns();

        $this->printRowHeadings($pdf);

        $pdf->SetY($pdf->GetY() + 5);
        foreach ($this->invoiceRowData as $row) {
            if ($row['partial_payment'] && 'dispatch' === $this->printStyle) {
                continue;
            }
            $savePDF = clone($pdf);
            $maxY = $this->printRow($pdf, $row);
            if (!$this->separateStatement && $this->printStyle == 'invoice'
                && $this->allowSeparateStatement
                && $pdf->GetY() > $this->invoiceRowMaxY
            ) {
                $this->separateStatement = true;
                $this->printInvoice();
                exit();
            }

            if ($maxY > $this->invoiceRowMaxY) {
                $pdf = $this->pdf = $savePDF;
                $pdf->addPage();
                $this->printRow($pdf, $row);
            }
        }
    }

    /**
     *  Print payment summary
     *
     * @return void
     */
    protected function printSummary()
    {
        if ('dispatch' === $this->printStyle) {
            return;
        }

        $pdf = $this->pdf;
        $pdf->saveAutoBreakState();
        $pdf->SetAutoPageBreak(false);
        $startY = $maxY = $pdf->GetY();

        // VAT Breakdown
        if ($this->senderData['vat_registered']
            && getSetting('invoice_show_vat_breakdown')
        ) {
            $pdf->SetFont('Helvetica', '', 9);
            $pdf->SetXY($this->left, $startY + 5);
            $pdf->Cell(
                20, 4, Translator::translate('invoice::RowVATPercent'), 0, 0, 'R'
            );
            $pdf->Cell(
                20, 4, Translator::translate('invoice::RowTotalVATLess'), 0, 0, 'R'
            );
            $pdf->Cell(
                20, 4, Translator::translate('invoice::RowTax'), 0, 0, 'R'
            );
            $pdf->Cell(
                20, 4, Translator::translate('invoice::RowTotal'), 0, 0, 'R'
            );
            $pdf->SetLineWidth(0.13);
            $pdf->Line($this->left + 2, $startY + 9, $pdf->GetX(), $startY + 9);

            $pdf->SetY($startY + 6);
            foreach ($this->groupedVATs as $group) {
                $pdf->SetXY($this->left, $pdf->getY() + 4);

                $pdf->Cell(
                    20, 4, $this->formatNumber($group['vat'], 1, true), 0, 0, 'R'
                );
                $pdf->Cell(
                    20, 4, $this->formatCurrency($group['totalsum']), 0, 0, 'R'
                );
                $pdf->Cell(
                    20, 4, $this->formatCurrency($group['totalvat']), 0, 0, 'R'
                );
                $pdf->Cell(
                    20, 4, $this->formatCurrency($group['totalsumvat']), 0, 0, 'R'
                );
            }

            if (count($this->groupedVATs) > 1) {
                $pdf->SetXY($this->left, $pdf->getY() + 4);
                $pdf->Cell(
                    20, 4, Translator::translate('invoice::RowTotal'), 0, 0, 'R'
                );
                $pdf->Cell(
                    20, 4, $this->formatCurrency($this->totalSum), 0, 0, 'R'
                );
                $pdf->Cell(
                    20, 4, $this->formatCurrency($this->totalVAT), 0, 0, 'R'
                );
                $pdf->Cell(
                    20, 4, $this->formatCurrency($this->totalSumVAT), 0, 0, 'R'
                );
            }

            $maxY = $pdf->GetY() + 5;
            $maxX = $pdf->GetX();

            // Border for the VAT summary
            $pdf->SetLineWidth(0.13);
            $pdf->Line($this->left, $startY + 5, $maxX + 2, $startY + 5);
            $pdf->Line($maxX + 2, $startY + 5, $maxX + 2, $maxY);
            $pdf->Line($maxX + 2, $maxY, $this->left, $maxY);
            $pdf->Line($this->left, $maxY, $this->left, $startY + 5);

            $pdf->SetY($startY);
        }

        if ($this->invoiceData['invoice_unpaid']) {
            $unpaidAmount = $this->totalSumVAT - $this->partialPayments;
        } else {
            $unpaidAmount = 0;
        }
        $colWidth = 30;
        $leftAmount = $this->left + $this->width - $colWidth;
        $right = $leftAmount - 5;
        if ($this->senderData['vat_registered']) {
            $pdf->SetFont('Helvetica', '', 10);
            $pdf->SetY($pdf->GetY() + 6);
            $pdf->Cell(
                $right,
                5,
                Translator::translate('invoice::TotalExcludingVAT') . ': ',
                0,
                0,
                'R'
            );
            $pdf->SetX($leftAmount);
            $pdf->Cell(
                $colWidth, 5, $this->formatCurrency($this->totalSum), 0, 0, 'R'
            );

            if (!getSetting('invoice_show_vat_breakdown')) {
                $pdf->SetY($pdf->GetY() + 5);
                $pdf->Cell(
                    $right,
                    5,
                    Translator::translate('invoice::TotalVAT') . ': ',
                    0,
                    0,
                    'R'
                );
                $pdf->SetX($leftAmount);
                $pdf->Cell(
                    $colWidth, 5, $this->formatCurrency($this->totalVAT), 0, 0, 'R'
                );
            }

            if ('invoice' !== $this->printStyle) {
                $pdf->SetFont('Helvetica', 'B', 10);
            }
            $pdf->SetY($pdf->GetY() + 5);
            $pdf->Cell(
                $right,
                5,
                Translator::translate('invoice::TotalIncludingVAT') . ': ',
                0,
                0,
                'R'
            );
            $pdf->SetX($leftAmount);
            $pdf->Cell(
                $colWidth,
                5,
                $this->formatCurrency($this->totalSumVAT),
                0,
                0,
                'R'
            );
            $pdf->SetFont('Helvetica', '', 10);
        } else {
            if ('invoice' !== $this->printStyle) {
                $pdf->SetFont('Helvetica', 'B', 10);
            }
            $pdf->SetY($pdf->GetY() + 5);
            $pdf->Cell(
                $right,
                5,
                Translator::translate('invoice::TotalPrice') . ': ',
                0,
                0,
                'R'
            );
            $pdf->SetX($leftAmount);
            $pdf->Cell(
                $colWidth,
                5,
                $this->formatCurrency($this->totalSumVAT),
                0,
                0,
                'R'
            );
            $pdf->SetFont('Helvetica', '', 10);
        }
        if ('invoice' === $this->printStyle) {
            $pdf->SetFont('Helvetica', 'B', 10);
            $pdf->SetY($pdf->GetY() + 5);
            $pdf->Cell(
                $right,
                5,
                Translator::translate('invoice::TotalToPay') . ': ',
                0,
                0,
                'R'
            );
            $pdf->SetX($leftAmount);
            $pdf->Cell(
                $colWidth, 5, $this->formatCurrency($unpaidAmount), 0, 1, 'R'
            );
        } else {
            $pdf->SetY($pdf->GetY() + 5);
        }
        $pdf->SetY(max([$pdf->GetY(), $maxY]));
        $pdf->restoreAutoBreakState();
    }

    /**
     * Adjust 'auto' column widths
     *
     * @return void
     */
    protected function adjustAutoFitColumns()
    {
        $pdf = $this->pdf;
        foreach ($this->columnDefs as $key => $column) {
            if (!$column['visible'] || empty($column['autofit'])) {
                continue;
            }
            $maxWidth = $pdf->GetStringWidth(
                Translator::translate($column['heading'])
            );

            foreach ($this->invoiceRowData as $row) {
                if ($row['price'] == 0 && $row['pcs'] == 0) {
                    continue;
                }
                if (!$column['visible']) {
                    continue;
                }
                $value = call_user_func([$this, $column['valuemethod']], $row);
                $curWidth = $pdf->GetStringWidth($value);
                if ($curWidth > $maxWidth) {
                    $maxWidth = $curWidth;
                }
            }
            $this->columnDefs[$key]['width'] = (int)round($maxWidth)
                + $this->columnPadding;
        }
    }

    /**
     * Print row headings
     *
     * @param object $pdf TCPDF object to use
     *
     * @return void
     */
    protected function printRowHeadings(&$pdf)
    {
        $curX = $this->left;
        $rowY = $pdf->GetY();
        foreach ($this->columnDefs as $key => $column) {
            if (!$column['visible']) {
                continue;
            }
            $pdf->SetXY($curX, $rowY);
            $width = $column['width'];
            if ('fill' === $width) {
                $width = $this->getColumnFillWidth($key);
            }
            if (!empty($column['autofit'])) {
                $pdf->Cell(
                    $width,
                    4,
                    Translator::translate($column['heading']),
                    0,
                    0,
                    $column['align']
                );
            } else {
                $pdf->multiCellMD(
                    $width,
                    4,
                    Translator::translate($column['heading']),
                    $column['align'],
                    1,
                    0,
                    true
                );
            }
            $curX += $width;
        }
        $pdf->SetLineWidth(0.13);
        $pdf->Line($this->left, $rowY + 5, $curX, $rowY + 5);
        $pdf->setY($rowY + 2);
    }

    /**
     * Print a row
     *
     * @param object $pdf TCPDF object to use
     * @param array  $row The row to print
     *
     * @return Y position after the row has been printed
     */
    protected function printRow(&$pdf, $row)
    {
        // Special case for rows with no price and pieces
        if ($row['price'] == 0 && $row['pcs'] == 0
            && $this->columnDefs['description']['visible']
        ) {
            $value = call_user_func(
                [$this, $this->columnDefs['description']['valuemethod']], $row
            );
            $maxHeight = isset($this->columnDefs['description']['maxheight'])
                ? $this->columnDefs['description']['maxheight'] : 0;

            $pdf->setX($this->left);
            $pdf->multiCellMD(
                $this->width,
                4,
                $value,
                $this->columnDefs['description']['align'],
                1,
                $maxHeight,
                true
            );

            $pdf->setY($pdf->getY() + 1);

            return $pdf->getY();
        }

        $maxY = 0;
        $rowY = $pdf->getY();
        $curX = $this->left;

        foreach ($this->columnDefs as $key => $column) {
            if (!$column['visible']) {
                continue;
            }
            $value = call_user_func([$this, $column['valuemethod']], $row);
            $width = $column['width'];
            if ('fill' === $width) {
                $width = $this->getColumnFillWidth($key);
            }
            $maxHeight = isset($column['maxheight']) ? $column['maxheight'] : 0;
            $pdf->setXY($curX, $rowY);
            if (!empty($column['autofit'])) {
                $pdf->Cell(
                    $width,
                    4,
                    $value,
                    0,
                    1,
                    $column['align']
                );
            } else {
                $pdf->multiCellMD(
                    $width,
                    4,
                    $value,
                    $column['align'],
                    1,
                    $maxHeight,
                    true
                );
            }
            $curY = $pdf->getY();
            if ($curY > $maxY) {
                $maxY = $curY;
            }
            $curX += $width;
        }

        ++$maxY;

        $pdf->SetY($maxY);

        if ($this->printStyle == 'dispatch'
            && getSetting('dispatch_note_show_barcodes')
            && ((!empty($row['barcode1']) && !empty($row['barcode1_type']))
            || (!empty($row['barcode2']) && !empty($row['barcode2_type'])))
        ) {
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
                $pdf->write1DBarcode(
                    $row['barcode1'],
                    $row['barcode1_type'],
                    $this->left,
                    $pdf->getY(),
                    98,
                    15,
                    0.34,
                    $style,
                    'T'
                );
            }
            if (!empty($row['barcode2']) && !empty($row['barcode2_type'])) {
                $pdf->write1DBarcode(
                    $row['barcode2'],
                    $row['barcode2_type'],
                    $this->left + 98,
                    $pdf->getY(),
                    105,
                    15,
                    0.34,
                    $style,
                    'T'
                );
            }
            $maxY = $pdf->GetY() + 18;
            $pdf->SetY($maxY);
        }

        return $maxY;
    }

    /**
     * Get column width for a column with 'fill' as the width
     *
     * @param string $column Column id
     *
     * @return int
     */
    protected function getColumnFillWidth($column)
    {
        $otherWidths = 0;
        foreach ($this->columnDefs as $key => $columnDef) {
            if ($key === $column || !$columnDef['visible']) {
                continue;
            }
            if ('fill' === $columnDef['width']) {
                throw new Exception(
                    "Cannot have multiple columns with 'fill' as width"
                );
            }
            $otherWidths += $columnDef['width'];
        }
        $width = $this->width - $otherWidths;
        if ($width <= 0) {
            throw new Exception("No room for column '$column'");
        }
        return $width;
    }

    /**
     * Get sequence number for row
     *
     * @param array $row Current row
     *
     * @return string
     */
    protected function getRowSequenceNumber($row)
    {
        return getSetting('invoice_show_sequential_number') == 1
            ? $row['sequence'] : $row['order_no'];
    }

    /**
     * Get description for row
     *
     * @param array $row Current row
     *
     * @return string
     */
    protected function getRowDescription($row)
    {
        $description = '';
        switch ($row['reminder_row']) {
        case 1 :
            $description = Translator::translate('invoice::PenaltyInterestDesc');
            break;
        case 2 :
            $description = Translator::translate('invoice::ReminderFeeDesc');
            break;
        default :
            if ($row['partial_payment']) {
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
        return $description;
    }

    /**
     * Get date for row
     *
     * @param array $row Current row
     *
     * @return string
     */
    protected function getRowDate($row)
    {
        return $this->formatDate($row['row_date']);
    }

    /**
     * Get price for row
     *
     * @param array $row Current row
     *
     * @return string
     */
    protected function getRowPrice($row)
    {
        $decimals = isset($row['price_decimals']) ? $row['price_decimals'] : 2;
        return $row['partial_payment'] ? ''
            : $this->formatCurrency(
                $row['price'], $decimals, false, $this->roundRowPrices
            );
    }

    /**
     * Get discount for row
     *
     * @param array $row Current row
     *
     * @return string
     */
    protected function getRowDiscount($row)
    {
        $discounts = [];
        if ((float)$row['discount']) {
            $discounts[] = $this->formatCurrency($row['discount'], 2, true) . ' %';
        }
        if ((float)$row['discount_amount']) {
            $decimals = isset($row['price_decimals']) ? $row['price_decimals'] : 2;
            $discounts[] = $this->formatCurrency(
                $row['discount_amount'], $decimals
            );
        }

        return implode($discounts, ', ');
    }

    /**
     * Get pieces for row
     *
     * @param array $row Current row
     *
     * @return string
     */
    protected function getRowPieces($row)
    {
        return $row['partial_payment'] ? ''
            : $this->formatNumber($row['pcs'], 2, true);
    }

    /**
     * Get item type for row
     *
     * @param array $row Current row
     *
     * @return string
     */
    protected function getRowItemType($row)
    {
        return $row['partial_payment'] ? ''
            : Translator::translate("invoice::{$row['type']}");
    }

    /**
     * Get VAT-less total for row
     *
     * @param array $row Current row
     *
     * @return string
     */
    protected function getRowTotalVATLess($row)
    {
        return $row['partial_payment'] ? ''
            : $this->formatCurrency($row['rowsum'], 2, false, $this->roundRowPrices);
    }

    /**
     * Get VAT percent for row
     *
     * @param array $row Current row
     *
     * @return string
     */
    protected function getRowVATPercent($row)
    {
        return $row['partial_payment'] ? ''
            : $this->formatNumber($row['vat'], 1, true);
    }

    /**
     * Get VAT for row
     *
     * @param array $row Current row
     *
     * @return string
     */
    protected function getRowVAT($row)
    {
        return $row['partial_payment'] ? ''
            : $this->formatCurrency($row['rowvat'], 2, false, $this->roundRowPrices);
    }

    /**
     * Get total including VAT for row
     *
     * @param array $row Current row
     *
     * @return string
     */
    protected function getRowTotal($row)
    {
        return $row['partial_payment'] ? $this->formatCurrency($row['price'])
            : $this->formatCurrency(
                $row['rowsumvat'], 2, false, $this->roundRowPrices
            );
    }

    /**
     * Print the invoice form at the end of the first page
     *
     * @return void
     */
    protected function printForm()
    {
        $pdf = $this->pdf;
        $saveX = $pdf->getX();
        $saveY = $pdf->getY();

        $senderData = $this->senderData;
        $invoiceData = $this->invoiceData;

        $pdf->saveAutoBreakState();
        $pdf->SetAutoPageBreak(false);

        $pdf->SetFont('Helvetica', '', 7);
        if ($this->printVirtualBarcode && $this->barcode) {
            $pdf->SetXY($this->left, 180);
            $pdf->Cell(
                120,
                2.8,
                Translator::translate('invoice::VirtualBarcode') . ': '
                . $this->barcode,
                0,
                1,
                'L'
            );
        }

        $lines = 3;
        $footerHeight = ($lines * 4);
        $intStartY = 197 - $footerHeight;
        $pdf->SetXY($pdf->footerLeftPos, $intStartY);
        $pdf->multiCellMD(
            $pdf->footerLeftWidth, 4, $this->senderAddressLine, 'L', 0, $footerHeight
        );
        $pdf->SetXY($pdf->footerCenterPos, $intStartY);
        $pdf->multiCellMD(
            $pdf->footerCenterWidth, 4, $this->senderContactInfo, 'C', 0,
            $footerHeight
        );
        $pdf->SetXY($pdf->footerRightPos, $intStartY);
        $pdf->multiCellMD(
            $pdf->footerRightWidth, 4,
            $senderData['www'] . "\n" . $senderData['email'], 'R', 0, $footerHeight
        );

        // Invoice form
        $intStartY = $intStartY + $footerHeight;
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
        $pdf->Line(
            $intStartX + 111.4, $intStartY + 57.5, $intMaxX, $intStartY + 57.5
        );
        // 5. hor.line - full width
        $pdf->Line($intStartX, $intStartY + 66, $intMaxX, $intStartY + 66);
        // 6. hor.line - full width
        $pdf->Line($intStartX, $intStartY + 74.5, $intMaxX, $intStartY + 74.5);

        // 1. ver.line - 1.hor - 3.hor
        $pdf->Line(
            $intStartX + 20, $intStartY, $intStartX + 20, $intStartY + 32
        );
        // 2. ver.line - 5.hor - 6.hor
        $pdf->Line(
            $intStartX + 20, $intStartY + 66, $intStartX + 20, $intStartY + 74.5
        );
        // 3. ver.line - full height
        $pdf->Line(
            $intStartX + 111.4, $intStartY, $intStartX + 111.4, $intStartY + 74.5
        );
        // 4. ver.line - 4.hor - 6. hor
        $pdf->Line(
            $intStartX + 130, $intStartY + 57.5, $intStartX + 130, $intStartY + 74.5
        );
        // 5. ver.line - 5.hor - 6. hor
        $pdf->Line(
            $intStartX + 160, $intStartY + 66, $intStartX + 160, $intStartY + 74.5
        );

        // signature
        $pdf->SetLineWidth(0.13);
        $pdf->Line(
            $intStartX + 23, $intStartY + 63, $intStartX + 90, $intStartY + 63
        );

        // bank
        $pdf->SetFont('Helvetica', '', 7);
        $pdf->SetXY($intStartX, $intStartY + 1);
        $pdf->multiCellMD(
            19,
            2.8,
            Translator::translate('invoice::FormRecipientAccountNumber1'),
            'R',
            0
        );
        $pdf->SetXY($intStartX, $intStartY + 8);
        $pdf->multiCellMD(
            19,
            2.8,
            Translator::translate('invoice::FormRecipientAccountNumber2'),
            'R',
            0
        );
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
            ]
        );

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
        $pdf->Cell(
            19, 5, Translator::translate('invoice::FormRecipient1'), 0, 1, 'R'
        );
        $pdf->SetXY($intStartX, $intStartY + 22);
        $pdf->Cell(
            19, 5, Translator::translate('invoice::FormRecipient2'), 0, 1, 'R'
        );
        $pdf->SetFont('Helvetica', '', 10);
        $pdf->SetXY($intStartX + 21, $intStartY + 17);
        $pdf->multiCellMD(100, 4, $this->senderAddress, 'L');

        // payer
        $pdf->SetFont('Helvetica', '', 7);
        $pdf->SetXY($intStartX, $intStartY + 35);
        $pdf->multiCellMD(
            19,
            2.8,
            Translator::translate('invoice::FormPayerNameAndAddress1'),
            'R',
            0
        );
        $pdf->SetXY($intStartX, $intStartY + 45);
        $pdf->multiCellMD(
            19,
            2.8,
            Translator::translate('invoice::FormPayernameAndAddress2'),
            'R',
            0
        );
        $pdf->SetFont('Helvetica', '', 10);
        $pdf->SetXY($intStartX + 21, $intStartY + 35);
        $pdf->multiCellMD(90, 4, $this->recipientFullAddress, 'L');

        // signature
        $pdf->SetFont('Helvetica', '', 7);
        $pdf->SetXY($intStartX, $intStartY + 59);
        $pdf->multiCellMD(
            19, 6, Translator::translate('invoice::FormSignature'), 'R', 0
        );

        // from account
        $pdf->SetXY($intStartX, $intStartY + 67);
        $pdf->multiCellMD(
            19, 6, Translator::translate('invoice::FormFromAccount'), 'R', 0
        );

        // info
        $pdf->SetFont('Helvetica', '', 10);
        $pdf->SetXY($intStartX + 112.4, $intStartY + 18);
        $pdf->Cell(
            70, 5,
            sprintf(
                Translator::translate('invoice::FormInvoiceNumber'),
                $invoiceData['invoice_no']
            ),
            0, 1, 'L'
        );
        $pdf->SetXY($intStartX + 112.4, $intStartY + 25);
        if (getSetting('invoice_show_info_in_form')
            && $this->invoiceData['info']
        ) {
            $pdf->multiCellMD(
                70,
                4,
                $this->invoiceData['info'],
                'L',
                1,
                $this->refNumber ? 20 : 30,
                true
            );
        }
        if ($this->refNumber) {
            $pdf->SetXY($intStartX + 112.4, $pdf->getY() + 3);
            $pdf->Cell(
                70,
                5,
                Translator::translate('invoice::FormRefNumberMandatory1'),
                0,
                1,
                'L'
            );
            $pdf->SetX($intStartX + 112.4);
            $pdf->Cell(
                70,
                5,
                Translator::translate('invoice::FormRefNumberMandatory2'),
                0,
                1,
                'L'
            );
        }

        // terms
        $pdf->SetFont('Helvetica', '', 5);
        $pdf->SetXY($intStartX + 133, $intStartY + 78);
        $pdf->multiCellMD(
            70, 2, Translator::translate('invoice::FormClearingTerms1'), 'L'
        );
        $pdf->SetXY($intStartX + 133, $intStartY + 83);
        $pdf->multiCellMD(
            70, 2, Translator::translate('invoice::FormClearingTerms2'), 'L'
        );
        $pdf->SetFont('Helvetica', '', 6);
        $pdf->SetXY($intStartX + 133, $intStartY + 90);
        $pdf->Cell(
            $intMaxX + 1 - 133 - $intStartX,
            5,
            Translator::translate('invoice::FormBank'),
            0,
            1,
            'R'
        );

        $pdf->SetFont('Helvetica', '', 7);
        // refnr
        $pdf->SetFont('Helvetica', '', 7);
        $pdf->SetXY($intStartX + 112.4, $intStartY + 58);
        $pdf->multiCellMD(
            15, 6, Translator::translate('invoice::FormReferenceNumber'), 'L'
        );
        if ($this->refNumber) {
            $pdf->SetFont('Helvetica', '', 10);
            $pdf->SetXY($intStartX + 131, $intStartY + 59);
            $pdf->Cell(15, 5, $this->refNumber, 0, 1, 'L');
        }

        // due date
        $pdf->SetFont('Helvetica', '', 7);
        $pdf->SetXY($intStartX + 112.4, $intStartY + 67);
        $pdf->multiCellMD(
            15, 6, Translator::translate('invoice::FormDueDate'), 'L'
        );
        $pdf->SetFont('Helvetica', '', 10);
        $pdf->SetXY($intStartX + 131.4, $intStartY + 68);
        $pdf->Cell(
            25,
            5,
            ($invoiceData['state_id'] == 5 || $invoiceData['state_id'] == 6)
                ? Translator::translate('invoice::FormDueDateNOW')
                : $this->formatDate($invoiceData['due_date']),
            0,
            1,
            'L'
        );

        // amount
        $pdf->SetFont('Helvetica', '', 7);
        $pdf->SetXY($intStartX + 161, $intStartY + 67);
        $pdf->multiCellMD(
            15, 6, Translator::translate('invoice::FormCurrency'), 'L'
        );
        $pdf->SetFont('Helvetica', '', 10);
        if (!empty($this->invoiceRowData)) {
            $pdf->SetXY($intStartX + 151, $intStartY + 68);
            $pdf->Cell(
                40,
                5,
                $this->formatNumber($this->totalSumVAT - $this->partialPayments),
                0,
                1,
                'R'
            );
        }

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
            $pdf->write1DBarcode(
                $this->barcode, 'C128C', 20, 273, 105, 14, 0.34, $style, 'N'
            );
        }
        $pdf->setXY($saveX, $saveY);
        $pdf->restoreAutoBreakState();
    }

    /**
     * Print out the results
     *
     * @return void
     */
    protected function printOut()
    {
        $pdf = $this->pdf;
        $invoiceData = $this->invoiceData;

        $filename = $this->getPrintOutFileName();
        $pdf->Output($filename, 'I');
    }

    /**
     * Format date to a user-readable format
     *
     * @param int $date Date
     *
     * @return string
     */
    protected function formatDate($date)
    {
        return dateConvDBDate2Date(
            $date, Translator::translate('invoice::DateFormat')
        );
    }

    /**
     * Format a numeric value
     *
     * @param float $value            Value to format
     * @param int   $decimals         Number of decimals to display
     * @param bool  $decimalsOptional Whether to hide decimals if they are 0
     * @param bool  $round            Whether to round the value instead of
     *                                truncating
     *
     * @return string
     */
    protected function formatNumber($value, $decimals = 2, $decimalsOptional = false,
        $round = true
    ) {
        if (!$round) {
            $value = round($value - (1 / (10 ** $decimals)) * 0.5, $decimals);
        }
        if ($decimalsOptional) {
            return miscRound2OptDecim(
                $value, $decimals,
                Translator::translate('invoice::DecimalSeparator'),
                Translator::translate('invoice::ThousandSeparator')
            );
        }
        return miscRound2Decim(
            $value,
            $decimals,
            Translator::translate('invoice::DecimalSeparator'),
            Translator::translate('invoice::ThousandSeparator')
        );
    }

    /**
     * Format a currency value
     *
     * @param float $value            Value to format
     * @param int   $decimals         Number of decimals to display
     * @param bool  $decimalsOptional Whether to hide decimals if they are 0
     * @param bool  $round            Whether to round the value instead of
     *                                truncating
     *
     * @return string
     */
    protected function formatCurrency($value, $decimals = 2,
        $decimalsOptional = false, $round = true
    ) {
        $number = $this->formatNumber($value, $decimals, $decimalsOptional, $round);
        return Translator::translate('invoice::CurrencyPrefix') . $number
             . Translator::translate('invoice::CurrencySuffix');
    }

    /**
     * Get the data for a set of placeholders
     *
     * @param array $placeholders Placeholders
     *
     * @return string Concatenated results
     */
    protected function getPlaceholderData($placeholders)
    {
        $values = [];
        foreach ($placeholders as $placeholder) {
            $placeholder = substr(substr($placeholder, 0, -1), 1);
            $pcparts = explode(':', $placeholder);
            switch ($pcparts[0]) {
            case 'sender':
                $values[] = isset($this->senderData[$pcparts[1]])
                    ? $this->senderData[$pcparts[1]] : '';
                break;
            case 'recipient':
                $values[] = isset($this->recipientData[$pcparts[1]])
                    ? $this->recipientData[$pcparts[1]] : '';
                break;
            case 'invoice':
                switch ($pcparts[1]) {
                case 'totalsum' :
                    $values[] = $this->formatCurrency($this->totalSum);
                    break;
                case 'totalvat' :
                    $values[] = $this->formatCurrency($this->totalVAT);
                    break;
                case 'totalsumvat' :
                    $values[] = $this->formatCurrency($this->totalSumVAT);
                    break;
                case 'totalunpaid' :
                    $values[] = $this->formatCurrency(
                        $this->totalSumVAT - $this->partialPayments
                    );
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
                case 'pdf_link':
                    $url = getSetting('pdf_link_base_url');
                    if ($url) {
                        include_once 'hmac.php';
                        $language = isset($pcparts[2])
                            ? $pcparts[2] : $this->printLanguage;
                        $uuid = $this->invoiceData['uuid'];
                        $ts = time();
                        $hash = HMAC::createHMAC(
                            [
                                $this->printTemplateId,
                                $language,
                                $uuid,
                                $ts
                            ]
                        );
                        $vars = [
                            't' => $this->printTemplateId,
                            'l' => $language,
                            'i' => $uuid,
                            'c' => $hash,
                            's' => $ts
                        ];
                        $url .= strpos($url, '?') !== false ? '&' : '?';
                        $url .= http_build_query($vars);
                        $values[] = $url;
                    } else {
                        $values[] = '';
                    }
                    break;
                default :
                    $value = isset($this->invoiceData[$pcparts[1]])
                        ? $this->invoiceData[$pcparts[1]] : '';
                    if (substr($pcparts[1], -5) == '_date') {
                        $value = $this->formatDate($value);
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
            case 'var':
                if ('date' === $pcparts[1]) {
                    $values[] = date(Translator::translate('DateFormat'));
                } elseif ('datetime' === $pcparts[1]) {
                    $values[] = date(Translator::translate('DateTimeFormat'));
                }
                break;
            default:
                error_log(
                    "Unknown placeholder '$placeholder' in invoice email fields"
                );
                $values[] = '';
            }
        }
        return implode(' ', $values);
    }

    /**
     * Replace placeholders in a text string
     *
     * @param string $string Text string
     *
     * @return string
     */
    protected function replacePlaceholders($string)
    {
        return preg_replace_callback(
            '/\{\w+:\w+(:.+?)?\}/',
            [
                $this,
                'getPlaceholderData'
            ],
            $string
        );
    }

    /**
     * Get a file name for the printout
     *
     * @param string $filename Optional file name overriding the default
     *
     * @return string
     */
    protected function getPrintOutFileName($filename = '')
    {
        // Replace the %d style placeholder
        $filename = sprintf(
            $filename ? $filename : $this->outputFileName,
            isset($this->invoiceData['invoice_no'])
                ? $this->invoiceData['invoice_no'] : ''
        );
        // Handle additional placeholders
        $filename = $this->replacePlaceholders($filename);
        return $filename;
    }

    /**
     * Get a title for the current print style
     *
     * @return string
     */
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
        } elseif ($this->invoiceData['refunded_invoice_no'] || $this->totalSum < 0) {
            return Translator::translate('invoice::CreditInvoiceHeader');
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
