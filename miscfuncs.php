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
function gpcAddSlashes($strString)
{
    if (!get_magic_quotes_gpc())
        return addslashes($strString);
    return $strString;
}

function gpcStripSlashes($strString)
{
    if (get_magic_quotes_gpc() && is_string($strString))
        return stripslashes($strString);
    return $strString;
}

function cond_utf8_decode($str)
{
    if (_CHARSET_ != 'UTF-8')
        return utf8_decode($str);
    return $str;
}

function cond_utf8_encode($str)
{
    if (_CHARSET_ != 'UTF-8')
        return utf8_encode($str);
    return $str;
}

function miscRound2Decim($value, $decimals = 2, $decimalSeparator = null,
    $thousandSeparator = null)
{
    return number_format($value, $decimals,
        isset($decimalSeparator) ? $decimalSeparator : Translator::translate('DecimalSeparator'),
        isset($thousandSeparator) ? $thousandSeparator : Translator::translate('ThousandSeparator'));
}

function miscRound2OptDecim($value, $decimals = 2, $decimalSeparator = null,
    $thousandSeparator = null)
{
    if ($value == floor($value)) {
        $decimals = 0;
    }
    return miscRound2Decim($value, $decimals, $decimalSeparator, $thousandSeparator);
}

function miscCalcCheckNo($intValue)
{
    $astrWeight = [
        '1',
        '3',
        '7',
        '1',
        '3',
        '7',
        '1',
        '3',
        '7',
        '1',
        '3',
        '7',
        '1',
        '3',
        '7',
        '1',
        '3',
        '7',
        '1',
        '3',
        '7',
        '1',
        '3',
        '7',
        '1',
        '3',
        '7',
        '1',
        '3',
        '7',
        '1',
        '3',
        '7',
        '1',
        '3',
        '7',
        '1',
        '3',
        '7',
        '1',
        '3',
        '7',
        '1',
        '3',
        '7',
        '1',
        '3',
        '7',
        '1',
        '3',
        '7',
        '1',
        '3',
        '7',
        '1',
        '3',
        '7',
        '1',
        '3',
        '7',
        '1',
        '3',
        '7',
        '1',
        '3',
        '7',
        '1',
        '3',
        '7',
        '1',
        '3',
        '7',
        '1',
        '3',
        '7',
        '1',
        '3',
        '7',
        '1',
        '3',
        '7',
        '1',
        '3',
        '7',
        '1',
        '3',
        '7',
        '1',
        '3',
        '7',
        '1',
        '3',
        '7',
        '1',
        '3',
        '7',
        '1',
        '3',
        '7',
        '1',
        '3',
        '7',
        '1',
        '3',
        '7',
        '1',
        '3',
        '7',
        '1',
        '3',
        '7',
        '1',
        '3',
        '7'
    ];
    $astrTmp = array_reverse(
        explode('.', substr(chunk_split($intValue, 1, '.'), 0, -1)));

    $intSum = 0;
    foreach ($astrTmp as $value) {
        $intSum += $value * array_pop($astrWeight);
    }
    $intCheckNo = ceil($intSum / 10) * 10 - $intSum;

    return $intCheckNo;
}

function getPost($strKey, $varDefault = null)
{
    return isset($_POST[$strKey]) ? gpcStripSlashes($_POST[$strKey]) : $varDefault;
}

function getRequest($strKey, $varDefault = null)
{
    return isset($_REQUEST[$strKey]) ? gpcStripSlashes($_REQUEST[$strKey]) : $varDefault;
}

function getGet($strKey, $varDefault = null)
{
    return isset($_GET[$strKey]) ? gpcStripSlashes($_GET[$strKey]) : $varDefault;
}

function getPostRequest($strKey, $varDefault = null)
{
    return getPost($strKey, getRequest($strKey, $varDefault));
}

