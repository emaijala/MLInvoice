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
require_once "sqlfuncs.php";
require_once "miscfuncs.php";
require_once "datefuncs.php";
require_once "memory.php";

function createList($strFunc, $strList, $strTableName = '', $strTitleOverride = '',
    $prefilter = '', $invoiceTotal = false, $highlightOverdue = false)
{
    $strWhereClause = $prefilter ? $prefilter : getRequest('where', '');

    include 'list_switch.php';

    if (!$strList) {
        $strList = $strFunc;
    }

    if (!$strTable) {
        return;
    }

    if ($strListFilter) {
        if ($strWhereClause) {
            // Special case: don't apply archived filter for invoices if search terms
            // already contain archived status
            if ($strList != 'invoices'
                || strpos($strWhereClause, 'i.archived') === false
            ) {
                $strWhereClause .= " AND $strListFilter";
            }
        } else {
            $strWhereClause = $strListFilter;
        }
    }

    if (!$strTableName) {
        $strTableName = "list_$strList";
    }

    if ($strTitleOverride) {
        $strTitle = $strTitleOverride;
    } else {
        $strTitle = '';
    }

    $params = [
        'listfunc' => $strFunc,
        'table' => $strList,
        'tableid' => $strTableName
    ];
    if ($strWhereClause) {
        $params['where'] = $strWhereClause;
    }
    if ($highlightOverdue) {
        $params['highlight_overdue'] = 1;
    }

    ?>
<script type="text/javascript">

  $(document).ready(function() {
<?php
if ($invoiceTotal) {
?>
    $('#<?php echo $strTableName?>').one('xhr.dt', function() {
      $.ajax({
        url: 'json.php?func=get_invoice_total_sum',
        data: <?php echo json_encode($params) ?>,
        type: 'POST'
      }).done(function(data) {
        $('#<?php echo $strTableName?>_title').append(' ' + MLInvoice.translate('InvoicesTotal', {'%%sum%%': MLInvoice.formatCurrency(data['sum'])}));
      });
    });
<?php
}
?>

    $('#<?php echo $strTableName?>').dataTable( {
      language: {
        <?php echo Translator::translate('TableTexts')?>
      },
      stateSave: true,
      stateDuration: 0,
      jQueryUI: true,
      pageLength: <?php echo getSetting('default_list_rows')?>,
      pagingType: "full_numbers",
      columnDefs: [
<?php
$i = 0;
foreach ($astrShowFields as $key => $field) {
    if ('HIDDEN' === $field['type']) {
        continue;
    }
    ++$i;
    $strWidth = isset($field['width']) ? ($field['width'] . 'px') : '';
?>
        { targets: [ <?php echo $i?> ], 'width': "<?php echo $strWidth?>" },
<?php
}
?>
        { targets: [ 0 ], 'searchable': false, 'visible': false }
      ],
      order: [[ 1, 'asc' ]],
      processing: true,
      serverSide: true,
      ajax: {
        url: 'json.php?func=get_list',
        data: <?php echo json_encode($params) ?>,
        type: 'POST',
        dataSrc: function (json) {
          for (var i = 0, len = json.data.length; i < len; i++) {
            <?php
            $i = 0;
            foreach ($astrShowFields as $key => $field) {
                ++$i;
                if (!empty($field['translate'])) {
                ?>
                    json.data[i][<?php echo $i?>] = MLInvoice.translate(json.data[i][<?php echo $i?>]);
                <?php
                } elseif ('CURRENCY' === $field['type']) {
                    $decimals = isset($field['decimals']) ? $field['decimals'] : 2;
                ?>
                    json.data[i][<?php echo $i?>] = MLInvoice.formatCurrency(json.data[i][<?php echo $i?>], <?php echo $decimals?>);
                <?php
                } elseif ('INTDATE' === $field['type']) {
                ?>
                    json.data[i][<?php echo $i?>] = formatDate(json.data[i][<?php echo $i?>]);
                <?php
                } else {
                ?>
                    json.data[i][<?php echo $i?>] = $('<div/>').text(json.data[i][<?php echo $i?>]).html();
                <?php
                }
            }
            ?>
          }
          return json.data;
        }
      }
    });
    $(document).on('click', '#<?php echo $strTableName?> tbody tr', function(e) {
      var data = $('#<?php echo $strTableName?>').dataTable().fnGetData(this);
      document.location.href = data[0];
    });
  });
  </script>

<div class="list_container">
    <div id="<?php echo $strTableName?>_title" class="table_header"><?php echo Translator::translate($strTitle)?></div>
    <table id="<?php echo $strTableName?>" class="list">
        <thead>
            <tr>
                <th>Link</th>
<?php
foreach ($astrShowFields as $field) {
    if ('HIDDEN' === $field['type']) {
        continue;
    }
    $strWidth = isset($field['width'])
        ? (' style="width: ' . $field['width'] . 'px"') : '';
?>
                <th<?php echo $strWidth?>><?php echo Translator::translate($field['header'])?></th>
<?php
}
?>
        </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
    <br>
</div>
<?php
}

