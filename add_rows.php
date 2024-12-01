<?php
/**
 * Add invoice rows from template
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

$invoiceId = getPostOrQuery('id', false);
$templateId = getPostOrQuery('from_template', false);

if (!$invoiceId || !$templateId) {
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

dbQueryCheck('BEGIN');
try {
    $newRowDate = date('Ymd');
    $strQuery = 'SELECT * FROM {prefix}invoice_row WHERE deleted=0 AND invoice_id=?';
    $rows = dbParamQuery($strQuery, [$templateId], 'exception');
    foreach ($rows as $row) {
        unset($row['id']);
        $row['invoice_id'] = $invoiceId;

        if ($row['row_date']) {
            $row['row_date'] = $newRowDate;
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

header("Location: index.php?func=invoices&list=invoice&form=invoice&id=$invoiceId");
