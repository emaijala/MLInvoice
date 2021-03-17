<?php
/**
 * JSON API
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
$phpErrors = [];
set_error_handler('handleError');

require_once 'vendor/autoload.php';
require_once 'config.php';

if (defined('_PROFILING_') && is_callable('xhprof_enable')) {
    xhprof_enable();
}

require_once 'sqlfuncs.php';
require_once 'miscfuncs.php';
require_once 'sessionfuncs.php';
require_once 'form_funcs.php';
require_once 'translator.php';
require_once 'settings.php';
require_once 'memory.php';
require_once 'form_config.php';
require_once 'list_config.php';

sesVerifySession(false);

$strFunc = getPostOrQuery('func', '');

switch ($strFunc) {
case 'get_company':
case 'get_company_contact':
case 'get_product':
case 'get_invoice':
case 'get_invoice_row':
case 'get_base':
case 'get_print_template':
case 'get_invoice_state':
case 'get_invoice_type':
case 'get_row_type':
case 'get_print_template':
case 'get_company':
case 'get_session_type':
case 'get_delivery_terms':
case 'get_delivery_method':
case 'get_default_value':
case 'get_attachment':
case 'get_send_api_config':
    printJSONRecord(substr($strFunc, 4));
    break;
case 'get_user':
    printJSONRecord('users');
    break;

case 'put_company':
case 'put_product':
case 'put_invoice':
case 'put_base':
case 'put_print_template':
case 'put_invoice_state':
case 'put_invoice_type':
case 'put_row_type':
case 'put_print_template':
case 'put_user':
case 'put_session_type':
case 'put_delivery_terms':
case 'put_delivery_method':
case 'put_default_value':
case 'put_attachment':
case 'put_invoice_attachment':
    saveJSONRecord(substr($strFunc, 4), '');
    break;

case 'delete_invoice_row':
case 'delete_default_value':
case 'delete_send_api_config':
case 'delete_attachment':
case 'delete_invoice_attachment':
    deleteJSONRecord(substr($strFunc, 7));
    break;

case 'put_send_api_config':
    saveJSONRecord(substr($strFunc, 4), 'base_id');
    break;
case 'get_send_api_configs':
    printJSONRecords('send_api_config', 'base_id', 'name');
    break;

case 'session_type':
case 'user':
    if (!sesAdminAccess()) {
        header('HTTP/1.1 403 Forbidden');
        break;
    }
    saveJSONRecord(substr($strFunc, 4), '');
    break;

case 'get_companies':
    printJSONRecords('company', '', 'company_name');
    break;

case 'get_company_contacts':
    printJSONRecords('company_contact', 'company_id', 'contact_person');
    break;

case 'delete_company_contact' :
    deleteJSONRecord('company_contact');
    break;

case 'put_company_contact' :
    saveJSONRecord('company_contact', 'company_id');
    break;

case 'get_products' :
    printJSONRecords('product', '', 'product_name');
    break;

case 'get_row_types' :
    printJSONRecords('row_type', '', 'order_no');
    break;

case 'get_invoice_rows' :
    printJSONRecords('invoice_row', 'invoice_id', 'order_no');
    break;

case 'put_invoice_row' :
    saveJSONRecord('invoice_row', 'invoice_id');
    break;

case 'get_custom_prices':
    $customPrice = getCustomPriceSettings(
        getPostOrQuery('companyId')
    );
    header('Content-Type: application/json');
    echo createResponse($customPrice);
    break;

case 'put_custom_prices':
    if (!sesWriteAccess()) {
        header('HTTP/1.1 403 Forbidden');
        return;
    }
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) {
        header('HTTP/1.1 400 Bad Request');
        return;
    }
    setCustomPriceSettings(
        $data['company_id'],
        $data['discount'],
        $data['multiplier'],
        $data['valid_until']
    );
    header('Content-Type: application/json');
    echo createResponse(['status' => 'ok']);
    break;

case 'delete_custom_prices':
    if (!sesWriteAccess()) {
        header('HTTP/1.1 403 Forbidden');
        return;
    }
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) {
        header('HTTP/1.1 400 Bad Request');
        return;
    }
    deleteCustomPriceSettings($data['company_id']);
    header('Content-Type: application/json');
    echo createResponse(['status' => 'ok']);
    break;

case 'get_custom_price':
    $customPrice = getCustomPrice(
        getPostOrQuery('company_id'),
        getPostOrQuery('product_id')
    );
    header('Content-Type: application/json');
    echo createResponse($customPrice);
    break;

case 'put_custom_price':
    if (!sesWriteAccess()) {
        header('HTTP/1.1 403 Forbidden');
        return;
    }
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) {
        header('HTTP/1.1 400 Bad Request');
        return;
    }
    $unitPrice = (float)$data['unit_price'];
    setCustomPrice(
        $data['company_id'],
        $data['product_id'],
        $unitPrice
    );
    header('Content-Type: application/json');
    echo createResponse(
        [
            'status' => 'ok',
            'unit_price' => $unitPrice
        ]
    );
    break;

case 'delete_custom_price':
    if (!sesWriteAccess()) {
        header('HTTP/1.1 403 Forbidden');
        return;
    }
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) {
        header('HTTP/1.1 400 Bad Request');
        return;
    }
    deleteCustomPrice($data['company_id'], $data['product_id']);
    $product = getProduct($data['product_id']);
    $unitPrice = $product['unit_price'];
    if ($unitPrice) {
        $customPrice = getCustomPriceSettings($data['company_id']);
        if ($customPrice && $customPrice['valid']) {
            $unitPrice -= $unitPrice * $customPrice['discount'] / 100;
            $unitPrice *= $customPrice['multiplier'];
        }
    }
    header('Content-Type: application/json');
    echo createResponse(
        [
            'status' => 'ok',
            'unit_price' => $unitPrice
        ]
    );
    break;

case 'add_reminder_fees' :
    include 'add_reminder_fees.php';
    $invoiceId = getPostOrQuery('id', 0);
    $errors = addReminderFees($invoiceId);
    if ($errors) {
        $ret = ['status' => 'error', 'errors' => $errors];
    } else {
        $ret = ['status' => 'ok'];
    }
    header('Content-Type: application/json');
    echo createResponse($ret);
    break;

case 'get_invoice_defaults' :
    $baseId = getPostOrQuery('base_id', 0);
    $companyId = getPostOrQuery('company_id', 0);
    $invoiceId = getPostOrQuery('id', 0);
    $invoiceDate = getPostOrQuery(
        'invoice_date', dateConvDBDate2Date(date('Y') . '0101')
    );
    $intervalType = getPostOrQuery('interval_type', 0);
    $invoiceNumber = getPostOrQuery('invoice_no', 0);

    $defaults = getInvoiceDefaults(
        $invoiceId, $baseId, $companyId, $invoiceDate, $intervalType, $invoiceNumber
    );

    header('Content-Type: application/json');
    echo createResponse($defaults);
    break;

case 'get_table_columns' :
    $table = getPostOrQuery('table', '');
    if (!$table) {
        header('HTTP/1.1 400 Bad Request');
        break;
    }
    if (!sesAdminAccess() && 'account_statement' !== $table) {
        header('HTTP/1.1 403 Forbidden');
        break;
    }
    // account_statement is a pseudo table for account statement "import"
    if ($table == 'account_statement') {
        header('Content-Type: application/json');
        echo '{"columns":';
        echo createResponse(
            [
                [
                    'id' => 'date',
                    'name' => Translator::translate('ImportStatementPaymentDate')
                ],
                [
                    'id' => 'amount',
                    'name' => Translator::translate('ImportStatementAmount')
                ],
                [
                    'id' => 'refnr',
                    'name' => Translator::translate('ImportStatementRefNr')
                ],
                [
                    'id' => 'correction',
                    'name' => Translator::translate('ImportStatementCorrectionRow')
                ]
            ]
        );
        echo "\n}";
        break;
    }

    if (!tableNameValid($table)) {
        header('HTTP/1.1 400 Bad Request');
        die('Invalid table name');
    }

    header('Content-Type: application/json');
    echo '{"columns":[';
    $res = dbQueryCheck("select * from {prefix}$table where 1=2");
    $field_count = mysqli_num_fields($res);
    for ($i = 0; $i < $field_count; $i ++) {
        $field_def = mysqli_fetch_field($res);
        if ($i == 0) {
            echo "\n";
        } else {
            echo ",\n";
        }
        echo createResponse(['name' => $field_def->name]);
    }
    if ('company' === $table || 'company_contact' === $table) {
        echo ",\n";
        echo createResponse(['name' => 'tags']);
    } elseif ('custom_price_map' === $table) {
        echo ",\n";
        echo createResponse(['name' => 'company_id']);
    }
    echo "\n]}";
    break;

case 'get_import_preview' :
    $table = getPostOrQuery('table', '');
    if ($table == 'account_statement') {
        include 'import_statement.php';
        $import = new ImportStatement();
    } else {
        if (!sesAdminAccess()) {
            header('HTTP/1.1 403 Forbidden');
            break;
        }
        include 'import.php';
        $import = new ImportFile();
    }
    $import->createImportPreview();
    break;

case 'get_list' :
    include 'list.php';

    $listFunc = getPostOrQuery('listfunc', '');

    $strList = getPostOrQuery('table', '');
    if (!$strList) {
        header('HTTP/1.1 400 Bad Request');
        die('Table must be defined');
    }

    $tableId = getPostOrQuery('tableid', '');

    $listConfig = getListConfig($strList);
    if (!$listConfig) {
        header('HTTP/1.1 400 Bad Request');
        die('Invalid table name');
    }

    $startRow = intval(getPostOrQuery('start', -1));
    $rowCount = intval(getPostOrQuery('length', -1));
    $sort = [];
    $columns = getPostOrQuery('columns', []);
    if ($orderCols = getPostOrQuery('order', [])) {
        foreach ($orderCols as $orderCol) {
            if (!isset($orderCol['column'])) {
                continue;
            }
            $sortColumn = $orderCol['column'];
            $sortDir = $orderCol['dir'];
            $sort[] = [
                $sortColumn => $sortDir === 'desc' ? 'desc' : 'asc'
            ];
        }
    }
    $search = getPostOrQuery('search', []);
    $filter = empty($search['value']) ? '' : $search['value'];
    $where = getPostOrQuery('where', '');
    $companyId = 'product' === $strList ? getPostOrQuery('company', null) : null;

    header('Content-Type: application/json');
    echo createJSONList(
        $listFunc, $strList, $startRow, $rowCount, $sort, $filter, $where,
        intval(getPostOrQuery('draw', 1)), $tableId, $companyId
    );
    Memory::set(
        $tableId,
        compact(
            'strList', 'startRow', 'rowCount', 'sort', 'filter', 'where'
        )
    );
    break;

case 'get_invoice_total_sum' :
    $where = getPostOrQuery('where', '');

    header('Content-Type: application/json');
    echo getInvoiceListTotal($where);
    break;

case 'get_selectlist' :
    include 'list.php';

    $table = getPostOrQuery('table', '');
    if (!$table) {
        header('HTTP/1.1 400 Bad Request (table)');
        break;
    }

    if (!tableNameValid($table)) {
        header('HTTP/1.1 400 Bad Request');
        die('Invalid table name');
    }

    $pageLen = intval(getPostOrQuery('pagelen', 10));
    $page = intval(getPostOrQuery('page', 1)) - 1;
    $filter = getPostOrQuery('q', '');
    $sort = getPostOrQuery('sort', '');
    $id = getPostOrQuery('id', '');
    $type = getPostOrQuery('type', '');

    if ($type) {
        $filter = [$filter, $type];
    }

    header('Content-Type: application/json');
    echo createJSONSelectList(
        $table, $page * $pageLen, $pageLen, $filter, $sort, $id
    );
    break;

case 'update_multiple':
    header('Content-Type: application/json');
    echo updateMultipleRows();
    break;

case 'update_row_order':
    header('Content-Type: application/json');
    echo updateRowOrder();
    break;

case 'update_stock_balance' :
    if (!sesWriteAccess()) {
        header('HTTP/1.1 403 Forbidden');
        break;
    }
    $productId = getPostOrQuery('product_id', 0);
    $change = getPostOrQuery('stock_balance_change', 0);
    $desc = getPostOrQuery('stock_balance_change_desc', '');
    header('Content-Type: application/json');
    echo updateStockBalance($productId, $change, $desc);
    break;

case 'get_stock_balance_rows' :
    $productId = getPostOrQuery('product_id', 0);
    if (!$productId) {
        break;
    }
    $rows = dbParamQuery(
        <<<EOT
SELECT l.time, u.name, l.stock_change, l.description FROM {prefix}stock_balance_log l
INNER JOIN {prefix}users u ON l.user_id=u.id WHERE product_id=? ORDER BY time DESC
EOT
        ,
        [$productId]
    );
    $html = '';
    foreach ($rows as $row) {
        ?>
<tr>
    <td><?php echo dateConvDBTimestamp2DateTime($row['time'])?></td>
    <td><?php echo $row['name']?></td>
    <td><?php echo miscRound2Decim($row['stock_change'])?></td>
    <td><?php echo $row['description']?></td>
</tr>
        <?php
    }
    break;

case 'get_send_api_services':
    header('Content-Type: application/json');
    echo getSendApiServices(getPostOrQuery('invoice_id'), getPostOrQuery('base_id'));
    break;

case 'add_invoice_attachment':
    if (!sesWriteAccess()) {
        header('HTTP/1.1 403 Forbidden');
        break;
    }
    addInvoiceAttachment();
    break;

case 'get_invoice_attachments':
    printJSONRecords('invoice_attachment', 'invoice_id', 'order_no');
    break;

case 'noop' :
    // Session keep-alive
    header('HTTP/1.1 204 No Content');
    break;

default :
    header('HTTP/1.1 404 Not Found');
}

if (defined('_PROFILING_') && is_callable('xhprof_disable')) {
    $data = xhprof_disable();
    file_put_contents(
        sys_get_temp_dir() . '/' . uniqid() . '.mlinvoice-json.xhprof',
        serialize($data)
    );
}

/**
 * Output a JSON record
 *
 * @param string $table    Table name
 * @param int    $id       Record ID
 * @param array  $warnings Warnings to include in the output
 *
 * @return void
 */
