<?php
/*******************************************************************************
 MLInvoice: web-based invoicing application.
 Copyright (C) 2010-2017 Ere Maijala

 This program is free software. See attached LICENSE.

 *******************************************************************************/

/*******************************************************************************
 MLInvoice: web-pohjainen laskutusohjelma.
 Copyright (C) 2010-2017 Ere Maijala

 Tämä ohjelma on vapaa. Lue oheinen LICENSE.

 *******************************************************************************/
ini_set('display_errors', 1);

require_once 'config.php';

if (defined('_PROFILING_') && is_callable('tideways_enable')) {
    tideways_enable(TIDEWAYS_FLAGS_CPU + TIDEWAYS_FLAGS_MEMORY);
}

require_once 'sqlfuncs.php';
require_once 'miscfuncs.php';
require_once 'sessionfuncs.php';
require_once 'form_funcs.php';
require_once 'translator.php';
require_once 'settings.php';
require_once 'memory.php';

sesVerifySession(false);

$strFunc = getRequest('func', '');
switch ($strFunc) {
case 'get_company':
case 'get_company_contact':
case 'get_product':
case 'get_invoice':
case 'get_invoice_row':
case 'get_base':
case 'get_print_template':
case 'get_invoice_state':
case 'get_row_type':
case 'get_print_template':
case 'get_session_type':
case 'get_delivery_terms':
case 'get_delivery_method':
case 'get_default_value':
    printJSONRecord(substr($strFunc, 4));
    break;
case 'get_event':
  printJSONRecord('events');
  break;

  case 'get_session':
    printJSONRecord('sessions');
    break;

case 'get_user':
    printJSONRecord('users');
    break;
case 'get_file':
    printJSONRecord('files');
break;

case 'put_company':
case 'put_product':
case 'put_invoice':
case 'put_base':
case 'put_print_template':
case 'put_invoice_state':
case 'put_row_type':
case 'put_print_template':
case 'put_user':
case 'put_session_type':
case 'put_delivery_terms':
case 'put_delivery_method':
case 'put_default_value':
case 'put_events':
case 'put_sessions':
    saveJSONRecord(substr($strFunc, 4), '');
    break;

case 'delete_invoice_row':
case 'delete_default_value':
    deleteJSONRecord(substr($strFunc, 7));
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
    printJSONRecords('company', '', 'company_name','');
    break;


case 'get_sessions':
$event_id= getRequest('event_id', -1);
$customer_id = getRequest('customer_id', -1);
if ($event_id >= 0)
{
    printJSONRecords('sessions', '', ''," event_id=".$event_id);
}
else if ($customer_id >= 0)
{
    printJSONRecords('sessions', '', ''," find_in_set(".$customer_id.",substring(participants,2,length(participants)-2))");
}
else {
    printJSONRecords('sessions', '', '','');
}


            break;

case 'get_events':
        printJSONRecords('events', '', '','');
        break;


case 'get_users':
                printJSONRecords('users', '', '','');
                break;


case 'get_company_contacts' :
    printJSONRecords('company_contact', 'company_id', 'contact_person','');
    break;

case 'delete_company_contact' :
    deleteJSONRecord('company_contact');
    break;

case 'put_company_contact' :
    saveJSONRecord('company_contact', 'company_id');
    break;

case 'get_products' :
    printJSONRecords('product', '', 'product_name','');
    break;
case 'get_vehicles':
  printJSONRecords('vehicles', '', '','');
  break;
case 'get_vehicles_category':
    printJSONRecords('vehicles_category', '', '','');
    break;

case 'get_invoices' :
        printJSONRecords('invoice', '', '','');
        break;

case 'get_invoices_by_state' :
    $invoiceStateid= getRequest('state_id', -1);
    if ($invoiceStateid >= 0)
    {
        printJSONRecords('invoice', '', ''," state_id=".$invoiceStateid);
    }
    else {
        printJSONRecords('invoice', '', '','');
    }

                break;
                case 'get_invoices_by_customer' :
                    $invoiceStateid= getRequest('company_id', -1);
                    if ($invoiceStateid >= 0)
                    {
                        printJSONRecords('invoice', '', ''," company_id=".$invoiceStateid);
                    }
                    else {
                        printJSONRecords('invoice', '', '','');
                    }

                                break;


                                case 'get_files' :
                                    $customer_id= getRequest('customer_id', -1);
                                    $category= getRequest('category', -1);
                                    if ($customer_id >= 0 && $category >=0)
                                    {
                                        printJSONRecords('files', '', ''," customer_id=".$customer_id." and category='".$category."'");
                                    }
                                    else {
                                        json_response();
                                    }

                                                break;

case 'get_unpaid_invoices' :
          printJSONRecords('invoice', '', ''," state_id=2 ");
break;


case 'get_row_types' :
    printJSONRecords('row_type', '', 'order_no','');
    break;

case 'get_invoice_rows' :
    printJSONRecords('invoice_row', 'invoice_id', 'order_no','');
    break;

case 'put_invoice_row' :
    saveJSONRecord('invoice_row', 'invoice_id');
    break;

case 'add_reminder_fees' :
    include 'add_reminder_fees.php';
    $invoiceId = getRequest('id', 0);
    $errors = addReminderFees($invoiceId);
    if ($errors) {
        $ret = ['status' => 'error', 'errors' => $errors];
    } else {

        $ret = ['status' => 'ok'];
    }
    json_response($ret);
    break;

case 'get_invoice_defaults' :
    $baseId = getRequest('base_id', 0);
    $companyId = getRequest('company_id', 0);
    $invoiceId = getRequest('id', 0);
    $invoiceDate = getRequest(
        'invoice_date', dateConvDBDate2Date(date('Y') . '0101')
    );
    $intervalType = getRequest('interval_type', 0);
    $invoiceNumber = getRequest('invoice_no', 0);

    $defaults = getInvoiceDefaults(
        $invoiceId, $baseId, $companyId, $invoiceDate, $intervalType, $invoiceNumber
    );

    //header('Content-Type: application/json');
    json_response($defaults);
    break;

case 'get_table_columns' :
    if (!sesAdminAccess()) {
        header('HTTP/1.1 403 Forbidden');
        break;
    }
    $table = getRequest('table', '');
    if (!$table) {
        header('HTTP/1.1 400 Bad Request');
        break;
    }
    // account_statement is a pseudo table for account statement "import"
    if ($table == 'account_statement') {
        //header('Content-Type: application/json');
        $str= '{"columns":';
        $str=$str.json_encode(
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
        $str= "\n}";
        json_response($str);
        break;
    }

    if (!table_valid($table)) {
        header('HTTP/1.1 400 Bad Request');
        die('Invalid table name');
    }

    //header('Content-Type: application/json');
    $str= '{"columns":[';
    $res = mysqli_query_check("select * from {prefix}$table where 1=2");
    $field_count = mysqli_num_fields($res);
    for ($i = 0; $i < $field_count; $i ++) {
        $field_def = mysqli_fetch_field($res);
        if ($i == 0) {
            $str=$str. "\n";
        } else {
            $str=$str.",\n";
        }
        $str=$str. json_encode(['name' => $field_def->name]);
    }
    if ('company' === $table || 'company_contact' === $table) {
        $str=$str. ",\n";
        $str=$str. json_encode(['name' => 'tags']);
    }
    $str=$str. "\n]}";
    json_response($str);
    break;

case 'get_import_preview' :
    if (!sesAdminAccess()) {
        header('HTTP/1.1 403 Forbidden');
        break;
    }
    $table = getRequest('table', '');
    if ($table == 'account_statement') {
        include 'import_statement.php';
        $import = new ImportStatement();
    } else {
        include 'import.php';
        $import = new ImportFile();
    }
    $import->create_import_preview();
    break;

case 'get_list' :
    include 'list.php';

    $listFunc = getRequest('listfunc', '');

    $strList = getRequest('table', '');
    if (!$strList) {
        header('HTTP/1.1 400 Bad Request');
        die('Table must be defined');
    }

    $tableId = getRequest('tableid', '');

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

    $where =utf8_decode(stripcslashes($where));

    header('Content-Type: application/json');
    echo createJSONList(
        $listFunc, $strList, $startRow, $rowCount, $sort, $filter, $where,
        intval(getRequest('draw', 1)), $tableId
    );
    Memory::set(
        $tableId,
        compact(
            'strList', 'startRow', 'rowCount', 'sort', 'filter', 'where'
        )
    );
    break;

case 'get_invoice_total_sum' :
    $start_date=getRequest('start_date', false);
    $end_date=getRequest('end_date', false);
    $where = getRequest('where', '');
    getInvoiceListTotal($where,$start_date,$end_date);
    break;
case 'get_selectlist' :
    include 'list.php';

    $table = getRequest('table', '');
    if (!$table) {
        header('HTTP/1.1 400 Bad Request (table)');
        break;
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
    $type = getRequest('type', '');

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

case 'upload':
    $valid_extensions = array('jpeg', 'jpg', 'png', 'gif', 'bmp' , 'pdf' , 'doc' , 'ppt'); // valid extensions
    $path = '/var/www/files/uploads/'; // upload directory

    if(!empty($_POST['name']) || !empty($_POST['email']) || $_FILES['file'])
    {
        $name = $_FILES['file']['name'];
        $tmp = $_FILES['file']['tmp_name'];
        $module = $_POST['module'];
        $customer_id = $_POST['customer_id'];
        $category = $_POST['category'];

        // get uploaded file's extension
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));

        // can upload same image using rand function
        $final_image = rand(1000,1000000).$name;

        // check's valid format
        if(in_array($ext, $valid_extensions))
        {
          $path = $path.strtolower($final_image);

          if(move_uploaded_file($tmp,$path))
          {

           dbParamQuery(

          "INSERT INTO {prefix}files
          (user_id, module, path, name,customer_id,category) VALUES (?, ?, ?, ?,?,?)"
          ,
                  [
                      $_SESSION['sesUSERID'],
                      $module,
                      $path,
                      $name,
                      $customer_id,
                      $category

                  ]
              );
              json_response("file_uploaded");
          }
}
else
{
json_response('Not allowed extension',400);
}
}
else {
  json_response('Not allowed extension',400);
  # code...
}
break;

case 'update_stock_balance' :
    if (!sesWriteAccess()) {
        header('HTTP/1.1 403 Forbidden');
        break;
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
        break;
    }
    $rows =  dbParamQuery(
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

case 'get_full_invoice':
    $id = getRequest('id', '');
    $table="invoice_row";
    $table = "{prefix}$table";
    $select = 'SELECT i.*,t.* ';
    $from = "FROM $table t";
    $from .=" LEFT JOIN {prefix}invoice i on t.invoice_id=i.id ";
    $where = 'WHERE i.id=?';
    $select .= ", CASE WHEN LENGTH(p.product_code) = 0 THEN IFNULL(p.product_name, '') ELSE CONCAT_WS(' ', p.product_code, IFNULL(p.product_name, '')) END as product_id_text";
    $from .= ' LEFT OUTER JOIN {prefix}product p on (p.id = t.product_id)';
    $query = "$select $from $where";
    $rows =  dbParamQuery($query, [$id]);

    json_response($rows);


  break;
case 'noop' :
    // Session keep-alive
    header('HTTP/1.1 204 No Content');
    break;

default :
    header('HTTP/1.1 404 Not Found');
}

if (defined('_PROFILING_') && is_callable('tideways_disable')) {
    $data = tideways_disable();
    file_put_contents(
        sys_get_temp_dir() . '/' . uniqid() . '.mlinvoice-json.xhprof',
        serialize($data)
    );
}

function printJSONRecord($table, $id = false, $warnings = null,$search=null)
{

    if ($id === false) {
        $id = getRequest('id', '');
    }

    if ($id) {
        if (substr($table, 0, 8) != '{prefix}') {
            $table = "{prefix}$table";
        }
        $select = 'SELECT t.*';
        $from = "FROM $table t";
        $where = 'WHERE t.id=?';
        if ($search)
        {
            $where=$where." AND ".$search;
        }

        if ($table == '{prefix}invoice_row') {
            // Include product name and code
            $select .= ", CASE WHEN LENGTH(p.product_code) = 0 THEN IFNULL(p.product_name, '') ELSE CONCAT_WS(' ', p.product_code, IFNULL(p.product_name, '')) END as product_id_text";
            $from .= ' LEFT OUTER JOIN {prefix}product p on (p.id = t.product_id)';
        }

        $query = "$select $from $where";

        $rows =  dbParamQuery($query, [$id]);
        if (!$rows) {
            header('HTTP/1.1 404 Not Found');
            return;
        }

        $row = $rows[0];
        if ($table == 'users') {
            unset($row['password']);
        }

        // Fetch tags
        if ($table == '{prefix}company') {
            $row['tags'] = getTags('company', $id);
        } elseif ($table == '{prefix}company_contact') {
            $row['tags'] = getTags('contact', $id);
        }


        //header('Content-Type: application/json');
        $row['warnings'] = $warnings;
        if ($table == '{prefix}base') {
            unset($row['logo_filedata']);
        }
        //print_r($row);
        if ($table == '{prefix}files')
        {
          //$row['url']=file_get_contents($row['path']);
          $filename = $row['path'];
          $handle = fopen($filename, "rb");
          $contents = fread($handle, filesize($filename));
          fclose($handle);
          //$row['url']=$contents;

          header('Content-Type: application/pdf');
          echo $contents;
          //json_response($row);
        }
        else {
          json_response($row);  // code...
        }

    }
}

function json_response($message = null, $code = 200)
{
    // clear the old headers
    header_remove();
    // set the actual code
    http_response_code($code);
    // set the header to make sure cache is forced
    header("Cache-Control: no-cache,no-transform,public,max-age=300,s-maxage=900");
    // treat this as json
    header('Access-Control-Allow-Credentials: true');
    if ( isset($_SERVER['HTTP_ORIGIN']))
    {
    $http_origin = $_SERVER['HTTP_ORIGIN'];


    if ($http_origin == "http://192.168.195.230:6075" || $http_origin == "http://localhost:6075" || $http_origin == "http://sunlumo.fi:6075")
    {
	        header("Access-Control-Allow-Origin: $http_origin");
    }
  }
  else 
  {
    header("Access-Control-Allow-Origin: http://localhost:6075");
  }
    header('Content-Type: application/json');
    $status = array(
        200 => '200 OK',
        400 => '400 Bad Request',
        422 => 'Unprocessable Entity',
        500 => '500 Internal Server Error'
        );
    // ok, validation error, or failure
    header('Status: '.$status[$code]);
    // return the encoded json    
    echo json_encode($message);
}


function printJSONRecords($table, $parentIdCol, $sort,$search=null)
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
    if ($search)
    {
      $where.=' AND '.$search;
    }

    $query = "$select $from $where";
    if ($sort) {
        $query .= " order by $sort";
    }
    //echo $query;
    $rows =  dbParamQuery($query, $params);
    //print_r($rows);
    $rows_uniq=array();
    //header('Content-Type: application/json');
    //$str= '{"records":[';
    $first = true;
    foreach ($rows as $row) {
        if ($first) {
      //      $str=$str. "\n";
            $first = false;
        } else {
        //    $str=$str. ",\n";
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

        array_push($rows_uniq,$row);

    }

    $records=array('records' => $rows_uniq);
    json_response($records);
}

function saveJSONRecord($table, $parentKeyName)
{

    if (!sesWriteAccess()) {
        header('HTTP/1.1 410 Forbidden');
        return;
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
    include 'form_switch.php';
    $new = $id ? false : true;
    unset($data['id']);

    $onPrint = false;
    if (isset($data['onPrint'])) {
        $onPrint = $data['onPrint'];
        unset($data['onPrint']);
    }

    $warnings = '';
    $res = saveFormData(
        $strTable, $id, $astrFormElements, $data, $warnings, $parentKeyName,
        $parentKeyName ? $data[$parentKeyName] : false, $onPrint
    );
    if ($res !== true) {
        if ($warnings) {
            header('HTTP/1.1 409 Conflict');
        }
        header('Content-Type: application/json');
        echo json_encode(['missing_fields' => $res, 'warnings' => $warnings]);
        return;
    }

    if ($new) {
        header('HTTP/1.1 201 Created');
    }
    printJSONRecord($strTable, $id, $warnings);
}

function DeleteJSONRecord($table)
{
    if (!sesWriteAccess()) {
        header('HTTP/1.1 403 Forbidden');
        return;
    }

    $ids = getRequest('id', '');
    if ($ids) {
        foreach ((array)$ids as $id) {
            deleteRecord("{prefix}$table", $id);
        }
        header('Content-Type: application/json');
        echo json_encode(['status' => 'ok']);
    }
}

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
    include 'form_switch.php';

    $warnings = '';
    foreach ($request['ids'] as $id) {
        $id = (int)$id;
        // Set fields anew for every row since saveFormData returns the whole record
        $data = $request['changes'];
        $res = saveFormData(
            '{prefix}' . $request['table'], $id, $astrFormElements, $data, $warnings,
            false, false, false, true
        );
        if ($res !== true) {
            if ($warnings) {
                header('HTTP/1.1 409 Conflict');
            }
            header('Content-Type: application/json');
            return json_encode(['missing_fields' => $res, 'warnings' => $warnings]);
        }
    }

    return json_encode(['status' => 'ok']);
}

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

    return json_encode(['status' => 'ok']);
}

