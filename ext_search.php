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
$intCategory_id = $_REQUEST['catid'] ? (int)$_REQUEST['catid'] : FALSE;


require "localize.php";
//print_r($_POST);
$strForm = $_POST['selectform'] ? $_POST['selectform'] : $_REQUEST['selectform'];


$blnSearch = (int)$_POST['search_x'] ? TRUE : FALSE;
$blnSave = (int)$_POST['save_x'] ? TRUE : FALSE;
$strFields = $_POST['fields'] ? $_POST['fields'] : FALSE;
$strSearchField = $_POST['searchfield'] ? $_POST['searchfield'] : FALSE;
$strSearchName = $_POST['searchname'] ? $_POST['searchname'] : "";
if( $strSearchField !== FALSE ) {
    if( $strFields === FALSE ) {
        $strFields = $strSearchField;
    }
    else {
        $strFields .= ",". $strSearchField;
    }
}

if($intCategory_id && $strFields === FALSE ) {
    $strFields = "type_id";
    $_POST[$strFields] = $intCategory_id;
}

if( $strFields !== FALSE ) {
    $astrSelectedFields = explode(",", $strFields);
}
else {
    $astrSelectedFields = array();
}

require "form_switch.php";

for( $j = 0; $j < count($astrSelectedFields); $j++ ) {
    $tmpDelete = $_POST["delete_".$astrSelectedFields[$j]. "_x"];
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
    $strSelectedOperator = $_POST['operator_'.$astrFormElements[$j]['name']] ? $_POST['operator_'.$astrFormElements[$j]['name']] : "OR";
    if($astrFormElements[$j]['type'] != "LABEL" && $astrFormElements[$j]['type'] != "HIDINT" && $astrFormElements[$j]['type'] != 'IFRAME' && $astrFormElements[$j]['type'] != 'IFORM' && $astrFormElements[$j]['type'] != 'BUTTON' && !in_array($astrFormElements[$j]['name'], $astrSelectedFields, true)) {
        $astrListValues[$x] = $astrFormElements[$j]['name'];
        $astrListOptions[$x] = str_replace("<br>", " ", $astrFormElements[$j]['label']); 
        $x++;
    }
    $strControlType = $astrFormElements[$j]['type'];
    $strControlName = $astrFormElements[$j]['name'];
    
    if( $strControlType == 'IFRAME' || $strControlType == 'IFORM' || $strControlType == 'BUTTON' ) {
       $astrValues[$strControlName] = $intKeyValue;
    }
    elseif( $strControlType != 'LABEL' ) {
        if( $strControlType == 'INTDATE' ) {
            $astrValues[$strControlName] = $_POST[$astrFormElements[$j]['name']];
        }
        else { 
            $astrValues[$strControlName] = $_POST[$astrFormElements[$j]['name']];
        }
    }
    /*$astrValues[$astrFormElements[$j]['name']] =             $_POST[$astrFormElements[$j]['name']] ||  $_POST[$astrFormElements[$j]['name']] === "0" ? $_POST[$astrFormElements[$j]['name']] : FALSE;*/
}
$strListBox = htmlListBox( "searchfield", $astrListValues, $astrListOptions, FALSE, "", 1 );

$astrListValues = array('=','!=','<','>');
$astrListOptions = array('on yhtä kuin','on eri kuin','on pienempi kuin','on suurempi kuin');

