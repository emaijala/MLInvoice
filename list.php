<?php
/**
 * List displays
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
require_once "sqlfuncs.php";
require_once "miscfuncs.php";
require_once "datefuncs.php";
require_once "memory.php";

use Michelf\Markdown;

/**
 * Create a list
 *
 * @param string $strFunc          Function
 * @param string $strList          List
 * @param string $strTableName     Table name
 * @param string $strTitleOverride Default title override
 * @param string $prefilter        Prefilter
 * @param bool   $invoiceTotal     Whether to display invoice total
 * @param bool   $highlightOverdue Whether to highlight overdue rows
 *
 * @return void
 */
function createList($strFunc, $strList, $strTableName = '', $strTitleOverride = '',
    $prefilter = '', $invoiceTotal = false, $highlightOverdue = false
) {

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
    if ('product' === $strList) {
        $companyId = getRequest('company');
    }
    $customPriceSettings = null;
    if (!empty($companyId)) {
        $params['company_id'] = $companyId;
        $customPriceSettings = getCustomPriceSettings($companyId);
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

<?php if ('product' === $strList && $companyId) { ?>
    $('#<?php echo $strTableName?>').on( 'click', 'td.editable', MLInvoice.editUnitPrice);
<?php } ?>

    $('#<?php echo $strTableName?>')
    .on('stateLoaded.dt', function () {
      var table = $('#<?php echo $strTableName?>').DataTable();
      if (table.search() != '' || table.page() != 0) {
        table.search('').page(0).draw('page');
      }
    })
    .dataTable( {
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
$i = 1;
foreach ($astrShowFields as $key => $field) {
    if ('HIDDEN' === $field['type']) {
        continue;
    }
    ++$i;
    $strWidth = isset($field['width']) ? ($field['width'] . 'px') : '';
?>
        {
            targets: [ <?php echo $i?> ],
            'width': "<?php echo $strWidth?>"
            <?php if ('product' === $strList && $field['name'] == 'custom_price') { ?>
            ,sortable: false
                <?php if ($customPriceSettings && $customPriceSettings['valid']) { ?>
            ,className: 'editable'
                <?php } ?>
            <?php } ?>

        },
<?php
}
?>
        { targets: [ 0, 1 ], 'searchable': false, 'visible': false }
      ],
      order: [[ 2, 'asc' ]],
      processing: true,
      serverSide: true,
      ajax: {
        url: 'json.php?func=get_list',
        data: <?php echo json_encode($params) ?>,
        type: 'POST',
        dataSrc: function (json) {
          for (var i = 0, len = json.data.length; i < len; i++) {
            <?php
            $i = 1;
            foreach ($astrShowFields as $key => $field) {
                if ('HIDDEN' === $field['type']) {
                    continue;
                }
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
      document.location.href = data[1];
    });
    $(document).on('mousedown', '#<?php echo $strTableName?> tbody tr', function(e) {
      if (e.button === 1 || e.ctrlKey || e.metaKey) {
        var data = $('#<?php echo $strTableName?>').dataTable().fnGetData(this);
        window.open(data[1], '_blank');
        e.preventDefault();
        return false;
      }
      return true;
    });
  });
  </script>

<?php
if ('product' === $strList) {
?>
    <div id="custom-prices" class="function_navi ui-helper-clearfix">
        <div class="medium_label label">
            <?php echo Translator::translate('ClientSpecificPrices')?>
        </div>
        <div class="field">
            <?php echo htmlFormElement(
                'company_id', 'SEARCHLIST', getRequest('company'), 'long',
                'table=company&sort=company_name,company_id', 'MODIFY', null, '', [],
                '_onChangeCompanyReload'
            );?>
        </div>
        <?php if ($companyId) { ?>
            <div id="no-custom-prices"<?php echo $customPriceSettings ? ' class="hidden"' : ''?>>
                <div class="label">
                    <?php echo Translator::translate('NoClientSpecificPricesDefined')?>
                </div>
                <?php if (sesWriteAccess()) { ?>
                    <div class="field">
                        <button id="add-custom-prices" class="ui-button ui-corner-all ui-widget">
                            <?php echo Translator::translate('Define')?>
                        </button>
                    </div>
                <?php } ?>
            </div>
            <div id="custom-prices-form" class="ui-helper-clearfix<?php echo !$customPriceSettings ? ' hidden' : ''?>">
                <div class="label medium_label">
                    <?php echo Translator::translate('DiscountPercent')?>
                </div>
                <div class="field">
                    <?php echo htmlFormElement(
                        'discount', 'INT',
                        $customPriceSettings
                            ? miscRound2OptDecim(
                                $customPriceSettings['discount']
                            ) : 0,
                        'percent'
                    );?>
                </div>
                <div class="label medium_label">
                    <?php echo Translator::translate('Multiplier')?>
                </div>
                <div class="field">
                    <?php echo htmlFormElement(
                        'multiplier', 'INT',
                        $customPriceSettings
                            ? miscRound2OptDecim(
                                $customPriceSettings['multiplier'], 5
                            ) : 1,
                        'currency'
                    );?>
                </div>
                <div class="label medium_label">
                    <?php echo Translator::translate('ValidUntil')?>
                </div>
                <div class="field">
                    <?php echo htmlFormElement(
                        'valid_until', 'INTDATE',
                        $customPriceSettings
                            ? dateConvDBDate2Date(
                                $customPriceSettings['valid_until']
                            ) : '',
                        'date'
                        . (!$customPriceSettings || $customPriceSettings['valid']
                            ? '' : ' ui-state-error')
                    );?>
                    <?php if ($customPriceSettings && !$customPriceSettings['valid']) { ?>
                        <i class="ui-icon ui-icon-alert"></i>
                    <?php } ?>
                </div>
                <div class="label medium_label">
                    <?php if (sesWriteAccess()) { ?>
                        <a class="actionlink save-button" href="#">
                            <?php echo Translator::translate('Save')?>
                        </a>
                        <a class="actionlink delete-button" href="#">
                            <?php echo Translator::translate('Delete')?>
                        </a>
                    <?php } ?>
                </div>
            </div>
        <?php } ?>
    </div>
<?php } ?>

<div class="list_container">
    <div id="<?php echo $strTableName?>_title" class="table_header">
        <?php echo Translator::translate($strTitle)?>
    </div>
    <table id="<?php echo $strTableName?>" class="list">
        <thead>
            <tr>
                <th>ID</th>
                <th>Link</th>
<?php
foreach ($astrShowFields as $field) {
    if ('HIDDEN' === $field['type']) {
        continue;
    }
    $strWidth = isset($field['width'])
        ? (' style="width: ' . $field['width'] . 'px"') : '';
?>
                <th<?php echo $strWidth?>>
                    <?php echo Translator::translate($field['header'])?>
                </th>
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

/**
 * Create a JSON list
 *
 * @param string $strFunc   Function
 * @param string $strList   List
 * @param int    $startRow  Start row
 * @param int    $rowCount  Number of rows
 * @param string $sort      Table name
 * @param string $filter    Filter
 * @param string $where     Where clause
 * @param int    $requestId Request ID
 * @param int    $listId    List ID
 * @param int    $companyId Company ID
 *
 * @return void
 */
function createJSONList($strFunc, $strList, $startRow, $rowCount, $sort, $filter,
    $where, $requestId, $listId, $companyId = null
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
    $queryParams = $params['params'];

    // Total count
    $fullQuery
        = "SELECT COUNT(*) AS cnt FROM $strTable $strCountJoin $strWhereClause";
    $rows = dbParamQuery($fullQuery, $queryParams);
    $totalCount = $filteredCount = $rows[0]['cnt'];

    // Add Filter
    if (isset($params['filteredTerms'])) {
        $strWhereClause = 'WHERE ' . $params['filteredTerms'];
        $queryParams = $params['filteredParams'];

        // Filtered count
        $fullQuery
            = "SELECT COUNT(*) as cnt FROM $strTable $strCountJoin $strWhereClause";
        $rows = dbParamQuery($fullQuery, $queryParams);
        $filteredCount = $rows[0]['cnt'];
    }

    $customPrices = null;
    if ('product' === $strList && null !== $companyId) {
        $customPrices = getCustomPriceSettings($companyId);
        if ($customPrices && $customPrices['valid_until']
            && $customPrices['valid_until'] < date('Ymd')
        ) {
            $customPrices = null;
        }
    }

    // Build the final select clause
    $strSelectClause = "$strPrimaryKey, $strDeletedField";
    foreach ($astrShowFields as $field) {
        if ('HIDDEN' === $field['type'] || !empty($field['virtual'])) {
            continue;
        }
        $strSelectClause .= ', ' .
             (isset($field['sql']) ? $field['sql'] : $field['name']);
    }
    if ('product' === $strList && $customPrices) {
        // Include any custom prices
        $strSelectClause .= <<<EOT
, (SELECT unit_price FROM {prefix}custom_price_map pm WHERE pm.custom_price_id = ?
AND pm.product_id = $strTable.id) custom_unit_price
EOT;
        $queryParams[] = $customPrices['id'];
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

    $rows = dbParamQuery($fullQuery, $queryParams, false, true);

    $astrPrimaryKeys = [];
    $records = [];
    $highlight = getRequest('highlight_overdue', false);
    foreach ($rows as $row) {
        $astrPrimaryKeys[] = $row[$strPrimaryKey];
        $deleted = $row[$strDeletedField] ? ' deleted' : '';
        $strLink = "?func=$strFunc&list=$strList&form=$strMainForm"
            . '&listid=' . urlencode($listId) . '&id=' . $row[$strPrimaryKey];
        $resultValues = [$row[$strPrimaryKey], $strLink];
        $rowClass = '';
        foreach ($astrShowFields as $field) {
            if ('HIDDEN' === $field['type']) {
                continue;
            }

            $name = $field['name'];
            if ('product' === $strList && 'custom_price' === $name) {
                $value = $row['unit_price'];
                if ($customPrices) {
                    if (null !== $row['.custom_unit_price']) {
                        $value = $row['.custom_unit_price'];
                        $rowClass = 'custom-price';
                    } else {
                        $value -= $value * $customPrices['discount'] / 100;
                        $value *= $customPrices['multiplier'];
                    }
                }
            } else {
                $value = $row[$name];
            }

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
                $rowDue = dbDate2UnixTime($row['i.due_date']);
                if ($rowDue < mktime(0, 0, 0, date("m"), date("d") - 14, date("Y"))
                ) {
                    $rowClass = ' overdue14';
                } elseif (true
                    && $rowDue < mktime(0, 0, 0, date("m"), date("d") - 7, date("Y"))
                ) {
                    $rowClass = ' overdue7';
                } elseif ($rowDue < mktime(0, 0, 0, date("m"), date("d"), date("Y"))
                ) {
                    $rowClass = ' overdue';
                }
            }
        }
        $class = "$rowClass$deleted";
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

/**
 * Create list query parameters
 *
 * @param string $strFunc  Function
 * @param string $strList  List
 * @param int    $startRow Start row
 * @param int    $rowCount Number of rows
 * @param string $sort     Table name
 * @param string $filter   Filter
 * @param string $where    Where clause
 *
 * @return array
 */
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
            if ('tags' === $field) {
                $tagTable = 'companies' === $strList ? 'company' : 'contact';
                foreach (explode(',', $term) as $i => $current) {
                    $subQuery = <<<EOT
SELECT {$tagTable}_id FROM {prefix}{$tagTable}_tag_link
  WHERE tag_id = (
    SELECT id FROM {prefix}{$tagTable}_tag WHERE tag = ?
  )
EOT;
                    if ($i > 0) {
                        $terms .= ' AND ';
                    }
                    $terms .= "$boolean id IN (" . $subQuery . ')';
                    $arrQueryParams[] = str_replace("%-", "%", $current);
                }
            } elseif (strcasecmp($operator, 'IN') === 0) {
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

    $filteredParams = $arrQueryParams;
    if ($filter) {
        $filteredTerms = "$terms $joinOp (" .
             createWhereClause($astrSearchFields, $filter, $filteredParams) . ')';
        $joinOp = ' AND';
    }

    if (!isset($strCountJoin)) {
        $strCountJoin = $strJoin;
    }

    // Sort options
    $orderBy = [];
    // Filter out hidden fields
    $shownFields = array_values(
        array_filter(
            $astrShowFields,
            function ($val) {
                return 'HIDDEN' !== $val['type'];
            }
        )
    );
    foreach ($sort as $sortField) {
        // Ignore invisible first columns
        $column = key($sortField) - 2;
        if (isset($shownFields[$column])) {
            $fieldName = $shownFields[$column]['name'];
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
        $result['filteredParams'] = $filteredParams;
    }

    return $result;
}

/**
 * Create a JSON select list
 *
 * @param string $strList  List
 * @param int    $startRow Start row
 * @param int    $rowCount Number of rows
 * @param string $filter   Filter
 * @param string $sort     Table name
 * @param int    $id       Item ID
 *
 * @return array
 */
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

    // Filter out inactive bases and companies
    if (($strList == 'company' || $strList == 'companies' || $strList == 'base'
        || $strList == 'bases') && empty($id)
    ) {
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
        if (!empty($field['virtual'])) {
            continue;
        }
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

    $customPrices = null;
    if ('product' === $strList) {
        $companyId = getRequest('company');
        if (!empty($companyId)) {
            $customPrices = getCustomPriceSettings($companyId);
        }
        if ($customPrices) {
            // Include any custom prices
            $strSelectClause .= <<<EOT
, (SELECT unit_price FROM {prefix}custom_price_map pm WHERE pm.custom_price_id = ?
AND pm.product_id = $strTable.id) custom_unit_price
EOT;
            array_unshift($arrQueryParams, $customPrices['id']);
        }
    }

    $fullQuery = "SELECT $strSelectClause FROM $strTable $strWhereClause$strGroupBy";
    if ($sort) {
        $fullQuery .= " ORDER BY $sort";
    }

    if ($startRow >= 0 && $rowCount >= 0) {
        $fullQuery .= " LIMIT $startRow, " . ($rowCount + 1);
    }

    $rows = dbParamQuery($fullQuery, $arrQueryParams);

    $records = [];
    $i = -1;
    $moreAvailable = false;
    foreach ($rows as $row) {
        ++$i;
        if ($startRow >= 0 && $rowCount >= 0 && $i >= $rowCount) {
            $moreAvailable = true;
            break;
        }
        $resultValues = [];
        $desc1 = [];
        $desc2 = [];
        $desc3 = [];
        foreach ($astrShowFields as $field) {
            if (!isset($field['select']) || !$field['select']) {
                continue;
            }
            $name = $field['name'];
            $value = empty($field['virtual']) ? $row[$name] : null;

            if ($field['type'] == 'TEXT' || $field['type'] == 'INT'
                || $field['type'] == 'HIDDEN'
            ) {
                if (isset($field['mappings']) && isset($field['mappings'][$value])) {
                    $value = Translator::translate($field['mappings'][$value]);
                }
            } elseif ($field['type'] == 'CURRENCY') {
                $value = miscRound2Decim(
                    $value, isset($field['decimals']) ? $field['decimals'] : 2
                );
            } elseif ($field['type'] == 'INTDATE') {
                $value = dateConvDBDate2Date($value);
            }

            if ('product' === $strList) {
                switch ($name) {
                case 'description':
                    if (!empty($value)) {
                        $desc1[] = $value;
                    }
                    continue 2;
                case 'product_group':
                    if (!empty($value)) {
                        $desc2[] = Translator::translate('ProductGroup') . ': '
                            . $value;
                    }
                    continue 2;
                case 'vendor':
                    if (!empty($value)) {
                        $desc3[] = Translator::translate('ProductVendor') . ': '
                            . $value;
                    }
                    continue 2;
                case 'vendors_code':
                    if (!empty($value)) {
                        $desc3[] = Translator::translate('ProductVendorsCode') . ': '
                            . $value;
                    }
                    continue 2;
                case 'unit_price':
                    if (!empty($value) && $value != 0.0) {
                        $desc3[] = Translator::translate('Price') . ': '
                            . $value;
                    }
                    continue 2;
                case 'custom_price':
                    if ($customPrices) {
                        $unitPrice = $row['custom_unit_price'];
                        if (null === $unitPrice
                            && !empty($row['unit_price'])
                            && $row['unit_price'] != 0.0
                        ) {
                            $unitPrice = $row['unit_price'];
                            $unitPrice -= $unitPrice * $customPrices['discount']
                                / 100;
                            $unitPrice *= $customPrices['multiplier'];
                        }
                        if (null !== $unitPrice) {
                            $unitPrice = miscRound2Decim(
                                $unitPrice,
                                isset($field['decimals']) ? $field['decimals'] : 2
                            );
                            if (!$customPrices['valid']) {
                                $unitPrice
                                    = "<span class=\"not-valid\">$unitPrice</span>";
                            }
                            $desc3[] = Translator::translate('ClientsPrice') . ': '
                                . $unitPrice;
                        }
                    }
                    continue 2;
                }
            }

            if (isset($field['translate']) && $field['translate']) {
                $value = Translator::translate($value);
            }
            $resultValues[$name] = $value;
        }
        $descriptions = $desc1;
        if ($desc2) {
            $descriptions[] = implode(', ', $desc2);
        }
        if ($desc3) {
            $descriptions[] = implode(', ', $desc3);
        }

        $markdown = getSetting('printout_markdown');
        foreach ($resultValues as &$resultValue) {
            $resultValue = $markdown ? Markdown::defaultTransform($resultValue)
                : htmlspecialchars($resultValue);
            $resultValue = preg_replace('/<p>(.*)<\/p>/', '$1', $resultValue);
        }
        foreach ($descriptions as &$description) {
            $description = $markdown ? Markdown::defaultTransform($description)
                : htmlspecialchars($description);
            $description = preg_replace('/<p>(.*)<\/p>/', '$1', $description);
        }

        $records[] = [
            'id' => $row[$strPrimaryKey],
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
