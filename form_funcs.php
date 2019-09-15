<?php
/**
 * Form handling
 *
 * PHP version 5
 *
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
require_once 'sqlfuncs.php';
require_once 'datefuncs.php';
require_once 'miscfuncs.php';
require_once 'crypt.php';

/**
 *  Get default values for a form
 *
 * @param array $formElements Form elements
 * @param int   $parentKey    Parent key value, if any
 *
 * @return array
 */
function getFormDefaultValues($formElements, $parentKey = false)
{
    $values = [];

    foreach ($formElements as $elem) {
        $values[$elem['name']] = getFormDefaultValue($elem, $parentKey);
    }
    return $values;
}

/**
 * Get the default value for the given form element
 *
 * @param array  $elem      Form element
 * @param string $parentKey Parent record id
 *
 * @return mixed Default value
 */
function getFormDefaultValue($elem, $parentKey)
{
    if (!isset($elem['default'])) {
        if (!empty($elem['default_query'])) {
            $intRes = dbQueryCheck($elem['default_query']);
            return dbFetchValue($intRes);
        }
        return null;
    }
    if ($elem['default'] === 'DATE_NOW') {
        return date(Translator::translate('DateFormat'));
    } elseif (strstr($elem['default'], 'DATE_NOW+')) {
        $atmpValues = explode('+', $elem['default']);
        return date(
            Translator::translate('DateFormat'),
            mktime(0, 0, 0, date('m'), date('d') + $atmpValues[1], date('Y'))
        );
    } elseif (strncmp($elem['default'], 'ADD+', 4) === 0) {
        $strQuery = str_replace('_PARENTID_', $parentKey, $elem['listquery']);
        $res = dbQueryCheck($strQuery);
        $intAdd = dbFetchValue($res);
        if (isset($intAdd)) {
            return $intAdd;
        }
        $intAdd = substr($elem['default'], 4);
        if (ctype_digit($intAdd)) {
            return $intAdd;
        }
    } elseif ($elem['default'] === 'POST') {
        // POST has special treatment in iform
        return '';
    }
    $result = $elem['default'];
    if ($elem['type'] == 'INT') {
        $decimals = isset($elem['decimals']) ? $elem['decimals'] : 2;
        $result = miscRound2Decim($result, $decimals);
    }
    return $result;
}

/**
 * Save form data.
 *
 * If primaryKey is not set, add a new record and set it, otherwise update existing
 * record.
 * Return true on success. Return false on conflict or a string of missing values if
 * encountered. In these cases, the record is not saved.
 *
 * @param string $table         Table name
 * @param int    $primaryKey    Primary key value
 * @param array  $formElements  Form elements
 * @param array  $values        Values
 * @param array  $warnings      Any warnings encountered
 * @param string $parentKeyName Parent key field name, if any
 * @param int    $parentKey     Parent key value, if any
 * @param bool   $onPrint       Whether the save is happening on print
 * @param bool   $partial       Whether values contain only updated fields
 *
 * @return mixed
 */
