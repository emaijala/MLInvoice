<?php
/**
 * List Service.
 *
 * PHP version 8
 *
 * Copyright (C) Ere Maijala 2010-2026.
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

declare(strict_types=1);

namespace MLInvoice\List;

use DI\Attribute\Inject;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\ORM\EntityManagerInterface;
use MLInvoice\Config\ConfigManagerInterface;
use MLInvoice\Config\SettingsManager;
use MLInvoice\Database\Entity\Company;
use MLInvoice\Database\Repository\InvoiceStateRepository;
use MLInvoice\I18n\NumberFormatter;
use MLInvoice\I18n\Translator;
use MLInvoice\Markdown\MLMarkdown;
use MLInvoice\Search\Search;
use MLInvoice\Search\SearchService;
use MLInvoice\Session\Memory;
use MLInvoice\Utils\DateUtils;
use Odan\Session\SessionInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * List Service.
 *
 * @category MLInvoice
 * @package  MLInvoice\Base
 * @author   Ere Maijala <ere@labs.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://labs.fi/mlinvoice.eng.php
 */
class ListService
{
    /**
     * Table name prefix.
     *
     * @var string
     */
    protected string $prefix;

    /**
     * Constructor
     */
    public function __construct(
        #[Inject('config')] protected array $config,
        protected Translator $translator,
        protected Memory $memory,
        protected InvoiceStateRepository $invoiceStateRepository,
        protected SearchService $searchService,
        protected SettingsManager $settingsManager,
        protected DateUtils $dateUtils,
        protected SessionInterface $session,
        protected EntityManagerInterface $entityManager,
        protected NumberFormatter $numberFormatter,
    ) {
        $this->prefix = $config['Database']['table_prefix'] ?? 'mlinvoice_';
    }

