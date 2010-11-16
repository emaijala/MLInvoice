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

$strSesID = $_REQUEST['ses'] ? $_REQUEST['ses'] : FALSE;

if( !sesCheckSession( $strSesID ) ) {
    die;
}
require "localize.php";

$blnNew = (int)$_POST['new'] || (int)$_REQUEST['new'] ? TRUE : FALSE;

echo htmlPageStart( _PAGE_TITLE_ );

?>

<body class="form" onload="<?php echo $strOnLoad?>">
<script type="text/javascript">
<!--
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
    
    return true;
}

-->
</script>
<left>
<table>
<tr>
    <td>
        <a class="actionlink" href="#" onclick="parent.f_main.document.forms[0].saveact.value=1; parent.f_main.document.forms[0].submit(); return false;"><?php echo $GLOBALS['locSAVE']?></a>
    </td>
<?php
if( !$blnNew ) {
?>    
    <td>
        <a class="actionlink" href="#" onclick="parent.f_main.document.forms[0].copyact.value=1; parent.f_main.document.forms[0].submit(); return false;"><?php echo $GLOBALS['locCOPY']?></a>
    </td>
    <td>
        <a class="actionlink" href="#" onclick="parent.f_main.document.forms[0].newact.value=1; parent.f_main.document.forms[0].submit(); return false;"><?php echo $GLOBALS['locNEW']?></a>
    </td>
    <td>
        <a class="actionlink" href="#" onclick="if(confirm('<?php echo $GLOBALS['locCONFIRMDELETE']?>')==true) {  parent.f_main.document.forms[0].deleteact.value=1; parent.f_main.document.forms[0].submit(); return false;} else{ return false; }"><?php echo $GLOBALS['locDELETE']?></a>        
    </td>
<?php
}
?>
<td>
    <a class="actionlink" href="#" onclick="window.open('help.php?ses=<?php echo $GLOBALS['sesID']?>&topic=form', '_blank', 'height=400,width=400,menubar=no,scrollbars=yes,status=no,toolbar=no'); return false;"><?php echo $GLOBALS['locHELP']?></a>
</td>
</tr>        
</table>
</left>
</body>
</html>
