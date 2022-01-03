<?php
/**
 * List displays
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
require_once 'sqlfuncs.php';
require_once 'miscfuncs.php';
require_once 'datefuncs.php';
require_once 'memory.php';
require_once 'list_config.php';
require_once 'markdown.php';

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
 * @param string $printType        Print template type for printing multiple
 *
 * @return void
 */
function createList($strFunc, $strList, $strTableName = '', $strTitleOverride = '',
    $prefilter = '', $invoiceTotal = false, $highlightOverdue = false,
    $printType = ''
) {
    $strWhereClause = $prefilter ? $prefilter : getPostOrQuery('where', '');

    $listConfig = getListConfig($strList);
    if (!$listConfig) {
        return;
    }

    $printTemplates = [];
    if ($printType) {
        $templateCandidates = dbParamQuery(
            'SELECT * FROM {prefix}print_template WHERE deleted=0 and type=? and inactive=0 ORDER BY order_no',
            [$printType]
        );
        $templates = [];
        foreach ($templateCandidates as $candidate) {
            $printer = getInvoicePrinter($candidate['filename']);
            if (null === $printer) {
                continue;
            }
            $uses = class_uses($printer);
            if (in_array('InvoicePrinterEmailTrait', $uses)
                || $printer instanceof InvoicePrinterXSLT
                || $printer instanceof InvoicePrinterBlank
            ) {
                continue;
            }
            $printTemplates[] = $candidate;
        }
    }

    if ($listConfig['listFilter']) {
        if ($strWhereClause) {
            // Special case: don't apply archived filter for invoices if search terms
            // already contain archived status
            if ($strList != 'invoice'
                || strpos($strWhereClause, 'i.archived') === false
            ) {
                $strWhereClause .= " AND {$listConfig['listFilter']}";
            }
        } else {
            $strWhereClause = $listConfig['listFilter'];
        }
    }

    if (!$strTableName) {
        $strTableName = "list_$strList";
    }
    $strTableName .= '_3';

    $strTitle = $strTitleOverride ? $strTitleOverride : $listConfig['title'];

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
        $companyId = getPostOrQuery('company');
    }
    $customPriceSettings = null;
    if (!empty($companyId)) {
        $params['company'] = $companyId;
        $customPriceSettings = getCustomPriceSettings($companyId);
    }

    ?>
