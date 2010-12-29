<?php
/*******************************************************************************
VLLasku: web-based invoicing application.
Copyright (C) 2010 Ere Maijala

Portions based on:
PkLasku : web-based invoicing software.
Copyright (C) 2004-2008 Samu Reinikainen

This program is free software. See attached LICENSE.

*******************************************************************************/

/*******************************************************************************
VLLasku: web-pohjainen laskutusohjelma.
Copyright (C) 2010 Ere Maijala

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

sesVerifySession();

require_once "localize.php";

$strForm = getPostRequest('selectform', '');

$strDefaults = getPostRequest('defaults', '');
if ($strDefaults) 
{
  $tmpDefaults = explode(" ", urldecode($strDefaults));
  while (list($key, $val) = each($tmpDefaults)) 
  {
    if ($val) 
    {
      $tmpValues = explode(">", $val);
      $astrDefaults[$tmpValues[0]] = $tmpValues[1];
    }
  }
}
else 
{
  $astrDefaults = array();
}
require "form_switch.php";

echo htmlPageStart( _PAGE_TITLE_ );

$blnSave = getPostRequest('saveact', FALSE) ? TRUE : FALSE;
$blnCopy = getPostRequest('copyact', FALSE) ? TRUE : FALSE;
$blnDelete = getPostRequest('deleteact', FALSE) ? TRUE : FALSE;
$intKeyValue = getPostRequest($strPrimaryKey, FALSE);
$intParentKey = getPostRequest($strParentKey, FALSE);

$strOnLoad = '';

foreach ($astrFormElements as $elem) 
{
  if (array_key_exists($elem['name'],$astrDefaults)) 
  {
    $astrValues[$elem['name']] = $astrDefaults[$elem['name']];
  }
  elseif (!$elem['default']) 
  {
    if ($elem['type'] == "INT") 
    {
      $tmpValue = str_replace(",", ".", getPost($elem['name'], ''));
      $astrValues[$elem['name']] = $tmpValue ? (float)$tmpValue : FALSE;
    }
    elseif ($elem['type'] == "LIST") 
    {
      $tmpValue = getPost($elem['name'], '');
      $astrValues[$elem['name']] = $tmpValue !== '' ? $tmpValue : NULL;
    }
    else 
    {
      $astrValues[$elem['name']] = getPost($elem['name'], FALSE);
    }
  }
  else 
  {
    if ($elem['default'] == "DATE_NOW") 
    {
      $strDefaultValue = date("d.m.Y");
    }
    elseif ($elem['default'] == "DATE_NOW+14") 
    {
      $strDefaultValue = date("d.m.Y",mktime(0, 0, 0, date("m"), date("d")+14, date("Y")));
    }
    elseif ($elem['default'] == "DATE_NOW+31") 
    {
      $strDefaultValue = date("d.m.Y",mktime(0, 0, 0, date("m"), date("d")+31, date("Y")));
    }
    elseif ($elem['default'] == "TIME_NOW") 
    {
      $strDefaultValue = date("H:i");
    }
    elseif ($elem['default'] == "POST") 
    {
      $strDefaultValue = getPost($elem['name'], FALSE);
    }
    elseif (strstr($elem['default'], "ADD")) 
    {
      $strQuery = str_replace("_PARENTID_", $intParentKey, $elem['listquery']);
      $intRes = mysql_query_check($strQuery);
      $intAdd = reset(mysql_fetch_row($intRes));
      $strDefaultValue = $intAdd;
    }
    else 
    {
      $strDefaultValue = $elem['default'];
    }
    
    if ($elem['type'] == "INT") 
    {
      $tmpValue = str_replace(",", ".", getPost($elem['name'], ''));
      $astrValues[$elem['name']] = $tmpValue !== '' ? (float)$tmpValue : $strDefaultValue;
    }
    else 
    {
      $astrValues[$elem['name']] = getPost($elem['name'], $strDefaultValue);
    }
  }
}
//save the form values when user hits SAVE
if ($blnSave) 
{ 
  //check all form elements which save values
  $blnMissingValues = FALSE;
  foreach ($astrFormElements as $elem)
  {
    $strControlType = $elem['type'];
    $strControlName = $elem['name'];
    $mixControlValue = $astrValues[$strControlName];
            
    if ($strControlType != 'IFORM' && $strControlType != 'BUTTON' && $strControlType != 'LABEL' && $strControlType != 'HID_INT' && $strControlType != 'ROWSUM' ) 
    {
      if ($strControlType == "INT") 
      {
        if (!isset($mixControlValue) && !$elem['allow_null']) 
        {
          $blnMissingValues = TRUE;
          $strOnLoad .= "alert('".$GLOBALS['locERRVALUEMISSING']." : ".$elem['label']."');";
          error_log('Value missing for field ' . $elem['name']);
        }
      }
      else 
      {
        if (!$mixControlValue && !$elem['allow_null']) 
        {
          $blnMissingValues = TRUE;
          $strOnLoad .= "alert('".$GLOBALS['locERRVALUEMISSING']." : ".$elem['label']."');";
          error_log('Value missing for field ' . $elem['name']);
        }
      }
    }
  }
  //if no required values missing -> create the sql-query fields 
  if (!$blnMissingValues ) 
  {
    $strFields = '';
    $strInsert = '';
    $strUpdateFields = '';
    $arrValues = array();
    foreach ($astrFormElements as $elem)
    {
      $strControlType = $elem['type'];
      $strControlName = $elem['name'];
      $mixControlValue = $astrValues[$strControlName];
      if ($strControlType == 'TEXT' || $strControlType == 'AREA') 
      {
        $strFields .= "$strControlName, ";
        $strInsert .= '?, ';
        $strUpdateFields .= "$strControlName=?, ";
        $arrValues[] = $mixControlValue;
      }
      elseif ($strControlType == 'PASSWD' && $mixControlValue) 
      {
        $strFields .= "$strControlName, ";
        $strInsert .= '?, ';
        $strUpdateFields .= "$strControlName=md5(?), ";
        $arrValues[] = $mixControlValue;
      }
      //elements that are numeric TODO: do we need to save 0(zero)
      elseif ($strControlType == 'INT' || $strControlType == 'LIST') 
      {
        //build the insert into fields
        $strFields .= "$strControlName, ";
        //format the numbers to right format - finnish use ,-separator
        $tmpValue = is_null($mixControlValue) ? NULL : str_replace(",", ".", $mixControlValue);
        $strInsert .= '?, ';
        $strUpdateFields .= "$strControlName=?, ";
        $arrValues[] = $tmpValue;
      }
      //checkboxelements handled bit differently than other int's
      elseif ($strControlType == 'CHECK') 
      {
        $strFields .= "$strControlName, ";
        //if checkbox checked save 1 else 0 TODO: consider TRUE/FALSE
        $tmpValue = $mixControlValue ? 1 : 0;
        $strInsert .= '?, ';
        $strUpdateFields .= "$strControlName=?, ";
        $arrValues[] = $tmpValue;
      }
      //date-elements need own formatting too
      elseif ($strControlType == 'INTDATE')
      {
        if (!$mixControlValue) 
        {
            $mixControlValue = 'NULL';
        }
        $strFields .= "$strControlName, ";
        $strInsert .= '?, ';
        //convert user input to right format
        $strUpdateFields .= "$strControlName=?, ";
        $arrValues[] = dateConvDate2IntDate($mixControlValue);
      }
      //time-elements need own formatting too
      elseif ($strControlType == 'TIME') 
      {
        $astrSearch = array('.', ',', ' ');
        $strFields .= "$strControlName, ";
        $strInsert .= '?, ';
        //convert user input to right format
        $strUpdateFields .= "$strControlName=?, ";
        $arrValues[] = str_replace($astrSearch, ":", $mixControlValue);
      }
    }
    
    //subtract last loops' unnecessary ', '-parts 
    $strInsert = substr($strInsert, 0, -2);
    $strFields = substr($strFields, 0, -2);
    $strUpdateFields = substr($strUpdateFields, 0, -2);
    if ($blnCopy) 
    {
      $strQuery = "INSERT INTO $strTable ($strFields, $strParentKey) VALUES ($strInsert, ?)";
      $arrValues[] = $intParentKey;
    }
    else 
    {
      $strQuery = "UPDATE $strTable SET $strUpdateFields, deleted=0 WHERE $strPrimaryKey=?";
      $arrValues[] = $intKeyValue;
    }
    
    mysql_param_query($strQuery, $arrValues);
    $blnUpdateDone = TRUE;
    $strOnLoad = "parent.document.forms[0].submit();";
  }
}

if ($blnDelete && $intKeyValue) 
{
  $strQuery = "UPDATE $strTable SET deleted=1 WHERE $strPrimaryKey=?";
  mysql_param_query($strQuery, array($intKeyValue));
  //dispose the primarykey value
  $intKeyValue = '';
  //clear form elements
  unset($astrValues);
  $blnNew = TRUE;
  $strOnLoad = "parent.document.forms[0].submit();";
}

if ($intKeyValue) 
{
  $strQuery = "SELECT * FROM $strTable WHERE $strPrimaryKey=?";
  $intRes = mysql_param_query($strQuery, array($intKeyValue));
  $row = mysql_fetch_assoc($intRes);
  if ($row) 
  {
    foreach ($astrFormElements as $elem) 
    {
      $strControlType = $elem['type'];
      $strControlName = $elem['name'];
      
      if ($strControlType == 'IFORM' || $strControlType == 'RESULT') 
      {
        $astrValues[$strControlName] = $intKeyValue;
        if (isset($elem['defaults']) && is_array($elem['defaults'])) 
        {
          $strDefaults = "defaults=";
          while (list($key, $val) = each($elem['defaults'])) 
          {
            if($elem['types'][$key] == 'INT' ) 
            {
              $elem['defaults'][$key] = $astrValues[$elem['mapping'][$key]];
            }
            elseif( $elem['types'][$key] == 'INTDATE' ) 
            {
              $elem['defaults'][$key] = dateConvDate2IntDate($astrValues[$elem['mapping'][$key]]);
            }
          }
        }
      }
      elseif ($strControlType == 'BUTTON') 
      {
        if (strstr($elem['listquery'], "=_ID_")) 
        {
          $astrValues[$strControlName] = $intKeyValue ? $intKeyValue : FALSE;
        }
        else 
        {
          $tmpListQuery = $elem['listquery'];
          $strReplName = substr($tmpListQuery, strpos($tmpListQuery, "_"));
          $strReplName = strtolower(substr($strReplName, 1, strrpos($strReplName, "_")-1));
          $astrValues[$strControlName] = isset($astrValues[$strReplName]) ? $astrValues[$strReplName] : '';
          $elem['listquery'] = str_replace(strtoupper($strReplName), "ID", $elem['listquery']);
        }
      }
      elseif ($strControlType != 'LABEL' && $strControlType != 'ROWSUM') 
      {
        if( $strControlType == 'INTDATE' ) 
        {
          $astrValues[$strControlName] = dateConvIntDate2Date($row[$strControlName]);
        }
        else 
        { 
          if ($strControlName)
            $astrValues[$strControlName] = $row[$strControlName];
        }
      }
    }
  }
  else 
  {
    echo $GLOBALS['locENTRYDELETED']; die;
  }
}

?>
<body class="iform_pop" onload="<?php echo $strOnLoad?>">
<script type="text/javascript">
<!--
$(function() {
  $('input[class~="hasCalendar"]').datepicker();
});
-->
</script>
<form method="post" action="iform_pop.php?selectform=<?php echo $strForm?>" target="_self" name="pop_iform" id="pop_iform">
<?php
if (!$blnCopy) {
    echo "<input type=\"hidden\" name=\"{$strPrimaryKey}\" value=\"{$intKeyValue}\">\n";
}
?>
<input type="hidden" name="<?php echo $strParentKey?>" value="<?php echo $intParentKey?>">
<input type="hidden" name="defaults" value="<?php echo $strDefaults?>">
<table class="iform">
    <tr>
<?php

foreach ($astrFormElements as $elem) 
{
  if ($elem['type'] != "HID_INT" && $elem['type'] != "SECHID_INT" && $elem['type'] != "BUTTON" && $elem['type'] != 'NEWLINE' && $elem['type'] != 'ROWSUM') 
  {
    $value = isset($astrValues[$elem['name']]) ? $astrValues[$elem['name']] : '';
    if ($elem['style'] == 'percent')
      $value = miscRound2Decim($value, 1);
    elseif ($elem['style'] == 'count' || $elem['style'] == 'currency')
      $value = miscRound2Decim($value, 2);
?>
    <td class="label">
        <?php echo $elem['label']?><br>
        <?php echo htmlFormElement( $elem['name'],$elem['type'], $value, $elem['style'],$elem['listquery'])?>
    </td>
<?php
    }
    elseif( $elem['type'] == 'SECHID_INT' ) {
?>
    <?php echo htmlFormElement( $elem['name'],"HID_INT", $astrValues[$elem['name']], $elem['style'],$elem['listquery'])?>
<?php
    }
    elseif( $elem['type'] == 'BUTTON' ) {
?>
    <td class="label">
        &nbsp;
    </td>
<?php
    }
    elseif( $elem['type'] == 'NEWLINE' ) {
        $strRowSpan = "rowspan=\"2\"";
?>
</tr>
<tr>
<?php
    }
}
?>
</tr>
</table>
<input type="hidden" name="saveact" value="0">
<input type="hidden" name="copyact" value="<?php echo $blnCopy?>">
<input type="hidden" name="deleteact" value="0">

</form>
</body>
</html>