function saveFormData($table, &$primaryKey, $formElements, &$values, &$warnings,
    $parentKeyName = '', $parentKey = false, $onPrint = false, $partial = false
) {
    global $dblink;

    $missingValues = '';
    $fields = [];
    $insert = [];
    $updateFields = [];
    $arrValues = [];

    if (!isset($primaryKey) || !$primaryKey) {
        if ($partial) {
            $warnings = 'Unable to do partial update without ID';
            return false;
        }
        unset($values['id']);
    }

    if ($partial) {
        $res = fetchRecord($table, $primaryKey, $formElements, $origValues);
        if ('notfound' === $res) {
            $warnings = "Row $primaryKey not found";
            return false;
        }
        foreach ($origValues as $key => $value) {
            if (!isset($values[$key])) {
                $values[$key] = $origValues[$key];
            }
        }
        unset($values['id']);
    }

    foreach ($formElements as $elem) {
        $type = $elem['type'];

        if (true
            && in_array(
                $type,
                [
                    '',
                    'IFORM',
                    'RESULT',
                    'BUTTON',
                    'JSBUTTON',
                    'DROPDOWNMENU',
                    'IMAGE',
                    'ROWSUM',
                    'NEWLINE',
                    'LABEL',
                    'TAGS'
                ]
            )
            || (isset($elem['read_only']) && $elem['read_only'])
        ) {
            continue;
        }

        $name = $elem['name'];
        if ($type !== 'FILE') {
            if (!$elem['allow_null']
                && (!isset($values[$name]) || $values[$name] === '')
            ) {
                if (!empty($elem['default'])) {
                    $values[$name] = getFormDefaultValue($elem, $parentKey);
                } else {
                    if ($missingValues) {
                        $missingValues .= ', ';
                    }
                    $missingValues .= Translator::translate($elem['label']);
                    continue;
                }
            }
        } else {
            if (!$elem['allow_null'] && !$primaryKey && !isset($_FILES[$name])) {
                if ($missingValues) {
                    $missingValues .= ', ';
                }
                $missingValues .= Translator::translate($elem['label']);
                continue;
            }
        }

        if ('FILE' !== $type) {
            if (isset($values[$name])) {
                if (empty($primaryKey) && '' === $values[$name]) {
                    $value = getFormDefaultValue($elem, $parentKey);
                } else {
                    $value = $values[$name];
                }
            } else {
                if (isset($primaryKey) && $primaryKey != 0) {
                    continue;
                }
                $value = getFormDefaultValue($elem, $parentKey);
            }
        }

        if (($type == 'PASSWD' || $type == 'PASSWD_STORED') && !$value) {
            continue; // Don't save empty password
        }

        if (isset($elem['unique']) && $elem['unique']) {
            $query = "SELECT * FROM $table WHERE deleted=0 AND $name=?";
            $params = [
                $value
            ];
            if (isset($primaryKey) && $primaryKey) {
                $query .= ' AND id!=?';
                $params[] = $primaryKey;
            }
            $checkRows = dbParamQuery($query, $params);
            if ($checkRows) {
                $warnings = sprintf(
                    Translator::translate('DuplicateValue'),
                    Translator::translate($elem['label'])
                );
                return false;
            }
        }

        switch ($type) {
        case 'PASSWD':
            $arrValues[] = password_hash($values[$name], PASSWORD_DEFAULT);
            break;
        case 'PASSWD_STORED':
            $crypt = new Crypt();
            $arrValues[] = $crypt->encrypt($values[$name]);
            break;
        case 'INT':
        case 'HID_INT':
        case 'SECHID_INT':
            $arrValues[] = ($value !== '' && $value !== false && $value !== null)
                ? str_replace(',', '.', $value)
                : ($elem['allow_null'] ? null : 0);
            break;
        case 'LIST':
        case 'SEARCHLIST':
            $arrValues[] = isset($values[$name])
                ? ($value !== '' && $value !== null ? str_replace(',', '.', $value) : null)
                : null;
            break;
        case 'CHECK':
            $arrValues[] = $value && 'false' !== $value ? 1 : 0;
            break;
        case 'INTDATE':
            if ($value) {
                $converted = dateConvDate2DBDate($value);
                if (null === $converted) {
                    $warnings = Translator::translate('ErrInvalidValue') . ': '
                        . Translator::translate($elem['label']);
                    return false;
                }
                $arrValues[] = $converted;
            } else {
                $arrValues[] = null;
            }
            break;
        case 'FILE':
            if (!isset($_FILES[$name])) {
                continue 2;
            }
            if ($_FILES[$name]['error'] != UPLOAD_ERR_OK) {
                $warnings = Translator::translate('ErrFileUploadFailed');
                return false;
            }

            $mimetype = getMimeType(
                $_FILES[$name]['tmp_name'], $_FILES[$name]['name']
            );
            if (!empty($elem['mimetypes'])
                && !in_array($mimetype, $elem['mimetypes'])
            ) {
                $warnings = Translator::translate(
                    'FileTypeInvalid', ['%%mimetype%%' => $mimetype]
                );
                return false;
            }

            $file = fopen($_FILES[$name]['tmp_name'], 'rb');
            if ($file === false) {
                $warnings = 'Could not process file upload - temp file missing';
                return false;
            }
            $fsize = filesize($_FILES[$name]['tmp_name']);

            // Additional fields for file information
            $fields[] = 'filename';
            $insert[] = '?';
            $updateFields[] = 'filename=?';
            $arrValues[] = $_FILES[$name]['name'];

            $fields[] = 'filesize';
            $insert[] = '?';
            $updateFields[] = 'filesize=?';
            $arrValues[] = $fsize;

            $fields[] = 'mimetype';
            $insert[] = '?';
            $updateFields[] = 'mimetype=?';
            $arrValues[] = $mimetype;

            $arrValues[] = fread($file, $fsize);
            fclose($file);
            break;
        default :
            $arrValues[] = null !== $value ? $value : '';
        }
        $fields[] = $name;
        $insert[] = '?';
        $updateFields[] = "$name=?";
    }

    if ($missingValues) {
        return $missingValues;
    }

    if ($fields) {
        $strFields = implode(', ', $fields);
        $strInsert = implode(', ', $insert);
        $strUpdateFields = implode(', ', $updateFields);

        dbQueryCheck('SET AUTOCOMMIT = 0');
        dbQueryCheck('BEGIN');
        try {
            // Special case for invoice rows - update product stock balance
            if (isset($values['invoice_id'])) {
                $invoiceId = $values['invoice_id'];
            } elseif ($table == '{prefix}invoice_row') {
                $rows = dbParamQuery(
                    'SELECT invoice_id FROM {prefix}invoice_row WHERE id=?',
                    [$primaryKey]
                );
                $invoiceId = isset($rows[0]['invoice_id'])
                    ? $rows[0]['invoice_id'] : null;
            }
            if ($table == '{prefix}invoice_row' && !isOffer($invoiceId)) {
                updateProductStockBalance(
                    isset($primaryKey) ? $primaryKey : null,
                    isset($values['product_id']) ? $values['product_id'] : null,
                    $values['pcs']
                );
            }

            if (!isset($primaryKey) || !$primaryKey) {
                if ($parentKeyName) {
                    $strFields .= ", $parentKeyName";
                    $strInsert .= ', ?';
                    $arrValues[] = $parentKey;
                }
                $strQuery = "INSERT INTO $table ($strFields) VALUES ($strInsert)";
                dbParamQuery($strQuery, $arrValues, 'exception');
                $primaryKey = mysqli_insert_id($dblink);
            } else {
                // Special case for invoice - update product stock balance for all
                // invoice rows if the invoice was previously deleted
                if ($table == '{prefix}invoice' && !isOffer($primaryKey)) {
                    $checkValues = dbParamQuery(
                        'SELECT deleted FROM {prefix}invoice WHERE id=?',
                        [$primaryKey]
                    );
                    if ($checkValues[0]['deleted']) {
                        $rows = dbParamQuery(
                            'SELECT product_id, pcs FROM {prefix}invoice_row WHERE invoice_id=? AND deleted=0',
                            [$primaryKey]
                        );
                        foreach ($rows as $row) {
                            updateProductStockBalance(
                                null, $row['product_id'], $row['pcs']
                            );
                        }
                    }
                }

                if ('{prefix}send_api_config' === $table
                    || '{prefix}attachment' === $table
                    || '{prefix}invoice_attachment' === $table
                ) {
                    $strQuery = "UPDATE $table SET $strUpdateFields WHERE id=?";
                } else {
                    $strQuery = "UPDATE $table SET $strUpdateFields, deleted=0 WHERE id=?";
                }
                $arrValues[] = $primaryKey;
                dbParamQuery($strQuery, $arrValues, 'exception');
            }
            if ($table === '{prefix}company') {
                saveTags(
                    'company',
                    $primaryKey,
                    !empty($values['tags']) ? $values['tags'] : ''
                );
            } elseif ($table === '{prefix}company_contact') {
                saveTags(
                    'contact',
                    $primaryKey,
                    !empty($values['tags']) ? $values['tags'] : ''
                );
            }
        } catch (Exception $e) {
            dbQueryCheck('ROLLBACK');
            dbQueryCheck('SET AUTOCOMMIT = 1');
            die($e->getMessage());
        }
        dbQueryCheck('COMMIT');
        dbQueryCheck('SET AUTOCOMMIT = 1');
    }

    // Special case for invoices - check for duplicate invoice numbers
    if ($table == '{prefix}invoice' && isset($values['invoice_no'])
        && !isOffer($primaryKey)
    ) {
        $query = 'SELECT ID FROM {prefix}invoice where deleted=0 AND id!=? AND invoice_no=?';
        $params = [
            $primaryKey,
            $values['invoice_no']
        ];
        if (getSetting('invoice_numbering_per_base')) {
            $query .= ' AND base_id=?';
            $params[] = $values['base_id'];
        }
        if (getSetting('invoice_numbering_per_year')) {
            $query .= ' AND invoice_date >= ' . date('Y') . '0101';
        }

        $check = dbParamQuery($query, $params);
        if ($check) {
            $warnings = Translator::translate('InvoiceNumberAlreadyInUse');
        }
    }

    // Special case for invoices - check, according to settings, that the invoice has
    // an invoice number and a reference number
    if ($table == '{prefix}invoice' && $onPrint && !isOffer($primaryKey)) {
        verifyInvoiceDataForPrinting($primaryKey);
    }

    // Special case for invoices: store base_id to session as a default invoicer
    if ('{prefix}invoice' === $table && !empty($values['base_id'])) {
        $_SESSION['default_base_id'] = $values['base_id'];
    }

    return true;
}

