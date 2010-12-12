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

function sesCreateSession($strLogin, $strPasswd) 
{
    if ($strLogin && $strPasswd) 
    {
        $key = $_SESSION['key'];
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
            $key = $_SESSION['key'];
            $md5 = md5($key . $passwd_md5);
            if ($md5 != $strPasswd)
              return 'FAIL';
              
            $_SESSION['sesTYPEID'] = $row['type_id'];
            $_SESSION['sesLANG'] = 'fi';
            $_SESSION['sesTIMEOUT'] = $row['time_out'];
            $_SESSION['sesUSERID'] = $row['user_id'];
            $_SESSION['sesACCESSLEVEL'] = $row['access_level'];
            $_SESSION['REMOTE_ADDR'] = $_SERVER['REMOTE_ADDR'];
            $_SESSION['HISTORY'] = array();
            $_SESSION['ACCESSTIME'] = time();
             
            return 'OK';
        }
    }
    return 'FAIL';
}

function sesEndSession() 
{
  session_destroy();
  return TRUE;
}

function sesVerifySession($redirect = TRUE) 
{
    session_start();
    if ($_SESSION['REMOTE_ADDR'] == $_SERVER['REMOTE_ADDR'] && time() <= $_SESSION['ACCESSTIME'] + $_SESSION['sesTIMEOUT'])
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
  return $_SESSION['key'];
}

function sesUpdateHistory($title, $url, $level)
{
  $arrNew = array();
  foreach ($_SESSION['HISTORY'] as &$item)
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

?>