function getPageTitle($strFunc, $strList, $strForm)
{
    switch ($strFunc) {
    case 'open_invoices' :
        if ($strForm) {
            if (getRequest('offer')
                || (($invId = getRequest('id')) && isOffer($invId))
            ) {
                return Translator::translate('Offer');
            }
            return Translator::translate('Invoice');
        } else {
            return Translator::translate('OpenAndUnpaidInvoices');
        }
        break;
    case 'invoices' :
        if ($strForm)
            return Translator::translate('Invoice');
        else
            return Translator::translate('Invoices');
        break;
    case 'archived_invoices' :
        if ($strForm)
            return Translator::translate('Invoice');
        else
            return Translator::translate('ArchivedInvoices');
        break;
    case 'companies' :
        if ($strForm)
            return Translator::translate('Client');
        else
            return Translator::translate('Clients');
        break;
    case 'reports' :
        switch ($strForm) {
        case 'invoice' :
            return Translator::translate('InvoiceReport');
        case 'product' :
            return Translator::translate('ProductReport');
        case 'product_stock' :
            return Translator::translate('ProductStockReport');
        default :
            return Translator::translate('Reports');
        }
        break;
    case 'settings' :
        if ($strForm) {
            switch ($strForm) {
            case 'base' :
                return Translator::translate('Base');
            case 'product' :
                return Translator::translate('Product');
            default :
                return Translator::translate('Settings');
            }
        } else {
            switch ($strList) {
            case 'settings' :
                return Translator::translate('GeneralSettings');
            case 'base' :
                return Translator::translate('Bases');
            case 'product' :
                return Translator::translate('Products');
            default :
                return Translator::translate('Settings');
            }
        }
        break;
    case 'system' :
        if ($strForm) {
            switch ($strForm) {
            case 'user' :
                return Translator::translate('User');
            case 'session_type' :
                return Translator::translate('SessionType');
            case 'row_type' :
                return Translator::translate('RowType');
            case 'print_template' :
                return Translator::translate('PrintTemplate');
            case 'invoice_state' :
                return Translator::translate('InvoiceState');
            case 'delivery_terms' :
                return Translator::translate('DeliveryTerms');
            case 'delivery_method' :
                return Translator::translate('DeliveryMethod');
            default :
                return Translator::translate('System');
            }
        } else {
            switch ($strList) {
            case 'user' :
                return Translator::translate('Users');
            case 'session_type' :
                return Translator::translate('SessionTypes');
            case 'row_type' :
                return Translator::translate('RowTypes');
            case 'print_template' :
                return Translator::translate('PrintTemplates');
            case 'invoice_state' :
                return Translator::translate('InvoiceStates');
            case 'delivery_terms' :
                return Translator::translate('DeliveryTerms');
            case 'delivery_method' :
                return Translator::translate('DeliveryMethods');
            default :
                return Translator::translate('System');
            }
        }
        break;
    case 'import_statement' :
        return Translator::translate('ImportAccountStatement');
    }
    return '';
}

function phpIniValueToInteger($value)
{
    $unit = strtoupper(substr($value, -1));
    if (!in_array($unit,
        [
            'P',
            'T',
            'G',
            'M',
            'K'
        ]))
        return $value;
    $value = substr($value, 0, -1);
    switch ($unit) {
    case 'P' :
        $value *= 1024;
    case 'T' :
        $value *= 1024;
    case 'G' :
        $value *= 1024;
    case 'M' :
        $value *= 1024;
    case 'K' :
        $value *= 1024;
    }
    return $value;
}

function getMaxUploadSize()
{
    return min(phpIniValueToInteger(ini_get('post_max_size')),
        phpIniValueToInteger(ini_get('upload_max_filesize')));
}

function fileSizeToHumanReadable($value)
{
    $suffixes = [
        'B',
        'KB',
        'MB',
        'GB',
        'TB',
        'PB'
    ];

    $idx = 0;
    while ($idx < count($suffixes) - 1 && $value / 1024 > 0.9) {
        $value /= 1024;
        ++$idx;
    }
    return round($value, 2) . ' ' . $suffixes[$idx];
}

