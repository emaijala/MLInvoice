<?php
/**
 * List displays
 *
 * PHP version 7
 *
 * Copyright (C) Samu Reinikainen 2004-2008
 * Copyright (C) Ere Maijala 2010-2022
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
require_once 'search.php';

use Doctrine\DBAL\Query\QueryBuilder;

/**
 * Create a list
 *
 * @param string $strFunc          Function
 * @param string $strList          List
 * @param string $strTableName     Table name
 * @param string $strTitleOverride Default title override
 * @param int    $searchId         Saved search id
 * @param bool   $invoiceTotal     Whether to display invoice total
 * @param bool   $highlightOverdue Whether to highlight overdue rows
 * @param string $printType        Print template type for printing multiple
 *
 * @return void
 */
function createList($strFunc, $strList, $strTableName = '', $strTitleOverride = '',
    int $searchId = null, $invoiceTotal = false, $highlightOverdue = false,
    $printType = ''
) {
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

    if (!$strTableName) {
        $strTableName = "list_$strList";
    }
    $strTableName .= '_3';

    $strTitle = $strTitleOverride ? $strTitleOverride : $listConfig['title'];

    $params = [
        'listfunc' => $strFunc,
        'table' => $strList,
        'tableid' => $strTableName,
        'searchId' => $searchId,
    ];
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
    $params['query'] = json_encode(getSearchParamsFromRequest());

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
        $('#<?php echo $strTableName?>_title').append(' ' + MLInvoice.translate('InvoicesTotal', {'%%sum%%': MLInvoice.formatCurrency(data['sum'], 2)}));
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
      stateLoadParams: function (settings, data) {
        if (data.length === -1) {
            data.length = 10;
        }
      },
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
        $class = '';
        if ($customPriceSettings && $customPriceSettings['valid'] && 'custom_price' === $field['name']) {
            $class = 'editable';
        } elseif ('i.due_date' === $field['name']) {
            $class = 'due-date';
        }
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
    buttons.container().appendTo($('#<?php echo $strTableName?>_length'));

    $(document).on('click', '#<?php echo $strTableName?> tbody tr', function(e) {
      if ($(e.target).hasClass('cb-select-row') || $(e.target).find('.cb-select-row').length > 0) {
        return;
      }
      var row = this;
      if (row.classList.contains('child')) {
        row = row.previousElementSibling;
      }
      var data = $('#<?php echo $strTableName?>').dataTable().fnGetData(row);
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
                    <?php if (sesWriteAccess()) { ?>
                        <button id="add-custom-prices" class="btn btn-secondary">
                            <?php echo Translator::translate('Define')?>
                        </button>
                    <?php } ?>
                </div>
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
                            ? dateConvDBDate2Ymd(
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
                        <a role="button" class="btn btn-secondary" href="#" data-bs-toggle="dropdown" aria-expanded="false">
                            <?php echo Translator::translate('Delete')?>
                        </a>
                        <ul class="dropdown-menu">
                            <li>
                                <a class="dropdown-item delete-button" href="#">
                                    <?php echo Translator::translate('ConfirmDelete')?>
                                </a>
                            </li>
                        </ul>
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
 * @param array  $sort      Sort settings
 * @param string $filter    Quick filter
 * @param array  $query     Search query
 * @param int    $requestId Request ID
 * @param string $listId    List ID
 * @param int    $companyId Company ID
 * @param int    $searchId  Saved search ID
 *
 * @return void
 */
function createJSONList(
    string $strFunc,
    string $strList,
    int $startRow,
    int $rowCount,
    array $sort,
    string $filter,
    array $query,
    int $requestId,
    string $listId,
    int $companyId = null,
    int $searchId = null
) {
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

    $queryBuilders = createListQuery(
        $strFunc,
        $strList,
        $startRow,
        $rowCount,
        $sort,
        $filter,
        $query,
        $searchId
    );

    $countQuery = $queryBuilders['countQuery'];
    $filteredQuery = $queryBuilders['filteredQuery'];
    $prefix = _DB_PREFIX_ . '_';

    $countQuery->select('count(*)')
        ->from($prefix . $listConfig['table'], $listConfig['alias']);

    $totalCount = $filteredCount = $countQuery->executeQuery()->fetchOne();

    if ($filter) {
        $filteredCountQuery = $queryBuilders['filteredCountQuery'];
        $filteredCountQuery->select('count(*)')
            ->from($prefix . $listConfig['table'], $listConfig['alias']);
        $filteredCount = $filteredCountQuery->executeQuery()->fetchOne();
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
    $fields = [
        $listConfig['primaryKey']
    ];
    if ($listConfig['deletedField']) {
        $fields[] = $listConfig['deletedField'];
    }
    foreach ($listConfig['fields'] as $field) {
        if ('HIDDEN' === $field['type'] || !empty($field['virtual'])) {
            continue;
        }
        $fields[] = $field['sql'] ?? $field['name'];
    }
    if ('product' === $strList && $customPrices) {
        // Include any custom prices
        $fields[] = '(SELECT unit_price FROM ' . $prefix . 'custom_price_map pm'
            . ' WHERE pm.custom_price_id = '
            . $filteredQuery->createNamedParameter($customPrices['id'])
            . " AND pm.product_id = $prefix{$listConfig['table']}.id) custom_unit_price";
    }

    $filteredQuery->select($fields)
        ->from($prefix . $listConfig['table'], $listConfig['alias']);

    if ($startRow >= 0 && $rowCount >= 0) {
        $filteredQuery->setFirstResult($startRow)->setMaxResults($rowCount);
    }

    $result = $filteredQuery->executeQuery();

    $astrPrimaryKeys = [];
    $records = [];
    $highlight = getPostOrQuery('highlight_overdue', false);
    $idField = stripPrefix($listConfig['primaryKey']);
    $deletedField = stripPrefix($listConfig['deletedField']);
    foreach ($result->fetchAllAssociative() as $row) {
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

            $name = getFieldNameOrAlias($field['name']);
            if ('product' === $strList && 'custom_price' === $name) {
                $value = $row['unit_price'];
                if ($customPrices) {
                    if (null !== $row['custom_unit_price']) {
                        $value = $row['custom_unit_price'];
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
            } elseif ($field['type'] === 'INTDATE') {
                if (0 === $value) {
                    $value = null;
                }
            }

            $resultValues[] = $value;

            // Special colouring for overdue invoices
            if ($highlight && $name == 'due_date') {
                $rowDue = dbDate2UnixTime($row['due_date']);
                if ($rowDue < mktime(0, 0, 0, date("m"), date("d") - 14, date("Y"))
                ) {
                    $rowClass = 'overdue14';
                } elseif (true
                    && $rowDue < mktime(0, 0, 0, date("m"), date("d") - 7, date("Y"))
                ) {
                    $rowClass = 'overdue7';
                } elseif ($rowDue < mktime(0, 0, 0, date("m"), date("d"), date("Y"))
                ) {
                    $rowClass = 'overdue';
                }
            }
        }
        $class = trim("$rowClass$deleted");
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
            'createParams' => [
                'func' => $strFunc,
                'list' => $strList,
                'sort' => $sort,
                'filter' => $filter,
                'query' => $query,
                'searchId' => $searchId,
            ],
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
 * Create list query
 *
 * @param string $strFunc  Function
 * @param string $strList  List
 * @param int    $startRow Start row
 * @param int    $rowCount Number of rows
 * @param array  $sort     Sort settings
 * @param string $filter   Filter
 * @param array  $query    Query terms
 * @param int    $searchId Search ID
 *
 * @return QueryBuilder
 */
function createListQuery($strFunc, $strList, $startRow, $rowCount, $sort,
    $filter, array $query, int $searchId = null
) {
    $listConfig = getListConfig($strList);
    $table = $listConfig['table'];

    $qb = getDb()->createQueryBuilder();
    $prefix = _DB_PREFIX_ . '_';

    $search = new Search();
    if (!empty($searchId)) {
        if (!($searchData = getQuickSearch($searchId))) {
            return;
        }
        if ($searchData['func'] !== $strList) {
            throw new \Exception("Saved search type {$searchData['func']} does not match $strList");
        }
        if (strncmp($searchData['whereclause'], '{', 1) === 0) {
            $searchGroups = json_decode($searchData['whereclause'], true);
        } else {
            $searchGroups = $search->convertLegacySearch($strList, $searchData['whereclause']);
        }
    } else {
        $searchGroups = $search->getSearchGroups($query);
    }
    $operator = $searchGroups['operator'];
    foreach ($searchGroups['groups'] as $group) {
        $groupOperator = $group['operator'];
        $expressions = [];
        foreach ($group['fields'] as $field) {
            $type = $field['name'];
            if ('tags' === $type) {
                $tagTable = 'company' === $table ? 'company' : 'contact';

                $qb->innerJoin("$prefix$table", "$prefix{$tagTable}_tag_link", 'tl');
                $qb->innerJoin('tl', "$prefix{$tagTable}_tag", 'tag');
                $expressions[] = $qb->expr()->in('tag.tag', explode(',', $field['value']));
            } else {
                if ($listConfig['alias'] && strpos($type, '.') === false) {
                    $type = $listConfig['alias'] . ".$type";
                }
                // Conversion for date fields:
                foreach ($listConfig['fields'] as $current) {
                    if ($current['name'] == $type) {
                        if ('INTDATE' === $current['type']) {
                            // Handle empty date as today for lt or lte:
                            if ('' === $field['value'] && in_array($field['comparison'], ['lt', 'lte'])) {
                                $field['value'] = date('Y-m-d');
                            }
                            $field['value'] = dateConvYmd2DBDate($field['value']);
                        }
                        break;
                    }
                }
                $param = $qb->createNamedParameter($field['value']);

                switch ($field['comparison']) {
                case 'eq':
                    $expressions[] = $qb->expr()->eq($type, $param);
                    break;
                case 'ne':
                    $expressions[] = $qb->expr()->neq($type, $param);
                    break;
                case 'lt':
                    $expressions[] = $qb->expr()->lt($type, $param);
                    break;
                case 'lte':
                    $expressions[] = $qb->expr()->lte($type, $param);
                    break;
                case 'gt':
                    $expressions[] = $qb->expr()->gt($type, $param);
                    break;
                case 'gte':
                    $expressions[] = $qb->expr()->gte($type, $param);
                    break;
                }
            }
        }
        if (!$expressions) {
            continue;
        }
        $expressionSet = call_user_func_array(
            [
                $qb->expr(),
                'OR' === $groupOperator ? 'or' : 'and'
            ],
            $expressions
        );
        if ('OR' === $operator) {
            $qb->orWhere($expressionSet);
        } else {
            $qb->andWhere($expressionSet);
        }
    }

    if (!getSetting('show_deleted_records') && $listConfig['deletedField']) {
        $qb->andWhere("{$listConfig['deletedField']}=0");
    }

    $countQb = clone $qb;

    // Add count join to count query builder:
    addJoins($countQb, $listConfig['alias'], $listConfig['countJoins']);

    $filteredQb = clone $qb;
    if ($filter) {
        $leftAnchored = getSetting('dynamic_select_search_in_middle');
        $termPrefix = $leftAnchored ? '' : '%';
        foreach (explode(' ', $filter) as $term) {
            if ('' === trim($term)) {
                continue;
            }
            $expressions = [];
            foreach ($listConfig['searchFields'] as $searchField) {
                switch ($searchField['type']) {
                case 'TEXT':
                    $expressions[] = $qb->expr()->like(
                        $searchField['name'],
                        $filteredQb->createNamedParameter("$termPrefix$term%")
                    );
                    break;
                case 'PRIMARY':
                case 'INT':
                    if (ctype_digit($term)) {
                        $expressions[] = $qb->expr()->eq(
                            $searchField['name'],
                            $term
                        );
                    }
                    break;
                case 'CURRENCY':
                    $expressions[] = $qb->expr()->like(
                        'CAST(' . $searchField['name'] . ' AS CHAR)',
                        $filteredQb->createNamedParameter("$termPrefix$term%")
                    );
                    break;
                default:
                    continue 2;
                }
            }
            $filteredQb->andWhere(call_user_func_array([$qb->expr(), 'or'], $expressions));
        }
    }

    $filteredCountQb = clone $filteredQb;
    // Add count join to filtered count query builder:
    addJoins($filteredCountQb, $listConfig['alias'], $listConfig['countJoins']);

    // Add display join to full and filtered query builder:
    addJoins($qb, $listConfig['alias'], $listConfig['displayJoins']);
    addJoins($filteredQb, $listConfig['alias'], $listConfig['displayJoins']);

    // Add grouping:
    if ($listConfig['groupBy']) {
        $qb->addGroupBy($listConfig['groupBy']);
        $filteredQb->addGroupBy($listConfig['groupBy']);
    }

    // Add sort:
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
        $column = $sortField['column'] - 2;
        if (isset($shownFields[$column])) {
            $fieldName = $shownFields[$column]['name'];
            $direction = $sortField['direction'] === 'desc' ? 'DESC' : 'ASC';
            if (substr($fieldName, 0, 1) == '.') {
                $fieldName = substr($fieldName, 1);
            }
            // Special case for natural ordering of invoice number and reference
            // number
            if (in_array($fieldName, ['i.invoice_no', 'i.ref_number'])) {
                $filteredQb->addOrderBy("LENGTH($fieldName)", $direction);
            }
            $filteredQb->addOrderBy($fieldName, $direction);
        }
    }
    $filteredQb->addOrderBy(
        $listConfig['alias'] ? ($listConfig['alias'] . '.id') : 'id',
        'ASC'
    );

    return [
        'fullQuery' => $qb,
        'countQuery' => $countQb,
        'filteredQuery' => $filteredQb,
        'filteredCountQuery' => $filteredCountQb,
    ];
}

/**
 * Add joins to a QueryBuilder
 *
 * @param QueryBuilder $qb    QueryBuilder
 * @param ?string      $alias Main table alias
 * @param array        $joins Joins
 *
 * @return void
 */
function addJoins(QueryBuilder $qb, ?string $alias, array $joins): void
{
    $prefix = _DB_PREFIX_ . '_';
    foreach ($joins as $join) {
        switch ($join['type']) {
        case 'LEFT OUTER':
            $qb->leftJoin(
                $alias,
                $join['expr'] ?? ($prefix . $join['table']),
                $join['alias'],
                $join['condition']
            );
            break;
        default:
            throw new \Exception('Unhandled join type: ' . $join['type']);
        }
    }
}

/**
 * Create a JSON select list
 *
 * @param string $strList    List
 * @param int    $startRow   Start row
 * @param int    $rowCount   Number of rows
 * @param string $filter     Filter
 * @param string $filterType Filter type
 * @param string $sort       Sort settings
 * @param int    $id         Item ID
 *
 * @return array
 */
function createJSONSelectList($strList, $startRow, $rowCount, $filter, $filterType,
    $sort, $id = null
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

    // Add Filter
    if ($filter = trim($filter)) {
        // For default_value there can be also the type in the filter
        if ($strList == 'default_value' && $filterType) {
            $strWhereClause .= ($strWhereClause ? ' AND ' : ' WHERE ')
                . 'type=?';
            $arrQueryParams[] = $filterType;
        }
        $strWhereClause .= ($strWhereClause ? ' AND ' : ' WHERE ')
            . createWhereClause(
                $listConfig['searchFields'], $filter, $arrQueryParams,
                !getSetting('dynamic_select_search_in_middle')
            );
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
        if ($customPrices && false) {
            // Include any custom prices
            $strSelectClause .= <<<EOT
, (SELECT unit_price FROM {prefix}custom_price_map pm WHERE pm.custom_price_id = ?
AND pm.product_id = {prefix}{$listConfig['table']}.id) custom_unit_price
EOT;
            array_unshift($arrQueryParams, $customPrices['id']);
        }
    }

    $fullQuery = "SELECT $strSelectClause FROM {prefix}{$listConfig['table']} $strWhereClause{$listConfig['groupBy']}";
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
            if (empty($field['select'])) {
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
        // Encoding of actual result values is up to the consumer
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

    return compact('moreAvailable', 'records', 'filter');
}

/**
 * Remove table or alias prefix from a field name
 *
 * @param string $fieldName Field name
 *
 * @return string
 */
function stripPrefix(string $fieldName): string
{
    $parts = explode('.', $fieldName, 2);
    return $parts[1] ?? $parts[0];
}

/**
 * Get field name or alias from a field specification
 *
 * Returns e.g. 'alias' from 'i.name alias'
 *
 * @param string $fieldSpec Field specification
 *
 * @return string
 */
function getFieldNameOrAlias(string $fieldSpec): string
{
    $parts = explode(' ', $fieldSpec);
    $last = end($parts);
    return stripPrefix($last);
}
