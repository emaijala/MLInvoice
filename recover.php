<?php
/**
 * Password recovery
 *
 * PHP version 7
 *
 * Copyright (C) Ere Maijala 2018-2021
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

require_once 'vendor/autoload.php';
require_once 'sessionfuncs.php';
require_once 'sqlfuncs.php';
require_once 'miscfuncs.php';
require_once 'config.php';
require_once 'htmlfuncs.php';
require_once 'translator.php';
require_once 'mailer.php';

if (!session_id()) {
    session_start();
}

if (!getSetting('password_recovery')) {
    echo htmlPageStart();
    ?>
<body>
    <div class="container-fluid">
        <div class="form_container">
            Unavailable
        </div>
    </div>
</body>
</html>
    <?php
    return;
}

$token = getPostOrQuery('token');
if ($token) {
    $tokenTime = intval(substr($token, -10));
    if (time() - $tokenTime > 3600) {
        $errorMessage = Translator::translate('TokenExpired');
    } else {
        $user = getUserByToken($token);
        if (!$user) {
            $errorMessage = Translator::translate('AccountNotFound');
        } else {
            $password = getPostOrQuery('password', false);
            if (false !== $password) {
                updateUserPassword($user['id'], $password);
                $message = Translator::translate('PasswordChanged');
                $completed = true;
            }
        }
    }
}

$userId = getPost('userid', false);
if ($userId) {
    if (CSRF_OK !== sesCheckCsrf(getPost('csrf'))) {
        $message = Translator::translate('LoginTimeout');
    } else {
        $user = getUserByLoginId($userId);
        if (!$user) {
            $message = Translator::translate('RecoveryInstructionsSent');
            $completed = true;
        } else {
            if (!empty($user['email'])) {
                $newToken = updateUserToken($user['id']);
                $scheme = $_SERVER['REQUEST_SCHEME'];
                $url = "$scheme://" . $_SERVER['SERVER_NAME'];
                $port = $_SERVER['SERVER_PORT'];
                if (('http' === $scheme && 80 != $port)
                    || ('https' === $scheme && 443 != $port)
                ) {
                    $url .= ":$port";
                }
                $url .= $_SERVER['REQUEST_URI'];
                $url .= (strpos($url, '?') === false ? '?' : '&');
                $url .= 'token=' . $newToken;

                $mailer = new Mailer();
                $result = $mailer->sendEmail(
                    $user['email'],
                    $user['email'],
                    [],
                    [],
                    Translator::translate('RecoverAccountEmailSubject'),
                    Translator::translate(
                        'RecoverAccountEmailBody',
                        ['%%url%%' => $url]
                    ),
                    []
                );
                if (!$result) {
                    $errorMessage = $mailer->getErrorMessage();
                } else {
                    $message = Translator::translate('RecoveryInstructionsSent');
                    $completed = true;
                }
            } else {
                $message = Translator::translate('RecoveryInstructionsSent');
                $completed = true;
            }
        }
    }
}

usleep(rand(500, 1000) * 1000 * MLINVOICE_LOGIN_DELAY_MULTIPLIER);
$csrf = sesCreateCsrf();

echo htmlPageStart('');
?>

<body>
    <div class="pagewrapper mb-4">
        <?php createNavBar([], '')?>

        <div class="content recover-form">

<?php
if (isset($message)) {
    ?>
            <div class="alert alert-success message">
                <?php echo $message?>
            </div>
            <br />
    <?php
} elseif (isset($errorMessage)) {
    ?>
        <div class="alert alert-danger message">
            <?php echo $errorMessage?>
        </div>
        <br />
    <?php
}
if (empty($completed)) {
    ?>
        <div class="form login">
            <h1><?php echo Translator::translate('RecoverAccount')?></h1>
            <form method="post" name="recover_form">
                <input type="hidden" name="csrf" value="<?php echo htmlentities($csrf)?>">
    <?php
    if ($token && !empty($user)) {
        ?>
                <input type="hidden" name="token" value="<?php echo htmlentities($token)?>">
                <p>
                    <span class="label">
                        <?php echo Translator::translate('UserID')?>
                    </span>
                    <?php echo htmlentities($user['login'])?>
                </p>
                <p>
                    <span class="label">
                        <?php echo Translator::translate('NewPassword')?>
                    </span>
                    <input class="form-control medium" name="password" id="password" type="password" value="">
                </p>
        <?php
    } else {
        ?>
                <p>
                    <span class="label">
                        <?php echo Translator::translate('UserIdOrEmail')?>
                    </span>
                    <input class="form-control medium" name="userid" id="userid" type="text" value="">
                </p>
        <?php
    }
    ?>

                <p>
                <input class="btn btn-primary" type="submit" name="logon"
                    value="<?php echo Translator::translate('Continue')?>">
                </p>
            </form>
        </div>
    <?php
}
?>
        <div>
            <p>
                <a href="login.php"><?php echo Translator::translate('BackToLogin')?></a>
            </p>
        </div>
    </div>
</div>
</body>
</html>
