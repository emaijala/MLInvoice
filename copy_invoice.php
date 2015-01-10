<?php
/*******************************************************************************
MLInvoice: web-based invoicing application.
Copyright (C) 2010-2012 Ere Maijala

This program is free software. See attached LICENSE.

*******************************************************************************/

/*******************************************************************************
MLInvoice: web-pohjainen laskutusohjelma.
Copyright (C) 2010-2012 Ere Maijala

Tämä ohjelma on vapaa. Lue oheinen LICENSE.

*******************************************************************************/

require_once 'htmlfuncs.php';
require_once 'sqlfuncs.php';
require_once 'sessionfuncs.php';

sesVerifySession();

require_once 'localize.php';
require_once 'datefuncs.php';
require_once 'miscfuncs.php';
require_once 'settings.php';

if (!sesWriteAccess())
{
  echo htmlPageStart(_PAGE_TITLE_, getSetting('session_keepalive') ? array('js/keepalive.js') : null);
?>
<body>
  <div class="ui-widget">
    <div class="form_container ui-widget-content">
      <?php echo $GLOBALS['locNoAccess'] . "\n"?>
    </div>
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
    mysqli_param_query($strQuery, array($intInvoiceId));
  }

  $strQuery =
    'SELECT * '.
    'FROM {prefix}invoice '.
    'WHERE {prefix}invoice.id = ?';
  $intRes = mysqli_param_query($strQuery, array($intInvoiceId));
  if ($row = mysqli_fetch_assoc($intRes))
  {
    $strname = $row['name'];
    $intCompanyId = $row['company_id'];
    $intPaymentDate = $row['payment_date'];
    $intRefNumber = $row['ref_number'];
    $intStateId = $row['state_id'];
    $strReference = $row['reference'];
    $intBaseId = $row['base_id'];
    $info = $row['info'];
    $internalInfo = $row['internal_info'];
    $intervalType = $row['interval_type'];
    $nextIntervalDate = $row['next_interval_date'];
  } else {
    echo htmlPageStart(_PAGE_TITLE_, getSetting('session_keepalive') ? array('js/keepalive.js') : null);
?>
<body>
  <div class="ui-widget">
    <div class="form_container ui-widget-content">
      <?php echo $GLOBALS['locRecordNotFound'] . "\n"?>
    </div>
  </div>
</body>
</html>
<?php
    return;
  }

  $intDate = date("Ymd");
  $intDueDate = date("Ymd", mktime(0, 0, 0, date("m"), date("d") + getSetting('invoice_payment_days'), date("Y")));

  switch ($intervalType) {
    // Month
    case 2:
      $nextIntervalDate = date("Ymd",mktime(0, 0, 0, date("m") + 1, date("d"), date("Y")));
      break;
    // Year
    case 3:
      $nextIntervalDate = date("Ymd",mktime(0, 0, 0, date("m"), date("d"), date("Y") + 1));
      break;
  }
  if ($intervalType > 0) {
    // Reset interval type of the original invoice
    $strQuery = 'UPDATE {prefix}invoice ' .
      'SET interval_type = 0 ' .
      'WHERE {prefix}invoice.id = ?';
    mysqli_param_query($strQuery, array($intInvoiceId));
  }

  $intRefundedId = $boolRefund ? $intInvoiceId : null;
  $strQuery =
      'INSERT INTO {prefix}invoice(name, company_id, invoice_date, due_date, payment_date, state_id, reference, base_id, refunded_invoice_id, info, internal_info, interval_type, next_interval_date) '.
      'VALUES (?, ?, ?, ?, NULL, 1, ?, ?, ?, ?, ?, ?, ?)';

  mysqli_param_query($strQuery, array($strname, $intCompanyId, $intDate, $intDueDate, $strReference, $intBaseId, $intRefundedId, $info, $internalInfo, $intervalType, $nextIntervalDate));
  $intNewId = mysqli_insert_id($dblink);
  if ($intNewId)
  {
    $strQuery =
        'SELECT * ' .
        'FROM {prefix}invoice_row ' .
        'WHERE deleted=0 AND invoice_id=?';
    $intRes = mysqli_param_query($strQuery, array($intInvoiceId));
    while ($row = mysqli_fetch_assoc($intRes))
    {
      $intProductId = $row['product_id'];
      $strDescription = $row['description'];
      $rowDate = $row['row_date'];
      $intTypeId = $row['type_id'];
      $intPcs = $row['pcs'];
      $intPrice = $row['price'];
      $intDiscount = $row['discount'];
      $intVat = $row['vat'];
      $intOrderNo = $row['order_no'];
      $boolVatIncluded = $row['vat_included'];
      $intReminderRow = $row['reminder_row'];

      if ($boolRefund)
        $intPcs = -$intPcs;
      else if ($intReminderRow)
        continue;

      if (getSetting('invoice_update_row_dates_on_copy')) {
        $rowDate = $intDate;
      }

      $strQuery =
        'INSERT INTO {prefix}invoice_row(invoice_id, product_id, description, type_id, pcs, price, discount, row_date, vat, order_no, vat_included, reminder_row) '.
        'VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
      mysqli_param_query($strQuery, array($intNewId, $intProductId, $strDescription, $intTypeId, $intPcs, $intPrice, $intDiscount, $rowDate, $intVat, $intOrderNo, $boolVatIncluded, $intReminderRow));
    }
  }
}

header("Location: ". _PROTOCOL_ . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/index.php?func=$strFunc&list=$strList&form=invoice&id=$intNewId");

?>