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

 // buffered, so we can redirect later if necessary
ini_set('implicit_flush', 'Off');
ob_start();

require_once 'sessionfuncs.php';

sesVerifySession();

require_once 'vendor/autoload.php';
require_once 'sqlfuncs.php';
require_once 'translator.php';
require_once 'pdf.php';
require_once 'datefuncs.php';
require_once 'miscfuncs.php';

$baseId = getRequest('base', false);
$companyId = getRequest('company', false);
$foreword = getRequest('foreword', '');

$strQuery = 'SELECT * FROM {prefix}company WHERE id=?';
$rows = db_param_query($strQuery, [$companyId]);
if ($rows) {
    $recipientData = $rows[0];
    if (!empty($recipientData['company_id'])) {
        $recipientData['vat_id'] = createVATID($recipientData['company_id']);
    } else {
        $recipientData['vat_id'] = '';
    }

    $strQuery = 'SELECT * FROM {prefix}company_contact WHERE company_id=?'
        . ' AND deleted=0 ORDER BY id';
    $recipientContactData = db_param_query($strQuery, [$companyId]);
} else {
    die('Could not find recipient data');
}

$strQuery = 'SELECT * FROM {prefix}base WHERE id=?';
$rows = db_param_query($strQuery, [$baseId]);
if (!$rows) {
    die('Could not find sender data');
}
$senderData = $rows[0];
$senderData['vat_id'] = createVATID($senderData['company_id']);

$invoiceData = [
    'foreword' => $foreword
];

$printer = instantiateInvoicePrinter('invoice_printer_blank.php');
$printer->init(
    0, '-,' . Translator::getActiveLanguage('') , 'cover.pdf', $senderData,
    $recipientData, $invoiceData, [], $recipientContactData, false, 0, true
);
$printer->printInvoice();
