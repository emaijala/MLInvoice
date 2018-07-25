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

if (!$authenticated) {
    if (substr($printTemplateFile, -6) === '_email') {
        $printTemplateFile = substr($printTemplateFile, -6);
    }
    $printParameters[1] = $language;
}

$printer = instantiateInvoicePrinter(trim($printTemplateFile));
$printer->init(
    $intInvoiceId, $printParameters, $printOutputFileName,
    $dateOverride, $printTemplate, $authenticated
);
$printer->printInvoice();

if ($authenticated && sesWriteAccess()) {
    dbParamQuery(
        'UPDATE {prefix}invoice SET print_date=? where id=?',
        [
            date('Ymd'),
            $intInvoiceId
        ]
    );
}
