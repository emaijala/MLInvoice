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

require "htmlfuncs.php";
require "sqlfuncs.php";
require "sessionfuncs.php";
require "miscfuncs.php";
require "datefuncs.php";
require_once "localize.php";
require_once 'form_funcs.php';

sesVerifySession();


$strForm = getPostRequest('selectform', '');
$strMode = getPostRequest('mode', 'MODIFY');

require "form_switch.php";

echo htmlPageStart( _PAGE_TITLE_ );

$blnAdd = getPost('addact', FALSE) ? TRUE : FALSE;
$blnSave = getPost('saveact', FALSE) ? TRUE : FALSE;
$blnDelete = getPost('deleteact', FALSE) ? TRUE : FALSE;
$intKeyValue = getPostRequest($strPrimaryKey, FALSE);
$intParentKey = getPostRequest($strParentKey, FALSE);

$blnInsertDone = FALSE;
$strMessage = '';

$astrValues = getPostValues($astrFormElements, $intKeyValue, $intParentKey);

if ($blnAdd || $blnSave) 
{
  if ($blnAdd)
    unset($intKeyValue);
  $res = saveFormData($strTable, $intKeyValue, $astrFormElements, $astrValues, $strParentKey, $intParentKey);
  if ($res !== TRUE)
  {
    $strMessage .= $GLOBALS['locERRVALUEMISSING'] . ": $res";
  }
  else
  {
    // Update defaults for running values
    foreach ($astrFormElements as $elem)
    {
      if (!$elem['default']) 
      {
        unset($astrValues[$elem['name']]);
      }  
      else 
      {
        if ($elem['default'] == 'DATE_NOW' ) 
        {
          $strDefaultValue = date('Ymd');
        }
        elseif (strstr($elem['default'], 'ADD')) 
        {
           $intAdd = substr($elem['default'], 4);
           $_POST[$elem['name']] += $intAdd;
        }
        else 
        {
          $strDefaultValue = $elem['default'];
        }
        $astrValues[$elem['name']] = getPost($elem['name'], $strDefaultValue);
      }
    }
  }
}
elseif ($blnDelete && $intKeyValue) 
{
  $strQuery = "UPDATE $strTable SET deleted=1 WHERE $strPrimaryKey=?";
  mysql_param_query($strQuery, array($intKeyValue));
  unset($intKeyValue);
  unset($astrValues);
  $blnNew = TRUE;
}

if ($intParentKey) 
{
  $strQuery =
    "SELECT * FROM $strTable " .
    'WHERE ' . (getSetting('show_deleted_records') ? '' : 'deleted=0 AND ') . "$strParentKey=? $strOrder";

  $res = mysql_param_query($strQuery, array($intParentKey));
  $astrExistingValues = array();
  for ($i = 0, $row = mysql_fetch_assoc($res); $row; $i++, $row = mysql_fetch_assoc($res)) 
  {
    $astrExistingValues[$i]['deleted'] = $row['deleted'];
    foreach ($astrFormElements as $elem)
    {
      if ($elem['type'] != 'NEWLINE' && $elem['type'] != 'ROWSUM' ) 
      {
        if ($elem['type'] == 'INTDATE') 
        {
          $astrExistingValues[$i][$elem['name']] = dateConvIntDate2Date($row[$elem['name']]);
        }
        elseif ($elem['type'] == 'BUTTON') 
        {
          $astrExistingValues[$i][$elem['name']] = $row['id'];
        }
        else 
        {
          $astrExistingValues[$i][$elem['name']] = $row[$elem['name']];
        }
      }
      else 
      {
        $astrExistingValues[$i][$elem['name']] = $intKeyValue;
      }
    }
  }
}

?>
<body class="iform">
<script type="text/javascript">
<!--
$(function() {
  $('input[class~="hasCalendar"]').datepicker();
  $('a[class~="row_edit_button"]').click(function(event) {
    var row_id = $(this).attr('href').match(/(\d+)/)[1];
    popup_editor(event, '<?php echo $GLOBALS['locRowEdit']?>', row_id, false);
    return false;
  });
  

  $('a[class~="row_copy_button"]').click(function(event) {
    var row_id = $(this).attr('href').match(/(\d+)/)[1];
    popup_editor(event, '<?php echo $GLOBALS['locRowCopy']?>', row_id, true);
    return false;
  });
  
});

