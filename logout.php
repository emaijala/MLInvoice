<?php
/*******************************************************************************
 MLInvoice: web-based invoicing application.
 Copyright (C) 2010-2017 Ere Maijala

 Portions based on:
 PkLasku : web-based invoicing software.
 Copyright (C) 2004-2008 Samu Reinikainen

 This program is free software. See attached LICENSE.

 *******************************************************************************/

/*******************************************************************************
 MLInvoice: web-pohjainen laskutusohjelma.
 Copyright (C) 2010-2017 Ere Maijala

 Perustuu osittain sovellukseen:
 PkLasku : web-pohjainen laskutusohjelmisto.
 Copyright (C) 2004-2008 Samu Reinikainen

 Tämä ohjelma on vapaa. Lue oheinen LICENSE.

 *******************************************************************************/

// buffered, so we can redirect later if necessary
ini_set('implicit_flush', 'Off');
ob_start();

require_once 'config.php';
require_once 'sessionfuncs.php';
require_once 'htmlfuncs.php';
require_once 'sqlfuncs.php';

sesVerifySession();

require_once 'translator.php';

sesEndSession();

echo htmlPageStart();

?>

<body>
    <div class="pagewrapper ui-widget ui-widget-content">
        <div id="maintabs" class="navi ui-widget-header ui-tabs">
            <ul class="ui-tabs-nav ui-helper-clearfix ui-corner-all">
                <li class="functionlink ui-state-default ui-corner-top">
                    <a class="ui-tabs-anchor functionlink" href="login.php"><?php echo Translator::translate('Login')?></a>
                </li>
            </ul>
        </div>

        <div class="ui-widget" style="padding: 30px;">

            <h1><?php echo Translator::translate('ThankYou')?></h1>
            <p>
<?php echo Translator::translate('SessionClosed')?>
</p>

            <p>
                <a href="login.php"><?php echo Translator::translate('BackToLogin')?></a>
            </p>

        </div>
    </div>
</body>
</html>
