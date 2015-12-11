<?php
/*******************************************************************************
 MLInvoice: web-based invoicing application.
 Copyright (C) 2010-2015 Ere Maijala

 Portions based on:
 PkLasku : web-based invoicing software.
 Copyright (C) 2004-2008 Samu Reinikainen

 This program is free software. See attached LICENSE.

 *******************************************************************************/

/*******************************************************************************
 MLInvoice: web-pohjainen laskutusohjelma.
 Copyright (C) 2010-2015 Ere Maijala

 Perustuu osittain sovellukseen:
 PkLasku : web-pohjainen laskutusohjelmisto.
 Copyright (C) 2004-2008 Samu Reinikainen

 Tämä ohjelma on vapaa. Lue oheinen LICENSE.

 *******************************************************************************/
require_once 'config.php';
require_once 'sqlfuncs.php';
require_once 'miscfuncs.php';

define('ROLE_READONLY', 0);
define('ROLE_USER', 1);
define('ROLE_BACKUPMGR', 90);
define('ROLE_ADMIN', 99);

function sesCreateSession($strLogin, $strPasswd)
{
    if ($strLogin && $strPasswd) {
        if (!isset($_SESSION['key']) || !isset($_SESSION['keyip'])) {
            error_log('No key information in session, timeout or session problem');
            return 'TIMEOUT';
        }
        $key_ip = $_SESSION['keyip'];
        if ($_SERVER['REMOTE_ADDR'] != $key_ip) {
            // Delay so that brute-force attacks become unpractical
            error_log("Login failed for $strLogin due to IP address change");
            sleep(2);
            return 'FAIL';
        }

        $key = $_SESSION['key'];
        unset($_SESSION['key']);
        $keytime = $_SESSION['keytime'];
        if (!$key || time() - $keytime > 300) {
            error_log(
                'Key not found or timeout, ' . time() - $keytime .
                     ' seconds since login form was created');
            return 'TIMEOUT';
        }

        $strQuery = 'SELECT u.id AS user_id, u.type_id, u.passwd, st.time_out, st.access_level ' .
             'FROM {prefix}users u ' .
             'INNER JOIN {prefix}session_type st ON st.id = u.type_id ' .
             'WHERE u.deleted=0 AND u.login=?';
        $intRes = mysqli_param_query($strQuery, [
            $strLogin
        ]);
        if ($row = mysqli_fetch_assoc($intRes)) {
            $passwd_md5 = $row['passwd'];
            $md5 = md5($key . $passwd_md5);
            if ($md5 != $strPasswd) {
                // Delay so that brute-force attacks become unpractical
                sleep(2);
                error_log("Login failed for $strLogin");
                return 'FAIL';
            }

            $_SESSION['sesTYPEID'] = $row['type_id'];
            $_SESSION['sesUSERID'] = $row['user_id'];
            $_SESSION['sesACCESSLEVEL'] = $row['access_level'];
            $_SESSION['REMOTE_ADDR'] = $_SERVER['REMOTE_ADDR'];
            $_SESSION['HISTORY'] = [];
            $_SESSION['ACCESSTIME'] = time();

            return 'OK';
        }
    }
    // Delay so that brute-force attacks become unpractical
    error_log('Login failed due to missing user name or password');
    sleep(2);
    return 'FAIL';
}

function sesEndSession()
{
    session_destroy();
    unset($_SESSION);
    session_regenerate_id(true);
    return true;
}

function sesVerifySession($redirect = TRUE)
{
    if (!session_id()) {
        session_start();
    }
    if (isset($_SESSION['REMOTE_ADDR'])
        && $_SESSION['REMOTE_ADDR'] == $_SERVER['REMOTE_ADDR']
    ) {
        $_SESSION['ACCESSTIME'] = time();
        return true;
    }
    if ($redirect) {
        if (substr($_SERVER['SCRIPT_FILENAME'], -9, 9) == 'index.php' &&
             $_SERVER['QUERY_STRING'] && getRequest('func', '') != 'logout'
        ) {
            $_SESSION['BACKLINK'] = getSelfPath() . '/index.php?' .
                $_SERVER['QUERY_STRING'];
            header('Location: ' . getSelfPath() . '/login.php?backlink=1');
        } else {
            header('Location: ' . getSelfPath() . '/login.php');
        }
    } else {
        header('HTTP/1.1 403 Forbidden');
    }
    exit();
}

function sesCreateKey()
{
    $_SESSION['key'] = createRandomString(20);
    $_SESSION['keytime'] = time();
    $_SESSION['keyip'] = $_SERVER['REMOTE_ADDR'];
    return $_SESSION['key'];
}

function sesUpdateHistory($title, $url, $level)
{
    $arrNew = [];
    foreach ($_SESSION['HISTORY'] as $item) {
        if ($item['level'] < $level)
            $arrNew[] = $item;
    }
    $arrNew[] = [
        'title' => $title,
        'url' => $url,
        'level' => $level
    ];
    $_SESSION['HISTORY'] = $arrNew;

    return $_SESSION['HISTORY'];
}

function createRandomString($length)
{
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    $str = '';
    for ($i = 0; $i < $length; $i ++) {
        $idx = mt_rand(0, strlen($chars) - 1);
        $str .= substr($chars, $idx, 1);
    }
    return $str;
}

function sesWriteAccess()
{
    return in_array(
        $_SESSION['sesACCESSLEVEL'],
        [
            ROLE_USER,
            ROLE_BACKUPMGR,
            ROLE_ADMIN
        ]
    );
}

function sesAdminAccess()
{
    return $_SESSION['sesACCESSLEVEL'] == ROLE_ADMIN;
}

function sesAccessLevel($allowedLevels)
{
    return in_array($_SESSION['sesACCESSLEVEL'], $allowedLevels);
}

// Database-based session management
function db_session_open($savePath, $sessionID)
{
    // Some distributions have gc disabled, need to do it manually
    db_session_gc(get_cfg_var('session.gc_maxlifetime'));
}

function db_session_close()
{
}

function db_session_read($sessionID)
{
    $res = mysqli_param_query(
        'SELECT data FROM {prefix}session where id=?', [$sessionID]
    );
    if ($row = mysqli_fetch_row($res))
        return reset($row);
    return '';
}

function db_session_write($sessionID, $sessionData)
{
    mysqli_param_query(
        'REPLACE INTO {prefix}session (id, data, session_timestamp) VALUES (?, ?, ?)',
        [
            $sessionID,
            $sessionData,
            date('Y-m-d H:i:s', time())
        ]);
    return true;
}

function db_session_destroy($sessionID)
{
    mysqli_param_query('DELETE FROM {prefix}session WHERE id=?', [
        $sessionID
    ]);
    return true;
}

function db_session_gc($sessionMaxAge)
{
    if (!$sessionMaxAge) {
        $sessionMaxAge = 900;
    }
    mysqli_param_query('DELETE FROM {prefix}session WHERE session_timestamp<?',
        [
            date('Y-m-d H:i:s', time() - $sessionMaxAge)
        ]);
    return true;
}

session_set_save_handler('db_session_open', 'db_session_close', 'db_session_read',
'db_session_write', 'db_session_destroy', 'db_session_gc');
session_name(_SESSION_NAME_);
if (_SESSION_RESTRICT_PATH_) {
    session_set_cookie_params(0, getSelfDirectory() . '/');
}
