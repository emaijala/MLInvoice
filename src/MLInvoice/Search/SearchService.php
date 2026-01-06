<?php
/**
 * Search Service.
 *
 * PHP version 8
 *
 * Copyright (C) Ere Maijala 2022-2026.
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

namespace MLInvoice\Search;

use MLInvoice\Config\SettingsManager;
use MLInvoice\Database\Entity\QuickSearch;
use MLInvoice\Database\Repository\InvoiceStateRepository;
use MLInvoice\Database\Repository\QuickSearchRepository;
use MLInvoice\I18n\Translator;
use Odan\Session\SessionInterface;

/**
 * Search Service.
 *
 * @category MLInvoice
 * @package  MLInvoice\Base
 * @author   Ere Maijala <ere@labs.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://labs.fi/mlinvoice.eng.php
 */
class SearchService
{
    /**
     * Built-in search id for repeating invoices
     *
     * @var int
     */
    public const SEARCH_REPEATING_INVOICES = -1;

    /**
     * Built-in search id for open invoices
     *
     * @var int
     */
    public const SEARCH_OPEN_INVOICES = -2;

    /**
     * Built-in search id for unpaid invoices
     *
     * @var int
     */
    public const SEARCH_UNPAID_INVOICES = -3;

    /**
     * Built-in search id for open offers
     *
     * @var int
     */
    public const SEARCH_OPEN_OFFERS = -4;

    /**
     * Built-in search id for non-archived invoices
     *
     * @var int
     */
    public const SEARCH_NON_ARCHIVED_INVOICES = -5;

    /**
     * Built-in search id for archived invoices
     *
     * @var int
     */
    public const SEARCH_ARCHIVED_INVOICES = -6;

    /**
     * Built-in search id for non-archived offers
     *
     * @var int
     */
    public const SEARCH_NON_ARCHIVED_OFFERS = -7;

    /**
     * Built-in search id for archived offers
     *
     * @var int
     */
    public const SEARCH_ARCHIVED_OFFERS = -8;

    /**
     * Built-in search id for recurring invoice templates
     *
     * @var int
     */
    public const SEARCH_RECURRING_INVOICE_TEMPLATES = -9;

    /**
     * Built-in search id for recurring invoice templates due for processing
     *
     * @var int
     */
    public const SEARCH_RECURRING_INVOICE_TEMPLATES_DUE = -10;

    /**
     * Constructor
     *
     * @param Translator $translator Translator
     * @param QuickSearchRepository $quickSearchRepository Quick search database repository
     * @param InvoiceStateRepository $invoiceStateRepository Invoice state database repository
     * @param  SessionInterface $session Session
     * SettingsManager $settingsManager Settings manager
     */
    public function __construct(
        protected Translator $translator,
        protected QuickSearchRepository $quickSearchRepository,
        protected InvoiceStateRepository $invoiceStateRepository,
        protected SessionInterface $session,
        protected SettingsManager $settingsManager,
    ) {
    }

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
        $formConfig['fields'] = $this->processFormConfigFields($formConfig['fields']);
        $listValues = [];
        foreach ($formConfig['fields'] as $field) {
            if (in_array($field['type'], $formConfig['searchFieldTypes'])) {
                $listValues[$field['name']] = str_replace(
                    '<br>', ' ',
                    $this->translator->translate($field['label'])
                );
            }
            if ('SEARCHLIST' === $field['type']) {
                // We need the name of current selection for search fields for display
                foreach ($searchGroups['groups'] as &$group) {
                    foreach ($group['fields'] as &$groupField) {
                        if ($groupField['value'] && $field['name'] === $groupField['name']) {
                            $groupField['value_text']
                                = getSearchListSelectedValue($field['listquery'], $groupField['value'], false);
                        }
                    }
                    unset($groupField);
                }
                unset($group);
            }
        }

