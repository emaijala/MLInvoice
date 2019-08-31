<?php
/**
 * Session handling
 *
 * PHP version 5
 *
 * Copyright (C) 2004-2008 Samu Reinikainen
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
require_once 'config.php';
require_once 'sqlfuncs.php';
require_once 'miscfuncs.php';

define('ROLE_READONLY', 0);
define('ROLE_USER', 1);
define('ROLE_BACKUPMGR', 90);
define('ROLE_ADMIN', 99);

define('CSRF_OK', 0);
define('CSRF_ERR_FAIL', 1);

/**
 * Create a session
 *
 * @param string $strLogin  Login name
 * @param string $strPasswd Password
 * @param string $strCsrf   CSRF token
 *
 * @return string OK|TIMEOUT|FAIL
 */
function sesCreateSession($strLogin, $strPasswd, $strCsrf)
{
    // Delay so that brute-force attacks become unpractical
    sleep(2);
    if ($strLogin && $strPasswd) {
        $res = sesCheckCsrf($strCsrf);
        if (CSRF_ERR_FAIL === $res) {
            $csrfTime = isset($_SESSION['csrftime']) ? $_SESSION['csrftime'] : time();
            error_log(
                'Key not found or timeout, ' . (time() - $csrfTime)
                . ' seconds since login form was created'
            );
            return 'TIMEOUT';
        }

        $strQuery = 'SELECT u.id AS user_id, u.type_id, u.passwd, st.time_out, st.access_level '
             . 'FROM {prefix}users u '
             . 'INNER JOIN {prefix}session_type st ON st.id = u.type_id '
             . 'WHERE u.deleted=0 AND u.login=?';
        $rows = dbParamQuery($strQuery, [$strLogin]);
        if ($rows) {
            $row = $rows[0];
            if (!password_verify($strPasswd, $row['passwd'])
                && md5($strPasswd) != $row['passwd']
            ) {
                // Delay so that brute-force attacks become unpractical
                sleep(2);
                error_log("Login failed for $strLogin");
                return 'FAIL';
            }

            $_SESSION['sesTYPEID'] = $row['type_id'];
            $_SESSION['sesUSERID'] = $row['user_id'];
            $_SESSION['sesACCESSLEVEL'] = $row['access_level'];
            $_SESSION['REMOTE_ADDR'] = $_SERVER['REMOTE_ADDR'];
            $_SESSION['ACCESSTIME'] = time();

            return 'OK';
        }
    }
    error_log('Login failed due to missing user name or password');
    return 'FAIL';
}

/**
 * Check a CSRF token
 *
 * @param string $csrf Token
 *
 * @return int
 */
function sesCheckCsrf($csrf)
{
    if (!isset($_SESSION['csrf'])) {
        return CSRF_ERR_FAIL;
    }

    $storedCsrf = $_SESSION['csrf'];
    unset($_SESSION['csrf']);
    $csrfTime = $_SESSION['csrftime'];
    if ($storedCsrf !== $csrf || time() - $csrfTime > 300) {
        return CSRF_ERR_FAIL;
    }

    return CSRF_OK;
}

/**
 * End a session
 *
 * @return bool
 */
function sesEndSession()
{
    session_destroy();
    unset($_SESSION);
    return true;
}

/**
 * Verify current session
 *
 * @param bool $redirect Whether to redirect to login if verification fails
 *
 * @return bool
 */
function sesVerifySession($redirect = true)
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
        if (substr($_SERVER['SCRIPT_FILENAME'], -9, 9) == 'index.php'
            && $_SERVER['QUERY_STRING'] && getPostOrQuery('func', '') != 'logout'
        ) {
            $_SESSION['BACKLINK'] = 'index.php?' . $_SERVER['QUERY_STRING'];
            header('Location: login.php?backlink=1');
        } else {
            header('Location: login.php');
        }
    } else {
        header('HTTP/1.1 403 Forbidden');
    }
    exit();
}

/**
 * Create a session CSRF hash
 *
 * @return string
 */
function sesCreateCsrf()
{
    $_SESSION['csrf'] = createRandomString(20);
    $_SESSION['csrftime'] = time();
    return $_SESSION['csrf'];
}

