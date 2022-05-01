<?php
/**
 * Search
 *
 * PHP version 7
 *
 * Copyright (C) Ere Maijala 2022
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
require_once 'htmlfuncs.php';
require_once 'sqlfuncs.php';
require_once 'sessionfuncs.php';
require_once 'miscfuncs.php';
require_once 'datefuncs.php';
require_once 'form_config.php';

/**
 * Search
 *
 * @category MLInvoice
 * @package  MLInvoice\Base
 * @author   Ere Maijala <ere@labs.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://labs.fi/mlinvoice.eng.php
 */
class Search
{
    /**
     * Display search form
     *
     * @return void
     */
    public function formAction()
    {
        if (!($searchData = $this->getSearchFromRequest())) {
            return;
        }
        $type = $searchData['type'];
        $searchGroups = $searchData['searchGroups'];
        $formConfig = getFormConfig($type, 'ext_search');
        $formConfig['fields'] = array_map(
            function ($field) {
                if (isset($field['label'])) {
                    $field['label'] = Translator::translate($field['label']);
                }
                if ('LIST' === $field['type']) {
                    if (is_string($field['listquery'])) {
                        $values = [];
                        $res = dbQueryCheck($field['listquery']);
                        while ($row = mysqli_fetch_row($res)) {
                            $values[$row[0]] = $row[1];
                        }
                        $field['options'] = $values;
                    } else {
                        $field['options'] = $field['listquery'];
                    }
                    $field['options'] = array_map('Translator::translate', $field['options']);
                }
                return $field;
            },
            $formConfig['fields']
        );
        $listValues = [];
        foreach ($formConfig['fields'] as $field) {
            if (in_array($field['type'], $formConfig['searchFieldTypes'])) {
                $listValues[$field['name']] = str_replace(
                    '<br>', ' ',
                    Translator::translate($field['label'])
                );
            }
        }

        ?>

<div role="search">
  <form id="search_form" method="GET">
    <input type="hidden" name="func" value="results">
    <input type="hidden" name="type" value="<?php echo htmlentities($type)?>">
    <div class="row mb-2 p-2 group-operator hidden">
      <div class="col-sm-6">
        <label for="operator" class="form-label"><?php echo Translator::translate('GroupHandlingMethod')?></label>
        <select id="operator" name="s_op" class="form-select">
          <option value="AND"<?php echo 'AND' === $searchGroups['operator'] ? ' selected' : ''?>><?php echo Translator::translate('AllGroups')?></option>
          <option value="OR"<?php echo 'OR' === $searchGroups['operator'] ? ' selected' : ''?>><?php echo Translator::translate('AnyGroup')?></option>
        </select>
      </div>
    </div>
    <div id="search_groups">
      <template id="template_group">
        <div class="card mb-4 group">
          <div class="card-header">
            <div><h2><?php echo Translator::translate('Hakuryhmä')?></h2></div>
            <div>
              <a href="#" role="button" class="btn btn-outline-primary btn-sm delete-group"
                title="<?php echo Translator::translate('DeleteSearchGroup')?>"
                aria-title="<?php echo Translator::translate('DeleteSearchGroup')?>"
              >
                <i class="icon-minus"></i>
              </a>
            </div>
          </div>
          <div class="card-body">
            <div class="row justify-content-end controls">
              <div class="col-sm-6 field-operator mt-2 mb-4 hidden">
                <select class="form-select operator">
                  <option value="AND" selected><?php echo Translator::translate('AllFieldsMustMatch')?></option>
                  <option value="OR"><?php echo Translator::translate('AnyFieldMustMatch')?></option>
                </select>
              </div>
            </div>
            <div class="fields">
            </div>
            <div class="row justify-content-end controls">
              <div class="col-sm-6 mb-2 mt-4">
                <?php echo htmlListBox('', $listValues, '', 'form-select add-search-field', false, 'SelectSearchField'); ?>
              </div>
            </div>
            <div class="mb-2">
            </div>
          </div>
        </div>
      </template>
    </div>
    <div class="mb-2 p-2 group-add">
      <a href="#" role="button" class="btn btn-outline-primary" id="add_group">
        <i class="icon-plus"></i><?php echo Translator::translate('AddSearchGroup')?>
      </a>
    </div>
    <div class="mb-2 p-2 search-buttons">
      <a href="#" role="button" class="btn btn-primary form-submit" id="search">
        <?php echo Translator::translate('Search')?>
      </a>
    </div>
  </form>
</div>

<script>
  MLInvoice.Search.initSearchForm(<?php echo json_encode($formConfig); ?>, <?php echo json_encode($searchGroups['groups'])?>);
</script>
        <?php
    }

