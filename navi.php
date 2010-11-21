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

$strSesID = sesVerifySession();

require "localize.php";

echo htmlPageStart( _PAGE_TITLE_ );

$blnShowSearch = getRequest('search', FALSE) ? TRUE : FALSE;
$strFormSwitch = getRequest('selectform', FALSE);
$blnShowAdmin = getRequest('admin', FALSE) ? TRUE : FALSE;
$blnShowSettings = getRequest('settings', FALSE) ? TRUE : FALSE;
$blnShowSettings2 = getRequest('settings2', FALSE) ? TRUE : FALSE;
$blnShowSettings3 = getRequest('settings3', FALSE) ? TRUE : FALSE;
$blnShowSystem = getRequest('system', FALSE) ? TRUE : FALSE;
$blnShowInv = getRequest('invoice', FALSE) ? TRUE : FALSE;
$blnShowReport = getRequest('reports', FALSE) ? TRUE : FALSE;
$blnShowPrintForm = getRequest('print_form', FALSE) ? TRUE : FALSE;

$strShow = getRequest('show', 'invoice');

$strOpenForm = "blank.html";

$strHiddenTerm = '';
$strLabel = '';
$strClientButton = '';
$strInvButton = '';
$strFormName = '';
if( $strFormSwitch ) {
    $strQuery = 
        "SELECT id, name FROM ". _DB_PREFIX_. "_location ".
        "WHERE name = '". $strFormSwitch. "'";
    $intRes = mysql_query($strQuery);
    $intNRes = mysql_numrows($intRes);
    if( $intNRes ) {
        $intcategoryID = mysql_result($intRes, 0, "id");
        $strLabel = mysql_result($intRes, 0, "name");
        $strHiddenTerm = "<input type=\"hidden\" name=\"category_id\" value=\"". $intcategoryID."\">";
        $strExtSearchTerm = "&catid=". $intcategoryID;
    }
}
else {
    $intcategoryID = FALSE;
    //$strLabel = $GLOBALS['locALLOCATIONS'];
    $strExtSearchTerm = "";
}

