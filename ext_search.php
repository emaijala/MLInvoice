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

$strSesID = sesVerifySession();

require "localize.php";

$strFunc = getRequest('func', '');
$strForm = getRequest('form', '');


$blnSearch = getPost('search_x', FALSE) ? TRUE : FALSE;
$blnSave = getPost('save_x', FALSE) ? TRUE : FALSE;
$strFields = getPost('fields', FALSE);
$strSearchField = getPost('searchfield', FALSE);
$strSearchName = getPost('searchname', '');
if( $strSearchField !== FALSE ) {
    if( $strFields === FALSE ) {
        $strFields = $strSearchField;
    }
    else {
        $strFields .= ",". $strSearchField;
    }
}

if( $strFields !== FALSE ) {
    $astrSelectedFields = explode(",", $strFields);
}
else {
    $astrSelectedFields = array();
}

require "form_switch.php";

for( $j = 0; $j < count($astrSelectedFields); $j++ ) {
    $tmpDelete = getPost('delete_' . $astrSelectedFields[$j] . '_x', FALSE);
    if( $tmpDelete ) {
        $astrSelectedFields[$j] = '';
    }
}

$strFields = implode(",", $astrSelectedFields);

for( $j = 0; $j < count($astrFormElements); $j++ ) {
    if( $astrFormElements[$j]['type'] == 'RESULT' && $astrFormElements[$j]['name'] != '' ) {
        $astrFormElements[$j]['type'] = 'TEXT';
    }
}

$x = 0;
for( $j = 0; $j < count($astrFormElements); $j++ ) {
    $strSelectedOperator = getPost('operator_'.$astrFormElements[$j]['name'], 'OR');
    if($astrFormElements[$j]['type'] != '' && $astrFormElements[$j]['type'] != "LABEL" && $astrFormElements[$j]['type'] != "HIDINT" && $astrFormElements[$j]['type'] != 'IFORM' && $astrFormElements[$j]['type'] != 'BUTTON' && !in_array($astrFormElements[$j]['name'], $astrSelectedFields, true)) {
        $astrListValues[$x] = $astrFormElements[$j]['name'];
        $astrListOptions[$x] = str_replace("<br>", " ", $astrFormElements[$j]['label']); 
        $x++;
    }
    $strControlType = $astrFormElements[$j]['type'];
    $strControlName = $astrFormElements[$j]['name'];
    
    if( $strControlType == 'IFORM' || $strControlType == 'BUTTON' ) {
       $astrValues[$strControlName] = '';
    }
    elseif( $strControlType != 'LABEL' ) {
        if( $strControlType == 'INTDATE' ) {
            $astrValues[$strControlName] = getPost($strControlName, '');
        }
        else { 
            $astrValues[$strControlName] = getPost($strControlName, '');
        }
    }
}
$strListBox = htmlListBox( "searchfield", $astrListValues, $astrListOptions, FALSE, "", 1 );

$astrListValues = array('=','!=','<','>');
$astrListOptions = array('on yhtä kuin','on eri kuin','on pienempi kuin','on suurempi kuin');