    /**
     * Display search results
     *
     * @return void
     */
    public function resultsAction()
    {
        if (!($searchData = $this->getSearchFromRequest())) {
            return;
        }
        $type = $searchData['type'];

        $terms = htmlentities(
            $this->getSearchDescription($type, $searchData['searchGroups'])
        ) ?: Translator::translate('NoSearchTerms');
        $searchDesc = Translator::translate(
            'ResultsForSearch',
            ['%%description%%' => $terms]
        );
        include_once 'list.php';
        createList($type, $type, "{$type}_results", $searchDesc, $searchData['searchId'], 'invoice' === $type);
    }

    /**
     * Save a search
     *
     * @param string $name         Search name
     * @param array  $searchGroups Search groups
     *
     * @return array [success => (bool), errors => (string)]
     */
    public function saveSearch(string $name, array $searchGroups)
    {
        if ('' === $name) {
            return [
                'success' => false,
                'errors' => Translator::translate('ErrorNoSearchName'),
            ];
        }
        try {
            $query = 'INSERT INTO {prefix}quicksearch(user_id, name, func, whereclause) '
                . 'VALUES (?, ?, ?, ?)';
            $jsonGroups = json_encode($searchGroups);
            dbParamQuery(
                $query,
                [
                    $_SESSION['sesUSERID'],
                    $name,
                    $searchGroups['type'],
                    $jsonGroups
                ]
            );
        } catch (\Exception $e) {
            return [
                'success' => false,
                'errors' => $e->getMessage()
            ];
        }

        return [
            'success' => true,
            'errors' => '',
        ];
    }

    /**
     * Edit searches
     *
     * @return void
     */
    public function editSearchesAction(): void
    {
        $type = getPostOrQuery('type', 'invoice');
        $action = getPost('action');
        if ('edit' === $action) {
            $params = [
                'func' => 'search',
                'type' => $type,
                'search_id' => getPost('search'),
            ];
            header('Location: index.php?' . http_build_query($params));
            exit();
        } elseif ('delete' === $action) {
            deleteQuickSearch(getPost('search'));
        } elseif ('search' === $action) {
            $params = [
              'func' => 'results',
              'type' => $type,
              'search_id' => getPost('search'),
            ];
            header('Location: index.php?' . http_build_query($params));
            exit();
        }
?>
<form method="POST">
  <input type="hidden" name="func" value="edit_searches">
  <input type="hidden" name="action" value="">
  <div class="row">
    <div class="col-4">
      <div class="mb-3">
        <label for="search_type" class="form-label"><?php echo Translator::translate('SavedSearchType')?></label>
        <select id="search_type" name="type" class="form-select" size="2" data-form-submit-on-change>
          <option value="invoice" <?php echo 'invoice' === $type ? 'selected' : ''?>>
            <?php echo Translator::translate('InvoicesAndOffers') ?>
          </option>
          <option value="company" <?php echo 'company' === $type ? 'selected' : ''?>>
            <?php echo Translator::translate('Clients') ?>
          </option>
        </select>
      </div>
      <div class="mb-1">
        <label for="search" class="form-label"><?php echo Translator::translate('SavedSearches')?></label>
        <select id="search" name="search" class="form-select" size="10">
          <?php foreach (getQuickSearches($type) as $search) { ?>
            <option value="<?php echo htmlentities($search['id'])?>"><?php echo htmlentities($search['name'])?></option>
          <?php } ?>
        </select>
      </div>
      <div>
        <a role="button" class="btn btn-secondary form-submit" data-set-field="action=edit">
          <?php echo Translator::translate('Edit') ?>
        </a>
        <a role="button" class="btn btn-secondary form-submit" data-set-field="action=delete">
          <?php echo Translator::translate('Delete') ?>
        </a>
        <a role="button" class="btn btn-secondary form-submit" data-set-field="action=search">
          <?php echo Translator::translate('Search') ?>
        </a>
      </div>
    </div>
    <div class="col-4">
    </div>
    <div class="col-4">
    </div>
  </div>
</form>
        <?php
    }

