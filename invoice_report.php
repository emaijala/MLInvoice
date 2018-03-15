<?php
/**
 * Invoice report
 *
 * PHP version 5
 *
 * Copyright (C) 2010-2018 Ere Maijala
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
 * @package  MLInvoice\Reports
 * @author   Ere Maijala <ere@labs.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://labs.fi/mlinvoice.eng.php
 */
require_once 'htmlfuncs.php';
require_once 'sqlfuncs.php';
require_once 'miscfuncs.php';
require_once 'datefuncs.php';
require_once 'translator.php';
require_once 'pdf.php';
require_once 'abstract_report.php';

/**
 * Invoice report
 *
 * @category MLInvoice
 * @package  MLInvoice\Reports
 * @author   Ere Maijala <ere@labs.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://labs.fi/mlinvoice.eng.php
 */
class InvoiceReport extends AbstractReport
{
    protected $fields = [
        'invoice_no' => [
            'label' => 'InvoiceNumber',
            'checked' => true
        ],
        'invoice_date' => [
            'label' => 'InvDate',
            'checked' => true
        ],
        'due_date' => [
            'label' => 'DueDate',
            'checked' => true
        ],
        'payment_date' => [
            'label' => 'PaymentDate',
            'checked' => false
        ],
        'company_name' => [
            'label' => 'Payer',
            'checked' => true
        ],
        'status' => [
            'label' => 'InvoiceState',
            'checked' => true
        ],
        'ref_number' => [
            'label' => 'ReferenceNumber',
            'checked' => false
        ],
        'sums' => [
            'label' => 'Sum',
            'checked' => true
        ],
        'vat_breakdown' => [
            'label' => 'VATBreakdown',
            'checked' => true
        ]
    ];

    protected $reportName = 'InvoiceReport';

    protected $description = '';

    /**
     * Create the report
     *
     * @return void
     */
    public function createReport()
    {
        $strReport = getRequest('report', '');

        if ($strReport) {
            $this->printReport();
            return;
        }

        $fields = getRequest('fields[]', []);
        $rowTypes = getRequest('row_types', 'all');
        $format = getRequest('format', 'html');
        $grouping = getRequest('grouping', '');
        ?>

<script type="text/javascript">
  $(document).ready(function() {
    $('input[class~="hasDateRangePicker"]').each(function() {
      $(this).daterangepicker(<?php echo Translator::translate('DateRangePickerOptions')?>);
    });

    $('input[name=format]').click(function() {
      if ($('input[name=format]:checked').val() == 'table') {
        $('input[name=grouping]').attr('disabled', 'disabled');
      } else {
          $('input[name=grouping]').removeAttr('disabled');
      }
    });
  });
  </script>

<div class="form_container ui-widget-content ui-helper-clearfix">
    <form method="get" id="params" name="params">
        <input name="func" type="hidden" value="reports"> <input name="form"
            type="hidden" value="<?php echo getRequest('form', 'invoice') ?>"> <input name="report" type="hidden"
            value="1">

        <div class="unlimited_label">
            <strong><?php echo Translator::translate($this->reportName)?></strong>
        </div>
<?php if (!empty($this->description)) { ?>
        <div class="unlimited_label">
            <p><?php echo $this->description ?></p>
        </div>
<?php } ?>
        <div style="float: left; clear: both; margin-right: 20px;">
<?php
        $this->addLimitSelection();
?>
            <div class="medium_label">
                <?php echo Translator::translate('PrintFormat')?>
            </div>
            <div class="field">
                <label>
                    <input type="radio" id="format-html" name="format" value="html"<?php echo $format == 'html' ? ' checked="checked"' : ''?>>
                    <?php echo Translator::translate('PrintFormatHTML')?>
                </label>
            </div>
            <div class="medium_label"></div>
            <div class="field">
                <label>
                    <input type="radio" id="format-table" name="format" value="table"<?php echo $format == 'table' ? ' checked="checked"' : ''?>>
                    <?php echo Translator::translate('PrintFormatTable')?>
                </label>
            </div>
            <div class="medium_label"></div>
            <div class="field">
                <label>
                    <input type="radio" id="format-pdf" name="format" value="pdf"<?php echo $format == 'pdf' ? ' checked="checked"' : ''?>>
                    <?php echo Translator::translate('PrintFormatPDF')?>
                </label>
            </div>
            <div class="medium_label"></div>
            <div class="field">
                <label>
                    <input type="radio" id="format-pdfl" name="format" value="pdfl"<?php echo $format == 'pdfl' ? ' checked="checked"' : ''?>>
                    <?php echo Translator::translate('PrintFormatPDFLandscape')?>
                </label>
            </div>
            <div class="field_sep"></div>

            <div class="medium_label">
                <?php echo Translator::translate('InvoiceRowTypes')?>
            </div>
            <div class="field">
                <label>
                    <input type="radio" id="row-type-all" name="row_types" value="all"<?php echo $rowTypes == 'all' ? ' checked="checked"' : ''?>>
                    <?php echo Translator::translate('PrintInvoiceRowTypeAll')?>
                </label>
            </div>
            <div class="medium_label"></div>
            <div class="field">
                <label>
                    <input type="radio" id="row-type-normal" name="row_types" value="normal" <?php echo $rowTypes == 'normal' ? ' checked="checked"' : ''?>>
                    <?php echo Translator::translate('PrintInvoiceRowTypeNormal')?>
                </label>
            </div>
            <div class="medium_label"></div>
            <div class="field">
                <label>
                    <input type="radio" id="row-type-reminder" name="row_types" value="reminder"<?php echo $rowTypes == 'reminder' ? ' checked="checked"' : ''?>>
                    <?php echo Translator::translate('PrintInvoiceRowTypeReminder')?>
                </label>
            </div>
            <div class="field_sep"></div>

            <div class="medium_label">
                <?php echo Translator::translate('PrintGrouping')?>
            </div>
            <div class="field">
                <label>
                    <input type="radio" id="grouping-none" name="grouping" value=""<?php echo $grouping == '' ? ' checked="checked"' : ''?>>
                    <?php echo Translator::translate('PrintGroupingNone')?>
                </label>
            </div>
            <div class="medium_label"></div>
            <div class="field">
                <label>
                    <input type="radio" id="grouping-state" name="grouping" value="state"<?php echo $grouping == 'state' ? ' checked="checked"' : ''?>>
                    <?php echo Translator::translate('PrintGroupingState')?>
                </label>
            </div>
            <div class="medium_label"></div>
            <div class="field">
                <label>
                    <input type="radio" id="grouping-month" name="grouping" value="month"<?php echo $grouping == 'month' ? ' checked="checked"' : ''?>>
                    <?php echo Translator::translate('PrintGroupingMonth')?>
                </label>
            </div>
            <div class="medium_label"></div>
            <div class="field">
                <label>
                    <input type="radio" id="grouping-client" name="grouping" value="client"<?php echo $grouping == 'client' ? ' checked="checked"' : ''?>>
                    <?php echo Translator::translate('PrintGroupingClient')?>
                </label>
            </div>
            <div class="medium_label"></div>
            <div class="field">
                <label>
                    <input type="radio" id="grouping-vat" name="grouping" value="vat"<?php echo $grouping == 'vat' ? ' checked="checked"' : ''?>>
                    <?php echo Translator::translate('PrintGroupingVAT')?>
                </label>
            </div>
            <div class="field_sep">&nbsp;</div>
        </div>
        <?php
        $this->addInvoiceStateSelection();
        ?>
        <div style="float: left">
            <div class="medium_label"><?php echo Translator::translate('PrintFields')?></div>
        <?php
        $first = true;
        foreach ($this->fields as $field => $spec) {
            $label = Translator::translate($spec['label']);
            $checked = $spec['checked'] ? ' checked="checked"' : '';
            if (!$first) {
                echo "      <div class=\"medium_label\"></div>\n";
            }
            ?>
        <div class="field">
            <label>
                <input type="checkbox" id="field-<?php echo $field?>" name="fields[]" value="<?php echo $field?>"<?php echo $checked?>>
                <?php echo $label?>
            </label>
        </div>
            <?php
            $first = false;
        }
        ?>
        </div>
        <div class="unlimited_label">
            <a class="actionlink form-submit" href="#" data-form-target="">
                <?php echo Translator::translate('CreateReport')?>
            </a>
            <a class="actionlink form-submit" href="#" data-form-target="_blank">
                <?php echo Translator::translate('CreateReportInNewWindow')?>
            </a>
        </div>
    </form>
</div>
<?php
    }

