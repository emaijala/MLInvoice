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

$strSesID = sesVerifySession();



echo htmlFrameSetStart( _PAGE_TITLE_ );

?>

<frameset cols="300,*" border="0">
    <frame src="open_invoices.php?ses=<?php echo $GLOBALS['sesID']?>" frameborder="0" marginheight="1px" marginwidth="1px" name="f_list" scrolling="auto">
    <frame src="frset_main.php?ses=<?php echo $GLOBALS['sesID']?>" frameborder="0" marginheight="1px" marginwidth="1px" name="frset_main" scrolling="auto">
</frameset>
</html>