        ?>

<div role="search">
  <form id="search_form" method="GET">
    <input type="hidden" name="func" value="results">
    <input type="hidden" name="type" value="<?php echo htmlentities($type)?>">
    <div class="row mb-2 p-2 group-operator hidden">
      <div class="col-sm-6">
        <label for="operator" class="form-label"><?php echo $this->translator->translate('GroupHandlingMethod')?></label>
        <select id="operator" name="s_op" class="form-select">
          <option value="AND"<?php echo 'AND' === $searchGroups['operator'] ? ' selected' : ''?>><?php echo $this->translator->translate('AllGroups')?></option>
          <option value="OR"<?php echo 'OR' === $searchGroups['operator'] ? ' selected' : ''?>><?php echo $this->translator->translate('AnyGroup')?></option>
        </select>
      </div>
    </div>
    <div id="search_groups">
      <template id="template_group">
        <div class="card mb-4 group">
          <div class="card-header">
            <div><h2><?php echo $this->translator->translate('SearchGroup')?></h2></div>
            <div>
              <a href="#" role="button" class="btn btn-outline-primary btn-sm delete-group"
                title="<?php echo $this->translator->translate('DeleteSearchGroup')?>"
                aria-title="<?php echo $this->translator->translate('DeleteSearchGroup')?>"
              >
                <i class="icon-minus"></i>
              </a>
            </div>
          </div>
          <div class="card-body">
            <div class="row justify-content-end controls">
              <div class="col-sm-6 field-operator mt-2 mb-4 hidden">
                <select class="form-select operator">
                  <option value="AND" selected><?php echo $this->translator->translate('AllFieldsMustMatch')?></option>
                  <option value="OR"><?php echo $this->translator->translate('AnyFieldMustMatch')?></option>
                </select>
              </div>
            </div>
            <div class="fields">
            </div>
            <div class="row justify-content-end controls">
              <div class="col-sm-6 mb-2 mt-4">
                <select class="form-select add-search-field">
                  <option value=""><?php echo $this->translator->translate('SelectSearchField') ?></option>
                  <?php foreach ($listValues as $field => $name) { ?>
                    <option value="<?php echo htmlspecialchars($field)?>"><?php echo htmlspecialchars($name)?></option>
                  <?php } ?>
                </select>
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
        <i class="icon-plus"></i><?php echo $this->translator->translate('AddSearchGroup')?>
      </a>
    </div>
    <div class="mb-2 p-2 search-buttons">
      <a href="#" role="button" class="btn btn-primary form-submit" id="search">
        <?php echo $this->translator->translate('Search')?>
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

