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
        isset($decimalSeparator) ? $decimalSeparator : $GLOBALS['locDecimalSeparator'], 
        isset($thousandSeparator) ? $thousandSeparator : $GLOBALS['locThousandSeparator']);
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

function getPost($strKey, $varDefault)
{
    return isset($_POST[$strKey]) ? gpcStripSlashes($_POST[$strKey]) : $varDefault;
}

function getRequest($strKey, $varDefault)
{
    return isset($_REQUEST[$strKey]) ? gpcStripSlashes($_REQUEST[$strKey]) : $varDefault;
}

function getGet($strKey, $varDefault)
{
    return isset($_GET[$strKey]) ? gpcStripSlashes($_GET[$strKey]) : $varDefault;
}

function getPostRequest($strKey, $varDefault)
{
    return getPost($strKey, getRequest($strKey, $varDefault));
}

function getPageTitle($strFunc, $strList, $strForm)
{
    switch ($strFunc) {
    case 'open_invoices' :
        if ($strForm)
            return $GLOBALS['locInvoice'];
        else
            return $GLOBALS['locOpenAndUnpaidInvoices'];
        break;
    case 'invoices' :
        if ($strForm)
            return $GLOBALS['locInvoice'];
        else
            return $GLOBALS['locInvoices'];
        break;
    case 'archived_invoices' :
        if ($strForm)
            return $GLOBALS['locInvoice'];
        else
            return $GLOBALS['locArchivedInvoices'];
        break;
    case 'companies' :
        if ($strForm)
            return $GLOBALS['locClient'];
        else
            return $GLOBALS['locClients'];
        break;
    case 'reports' :
        switch ($strForm) {
        case 'invoice' :
            return $GLOBALS['locInvoiceReport'];
        case 'product' :
            return $GLOBALS['locProductReport'];
        default :
            return $GLOBALS['locReports'];
        }
        break;
    case 'settings' :
        if ($strForm) {
            switch ($strForm) {
            case 'base' :
                return $GLOBALS['locBase'];
            case 'product' :
                return $GLOBALS['locProduct'];
            default :
                return $GLOBALS['locSettings'];
            }
        } else {
            switch ($strList) {
            case 'settings' :
                return $GLOBALS['locGeneralSettings'];
            case 'base' :
                return $GLOBALS['locBases'];
            case 'product' :
                return $GLOBALS['locProducts'];
            default :
                return $GLOBALS['locSettings'];
            }
        }
        break;
    case 'system' :
        if ($strForm) {
            switch ($strForm) {
            case 'user' :
                return $GLOBALS['locUser'];
            case 'session_type' :
                return $GLOBALS['locSessionType'];
            case 'row_type' :
                return $GLOBALS['locRowType'];
            case 'print_template' :
                return $GLOBALS['locPrintTemplate'];
            case 'invoice_state' :
                return $GLOBALS['locInvoiceState'];
            case 'delivery_terms' :
                return $GLOBALS['locDeliveryTerms'];
            case 'delivery_method' :
                return $GLOBALS['locDeliveryMethod'];
            default :
                return $GLOBALS['locSystem'];
            }
        } else {
            switch ($strList) {
            case 'user' :
                return $GLOBALS['locUsers'];
            case 'session_type' :
                return $GLOBALS['locSessionTypes'];
            case 'row_type' :
                return $GLOBALS['locRowTypes'];
            case 'print_template' :
                return $GLOBALS['locPrintTemplates'];
            case 'invoice_state' :
                return $GLOBALS['locInvoiceStates'];
            case 'delivery_terms' :
                return $GLOBALS['locDeliveryTerms'];
            case 'delivery_method' :
                return $GLOBALS['locDeliveryMethods'];
            default :
                return $GLOBALS['locSystem'];
            }
        }
        break;
    case 'import_statement' :
        return $GLOBALS['locImportAccountStatement'];
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
    if (substr($charset, 0, 6) == 'UTF-16') {
        $be = $charset == 'UTF-16' || $charset == 'UTF-16BE';
        $str = '';
        $le_pos = 0;
        $le_len = strlen($line_ending);
        while (!feof($handle)) {
            $c1 = fgetc($handle);
            $c2 = fgetc($handle);
            if ($c1 === false || $c2 === false)
                break;
            $str .= $c1 . $c2;
            if (($be && ord($c1) == 0 && $c2 == $line_ending[$le_pos]) ||
                 (!$be && ord($c2) == 0 && $c1 == $line_ending[$le_pos])) {
                if (++$le_pos >= $le_len)
                    break;
            } else
                $le_pos = 0;
        }
        $str = iconv($charset, _CHARSET_, $str);
    } else {
        $str = '';
        $le_pos = 0;
        $le_len = strlen($line_ending);
        while (!feof($handle)) {
            $c1 = fgetc($handle);
            if ($c1 === false)
                break;
            $str .= $c1;
            if ($c1 == $line_ending[$le_pos]) {
                if (++$le_pos >= $le_len)
                    break;
            } else
                $le_pos = 0;
        }
        $conv_str = iconv($charset, _CHARSET_, $str);
        if ($str && !$conv_str)
            error_log(
                "Conversion from '$charset' to '" . _CHARSET_ .
                     "' failed for string '$str'");
        else
            $str = $conv_str;
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
    $p = strrpos($path, '/');
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