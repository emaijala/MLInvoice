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
  $strKeyValues = getPost('key_values', '');
  $astrKeyValues = $strKeyValues ? explode("\t", $strKeyValues) : NULL;
  $intPage = getRequest('page', 1);
  $intID = getRequest('id', FALSE);
  
  require "list_switch.php";
  
  if (!$strTable)
    return;
  
  $arrQueryParams = array();
  if( !$astrKeyValues ) {
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
      if( $strOrderClause2 ) {
          $strOrderClause = $strOrderClause2;
      }
      else {
          for( $j = 0; $j < count($astrShowFields); $j++ ) {
              $strOrderClause .= $strTable. ".". $astrShowFields[$j]['name'] . " ASC, ";
          }
          $strOrderClause = substr( $strOrderClause, 0, -2);
      }
      $strQuery = 
          "SELECT $strPrimaryKey FROM $strTable " .
          $strWhereClause . 
          " ORDER BY $strOrderClause";
  
      $intRes = mysql_param_query($strQuery, $arrQueryParams);
      if( $intRes ) {
          $intTotal = mysql_num_rows($intRes);
          for( $i = 0; $i < $intTotal; $i++ ) {
              $astrKeyValues[$i] = mysql_result($intRes, $i, 0);
          }
      }
  }
  //echo $strQuery;
  
  createHtmlList($strFunc, $strList, $astrKeyValues, $intTotal);
}

