<?php
/**
 * Database functions
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
require_once 'config.php';

$dblink = null;

/**
 * Initialize database connection
 *
 * @return void
 */
function initDbConnection()
{
    global $dblink;

    // Connect to database server
    $dblink = mysqli_connect(_DB_SERVER_, _DB_USERNAME_, _DB_PASSWORD_);

    if (mysqli_connect_errno()) {
        die('Could not connect to database: ' . mysqli_connect_error());
    }

    // Select database
    mysqli_select_db($dblink, _DB_NAME_) or
         die('Could not select database: ' . mysqli_error($dblink));

    if (_CHARSET_ == 'UTF-8') {
        dbQueryCheck('SET NAMES \'utf8\'');
    }

    dbQueryCheck('SET AUTOCOMMIT=1');
}

/**
 * Parse a search string
 *
 * @param string $searchTerms Search terms
 * @param string $field       Field
 * @param string $operator    Operator
 * @param string $term        Extracted term
 * @param string $boolean     Any boolean operator
 *
 * @return bool Whether the extraction succeeded
 */
function extractSearchTerm(&$searchTerms, &$field, &$operator, &$term, &$boolean)
{
    if (true
        && !preg_match(
            '/^([\w\.\_]+)\s*(=|!=|<=?|>=?|LIKE)\s*(.+)/',
            $searchTerms,
            $matches
        )
    ) {
        if (!preg_match('/^([\w\.\_]+)\s+(IN)\s+(.+)/', $searchTerms, $matches)) {
            return false;
        }
    }
    $field = $matches[1];
    $operator = $matches[2];
    $rest = $matches[3];
    $term = '';
    $inQuotes = false;
    $inParenthesis = 0;
    $escaped = false;
    while ($rest != '') {
        $ch = substr($rest, 0, 1);
        $rest = substr($rest, 1);
        if ($escaped) {
            $escaped = false;
            $term .= $ch;
            continue;
        }
        if ($ch == '\\') {
            $escaped = true;
            continue;
        }

        if ($ch == "'") {
            $inQuotes = !$inQuotes;
            continue;
        }
        if ($ch == '(') {
            ++$inParenthesis;
        } elseif ($ch == ')') {
            if (--$inParenthesis < 0) {
                die('Unbalanced parenthesis');
            }
        }
        if ($ch == ' ' && !$inQuotes && $inParenthesis == 0) {
            break;
        }
        $term .= $ch;
    }
    if ($inParenthesis > 0) {
        die('Unbalanced parenthesis');
    }
    if (substr($rest, 0, 4) == 'AND ') {
        $boolean = ' AND ';
        $searchTerms = substr($rest, 4);
    } elseif (substr($rest, 0, 3) == 'OR ') {
        $boolean = ' OR ';
        $searchTerms = substr($rest, 3);
    } else {
        $boolean = '';
        $searchTerms = '';
    }
    return $term != '';
}

/**
 * Create a 'where' clause
 *
 * @param array  $astrSearchFields Search fields
 * @param string $strSearchTerms   Search terms
 * @param array  $arrQueryParams   Query parameters
 * @param bool   $leftAnchored     Whether the search is anchored or in the middle
 *
 * @return string
 */
function createWhereClause($astrSearchFields, $strSearchTerms, &$arrQueryParams,
    $leftAnchored = false
) {
    $astrTerms = explode(' ', $strSearchTerms);
    $strWhereClause = '(';
    $termPrefix = $leftAnchored ? '' : '%';
    for ($i = 0; $i < count($astrTerms); $i ++) {
        if ($i > 0) {
            $termPrefix = '%';
        }
        if ($astrTerms[$i]) {
            $strWhereClause .= '(';
            for ($j = 0; $j < count($astrSearchFields); $j ++) {
                if ($astrSearchFields[$j]['type'] == 'TEXT') {
                    $strWhereClause .= $astrSearchFields[$j]['name'] . ' LIKE ? OR ';
                    $arrQueryParams[] = $termPrefix . $astrTerms[$i] . '%';
                } elseif ($astrSearchFields[$j]['type'] == 'INT'
                    && preg_match('/^([0-9]+)$/', $astrTerms[$i])
                ) {
                    $strWhereClause .= $astrSearchFields[$j]['name'] . ' = ?'
                        . ' OR ';
                    $arrQueryParams[] = $astrTerms[$i];
                } elseif ($astrSearchFields[$j]['type'] == 'PRIMARY'
                    && preg_match('/^([0-9]+)$/', $intID)
                ) {
                    $strWhereClause = 'WHERE ' . $astrSearchFields[$j]['name']
                        . ' = ? ';
                    $arrQueryParams = [$intID];
                    unset($astrSearchFields);
                    break 2;
                }
            }
            $strWhereClause = substr($strWhereClause, 0, -3) . ') AND ';
        }
    }
    $strWhereClause = substr($strWhereClause, 0, -4) . ')';
    return $strWhereClause;
}

/**
 * Update product stock balance for an invoice row
 *
 * @param int $invoiceRowId Invoice row ID
 * @param int $productId    Product ID
 * @param int $count        Count of items
 *
 * @return void
 */
function updateProductStockBalance($invoiceRowId, $productId, $count)
{
    // Get old product stock balance
    $oldProductId = false;
    $oldCount = 0;
    if (!empty($invoiceRowId)) {
        // Fetch old product id
        $rows = dbParamQuery(
            'SELECT product_id, pcs from {prefix}invoice_row WHERE id=?'
            . ' AND deleted=0',
            [$invoiceRowId],
            'exception'
        );
        if ($rows) {
            $oldProductId = $rows[0]['product_id'];
            $oldCount = $rows[0]['pcs'];
        }
    }

    if ($oldProductId) {
        // Add old balance to old product
        dbParamQuery(
            'UPDATE {prefix}product SET stock_balance=IFNULL(stock_balance, 0)+?'
            . ' WHERE id=?',
            [$oldCount, $oldProductId],
            'exception'
        );
    }
    if (!empty($productId)) {
        // Deduct from new product
        dbParamQuery(
            'UPDATE {prefix}product SET stock_balance=IFNULL(stock_balance, 0)-?'
            . ' WHERE id=?',
            [
                $count,
                $productId
            ],
            'exception'
        );
    }
}

/**
 * Get payment days for a company
 *
 * @param int $companyId Company ID
 *
 * @return int
 */
function getPaymentDays($companyId)
{
    if (!empty($companyId)) {
        $rows = dbParamQuery(
            'SELECT payment_days FROM {prefix}company WHERE id = ?',
            [$companyId]
        );
        if (!empty($rows[0]['payment_days'])) {
            return $rows[0]['payment_days'];
        }
    }
    return getSetting('invoice_payment_days');
}

/**
 * Check if an invoice record in an offer
 *
 * @param int $invoiceId Invoice ID
 *
 * @return bool
 */
function isOffer($invoiceId)
{
    $rows = dbParamQuery(
        'SELECT id FROM {prefix}invoice_state WHERE invoice_offer=1 AND id IN ('
        . 'SELECT state_id FROM {prefix}invoice WHERE id=?)',
        [$invoiceId]
    );
    return $rows ? true : false;
}

/**
 * Check if an invoice row belongs to an offer
 *
 * @param int $invoiceRowId Invoice row ID
 *
 * @return bool
 */
function isRowOfOffer($invoiceRowId)
{
    $rows = dbParamQuery(
        'SELECT id FROM {prefix}invoice_state WHERE invoice_offer=1 AND id IN ('
        . 'SELECT state_id FROM {prefix}invoice i'
        . ' INNER JOIN {prefix}invoice_row ir ON i.id = ir.invoice_id'
        . ' WHERE ir.id=?)',
        [$invoiceRowId]
    );
    return $rows ? true : false;
}

/**
 * Get the initial state for offers
 *
 * @return int
 */
function getInitialOfferState()
{
    $res = dbQueryCheck(
        'SELECT id FROM {prefix}invoice_state'
        . ' WHERE invoice_open=1 AND invoice_offer=1 AND invoice_offer_sent=0'
        . ' ORDER BY order_no'
    );
    $result = dbFetchValue($res) ?: 1;
    return $result;
}

/**
 * Get tags for a record
 *
 * @param string $type Record type (company, contact)
 * @param int    $id   Record ID
 *
 * @return string Comma-separated list of tags
 */
