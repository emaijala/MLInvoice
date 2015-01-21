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

require_once "htmlfuncs.php";
require_once "sqlfuncs.php";
require_once "miscfuncs.php";
require_once "datefuncs.php";
require_once "localize.php";
require_once "pdf.php";

class InvoiceReport
{
  protected $fields = array(
    'invoice_no' => array('label' => 'locInvoiceNumber', 'checked' => true),
    'invoice_date' => array('label' => 'locInvDate', 'checked' => true),
    'due_date' => array('label' => 'locDueDate', 'checked' => true),
    'payment_date' => array('label' => 'locPaymentDate', 'checked' => false),
    'company_name' => array('label' => 'locPayer', 'checked' => true),
    'status' => array('label' => 'locInvoiceState', 'checked' => true),
    'ref_number' => array('label' => 'locReferenceNumber', 'checked' => false),
    'sums' => array('label' => 'locSum', 'checked' => true)
  );

  protected $pdf = null;

  public function createReport()
  {
    $strReport = getRequest('report', '');

    if ($strReport)
    {
      $this->printReport();
      return;
    }

    $intBaseId = getRequest('base', false);
    $intCompanyId = getRequest('company', false);
    $invoiceDateRange = getRequest('date', '');
    $invoiceRowDateRange = getRequest('row_date', '');
    $paymentDateRange = getRequest('payment_date', '');
    $fields = getRequest('fields[]', array());
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
    <input name="func" type="hidden" value="reports">
    <input name="form" type="hidden" value="invoice">
    <input name="report" type="hidden" value="1">

    <div class="unlimited_label"><strong><?php echo $GLOBALS['locInvoiceReport']?></strong></div>

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
    <div class="field"><input type="radio" name="format" value="html"<?php if ($format == 'html') echo ' checked="checked"'?>><?php echo $GLOBALS['locPrintFormatHTML']?></div>
    <div class="medium_label"></div>
    <div class="field"><input type="radio" name="format" value="pdf"<?php if ($format == 'pdf') echo ' checked="checked"'?>><?php echo $GLOBALS['locPrintFormatPDF']?></div>
    <div class="medium_label"></div>
    <div class="field"><input type="radio" name="format" value="pdfl"<?php if ($format == 'pdfl') echo ' checked="checked"'?>><?php echo $GLOBALS['locPrintFormatPDFLandscape']?></div>
    <div class="field_sep"></div>

    <div class="medium_label"><?php echo $GLOBALS['locInvoiceRowTypes']?></div>
    <div class="field"><input type="radio" name="row_types" value="all"<?php if ($rowTypes == 'all') echo ' checked="checked"'?>><?php echo $GLOBALS['locPrintInvoiceRowTypeAll']?></div>
    <div class="medium_label"></div>
    <div class="field"><input type="radio" name="row_types" value="normal"<?php if ($rowTypes == 'normal') echo ' checked="checked"'?>><?php echo $GLOBALS['locPrintInvoiceRowTypeNormal']?></div>
    <div class="medium_label"></div>
    <div class="field"><input type="radio" name="row_types" value="reminder"<?php if ($rowTypes == 'reminder') echo ' checked="checked"'?>><?php echo $GLOBALS['locPrintInvoiceRowTypeReminder']?></div>
    <div class="field_sep"></div>

    <div class="medium_label"><?php echo $GLOBALS['locPrintGrouping']?></div>
    <div class="field"><input type="radio" name="grouping" value=""<?php if ($grouping == '') echo ' checked="checked"'?>><?php echo $GLOBALS['locPrintGroupingNone']?></div>
    <div class="medium_label"></div>
    <div class="field"><input type="radio" name="grouping" value="state"<?php if ($grouping == 'state') echo ' checked="checked"'?>><?php echo $GLOBALS['locPrintGroupingState']?></div>
    <div class="medium_label"></div>
    <div class="field"><input type="radio" name="grouping" value="month"<?php if ($grouping == 'month') echo ' checked="checked"'?>><?php echo $GLOBALS['locPrintGroupingMonth']?></div>
    <div class="medium_label"></div>
    <div class="field"><input type="radio" name="grouping" value="client"<?php if ($grouping == 'client') echo ' checked="checked"'?>><?php echo $GLOBALS['locPrintGroupingClient']?></div>
    <div class="field_sep">&nbsp;</div>

    </div>
    <div style="float: left; margin-right: 20px;">
      <div class="medium_label"><?php echo $GLOBALS['locPrintReportStates']?></div>
<?php
    $strQuery =
        "SELECT id, name ".
        "FROM {prefix}invoice_state WHERE deleted=0 ".
        "ORDER BY order_no";
    $intRes = mysqli_query_check($strQuery);
    $first = true;
    while ($row = mysqli_fetch_assoc($intRes))
    {
      $intStateId = $row['id'];
      $strStateName = isset($GLOBALS['loc' . $row['name']]) ? $GLOBALS['loc' . $row['name']] : $row['name'];
      $tmpSelected = getRequest("stateid_$intStateId", TRUE) ? TRUE : false;
      $strChecked = $tmpSelected ? ' checked' : '';
      if (!$first) {
        echo "      <div class=\"medium_label\"></div>\n";
      }
      $first = false;
?>
      <div class="field"><input type="checkbox" name="stateid_<?php echo $intStateId?>" value="1"<?php echo $strChecked?>> <?php echo htmlspecialchars($strStateName)?></div>
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
        <div class="field"><input type="checkbox" name="fields[]" value="<?php echo $field?>" <?php echo $checked?>> <?php echo $label?></div>
<?php
      $first = false;
    }
?>
    </div>
    <div class="medium_label">
      <a class="actionlink" href="#" onclick="document.getElementById('params').submit(); return false;"><?php echo $GLOBALS['locCreateReport']?></a>
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
    $printFields = getRequest('fields', array());
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

    $arrParams = array();

    $strQuery =
        "SELECT i.id, i.invoice_no, i.invoice_date, i.due_date, i.payment_date, i.ref_number, i.ref_number, c.company_name AS name, c.billing_address, ist.name as state ".
        "FROM {prefix}invoice i ".
        "LEFT OUTER JOIN {prefix}company c ON c.id = i.company_id ".
        "LEFT OUTER JOIN {prefix}invoice_state ist ON i.state_id = ist.id ".
        "WHERE i.deleted=0";

    if ($startDate)
    {
      $strQuery .= ' AND i.invoice_date >= ?';
      $arrParams[] = $startDate;
    }
    if ($endDate)
    {
      $strQuery .= ' AND i.invoice_date <= ?';
      $arrParams[] = $endDate;
    }
    if ($paymentStartDate)
    {
      $strQuery .= ' AND i.payment_date >= ?';
      $arrParams[] = $paymentStartDate;
    }
    if ($paymentEndDate)
    {
      $strQuery .= ' AND i.payment_date <= ?';
      $arrParams[] = $paymentEndDate;
    }
    if ($intBaseId)
    {
      $strQuery .= ' AND i.base_id = ?';
      $arrParams[] = $intBaseId;
    }

    if ($intCompanyId)
    {
      $strQuery .= ' AND i.company_id = ?';
      $arrParams[] = $intCompanyId;
    }

    $strQuery2 = '';
    $strQuery3 =
        "SELECT id, name ".
        "FROM {prefix}invoice_state WHERE deleted=0 ".
        "ORDER BY order_no";
    $intRes = mysqli_query_check($strQuery3);
    while ($row = mysqli_fetch_assoc($intRes))
    {
      $intStateId = $row['id'];
      $strStateName = $row['name'];
      $strTemp = "stateid_$intStateId";
      $tmpSelected = getRequest($strTemp, false);
      if ($tmpSelected)
      {
        $strQuery2 .= 'i.state_id = ? OR ';
        $arrParams[] = $intStateId;
      }
    }
    if ($strQuery2)
    {
      $strQuery2 = ' AND (' . substr($strQuery2, 0, -4) . ')';
    }

    $strQuery .= "$strQuery2 ORDER BY ";
    switch ($grouping) {
    case 'state':
      $strQuery .= "state_id, invoice_date, invoice_no";
      break;
    case 'client':
      $strQuery .= "name, invoice_date, invoice_no";
      break;
    default:
      $strQuery .= "invoice_date, invoice_no";
    }

    $this->printHeader($format, $printFields, $startDate, $endDate);

    $intTotSum = 0;
    $intTotVAT = 0;
    $intTotSumVAT = 0;
    $currentGroup = false;
    $groupTotSum = 0;
    $groupTotVAT = 0;
    $groupTotSumVAT = 0;
    $intRes = mysqli_param_query($strQuery, $arrParams);
    while ($row = mysqli_fetch_assoc($intRes))
    {
      switch ($grouping) {
        case 'state':
          $invoiceGroup = $row['state'];
          break;
        case 'month':
          $invoiceGroup = substr($row['invoice_date'], 4, 2);
          break;
        case 'client':
          $invoiceGroup = $row['name'];
          break;
        default:
          $invoiceGroup = false;
      }

      $rowParams = array($row['id']);
      $strQuery =
          "SELECT ir.description, ir.pcs, ir.price, ir.discount, ir.row_date, ir.vat, ir.vat_included ".
          "FROM {prefix}invoice_row ir ".
          "WHERE ir.invoice_id=? AND ir.deleted=0";

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

      $intRes2 = mysqli_param_query($strQuery, $rowParams);
      $intRowSum = 0;
      $intRowVAT = 0;
      $intRowSumVAT = 0;
      $rows = false;
      while ($row2 = mysqli_fetch_assoc($intRes2))
      {
        $rows = true;
        list($intSum, $intVAT, $intSumVAT) = calculateRowSum($row2['price'], $row2['pcs'], $row2['vat'], $row2['vat_included'], $row2['discount']);

        $intRowSum += $intSum;
        $intRowVAT += $intVAT;
        $intRowSumVAT += $intSumVAT;

        $intTotSum += $intSum;
        $intTotVAT += $intVAT;
        $intTotSumVAT += $intSumVAT;
      }

      if (!$rows) {
        continue;
      }

      if ($grouping && $currentGroup !== false && $currentGroup != $invoiceGroup)
      {
        $this->printGroupSums($format, $printFields, $row, $groupTotSum, $groupTotVAT, $groupTotSumVAT);
        $groupTotSum = 0;
        $groupTotVAT = 0;
        $groupTotSumVAT = 0;
      }
      $currentGroup = $invoiceGroup;

      $groupTotSum += $intRowSum;
      $groupTotVAT += $intRowVAT;
      $groupTotSumVAT += $intRowSumVAT;

      $this->printRow($format, $printFields, $row, $intRowSum, $intRowVAT, $intRowSumVAT);
    }
    if ($grouping) {
      $this->printGroupSums($format, $printFields, $row, $groupTotSum, $groupTotVAT, $groupTotSumVAT);
    }
    $this->printTotals($format, $printFields, $intTotSum, $intTotVAT, $intTotSumVAT);
    $this->printFooter($format, $printFields);
  }