function createHtmlList($strFunc, $strList, $astrKeyValues, $intTotal, $strTitleOverride = '')
{
  // TODO: a better architecture
  require 'list_switch.php';
  
  if (!isset($GLOBALS['TABLENO']))
    $GLOBALS['TABLENO'] = 1;
  else
    ++$GLOBALS['TABLENO'];
  
  if ($strTitleOverride)
    $strTitle = $strTitleOverride;

  $astrListValues = array(array());
  if( count($astrKeyValues) > 0 ) {
      $intTotal = count($astrKeyValues);
      $intLimit = _NAVI_LIST_ROWS_; //how many results to show on page
      if( $intLimit > 0 && $intTotal > $intLimit ) {
          $intEnd = $intLimit * $intPage;
          if( $intEnd > $intTotal ) {
              $intEnd = $intTotal;
              $intStart = $intEnd - ($intLimit - ($intLimit * $intPage - $intTotal));
          }
          else {
              $intStart = $intEnd - $intLimit;
          }
          
      }
      else {
          $intEnd = $intTotal;
          $intStart = 0;
      }
      //echo $intStart . " - " . $intEnd . " / " . $intTotal;
      $arrKeysIn = array();
      for( $i = $intStart; $i < $intEnd; $i++ ) {
          $arrKeysIn[] = $astrKeyValues[$i];
      }
      $strSelectClause = $strPrimaryKey .",";
      $strOrderClause = "";
      for( $j = 0; $j < count($astrShowFields); $j++ ) {
          $strOrder = isset($astrShowFields[$j]['order']) ? $astrShowFields[$j]['order'] : "ASC";
          $strSelectClause .= $astrShowFields[$j]['name'] . ",";
          $strOrderClause .= $astrShowFields[$j]['name'] . " $strOrder, ";
      }
      $strSelectClause = substr($strSelectClause, 0, -1);
      if( $strOrderClause2 ) {
          $strOrderClause = $strOrderClause2;
      }
      else {
          $strOrderClause = substr($strOrderClause, 0, -2);
      }
      $strQuery =
          "SELECT $strSelectClause FROM $strTable ".
          "WHERE $strPrimaryKey IN (?) ".
          "ORDER BY $strOrderClause";
      $intRes = mysql_param_query($strQuery, array($arrKeysIn));
      $intNRes = mysql_num_rows($intRes);
      for( $i = 0; $i < $intNRes; $i++ ) {
          $astrPrimaryKeys[$i] = mysql_result($intRes, $i, $strPrimaryKey);
          for( $j = 0; $j < count($astrShowFields); $j++ ) {
              if( $astrShowFields[$j]['type'] == "TEXT" || $astrShowFields[$j]['type'] == "INT" ) {
                      $astrListValues[$i][$j] = mysql_result($intRes, $i, $astrShowFields[$j]['name']);
              }
              elseif( $astrShowFields[$j]['type'] == "INTDATE" ) {
                      $astrListValues[$i][$j] = 
                        dateConvIntDate2Date( mysql_result($intRes, $i, $astrShowFields[$j]['name']) );
              }
              
          }
      }
      $strKeyValues = implode("\t", $astrKeyValues);
  }

  if( $intTotal == 1 ) {
      $strCounter = "1 / 1";
  }
  else {
      $strCounter = ($intStart + 1) . " - $intEnd / $intTotal";
  }
  if( count($astrListValues) > 0 ) {
    if (_NAVI_LIST_ROWS_ == 0) {
  ?>
<script type="text/javascript">

function sort_multi(a,b) 
{
  a = a.replace( /<.*?>/g, "" );
  b = b.replace( /<.*?>/g, "" );
  var float_re = /^\d+\.?\d*$/;
  var date_re = /^(\d{1,2})\.(\d{1,2})\.(\d{4})$/;
  if (a.match(float_re) && b.match(float_re))
  {
    a = parseFloat(a);
    b = parseFloat(b);
    return ((a < b) ? -1 : ((a > b) ?  1 : 0));
  }
  var am = a.match(date_re);
  var bm = b.match(date_re);
  if (am && bm)
  {
    ad = am[2] + '.' + am[1] + '.' + am[0];
    bd = bm[2] + '.' + bm[1] + '.' + bm[0];
    return ((ad < bd) ? -1 : ((ad > bd) ?  1 : 0));
  }
  a = a.toLowerCase();
  b = b.toLowerCase();
  return ((a < b) ? -1 : ((a > b) ?  1 : 0));
};
 
jQuery.fn.dataTableExt.oSort['html-multi-asc']  = function(a,b) {
	return sort_multi(a, b);
};

jQuery.fn.dataTableExt.oSort['html-multi-desc'] = function(a,b) {
	return -sort_multi(a, b);
};

$(document).ready(function() {
  $('#resultlist<?php echo $GLOBALS['TABLENO']?>').dataTable( {
    "oLanguage": {
      <?php echo $GLOBALS['locTABLETEXTS']?> 
		},
		"sPaginationType": "full_numbers",
    "aoColumns": [
<?php
			for ($i = 0; $i < count($astrShowFields); $i++ ) {
        echo '      { "sType": "html-multi" },' . "\n";
      }?>
    ]
  }
);
});
  </script>
  <?php
    }
?>
  <div class="list_container">
  <b><?php echo $strTitle?>: </b>
  <?php 
  if (_NAVI_LIST_ROWS_ > 0)
  {
  ?>
  <table>
    
      <tr>
          <td align="left">
  <?php
  $strPage = '?' . preg_replace('/&page=\d*/', '', $_SERVER['QUERY_STRING']);
  $strPrevPage = "$strPage&amp;page=" . ($intPage - 1); 
  $strNextPage = "$strPage&amp;page=" . ($intPage + 1); 
  if( $intPage > 1 ) {
  ?>
              <a class="tinyactionlink" href="<?php echo $strPrevPage?>"> < </a>
  <?php
  }
  else {
  ?>
      &nbsp;
  <?php
  }
  ?>
          </td>
          <td align="center">
              <?php echo $strCounter?>
          </td>
          <td align="right">
  <?php
  if( $intEnd != $intTotal ) {
  ?>        
              <a class="tinyactionlink" href="<?php echo $strNextPage?>"> > </a>
  <?php
  }
  else {
  ?>
      &nbsp;
  <?php
  }
  ?>
          </td>
      </tr>
  </form>
  </table>
  <?php
  }
  else
    echo "<br><br>";
  ?>
  <table id="resultlist<?php echo $GLOBALS['TABLENO']?>" class="list">
    <thead>
      <tr>
  <?php
  for( $j = 0; $j < count($astrShowFields); $j++ ) {
    $strWidth = isset($astrShowFields[$j]['width']) ? (' width="' . $astrShowFields[$j]['width'] . '"') : '';
  ?>
          <th class="label"<?php echo $strWidth?>>
              <?php echo $astrShowFields[$j]['header']?>
          </th>
  <?php
  }
  ?>
      </tr>
    </thead>
    <tbody>
  <?php
      for( $i = 0; $i < count($astrListValues); $i++ ) {
          $strLink = '?ses=' . $GLOBALS['sesID'] . "&amp;func=$strFunc&amp;list=$strList&amp;form=$strMainForm&amp;";
          $strLink .= 'id=' . $astrPrimaryKeys[$i];
  ?>
  
      <tr class="listrow">
  <?php
      for( $j = 0; $j < count($astrListValues[$i]); $j++ ) {
  ?>
          <td class="label"><a class="navilink" href="<?php echo $strLink?>"><?php echo $astrListValues[$i][$j]?></a></td>
  <?php
      }
  ?>
      </tr>
  
  <?php
      }
      $strLink = '?' . str_replace('&', '&amp;', $_SERVER['QUERY_STRING']) . "&amp;form=$strMainForm&amp;new=1";
  ?>
    </tbody>
  </table>
  <div style="float: left; margin-left: 60px; margin-top: 3px">
    <a class="actionlink" href="<?php echo $strLink?>"><?php echo $GLOBALS['locNEW']?></a>
    <a class="actionlink" href="#" onclick="window.open('help.php?ses=<?php echo $GLOBALS['sesID']?>&amp;topic=list', '_blank', 'height=400,width=400,menubar=no,scrollbars=yes,status=no,toolbar=no'); return false;"><?php echo $GLOBALS['locHELP']?></a>
  </div>
  <br>
  <br>
  <?php
  }
  else {
      $strLink = '?' . str_replace('&', '&amp;', $_SERVER['QUERY_STRING']) . "&amp;form=$strMainForm&amp;new=1";
  ?>
  <b><?php echo $strTitle?> :</b>
  <b><?php echo $GLOBALS['locNOENTRIES']?></b><br><br>
  <a class="actionlink" href="<?php echo $strLink?>"><?php echo $GLOBALS['locNEW']?></a>
  <a class="actionlink" href="#" onclick="window.open('help.php?ses=<?php echo $GLOBALS['sesID']?>&amp;topic=list', '_blank', 'height=400,width=400,menubar=no,scrollbars=yes,status=no,toolbar=no'); return false;"><?php echo $GLOBALS['locHELP']?></a>
  <?php
  }
  ?>
  </div>
  <?php
}
?>