function getTags($type, $id)
{
    $tags = [];
    $rows = dbParamQuery(
        <<<EOT
SELECT tag FROM {prefix}${type}_tag WHERE id IN (
    SELECT tag_id FROM {prefix}${type}_tag_link WHERE ${type}_id=?
)
EOT
        ,
        [$id]
    );
    foreach ($rows as $tagRow) {
        $tags[] = $tagRow['tag'];
    }
    return implode(',', $tags);
}

/**
 * Save tags for a record
 *
 * @param string $type Record type (company, contact)
 * @param int    $id   Record ID
 * @param string $tags Comma-separated list of tags
 *
 * @return void
 */
function saveTags($type, $id, $tags)
{
    global $dblink;

    // Delete tag links
    dbParamQuery(
        "DELETE FROM {prefix}${type}_tag_link WHERE ${type}_id=?",
        [$id],
        'exception'
    );
    if ($tags) {
        // Save tags
        foreach (explode(',', $tags) as $tag) {
            $rows = dbParamQuery(
                "SELECT id FROM {prefix}${type}_tag WHERE tag=?",
                [$tag]
            );
            $tagId = $rows ? $rows[0]['id'] : null;
            if (null === $tagId) {
                dbParamQuery(
                    "INSERT INTO {prefix}${type}_tag (tag) VALUES (?)",
                    [$tag],
                    'exception'
                );
                $tagId = mysqli_insert_id($dblink);
            }
            dbParamQuery(
                "INSERT INTO {prefix}${type}_tag_link (tag_id, ${type}_id)"
                . ' VALUES (?, ?)',
                [$tagId, $id],
                'exception'
            );
        }
    }
    // Delete any orphaned tags
    dbParamQuery(
        <<<EOT
DELETE FROM {prefix}${type}_tag WHERE id NOT IN
    (SELECT tag_id FROM {prefix}${type}_tag_link)
EOT
        ,
        [],
        'exception'
    );
}

/**
 * Get a default value
 *
 * @param int  $id   Record ID
 * @param bool $full Whether to return full record or just the value
 *
 * @return mixed String, array if $full or null
 */
function getDefaultValue($id, $full = false)
{
    $rows = dbParamQuery(
        'SELECT * FROM {prefix}default_value WHERE id=?',
        [$id]
    );
    if (!$rows) {
        return null;
    }
    return $full ? $rows[0] : $rows[0]['content'];
}

/**
 * Get a product
 *
 * @param int $productId Product ID
 *
 * @return array
 */
function getProduct($productId)
{
    $rows = dbParamQuery(
        'SELECT * FROM {prefix}product WHERE id=?',
        [$productId]
    );
    return $rows ? $rows[0] : [];
}

/**
 * Get company specific price settings
 *
 * @param int $companyId Company ID
 *
 * @return array
 */
function getCustomPriceSettings($companyId)
{
    $rows = dbParamQuery(
        'SELECT * FROM {prefix}custom_price WHERE company_id=?',
        [$companyId]
    );
    if ($rows) {
        $result = $rows[0];
        $result['valid'] = empty($result['valid_until'])
            || $result['valid_until'] >= date('Ymd');
        return $result;
    }
    return [];
}

/**
 * Set company specific price settings
 *
 * @param int   $companyId  Company ID
 * @param float $discount   Discount percentage
 * @param float $multiplier Price multiplier
 * @param int   $validUntil Valid until date
 *
 * @return void
 */
function setCustomPriceSettings($companyId, $discount, $multiplier, $validUntil)
{
    $settings = getCustomPriceSettings($companyId);
    if ($settings) {
        dbParamQuery(
            'UPDATE {prefix}custom_price SET discount=?, multiplier=?'
            . ', valid_until=? WHERE id=?',
            [$discount, $multiplier, $validUntil, $settings['id']]
        );
    } else {
        dbParamQuery(
            'INSERT INTO {prefix}custom_price (company_id, discount, multiplier'
            . ', valid_until) VALUES (?, ?, ?, ?)',
            [$companyId, $discount, $multiplier, $validUntil]
        );
    }
}

/**
 * Delete company specific price settings
 *
 * @param int $companyId Company ID
 *
 * @return void
 */
function deleteCustomPriceSettings($companyId)
{
    dbParamQuery(
        'DELETE FROM {prefix}custom_price WHERE company_id=?', [$companyId]
    );
}

/**
 * Get company specific price for a product
 *
 * @param int $companyId Company ID
 * @param int $productId Product ID
 *
 * @return array
 */
function getCustomPrice($companyId, $productId)
{
    $rows = dbParamQuery(
        <<<EOT
SELECT * FROM {prefix}custom_price_map WHERE product_id=? AND custom_price_id IN (
    SELECT id FROM {prefix}custom_price WHERE company_id=?
)
EOT
        ,
        [$productId, $companyId]
    );
    return $rows ? $rows[0] : [];
}

/**
 * Set company specific product price
 *
 * @param int   $companyId Company ID
 * @param int   $productId Product ID
 * @param float $unitPrice Unit price
 *
 * @return void
 */
function setCustomPrice($companyId, $productId, $unitPrice)
{
    dbQueryCheck('BEGIN');
    try {
        $customPrices = getCustomPriceSettings($companyId);
        if (!$customPrices) {
            die('No custom prices defined for the company');
        }
        dbParamQuery(
            <<<EOT
DELETE FROM {prefix}custom_price_map WHERE product_id=? AND custom_price_id=?
EOT
            ,
            [$productId, $customPrices['id']]
        );
        dbParamQuery(
            <<<EOT
INSERT INTO {prefix}custom_price_map (custom_price_id, product_id, unit_price)
    VALUES (?, ?, ?)
EOT
            ,
            [$customPrices['id'], $productId, $unitPrice]
        );
    } catch (Exception $e) {
        dbQueryCheck('ROLLBACK');
        throw $e;
    }
    dbQueryCheck('COMMIT');
}

/**
 * Delete company specific product price
 *
 * @param int $companyId Company ID
 * @param int $productId Product ID
 *
 * @return void
 */
function deleteCustomPrice($companyId, $productId)
{
    $customPrices = getCustomPriceSettings($companyId);
    if (!$customPrices) {
        return;
    }
    dbParamQuery(
        <<<EOT
DELETE FROM {prefix}custom_price_map WHERE product_id=? AND custom_price_id=?
EOT
        ,
        [$productId, $customPrices['id']]
    );
}

/**
 * Get user by id
 *
 * @param string $id User id
 *
 * @return mixed array or null
 */
function getUserById($id)
{
    $rows = dbParamQuery(
        'SELECT * FROM {prefix}users WHERE id=?',
        [$id]
    );
    return $rows ? $rows[0] : null;
}

/**
 * Get user by login name or email
 *
 * @param string $userId User login name or email address
 *
 * @return mixed array or null
 */
function getUserByLoginId($userId)
{
    $rows = dbParamQuery(
        'SELECT * FROM {prefix}users WHERE login=? OR email=?',
        [$userId, $userId]
    );
    return $rows ? $rows[0] : null;
}

/**
 * Get user by token
 *
 * @param string $token User token
 *
 * @return mixed array or null
 */
function getUserByToken($token)
{
    $rows = dbParamQuery(
        'SELECT * FROM {prefix}users WHERE token=?',
        [$token]
    );
    return $rows ? $rows[0] : null;
}

/**
 * Update user's token
 *
 * @param int $id User id
 *
 * @return string
 */
function updateUserToken($id)
{
    $user = getUserById($id);
    if (!$user) {
        throw new Exception('User not found');
    }
    $token = sha1($user['id'] . $user['login'] . $user['passwd'] . rand())
        . str_pad(substr((string)time(), 0, 10), 10, '0', STR_PAD_LEFT);
    dbParamQuery(
        'UPDATE {prefix}users SET token=? WHERE id=?',
        [$token, $id]
    );
    return $token;
}

/**
 * Update user
 *
 * @param int   $id   User id
 * @param array $data User data
 *
 * @return void
 */
function updateUser($id, $data)
{
    $fields = [];
    $params = [];
    foreach ($data as $key => $value) {
        $fields[] = "$key = ?";
        $params[] = $value;
    }
    $params[] = $id;
    dbParamQuery(
        'UPDATE {prefix}users SET ' . implode(', ', $fields) . ' WHERE id=?',
        $params
    );
}

