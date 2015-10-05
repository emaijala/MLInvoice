<?php
/*******************************************************************************
 MLInvoice: web-based invoicing application.
 Copyright (C) 2010-2015 Ere Maijala

 This program is free software. See attached LICENSE.

 *******************************************************************************/

/*******************************************************************************
 MLInvoice: web-pohjainen laskutusohjelma.
 Copyright (C) 2010-2015 Ere Maijala

 Tämä ohjelma on vapaa. Lue oheinen LICENSE.

 *******************************************************************************/
ini_set('display_errors', 0);

require_once 'sqlfuncs.php';
require_once 'miscfuncs.php';
require_once 'sessionfuncs.php';
require_once 'form_funcs.php';
require_once 'localize.php';
require_once 'settings.php';

sesVerifySession(FALSE);

$strFunc = getRequest('func', '');

switch ($strFunc) {
case 'get_company' :
case 'get_company_contact' :
case 'get_product' :
case 'get_invoice' :
case 'get_invoice_row' :
case 'get_base' :
case 'get_print_template' :
case 'get_invoice_state' :
case 'get_row_type' :
case 'get_print_template' :
case 'get_company' :
case 'get_session_type' :
case 'get_delivery_terms' :
case 'get_delivery_method' :
    printJSONRecord(substr($strFunc, 4));
    break;
case 'get_user' :
    printJSONRecord('users');
    break;

case 'put_company' :
case 'put_product' :
case 'put_invoice' :
case 'put_base' :
case 'put_print_template' :
case 'put_invoice_state' :
case 'put_row_type' :
case 'put_print_template' :
case 'put_user' :
case 'put_session_type' :
case 'put_delivery_terms' :
case 'put_delivery_method' :
    saveJSONRecord(substr($strFunc, 4), '');
    break;

case 'session_type' :
case 'user' :
    if (!sesAdminAccess()) {
        header('HTTP/1.1 403 Forbidden');
        exit();
    }
    saveJSONRecord(substr($strFunc, 4), '');
    break;

case 'get_companies' :
    printJSONRecords('company', '', 'company_name');
    break;

case 'get_company_contacts' :
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

case 'delete_invoice_row' :
    deleteJSONRecord('invoice_row');
    break;

case 'add_reminder_fees' :
    require 'add_reminder_fees.php';
    $invoiceId = getRequest('id', 0);
    $errors = addReminderFees($invoiceId);
    if ($errors) {
        $ret = [
            'status' => 'error',
            'errors' => $errors
        ];
    } else {
        $ret = [
            'status' => 'ok'
        ];
    }
    echo json_encode($ret);
    break;

case 'get_invoice_defaults' :
    $baseId = getRequest('base_id', 0);
    $companyId = getRequest('company_id', 0);
    $invoiceId = getRequest('id', 0);
    $invoiceDate = getRequest('invoice_date',
        dateConvDBDate2Date(date('Y') . '0101'));
    $intervalType = getRequest('interval_type', 0);
    $invNr = getRequest('invoice_no', 0);
    $perYear = getSetting('invoice_numbering_per_year');

    // If the invoice already has an invoice number, verify that it's not in use in another invoice
    if ($invNr) {
        $query = 'SELECT ID FROM {prefix}invoice where deleted=0 AND id!=? AND invoice_no=?';
        $params = [
            $invoiceId,
            $invNr
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
            $invNr = 0;
        }
    }

    if (!$invNr) {
        $maxNr = get_max_invoice_number($invoiceId,
            getSetting('invoice_numbering_per_base') && $baseId ? $baseId : null,
            $perYear);
        if ($maxNr === null && $perYear) {
            $maxNr = get_max_invoice_number($invoiceId,
                getSetting('invoice_numbering_per_base') && $baseId ? $baseId : null,
                false);
        }
        $invNr = $maxNr + 1;
    }
    if ($invNr < 100)
        $invNr = 100; // min ref number length is 3 + check digit, make sure invoice number matches that
    $refNr = $invNr . miscCalcCheckNo($invNr);
    $strDate = date($GLOBALS['locDateFormat']);
    $strDueDate = date($GLOBALS['locDateFormat'],
        mktime(0, 0, 0, date('m'), date('d') + getPaymentDays($companyId), date('Y')));
    switch ($intervalType) {
    case 2 :
        $nextIntervalDate = date($GLOBALS['locDateFormat'],
            mktime(0, 0, 0, date('m') + 1, date('d'), date('Y')));
        break;
    case 3 :
        $nextIntervalDate = date($GLOBALS['locDateFormat'],
            mktime(0, 0, 0, date('m'), date('d'), date('Y') + 1));
        break;
    default :
        $nextIntervalDate = '';
    }
    $arrData = [
        'invoice_no' => $invNr,
        'ref_no' => $refNr,
        'date' => $strDate,
        'due_date' => $strDueDate,
        'next_interval_date' => $nextIntervalDate
    ];
    header('Content-Type: application/json');
    echo json_encode($arrData);
    break;

case 'get_table_columns' :
    if (!sesAdminAccess()) {
        header('HTTP/1.1 403 Forbidden');
        exit();
    }
    $table = getRequest('table', '');
    if (!$table) {
        header('HTTP/1.1 400 Bad Request');
        exit();
    }
    // account_statement is a pseudo table for account statement "import"
    if ($table == 'account_statement') {
        header('Content-Type: application/json');
        echo '{"columns":';
        echo json_encode(
            [
                [
                    'id' => 'date',
                    'name' => $GLOBALS['locImportStatementPaymentDate']
                ],
                [
                    'id' => 'amount',
                    'name' => $GLOBALS['locImportStatementAmount']
                ],
                [
                    'id' => 'refnr',
                    'name' => $GLOBALS['locImportStatementRefNr']
                ]
            ]);
        echo "\n}";
        exit();
    }

    if (!table_valid($table)) {
        header('HTTP/1.1 400 Bad Request');
        die('Invalid table name');
    }

    header('Content-Type: application/json');
    echo '{"columns":[';
    $res = mysqli_query_check("select * from {prefix}$table where 1=2");
    $field_count = mysqli_num_fields($res);
    for ($i = 0; $i < $field_count; $i ++) {
        $field_def = mysqli_fetch_field($res);
        if ($i == 0) {
            echo "\n";
        } else
            echo ",\n";
        echo json_encode([
            'name' => $field_def->name
        ]);
    }
    echo "\n]}";
    break;

case 'get_import_preview' :
    if (!sesAdminAccess()) {
        header('HTTP/1.1 403 Forbidden');
        exit();
    }
    $table = getRequest('table', '');
    if ($table == 'account_statement') {
        require 'import_statement.php';
        $import = new ImportStatement();
    } else {
        require 'import.php';
        $import = new ImportFile();
    }
    $import->create_import_preview();
    break;

case 'get_list' :
    require 'list.php';

    $listFunc = getRequest('listfunc', '');

    $strList = getRequest('table', '');
    if (!$strList) {
        header('HTTP/1.1 400 Bad Request');
        die('Table must be defined');
    }

    include 'list_switch.php';

    if (!$strTable) {
        header('HTTP/1.1 400 Bad Request');
        die('Invalid table name');
    }

    $startRow = intval(getRequest('start', -1));
    $rowCount = intval(getRequest('length', -1));
    $sort = [];
    $columns = getRequest('columns', []);
    if ($orderCols = getRequest('order', [])) {
        for ($i = 0; $i < count($orderCols); $i ++) {
            if (!isset($orderCols[$i]['column'])) {
                continue;
            }
            $sortColumn = $orderCols[$i]['column'];
            $sortDir = $orderCols[$i]['dir'];
            $sort[] = [
                $sortColumn => $sortDir === 'desc' ? 'desc' : 'asc'
            ];
        }
    }
    $search = getRequest('search', []);
    $filter = empty($search['value']) ? '' : $search['value'];
    $where = getRequest('where', '');

    header('Content-Type: application/json');
    echo createJSONList($listFunc, $strList, $startRow, $rowCount, $sort, $filter,
        $where, intval(getRequest('draw', 1)));
    break;

case 'get_invoice_total_sum' :
    $where = getRequest('where', '');

    header('Content-Type: application/json');
    echo getInvoiceListTotal($where);
    break;

case 'get_selectlist' :
    require 'list.php';

    $table = getRequest('table', '');
    if (!$table) {
        header('HTTP/1.1 400 Bad Request (table)');
        exit();
    }

    if (!table_valid($table)) {
        header('HTTP/1.1 400 Bad Request');
        die('Invalid table name');
    }

    $pageLen = intval(getRequest('pagelen', 10));
    $page = intval(getRequest('page', 1)) - 1;
    $filter = getRequest('q', '');
    $sort = getRequest('sort', '');
    $id = getRequest('id', '');

    header('Content-Type: application/json');
    echo createJSONSelectList($table, $page * $pageLen, $pageLen, $filter, $sort,
        $id);
    break;

case 'update_invoice_row_dates' :
    if (!sesWriteAccess()) {
        header('HTTP/1.1 403 Forbidden');
        exit();
    }
    $invoiceId = getRequest('id', 0);
    $date = getRequest('date', '');
    if (!$date) {
        header('HTTP/1.1 400 Bad Request');
        die('date must be given');
    }
    header('Content-Type: application/json');
    echo updateInvoiceRowDates($invoiceId, $date);
    break;

case 'update_stock_balance' :
    if (!sesWriteAccess()) {
        header('HTTP/1.1 403 Forbidden');
        exit();
    }
    $productId = getRequest('product_id', 0);
    $change = getRequest('stock_balance_change', 0);
    $desc = getRequest('stock_balance_change_desc', '');
    header('Content-Type: application/json');
    echo updateStockBalance($productId, $change, $desc);
    break;

case 'get_stock_balance_rows' :
    $productId = getRequest('product_id', 0);
    if (!$productId) {
        exit();
    }
    $res = mysqli_param_query(
        'SELECT l.time, u.name, l.stock_change, l.description FROM {prefix}stock_balance_log l INNER JOIN {prefix}users u ON l.user_id=u.id WHERE product_id=? ORDER BY time DESC',
        [
            $productId
        ]);
    $html = '';
    while ($row = mysqli_fetch_assoc($res)) {
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

case 'noop' :
    // Session keep-alive
    break;

default :
    header('HTTP/1.1 404 Not Found');
}

function printJSONRecord($table, $id = FALSE, $warnings = null)
{
    if ($id === FALSE)
        $id = getRequest('id', '');
    if ($id) {
        if (substr($table, 0, 8) != '{prefix}')
            $table = "{prefix}$table";
        $select = 'SELECT t.*';
        $from = "FROM $table t";
        $where = 'WHERE t.id=?';

        if ($table == '{prefix}invoice_row') {
            // Include product name and code
            $select .= ", CASE WHEN LENGTH(p.product_code) = 0 THEN IFNULL(p.product_name, '') ELSE CONCAT_WS(' ', p.product_code, IFNULL(p.product_name, '')) END as product_id_text";
            $from .= ' LEFT OUTER JOIN {prefix}product p on (p.id = t.product_id)';
        }

        $query = "$select $from $where";
        $res = mysqli_param_query($query, [
            $id
        ]);
        $row = mysqli_fetch_assoc($res);
        if ($table == 'users')
            unset($row['password']);
        header('Content-Type: application/json');
        $row['warnings'] = $warnings;
        if ($table == '{prefix}base') {
            unset($row['logo_filedata']);
        }
        echo json_encode($row);
    }
}

function printJSONRecords($table, $parentIdCol, $sort)
{
    $select = 'SELECT t.*';
    $from = "FROM {prefix}$table t";

    if ($table == 'invoice_row') {
        // Include product name, product code and row type name
        $select .= ", CASE WHEN LENGTH(p.product_code) = 0 THEN IFNULL(p.product_name, '') ELSE CONCAT_WS(' ', p.product_code, IFNULL(p.product_name, '')) END as product_id_text";
        $from .= ' LEFT OUTER JOIN {prefix}product p on (p.id = t.product_id)';
        $select .= ', rt.name as type_id_text';
        $from .= ' LEFT OUTER JOIN {prefix}row_type rt on (rt.id = t.type_id)';
    }

    $where = '';
    $params = [];
    $id = getRequest('parent_id', '');
    if ($id && $parentIdCol) {
        $where .= " WHERE t.$parentIdCol=?";
        $params[] = $id;
    }
    if (!getSetting('show_deleted_records')) {
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
    $res = mysqli_param_query($query, $params);
    header('Content-Type: application/json');
    echo '{"records":[';
    $first = true;
    while ($row = mysqli_fetch_assoc($res)) {
        if ($first) {
            echo "\n";
            $first = false;
        } else
            echo ",\n";
        if ($table == 'users')
            unset($row['password']);
        if ($table == 'invoice_row') {
            if (!empty($row['type_id_text']) &&
                 isset($GLOBALS['loc' . $row['type_id_text']])) {
                $row['type_id_text'] = $GLOBALS['loc' . $row['type_id_text']];
            }
        }
        echo json_encode($row);
    }
    echo "\n]}";
}

function saveJSONRecord($table, $parentKeyName)
{
    if (!sesWriteAccess()) {
        header('HTTP/1.1 403 Forbidden');
        exit();
    }

    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) {
        header('HTTP/1.1 400 Bad Request');
        return;
    }
    $strForm = $table;
    $strFunc = '';
    $strList = '';
    $id = isset($data['id']) ? $data['id'] : false;
    require 'form_switch.php';
    $new = $id ? false : true;
    unset($data['id']);
    $warnings = '';
    $res = saveFormData($strTable, $id, $astrFormElements, $data, $warnings,
        $parentKeyName, $parentKeyName ? $data[$parentKeyName] : FALSE);
    if ($res !== true) {
        if ($warnings) {
            header('HTTP/1.1 409 Conflict');
        }
        header('Content-Type: application/json');
        echo json_encode(
            [
                'missing_fields' => $res,
                'warnings' => $warnings
            ]);
        return;
    }
    if ($new)
        header('HTTP/1.1 201 Created');
    printJSONRecord($strTable, $id, $warnings);
}

function DeleteJSONRecord($table)
{
    if (!sesWriteAccess()) {
        header('HTTP/1.1 403 Forbidden');
        exit();
    }

    $id = getRequest('id', '');
    if ($id) {
        deleteRecord("{prefix}$table", $id);
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'ok'
        ]);
    }
}