    /**
     * Add limits
     *
     * @return void
     */
    protected function addLimitSelection()
    {
        $intBaseId = getRequest('base', false);
        $intCompanyId = getRequest('company', false);
        $invoiceDateRange = getRequest('date', '');
        $invoiceRowDateRange = getRequest('row_date', '');
        $paymentDateRange = getRequest('payment_date', '');
?>
            <div class="medium_label"><?php echo Translator::translate('InvoiceDateInterval')?></div>
            <div class="field">
                <?php echo htmlFormElement('date', 'TEXT', $invoiceDateRange, 'medium hasDateRangePicker', '', 'MODIFY', false)?>
            </div>

            <div class="medium_label"><?php echo Translator::translate('InvoiceRowDateInterval')?></div>
            <div class="field">
                <?php echo htmlFormElement('row_date', 'TEXT', $invoiceRowDateRange, 'medium hasDateRangePicker', '', 'MODIFY', false)?>
            </div>

            <div class="medium_label"><?php echo Translator::translate('PaymentDateInterval')?></div>
            <div class="field">
                <?php echo htmlFormElement('payment_date', 'TEXT', $paymentDateRange, 'medium hasDateRangePicker', '', 'MODIFY', false)?>
            </div>

            <div class="medium_label"><?php echo Translator::translate('Biller')?></div>
            <div class="field">
                <?php echo htmlFormElement('base', 'LIST', $intBaseId, 'medium', 'SELECT id, name FROM {prefix}base WHERE deleted=0 ORDER BY name', 'MODIFY', false)?>
            </div>

            <div class="medium_label"><?php echo Translator::translate('Client')?></div>
            <div class="field">
                <?php echo htmlFormElement('company', 'LIST', $intCompanyId, 'medium', 'SELECT id, company_name FROM {prefix}company WHERE deleted=0 ORDER BY company_name', 'MODIFY', false)?>
            </div>
<?php
    }