/**
 * Update user's password
 *
 * @param int    $id       User id
 * @param string $password New password
 *
 * @return void
 */
function updateUserPassword($id, $password)
{
    $user = getUserById($id);
    if (!$user) {
        throw new Exception('User not found');
    }
    dbParamQuery(
        'UPDATE {prefix}users SET passwd=? WHERE id=?',
        [password_hash($password, PASSWORD_DEFAULT), $id]
    );
}

/**
 * Get the maximum invoice number with the given arguments
 *
 * @param int  $invoiceId Invoice ID
 * @param int  $baseId    Base ID
 * @param bool $perYear   Whether to use year-based invoice numbering
 *
 * @return int
 */
function getMaxInvoiceNumber($invoiceId, $baseId, $perYear)
{
    if ($baseId !== null) {
        $sql = 'SELECT max(cast(invoice_no as unsigned integer)) as maxnum'
            . ' FROM {prefix}invoice WHERE deleted=0 AND id!=? AND base_id=?';
        $params = [
            $invoiceId,
            $baseId
        ];
    } else {
        $sql = 'SELECT max(cast(invoice_no as unsigned integer)) as maxnum'
            . ' FROM {prefix}invoice WHERE deleted=0 AND id!=?';
        $params = [$invoiceId];
    }
    if ($perYear) {
        $sql .= ' AND invoice_date >= ' . date('Y') . '0101';
    }
    $rows = dbParamQuery($sql, $params);
    return $rows[0]['maxnum'];
}

/**
 * Delete a record by ID
 *
 * @param string $table Table name
 * @param int    $id    Record ID
 *
 * @return void
 */
function deleteRecord($table, $id)
{
    dbQueryCheck('BEGIN');
    try {
        // Special case for invoice_row - update product stock balance
        if ($table == '{prefix}invoice_row' && !isRowOfOffer($id)) {
            updateProductStockBalance($id, null, null);
        }

        // Special case for invoice - update all products in invoice rows
        if ($table == '{prefix}invoice' && !isOffer($id)) {
            $rows = dbParamQuery(
                'SELECT id FROM {prefix}invoice_row WHERE invoice_id=?'
                    . ' AND deleted=0',
                [$id],
                'exception'
            );
            foreach ($rows as $row) {
                updateProductStockBalance($row['id'], null, null);
            }
        }
        $query = "UPDATE $table SET deleted=1 WHERE id=?";
        dbParamQuery($query, [$id], 'exception');
    } catch (Exception $e) {
        dbQueryCheck('ROLLBACK');
        throw $e;
    }
    dbQueryCheck('COMMIT');
}

/**
 * Perform a query and check the result but don't return any data
 *
 * @param string $query  SQL query
 * @param bool   $noFail Whether to return an error code instead of dying on error
 *
 * @return int
 */
function dbQueryCheck($query, $noFail = false)
{
    global $dblink;

    $query = str_replace('{prefix}', _DB_PREFIX_ . '_', $query);
    $startTime = microtime(true);
    $intRes = mysqli_query($dblink, $query);
    if (defined('_SQL_DEBUG_')) {
        error_log('QUERY [' . round(microtime(true) - $startTime, 4) . "s]: $query");
    }
    if ($intRes === false) {
        handleDbError($query, [], $noFail);
    }
    return $intRes;
}

/**
 * Perform a parameterized query
 *
 * @param string $query    Query string
 * @param array  $params   Query params
 * @param bool   $noFail   Whether to not abort execution
 * @param bool   $prefixed Whether to return the results in a prefixed array
 *
 * @return array
 */
function dbParamQuery($query, $params = [], $noFail = false, $prefixed = false)
{
    global $dblink;
    static $preparedStatements = [];

    if (!$dblink) {
        // We may need a reinit for e.g. session closure
        initDbConnection();
    }

    $query = str_replace('{prefix}', _DB_PREFIX_ . '_', $query);

    $queryType = strtoupper(strtok($query, ' '));
    if (true
        && in_array(
            $queryType,
            ['ALTER', 'BEGIN', 'COMMIT', 'ROLLBACK', 'LOCK', 'UNLOCK']
        )
    ) {
        if (!mysqli_query($dblink, $query)) {
            handleDbError($query, $params, $noFail);
            return false;
        }
        return true;
    }

    $hash = md5($query);
    if (isset($preparedStatements[$hash])) {
        $statement = $preparedStatements[$hash];
    } else {
        $statement = mysqli_stmt_init($dblink);
        if (!mysqli_stmt_prepare($statement, $query)) {
            handleDbError($query, $params, $noFail);
            return false;
        }
        $preparedStatements[$hash] = $statement;
    }
    if ($params) {
        $paramTypes = '';
        foreach ($params as &$v) {
            if (null === $v) {
                $paramTypes .= 's';
            } elseif (is_array($v)) {
                $v = implode(',', $v);
                $paramTypes .= 's';
            } elseif (is_bool($v)) {
                $v = $v ? '1' : '0';
                $paramTypes .= 'i';
            } else {
                $paramTypes .= 's';
            }
        }
        $paramRefs = [];
        foreach ($params as $key => $param) {
            $paramRefs[$key] = &$params[$key];
        }
        $bindParams = array_merge([$statement, $paramTypes], $paramRefs);
        call_user_func_array('mysqli_stmt_bind_param', $bindParams);
    }
    $startTime = microtime(true);
    $res = mysqli_stmt_execute($statement);
    if (defined('_SQL_DEBUG_')) {
        error_log(
            'QUERY [' . round(microtime(true) - $startTime, 4)
            . "s]: $query, params: " . var_export($params, true)
        );
    }
    if (!$res) {
        handleDbError($query, $params, $noFail);
        return false;
    }

    $results = [];
    if ('SELECT' === $queryType) {
        mysqli_stmt_store_result($statement);
        $resultMetadata = $statement->result_metadata();
        $fieldKeys = [];
        while ($field = $resultMetadata->fetch_field()) {
            if ($prefixed
                && strncmp(
                    $field->table, _DB_PREFIX_ . '_', strlen(_DB_PREFIX_) + 1
                ) != 0
            ) {
                $key = $field->table . '.' . $field->name;
            } else {
                $key = $field->name;
            }

            $fieldKeys[] = $key;
        }
        for ($i = 0; $i < $statement->num_rows; $i++) {
            $fields = [];
            foreach ($fieldKeys as $key) {
                $fields[] = &$results[$i][$key];
            }
            call_user_func_array([$statement, 'bind_result'], $fields);
            $statement->fetch();
        }
    }
    mysqli_stmt_free_result($statement);

    return $results;
}

/**
 * Handle a DB error
 *
 * @param string $query  Query string
 * @param array  $params Query params
 * @param bool   $noFail Whether to not abort execution
 *
 * @return void
 */
function handleDbError($query, $params, $noFail)
{
    global $dblink;
    $errno = mysqli_errno($dblink);
    if (strlen($query) > 1024) {
        $query = substr($query, 0, 1024) . '[' . (strlen($query) - 1024)
            . ' more characters]';
    }
    $errorMsg = "Query '$query' with params " . var_export($params, true)
        . " failed: ($errno) " . mysqli_error($dblink);

    error_log($errorMsg);
    if ($noFail !== true) {
        if (!headers_sent()) {
            header('HTTP/1.1 500 Internal Server Error');
        }
        $msg = (!defined('_DB_VERBOSE_ERRORS_') || !_DB_VERBOSE_ERRORS_)
            ? Translator::translate('DBError')
            : htmlspecialchars($errorMsg);
        if ($noFail == 'exception') {
            throw new Exception($msg);
        }
        die($msg);
    }
}

/**
 * Fetch a single value
 *
 * @param mysqli_result $result MySQLi result
 *
 * @return mixed
 */
function dbFetchValue($result)
{
    $row = mysqli_fetch_row($result);
    return isset($row[0]) ? $row[0] : null;
}

/**
 * Create a backup dump
 *
 * @return void
 */