function xml_encode($str)
{
    $str = str_replace('&', '&amp;', $str);
    $str = str_replace('<', '&lt;', $str);
    $str = str_replace('>', '&gt;', $str);
    $str = str_replace('"', '&quot;', $str);
    return $str;
}

if (!function_exists('str_getcsv')) {

    function str_getcsv($input, $delimiter = ',', $enclosure = '"', $escape = null, $eol = null)
    {
        $temp = fopen('php://memory', 'rw');
        fwrite($temp, $input);
        fseek($temp, 0);
        $r = fgetcsv($temp, 4096, $delimiter, $enclosure);
        fclose($temp);
        return $r;
    }
}

function fgets_charset($handle, $charset, $line_ending = "\n")
{
    if (strncmp($charset, 'UTF-16', 6) == 0) {
        $be = $charset == 'UTF-16' || $charset == 'UTF-16BE';
        $str = '';
        $le_pos = 0;
        $le_len = strlen($line_ending);
        while (!feof($handle)) {
            $c1 = fgetc($handle);
            $c2 = fgetc($handle);
            if ($c1 === false || $c2 === false) {
                break;
            }
            $str .= $c1 . $c2;
            if (($be && ord($c1) == 0 && $c2 == $line_ending[$le_pos])
                || (!$be && ord($c2) == 0 && $c1 == $line_ending[$le_pos])
            ) {
                if (++$le_pos >= $le_len)
                    break;
            } else {
                $le_pos = 0;
            }
        }
        $str = iconv($charset, _CHARSET_, $str);
    } else {
        $str = '';
        $le_pos = 0;
        $le_len = strlen($line_ending);
        while (!feof($handle)) {
            $c1 = fgetc($handle);
            if ($c1 === false) {
                break;
            }
            $str .= $c1;
            if ($c1 == $line_ending[$le_pos]) {
                if (++$le_pos >= $le_len) {
                    break;
                }
            } else {
                $le_pos = 0;
            }
        }
        $conv_str = iconv($charset, _CHARSET_, $str);
        if ($str && !$conv_str) {
            error_log(
                "Conversion from '$charset' to '" . _CHARSET_
                . "' failed for string '$str'"
            );
        } else {
            $str = $conv_str;
        }
    }
    return $str;
}

function iconvErrorHandler($errno, $errstr, $errfile, $errline)
{
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
}

function try_iconv($from, $to, $str)
{
    set_error_handler('iconvErrorHandler');
    try {
        $str = iconv($from, $to, $str);
    } catch (ErrorException $e) {
        restore_error_handler();
        return false;
    }
    restore_error_handler();
    return $str;
}

function sanitize($str)
{
    return preg_replace('/[^\w\d]/', '', $str);
}

function calculateRowSum($price, $count, $VAT, $VATIncluded, $discount)
{
    if (isset($discount))
        $price *= (1 - $discount / 100);

    if ($VATIncluded) {
        $rowSumVAT = round($count * $price, 2);
        $rowSum = round(($rowSumVAT / (1 + $VAT / 100)), 2);
        $rowVAT = $rowSumVAT - $rowSum;
    } else {
        $rowSum = round($count * $price, 2);
        $rowVAT = round(($rowSum * ($VAT / 100)), 2);
        $rowSumVAT = $rowSum + $rowVAT;
    }
    return [
        $rowSum,
        $rowVAT,
        $rowSumVAT
    ];
}

function createVATID($id)
{
    $id = strtoupper(str_replace('-', '', $id));
    if (!preg_match('/^[A-Z]{2}/', $id)) {
        $id = "FI$id";
    }
    return $id;
}

function getSelfPath()
{
    return _PROTOCOL_ . $_SERVER['HTTP_HOST'] . getSelfDirectory();
}