function createJSONList($strFunc, $strList, $startRow, $rowCount, $sort, $filter,
    $where, $requestId, $listId
) {
    include "list_switch.php";

    global $dblink;

    if (!sesAccessLevel($levelsAllowed) && !sesAdminAccess()) {
        ?>
<div class="form_container ui-widget-content">
    <?php echo Translator::translate('NoAccess') . "\n"?>
  </div>
<?php
        return;
    }

    if (!$strTable) {
        return;
    }

    $params = createListQueryParams(
        $strFunc, $strList, $startRow, $rowCount, $sort, $filter, $where
    );

    $strJoin = $params['join'];
    $strCountJoin = $params['countJoin'];
    $strWhereClause = !empty($params['terms']) ? "WHERE {$params['terms']}" : '';
    $strGroupBy = !empty($params['group']) ? " GROUP BY {$params['group']}" : '';

    // Total count
    $fullQuery
        = "SELECT COUNT(*) AS cnt FROM $strTable $strCountJoin $strWhereClause";
    $res = mysqli_param_query($fullQuery, $params['params']);
    $row = mysqli_fetch_assoc($res);
    $totalCount = $filteredCount = $row['cnt'];

    // Add Filter
    if (isset($params['filteredTerms'])) {
        $strWhereClause = 'WHERE ' . $params['filteredTerms'];

        // Filtered count
        $fullQuery
            = "SELECT COUNT(*) as cnt FROM $strTable $strCountJoin $strWhereClause";
        $res = mysqli_param_query($fullQuery, $params['params']);
        $row = mysqli_fetch_assoc($res);
        $filteredCount = $row['cnt'];
    }

    // Build the final select clause
    $strSelectClause = "$strPrimaryKey, $strDeletedField";
    foreach ($astrShowFields as $field) {
        $strSelectClause .= ', ' .
             (isset($field['sql']) ? $field['sql'] : $field['name']);
    }

    $fullQuery = "SELECT $strSelectClause FROM $strTable $strJoin"
        . " $strWhereClause$strGroupBy";

    $order = [];
    if ($params['order']) {
        $order = explode(',', $params['order']);
    }
    $order[] = 'id';
    $fullQuery .= ' ORDER BY ' . implode(',', $order);

    if ($startRow >= 0 && $rowCount >= 0) {
        $fullQuery .= " LIMIT $startRow, $rowCount";
    }

    $res = mysqli_param_query($fullQuery, $params['params']);

    $astrPrimaryKeys = [];
    $records = [];
    $highlight = getRequest('highlight_overdue', false);
    while ($row = mysqli_fetch_prefixed_assoc($res)) {
        $astrPrimaryKeys[] = $row[$strPrimaryKey];
        $deleted = $row[$strDeletedField] ? ' deleted' : '';
        $strLink = "?func=$strFunc&list=$strList&form=$strMainForm"
            . '&listid=' . urlencode($listId) . '&id=' . $row[$strPrimaryKey];
        $resultValues = [$strLink];
        $overdue = '';
        foreach ($astrShowFields as $field) {
            $name = $field['name'];
            $value = $row[$name];
            if ($field['type'] == 'TEXT' || $field['type'] == 'INT') {
                if (isset($field['mappings']) && isset($field['mappings'][$value])) {
                    $value = Translator::translate($field['mappings'][$value]);
                } elseif (!empty($field['pretranslate'])) {
                    $value = Translator::translate($value);
                }
            } elseif ($field['type'] == 'CURRENCY') {
                $value = miscRound2Decim(
                    $value,
                    isset($field['decimals']) ? $field['decimals'] : 2,
                    '.', ''
                );
            }

            $resultValues[] = $value;

            // Special colouring for overdue invoices
            if ($highlight && $name == 'i.due_date') {
                $rowDue = $row['i.due_date'];
                if ($rowDue < mktime(0, 0, 0, date("m"), date("d") - 14, date("Y"))
                ) {
                    $overdue = ' overdue14';
                } elseif (true
                    && $rowDue < mktime(0, 0, 0, date("m"), date("d") - 7, date("Y"))
                ) {
                    $overdue = ' overdue7';
                } elseif ($rowDue < mktime(0, 0, 0, date("m"), date("d"), date("Y"))
                ) {
                    $overdue = ' overdue';
                }
            }
        }
        $class = "$overdue$deleted";
        if ($class) {
            $resultValues['DT_RowClass'] = $class;
        }

        $records[] = $resultValues;
    }

    Memory::set(
        "{$listId}_info",
        [
            'startRow' => $startRow,
            'rowCount' => $rowCount,
            'recordCount' => isset($filteredCount) ? $filteredCount : $totalCount,
            'ids' => $astrPrimaryKeys,
            'queryParams' => $params
        ]
    );

    $results = [
        'draw' => $requestId,
        'recordsTotal' => $totalCount,
        'recordsFiltered' => isset($filteredCount) ? $filteredCount : $totalCount,
        'data' => $records
    ];
    return json_encode($results);

}