    /**
     * Create a limit query
     *
     * @return string
     */
    protected function createLimitQuery()
    {
        $strQuery = '';
        $arrParams = [];

        $intBaseId = getRequest('base', false);
        if ($intBaseId) {
            $strQuery .= ' AND i.base_id = ?';
            $arrParams[] = $intBaseId;
        }
        $intCompanyId = getRequest('company', false);
        if ($intCompanyId) {
            $strQuery .= ' AND i.company_id = ?';
            $arrParams[] = $intCompanyId;
        }

        $dateRange = explode(' - ', getRequest('date', ''));
        $startDate = $dateRange[0];
        $endDate = isset($dateRange[1]) ? $dateRange[1] : $startDate;
        if ($startDate) {
            $strQuery .= ' AND i.invoice_date >= ?';
            $arrParams[] = dateConvDate2DBDate($startDate);
        }
        if ($endDate) {
            $strQuery .= ' AND i.invoice_date <= ?';
            $arrParams[] = dateConvDate2DBDate($endDate);
        }

        $paymentDateRange = explode(' - ', getRequest('payment_date', ''));
        $paymentStartDate = $paymentDateRange[0];
        $paymentEndDate = isset($paymentDateRange[1]) ? $paymentDateRange[1] : '';
        if ($paymentStartDate) {
            $strQuery .= ' AND i.payment_date >= ?';
            $arrParams[] = dateConvDate2DBDate($paymentStartDate);
        }
        if ($paymentEndDate) {
            $strQuery .= ' AND i.payment_date <= ?';
            $arrParams[] = dateConvDate2DBDate($paymentEndDate);
        }

        return [$strQuery, $arrParams];
    }

    /**
     * Print the report
     *
     * @return void
     */
    protected function printReport()
    {
        $grouping = getRequest('grouping', '');
        $format = getRequest('format', 'html');
        $printFields = getRequest('fields', []);
        $rowTypes = getRequest('row_types', 'all');

        $strQuery = 'SELECT i.id, i.invoice_no, i.invoice_date, i.due_date,'
            . ' i.payment_date, i.ref_number, i.ref_number, c.company_name AS name,'
            . ' c.billing_address, ist.name as state, ist.invoice_unpaid as unpaid'
            . ($grouping == 'vat' ? ', ir.vat' : '')
            . ' FROM {prefix}invoice i'
            . ($grouping == 'vat' ? ' INNER JOIN {prefix}invoice_row ir ON ir.invoice_id = i.id' : '')
            . ' LEFT OUTER JOIN {prefix}company c ON c.id = i.company_id'
            . ' LEFT OUTER JOIN {prefix}invoice_state ist ON i.state_id = ist.id'
            . ' WHERE i.deleted=0';

        list($limitQuery, $arrParams) = $this->createLimitQuery();

        $strQuery .= " $limitQuery";

        $strQuery2 = '';
        $strQuery3 = 'SELECT id, name ' .
            'FROM {prefix}invoice_state WHERE deleted=0 ORDER BY order_no';
        $intRes = dbQueryCheck($strQuery3);
        while ($row = mysqli_fetch_assoc($intRes)) {
            $intStateId = $row['id'];
            $strStateName = $row['name'];
            $strTemp = "stateid_$intStateId";
            $tmpSelected = getRequest($strTemp, false);
            if ($tmpSelected) {
                $strQuery2 .= 'i.state_id = ? OR ';
                $arrParams[] = $intStateId;
            }
        }
        if ($strQuery2) {
            $strQuery2 = ' AND (' . substr($strQuery2, 0, -4) . ')';
        }

        $strQuery .= $strQuery2;
        switch ($grouping) {
        case 'state':
            $strQuery .= ' ORDER BY state_id, invoice_date, invoice_no';
            break;
        case 'client':
            $strQuery .= ' ORDER BY name, invoice_date, invoice_no';
            break;
        case 'vat':
            $strQuery .= ' GROUP BY i.id, ir.vat ORDER BY vat, invoice_date, invoice_no';
            break;
        default :
            $strQuery .= ' ORDER BY invoice_date, invoice_no';
        }

        $rowDateRange = explode(' - ', getRequest('row_date', ''));
        $rowStartDate = $rowDateRange[0];
        $rowEndDate = isset($rowDateRange[1]) ? $rowDateRange[1] : $rowStartDate;
        if ($rowStartDate) {
            $rowStartDate = dateConvDate2DBDate($rowStartDate);
        }
        if ($rowEndDate) {
            $rowEndDate = dateConvDate2DBDate($rowEndDate);
        }

        $this->printHeader($format, $printFields);

        $intTotSum = 0;
        $intTotVAT = 0;
        $intTotSumVAT = 0;
        $intTotalToPay = 0;
        $currentGroup = false;
        $groupTotSum = 0;
        $groupTotVAT = 0;
        $groupTotSumVAT = 0;
        $groupTotalToPay = 0;
        $totalsPerVAT = [];
        $rows = dbParamQuery($strQuery, $arrParams);
        foreach ($rows as $row) {
            switch ($grouping) {
            case 'state' :
                $invoiceGroup = $row['state'];
                break;
            case 'month' :
                $invoiceGroup = substr($row['invoice_date'], 4, 2);
                break;
            case 'client' :
                $invoiceGroup = $row['name'];
                break;
            case 'vat':
                $invoiceGroup = $row['vat'];
                break;
            default :
                $invoiceGroup = false;
            }

            $rowParams = [
                $row['id']
            ];
            $strQuery = 'SELECT ir.description, ir.pcs, ir.price, ir.discount, ir.discount_amount, ir.row_date, ir.vat, ir.vat_included, ir.partial_payment ' .
                 'FROM {prefix}invoice_row ir ' .
                 'WHERE ir.invoice_id=? AND ir.deleted=0';

            if ($rowStartDate) {
                $strQuery .= ' AND ir.row_date >= ?';
                $rowParams[] = $rowStartDate;
            }
            if ($rowEndDate) {
                $strQuery .= ' AND ir.row_date <= ?';
                $rowParams[] = $rowEndDate;
            }
            if ($rowTypes != 'all') {
                if ($rowTypes == 'normal') {
                    $strQuery .= ' AND ir.reminder_row = 0';
                } else if ($rowTypes == 'reminder') {
                    $strQuery .= ' AND ir.reminder_row in (1, 2)';
                }
            }

            if ($grouping == 'vat') {
                if ($row['vat'] === null) {
                    $strQuery .= ' AND ir.vat IS NULL';
                } else {
                    $strQuery .= ' AND ir.vat = ?';
                    $rowParams[] = $row['vat'];
                }
            }

            $rows2 = dbParamQuery($strQuery, $rowParams);
            $intRowSum = 0;
            $intRowVAT = 0;
            $intRowSumVAT = 0;
            $rowPayments = 0;

            if (!$rows2) {
                continue;
            }

            foreach ($rows2 as $row2) {
                $rows = true;

                if ($row2['partial_payment']) {
                    $rowPayments -= $row2['price'];
                    continue;
                }

                list($intSum, $intVAT, $intSumVAT) = calculateRowSum($row2);

                $intRowSum += $intSum;
                $intRowVAT += $intVAT;
                $intRowSumVAT += $intSumVAT;

                if (!isset($totalsPerVAT[$row2['vat']])) {
                    $totalsPerVAT[$row2['vat']] = [
                        'sum' => $intSum,
                        'VAT' => $intVAT,
                        'sumVAT' => $intSumVAT
                    ];
                } else {
                    $totalsPerVAT[$row2['vat']]['sum'] += $intSum;
                    $totalsPerVAT[$row2['vat']]['VAT'] += $intVAT;
                    $totalsPerVAT[$row2['vat']]['sumVAT'] += $intSumVAT;
                }
            }

            $intTotSum += $intRowSum;
            $intTotVAT += $intRowVAT;
            $intTotSumVAT += $intRowSumVAT;

            if ($row['unpaid']) {
                $intTotalToPay += $intRowSumVAT - $rowPayments;
            } else {
                $rowPayments = $intRowSumVAT;
            }

            if ($grouping && $currentGroup !== false && $currentGroup != $invoiceGroup) {
                $this->printGroupSums(
                    $format, $printFields, $row, $groupTotSum,
                    $groupTotVAT, $groupTotSumVAT, $groupTotalToPay,
                    $grouping == 'vat' ? Translator::translate('VAT') . ' ' . miscRound2Decim($currentGroup) : ''
                );
                $groupTotSum = 0;
                $groupTotVAT = 0;
                $groupTotSumVAT = 0;
                $groupTotalToPay = 0;
            }
            $currentGroup = $invoiceGroup;

            $groupTotSum += $intRowSum;
            $groupTotVAT += $intRowVAT;
            $groupTotSumVAT += $intRowSumVAT;
            $groupTotalToPay += $intRowSumVAT - $rowPayments;

            $this->printRow(
                $format, $printFields, $row, $intRowSum, $intRowVAT,
                $intRowSumVAT, $intRowSumVAT - $rowPayments
            );
        }
        if ($grouping) {
            $this->printGroupSums(
                $format, $printFields, $row, $groupTotSum,
                $groupTotVAT, $groupTotSumVAT, $groupTotalToPay,
                $grouping == 'vat' ? Translator::translate('VAT') . ' '
                    . miscRound2Decim($currentGroup) : ''
            );
        }
        ksort($totalsPerVAT, SORT_NUMERIC);
        $this->printTotals(
            $format, $printFields, $intTotSum, $intTotVAT,
            $intTotSumVAT, $intTotalToPay, $totalsPerVAT
        );
        $this->printFooter($format, $printFields);
    }

