<?php
/**
 * Logo handling
 *
 * PHP version 5
 *
 * Copyright (C) 2010-2018 Ere Maijala
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category MLInvoice
 * @package  MLInvoice\Base
 * @author   Ere Maijala <ere@labs.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://labs.fi/mlinvoice.eng.php
 */
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
        dbParamQuery($strQuery, [$intInvoiceId]);
    }

    $strQuery = 'SELECT * ' . 'FROM {prefix}invoice '
        . 'WHERE {prefix}invoice.id = ?';
    $rows = dbParamQuery($strQuery, [$intInvoiceId]);
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
    $invoiceData['deleted'] = 0;
    if (!$boolRefund) {
        unset($invoiceData['ref_number']);
        if (!empty($invoiceData['company_id'])) {
            $rows = dbParamQuery(
                'SELECT default_ref_number FROM {prefix}company WHERE id=?',
                [$invoiceData['company_id']]
            );
            $invoiceData['ref_number'] = isset($rows[0])
                ? $rows[0]['default_ref_number'] : null;
        }
        if (!empty($invoiceData['base_id'])) {
            $rows = dbParamQuery(
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

    dbQueryCheck('SET AUTOCOMMIT = 0');
    dbQueryCheck('BEGIN');

    try {
        if ($invoiceData['interval_type'] > 0) {
            // Reset interval type of the original invoice
            $strQuery = 'UPDATE {prefix}invoice ' . 'SET interval_type = 0 ' .
                 'WHERE {prefix}invoice.id = ?';
            dbParamQuery($strQuery, [$intInvoiceId], 'exception');
        }

        $strQuery = 'INSERT INTO {prefix}invoice(' .
             implode(', ', array_keys($invoiceData)) . ') ' . 'VALUES (' .
             str_repeat('?, ', count($invoiceData) - 1) . '?)';

        dbParamQuery($strQuery, $invoiceData, 'exception');
        $intNewId = mysqli_insert_id($dblink);
        if (!$intNewId) {
            die('Could not get ID of the new invoice');
        }
        $newRowDate = date('Ymd');
        $strQuery = 'SELECT * ' . 'FROM {prefix}invoice_row ' .
             'WHERE deleted=0 AND invoice_id=?';
        $rows = dbParamQuery($strQuery, [$intInvoiceId], 'exception');
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
            dbParamQuery($strQuery, $row, 'exception');
        }
    } catch (Exception $e) {
        dbQueryCheck('ROLLBACK');
        dbQueryCheck('SET AUTOCOMMIT = 1');
        die($e->getMessage());
    }
    dbQueryCheck('COMMIT');
    dbQueryCheck('SET AUTOCOMMIT = 1');
}

header("Location: index.php?func=$strFunc&list=$strList&form=invoice&id=$intNewId");
