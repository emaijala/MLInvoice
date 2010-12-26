<?php
/*******************************************************************************
VLLasku: web-based invoicing application.
Copyright (C) 2010 Ere Maijala

This program is free software. See attached LICENSE.

*******************************************************************************/

/*******************************************************************************
VLLasku: web-pohjainen laskutusohjelma.
Copyright (C) 2010 Ere Maijala

Tämä ohjelma on vapaa. Lue oheinen LICENSE.

*******************************************************************************/

require_once "sqlfuncs.php";
require_once "miscfuncs.php";
require_once "sessionfuncs.php";

sesVerifySession(FALSE);

$strFunc = getRequest('func', '');

switch ($strFunc)
{
case 'get_company':
  $companyId = getRequest('id', '');
  if ($companyId) 
  {
    $res = mysql_param_query('SELECT * FROM {prefix}company WHERE ID=?', array($companyId));
    $row = mysql_fetch_assoc($res);
    header('Content-Type: application/json');
    echo json_encode($row);
  }
break;

case 'get_invoice_defaults':
  $baseId = getRequest('base_id', 0);
  $invoiceId = getRequest('id', 0);
  if (getSetting('invoice_numbering_per_base') && $baseId)
    $res = mysql_param_query('SELECT max(cast(invoice_no as unsigned integer)) FROM {prefix}invoice where id != ? AND base_id = ?', array($invoiceId, $baseId));
  else
    $res = mysql_param_query('SELECT max(cast(invoice_no as unsigned integer)) FROM {prefix}invoice where id != ?', array($invoiceId));
  $invNo = mysql_result($res, 0, 0) + 1;
  $refNo = $invNo . miscCalcCheckNo($invNo);
  $strDate = date("d.m.Y");
  $strDueDate = date("d.m.Y", mktime(0, 0, 0, date("m"), date("d")+getSetting('invoice_payment_days'), date("Y")));
  $arrData = array(
    'invoice_no' => $invNo, 
    'ref_no' => $refNo,
    'date' => $strDate,
    'due_date' => $strDueDate
  );
  header('Content-Type: application/json');
  echo json_encode($arrData);

break;
default:
  header('HTTP/1.1 404 Not Found');
}
