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
require_once 'htmlfuncs.php';
require_once 'sqlfuncs.php';
require_once 'miscfuncs.php';
require_once 'datefuncs.php';
require_once 'localize.php';
require_once 'pdf.php';

class InvoiceReport
{
    protected $fields = [
        'invoice_no' => [
            'label' => 'locInvoiceNumber',
            'checked' => true
        ],
        'invoice_date' => [
            'label' => 'locInvDate',
            'checked' => true
        ],
        'due_date' => [
            'label' => 'locDueDate',
            'checked' => true
        ],
        'payment_date' => [
            'label' => 'locPaymentDate',
            'checked' => false
        ],
        'company_name' => [
            'label' => 'locPayer',
            'checked' => true
        ],
        'status' => [
            'label' => 'locInvoiceState',
            'checked' => true
        ],
        'ref_number' => [
            'label' => 'locReferenceNumber',
            'checked' => false
        ],
        'sums' => [
            'label' => 'locSum',
            'checked' => true
        ],
        'vat_breakdown' => [
            'label' => 'locVATBreakdown',
            'checked' => true
        ]
    ];
    protected $pdf = null;

    public function createReport()
    {
        $strReport = getRequest('report', '');

        if ($strReport) {
            $this->printReport();
            return;
        }

        $intBaseId = getRequest('base', false);
        $intCompanyId = getRequest('company', false);
        $invoiceDateRange = getRequest('date', '');
        $invoiceRowDateRange = getRequest('row_date', '');
        $paymentDateRange = getRequest('payment_date', '');
        $fields = getRequest('fields[]', []);
        $rowTypes = getRequest('row_types', 'all');
        $format = getRequest('format', 'html');
        $grouping = getRequest('grouping', '');
        ?>

<script type="text/javascript">
  $(document).ready(function() {
    $('input[class~="hasDateRangePicker"]').each(function() {
      $(this).daterangepicker(<?php echo $GLOBALS['locDateRangePickerOptions']?>);
    });
  });
  </script>

<div class="form_container ui-widget-content ui-helper-clearfix">
	<form method="get" id="params" name="params">
		<input name="func" type="hidden" value="reports"> <input name="form"
			type="hidden" value="invoice"> <input name="report" type="hidden"
			value="1">

		<div class="unlimited_label">
			<strong><?php echo $GLOBALS['locInvoiceReport']?></strong>
		</div>

		<div style="float: left; clear: both; margin-right: 20px;">

			<div class="medium_label"><?php echo $GLOBALS['locInvoiceDateInterval']?></div>
			<div class="field"><?php echo htmlFormElement('date', 'TEXT', $invoiceDateRange, 'medium hasDateRangePicker', '', 'MODIFY', false)?></div>

			<div class="medium_label"><?php echo $GLOBALS['locInvoiceRowDateInterval']?></div>
			<div class="field"><?php echo htmlFormElement('row_date', 'TEXT', $invoiceRowDateRange, 'medium hasDateRangePicker', '', 'MODIFY', false)?></div>

			<div class="medium_label"><?php echo $GLOBALS['locPaymentDateInterval']?></div>
			<div class="field"><?php echo htmlFormElement('payment_date', 'TEXT', $paymentDateRange, 'medium hasDateRangePicker', '', 'MODIFY', false)?></div>

			<div class="medium_label"><?php echo $GLOBALS['locBiller']?></div>
			<div class="field"><?php echo htmlFormElement('base', 'LIST', $intBaseId, 'medium', 'SELECT id, name FROM {prefix}base WHERE deleted=0 ORDER BY name', 'MODIFY', false)?></div>

			<div class="medium_label"><?php echo $GLOBALS['locClient']?></div>
			<div class="field"><?php echo htmlFormElement('company', 'LIST', $intCompanyId, 'medium', 'SELECT id, company_name FROM {prefix}company WHERE deleted=0 ORDER BY company_name', 'MODIFY', false)?></div>

			<div class="medium_label"><?php echo $GLOBALS['locPrintFormat']?></div>
			<div class="field">
				<input type="radio" id="format-html" name="format" value="html"
					<?php if ($format == 'html') echo ' checked="checked"'?>><label for="format-html"><?php echo $GLOBALS['locPrintFormatHTML']?></label></div>
			<div class="medium_label"></div>
			<div class="field">
				<input type="radio" id="format-pdf" name="format" value="pdf"
					<?php if ($format == 'pdf') echo ' checked="checked"'?>><label for="format-pdf"><?php echo $GLOBALS['locPrintFormatPDF']?></label></div>
			<div class="medium_label"></div>
			<div class="field">
				<input type="radio" id="format-pdfl" name="format" value="pdfl"
					<?php if ($format == 'pdfl') echo ' checked="checked"'?>><label for="format-pdfl"><?php echo $GLOBALS['locPrintFormatPDFLandscape']?></label></div>
			<div class="field_sep"></div>

			<div class="medium_label"><?php echo $GLOBALS['locInvoiceRowTypes']?></div>
			<div class="field">
				<input type="radio" id="row-type-all" name="row_types" value="all"
					<?php if ($rowTypes == 'all') echo ' checked="checked"'?>><label for="row-type-all"><?php echo $GLOBALS['locPrintInvoiceRowTypeAll']?></label></div>
			<div class="medium_label"></div>
			<div class="field">
				<input type="radio" id="row-type-normal" name="row_types" value="normal"
					<?php if ($rowTypes == 'normal') echo ' checked="checked"'?>><label for="row-type-normal"><?php echo $GLOBALS['locPrintInvoiceRowTypeNormal']?></label></div>
			<div class="medium_label"></div>
			<div class="field">
				<input type="radio" id="row-type-reminder" name="row_types" value="reminder"
					<?php if ($rowTypes == 'reminder') echo ' checked="checked"'?>><label for="row-type-reminder"><?php echo $GLOBALS['locPrintInvoiceRowTypeReminder']?></label></div>
			<div class="field_sep"></div>

			<div class="medium_label"><?php echo $GLOBALS['locPrintGrouping']?></div>
			<div class="field">
				<input type="radio" id="grouping-none" name="grouping" value=""
					<?php if ($grouping == '') echo ' checked="checked"'?>><label for="grouping-none"><?php echo $GLOBALS['locPrintGroupingNone']?></label></div>
			<div class="medium_label"></div>
			<div class="field">
				<input type="radio" id="grouping-state" name="grouping" value="state"
					<?php if ($grouping == 'state') echo ' checked="checked"'?>><label for="grouping-state"><?php echo $GLOBALS['locPrintGroupingState']?></label></div>
			<div class="medium_label"></div>
			<div class="field">
				<input type="radio" id="grouping-month" name="grouping" value="month"
					<?php if ($grouping == 'month') echo ' checked="checked"'?>><label for="grouping-month"><?php echo $GLOBALS['locPrintGroupingMonth']?></label></div>
			<div class="medium_label"></div>
			<div class="field">
				<input type="radio" id="grouping-client" name="grouping" value="client"
					<?php if ($grouping == 'client') echo ' checked="checked"'?>><label for="grouping-client"><?php echo $GLOBALS['locPrintGroupingClient']?></label></div>
			<div class="medium_label"></div>
			<div class="field">
				<input type="radio" id="grouping-vat" name="grouping" value="vat"
					<?php if ($grouping == 'vat') echo ' checked="checked"'?>><label for="grouping-vat"><?php echo $GLOBALS['locPrintGroupingVAT']?></label></div>
			<div class="field_sep">&nbsp;</div>

		</div>
		<div style="float: left; margin-right: 20px;">
			<div class="medium_label"><?php echo $GLOBALS['locPrintReportStates']?></div>
<?php
        $strQuery = 'SELECT id, name ' . 'FROM {prefix}invoice_state WHERE deleted=0 ' .
             'ORDER BY order_no';
        $intRes = mysqli_query_check($strQuery);
        $first = true;
        while ($row = mysqli_fetch_assoc($intRes)) {
            $intStateId = $row['id'];
            $strStateName = isset($GLOBALS['loc' . $row['name']]) ? $GLOBALS['loc' .
                 $row['name']] : $row['name'];
            $tmpSelected = getRequest("stateid_$intStateId", TRUE) ? TRUE : false;
            $strChecked = $tmpSelected ? ' checked' : '';
            if (!$first) {
                echo "      <div class=\"medium_label\"></div>\n";
            }
            $first = false;
            ?>
      <div class="field">
				<input type="checkbox" id="state-<?php echo $intStateId?>" name="stateid_<?php echo $intStateId?>"
					value="1" <?php echo $strChecked?>> <label for="state-<?php echo $intStateId?>"><?php echo htmlspecialchars($strStateName)?></label></div>
<?php
        }
        ?>
    </div>
		<div style="float: left">
			<div class="medium_label"><?php echo $GLOBALS['locPrintFields']?></div>
<?php
        $first = true;
        foreach ($this->fields as $field => $spec) {
            $label = $GLOBALS[$spec['label']];
            $checked = $spec['checked'] ? 'checked="checked"' : '';
            if (!$first) {
                echo "      <div class=\"medium_label\"></div>\n";
            }
            ?>
        <div class="field">
				<input type="checkbox" id="field-<?php echo $field?>" name="fields[]" value="<?php echo $field?>"
					<?php echo $checked?>> <label for="field-<?php echo $field?>"><?php echo $label?></label></div>
<?php
            $first = false;
        }
        ?>
    </div>
		<div class="medium_label">
			<a class="actionlink" href="#"
				onclick="document.getElementById('params').submit(); return false;"><?php echo $GLOBALS['locCreateReport']?></a>
		</div>
	</form>
</div>
<?php
    }

