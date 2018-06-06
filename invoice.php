<?php
/**
 * Printouts
 *
 * PHP version 5
 *
 * Copyright (C) 2004-2008 Samu Reinikainen
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

 // buffered, so we can redirect later if necessary
ini_set('implicit_flush', 'Off');
ob_start();

require_once 'sessionfuncs.php';

$authenticated = true;
$intInvoiceId = getRequest('id', false);
$printTemplate = getRequest('t', false);
$dateOverride = false;
$language = getRequest('l', false);
$uuid = getRequest('i', false);
$hash = getRequest('c', false);
$ts = getRequest('s', false);
if (false === $printTemplate || false === $language || false === $uuid
    || false === $hash || false === $ts
) {
    if ($intInvoiceId) {
        sesVerifySession();
    } else {
        return;
    }
} else {
    $authenticated = false;
}

require_once 'vendor/autoload.php';
require_once 'sqlfuncs.php';
require_once 'translator.php';
require_once 'pdf.php';
require_once 'datefuncs.php';
require_once 'miscfuncs.php';

if ($authenticated) {
    $printTemplate = getRequest('template', 1);
    $dateOverride = getRequest('date', false);
    if (!is_string($dateOverride) || !ctype_digit($dateOverride)
        || strlen($dateOverride) != 8
    ) {
        $dateOverride = false;
    }
} else {
    include_once 'hmac.php';
    $reqHash = HMAC::createHMAC([$printTemplate, $language, $uuid, $ts]);
    if ($reqHash !== $hash) {
        return;
    }
    Translator::setActiveLanguage('', $language);
    if (abs(time() - $ts) > 90 * 24 * 60 * 60) { // 90 days
        die(Translator::translate('LinkExpired'));
    }
    $rows = dbParamQuery(
        'SELECT id FROM {prefix}invoice WHERE uuid=?',
        [$uuid]
    );
    if (!$rows) {
        return;
    }
    $intInvoiceId = $rows[0]['id'];
}

if (!$intInvoiceId) {
    if ($authenticated) {
        die('Id missing');
    }
    return;
}

$rows = dbParamQuery(
    'SELECT filename, parameters, output_filename from {prefix}print_template WHERE id=?',
    [$printTemplate]
);
if (!$rows) {
    if ($authenticated) {
        die('Could not find print template');
    }
    return;
}
$row = $rows[0];
$printTemplateFile = $row['filename'];
$printParameters = $row['parameters'];
$printOutputFileName = $row['output_filename'];

$strQuery = 'SELECT inv.*, ref.invoice_no as refunded_invoice_no, delivery_terms.name as delivery_terms,'
    . ' delivery_method.name as delivery_method, invoice_state.name as invoice_state,'
    . ' invoice_state.invoice_open as invoice_open, invoice_state.invoice_unpaid as invoice_unpaid '
    . 'FROM {prefix}invoice inv '
    . 'LEFT OUTER JOIN {prefix}invoice ref ON ref.id = inv.refunded_invoice_id '
    . 'LEFT OUTER JOIN {prefix}delivery_terms as delivery_terms ON delivery_terms.id = inv.delivery_terms_id '
    . 'LEFT OUTER JOIN {prefix}delivery_method as delivery_method ON delivery_method.id = inv.delivery_method_id '
    . 'LEFT OUTER JOIN {prefix}invoice_state as invoice_state ON invoice_state.id = inv.state_id '
    . 'WHERE inv.id=?';
$rows = dbParamQuery($strQuery, [$intInvoiceId]);
if (!$rows) {
    if ($authenticated) {
        die('Could not find invoice data');
    }
    return;
}
$invoiceData = $rows[0];

if (isOffer($intInvoiceId)) {
    $invoiceData['invoice_no'] = $intInvoiceId;
}

$strQuery = 'SELECT * FROM {prefix}company WHERE id=?';
$rows = dbParamQuery($strQuery, [$invoiceData['company_id']]);
if ($rows) {
    $recipientData = $rows[0];
    if (!empty($recipientData['company_id'])) {
        $recipientData['vat_id'] = createVATID($recipientData['company_id']);
    } else {
        $recipientData['vat_id'] = '';
    }

    $strQuery = 'SELECT * FROM {prefix}company_contact WHERE company_id=?'
        . ' AND deleted=0 ORDER BY id';
    $recipientContactData = dbParamQuery($strQuery, [$invoiceData['company_id']]);
} else {
    $recipientData = null;
    $recipientContactData = [];
}

$strQuery = 'SELECT * FROM {prefix}base WHERE id=?';
$rows = dbParamQuery($strQuery, [$invoiceData['base_id']]);
if (!$rows) {
    if ($authenticated) {
        die('Could not find invoice sender data');
    }
    return;
}
$senderData = $rows[0];
$senderData['vat_id'] = createVATID($senderData['company_id']);

$queryParams = [$intInvoiceId];
$where = 'ir.invoice_id=? AND ir.deleted=0';
if ($dateOverride) {
    $where .= ' AND row_date=?';
    $queryParams[] = $dateOverride;
}

$strQuery = <<<EOT
SELECT pr.product_name, pr.product_code, pr.price_decimals,
    pr.barcode1, pr.barcode1_type, pr.barcode2, pr.barcode2_type,
    ir.description, ir.pcs, ir.price, IFNULL(ir.discount, 0) as discount,
    IFNULL(ir.discount_amount, 0) as discount_amount, ir.row_date, ir.vat,
    ir.vat_included, ir.reminder_row, ir.partial_payment, ir.order_no, rt.name type
    FROM {prefix}invoice_row ir
    LEFT OUTER JOIN {prefix}row_type rt ON rt.id = ir.type_id
    LEFT OUTER JOIN {prefix}product pr ON ir.product_id = pr.id
    WHERE $where ORDER BY ir.order_no, row_date, pr.product_name DESC,
    ir.description DESC
EOT;
$invoiceRowData = dbParamQuery($strQuery, $queryParams);

if ($authenticated && sesWriteAccess()) {
    dbParamQuery(
        'UPDATE {prefix}invoice SET print_date=? where id=?',
        [
            date('Ymd'),
            $intInvoiceId
        ]
    );
}

if (!$authenticated) {
    if (substr($printTemplateFile, -6) === '_email') {
        $printTemplateFile = substr($printTemplateFile, -6);
    }
    $printParameters[1] = $language;
}

$printer = instantiateInvoicePrinter(trim($printTemplateFile));
$printer->init(
    $intInvoiceId, $printParameters, $printOutputFileName, $senderData,
    $recipientData, $invoiceData, $invoiceRowData, $recipientContactData,
    $dateOverride, $printTemplate, $authenticated
);
$printer->printInvoice();