function popup_editor(event, title, id, copy_row)
{
  $.getJSON('json.php?func=get_<?php echo $strJSONType?>&id=' + id, function(json) { 
    if (!json.id) return; 
    var form = document.getElementById('iform_popup');
    
    if (copy_row)
      form.addact.value = 1;
    form.<?php echo $strPrimaryKey?>.value = id;
<?php
foreach ($astrFormElements as $elem)
{
  if (in_array($elem['type'], array('HID_INT', 'SECHID_INT', 'BUTTON', 'NEWLINE', 'ROWSUM')))
    continue;
  $name = $elem['name'];
  if ($elem['type'] == 'LIST')
  {
?>
    for (var i = 0; i < form.<?php echo $name?>.options.length; i++)
    {  
      var item = form.<?php echo $name?>.options[i];
      if (item.value == json.<?php echo $name?>)
      {
        item.selected = true;
        break;
      }
    }
<?php
  }
  elseif ($elem['type'] == 'INT')
  {
?> 
    form.<?php echo $name?>.value = json.<?php echo $name?>.replace('.', ',');
<?php
  }
  else
  {
?> 
    form.<?php echo $name?>.value = json.<?php echo $name?>;
<?php
  }
}
?>    
    $("#popup_edit").dialog({ modal: true, width: 810, height: 140, resizable: false, 
      position: [50, 20], 
      buttons: {
          "<?php echo $GLOBALS['locSAVE']?>": function() { var form = document.getElementById('iform_popup'); form.saveact.value=1; form.submit(); return false; },
          "<?php echo $GLOBALS['locDELETE']?>": function() { if(confirm('<?php echo $GLOBALS['locCONFIRMDELETE']?>')==true) { var form = document.getElementById('iform_popup'); form.deleteact.value=1; form.submit(); } return false; },
          "<?php echo $GLOBALS['locCLOSE']?>": function() { $("#popup_edit").dialog('close'); }
      },
      title: title,
    });

  });
}  
-->
</script>

<?php
if ($strMessage)
{
?>
<div class="message"><?php echo $strMessage?></div>
<?php
}
?>

<form method="post" action="<?php echo $strMainForm?>" target="_self" name="iform">
<input type="hidden" name="<?php echo $strParentKey?>" value="<?php echo $intParentKey?>">
<table class="iform">
  <tr>
<?php
$strRowSpan = '';
foreach ($astrFormElements as $elem)
{
  if (!in_array($elem['type'], array('HID_INT', 'SECHID_INT', 'BUTTON', 'NEWLINE', 'ROWSUM')))
  {
?>
    <td class="label <?php echo strtolower($elem['style'])?>_label">
      <?php echo $elem['label']?><br>
      <?php echo htmlFormElement($elem['name'], $elem['type'], gpcStripSlashes(isset($astrValues[$elem['name']]) ? $astrValues[$elem['name']] : ''), $elem['style'],$elem['listquery'], "MODIFY", 0, '', array(), $elem['elem_attributes'])?>
    </td>
<?php
  }
  elseif( $elem['type'] == 'SECHID_INT' ) 
  {
?>
    <input type="hidden" name="<?php echo $elem['name']?>" value="<?php echo gpcStripSlashes($astrValues[$elem['name']])?>">
<?php
  }
  elseif( $elem['type'] == 'BUTTON' ) 
  {
?>
    <td class="label">
      &nbsp;
    </td>
<?php
  }
  elseif( $elem['type'] == 'NEWLINE' ) 
  {
    $strRowSpan = 'rowspan="2"';
?>
</tr>
<tr>
<?php
  }
  elseif ($elem['type'] == 'ROWSUM') 
  {
?>
    <td class="label <?php echo strtolower($elem['style'])?>_label">
      <?php echo $elem['label']?><br>
    </td>
<?php
  }
}
?>
    <td class="button" <?php echo $strRowSpan?>>
      <br>
      <input type="hidden" name="addact" value="0">
      <a class="tinyactionlink" href="#" onclick="self.document.forms[0].addact.value=1; self.document.forms[0].submit(); return false;"><?php echo $GLOBALS['locADDROW']?></a>
    </td>
  </tr>

