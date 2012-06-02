<?php
/*******************************************************************************
VLLasku: web-based invoicing application.
Copyright (C) 2010-2012 Ere Maijala

This program is free software. See attached LICENSE.

*******************************************************************************/

/*******************************************************************************
VLLasku: web-pohjainen laskutusohjelma.
Copyright (C) 2010-2012 Ere Maijala

Tämä ohjelma on vapaa. Lue oheinen LICENSE.

*******************************************************************************/

ini_set('display_errors', 0);

require_once 'sqlfuncs.php';
require_once 'miscfuncs.php';
require_once 'sessionfuncs.php';
require_once 'form_funcs.php';
require_once 'localize.php';

sesVerifySession(FALSE);

$strFunc = getRequest('func', '');

switch ($strFunc)
{
case 'get_company':
case 'get_company_contact':
case 'get_product':
case 'get_invoice':
case 'get_invoice_row':
case 'get_base':
case 'get_print_template':
case 'get_invoice_state':
case 'get_row_type':
case 'get_print_template':
case 'get_company':
case 'get_session_type':
  printJSONRecord(substr($strFunc, 4));
  break;
case 'get_user':
  printJSONRecord('users');
  break;

case 'put_company':
case 'put_product':
case 'put_invoice':
case 'put_base':
case 'put_print_template':
case 'put_invoice_state':
case 'put_row_type':
case 'put_print_template':
case 'put_user':
case 'put_session_type':
  saveJSONRecord(substr($strFunc, 4), '');
  break;

case 'session_type':
case 'user':
  if (!sesAdminAccess())
  {
    header('HTTP/1.1 403 Forbidden');
    exit;
  }
    saveJSONRecord(substr($strFunc, 4), '');
  break;

case 'get_companies':
  printJSONRecords('company', '', 'company_name');
  break;

case 'get_company_contacts':
  printJSONRecords('company_contact', 'company_id', 'contact_person');
  break;

case 'delete_company_contact':
  deleteRecord('company_contact');
  break;

case 'put_company_contact':
  saveJSONRecord('company_contact', 'company_id');
  break;

case 'get_products':
  printJSONRecords('product', '', 'product_name');
  break;

case 'get_row_types':
  printJSONRecords('row_type', '', 'order_no');
  break;

case 'get_invoice_rows':
  printJSONRecords('invoice_row', 'invoice_id', 'order_no');
  break;

case 'put_invoice_row':
  saveJSONRecord('invoice_row', 'invoice_id');
  break;

case 'delete_invoice_row':
  deleteRecord('invoice_row');
  break;
  
case 'add_reminder_fees':
  require 'add_reminder_fees.php';
  $invoiceId = getRequest('id', 0);
  $errors = addReminderFees($invoiceId);
  if ($errors)
  {
    $ret = array('status' => 'error', 'errors' => $errors);
  }
  else
  {
    $ret = array('status' => 'ok');
  }
  echo json_encode($ret);
  break;

case 'get_invoice_defaults':
  $baseId = getRequest('base_id', 0);
  $invoiceId = getRequest('id', 0);
  $invNr = getRequest('invoice_no', 0);
  if (!$invNr) {
    if (getSetting('invoice_numbering_per_base') && $baseId)
      $res = mysql_param_query('SELECT max(cast(invoice_no as unsigned integer)) FROM {prefix}invoice WHERE deleted=0 AND id!=? AND base_id=?', array($invoiceId, $baseId));
    else
      $res = mysql_param_query('SELECT max(cast(invoice_no as unsigned integer)) FROM {prefix}invoice WHERE deleted=0 AND id!=?', array($invoiceId));
    $invNr = mysql_fetch_value($res) + 1;
  }
  if ($invNr < 100)
    $invNr = 100; // min ref number length is 3 + check digit, make sure invoice number matches that
  $refNr = $invNr . miscCalcCheckNo($invNr);
  $strDate = date("d.m.Y");
  $strDueDate = date("d.m.Y", mktime(0, 0, 0, date("m"), date("d")+getSetting('invoice_payment_days'), date("Y")));
  $arrData = array(
    'invoice_no' => $invNr, 
    'ref_no' => $refNr,
    'date' => $strDate,
    'due_date' => $strDueDate
  );
  header('Content-Type: application/json');
  echo json_encode($arrData);
  break;
  
case 'get_table_columns':
  if (!sesAdminAccess())
  {
    header('HTTP/1.1 403 Forbidden');
    exit;
  }
  $table = getRequest('table', '');
  if (!$table)
  {
    header('HTTP/1.1 400 Bad Request');
    exit;
  }
  if (!table_valid($table))
  {
    header('HTTP/1.1 400 Bad Request');
    die('Invalid table name');
    }

  header('Content-Type: application/json');
  echo "{\"columns\":[";
  $res = mysql_query_check("select * from {prefix}$table where 1=2");
  $field_count = mysql_num_fields($res);
  for ($i = 0; $i < $field_count; $i++)
  {
    $field_def = mysql_fetch_field($res, $i);
    if ($i == 0)
    {
      echo "\n";
    }
    else
      echo ",\n";
    echo json_encode(array('name' => $field_def->name));
  }
  echo "\n]}";
  break;
  
case 'get_import_preview':
  if (!sesAdminAccess())
  {
    header('HTTP/1.1 403 Forbidden');
    exit;
  }
  require 'import.php';
  create_import_preview();
  break;
  
case 'noop':
  // Session keep-alive
  break;
  
default:
  header('HTTP/1.1 404 Not Found');
}

