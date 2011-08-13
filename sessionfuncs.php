<?php
/*******************************************************************************
VLLasku: web-based invoicing application.
Copyright (C) 2010-2011 Ere Maijala

Portions based on:
PkLasku : web-based invoicing software.
Copyright (C) 2004-2008 Samu Reinikainen

This program is free software. See attached LICENSE.

*******************************************************************************/

/*******************************************************************************
VLLasku: web-pohjainen laskutusohjelma.
Copyright (C) 2010-2011 Ere Maijala

Perustuu osittain sovellukseen:
PkLasku : web-pohjainen laskutusohjelmisto.
Copyright (C) 2004-2008 Samu Reinikainen

Tämä ohjelma on vapaa. Lue oheinen LICENSE.

*******************************************************************************/

require_once 'sqlfuncs.php';

function sesCreateSession($strLogin, $strPasswd) 
{
    if ($strLogin && $strPasswd) 
    {
        if (!isset($_SESSION['keyip']))
          return 'TIMEOUT';
        $key_ip = $_SESSION['keyip'];
        if ($_SERVER['REMOTE_ADDR'] != $key_ip)
        {
          // Delay so that brute-force attacks become unpractical
          sleep(2);
          return 'FAIL';
        }

        $key = $_SESSION['key'];
        unset($_SESSION['key']);
        $keytime = $_SESSION['keytime'];
        if (!$key || time() - $keytime > 300)
          return 'TIMEOUT';
        
        $strQuery = 
            'SELECT {prefix}users.id AS user_id, type_id, time_out, access_level, passwd '.
            'FROM {prefix}users '.
            'INNER JOIN {prefix}session_type ON {prefix}session_type.id = {prefix}users.type_id '.
            "WHERE login=?";
        $intRes = mysql_param_query($strQuery, array($strLogin));
        if ($row = mysql_fetch_assoc($intRes)) 
        {
            $passwd_md5 = $row['passwd'];
            $md5 = md5($key . $passwd_md5);
            if ($md5 != $strPasswd)
            {
              // Delay so that brute-force attacks become unpractical
              sleep(2);
              return 'FAIL';
            }
              
            $_SESSION['sesTYPEID'] = $row['type_id'];
            $_SESSION['sesLANG'] = 'fi';
            $_SESSION['sesUSERID'] = $row['user_id'];
            $_SESSION['sesACCESSLEVEL'] = $row['access_level'];
            $_SESSION['REMOTE_ADDR'] = $_SERVER['REMOTE_ADDR'];
            $_SESSION['HISTORY'] = array();
            $_SESSION['ACCESSTIME'] = time();
             
            return 'OK';
        }
    }
    // Delay so that brute-force attacks become unpractical
    sleep(2);
    return 'FAIL';
}

function sesEndSession() 
{
  session_destroy();
  return TRUE;
}

function sesVerifySession($redirect = TRUE) 
{
    if (!session_id())
      session_start();
    if (isset($_SESSION['REMOTE_ADDR']) && $_SESSION['REMOTE_ADDR'] == $_SERVER['REMOTE_ADDR'])
    {
      $_SESSION['ACCESSTIME'] = time();
      return TRUE;
    }
    if ($redirect)
    {
      header('Location: ' . _PROTOCOL_ . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/login.php');
    }
    else
    {
      header('HTTP/1.1 403 Forbidden');
    }
    exit;
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
  $arrNew = array();
  foreach ($_SESSION['HISTORY'] as $item)
  {
    if ($item['level'] < $level)
      $arrNew[] = $item;
  }
  $arrNew[] = array('title' => $title, 'url' => $url, 'level' => $level);
  $_SESSION['HISTORY'] = $arrNew;

  return $_SESSION['HISTORY'];
}

function createRandomString($length)
{  
  $chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";  
  $str = '';  
  for ($i = 0; $i < $length; $i++) 
  {  
    $idx = mt_rand(0, strlen($chars) - 1);  
    $str .= substr($chars, $idx, 1);  
  }  
  return $str;  
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
  $res = mysql_param_query('SELECT data FROM {prefix}session where id=?', array($sessionID));
  if ($row = mysql_fetch_row($res))
    return reset($row);
  return '';  
}

function db_session_write($sessionID, $sessionData)
{
  mysql_param_query('REPLACE INTO {prefix}session (id, data) VALUES (?, ?)', array($sessionID, $sessionData));
  return true;
}

function db_session_destroy($sessionID)
{
  mysql_param_query('DELETE FROM {prefix}session WHERE id=?', array($sessionID));
  return true;
}
 
function db_session_gc($sessionMaxAge) 
{
  mysql_param_query('DELETE FROM {prefix}session WHERE session_timestamp<?', array(date('Y-m-d H:i:s', time()-$sessionMaxAge)));
  return true;
}

session_set_save_handler('db_session_open', 'db_session_close', 'db_session_read', 'db_session_write', 'db_session_destroy', 'db_session_gc');
session_name(_SESSION_NAME_);
if (_SESSION_RESTRICT_PATH_)
  session_set_cookie_params(0, dirname($_SERVER['PHP_SELF']) . '/');

?>
