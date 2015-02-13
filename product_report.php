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

class ProductReport
{
  private $pdf = null;

  public function createReport()
  {
    $strReport = getRequest('report', '');

    if ($strReport)
    {
      $this->printReport();
      return;
    }

    $intBaseId = getRequest('base', FALSE);
    $intCompanyId = getRequest('company', FALSE);
    $intProductId = getRequest('product', FALSE);
    $dateRange = getRequest('date', '');
?>

  <script type="text/javascript">
  $(document).ready(function() {
    $('input[class~="hasDateRangePicker"]').daterangepicker(<?php echo $GLOBALS['locDateRangePickerOptions']?>);
  });
  </script>

  <div class="form_container ui-widget-content ui-helper-clearfix">
    <form method="get" id="params" name="params">
    <input name="func" type="hidden" value="reports">
    <input name="form" type="hidden" value="product">
    <input name="report" type="hidden" value="1">

    <div class="unlimited_label"><h1><?php echo $GLOBALS['locProductReport']?></h1></div>

    <div class="medium_label"><?php echo $GLOBALS['locInvoiceDateInterval']?></div>
    <div class="field"><?php echo htmlFormElement('date', 'TEXT', "$dateRange" , 'medium hasDateRangePicker', '', 'MODIFY', FALSE)?></div>

    <div class="medium_label"><?php echo $GLOBALS['locBiller']?></div>
    <div class="field"><?php echo htmlFormElement('base', 'LIST', $intBaseId, 'medium', 'SELECT id, name FROM {prefix}base WHERE deleted=0 ORDER BY name', 'MODIFY', FALSE)?></div>

    <div class="medium_label"><?php echo $GLOBALS['locClient']?></div>
    <div class="field"><?php echo htmlFormElement('company', 'LIST', $intCompanyId, 'medium', 'SELECT id, company_name FROM {prefix}company WHERE deleted=0 ORDER BY company_name', 'MODIFY', FALSE)?></div>

    <div class="medium_label"><?php echo $GLOBALS['locProduct']?></div>
    <div class="field"><?php echo htmlFormElement('product', 'LIST', $intProductId, 'medium', 'SELECT id, product_name FROM {prefix}product WHERE deleted=0 ORDER BY product_name', 'MODIFY', FALSE)?></div>

    <div class="medium_label"><?php echo $GLOBALS['locPrintFormat']?></div>
    <div class="field"><input type="radio" name="format" value="html" checked="checked"><?php echo $GLOBALS['locPrintFormatHTML']?></input></div>
    <div class="medium_label"></div>
    <div class="field"><input type="radio" name="format" value="pdf"><?php echo $GLOBALS['locPrintFormatPDF']?></input></div>
    <div class="field_sep"></div>

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
        echo "    <div class=\"medium_label\"></div>\n";
      }
      $first = false;
?>
    <div class="field"><input type="checkbox" name="stateid_<?php echo $intStateId?>" value="1"<?php echo $strChecked?>> <?php echo htmlspecialchars($strStateName)?></div>
<?php
    }
?>
    <div class="medium_label">
      <a class="actionlink" href="#" onclick="document.getElementById('params').submit(); return false;"><?php echo $GLOBALS['locCreateReport']?></a>
    </div>
    </form>
  </div>