<?php
foreach ($astrExistingValues as $row) 
{
?>
  <tr>
<?php
 foreach ($astrFormElements as $elem) 
  {
    $elemStyle = $elem['style'];
    if ($row['deleted'])
      $elemStyle .= ' deleted';
      
    if ($elem['type'] == 'ROWSUM') 
    {
      $items = $row[$multiplierColumn];
      $price = $row[$priceColumn];
      $VATPercent = $row[$VATColumn];
      $VATIncluded = $row[$VATIncludedColumn];
      
      if ($VATIncluded)
      {
        $sumVAT = $items * $price;
        $sum = $sumVAT / (1 + $VATPercent / 100);
        $VAT = $sumVAT - $sum;
      }
      else
      {
        $sum = $items * $price;
        $VAT = $sum * ($VATPercent / 100);
        $sumVAT = $sum + $VAT;
      }
      $title = $GLOBALS['locVATLESS'] . ': ' . miscRound2Decim($sum) . ' &ndash; ' . $GLOBALS['locVATPART'] . ': ' . miscRound2Decim($VAT);         
?>
    <td class="<?php echo $elemStyle?>" >
      <?php echo htmlFormElement($elem['name'], 'TITLEDTEXT', miscRound2Decim($sumVAT), $elem['style'], '', 'NO_MOD', 0, $title, array(), isset($elem['elem_attributes']) ? $elem['elem_attributes'] : '')?>
    </td>
<?php
    }
    elseif ($elem['type'] != 'HID_INT' && $elem['type'] != 'SECHID_INT' && $elem['type'] != 'NEWLINE') 
    {
      $value = $row[$elem['name']];
      if ($elem['style'] == 'percent')
        $value = miscRound2Decim($value, 1);
      elseif ($elem['style'] == 'count' || $elem['style'] == 'currency')
        $value = miscRound2Decim($value, 2);
?>
    <td class="<?php echo $elemStyle?>">
      <?php echo htmlFormElement($elem['name'], $elem['type'], $value, $elem['style'], $elem['listquery'], 'NO_MOD', 0, $elem['label'], array(), isset($elem['elem_attributes']) ? $elem['elem_attributes'] : '')?>
    </td>
<?php
    }
    elseif ($elem['type'] == 'HID_INT' || $elem['type'] == 'SECHID_INT') 
    {
      $strHidField = htmlFormElement($elem['name'],"HID_INT", gpcStripSlashes($row[$elem['name']]), $elem['style'], $elem['listquery'], 'NO_MOD', 0, $elem['label']);
      if( $elem['type'] == "HID_INT" ) 
      {
        $strPrimaryName = $elem['name'];
        $intPrimaryId = gpcStripSlashes($row[$elem['name']]);
      }
    }
    elseif ($elem['type'] == 'NEWLINE') 
    {
      $strRowSpan = 'rowspan="2"';
?>
  </tr>
  <tr>
<?php
    }
  }
  $strPopLinkEdit = "#$intPrimaryId";
  $strPopLinkCopy = "#$intPrimaryId";
?>
    <td class="button">
      <a class="tinyactionlink row_edit_button" href="<?php echo $strPopLinkEdit?>"><?php echo $GLOBALS['locEDIT']?></a>
    </td>
    <td class="button">
      <a class="tinyactionlink row_copy_button" href="<?php echo $strPopLinkCopy?>"><?php echo $GLOBALS['locCOPY']?></a>
    </td>
  </tr>
<?php
}

if (isset($showPriceSummary) && $showPriceSummary)
{
  $intTotSum = 0;
  $intTotVAT = 0;
  $intTotSumVAT = 0;
  foreach ($astrExistingValues as $row) 
  {
    $intItemPrice = $row[$priceColumn];
    $intItems = $row[$multiplierColumn]; 
    $intVATPercent = $row[$VATColumn];
    $boolVATIncluded = $row[$VATIncludedColumn];
    
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

    $intTotSum += $intSum;
    $intTotVAT += $intVAT;
    $intTotSumVAT += $intSumVAT;
  }
?>
  <tr class="summary">
    <td class="input" colspan="9" align="right">
      <?php echo $GLOBALS['locTOTALEXCLUDINGVAT']?><br>
      <?php echo $GLOBALS['locTOTALVAT']?><br>
      <?php echo $GLOBALS['locTOTALINCLUDINGVAT']?>
    </td>
    <td class="input" align="right">
      &nbsp;<?php echo miscRound2Decim($intTotSum)?><br>
      &nbsp;<?php echo miscRound2Decim($intTotVAT)?><br>
      &nbsp;<?php echo miscRound2Decim($intTotSumVAT)?>
    </td>
  </tr>
<?php
}

?>
</table>
</form>

<!-- popup editor -->
<div id="popup_edit" style="display: none; width: 900px; overflow: hidden">
<form method="post" action="<?php echo $strMainForm?>" target="_self" name="iform_popup" id="iform_popup">
<input type="hidden" name="<?php echo $strPrimaryKey?>" value="">
<input type="hidden" name="<?php echo $strParentKey?>" value="<?php echo $intParentKey?>">
<input type="hidden" name="saveact" value="0">
<input type="hidden" name="addact" value="0">
<input type="hidden" name="deleteact" value="0">
<table class="iform">
  <tr>
<?php
$strRowSpan = '';
foreach ($astrFormElements as $elem)
{
  if (!in_array($elem['type'], array('HID_INT', 'SECHID_INT', 'BUTTON', 'NEWLINE', 'ROWSUM')))
  {
?>
    <td class="label <?php echo strtolower($elem['style'])?>_label">
      <?php echo $elem['label']?><br>
      <?php echo htmlFormElement($elem['name'], $elem['type'], '', $elem['style'], $elem['listquery'], "MODIFY", 0, '', array(), $elem['elem_attributes'])?>
    </td>
<?php
  }
  elseif( $elem['type'] == 'SECHID_INT' ) 
  {
?>
    <input type="hidden" name="<?php echo $elem['name']?>" value="<?php echo gpcStripSlashes($astrValues[$elem['name']])?>">
<?php
  }
  elseif( $elem['type'] == 'BUTTON' ) 
  {
?>
    <td class="label">
      &nbsp;
    </td>
<?php
  }
}
?>
</table>
</form>
</div>

</body>
</html>
