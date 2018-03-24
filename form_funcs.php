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

/**
 *  Get post values or defaults for unspecified values
 *
 * @param array $formElements Form elements
 * @param int   $primaryKey   Primary key value
 * @param int   $parentKey    Parent key value, if any
 *
 * @return array
 */
function getPostValues(&$formElements, $primaryKey, $parentKey = false)
{
    $values = [];

    foreach ($formElements as $elem) {
        if (true
            && in_array(
                $elem['type'],
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
                    'LABEL'
                ]
            )
        ) {
            $values[$elem['name']] = isset($primaryKey) ? $primaryKey : false;
        } else {
            $values[$elem['name']] = getPostRequest($elem['name'], false);
            if (isset($elem['default']) && ($values[$elem['name']] === false
                || ($elem['type'] == 'INT' && $values[$elem['name']] === ''))
            ) {
                $values[$elem['name']] = getFormDefaultValue($elem, $parentKey);
                if (null === $values[$elem['name']]) {
                    $values[$elem['name']] = '';
                }
            } elseif ($elem['type'] == 'INT') {
                $values[$elem['name']]
                    = str_replace(',', '.', $values[$elem['name']]);
            } elseif ($elem['type'] == 'LIST' && $values[$elem['name']] === false) {
                $values[$elem['name']] = '';
            }
        }
    }
    return $values;
}

/**
 * Get the default value for the given form element
 *
 * @param string $elem      Element id
 * @param string $parentKey Parent record id
 *
 * @return mixed Default value
 */
function getFormDefaultValue($elem, $parentKey)
{
    if (!isset($elem['default'])) {
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
    } elseif (strstr($elem['default'], 'ADD')) {
        $strQuery = str_replace('_PARENTID_', $parentKey, $elem['listquery']);
        $res = dbQueryCheck($strQuery);
        $intAdd = dbFetchValue($res);
        return isset($intAdd) ? $intAdd : 5;
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
function saveFormData($table, &$primaryKey, &$formElements, &$values, &$warnings,
    $parentKeyName = '', $parentKey = false, $onPrint = false, $partial = false
) {
    global $dblink;

    $missingValues = '';
    $strFields = '';
    $strInsert = '';
    $strUpdateFields = '';
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
        if (!$elem['allow_null'] && (!isset($values[$name]) || $values[$name] === '')) {
            if (!empty($elem['default'])) {
                $values[$name] = $elem['default'];
            } else {
                if ($missingValues) {
                    $missingValues .= ', ';
                }
                $missingValues .= Translator::translate($elem['label']);
                continue;
            }
        }

        if (isset($values[$name])) {
            $value = $values[$name];
        } else {
            if (isset($primaryKey) && $primaryKey != 0) {
                continue;
            }
            $value = getFormDefaultValue($elem, $parentKey);
        }

        if ($type == 'PASSWD' && !$value) {
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

        if ($strFields) {
            $strFields .= ', ';
            $strInsert .= ', ';
            $strUpdateFields .= ', ';
        }
        $strFields .= $name;
        $fieldPlaceholder = '?';
        switch ($type) {
        case 'PASSWD':
            $arrValues[] = password_hash($values[$name], PASSWORD_DEFAULT);
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
                ? ($value !== '' ? str_replace(',', '.', $value) : null)
                : null;
            break;
        case 'CHECK':
            $arrValues[] = $value ? 1 : 0;
            break;
        case 'INTDATE':
            $arrValues[] = $value ? dateConvDate2DBDate($value) : null;
            break;
        default :
            $arrValues[] = null !== $value ? $value : '';
        }
        $strInsert .= $fieldPlaceholder;
        $strUpdateFields .= "$name=$fieldPlaceholder";
    }

    if ($missingValues) {
        return $missingValues;
    }

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

            $strQuery = "UPDATE $table SET $strUpdateFields, deleted=0 WHERE id=?";
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
    if ($table == '{prefix}invoice' && $onPrint && !isOffer($primaryKey)
        && (getSetting('invoice_add_number')
        || getSetting('invoice_add_reference_number'))
    ) {
        dbQueryCheck(
            'LOCK TABLES {prefix}invoice WRITE, {prefix}settings READ'
            . ', {prefix}company READ'
        );
        $rows = dbParamQuery(
            'SELECT invoice_no, ref_number, base_id, company_id, invoice_date,'
            . "interval_type FROM $table WHERE id=?",
            [$primaryKey]
        );
        $data = isset($rows[0]) ? $rows[0] : null;
        $needInvNo = getSetting('invoice_add_number');
        $needRefNo = getSetting('invoice_add_reference_number');
        if (($needInvNo && empty($data['invoice_no']))
            || ($needRefNo && empty($data['ref_number']))
        ) {
            $defaults = getInvoiceDefaults(
                $primaryKey, $data['base_id'], $data['company_id'],
                dateConvDBDate2Date($data['invoice_date']), $data['interval_type'],
                $data['invoice_no']
            );
            $sql = "UPDATE {prefix}invoice SET";
            $updateStrings = [];
            $params = [];
            if ($needInvNo && empty($data['invoice_no'])) {
                $updateStrings[] = 'invoice_no=?';
                $params[] = $defaults['invoice_no'];
            }
            if ($needRefNo && empty($data['ref_number'])) {
                $updateStrings[] = 'ref_number=?';
                $params[] = $defaults['ref_no'];
            }
            $sql .= ' ' . implode(', ', $updateStrings) . ' WHERE id=?';
            $params[] = $primaryKey;
            dbParamQuery($sql, $params);
        }
        dbQueryCheck('UNLOCK TABLES');
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
function fetchRecord($table, $primaryKey, &$formElements, &$values)
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

/**
 * Get form elements
 *
 * @param string $form Form name
 *
 * @return array
 */
function getFormElements($form)
{
    $strForm = $form;
    include 'form_switch.php';
    return $astrFormElements;
}

/**
 * Get form parent key field
 *
 * @param string $form Form name
 *
 * @return string
 */
function getFormParentKey($form)
{
    $strForm = $form;
    include 'form_switch.php';
    return $strParentKey;
}

/**
 * Get form JSON type
 *
 * @param string $form Form name
 *
 * @return string
 */
function getFormJSONType($form)
{
    $strForm = $form;
    include 'form_switch.php';
    return $strJSONType;
}

/**
 * Get form "clear row values after add"
 *
 * @param string $form Form name
 *
 * @return bool
 */
function getFormClearRowValuesAfterAdd($form)
{
    $strForm = $form;
    include 'form_switch.php';
    return $clearRowValuesAfterAdd;
}

/**
 * Get form "on row added event"
 *
 * @param string $form Form name
 *
 * @return string
 */
function getFormOnAfterRowAdded($form)
{
    $strForm = $form;
    include 'form_switch.php';
    return $onAfterRowAdded;
}
