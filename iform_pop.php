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
require_once 'form_funcs.php';

sesVerifySession();

require_once "localize.php";

$strForm = getPostRequest('selectform', '');

require "form_switch.php";

echo htmlPageStart( _PAGE_TITLE_ );

$blnSave = getPostRequest('saveact', FALSE) ? TRUE : FALSE;
$blnCopy = getPostRequest('copyact', FALSE) ? TRUE : FALSE;
$blnDelete = getPostRequest('deleteact', FALSE) ? TRUE : FALSE;
$intKeyValue = getPostRequest($strPrimaryKey, FALSE);
$intParentKey = getPostRequest($strParentKey, FALSE);

$strOnLoad = '';

$astrValues = getPostValues($astrFormElements, $intKeyValue, $intParentKey);

//save the form values when user hits SAVE
if ($blnSave) 
{ 
  if ($blnCopy)
    unset($intKeyValue);
  $res = saveFormData($strTable, $intKeyValue, $astrFormElements, $astrValues, $strParentKey, $intParentKey);
  if ($res !== TRUE)
  {
    $strOnLoad .= "alert('" . $GLOBALS['locERRVALUEMISSING'] . ": $res');";
  }
  else
  {
    $strOnLoad = "parent.document.forms[0].submit();";
  }
}

if ($blnDelete && $intKeyValue) 
{
  $strQuery = "UPDATE $strTable SET deleted=1 WHERE $strPrimaryKey=?";
  mysql_param_query($strQuery, array($intKeyValue));
  $intKeyValue = '';
  unset($astrValues);
  $blnNew = TRUE;
  $strOnLoad = "parent.document.forms[0].submit();";
}

if ($intKeyValue) 
{
  $res = fetchRecord($strTable, &$intKeyValue, &$astrFormElements, &$astrValues);
  if ($res === 'notfound')
  {
    echo $GLOBALS['locENTRYDELETED']; 
    die;
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
<table class="iform">
    <tr>
<?php

foreach ($astrFormElements as $elem) 
{
  if (!in_array($elem['type'], array('HID_INT', 'SECHID_INT', 'BUTTON', 'NEWLINE', 'ROWSUM')))
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
  elseif ($elem['type'] == 'SECHID_INT') 
  {
?>
    <?php echo htmlFormElement( $elem['name'],"HID_INT", $astrValues[$elem['name']], $elem['style'],$elem['listquery'])?>
<?php
  }
  elseif ($elem['type'] == 'BUTTON') 
  {
?>
    <td class="label">
        &nbsp;
    </td>
<?php
  }
  elseif ($elem['type'] == 'NEWLINE') 
  {
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