switch ( $strShow ) {
    
case "system" :
    $strLabel = $GLOBALS['locSHOWSYSTEMNAVI'];
    $astrNaviLinks = 
    array( 
        array("href" => "list.php?ses=".$GLOBALS['sesID']."&selectform=session_type", "text" => $GLOBALS['locSESSIONTYPES'], "target" => "f_list", "levels_allowed" => array(99)),
        array("href" => "list.php?ses=".$GLOBALS['sesID']."&selectform=users", "text" => $GLOBALS['locUSERS'], "target" => "f_list", "levels_allowed" => array(99))
    );
break;
case "settings" :
    $strLabel = $GLOBALS['locSHOWSETTINGSNAVI'];
    $astrNaviLinks = 
    array( 
        array("href" => "list.php?ses=".$GLOBALS['sesID']."&selectform=base_info", "text" => $GLOBALS['locBASEINFO'], "target" => "f_list", "levels_allowed" => array(1)),
//        array("href" => "list.php?ses=".$GLOBALS['sesID']."&selectform=company_type", "text" => $GLOBALS['locCOMPANYTYPES'], "target" => "f_list", "levels_allowed" => array(1)),
        array("href" => "list.php?ses=".$GLOBALS['sesID']."&selectform=invoice_state", "text" => $GLOBALS['locINVOICESTATES'], "target" => "f_list", "levels_allowed" => array(1)),
        array("href" => "list.php?ses=".$GLOBALS['sesID']."&selectform=product", "text" => $GLOBALS['locPRODUCTS'], "target" => "f_list", "levels_allowed" => array(1)),
        array("href" => "list.php?ses=".$GLOBALS['sesID']."&selectform=row_type", "text" => $GLOBALS['locROWTYPES'], "target" => "f_list", "levels_allowed" => array(1)),
        
    );
break;

case "reports" :
    $strLabel = $GLOBALS['locSHOWREPORTNAVI'];
    $astrNaviLinks = 
    array( 
        array("href" => "select_invoice.php?ses=".$GLOBALS['sesID']. "&type=report", "text" => $GLOBALS['locINVOICEREPORTS'], "target" => "f_list", "levels_allowed" => array(1))
    );
break;

case "company":
    $blnShowSearch = TRUE;
    $strOpenForm = "list.php?ses={$GLOBALS['sesID']}&selectform=company";
    $strFormName = "company";
    $strFormSwitch = "company";
    //$strLabel = $GLOBALS['locSEARCH'];
    $astrNaviLinks = 
    array( 
        /*array("href" => "select_invoice.php?ses=".$GLOBALS['sesID']. "&type=payment", "text" => $GLOBALS['locADDPAYMENT'], "target" => "f_list", "levels_allowed" => array(1))*/
    );
    
    $strClientButton = "<a class=\"actionlink\" href=\"#\" onclick=\"parent.frset_bottom.frset_main.f_main.location.href = 'form.php?ses={$GLOBALS['sesID']}&selectform=company&new=1'; parent.frset_bottom.f_list.location.href = 'list.php?ses={$GLOBALS['sesID']}&selectform=company'; return false;\">Uusi asiakas</a>";
break;
case "product":
    $blnShowSearch = TRUE;
    $strOpenForm = "list.php?ses={$GLOBALS['sesID']}&selectform=product";
    $strFormName = "product";
    $strFormSwitch = "product";
    //$strLabel = $GLOBALS['locSEARCH'];
    $astrNaviLinks = 
    array( 
        /*array("href" => "select_invoice.php?ses=".$GLOBALS['sesID']. "&type=payment", "text" => $GLOBALS['locADDPAYMENT'], "target" => "f_list", "levels_allowed" => array(1))*/
    );
    
    $strClientButton = "<a class=\"actionlink\" href=\"#\" onclick=\"parent.frset_bottom.frset_main.f_main.location.href = 'form.php?ses={$GLOBALS['sesID']}&selectform=product&new=1'; parent.frset_bottom.f_list.location.href = 'list.php?ses={$GLOBALS['sesID']}&selectform=product'; return false;\">Uusi tuote</a>";
break;
default :
    $blnShowSearch = TRUE;
    $strOpenForm = "open_invoices.php?ses={$GLOBALS['sesID']}";
    $strFormName = "invoice";
    $strFormSwitch = "invoice";
    //$strLabel = $GLOBALS['locSEARCH'];
    $astrNaviLinks = 
    array( 
        /*array("href" => "select_invoice.php?ses=".$GLOBALS['sesID']. "&type=payment", "text" => $GLOBALS['locADDPAYMENT'], "target" => "f_list", "levels_allowed" => array(1))*/
    );
    $strInvButton = "<a class=\"actionlink\" href=\"#\" onclick=\"parent.frset_bottom.frset_main.f_main.location.href = 'form.php?ses={$GLOBALS['sesID']}&selectform=invoice&new=1'; parent.frset_bottom.f_list.location.href = 'list.php?ses={$GLOBALS['sesID']}&selectform=invoice'; return false;\">Uusi lasku</a>";
    
break;
}
//$strOpenForm = "blank.html";

?>
<body class="navi" onload="changeNavi();">
<script type="text/javascript">
<!--
function changeNavi() {
//    if( this.document.form_search.changed.value == 0 || confirm('All changes will be lost!\n\rOK to continue?') ) {
        //self.location.href = strLink;
        parent.frset_bottom.f_list.location.href = '<?php echo $strOpenForm?>';
        parent.frset_bottom.frset_main.f_main.location.href = 'blank.html';
        parent.frset_bottom.frset_main.f_funcs.location.href = 'blank.html';
        parent.frset_bottom.frset_main.f_funcs2.location.href = 'blank.html';
//    }
    
    return true;
}