function printJSONRecord($table, $id = false, $warnings = null)
{
    if ($id === false) {
        $id = getPostOrQuery('id', '');
    }
    if ($id) {
        if (substr($table, 0, 8) != '{prefix}') {
            $table = "{prefix}$table";
        }
        $select = 'SELECT t.*';
        $from = "FROM $table t";
        $where = 'WHERE t.id=?';

        if ($table == '{prefix}invoice_row') {
            // Include product name and code
            $select .= ", CASE WHEN LENGTH(p.product_code) = 0 THEN IFNULL(p.product_name, '') ELSE CONCAT_WS(' ', p.product_code, IFNULL(p.product_name, '')) END as product_id_text";
            $from .= ' LEFT OUTER JOIN {prefix}product p on (p.id = t.product_id)';
        }

        $query = "$select $from $where";
        $rows = dbParamQuery($query, [$id]);
        if (!$rows) {
            header('HTTP/1.1 404 Not Found');
            return;
        }
        $row = $rows[0];
        switch ($table) {
        case '{prefix}users':
            unset($row['password']);
            break;
        case '{prefix}attachment':
        case '{prefix}invoice_attachment':
            unset($row['filedata']);
            $row['filesize_readable'] = fileSizeToHumanReadable($row['filesize']);
            break;
        case '{prefix}base':
            $row['logo_filedata'] = base64_encode($row['logo_filedata']);
            break;
        }

        // Include any custom price for a product
        if ($table == '{prefix}product' && ($companyId = getPostOrQuery('company_id'))) {
            $customPriceSettings = getCustomPriceSettings($companyId);
            if (empty($customPriceSettings['valid'])) {
                $customPriceSettings = null;
            }
            $customPrice = null;
            if ($customPriceSettings) {
                $customPrice = getCustomPrice($companyId, $id);
                if (!$customPrice) {
                    $unitPrice = $row['unit_price'];
                    $unitPrice -= $unitPrice * $customPriceSettings['discount']
                        / 100;
                    $unitPrice *= $customPriceSettings['multiplier'];
                    $customPrice = [
                        'unit_price' => $unitPrice
                    ];
                }
            }
            $row['custom_price'] = $customPrice ? $customPrice : null;
        }

        // Fetch tags
        if ($table == '{prefix}company') {
            $row['tags'] = getTags('company', $id);
        } elseif ($table == '{prefix}company_contact') {
            $row['tags'] = getTags('contact', $id);
        }

        header('Content-Type: application/json');
        $row['warnings'] = $warnings;
        echo createResponse($row);
    }
}

