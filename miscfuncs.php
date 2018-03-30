<?php
/**
 * Miscellaneous functions
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

/**
 * Add slashes if magic quotes are not enabled
 *
 * @param string $strString String
 *
 * @return string
 */
function gpcAddSlashes($strString)
{
    if (!get_magic_quotes_gpc()) {
        return addslashes($strString);
    }
    return $strString;
}

/**
 * Remove slashes if magic quotes are enabled
 *
 * @param string $strString String
 *
 * @return string
 */
function gpcStripSlashes($strString)
{
    if (get_magic_quotes_gpc() && is_string($strString)) {
        return stripslashes($strString);
    }
    return $strString;
}

/**
 * Decode UTF-8 if current charset is something else
 *
 * @param string $str String
 *
 * @return string
 */
function condUtf8Decode($str)
{
    if (_CHARSET_ != 'UTF-8') {
        return utf8_decode($str);
    }
    return $str;
}

/**
 * Encode UTF-8 if current charset is something else
 *
 * @param string $str String
 *
 * @return string
 */
function condUtf8Encode($str)
{
    if (_CHARSET_ != 'UTF-8') {
        return utf8_encode($str);
    }
    return $str;
}

/**
 * Round a value to given decimals
 *
 * @param float  $value             Value
 * @param int    $decimals          Number of decimals
 * @param string $decimalSeparator  Decimal separator
 * @param string $thousandSeparator Thousand separator
 *
 * @return string
 */
function miscRound2Decim($value, $decimals = 2, $decimalSeparator = null,
    $thousandSeparator = null
) {
    return number_format(
        $value, $decimals,
        isset($decimalSeparator) ? $decimalSeparator : Translator::translate('DecimalSeparator'),
        isset($thousandSeparator) ? $thousandSeparator : Translator::translate('ThousandSeparator')
    );
}

/**
 * Round a value to maximum of given decimals. Drop any unnecessary decimals.
 *
 * @param float  $value             Value
 * @param int    $decimals          Number of decimals
 * @param string $decimalSeparator  Decimal separator
 * @param string $thousandSeparator Thousand separator
 *
 * @return string
 */
function miscRound2OptDecim($value, $decimals = 2, $decimalSeparator = null,
    $thousandSeparator = null
) {

    if ($value == floor($value)) {
        $decimals = 0;
    }
    return miscRound2Decim($value, $decimals, $decimalSeparator, $thousandSeparator);
}

/**
 * Calculate check number for a reference number
 *
 * @param int $intValue Reference number
 *
 * @return int
 */
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
        explode('.', substr(chunk_split($intValue, 1, '.'), 0, -1))
    );

    $intSum = 0;
    foreach ($astrTmp as $value) {
        $intSum += $value * array_pop($astrWeight);
    }
    $intCheckNo = ceil($intSum / 10) * 10 - $intSum;

    return $intCheckNo;
}

/**
 * Get a POST request value
 *
 * @param string $strKey     Parameter name
 * @param mixed  $varDefault Default value
 *
 * @return mixed
 */
function getPost($strKey, $varDefault = null)
{
    return isset($_POST[$strKey]) ? gpcStripSlashes($_POST[$strKey]) : $varDefault;
}

/**
 * Get a request value
 *
 * @param string $strKey     Parameter name
 * @param mixed  $varDefault Default value
 *
 * @return mixed
 */
function getRequest($strKey, $varDefault = null)
{
    return isset($_REQUEST[$strKey]) ? gpcStripSlashes($_REQUEST[$strKey]) : $varDefault;
}

/**
 * Get a GET request value
 *
 * @param string $strKey     Parameter name
 * @param mixed  $varDefault Default value
 *
 * @return mixed
 */
function getGet($strKey, $varDefault = null)
{
    return isset($_GET[$strKey]) ? gpcStripSlashes($_GET[$strKey]) : $varDefault;
}

/**
 * Get a POST or GET request value
 *
 * @param string $strKey     Parameter name
 * @param mixed  $varDefault Default value
 *
 * @return mixed
 */
function getPostRequest($strKey, $varDefault = null)
{
    return getPost($strKey, getRequest($strKey, $varDefault));
}

/**
 * Get page title
 *
 * @param string $strFunc Function
 * @param string $strList List
 * @param string $strForm Form
 *
 * @return string
 */
function getPageTitle($strFunc, $strList, $strForm)
{
    switch ($strFunc) {
    case 'open_invoices':
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
    case 'invoices':
        if ($strForm) {
            return Translator::translate('Invoice');
        } else {
            return Translator::translate('Invoices');
        }
        break;
    case 'archived_invoices':
        if ($strForm) {
            return Translator::translate('Invoice');
        } else {
            return Translator::translate('ArchivedInvoices');
        }
        break;
    case 'companies':
        if ($strForm) {
            return Translator::translate('Client');
        } else {
            return Translator::translate('Clients');
        }
        break;
    case 'reports':
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
    case 'settings':
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
    case 'system':
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
    case 'profile':
        return Translator::translate('Profile');
    }
    return '';
}

/**
 * Convert a PHP ini value to integer
 *
 * @param string $value Value
 *
 * @return int
 */