function createListQueryParams($strFunc, $strList, $startRow, $rowCount, $sort,
    $filter, $where
) {
    include "list_switch.php";

    global $dblink;

    $terms = '';
    $joinOp = '';
    $arrQueryParams = [];
    if ($where) {
        // Validate and build query parameters
        $boolean = '';
        while (extractSearchTerm($where, $field, $operator, $term, $nextBool)) {
            if (strcasecmp($operator, 'IN') === 0) {
                $terms .= "$boolean$field $operator " .
                     mysqli_real_escape_string($dblink, $term);
            } else {
                $terms .= "$boolean$field $operator ?";
                $arrQueryParams[] = str_replace("%-", "%", $term);
            }
            if (!$nextBool) {
                break;
            }
            $boolean = " $nextBool";
        }
        if ($terms) {
            $terms = "($terms)";
            $joinOp = ' AND';
        }
    }

    if (!getSetting('show_deleted_records')) {
        $terms .= "$joinOp $strDeletedField=0";
        $joinOp = ' AND';
    }

    if ($filter) {
        $filteredTerms = "$terms $joinOp (" .
             createWhereClause($astrSearchFields, $filter, $arrQueryParams) . ')';
        $joinOp = ' AND';
    }

    if (!isset($strCountJoin)) {
        $strCountJoin = $strJoin;
    }

    // Sort options
    $orderBy = [];
    foreach ($sort as $sortField) {
        // Ignore invisible first column
        $column = key($sortField) - 1;
        if (isset($astrShowFields[$column])) {
            $fieldName = $astrShowFields[$column]['name'];
            $direction = current($sortField) === 'desc' ? 'DESC' : 'ASC';
            if (substr($fieldName, 0, 1) == '.') {
                $fieldName = substr($fieldName, 1);
            }
            // Special case for natural ordering of invoice number and reference
            // number
            if (in_array($fieldName, ['i.invoice_no', 'i.ref_number'])) {
                $orderBy[] = "LENGTH($fieldName) $direction";
            }
            $orderBy[] = "$fieldName $direction";
        }
    }

    $result = [
        'table' => $strTable,
        'primaryKey' => $strPrimaryKey,
        'terms' => $terms,
        'params' => $arrQueryParams,
        'order' => implode(',', $orderBy),
        'group' => $strGroupBy,
        'join' => $strJoin,
        'countJoin' => isset($strCountJoin) ? $strCountJoin : $strJoin
    ];
    if (isset($filteredTerms)) {
        $result['filteredTerms'] = $filteredTerms;
    }

    return $result;
}