function printJSONRecord($table, $id = FALSE, $warnings = null)
{
  if ($id === FALSE)
    $id = getRequest('id', '');
  if ($id) 
  {
    if (substr($table, 0, 8) != '{prefix}')
      $table = "{prefix}$table";
    $res = mysql_param_query("SELECT * FROM $table WHERE id=?", array($id));
    $row = mysql_fetch_assoc($res);
    if ($table == 'users')
      unset($row['password']);
    header('Content-Type: application/json');
    $row['warnings'] = $warnings;
    echo json_encode($row);
  }
}

function printJSONRecords($table, $parentIdCol, $sort)
{
  $query = "SELECT * FROM {prefix}$table";
  $where = '';
  $params = array();
  $id = getRequest('parent_id', '');
  if ($id && $parentIdCol)
  {
    $where .= " WHERE $parentIdCol=?";
    $params[] = $id;
  }
  if (!getSetting('show_deleted_records'))
  {
    if ($where)
      $where .= " AND deleted=0";
    else
      $where = " WHERE deleted=0";
  }
  
  $query .= $where;
  if ($sort)
    $query .= " order by $sort";

  $res = mysql_param_query($query, $params);
  header('Content-Type: application/json');
  echo "{\"records\":[";
  $first = true;
  while ($row = mysql_fetch_assoc($res))
  {
    if ($first)
    {
      echo "\n";
      $first = false;
    }
    else
      echo ",\n";
    if ($table == 'users')
      unset($row['password']);
    echo json_encode($row);
  }
  echo "\n]}";
}

function saveJSONRecord($table, $parentKeyName)
{
  if (!sesWriteAccess())
  {
    header('HTTP/1.1 403 Forbidden');
    exit;
  }

	$data = json_decode(file_get_contents('php://input'), true);
  if (!$data)
  {
    header('HTTP/1.1 400 Bad Request');
    return;
  }
  $strForm = $table;
  $strFunc = '';
  $strList = '';
  require 'form_switch.php';
  $id = isset($data['id']) ? $data['id'] : false;
  $new = $id ? false : true;
  unset($data['id']);
  $warnings = '';
  $res = saveFormData($strTable, $id, $astrFormElements, $data, $warnings, $parentKeyName, $parentKeyName ? $data[$parentKeyName] : FALSE);
  if ($res !== true)
  { 
    header('Content-Type: application/json');
    echo json_encode(array('missing_fields' => $res));
    return;
  }
  if ($new)
    header('HTTP/1.1 201 Created');
  printJSONRecord($strTable, $id, $warnings);
}

function deleteRecord($table)
{
  if (!sesWriteAccess())
  {
    header('HTTP/1.1 403 Forbidden');
    exit;
  }

  $id = getRequest('id', '');
  if ($id)
  {
    $query = "UPDATE {prefix}$table SET deleted=1 WHERE id=?";
    mysql_param_query($query, array($id));
    header('Content-Type: application/json');
    echo json_encode(array('status' => 'ok'));
  }
}
