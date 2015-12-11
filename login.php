<?php
/*******************************************************************************
 MLInvoice: web-based invoicing application.
 Copyright (C) 2010-2015 Ere Maijala

 This program is free software. See attached LICENSE.

 *******************************************************************************/

/*******************************************************************************
 MLInvoice: web-pohjainen laskutusohjelma.
 Copyright (C) 2010-2015 Ere Maijala

 Tämä ohjelma on vapaa. Lue oheinen LICENSE.

 *******************************************************************************/

// buffered, so we can redirect later if necessary
ini_set('implicit_flush', 'Off');
ob_start();

require_once 'sessionfuncs.php';
require_once 'sqlfuncs.php';
require_once 'miscfuncs.php';
require_once 'config.php';
require_once 'htmlfuncs.php';

if (!session_id()) {
    session_start();
}

$strLogin = getPost('flogin', FALSE);
$strPasswd = getPost('fpasswd', FALSE);
$strLogon = getPost('logon', '');
$backlink = getRequest('backlink', '0');

if (defined('_UI_LANGUAGE_SELECTION_')) {
    $languages = [];
    foreach (explode('|', _UI_LANGUAGE_SELECTION_) as $lang) {
        $lang = explode('=', $lang, 2);
        $languages[$lang[0]] = $lang[1];
    }
    $language = getRequest('lang', '');
    if ($language && isset($languages[$language])) {
        $_SESSION['sesLANG'] = $language;
    }
}
if (!isset($_SESSION['sesLANG'])) {
    $_SESSION['sesLANG'] = defined('_UI_LANGUAGE_') ? _UI_LANGUAGE_ : 'fi-FI';
}

require_once 'localize.php';

switch (verifyDatabase()) {
case 'OK' :
    break;
case 'UPGRADED' :
    $upgradeMessage = $GLOBALS['locDatabaseUpgraded'];
    break;
case 'FAILED' :
    $upgradeFailed = true;
    $upgradeMessage = $GLOBALS['locDatabaseUpgradeFailed'];
    break;
}

$strMessage = $GLOBALS['locWelcomeMessage'];

if ($strLogon) {
    if ($strLogin && $strPasswd) {
        switch (sesCreateSession($strLogin, $strPasswd)) {
        case 'OK' :
            if ($backlink == '1' && isset($_SESSION['BACKLINK'])) {
                header('Location: ' . $_SESSION['BACKLINK']);
            } else {
                header('Location: ' . getSelfPath() . '/index.php');
            }
            exit();
        case 'FAIL' :
            $strMessage = $GLOBALS['locInvalidCredentials'];
            break;
        case 'TIMEOUT' :
            $strMessage = $GLOBALS['locLoginTimeout'];
            break;
        }
    } else {
        $strMessage = $GLOBALS['locMissingFields'];
    }
}

$key = sesCreateKey();

echo htmlPageStart(_PAGE_TITLE_, [
    'jquery/js/jquery.md5.js'
]);
?>

<body onload="document.getElementById('flogin').focus();">
	<div class="pagewrapper ui-widget ui-widget-content">
		<div class="form" style="padding: 30px;">

<?php
if (isset($upgradeMessage)) {
    ?>
<div
				class="message ui-widget <?php echo isset($upgradeFailed) ? 'ui-state-error' : 'ui-state-highlight'?>">
  <?php echo $upgradeMessage?>
</div>
			<br />
<?php
}
?>

<?php
if (isset($languages)) {
    foreach ($languages as $code => $name) {
        if ($code == $_SESSION['sesLANG']) {
            continue;
        }
        ?>
<a href="login.php?lang=<?php echo $code?>"><?php echo htmlspecialchars($name)?></a><br />
<?php
    }
    echo '<br/>';
}
?>
<h1><?php echo $GLOBALS['locWelcome']?></h1>
			<p>
				<span id="loginmsg"><?php echo $strMessage?></span>
			</p>

			<script type="text/javascript">
function createHash()
{
  var pass_md5 = $.md5(document.getElementById('passwd').value);
  var key = document.getElementById('key').value;
  document.getElementById('fpasswd').value = $.md5(key + pass_md5);
  document.getElementById('passwd').value = '';
  document.getElementById('key').value = '';
  var loginmsg = document.getElementById('loginmsg');
  loginmsg.childNodes.item(0).nodeValue = '<?php echo $GLOBALS['locLoggingIn']?>';
}
</script>

			<form action="login.php" method="post" name="login_form"
				onsubmit="createHash();">
				<input type="hidden" name="backlink" value="<?php echo $backlink?>">
				<input type="hidden" name="fpasswd" id="fpasswd" value=""> <input
					type="hidden" name="key" id="key" value="<?php echo $key?>">
				<p>
					<span style="width: 100px; display: inline-block;"><?php echo $GLOBALS['locUserID']?></span>
					<input class="medium" name="flogin" id="flogin" type="text"
						value="">
				</p>
				<p>
					<span style="width: 100px; display: inline-block;"><?php echo $GLOBALS['locPassword']?></span>
					<input class="medium" name="passwd" id="passwd" type="password"
						value="">
				</p>
				<input type="submit" name="logon"
					value="<?php echo $GLOBALS['locLogin']?>">
			</form>

		</div>
	</div>
</body>
</html>
