<?php
/*******************************************************************************
PkLasku : web-based invoicing software.
Copyright (C) 2004-2008 Samu Reinikainen

This program is free software. See attached LICENSE.

*******************************************************************************/

/*******************************************************************************
PkLasku : web-pohjainen laskutusohjelmisto.
Copyright (C) 2004-2008 Samu Reinikainen

Tämä ohjelma on vapaa. Lue oheinen LICENSE.

*******************************************************************************/

require_once "sqlfuncs.php";
require_once "miscfuncs.php";
require_once "datefuncs.php";
require_once "localize.php";

function createList($strFunc, $strList)
{
  $strWhereClause = getPostRequest('where', '');
  $strSearchTerms = trim(getPostRequest('searchterms', ''));
  $intID = getRequest('id', FALSE);
  
  require "list_switch.php";
  
  if (!in_array($_SESSION['sesACCESSLEVEL'], $levelsAllowed) && $_SESSION['sesACCESSLEVEL'] != 99 )
  {
?>
  <div class="form_container">
    <?php echo $GLOBALS['locNOACCESS'] . "\n"?>
  </div>
<?php
    return;
  }

  if (!$strTable)
    return;
  
  $arrQueryParams = array();
  if( $strWhereClause ) {
      $strWhereClause = "WHERE " . gpcStripSlashes(urldecode($strWhereClause));
      $strWhereClause = str_replace("%-", "%", $strWhereClause);
  }
  elseif( $strSearchTerms == "*"  && !$intID ) {
      $strWhereClause = "WHERE " . $strPrimaryKey . " IS NOT NULL ";
  }
  elseif( !$strSearchTerms && !$intID ) {
      $strWhereClause = "WHERE " . $strPrimaryKey . " IS NOT NULL ";
      $strOrderClause2 = " " . $strPrimaryKey . " DESC ";
  }
  else {
      $astrTerms = explode(" ",$strSearchTerms);
      $strWhereClause = "WHERE ";
      for( $i = 0; $i < count($astrTerms); $i++ ) {
          if( $astrTerms[$i] || $intID ) {
              $strWhereClause .= '(';
              for( $j = 0; $j < count($astrSearchFields); $j++ ) {
                  if( $astrSearchFields[$j]['type'] == "TEXT" ) {
                      $strWhereClause .= $astrSearchFields[$j]['name'] . " LIKE ? OR ";
                      $arrQueryParams[] = '%' . $astrTerms[$i] . '%';
                  }
                  elseif( $astrSearchFields[$j]['type'] == "INT" && preg_match ("/^([0-9]+)$/", $astrTerms[$i]) ) {
                      $strWhereClause .= $astrSearchFields[$j]['name'] . " = ?" . " OR ";
                      $arrQueryParams[] = $astrTerms[$i];
                  }
                  elseif( $astrSearchFields[$j]['type'] == "PRIMARY" && preg_match ("/^([0-9]+)$/", $intID) ) {
                      $strWhereClause = 
                          "WHERE ". $astrSearchFields[$j]['name']. " = ?     ";
                      $arrQueryParams = array($intID);
                      unset($astrSearchFields);
                      break 2;
                  }
                  
              }
              $strWhereClause = substr( $strWhereClause, 0, -3) . ") AND ";
          }
      }
      $strWhereClause = substr( $strWhereClause, 0, -4);
  }
      
  if ($strFilter)
  {
    if ($strWhereClause)
      $strWhereClause .= " AND $strFilter";
    else
      $strWhereClause = " WHERE $strFilter";
  }
  
  if (!getSetting('show_deleted_records'))
  {
    if ($strWhereClause)
      $strWhereClause .= " AND $strDeletedField=0";
    else
      $strWhereClause = " WHERE $strDeletedField=0";
  }
  
  $strQuery = 
    "SELECT $strPrimaryKey FROM $strTable $strWhereClause"; 

  createHtmlList($strFunc, $strList, $strQuery, $arrQueryParams);
}