        $terms = $this->getSearchDescription($type, $searchData['searchGroups'])
            ?: $this->translator->translate('NoSearchTerms');
        $searchDesc = $this->translator->translate(
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
                'errors' => $this->translator->translate('ErrorNoSearchName'),
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
        $descriptions = [
          'invoice' => $this->translator->translate('InvoicesAndOffers'),
          'company' => $this->translator->translate('Clients'),
        ];
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
        } elseif ('delete' === $action && ($searchId = getPost('search')) > 0) {
            deleteQuickSearch($searchId);
        } elseif ('search' === $action) {
            $params = [
                'func' => 'results',
                'type' => $type,
                'search_id' => getPost('search'),
            ];
            header('Location: index.php?' . http_build_query($params));
            exit();
        } elseif (in_array($action, ['add_start', 'add_default_start'])
            && ($searchId = getPost('search'))
        ) {
            $default = 'add_default_start' === $action;
            $this->setStartPageSearches(
                array_unique(
                    array_merge(
                        $this->getStartPageSearchIds($default),
                        [$searchId]
                    )
                ),
                $default
            );
        } elseif ('remove_start' === $action) {
            $searchId = intval(getPost('start_page') ?? 0);
            $this->setStartPageSearches(
                array_diff(
                    $this->getStartPageSearchIds(false),
                    [$searchId]
                )
            );
        } elseif ('remove_default_start' === $action) {
            $searchId = intval(getPost('default_start_page') ?? 0);
            $this->setStartPageSearches(
                array_diff(
                    $this->getStartPageSearchIds(true),
                    [$searchId]
                ),
                true
            );
        } elseif ('reset_start' === $action) {
            $this->setStartPageSearches([]);
        } elseif ('copy_to_default' === $action) {
            $this->setStartPageSearches($this->getStartPageSearchIds(false), true);
        } elseif ('copy_to_own' === $action) {
            $this->setStartPageSearches($this->getStartPageSearchIds(true), false);
        } elseif ('reset_default_start' === $action) {
            $this->setStartPageSearches([], true);
        }
        ?>
<form method="POST">
  <input type="hidden" name="func" value="edit_searches">
  <div class="row edit-searches">
    <div class="col-12">
      <div class="col-12 col-md-3 mb-3">
        <label for="search_type" class="form-label"><?php echo $this->translator->translate('SavedSearchType')?></label>
        <select id="search_type" name="type" class="form-select" size="2" data-form-submit-on-change>
          <option value="invoice" <?php echo 'invoice' === $type ? 'selected' : ''?>>
            <?php echo $this->translator->translate('InvoicesAndOffers') ?>
          </option>
          <option value="company" <?php echo 'company' === $type ? 'selected' : ''?>>
            <?php echo $this->translator->translate('Clients') ?>
          </option>
        </select>
      </div>
    </div>
    <div class="col-12 col-xl-6">
      <div class="mb-1">
        <label for="search" class="form-label"><?php echo $this->translator->translate('SavedSearches')?></label>
        <select id="search" name="search" class="form-select" size="10">
          <?php foreach (getQuickSearches($type) as $search) { ?>
            <option value="<?php echo htmlentities($search['id'])?>"><?php echo htmlentities($search['name'])?></option>
          <?php } ?>
          <?php if ('invoice' === $type) { ?>
            <option value="">--- <?php echo $this->translator->translate('PredefinedSearches')?> ---</option>
            <option value="-1"><?php echo $this->translator->translate('LabelInvoicesWithIntervalDue')?></option>
            <option value="-2"><?php echo $this->translator->translate('LabelOpenInvoices')?></option>
            <option value="-3"><?php echo $this->translator->translate('LabelUnpaidInvoices')?></option>
            <option value="-4"><?php echo $this->translator->translate('LabelUnfinishedOffers')?></option>
          <?php } ?>
        </select>
      </div>
      <div class="mb-3">
        <button type="submit" name="action" value="edit" class="btn btn-secondary">
          <?php echo $this->translator->translate('Edit') ?>
        </button>
        <button type="submit" name="action" value="delete" class="btn btn-secondary">
          <?php echo $this->translator->translate('Delete') ?>
        </button>
        <button type="submit" name="action" value="search" class="btn btn-secondary">
          <?php echo $this->translator->translate('Search') ?>
        </button>
        <button type="submit" name="action" value="add_start" class="btn btn-secondary">
          <?php echo $this->translator->translate('AddToStartPage') ?>
        </button>
        <button type="submit" name="action" value="add_default_start" class="btn btn-secondary"<?php echo !sesAdminAccess() ? ' disabled' : ''?>>
          <?php echo $this->translator->translate('AddToDefaultStartPage') ?>
        </button>
      </div>
    </div>
    <div class="col-12 col-xl-6">
      <div class="mb-1">
        <label for="start_page" class="form-label"><?php echo $this->translator->translate('OwnStartPage')?></label>
        <select id="start_page" name="start_page" class="form-select" size="10">
          <?php foreach ($this->getStartPageSearchIds(false) as $i => $id) { ?>
                <?php
                if (!($search = getQuickSearch($id))) {
                    continue;
                }
                if ($typeDesc = $descriptions[$search['func']] ?? '') {
                    $typeDesc = " ($typeDesc)";
                }
                ?>
              <option value="<?php echo htmlentities($id)?>"<?php echo 0 === $i ? ' selected' : ''?>><?php echo htmlentities($search['name'] . $typeDesc)?></option>
          <?php } ?>
        </select>
      </div>
      <div class="mb-3">
        <button type="submit" name="action" value="remove_start" class="btn btn-secondary">
          <?php echo $this->translator->translate('RemoveFromStartPage') ?>
        </button>
        <button type="submit" name="action" value="copy_to_default" class="btn btn-secondary"<?php echo !sesAdminAccess() ? ' disabled' : ''?>>
          <?php echo $this->translator->translate('CopyToDefaultStartPage') ?>
        </button>
        <button type="submit" name="action" value="reset_start" class="btn btn-secondary">
          <?php echo $this->translator->translate('ResetStartPage') ?>
        </button>
      </div>
      <div class="mb-1">
        <label for="default_start_page" class="form-label"><?php echo $this->translator->translate('DefaultStartPage')?></label>
        <select id="default_start_page" name="default_start_page" class="form-select" size="10">
          <?php foreach ($this->getStartPageSearchIds(true) as $i => $id) { ?>
                <?php
                if (!($search = getQuickSearch($id))) {
                    continue;
                }
                if ($typeDesc = $descriptions[$search['func']] ?? '') {
                    $typeDesc = " ($typeDesc)";
                }
                ?>
                <option value="<?php echo htmlentities($id)?>"<?php echo 0 === $i ? ' selected' : ''?>><?php echo htmlentities($search['name'] . $typeDesc)?></option>
          <?php } ?>
        </select>
      </div>
      <div class="mb-3">
        <button type="submit" name="action" value="remove_default_start" class="btn btn-secondary"<?php echo !sesAdminAccess() ? ' disabled' : ''?>>
          <?php echo $this->translator->translate('RemoveFromStartPage') ?>
        </button>
        <button type="submit" name="action" value="copy_to_own" class="btn btn-secondary">
          <?php echo $this->translator->translate('CopyToOwnStartPage') ?>
        </button>
        <button type="submit" name="action" value="reset_default_start" class="btn btn-secondary"<?php echo !sesAdminAccess() ? ' disabled' : ''?>>
          <?php echo $this->translator->translate('ResetStartPage') ?>
        </button>
      </div>
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
            $groupOperator = $request["s_op$group"] ?? 'AND';
            $searchGroup = [
                'operator' => $groupOperator,
                'fields' => []
            ];
            foreach ($request["s_field$group"] ?? [] as $i => $value) {
                if (!($name = $request["s_type$group"][$i] ?? null)) {
                    continue;
                }
                $comparison = $request["s_cmp$group"][$i] ?? 'eq';
                $searchGroup['fields'][] = [
                    'name' => $name,
                    'value' => $value,
                    'comparison' => $comparison,
                ];
            }
            if (!$searchGroup['fields']) {
                break;
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
        $term = '';
        while ($this->extractSearchTerm($query, $field, $operator, $term, $nextBool)) {
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
     * Get searches active on the start page
     *
     * @param mixed $default True to get the default set for all users, false to get
     *                       only the user-specific set, null to get user-specific
     *                       or default if no user-specific set exists.
     *
     * @return array
     */
    public function getStartPageSearchIds($default = null): array
    {
        if (null === $default) {
            $searches = $this->settingsManager->get('startPageSearches' . $this->session->get('user'))
                ?: $this->settingsManager->get('startPageSearches');
        } elseif (false === $default) {
            $searches = $this->settingsManager->get('startPageSearches' . $this->session->get('user'));
            if (!$searches) {
                return [];
            }
        } else {
            $searches = $this->settingsManager->get('startPageSearches');
        }
        return null === $searches
            ? [
                static::SEARCH_REPEATING_INVOICES,
                static::SEARCH_OPEN_INVOICES,
                static::SEARCH_UNPAID_INVOICES,
                static::SEARCH_OPEN_OFFERS,
            ] : array_filter(explode(',', $searches));
    }

    /**
     * Get a quick search
     *
     * @param int $id Quick search ID
     *
     * @return array
     */
    public function getQuickSearch(int $id): array
    {
        if ($id < 0) {
            switch ($id) {
            case SearchService::SEARCH_REPEATING_INVOICES:
                $label = 'LabelInvoicesWithIntervalDue';
                $groups = [
                    [
                        'operator' => 'AND',
                        'fields' => [
                            [
                                'name' => 'interval_type',
                                'value' => '0',
                                'comparison' => 'ne'
                            ],
                            [
                                'name' => 'archived',
                                'value' => '0',
                                'comparison' => 'eq'
                            ],
                            [
                                'name' => 'next_interval_date',
                                'value' => date('Y-m-d'),
                                'comparison' => 'lte'
                            ],
                        ],
                    ],
                ];
                break;
            case SearchService::SEARCH_OPEN_INVOICES:
                $label = 'LabelOpenInvoices';
                $groups = [
                    [
                        'operator' => 'OR',
                        'fields' => $this->getInvoiceStateSearchFields(['open' => true, 'offer' => false])
                    ],
                    [
                        'operator' => 'AND',
                        'fields' => [
                            [
                                'name' => 'archived',
                                'value' => '0',
                                'comparison' => 'eq'
                            ],
                        ]
                    ]
                ];
                break;
            case SearchService::SEARCH_UNPAID_INVOICES:
                $label = 'LabelUnpaidInvoices';
                $groups = [
                    [
                        'operator' => 'OR',
                        'fields' => $this->getInvoiceStateSearchFields(['open' => false, 'unpaid' => true, 'offer' => false])
                    ],
                    [
                        'operator' => 'AND',
                        'fields' => [
                            [
                                'name' => 'archived',
                                'value' => '0',
                                'comparison' => 'eq'
                            ],
                        ]
                    ]
                ];
                break;
            case SearchService::SEARCH_OPEN_OFFERS:
                $label = 'LabelUnfinishedOffers';
                $groups = [
                    [
                        'operator' => 'OR',
                        'fields' => $this->getInvoiceStateSearchFields(['open' => true, 'offer' => true]),
                    ],
                    [
                        'operator' => 'AND',
                        'fields' => [
                            [
                                'name' => 'archived',
                                'value' => '0',
                                'comparison' => 'eq'
                            ],
                        ]
                    ]
                ];
                break;
            case SearchService::SEARCH_NON_ARCHIVED_INVOICES:
                $label = 'NonArchivedInvoices';
                $groups = [
                    [
                        'operator' => 'OR',
                        'fields' => $this->getInvoiceStateSearchFields(['offer' => false, 'template' => false]),
                    ],
                    [
                        'operator' => 'AND',
                        'fields' => [
                            [
                                'name' => 'archived',
                                'value' => '0',
                                'comparison' => 'eq'
                            ],
                        ]
                    ]
                ];
                break;
            case SearchService::SEARCH_ARCHIVED_INVOICES:
                $label = 'ArchivedInvoices';
                $groups = [
                    [
                        'operator' => 'OR',
                        'fields' => $this->getInvoiceStateSearchFields(['offer' => false, 'template' => false]),
                    ],
                    [
                        'operator' => 'AND',
                        'fields' => [
                            [
                                'name' => 'archived',
                                'value' => '1',
                                'comparison' => 'eq'
                            ],
                        ]
                    ]
                ];
                break;
            case SearchService::SEARCH_NON_ARCHIVED_OFFERS:
                $label = 'NonArchivedOffers';
                $groups = [
                    [
                        'operator' => 'OR',
                        'fields' => $this->getInvoiceStateSearchFields(['offer' => true]),
                    ],
                    [
                        'operator' => 'AND',
                        'fields' => [
                            [
                                'name' => 'archived',
                                'value' => '0',
                                'comparison' => 'eq'
                            ],
                        ]
                    ]
                ];
                break;
            case SearchService::SEARCH_ARCHIVED_OFFERS:
                $label = 'ArchivedOffers';
                $groups = [
                    [
                        'operator' => 'OR',
                        'fields' => $this->getInvoiceStateSearchFields(['offer' => true]),
                    ],
                    [
                        'operator' => 'AND',
                        'fields' => [
                            [
                                'name' => 'archived',
                                'value' => '1',
                                'comparison' => 'eq'
                            ],
                        ]
                    ]
                ];
                break;
            case SearchService::SEARCH_RECURRING_INVOICE_TEMPLATES:
                $label = 'RecurringInvoiceTemplates';
                $groups = [
                    [
                        'operator' => 'OR',
                        'fields' => $this->getInvoiceStateSearchFields(['template' => true]),
                    ],
                    [
                        'operator' => 'AND',
                        'fields' => [
                            [
                                'name' => 'archived',
                                'value' => '0',
                                'comparison' => 'eq'
                            ],
                        ]
                    ]
                ];
                break;
            case SearchService::SEARCH_RECURRING_INVOICE_TEMPLATES_DUE:
                $label = 'RecurringInvoiceTemplates';
                $groups = [
                    [
                        'operator' => 'OR',
                        'fields' => $this->getInvoiceStateSearchFields(['template' => true]),
                    ],
                    [
                        'operator' => 'AND',
                        'fields' => [
                            [
                                'name' => 'next_interval_date',
                                'value' => date('Y-m-d'),
                                'comparison' => 'lte'
                            ],
                            [
                                'name' => 'archived',
                                'value' => '0',
                                'comparison' => 'eq'
                            ],
                        ]
                    ]
                ];
                break;
            default:
                throw new \Exception('Invalid search id');
            }

            return [
                'id' => $id,
                'user_id' => null,
                'name' => $this->translator->translate($label),
                'func' => 'invoice',
                'whereclause' => json_encode(
                    [
                        'type' => 'invoice',
                        'operator' => 'AND',
                        'groups' => $groups,
                    ]
                )
            ];
        }

        $search = $this->quickSearchRepository->findByIdAndUserId($id, $this->session->get('user'));
        return $search ? [
            'id' => $search->getId(),
            'user_id' => $search->getUser()?->getId(),
            'name' => $search->getName(),
            'func' => $search->getFunc(),
            'whereclause' => $search->getQuery(),
        ] : [];
    }

    /**
     * Set searches active on the start page
     *
     * @param array $searches Searches
     * @param bool  $default  Whether to set the default set for all users
     *
     * @return void
     */
    protected function setStartPageSearches(array $searches, bool $default = false): void
    {
        $key = 'startPageSearches';
        if (!$default) {
            $key .= $_SESSION['sesUSERID'];
        } elseif (!sesAdminAccess()) {
            return;
        }
        setSetting($key, implode(',', $searches));
    }

    /**
     * Get search data from request params
     *
     * @return array
     */
    protected function getSearchFromRequest(): array
    {
        $result = [];
        if ($searchId = getQuery('search_id')) {
            $result = $this->getSavedSearch(intval($searchId));
        }
        $type = getQuery('type');
        $searchGroups = $this->getSearchGroups($_GET);
        if ((null === $type || $type === ($result['type'] ?? null))
            && $searchGroups['operator'] === $result['searchGroups']['operator'] ?? null
        ) {
            // Merge saved search with other url params and return it:
            $result['searchGroups']['groups'] = array_merge(
                $result['searchGroups']['groups'],
                $searchGroups['groups']
            );
            return $result;
        }
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
                } elseif ('template_invoice_id' === $type) {
                    $type = 'RecurringInvoiceTemplate';
                } else {
                    $type = $fieldConfig['label'] ?? null;
                    if (!$type) {
                        continue;
                    }
                }

                $value = $field['value'];
                switch ($fieldConfig['type'] ?? null) {
                case 'TEXT':
                case 'INT':
                case 'AREA':
                    $value = "'$value'";
                    break;
                case 'INTDATE':
                    $value = dateConvDBDate2Date(dateConvYmd2DBDate($value ?: date('Y-m-d')));
                    break;
                case 'SEARCHLIST':
                    $value = "'" . trim(getSearchListSelectedValue($fieldConfig['listquery'], $value, false)) . "'";
                    break;
                case 'SELECT':
                    $value = "'" . $this->translator->translate($fieldConfig['options'][$value] ?? '??') . "'";
                    break;
                case 'LIST':
                    if (is_string($fieldConfig['listquery'])) {
                        $values = [];
                        $res = dbQueryCheck($fieldConfig['listquery']);
                        while ($row = mysqli_fetch_row($res)) {
                            $values[$row[0]] = $row[1];
                        }
                        $value = $this->translator->translate($values[$value] ?? '??');
                    } else {
                        $value = $this->translator->translate($fieldConfig['listquery'][$value] ?? '??');
                    }
                    $value = "'$value'";
                    break;
                case 'CHECK':
                    $value = $this->translator->translate($value ? 'Selected' : 'Unselected');
                    break;
                }

                $joins = [
                    'eq' => '=',
                    'ne' => '!=',
                    'lt' => '<',
                    'lte' => '<=',
                    'gt' => '>',
                    'gte' => '>='
                ];
                $expressions[] = '<span class="search-type">'
                    . $this->translator->translate($type)
                    . '</span> <span class="search-comparison">'
                    . ($joins[$field['comparison']] ?? '=')
                    . '</span> <span class="search-value">'
                    . htmlspecialchars($value)
                    . '</span>';
            }
            $groups[] = implode(
                ' <span class="search-join">' . $this->translator->translate('Search' . $groupOperator) . '</span> ',
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
            ' <span class="search-join-groups">' . $this->translator->translate('Search' . $operator) . '</span> ',
            $groups
        );
    }

