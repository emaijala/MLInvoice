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
        $strLogin = mysql_real_escape_string($strLogin);
        $strPasswd = mysql_real_escape_string($strPasswd);
        $strQuery = 
            "SELECT ". _DB_PREFIX_. "_users.id AS user_id, type_id, time_out, access_level ".
            "FROM ". _DB_PREFIX_. "_users ".
            "INNER JOIN ". _DB_PREFIX_. "_session_type ON ". _DB_PREFIX_. "_session_type.id = ". _DB_PREFIX_. "_users.type_id ".
            "WHERE login='".$strLogin."' AND passwd=md5('".$strPasswd."')";
        $intRes = mysql_query($strQuery);
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
        "DELETE FROM ". _DB_PREFIX_. "_session where ip='". $strIP ."';";
//    $intRes = mysql_query($strQuery);


    $strQuery = 
        "INSERT INTO ". _DB_PREFIX_. "_session(id, ip, timestamp, timeout, type_id, user_id, access_level) ".
        "VALUES('". $strSesID ."', '". $strIP ."', ". $intTimeStamp .", ".
        $GLOBALS['sesTIMEOUT'] .", ".
        $GLOBALS['sesTYPEID']. ", ". $GLOBALS['sesUSERID'] .", ". $GLOBALS['sesACCESSLEVEL']. ");";
    $intRes = mysql_query($strQuery);
    if( $intRes ) {        
        return $strSesID;
    }
    
    return FALSE;
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

    $strSesID = mysql_real_escape_string($strSesID);
    $strQuery = 
        "DELETE FROM ". _DB_PREFIX_. "_session where id='". $strSesID ."';";
    $intRes = mysql_query($strQuery);

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
    $strSesID = mysql_real_escape_string($strSesID);

    $strIP = $_SERVER['REMOTE_ADDR'];
    $intTimeStamp = time();
    
    $strQuery = 
        "SELECT * FROM ". _DB_PREFIX_. "_session ".
        "WHERE id='". $strSesID ."' AND ip='". $strIP ."';";
    $intRes = mysql_query($strQuery);
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
                "UPDATE ". _DB_PREFIX_. "_session SET timestamp=". $intTimeStamp ." ".
                "WHERE id='". $strSesID ."';";
                
            $intRes = mysql_query($strQuery);
            return TRUE;
        }
    }
    /*kun https
    header("Location: https://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/redirect.html");
    */
    
    header("Location: ". _PROTOCOL_ . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/redirect.html");
    return FALSE;
}

?>