function getSelfDirectory()
{
    $path = $_SERVER['PHP_SELF'];
    $p = strrpos($path, DIRECTORY_SEPARATOR);
    if ($p > 0) {
        $path = substr($path, 0, $p);
    } else {
        $path = '';
    }
    return $path;
}

function instantiateInvoicePrinter($printTemplateFile)
{
    $className = $printTemplateFile;
    $className = str_replace('.php', '', $className);
    $className = str_replace('_', ' ', $className);
    $className = ucwords($className);
    $className = str_replace(' ', '', $className);

    require_once $printTemplateFile;
    return new $className();
}

function formatRefNumber($refNumber)
{
    if (strncasecmp($refNumber, 'RF', 2) == 0) {
        return strtoupper(trim(chunk_split($refNumber, 4, ' ')));
    }
    return trim(strrev(chunk_split(strrev($refNumber), 5, ' ')));
}

function addFileTimestamp($filename)
{
    if (!file_exists($filename)) {
        return $filename;
    }
    $mtime = filemtime($filename);
    if (false !== $mtime) {
        $filename .= strstr($filename, '?') ? '&_=' : '?_=';
        $filename .= $mtime;
    }
    return $filename;
}

function getInvoiceDefaults($invoiceId, $baseId, $companyId, $invoiceDate,
    $intervalType, $invoiceNumber
) {
    $perYear = getSetting('invoice_numbering_per_year');

    // If the invoice already has an invoice number, verify that it's not in use in another invoice
    if ($invoiceNumber) {
        $query = 'SELECT ID FROM {prefix}invoice where deleted=0 AND id!=? AND invoice_no=?';
        $params = [
            $invoiceId,
            $invoiceNumber
        ];
        if (getSetting('invoice_numbering_per_base') && $baseId) {
            $query .= ' AND base_id=?';
            $params[] = $baseId;
        }
        if ($perYear) {
            $query .= ' AND invoice_date >= ' . dateConvDate2DBDate($invoiceDate);
        }

        $res = mysqli_param_query($query, $params);
        if (mysqli_fetch_assoc($res)) {
            $invoiceNumber = 0;
        }
    }

    if (!$invoiceNumber) {
        $maxNr = get_max_invoice_number(
            $invoiceId,
            getSetting('invoice_numbering_per_base') && $baseId ? $baseId : null,
            $perYear
        );
        if ($maxNr === null && $perYear) {
            $maxNr = get_max_invoice_number(
                $invoiceId,
                getSetting('invoice_numbering_per_base') && $baseId ? $baseId : null,
                false
            );
        }
        $invoiceNumber = $maxNr + 1;
    }
    if ($invoiceNumber < 100)
        $invoiceNumber = 100; // min ref number length is 3 + check digit, make sure invoice number matches that
    $refNr = $invoiceNumber . miscCalcCheckNo($invoiceNumber);
    $strDate = date(Translator::translate('DateFormat'));
    $strDueDate = date(
        Translator::translate('DateFormat'),
        mktime(0, 0, 0, date('m'), date('d') + getPaymentDays($companyId), date('Y'))
    );
    switch ($intervalType) {
    case 2:
        $nextIntervalDate = date(
            Translator::translate('DateFormat'),
            mktime(0, 0, 0, date('m') + 1, date('d'), date('Y'))
        );
        break;
    case 3:
        $nextIntervalDate = date(
            Translator::translate('DateFormat'),
            mktime(0, 0, 0, date('m'), date('d'), date('Y') + 1)
        );
        break;
    case 4:
    case 5:
    case 6:
    case 7:
    case 8:
        $nextIntervalDate = date(
            Translator::translate('DateFormat'),
            mktime(0, 0, 0, date('m') + $intervalType - 2, date('d'), date('Y'))
        );
        break;
    default :
        $nextIntervalDate = '';
    }
    return [
        'invoice_no' => $invoiceNumber,
        'ref_no' => $refNr,
        'date' => $strDate,
        'due_date' => $strDueDate,
        'next_interval_date' => $nextIntervalDate
    ];
}