/**
 * Create a random character string
 *
 * @param int $length Length
 *
 * @return string
 */
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

/**
 * Check if current session has write access
 *
 * @return bool
 */
function sesWriteAccess()
{
    if (!isset($_SESSION['sesACCESSLEVEL'])) {
        ob_clean();
        die();
    }
    return in_array(
        $_SESSION['sesACCESSLEVEL'],
        [
            ROLE_USER,
            ROLE_BACKUPMGR,
            ROLE_ADMIN
        ]
    );
}

/**
 * Check if current session has admin access
 *
 * @return bool
 */
function sesAdminAccess()
{
    if (!isset($_SESSION['sesACCESSLEVEL'])) {
        ob_clean();
        die();
    }
    return $_SESSION['sesACCESSLEVEL'] == ROLE_ADMIN;
}

/**
 * Check if current session's access level is one of the allowed levels
 *
 * @param array $allowedLevels Allowed levels
 *
 * @return bool
 */
function sesAccessLevel($allowedLevels)
{
    if (!isset($_SESSION['sesACCESSLEVEL'])) {
        ob_clean();
        die();
    }
    return in_array($_SESSION['sesACCESSLEVEL'], $allowedLevels);
}

// Database-based session management

/**
 * Open a session
 *
 * @param string $savePath  Save path
 * @param string $sessionID Session ID
 *
 * @return bool
 */
function dbSessionOpen($savePath, $sessionID)
{
    return true;
}

/**
 * Close a session
 *
 * @return bool
 */
function dbSessionClose()
{
    return true;
}

/**
 * Read session data
 *
 * @param string $sessionID Session ID
 *
 * @return bool
 */
function dbSessionRead($sessionID)
{
    $rows = dbParamQuery(
        'SELECT data, session_timestamp FROM {prefix}session where id=?', [$sessionID]
    );
    if (isset($rows[0])) {
        // Check for expiration
        $sessionMaxAge = get_cfg_var('session.gc_maxlifetime');
        $minTimestamp = date(
            'Y-m-d H:i:s',
            time() - ($sessionMaxAge ? $sessionMaxAge : 900)
        );
        if ($rows[0]['session_timestamp'] >= $minTimestamp) {
            return $rows[0]['data'];
        }
    }
    return '';
}

/**
 * Write session data
 *
 * @param string $sessionID   Session ID
 * @param string $sessionData Session data
 *
 * @return bool
 */
function dbSessionWrite($sessionID, $sessionData)
{
    dbParamQuery(
        'REPLACE INTO {prefix}session (id, data, session_timestamp) VALUES'
        . ' (?, ?, ?)',
        [
            $sessionID,
            $sessionData,
            date('Y-m-d H:i:s', time())
        ]
    );
    return true;
}

/**
 * Delete a session
 *
 * @param string $sessionID Session ID
 *
 * @return bool
 */
function dbSessionDestroy($sessionID)
{
    dbParamQuery('DELETE FROM {prefix}session WHERE id=?', [$sessionID]);
    // Some distributions have gc disabled, need to do it manually
    dbSessionGc(get_cfg_var('session.gc_maxlifetime'));
    return true;
}

/**
 * Collect session garbage
 *
 * @param int $sessionMaxAge Session maximum age
 *
 * @return bool
 */
function dbSessionGc($sessionMaxAge)
{
    if (!$sessionMaxAge) {
        $sessionMaxAge = 900;
    }
    // The query may fail if there are simultaneous requests, so don't let it cause
    // the request to fail
    dbParamQuery(
        'DELETE FROM {prefix}session WHERE session_timestamp<?',
        [
            date('Y-m-d H:i:s', time() - $sessionMaxAge)
        ],
        true
    );
    return true;
}

session_set_save_handler(
    'dbSessionOpen', 'dbSessionClose', 'dbSessionRead',
    'dbSessionWrite', 'dbSessionDestroy', 'dbSessionGc'
);
session_name(_SESSION_NAME_);
if (_SESSION_RESTRICT_PATH_) {
    session_set_cookie_params(0, getSelfDirectory() . '/');
}