<?php
  }

  private function printReport()
  {
    $intStateID = getRequest('stateid', FALSE);
    $intBaseId = getRequest('base', FALSE);
    $intCompanyId = getRequest('company', FALSE);
    $intProductId = getRequest('product', FALSE);
    $format = getRequest('format', 'html');

    $dateRange = explode(' - ', getRequest('date', ''));
    $startDate = $dateRange[0];
    $endDate = isset($dateRange[1]) ? $dateRange[1] : $startDate;

    if ($startDate) {
      $startDate = dateConvDate2DBDate($startDate);
    }
    if ($endDate) {
      $endDate = dateConvDate2DBDate($endDate);
    }

    $arrParams = array();

    $strQuery =
        "SELECT i.id ".
        "FROM {prefix}invoice i ".
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
      $tmpSelected = getRequest($strTemp, FALSE) ? TRUE : FALSE;
      if ($tmpSelected)
      {
        $strQuery2 .= ' i.state_id = ? OR ';
        $arrParams[] = $intStateId;
      }
    }
    if ($strQuery2)
    {
      $strQuery2 = ' AND (' . substr($strQuery2, 0, -3) . ')';
    }

    $strQuery .= "$strQuery2 ORDER BY invoice_no";

    if ($intProductId)
    {
      $strProductWhere = 'AND ir.product_id = ? ';
      $arrParams[] = $intProductId;
    }
    else
      $strProductWhere = '';

    $strProductQuery = 'SELECT p.id, p.product_code, p.product_name, ir.description, ' .
      'CASE WHEN ir.vat_included = 0 THEN sum(ROUND(ir.price * ir.pcs * (1 - IFNULL(ir.discount, 0) / 100), 2)) ELSE sum(ROUND(ir.price * ir.pcs * (1 - IFNULL(ir.discount, 0) / 100) / (1 + ir.vat / 100), 2)) END as total_price, ' .
      'ir.vat, sum(ir.pcs) as pcs, t.name as unit ' .
      'FROM {prefix}invoice_row ir ' .
      'LEFT OUTER JOIN {prefix}product p ON p.id = ir.product_id ' .
      'LEFT OUTER JOIN {prefix}row_type t ON t.id = ir.type_id ' .
      "WHERE ir.deleted=0 AND ir.invoice_id IN ($strQuery) $strProductWhere" .
      'GROUP BY p.id, ir.description, ir.vat, t.name ' .
      'ORDER BY p.product_name, ir.description';

    $this->printHeader($format, $startDate, $endDate);

    $intTotSum = 0;
    $intTotVAT = 0;
    $intTotSumVAT = 0;
    $intRes = mysqli_param_query($strProductQuery, $arrParams);
    while ($row = mysqli_fetch_assoc($intRes))
    {
      $strCode = $row['product_code'];
      $strProduct = $row['product_name'];
      $strDescription = $row['description'];
      $intCount = $row['pcs'];
      $strUnit = $row['unit'];
      if ($strUnit) {
        $strUnit = $GLOBALS["loc$strUnit"];
      }
      $intSum = $row['total_price'];
      $intVATPercent = $row['vat'];

      $intVAT = round($intSum * $intVATPercent / 100, 2);
      $intSumVAT = $intSum + $intVAT;

      $intTotSum += $intSum;
      $intTotVAT += $intVAT;
      $intTotSumVAT += $intSumVAT;

      $this->printRow($format, $strCode, $strProduct, $strDescription, $intCount, $strUnit, $intSum, $intVATPercent, $intVAT, $intSumVAT);
    }
    $this->printTotals($format, $intTotSum, $intTotVAT, $intTotSumVAT);
    $this->printFooter($format);
  }

  private function printHeader($format, $startDate, $endDate)
  {
    if ($format == 'pdf')
    {
      ob_end_clean();
      $pdf = new PDF('P','mm','A4', _CHARSET_ == 'UTF-8', _CHARSET_, false);
      $pdf->setTopMargin(20);
      $pdf->headerRight = $GLOBALS['locReportPage'];
      $pdf->printHeaderOnFirstPage = true;
      $pdf->AddPage();
      $pdf->SetAutoPageBreak(TRUE, 15);

      $pdf->setY(10);
      $pdf->SetFont('Helvetica','B',12);
      $pdf->Cell(100, 15, $GLOBALS['locProductReport'], 0, 1, 'L');

      if ($startDate || $endDate)
      {
        $pdf->SetFont('Helvetica','',8);
        $pdf->Cell(25, 15, $GLOBALS['locDateInterval'], 0, 0, 'L');
        $pdf->Cell(50, 15, dateConvDBDate2Date($startDate) . ' - ' . dateConvDBDate2Date($endDate), 0, 1, 'L');
      }

      $pdf->SetFont('Helvetica','B',8);
      $pdf->Cell(10, 4, $GLOBALS['locCode'], 0, 0, 'L');
      $pdf->Cell(40, 4, $GLOBALS['locProduct'], 0, 0, 'L');
      $pdf->Cell(25, 4, $GLOBALS['locPCS'], 0, 0, 'R');
      $pdf->Cell(25, 4, $GLOBALS['locUnit'], 0, 0, 'R');
      $pdf->Cell(25, 4, $GLOBALS['locVATLess'], 0, 0, 'R');
      $pdf->Cell(15, 4, $GLOBALS['locVATPercent'], 0, 0, 'R');
      $pdf->Cell(25, 4, $GLOBALS['locVATPart'], 0, 0, 'R');
      $pdf->Cell(25, 4, $GLOBALS['locWithVAT'], 0, 1, 'R');
      $this->pdf = $pdf;
      return;
    }
?>
    <div class="report">
    <table>
    <tr>
        <th class="label">
            <?php echo $GLOBALS['locCode']?>
        </th>
        <th class="label">
            <?php echo $GLOBALS['locProduct']?>
        </th>
        <th class="label" style="text-align: right">
            <?php echo $GLOBALS['locPCS']?>
        </th>
        <th class="label" style="text-align: right">
            <?php echo $GLOBALS['locUnit']?>
        </th>
        <th class="label" style="text-align: right">
            <?php echo $GLOBALS['locVATLess']?>
        </th>
        <th class="label" style="text-align: right">
            <?php echo $GLOBALS['locVATPercent']?>
        </th>
        <th class="label" style="text-align: right">
            <?php echo $GLOBALS['locVATPart']?>
        </th>
        <th class="label" style="text-align: right">
            <?php echo $GLOBALS['locWithVAT']?>
        </th>
    </tr>
<?php
  }

  private function printRow($format, $strCode, $strProduct, $strDescription, $intCount, $strUnit, $intSum, $intVATPercent, $intVAT, $intSumVAT)
  {
    if ($strDescription)
    {
      if ($format == 'html' && mb_strlen($strDescription) > 20)
        $strDescription = mb_substr($strDescription, 0, 17) . '...';
      if ($strProduct)
        $strProduct .= " ($strDescription)";
      else
        $strProduct = $strDescription;
    }

    if ($format == 'pdf')
    {
      if (!$strProduct)
        $strProduct = '-';

      $pdf = $this->pdf;
      $pdf->SetFont('Helvetica','',8);
      $pdf->setY($pdf->getY() + 1);
      $pdf->Cell(10, 3, $strCode, 0, 0, 'L');
      $nameX = $pdf->getX();
      $pdf->setX($nameX + 40);
      $pdf->Cell(25, 3, miscRound2Decim($intCount), 0, 0, 'R');
      $pdf->Cell(25, 3, $strUnit, 0, 0, 'R');
      $pdf->Cell(25, 3, miscRound2Decim($intSum), 0, 0, 'R');
      $pdf->Cell(15, 3, miscRound2Decim($intVATPercent, 1), 0, 0, 'R');
      $pdf->Cell(25, 3, miscRound2Decim($intVAT), 0, 0, 'R');
      $pdf->Cell(25, 3, miscRound2Decim($intSumVAT), 0, 0, 'R');
      $pdf->setX($nameX);
      $pdf->MultiCell(40, 3, $strProduct, 0, 'L');
      return;
    }
    if (!$strProduct)
      $strProduct = '&ndash;';
    else
      $strProduct = htmlspecialchars($strProduct);
?>
    <tr>
        <td class="input">
            <?php echo $strCode?>
        </td>
        <td class="input">
            <?php echo $strProduct?>
        </td>
        <td class="input" style="text-align: right">
            <?php echo miscRound2Decim($intCount)?>
        </td>
        <td class="input" style="text-align: left">
            <?php echo htmlspecialchars($strUnit)?>
        </td>
        <td class="input" style="text-align: right">
            <?php echo miscRound2Decim($intSum)?>
        </td>
        <td class="input" style="text-align: right">
            <?php echo miscRound2Decim($intVATPercent, 1)?>
        </td>
        <td class="input" style="text-align: right">
            <?php echo miscRound2Decim($intVAT)?>
        </td>
        <td class="input" style="text-align: right">
            <?php echo miscRound2Decim($intSumVAT)?>
        </td>
    </tr>
<?php
  }

  private function printTotals($format, $intTotSum, $intTotVAT, $intTotSumVAT)
  {
    if ($format == 'pdf')
    {
      $pdf = $this->pdf;
      $pdf->SetFont('Helvetica','B',8);
      $pdf->setY($pdf->getY() + 3);
      $pdf->Cell(50, 3, $GLOBALS['locTotal'], 0, 0, 'L');
      $pdf->Cell(25, 3, '', 0, 0, 'L');
      $pdf->Cell(25, 3, '', 0, 0, 'L');
      $pdf->Cell(25, 3, miscRound2Decim($intTotSum), 0, 0, 'R');
      $pdf->Cell(15, 3, '', 0, 0, 'L');
      $pdf->Cell(25, 3, miscRound2Decim($intTotVAT), 0, 0, 'R');
      $pdf->Cell(25, 3, miscRound2Decim($intTotSumVAT), 0, 1, 'R');
      return;
    }
?>
    <tr>
        <td class="input total_sum">
            <?php echo $GLOBALS['locTotal']?>
        </td>
        <td class="input total_sum" style="text-align: right">
            &nbsp;
        </td>
        <td class="input total_sum" style="text-align: right">
            &nbsp;
        </td>
        <td class="input total_sum" style="text-align: right">
            &nbsp;
        </td>
        <td class="input total_sum" style="text-align: right">
            <?php echo miscRound2Decim($intTotSum)?>
        </td>
        <td class="input total_sum" style="text-align: right">
            &nbsp;
        </td>
        <td class="input total_sum" style="text-align: right">
            <?php echo miscRound2Decim($intTotVAT)?>
        </td>
        <td class="input total_sum" style="text-align: right">
            <?php echo miscRound2Decim($intTotSumVAT)?>
        </td>
    </tr>
<?php
  }

  private function printFooter($format)
  {
    if ($format == 'pdf')
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