/**
 * Output multiple records
 *
 * @param string $table       Table name
 * @param string $parentIdCol Parent ID column name
 * @param string $sort        Sort rules
 *
 * @return void
 */
function printJSONRecords($table, $parentIdCol, $sort)
{
    $select = 'SELECT t.*';
    $from = "FROM {prefix}$table t";

    if ($table == 'invoice_row') {
        // Include product name, product code, product weight and row type name
        $select .= <<<EOT
, CASE WHEN LENGTH(p.product_code) = 0 THEN IFNULL(p.product_name, '')
  ELSE CONCAT_WS(' ', p.product_code, IFNULL(p.product_name, ''))
  END as product_id_text, p.weight as product_weight
EOT;
        $from .= ' LEFT OUTER JOIN {prefix}product p on (p.id = t.product_id)';
        $select .= ', rt.name as type_id_text';
        $from .= ' LEFT OUTER JOIN {prefix}row_type rt on (rt.id = t.type_id)';
    }

    $where = '';
    $params = [];
    $id = getPostOrQuery('parent_id', '');
    if ($id && $parentIdCol) {
        $where .= " WHERE t.$parentIdCol=?";
        $params[] = $id;
    }
    if (!getSetting('show_deleted_records') && 'send_api_config' !== $table
        && 'attachment' !== $table && 'invoice_attachment' !== $table
    ) {
        if ($where) {
            $where .= ' AND t.deleted=0';
        } else {
            $where = ' WHERE t.deleted=0';
        }
    }

    $query = "$select $from $where";
    if ($sort) {
        $query .= " order by $sort";
    }
    $rows = dbParamQuery($query, $params);
    header('Content-Type: application/json');
    echo '{"records":[';
    $first = true;
    foreach ($rows as $row) {
        if ($first) {
            echo "\n";
            $first = false;
        } else {
            echo ",\n";
        }
        if ($table == 'users') {
            unset($row['password']);
        }
        if ($table == 'invoice_row') {
            $row['type_id_text'] = Translator::translate($row['type_id_text']);
        }
        // Fetch tags
        if ($table == 'company') {
            $row['tags'] = getTags('company', $row['id']);
        } elseif ($table == 'company_contact') {
            $row['tags'] = getTags('contact', $row['id']);
        }
        if ('attachment' === $table || 'invoice_attachment' === $table) {
            unset($row['filedata']);
            $row['filesize_readable'] = fileSizeToHumanReadable($row['filesize']);
        }

        echo createResponse($row);
    }
    echo "\n]}";
}