function createHtmlList($strFunc, $strList, $strIDQuery, &$arrQueryParams, $strTitleOverride = '', $strNoEntries = '', $strTableName = '')
{
  require 'list_switch.php';

  if (!$strTableName)
    $strTableName = "resultlist_$strMainForm";
  
  if ($strTitleOverride)
    $strTitle = "<strong>$strTitleOverride</strong><br><br>";
  else
    $strTitle = '';
  if (!$strNoEntries)
    $strNoEntries = $GLOBALS['locNOENTRIES'];

  $astrListValues = array(array());
  
  $strSelectClause = "$strPrimaryKey,$strDeletedField";
  foreach ($astrShowFields as $field) 
  {
    $strSelectClause .= ',' . $field['name'];
  }
  $strQuery =
    "SELECT $strSelectClause FROM $strTable ".
    "WHERE $strPrimaryKey IN ($strIDQuery) ";

  $intRes = mysql_param_query($strQuery, $arrQueryParams);
  if (mysql_num_rows($intRes) == 0)
  {
?>
  <div class="list_container">
    <?php echo $strTitle?>
    <?php echo $strNoEntries?>
    <br>
    <br>
  </div>
<?php
    return;
  }
  
  $i = -1;
  while ($row = mysql_fetch_prefixed_assoc($intRes)) 
  {
    ++$i;
    $astrPrimaryKeys[$i] = $row[$strPrimaryKey];
    $aboolDeleted[$i] = $row[$strDeletedField];
    foreach ($astrShowFields as $field) 
    { 
      $name = $field['name'];
      if ($field['type'] == 'TEXT' || $field['type'] == 'INT') 
      {
        $value = $row[$name];
        if (isset($field['mappings']) && isset($field['mappings'][$value]))
          $value = $field['mappings'][$value];  
        $astrListValues[$i][$name] = $value;
      }
      elseif ($field['type'] == 'INTDATE') 
      {
        $astrListValues[$i][$name] = dateConvIntDate2Date($row[$name]);
      }
    }
  }

?>
  <script type="text/javascript">
  
  $(document).ready(function() {
    $('#<?php echo $strTableName?>').dataTable( {
      "oLanguage": {
        <?php echo $GLOBALS['locTABLETEXTS']?> 
      },
      "bStateSave": true,
      "bJQueryUI": true,
      "sPaginationType": "full_numbers",
      "aoColumnDefs": [
        { "aTargets": ["_all"], "sType": "html-multi", }
      ]
    }
  );
  });
  </script>
  
  <div class="list_container">
    <?php echo $strTitle?>
    <table id="<?php echo $strTableName?>" class="list">
      <thead>
        <tr>
<?php
  foreach ($astrShowFields as $field) 
  {
    $strWidth = isset($field['width']) ? (' style="width: ' . $field['width'] . 'px"') : '';
?>
          <th <?php echo $strWidth?>><?php echo $field['header']?></th>
<?php
  }
?>
        </tr>
      </thead>
      <tbody>
<?php
  for ($i = 0; $i < count($astrListValues); $i++) 
  {
    $strLink = "?func=$strFunc&amp;list=$strList&amp;form=$strMainForm&amp;id=" . $astrPrimaryKeys[$i];
    $deleted = $aboolDeleted[$i] ? ' deleted' : '';
?>
  
        <tr class="listrow">
<?php
    foreach ($astrShowFields as $field) 
    {
?>
          <td class="label<?php echo $deleted?>"><a class="navilink" href="<?php echo $strLink?>"><?php echo trim($astrListValues[$i][$field['name']]) ? htmlspecialchars($astrListValues[$i][$field['name']]) : '&nbsp;'?></a></td>
<?php
    }
?>
        </tr>
  
<?php
  }
  $strLink = '?' . str_replace('&', '&amp;', $_SERVER['QUERY_STRING']) . "&amp;form=$strMainForm";
?>
      </tbody>
    </table>
    <br>
    <br>
<?php
?>
  </div>
<?php
}
?>
