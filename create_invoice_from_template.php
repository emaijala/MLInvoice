<?php
/**
 * Create an invoice from recurring invoice template
 *
 * PHP version 8
 *
 * Copyright (C) Ere Maijala 2024.
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

use MLInvoice\Factory;
use MLInvoice\Invoice;

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

$invoice = Factory::getInvoiceService();
try {
    $invoiceId = $invoice->createFromTemplate($templateId);
} catch (\Exception $e) {
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

$_SESSION['formWarningMessage'] = Translator::Translate('CheckCreatedInvoice');
header("Location: index.php?func=$strFunc&list=$strList&form=invoice&id=$invoiceId");
