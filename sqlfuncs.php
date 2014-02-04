<?php
/*******************************************************************************
MLInvoice: web-based invoicing application.
Copyright (C) 2010-2012 Ere Maijala

Portions based on:
PkLasku : web-based invoicing software.
Copyright (C) 2004-2008 Samu Reinikainen

This program is free software. See attached LICENSE.

*******************************************************************************/

/*******************************************************************************
MLInvoice: web-pohjainen laskutusohjelma.
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

mysql_query_check('SET AUTOCOMMIT=1');

function extractSearchTerm(&$searchTerms, &$field, &$operator, &$term, &$boolean)
{
  if (!preg_match('/^([\w\.\_]+)\s*(=|!=|<=?|>=?|LIKE)\s*(.+)/', $searchTerms, $matches)) {
    if (!preg_match('/^([\w\.\_]+)\s+(IN)\s+(.+)/', $searchTerms, $matches)) {
      return false;
    }
  }
  $field = $matches[1];
  $operator = $matches[2];
  $rest = $matches[3];
  $term = '';
  $inQuotes = false;
  $inParenthesis = 0;
  $escaped = false;
  while ($rest != '')
  {
    $ch = substr($rest, 0, 1);
    $rest = substr($rest, 1);
    if ($escaped) {
      $escaped = false;
      $term .= $ch;
      continue;
    }
    if ($ch == '\\') {
      $escaped = true;
      continue;
    }

    if ($ch == "'") {
      $inQuotes = !$inQuotes;
      continue;
    }
    if ($ch == '(') {
      ++$inParenthesis;
    } elseif ($ch == ')') {
      if (--$inParenthesis < 0) {
        die('Unbalanced parenthesis');
      }
    }
    if ($ch == ' ' && !$inQuotes && $inParenthesis == 0)
      break;
    $term .= $ch;
  }
  if ($inParenthesis > 0) {
    die('Unbalanced parenthesis');
  }
  if (substr($rest, 0, 4) == 'AND ')
  {
    $boolean = ' AND ';
    $searchTerms = substr($rest, 4);
  }
  elseif (substr($rest, 0, 3) == 'OR ')
  {
    $boolean = ' OR ';
    $searchTerms = substr($rest, 3);
  }
  else
  {
    $boolean = '';
    $searchTerms = '';
  }
  return $term != '';
}

function createWhereClause($astrSearchFields, $strSearchTerms, &$arrQueryParams, $leftAnchored = false)
{
  $astrTerms = explode(" ", $strSearchTerms);
  $strWhereClause = "(";
  $termPrefix = $leftAnchored ? '' : '%';
  for( $i = 0; $i < count($astrTerms); $i++ ) {
      if ($astrTerms[$i]) {
          $strWhereClause .= '(';
          for( $j = 0; $j < count($astrSearchFields); $j++ ) {
              if( $astrSearchFields[$j]['type'] == "TEXT" ) {
                  $strWhereClause .= $astrSearchFields[$j]['name'] . " LIKE ? OR ";
                  $arrQueryParams[] = $termPrefix . $astrTerms[$i] . '%';
              }
              elseif( $astrSearchFields[$j]['type'] == "INT" && preg_match ("/^([0-9]+)$/", $astrTerms[$i]) ) {
                  $strWhereClause .= $astrSearchFields[$j]['name'] . " = ?" . " OR ";
                  $arrQueryParams[] = $astrTerms[$i];
              }
              elseif( $astrSearchFields[$j]['type'] == "PRIMARY" && preg_match ("/^([0-9]+)$/", $intID) ) {
                  $strWhereClause =
                      "WHERE ". $astrSearchFields[$j]['name']. " = ?     ";
                  $arrQueryParams = array($intID);
                  unset($astrSearchFields);
                  break 2;
              }

          }
          $strWhereClause = substr( $strWhereClause, 0, -3) . ") AND ";
      }
  }
  $strWhereClause = substr( $strWhereClause, 0, -4) . ')';
  return $strWhereClause;
}

function mysql_query_check($query, $noFail=false)
{
  $query = str_replace('{prefix}', _DB_PREFIX_ . '_', $query);
  $startTime = microtime(true);
  $intRes = mysql_query($query);
  if (defined('_SQL_DEBUG_')) {
    error_log('QUERY [' . round(microtime(true) - $startTime, 4) . "s]: $query");
  }
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
          if (!is_numeric($v2) || (strlen(trim($v2)) > 0 && substr(trim($v2), 0, 1) == '0')) {
            if (substr(trim($v2), 0, 1 != '(')) {
              $v2 = "'$v2'";
            }
          }
          $t .= $v2;
        }
        $v = $t;
      }
      else
      {
        $v = mysql_real_escape_string($v);
        if (!is_numeric($v) || (strlen(trim($v)) > 1 && substr(trim($v), 0, 1) == '0')) {
          if (substr(trim($v), 0, 1) != '(') {
            $v = "'$v'";
          }
        }
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
    'product', 'session_type', 'users', 'invoice', 'invoice_row', 'quicksearch', 'settings', 'session', 'print_template', 'state');

  $filename = 'mlinvoice_backup_' . date('Ymd') . '.sql';
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
    $updates = array_merge($updates, array(
      'ALTER TABLE {prefix}invoice ADD CONSTRAINT FOREIGN KEY (base_id) REFERENCES {prefix}base(id)',
      'ALTER TABLE {prefix}invoice ADD COLUMN interval_type int(11) NOT NULL default 0',
      'ALTER TABLE {prefix}invoice ADD COLUMN next_interval_date int(11) default NULL',
      "REPLACE INTO {prefix}state (id, data) VALUES ('version', '16')"
    ));
  }
  if ($version < 17) {
    $updates = array_merge($updates, array(
      "ALTER TABLE {prefix}invoice_state CHANGE COLUMN name name varchar(255)",
      "UPDATE {prefix}invoice_state set name='StateOpen' where id=1",
      "UPDATE {prefix}invoice_state set name='StateSent' where id=2",
      "UPDATE {prefix}invoice_state set name='StatePaid' where id=3",
      "UPDATE {prefix}invoice_state set name='StateAnnulled' where id=4",
      "UPDATE {prefix}invoice_state set name='StateFirstReminder' where id=5",
      "UPDATE {prefix}invoice_state set name='StateSecondReminder' where id=6",
      "UPDATE {prefix}invoice_state set name='StateDebtCollection' where id=7",
      "UPDATE {prefix}print_template set name='PrintInvoiceFinnish' where name='Lasku'",
      "UPDATE {prefix}print_template set name='PrintDispatchNoteFinnish' where name='Lähetysluettelo'",
      "UPDATE {prefix}print_template set name='PrintReceiptFinnish' where name='Kuitti'",
      "UPDATE {prefix}print_template set name='PrintEmailFinnish' where name='Email'",
      "UPDATE {prefix}print_template set name='PrintInvoiceEnglish' where name='Invoice'",
      "UPDATE {prefix}print_template set name='PrintReceiptEnglish' where name='Receipt'",
      "UPDATE {prefix}print_template set name='PrintFinvoice' where name='Finvoice'",
      "UPDATE {prefix}print_template set name='PrintFinvoiceStyled' where name='Finvoice Styled'",
      "UPDATE {prefix}print_template set name='PrintInvoiceFinnishWithVirtualBarcode' where name='Lasku virtuaaliviivakoodilla'",
      "UPDATE {prefix}print_template set name='PrintInvoiceFinnishFormless' where name='Lomakkeeton lasku'",
      "INSERT INTO {prefix}print_template (name, filename, parameters, output_filename, type, order_no, inactive) VALUES ('PrintInvoiceEnglishWithVirtualBarcode', 'invoice_printer.php', 'invoice,en,Y', 'invoice_%d.pdf', 'invoice', 70, 1)",
      "INSERT INTO {prefix}print_template (name, filename, parameters, output_filename, type, order_no, inactive) VALUES ('PrintInvoiceEnglishFormless', 'invoice_printer_formless.php', 'invoice,en,N', 'invoice_%d.pdf', 'invoice', 80, 1)",
      "ALTER TABLE {prefix}row_type CHANGE COLUMN name name varchar(255)",
      "UPDATE {prefix}row_type set name='TypeHour' where name='h'",
      "UPDATE {prefix}row_type set name='TypeDay' where name='pv'",
      "UPDATE {prefix}row_type set name='TypeMonth' where name='kk'",
      "UPDATE {prefix}row_type set name='TypePieces' where name='kpl'",
      "UPDATE {prefix}row_type set name='TypeYear' where name='vuosi'",
      "UPDATE {prefix}row_type set name='TypeLot' where name='erä'",
      "UPDATE {prefix}row_type set name='TypeKilometer' where name='km'",
      "UPDATE {prefix}row_type set name='TypeKilogram' where name='kg'",
      "REPLACE INTO {prefix}state (id, data) VALUES ('version', '17')"
    ));
  }
  if ($version < 18) {
    $updates = array_merge($updates, array(
      'ALTER TABLE {prefix}base ADD COLUMN country varchar(255) default NULL',
      'ALTER TABLE {prefix}company ADD COLUMN country varchar(255) default NULL',
      "REPLACE INTO {prefix}state (id, data) VALUES ('version', '18')"
    ));
  }
  if ($version < 19) {
    $updates = array_merge($updates, array(
      "UPDATE {prefix}session_type set name='SessionTypeUser' where name='Käyttäjä'",
      "UPDATE {prefix}session_type set name='SessionTypeAdmin' where name='Ylläpitäjä'",
      "UPDATE {prefix}session_type set name='SessionTypeBackupUser' where name='Käyttäjä - varmuuskopioija'",
      "UPDATE {prefix}session_type set name='SessionTypeReadOnly' where name='Vain laskujen ja raporttien tarkastelu'",
      "REPLACE INTO {prefix}state (id, data) VALUES ('version', '19')"
    ));
  }
  if ($version < 20) {
    $updates = array_merge($updates, array(
      "ALTER TABLE {prefix}product CHANGE COLUMN unit_price unit_price decimal(15,5)",
      "ALTER TABLE {prefix}invoice_row CHANGE COLUMN price price decimal(15,5)",
      "ALTER TABLE {prefix}product CHANGE COLUMN discount discount decimal(4,1) NULL",
      "ALTER TABLE {prefix}invoice_row CHANGE COLUMN discount discount decimal(4,1) NULL",
      "REPLACE INTO {prefix}state (id, data) VALUES ('version', '20')"
    ));
  }
  if ($version < 21) {
    $updates = array_merge($updates, array(
      "INSERT INTO {prefix}print_template (name, filename, parameters, output_filename, type, order_no, inactive) VALUES ('PrintInvoiceSwedish', 'invoice_printer.php', 'invoice,sv-FI,Y', 'faktura_%d.pdf', 'invoice', 90, 1)",
      "INSERT INTO {prefix}print_template (name, filename, parameters, output_filename, type, order_no, inactive) VALUES ('PrintInvoiceSwedishFormless', 'invoice_printer_formless.php', 'invoice,sv-FI,N', 'faktura_%d.pdf', 'invoice', 100, 1)",
      "REPLACE INTO {prefix}state (id, data) VALUES ('version', '21')"
    ));
  }
  if ($version < 22) {
    $updates = array_merge($updates, array(
      "INSERT INTO {prefix}print_template (name, filename, parameters, output_filename, type, order_no, inactive) VALUES ('PrintEmailReceiptFinnish', 'invoice_printer_email.php', 'receipt', 'kuitti_%d.pdf', 'invoice', 110, 1)",
      "INSERT INTO {prefix}print_template (name, filename, parameters, output_filename, type, order_no, inactive) VALUES ('PrintEmailReceiptSwedish', 'invoice_printer_email.php', 'receipt,sv-FI', 'kvitto_%d.pdf', 'invoice', 120, 1)",
      "INSERT INTO {prefix}print_template (name, filename, parameters, output_filename, type, order_no, inactive) VALUES ('PrintEmailReceiptEnglish', 'invoice_printer_email.php', 'receipt,en', 'receipt_%d.pdf', 'invoice', 130, 1)",
      "REPLACE INTO {prefix}state (id, data) VALUES ('version', '22')"
    ));
  }
  if ($version < 23) {
    $updates = array_merge($updates, array(
      'ALTER TABLE {prefix}product ADD COLUMN order_no int(11) default NULL',
      'ALTER TABLE {prefix}users CHANGE COLUMN name name varchar(255)',
      'ALTER TABLE {prefix}users CHANGE COLUMN login login varchar(255)',
      "REPLACE INTO {prefix}state (id, data) VALUES ('version', '23')"
    ));
  }
  if ($version < 24) {
    $updates = array_merge($updates, array(
      "INSERT INTO {prefix}print_template (name, filename, parameters, output_filename, type, order_no, inactive) VALUES ('PrintOrderConfirmationFinnish', 'invoice_printer_order_confirmation.php', 'receipt', 'tilausvahvistus_%d.pdf', 'invoice', 140, 1)",
      "INSERT INTO {prefix}print_template (name, filename, parameters, output_filename, type, order_no, inactive) VALUES ('PrintOrderConfirmationSwedish', 'invoice_printer_order_confirmation.php', 'receipt,sv-FI', 'orderbekraftelse_%d.pdf', 'invoice', 150, 1)",
      "INSERT INTO {prefix}print_template (name, filename, parameters, output_filename, type, order_no, inactive) VALUES ('PrintOrderConfirmationEnglish', 'invoice_printer_order_confirmation.php', 'receipt,en', 'order_confirmation_%d.pdf', 'invoice', 160, 1)",
      "REPLACE INTO {prefix}state (id, data) VALUES ('version', '24')"
    ));
  }
  if ($version < 25) {
    $updates = array_merge($updates, array(
    <<<EOT
CREATE TABLE {prefix}delivery_terms (
  id int(11) NOT NULL auto_increment,
  deleted tinyint NOT NULL default 0,
  name varchar(255) default NULL,
  order_no int(11) default NULL,
  PRIMARY KEY (id)
) ENGINE=INNODB CHARACTER SET utf8 COLLATE utf8_swedish_ci
EOT
      ,
      <<<EOT
CREATE TABLE {prefix}delivery_method (
  id int(11) NOT NULL auto_increment,
  deleted tinyint NOT NULL default 0,
  name varchar(255) default NULL,
  order_no int(11) default NULL,
  PRIMARY KEY (id)
) ENGINE=INNODB CHARACTER SET utf8 COLLATE utf8_swedish_ci
EOT
      ,
      'ALTER TABLE {prefix}invoice ADD COLUMN delivery_terms_id int(11) default NULL',
      'ALTER TABLE {prefix}invoice ADD CONSTRAINT FOREIGN KEY (delivery_terms_id) REFERENCES {prefix}delivery_terms(id)',
      'ALTER TABLE {prefix}invoice ADD COLUMN delivery_method_id int(11) default NULL',
      'ALTER TABLE {prefix}invoice ADD CONSTRAINT FOREIGN KEY (delivery_method_id) REFERENCES {prefix}delivery_method(id)',
      'ALTER TABLE {prefix}company ADD COLUMN delivery_terms_id int(11) default NULL',
      'ALTER TABLE {prefix}company ADD CONSTRAINT FOREIGN KEY (delivery_terms_id) REFERENCES {prefix}delivery_terms(id)',
      'ALTER TABLE {prefix}company ADD COLUMN delivery_method_id int(11) default NULL',
      'ALTER TABLE {prefix}company ADD CONSTRAINT FOREIGN KEY (delivery_method_id) REFERENCES {prefix}delivery_method(id)',
      "REPLACE INTO {prefix}state (id, data) VALUES ('version', '25')"
    ));
  }

  if ($version < 26) {
    $updates = array_merge($updates, array(
      'CREATE INDEX {prefix}company_name on {prefix}company(company_name)',
      'CREATE INDEX {prefix}company_id on {prefix}company(company_id)',
      'CREATE INDEX {prefix}company_deleted on {prefix}company(deleted)',
      'CREATE INDEX {prefix}invoice_no on {prefix}invoice(invoice_no)',
      'CREATE INDEX {prefix}invoice_ref_number on {prefix}invoice(ref_number)',
      'CREATE INDEX {prefix}invoice_name on {prefix}invoice(name)',
      'CREATE INDEX {prefix}invoice_deleted on {prefix}invoice(deleted)',
      'CREATE INDEX {prefix}base_name on {prefix}base(name)',
      'CREATE INDEX {prefix}base_deleted on {prefix}base(deleted)',
      'CREATE INDEX {prefix}product_name on {prefix}product(product_name)',
      'CREATE INDEX {prefix}product_code on {prefix}product(product_code)',
      'CREATE INDEX {prefix}product_deleted on {prefix}product(deleted)',
      'CREATE INDEX {prefix}product_order_no_deleted on {prefix}product(order_no, deleted)',
      'CREATE INDEX {prefix}users_name on {prefix}users(name)',
      'CREATE INDEX {prefix}users_deleted on {prefix}users(deleted)',
      "REPLACE INTO {prefix}state (id, data) VALUES ('version', '26')"
    ));
  }

  if ($version < 27) {
    $updates = array_merge($updates, array(
      "INSERT INTO {prefix}invoice_state (name, order_no) VALUES ('StatePaidInCash', 17)",
      "REPLACE INTO {prefix}state (id, data) VALUES ('version', '27')"
    ));
  }

  if ($version < 28) {
    $updates = array_merge($updates, array(
      "INSERT INTO {prefix}print_template (name, filename, parameters, output_filename, type, order_no, inactive) VALUES ('PrintOrderConfirmationEmailFinnish', 'invoice_printer_order_confirmation_email.php', 'receipt', 'tilausvahvistus_%d.pdf', 'invoice', 170, 1)",
      "INSERT INTO {prefix}print_template (name, filename, parameters, output_filename, type, order_no, inactive) VALUES ('PrintOrderConfirmationEmailSwedish', 'invoice_printer_order_confirmation_email.php', 'receipt,sv-FI', 'orderbekraftelse_%d.pdf', 'invoice', 180, 1)",
      "INSERT INTO {prefix}print_template (name, filename, parameters, output_filename, type, order_no, inactive) VALUES ('PrintOrderConfirmationEmailEnglish', 'invoice_printer_order_confirmation_email.php', 'receipt,en', 'order_confirmation_%d.pdf', 'invoice', 190, 1)",
      "REPLACE INTO {prefix}state (id, data) VALUES ('version', '28')"
    ));
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