    /**
     * Parse search groups from a request
     *
     * @param array $request Request parameters
     *
     * @return array
     */
    public function getSearchGroups(array $request): array
    {
        $searchGroups = [
            'type' => $request['type'] ?? '',
            'operator' => $request['s_op'] ?? 'AND',
            'groups' => []
        ];
        for ($group = 1; $group < 100; $group++) {
            $groupOperator = $request["s_op$group"] ?? null;
            if (null === $groupOperator) {
                break;
            }
            $searchGroup = [
                'operator' => $groupOperator,
                'fields' => []
            ];
            foreach ($request["s_field$group"] as $i => $value) {
                if (!($name = $request["s_type$group"][$i] ?? null)) {
                    continue;
                }
                if (!($comparison = $request["s_cmp$group"][$i] ?? null)) {
                    continue;
                }
                $searchGroup['fields'][] = [
                    'name' => $name,
                    'value' => $value,
                    'comparison' => $comparison,
                ];
            }
            $searchGroups['groups'][] = $searchGroup;
        }

        return $searchGroups;
    }

    /**
     * Convert a legacy search query
     *
     * @param string $type  Search type
     * @param string $query Search query
     *
     * @return array
     */
    public function convertLegacySearch(string $type, string $query): array
    {
        $formConfig = getFormConfig($type, 'ext_search');

        $groups = [
          'operator' => 'AND',
          'groups' => []
        ];
        if (!$query) {
            return $groups;
        }

        $boolean = '';
        $fields = [];
        $query = urldecode($query);
        while (extractSearchTerm($query, $field, $operator, $term, $nextBool)) {
            if ('tags' === $field) {
                $fields[] = [
                    'name' => 'tags',
                    'value' => $term,
                    'comparison' => 'eq',
                ];
            } else {
                $parts = explode('.', $field, 2);
                if ($parts[0] . '.' === $formConfig['tableAlias'] && isset($parts[1])) {
                    $field = $parts[1];
                }
                $comparisons = [
                    '=' => 'eq',
                    '!=' => 'ne',
                    '<' => 'lt',
                    '<=' => 'lte',
                    '>' => 'gt',
                    '>=' => 'gte',
                    'LIKE' => 'eq',
                    'NOT LIKE' => 'ne',
                ];
                $fields[] = [
                    'name' => $field,
                    'value' => str_replace("%-", "%", $term),
                    'comparison' => $comparisons[$operator] ?? 'eq'
                ];

                if (!$nextBool) {
                    break;
                }
                // If next boolean is different from current, add current fields
                // and start adding a new group:
                if ($boolean && $nextBool !== $boolean) {
                    $groups['groups'][] = [
                        'operator' => trim($boolean ?: 'AND'),
                        'fields' => $fields
                    ];
                    $fields = [];
                }

                $boolean = $nextBool;
            }
        }
        if ($fields) {
            $groups['groups'][] = [
                'operator' => trim($boolean ?: 'AND'),
                'fields' => $fields
            ];
        }

        return $groups;
    }