/**
 * Fetch a record. Values in $values, may modify $formElements.
 *
 * Returns true on success, 'deleted' for deleted records and 'notfound' if record is
 * not found.
 *
 * @param string $table        Table name
 * @param int    $primaryKey   Record ID
 * @param int    $formElements Form elements
 * @param array  $values       Record data
 *
 * @return mixed
 */
function fetchRecord($table, $primaryKey, $formElements, &$values)
{
    $result = true;
    $strQuery = "SELECT * FROM $table WHERE id=?";
    $rows = dbParamQuery($strQuery, [$primaryKey]);
    if (!$rows) {
        return 'notfound';
    }
    $row = $rows[0];

    if (!empty($row['deleted'])) {
        $result = 'deleted';
    }

    foreach ($formElements as $elem) {
        $type = $elem['type'];
        $name = $elem['name'];

        if (!$type || $type == 'LABEL' || $type == 'FILLER'
            || $type == 'DROPDOWNMENU'
        ) {
            continue;
        }

        switch ($type) {
        case 'ROWSUM':
            break;
        case 'IFORM':
        case 'RESULT':
            $values[$name] = $primaryKey;
            break;
        case 'BUTTON':
        case 'JSBUTTON':
        case 'IMAGE':
        case 'FILE':
            if (strstr($elem['listquery'], '=_ID_')) {
                $values[$name] = $primaryKey;
            } else {
                $tmpListQuery = $elem['listquery'];
                $strReplName = substr($tmpListQuery, strpos($tmpListQuery, '_'));
                $strReplName = strtolower(
                    substr($strReplName, 1, strrpos($strReplName, '_') - 1)
                );
                $values[$name] = isset($values[$strReplName]) ? $values[$strReplName] : '';
                $elem['listquery'] = str_replace(
                    strtoupper($strReplName), 'ID', $elem['listquery']
                );
            }
            break;
        case 'INTDATE':
            $values[$name] = dateConvDBDate2Date($row[$name]);
            break;
        case 'INT':
            if (isset($elem['decimals'])) {
                $values[$name] = miscRound2Decim($row[$name], $elem['decimals']);
            } else {
                $values[$name] = $row[$name];
            }
            break;
        case 'TAGS':
            $values[$name] = '';
            if ('{prefix}company' === $table) {
                $values[$name] = getTags('company', $primaryKey);
            } elseif ('{prefix}company_contact' === $table) {
                $values[$name] = getTags('contact', $primaryKey);
            }
            break;
        default:
            $values[$name] = $row[$name];
        }
    }
    return $result;
}