    /**
     * Print header
     *
     * @param string $format      Print format
     * @param array  $printFields Fields to print
     *
     * @return void
     */
    protected function printHeader($format, $printFields)
    {
        if ($format == 'pdf' || $format == 'pdfl') {
            ob_end_clean();
            $pdf = new PDF(
                $format == 'pdf' ? 'P' : 'L', 'mm', 'A4',
                _CHARSET_ == 'UTF-8', _CHARSET_, false
            );
            $pdf->SetFillColor(255, 255, 255);
            if ($format == 'pdfl') {
                $pdf->headerRightPos = 223;
            }
            $pdf->setTopMargin(20);
            if ('pdf' === $format) {
                $pdf->setLeftMargin(5);
            }
            $pdf->headerRight = Translator::translate('ReportPage');
            $pdf->printHeaderOnFirstPage = true;
            $pdf->AddPage();
            $pdf->SetAutoPageBreak(true, 15);

            $pdf->setY(10);
            $pdf->SetFont('Helvetica', 'B', 12);
            $pdf->Cell(100, 15, Translator::translate($this->reportName), 0, 1, 'L');

            $pdf->SetFont('Helvetica', '', 8);
            $pdf->MultiCell(180, 5, $this->getParamsStr(false), 0, 'L');
            $pdf->setY($pdf->getY() + 5);

            $pdf->SetFont('Helvetica', 'B', 8);

            if (in_array('invoice_no', $printFields)) {
                $pdf->Cell(18, 4, Translator::translate('InvoiceNumber'), 0, 0, 'L');
            }
            if (in_array('invoice_date', $printFields)) {
                $pdf->Cell(20, 4, Translator::translate('InvDate'), 0, 0, 'L');
            }
            if (in_array('due_date', $printFields)) {
                $pdf->Cell(20, 4, Translator::translate('DueDate'), 0, 0, 'L');
            }
            if (in_array('payment_date', $printFields)) {
                $pdf->Cell(20, 4, Translator::translate('PaymentDate'), 0, 0, 'L');
            }
            if (in_array('company_name', $printFields)) {
                $pdf->Cell(40, 4, Translator::translate('Payer'), 0, 0, 'L');
            }
            if (in_array('status', $printFields)) {
                $pdf->Cell(20, 4, Translator::translate('State'), 0, 0, 'L');
            }
            if (in_array('ref_number', $printFields)) {
                $pdf->Cell(20, 4, Translator::translate('ReferenceNumber'), 0, 0, 'L');
            }
            if (in_array('sums', $printFields)) {
                $pdf->Cell(20, 4, Translator::translate('VATLess'), 0, 0, 'R');
                $pdf->Cell(20, 4, Translator::translate('VATPart'), 0, 0, 'R');
                $pdf->Cell(20, 4, Translator::translate('WithVAT'), 0, 0, 'R');
                $pdf->Cell(20, 4, Translator::translate('TotalToPay'), 0, 1, 'R');
            }

            $this->pdf = $pdf;
            return;
        }
        ?>
  <div class="report">
    <table class="report-table">
      <tr>
        <td>
          <div class="unlimited_label">
            <strong><?php echo Translator::translate($this->reportName)?></strong>
          </div>
        </td>
      </tr>
      <tr>
        <td>
            <?php echo $this->getParamsStr(true) ?>
        </td>
      </tr>
    </table>

    <table class="report-table<?php echo $format == 'table' ? ' datatable' : '' ?>">
      <thead>
        <tr>
        <?php
        if (in_array('invoice_no', $printFields)) {
            ?>
        <th class="label">
            <?php echo Translator::translate('InvoiceNumber')?>
        </th>
        <?php
        }
        if (in_array('invoice_date', $printFields)) {
            ?>
        <th class="label">
            <?php echo Translator::translate('InvDate')?>
        </th>
        <?php
        }
        if (in_array('due_date', $printFields)) {
            ?>
        <th class="label">
            <?php echo Translator::translate('DueDate')?>
        </th>
            <?php
        }
        if (in_array('payment_date', $printFields)) {
            ?>
        <th class="label">
            <?php echo Translator::translate('PaymentDate')?>
        </th>
            <?php
        }
        if (in_array('company_name', $printFields)) {
            ?>
        <th class="label">
            <?php echo Translator::translate('Payer')?>
        </th>
            <?php
        }
        if (in_array('status', $printFields)) {
            ?>
        <th class="label">
            <?php echo Translator::translate('InvoiceState')?>
        </th>
            <?php
        }
        if (in_array('ref_number', $printFields)) {
            ?>
        <th class="label">
            <?php echo Translator::translate('ReferenceNumber')?>
        </th>
            <?php
        }
        if (in_array('sums', $printFields)) {
            ?>
        <th class="label" style="text-align: right">
            <?php echo Translator::translate('VATLess')?>
        </th>
        <th class="label" style="text-align: right">
            <?php echo Translator::translate('VATPart')?>
        </th>
        <th class="label" style="text-align: right">
            <?php echo Translator::translate('WithVAT')?>
        </th>
        <th class="label" style="text-align: right">
            <?php echo Translator::translate('TotalToPay')?>
        </th>
        <?php } ?>
    </tr>
    </thead>
    <tbody>
<?php
    }