function getInvoiceListTotal($where)
{
    global $dblink;
    $strFunc = 'invoices';
    $strList = 'invoice';

    require 'list_switch.php';

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
            if (!$nextBool)
                break;
            $boolean = " $nextBool";
        }
        if ($strWhereClause) {
            $strWhereClause = "WHERE ($strWhereClause)";
            $joinOp = ' AND';
        }
    }
    if (!getSetting('show_deleted_records')) {
        $strWhereClause .= "$joinOp $strDeletedField=0";
        $joinOp = ' AND';
    }

    $sql = "SELECT sum(it.row_total) as total_sum from $strTable $strJoin $strWhereClause";

    $sum = 0;
    $res = mysqli_param_query($sql, $arrQueryParams);
    if ($row = mysqli_fetch_assoc($res)) {
        $sum = $row['total_sum'];
    }
    $result = [
        'sum' => $sum,
        'sum_str' => sprintf($GLOBALS['locInvoicesTotal'], miscRound2Decim($sum))
    ];

    echo json_encode($result);
}

function updateInvoiceRowDates($invoiceId, $date)
{
    $date = dateConvDate2DBDate($date);
    if ($date === false) {
        return json_encode(
            [
                'status' => 'error',
                'errors' => $GLOBALS['locErrInvalidValue']
            ]);
    }
    mysqli_param_query(
        'UPDATE {prefix}invoice_row SET row_date=? WHERE invoice_id=? AND deleted=0',
        [
            $date,
            $invoiceId
        ]);
    return json_encode([
        'status' => 'ok'
    ]);
}