/**
 * Save a record
 *
 * @param string $table         Table name
 * @param string $parentKeyName Parent ID column name
 *
 * @return void
 */
function saveJSONRecord($table, $parentKeyName)
{
    if (!sesWriteAccess()) {
        header('HTTP/1.1 403 Forbidden');
        return;
    }

    list($contentType) = explode(';', $_SERVER['CONTENT_TYPE']);
    if ($contentType === 'application/json') {
        $data = json_decode(file_get_contents('php://input'), true);
    } else {
        // If we don't have a JSON request, assume we have POST data
        $data = $_POST;
    }
    if (!$data) {
        header('HTTP/1.1 400 Bad Request');
        return;
    }
    $strForm = $table;
    $strFunc = '';
    $strList = '';
    $id = isset($data['id']) ? $data['id'] : false;
    $new = $id ? false : true;
    unset($data['id']);
    $formConfig = getFormConfig($strForm, 'json');

    $onPrint = false;
    if (isset($data['onPrint'])) {
        $onPrint = $data['onPrint'];
        unset($data['onPrint']);
    }

    // Allow partial update for invoice attachments. This is a safety check since the
    // partial update mechanism might hide issues with other record types.
    $partial = !$new && 'invoice_attachment' === $table;

    $warnings = '';
    try {
        $res = saveFormData(
            $formConfig['table'], $id, $formConfig['fields'], $data, $warnings, $parentKeyName,
            $parentKeyName ? $data[$parentKeyName] : false, $onPrint, $partial
        );
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo createResponse(['error' => $e->getMessage()]);
        return;
    }
    if ($res !== true) {
        if ($warnings) {
            header('HTTP/1.1 409 Conflict');
        }
        header('Content-Type: application/json');
        echo createResponse(['missing_fields' => $res, 'warnings' => $warnings]);
        return;
    }

    if ($new) {
        header('HTTP/1.1 201 Created');
    }
    printJSONRecord($formConfig['table'], $id, $warnings);
}