    /**
     * Create a JSON list
     *
     * @param string  $strFunc   Function
     * @param string  $list   List
     * @param int     $startRow  Start row
     * @param int     $rowCount  Number of rows
     * @param array   $sort      Sort settings
     * @param string  $filter    Quick filter
     * @param array   $query     Search query
     * @param int     $requestId Request ID
     * @param string  $listId    List ID
     * @param int     $companyId Company ID
     * @param int     $searchId  Saved search ID
     * @param ?string $format    Record output format (object for key-value object, any other value for DataTables format)
     *
     * @return array
     */
    function createJSONList(
        string $strFunc,
        string $list,
        int $startRow,
        int $rowCount,
        array $sort,
        string $filter,
        array $query,
        int $requestId,
        string $listId,
        ?int $companyId = null,
        ?int $searchId = null,
        ?string $format = null
    ): array {
        $listConfig = $this->getListConfig($list);
        if (!$listConfig) {
            return '{"error": "Invalid list"}';
        }

        $accessLevel = $this->session->get('accessLevel');
        if ($accessLevel !== MLINVOICE_USER_ROLE_ADMIN && !in_array($accessLevel, $listConfig['accessLevels'])) {
            return '{"error": "Access denied"}';
        }

        $queryBuilders = $this->createListQuery(
            $strFunc,
            $list,
            $startRow,
            $rowCount,
            $sort,
            $filter,
            $query,
            $searchId
        );

        if ('object' === $format) {
            // Override sort order for 'object' format. TODO: This is a bit of a hack. Add way to properly describe sort
            // order when doing a non-datatables query.
            $queryBuilders['filteredQuery']->resetOrderBy();
            $queryBuilders['filteredQuery']->addOrderBy(
                $listConfig['alias'] ? ($listConfig['alias'] . '.id') : 'id',
                'DESC'
            );
        }

        $countQuery = $queryBuilders['countQuery'];
        $filteredQuery = $queryBuilders['filteredQuery'];

        $countQuery->select('count(*)')
            ->from($this->prefix . $listConfig['table'], $listConfig['alias']);

        $totalCount = $filteredCount = $countQuery->executeQuery()->fetchOne();

        if ($filter) {
            $filteredCountQuery = $queryBuilders['filteredCountQuery'];
            $filteredCountQuery->select('count(*)')
                ->from($this->prefix . $listConfig['table'], $listConfig['alias']);
            $filteredCount = $filteredCountQuery->executeQuery()->fetchOne();
        }

        $customPrices = null;
        if ('product' === $list && null !== $companyId) {
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
        $fieldLabels = [];
        foreach ($listConfig['fields'] as $field) {
            if ('HIDDEN' === $field['type'] || !empty($field['virtual'])) {
                continue;
            }
            $fields[] = $field['sql'] ?? $field['name'];
            if ('object' === $format) {
                $name = getFieldNameOrAlias($field['name']);
                $fieldLabels[$name] = 'id' === $field['name'] ? '' : $this->translator->translate($field['header']);
            }
        }
        if ('product' === $list && $customPrices) {
            // Include any custom prices
            $fields[] = '(SELECT unit_price FROM ' . $this->prefix . 'custom_price_map pm'
                . ' WHERE pm.custom_price_id = '
                . $filteredQuery->createNamedParameter($customPrices['id'])
                . " AND pm.product_id = {$this->prefix}{$listConfig['table']}.id) custom_unit_price";
        }

        $filteredQuery->select($fields)
            ->from($this->prefix . $listConfig['table'], $listConfig['alias']);

        if ($startRow >= 0 && $rowCount >= 0) {
            $filteredQuery->setFirstResult($startRow)->setMaxResults($rowCount);
        }

        $result = $filteredQuery->executeQuery();

        $astrPrimaryKeys = [];
        $records = [];
        $idField = $this->stripPrefix($listConfig['primaryKey']);
        $deletedField = $this->stripPrefix($listConfig['deletedField']);
        foreach ($result->fetchAllAssociative() as $row) {
            $astrPrimaryKeys[] = $row[$idField];
            $deleted = ($deletedField && $row[$deletedField]) ? ' deleted' : '';
            $strLink = "?func=$strFunc&list=$list&form={$listConfig['mainForm']}"
                . '&listid=' . urlencode($listId) . '&id=' . $row[$idField];
            $resultValues = [$row[$idField], $strLink];
            $resultObject = [
                $idField => $row[$idField],
                '_link' => $strLink,
            ];
            $rowClass = '';
            foreach ($listConfig['fields'] as $field) {
                if ('HIDDEN' === $field['type'] && 'object' !== $format) {
                    continue;
                }

                $name = $this->getFieldNameOrAlias($field['name']);
                if ('product' === $list && 'custom_price' === $name) {
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
                        $value = $this->translator->translate($field['mappings'][$value]);
                    } elseif (!empty($field['pretranslate'])) {
                        $value = $this->translator->translate($value);
                    }
                    if (isset($field['callback'])) {
                        $value = $field['callback']($value);
                    }
                } elseif ($field['type'] == 'CURRENCY') {
                    $value = $this->numberFormatter->roundCurrency(
                        (float)($value ?? 0),
                        $field['decimals'] ?? 2,
                        decimalSeparator: '.',
                        thousandSeparator: ''
                    );
                } elseif ($field['type'] === 'INTDATE') {
                    if (0 === $value) {
                        $value = null;
                    }
                }

                $resultValues[] = $value;
                $resultObject[$name] = $value;

                // Special colouring for overdue invoices
                if ('invoices' === $list && $name == 'due_date') {
                    $rowDue = dbDate2UnixTime($row['due_date']);
                    if ($rowDue < mktime(0, 0, 0, (int)date("m"), (int)date("d") - 14, (int)date("Y"))
                    ) {
                        $rowClass = 'overdue14';
                    } elseif (true
                        && $rowDue < mktime(0, 0, 0, (int)date("m"), (int)date("d") - 7, (int)date("Y"))
                    ) {
                        $rowClass = 'overdue7';
                    } elseif ($rowDue < mktime(0, 0, 0, (int)date("m"), (int)date("d"), (int)date("Y"))
                    ) {
                        $rowClass = 'overdue';
                    }
                }

                // Special colouring for due/overdue invoice templates
                if ('invoice_templates' === $list
                    && $name == 'next_interval_date'
                    && $row['next_interval_date']
                ) {
                    $nextDate = dbDate2UnixTime($row['next_interval_date']);
                    if ($nextDate <= mktime(0, 0, 0, (int)date("m"), (int)date("d"), (int)date("Y"))
                    ) {
                        $rowClass = 'due';
                    }
                }
            }
            $class = trim("$rowClass$deleted");
            if ($class) {
                $resultValues['DT_RowClass'] = $class;
            }

            $records[] = $format === 'object' ? $resultObject : $resultValues;
        }

        $this->memory->set(
            "{$listId}_info",
            [
                'startRow' => $startRow,
                'rowCount' => $rowCount,
                'recordCount' => $filteredCount ?? $totalCount,
                'ids' => $astrPrimaryKeys,
                'createParams' => [
                    'func' => $strFunc,
                    'list' => $list,
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
        if ('object' === $format) {
            $results['labels'] = $fieldLabels;
        }
        return $results;
    }

    /**
     * Create list query
     *
     * @param string $strFunc  Function
     * @param string $list  List
     * @param int    $startRow Start row
     * @param int    $rowCount Number of rows
     * @param array  $sort     Sort settings
     * @param string $filter   Filter
     * @param array  $query    Query terms
     * @param int    $searchId Search ID
     *
     * @return QueryBuilder
     */
    public function createListQuery($strFunc, $list, $startRow, $rowCount, $sort,
        $filter, array $query, ?int $searchId = null
    ) {
        $listConfig = $this->getListConfig($list);
        $table = $listConfig['table'];

        $qb = $this->entityManager->getConnection()->createQueryBuilder();

        if (!empty($searchId)) {
            if (!($searchData = $this->searchService->getQuickSearch($searchId))) {
                return;
            }
            if (strncmp($searchData['whereclause'], '{', 1) === 0) {
                $searchGroups = json_decode($searchData['whereclause'], true);
            } else {
                $searchGroups = $this->searchService->convertLegacySearch($list, $searchData['whereclause']);
            }
            $querySearchGroups = $this->searchService->getSearchGroups($query);
            $searchGroups['groups'] = array_merge(
                $searchGroups['groups'],
                $querySearchGroups['groups']
            );
        } else {
            $searchGroups = $this->searchService->getSearchGroups($query);
        }
        $operator = $searchGroups['operator'];
        foreach ($searchGroups['groups'] as $group) {
            $groupOperator = $group['operator'];
            $expressions = [];
            foreach ($group['fields'] as $field) {
                $type = $field['name'];
                if ('tags' === $type) {
                    $tagTable = 'company' === $table ? 'company' : 'contact';

                    $qb->innerJoin("{$this->prefix}$table", "{$this->prefix}{$tagTable}_tag_link", 'tl');
                    $qb->innerJoin('tl', "{$this->prefix}{$tagTable}_tag", 'tag');
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
                                $field['value'] = $this->dateUtils->ymdToDbDate($field['value']);
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

        if (!$this->settingsManager->get('show_deleted_records') && $listConfig['deletedField']) {
            $qb->andWhere("{$listConfig['deletedField']}=0");
        }

        $countQb = clone $qb;

        // Add count join to count query builder:
        $this->addJoins($countQb, $listConfig['alias'], $listConfig['countJoins']);

        $filteredQb = clone $qb;
        if ($filter) {
            // Full term:
            $fullGroup = call_user_func_array(
                [$filteredQb->expr(), 'or'],
                getFilterExpressions($filteredQb, $listConfig['searchFields'], $filter)
            );

            // Words:
            $wordGroups = [];
            foreach (explode(' ', $filter) as $term) {
                if ('' === trim($term)) {
                    continue;
                }
                $wordGroups[] = call_user_func_array(
                    [$filteredQb->expr(), 'or'],
                    getFilterExpressions($filteredQb, $listConfig['searchFields'], $term)
                );
            }

            $filteredQb->andWhere(
                $filteredQb->expr()->or(
                    $fullGroup,
                    call_user_func_array(
                        [$filteredQb->expr(), 'and'],
                        $wordGroups
                    )
                )
            );
        }

        $filteredCountQb = clone $filteredQb;
        // Add count join to filtered count query builder:
        $this->addJoins($filteredCountQb, $listConfig['alias'], $listConfig['countJoins']);

        // Add display join to full and filtered query builder:
        $this->addJoins($qb, $listConfig['alias'], $listConfig['displayJoins']);
        $this->addJoins($filteredQb, $listConfig['alias'], $listConfig['displayJoins']);

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
                [$fieldName] = explode(' ', $shownFields[$column]['name']);
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
     * Get filter expressions for a filter term.
     *
     * @param QueryBuilder $qb           Query builder
     * @param array        $searchFields Search fields
     * @param string       $term         Search term
     *
     * @return array
     */
    function getFilterExpressions(QueryBuilder $qb, array $searchFields, string $term): array
    {
        $leftAnchored = !$this->settingsManager->get('dynamic_select_search_in_middle');
        $termPrefix = $leftAnchored ? '' : '%';

        foreach ($searchFields as $searchField) {
            switch ($searchField['type']) {
            case 'TEXT':
                $expressions[] = $qb->expr()->like(
                    $searchField['name'],
                    $qb->createNamedParameter("$termPrefix$term%")
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
                    $qb->createNamedParameter("$termPrefix$term%")
                );
                break;
            default:
                continue 2;
            }
        }
        return $expressions;
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
        foreach ($joins as $join) {
            switch ($join['type']) {
            case 'LEFT OUTER':
                $qb->leftJoin(
                    $alias,
                    $join['expr'] ?? ($this->prefix . $join['table']),
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
     * @param string $list    List
     * @param int    $startRow   Start row
     * @param int    $rowCount   Number of rows
     * @param string $filter     Filter
     * @param string $filterType Filter type
     * @param string $sort       Sort settings
     * @param int    $id         Item ID
     * @param ?ServerRequestInterface $request Request
     *
     * @return array
     */
    function createJSONSelectList($list, $startRow, $rowCount, $filter, $filterType,
        $sort, $id = null, ?ServerRequestInterface $request = null
    ) {
        global $dblink;

        $listConfig = $this->getListConfig($list);
        if (empty($id) && !sesAccessLevel($listConfig['accessLevels']) && !sesAdminAccess()) {
            ?>
    <div class="form_container">
            <?php echo $this->translator->translate('NoAccess') . "\n"?>
    </div>
            <?php
            return;
        }

        if ($sort) {
            if (!preg_match('/^[\w_,]+$/', $sort)) {
                http_response_code(400);
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
                http_response_code(400);
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

        if (!$this->settingsManager->get('show_deleted_records') && empty($id)
            && !empty($listConfig['deletedField'])
        ) {
            $strWhereClause = " WHERE {$listConfig['deletedField']}=0";
        }

        // Add Filter
        if ($filter = trim($filter)) {
            // For default_value there can be also the type in the filter
            if ($list == 'default_value' && $filterType) {
                $strWhereClause .= ($strWhereClause ? ' AND ' : ' WHERE ')
                    . 'type=?';
                $arrQueryParams[] = $filterType;
            }
            $strWhereClause .= ($strWhereClause ? ' AND ' : ' WHERE ')
                . createWhereClause(
                    $listConfig['searchFields'], $filter, $arrQueryParams,
                    !$this->settingsManager->get('dynamic_select_search_in_middle')
                );
        }

        // Filter out inactive bases and companies
        if (($list == 'company' || $list == 'base') && empty($id)) {
            $strWhereClause .= ($strWhereClause ? ' AND ' : ' WHERE ') . 'inactive=0';
        }

        if ($id) {
            $strWhereClause .= ($strWhereClause ? ' AND ' : ' WHERE ') . 'id=?';
            $arrQueryParams[] = $id;
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
            $exactSort = "IF(? IN ($fieldList, CONCAT_WS(' ', $fieldList)), 0, 1)";
            $arrQueryParams[] = $filter;
            if ($sort) {
                $sort = "$exactSort, $sort";
            } else {
                $sort = $exactSort;
            }
        }

        $customPrices = null;
        if ('product' === $list) {
            $companyId = $request->getParsedBody()['company'] ?? $request->getQueryParams()['company'] ?? null;
            if ($companyId) {
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
                        $value = $this->translator->translate($field['mappings'][$value]);
                    }
                    if (isset($field['callback'])) {
                        $value = $field['callback']($value);
                    }
                } elseif ($field['type'] == 'CURRENCY') {
                    $value = miscRound2Decim(
                        $value, $field['decimals'] ?? 2
                    );
                } elseif ($field['type'] == 'INTDATE') {
                    $value = $this->dateUtils->dbDateToDate($value);
                }

                if ('product' === $list) {
                    switch ($name) {
                    case 'description':
                        if (!empty($value)) {
                            $desc1[] = $value;
                        }
                        continue 2;
                    case 'product_group':
                        if (!empty($value)) {
                            $desc2[] = $this->translator->translate('ProductGroup') . ': '
                                . $value;
                        }
                        continue 2;
                    case 'vendor':
                        if (!empty($value)) {
                            $desc3[] = $this->translator->translate('ProductVendor') . ': '
                                . $value;
                        }
                        continue 2;
                    case 'vendors_code':
                        if (!empty($value)) {
                            $desc3[] = $this->translator->translate('ProductVendorsCode') . ': '
                                . $value;
                        }
                        continue 2;
                    case 'unit_price':
                        if (!empty($value) && $value != 0.0) {
                            $desc3[] = $this->translator->translate('Price') . ': '
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
                                $desc3[] = $this->translator->translate('ClientsPrice') . ': '
                                    . $unitPrice;
                            }
                        }
                        continue 2;
                    case 'stock_balance':
                        $desc3[] = $this->translator->translate('StockBalance') . ": $value";
                        continue 2;
                    }
                }

                if (isset($field['translate']) && $field['translate']) {
                    $value = $this->translator->translate($value);
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

            $markdown = $this->settingsManager->get('printout_markdown');
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
     * Get list configuration.
     *
     * @param string $list List
     *
     * @return ?array
     */
    public function getListConfig(string $list): ?array
    {
        $table = '';
        $primaryKey = 'id';
        $countJoins = [];
        $displayJoins = [];
        $listFilter = '';
        $groupBy = '';
        $deletedField = '';
        $levelsAllowed = [
            MLINVOICE_USER_ROLE_USER,
            MLINVOICE_USER_ROLE_BACKUPMGR
        ];
        switch ($list) {
        case 'company':
            $itemRoute = 'companies/{id}';
            $table = Company::class;
            $astrSearchFields = [
                [
                    'name' => 'companyName',
                    'type' => 'TEXT'
                ],
                [
                    'name' => 'companyId',
                    'type' => 'TEXT'
                ],
                [
                    'name' => 'email',
                    'type' => 'TEXT'
                ],
            ];
            $primaryKey = 'id';
            $deletedField = 'deleted';
            $listFields = [
                [
                    'name' => 'id',
                    'width' => 20,
                    'type' => 'CHECKBOX',
                    'order' => 'DESC',
                    'header' => '<input class="cb-select-all" type="checkbox" value="">',
                    'class' => 'cb-select-row',
                    'sort' => false
                ],
                [
                    'name' => 'company_name',
                    'width' => 150,
                    'type' => 'TEXT',
                    'header' => 'ClientName',
                    'select' => true
                ],
                [
                    'name' => 'company_id',
                    'width' => 100,
                    'type' => 'TEXT',
                    'header' => 'ClientVATID',
                    'select' => true
                ],
                [
                    'name' => 'inactive',
                    'width' => 100,
                    'type' => 'TEXT',
                    'header' => 'HeaderClientActive',
                    'mappings' => [
                        '0' => 'Active',
                        '1' => 'Inactive'
                    ]
                ],
                [
                    'name' => 'customer_no',
                    'width' => 100,
                    'type' => 'TEXT',
                    'header' => 'CustomerNr'
                ],
                [
                    'name' => 'email',
                    'width' => 150,
                    'type' => 'TEXT',
                    'header' => 'Email'
                ],
                [
                    'name' => 'phone',
                    'width' => 100,
                    'type' => 'TEXT',
                    'header' => 'Phone'
                ],
                [
                    'name' => 'gsm',
                    'width' => 100,
                    'type' => 'TEXT',
                    'header' => 'GSM'
                ]
            ];
            $strMainForm = 'company';
            break;

        case 'invoice_template':
            $itemRoute = 'invoice-templates/{id}';
        case 'archived_invoice':
            $itemRoute = 'invoices/{id}';
        case 'archived_offer':
            $itemRoute = 'offers/{id}';
        case 'invoice':
            $itemRoute = 'invoices/{id}';
            $levelsAllowed[] = MLINVOICE_USER_ROLE_READONLY;

            $listFilter = 'i.archived = 0';
            if ('archived_invoices' === $list) {
                $listFilter = 'i.archived = 1 AND i.state_id NOT IN (' . implode(',', $this->getOfferStateIds()) . ')';
            } elseif ('archived_offers' === $list) {
                $listFilter = 'i.archived = 1 AND i.state_id IN (' . implode(',', $this->getOfferStateIds()) . ')';
            }

            $table = 'invoice';
            $tableAlias = 'i';

            $countJoins = $displayJoins = [
                [
                    'type' => 'LEFT OUTER',
                    'table' => 'base',
                    'alias' => 'b',
                    'condition' => 'i.base_id = b.id',
                ],
                [
                    'type' => 'LEFT OUTER',
                    'table' => 'company',
                    'alias' => 'c',
                    'condition' => 'i.company_id = c.id',
                ],
                [
                    'type' => 'LEFT OUTER',
                    'table' => 'invoice_state',
                    'alias' => 's',
                    'condition' => 'i.state_id = s.id',
                ],
            ];

            $displayJoins[] = $this->getInvoiceTotalJoinQuery();

            $astrSearchFields = [
                [
                    'name' => $list === 'offer' ? 'i.id' : 'i.invoice_no',
                    'type' => 'TEXT'
                ],
                [
                    'name' => 'i.ref_number',
                    'type' => 'TEXT'
                ],
                [
                    'name' => 'i.name',
                    'type' => 'TEXT'
                ],
                [
                    'name' => 'b.name',
                    'type' => 'TEXT'
                ],
                [
                    'name' => 'c.company_name',
                    'type' => 'TEXT'
                ]
            ];
            $primaryKey = 'i.id';
            $deletedField = 'i.deleted';
            $listFields = [
                [
                    'name' => 'i.id',
                    'width' => 20,
                    'type' => 'CHECKBOX',
                    'order' => 'DESC',
                    'header' => '<input class="cb-select-all" type="checkbox" value="">',
                    'class' => 'cb-select-row',
                    'sort' => false
                ],
                [
                    'name' => 'i.invoice_date',
                    'width' => 80,
                    'type' => 'INTDATE',
                    'order' => 'DESC',
                    'header' => 'HeaderInvoiceDate',
                    'visible' => 'invoice_templates' !== $list,
                ],
                [
                    'name' => 'i.payment_date',
                    'width' => 80,
                    'type' => 'INTDATE',
                    'order' => 'DESC',
                    'header' => 'HeaderInvoicePaymentDate',
                    'visible' => 'archived_invoices' === $list,
                ],
                [
                    'name' => 'i.due_date',
                    'width' => 80,
                    'type' => 'INTDATE',
                    'order' => 'DESC',
                    'header' => 'HeaderInvoiceDueDate',
                    'visible' => 'invoice_templates' !== $list,
                ],
                [
                    'name' => $list === 'offer' ? 'i.id' : 'i.invoice_no',
                    'width' => 80,
                    'type' => 'TEXT',
                    'header' => 'HeaderInvoiceNr'
                ],
                [
                    'name' => 'b.name base_name',
                    'width' => 150,
                    'type' => 'TEXT',
                    'header' => 'HeaderInvoiceBase'
                ],
                [
                    'name' => 'c.company_name',
                    'width' => 150,
                    'type' => 'TEXT',
                    'header' => 'HeaderInvoiceClient'
                ],
                [
                    'name' => 'i.name',
                    'width' => 100,
                    'type' => 'TEXT',
                    'header' => in_array($list, ['offer', 'archived_offers']) ? 'HeaderOfferName' : 'HeaderInvoiceName'
                ],
                [
                    'name' => 'state',
                    'sql' => 's.name state',
                    'width' => 120,
                    'type' => 'TEXT',
                    'header' => 'HeaderInvoiceState',
                    'translate' => true,
                    'visible' => 'invoice_templates' !== $list,
                ],
                [
                    'name' => 'i.interval_type',
                    'width' => 60,
                    'type' => 'TEXT',
                    'header' => 'HeaderInvoiceIntervalType',
                    'mappings' => $this->getIntervalOptions(),
                    'visible' => 'invoice_templates' === $list,
                ],
                [
                    'name' => 'i.next_interval_date',
                    'width' => 60,
                    'type' => 'INTDATE',
                    'header' => 'HeaderInvoiceNextIntervalDate',
                    'visible' => 'invoice_templates' === $list,
                ],
                [
                    'name' => 'i.ref_number',
                    'width' => 100,
                    'type' => 'TEXT',
                    'header' => 'HeaderInvoiceReference'
                ],
                [
                    'name' => 'i.reference',
                    'width' => 100,
                    'type' => 'TEXT',
                    'header' => 'HeaderInvoiceClientsReference',
                    'visible' => false
                ],
                [
                    'name' => 'total_price',
                    'sql' => 'SUM(it.row_total) as total_price',
                    'width' => 80,
                    'type' => 'CURRENCY',
                    'header' => 'HeaderInvoiceTotal'
                ]
            ];
            $groupBy = 'i.id, i.deleted, i.invoice_date, i.due_date, i.invoice_no,'
                . ' b.name, c.company_name, i.name, s.name, i.ref_number';
            $strMainForm = $list === 'invoice_templates' ? 'invoice_template' : 'invoice';
            break;

        /***********************************************************************
         SETTINGS
        ***********************************************************************/
        case 'base':
            $itemRoute = 'bases/{id}';
            $table = 'base';
            $astrSearchFields = [
                [
                    'name' => 'name',
                    'type' => 'TEXT',
                ],
                [
                    'name' => 'company_id',
                    'type' => 'TEXT'
                ],
                [
                    'name' => 'contact_person',
                    'type' => 'TEXT'
                ],
                [
                    'name' => 'email',
                    'type' => 'TEXT'
                ],
            ];
            $primaryKey = 'id';
            $deletedField = 'deleted';
            $listFields = [
                [
                    'name' => 'name',
                    'width' => 200,
                    'type' => 'TEXT',
                    'header' => 'BaseName',
                    'select' => true,
                ],
                [
                    'name' => 'company_id',
                    'width' => 100,
                    'type' => 'TEXT',
                    'header' => 'ClientVATID',
                    'select' => true,
                ],
                [
                    'name' => 'contact_person',
                    'width' => 150,
                    'type' => 'TEXT',
                    'header' => 'ContactPerson'
                ],
                [
                    'name' => 'email',
                    'width' => 150,
                    'type' => 'TEXT',
                    'header' => 'Email'
                ]
            ];
            $strMainForm = 'base';
            break;

        case 'invoice_state':
            $itemRoute = 'invoice-states/{id}';
            $table = 'invoice_state';
            $astrSearchFields = [
                [
                    'name' => 'name',
                    'type' => 'TEXT'
                ]
            ];
            $primaryKey = 'id';
            $deletedField = 'deleted';
            $listFields = [
                [
                    'name' => 'order_no',
                    'width' => 150,
                    'type' => 'TEXT',
                    'header' => 'OrderNr'
                ],
                [
                    'name' => 'name',
                    'width' => 450,
                    'type' => 'TEXT',
                    'header' => 'Status',
                    'pretranslate' => true
                ]
            ];
            // array('order_no','name');
            $strMainForm = 'invoice_state';
            break;

        case 'invoice_type':
            $itemRoute = 'invoice-types/{id}';
            $table = 'invoice_type';
            $astrSearchFields = [
                [
                    'name' => 'identifier',
                    'type' => 'TEXT'
                ],
                [
                    'name' => 'name',
                    'type' => 'TEXT'
                ]
            ];
            $primaryKey = 'id';
            $deletedField = 'deleted';
            $listFields = [
                [
                    'name' => 'order_no',
                    'width' => 150,
                    'type' => 'TEXT',
                    'header' => 'OrderNr'
                ],
                [
                    'name' => 'identifier',
                    'width' => 150,
                    'type' => 'TEXT',
                    'header' => 'Identifier',
                    'select' => true
                ],
                [
                    'name' => 'name',
                    'width' => 450,
                    'type' => 'TEXT',
                    'header' => 'Name',
                    'select' => true
                ]
            ];
            $strMainForm = 'invoice_type';
            break;

        case 'product':
            $itemRoute = 'products/{id}';
            $table = 'product';
            $astrSearchFields = [
                [
                    'name' => 'product_code',
                    'type' => 'TEXT'
                ],
                [
                    'name' => 'product_name',
                    'type' => 'TEXT'
                ],
                [
                    'name' => 'description',
                    'type' => 'TEXT'
                ],
                [
                    'name' => 'product_group',
                    'type' => 'TEXT'
                ],
            ];
            $primaryKey = 'id';
            $deletedField = 'deleted';
            $listFields = [
                [
                    'name' => 'id',
                    'width' => 20,
                    'type' => 'CHECKBOX',
                    'order' => 'DESC',
                    'header' => '<input class="cb-select-all" type="checkbox" value="">',
                    'class' => 'cb-select-row',
                    'sort' => false
                ],
                [
                    'name' => 'order_no',
                    'type' => 'TEXT',
                    'header' => 'OrderNr'
                ],
                [
                    'name' => 'product_code',
                    'type' => 'TEXT',
                    'header' => 'ProductCode',
                    'select' => true
                ],
                [
                    'name' => 'product_name',
                    'type' => 'TEXT',
                    'header' => 'ProductName',
                    'select' => true
                ],
                [
                    'name' => 'description',
                    'type' => 'TEXT',
                    'header' => 'ProductDescription',
                    'select' => true,
                    'visible' => false,
                ],
                [
                    'name' => 'product_group',
                    'type' => 'TEXT',
                    'header' => 'ProductGroup',
                    'select' => true
                ],
                [
                    'name' => 'vendor',
                    'width' => 0,
                    'type' => 'HIDDEN',
                    'header' => '',
                    'select' => true
                ],
                [
                    'name' => 'vendors_code',
                    'width' => 0,
                    'type' => 'HIDDEN',
                    'header' => '',
                    'select' => true
                ],
                [
                    'name' => 'unit_price',
                    'type' => 'CURRENCY',
                    'header' => 'UnitPrice',
                    'decimals' => $this->settingsManager->get('unit_price_decimals'),
                    'select' => true
                ],
                [
                    'name' => 'custom_price',
                    'type' => 'CURRENCY',
                    'header' => 'ClientsPrice',
                    'decimals' => $this->settingsManager->get('unit_price_decimals'),
                    'virtual' => true,
                    'select' => true,
                    'sort' => false
                ],
                [
                    'name' => 'discount',
                    'type' => 'CURRENCY',
                    'header' => 'DiscountPct'
                ],
                [
                    'name' => 'discount_amount',
                    'type' => 'CURRENCY',
                    'header' => 'DiscountAmount',
                    'decimals' => $this->settingsManager->get('unit_price_decimals')
                ],
                [
                    'name' => 'stock_balance',
                    'type' => 'CURRENCY',
                    'header' => 'StockBalance',
                    'decimals' => 2,
                    'select' => $this->settingsManager->get('invoice_display_product_stock_in_selection')
                ]
            ];

            $strMainForm = 'product';
            break;

        case 'row_type':
            $itemRoute = 'row-types/{id}';
            $table = 'row_type';
            $astrSearchFields = [
                [
                    'name' => 'name',
                    'type' => 'TEXT'
                ]
            ];
            $primaryKey = 'id';
            $deletedField = 'deleted';
            $listFields = [
                [
                    'name' => 'order_no',
                    'width' => 150,
                    'type' => 'TEXT',
                    'header' => 'OrderNr'
                ],
                [
                    'name' => 'name',
                    'width' => 450,
                    'type' => 'TEXT',
                    'header' => 'RowType',
                    'pretranslate' => true
                ]
            ];
            $strMainForm = 'row_type';
            break;

        case 'delivery_terms':
            $table = 'delivery_terms';
            $astrSearchFields = [
                [
                    'name' => 'name',
                    'type' => 'TEXT'
                ]
            ];
            $primaryKey = 'id';
            $deletedField = 'deleted';
            $listFields = [
                [
                    'name' => 'order_no',
                    'width' => 150,
                    'type' => 'TEXT',
                    'header' => 'OrderNr'
                ],
                [
                    'name' => 'name',
                    'width' => 450,
                    'type' => 'TEXT',
                    'header' => 'DeliveryTerms'
                ]
            ];
            $strMainForm = 'delivery_terms';
            break;

        case 'delivery_method':
            $itemRoute = 'delivery-methods/{id}';
            $table = 'delivery_method';
            $astrSearchFields = [
                [
                    'name' => 'name',
                    'type' => 'TEXT'
                ]
            ];
            $primaryKey = 'id';
            $deletedField = 'deleted';
            $listFields = [
                [
                    'name' => 'order_no',
                    'width' => 150,
                    'type' => 'TEXT',
                    'header' => 'OrderNr'
                ],
                [
                    'name' => 'name',
                    'width' => 450,
                    'type' => 'TEXT',
                    'header' => 'DeliveryMethod'
                ]
            ];
            $strMainForm = 'delivery_method';
            break;

        case 'print_template':
            $itemRoute = 'print-templates/{id}';
            $table = 'print_template';
            $astrSearchFields = [
                [
                    'name' => 'name',
                    'type' => 'TEXT'
                ],
                [
                    'name' => 'filename',
                    'type' => 'TEXT'
                ],
            ];
            $primaryKey = 'id';
            $deletedField = 'deleted';
            $listFields = [
                [
                    'name' => 'order_no',
                    'width' => 150,
                    'type' => 'TEXT',
                    'header' => 'OrderNr'
                ],
                [
                    'name' => 'type',
                    'width' => 150,
                    'type' => 'TEXT',
                    'header' => 'PrintTemplateType',
                    'mappings' => [
                        'invoice' => 'PrintTemplateTypeInvoice',
                        'offer' => 'PrintTemplateTypeOffer'
                    ]
                ],
                [
                    'name' => 'name',
                    'width' => 200,
                    'type' => 'TEXT',
                    'header' => 'PrintTemplateName',
                    'pretranslate' => true
                ],
                [
                    'name' => 'inactive',
                    'width' => 100,
                    'type' => 'TEXT',
                    'header' => 'HeaderPrintTemplateActive',
                    'mappings' => [
                        '0' => 'Active',
                        '1' => 'Inactive'
                    ]
                ],
                [
                    'name' => 'filename',
                    'width' => 200,
                    'type' => 'TEXT',
                    'header' => 'PrintTemplateFileName'
                ],
                [
                    'name' => 'parameters',
                    'width' => 150,
                    'type' => 'TEXT',
                    'header' => 'PrintTemplateParameters'
                ]
            ];
            $strMainForm = 'print_template';
            break;

        case 'default_value':
            $itemRoute = 'default-values/{id}';
            $table = 'default_value';
            $astrSearchFields = [
                [
                    'name' => 'name',
                    'type' => 'TEXT'
                ],
                [
                    'name' => 'content',
                    'type' => 'TEXT'
                ],
                [
                    'name' => 'additional',
                    'type' => 'TEXT'
                ],
            ];
            $primaryKey = 'id';
            $deletedField = 'deleted';
            $listFields = [
                [
                    'name' => 'order_no',
                    'width' => 150,
                    'type' => 'TEXT',
                    'header' => 'OrderNr'
                ],
                [
                    'name' => 'type',
                    'width' => 150,
                    'type' => 'TEXT',
                    'header' => 'DefaultValueType',
                    'mappings' => [
                        'info' => 'Info',
                        'foreword' => 'Foreword',
                        'afterword' => 'Afterword',
                        'email' => 'Email'
                    ]
                ],
                [
                    'name' => 'name',
                    'width' => 450,
                    'type' => 'TEXT',
                    'header' => 'Name',
                    'select' => true
                ]
            ];
            $strMainForm = 'default_value';
            break;

        case 'attachment':
            $itemRoute = 'attachments/{id}';
            $table = 'attachment';
            $astrSearchFields = [
                [
                    'name' => 'name',
                    'type' => 'TEXT'
                ],
                [
                    'name' => 'description',
                    'type' => 'TEXT'
                ],
                [
                    'name' => 'filename',
                    'type' => 'TEXT'
                ],
            ];
            $primaryKey = 'id';
            $listFields = [
                [
                    'name' => 'order_no',
                    'width' => 150,
                    'type' => 'TEXT',
                    'header' => 'OrderNr'
                ],
                [
                    'name' => 'name',
                    'width' => 200,
                    'type' => 'TEXT',
                    'header' => 'Name'
                ],
                [
                    'name' => 'date',
                    'width' => 100,
                    'type' => 'INTDATE',
                    'header' => 'Date'
                ],
                [
                    'name' => 'filename',
                    'width' => 200,
                    'type' => 'TEXT',
                    'header' => 'File'
                ],
                [
                    'name' => 'filesize',
                    'width' => 200,
                    'type' => 'INT',
                    'callback' => 'fileSizeToHumanReadable',
                    'header' => 'HeaderFileSize'
                ]
            ];
            $strMainForm = 'attachment';
            break;

        case 'company_tag':
            $table = 'company_tag';
            $astrSearchFields = [
                [
                    'name' => 'tag',
                    'type' => 'TEXT'
                ]
            ];
            $primaryKey = 'id';
            $listFields = [
                [
                    'name' => 'tag',
                    'width' => 450,
                    'type' => 'TEXT',
                    'header' => '',
                    'select' => true
                ]
            ];
            $strMainForm = 'company';
            break;

        case 'contact_tag':
            $table = 'contact_tag';
            $astrSearchFields = [
                [
                    'name' => 'tag',
                    'type' => 'TEXT'
                ]
            ];
            $primaryKey = 'id';
            $listFields = [
                [
                    'name' => 'tag',
                    'width' => 450,
                    'type' => 'TEXT',
                    'header' => '',
                    'select' => true
                ]
            ];
            break;

        /***********************************************************************
         SYSTEM
        ***********************************************************************/
        case 'session_type':
            $itemRoute = 'session-types/{id}';
            $levelsAllowed = [
                99
            ];
            $table = 'session_type';
            $astrSearchFields = [
                [
                    'name' => 'name',
                    'type' => 'TEXT'
                ]
            ];
            $primaryKey = 'id';
            $deletedField = 'deleted';
            $listFields = [
                [
                    'name' => 'order_no',
                    'width' => 150,
                    'type' => 'TEXT',
                    'header' => 'OrderNr'
                ],
                [
                    'name' => 'name',
                    'width' => 450,
                    'type' => 'TEXT',
                    'header' => 'SessionType',
                    'pretranslate' => true
                ]
            ];
            $strMainForm = 'session_type';
            break;

        case 'user':
            $itemRoute = 'users/{id}';
            $levelsAllowed = [
                MLINVOICE_USER_ROLE_ADMIN
            ];
            $table = 'users';
            $astrSearchFields = [
                [
                    'name' => 'name',
                    'type' => 'TEXT'
                ],
                [
                    'name' => 'email',
                    'type' => 'TEXT'
                ],
                [
                    'name' => 'login',
                    'type' => 'TEXT'
                ],
            ];
            $primaryKey = 'id';
            $deletedField = 'deleted';
            $listFields = [
                [
                    'name' => 'name',
                    'width' => 350,
                    'type' => 'TEXT',
                    'header' => 'UserName'
                ],
                [
                    'name' => 'login',
                    'width' => 250,
                    'type' => 'TEXT',
                    'header' => 'LoginName'
                ],
                [
                    'name' => 'email',
                    'width' => 250,
                    'type' => 'TEXT',
                    'header' => 'Email'
                ],
            ];
            $strMainForm = 'user';
            break;

        default:
            break;
        }

        return $table ? [
            'accessLevels' => $levelsAllowed,
            'table' => $table,
            'alias' => $tableAlias ?? null,
            'displayJoins' => $displayJoins,
            'countJoins' => $countJoins,
            'groupBy' => $groupBy,
            'listFilter' => $listFilter,
            'primaryKey' => $primaryKey,
            'deletedField' => $deletedField,
            'fields' => $listFields,
            'searchFields' => $astrSearchFields ?? null,
            'mainForm' => $strMainForm ?? '',
            'itemRoute' => $itemRoute ?? null,
        ] : [];
    }

    /**
     * Get total sums for invoice list
     *
     * @param array $query    Query
     * @param int   $searchId Search ID
     *
     * @return array
     */
    public function getInvoiceListTotal(array $query, ?int $searchId = null): array
    {
        $listConfig = $this->getListConfig('invoice');
        $queries = $this->createListQuery('invoice', 'invoice', 0, 0, [], '', $query, $searchId);
        $query = $queries['fullQuery'];
        $query
            ->select('sum(it.row_total)')
            ->from($this->prefix . $listConfig['table'], $listConfig['alias']);
        // Reset grouping and order to get just a single line:
        $query->add('groupBy', [], false);
        $query->add('orderBy', [], false);
        $sum = $query->executeQuery()->fetchOne();
        return [
            'sum' => null !== $sum ? $sum : 0,
            'sum_rounded' => $this->numberFormatter->roundNumber((float)$sum, 2, '.', '')
        ];
    }

    /**
     * Get join query to retrieve invoice total sum
     *
     * @return array
     */
    public function getInvoiceTotalJoinQuery(): array
    {
        $prefix = $this->prefix;
        if ($this->settingsManager->get('invoice_display_vatless_price_in_list')) {
            $expr = <<<EOT
                (SELECT ir.invoice_id,
                CASE WHEN ir.partial_payment = 0 THEN
                CASE WHEN ir.vat_included = 0
                    THEN (ir.price * (1 - IFNULL(ir.discount, 0) / 100)
                        - IFNULL(ir.discount_amount, 0)) * ir.pcs
                    ELSE (ir.price * (1 - IFNULL(ir.discount, 0) / 100)
                        - IFNULL(ir.discount_amount, 0)) * ir.pcs / (1 + ir.vat / 100)
                    END
                ELSE
                    ir.price
                END as row_total
                FROM {$prefix}invoice_row ir
                WHERE ir.deleted = 0)
                EOT;
        } else {
            $expr = <<<EOT
                (SELECT ir.invoice_id,
                CASE WHEN ir.partial_payment = 0 THEN
                CASE WHEN ir.vat_included = 0
                    THEN (ir.price * (1 - IFNULL(ir.discount, 0) / 100)
                        - IFNULL(ir.discount_amount, 0)) * ir.pcs * (1 + ir.vat / 100)
                    ELSE (ir.price * (1 - IFNULL(ir.discount, 0) / 100)
                        - IFNULL(ir.discount_amount, 0)) * ir.pcs
                    END
                ELSE
                    ir.price
                END as row_total
                FROM {$prefix}invoice_row ir
                WHERE ir.deleted = 0)
                EOT;
        }

        return [
            'type' => 'LEFT OUTER',
            'expr' => $expr,
            'alias' => 'it',
            'condition' => 'i.id = it.invoice_id'
        ];
    }

    /**
     * Remove table or alias prefix from a field name
     *
     * @param string $fieldName Field name
     *
     * @return string
     */
    protected function stripPrefix(string $fieldName): string
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
    protected function getFieldNameOrAlias(string $fieldSpec): string
    {
        $parts = explode(' ', $fieldSpec);
        $last = end($parts);
        return $this->stripPrefix($last);
    }

    /**
     * Get all offer state id's
     *
     * @return array
     */
    protected function getOfferStateIds(): array
    {
        $result = [];
        foreach ($this->invoiceStateRepository->findAllOfferStates() as $state) {
            $result[] = $state->getId();
        }
        return $result;
    }

    /**
     * Get invoice interval options
     *
     * N.B. Update copy_invoice accordingly too!
     *
     * @return array
     */
    protected function getIntervalOptions(): array
    {
        $intervalOptions = [
            '0' => $this->translator->translate('InvoiceIntervalNone'),
            '2' => $this->translator->translate('InvoiceIntervalMonth'),
            '3' => $this->translator->translate('InvoiceIntervalYear')
        ];
        for ($i = 2; $i <= 6; $i++) {
            $intervalOptions[(string)($i + 2)] = str_replace('%d', (string)$i, $this->translator->translate('InvoiceIntervalMonths'));
        }
        // We don't currently have 7-11 months, but leave room for them in keys 9 - 13 just in case!
        for ($i = 2; $i <= 3; $i++) {
            $intervalOptions[(string)($i + 12)] = str_replace('%d', (string)$i, $this->translator->translate('InvoiceIntervalYears'));
        }
        return $intervalOptions;
    }
}