function createJSONSelectList($strList, $startRow, $rowCount, $filter, $sort,
    $id = null
) {
    global $dblink;
    include "list_switch.php";

    if (empty($id) && !sesAccessLevel($levelsAllowed) && !sesAdminAccess()) {
        ?>
<div class="form_container ui-widget-content">
    <?php echo Translator::translate('NoAccess') . "\n"?>
  </div>
<?php
        return;
    }

    if ($sort) {
        if (!preg_match('/^[\w_,]+$/', $sort)) {
            header('HTTP/1.1 400 Bad Request');
            die('Invalid sort type');
        }
        $sortValid = 0;
        $sortFields = explode(',', $sort);
        foreach ($sortFields as $sortField) {
            foreach ($astrShowFields as $field) {
                if ($sortField === $field['name']) {
                    ++$sortValid;
                    break;
                }
            }
        }
        if ($sortValid != count($sortFields)) {
            header('HTTP/1.1 400 Bad Request');
            die('Invalid sort type');
        }
    } else {
        foreach ($astrShowFields as $field) {
            if ($field['name'] == 'order_no') {
                $sort = 'order_no';
            }
        }
    }
    if ($sort) {
        $sort .= ',';
    }
    $sort .= 'id';

    $arrQueryParams = [];

    $strWhereClause = '';

    if (!getSetting('show_deleted_records') && empty($id)
        && !empty($strDeletedField)
    ) {
        $strWhereClause = " WHERE $strDeletedField=0";
    }

    if ($strGroupBy) {
        $strGroupBy = " GROUP BY $strGroupBy";
    }

    // Add Filter
    if ($filter) {
        // For default_value there can be also the type in the filter
        if ($strList == 'default_value' && is_array($filter)) {
            if (count($filter) > 1) {
                $strWhereClause .= ($strWhereClause ? ' AND ' : ' WHERE ')
                    . 'type=?';
                $arrQueryParams[] = $filter[1];
            }
            $filter = $filter[0];
        }
        if ($filter) {
            $strWhereClause .= ($strWhereClause ? ' AND ' : ' WHERE ')
                . createWhereClause(
                    $astrSearchFields, $filter, $arrQueryParams,
                    !getSetting('dynamic_select_search_in_middle')
                );
        }
    }

    // Filter out inactive companies
    if ($strList == 'company' || $strList == 'companies' && empty($id)) {
        $strWhereClause .= ($strWhereClause ? ' AND ' : ' WHERE ') . 'inactive=0';
    }

    if ($id) {
        $strWhereClause .= ($strWhereClause ? ' AND ' : ' WHERE ') . 'id=' .
             mysqli_real_escape_string($dblink, $id);
    }

    // Build the final select clause
    $strSelectClause = !empty($strDeletedField) ? "$strPrimaryKey, $strDeletedField"
        : $strPrimaryKey;
    foreach ($astrShowFields as $field) {
        $strSelectClause .= ', ' .
             (isset($field['sql']) ? $field['sql'] : $field['name']);
    }

    // Sort any exact matches first
    if ($astrSearchFields && $filter) {
        $fields = [];
        foreach ($astrSearchFields as $searchField) {
            if (in_array($searchField['type'], ['TEXT', 'INT', 'PRIMARY'])) {
                $fields[] = $searchField['name'];
            }
        }
        $fieldList = implode(',', $fields);
        $escapedFilter = mysqli_real_escape_string($dblink, $filter);
        $exactSort = "IF('$escapedFilter' IN ($fieldList, "
            . "CONCAT_WS(' ', $fieldList)), 0, 1)";
        if ($sort) {
            $sort = "$exactSort, $sort";
        } else {
            $sort = $exactSort;
        }
    }

    $fullQuery = "SELECT $strSelectClause FROM $strTable $strWhereClause$strGroupBy";
    if ($sort) {
        $fullQuery .= " ORDER BY $sort";
    }

    if ($startRow >= 0 && $rowCount >= 0) {
        $fullQuery .= " LIMIT $startRow, " . ($rowCount + 1);
    }

    $res = mysqli_param_query($fullQuery, $arrQueryParams);

    $astrListValues = [];
    $i = -1;
    $moreAvailable = false;
    while ($row = mysqli_fetch_prefixed_assoc($res)) {
        ++$i;
        if ($startRow >= 0 && $rowCount >= 0 && $i >= $rowCount) {
            $moreAvailable = true;
            break;
        }
        $astrPrimaryKeys[$i] = $row[$strPrimaryKey];
        $aboolDeleted[$i] = $row[$strDeletedField];
        foreach ($astrShowFields as $field) {
            $name = $field['name'];
            if ($field['type'] == 'TEXT' || $field['type'] == 'INT'
                || $field['type'] == 'HIDDEN'
            ) {
                $value = $row[$name];
                if (isset($field['mappings']) && isset($field['mappings'][$value])) {
                    $value = Translator::translate($field['mappings'][$value]);
                }
                $astrListValues[$i][$name] = $value;
            } elseif ($field['type'] == 'CURRENCY') {
                $value = $row[$name];
                $value = miscRound2Decim(
                    $value, isset($field['decimals']) ? $field['decimals'] : 2
                );
                $astrListValues[$i][$name] = $value;
            } elseif ($field['type'] == 'INTDATE') {
                $astrListValues[$i][$name] = dateConvDBDate2Date($row[$name]);
            }
        }
    }

    $records = [];
    for ($i = 0; $i < count($astrListValues); $i ++) {
        $row = $astrListValues[$i];
        $resultValues = [];
        $descriptions = [];
        $desc2 = [];
        foreach ($astrShowFields as $field) {
            if (!isset($field['select']) || !$field['select']) {
                continue;
            }
            $name = $field['name'];

            if ('product' === $strList) {
                switch ($name) {
                case 'description':
                    if (!empty($row[$name])) {
                        $descriptions[] = $row[$name];
                    }
                    continue 2;
                case 'vendor':
                    if (!empty($row[$name])) {
                        $desc2[] = Translator::translate('ProductVendor') . ': ' . $row[$name];
                    }
                    continue 2;
                case 'vendors_code':
                    if (!empty($row[$name])) {
                        $desc2[] = Translator::translate('ProductVendorsCode') . ': '
                            . $row[$name];
                    }
                    continue 2;
                }
            }

            if (isset($field['translate']) && $field['translate']) {
                $value = Translator::translate($row[$name]);
            } else {
                $value = $row[$name];
            }
            $resultValues[$name] = $value;
        }
        if ($desc2) {
            $descriptions[] = implode(', ', $desc2);
        }

        $records[] = [
            'id' => $astrPrimaryKeys[$i],
            'descriptions' => $descriptions,
            'text' => implode(' ', $resultValues)
        ];
    }

    $results = [
        'moreAvailable' => $moreAvailable,
        'records' => $records,
        'filter' => $filter
    ];
    return json_encode($results);
}
