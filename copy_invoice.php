<?php
/*******************************************************************************
 MLInvoice: web-based invoicing application.
 Copyright (C) 2010-2017 Ere Maijala

 This program is free software. See attached LICENSE.

 *******************************************************************************/

/*******************************************************************************
 MLInvoice: web-pohjainen laskutusohjelma.
 Copyright (C) 2010-2017 Ere Maijala

 Tämä ohjelma on vapaa. Lue oheinen LICENSE.

 *******************************************************************************/
require_once 'htmlfuncs.php';
require_once 'sqlfuncs.php';
require_once 'sessionfuncs.php';

sesVerifySession();

require_once 'translator.php';
require_once 'datefuncs.php';
require_once 'miscfuncs.php';
require_once 'settings.php';

if (!sesWriteAccess()) {
    echo htmlPageStart();
?>
<body>
    <div class="ui-widget">
        <div class="form_container ui-widget-content">
        <?php echo Translator::translate('NoAccess') . "\n"?>
    </div>
    </div>
</body>
</html>
<?php
    return;
}

$intInvoiceId = getRequest('id', false);
$boolRefund = getRequest('refund', false);
$strFunc = getRequest('func', '');
$strList = getRequest('list', '');
$isOffer = !getRequest('invoice', false) && isOffer($intInvoiceId);

if ($intInvoiceId) {
    if ($boolRefund) {
        $strQuery = 'UPDATE {prefix}invoice ' . 'SET state_id = 4 '
             . 'WHERE {prefix}invoice.id = ?';
        db_param_query($strQuery, [$intInvoiceId]);
    }

    $strQuery = 'SELECT * ' . 'FROM {prefix}invoice '
        . 'WHERE {prefix}invoice.id = ?';
    $rows = db_param_query($strQuery, [$intInvoiceId]);
    if (!$rows) {
        echo htmlPageStart();
?>
<body>
    <div class="ui-widget">
        <div class="form_container ui-widget-content">
        <?php echo Translator::translate('RecordNotFound') . "\n"?>
    </div>
    </div>
</body>
</html>
<?php
        return;
    }
    $invoiceData = $rows[0];

    $paymentDays = getPaymentDays($invoiceData['company_id']);

    unset($invoiceData['id']);
    unset($invoiceData['invoice_no']);
    if (!$boolRefund) {
        unset($invoiceData['ref_number']);
        if (!empty($invoiceData['company_id'])) {
            $rows = db_param_query(
                'SELECT default_ref_number FROM {prefix}company WHERE id=?',
                [$invoiceData['company_id']]
            );
            $invoiceData['ref_number'] = isset($rows[0])
                ? $rows[0]['default_ref_number'] : null;
        }
        if (!empty($invoiceData['base_id'])) {
            $rows = db_param_query(
                'SELECT invoice_default_info FROM {prefix}base WHERE id=?',
                [$invoiceData['base_id']]
            );
            $invoiceData['info'] = isset($rows[0])
                ? $rows[0]['invoice_default_info'] : null;
        }
    }
    $invoiceData['invoice_date'] = date('Ymd');
    $invoiceData['due_date'] = date(
        'Ymd', mktime(0, 0, 0, date('m'), date('d') + $paymentDays, date('Y'))
    );
    $invoiceData['payment_date'] = null;
    if ($isOffer) {
        $invoiceData['state_id'] = getInitialOfferState();
    } else {
        $invoiceData['state_id'] = 1;
    }
    $invoiceData['archived'] = false;
    $invoiceData['refunded_invoice_id'] = $boolRefund ? $intInvoiceId : null;
    if ($boolRefund) {
        $invoiceData['interval_type'] = 0;
    }

    switch ($invoiceData['interval_type']) {
    // Month
    case 2:
        $invoiceData['next_interval_date'] = date(
            'Ymd', mktime(0, 0, 0, date('m') + 1, date('d'), date('Y'))
        );
        break;
    // Year
    case 3:
        $invoiceData['next_interval_date'] = date(
            'Ymd', mktime(0, 0, 0, date('m'), date('d'), date('Y') + 1)
        );
        break;
    // 2 to 6 months
    case 4:
    case 5:
    case 6:
    case 7:
    case 8:
        $invoiceData['next_interval_date'] = date(
            'Ymd',
            mktime(
                0, 0, 0, date('m') + $invoiceData['interval_type'] - 2,
                date('d'), date('Y')
            )
        );
        break;
    }

    mysqli_query_check('SET AUTOCOMMIT = 0');
    mysqli_query_check('BEGIN');

    try {
        if ($invoiceData['interval_type'] > 0) {
            // Reset interval type of the original invoice
            $strQuery = 'UPDATE {prefix}invoice ' . 'SET interval_type = 0 ' .
                 'WHERE {prefix}invoice.id = ?';
            db_param_query($strQuery, [$intInvoiceId], 'exception');
        }

        $strQuery = 'INSERT INTO {prefix}invoice(' .
             implode(', ', array_keys($invoiceData)) . ') ' . 'VALUES (' .
             str_repeat('?, ', count($invoiceData) - 1) . '?)';

        db_param_query($strQuery, $invoiceData, 'exception');
        $intNewId = mysqli_insert_id($dblink);
        if (!$intNewId) {
            die('Could not get ID of the new invoice');
        }
        $newRowDate = date('Ymd');
        $strQuery = 'SELECT * ' . 'FROM {prefix}invoice_row ' .
             'WHERE deleted=0 AND invoice_id=?';
        $rows = db_param_query($strQuery, [$intInvoiceId], 'exception');
        foreach ($rows as $row) {
            if ($boolRefund) {
                $row['pcs'] = -$row['pcs'];
            } else if ($row['reminder_row']) {
                continue;
            }
            unset($row['id']);
            $row['invoice_id'] = $intNewId;

            if (getSetting('invoice_update_row_dates_on_copy')) {
                $row['row_date'] = $newRowDate;
            }
            // Update product stock balance
            if (!$isOffer && $row['product_id'] !== null) {
                updateProductStockBalance(null, $row['product_id'], $row['pcs']);
            }
            $strQuery = 'INSERT INTO {prefix}invoice_row(' .
                 implode(', ', array_keys($row)) . ') ' . 'VALUES (' .
                 str_repeat('?, ', count($row) - 1) . '?)';
            db_param_query($strQuery, $row, 'exception');
        }
    } catch (Exception $e) {
        mysqli_query_check('ROLLBACK');
        mysqli_query_check('SET AUTOCOMMIT = 1');
        die($e->getMessage());
    }
    mysqli_query_check('COMMIT');
    mysqli_query_check('SET AUTOCOMMIT = 1');
}

header("Location: index.php?func=$strFunc&list=$strList&form=invoice&id=$intNewId");
