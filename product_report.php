<?php
/*******************************************************************************
VLLasku: web-based invoicing application.
Copyright (C) 2010-2012 Ere Maijala

Portions based on:
PkLasku : web-based invoicing software.
Copyright (C) 2004-2008 Samu Reinikainen

This program is free software. See attached LICENSE.

*******************************************************************************/

/*******************************************************************************
VLLasku: web-pohjainen laskutusohjelma.
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
    $startDate = getRequest('from', date('d.m.Y', mktime(0, 0, 0, date('m') - 1, 1, date('Y'))));
    $endDate = getRequest('until', date('d.m.Y', mktime(0, 0, 0, date('m'), 0, date('Y'))));
          
    $typeListQuery = 
        "SELECT 'html' AS id, '" . $GLOBALS['locPrintFormatHTML'] . "' AS name UNION ".
        "SELECT 'pdf' AS id, '" . $GLOBALS['locPrintFormatPDF'] . "' AS name";
?>
    
  <script type="text/javascript">
  $(document).ready(function() { 
    $('input[class~="hasCalendar"]').datepicker();
  });
  </script>
  
  <div class="form_container ui-widget-content ui-helper-clearfix">
    <form method="get" id="params" name="params">
    <input name="func" type="hidden" value="reports">
    <input name="form" type="hidden" value="product">
    <input name="report" type="hidden" value="1">
    
    <div class="unlimited_label"><h1><?php echo $GLOBALS['locPRODUCTREPORT']?></h1></div>
    
    <div class="medium_label"><?php echo $GLOBALS['locDateInterval']?></div>
    <div class="field"><?php echo htmlFormElement('from', 'TEXT', $startDate, 'medium hasCalendar', '', 'MODIFY', FALSE)?> - <?php echo htmlFormElement('until', 'TEXT', $endDate, 'medium hasCalendar', '', 'MODIFY', FALSE)?></div>

    <div class="medium_label"><?php echo $GLOBALS['locBILLER']?></div>
    <div class="field"><?php echo htmlFormElement('base', 'LIST', $intBaseId, 'medium hasCalendar', 'SELECT id, name FROM {prefix}base WHERE deleted=0 ORDER BY name', 'MODIFY', FALSE)?></div>

    <div class="medium_label"><?php echo $GLOBALS['locCOMPANY']?></div>
    <div class="field"><?php echo htmlFormElement('company', 'LIST', $intCompanyId, 'medium', 'SELECT id, company_name FROM {prefix}company WHERE deleted=0 ORDER BY company_name', 'MODIFY', FALSE)?></div>

    <div class="medium_label"><?php echo $GLOBALS['locPRODUCT']?></div>
    <div class="field"><?php echo htmlFormElement('product', 'LIST', $intProductId, 'medium', 'SELECT id, product_name FROM {prefix}product WHERE deleted=0 ORDER BY product_name', 'MODIFY', FALSE)?></div>

    <div class="medium_label"><?php echo $GLOBALS['locPrintFormat']?></div>
    <div class="field"><?php echo htmlFormElement('format', 'LIST', 'html', 'medium noemptyvalue', $typeListQuery, 'MODIFY', FALSE)?></div>

    <div class="unlimited_label"><h1><?php echo $GLOBALS['locPrintReportStates']?></h1></div>
  <?php
    $strQuery = 
        "SELECT id, name ".
        "FROM {prefix}invoice_state WHERE deleted=0 ".
        "ORDER BY order_no";
    $intRes = mysql_query_check($strQuery);
    while ($row = mysql_fetch_assoc($intRes))
    {
      $intStateId = $row['id'];
      $strStateName = $row['name'];
      $tmpSelected = getRequest("stateid_$intStateId", TRUE) ? TRUE : FALSE;
      $strChecked = $tmpSelected ? ' checked' : '';
    ?>
    <div class="medium_label"><input type="checkbox" name="stateid_<?php echo $intStateId?>" value="1"<?php echo $strChecked?>> <?php echo htmlspecialchars($strStateName)?></div>
  <?php
    }
  ?>
    <div class="medium_label">
      <a class="actionlink" href="#" onclick="document.getElementById('params').submit(); return false;"><?php echo $GLOBALS['locGET']?></a>
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
    $startDate = getRequest('from', FALSE);
    $endDate = getRequest('until', FALSE);
    $format = getRequest('format', 'html');
    
    if ($startDate)
      $startDate = dateConvDate2DBDate($startDate);
    if ($endDate)
      $endDate = dateConvDate2DBDate($endDate);
    
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
    $intRes = mysql_query_check($strQuery3);
    while ($row = mysql_fetch_assoc($intRes)) 
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
    
    $strProductQuery = 'SELECT p.product_name, ir.description, ' . 
      'CASE WHEN ir.vat_included = 0 THEN sum(ir.price * ir.pcs * (1 - ir.discount / 100)) ELSE sum(ir.price * ir.pcs * (1 - ir.discount / 100) / (1 + ir.vat / 100)) END as total_price, ' .
      'ir.vat, sum(ir.pcs) as pcs, t.name as unit ' .
      'FROM {prefix}invoice_row ir ' .
      'LEFT OUTER JOIN {prefix}product p ON p.id = ir.product_id ' .
      'LEFT OUTER JOIN {prefix}row_type t ON t.id = ir.type_id ' .
      "WHERE ir.deleted=0 AND ir.invoice_id IN ($strQuery) $strProductWhere" .
      'GROUP BY p.product_name, ir.description, ir.vat, t.name ' .
      'ORDER BY p.product_name, ir.description';
      
    $this->printHeader($format, $startDate, $endDate);  
      
    $intTotSum = 0;
    $intTotVAT = 0;
    $intTotSumVAT = 0;
    $intRes = mysql_param_query($strProductQuery, $arrParams);
    while ($row = mysql_fetch_assoc($intRes))
    {
      $strProduct = $row['product_name'];
      $strDescription = $row['description'];
      $intCount = $row['pcs'];
      $strUnit = $row['unit'];
      $intSum = $row['total_price'];
      $intVATPercent = $row['vat'];
      
      $intVAT = $intSum * $intVATPercent / 100;
      $intSumVAT = $intSum + $intVAT;
      
      $intTotSum += $intSum;
      $intTotVAT += $intVAT;
      $intTotSumVAT += $intSumVAT;
      
      $this->printRow($format, $strProduct, $strDescription, $intCount, $strUnit, $intSum, $intVATPercent, $intVAT, $intSumVAT);
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
      $pdf->Cell(100, 15, $GLOBALS['locPRODUCTREPORT'], 0, 1, 'L');
      
      if ($startDate || $endDate)
      {
        $pdf->SetFont('Helvetica','',8);
        $pdf->Cell(25, 15, $GLOBALS['locDateInterval'], 0, 0, 'L');
        $pdf->Cell(50, 15, dateConvDBDate2Date($startDate) . ' - ' . dateConvDBDate2Date($endDate), 0, 1, 'L');
      }
      
      $pdf->SetFont('Helvetica','B',8);
      $pdf->Cell(50, 4, $GLOBALS['locPRODUCT'], 0, 0, 'L');
      $pdf->Cell(25, 4, $GLOBALS['locPCS'], 0, 0, 'R');
      $pdf->Cell(25, 4, $GLOBALS['locUNIT'], 0, 0, 'R');
      $pdf->Cell(25, 4, $GLOBALS['locVATLESS'], 0, 0, 'R');
      $pdf->Cell(15, 4, $GLOBALS['locVATPERCENT'], 0, 0, 'R');
      $pdf->Cell(25, 4, $GLOBALS['locVATPART'], 0, 0, 'R');
      $pdf->Cell(25, 4, $GLOBALS['locWITHVAT'], 0, 1, 'R');
      $this->pdf = $pdf;
      return;
    }
  ?>
    <div class="report">
    <table>
    <tr>
        <th class="label">
            <?php echo $GLOBALS['locPRODUCT']?>
        </th>
        <th class="label" style="text-align: right">
            <?php echo $GLOBALS['locPCS']?>
        </th>
        <th class="label" style="text-align: right">
            <?php echo $GLOBALS['locUNIT']?>
        </th>
        <th class="label" style="text-align: right">
            <?php echo $GLOBALS['locVATLESS']?>
        </th>
        <th class="label" style="text-align: right">
            <?php echo $GLOBALS['locVATPERCENT']?>
        </th>
        <th class="label" style="text-align: right">
            <?php echo $GLOBALS['locVATPART']?>
        </th>
        <th class="label" style="text-align: right">
            <?php echo $GLOBALS['locWITHVAT']?>
        </th>
    </tr>
  <?php
  }
  
  private function printRow($format, $strProduct, $strDescription, $intCount, $strUnit, $intSum, $intVATPercent, $intVAT, $intSumVAT)
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
      $nameX = $pdf->getX();
      $pdf->setXY($nameX + 50, $pdf->getY() + 1);
      $pdf->Cell(25, 4, miscRound2Decim($intCount), 0, 0, 'R');
      $pdf->Cell(25, 4, $strUnit, 0, 0, 'R');
      $pdf->Cell(25, 4, miscRound2Decim($intSum), 0, 0, 'R');
      $pdf->Cell(15, 4, miscRound2Decim($intVATPercent, 1), 0, 0, 'R');
      $pdf->Cell(25, 4, miscRound2Decim($intVAT), 0, 0, 'R');
      $pdf->Cell(25, 4, miscRound2Decim($intSumVAT), 0, 0, 'R');
      $pdf->setX($nameX);
      $pdf->MultiCell(50, 4, $strProduct, 0, 'L');
      return;
    }
    if (!$strProduct)
      $strProduct = '&ndash;';
    else
      $strProduct = htmlspecialchars($strProduct);
?>
    <tr>
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
      $pdf->Cell(50, 4, $GLOBALS['locTOTAL'], 0, 0, 'L');
      $pdf->Cell(25, 4, '', 0, 0, 'L');
      $pdf->Cell(25, 4, '', 0, 0, 'L');
      $pdf->Cell(25, 4, miscRound2Decim($intTotSum), 0, 0, 'R');
      $pdf->Cell(15, 4, '', 0, 0, 'L');
      $pdf->Cell(25, 4, miscRound2Decim($intTotVAT), 0, 0, 'R');
      $pdf->Cell(25, 4, miscRound2Decim($intTotSumVAT), 0, 1, 'R');
      return;
    }
  ?>
    <tr>
        <td class="input total_sum">
            <?php echo $GLOBALS['locTOTAL']?>
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