    /**
     * Print a row
     *
     * @param string $format        Print format
     * @param array  $printFields   Fields to print
     * @param array  $row           Row data
     * @param int    $intRowSum     Row sum
     * @param int    $intRowVAT     Row VAT
     * @param int    $intRowSumVAT  Row sum including VAT
     * @param int    $rowTotalToPay Total to pay for the row
     *
     * @return void
     */
    protected function printRow($format, $printFields, $row, $intRowSum, $intRowVAT,
        $intRowSumVAT, $rowTotalToPay
    ) {
        if ($format == 'pdf' || $format == 'pdfl') {
            $pdf = $this->pdf;
            $pdf->SetFont('Helvetica', '', 8);
            $pdf->setY($pdf->getY() + 1);
            if (in_array('invoice_no', $printFields)) {
                $pdf->Cell(18, 4, $row['invoice_no'], 0, 0, 'L');
            }
            if (in_array('invoice_date', $printFields)) {
                $pdf->Cell(
                    20, 4, dateConvDBDate2Date($row['invoice_date']), 0, 0, 'L'
                );
            }
            if (in_array('due_date', $printFields)) {
                $pdf->Cell(20, 4, dateConvDBDate2Date($row['due_date']), 0, 0, 'L');
            }
            if (in_array('payment_date', $printFields)) {
                $pdf->Cell(
                    20, 4, dateConvDBDate2Date($row['payment_date']), 0, 0, 'L'
                );
            }
            if (in_array('company_name', $printFields)) {
                $nameX = $pdf->getX();
                $pdf->setX($nameX + 40);
            }
            if (in_array('status', $printFields)) {
                $pdf->Cell(20, 4, Translator::translate($row['state']), 0, 0, 'L');
            }
            if (in_array('ref_number', $printFields)) {
                $pdf->Cell(
                    20, 4, formatRefNumber($row['ref_number']), 0, 0, 'L', true
                );
            }
            if (in_array('sums', $printFields)) {
                $pdf->Cell(20, 4, miscRound2Decim($intRowSum), 0, 0, 'R');
                $pdf->Cell(20, 4, miscRound2Decim($intRowVAT), 0, 0, 'R');
                $pdf->Cell(20, 4, miscRound2Decim($intRowSumVAT), 0, 0, 'R');
                $pdf->Cell(20, 4, miscRound2Decim($rowTotalToPay), 0, 0, 'R');
            }
            // Print company name last, as it can span multiple lines
            if (in_array('company_name', $printFields)) {
                $pdf->setX($nameX);
                $pdf->MultiCell(40, 4, $row['name'], 0, 'L');
            }
            return;
        }
        ?>
    <tr>
        <?php
        if (in_array('invoice_no', $printFields)) {
            ?>
        <td class="input" data-sort="<?php echo htmlspecialchars($row['invoice_no'])?>">
          <a href="index.php?func=invoices&list=invoices&form=invoice&id=<?php echo htmlspecialchars($row['id'])?>">
            <?php echo('' != $row['invoice_no'] ? htmlspecialchars($row['invoice_no']) : '-')?>
          </a>
        </td>
            <?php
        }
        if (in_array('invoice_date', $printFields)) {
            ?>
        <td class="input">
            <?php echo htmlspecialchars(dateConvDBDate2Date($row['invoice_date']))?>
        </td>
            <?php
        }
        if (in_array('due_date', $printFields)) {
            ?>
        <td class="input">
            <?php echo htmlspecialchars(dateConvDBDate2Date($row['due_date']))?>
        </td>
            <?php
        }
        if (in_array('payment_date', $printFields)) {
            ?>
        <td class="input">
            <?php echo htmlspecialchars(dateConvDBDate2Date($row['payment_date']))?>
        </td>
            <?php
        }
        if (in_array('company_name', $printFields)) {
            ?>
        <td class="input">
            <?php echo htmlspecialchars($row['name'])?>
        </td>
            <?php
        }
        if (in_array('status', $printFields)) {
            ?>
        <td class="input">
            <?php echo htmlspecialchars(Translator::translate($row['state']))?>
        </td>
            <?php
        }
        if (in_array('ref_number', $printFields)) {
            ?>
        <td class="input">
            <?php echo htmlspecialchars(formatRefNumber($row['ref_number']))?>
        </td>
            <?php
        }
        if (in_array('sums', $printFields)) {
            ?>
        <td class="input" style="text-align: right">
            <?php echo miscRound2Decim($intRowSum)?>
        </td>
            <td class="input" style="text-align: right">
            <?php echo miscRound2Decim($intRowVAT)?>
        </td>
            <td class="input" style="text-align: right">
            <?php echo miscRound2Decim($intRowSumVAT)?>
        </td>
            <td class="input" style="text-align: right">
            <?php echo miscRound2Decim($rowTotalToPay)?>
        </td>
        <?php
        }
    ?>
      </tr>
    <?php
    }