function updateStockBalance($productId, $change, $desc)
{
    $missing = [];
    if (!$change) {
        $missing[] = $GLOBALS['locStockBalanceChange'];
    }
    if (!$desc) {
        $missing[] = $GLOBALS['locStockBalanceChangeDescription'];
    }

    if ($missing) {
        return json_encode([
            'missing_fields' => $missing
        ]);
    }

    $res = mysqli_param_query('SELECT stock_balance FROM {prefix}product WHERE id=?',
        [
            $productId
        ]);
    $row = mysqli_fetch_row($res);
    if ($row === null) {
        return json_encode(
            [
                'status' => 'error',
                'errors' => $GLOBALS['locErrInvalidValue']
            ]);
    }
    $balance = $row[0];
    $balance += $change;
    mysqli_param_query('UPDATE {prefix}product SET stock_balance=? where id=?',
        [
            $balance,
            $productId
        ]);
    mysqli_param_query(
        'INSERT INTO {prefix}stock_balance_log (user_id, product_id, stock_change, description) VALUES (?, ?, ?, ?)',
        [
            $_SESSION['sesUSERID'],
            $productId,
            $change,
            $desc
        ]);
    return json_encode(
        [
            'status' => 'ok',
            'new_stock_balance' => $balance
        ]);
}

function get_max_invoice_number($invoiceId, $baseId, $perYear)
{
    if ($baseId !== null) {
        $sql = 'SELECT max(cast(invoice_no as unsigned integer)) FROM {prefix}invoice WHERE deleted=0 AND id!=? AND base_id=?';
        $params = [
            $invoiceId,
            $baseId
        ];
    } else {
        $sql = 'SELECT max(cast(invoice_no as unsigned integer)) FROM {prefix}invoice WHERE deleted=0 AND id!=?';
        $params = [
            $invoiceId
        ];
    }
    if ($perYear) {
        $sql .= ' AND invoice_date >= ' . date('Y') . '0101';
    }
    $res = mysqli_param_query($sql, $params);
    return mysqli_fetch_value($res);
}