    private function printReport()
    {
        $intBaseId = getRequest('base', false);
        $intCompanyId = getRequest('company', false);
        $grouping = getRequest('grouping', '');
        $format = getRequest('format', 'html');
        $printFields = getRequest('fields', []);
        $rowTypes = getRequest('row_types', 'all');

        $dateRange = explode(' - ', getRequest('date', ''));
        $startDate = $dateRange[0];
        $endDate = isset($dateRange[1]) ? $dateRange[1] : $startDate;
        if ($startDate) {
            $startDate = dateConvDate2DBDate($startDate);
        }
        if ($endDate) {
            $endDate = dateConvDate2DBDate($endDate);
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

        $paymentDateRange = explode(' - ', getRequest('payment_date', ''));
        $paymentStartDate = $paymentDateRange[0];
        $paymentEndDate = isset($paymentDateRange[1]) ? $paymentDateRange[1] : '';
        if ($paymentStartDate) {
            $paymentStartDate = dateConvDate2DBDate($paymentStartDate);
        }
        if ($paymentEndDate) {
            $paymentEndDate = dateConvDate2DBDate($paymentEndDate);
        }

        $arrParams = [];

        $strQuery = 'SELECT i.id, i.invoice_no, i.invoice_date, i.due_date, i.payment_date, i.ref_number, i.ref_number, c.company_name AS name, c.billing_address, ist.name as state, ist.invoice_unpaid as unpaid' .
            ($grouping == 'vat' ? ', ir.vat' : '') .
            ' FROM {prefix}invoice i' .
            ($grouping == 'vat' ? ' INNER JOIN {prefix}invoice_row ir ON ir.invoice_id = i.id' : '') .
            ' LEFT OUTER JOIN {prefix}company c ON c.id = i.company_id' .
            ' LEFT OUTER JOIN {prefix}invoice_state ist ON i.state_id = ist.id' .
            ' WHERE i.deleted=0';

        if ($startDate) {
            $strQuery .= ' AND i.invoice_date >= ?';
            $arrParams[] = $startDate;
        }
        if ($endDate) {
            $strQuery .= ' AND i.invoice_date <= ?';
            $arrParams[] = $endDate;
        }
        if ($paymentStartDate) {
            $strQuery .= ' AND i.payment_date >= ?';
            $arrParams[] = $paymentStartDate;
        }
        if ($paymentEndDate) {
            $strQuery .= ' AND i.payment_date <= ?';
            $arrParams[] = $paymentEndDate;
        }
        if ($intBaseId) {
            $strQuery .= ' AND i.base_id = ?';
            $arrParams[] = $intBaseId;
        }

        if ($intCompanyId) {
            $strQuery .= ' AND i.company_id = ?';
            $arrParams[] = $intCompanyId;
        }

        $strQuery2 = '';
        $strQuery3 = 'SELECT id, name ' .
            'FROM {prefix}invoice_state WHERE deleted=0 ORDER BY order_no';
        $intRes = mysqli_query_check($strQuery3);
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

        $this->printHeader($format, $printFields, $startDate, $endDate);

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
        $intRes = mysqli_param_query($strQuery, $arrParams);
        while ($row = mysqli_fetch_assoc($intRes)) {
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
            $strQuery = 'SELECT ir.description, ir.pcs, ir.price, ir.discount, ir.row_date, ir.vat, ir.vat_included, ir.partial_payment ' .
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

            $intRes2 = mysqli_param_query($strQuery, $rowParams);
            $intRowSum = 0;
            $intRowVAT = 0;
            $intRowSumVAT = 0;
            $rowPayments = 0;
            $rows = false;
            while ($row2 = mysqli_fetch_assoc($intRes2)) {
                $rows = true;

                if ($row2['partial_payment']) {
                    $rowPayments -= $row2['price'];
                    continue;
                }

                list ($intSum, $intVAT, $intSumVAT) = calculateRowSum(
                    $row2['price'], $row2['pcs'], $row2['vat'],
                    $row2['vat_included'], $row2['discount']);

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

            if (!$rows) {
                continue;
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
                $this->printGroupSums($format, $printFields, $row, $groupTotSum,
                    $groupTotVAT, $groupTotSumVAT, $groupTotalToPay,
                    $grouping == 'vat' ? $GLOBALS['locVAT'] . ' ' . miscRound2Decim($currentGroup) : '');
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

            $this->printRow($format, $printFields, $row, $intRowSum, $intRowVAT,
                $intRowSumVAT, $intRowSumVAT - $rowPayments);
        }
        if ($grouping) {
            $this->printGroupSums($format, $printFields, $row, $groupTotSum,
                $groupTotVAT, $groupTotSumVAT, $groupTotalToPay,
                $grouping == 'vat' ? $GLOBALS['locVAT'] . ' '
                    . miscRound2Decim($currentGroup) : '');
        }
        ksort($totalsPerVAT, SORT_NUMERIC);
        $this->printTotals($format, $printFields, $intTotSum, $intTotVAT,
            $intTotSumVAT, $intTotalToPay, $totalsPerVAT);
        $this->printFooter($format, $printFields);
    }

    private function printHeader($format, $printFields, $startDate, $endDate)
    {
        if ($format == 'pdf' || $format == 'pdfl') {
            ob_end_clean();
            $pdf = new PDF($format == 'pdf' ? 'P' : 'L', 'mm', 'A4',
                _CHARSET_ == 'UTF-8', _CHARSET_, false);
            $pdf->setTopMargin(20);
            $pdf->headerRight = $GLOBALS['locReportPage'];
            $pdf->printHeaderOnFirstPage = true;
            $pdf->AddPage();
            $pdf->SetAutoPageBreak(TRUE, 15);

            $pdf->setY(10);
            $pdf->SetFont('Helvetica', 'B', 12);
            $pdf->Cell(100, 15, $GLOBALS['locInvoiceReport'], 0, 1, 'L');

            if ($startDate || $endDate) {
                $pdf->SetFont('Helvetica', '', 8);
                $pdf->Cell(25, 15, $GLOBALS['locDateInterval'], 0, 0, 'L');
                $pdf->Cell(50, 15,
                    dateConvDBDate2Date($startDate) . ' - ' .
                         dateConvDBDate2Date($endDate), 0, 1, 'L');
            }

            $pdf->SetFont('Helvetica', 'B', 8);

            if (in_array('invoice_no', $printFields)) {
                $pdf->Cell(18, 4, $GLOBALS['locInvoiceNumber'], 0, 0, 'L');
            }
            if (in_array('invoice_date', $printFields)) {
                $pdf->Cell(20, 4, $GLOBALS['locInvDate'], 0, 0, 'L');
            }
            if (in_array('due_date', $printFields)) {
                $pdf->Cell(20, 4, $GLOBALS['locDueDate'], 0, 0, 'L');
            }
            if (in_array('payment_date', $printFields)) {
                $pdf->Cell(20, 4, $GLOBALS['locPaymentDate'], 0, 0, 'L');
            }
            if (in_array('company_name', $printFields)) {
                $pdf->Cell(40, 4, $GLOBALS['locPayer'], 0, 0, 'L');
            }
            if (in_array('status', $printFields)) {
                $pdf->Cell(15, 4, $GLOBALS['locInvoiceState'], 0, 0, 'L');
            }
            if (in_array('ref_number', $printFields)) {
                $pdf->Cell(25, 4, $GLOBALS['locReferenceNumber'], 0, 0, 'L');
            }
            if (in_array('sums', $printFields)) {
                $pdf->Cell(20, 4, $GLOBALS['locVATLess'], 0, 0, 'R');
                $pdf->Cell(20, 4, $GLOBALS['locVATPart'], 0, 0, 'R');
                $pdf->Cell(20, 4, $GLOBALS['locWithVAT'], 0, 0, 'R');
                $pdf->Cell(20, 4, $GLOBALS['locTotalToPay'], 0, 1, 'R');
            }

            $this->pdf = $pdf;
            return;
        }
        ?>
<div class="report">
	<table>
		<tr>
      <?php if (in_array('invoice_no', $printFields)) {?>
        <th class="label">
            <?php echo $GLOBALS['locInvoiceNumber']?>
        </th>
      <?php
        }
        if (in_array('invoice_date', $printFields)) {
            ?>
        <th class="label">
            <?php echo $GLOBALS['locInvDate']?>
        </th>
      <?php
        }
        if (in_array('due_date', $printFields)) {
            ?>
        <th class="label">
            <?php echo $GLOBALS['locDueDate']?>
        </th>
      <?php
        }
        if (in_array('payment_date', $printFields)) {
            ?>
        <th class="label">
            <?php echo $GLOBALS['locPaymentDate']?>
        </th>
        <?php
        }
        if (in_array('company_name', $printFields)) {
            ?>
        <th class="label">
            <?php echo $GLOBALS['locPayer']?>
        </th>
      <?php
        }
        if (in_array('status', $printFields)) {
            ?>
        <th class="label">
            <?php echo $GLOBALS['locInvoiceState']?>
        </th>
      <?php
        }
        if (in_array('ref_number', $printFields)) {
            ?>
        <th class="label">
            <?php echo $GLOBALS['locReferenceNumber']?>
        </th>
      <?php
        }
        if (in_array('sums', $printFields)) {
            ?>
        <th class="label" style="text-align: right">
            <?php echo $GLOBALS['locVATLess']?>
        </th>
		<th class="label" style="text-align: right">
            <?php echo $GLOBALS['locVATPart']?>
        </th>
		<th class="label" style="text-align: right">
            <?php echo $GLOBALS['locWithVAT']?>
        </th>
		<th class="label" style="text-align: right">
            <?php echo $GLOBALS['locTotalToPay']?>
        </th>
        <?php } ?>
    </tr>
<?php
    }

    private function printRow($format, $printFields, $row, $intRowSum, $intRowVAT,
        $intRowSumVAT, $rowTotalToPay)
    {
        if ($format == 'pdf' || $format == 'pdfl') {
            $pdf = $this->pdf;
            $pdf->SetFont('Helvetica', '', 8);
            $pdf->setY($pdf->getY() + 1);
            if (in_array('invoice_no', $printFields)) {
                $pdf->Cell(18, 4, $row['invoice_no'], 0, 0, 'L');
            }
            if (in_array('invoice_date', $printFields)) {
                $pdf->Cell(20, 4, dateConvDBDate2Date($row['invoice_date']), 0, 0,
                    'L');
            }
            if (in_array('due_date', $printFields)) {
                $pdf->Cell(20, 4, dateConvDBDate2Date($row['due_date']), 0, 0, 'L');
            }
            if (in_array('payment_date', $printFields)) {
                $pdf->Cell(20, 4, dateConvDBDate2Date($row['payment_date']), 0, 0,
                    'L');
            }
            if (in_array('company_name', $printFields)) {
                $nameX = $pdf->getX();
                $pdf->setX($nameX + 40);
            }
            if (in_array('status', $printFields)) {
                $pdf->Cell(15, 4,
                    isset($GLOBALS['loc' . $row['state']]) ? $GLOBALS['loc' .
                         $row['state']] : $row['state'], 0, 0, 'L');
            }
            if (in_array('ref_number', $printFields)) {
                $pdf->Cell(25, 4, formatRefNumber($row['ref_number']), 0, 0, 'L');
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
      <?php if (in_array('invoice_no', $printFields)) {?>
        <td class="input">
            <?php echo htmlspecialchars($row['invoice_no'])?>
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
            <?php echo htmlspecialchars(isset($GLOBALS['loc' . $row['state']]) ? $GLOBALS['loc' . $row['state']] : $row['state'])?>
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
        <?php } ?>
      </tr>
<?php
    }

    private function printGroupSums($format, $printFields, $row, $groupTotSum,
        $groupTotVAT, $groupTotSumVAT, $groupTotalToPay, $groupTitle)
    {
        if (!in_array('sums', $printFields)) {
            return;
        }
        if ($format == 'pdf' || $format == 'pdfl') {
            $pdf = $this->pdf;
            if ($pdf->getY() > $pdf->getPageHeight() - 7 - 15)
                $pdf->AddPage();
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
                $rowWidth += 15;
            }
            if (in_array('ref_number', $printFields)) {
                $rowWidth += 25;
            }
            $sumPos = $rowWidth;
            $rowWidth += 80;
            if ($groupTitle) {
                $sumPos -= 25;
            }

            $pdf->line($pdf->getX() + $sumPos, $pdf->getY(),
                $pdf->getX() + $rowWidth, $pdf->getY());
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

    private function printTotals($format, $printFields, $intTotSum, $intTotVAT,
        $intTotSumVAT, $totalToPay, $totalsPerVAT)
    {
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
                $rowWidth += 15;
            }
            if (in_array('ref_number', $printFields)) {
                $rowWidth += 25;
            }
            $sumPos = $rowWidth;
            $rowWidth += 80;

            $pdf = $this->pdf;
            $pdf->SetFont('Helvetica', 'B', 8);
            $pdf->line($pdf->getX() + $sumPos, $pdf->getY(),
                $pdf->getX() + $rowWidth, $pdf->getY());
            $pdf->setY($pdf->getY() + 1);
            $pdf->Cell($sumPos, 4, $GLOBALS['locTotal'], 0, 0, 'R');
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
                $pdf->Cell(15, 4, $GLOBALS['locVATBreakdown'], 0, 0, 'R');
                $pdf->Cell(25, 4, $GLOBALS['locVATLess'], 0, 0, 'R');
                $pdf->Cell(25, 4, $GLOBALS['locVATPart'], 0, 0, 'R');
                $pdf->Cell(25, 4, $GLOBALS['locWithVAT'], 0, 1, 'R');
                $pdf->SetFont('Helvetica', '', 8);
                foreach ($totalsPerVAT as $vat => $sums) {
                    $pdf->Cell(15, 4, miscRound2OptDecim($vat) . '%', 0, 0, 'R');
                    $pdf->Cell(25, 4, miscRound2Decim($sums['sum']), 0, 0, 'R');
                    $pdf->Cell(25, 4, miscRound2Decim($sums['VAT']), 0, 0, 'R');
                    $pdf->Cell(25, 4, miscRound2Decim($sums['sumVAT']), 0, 1, 'R');
                }
            }

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
        ?>
    <tr>
    <?php if ($colSpan > 0) { ?>
        <td class="input total_sum" colspan="<?php echo $colSpan?>"
				style="text-align: right">
            <?php echo $GLOBALS['locTotal']?>
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
            <th class="label" style="text-align: right"><?php echo $GLOBALS['locVATBreakdown']?></th>
            <th class="label" style="text-align: right"><?php echo $GLOBALS['locVATLess']?></th>
            <th class="label" style="text-align: right"><?php echo $GLOBALS['locVATPart']?></th>
            <th class="label" style="text-align: right"><?php echo $GLOBALS['locWithVAT']?></th>
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

    private function printFooter($format, $printFields)
    {
        if ($format == 'pdf' || $format == 'pdfl') {
            $pdf = $this->pdf;
            $pdf->Output('report.pdf', 'I');
            return;
        }
        ?>
    </table>
</div>
<?php
    }
}