    /**
     * Print a group sum
     *
     * @param string $format          Print format
     * @param array  $printFields     Fields to print
     * @param array  $row             Row data
     * @param int    $groupTotSum     Group total sum
     * @param int    $groupTotVAT     Group total VAT
     * @param int    $groupTotSumVAT  Group total sum including VAT
     * @param int    $groupTotalToPay Total to pay for the group
     * @param string $groupTitle      Group title
     *
     * @return void
     */
    protected function printGroupSums($format, $printFields, $row, $groupTotSum,
        $groupTotVAT, $groupTotSumVAT, $groupTotalToPay, $groupTitle
    ) {
        if (!in_array('sums', $printFields)) {
            return;
        }
        if ($format == 'pdf' || $format == 'pdfl') {
            $pdf = $this->pdf;
            if ($pdf->getY() > $pdf->getPageHeight() - 7 - 15) {
                $pdf->AddPage();
            }
            $pdf->SetFont('Helvetica', '', 8);
            $pdf->setLineWidth(0.2);

            $rowWidth = 0;
            if (in_array('invoice_no', $printFields)) {
                $rowWidth += 18;
            }
            if (in_array('invoice_date', $printFields)) {
                $rowWidth += 20;
            }
            if (in_array('due_date', $printFields)) {
                $rowWidth += 20;
            }
            if (in_array('payment_date', $printFields)) {
                $rowWidth += 20;
            }
            if (in_array('company_name', $printFields)) {
                $rowWidth += 40;
            }
            if (in_array('status', $printFields)) {
                $rowWidth += 20;
            }
            if (in_array('ref_number', $printFields)) {
                $rowWidth += 20;
            }
            $sumPos = $rowWidth;
            $rowWidth += 80;
            if ($groupTitle) {
                $sumPos -= 25;
            }

            $pdf->line(
                $pdf->getX() + $sumPos, $pdf->getY(), $pdf->getX() + $rowWidth,
                $pdf->getY()
            );
            $pdf->setXY($pdf->getX() + $sumPos, $pdf->getY() + 1);
            if ($groupTitle) {
                $pdf->Cell(25, 4, $groupTitle, 0, 0, 'R');
            }
            $pdf->Cell(20, 4, miscRound2Decim($groupTotSum), 0, 0, 'R');
            $pdf->Cell(20, 4, miscRound2Decim($groupTotVAT), 0, 0, 'R');
            $pdf->Cell(20, 4, miscRound2Decim($groupTotSumVAT), 0, 0, 'R');
            $pdf->Cell(20, 4, miscRound2Decim($groupTotalToPay), 0, 1, 'R');
            $pdf->setY($pdf->getY() + 2);
            return;
        }

        if ($format != 'html') {
            return;
        }

        $colSpan = 0;
        if (in_array('invoice_no', $printFields)) {
            ++$colSpan;
        }
        if (in_array('invoice_date', $printFields)) {
            ++$colSpan;
        }
        if (in_array('due_date', $printFields)) {
            ++$colSpan;
        }
        if (in_array('payment_date', $printFields)) {
            ++$colSpan;
        }
        if (in_array('company_name', $printFields)) {
            ++$colSpan;
        }
        if (in_array('status', $printFields)) {
            ++$colSpan;
        }
        if (in_array('ref_number', $printFields)) {
            ++$colSpan;
        }

        if ($groupTitle) {
            --$colSpan;
        }

        ?>
    <tr>
    <?php if ($colSpan > 0) { ?>
        <td class="input" colspan="<?php echo $colSpan?>">&nbsp;</td>
    <?php } ?>
    <?php if ($groupTitle) { ?>
        <td class="input row_sum" style="text-align: right">
            &nbsp;<?php echo htmlentities($groupTitle)?>
        </td>
    <?php } ?>
        <td class="input row_sum" style="text-align: right">
            &nbsp;<?php echo miscRound2Decim($groupTotSum)?>
        </td>
        <td class="input row_sum" style="text-align: right">
            &nbsp;<?php echo miscRound2Decim($groupTotVAT)?>
        </td>
        <td class="input row_sum" style="text-align: right">
            &nbsp;<?php echo miscRound2Decim($groupTotSumVAT)?>
        </td>
        <td class="input row_sum" style="text-align: right">
            &nbsp;<?php echo miscRound2Decim($groupTotalToPay)?>
        </td>
        </tr>
<?php
    }