function createDbDump()
{
    $in_tables = [
        'invoice_state',
        'row_type',
        'company_type',
        'base',
        'delivery_terms',
        'delivery_method',
        'company',
        'company_contact',
        'company_tag',
        'company_tag_link',
        'contact_tag',
        'contact_tag_link',
        'product',
        'session_type',
        'users',
        'stock_balance_log',
        'invoice',
        'invoice_row',
        'quicksearch',
        'settings',
        'session',
        'print_template',
        'default_value',
        'custom_price',
        'custom_price_map',
        'state'
    ];

    $filename = 'mlinvoice_backup_' . date('Ymd') . '.sql';
    header('Content-type: text/x-sql');
    header("Content-Disposition: attachment; filename=\"$filename\"");

    if (_CHARSET_ == 'UTF-8') {
        echo("SET NAMES 'utf8';\n\n");
    }

    echo "SET FOREIGN_KEY_CHECKS=0;\n\n";

    $tables = [];
    foreach ($in_tables as $table) {
        $tables[] = _DB_PREFIX_ . "_$table";
    }

    $res = dbQueryCheck("SHOW TABLES LIKE '" . _DB_PREFIX_ . "_%'");
    while ($row = mysqli_fetch_row($res)) {
        if (!in_array($row[0], $tables)) {
            error_log("Adding unlisted table $row[0] to export");
            $tables[] = $row[0];
        }
    }
    foreach ($tables as $table) {
        $res = dbQueryCheck("show create table $table");
        $row = mysqli_fetch_assoc($res);
        if (!$row) {
            die("Could not read table definition for table $table");
        }
        echo $row['Create Table'] . ";\n\n";

        $res = dbQueryCheck("show fields from $table");
        $field_count = mysqli_num_rows($res);
        $field_defs = [];
        $columns = '';
        while ($row = mysqli_fetch_assoc($res)) {
            $field_defs[] = $row;
            if ($columns) {
                $columns .= ', ';
            }
            $columns .= $row['Field'];
        }
        // Don't dump current sessions
        if ($table == _DB_PREFIX_ . '_session') {
            continue;
        }

        $res = dbQueryCheck("select * from $table");
        while ($row = mysqli_fetch_row($res)) {
            echo "INSERT INTO `$table` ($columns) VALUES (";
            for ($i = 0; $i < $field_count; $i ++) {
                if ($i > 0) {
                    echo ', ';
                }
                $value = $row[$i];
                $type = $field_defs[$i]['Type'];
                if (is_null($value)) {
                    echo 'null';
                } elseif (substr($type, 0, 3) == 'int'
                    || substr($type, 0, 7) == 'decimal'
                ) {
                    echo $value;
                } elseif ($value && ($type == 'longblob' || strpos($value, "\n"))) {
                    echo '0x' . bin2hex($value);
                } else {
                    echo '\'' . addslashes($value) . '\'';
                }
            }
            echo ");\n";
        }
        echo "\n";
    }
    echo "\nSET FOREIGN_KEY_CHECKS=1;\n";
}

/**
 * Check if a table name is valid
 *
 * @param string $table Table name without prefix
 *
 * @return bool
 */
function tableNameValid($table)
{
    $table = _DB_PREFIX_ . "_$table";
    $res = dbQueryCheck('SHOW TABLES');
    while ($row = mysqli_fetch_row($res)) {
        if ($table == $row[0]) {
            return true;
        }
    }
    return false;
}

/**
 * Verify database status and upgrade as necessary.
 * Expects all pre-1.6.0 changes to have been already made.
 *
 * @return string status (OK|UPGRADED|FAILED)
 */
