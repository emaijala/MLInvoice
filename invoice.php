<?php
/*******************************************************************************
 MLInvoice: web-based invoicing application.
 Copyright (C) 2010-2015 Ere Maijala

 Portions based on:
 PkLasku : web-based invoicing software.
 Copyright (C) 2004-2008 Samu Reinikainen

 This program is free software. See attached LICENSE.

 *******************************************************************************/

/*******************************************************************************
 MLInvoice: web-pohjainen laskutusohjelma.
 Copyright (C) 2010-2015 Ere Maijala

 Perustuu osittain sovellukseen:
 PkLasku : web-pohjainen laskutusohjelmisto.
 Copyright (C) 2004-2008 Samu Reinikainen

 Tämä ohjelma on vapaa. Lue oheinen LICENSE.

 *******************************************************************************/
require_once 'sessionfuncs.php';

sesVerifySession();

require_once 'sqlfuncs.php';
require_once 'localize.php';
require_once 'pdf.php';
require_once 'datefuncs.php';
require_once 'miscfuncs.php';

$intInvoiceId = getRequest('id', FALSE);
$printTemplate = getRequest('template', 1);

if (!$intInvoiceId)
    return;

$res = mysqli_param_query(
    'SELECT filename, parameters, output_filename from {prefix}print_template WHERE id=?',
    [
        $printTemplate
    ]);
if (!$row = mysqli_fetch_row($res))
    return;
$printTemplateFile = $row[0];
$printParameters = $row[1];
$printOutputFileName = $row[2];

$strQuery = 'SELECT inv.*, ref.invoice_no as refunded_invoice_no, delivery_terms.name as delivery_terms, delivery_method.name as delivery_method, invoice_state.name as invoice_state, invoice_state.invoice_open as invoice_open, invoice_state.invoice_unpaid as invoice_unpaid ' .
     'FROM {prefix}invoice inv ' .
     'LEFT OUTER JOIN {prefix}invoice ref ON ref.id = inv.refunded_invoice_id ' .
     'LEFT OUTER JOIN {prefix}delivery_terms as delivery_terms ON delivery_terms.id = inv.delivery_terms_id ' .
     'LEFT OUTER JOIN {prefix}delivery_method as delivery_method ON delivery_method.id = inv.delivery_method_id ' .
     'LEFT OUTER JOIN {prefix}invoice_state as invoice_state ON invoice_state.id = inv.state_id ' .
     'WHERE inv.id=?';
$intRes = mysqli_param_query($strQuery, [
    $intInvoiceId
]);
$invoiceData = mysqli_fetch_assoc($intRes);
if (!$invoiceData)
    die('Could not find invoice data');

$strQuery = 'SELECT * FROM {prefix}company WHERE id=?';
$intRes = mysqli_param_query($strQuery, [
    $invoiceData['company_id']
]);
$recipientData = mysqli_fetch_assoc($intRes);
if (!empty($recipientData['company_id'])) {
    $recipientData['vat_id'] = createVATID($recipientData['company_id']);
} else {
    $recipientData['vat_id'] = '';
}

$strQuery = 'SELECT * FROM {prefix}base WHERE id=?';
$intRes = mysqli_param_query($strQuery, [
    $invoiceData['base_id']
]);
$senderData = mysqli_fetch_assoc($intRes);
if (!$senderData)
    die('Could not find invoice sender data');
$senderData['vat_id'] = createVATID($senderData['company_id']);

$strQuery = 'SELECT pr.product_name, pr.product_code, pr.price_decimals, pr.barcode1, pr.barcode1_type, pr.barcode2, pr.barcode2_type, ir.description, ir.pcs, ir.price, IFNULL(ir.discount, 0) as discount, ir.row_date, ir.vat, ir.vat_included, ir.reminder_row, ir.partial_payment, rt.name type ' .
     'FROM {prefix}invoice_row ir ' .
     'LEFT OUTER JOIN {prefix}row_type rt ON rt.id = ir.type_id ' .
     'LEFT OUTER JOIN {prefix}product pr ON ir.product_id = pr.id ' .
     'WHERE ir.invoice_id=? AND ir.deleted=0 ORDER BY ir.order_no, row_date, pr.product_name DESC, ir.description DESC';
$intRes = mysqli_param_query($strQuery, [
    $intInvoiceId
]);
$invoiceRowData = [];
while ($row = mysqli_fetch_assoc($intRes)) {
    $invoiceRowData[] = $row;
}

if (sesWriteAccess()) {
    mysqli_param_query('UPDATE {prefix}invoice SET print_date=? where id=?',
        [
            date('Ymd'),
            $intInvoiceId
        ]);
}

$printer = instantiateInvoicePrinter(trim($printTemplateFile));
$printer->init($intInvoiceId, $printParameters, $printOutputFileName, $senderData,
    $recipientData, $invoiceData, $invoiceRowData);
$printer->printInvoice();