    /**
     * Print report totals
     *
     * @param string $format       Print format
     * @param array  $printFields  Fields to print
     * @param int    $intTotSum    Total sum
     * @param int    $intTotVAT    Total VAT
     * @param int    $intTotSumVAT Total sum including VAT
     * @param int    $totalToPay   Total to pay for the group
     * @param array  $totalsPerVAT Totals grouped by VAT
     *
     * @return void
     */
    protected function printTotals($format, $printFields, $intTotSum, $intTotVAT,
        $intTotSumVAT, $totalToPay, $totalsPerVAT
    ) {
        if (!in_array('sums', $printFields)) {
            return;
        }
        if ($format == 'pdf' || $format == 'pdfl') {
            $pdf = $this->pdf;
            if ($pdf->getY() > $pdf->getPageHeight() - 7 - 15) {
                $pdf->AddPage();
            }
            $pdf->SetFont('Helvetica', '', 8);
            $pdf->setLineWidth(0.2);

            $rowWidth = 0;
            $sumPos = 75;
            if (in_array('invoice_no', $printFields)) {
                $rowWidth += 18;
            }
            if (in_array('invoice_date', $printFields)) {
                $rowWidth += 20;
            }
            if (in_array('due_date', $printFields)) {
                $rowWidth += 20;
            }
            if (in_array('payment_date', $printFields)) {
                $rowWidth += 20;
            }
            if (in_array('company_name', $printFields)) {
                $rowWidth += 40;
            }
            if (in_array('status', $printFields)) {
                $rowWidth += 20;
            }
            if (in_array('ref_number', $printFields)) {
                $rowWidth += 20;
            }
            $sumPos = $rowWidth;
            $rowWidth += 80;

            $pdf = $this->pdf;
            $pdf->SetFont('Helvetica', 'B', 8);
            $pdf->line(
                $pdf->getX() + $sumPos, $pdf->getY(),
                $pdf->getX() + $rowWidth, $pdf->getY()
            );
            $pdf->setY($pdf->getY() + 1);
            $pdf->Cell($sumPos, 4, Translator::translate('Total'), 0, 0, 'R');
            $pdf->Cell(20, 4, miscRound2Decim($intTotSum), 0, 0, 'R');
            $pdf->Cell(20, 4, miscRound2Decim($intTotVAT), 0, 0, 'R');
            $pdf->Cell(20, 4, miscRound2Decim($intTotSumVAT), 0, 0, 'R');
            $pdf->Cell(20, 4, miscRound2Decim($totalToPay), 0, 1, 'R');

            if (in_array('vat_breakdown', $printFields)) {
                if ($pdf->getY() > $pdf->getPageHeight() - 30) {
                    $pdf->AddPage();
                } else {
                    $pdf->setY($pdf->getY() + 4);
                }

                $pdf->setY($pdf->getY() + 4);
                $pdf->Cell(20, 4, Translator::translate('VATBreakdown'), 0, 0, 'R');
                $pdf->Cell(25, 4, Translator::translate('VATLess'), 0, 0, 'R');
                $pdf->Cell(25, 4, Translator::translate('VATPart'), 0, 0, 'R');
                $pdf->Cell(25, 4, Translator::translate('WithVAT'), 0, 1, 'R');
                $pdf->SetFont('Helvetica', '', 8);
                foreach ($totalsPerVAT as $vat => $sums) {
                    $pdf->Cell(20, 4, miscRound2OptDecim($vat) . '%', 0, 0, 'R');
                    $pdf->Cell(25, 4, miscRound2Decim($sums['sum']), 0, 0, 'R');
                    $pdf->Cell(25, 4, miscRound2Decim($sums['VAT']), 0, 0, 'R');
                    $pdf->Cell(25, 4, miscRound2Decim($sums['sumVAT']), 0, 1, 'R');
                }
            }

            return;
        }

        if ($format != 'html') {
            return;
        }

        $colSpan = $this->getSumStartCol($printFields);
        ?>
    <tr>
    <?php if ($colSpan > 0) { ?>
        <td class="input total_sum" colspan="<?php echo $colSpan?>"
                style="text-align: right">
            <?php echo Translator::translate('Total')?>
        </td>
    <?php } ?>
        <td class="input total_sum" style="text-align: right">
            &nbsp;<?php echo miscRound2Decim($intTotSum)?>
        </td>
        <td class="input total_sum" style="text-align: right">
            &nbsp;<?php echo miscRound2Decim($intTotVAT)?>
        </td>
        <td class="input total_sum" style="text-align: right">
            &nbsp;<?php echo miscRound2Decim($intTotSumVAT)?>
        </td>
        <td class="input total_sum" style="text-align: right">
            &nbsp;<?php echo miscRound2Decim($totalToPay)?>
        </td>
    </tr>
        <?php
        if (in_array('vat_breakdown', $printFields)) {
?>
    </table>
    <table>
        <tr>
            <th class="label" style="text-align: right"><?php echo Translator::translate('VATBreakdown')?></th>
            <th class="label" style="text-align: right"><?php echo Translator::translate('VATLess')?></th>
            <th class="label" style="text-align: right"><?php echo Translator::translate('VATPart')?></th>
            <th class="label" style="text-align: right"><?php echo Translator::translate('WithVAT')?></th>
        </tr>
            <?php
            foreach ($totalsPerVAT as $vat => $sums) {
?>
        <tr>
            <td class="input" style="text-align: right"><?php echo miscRound2OptDecim($vat)?>%</td>
            <td class="input" style="text-align: right"><?php echo miscRound2Decim($sums['sum'])?></td>
            <td class="input" style="text-align: right"><?php echo miscRound2Decim($sums['VAT'])?></td>
            <td class="input" style="text-align: right"><?php echo miscRound2Decim($sums['sumVAT'])?></td>
        </tr>
            <?php
            }
        }
    }

