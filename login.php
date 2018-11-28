<?php
/**
 * Login page
 *
 * PHP version 5
 *
 * Copyright (C) 2010-2018 Ere Maijala
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category MLInvoice
 * @package  MLInvoice\Base
 * @author   Ere Maijala <ere@labs.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://labs.fi/mlinvoice.eng.php
 */

// buffered, so we can redirect later if necessary
ini_set('implicit_flush', 'Off');
ob_start();

if (!file_exists(__DIR__ . DIRECTORY_SEPARATOR . 'config.php')) {
    include_once 'setup.php';
    $setup = new Setup();
    $setup->initialSetup();
    exit();
}

require_once 'sessionfuncs.php';
require_once 'sqlfuncs.php';
require_once 'miscfuncs.php';
require_once 'config.php';
require_once 'htmlfuncs.php';

if (!session_id()) {
    session_start();
}
$strLogin = getPost('flogin', false);
$strPasswd = getPost('fpasswd', false);
$strLogin = getPost('login', false);
$strPasswd = md5(getPost('passwd', false));
$strCsrf = getPost('csrf', false);
$strLogon = getPost('logon', '');
$backlink = getRequest('backlink', '0');
$getkey = getRequest('getkey', '0');
if ($getkey) {
    $strLogin = getRequest('flogin', false);
    $strPasswd = getRequest('fpasswd', false);
    $strLogon = getPost('logon', true);
}


function sesCreateKey()
{
    $_SESSION['key'] = createRandomString(20);
    $_SESSION['keytime'] = time();
    $_SESSION['keyip'] = $_SERVER['REMOTE_ADDR'];
    return $_SESSION['key'];
}

function json_response($message = null, $code = 200)
{
    // clear the old headers
    header_remove();
    // set the actual code
    http_response_code($code);
    // set the header to make sure cache is forced
    header("Cache-Control: no-transform,public,max-age=300,s-maxage=900");
    // treat this as json
    header('Access-Control-Allow-Credentials: true');
    $http_origin = null;
    if (isset($_SERVER['HTTP_ORIGIN'])) {
        $http_origin = $_SERVER['HTTP_ORIGIN'];
    }    
    if ($http_origin == "http://192.168.195.230:6075" || $http_origin == "http://localhost:6075" || $http_origin == "http://sunlumo.fi:6075") {
        header("Access-Control-Allow-Origin: $http_origin");
    } else {header("Access-Control-Allow-Origin: https://kotikolo.sunlumo.fi");}
    header('Content-Type: application/json');
    $status = array(
        200 => '200 OK',
        400 => '400 Bad Request',
        422 => 'Unprocessable Entity',
        500 => '500 Internal Server Error',
    );
    // ok, validation error, or failure
    header('Status: ' . $status[$code]);
    // return the encoded json
    echo json_encode(array(
        'status' => $code < 300, // success or not?
        'message' => $message,
    ));
}

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

require_once 'translator.php';

switch (verifyDatabase()) {
    case 'OK':
        break;
    case 'UPGRADED':
        $upgradeMessage = Translator::translate('DatabaseUpgraded');
        break;
    case 'FAILED':
        $upgradeFailed = true;
        $upgradeMessage = Translator::translate('DatabaseUpgradeFailed');
        break;
}

$strMessage = Translator::translate('WelcomeMessage');

if ($getkey) {
    $key = sesCreateKey();
   // $strPasswd = md5($key . $strPasswd);
    $strCsrf = sesCreateCsrf();
}

if ($strLogon) {
    if ($strLogin && $strPasswd) {        
        switch (sesCreateSession($strLogin, $strPasswd, $strCsrf)) {
            case 'OK':
                if ($backlink == '1' && isset($_SESSION['BACKLINK'])) {
                    header('Location: ' . $_SESSION['BACKLINK']);
                } else {
                    if ($getkey) {
                        json_response($_SESSION);
                    } else {
                        header('Location: index.php');
                    }
                }
                exit();
            case 'FAIL':
                $strMessage = Translator::translate('InvalidCredentials');
                break;
            case 'TIMEOUT':
                $strMessage = Translator::translate('LoginTimeout');
                break;
        }
    } else {
        $strMessage = Translator::translate('MissingFields');
    }
}

$csrf = sesCreateCsrf();

echo htmlPageStart('');
?>

<body onload="document.getElementById('login').focus();">
    <div class="pagewrapper ui-widget ui-widget-content login">
        <div id="maintabs" class="navi ui-widget-header ui-tabs">
            <ul class="ui-tabs-nav ui-helper-clearfix ui-corner-all">
                <li class="functionlink ui-state-default ui-corner-top ui-tabs-selected ui-state-active">
                    <a class="ui-tabs-anchor functionlink"><?php echo Translator::translate('Login') ?></a>
                </li>
            </ul>
        </div>

<?php
if (isset($upgradeMessage)) {
    ?>
        <div class="message ui-widget <?php echo isset($upgradeFailed) ? 'ui-state-error' : 'ui-state-highlight' ?>">
            <?php echo $upgradeMessage ?>
        </div>
        <br />
    <?php
}
?>
        <div class="ui-widget form" style="padding: 30px;">

<?php
if (isset($languages)) {
    foreach ($languages as $code => $name) {
        if ($code == $_SESSION['sesLANG']) {
            continue;
        }
        ?>
<a href="login.php?lang=<?php echo $code ?>"><?php echo htmlspecialchars($name) ?></a><br />
        <?php
}
    echo '<br/>';
}
?>
            <h1><?php echo Translator::translate('Welcome') ?></h1>
            <p>
                <span id="loginmsg"><?php echo $strMessage ?></span>
            </p>            
            <form action="login.php" method="post" name="login_form">
                <input type="hidden" name="backlink" value="<?php echo $backlink ?>">
                <input type="hidden" name="csrf" id="csrf" value="<?php echo $csrf ?>">
                <p>
                    <span style="width: 100px; display: inline-block;">
                        <?php echo Translator::translate('UserID') ?>
                    </span>
                    <input class="medium" name="login" id="login" type="text" value="">
                </p>
                <p>
                    <span style="width: 100px; display: inline-block;">
                        <?php echo Translator::translate('Password') ?>
                    </span>
                    <input class="medium" name="passwd" id="passwd" type="password" value="">
                </p>
                <p>
                <input class="ui-button ui-corner-all ui-widget" type="submit" name="logon"
                    value="<?php echo Translator::translate('Login') ?>">
                </p>
<?php
if (getSetting('password_recovery')) {
    ?>
                <p>
                  <a href="recover.php">
                    <?php echo Translator::translate('ForgotPassword') ?>
                  </a>
                </p>
    <?php
}
?>
            </form>
        </div>
    </div>
</body>
</html>
