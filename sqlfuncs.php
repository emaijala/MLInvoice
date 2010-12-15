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

if (_CHARSET_ == 'UTF-8')
  mysql_query_check('SET NAMES \'utf8\'');

function mysql_query_check($query, $noFail=false)
{
  $query = str_replace('{prefix}', _DB_PREFIX_ . '_', $query);
//  error_log("QUERY: $query");
  $intRes = mysql_query($query);
  if ($intRes === FALSE)
  {
    $intError = mysql_errno();
    error_log("Query '$query' failed: ($intError) " . mysql_error());
    if (!$noFail)
    {
      switch ($intError)
      {
      case 1451: die($GLOBALS['locDBERRORFOREIGNKEY']);
      default: die($GLOBALS['locDBERROR']);
      }
    }
  }
  return $intRes;
}

function mysql_param_query($query, $params=false, $noFail=false) 
{
  if ($params) 
  {
    foreach ($params as &$v) 
    { 
      if (is_null($v))
        $v = 'NULL';
      elseif (is_array($v))
      {
        $t = '';
        foreach ($v as $v2)
        {
          if ($t)
            $t .= ',';
          $v2 = mysql_real_escape_string($v2); 
          if (!is_numeric($v2) || (strlen(trim($v2)) > 0 && substr(trim($v2), 0, 1) == '0'))
            $v2 = "'$v2'";
          $t .= $v2;
        }
        $v = $t;
      }
      else
      {
        $v = mysql_real_escape_string($v); 
        if (!is_numeric($v) || (strlen(trim($v)) > 0 && substr(trim($v), 0, 1) == '0'))
          $v = "'$v'";
      }
    }
    $sql_query = vsprintf(str_replace("?","%s",$query), $params);
    return mysql_query_check($sql_query, $noFail);
  }    
  return mysql_query_check($query);
} 

function create_db_dump()
{
  $in_tables = array('invoice_state', 'row_type', 'company_type', 'base', 'company', 'company_contact',
    'product', 'invoice', 'invoice_row', 'session_type', 'users', 'quicksearch');

  $filename = 'vllasku_backup_' . date('Ymd') . '.sql';
  header('Content-type: text/x-sql');
  header("Content-Disposition: attachment; filename=\"$filename\"");

  if (_CHARSET_ == 'UTF-8')
    echo("SET NAMES 'utf8';\n\n");

  $tables = array();
  foreach ($in_tables as $table)
  { 
    $tables[] = _DB_PREFIX_ . "_$table";
  }

  $res = mysql_query_check('SHOW TABLES');
  while ($row = mysql_fetch_row($res))
  {
    if (!in_array($row[0], $tables))
    {
      error_log("Adding unlisted table $row[0] to export");
      $tables[] = $row[0];
    }
  }
  foreach ($tables as $table)
  {
    $res = mysql_query_check("show create table `$table`");
    $row = mysql_fetch_assoc($res);
    if (!$row)
      die("Could not read table definition for table $table");
    echo $row['Create Table'] . ";\n\n";
    
    $res = mysql_query_check("select * from `$table`");
    $field_count = mysql_num_fields($res);
    $field_defs = array();
    for ($i = 0; $i < $field_count; $i++)
    {
      $field_def = mysql_fetch_field($res, $i);
      $field_defs[] = $field_def->type;
    }
    while ($row = mysql_fetch_row($res))
    {
      echo "INSERT INTO `$table` VALUES (";
      for ($i = 0; $i < $field_count; $i++)
      {
        if ($i > 0)
          echo ', ';
        $value = $row[$i];
        $type = $field_defs[$i];
        if (is_null($value))
          echo 'null';
        elseif ($type == 'int' || $type == 'real')
          echo $value;
        else
          echo '\'' . addslashes($value) . '\'';
      }
      echo ");\n";
    }
    echo "\n";
  }
}

?>