    /**
     * Print footer
     *
     * @param string $format      Print format
     * @param array  $printFields Fields to print
     *
     * @return void
     */
    protected function printFooter($format, $printFields)
    {
        if ($format == 'pdf' || $format == 'pdfl') {
            $pdf = $this->pdf;
            $pdf->Output('report.pdf', 'I');
            return;
        }
        $sumStartCol = $this->getSumStartCol($printFields);
        ?>
        </tbody>
        <tfoot>
          <tr>
            <?php
            for ($i = 0; $i < $sumStartCol + 4; $i++) {
                echo "<td></td>\n";
            }
            ?>
          </tr>
        </tfoot>
    </table>
  </div>
        <?php
        if ($format == 'table') {
            $sumColumns = [$sumStartCol, $sumStartCol + 1, $sumStartCol + 2, $sumStartCol + 3];
        ?>
<script type="text/javascript">
var table = $('.report-table.datatable').DataTable({
    'language': {
        <?php echo Translator::translate('TableTexts')?>
    },
    'pageLength': 50,
    'jQueryUI': true,
    'pagingType': 'full_numbers',
    'footerCallback': function (row, data, start, end, display) {
        var api = this.api(), data;

        $([<?php echo implode(', ', $sumColumns)?>]).each(function(i, column) {
            // Total over all pages
            var total = api
                .column(column)
                .data()
                .reduce(function (a, b) {
                    return intVal(a) + intVal(b);
                }, 0);


            // Total over this page
            var pageTotal = api
                .column(column, { page: 'current'})
                .data()
                .reduce(function (a, b) {
                    return intVal(a) + intVal(b);
                }, 0);

            // Update footer
            pageTotal = MLInvoice.formatCurrency(pageTotal/100);
            total = MLInvoice.formatCurrency(total/100);
            $(api.column(column).footer()).html(
                '<div style="float: right"><?php echo Translator::translate('VisiblePage') ?>&nbsp;'
                + pageTotal + '</div><br><div style="float: right"><?php echo Translator::translate('Total') ?>&nbsp;'
                + total + '</div>'
            );
        });
    }
});

var buttons = new $.fn.dataTable.Buttons(table, {
    buttons: [
        'copy', 'csv', 'excel', 'pdf'
    ]
});

table.buttons().container().appendTo($('.fg-toolbar', table.table().container()));
</script>
<?php
        }
    }

    /**
     * Get the first sum column
     *
     * @param array $printFields Fields to print
     *
     * @return int
     */
    protected function getSumStartCol($printFields)
    {
        $startCol = 0;
        if (in_array('invoice_no', $printFields)) {
            ++$startCol;
        }
        if (in_array('invoice_date', $printFields)) {
            ++$startCol;
        }
        if (in_array('due_date', $printFields)) {
            ++$startCol;
        }
        if (in_array('payment_date', $printFields)) {
            ++$startCol;
        }
        if (in_array('company_name', $printFields)) {
            ++$startCol;
        }
        if (in_array('status', $printFields)) {
            ++$startCol;
        }
        if (in_array('ref_number', $printFields)) {
            ++$startCol;
        }

        return $startCol;
    }
}