  private function printHeader($format, $printFields, $startDate, $endDate)
  {
    if ($format == 'pdf' || $format == 'pdfl')
    {
      ob_end_clean();
      $pdf = new PDF($format == 'pdf' ? 'P' : 'L', 'mm', 'A4', _CHARSET_ == 'UTF-8', _CHARSET_, false);
      $pdf->setTopMargin(20);
      $pdf->headerRight = $GLOBALS['locReportPage'];
      $pdf->printHeaderOnFirstPage = true;
      $pdf->AddPage();
      $pdf->SetAutoPageBreak(TRUE, 15);

      $pdf->setY(10);
      $pdf->SetFont('Helvetica','B',12);
      $pdf->Cell(100, 15, $GLOBALS['locInvoiceReport'], 0, 1, 'L');

      if ($startDate || $endDate)
      {
        $pdf->SetFont('Helvetica','',8);
        $pdf->Cell(25, 15, $GLOBALS['locDateInterval'], 0, 0, 'L');
        $pdf->Cell(50, 15, dateConvDBDate2Date($startDate) . ' - ' . dateConvDBDate2Date($endDate), 0, 1, 'L');
      }

      $pdf->SetFont('Helvetica','B',8);

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
        $pdf->Cell(45, 4, $GLOBALS['locPayer'], 0, 0, 'L');
      }
      if (in_array('status', $printFields)) {
        $pdf->Cell(20, 4, $GLOBALS['locInvoiceState'], 0, 0, 'L');
      }
      if (in_array('ref_number', $printFields)) {
        $pdf->Cell(25, 4, $GLOBALS['locReferenceNumber'], 0, 0, 'L');
      }
      if (in_array('sums', $printFields)) {
        $pdf->Cell(25, 4, $GLOBALS['locVATLess'], 0, 0, 'R');
        $pdf->Cell(25, 4, $GLOBALS['locVATPart'], 0, 0, 'R');
        $pdf->Cell(25, 4, $GLOBALS['locWithVAT'], 0, 1, 'R');
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
      <?php }
      if (in_array('invoice_date', $printFields)) {?>
        <th class="label">
            <?php echo $GLOBALS['locInvDate']?>
        </th>
      <?php }
      if (in_array('due_date', $printFields)) {?>
        <th class="label">
            <?php echo $GLOBALS['locDueDate']?>
        </th>
      <?php }
      if (in_array('payment_date', $printFields)) {?>
        <th class="label">
            <?php echo $GLOBALS['locPaymentDate']?>
        </th>
        <?php }
      if (in_array('company_name', $printFields)) {?>
        <th class="label">
            <?php echo $GLOBALS['locPayer']?>
        </th>
      <?php }
      if (in_array('status', $printFields)) {?>
        <th class="label">
            <?php echo $GLOBALS['locInvoiceState']?>
        </th>
      <?php }
      if (in_array('ref_number', $printFields)) {?>
        <th class="label">
            <?php echo $GLOBALS['locReferenceNumber']?>
        </th>
      <?php }
      if (in_array('sums', $printFields)) {?>
        <th class="label" style="text-align: right">
            <?php echo $GLOBALS['locVATLess']?>
        </th>
        <th class="label" style="text-align: right">
            <?php echo $GLOBALS['locVATPart']?>
        </th>
        <th class="label" style="text-align: right">
            <?php echo $GLOBALS['locWithVAT']?>
        </th>
      <?php } ?>
    </tr>
<?php
  }