/**
 * Delete a record
 *
 * @param string $table Table name
 *
 * @return void
 */
function deleteJSONRecord($table)
{
    if (!sesWriteAccess()) {
        header('HTTP/1.1 403 Forbidden');
        return;
    }

    $ids = getPostOrQuery('id', '');
    if ($ids) {
        foreach ((array)$ids as $id) {
            deleteRecord("{prefix}$table", $id);
        }
        header('Content-Type: application/json');
        echo createResponse(['status' => 'ok']);
    }
}

/**
 * Update multiple rows
 *
 * @return void
 */
function updateMultipleRows()
{
    if (!sesWriteAccess()) {
        header('HTTP/1.1 403 Forbidden');
        return;
    }

    $request = json_decode(file_get_contents('php://input'), true);
    if (!$request) {
        header('HTTP/1.1 400 Bad Request');
        return;
    }

    $strForm = $request['table'];
    $strFunc = '';
    $strList = '';
    $formConfig = getFormConfig($strForm, 'json');

    $warnings = '';
    foreach ($request['ids'] as $id) {
        $id = (int)$id;
        // Set fields anew for every row since saveFormData returns the whole record
        $data = $request['changes'];
        $res = saveFormData(
            '{prefix}' . $request['table'], $id, $formConfig['fields'], $data, $warnings,
            false, false, false, true
        );
        if ($res !== true) {
            if ($warnings) {
                header('HTTP/1.1 409 Conflict');
            }
            header('Content-Type: application/json');
            return createResponse(['missing_fields' => $res, 'warnings' => $warnings]);
        }
    }

    return createResponse(['status' => 'ok']);
}