    /**
     * Get search data from request params
     *
     * @return array
     */
    protected function getSearchFromRequest(): array
    {
        if ($searchId = getQuery('search_id')) {
            return $this->getSavedSearch(intval($searchId));
        }
        $type = getQuery('type');
        $searchGroups = $this->getSearchGroups($_GET);
        return $type ? compact('type', 'searchGroups', 'searchId') : [];
    }

    /**
     * Get a saved search
     *
     * @param int $searchId Search ID
     *
     * @return array
     */
    protected function getSavedSearch(int $searchId): array
    {
        if (!($search = getQuickSearch($searchId))) {
            return [];
        }
        $type = $search['func'];
        if ('companies' === $type) {
            $type = 'company';
        } elseif (substr($type, -1) === 's') {
            $type = substr($type, 0, -1);
        }
        if (strncmp($search['whereclause'], '{', 1) === 0) {
            $searchGroups = json_decode($search['whereclause'], true);
        } else {
            $searchGroups = $this->convertLegacySearch($type, $search['whereclause']);
        }

        return compact('type', 'searchGroups', 'searchId');
    }

    /**
     * Get a description string for search terms
     *
     * @param string $type         Search type
     * @param array  $searchGroups Search groups
     *
     * @return string
     */
    protected function getSearchDescription(string $type, array $searchGroups)
    {
        if (!($formConfig = getFormConfig($type, 'ext_search'))) {
            return '';
        }
        $operator = $searchGroups['operator'];
        $groups = [];
        foreach ($searchGroups['groups'] as $group) {
            $groupOperator = $group['operator'];
            $expressions = [];
            foreach ($group['fields'] as $field) {
                $type = $field['name'];
                $fieldConfig = $formConfig['fields'][$type] ?? [];
                if ('tags' === $type) {
                    $type = 'Tags';
                } else {
                    $type = $fieldConfig['label'] ?? null;
                    if (!$type) {
                        continue;
                    }
                }

                $value = $field['value'];
                switch ($fieldConfig['type']) {
                case 'TEXT':
                case 'INT':
                case 'AREA':
                    $value = "'$value'";
                    break;
                case 'INTDATE':
                    $value = dateConvDBDate2Date(dateConvYmd2DBDate($value));
                    break;
                case 'SEARCHLIST':
                    $value = "'" . trim(getSearchListSelectedValue($fieldConfig['listquery'], $value, false)) . "'";
                    break;
                case 'SELECT':
                    $value = "'" . Translator::translate($fieldConfig['options'][$value] ?? '??') . "'";
                    break;
                case 'LIST':
                    if (is_string($fieldConfig['listquery'])) {
                        $values = [];
                        $res = dbQueryCheck($fieldConfig['listquery']);
                        while ($row = mysqli_fetch_row($res)) {
                            $values[$row[0]] = $row[1];
                        }
                        $value = Translator::translate($values[$value] ?? '??');
                    } else {
                        $value = Translator::translate($fieldConfig['listquery'][$value] ?? '??');
                    }
                    $value = "'$value'";
                    break;
                }

                $joins = [
                  'eq' => ' = ',
                  'ne' => ' != ',
                  'lt' => ' < ',
                  'lte' => ' <= ',
                  'gt' => ' > ',
                  'gte' => ' >= '
                ];
                $expressions[] = Translator::translate($type) . ($joins[$field['comparison']] ?? ' = ') . $value;
            }
            $groups[] = implode(
                ' ' . Translator::translate('Search' . $groupOperator) . ' ',
                $expressions
            );
        }
        if (count($groups) > 1) {
            $groups = array_map(
                function ($s) {
                    return "($s)";
                },
                $groups
            );
        }
        return implode(
            ' ' . Translator::translate('Search' . $operator) . ' ',
            $groups
        );
    }
}
