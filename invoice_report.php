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

function createInvoiceReport($strType)
{
  $strReport = getRequest('report', '');
  
  if ($strReport)
  {
    printReport();
    return;
  }
  
  $intBaseId = getRequest('base', FALSE);
  $intCompanyId = getRequest('company', FALSE);
  $intYear = getRequest('year', date("Y"));
  $intMonth = getRequest('month', date("n"));
  $intSelectedStateId = getRequest('stateid', 1);
  
  $intCurrentYear = date("Y");
  for( $i = 0; $i <= 20; $i++) {
      $astrYearListValues[$i] = ($intCurrentYear+10)-$i;
      $astrYearListOptions[$i] = ($intCurrentYear+10)-$i;
  }
  $strYearListBox = htmlListBox( "year", $astrYearListValues, $astrYearListOptions, $intYear, "", TRUE, FALSE );
  
  $astrShowElements = array();
  switch ( $strType ) {
  
  case 'report':
     $strTopLabel = $GLOBALS['locPRINTINVOICEREPORT'];
     $strMidLabel = $GLOBALS['locPRINTREPORTSTATES'];
     $strListQuery = 
          "SELECT '0' AS id, '".$GLOBALS['locALL']."' AS name UNION ".
          "SELECT '01' AS id, '".$GLOBALS['locJAN']."' AS name UNION ".
          "SELECT '02' AS id, '".$GLOBALS['locFEB']."' AS name UNION ".
          "SELECT '03' AS id, '".$GLOBALS['locMAR']."' AS name UNION ".
          "SELECT '04' AS id, '".$GLOBALS['locAPR']."' AS name UNION ".
          "SELECT '05' AS id, '".$GLOBALS['locMAY']."' AS name UNION ".
          "SELECT '06' AS id, '".$GLOBALS['locJUN']."' AS name UNION ".
          "SELECT '07' AS id, '".$GLOBALS['locJUL']."' AS name UNION ".
          "SELECT '08' AS id, '".$GLOBALS['locAUG']."' AS name UNION ".
          "SELECT '09' AS id, '".$GLOBALS['locSEP']."' AS name UNION ".
          "SELECT '10' AS id, '".$GLOBALS['locOCT']."' AS name UNION ".
          "SELECT '11' AS id, '".$GLOBALS['locNOV']."' AS name UNION ".
          "SELECT '12' AS id, '".$GLOBALS['locDEC']."' AS name ";
     $astrSearchElements =
      array(
       array("type" => "ELEMENT", "element" => $strYearListBox, "label" => $GLOBALS['locYEAR']),
       array("name" => "month", "label" => $GLOBALS['locMONTH'], "type" => "SUBMITLIST", "style" => "medium", "listquery" => $strListQuery, "value" => $intMonth),
       array("name" => "base", "label" => $GLOBALS['locBILLER'], "type" => "SUBMITLIST", "style" => "medium", "listquery" => "SELECT id, name FROM {prefix}base WHERE deleted=0 ORDER BY name", "value" => $intBaseId),
       array("name" => "company", "label" => $GLOBALS['locCOMPANY'], "type" => "SUBMITLIST", "style" => "medium", "listquery" => "SELECT id, company_name FROM {prefix}company WHERE deleted=0 ORDER BY company_name", "value" => $intCompanyId)
      );
      
      $strQuery = 
          "SELECT id, name ".
          "FROM {prefix}invoice_state WHERE deleted=0 ".
          "ORDER BY order_no";
      $intRes = mysql_query_check($strQuery);
      for ($i = 0, $row = mysql_fetch_assoc($intRes); $row; $i++, $row = mysql_fetch_assoc($intRes))
      {
          $intStateId = $row['id'];
          $strStateName = $row['name'];
          $strTemp = "stateid_$intStateId";
          $tmpSelected = getPost($strTemp, FALSE) ? TRUE : FALSE;
          $strChecked = $tmpSelected ? 'checked' : '';
          $astrHtmlElements[$i] = 
            array("label" => $strStateName, "html" => "<input type=\"checkbox\" name=\"stateid_{$intStateId}\" value=\"1\" $strChecked>\n");
      }
  break;
  }
?>
  
  <div class="list_container">
  <form method="get" action="" name="selectinv">
  <input name="func" type="hidden" value="reports">
  <input name="form" type="hidden" value="invoice">
  
  <br>
  <b><?php echo $strTopLabel?></b>
  <table>
<?php
  for( $j = 0; $j < count($astrSearchElements); $j++ ) {
      if( $astrSearchElements[$j]['type'] == "ELEMENT" ) {
?>
      <tr>
          <td class="label">
              <?php echo $astrSearchElements[$j]['label']?>
          </td>
          <td class="field">
              <?php echo $astrSearchElements[$j]['element']?>
          </td>
      </tr>
<?php
      }
      else {
?>
      <tr>
          <td class="label">
              <?php echo $astrSearchElements[$j]['label']?>
          </td>
          <td class="field">
              <?php echo htmlFormElement($astrSearchElements[$j]['name'], $astrSearchElements[$j]['type'], $astrSearchElements[$j]['value'], $astrSearchElements[$j]['style'], $astrSearchElements[$j]['listquery'], "MODIFY", isset($astrSearchElements[$j]['parent_key']) ? $astrSearchElements[$j]['parent_key'] : FALSE)?>
          </td>
      </tr>
<?php
      }
  }
  
?>
  </table>
  </form>
  <form method="get" action="" name="invoice">
  <input name="func" type="hidden" value="reports">
  <input name="form" type="hidden" value="invoice">

  <input name="year" type="hidden" value="<?php echo $intYear?>">
  <input name="month" type="hidden" value="<?php echo $intMonth?>">
  <input name="base" type="hidden" value="<?php echo $intBaseId?>">
  <input name="company" type="hidden" value="<?php echo $intCompanyId?>">
  <input name="report" type="hidden" value="<?php echo $strType?>">
  <b><?php echo $strMidLabel?></b>
  <table>
<?php
  for( $j = 0; $j < count($astrShowElements); $j++ ) {
?>
      <tr>
          <td class="label">
              <?php echo $astrShowElements[$j]['label']?>
          </td>
          <td class="field">
              <?php echo htmlFormElement($astrShowElements[$j]['name'], $astrShowElements[$j]['type'], $astrShowElements[$j]['value'], $astrShowElements[$j]['style'], $astrShowElements[$j]['listquery'], "MODIFY", $astrShowElements[$j]['parent_key'])?>
          </td>
      </tr>
<?php
  }
  for( $j = 0; $j < count($astrHtmlElements); $j++ ) {
  ?>
      <tr>
          <td class="label">
              <?php echo $astrHtmlElements[$j]['label']?>
          </td>
          <td class="field">
              <?php echo $astrHtmlElements[$j]['html']?>
          </td>
      </tr>
<?php
  }
?>
      <tr>
          <td>
              <input type="hidden" name="get_x" value="0">
              <a class="actionlink" href="#" onclick="self.document.invoice.get_x.value=1; self.document.invoice.submit(); return false;"><?php echo $GLOBALS['locGET']?></a>
          </td>
      </tr>
  
  </table>
  </form>
  </div>
<?php
}

