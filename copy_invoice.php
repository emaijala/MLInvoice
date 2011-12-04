<?php
/*******************************************************************************
VLLasku: web-based invoicing application.
Copyright (C) 2010-2011 Ere Maijala

Portions based on:
PkLasku : web-based invoicing software.
Copyright (C) 2004-2008 Samu Reinikainen

This program is free software. See attached LICENSE.

*******************************************************************************/

/*******************************************************************************
VLLasku: web-pohjainen laskutusohjelma.
Copyright (C) 2010-2011 Ere Maijala

Perustuu osittain sovellukseen:
PkLasku : web-pohjainen laskutusohjelmisto.
Copyright (C) 2004-2008 Samu Reinikainen

Tämä ohjelma on vapaa. Lue oheinen LICENSE.

*******************************************************************************/

require "htmlfuncs.php";
require "sqlfuncs.php";
require "sessionfuncs.php";

sesVerifySession();

require_once "localize.php";
require "datefuncs.php";
require "miscfuncs.php";

if (!sesWriteAccess())
{
  echo htmlPageStart(_PAGE_TITLE_, getSetting('session_keepalive') ? array('js/keepalive.js') : null);
?>
<body>
  <div class="form_container ui-widget-content">
    <?php echo $GLOBALS['locNOACCESS'] . "\n"?>
  </div>
</body>
</html>
<?php
  return;
}

$intInvoiceId = getRequest('id', FALSE);
$boolRefund = getRequest('refund', FALSE);
$strFunc = getRequest('func', '');
$strList = getRequest('list', '');

if ($intInvoiceId) 
{
  if ($boolRefund)
  {
    $strQuery = 'UPDATE {prefix}invoice ' .
      'SET state_id = 4 ' .
      'WHERE {prefix}invoice.id = ?';
    mysql_param_query($strQuery, array($intInvoiceId));
  }

  $strQuery = 
    'SELECT * '.
    'FROM {prefix}invoice '.
    'WHERE {prefix}invoice.id = ?';
  $intRes = mysql_param_query($strQuery, array($intInvoiceId));
  if ($row = mysql_fetch_assoc($intRes)) 
  {
    $strname = $row['name'];
    $intCompanyId = $row['company_id'];
    $intInvoiceNo = $row['invoice_no'];
    $intInvoiceDate = $row['invoice_date'];
    $intDueDate = $row['due_date'];
    $intPaymentDate = $row['payment_date'];
    $intRefNumber = $row['ref_number'];
    $intStateId = $row['state_id'];
    $strReference = $row['reference'];
    $intBaseId = $row['base_id'];
  }
  
  $intDate = date("Ymd");
  $intDueDate = date("Ymd",mktime(0, 0, 0, date("m"), date("d") + getSetting('invoice_payment_days'), date("Y")));
  
  $intNewInvNo = 0;
  $intNewRefNo = 'NULL';
  
  $intRefundedId = $boolRefund ? $intInvoiceId : 'NULL';
  $strQuery = 
      'INSERT INTO {prefix}invoice(name, company_id, invoice_date, due_date, payment_date, state_id, reference, base_id, refunded_invoice_id) '.
      'VALUES (?, ?, ?, ?, NULL, 1, ?, ?, ?)';
      
  mysql_param_query($strQuery, array($strname, $intCompanyId, $intDate, $intDueDate, $strReference, $intBaseId, $intRefundedId));
  $intNewId = mysql_insert_id();
  if ($intNewId) 
  {    
    $strQuery = 
        'SELECT * '.
        'FROM {prefix}invoice_row '.
        'WHERE deleted=0 AND invoice_id=?';
    $intRes = mysql_param_query($strQuery, array($intInvoiceId));
    while ($row = mysql_fetch_assoc($intRes)) 
    {
      $intProductId = $row['product_id'];
      $strDescription = $row['description'];
      $intTypeId = $row['type_id'];
      $intPcs = $row['pcs'];
      $intPrice = $row['price'];
      $intDiscount = $row['discount'];
      $intRowDate = $row['row_date'];
      $intVat = $row['vat'];
      $intOrderNo = $row['order_no'];
      $boolVatIncluded = $row['vat_included'];
      $intReminderRow = $row['reminder_row'];

      if ($boolRefund)
        $intPcs = -$intPcs;
      else if ($intReminderRow)
        continue;
      
      $strQuery = 
        'INSERT INTO {prefix}invoice_row(invoice_id, product_id, description, type_id, pcs, price, discount, row_date, vat, order_no, vat_included, reminder_row) '.
        'VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
      mysql_param_query($strQuery, array($intNewId, $intProductId, $strDescription, $intTypeId, $intPcs, $intPrice, $intDiscount, $intRowDate, $intVat, $intOrderNo, $boolVatIncluded, $intReminderRow));
    }
  }
}

header("Location: ". _PROTOCOL_ . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/index.php?func=$strFunc&list=$strList&form=invoice&id=$intNewId");

?>