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

/********************************************************************
Includefile : sessionfuncs.php
    Sessionhandling functions

Provides functions : 
    
    
Includes files: -none-

Todo : 
    
********************************************************************/

function sesValidateUser( $strLogin, $strPasswd ) {
/********************************************************************
Function : sesValidateUser
    Checks if given login & passwd are valid

Args : 
    $strLogin (str): loginname
    $strPasswd (str): password

Globals set :
    $GLOBALS['sesLANG'] (str) : session language
    $GLOBALS['sesTYPE'] (int) : session type
    $GLOBALS['sesTIMEOUT'] (int) : session timelimit

Return : 
    if valid : TRUE
    if !valid : FALSE

Todo : 
********************************************************************/
    if( $strLogin && $strPasswd ) {
        $strQuery = 
            'SELECT {prefix}users.id AS user_id, type_id, time_out, access_level '.
            'FROM {prefix}users '.
            'INNER JOIN {prefix}session_type ON {prefix}session_type.id = {prefix}users.type_id '.
            "WHERE login=? AND passwd=md5(?)";
        $intRes = mysql_param_query($strQuery, array($strLogin, $strPasswd));
        $intNumRows = mysql_num_rows($intRes);
        if( $intNumRows ) {
            $GLOBALS['sesTYPEID'] = mysql_result($intRes, 0, "type_id");
            $GLOBALS['sesLANG'] = 'fi';
            $GLOBALS['sesTIMEOUT'] = mysql_result($intRes, 0, "time_out");
            $GLOBALS['sesUSERID'] = mysql_result($intRes, 0, "user_id");
            $GLOBALS['sesACCESSLEVEL'] = mysql_result($intRes, 0, "access_level");
            
            return TRUE;
        }
    }
    else {
        return FALSE;
    }
}

function sesCreateSession() {
/********************************************************************
Function : sesCreateSession
    Opens new session for validated user

Args : 


Return : 
    if OK : $strSesID (str): session ID
    else : FALSE
Todo : 
********************************************************************/
    $strIP = $_SERVER['REMOTE_ADDR'];
    $intTimeStamp = time();
    $intRandVal = mt_rand();
    $strSesID = sha1($intTimeStamp.$strIP.$intRandVal);

    $strQuery = 
        'INSERT INTO {prefix}session(id, ip, timestamp, timeout, type_id, user_id, access_level) '.
        "VALUES(?, ?, ?, ?, ?, ?, ?)";
    mysql_param_query($strQuery, array($strSesID, $strIP, $intTimeStamp, $GLOBALS['sesTIMEOUT'], $GLOBALS['sesTYPEID'], 
        $GLOBALS['sesUSERID'], $GLOBALS['sesACCESSLEVEL']));

    // cleanup old sessions
    $intTimeStamp = time();
    $strQuery = 
        "DELETE FROM {prefix}session_history ".
        "WHERE session_id in (select s.id from {prefix}session s where ?-s.timestamp > s.timeout)";
    mysql_param_query($strQuery, array($intTimeStamp));
    $strQuery = 
        "DELETE FROM {prefix}session ".
        "WHERE ?-timestamp > timeout";
    mysql_param_query($strQuery, array($intTimeStamp));
        
    return $strSesID;
}
function sesEndSession($strSesID) {
/********************************************************************
Function : sesEndSession
    Terminates the current session

Args : 
    $strSesID (str) : session ID

Return : TRUE 

Todo : 
********************************************************************/

    $strQuery = "DELETE FROM {prefix}session_history where session_id=?";
    mysql_param_query($strQuery, array($strSesID));

    $strQuery = "DELETE FROM {prefix}session where id=?";
    mysql_param_query($strQuery, array($strSesID));

    return TRUE;
}

function sesCheckSession( $strSesID ) {
/********************************************************************
Function : sesCheckSession
    Checks if session is still valid & updates session timestamp

Args : 
    $strSesID (str) : session ID
    
Globals set :
    $GLOBALS['sesLANG'] (str) : session language
    $GLOBALS['sesTYPE'] (int) : session type
    $GLOBALS['sesUSERID'] (int) : session userid
    
Return : 
    if session OK : TRUE
    else :          FALSE

Todo : 
********************************************************************/
    $strIP = $_SERVER['REMOTE_ADDR'];
    $intTimeStamp = time();
    
    $strQuery = 
        "SELECT * FROM {prefix}session ".
        "WHERE id=? AND ip=?";
    $intRes = mysql_param_query($strQuery, array($strSesID,  $strIP));
    $intNumRows = mysql_num_rows($intRes);
    if( $intNumRows ) {
        $intOldTimeStamp = mysql_result($intRes, 0, "timestamp");
        $intTimeLimit = mysql_result($intRes, 0, "timeout");
        
        if( $intTimeStamp - $intOldTimeStamp < $intTimeLimit ) {
            $GLOBALS['sesTYPEID'] = mysql_result($intRes, 0, "type_id");
            $GLOBALS['sesLANG'] = 'fi';
            $GLOBALS['sesID'] = $strSesID;
            $GLOBALS['sesUSERID'] = mysql_result($intRes, 0, "user_id");
            $GLOBALS['sesACCESSLEVEL'] = mysql_result($intRes, 0, "access_level");
            $strQuery =
                "UPDATE {prefix}session SET timestamp=". $intTimeStamp ." ".
                "WHERE id='". $strSesID ."';";
                
            $intRes = mysql_query_check($strQuery);
            return TRUE;
        }
    }
    header("Location: ". _PROTOCOL_ . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/denied.html");
    return FALSE;
}

function sesVerifySession()
{
  $strSes = isset($_REQUEST['ses']) ? $_REQUEST['ses'] : FALSE;
  if (!sesCheckSession($strSes))
    die;
  return $strSes;
}

function sesUpdateHistory($strSesID, $title, $url, $level)
{
 /* $boolReset = TRUE;
  foreach ($_GET as $key=>$value)
  {
    if ($key != 'ses' && $key != 'func')
    {
      $boolReset = FALSE;
      break;
    }
  }
  if ($boolReset)
  {
    $strQuery = 'DELETE FROM {prefix}session_history where session_id=?';
    mysql_param_query($strQuery, array($strSesID));    
  }
  else*/
  {
    $strQuery = 'DELETE FROM {prefix}session_history where session_id=? AND level>=?';
    mysql_param_query($strQuery, array($strSesID, $level));    
  }
  $strQuery = 'INSERT INTO {prefix}session_history (session_id, timestamp, title, url, level) value (?, ?, ?, ?, ?)';
  mysql_param_query($strQuery, array($strSesID, time(), $title, $url, $level));    
  
/*  if ($boolReset)
  {
    return array(array('title' => $title, 'url' => $url));
  }*/
  $arrHistory = array();
  $strQuery = 'SELECT title, url FROM {prefix}session_history WHERE session_id=? ORDER BY timestamp';
  $intRes = mysql_param_query($strQuery, array($strSesID));
  while ($row = mysql_fetch_assoc($intRes))
  {
    $arrHistory[] = $row;
  }
  return $arrHistory;
}

?>