function openHelpWindow(event) {
    x = event.screenX;
    y = event.screenY;
    strLink = 'help.php?ses=<?php echo $GLOBALS['sesID']?>&topic=search'; window.open(strLink, '_blank', 'height=400,width=400,screenX=' + x + ',screenY=' + y + ',left=' + x + ',top=' + y + ',menubar=no,scrollbars=yes,status=no,toolbar=no');
    
    return true;
}
function openSearchWindow(mode, event) {
    x = event.screenX;
    y = event.screenY;
    if( mode == 'ext' ) {
        strLink = 'ext_search.php?ses=<?php echo $GLOBALS['sesID']?>' + '&selectform=<?php echo $strFormName?>';
        strLink = strLink + '<?php echo $strExtSearchTerm?>';
        height = '400';
        width = '500';
        windowname = 'ext';
    }
    if( mode == 'quick' ) {
        strLink = 'quick_search.php?ses=<?php echo $GLOBALS['sesID']?>';
        height = '400';
        width = '250';
        windowname = 'quicksearch';
    }

    window.open(strLink, windowname, 'height='+height+',width='+width+',screenX=' + x + ',screenY=' + y + ',left=' + x + ',top=' + y + ',menubar=no,scrollbars=yes,status=no,toolbar=no');
    
    return true;
}
-->
</script>
<form method="post" action="list.php?ses=<?php echo $GLOBALS['sesID']?>" target="f_list" name="form_search">
<table>
    <tr>
    
    <td>
        <b><?php echo $strLabel?> </b>
    </td>
<?php
if( $blnShowSearch ) {
        
?>

    <td>
        <input type="hidden" name="changed" value="0">
        <?php echo $strHiddenTerm?>
        <input type="hidden" name="selectform" value="<?php echo $strFormName?>">
    </td>
    <td>
        <input type="text" class="small" name="searchterms" value="" title="<?php echo $GLOBALS['locENTERTERMS']?>">
    </td>
    <td>
        <a class="actionlink" href="#" onClick="self.document.forms[0].submit();"><?php echo $GLOBALS['locSEARCH']?></a>
    </td>
    <td>
        <?php echo $strInvButton?>
    </td>
    <td>
        <?php echo $strClientButton?>
    </td>
    <td>
        <a class="buttonlink" href="#" onClick="openSearchWindow('ext',event); return false;"><?php echo $GLOBALS['locEXTSEARCH']?></a>
    </td>
    <td>
        <a class="buttonlink" href="#" onClick="openSearchWindow('quick',event); return false;"><?php echo $GLOBALS['locQUICKSEARCH']?></a>
    </td>
    <td>
        <a class="buttonlink" href="#" onClick="openHelpWindow(event); return false;"><?php echo $GLOBALS['locHELP']?></a>
    </td>
<?php
}

    for( $i = 0; $i < count($astrNaviLinks); $i++ ) {
        if( in_array($GLOBALS['sesACCESSLEVEL'], $astrNaviLinks[$i]["levels_allowed"]) || $GLOBALS['sesACCESSLEVEL'] == 99 ) {
            if( $astrNaviLinks[$i]['target'] == "_self" ) {
?>    
     <td>
        <a class="buttonlink" href="#"  onClick="self.location.href = '<?php echo $astrNaviLinks[$i]['href']?>'; parent.frset_bottom.frset_main.f_main.location.href = 'blank.html'; parent.frset_bottom.frset_main.f_funcs.location.href = 'blank.html'; parent.frset_bottom.frset_main.f_funcs2.location.href = 'blank.html'; return false;"><?php echo $astrNaviLinks[$i]['text']?></a>
     </td>
<?php            
            }
            else {
?>    
     <td>
        <a class="buttonlink" href="#"  onClick="parent.frset_bottom.f_list.location.href = '<?php echo $astrNaviLinks[$i]['href']?>'; parent.frset_bottom.frset_main.f_main.location.href = 'blank.html'; parent.frset_bottom.frset_main.f_funcs.location.href = 'blank.html'; parent.frset_bottom.frset_main.f_funcs2.location.href = 'blank.html'; return false;"><?php echo $astrNaviLinks[$i]['text']?></a>
     </td>
<?php
            }
        }
    }
?>

</tr>
</table>
</form>

</body>
</html>