function phpIniValueToInteger($value)
{
    $unit = strtoupper(substr($value, -1));
    if (!in_array(
        $unit,
        [
            'P',
            'T',
            'G',
            'M',
            'K'
        ]
    )
    ) {
        return $value;
    }
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

/**
 * Get maximum file upload size
 *
 * @return int
 */
function getMaxUploadSize()
{
    return min(
        phpIniValueToInteger(ini_get('post_max_size')),
        phpIniValueToInteger(ini_get('upload_max_filesize'))
    );
}

/**
 * Convert a file size to a human-readable value
 *
 * @param int $value File size
 *
 * @return string
 */
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

/**
 * Encode string in XML
 *
 * @param string $str String
 *
 * @return string
 */
function xmlEncode($str)
{
    $str = str_replace('&', '&amp;', $str);
    $str = str_replace('<', '&lt;', $str);
    $str = str_replace('>', '&gt;', $str);
    $str = str_replace('"', '&quot;', $str);
    return $str;
}

/**
 * Sanitize a string
 *
 * @param string $str String
 *
 * @return string
 */
function sanitize($str)
{
    return preg_replace('/[^\w\d]/', '', $str);
}

/**
 * Calculate row sum for an invoice row
 *
 * @param array $row Row
 *
 * @return array
 */
function calculateRowSum($row)
{
    $price = $row['price'];
    $count = $row['pcs'];
    $VAT = $row['vat'];
    $VATIncluded = $row['vat_included'];
    $discount = $row['discount'];
    $discountAmount = $row['discount_amount'];

    if ($discount) {
        $price *= (1 - $discount / 100);
    }
    if ($discountAmount) {
        $price -= $discountAmount;
    }

    if ($VATIncluded) {
        $rowSumVAT = $count * $price;
        $rowSum = ($rowSumVAT / (1 + $VAT / 100));
        $rowVAT = $rowSumVAT - $rowSum;
    } else {
        $rowSum = $count * $price;
        $rowVAT = ($rowSum * ($VAT / 100));
        $rowSumVAT = $rowSum + $rowVAT;
    }
    return [
        $rowSum,
        $rowVAT,
        $rowSumVAT
    ];
}

/**
 * Create a VAT ID
 *
 * @param string $id ID
 *
 * @return string
 */
function createVATID($id)
{
    $id = strtoupper(str_replace('-', '', $id));
    if (!preg_match('/^[A-Z]{2}/', $id)) {
        $id = "FI$id";
    }
    return $id;
}

/**
 * Get our path
 *
 * @return string
 */
function getSelfPath()
{
    return _PROTOCOL_ . $_SERVER['HTTP_HOST'] . getSelfDirectory();
}

/**
 * Get our directory
 *
 * @return string
 */
function getSelfDirectory()
{
    $path = $_SERVER['SCRIPT_NAME'];
    $path = str_replace('\\', '/', $path);
    $p = strrpos($path, '/');
    if ($p > 0) {
        $path = substr($path, 0, $p);
    } else {
        $path = '';
    }
    return $path;
}

/**
 * Instantiate an invoice printer
 *
 * @param string $printTemplateFile Print template
 *
 * @return object
 */
function instantiateInvoicePrinter($printTemplateFile)
{
    if (!is_readable($printTemplateFile)) {
        return null;
    }

    $className = $printTemplateFile;
    $className = str_replace('.php', '', $className);
    $className = str_replace('_', ' ', $className);
    $className = ucwords($className);
    $className = str_replace(' ', '', $className);

    include_once $printTemplateFile;
    return new $className();
}

/**
 * Format a reference number
 *
 * @param string $refNumber Reference number
 *
 * @return string
 */
function formatRefNumber($refNumber)
{
    if (strncasecmp($refNumber, 'RF', 2) == 0) {
        return strtoupper(trim(chunk_split($refNumber, 4, ' ')));
    }
    return trim(strrev(chunk_split(strrev($refNumber), 5, ' ')));
}

/**
 * Add a file timestamp parameter to a filename
 *
 * @param string $filename Filename
 *
 * @return string
 */
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

/**
 * Get default values for an invoice
 *
 * @param int $invoiceId     Invoice ID
 * @param int $baseId        Base ID
 * @param int $companyId     Company ID
 * @param int $invoiceDate   Invoice date
 * @param int $intervalType  Invoice interval
 * @param int $invoiceNumber Invoice number
 *
 * @return array
 */
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

        $rows = dbParamQuery($query, $params);
        if ($rows) {
            $invoiceNumber = 0;
        }
    }

    if (!$invoiceNumber) {
        $maxNr = getMaxInvoiceNumber(
            $invoiceId,
            getSetting('invoice_numbering_per_base') && $baseId ? $baseId : null,
            $perYear
        );
        if ($maxNr === null && $perYear) {
            $maxNr = getMaxInvoiceNumber(
                $invoiceId,
                getSetting('invoice_numbering_per_base') && $baseId ? $baseId : null,
                false
            );
        }
        $invoiceNumber = $maxNr + 1;
    }
    if ($invoiceNumber < 100) {
        $invoiceNumber = 100; // min ref number length is 3 + check digit, make sure invoice number matches that
    }
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
