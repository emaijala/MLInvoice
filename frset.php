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
require "localize.php";
require "sqlfuncs.php";
require "miscfuncs.php";
require "datefuncs.php";
require "sessionfuncs.php";

$strSesID = $_REQUEST['ses'] ? $_REQUEST['ses'] : FALSE;

if( !sesCheckSession( $strSesID ) ) {
    die;
}

echo htmlFrameSetStart( _PAGE_TITLE_ );

?>

<frameset rows="35,40,*" border="0">
    <frame src="topnavi.php?ses=<?php echo $GLOBALS['sesID']?>" name="f_topnavi" frameborder="0" marginheight="1px" marginwidth="1px" scrolling="no" noresize>
    <frame src="navi.php?ses=<?php echo $GLOBALS['sesID']?>" name="f_navi" frameborder="0" marginheight="1px" marginwidth="1px" scrolling="no" noresize>
    <frame src="frset_bottom.php?ses=<?php echo $GLOBALS['sesID']?>" frameborder="0" marginheight="1px" marginwidth="1px" name="frset_bottom" scrolling="no">
</frameset>
</html>
