<?php
/*******************************************************************************
MLInvoice: web-based invoicing application.
Copyright (C) 2010-2012 Ere Maijala

Portions based on:
PkLasku : web-based invoicing software.
Copyright (C) 2004-2008 Samu Reinikainen

This program is free software. See attached LICENSE.

*******************************************************************************/

/*******************************************************************************
MLInvoice: web-pohjainen laskutusohjelma.
Copyright (C) 2010-2012 Ere Maijala

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

  private $pdf = null;

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
    <input name="form" type="hidden" value="invoice">
    <input name="report" type="hidden" value="1">
    
    <div class="unlimited_label"><strong><?php echo $GLOBALS['locInvoiceReport']?></strong></div>
    
    <div class="medium_label"><?php echo $GLOBALS['locDateInterval']?></div>
    <div class="field"><?php echo htmlFormElement('date', 'TEXT', $dateRange, 'medium hasDateRangePicker', '', 'MODIFY', false)?></div>

    <div class="medium_label"><?php echo $GLOBALS['locBiller']?></div>
    <div class="field"><?php echo htmlFormElement('base', 'LIST', $intBaseId, 'medium', 'SELECT id, name FROM {prefix}base WHERE deleted=0 ORDER BY name', 'MODIFY', false)?></div>

    <div class="medium_label"><?php echo $GLOBALS['locClient']?></div>
    <div class="field"><?php echo htmlFormElement('company', 'LIST', $intCompanyId, 'medium', 'SELECT id, company_name FROM {prefix}company WHERE deleted=0 ORDER BY company_name', 'MODIFY', false)?></div>

    <div class="medium_label"><?php echo $GLOBALS['locPrintFormat']?></div>
    <div class="field"><input type="radio" name="format" value="html" checked="checked"><?php echo $GLOBALS['locPrintFormatHTML']?></input></div>
    <div class="medium_label"></div>
    <div class="field"><input type="radio" name="format" value="pdf"><?php echo $GLOBALS['locPrintFormatPDF']?></input></div>
    <div class="field_sep"></div>
    
    <div class="medium_label"><?php echo $GLOBALS['locPrintGrouping']?></div>
    <div class="field"><input type="radio" name="grouping" value="" checked="checked"><?php echo $GLOBALS['locPrintGroupingNone']?></input></div>
    <div class="medium_label"></div>
    <div class="field"><input type="radio" name="grouping" value="state"><?php echo $GLOBALS['locPrintGroupingState']?></input></div>
    <div class="medium_label"></div>
    <div class="field"><input type="radio" name="grouping" value="month"><?php echo $GLOBALS['locPrintGroupingMonth']?></input></div>
    <div class="field_sep">&nbsp;</div>
    
    <div class="medium_label"><?php echo $GLOBALS['locPrintReportStates']?></div>
<?php
    $strQuery = 
        "SELECT id, name ".
        "FROM {prefix}invoice_state WHERE deleted=0 ".
        "ORDER BY order_no";
    $intRes = mysql_query_check($strQuery);
    $first = true;
    while ($row = mysql_fetch_assoc($intRes))
    {
      $intStateId = $row['id'];
      $strStateName = $row['name'];
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
    $intBaseId = getRequest('base', false);
    $intCompanyId = getRequest('company', false);
    $startDate = getRequest('from', false);
    $endDate = getRequest('until', false);
    $grouping = getRequest('grouping', '');
    $format = getRequest('format', 'html');
  
    $dateRange = explode(' - ', getRequest('date', ''));
    $startDate = $dateRange[0];
    $endDate = isset($dateRange[1]) ? $dateRange[1] : ''; 
        
    if ($startDate) {
      $startDate = dateConvDate2DBDate($startDate);
    }
    if ($endDate) {
      $endDate = dateConvDate2DBDate($endDate);
    }
    
    $arrParams = array();
    
    $strQuery = 
        "SELECT i.id, i.invoice_no, i.invoice_date, i.due_date, i.ref_number, i.name AS invoice_name, i.reference, c.company_name AS name, c.billing_address, ist.name as state ".
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
    $intRes = mysql_query_check($strQuery3);
    while ($row = mysql_fetch_assoc($intRes)) 
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
    
    $strQuery .= "$strQuery2 ORDER BY " . ($grouping == 'state' ? 'state_id, invoice_date, invoice_no' : 'invoice_date, invoice_no');
    
    $this->printHeader($format, $startDate, $endDate);  
  
    $intTotSum = 0;
    $intTotVAT = 0;
    $intTotSumVAT = 0;
    $currentGroup = false;
    $groupTotSum = 0;
    $groupTotVAT = 0;
    $groupTotSumVAT = 0;
    $intRes = mysql_param_query($strQuery, $arrParams);
    while ($row = mysql_fetch_assoc($intRes))
    {
      $intInvoiceID = $row['id'];
      $strInvoiceName = $row['invoice_name'];
      $strInvoiceNr = $row['invoice_no'];
      $strInvoiceState = $row['state'];
      $strRefNumber = $row['ref_number'];
      $strInvoiceDate = dateConvDBDate2Date($row['invoice_date']);
      $strDueDate = dateConvDBDate2Date($row['due_date']);
      $strName = $row['name'];
      $strRefNumber = chunk_split($strRefNumber, 5, ' ');
      switch ($grouping) {
        case 'state':
          $invoiceGroup = $strInvoiceState;
          break;
        case 'month':
          $invoiceGroup = substr($row['invoice_date'], 4, 2);
          break;
        default:
          $invoiceGroup = false;
      }
      
      if ($grouping && $currentGroup !== false && $currentGroup != $invoiceGroup)
      {
        $this->printGroupSums($format, $groupTotSum, $groupTotVAT, $groupTotSumVAT);
        $groupTotSum = 0;
        $groupTotVAT = 0;
        $groupTotSumVAT = 0;
      }
      $currentGroup = $invoiceGroup;
      
      $strQuery = 
          "SELECT ir.description, ir.pcs, ir.price, ir.discount, ir.row_date, ir.vat, ir.vat_included ".
          "FROM {prefix}invoice_row ir ".
          "WHERE ir.invoice_id=? AND ir.deleted=0";
      $intRes2 = mysql_param_query($strQuery, array($intInvoiceID));
      $intRowSum = 0;
      $intRowVAT = 0;
      $intRowSumVAT = 0;
      while ($row2 = mysql_fetch_assoc($intRes2))
      {
        list($intSum, $intVAT, $intSumVAT) = calculateRowSum($row2['price'], $row2['pcs'], $row2['vat'], $row2['vat_included'], $row2['discount']);
        
        $intRowSum += $intSum;
        $intRowVAT += $intVAT;
        $intRowSumVAT += $intSumVAT;
        
        $intTotSum += $intSum;
        $intTotVAT += $intVAT;
        $intTotSumVAT += $intSumVAT;
      }
      $groupTotSum += $intRowSum;
      $groupTotVAT += $intRowVAT;
      $groupTotSumVAT += $intRowSumVAT;
      
      $this->printRow($format, $strInvoiceNr, $strInvoiceDate, $strDueDate, $strName, $strInvoiceState, $intRowSum, $intRowVAT, $intRowSumVAT);
    }
    if ($grouping) {
      $this->printGroupSums($format, $groupTotSum, $groupTotVAT, $groupTotSumVAT);
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
      $pdf->Cell(100, 15, $GLOBALS['locInvoiceReport'], 0, 1, 'L');
      
      if ($startDate || $endDate)
      {
        $pdf->SetFont('Helvetica','',8);
        $pdf->Cell(25, 15, $GLOBALS['locDateInterval'], 0, 0, 'L');
        $pdf->Cell(50, 15, dateConvDBDate2Date($startDate) . ' - ' . dateConvDBDate2Date($endDate), 0, 1, 'L');
      }
      
      $pdf->SetFont('Helvetica','B',8);
      $pdf->Cell(18, 4, $GLOBALS['locInvoiceNumber'], 0, 0, 'L');
      $pdf->Cell(20, 4, $GLOBALS['locInvDate'], 0, 0, 'L');
      $pdf->Cell(20, 4, $GLOBALS['locDueDate'], 0, 0, 'L');
      $pdf->Cell(45, 4, $GLOBALS['locPayer'], 0, 0, 'L');
      $pdf->Cell(15, 4, $GLOBALS['locInvoiceState'], 0, 0, 'L');
      $pdf->Cell(25, 4, $GLOBALS['locVATLess'], 0, 0, 'R');
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
            <?php echo $GLOBALS['locInvoiceNumber']?>
        </th>
        <th class="label">
            <?php echo $GLOBALS['locInvDate']?>
        </th>
        <th class="label">
            <?php echo $GLOBALS['locDueDate']?>
        </th>
        <th class="label">
            <?php echo $GLOBALS['locPayer']?>
        </th>
        <th class="label">
            <?php echo $GLOBALS['locInvoiceState']?>
        </th>
        <th class="label" style="text-align: right">
            <?php echo $GLOBALS['locVATLess']?>
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
  
  private function printRow($format, $strInvoiceNr, $strInvoiceDate, $strDueDate, $strName, $strInvoiceState, $intRowSum, $intRowVAT, $intRowSumVAT)
  {
    if ($format == 'pdf')
    {
      $pdf = $this->pdf;
      $pdf->SetFont('Helvetica','',8);
      $pdf->setY($pdf->getY() + 1);
      $pdf->Cell(18, 4, $strInvoiceNr, 0, 0, 'L');
      $pdf->Cell(20, 4, $strInvoiceDate, 0, 0, 'L');
      $pdf->Cell(20, 4, $strDueDate, 0, 0, 'L');
      $nameX = $pdf->getX();
      $pdf->setX($nameX + 45);
      $pdf->Cell(15, 4, $strInvoiceState, 0, 0, 'L');
      $pdf->Cell(25, 4, miscRound2Decim($intRowSum), 0, 0, 'R');
      $pdf->Cell(25, 4, miscRound2Decim($intRowVAT), 0, 0, 'R');
      $pdf->Cell(25, 4, miscRound2Decim($intRowSumVAT), 0, 0, 'R');
      $pdf->setX($nameX);
      $pdf->MultiCell(45, 4, $strName, 0, 'L');
      return;
    }
?>
    <tr>
        <td class="input">
            <?php echo htmlspecialchars($strInvoiceNr)?>
        </td>
        <td class="input">
            <?php echo htmlspecialchars($strInvoiceDate)?>
        </td>
        <td class="input">
            <?php echo htmlspecialchars($strDueDate)?>
        </td>
        <td class="input">
            <?php echo htmlspecialchars($strName)?>
        </td>
        <td class="input">
            <?php echo htmlspecialchars($strInvoiceState)?>
        </td>
        <td class="input" style="text-align: right">
            <?php echo miscRound2Decim($intRowSum)?>
        </td>
        <td class="input" style="text-align: right">
            <?php echo miscRound2Decim($intRowVAT)?>
        </td>
        <td class="input" style="text-align: right">
            <?php echo miscRound2Decim($intRowSumVAT)?>
        </td>
    </tr>
<?php
  }
      
  private function printGroupSums($format, $groupTotSum, $groupTotVAT, $groupTotSumVAT)
  {
    if ($format == 'pdf')
    {
      $pdf = $this->pdf;
      if ($pdf->getY() > $pdf->getPageHeight() - 7 - 15) 
        $pdf->AddPage();
      $pdf->SetFont('Helvetica','',8);
      $pdf->setLineWidth(0.2);
      $pdf->line($pdf->getX() + 120, $pdf->getY(), $pdf->getX() + 120 + 73, $pdf->getY());
      $pdf->setY($pdf->getY() + 1);
      $pdf->Cell(18, 4, '', 0, 0, 'L');
      $pdf->Cell(20, 4, '', 0, 0, 'L');
      $pdf->Cell(20, 4, '', 0, 0, 'L');
      $pdf->Cell(45, 4, '', 0, 0, 'L');
      $pdf->Cell(15, 4, '', 0, 0, 'L');
      $pdf->Cell(25, 4, miscRound2Decim($groupTotSum), 0, 0, 'R');
      $pdf->Cell(25, 4, miscRound2Decim($groupTotVAT), 0, 0, 'R');
      $pdf->Cell(25, 4, miscRound2Decim($groupTotSumVAT), 0, 1, 'R');
      $pdf->setY($pdf->getY() + 2);
      return;
    }
?>
    <tr>
        <td class="input" colspan="5" style="text-align: right">
            &nbsp;
        </td>
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
  
  private function printTotals($format, $intTotSum, $intTotVAT, $intTotSumVAT)
  {
    if ($format == 'pdf')
    {
      $pdf = $this->pdf;
      $pdf->SetFont('Helvetica','B',8);
      $pdf->setY($pdf->getY() + 3);
      $pdf->Cell(25, 4, '', 0, 0, 'L');
      $pdf->Cell(25, 4, '', 0, 0, 'L');
      $pdf->Cell(68, 4, $GLOBALS['locTotal'], 0, 0, 'R');
      $pdf->Cell(25, 4, miscRound2Decim($intTotSum), 0, 0, 'R');
      $pdf->Cell(25, 4, miscRound2Decim($intTotVAT), 0, 0, 'R');
      $pdf->Cell(25, 4, miscRound2Decim($intTotSumVAT), 0, 1, 'R');
      return;
    }
?>
    <tr>
        <td class="input total_sum" colspan="5" style="text-align: right">
            <?php echo $GLOBALS['locTotal']?>
        </td>
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