  private function printRow($format, $printFields, $row, $intRowSum, $intRowVAT, $intRowSumVAT)
  {
    if ($format == 'pdf' || $format == 'pdfl')
    {
      $pdf = $this->pdf;
      $pdf->SetFont('Helvetica','',8);
      $pdf->setY($pdf->getY() + 1);
      if (in_array('invoice_no', $printFields)) {
        $pdf->Cell(18, 4, $row['invoice_no'], 0, 0, 'L');
      }
      if (in_array('invoice_date', $printFields)) {
        $pdf->Cell(20, 4, dateConvDBDate2Date($row['invoice_date']), 0, 0, 'L');
      }
      if (in_array('due_date', $printFields)) {
        $pdf->Cell(20, 4, dateConvDBDate2Date($row['due_date']), 0, 0, 'L');
      }
      if (in_array('payment_date', $printFields)) {
        $pdf->Cell(20, 4, dateConvDBDate2Date($row['payment_date']), 0, 0, 'L');
      }
      if (in_array('company_name', $printFields)) {
        $nameX = $pdf->getX();
        $pdf->setX($nameX + 45);
      }
      if (in_array('status', $printFields)) {
        $pdf->Cell(20, 4, isset($GLOBALS['loc' . $row['state']]) ? $GLOBALS['loc' . $row['state']] : $row['state'], 0, 0, 'L');
      }
      if (in_array('ref_number', $printFields)) {
        $pdf->Cell(25, 4, formatRefNumber($row['ref_number']), 0, 0, 'L');
      }
      if (in_array('sums', $printFields)) {
        $pdf->Cell(25, 4, miscRound2Decim($intRowSum), 0, 0, 'R');
        $pdf->Cell(25, 4, miscRound2Decim($intRowVAT), 0, 0, 'R');
        $pdf->Cell(25, 4, miscRound2Decim($intRowSumVAT), 0, 0, 'R');
      }
      // Print company name last, as it can span multiple lines
      if (in_array('company_name', $printFields)) {
        $pdf->setX($nameX);
        $pdf->MultiCell(45, 4, $row['name'], 0, 'L');
      }
      return;
    }
?>
    <tr>
      <?php if (in_array('invoice_no', $printFields)) {?>
        <td class="input">
            <?php echo htmlspecialchars($row['invoice_no'])?>
        </td>
      <?php }
      if (in_array('invoice_date', $printFields)) {?>
        <td class="input">
            <?php echo htmlspecialchars(dateConvDBDate2Date($row['invoice_date']))?>
        </td>
      <?php }
      if (in_array('due_date', $printFields)) {?>
        <td class="input">
            <?php echo htmlspecialchars(dateConvDBDate2Date($row['due_date']))?>
        </td>
      <?php }
      if (in_array('payment_date', $printFields)) {?>
        <td class="input">
            <?php echo htmlspecialchars(dateConvDBDate2Date($row['payment_date']))?>
        </td>
      <?php }
      if (in_array('company_name', $printFields)) {?>
        <td class="input">
            <?php echo htmlspecialchars($row['name'])?>
        </td>
      <?php }
      if (in_array('status', $printFields)) {?>
        <td class="input">
            <?php echo htmlspecialchars(isset($GLOBALS['loc' . $row['state']]) ? $GLOBALS['loc' . $row['state']] : $row['state'])?>
        </td>
      <?php }
      if (in_array('ref_number', $printFields)) {?>
        <td class="input">
            <?php echo htmlspecialchars(formatRefNumber($row['ref_number']))?>
        </td>
      <?php }
      if (in_array('sums', $printFields)) {?>
        <td class="input" style="text-align: right">
            <?php echo miscRound2Decim($intRowSum)?>
        </td>
        <td class="input" style="text-align: right">
            <?php echo miscRound2Decim($intRowVAT)?>
        </td>
        <td class="input" style="text-align: right">
            <?php echo miscRound2Decim($intRowSumVAT)?>
        </td>
      <?php } ?>
      </tr>
<?php
  }