    /**
     * Pre-process form config fields
     *
     * @param array $fields Form fields
     *
     * @return array
     */
    protected function processFormConfigFields($fields)
    {
        return array_map(
            function ($field) {
                if (isset($field['label'])) {
                    $field['label'] = str_replace('<br>', ' ', $this->translator->translate($field['label']));
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
                    $field['options'] = array_map(
                        function ($s) {
                            return $this->translator->translate($s ?? '');
                        },
                        $field['options']
                    );
                }

                return $field;
            },
            $fields
        );
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
    protected function extractSearchTerm(
        string &$searchTerms,
        string &$field,
        string &$operator,
        string &$term,
        string &$boolean
    ): bool {
        if (preg_match('/^([\w\.\_]+)\s*(=|!=|<=?|>=?|LIKE)\s*(.+)/', $searchTerms, $matches)) {
            if (!preg_match('/^([\w\.\_]+)\s+(NOT IN|IN)\s+(.+)/', $searchTerms, $matches)) {
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
            throw new \InvalidArgumentException('Unbalanced parenthesis');
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
     * Get an array of invoice state search fields that match the given state query
     *
     * @param array $filter Filter
     *
     * @return array
     */
    protected function getInvoiceStateSearchFields(array $filter): array
    {
        $states = $this->invoiceStateRepository->findBy($filter);
        foreach ($states as $state) {
            $stateFields[] = [
                'name' => 'state_id',
                'value' => $state->getId(),
                'comparison' => 'eq',
            ];
        }
        return $stateFields;
    }
}