function printReport()
{
  $intBaseId = getRequest('base', FALSE);
  $intCompanyId = getRequest('company', FALSE);
  $intYear = getRequest('year', date("Y"));
  $intMonth = getRequest('month', date("n"));
  $intSelectedStateId = getRequest('stateid', 1);
  
  if( $intMonth == 0 ) {
      $intStartDate = "00";
      $intEndDate = "31";
      $intEndMonth = "12";
  }
  else {
      $intStartDate = "00";
      $intEndDate = date("t",mktime(0, 0, 0, $intMonth, 1, $intYear));
      $intEndMonth = $intMonth;
  }
  if( strlen($intMonth) == 1 ) {
      $intMonth = "0".$intMonth;
  }
  if( strlen($intEndMonth) == 1 ) {
      $intEndMonth = "0".$intEndMonth;
  }
  $strStart = $intYear. $intMonth. $intStartDate;
  $strEnd = $intYear. $intEndMonth. $intEndDate;
  
  $arrParams = array($strStart, $strEnd);
  
  $strQuery = 
      "SELECT i.id, i.invoice_no, i.invoice_date, i.due_date, i.ref_number, i.name AS invoice_name, i.reference, c.company_name AS name, c.billing_address, ist.name as state ".
      "FROM {prefix}invoice i ".
      "LEFT OUTER JOIN {prefix}company c ON c.id = i.company_id ".
      "LEFT OUTER JOIN {prefix}invoice_state ist ON i.state_id = ist.id ".
      "WHERE i.deleted=0 AND i.invoice_date > ? AND i.invoice_date <= ?";
  
  $strQuery2 = "";
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
      if( $tmpSelected ) {
          $strQuery2 .= 
              ' i.state_id = ? OR ';
          $arrParams[] = $intStateId;
      }
  }
  if( $strQuery2 ) {
      $strQuery2 = " AND (". substr($strQuery2, 0, -3). ")";
  }
  
  if( $intBaseId ) {
      $strQuery .= 
          " AND i.base_id = ?";
      $arrParams[] = $intBaseId;
  }

  if( $intCompanyId ) {
      $strQuery .= 
          " AND i.company_id = ?";
      $arrParams[] = $intCompanyId;
  }
  
  $strQuery .= "$strQuery2 ORDER BY invoice_no";
  
  $intRes = mysql_param_query($strQuery, $arrParams);
  $intNumRows = mysql_numrows($intRes);
