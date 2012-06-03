<?php
/*******************************************************************************
VLLasku: web-based invoicing application.
Copyright (C) 2010-2012 Ere Maijala

Portions based on:
PkLasku : web-based invoicing software.
Copyright (C) 2004-2008 Samu Reinikainen

This program is free software. See attached LICENSE.

*******************************************************************************/

/*******************************************************************************
VLLasku: web-pohjainen laskutusohjelma.
Copyright (C) 2010-2012 Ere Maijala

Perustuu osittain sovellukseen:
PkLasku : web-pohjainen laskutusohjelmisto.
Copyright (C) 2004-2008 Samu Reinikainen

Tämä ohjelma on vapaa. Lue oheinen LICENSE.

*******************************************************************************/

require_once 'config.php';

// Connect to database server
$link = mysql_connect(_DB_SERVER_, _DB_USERNAME_, _DB_PASSWORD_)
   or die("Could not connect : " . mysql_error());

// Select database
mysql_select_db(_DB_NAME_) or die("Could not select database: " . mysql_error());

if (_CHARSET_ == 'UTF-8')
  mysql_query_check('SET NAMES \'utf8\'');

function mysql_query_check($query, $noFail=false)
{
  $query = str_replace('{prefix}', _DB_PREFIX_ . '_', $query);
  //error_log("QUERY: $query");
  $intRes = mysql_query($query);
  if ($intRes === FALSE)
  {
    $intError = mysql_errno();
    if (strlen($query) > 1024)
      $query = substr($query, 0, 1024) . '[' . (strlen($query) - 1024) . ' more characters]';
    error_log("Query '$query' failed: ($intError) " . mysql_error());
    if (!$noFail)
    {
      header('HTTP/1.1 500 Internal Server Error');
      if (!defined('_DB_VERBOSE_ERRORS_') || !_DB_VERBOSE_ERRORS_)
        die($GLOBALS['locDBError']);  
      die(htmlspecialchars("Query '$query' failed: ($intError) " . mysql_error()));
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
        if (!is_numeric($v) || (strlen(trim($v)) > 1 && substr(trim($v), 0, 1) == '0'))
          $v = "'$v'";
      }
    }
    $sql_query = vsprintf(str_replace("?","%s",$query), $params);
    return mysql_query_check($sql_query, $noFail);
  }    
  return mysql_query_check($query);
} 

function mysql_fetch_value($result)
{
  $row = mysql_fetch_row($result);
  return $row[0];
}

function mysql_fetch_prefixed_assoc($result)
{
  if (!($row = mysql_fetch_row($result)))
    return null;

  $assoc = Array();
  $columns = mysql_num_fields($result);
  for ($i = 0; $i < $columns; $i++)
  {
    $table = mysql_field_table($result, $i);
    $field = mysql_field_name($result, $i);
    if (substr($table, 0, strlen(_DB_PREFIX_) + 1) == _DB_PREFIX_ . '_')
      $assoc[$field] = $row[$i];
    else
      $assoc["$table.$field"] = $row[$i];
  }
  return $assoc;
}

function create_db_dump()
{
  $in_tables = array('invoice_state', 'row_type', 'company_type', 'base', 'company', 'company_contact',
    'product', 'invoice', 'invoice_row', 'session_type', 'users', 'quicksearch', 'settings', 'session', 'print_template');

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

  $res = mysql_query_check("SHOW TABLES LIKE '" . _DB_PREFIX_ . "_%'");
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
    $res = mysql_query_check("show create table $table");
    $row = mysql_fetch_assoc($res);
    if (!$row)
      die("Could not read table definition for table $table");
    echo $row['Create Table'] . ";\n\n";
    
    $res = mysql_query_check("show fields from $table");
    $field_count = mysql_num_rows($res);
    $field_defs = array();
    $columns = '';
    while ($row = mysql_fetch_assoc($res))
    {
      $field_defs[] = $row;
      if ($columns)
        $columns .= ', ';
      $columns .= $row['Field'];
    }
    // Don't dump current sessions
    if ($table == _DB_PREFIX_ . '_session')
      continue;
    
    $res = mysql_query_check("select * from $table");
    while ($row = mysql_fetch_row($res))
    {
      echo "INSERT INTO `$table` ($columns) VALUES (";
      for ($i = 0; $i < $field_count; $i++)
      {
        if ($i > 0)
          echo ', ';
        $value = $row[$i];
        $type = $field_defs[$i]['Type'];
        if (is_null($value))
          echo 'null';
        elseif (substr($type, 0, 3) == 'int' || substr($type, 0, 7) == 'decimal')
          echo $value;
        elseif ($value && ($type == 'longblob' || strpos($value, "\n")))
          echo '0x' . bin2hex($value);
        else
          echo '\'' . addslashes($value) . '\'';
      }
      echo ");\n";
    }
    echo "\n";
  }
}

function table_valid($table)
{
  $tables = array();
  $res = mysql_query_check('SHOW TABLES');
  while ($row = mysql_fetch_row($res))
  {
    $tables[] = $row[0];
  }
  return in_array(_DB_PREFIX_ . "_$table", $tables);
}

/**
 * Verify database status and upgrade as necessary.
 * Expects all pre-1.6.0 changes to have been already made.
 * 
 * @return string status (OK|UPGRADED|FAILED)
 */
function verifyDatabase()
{
  $res = mysql_query_check("SHOW TABLES LIKE '{prefix}state'");
  if (mysql_num_rows($res) == 0) {
    $res = mysql_query_check(<<<EOT
CREATE TABLE {prefix}state (
  id char(32) NOT NULL,
  data varchar(100) NULL,
  PRIMARY KEY (id)
) ENGINE=INNODB CHARACTER SET utf8 COLLATE utf8_swedish_ci;
EOT
    , true);
    if ($res === false) {
      return 'FAILED';
    }
    mysql_query_check("REPLACE INTO {prefix}state (id, data) VALUES ('version', '15')");
  } 

  $res = mysql_param_query('SELECT data FROM {prefix}state WHERE id=?', array('version'));
  $version = mysql_fetch_value($res);
  $updates = array();
  if ($version < 16) {
    $updates = array(
      "ALTER TABLE {prefix}invoice ADD CONSTRAINT FOREIGN KEY (base_id) REFERENCES {prefix}base(id)",
      "ALTER TABLE {prefix}invoice ADD COLUMN interval_type int(11) NOT NULL default 0",
      "ALTER TABLE {prefix}invoice ADD COLUMN next_interval_date int(11) default NULL",
      "REPLACE INTO {prefix}state (id, data) VALUES ('version', '16')"
    );
  }
  
  if (!empty($updates)) {
    foreach ($updates as $update) {
      $res = mysql_query_check($update, true);
      if ($res === false) {
        error_log('Database upgrade query failed. Please execute the following queries manually: ');
        $ok = true;
        foreach ($updates as $update2) {
          if ($update === $update2) {
            $ok = false;
          }
          if ($ok) {
            continue;
          }
          $update2 = str_replace('{prefix}', _DB_PREFIX_ . '_', $update2);
          error_log($update2);
        }
        return 'FAILED';
      }
    }
    return 'UPGRADED';
  }
  return 'OK';  
}