  private function printGroupSums($format, $printFields, $row, $groupTotSum, $groupTotVAT, $groupTotSumVAT)
  {
    if (!in_array('sums', $printFields)) {
      return;
    }
    if ($format == 'pdf' || $format == 'pdfl')
    {
      $pdf = $this->pdf;
      if ($pdf->getY() > $pdf->getPageHeight() - 7 - 15)
        $pdf->AddPage();
      $pdf->SetFont('Helvetica','',8);
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
        $rowWidth += 45;
      }
      if (in_array('status', $printFields)) {
        $rowWidth += 20;
      }
      if (in_array('ref_number', $printFields)) {
        $rowWidth += 25;
      }
      $sumPos = $rowWidth;
      $rowWidth += 75;

      $pdf->line($pdf->getX() + $sumPos, $pdf->getY(), $pdf->getX() + $rowWidth, $pdf->getY());
      $pdf->setXY($pdf->getX() + $sumPos, $pdf->getY() + 1);
      $pdf->Cell(25, 4, miscRound2Decim($groupTotSum), 0, 0, 'R');
      $pdf->Cell(25, 4, miscRound2Decim($groupTotVAT), 0, 0, 'R');
      $pdf->Cell(25, 4, miscRound2Decim($groupTotSumVAT), 0, 1, 'R');
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
?>
    <tr>
    <?php if ($colSpan > 0) { ?>
        <td class="input" colspan="<?php echo $colSpan?>">
            &nbsp;
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
    </tr>
<?php
  }

  private function printTotals($format, $printFields, $intTotSum, $intTotVAT, $intTotSumVAT)
  {
    if (!in_array('sums', $printFields)) {
      return;
    }
    if ($format == 'pdf' || $format == 'pdfl')
    {
      $pdf = $this->pdf;
      if ($pdf->getY() > $pdf->getPageHeight() - 7 - 15)
        $pdf->AddPage();
      $pdf->SetFont('Helvetica','',8);
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
        $rowWidth += 45;
      }
      if (in_array('status', $printFields)) {
        $rowWidth += 20;
      }
      if (in_array('ref_number', $printFields)) {
        $rowWidth += 25;
      }
      $sumPos = $rowWidth;
      $rowWidth += 75;

      $pdf = $this->pdf;
      $pdf->SetFont('Helvetica','B',8);
      $pdf->line($pdf->getX() + $sumPos, $pdf->getY(), $pdf->getX() + $rowWidth, $pdf->getY());
      $pdf->setY($pdf->getY() + 1);
      $pdf->Cell($sumPos, 4, $GLOBALS['locTotal'], 0, 0, 'R');
      $pdf->Cell(25, 4, miscRound2Decim($intTotSum), 0, 0, 'R');
      $pdf->Cell(25, 4, miscRound2Decim($intTotVAT), 0, 0, 'R');
      $pdf->Cell(25, 4, miscRound2Decim($intTotSumVAT), 0, 1, 'R');
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
        <td class="input total_sum" colspan="<?php echo $colSpan?>" style="text-align: right">
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
    </tr>
<?php
  }

  private function printFooter($format, $printFields)
  {
    if ($format == 'pdf' || $format == 'pdfl')
    {
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