<script>

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
    <?php if (!getPostOrQuery('bc')) { ?>
    .on('stateLoaded.dt', function () {
      var table = $('#<?php echo $strTableName?>').DataTable();
      if (table.search() != '' || table.page() != 0) {
        table.search('').page(0).draw('page');
      }
    })
    <?php } ?>
    .dataTable( {
      language: {
        <?php echo Translator::translate('TableTexts')?>
      },
      stateSave: true,
      stateDuration: 0,
      pageLength: 10,
      lengthMenu: [ [10, 25, 50, 100, 500, -1], [10, 25, 50, 100, 500, "<?php echo Translator::translate('All')?>"] ],
      pagingType: "full_numbers",
      columnDefs: [
    <?php
    $hasRowSelection = false;
    $i = 1;
    foreach ($listConfig['fields'] as $field) {
        if ('HIDDEN' === $field['type']) {
            continue;
        }
        ++$i;
        $strWidth = isset($field['width']) ? ($field['width'] . 'px') : '';
        $sortable = !isset($field['sort']) || $field['sort'] ? 'true' : 'false';
        $class = $customPriceSettings && $customPriceSettings['valid']
            && 'custom_price' === $field['name']
            ? 'editable' : '';
        $visible = !isset($field['visible']) || $field['visible'] ? 'true' : 'false';
        ?>
        {
            targets: [ <?php echo $i?> ],
            width: '<?php echo $strWidth?>',
            sortable: <?php echo $sortable?>,
            className: '<?php echo $class?>',
            visible: <?php echo $visible?>
        },
        <?php
    }
    ?>
        { targets: [ 0, 1 ], 'searchable': false, 'visible': false }
      ],
      order: [[ 3, 'asc' ]],
      processing: true,
      serverSide: true,
      scrollX: false,
      responsive: {
        details: {
          display: $.fn.dataTable.Responsive.display.childRowImmediate
        }
      },
      autoWidth: true,
      ajax: {
        url: 'json.php?func=get_list',
        data: <?php echo json_encode($params) ?>,
        type: 'POST',
        dataSrc: function (json) {
          for (var i = 0, len = json.data.length; i < len; i++) {
            <?php
            $i = 1;
            foreach ($listConfig['fields'] as $field) {
                if ('HIDDEN' === $field['type']) {
                    continue;
                }
                ++$i;
                $class = !empty($field['class']) ? ' class="' . $field['class'] . '"'
                    : '';
                $container = $class ? "<span$class/>" : '<span/>';
                if (!empty($field['translate'])) {
                    ?>
                    json.data[i][<?php echo $i?>] = MLInvoice.translate(json.data[i][<?php echo $i?>]);
                    <?php
                } elseif ('CURRENCY' === $field['type']) {
                    $decimals = $field['decimals'] ?? 2;
                    ?>
                    json.data[i][<?php echo $i?>] = MLInvoice.formatCurrency(json.data[i][<?php echo $i?>], <?php echo $decimals?>);
                    <?php
                } elseif ('INTDATE' === $field['type']) {
                    ?>
                    json.data[i][<?php echo $i?>] = null !== json.data[i][<?php echo $i?>] ? MLInvoice.formatDate(json.data[i][<?php echo $i?>]) : '';
                    <?php
                } elseif ('CHECKBOX' === $field['type']) {
                    $hasRowSelection = true;
                    ?>
                    json.data[i][<?php echo $i?>] = ' <input<?php echo $class?> name="id[]" type="checkbox" value="' + json.data[i][<?php echo $i?>] + '">';
                    <?php
                } else {
                    ?>
                    var $div = $('<div/>');
                    $('<?php echo $container?>').text(json.data[i][<?php echo $i?>]).appendTo($div);
                    json.data[i][<?php echo $i?>] = $div.html();
                    <?php
                }
            }
            ?>
          }
          return json.data;
        }
      }
    })
    <?php
    if ($hasRowSelection) {
        ?>
        .on('draw.dt', function () {
            var $container = $(this).closest('.list_container');
            $container.find('input.cb-select-all').prop('checked', false);
            $container.find('input.cb-select-row').off('click').on('click', function(event) {
                MLInvoice.updateRowSelectedState($container);
                event.stopPropagation();
            });
            MLInvoice.updateRowSelectedState($container);
        })
        <?php
    }
    ?>
    ;
    var $table = $('#<?php echo $strTableName?>');
    var buttons = new $.fn.dataTable.Buttons($table.DataTable(), {
        buttons: [
            {
                text: '<i class="icon-columns"></i><span class="visually-hidden"><?php echo Translator::translate('Columns')?></span>',
                titleAttr: '<?php echo Translator::translate('Columns')?>',
                extend: 'colvis',
                columns: ':gt(2)'
            }
        ]
    });
    $table.DataTable().buttons().container().appendTo($('#<?php echo $strTableName?>_length'));

    $(document).on('click', '#<?php echo $strTableName?> tbody tr', function(e) {
      if ($(e.target).hasClass('cb-select-row') || $(e.target).find('.cb-select-row').length > 0) {
        return;
      }
      var data = $('#<?php echo $strTableName?>').dataTable().fnGetData(this);
      if (e.button === 1 || e.ctrlKey || e.metaKey) {
        window.open(data[1], '_blank');
      } else {
        document.location.href = data[1];
      }
    });
  });
  </script>

    <?php
    if ('product' === $strList) {
        ?>
    <div id="custom-prices" class="function_navi clearfix">
        <div class="medium_label label">
            <?php echo Translator::translate('ClientSpecificPrices')?>
        </div>
        <div class="field">
            <?php echo htmlFormElement(
                'company_id', 'SEARCHLIST', getPostOrQuery('company'), 'long',
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
                        <button id="add-custom-prices" class="btn btn-secondary">
                            <?php echo Translator::translate('Define')?>
                        </button>
                    </div>
                <?php } ?>
            </div>
            <div id="custom-prices-form" class="clearfix<?php echo !$customPriceSettings ? ' hidden' : ''?>">
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
                        'currency'
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
                            ? '' : ' text-danger')
                    );?>
                    <?php if ($customPriceSettings && !$customPriceSettings['valid']) { ?>
                        <i class="icon-attention text-danger"></i>
                    <?php } ?>
                </div>
                <div class="label medium_label">
                    <?php if (sesWriteAccess()) { ?>
                        <a role="button" class="btn btn-secondary save-button" href="#">
                            <?php echo Translator::translate('Save')?>
                        </a>
                        <a role="button" class="btn btn-secondary delete-button" href="#">
                            <?php echo Translator::translate('Delete')?>
                        </a>
                    <?php } ?>
                </div>
            </div>
            <?php } ?>
    </div>
    <script>
    $(document).ready(function () {
        MLInvoice.Form.setupSelect2();
    });
    </script>
    <?php } ?>