?>
  <div class="report">
  <table>
  <tr>
      <th class="label" align="center">
          <?php echo $GLOBALS['locINVNO']?>
      </th>
      <th class="label" align="center">
          <?php echo $GLOBALS['locINVDATE']?>
      </th>
      <th class="label" align="center">
          <?php echo $GLOBALS['locPAYER']?>
      </th>
      <th class="label" align="center">
          <?php echo $GLOBALS['locINVOICESTATE']?>
      </th>
      <th class="label" align="center">
          <?php echo $GLOBALS['locVATLESS']?>
      </th>
      <th class="label" align="center">
          <?php echo $GLOBALS['locVATPART']?>
      </th>
      <th class="label" align="center">
          <?php echo $GLOBALS['locWITHVAT']?>
      </th>
  </tr>
<?php
  if( $intNumRows ) {
      $intTotSum = 0;
      $intTotVAT = 0;
      $intTotSumVAT = 0;
      while($row = mysql_fetch_assoc($intRes))
      {
          $intInvoiceID = $row['id'];
          $strInvoiceName = $row['invoice_name'];
          $strInvoiceNo = $row['invoice_no'];
          $strInvoiceState = $row['state'];
          $strRefNumber = $row['ref_number'];
          $strInvoiceDate = dateConvIntDate2Date($row['invoice_date']);
          $strDueDate = dateConvIntDate2Date($row['due_date']);
          $strName = $row['name'];
          if( !$strName ) {
              $strName = $row['client_name'];
          }
          $strRefNumber = chunk_split($strRefNumber, 5, ' ');
          $strQuery = 
              "SELECT ir.description, ir.pcs, ir.price, ir.row_date, ir.vat, ir.vat_included ".
              "FROM {prefix}invoice_row ir ".
              "WHERE ir.invoice_id=? AND ir.deleted=0";
          $intRes2 = mysql_param_query($strQuery, array($intInvoiceID));
          if( $intRes2 ) {
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
?>
  <tr>
      <td class="input" colspan="4" align="right">
          <b><?php echo $GLOBALS['locTOTAL']?></b>
      </td>
      <td class="input" align="right">
          <b>&nbsp;<?php echo miscRound2Decim($intTotSum)?></b>
      </td>
      <td class="input" align="right">
          <b>&nbsp;<?php echo miscRound2Decim($intTotVAT)?></b>
      </td>
      <td class="input" align="right">
          <b>&nbsp;<?php echo miscRound2Decim($intTotSumVAT)?></b>
      </td>
  </tr>
  </table>
  </div>
<?php
  }
}