$strOnLoad = '';
if( $blnSearch || $blnSave ) {
    for( $j = 0; $j < count($astrFormElements); $j++ ) {
        if(in_array($astrFormElements[$j]['name'], $astrSelectedFields, true)) {
            $strSearchMatch =
                getPost('searchmatch_' . $astrFormElements[$j]['name'], '=');
            $strSearchValue = $astrValues[$astrFormElements[$j]['name']];
            //do LIKE || NOT LIKE search to elements with text or varchar datatype
            if( $astrFormElements[$j]['type'] == 'TEXT' || $astrFormElements[$j]['type'] == 'AREA' ) {
                if( $strSearchMatch == "=" ) {
                    $strSearchMatch = "LIKE";
                }
                else {
                    $strSearchMatch = "NOT LIKE";
                }
                //add to the where clause
                $strWhereClause .= $strListTableAlias . $astrFormElements[$j]['name']. " ". $strSearchMatch. " '%-". $strSearchValue. "%-' $strSelectedOperator ";
            }
            //do =, !=, < or > search to elements that are numeric
            elseif( $astrFormElements[$j]['type'] == 'INT' || $astrFormElements[$j]['type'] == 'LIST' ) {
                //add to the where clause
                $strWhereClause .= $strListTableAlias . $astrFormElements[$j]['name']. " ". $strSearchMatch. " ". $strSearchValue. " $strSelectedOperator ";
            }
            //checkboxelements handled bit differently than other int's
            elseif( $astrFormElements[$j]['type'] == 'CHECK' ) {
                $tmpValue = $astrValues[$astrFormElements[$j]['name']] ? 1 : 0;
                //add to the where clause
                $strWhereClause .= $strListTableAlias . $astrFormElements[$j]['name']. " ". $strSearchMatch. " ". $tmpValue. " $strSelectedOperator ";
            }
            //date-elements need own formatting too
            elseif( $astrFormElements[$j]['type'] == 'INTDATE' ) {
                $tmpValue = dateConvDate2IntDate($astrValues[$astrFormElements[$j]['name']]);
                //add to the where clause
                $strWhereClause .= $strListTableAlias . $astrFormElements[$j]['name']. " ". $strSearchMatch. " ". $tmpValue. " $strSelectedOperator ";
            }
        }
    }
    
    $strWhereClause = substr( $strWhereClause, 0, -4);
    $strWhereClause = urlencode($strWhereClause);
    if( $blnSearch ) {
        $strLink = "index.php?ses=". $GLOBALS['sesID']."&func=$strFunc&selectform=$strForm&where=$strWhereClause";
        $strOnLoad = "opener.location.href='$strLink'";
    }
    
    if( $blnSave && $strSearchName ) {
        $strQuery = 
            'INSERT INTO {prefix}quicksearch(user_id, name, form, whereclause) '.
            'VALUES (?, ?, ?, ?)';
        $intRes = mysql_param_query($strQuery, array($GLOBALS['sesUSERID'], $strSearchName, $strForm, $strWhereClause));
    }
    elseif( $blnSave && !$strSearchName) {
        $strOnLoad = "alert('".$GLOBALS['locERRORNOSEARCHNAME']."')";
    }
}
echo htmlPageStart( _PAGE_TITLE_ );
?>
<script type="text/javascript">
<!--
$(function() {
  $('input[class~="hasCalendar"]').datepicker();
});
-->
</script>

<body class="form" onload="<?php echo $strOnLoad?>">
<form method="post" action="ext_search.php?ses=<?php echo $GLOBALS['sesID']?>&amp;func=<?php echo $strFunc?>&amp;form=<?php echo $strForm?>" target="_self" name="search_form">
<input type="hidden" name="fields" value="<?php echo $strFields?>">
<table>
<tr>
    <td class="sublabel" colspan="4">
    <?php echo $GLOBALS['locLABELEXTSEARCH']?>
    </td>
</tr>
<tr>
    <td class="label">
    <?php echo $GLOBALS['locSELECTSEARCHFIELD']?>
    </td>
    <td class="field">
    <?php echo $strListBox?>
    </td>
    <td class="label">
    <?php echo $GLOBALS['locSEARCHNAME']?>
    </td>
    <td class="field">
    <input class="medium" type="text" name="searchname" value="<?php echo $strSearchName?>"> 
    </td>
</tr>
</table>
<table>
<tr>
    <td class="sublabel">
    <?php echo $GLOBALS['locSEARCHFIELD']?>
    </td>
    <td class="sublabel">
    <?php echo $GLOBALS['locSEARCHMATCH']?>
    </td>
    <td class="sublabel">
    <?php echo $GLOBALS['locSEARCHTERM']?>
    </td>
</tr>
<?php

