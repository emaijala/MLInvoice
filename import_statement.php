<?php

require_once 'import.php';

class ImportStatement extends ImportFile
{
  public function __construct()
  {
    parent::__construct();

    $this->tableName = 'account_statement';
    $this->allowServerFile = false;
    $this->duplicateControl = false;
    $this->dateFormat = true;
    $this->decimalSeparator = true;
    $this->ignoreEmptyRows = true;
    $this->presets = array(
      array(
        'name' => 'Osuuspankki',
        'selections' => array(
          'charset' => 1,
          'format' => 0,
          'field_delim' => 1,
          'enclosure_char' => 2,
          'row_delim' => 0,
          'date_format' => 0
        ),
        'mappings' => array(
          'map_column1' => 1,
          'map_column2' => 2,
          'map_column7' => 3
        ),
        'values' => array(
          'decimal_separator' => ',',
          'skip_rows' => '0'
        )
      ),
      array(
        'name' => 'Nordea',
        'selections' => array(
          'charset' => 0,
          'format' => 0,
          'field_delim' => 2,
          'enclosure_char' => 2,
          'row_delim' => 1,
          'date_format' => 0
        ),
        'mappings' => array(
          'map_column1' => 1,
          'map_column2' => 2,
          'map_column8' => 3
        ),
        'values' => array(
          'decimal_separator' => ',',
          'skip_rows' => '1'
        )
      )
    );
  }

  protected function get_field_defs($table)
  {
    return array(
      'date' => true,
      'amount' => true,
      'refnr' => true
    );
  }

  protected function table_valid($table)
  {
    return $table == 'account_statement';
  }

  protected function process_import_row($table, $row, $dupMode, $dupCheckColumns, $mode, &$addedRecordId)
  {
    if (!isset($row['date']) || !isset($row['amount']) || !isset($row['refnr'])) {
      return $GLOBALS['locImportStatementFieldMissing'];
    }

    $refnr = str_replace(' ',  '', $row['refnr']);
    $date = date('Ymd', DateTime::createFromFormat(getRequest('date_format', 'd.m.Y'), $row['date'])->getTimestamp());
    $amount = trim($row['amount']);
    if (substr($amount, 0, 1) == '-') {
      return;
    }
    if (substr($amount, 0, 1) == '+') {
      $amount = substr($amount, 1);
    }
    $sep = getRequest('decimal_separator', ',');
    if ($sep == ' ' || $sep == ',') {
      $amount = str_replace('.', '', $amount);
      $amount = str_replace($sep, '.', $amount);
    }
    elseif ($sep == '.') {
      $amount = str_replace(',', '', $amount);
    }
    $amount = floatval($amount);

    if ($row['refnr'] === '') {
      return $GLOBALS['locImportStatementFieldMissing'];
    }

    $intRes = mysql_param_query('SELECT i.* FROM {prefix}invoice i'
      . ' WHERE i.Deleted=0 AND REPLACE(i.ref_number, " ", "") = ?',
      array($refnr));
    $count = mysql_num_rows($intRes);
    if ($count == 0) {
      return str_replace('{refnr}', $refnr, $GLOBALS['locImportStatementInvoiceNotFound']);
    }
    if ($count > 1) {
      return str_replace('{refnr}', $refnr, $GLOBALS['locImportStatementMultipleInvoicesFound']);
    }

    $row = mysql_fetch_assoc($intRes);

    if ($row['state_id'] == 3) {
      return str_replace('{refnr}', $refnr, $GLOBALS['locImportStatementInvoiceAlreadyPaid']);
    }

    $intRes2 = mysql_param_query('SELECT SUM(CASE WHEN ir.vat_included = 0 THEN ir.price * ir.pcs * (1 - IFNULL(ir.discount, 0) / 100) * (1 + ir.vat / 100) ELSE ir.price * ir.pcs * (1 - IFNULL(ir.discount, 0) / 100) END) as total_price from {prefix}invoice_row ir where ir.deleted = 0 AND ir.invoice_id = ?',
       array($row['id']));
    $rowTotal = mysql_fetch_assoc($intRes2);

    if (floatval($rowTotal['total_price']) != $amount) {
      $msg = str_replace('{statementAmount}', miscRound2Decim($amount), $GLOBALS['locImportStatementAmountMismatch']);
      $msg = str_replace('{invoiceAmount}', miscRound2Decim($rowTotal['total_price']), $msg);
      $msg = str_replace('{refnr}', $refnr, $msg);
      return $msg;
    }

    if ($mode == 'import') {
      $sql = 'UPDATE {prefix}invoice SET state_id=3, payment_date=?';
      if (getSetting('invoice_auto_archive')) {
        $sql .= ', archived=1';
      }
      $sql .= ' WHERE id = ?';
      mysql_param_query($sql, array($date, $row['id']));
    }
    $msg = str_replace('{amount}', miscRound2Decim($amount), $GLOBALS['locImportStatementInvoiceMarkedAsPaid']);
    $msg = str_replace('{id}', $row['id'], $msg);
    $msg = str_replace('{date}', dateConvDBDate2Date($date), $msg);
    $msg = str_replace('{refnr}', $refnr, $msg);
    return $msg;
  }
}