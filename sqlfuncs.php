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
Includefile : sqlfuncs.php
    SQL related functions. 
    Creates sql-connection - no need to create in other files.

Provides functions : 
    
    
Includes files: settings.php

Todo : 
    
********************************************************************/

require 'settings.php';

/* Connecting, selecting database */

$link = mysql_connect(_DB_SERVER_, _DB_USERNAME_, _DB_PASSWORD_)
   or die("Could not connect : " . mysql_error());

mysql_select_db(_DB_NAME_) or die("Could not select database: " . mysql_error());

function mysql_query_check($query)
{
  $intRes = mysql_query($query);
  if ($intRes === FALSE)
  {
    error_log("Query '$query' failed: " . mysql_error());
    die($GLOBALS['locDBERROR']);
  }
  return $intRes;
}

function mysql_param_query($query, $params=false) 
{
  if ($params) 
  {
    foreach ($params as &$v) 
    { 
      $v = mysql_real_escape_string($v); 
    }
    $sql_query = vsprintf(str_replace("?","'%s'",$query), $params);   
    return mysql_query_check($sql_query);
  }    
  return mysql_query_check($query);
} 

?>