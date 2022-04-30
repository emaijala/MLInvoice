<?php
/**
 * Printouts
 *
 * PHP version 7
 *
 * Copyright (C) Samu Reinikainen 2004-2008
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

$authenticated = true;
$intInvoiceId = getPostOrQuery('id', false);
$printTemplate = getPostOrQuery('t', false);
$dateOverride = false;
$language = getPostOrQuery('l', false);
$uuid = getPostOrQuery('i', false);
$hash = getPostOrQuery('c', false);
$ts = getPostOrQuery('s', false);
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
require_once 'translator.php';
require_once 'datefuncs.php';
require_once 'miscfuncs.php';

if ($authenticated) {
    $printTemplate = getPostOrQuery('template', 1);
    if ($date = getPostOrQuery('date', false)) {
        $dateOverride = dateConvYmd2DBDate($date);
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

if ($authenticated && is_array($intInvoiceId)) {

    if (!sesWriteAccess()) {
        die('Write access required for printing multiple');
    }

    include_once 'pdf.php';
    $mainPdf = new PDF('P', 'mm', 'A4', _CHARSET_ == 'UTF-8', _CHARSET_, false);
    foreach ($intInvoiceId as $singleId) {
        $printer = getInvoicePrinter($printTemplateFile);
        $uses = class_uses($printer);
        if (in_array('InvoicePrinterEmailTrait', $uses)
            || $printer instanceof InvoicePrinterXSLT
            || $printer instanceof InvoicePrinterBlank
        ) {
            die('Cannot print multiple with the given print template');
        }

        verifyInvoiceDataForPrinting($singleId);

        $printer->init(
            $singleId, $printParameters, $printOutputFileName,
            $dateOverride, $printTemplate, $authenticated
        );

        $pdfResult = $printer->createPrintout();

        // Import PDF
        $pageCount = $mainPdf->setSourceFile(
            \setasign\Fpdi\PdfParser\StreamReader::createByString($pdfResult['data'])
        );
        for ($i = 1; $i <= $pageCount; $i++) {
            $tplx = $mainPdf->importPage($i);
            $size = $mainPdf->getTemplateSize($tplx);
            $mainPdf->AddPage('P', [$size['width'], $size['height']]);
            $mainPdf->useTemplate($tplx);
        }

        if ($authenticated && sesWriteAccess()) {
            updateInvoicePrintDate($singleId);
        }
    }
    $filename = Translator::Translate('File') . '_' . date('Y-m-d_H:i:s') . '.pdf';
    $pdfResult['headers']['Content-Disposition'] = 'inline; filename="' . $filename . '"';
    foreach ($pdfResult['headers'] as $header => $value) {
        header("$header: $value");
    }
    echo $mainPdf->Output('', 'S');
} else {
    $printer = getInvoicePrinter($printTemplateFile);
    if (null === $printer) {
        die("Could not read print template '$printTemplateFile'");
    }
    $printer->init(
        $intInvoiceId, $printParameters, $printOutputFileName,
        $dateOverride, $printTemplate, $authenticated
    );
    $printer->printInvoice();

    if ($authenticated && sesWriteAccess()) {
        updateInvoicePrintDate($intInvoiceId);
    }
}
