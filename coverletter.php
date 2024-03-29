<?php
/**
 * Cover letter
 *
 * PHP version 7
 *
 * Copyright (C) Ere Maijala 2010-2021
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
require_once 'sqlfuncs.php';

initDbConnection();
sesVerifySession();

require_once 'vendor/autoload.php';
require_once 'translator.php';
require_once 'pdf.php';
require_once 'datefuncs.php';
require_once 'miscfuncs.php';

$baseId = getPostOrQuery('base', false);
$companyId = getPostOrQuery('company', false);
$foreword = getPostOrQuery('foreword', '');

$strQuery = 'SELECT * FROM {prefix}company WHERE id=?';
$rows = dbParamQuery($strQuery, [$companyId]);
if ($rows) {
    $recipientData = $rows[0];
    if (!empty($recipientData['company_id'])) {
        $recipientData['vat_id'] = createVATID($recipientData['company_id']);
    } else {
        $recipientData['vat_id'] = '';
    }

    $strQuery = 'SELECT * FROM {prefix}company_contact WHERE company_id=?'
        . ' AND deleted=0 ORDER BY id';
    $recipientContactData = dbParamQuery($strQuery, [$companyId]);
} else {
    die('Could not find recipient data');
}

$strQuery = 'SELECT * FROM {prefix}base WHERE id=?';
$rows = dbParamQuery($strQuery, [$baseId]);
if (!$rows) {
    die('Could not find sender data');
}
$senderData = $rows[0];
$senderData['vat_id'] = createVATID($senderData['company_id']);

$invoiceData = [
    'foreword' => $foreword
];

$printer = getInvoicePrinter('invoice_printer_blank.php');
$printer->init(
    0, '-,' . Translator::getActiveLanguage(''), 'cover.pdf', false, 0, true
);
$printer->setSenderData($senderData);
$printer->setInvoiceData($invoiceData);
$printer->setRecipientData($recipientData, $recipientContactData);
$printer->printInvoice();