function getInvoiceListTotal($where,$startDate,$endDate)
{
    global $dblink;
    $strFunc = 'invoices';
    $strList = 'invoice';

    include 'list_switch.php';

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
    if (!getSetting('show_deleted_records')) {
        $strWhereClause .= "$joinOp $strDeletedField=0";
        $joinOp = ' AND';
    }

    if (preg_match('/^[0-9]{8}$/',$startDate))
    {
      $strWhereClause .= "$joinOp invoice_date>=".$startDate;
      $joinOp = ' AND';

    }

    if (preg_match('/^[0-9]{8}$/',$endDate))
    {
      $strWhereClause .= "$joinOp invoice_date<=".$endDate;
      $joinOp = ' AND';

    }

    $sql = "SELECT sum(it.row_total) as total_sum from $strTable $strJoin $strWhereClause";

    $sum = 0;
    $rows =  dbParamQuery($sql, $arrQueryParams);
    if ($rows) {
        $sum = $rows[0]['total_sum'];
    }
    $result = [
        'sum' => null !== $sum ? $sum : 0,
        'sum_rounded' => miscRound2Decim($sum, 2, '.', '')
    ];

    json_response($result);
}

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
        return json_encode(['missing_fields' => $missing]);
    }

    $rows =  dbParamQuery(
        'SELECT stock_balance FROM {prefix}product WHERE id=?',
        [$productId]
    );
    if (!$rows) {
        return json_encode(
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
    return json_encode(['status' => 'ok', 'new_stock_balance' => $balance]);
}

function ReadFolderDirectory($dir,$recurse = FALSE)
{
  $retval = array();

  // add trailing slash if missing
  if(substr($dir, -1) != "/") {
    $dir .= "/";
  }

  // open pointer to directory and read list of files
  $d = @dir($dir) or json_response("Unable to get directory",400);
  if (!$d)
  {exit();}

  //die("getFileList: Failed opening directory {$dir} for reading");
  while(FALSE !== ($entry = $d->read())) {
    // skip hidden files
    if($entry{0} == ".") continue;
    if(is_dir("{$dir}{$entry}")) {
      $retval[] = [
        'name' => "{$dir}{$entry}/",
        'type' => filetype("{$dir}{$entry}"),
        'size' => 0,
        'lastmod' => filemtime("{$dir}{$entry}"),
        'children' => ($recurse && is_readable("{$dir}{$entry}/")) ? ReadFolderDirectory("{$dir}{$entry}/", TRUE) : []
      ];
      /*if($recurse && is_readable("{$dir}{$entry}/")) {
        $retval = array_merge($retval, ReadFolderDirectory("{$dir}{$entry}/", TRUE));
      }*/
    } elseif(is_readable("{$dir}{$entry}")) {
      $retval[] = [
        'name' => "{$dir}{$entry}",
        'type' => mime_content_type("{$dir}{$entry}"),
        'size' => filesize("{$dir}{$entry}"),
        'lastmod' => filemtime("{$dir}{$entry}"),
        'children' => array()
      ];
    }
  }
  $d->close();

  return $retval;
}


function get_max_invoice_number($invoiceId, $baseId, $perYear)
{
    if ($baseId !== null) {
        $sql = 'SELECT max(cast(invoice_no as unsigned integer)) as maxnum FROM {prefix}invoice WHERE deleted=0 AND id!=? AND base_id=?';
        $params = [
            $invoiceId,
            $baseId
        ];
    } else {
        $sql = 'SELECT max(cast(invoice_no as unsigned integer)) as maxnum FROM {prefix}invoice WHERE deleted=0 AND id!=?';
        $params = [$invoiceId];
    }
    if ($perYear) {
        $sql .= ' AND invoice_date >= ' . date('Y') . '0101';
    }
    $rows =  dbParamQuery($sql, $params);
    return $rows[0]['maxnum'];
}
