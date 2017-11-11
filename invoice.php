<?php
/*******************************************************************************
 MLInvoice: web-based invoicing application.
 Copyright (C) 2010-2017 Ere Maijala

 Portions based on:
 PkLasku : web-based invoicing software.
 Copyright (C) 2004-2008 Samu Reinikainen

 This program is free software. See attached LICENSE.

 *******************************************************************************/

/*******************************************************************************
 MLInvoice: web-pohjainen laskutusohjelma.
 Copyright (C) 2010-2017 Ere Maijala

 Perustuu osittain sovellukseen:
 PkLasku : web-pohjainen laskutusohjelmisto.
 Copyright (C) 2004-2008 Samu Reinikainen

 Tämä ohjelma on vapaa. Lue oheinen LICENSE.

 *******************************************************************************/
require_once 'sessionfuncs.php';

sesVerifySession();

require_once 'vendor/autoload.php';
require_once 'sqlfuncs.php';
require_once 'translator.php';
require_once 'pdf.php';
require_once 'datefuncs.php';
require_once 'miscfuncs.php';

$intInvoiceId = getRequest('id', false);
$printTemplate = getRequest('template', 1);
$dateOverride = getRequest('date', false);
if (!is_string($dateOverride) || !ctype_digit($dateOverride)
    || strlen($dateOverride) != 8
) {
    $dateOverride = false;
}

if (!$intInvoiceId) {
    die('Id missing');
}

$rows = db_param_query(
    'SELECT filename, parameters, output_filename from {prefix}print_template WHERE id=?',
    [$printTemplate]
);
if (!$rows) {
    die('Could not find print template');
}
$row = $rows[0];
$printTemplateFile = $row['filename'];
$printParameters = $row['parameters'];
$printOutputFileName = $row['output_filename'];

$strQuery = 'SELECT inv.*, ref.invoice_no as refunded_invoice_no, delivery_terms.name as delivery_terms, delivery_method.name as delivery_method, invoice_state.name as invoice_state, invoice_state.invoice_open as invoice_open, invoice_state.invoice_unpaid as invoice_unpaid ' .
     'FROM {prefix}invoice inv ' .
     'LEFT OUTER JOIN {prefix}invoice ref ON ref.id = inv.refunded_invoice_id ' .
     'LEFT OUTER JOIN {prefix}delivery_terms as delivery_terms ON delivery_terms.id = inv.delivery_terms_id ' .
     'LEFT OUTER JOIN {prefix}delivery_method as delivery_method ON delivery_method.id = inv.delivery_method_id ' .
     'LEFT OUTER JOIN {prefix}invoice_state as invoice_state ON invoice_state.id = inv.state_id ' .
     'WHERE inv.id=?';
$rows = db_param_query($strQuery, [$intInvoiceId]);
if (!$rows) {
    die('Could not find invoice data');
}
$invoiceData = $rows[0];

if (isOffer($intInvoiceId)) {
    $invoiceData['invoice_no'] = $intInvoiceId;
}

$strQuery = 'SELECT * FROM {prefix}company WHERE id=?';
$rows = db_param_query($strQuery, [$invoiceData['company_id']]);
if ($rows) {
    $recipientData = $rows[0];
    if (!empty($recipientData['company_id'])) {
        $recipientData['vat_id'] = createVATID($recipientData['company_id']);
    } else {
        $recipientData['vat_id'] = '';
    }

    $strQuery = 'SELECT * FROM {prefix}company_contact WHERE company_id=?'
        . ' AND deleted=0 ORDER BY id';
    $recipientContactData = db_param_query($strQuery, [$invoiceData['company_id']]);
} else {
    $recipientData = null;
    $recipientContactData = [];
}

$strQuery = 'SELECT * FROM {prefix}base WHERE id=?';
$rows = db_param_query($strQuery, [$invoiceData['base_id']]);
if (!$rows) {
    die('Could not find invoice sender data');
}
$senderData = $rows[0];
$senderData['vat_id'] = createVATID($senderData['company_id']);

$queryParams = [$intInvoiceId];
$where = 'ir.invoice_id=? AND ir.deleted=0';
if ($dateOverride) {
    $where .= ' AND row_date=?';
    $queryParams[] = $dateOverride;
}

$strQuery = 'SELECT pr.product_name, pr.product_code, pr.price_decimals, pr.barcode1, pr.barcode1_type, pr.barcode2, pr.barcode2_type, ir.description, ir.pcs, ir.price, IFNULL(ir.discount, 0) as discount, IFNULL(ir.discount_amount, 0) as discount_amount, ir.row_date, ir.vat, ir.vat_included, ir.reminder_row, ir.partial_payment, rt.name type ' .
     'FROM {prefix}invoice_row ir ' .
     'LEFT OUTER JOIN {prefix}row_type rt ON rt.id = ir.type_id ' .
     'LEFT OUTER JOIN {prefix}product pr ON ir.product_id = pr.id ' .
     "WHERE $where ORDER BY ir.order_no, row_date, pr.product_name DESC, ir.description DESC";
$invoiceRowData = db_param_query($strQuery, $queryParams);

if (sesWriteAccess()) {
    db_param_query(
        'UPDATE {prefix}invoice SET print_date=? where id=?',
        [
            date('Ymd'),
            $intInvoiceId
        ]
    );
}

$printer = instantiateInvoicePrinter(trim($printTemplateFile));
$printer->init(
    $intInvoiceId, $printParameters, $printOutputFileName, $senderData,
    $recipientData, $invoiceData, $invoiceRowData, $recipientContactData,
    $dateOverride
);
$printer->printInvoice();
