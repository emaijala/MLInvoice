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

sesEndSession( $strSesID );

echo htmlPageStart( _PAGE_TITLE_ );

?>

<body style="margin: 30px">

<h1>Kiitos</h1>
<p>
Istunto on päätetty.
</p>

<p>
<a href="login.php">Takaisin kirjautumaan</a>
</p>

</form>
</body>
</html>
