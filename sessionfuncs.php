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
    if ($strLogin && $strPasswd) {
        if (!isset($_SESSION['csrf']) || !isset($_SESSION['csrfip'])) {
            error_log('No key information in session, timeout or session problem');
            return 'TIMEOUT';
        }
        $csrfIp = $_SESSION['csrfip'];
        if ($_SERVER['REMOTE_ADDR'] != $csrfIp) {
            // Delay so that brute-force attacks become unpractical
            error_log("Login failed for $strLogin due to IP address change");
            sleep(2);
            return 'FAIL';
        }

        $csrf = $_SESSION['csrf'];
        unset($_SESSION['csrf']);
        $csrfTime = $_SESSION['csrftime'];
        if ($csrf !== $strCsrf || time() - $csrfTime > 300) {
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
    // Delay so that brute-force attacks become unpractical
    error_log('Login failed due to missing user name or password');
    sleep(2);
    return 'FAIL';
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
            && $_SERVER['QUERY_STRING'] && getRequest('func', '') != 'logout'
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
    $_SESSION['csrfip'] = $_SERVER['REMOTE_ADDR'];
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
    // Some distributions have gc disabled, need to do it manually
    dbSessionGc(get_cfg_var('session.gc_maxlifetime'));
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
        'SELECT data FROM {prefix}session where id=?', [$sessionID]
    );
    return isset($rows[0]['data']) ? $rows[0]['data'] : '';
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
    dbParamQuery(
        'DELETE FROM {prefix}session WHERE session_timestamp<?',
        [
            date('Y-m-d H:i:s', time() - $sessionMaxAge)
        ]
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