/**
 * Update row order based on POST data
 *
 * @return void
 */
function updateRowOrder()
{
    if (!sesWriteAccess()) {
        header('HTTP/1.1 403 Forbidden');
        return;
    }

    $request = json_decode(file_get_contents('php://input'), true);
    if (!$request) {
        header('HTTP/1.1 400 Bad Request');
        return;
    }

    foreach ($request['order'] as $id => $orderNo) {
        dbParamQuery(
            "UPDATE {prefix}{$request['table']} SET order_no=? WHERE id=?",
            [$orderNo, $id]
        );
    }

    return createResponse(['status' => 'ok']);
}

/**
 * Output total sums for invoice list
 *
 * @param string $where Where clause
 *
 * @return void
 */
function getInvoiceListTotal($where)
{
    global $dblink;
    $strFunc = 'invoices';
    $strList = 'invoice';

    $listConfig = getListConfig($strList);

    $strWhereClause = '';
    $joinOp = 'WHERE';
    $arrQueryParams = [];
    if ($where) {
        // Validate and build query parameters
        $boolean = '';
        while (extractSearchTerm($where, $field, $operator, $term, $nextBool)) {
            if (strcasecmp($operator, 'IN') === 0) {
                $strWhereClause .= "$boolean$field $operator " .
                     mysqli_real_escape_string($dblink, $term);
            } else {
                $strWhereClause .= "$boolean$field $operator ?";
                $arrQueryParams[] = str_replace('%-', '%', $term);
            }
            if (!$nextBool) {
                break;
            }
            $boolean = " $nextBool";
        }
        if ($strWhereClause) {
            $strWhereClause = "WHERE ($strWhereClause)";
            $joinOp = ' AND';
        }
    }
    if (!getSetting('show_deleted_records') && $listConfig['deletedField']) {
        $strWhereClause .= "$joinOp {$listConfig['deletedField']}=0";
        $joinOp = ' AND';
    }

    $sql = "SELECT sum(it.row_total) as total_sum from {$listConfig['table']} {$listConfig['displayJoin']} $strWhereClause";

    $sum = 0;
    $rows = dbParamQuery($sql, $arrQueryParams);
    if ($rows) {
        $sum = $rows[0]['total_sum'];
    }
    $result = [
        'sum' => null !== $sum ? $sum : 0,
        'sum_rounded' => miscRound2Decim($sum, 2, '.', '')
    ];

    echo createResponse($result);
}