if( $blnSearch || $blnSave ) {
    for( $j = 0; $j < count($astrFormElements); $j++ ) {
        if(in_array($astrFormElements[$j]['name'], $astrSelectedFields, true)) {
            $strSearchMatch =
                $_POST['searchmatch_'.$astrFormElements[$j]['name']] ? $_POST['searchmatch_'.$astrFormElements[$j]['name']] : "=";
            $strSearchValue = $astrValues[$astrFormElements[$j]['name']];
            //do LIKE || NOT LIKE search to elements with text or varchar datatype
            //echo $astrFormElements[$j]['type'];
            if( $astrFormElements[$j]['type'] == 'TEXT' || $astrFormElements[$j]['type'] == 'AREA' ) {
                if( $strSearchMatch == "=" ) {
                    $strSearchMatch = "LIKE";
                }
                else {
                    $strSearchMatch = "NOT LIKE";
                }
                //add to the where clause
                $strWhereClause .= $astrFormElements[$j]['name']. " ". $strSearchMatch. " '%-". $strSearchValue. "%-' $strSelectedOperator ";
            }
            //do =, !=, < or > search to elements that are numeric
            elseif( $astrFormElements[$j]['type'] == 'INT' || $astrFormElements[$j]['type'] == 'LIST' ) {
                //add to the where clause
                $strWhereClause .= $astrFormElements[$j]['name']. " ". $strSearchMatch. " ". $strSearchValue. " $strSelectedOperator ";
            }
            //checkboxelements handled bit differently than other int's
            elseif( $astrFormElements[$j]['type'] == 'CHECK' ) {
                $tmpValue = $astrValues[$astrFormElements[$j]['name']] ? 1 : 0;
                //add to the where clause
                $strWhereClause .= $astrFormElements[$j]['name']. " ". $strSearchMatch. " ". $tmpValue. " $strSelectedOperator ";
            }
            //date-elements need own formatting too
            elseif( $astrFormElements[$j]['type'] == 'INTDATE' ) {
                $tmpValue = dateConvDate2IntDate($astrValues[$astrFormElements[$j]['name']]);
                //add to the where clause
                $strWhereClause .= $astrFormElements[$j]['name']. " ". $strSearchMatch. " ". $tmpValue. " $strSelectedOperator ";
            }
        }
    }
    
    $strWhereClause = substr( $strWhereClause, 0, -4);
    $strWhereClause = urlencode($strWhereClause);
    if( $blnSearch ) {
        $strLink = 
            "list.php?ses=". $GLOBALS['sesID']."&selectform=". 
            $strForm. "&where=". $strWhereClause;
        $strOnLoad = "opener.top.frset_bottom.f_list.location.href='".$strLink. "'";
        //$strOnLoad = "alert(opener.top.f_list.location.href);";
    }
    
    if( $blnSave && $strSearchName ) {
        $strQuery = 
            "INSERT INTO ". _DB_PREFIX_. "_quicksearch(".
            "user_id, name, form, whereclause)".
            "VALUES(". $GLOBALS['sesUSERID']. ",'".$strSearchName. "','".
            $strForm. "','". $strWhereClause. "')";
        $intRes = mysql_query($strQuery);
    }
    elseif( $blnSave && !$strSearchName) {
        $strOnLoad = "alert('".$GLOBALS['locERRORNOSEARCHNAME']."')";
    }
}
echo htmlPageStart( _PAGE_TITLE_ );
?>
<script type="text/javascript">
<!--
function openHelpWindow(event) {
    x = event.screenX;
    y = event.screenY;
    strLink = 'help.php?ses=<?php echo $GLOBALS['sesID']?>&topic=extsearch'; window.open(strLink, '_blank', 'height=400,width=400,screenX=' + x + ',screenY=' + y + ',left=' + x + ',top=' + y + ',menubar=no,scrollbars=yes,status=no,toolbar=no');
    
    return true;
}
function OpenCalendar(datefield, event) {
    x = event.screenX;
    y = event.screenY;
    strLink = 'calendar.php?ses=<?php echo $GLOBALS['sesID']?>&datefield=' + datefield;
    
    window.open(strLink, 'calendar', 'height=260,width=280,screenX=' + x + ',screenY=' + y + ',left=' + x + ',top=' + y + ',menubar=no,scrollbars=yes,status=no,toolbar=no');
    
    return true;
}
function OpenClock(timefield, event) {
    x = event.screenX;
    y = event.screenY;
    strLink = 'clock.php?ses=<?php echo $GLOBALS['sesID']?>&timefield=' + timefield;
    
    window.open(strLink, 'clock', 'height=150,width=200,screenX=' + x + ',screenY=' + y + ',left=' + x + ',top=' + y + ',menubar=no,scrollbars=yes,status=no,toolbar=no');
}

-->
</script>

<body class="form" onload="<?php echo $strOnLoad?>">
<form method="post" action="ext_search.php?ses=<?php echo $GLOBALS['sesID']?>&selectform=<?php echo $strForm?>" target="_self" name="search_form">
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
        $strSearchMatch = $_POST['searchmatch_'.$astrFormElements[$j]['name']] ? $_POST['searchmatch_'.$astrFormElements[$j]['name']] : "=";
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
    <td>
        <a class="actionlink" href="#" onclick="openHelpWindow(event); return false;"><?php echo $GLOBALS['locHELP']?></a>
        <!--
        <img name="help" src="./<?php echo $GLOBALS['sesLANG']?>_images/detail_help.gif" alt="<?php echo $GLOBALS['locHELP']?>" title="<?php echo $GLOBALS['locHELP']?>" onClick="openHelpWindow(event);" style="cursor:pointer;cursor:hand;">-->
    </td>
</tr>
</table>
</center>
</form>
</body>
</html>
