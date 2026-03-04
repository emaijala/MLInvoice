<?php
/**
 * Miscellaneous functions
 *
 * PHP version 8
 *
 * Copyright (C) Samu Reinikainen 2004-2008
 * Copyright (C) Ere Maijala 2010-2021.
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
 * @param ?float $value             Value
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
        $value ?? 0, $decimals,
        $decimalSeparator ?? Translator::translate('DecimalSeparator'),
        $thousandSeparator ?? Translator::translate('ThousandSeparator')
    );
}

/**
 * Round a value to given decimals using the US separators
 *
 * @param float $value    Value
 * @param int   $decimals Number of decimals
 *
 * @return string
 */
function miscRound2US($value, $decimals = 2)
{
    return miscRound2Decim($value, $decimals, '.', ',');
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
 * Create an RF reference from a Finnish reference number
 *
 * @param string $refNr Finnish reference number
 *
 * @return string
 */
function createRFReference($refNr)
{
    $remainder = ($refNr . '271500') % 97;
    $check = 98 - $remainder;
    if ($check < 10) {
        $check = "0$check";
    }
    return "RF$check$refNr";
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
    if ($strKey === '') {
        return $_POST;
    }
    return $_POST[$strKey] ?? $varDefault;
}

/**
 * Get a query parameter value
 *
 * @param string $strKey     Parameter name
 * @param mixed  $varDefault Default value
 *
 * @return mixed
 */
function getQuery($strKey, $varDefault = null)
{
    return $_GET[$strKey] ?? $varDefault;
}

/**
 * Get a POST request or query parameter value
 *
 * @param string $strKey     Parameter name
 * @param mixed  $varDefault Default value
 *
 * @return mixed
 */
function getPostOrQuery($strKey, $varDefault = null)
{
    return getPost($strKey, getQuery($strKey, $varDefault));
}

/**
 * Get search parameters from query parameters
 *
 * @return array;
 */
function getSearchParamsFromRequest()
{
    return array_filter(
        $_GET,
        function ($key) {
            return strncmp($key, 's_', 2) === 0;
        },
        ARRAY_FILTER_USE_KEY
    );
}

/**
 * Get page title
 *
 * @param string $strFunc   Function
 * @param string $strList   List
 * @param string $strForm   Form
 * @param string $operation Operation
 *
 * @return string
 */
function getPageTitle($strFunc, $strList, $strForm, $operation)
{
    switch ($strFunc ? $strFunc : $strList) {
    case 'start_page':
        if ($strForm) {
            if (getPostOrQuery('offer')
                || (($invId = getPostOrQuery('id')) && isOffer($invId))
            ) {
                return Translator::translate('Offer');
            }
            return Translator::translate('Invoice');
        } else {
            return Translator::translate('StartPage');
        }
        break;
    case 'invoice':
        if ($strForm) {
            return Translator::translate('Invoice');
        } else {
            return Translator::translate('AllNonArchived');
        }
        break;
    case 'invoices':
        if ($strForm) {
            return Translator::translate('Invoice');
        } else {
            return Translator::translate('NonArchivedInvoices');
        }
    case 'invoice_templates':
        if ($strForm) {
            return Translator::translate('RecurringInvoiceTemplate');
        } else {
            return Translator::translate('RecurringInvoiceTemplates');
        }
        break;
    case 'invoice_templates_due':
        if ($strForm) {
            return Translator::translate('RecurringInvoiceTemplate');
        } else {
            return Translator::translate('RecurringInvoiceTemplatesDueForProcessing');
        }
        break;
    case 'archived_invoices':
        if ($strForm) {
            return Translator::translate('Invoice');
        } else {
            return Translator::translate('ArchivedInvoices');
        }
        break;
    case 'offers':
        if ($strForm) {
            return Translator::translate('Offer');
        } else {
            return Translator::translate('NonArchivedOffers');
        }
    case 'archived_offers':
        if ($strForm) {
            return Translator::translate('Offer');
        } else {
            return Translator::translate('ArchivedOffers');
        }
        break;
    case 'company':
        if ($strForm) {
            return Translator::translate('Client');
        } else {
            return Translator::translate('Clients');
        }
        break;
    case 'accounting_report':
        return Translator::translate('AccountingReport');
    case 'invoice_report':
        return Translator::translate('InvoiceReport');
    case 'product_report':
        return Translator::translate('ProductReport');
    case 'product_stock_report':
        return Translator::translate('ProductStockReport');
    case 'settings':
        if ($strForm) {
            switch ($strForm) {
            case 'base':
                return Translator::translate('Base');
            case 'product':
                return Translator::translate('Product');
            case 'default_value':
                return Translator::translate('DefaultValue');
            case 'attachment':
                return Translator::translate('Attachment');
            default:
                return Translator::translate('Settings');
            }
        } else {
            switch ($strList) {
            case 'settings':
                return Translator::translate('GeneralSettings');
            case 'base':
                return Translator::translate('Bases');
            case 'product':
                return Translator::translate('Products');
            case 'default_value':
                return Translator::translate('DefaultValues');
            case 'attachment':
                return Translator::translate('Attachments');
            default:
                return Translator::translate('Settings');
            }
        }
        break;
    case 'system':
        switch ($strForm ?? '') {
        case 'user':
            return Translator::translate('User');
        case 'session_type':
            return Translator::translate('SessionType');
        case 'row_type':
            return Translator::translate('RowType');
        case 'print_template':
            return Translator::translate('PrintTemplate');
        case 'invoice_state':
            return Translator::translate('InvoiceState');
        case 'invoice_type':
            return Translator::translate('InvoiceType');
        case 'delivery_terms':
            return Translator::translate('DeliveryTerms');
        case 'delivery_method':
            return Translator::translate('DeliveryMethod');
        }
        switch ($strList) {
        case 'user':
            return Translator::translate('Users');
        case 'session_type':
            return Translator::translate('SessionTypes');
        case 'row_type':
            return Translator::translate('RowTypes');
        case 'print_template':
            return Translator::translate('PrintTemplates');
        case 'invoice_state':
            return Translator::translate('InvoiceStates');
        case 'invoice_type':
            return Translator::translate('InvoiceTypes');
        case 'delivery_terms':
            return Translator::translate('DeliveryTerms');
        case 'delivery_method':
            return Translator::translate('DeliveryMethods');
        }
        switch ($operation) {
        case 'backup':
        case 'dbdump':
            return Translator::translate('BackupDatabase');
        case 'import':
            return Translator::translate('ImportData');
        case 'export':
            return Translator::translate('ExportData');
        case 'update':
            return Translator::translate('Update');
        }
        return Translator::translate('System');
    case 'import_statement':
        return Translator::translate('ImportAccountStatement');
    case 'profile':
        return Translator::translate('Profile');
    case 'multiedit':
        return Translator::translate('EditMultiple');
    case 'search':
        return Translator::translate('ExtSearch');
    case 'results':
        return Translator::translate('SearchResults');
    case 'edit_searches':
        return Translator::translate('SavedSearches');
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
    case 'P':
        $value *= 1024;
    case 'T':
        $value *= 1024;
    case 'G':
        $value *= 1024;
    case 'M':
        $value *= 1024;
    case 'K':
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
        'SizeB',
        'SizeKB',
        'SizeMB',
        'SizeGB',
        'SizeTB',
        'SizePB'
    ];

    $idx = 0;
    while ($idx < count($suffixes) - 1 && $value / 1024 > 0.9) {
        $value /= 1024;
        ++$idx;
    }
    return miscRound2Decim($value, 0) . ' ' . Translator::translate($suffixes[$idx]);
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
 * Get an invoice printer
 *
 * @param string $printTemplateFile Print template
 *
 * @return object
 */
function getInvoicePrinter($printTemplateFile)
{
    $printTemplateFile = trim($printTemplateFile);
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
 * Get mime type from a file name
 *
 * @param string $path     Path to the file
 * @param string $filename The real filename
 *
 * @return string
 */
function getMimeType($path, $filename)
{
    if (is_callable('mime_content_type')) {
        return mime_content_type($path);
    }

    // If mime_content_type is not callable, handle only the types we really care of
    $extension = pathinfo($filename, PATHINFO_EXTENSION);
    switch (strtolower($extension)) {
    case 'jpg':
    case 'jpeg':
        return 'image/jpeg';
    case 'png':
        return 'image/png';
    case 'pdf':
        return 'application/pdf';
    default:
        return $extension;
    }
}

/**
 * Get list type based on current function
 *
 * @param string $func Function
 *
 * @return string
 */
function getListFromFunc($func)
{
    // Func is typically plural, but list singular. Adjust as necessary.
    $list = $func;
    if (!in_array($list, ['delivery_terms', 'settings'])
        && substr($list, -1) === 's'
    ) {
        $list = substr($list, 0, -1);
    }

    return $list;
}

/**
 * Get invoice interval options
 *
 * N.B. Update copy_invoice accordingly too!
 *
 * @return array
 */
function getIntervalOptions(): array
{
    $intervalOptions = [
        '0' => Translator::translate('InvoiceIntervalNone'),
        '2' => Translator::translate('InvoiceIntervalMonth'),
        '3' => Translator::translate('InvoiceIntervalYear')
    ];
    for ($i = 2; $i <= 6; $i++) {
        $intervalOptions[(string)($i + 2)] = str_replace('%d', $i, Translator::translate('InvoiceIntervalMonths'));
    }
    // We don't currently have 7-11 months, but leave room for them in keys 9 - 13 just in case!
    for ($i = 2; $i <= 3; $i++) {
        $intervalOptions[(string)($i + 12)] = str_replace('%d', $i, Translator::translate('InvoiceIntervalYears'));
    }
    return $intervalOptions;
}

/**
 * Advance the next interval date of a recurring invoice or template
 *
 * @param array $invoiceData Invoice data
 *
 * @return void
 */
function advanceInvoiceIntervalDate(array &$invoiceData): void
{
    switch ($invoiceData['interval_type']) {
    // Month
    case 2:
        $invoiceData['next_interval_date'] = date(
            'Ymd', mktime(0, 0, 0, date('m') + 1, date('d'), date('Y'))
        );
        break;
    // Year
    case 3:
        $invoiceData['next_interval_date'] = date(
            'Ymd', mktime(0, 0, 0, date('m'), date('d'), date('Y') + 1)
        );
        break;
    // 2 to 6 months
    case 4:
    case 5:
    case 6:
    case 7:
    case 8:
        $invoiceData['next_interval_date'] = date(
            'Ymd',
            mktime(
                0, 0, 0, date('m') + $invoiceData['interval_type'] - 2,
                date('d'), date('Y')
            )
        );
        break;
    // 2 years
    case 14:
        $invoiceData['next_interval_date'] = date(
            'Ymd', mktime(0, 0, 0, date('m'), date('d'), date('Y') + 2)
        );
        break;
    // 3 years
    case 15:
        $invoiceData['next_interval_date'] = date(
            'Ymd', mktime(0, 0, 0, date('m'), date('d'), date('Y') + 3)
        );
        break;
    }
}
