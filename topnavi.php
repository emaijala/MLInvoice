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

$strSesID = sesVerifySession();


require "localize.php";

//button & function array...
$astrButtons = array (
    array("name" => "invoice", "title" => "locSHOWINVOICENAVI", "link" => "'navi.php?ses=". $GLOBALS['sesID']. "&amp;show=invoice'", "action" => "changeNavi", "levels_allowed" => array(1) ),
    array("name" => "company", "title" => "locSHOWCOMPANYNAVI", "link" => "'navi.php?ses=". $GLOBALS['sesID']. "&amp;show=company'", "action" => "changeNavi", "levels_allowed" => array(1) ),
    array("name" => "reports", "title" => "locSHOWREPORTNAVI", "link" => "'navi.php?ses=". $GLOBALS['sesID']. "&amp;show=reports'", "action" => "changeNavi", "levels_allowed" => array(1) ),
    array("name" => "settings", "title" => "locSHOWSETTINGSNAVI", "link" => "'navi.php?ses=". $GLOBALS['sesID']. "&amp;show=settings'", "action" => "changeNavi", "levels_allowed" => array(1) ),
    array("name" => "system", "title" => "locSHOWSYSTEMNAVI", "link" => "'navi.php?ses=". $GLOBALS['sesID']. "&amp;show=system'", "action" => "changeNavi", "levels_allowed" => array(99) ),
    array("name" => "help", "title" => "locSHOWHELP", "link" => "'help.php?ses=". $GLOBALS['sesID']. "&amp;topic=main', '_blank', 'height=400,width=400,menubar=no,scrollbars=yes,status=no,toolbar=no'", "action" => "window.open", "levels_allowed" => array(1) ),
    array("name" => "logout", "title" => "locLOGOUT", "link" => "'logout.php?ses=". $GLOBALS['sesID']. "', '_top'", "action" => "window.open", "levels_allowed" => array(1) )
);

echo htmlPageStart( _PAGE_TITLE_ );

?>
<script type="text/javascript">
<!--
function changeNavi( strLink ) {
    parent.f_navi.location.href = strLink;
    return true;
}
-->
</script>
<body class="navi">
<form method="post" action="<?php echo $_SERVER["PHP_SELF"]?>?ses=<?php echo $GLOBALS['sesID']?>" target="_self" name="form_topnavi">
<table>
<?php
for( $i = 0; $i < count($astrButtons); $i++ ) {
    $strButton = 
        "<a class=\"buttonlink\" href=\"#\" onClick=\"". $astrButtons[$i]["action"]. "(". $astrButtons[$i]["link"]. "); return false;\">". $GLOBALS[$astrButtons[$i]["title"]]. "</a>";
        
    if( in_array($GLOBALS['sesACCESSLEVEL'], $astrButtons[$i]["levels_allowed"]) || $GLOBALS['sesACCESSLEVEL'] == 99 ) {
?>
    <td>
        <?php echo $strButton?>
    </td>
<?php
    }
}

?>

</tr>
</table>
</form>
</body>
</html>
