<?php
/*******************************************************************************
VLLasku: web-based invoicing application.
Copyright (C) 2010-2012 Ere Maijala

Portions based on:
PkLasku : web-based invoicing software.
Copyright (C) 2004-2008 Samu Reinikainen

This program is free software. See attached LICENSE.

*******************************************************************************/

/*******************************************************************************
VLLasku: web-pohjainen laskutusohjelma.
Copyright (C) 2010-2012 Ere Maijala

Perustuu osittain sovellukseen:
PkLasku : web-pohjainen laskutusohjelmisto.
Copyright (C) 2004-2008 Samu Reinikainen

Tämä ohjelma on vapaa. Lue oheinen LICENSE.

*******************************************************************************/

require "htmlfuncs.php";
require "sqlfuncs.php";
require "sessionfuncs.php";

sesVerifySession();

require_once "localize.php";

sesEndSession();

echo htmlPageStart( _PAGE_TITLE_ );

?>

<body>
<div class="pagewrapper ui-widget ui-widget-content">
<div style="padding: 30px;">

<h1><?php echo $GLOBALS['locTHANKYOU']?></h1>
<p>
<?php echo $GLOBALS['locSESSIONCLOSED']?>
</p>

<p>
<a href="login.php"><?php echo $GLOBALS['locBACKTOLOGIN']?></a>
</p>

</div>
</div>
</div>
</body>
</html>
<?php