function verifyDatabase()
{
    // phpcs:disable Generic.Files.LineLength
    $res = dbQueryCheck("SHOW TABLES LIKE '{prefix}state'");
    $stateRows = mysqli_num_rows($res);
    if ($stateRows == 0) {
        $res = dbQueryCheck(
            <<<EOT
CREATE TABLE {prefix}state (
  id char(32) NOT NULL,
  data varchar(100) NULL,
  PRIMARY KEY (id)
) ENGINE=INNODB CHARACTER SET utf8 COLLATE utf8_swedish_ci;
EOT
            ,
            true
        );
        if ($res === false) {
            return 'FAILED';
        }
        dbQueryCheck(
            "REPLACE INTO {prefix}state (id, data) VALUES ('version', '15')"
        );
    }

    // Convert any MyISAM tables to InnoDB
    $rows = dbParamQuery(
        'SELECT data FROM {prefix}state WHERE id=?', ['tableconversiondone']
    );
    if (!$rows) {
        dbQueryCheck('SET AUTOCOMMIT = 0');
        dbQueryCheck('BEGIN');
        dbQueryCheck('SET FOREIGN_KEY_CHECKS = 0');
        $res = dbQueryCheck(
            "SHOW TABLE STATUS WHERE Name like '" . _DB_PREFIX_
            . "_%' AND ENGINE='MyISAM'"
        );
        while ($row = mysqli_fetch_array($res)) {
            $res2 = dbQueryCheck(
                'ALTER TABLE `' . $row['Name'] . '` ENGINE=INNODB', true
            );
            if ($res2 === false) {
                dbQueryCheck('ROLLBACK');
                dbQueryCheck('SET FOREIGN_KEY_CHECKS = 1');
                error_log(
                    'Database upgrade query failed. Please convert the tables using'
                    . ' MyISAM engine to InnoDB engine manually'
                );
                return 'FAILED';
            }
        }
        dbQueryCheck(
            'INSERT INTO {prefix}state (id, data) VALUES'
            . " ('tableconversiondone', '1')"
        );
        dbQueryCheck('COMMIT');
        dbQueryCheck('SET AUTOCOMMIT = 1');
        dbQueryCheck('SET FOREIGN_KEY_CHECKS = 1');
    }

    $rows = dbParamQuery(
        'SELECT data FROM {prefix}state WHERE id=?', ['version']
    );
    $version = $rows ? $rows[0]['data'] : 0;
    $updates = [];
    if ($version < 16) {
        $updates = array_merge(
            $updates,
            [
                'ALTER TABLE {prefix}invoice ADD CONSTRAINT FOREIGN KEY (base_id) REFERENCES {prefix}base(id)',
                'ALTER TABLE {prefix}invoice ADD COLUMN interval_type int(11) NOT NULL default 0',
                'ALTER TABLE {prefix}invoice ADD COLUMN next_interval_date int(11) default NULL',
                "REPLACE INTO {prefix}state (id, data) VALUES ('version', '16')"
            ]
        );
    }
    if ($version < 17) {
        $updates = array_merge(
            $updates,
            [
                'ALTER TABLE {prefix}invoice_state CHANGE COLUMN name name varchar(255)',
                "UPDATE {prefix}invoice_state set name='StateOpen' where id=1",
                "UPDATE {prefix}invoice_state set name='StateSent' where id=2",
                "UPDATE {prefix}invoice_state set name='StatePaid' where id=3",
                "UPDATE {prefix}invoice_state set name='StateAnnulled' where id=4",
                "UPDATE {prefix}invoice_state set name='StateFirstReminder' where id=5",
                "UPDATE {prefix}invoice_state set name='StateSecondReminder' where id=6",
                "UPDATE {prefix}invoice_state set name='StateDebtCollection' where id=7",
                "UPDATE {prefix}print_template set name='PrintInvoiceFinnish' where name='Lasku'",
                "UPDATE {prefix}print_template set name='PrintDispatchNoteFinnish' where name='Lähetysluettelo'",
                "UPDATE {prefix}print_template set name='PrintReceiptFinnish' where name='Kuitti'",
                "UPDATE {prefix}print_template set name='PrintEmailFinnish' where name='Email'",
                "UPDATE {prefix}print_template set name='PrintInvoiceEnglish' where name='Invoice'",
                "UPDATE {prefix}print_template set name='PrintReceiptEnglish' where name='Receipt'",
                "UPDATE {prefix}print_template set name='PrintFinvoice' where name='Finvoice'",
                "UPDATE {prefix}print_template set name='PrintFinvoiceStyled' where name='Finvoice Styled'",
                "UPDATE {prefix}print_template set name='PrintInvoiceFinnishWithVirtualBarcode' where name='Lasku virtuaaliviivakoodilla'",
                "UPDATE {prefix}print_template set name='PrintInvoiceFinnishFormless' where name='Lomakkeeton lasku'",
                "INSERT INTO {prefix}print_template (name, filename, parameters, output_filename, type, order_no, inactive)"
                . " VALUES ('PrintInvoiceEnglishWithVirtualBarcode', 'invoice_printer.php', 'invoice,en,Y', 'invoice_%d.pdf', 'invoice', 70, 1)",
                "INSERT INTO {prefix}print_template (name, filename, parameters, output_filename, type, order_no, inactive)"
                . " VALUES ('PrintInvoiceEnglishFormless', 'invoice_printer_formless.php', 'invoice,en,N', 'invoice_%d.pdf', 'invoice', 80, 1)",
                'ALTER TABLE {prefix}row_type CHANGE COLUMN name name varchar(255)',
                "UPDATE {prefix}row_type set name='TypeHour' where name='h'",
                "UPDATE {prefix}row_type set name='TypeDay' where name='pv'",
                "UPDATE {prefix}row_type set name='TypeMonth' where name='kk'",
                "UPDATE {prefix}row_type set name='TypePieces' where name='kpl'",
                "UPDATE {prefix}row_type set name='TypeYear' where name='vuosi'",
                "UPDATE {prefix}row_type set name='TypeLot' where name='erä'",
                "UPDATE {prefix}row_type set name='TypeKilometer' where name='km'",
                "UPDATE {prefix}row_type set name='TypeKilogram' where name='kg'",
                "REPLACE INTO {prefix}state (id, data) VALUES ('version', '17')"
            ]
        );
    }
    if ($version < 18) {
        $updates = array_merge(
            $updates,
            [
                'ALTER TABLE {prefix}base ADD COLUMN country varchar(255) default NULL',
                'ALTER TABLE {prefix}company ADD COLUMN country varchar(255) default NULL',
                "REPLACE INTO {prefix}state (id, data) VALUES ('version', '18')"
            ]
        );
    }
    if ($version < 19) {
        $updates = array_merge(
            $updates,
            [
                "UPDATE {prefix}session_type set name='SessionTypeUser' where name='Käyttäjä'",
                "UPDATE {prefix}session_type set name='SessionTypeAdmin' where name='Ylläpitäjä'",
                "UPDATE {prefix}session_type set name='SessionTypeBackupUser' where name='Käyttäjä - varmuuskopioija'",
                "UPDATE {prefix}session_type set name='SessionTypeReadOnly' where name='Vain laskujen ja raporttien tarkastelu'",
                "REPLACE INTO {prefix}state (id, data) VALUES ('version', '19')"
            ]
        );
    }
    if ($version < 20) {
        $updates = array_merge(
            $updates,
            [
                'ALTER TABLE {prefix}product CHANGE COLUMN unit_price unit_price decimal(15,5)',
                'ALTER TABLE {prefix}invoice_row CHANGE COLUMN price price decimal(15,5)',
                'ALTER TABLE {prefix}product CHANGE COLUMN discount discount decimal(4,1) NULL',
                'ALTER TABLE {prefix}invoice_row CHANGE COLUMN discount discount decimal(4,1) NULL',
                "REPLACE INTO {prefix}state (id, data) VALUES ('version', '20')"
            ]
        );
    }
    if ($version < 21) {
        $updates = array_merge(
            $updates,
            [
                "INSERT INTO {prefix}print_template (name, filename, parameters, output_filename, type, order_no, inactive)"
                . " VALUES ('PrintInvoiceSwedish', 'invoice_printer.php', 'invoice,sv-FI,Y', 'faktura_%d.pdf', 'invoice', 90, 1)",
                "INSERT INTO {prefix}print_template (name, filename, parameters, output_filename, type, order_no, inactive)"
                . " VALUES ('PrintInvoiceSwedishFormless', 'invoice_printer_formless.php', 'invoice,sv-FI,N', 'faktura_%d.pdf', 'invoice', 100, 1)",
                "REPLACE INTO {prefix}state (id, data) VALUES ('version', '21')"
            ]
        );
    }
    if ($version < 22) {
        $updates = array_merge(
            $updates,
            [
                "INSERT INTO {prefix}print_template (name, filename, parameters, output_filename, type, order_no, inactive)"
                . " VALUES ('PrintEmailReceiptFinnish', 'invoice_printer_email.php', 'receipt', 'kuitti_%d.pdf', 'invoice', 110, 1)",
                "INSERT INTO {prefix}print_template (name, filename, parameters, output_filename, type, order_no, inactive)"
                . " VALUES ('PrintEmailReceiptSwedish', 'invoice_printer_email.php', 'receipt,sv-FI', 'kvitto_%d.pdf', 'invoice', 120, 1)",
                "INSERT INTO {prefix}print_template (name, filename, parameters, output_filename, type, order_no, inactive)"
                . " VALUES ('PrintEmailReceiptEnglish', 'invoice_printer_email.php', 'receipt,en', 'receipt_%d.pdf', 'invoice', 130, 1)",
                "REPLACE INTO {prefix}state (id, data) VALUES ('version', '22')"
            ]
        );
    }
    if ($version < 23) {
        $updates = array_merge(
            $updates,
            [
                'ALTER TABLE {prefix}product ADD COLUMN order_no int(11) default NULL',
                'ALTER TABLE {prefix}users CHANGE COLUMN name name varchar(255)',
                'ALTER TABLE {prefix}users CHANGE COLUMN login login varchar(255)',
                "REPLACE INTO {prefix}state (id, data) VALUES ('version', '23')"
            ]
        );
    }
    if ($version < 24) {
        $updates = array_merge(
            $updates,
            [
                "INSERT INTO {prefix}print_template (name, filename, parameters, output_filename, type, order_no, inactive)"
                . " VALUES ('PrintOrderConfirmationFinnish', 'invoice_printer_order_confirmation.php', 'receipt', 'tilausvahvistus_%d.pdf', 'invoice', 140, 1)",
                "INSERT INTO {prefix}print_template (name, filename, parameters, output_filename, type, order_no, inactive)"
                . " VALUES ('PrintOrderConfirmationSwedish', 'invoice_printer_order_confirmation.php', 'receipt,sv-FI', 'orderbekraftelse_%d.pdf', 'invoice', 150, 1)",
                "INSERT INTO {prefix}print_template (name, filename, parameters, output_filename, type, order_no, inactive)"
                . " VALUES ('PrintOrderConfirmationEnglish', 'invoice_printer_order_confirmation.php', 'receipt,en', 'order_confirmation_%d.pdf', 'invoice', 160, 1)",
                "REPLACE INTO {prefix}state (id, data) VALUES ('version', '24')"
            ]
        );
    }
    if ($version < 25) {
        $updates = array_merge(
            $updates,
            [
                <<<EOT
CREATE TABLE {prefix}delivery_terms (
  id int(11) NOT NULL auto_increment,
  deleted tinyint NOT NULL default 0,
  name varchar(255) default NULL,
  order_no int(11) default NULL,
  PRIMARY KEY (id)
) ENGINE=INNODB CHARACTER SET utf8 COLLATE utf8_swedish_ci
EOT
                ,
                <<<EOT
CREATE TABLE {prefix}delivery_method (
  id int(11) NOT NULL auto_increment,
  deleted tinyint NOT NULL default 0,
  name varchar(255) default NULL,
  order_no int(11) default NULL,
  PRIMARY KEY (id)
) ENGINE=INNODB CHARACTER SET utf8 COLLATE utf8_swedish_ci
EOT
                ,
                'ALTER TABLE {prefix}invoice ADD COLUMN delivery_terms_id int(11) default NULL',
                'ALTER TABLE {prefix}invoice ADD CONSTRAINT FOREIGN KEY (delivery_terms_id) REFERENCES {prefix}delivery_terms(id)',
                'ALTER TABLE {prefix}invoice ADD COLUMN delivery_method_id int(11) default NULL',
                'ALTER TABLE {prefix}invoice ADD CONSTRAINT FOREIGN KEY (delivery_method_id) REFERENCES {prefix}delivery_method(id)',
                'ALTER TABLE {prefix}company ADD COLUMN delivery_terms_id int(11) default NULL',
                'ALTER TABLE {prefix}company ADD CONSTRAINT FOREIGN KEY (delivery_terms_id) REFERENCES {prefix}delivery_terms(id)',
                'ALTER TABLE {prefix}company ADD COLUMN delivery_method_id int(11) default NULL',
                'ALTER TABLE {prefix}company ADD CONSTRAINT FOREIGN KEY (delivery_method_id) REFERENCES {prefix}delivery_method(id)',
                "REPLACE INTO {prefix}state (id, data) VALUES ('version', '25')"
            ]
        );
    }

    if ($version < 26) {
        $updates = array_merge(
            $updates,
            [
                'CREATE INDEX {prefix}company_name on {prefix}company(company_name)',
                'CREATE INDEX {prefix}company_id on {prefix}company(company_id)',
                'CREATE INDEX {prefix}company_deleted on {prefix}company(deleted)',
                'CREATE INDEX {prefix}invoice_no on {prefix}invoice(invoice_no)',
                'CREATE INDEX {prefix}invoice_ref_number on {prefix}invoice(ref_number)',
                'CREATE INDEX {prefix}invoice_name on {prefix}invoice(name)',
                'CREATE INDEX {prefix}invoice_deleted on {prefix}invoice(deleted)',
                'CREATE INDEX {prefix}base_name on {prefix}base(name)',
                'CREATE INDEX {prefix}base_deleted on {prefix}base(deleted)',
                'CREATE INDEX {prefix}product_name on {prefix}product(product_name)',
                'CREATE INDEX {prefix}product_code on {prefix}product(product_code)',
                'CREATE INDEX {prefix}product_deleted on {prefix}product(deleted)',
                'CREATE INDEX {prefix}product_order_no_deleted on {prefix}product(order_no, deleted)',
                'CREATE INDEX {prefix}users_name on {prefix}users(name)',
                'CREATE INDEX {prefix}users_deleted on {prefix}users(deleted)',
                "REPLACE INTO {prefix}state (id, data) VALUES ('version', '26')"
            ]
        );
    }

    if ($version < 27) {
        $updates = array_merge(
            $updates,
            [
                "INSERT INTO {prefix}invoice_state (name, order_no) VALUES ('StatePaidInCash', 17)",
                "REPLACE INTO {prefix}state (id, data) VALUES ('version', '27')"
            ]
        );
    }

    if ($version < 28) {
        $updates = array_merge(
            $updates,
            [
                "INSERT INTO {prefix}print_template (name, filename, parameters, output_filename, type, order_no, inactive)"
                . " VALUES ('PrintOrderConfirmationEmailFinnish', 'invoice_printer_order_confirmation_email.php', 'receipt', 'tilausvahvistus_%d.pdf', 'invoice', 170, 1)",
                "INSERT INTO {prefix}print_template (name, filename, parameters, output_filename, type, order_no, inactive)"
                . " VALUES ('PrintOrderConfirmationEmailSwedish', 'invoice_printer_order_confirmation_email.php', 'receipt,sv-FI', 'orderbekraftelse_%d.pdf', 'invoice', 180, 1)",
                "INSERT INTO {prefix}print_template (name, filename, parameters, output_filename, type, order_no, inactive)"
                . " VALUES ('PrintOrderConfirmationEmailEnglish', 'invoice_printer_order_confirmation_email.php', 'receipt,en', 'order_confirmation_%d.pdf', 'invoice', 190, 1)",
                "REPLACE INTO {prefix}state (id, data) VALUES ('version', '28')"
            ]
        );
    }

    if ($version < 29) {
        $updates = array_merge(
            $updates,
            [
                'ALTER TABLE {prefix}session CHANGE COLUMN id id varchar(255)',
                "REPLACE INTO {prefix}state (id, data) VALUES ('version', '29')"
            ]
        );
    }

    if ($version < 30) {
        $updates = array_merge(
            $updates,
            [
                'ALTER TABLE {prefix}base ADD COLUMN payment_intermediator varchar(100) default NULL',
                'ALTER TABLE {prefix}company ADD COLUMN payment_intermediator varchar(100) default NULL',
                "INSERT INTO {prefix}print_template (name, filename, parameters, output_filename, type, order_no, inactive)"
                . " VALUES ('PrintFinvoiceSOAP', 'invoice_printer_finvoice_soap.php', '', 'finvoice_%d.xml', 'invoice', 55, 1)",
                "REPLACE INTO {prefix}state (id, data) VALUES ('version', '30')"
            ]
        );
    }

    if ($version < 31) {
        $updates = array_merge(
            $updates,
            [
                'ALTER TABLE {prefix}product ADD COLUMN ean_code1 varchar(13) default NULL',
                'ALTER TABLE {prefix}product ADD COLUMN ean_code2 varchar(13) default NULL',
                "REPLACE INTO {prefix}state (id, data) VALUES ('version', '31')"
            ]
        );
    }

    if ($version < 32) {
        $updates = array_merge(
            $updates,
            [
                'ALTER TABLE {prefix}product ADD COLUMN purchase_price decimal(15,5) NULL',
                'ALTER TABLE {prefix}product ADD COLUMN stock_balance int(11) default NULL',
                <<<EOT
CREATE TABLE {prefix}stock_balance_log (
  id int(11) NOT NULL auto_increment,
  time timestamp NOT NULL default CURRENT_TIMESTAMP,
  user_id int(11) NOT NULL,
  product_id int(11) NOT NULL,
  stock_change int(11) NOT NULL,
  description varchar(255) NOT NULL,
  PRIMARY KEY (id),
  FOREIGN KEY (user_id) REFERENCES {prefix}users(id),
  FOREIGN KEY (product_id) REFERENCES {prefix}product(id)
) ENGINE=INNODB CHARACTER SET utf8 COLLATE utf8_swedish_ci
EOT
                ,
                "REPLACE INTO {prefix}state (id, data) VALUES ('version', '32')"
            ]
        );
    }

    if ($version < 33) {
        $updates = array_merge(
            $updates,
            [
                'ALTER TABLE {prefix}base ADD COLUMN receipt_email_subject varchar(255) NULL',
                'ALTER TABLE {prefix}base ADD COLUMN receipt_email_body text NULL',
                "REPLACE INTO {prefix}state (id, data) VALUES ('version', '33')"
            ]
        );
    }

    if ($version < 34) {
        $updates = array_merge(
            $updates,
            [
                'ALTER TABLE {prefix}product CHANGE COLUMN stock_balance stock_balance decimal(11,2) default NULL',
                'ALTER TABLE {prefix}stock_balance_log CHANGE COLUMN stock_change stock_change decimal(11,2) default NULL',
                "REPLACE INTO {prefix}state (id, data) VALUES ('version', '34')"
            ]
        );
    }

    if ($version < 35) {
        $updates = array_merge(
            $updates,
            [
                'ALTER TABLE {prefix}invoice_state ADD COLUMN invoice_open tinyint NOT NULL default 0',
                'ALTER TABLE {prefix}invoice_state ADD COLUMN invoice_unpaid tinyint NOT NULL default 0',
                'UPDATE {prefix}invoice_state SET invoice_open=1 WHERE id IN (1)',
                'UPDATE {prefix}invoice_state SET invoice_unpaid=1 WHERE id IN (2, 5, 6, 7)',
                "REPLACE INTO {prefix}state (id, data) VALUES ('version', '35')"
            ]
        );
    }

    if ($version < 36) {
        $updates = array_merge(
            $updates,
            [
                'ALTER TABLE {prefix}product CHANGE COLUMN ean_code1 barcode1 varchar(255) default NULL',
                'ALTER TABLE {prefix}product CHANGE COLUMN ean_code2 barcode2 varchar(255) default NULL',
                'ALTER TABLE {prefix}product ADD COLUMN barcode1_type varchar(20) default NULL',
                'ALTER TABLE {prefix}product ADD COLUMN barcode2_type varchar(20) default NULL',
                "UPDATE {prefix}product SET barcode1_type='EAN13' WHERE barcode1 IS NOT NULL",
                "UPDATE {prefix}product SET barcode2_type='EAN13' WHERE barcode2 IS NOT NULL",
                'ALTER TABLE {prefix}base ADD COLUMN order_confirmation_email_subject varchar(255) NULL',
                'ALTER TABLE {prefix}base ADD COLUMN order_confirmation_email_body text NULL',
                "REPLACE INTO {prefix}state (id, data) VALUES ('version', '36')"
            ]
        );
    }

    if ($version < 37) {
        $updates = array_merge(
            $updates,
            [
                'ALTER TABLE {prefix}company ADD COLUMN payment_days int(11) default NULL',
                'ALTER TABLE {prefix}company ADD COLUMN terms_of_payment varchar(255) NULL',
                "REPLACE INTO {prefix}state (id, data) VALUES ('version', '37')"
            ]
        );
    }

    if ($version < 38) {
        $updates = array_merge(
            $updates,
            [
                'UPDATE {prefix}invoice_row ir SET ir.row_date=(SELECT i.invoice_date'
                . ' FROM {prefix}invoice i where i.id=ir.invoice_id) WHERE ir.row_date IS NULL',
                "REPLACE INTO {prefix}state (id, data) VALUES ('version', '38')"
            ]
        );
    }

    if ($version < 39) {
        // Check for a bug in database creation script in v1.12.0 and v1.12.1
        $rows = dbParamQuery(
            "SELECT count(*) as cnt FROM information_schema.columns WHERE table_schema = '"
            . _DB_NAME_ . "' AND table_name   = '{prefix}invoice_row' AND column_name = 'partial_payment'"
        );
        $count = $rows[0]['cnt'];
        if ($count == 0) {
            $updates = array_merge(
                $updates,
                [
                    'ALTER TABLE {prefix}invoice_row ADD COLUMN partial_payment tinyint NOT NULL default 0',
                    "REPLACE INTO {prefix}state (id, data) VALUES ('version', '39')"
                ]
            );
        }
    }

    if ($version < 40) {
        $updates = array_merge(
            $updates,
            [
                'UPDATE {prefix}invoice_state SET invoice_unpaid=1 WHERE id=1',
                "REPLACE INTO {prefix}state (id, data) VALUES ('version', '40')"
            ]
        );
    }

    if ($version < 41) {
        $updates = array_merge(
            $updates,
            [
                'ALTER TABLE {prefix}base ADD COLUMN invoice_default_info text NULL',
                "REPLACE INTO {prefix}state (id, data) VALUES ('version', '41')"
            ]
        );
    }

    if ($version < 42) {
        $updates = array_merge(
            $updates,
            [
                'ALTER TABLE {prefix}invoice_state ADD COLUMN invoice_offer tinyint NOT NULL default 0',
                'ALTER TABLE {prefix}invoice_state ADD COLUMN invoice_offer_sent tinyint NOT NULL default 0',
                "INSERT INTO {prefix}invoice_state (name, order_no, invoice_open, invoice_unpaid, invoice_offer)"
                . " VALUES ('StateOfferOpen', 40, 1, 0, 1)",
                "INSERT INTO {prefix}invoice_state (name, order_no, invoice_open, invoice_unpaid, invoice_offer, invoice_offer_sent)"
                . " VALUES ('StateOfferSent', 45, 1, 0, 1, 1)",
                "INSERT INTO {prefix}invoice_state (name, order_no, invoice_open, invoice_unpaid, invoice_offer, invoice_offer_sent)"
                . " VALUES ('StateOfferUnrealised', 50, 0, 0, 1, 1)",
                "INSERT INTO {prefix}print_template (name, filename, parameters, output_filename, type, order_no, inactive)"
                . " VALUES ('PrintOfferFinnish', 'invoice_printer_offer.php', 'offer', 'tarjous_%d.pdf', 'offer', 200, 1)",
                "INSERT INTO {prefix}print_template (name, filename, parameters, output_filename, type, order_no, inactive)"
                . " VALUES ('PrintOfferSwedish', 'invoice_printer_offer.php', 'offer,sv-FI', 'anbud_%d.pdf', 'offer', 210, 1)",
                "INSERT INTO {prefix}print_template (name, filename, parameters, output_filename, type, order_no, inactive)"
                . " VALUES ('PrintOfferEnglish', 'invoice_printer_offer.php', 'offer,en', 'offer_%d.pdf', 'offer', 220, 1)",
                "INSERT INTO {prefix}print_template (name, filename, parameters, output_filename, type, order_no, inactive)"
                . " VALUES ('PrintOfferEmailFinnish', 'invoice_printer_offer_email.php', 'offer', 'tarjous_%d.pdf', 'offer', 230, 1)",
                "INSERT INTO {prefix}print_template (name, filename, parameters, output_filename, type, order_no, inactive)"
                . " VALUES ('PrintOfferEmailSwedish', 'invoice_printer_offer_email.php', 'offer,sv-FI', 'anbud_%d.pdf', 'offer', 240, 1)",
                "INSERT INTO {prefix}print_template (name, filename, parameters, output_filename, type, order_no, inactive)"
                . " VALUES ('PrintOfferEmailEnglish', 'invoice_printer_offer_email.php', 'offer,en', 'offer_%d.pdf', 'offer', 250, 1)",
                'ALTER TABLE {prefix}base ADD COLUMN offer_email_subject varchar(255) NULL',
                'ALTER TABLE {prefix}base ADD COLUMN offer_email_body text NULL',
                "REPLACE INTO {prefix}state (id, data) VALUES ('version', '42')"
            ]
        );
    }

    if ($version < 43) {
        $updates = array_merge(
            $updates,
            [
                'ALTER TABLE {prefix}company_contact ADD COLUMN contact_type VARCHAR(100) NULL',
                "INSERT INTO {prefix}invoice_state (name, order_no, invoice_open, invoice_unpaid, invoice_offer, invoice_offer_sent)"
                . " VALUES ('StateOfferRealised', 55, 0, 0, 1, 1)",
                'ALTER TABLE {prefix}base ADD COLUMN invoice_default_foreword text NULL',
                'ALTER TABLE {prefix}base ADD COLUMN invoice_default_afterword text NULL',
                'ALTER TABLE {prefix}base ADD COLUMN offer_default_foreword text NULL',
                'ALTER TABLE {prefix}base ADD COLUMN offer_default_afterword text NULL',
                'ALTER TABLE {prefix}invoice ADD COLUMN foreword text NULL',
                'ALTER TABLE {prefix}invoice ADD COLUMN afterword text NULL',
                "REPLACE INTO {prefix}state (id, data) VALUES ('version', '43')"
            ]
        );
    }

    if ($version < 44) {
        $updates = array_merge(
            $updates,
            [
                'ALTER TABLE {prefix}invoice ADD COLUMN delivery_time varchar(100) default NULL',
                "REPLACE INTO {prefix}state (id, data) VALUES ('version', '44')"
            ]
        );
    }

    if ($version < 45) {
        $updates = array_merge(
            $updates,
            [
                <<<EOT
CREATE TABLE {prefix}default_value (
  id int(11) NOT NULL auto_increment,
  deleted tinyint NOT NULL default 0,
  name varchar(255) default NULL,
  order_no int(11) default NULL,
  type varchar(100) NULL,
  content text NULL,
  PRIMARY KEY (id)
) ENGINE=INNODB CHARACTER SET utf8 COLLATE utf8_swedish_ci;
EOT
                ,
                "REPLACE INTO {prefix}state (id, data) VALUES ('version', '45')"
            ]
        );
    }

    if ($version < 46) {
        $updates = array_merge(
            $updates,
            [
                'ALTER TABLE {prefix}base ADD COLUMN terms_of_payment varchar(255) NULL',
                'ALTER TABLE {prefix}base ADD COLUMN period_for_complaints varchar(255) NULL',
                "REPLACE INTO {prefix}state (id, data) VALUES ('version', '46')"
            ]
        );
    }

    if ($version < 47) {
        $updates = array_merge(
            $updates,
            [
                "UPDATE {prefix}print_template SET type='offer' WHERE filename LIKE 'invoice_printer_offer%'",
                "REPLACE INTO {prefix}state (id, data) VALUES ('version', '47')"
            ]
        );
    }

    if ($version < 48) {
        $updates = array_merge(
            $updates,
            [
                'ALTER TABLE {prefix}product ADD COLUMN vendor varchar(255) NULL',
                'ALTER TABLE {prefix}product ADD COLUMN vendors_code varchar(100) NULL',
                "REPLACE INTO {prefix}state (id, data) VALUES ('version', '48')"
            ]
        );
    }

    if ($version < 49) {
        $updates = array_merge(
            $updates,
            [
                <<<EOT
CREATE TABLE {prefix}company_tag (
  id int(11) NOT NULL auto_increment,
  tag varchar(100) default NULL,
  PRIMARY KEY (id)
) ENGINE=INNODB CHARACTER SET utf8 COLLATE utf8_swedish_ci
EOT
                , <<<EOT
CREATE TABLE {prefix}company_tag_link (
  id int(11) NOT NULL auto_increment,
  tag_id int(11) NOT NULL,
  company_id int(11) NOT NULL,
  PRIMARY KEY (id),
  FOREIGN KEY (tag_id) REFERENCES {prefix}company_tag(id),
  FOREIGN KEY (company_id) REFERENCES {prefix}company(id)
) ENGINE=INNODB CHARACTER SET utf8 COLLATE utf8_swedish_ci
EOT
                , <<<EOT
CREATE TABLE {prefix}contact_tag (
  id int(11) NOT NULL auto_increment,
  tag varchar(100) default NULL,
  PRIMARY KEY (id)
) ENGINE=INNODB CHARACTER SET utf8 COLLATE utf8_swedish_ci;
EOT
                , <<<EOT
CREATE TABLE {prefix}contact_tag_link (
  id int(11) NOT NULL auto_increment,
  tag_id int(11) NOT NULL,
  contact_id int(11) NOT NULL,
  PRIMARY KEY (id),
  FOREIGN KEY (tag_id) REFERENCES {prefix}contact_tag(id),
  FOREIGN KEY (contact_id) REFERENCES {prefix}company_contact(id)
) ENGINE=INNODB CHARACTER SET utf8 COLLATE utf8_swedish_ci;
EOT
                ,
                "REPLACE INTO {prefix}state (id, data) VALUES ('version', '49')"
            ]
        );
    }

    if ($version < 50) {
        $updates = array_merge(
            $updates,
            [
                'ALTER TABLE {prefix}product ADD COLUMN discount_amount decimal(15,5) NULL',
                'ALTER TABLE {prefix}invoice_row ADD COLUMN discount_amount decimal(15,5) NULL',
                "REPLACE INTO {prefix}state (id, data) VALUES ('version', '50')"
            ]
        );
    }

    if ($version < 51) {
        $updates = array_merge(
            $updates,
            [
                'ALTER TABLE {prefix}base CHANGE COLUMN email email varchar(512) default NULL',
                'ALTER TABLE {prefix}company CHANGE COLUMN email email varchar(512) default NULL',
                'ALTER TABLE {prefix}company_contact CHANGE COLUMN email email varchar(512) default NULL',
                'ALTER TABLE {prefix}users CHANGE COLUMN email email varchar(512) default NULL',
                "REPLACE INTO {prefix}state (id, data) VALUES ('version', '51')"
            ]
        );
    }

    if ($version < 52) {
        $updates = array_merge(
            $updates,
            [
                'ALTER TABLE {prefix}base ADD COLUMN inactive tinyint NOT NULL default 0',
                "REPLACE INTO {prefix}state (id, data) VALUES ('version', '52')"
            ]
        );
    }

    if ($version < 53) {
        $updates = array_merge(
            $updates,
            [
                'ALTER TABLE {prefix}invoice ADD COLUMN uuid varchar(50) default NULL',
                "REPLACE INTO {prefix}state (id, data) VALUES ('version', '53')"
            ]
        );
    }

    if ($version < 54) {
        $updates = array_merge(
            $updates,
            [
                "INSERT INTO {prefix}print_template (name, filename, parameters, output_filename, type, order_no, inactive)"
                . " VALUES ('PrintEmailEnglish', 'invoice_printer_email.php', 'invoice,en-US', 'invoice_%d.pdf', 'invoice', 11, 1)",
                "INSERT INTO {prefix}print_template (name, filename, parameters, output_filename, type, order_no, inactive)"
                . " VALUES ('PrintEmailSwedish', 'invoice_printer_email.php', 'invoice,sv-FI', 'faktura_%d.pdf', 'invoice', 12, 1)",
                "INSERT INTO {prefix}print_template (name, filename, parameters, output_filename, type, order_no, inactive)"
                . " VALUES ('PrintEmailNoAttachment', 'invoice_printer_email.php', 'invoice,fi-FI,N,attachment=false', '', 'invoice', 260, 1)",
                "INSERT INTO {prefix}print_template (name, filename, parameters, output_filename, type, order_no, inactive)"
                . " VALUES ('PrintEmailReceiptNoAttachment', 'invoice_printer_email.php', 'receipt,fi-FI,N,attachment=false', '', 'invoice', 270, 1)",
                "INSERT INTO {prefix}print_template (name, filename, parameters, output_filename, type, order_no, inactive)"
                . " VALUES ('PrintOrderConfirmationEmailNoAttachment', 'invoice_printer_order_confirmation_email.php', 'receipt,fi-FI,N,attachment=false', '', 'invoice', 280, 1)",
                "INSERT INTO {prefix}print_template (name, filename, parameters, output_filename, type, order_no, inactive)"
                . " VALUES ('PrintOfferEmailNoAttachment', 'invoice_printer_offer_email.php', 'offer,fi-FI,N,attachment=false', '', 'offer', 280, 1)",
                "REPLACE INTO {prefix}state (id, data) VALUES ('version', '54')"
            ]
        );
    }

    if ($version < 55) {
        $updates = array_merge(
            $updates,
            [
                'ALTER TABLE {prefix}product ADD COLUMN weight decimal(15,5) NULL',
                "REPLACE INTO {prefix}state (id, data) VALUES ('version', '55')"
            ]
        );
    }

    if ($version < 56) {
        $updates = array_merge(
            $updates,
            [
                <<<EOT
    CREATE TABLE {prefix}custom_price (
        id int(11) NOT NULL auto_increment,
        company_id int(11) NOT NULL,
        discount decimal(4,1) NULL,
        multiplier decimal(10,5) NULL,
        valid_until int(11) default NULL,
        PRIMARY KEY (id),
        FOREIGN KEY (company_id) REFERENCES {prefix}company(id) ON DELETE CASCADE
    ) ENGINE=INNODB CHARACTER SET utf8 COLLATE utf8_swedish_ci;
EOT
                , <<<EOT
    CREATE TABLE {prefix}custom_price_map (
        id int(11) NOT NULL auto_increment,
        custom_price_id int(11) NOT NULL,
        product_id int(11) NOT NULL,
        unit_price decimal(15,5) NULL,
        discount decimal(4,1) NULL,
        discount_amount decimal(15,5) NULL,
        PRIMARY KEY (id),
        FOREIGN KEY (custom_price_id) REFERENCES {prefix}custom_price(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES {prefix}product(id) ON DELETE CASCADE
    ) ENGINE=INNODB CHARACTER SET utf8 COLLATE utf8_swedish_ci;
EOT
                ,
                "REPLACE INTO {prefix}state (id, data) VALUES ('version', '56')"
            ]
        );
    }

    if ($version < 57) {
        $updates = array_merge(
            $updates,
            [
                'ALTER TABLE {prefix}default_value ADD COLUMN additional text NULL',
                "REPLACE INTO {prefix}state (id, data) VALUES ('version', '57')"
            ]
        );
    }

    if ($version < 58) {
        $updates = array_merge(
            $updates,
            [
                'ALTER TABLE {prefix}users ADD COLUMN token varchar(255) NULL',
                "REPLACE INTO {prefix}state (id, data) VALUES ('version', '58')"
            ]
        );
    }

    // phpcs:enable Generic.Files.LineLength
    if (!empty($updates)) {
        dbQueryCheck('SET AUTOCOMMIT = 0');
        dbQueryCheck('BEGIN');
        foreach ($updates as $update) {
            $res = dbQueryCheck($update, true);
            if ($res === false) {
                dbQueryCheck('ROLLBACK');
                dbQueryCheck('SET AUTOCOMMIT = 1');
                error_log(
                    'Database upgrade query failed. Please execute the following'
                    . ' queries manually:'
                );
                foreach ($updates as $s) {
                    error_log(
                        '  ' . str_replace('{prefix}', _DB_PREFIX_ . '_', $s) . ';'
                    );
                }
                return 'FAILED';
            }
        }
        dbQueryCheck('COMMIT');
        dbQueryCheck('SET AUTOCOMMIT = 1');
        return 'UPGRADED';
    }
    return 'OK';
}

// Open database connection whenever this script is included
initDbConnection();