<div class="list_container">
    <?php
    if ($hasRowSelection) {
        ?>
        <form method="POST">
            <input type="hidden" name="func" value="multiedit">
            <input type="hidden" name="list" value="<?php echo htmlentities($strList)?>">
            <input type="hidden" name="form" value="<?php echo htmlentities($strList === 'open_invoice' ? 'invoice' : $strList)?>">
        <?php
    }
    ?>
    <?php if ($strTitle) { ?>
        <h2 id="<?php echo $strTableName?>_title" class="mb-2">
            <?php echo Translator::translate($strTitle)?>
        </h2>
    <?php } ?>
    <table id="<?php echo $strTableName?>" class="table table-striped table-bordered table-hover list nowrap">
        <thead>
            <tr>
                <th>ID</th>
                <th>Link</th>
    <?php
    foreach ($listConfig['fields'] as $field) {
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
    <?php
    if ($hasRowSelection) {
        ?>
        <div class="selection-buttons">
            <?php echo Translator::translate('ForSelected')?>:
            <input type="submit" value="<?php echo Translator::translate('Edit')?>" class="btn btn-secondary btn-sm selected-row-button update-selected-rows">
            <?php if ($printTemplates) { ?>
                <a role="button" class="btn btn-secondary btn-sm dropdown-toggle selected-row-button" href="#"
                    id="<?php echo $strTableName?>-dropdown-selected-actions" data-bs-toggle="dropdown" aria-expanded="false">
                    <?php echo Translator::translate('Print')?>...
                </a>
                <ul class="dropdown-menu print-selected-rows" aria-labelledby="<?php echo $strTableName?>-dropdown-selected-actions">
                    <?php foreach ($printTemplates as $template) { ?>
                        <li>
                            <div class="dropdown-item">
                                <a role="button" class="btn print-selected-item"
                                    data-template-id="<?php echo htmlentities($template['id'])?>"
                                    data-style="<?php echo $template['new_window'] ? 'openwindow' : 'redirect'?>"
                                >
                                    <?php echo Translator::translate($template['name'])?>
                                </a>
                            </div>
                        </li>
                    <?php } ?>
                </ul>
            <?php } ?>
        </div>
    </form>
        <?php
    }
    ?>
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
    global $dblink;

    $listConfig = getListConfig($strList);
    if (!$listConfig) {
        return;
    }

    if (!sesAccessLevel($listConfig['accessLevels']) && !sesAdminAccess()) {
        ?>
<div class="form_container">
        <?php echo Translator::translate('NoAccess') . "\n"?>
  </div>
        <?php
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
        = "SELECT COUNT(*) AS cnt FROM {$listConfig['table']} $strCountJoin $strWhereClause";
    $rows = dbParamQuery($fullQuery, $queryParams);
    $totalCount = $filteredCount = $rows[0]['cnt'];

    // Add Filter
    if (isset($params['filteredTerms'])) {
        $strWhereClause = 'WHERE ' . $params['filteredTerms'];
        $queryParams = $params['filteredParams'];

        // Filtered count
        $fullQuery
            = "SELECT COUNT(*) as cnt FROM {$listConfig['table']} $strCountJoin $strWhereClause";
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
    $strSelectClause = $listConfig['primaryKey'];
    if ($listConfig['deletedField']) {
        $strSelectClause .= ", {$listConfig['deletedField']}";
    }
    foreach ($listConfig['fields'] as $field) {
        if ('HIDDEN' === $field['type'] || !empty($field['virtual'])) {
            continue;
        }
        $strSelectClause .= ', ' .
             ($field['sql'] ?? $field['name']);
    }
    if ('product' === $strList && $customPrices) {
        // Include any custom prices
        $strSelectClause .= <<<EOT
, (SELECT unit_price FROM {prefix}custom_price_map pm WHERE pm.custom_price_id = ?
AND pm.product_id = {$listConfig['table']}.id) custom_unit_price
EOT;
        $queryParams[] = $customPrices['id'];
    }

    $fullQuery = "SELECT $strSelectClause FROM {$listConfig['table']} $strJoin"
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
    $highlight = getPostOrQuery('highlight_overdue', false);
    $idField = $listConfig['primaryKey'];
    $deletedField = $listConfig['deletedField'];
    foreach ($rows as $row) {
        $astrPrimaryKeys[] = $row[$idField];
        $deleted = ($deletedField && $row[$deletedField]) ? ' deleted' : '';
        $strLink = "?func=$strFunc&list=$strList&form={$listConfig['mainForm']}"
            . '&listid=' . urlencode($listId) . '&id=' . $row[$idField];
        $resultValues = [$row[$idField], $strLink];
        $rowClass = '';
        foreach ($listConfig['fields'] as $field) {
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
                if (isset($field['callback'])) {
                    $value = $field['callback']($value);
                }
            } elseif ($field['type'] == 'CURRENCY') {
                $value = miscRound2Decim(
                    $value,
                    $field['decimals'] ?? 2,
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
            'recordCount' => $filteredCount ?? $totalCount,
            'ids' => $astrPrimaryKeys,
            'queryParams' => $params
        ]
    );

    $results = [
        'draw' => $requestId,
        'recordsTotal' => $totalCount,
        'recordsFiltered' => $filteredCount ?? $totalCount,
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
    global $dblink;

    $listConfig = getListConfig($strList);

    $terms = '';
    $joinOp = '';
    $arrQueryParams = [];
    if ($where) {
        // Validate and build query parameters
        $boolean = '';
        while (extractSearchTerm($where, $field, $operator, $term, $nextBool)) {
            if ('tags' === $field) {
                $tagTable = 'company' === $strList ? 'company' : 'contact';
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
            } elseif (strcasecmp($operator, 'IN') === 0 || strcasecmp($operator, 'NOT IN') === 0) {
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

    if (!getSetting('show_deleted_records') && $listConfig['deletedField']) {
        $terms .= "$joinOp {$listConfig['deletedField']}=0";
        $joinOp = ' AND';
    }

    $filteredParams = $arrQueryParams;
    if ($filter) {
        $filteredTerms = "$terms $joinOp (" .
             createWhereClause($listConfig['searchFields'], $filter, $filteredParams) . ')';
        $joinOp = ' AND';
    }

    // Sort options
    $orderBy = [];
    // Filter out hidden fields
    $shownFields = array_values(
        array_filter(
            $listConfig['fields'],
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
        'table' => $listConfig['table'],
        'primaryKey' => $listConfig['primaryKey'],
        'terms' => $terms,
        'params' => $arrQueryParams,
        'order' => implode(',', $orderBy),
        'group' => $listConfig['groupBy'],
        'join' => $listConfig['displayJoin'],
        'countJoin' => $listConfig['countJoin'] ? $listConfig['countJoin'] : $listConfig['displayJoin']
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

    $listConfig = getListConfig($strList);
    if (empty($id) && !sesAccessLevel($listConfig['accessLevels']) && !sesAdminAccess()) {
        ?>
<div class="form_container">
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
            foreach ($listConfig['fields'] as $field) {
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
        foreach ($listConfig['fields'] as $field) {
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
        && !empty($listConfig['deletedField'])
    ) {
        $strWhereClause = " WHERE {$listConfig['deletedField']}=0";
    }

    $strGroupBy = $listConfig['groupBy'] ? " GROUP BY {$listConfig['groupBy']}" : '';

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
                    $listConfig['searchFields'], $filter, $arrQueryParams,
                    !getSetting('dynamic_select_search_in_middle')
                );
        }
    }

    // Filter out inactive bases and companies
    if (($strList == 'company' || $strList == 'base') && empty($id)) {
        $strWhereClause .= ($strWhereClause ? ' AND ' : ' WHERE ') . 'inactive=0';
    }

    if ($id) {
        $strWhereClause .= ($strWhereClause ? ' AND ' : ' WHERE ') . 'id=' .
             mysqli_real_escape_string($dblink, $id);
    }

    // Build the final select clause
    $strSelectClause = $listConfig['deletedField'] ? "{$listConfig['primaryKey']}, {$listConfig['deletedField']}"
        : $listConfig['primaryKey'];
    foreach ($listConfig['fields'] as $field) {
        if (!empty($field['virtual'])) {
            continue;
        }
        $strSelectClause .= ', ' .
             ($field['sql'] ?? $field['name']);
    }

    // Sort any exact matches first
    if ($listConfig['searchFields'] && $filter) {
        $fields = [];
        foreach ($listConfig['searchFields'] as $searchField) {
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
        $companyId = getPostOrQuery('company');
        if (!empty($companyId)) {
            $customPrices = getCustomPriceSettings($companyId);
        }
        if ($customPrices) {
            // Include any custom prices
            $strSelectClause .= <<<EOT
, (SELECT unit_price FROM {prefix}custom_price_map pm WHERE pm.custom_price_id = ?
AND pm.product_id = {$listConfig['table']}.id) custom_unit_price
EOT;
            array_unshift($arrQueryParams, $customPrices['id']);
        }
    }

    $fullQuery = "SELECT $strSelectClause FROM {$listConfig['table']} $strWhereClause{$listConfig['groupBy']}";
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
        foreach ($listConfig['fields'] as $field) {
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
                if (isset($field['callback'])) {
                    $value = $field['callback']($value);
                }
            } elseif ($field['type'] == 'CURRENCY') {
                $value = miscRound2Decim(
                    $value, $field['decimals'] ?? 2
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
                                $field['decimals'] ?? 2
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
                case 'stock_balance':
                    $desc3[] = Translator::translate('StockBalance') . ": $value";
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
        if ($markdown) {
            $markdownParser = new MLMarkdown();
        }
        foreach ($resultValues as &$resultValue) {
            $resultValue = $markdown ? $markdownParser->transform($resultValue)
                : htmlspecialchars($resultValue);
            $resultValue = preg_replace('/<p>(.*)<\/p>/', '$1', $resultValue);
        }
        foreach ($descriptions as &$description) {
            $description = $markdown ? $markdownParser->transform($description)
                : htmlspecialchars($description);
            $description = preg_replace('/<p>(.*)<\/p>/', '$1', $description);
        }

        $records[] = [
            'id' => $row[$listConfig['primaryKey']],
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
