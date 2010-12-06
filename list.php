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

function createList($strList, $strFunc)
{
  $strWhereClause = getPostRequest('where', '');
  $strSearchTerms = trim(getPostRequest('searchterms', ''));
  $intID = getRequest('id', FALSE);
  
  require "list_switch.php";
  
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
    $strTitle = "<b>$strTitleOverride</b><br><br>";
  else
    $strTitle = '';
  if (!$strNoEntries)
    $strNoEntries = $GLOBALS['locNOENTRIES'];

  $astrListValues = array(array());
  
  $strSelectClause = "$strPrimaryKey,";
  for( $j = 0; $j < count($astrShowFields); $j++ ) 
  {
    $strSelectClause .= $astrShowFields[$j]['name'] . ",";
  }
  $strSelectClause = substr($strSelectClause, 0, -1);
  $strQuery =
    "SELECT $strSelectClause FROM $strTable ".
    "WHERE $strPrimaryKey IN ($strIDQuery) ";
  $intRes = mysql_param_query($strQuery, $arrQueryParams);
  $intNRes = mysql_num_rows($intRes);
  if ($intNRes == 0)
  {
?>
  <div class="list_container">
    <?php echo $strTitle?>
    <b><?php echo $strNoEntries?></b>
    <br>
    <br>
  </div>
<?php
    return;
  }
  
  $i = 0;
  for( $i = 0; $i < $intNRes; $i++ ) 
  {
    $astrPrimaryKeys[$i] = mysql_result($intRes, $i, $strPrimaryKey);
    for( $j = 0; $j < count($astrShowFields); $j++ ) 
    {
      if( $astrShowFields[$j]['type'] == "TEXT" || $astrShowFields[$j]['type'] == "INT" ) 
      {
        $astrListValues[$i][$j] = mysql_result($intRes, $i, $astrShowFields[$j]['name']);
      }
      elseif( $astrShowFields[$j]['type'] == "INTDATE" ) 
      {
        $astrListValues[$i][$j] = 
          dateConvIntDate2Date( mysql_result($intRes, $i, $astrShowFields[$j]['name']) );
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
      "sPaginationType": "full_numbers",
      "aoColumns": [
<?php
        for ($i = 0; $i < count($astrShowFields); $i++ ) 
        {
          echo '        { "sType": "html-multi" },' . "\n";
        }?>
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
  for( $j = 0; $j < count($astrShowFields); $j++ ) 
  {
    $strWidth = isset($astrShowFields[$j]['width']) ? (' width="' . $astrShowFields[$j]['width'] . '"') : '';
?>
          <th class="label"<?php echo $strWidth?>><?php echo $astrShowFields[$j]['header']?></th>
<?php
  }
?>
        </tr>
      </thead>
      <tbody>
<?php
  for( $i = 0; $i < count($astrListValues); $i++ ) 
  {
    $strLink = '?ses=' . $GLOBALS['sesID'] . "&amp;func=$strFunc&amp;list=$strList&amp;form=$strMainForm&amp;";
    $strLink .= 'id=' . $astrPrimaryKeys[$i];
?>
  
        <tr class="listrow">
<?php
    for( $j = 0; $j < count($astrListValues[$i]); $j++ ) 
    {
?>
          <td class="label"><a class="navilink" href="<?php echo $strLink?>"><?php echo $astrListValues[$i][$j] ? $astrListValues[$i][$j] : '&nbsp;'?></a></td>
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
