<?php
/*******************************************************************************
VLLasku: web-based invoicing application.
Copyright (C) 2010-2011 Ere Maijala

Portions based on:
PkLasku : web-based invoicing software.
Copyright (C) 2004-2008 Samu Reinikainen

This program is free software. See attached LICENSE.

*******************************************************************************/

/*******************************************************************************
VLLasku: web-pohjainen laskutusohjelma.
Copyright (C) 2010-2011 Ere Maijala

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
    
    $intBaseId = getRequest('base', FALSE);
    $intCompanyId = getRequest('company', FALSE);
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
  
  <div class="form_container">
    <form method="get" action="" id="params" name="params">
    <input name="func" type="hidden" value="reports">
    <input name="form" type="hidden" value="invoice">
    <input name="report" type="hidden" value="1">
    
    <div class="unlimited_label"><strong><?php echo $GLOBALS['locINVOICEREPORT']?></strong></div>
    
    <div class="medium_label"><?php echo $GLOBALS['locDateInterval']?></div>
    <div class="field"><?php echo htmlFormElement('from', 'TEXT', $startDate, 'medium hasCalendar', '', 'MODIFY', FALSE)?> - <?php echo htmlFormElement('until', 'TEXT', $endDate, 'medium hasCalendar', '', 'MODIFY', FALSE)?></div>

    <div class="medium_label"><?php echo $GLOBALS['locBILLER']?></div>
    <div class="field"><?php echo htmlFormElement('base', 'LIST', $intBaseId, 'medium hasCalendar', 'SELECT id, name FROM {prefix}base WHERE deleted=0 ORDER BY name', 'MODIFY', FALSE)?></div>

    <div class="medium_label"><?php echo $GLOBALS['locCOMPANY']?></div>
    <div class="field"><?php echo htmlFormElement('company', 'LIST', $intCompanyId, 'medium', 'SELECT id, company_name FROM {prefix}company WHERE deleted=0 ORDER BY company_name', 'MODIFY', FALSE)?></div>

    <div class="medium_label"><?php echo $GLOBALS['locPrintFormat']?></div>
    <div class="field"><?php echo htmlFormElement('format', 'LIST', 'html', 'medium noemptyvalue', $typeListQuery, 'MODIFY', FALSE)?></div>

    <div class="medium_label"><?php echo $GLOBALS['locPrintStateSums']?></div>
    <div class="field"><?php echo htmlFormElement('sums', 'CHECK', 0, 'medium', '', 'MODIFY', FALSE)?></div>
    
    <div class="unlimited_label"><strong><?php echo $GLOBALS['locPrintReportStates']?></strong></div>
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
    $intBaseId = getRequest('base', FALSE);
    $intCompanyId = getRequest('company', FALSE);
    $startDate = getRequest('from', FALSE);
    $endDate = getRequest('until', FALSE);
    $sums = getRequest('sums', FALSE);
    $format = getRequest('format', 'html');
  
    if ($startDate)
      $startDate = dateConvDate2IntDate($startDate);
    if ($endDate)
      $endDate = dateConvDate2IntDate($endDate);
    
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
      $tmpSelected = getRequest($strTemp, FALSE);
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
    
    $strQuery .= "$strQuery2 ORDER BY " . ($sums ? 'state_id, invoice_date, invoice_no' : 'invoice_date, invoice_no');
    
    $this->printHeader($format, $startDate, $endDate);  
  
    $intTotSum = 0;
    $intTotVAT = 0;
    $intTotSumVAT = 0;
    $currentState = FALSE;
    $stateTotSum = 0;
    $stateTotVAT = 0;
    $stateTotSumVAT = 0;
    $intRes = mysql_param_query($strQuery, $arrParams);
    while ($row = mysql_fetch_assoc($intRes))
    {
      $intInvoiceID = $row['id'];
      $strInvoiceName = $row['invoice_name'];
      $strInvoiceNo = $row['invoice_no'];
      $strInvoiceState = $row['state'];
      $strRefNumber = $row['ref_number'];
      $strInvoiceDate = dateConvIntDate2Date($row['invoice_date']);
      $strDueDate = dateConvIntDate2Date($row['due_date']);
      $strName = $row['name'];
      if (!$strName) 
        $strName = $row['client_name'];
      $strRefNumber = chunk_split($strRefNumber, 5, ' ');
      
      if ($sums && $currentState !== FALSE && $currentState != $strInvoiceState)
      {
        $this->printStateSums($format, $currentState, $stateTotSum, $stateTotVAT, $stateTotSumVAT);
        $stateTotSum = 0;
        $stateTotVAT = 0;
        $stateTotSumVAT = 0;
      }
      $currentState = $strInvoiceState;
      
      $strQuery = 
          "SELECT ir.description, ir.pcs, ir.price, ir.row_date, ir.vat, ir.vat_included ".
          "FROM {prefix}invoice_row ir ".
          "WHERE ir.invoice_id=? AND ir.deleted=0";
      $intRes2 = mysql_param_query($strQuery, array($intInvoiceID));
      $intRowSum = 0;
      $intRowVAT = 0;
      $intRowSumVAT = 0;
      while ($row2 = mysql_fetch_assoc($intRes2))
      {
        $intItemPrice = $row2['price'];
        $intItems = $row2['pcs'];
        $intVATPercent = $row2['vat'];
        $boolVATIncluded = $row2['vat_included'];
        
        if ($boolVATIncluded)
        {
          $intSumVAT = $intItems * $intItemPrice;
          $intSum = $intSumVAT / (1 + $intVATPercent / 100);
          $intVAT = $intSumVAT - $intSum;
        }
        else
        {
          $intSum = $intItems * $intItemPrice;
          $intVAT = $intSum * ($intVATPercent / 100);
          $intSumVAT = $intSum + $intVAT;
        }
    
        $intRowSum += $intSum;
        $intRowVAT += $intVAT;
        $intRowSumVAT += $intSumVAT;
        $intTotSum += $intSum;
        $intTotVAT += $intVAT;
        $intTotSumVAT += $intSumVAT;
      }
      $stateTotSum += $intRowSum;
      $stateTotVAT += $intRowVAT;
      $stateTotSumVAT += $intRowSumVAT;
      
      $this->printRow($format, $strInvoiceNo, $strInvoiceDate, $strName, $strInvoiceState, $intRowSum, $intRowVAT, $intRowSumVAT);
    }
    if ($sums)
      $this->printStateSums($format, $currentState, $stateTotSum, $stateTotVAT, $stateTotSumVAT);
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
      $pdf->Cell(100, 15, $GLOBALS['locINVOICEREPORT'], 0, 1, 'L');
      
      if ($startDate || $endDate)
      {
        $pdf->SetFont('Helvetica','',8);
        $pdf->Cell(25, 15, $GLOBALS['locDateInterval'], 0, 0, 'L');
        $pdf->Cell(50, 15, dateConvIntDate2Date($startDate) . ' - ' . dateConvIntDate2Date($endDate), 0, 1, 'L');
      }
      
      $pdf->SetFont('Helvetica','B',8);
      $pdf->Cell(25, 4, $GLOBALS['locINVNO'], 0, 0, 'L');
      $pdf->Cell(25, 4, $GLOBALS['locINVDATE'], 0, 0, 'L');
      $pdf->Cell(45, 4, $GLOBALS['locPAYER'], 0, 0, 'L');
      $pdf->Cell(20, 4, $GLOBALS['locINVOICESTATE'], 0, 0, 'L');
      $pdf->Cell(25, 4, $GLOBALS['locVATLESS'], 0, 0, 'R');
      $pdf->Cell(25, 4, $GLOBALS['locVATPART'], 0, 0, 'R');
      $pdf->Cell(25, 4, $GLOBALS['locWITHVAT'], 0, 1, 'R');
      $this->pdf = $pdf;
      return;
    }
  ?>
    <div class="report">
    <table>
    <tr>
        <th class="label" align="left">
            <?php echo $GLOBALS['locINVNO']?>
        </th>
        <th class="label" align="left">
            <?php echo $GLOBALS['locINVDATE']?>
        </th>
        <th class="label" align="left">
            <?php echo $GLOBALS['locPAYER']?>
        </th>
        <th class="label" align="left">
            <?php echo $GLOBALS['locINVOICESTATE']?>
        </th>
        <th class="label" align="right" style="text-align: right">
            <?php echo $GLOBALS['locVATLESS']?>
        </th>
        <th class="label" align="right" style="text-align: right">
            <?php echo $GLOBALS['locVATPART']?>
        </th>
        <th class="label" align="right" style="text-align: right">
            <?php echo $GLOBALS['locWITHVAT']?>
        </th>
    </tr>
  <?php
  }
  
  private function printRow($format, $strInvoiceNo, $strInvoiceDate, $strName, $strInvoiceState, $intRowSum, $intRowVAT, $intRowSumVAT)
  {
    if ($format == 'pdf')
    {
      $pdf = $this->pdf;
      $pdf->SetFont('Helvetica','',8);
      $pdf->setY($pdf->getY() + 1);
      $pdf->Cell(25, 4, $strInvoiceNo, 0, 0, 'L');
      $pdf->Cell(25, 4, $strInvoiceDate, 0, 0, 'L');
      $nameX = $pdf->getX();
      $pdf->setX($nameX + 45);
      $pdf->Cell(20, 4, $strInvoiceState, 0, 0, 'L');
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
            <?php echo htmlspecialchars($strInvoiceNo)?>
        </td>
        <td class="input">
            <?php echo htmlspecialchars($strInvoiceDate)?>
        </td>
        <td class="input">
            <?php echo htmlspecialchars($strName)?>
        </td>
        <td class="input">
            <?php echo htmlspecialchars($strInvoiceState)?>
        </td>
        <td class="input" align="right">
            <?php echo miscRound2Decim($intRowSum)?>
        </td>
        <td class="input" align="right">
            <?php echo miscRound2Decim($intRowVAT)?>
        </td>
        <td class="input" align="right">
            <?php echo miscRound2Decim($intRowSumVAT)?>
        </td>
    </tr>
  <?php
  }
      
  private function printStateSums($format, $state, $stateTotSum, $stateTotVAT, $stateTotSumVAT)
  {
    if ($format == 'pdf')
    {
      $pdf = $this->pdf;
      if ($pdf->getY() > $pdf->getPageHeight() - 7 - 15) 
        $pdf->AddPage();
      $pdf->SetFont('Helvetica','',8);
      $pdf->setLineWidth(0.2);
      $pdf->line($pdf->getX() + 120, $pdf->getY(), $pdf->getX() + 120 + 70, $pdf->getY());
      $pdf->setY($pdf->getY() + 1);
      $pdf->Cell(25, 4, '', 0, 0, 'L');
      $pdf->Cell(25, 4, '', 0, 0, 'L');
      $pdf->Cell(45, 4, '', 0, 0, 'L');
      $pdf->Cell(20, 4, '', 0, 0, 'L');
      $pdf->Cell(25, 4, miscRound2Decim($stateTotSum), 0, 0, 'R');
      $pdf->Cell(25, 4, miscRound2Decim($stateToVAT), 0, 0, 'R');
      $pdf->Cell(25, 4, miscRound2Decim($stateTotSumVAT), 0, 1, 'R');
      $pdf->setY($pdf->getY() + 2);
      return;
    }
  ?>
    <tr>
        <td class="input" colspan="4" align="right">
            &nbsp;
        </td>
        <td class="input row_sum" align="right">
            &nbsp;<?php echo miscRound2Decim($stateTotSum)?>
        </td>
        <td class="input row_sum" align="right">
            &nbsp;<?php echo miscRound2Decim($stateTotVAT)?>
        </td>
        <td class="input row_sum" align="right">
            &nbsp;<?php echo miscRound2Decim($stateTotSumVAT)?>
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
      $pdf->Cell(65, 4, $GLOBALS['locTOTAL'], 0, 0, 'L');
      $pdf->Cell(25, 4, '', 0, 0, 'L');
      $pdf->Cell(25, 4, '', 0, 0, 'L');
      $pdf->Cell(25, 4, miscRound2Decim($intTotSum), 0, 0, 'R');
      $pdf->Cell(25, 4, miscRound2Decim($intTotVAT), 0, 0, 'R');
      $pdf->Cell(25, 4, miscRound2Decim($intTotSumVAT), 0, 1, 'R');
      return;
    }
  ?>
    <tr>
        <td class="input total_sum" colspan="4" align="right">
            <?php echo $GLOBALS['locTOTAL']?>
        </td>
        <td class="input total_sum" align="right">
            &nbsp;<?php echo miscRound2Decim($intTotSum)?>
        </td>
        <td class="input total_sum" align="right">
            &nbsp;<?php echo miscRound2Decim($intTotVAT)?>
        </td>
        <td class="input total_sum" align="right">
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