for( $j = 0; $j < count($astrFormElements); $j++ ) {
    if(in_array($astrFormElements[$j]['name'], $astrSelectedFields, true)) {
        $strSearchMatch = getPost('searchmatch_'.$astrFormElements[$j]['name'], '=');
        if( $astrFormElements[$j]['style'] == "xxlong" ){
            $astrFormElements[$j]['style'] = "long";
        }
?>
<tr>
    <td class="label">
        <?php echo $astrFormElements[$j]['label']?>
    </td>
    <td class="field">
        <?php echo htmlListBox( "searchmatch_". $astrFormElements[$j]['name'], $astrListValues, $astrListOptions, $strSearchMatch, "", 0 )?>
    </td>
    <td class="field">
        <?php echo htmlFormElement($astrFormElements[$j]['name'], $astrFormElements[$j]['type'],                               gpcStripSlashes($astrValues[$astrFormElements[$j]['name']]),                               $astrFormElements[$j]['style'],$astrFormElements[$j]['listquery'], "MODIFY", $astrFormElements[$j]['parent_key'])?>
    </td>
    <td>
        <input type="hidden" name="delete_<?php echo $astrFormElements[$j]['name']?>_x" value="0">
        <a class="tinyactionlink" href="#" title="<?php echo $GLOBALS['locDELROW']?>" onclick="self.document.forms[0].delete_<?php echo $astrFormElements[$j]['name']?>_x.value=1; self.document.forms[0].submit(); return false;"> X </a>
        <!--
        <input type="image" name="delete_<?php echo $astrFormElements[$j]['name']?>" src="./<?php echo $GLOBALS['sesLANG']?>_images/x.gif"  title="<?php echo $GLOBALS['locDELROW']?>" alt="<?php echo $GLOBALS['locDELROW']?>" style="cursor:pointer;cursor:hand;">-->
    </td>
</tr>        
<?php
        
        if( $j > 0 && $j < count($astrFormElements) ) {
            
            $strOperator = htmlFormElement("operator_".$astrFormElements[$j]['name'], "LIST",                               gpcStripSlashes($strSelectedOperator),                               "tiny","SELECT 'AND' AS id, 'AND' AS name UNION SELECT 'OR' AS id, 'OR' AS name", "MODIFY", '');
?>
<tr>
    <td colspan="4" align="center">
        <?php echo $strOperator?>
    </td>
</tr>
<?php
        }
    }
}

?>
</table>
<center>
<table>
<tr>
    <td>
        <input type="hidden" name="search_x" value="0">
        <a class="actionlink" href="#" onclick="self.document.forms[0].search_x.value=1; self.document.forms[0].submit(); return false;"><?php echo $GLOBALS['locSEARCH']?></a>
        <!--
        <input type="image" name="search"  src="./<?php echo $GLOBALS['sesLANG']?>_images/seek.gif"  alt="<?php echo $GLOBALS['locSEARCH']?>" style="cursor:pointer;cursor:hand;">-->
    </td>
    <td>
        <input type="hidden" name="save_x" value="0">
        <a class="actionlink" href="#" onclick="self.document.forms[0].save_x.value=1; self.document.forms[0].submit(); return false;"><?php echo $GLOBALS['locSAVESEARCH']?></a>
        <!--
        <input type="image"  name="save"  src="./<?php echo $GLOBALS['sesLANG']?>_images/save.gif"  alt="<?php echo $GLOBALS['locSAVESEARCH']?>" style="cursor:pointer;cursor:hand;">-->
    </td>
    <td>
        <a class="actionlink" href="#" onclick="self.close(); return false;"><?php echo $GLOBALS['locCLOSE']?></a>
        <!--
        <img name="close_button"  src="./<?php echo $GLOBALS['sesLANG']?>_images/close.gif"  alt="<?php echo $GLOBALS['locCLOSE']?>" title="<?php echo $GLOBALS['locCLOSE']?>" onClick="self.close();" style="cursor:pointer;cursor:hand;">-->
    </td>
</tr>
</table>
</center>
</form>
</body>
</html>