/**
 * Update product stock balance
 *
 * @param int    $productId Product ID
 * @param int    $change    Change in balance
 * @param string $desc      Change description
 *
 * @return void
 */
function updateStockBalance($productId, $change, $desc)
{
    $missing = [];
    if (!$change) {
        $missing[] = Translator::translate('StockBalanceChange');
    }
    if (!$desc) {
        $missing[] = Translator::translate('StockBalanceChangeDescription');
    }

    if ($missing) {
        return createResponse(['missing_fields' => $missing]);
    }

    $rows = dbParamQuery(
        'SELECT stock_balance FROM {prefix}product WHERE id=?',
        [$productId]
    );
    if (!$rows) {
        return createResponse(
            ['status' => 'error', 'errors' => Translator::translate('ErrInvalidValue')]
        );
    }
    $row = $rows[0];
    $balance = $row['stock_balance'];
    $balance += $change;
    dbParamQuery(
        'UPDATE {prefix}product SET stock_balance=? where id=?',
        [$balance, $productId]
    );
    dbParamQuery(
        <<<EOT
INSERT INTO {prefix}stock_balance_log
(user_id, product_id, stock_change, description) VALUES (?, ?, ?, ?)
EOT
        ,
        [
            $_SESSION['sesUSERID'],
            $productId,
            $change,
            $desc
        ]
    );
    return createResponse(['status' => 'ok', 'new_stock_balance' => $balance]);
}

/**
 * Get send API services for the given invoice and base
 *
 * @param int $invoiceId Invoice ID
 * @param int $baseId    Base ID
 *
 * @return string
 */
function getSendApiServices($invoiceId, $baseId)
{
    $templateCandidates = dbParamQuery(
        'SELECT * FROM {prefix}print_template WHERE deleted=0 and type=? and inactive=0 ORDER BY order_no',
        [isOffer($invoiceId) ? 'offer' : 'invoice']
    );
    $templates = [];
    foreach ($templateCandidates as $candidate) {
        $printer = getInvoicePrinter($candidate['filename']);
        if (null === $printer) {
            continue;
        }
        $uses = class_uses($printer);
        if (in_array('InvoicePrinterEmailTrait', $uses)
            || $printer instanceof InvoicePrinterFinvoiceSOAP
            || $printer instanceof InvoicePrinterBlank
        ) {
            continue;
        }
        $templates[] = $candidate;
    }

    $services = [];
    foreach (getSendApiConfigs($baseId) as $config) {
        $urlBase = [
            'func' => 'send_api',
            'invoice_id' => $invoiceId,
            'api_id' => $config['id']
        ];
        $items = [];
        foreach ($templates as $template) {
            $item = $urlBase;
            $item['template_id'] = $template['id'];
            $items[] = [
                'href' => http_build_query($item),
                'name' => Translator::translate($template['name'])
            ];
        }
        $services[] = [
            'name' => $config['name'] ? $config['name'] : $config['method'],
            'items' => $items
        ];
    }

    return createResponse(['services' => $services]);
}

/**
 * Add an attachment to an invoice and return the new record
 *
 * @return string
 */
function addInvoiceAttachment()
{
    $newId = addAttachmentToInvoice(getPostOrQuery('id'), getPostOrQuery('invoice_id'));
    printJSONRecord('invoice_attachment', $newId);

}

/**
 * Handle error without disturbing actual output
 *
 * @param string $errno   Error code number
 * @param string $errstr  Error message
 * @param string $errfile File where error occurred
 * @param string $errline Line number of error
 *
 * @return bool           Always true to cancel default error handling
 */
function handleError($errno, $errstr, $errfile, $errline)
{
    global $phpErrors;
    $phpErrors[] = "[$errno] $errstr at $errfile:$errline";
    return true;
}

/**
 * Format a response as a JSON array
 *
 * @param array $response Response
 *
 * @return string
 */
function createResponse($response)
{
    global $phpErrors;
    if ($phpErrors) {
        $response['php_errors'] = $phpErrors;
    }
    return json_encode($response);
}
