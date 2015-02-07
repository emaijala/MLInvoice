<?php
/*******************************************************************************
MLInvoice: web-based invoicing application.
Copyright (C) 2010-2015 Ere Maijala

This program is free software. See attached LICENSE.

*******************************************************************************/

/*******************************************************************************
MLInvoice: web-pohjainen laskutusohjelma.
Copyright (C) 2010-2015 Ere Maijala

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
  if (!($invoiceData = mysqli_fetch_assoc($intRes)))
  {
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

  $paymentDays = getPaymentDays($invoiceData['company_id']);

  unset($invoiceData['id']);
  unset($invoiceData['invoice_no']);
  if (!$boolRefund) {
    unset($invoiceData['ref_number']);
    if (!empty($invoiceData['company_id'])) {
      $res = mysqli_param_query('SELECT default_ref_number FROM {prefix}company WHERE id=?', array($invoiceData['company_id']));
      $invoiceData['ref_number'] = mysqli_fetch_value($res);
    }
  }
  $invoiceData['invoice_date'] = date("Ymd");
  $invoiceData['due_date'] = date("Ymd", mktime(0, 0, 0, date("m"), date("d") + $paymentDays, date("Y")));
  $invoiceData['payment_date'] = null;
  $invoiceData['state_id'] = 1;
  $invoiceData['refunded_invoice_id'] = $boolRefund ? $intInvoiceId : null;
  if ($boolRefund) {
    $invoiceData['interval_type'] = 0;
  }

  switch ($invoiceData['interval_type']) {
    // Month
    case 2:
      $invoiceData['next_interval_date'] = date("Ymd",mktime(0, 0, 0, date("m") + 1, date("d"), date("Y")));
      break;
    // Year
    case 3:
      $invoiceData['next_interval_date'] = date("Ymd",mktime(0, 0, 0, date("m"), date("d"), date("Y") + 1));
      break;
  }

  mysqli_query_check('BEGIN');

  try {
    if ($invoiceData['interval_type'] > 0) {
      // Reset interval type of the original invoice
      $strQuery = 'UPDATE {prefix}invoice ' .
        'SET interval_type = 0 ' .
        'WHERE {prefix}invoice.id = ?';
      mysqli_param_query($strQuery, array($intInvoiceId), 'exception');
    }

    $strQuery =
      'INSERT INTO {prefix}invoice(' . implode(', ', array_keys($invoiceData)) . ') '
      . 'VALUES (' . str_repeat('?, ', count($invoiceData) - 1) . '?)';

    mysqli_param_query($strQuery, $invoiceData, 'exception');
    $intNewId = mysqli_insert_id($dblink);
    if (!$intNewId) {
    	die('Could not get ID of the new invoice');
    }
    $strQuery =
      'SELECT * ' .
      'FROM {prefix}invoice_row ' .
      'WHERE deleted=0 AND invoice_id=?';
    $intRes = mysqli_param_query($strQuery, array($intInvoiceId), 'exception');
    while ($row = mysqli_fetch_assoc($intRes))
    {
      if ($boolRefund) {
      	$row['pcs'] = -$row['pcs'];
      } else if ($row['reminder_row']) {
        continue;
      }
    	unset($row['id']);
    	$row['invoice_id'] = $intNewId;

      if (getSetting('invoice_update_row_dates_on_copy')) {
        $row['row_date'] = $intDate;
      }
      // Update product stock balance
      if ($row['product_id'] !== null) {
        updateProductStockBalance(null, $row['product_id'], $row['pcs']);
      }
      $strQuery =
        'INSERT INTO {prefix}invoice_row(' . implode(', ', array_keys($row)) . ') '
        . 'VALUES (' . str_repeat('?, ', count($row) - 1) . '?)';
      mysqli_param_query($strQuery, $row, 'exception');
    }
  } catch (Exception $e) {
  	mysqli_param_query('ROLLBACK');
  	die($e->message);
  }
  mysqli_param_query('COMMIT');
}

header("Location: ". _PROTOCOL_ . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/index.php?func=$strFunc&list=$strList&form=invoice&id=$intNewId");

?>