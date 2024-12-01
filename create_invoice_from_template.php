<?php
/**
 * Create an invoice from recurring invoice template
 *
 * PHP version 8
 *
 * Copyright (C) Ere Maijala 2024
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

initDbConnection();
sesVerifySession();

require_once 'translator.php';
require_once 'datefuncs.php';
require_once 'miscfuncs.php';
require_once 'settings.php';

if (!sesWriteAccess()) {
    echo htmlPageStart();
    ?>
<body>
    <div class="container-fluid">
        <div class="form_container">
            <?php echo Translator::translate('NoAccess') . "\n"?>
        </div>
    </div>
</body>
</html>
    <?php
    return;
}

$templateId = getPostOrQuery('template_id');
if (!$templateId) {
    echo htmlPageStart();
    ?>
<body>
<div class="container-fluid">
    <div class="form_container">
        <?php echo Translator::translate('ErrInvalidValue')?>
    </div>
</div>
</body>
</html>
    <?php
    return;
}

if (!($invoiceData = getInvoice($templateId))) {
    echo htmlPageStart();
    ?>
<body>
    <div class="container-fluid">
        <div class="form_container">
            <?php echo Translator::translate('RecordNotFound')?>
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
    $invoiceData['deleted'] = 0;
    $invoiceData['invoice_date'] = date('Ymd');
    $invoiceData['due_date'] = date(
        'Ymd', mktime(0, 0, 0, date('m'), date('d') + $paymentDays, date('Y'))
    );
    $invoiceData['payment_date'] = null;
    $invoiceData['state_id'] = 1;
    $invoiceData['archived'] = false;
    $invoiceData['refunded_invoice_id'] = null;
    $invoiceData['interval_type'] = 0;
    $invoiceData['next_interval_date'] = null;
    $invoiceData['template_invoice_id'] = $intInvoiceId;



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

        dbParamQuery($strQuery, array_values($invoiceData), 'exception');
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
                if ($row['partial_payment']) {
                    $row['price'] = -$row['price'];
                }
            } elseif ($row['reminder_row']) {
                continue;
            }
            unset($row['id']);
            $row['invoice_id'] = $intNewId;

            if (getSetting('invoice_update_row_dates_on_copy')) {
                $row['row_date'] = $newRowDate;
            }
            // Update product stock balance
            if (!$isOffer && !$isTemplate && $row['product_id'] !== null) {
                updateProductStockBalance(null, $row['product_id'], $row['pcs']);
            }
            $strQuery = 'INSERT INTO {prefix}invoice_row(' .
                 implode(', ', array_keys($row)) . ') ' . 'VALUES (' .
                 str_repeat('?, ', count($row) - 1) . '?)';
            dbParamQuery($strQuery, $row, 'exception');
        }

        // Update interval of the template:
        $template = getInvoice($templateId);
        advanceInvoiceIntervalData($template);
        updateInvoice($template);
    } catch (Exception $e) {
        dbQueryCheck('ROLLBACK');
        dbQueryCheck('SET AUTOCOMMIT = 1');
        die($e->getMessage());
    }
    dbQueryCheck('COMMIT');
    dbQueryCheck('SET AUTOCOMMIT = 1');
}

header("Location: index.php?func=$strFunc&list=$strList&form=invoice&id=$intNewId");
