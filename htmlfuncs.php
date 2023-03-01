<?php
/**
 * HTML functions
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
require_once 'list.php';
require_once 'settings.php';
require_once 'sqlfuncs.php';

/**
 * Create HTTP headers and HTML page start
 *
 * @param string $strTitle        Page title
 * @param array  $arrExtraScripts Extra scripts to add
 * @param bool   $loggedIn        Whether the user is logged in
 *
 * @return string HTML content
 */
function htmlPageStart($strTitle = '', $arrExtraScripts = [], $loggedIn = true)
{
    // These are to prevent browser & proxy caching
    // HTTP/1.1
    header('Cache-Control: no-store, no-cache, must-revalidate');
    header('Cache-Control: post-check=0, pre-check=0', false);
    // Date in the past
    header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
    // always modified
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');

    if (defined('_FORCE_HTTPS_') && _FORCE_HTTPS_) {
        // Check if we are using a secure connection and redirect if necessary
        if (empty($_SERVER['HTTPS']) || 'off' === $_SERVER['HTTPS']) {
            $url = 'https://';
            if (!empty($_SERVER['HTTP_HOST'])) {
                $host = $_SERVER['HTTP_HOST'];
                // Attempt to support the typical alternatice port combination
                $host = str_replace(':8080', ':8443', $host);
                $url .= $host;
            } else {
                $url .= $_SERVER['SERVER_NAME'];
                // Attempt to support the typical alternatice port combination
                if ($_SERVER['SERVER_PORT'] == 8080) {
                    $url .= ':8443';
                }
            }
            $url .= $_SERVER['REQUEST_URI'];
            header("Location: $url");
            exit();
        }
    }

    $charset = (_CHARSET_ == 'UTF-8') ? 'UTF-8' : 'ISO-8859-15';
    $lang = $_SESSION['sesLANG'] ?? 'fi-FI';
    $dateRangePickerOptions = Translator::translate('DateRangePickerOptions');

    $scripts = [
        'vendor/twbs/bootstrap/dist/js/bootstrap.bundle.js',
        'vendor/components/jquery/jquery.min.js',

        // DataTables:
        'node_modules/datatables.net/js/jquery.dataTables.min.js',
        'node_modules/datatables.net-bs5/js/dataTables.bootstrap5.min.js',
        'node_modules/datatables.net-responsive/js/dataTables.responsive.min.js',
        'node_modules/datatables.net-responsive-bs5/js/responsive.bootstrap5.min.js',
        'node_modules/datatables.net-buttons/js/dataTables.buttons.js',
        'node_modules/datatables.net-buttons/js/buttons.html5.min.js',
        'node_modules/datatables.net-buttons-bs5/js/buttons.bootstrap5.min.js',
        'node_modules/datatables.net-buttons/js/buttons.colVis.min.js',

        'js/vendor/moment-with-locales.min.js',
        'js/vendor/daterangepicker.min.js',
        'node_modules/select2/dist/js/select2.js',
        'js/formdata.min.js',
        'js/vendor/js.cookie-2.2.1.min.js',
        'js/vendor/Sortable.min.js',
    ];

    if (defined('_JS_DEBUG_')) {
        $scripts[] = 'js/mlinvoice.js';
        $scripts[] = 'js/mlinvoice-form.js';
        $scripts[] = 'js/mlinvoice-search.js';
    } else {
        $scripts[] = 'js/mlinvoice.min.js';
    }

    if (getSetting('printout_markdown')) {
        $scripts[] = 'js/easymde.min.js';
    }

    if (file_exists("select2/select2_locale_$lang.js")) {
        $scripts[] = "select2/select2_locale_$lang.js";
    }

    $scripts = array_merge($scripts, $arrExtraScripts);
    foreach ($scripts as &$script) {
        $script = '  <script src="'
            . addFileTimestamp($script) . '"></script>';
    }
    $scriptLinks = implode("\n", $scripts);

    $css = [
        'css/vendor/daterangepicker.css',

        // DataTables:
        'node_modules/datatables.net-bs5/css/dataTables.bootstrap5.min.css',
        'node_modules/datatables.net-buttons-bs5/css/buttons.bootstrap5.min.css',

        'node_modules/select2/dist/css/select2.min.css',
        getSetting('printout_markdown') ? 'css/easymde.min.css' : '',
        'css/style.css',
    ];

    if (file_exists('css/custom.css')) {
        $css[] = 'css/custom.css';
    }

    foreach ($css as &$style) {
        if (empty($style)) {
            continue;
        }
        $style = '  <link rel="stylesheet" type="text/css" href="'
            . addFileTimestamp($style) . '">';
    }
    $cssLinks = implode("\n", $css);

    $favicon = addFileTimestamp('favicon.ico');

    $strTitle = $strTitle ? _PAGE_TITLE_ . " - $strTitle" : _PAGE_TITLE_;

    $translations = [
        'DecimalSeparator',
        'InvoiceDateNonCurrent',
        'InvoiceNumberNotDefined',
        'InvoiceRefNumberTooShort',
        'InvoicesTotal',
        'NoYTJResultsFound',
        'SearchYTJPrompt',
        'SettingDispatchNotes',
        'ThousandSeparator',
        'ForSelected',
        'Delete',
        'Modify',
        'ModifySelectedRows',
        'Modified',
        'ProductWeight',
        'FutureDateEntered',
        'RecordSaved',
        'RecordDeleted',
        'UnsavedData',
        'Close',
        'Attachments',
        'NoEntries',
        'ErrValueMissing',
        'RemoveAttachment',
        'LargeFile',
        'SendToClient',
        'Description',
        'Save',
        'UpdateStockBalance',
        'YesButton',
        'NoButton',
        'Edit',
        'Copy',
        'TotalExcludingVAT',
        'TotalVAT',
        'TotalIncludingVAT',
        'TotalToPay',
        'RowCopy',
        'RowModification',
        'PartialPayment',
        'Sort',
        'ReminderFeesAdded',
        'VATLess',
        'VATPart',
        'Info',
        'ServerError',
        'Total',
        'VisiblePage',
        'SearchSaved',
        'SearchEqual',
        'SearchNotEqual',
        'SearchLessThan',
        'SearchLessThanOrEqual',
        'SearchGreaterThan',
        'SearchGreaterThanOrEqual',
        'Selected',
        'Unselected',
    ];

    $res = dbQueryCheck(
        'SELECT name FROM {prefix}invoice_state WHERE deleted=0'
    );
    while ($row = dbFetchValue($res)) {
        $translations[] = $row;
    }

    if (getSetting('check_updates')) {
        $translations = array_merge(
            $translations,
            [
                'UpdateAvailable',
                'UpdateAvailableTitle',
                'UpdateInformation',
                'UpdateNow'
            ]
        );
    }

    $jsTranslations = [];
    foreach ($translations as $translation) {
        $translated = Translator::translate($translation);
        if ($translated != $translation) {
            $jsTranslations[$translation] = $translated;
        }
    }

    $jsTranslations = json_encode($jsTranslations);

    $dispatchNotePrintStyle = 'none';
    $res = dbQueryCheck('SELECT * FROM {prefix}print_template WHERE id=2');
    if ($row = mysqli_fetch_assoc($res)) {
        if (!$row['deleted'] && !$row['inactive']) {
            $dispatchNotePrintStyle = $row['new_window'] ? 'openwindow' : 'redirect';
        }
    }

    $offerStates = json_encode(getOfferStateIds());

    $keepAlive = $loggedIn && getSetting('session_keepalive') ? 'true' : 'false';
    $lang = Translator::translate('HTMLLanguageCode');
    $currencyDecimals = getSetting('unit_price_decimals');
    $dateFormat = Translator::translate('DateFormat');
    $dateFormat = str_replace(
        ['d', 'm', 'j', 'n', 'Y', 'y'],
        ['DD', 'MM', 'D', 'M', 'YYYY', 'YY'],
        $dateFormat
    );

    $strHtmlStart = <<<EOT
<!DOCTYPE html>
<html lang="$lang">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=$charset">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>$strTitle</title>
  <link rel="shortcut icon" href="$favicon" type="image/x-icon">
$cssLinks
$scriptLinks
  <script>
moment.locale('$lang');
MLInvoice.addTranslations($jsTranslations);
MLInvoice.setDispatchNotePrintStyle('$dispatchNotePrintStyle');
MLInvoice.setOfferStates($offerStates);
MLInvoice.setKeepAlive($keepAlive);
MLInvoice.setCurrencyDecimals($currencyDecimals);
MLInvoice.setDateFormat('$dateFormat');
MLInvoice.setDateRangePickerDefaults($dateRangePickerOptions);
  </script>
</head>
EOT;

    return $strHtmlStart;
}

/**
 * Create main tabs
 *
 * @param string $func Current function
 *
 * @return void
 */
function htmlMainTabs($func)
{
    $normalMenuRights = [
        ROLE_READONLY,
        ROLE_USER,
        ROLE_BACKUPMGR
    ];
    $astrMainButtons = [
        [
            'title' => 'InvoicesAndOffers',
            'action' => 'invoices',
            'levels_allowed' => [
                ROLE_READONLY,
                ROLE_USER,
                ROLE_BACKUPMGR
            ],
            'submenu' => [
                [
                    'title' => 'StartPage',
                    'action' => 'start_page',
                    'levels_allowed' => [
                        ROLE_READONLY,
                        ROLE_USER,
                        ROLE_BACKUPMGR
                    ],
                ],
                [
                    'title' => 'AllNonArchived',
                    'action' => 'invoices',
                    'levels_allowed' => [
                        ROLE_READONLY,
                        ROLE_USER,
                        ROLE_BACKUPMGR,
                    ],
                ],
                [
                    'title' => 'ShowArchivedInvoicesNavi',
                    'action' => 'archived_invoices',
                    'levels_allowed' => [
                        ROLE_READONLY,
                        ROLE_USER,
                        ROLE_BACKUPMGR
                    ],
                ],
                [
                    'title' => 'ShowArchivedOffersNavi',
                    'action' => 'archived_offers',
                    'levels_allowed' => [
                        ROLE_READONLY,
                        ROLE_USER,
                        ROLE_BACKUPMGR
                    ],
                ],
                [
                    'title' => 'ImportAccountStatement',
                    'action' => 'import_statement',
                    'levels_allowed' => [
                        ROLE_USER,
                        ROLE_BACKUPMGR,
                    ],
                ],
                [
                    'title' => 'NewInvoice',
                    'action' => [
                        'func' => 'invoices',
                        'form' => 'invoice',
                    ],
                    'levels_allowed' => [
                        ROLE_USER,
                        ROLE_BACKUPMGR,
                    ],
                ],
                [
                    'title' => 'NewOffer',
                    'action' => [
                        'func' => 'invoices',
                        'form' => 'invoice',
                        'offer' => '1',
                    ],
                    'levels_allowed' => [
                        ROLE_USER,
                        ROLE_BACKUPMGR,
                    ],
                ],
                [
                    'title' => 'ExtSearch',
                    'action' => [
                        'func' => 'search',
                        'form' => 'invoice',
                    ],
                    'levels_allowed' => [
                        ROLE_READONLY,
                        ROLE_USER,
                        ROLE_BACKUPMGR,
                    ],
                ],
            ]
        ],
        [
            'name' => 'company',
            'title' => 'ShowClientNavi',
            'action' => 'company',
            'levels_allowed' => [
                ROLE_USER,
                ROLE_BACKUPMGR
            ],
        ],
        [
            'name' => 'reports',
            'title' => 'ShowReportNavi',
            'levels_allowed' => [
                ROLE_READONLY,
                ROLE_USER,
                ROLE_BACKUPMGR
            ],
            'submenu' => [
                [
                    'title' => 'InvoiceReport',
                    'action' => 'invoice_report',
                    'levels_allowed' => [
                        ROLE_READONLY,
                        ROLE_USER,
                        ROLE_BACKUPMGR
                    ]
                ],
                [
                    'title' => 'ProductReport',
                    'action' => 'product_report',
                    'levels_allowed' => [
                        ROLE_READONLY,
                        ROLE_USER,
                        ROLE_BACKUPMGR
                    ]
                ],
                [
                    'title' => 'ProductStockReport',
                    'action' => 'product_stock_report',
                    'levels_allowed' => [
                        ROLE_READONLY,
                        ROLE_USER,
                        ROLE_BACKUPMGR
                    ]
                ],
                [
                    'title' => 'AccountingReport',
                    'action' => 'accounting_report',
                    'levels_allowed' => [
                        ROLE_READONLY,
                        ROLE_USER,
                        ROLE_BACKUPMGR
                    ]
                ]
            ],
        ],
        [
            'name' => 'settings',
            'title' => 'ShowSettingsNavi',
            'action' => 'settings',
            'action' => 'settings',
            'levels_allowed' => [
                ROLE_USER,
                ROLE_BACKUPMGR
            ],
            'submenu' => [
                [
                    'title' => 'GeneralSettings',
                    'action' => 'settings',
                    'levels_allowed' => [
                        ROLE_ADMIN
                    ]
                ],
                [
                    'title' => 'Bases',
                    'action' => [
                        'func' => 'settings',
                        'list' => 'base',
                    ],
                    'levels_allowed' => [
                        ROLE_USER,
                        ROLE_BACKUPMGR
                    ]
                ],
                [
                    'title' => 'Products',
                    'action' => [
                        'func' => 'settings',
                        'list' => 'product',
                    ],
                    'levels_allowed' => [
                        ROLE_USER,
                        ROLE_BACKUPMGR
                    ]
                ],
                [
                    'title' => 'DefaultValues',
                    'action' => [
                        'func' => 'settings',
                        'list' => 'default_value',
                    ],
                    'levels_allowed' => [
                        ROLE_USER,
                        ROLE_BACKUPMGR
                    ]
                ],
                [
                    'title' => 'Attachments',
                    'action' => [
                        'func' => 'settings',
                        'list' => 'attachment',
                    ],
                    'levels_allowed' => [
                        ROLE_USER,
                        ROLE_BACKUPMGR
                    ]
                ],
                [
                    'title' => 'StartPageAndSavedSearches',
                    'action' => [
                        'func' => 'edit_searches',
                        'type' => 'invoice',
                    ],
                    'levels_allowed' => [
                        ROLE_USER,
                        ROLE_BACKUPMGR
                    ]
                ],
            ]
        ],
        [
            'name' => 'system',
            'title' => 'ShowSystemNavi',
            'action' => 'system',
            'levels_allowed' => [
                ROLE_BACKUPMGR,
                ROLE_ADMIN
            ],
            'submenu' => [
                [
                    'title' => 'Users',
                    'action' => [
                        'func' => 'system',
                        'list' => 'user',
                    ],
                    'levels_allowed' => [
                        ROLE_ADMIN
                    ]
                ],
                [
                    'title' => 'InvoiceStates',
                    'action' => [
                        'func' => 'system',
                        'list' => 'invoice_state',
                    ],
                    'levels_allowed' => [
                        ROLE_ADMIN
                    ]
                ],
                [
                    'title' => 'InvoiceTypes',
                    'action' => [
                        'func' => 'system',
                        'list' => 'invoice_type',
                    ],
                    'levels_allowed' => [
                        ROLE_ADMIN
                    ]
                ],
                [
                    'title' => 'RowTypes',
                    'action' => [
                        'func' => 'system',
                        'list' => 'row_type',
                    ],
                    'levels_allowed' => [
                        ROLE_ADMIN
                    ]
                ],
                [
                    'title' => 'DeliveryTerms',
                    'action' => [
                        'func' => 'system',
                        'list' => 'delivery_terms',
                    ],
                    'levels_allowed' => [
                        ROLE_ADMIN
                    ]
                ],
                [
                    'title' => 'DeliveryMethods',
                    'action' => [
                        'func' => 'system',
                        'list' => 'delivery_method',
                    ],
                    'levels_allowed' => [
                        ROLE_ADMIN
                    ]
                ],
                [
                    'title' => 'PrintTemplates',
                    'action' => [
                        'func' => 'system',
                        'list' => 'print_template'
                    ],
                    'levels_allowed' => [
                        ROLE_ADMIN
                    ]
                ],
                [
                    'title' => 'BackupDatabase',
                    'action' => [
                        'func' => 'system',
                        'operation' => 'backup',
                    ],
                    'levels_allowed' => [
                        ROLE_BACKUPMGR,
                        ROLE_ADMIN
                    ]
                ],
                [
                    'title' => 'ImportData',
                    'action' => [
                        'func' => 'system',
                        'operation' => 'import',
                    ],
                    'levels_allowed' => [
                        ROLE_ADMIN
                    ]
                ],
                [
                    'title' => 'ExportData',
                    'action' => [
                        'func' => 'system',
                        'operation' => 'export',
                    ],
                    'levels_allowed' => [
                        ROLE_ADMIN
                    ]
                ],
                [
                    'title' => 'Update',
                    'action' => [
                        'func' => 'system',
                        'operation' => 'update',
                    ],
                    'levels_allowed' => [
                        ROLE_ADMIN
                    ]
                ]
            ],
        ],
    ];

    createNavBar($astrMainButtons, $func);
}

/**
 * Create the navigation menu
 *
 * @param array  $buttons     Buttons
 * @param string $currentFunc Currently active action
 *
 * @return void
 */
function createNavBar($buttons, $currentFunc = '')
{
    ?>
            <nav class="navbar navbar-expand-md navbar-light border-bottom mb-2">
              <div class="container-fluid">
                <a class="navbar-brand" href="index.php" aria-label="<?php echo Translator::translate('StartPage')?>">MLInvoice</a>
                <button class="navbar-toggler" type="button"
                  data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent"
                  aria-controls="navbarSupportedContent" aria-expanded="false"
                  aria-label="<?php echo Translator::translate('ToggleMenu')?>"
                >
                  <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                  <ul class="navbar-nav mr-auto">
    <?php
    foreach ($buttons as $i => $button) {
        if (isset($button['levels_allowed'])
            && !sesAccessLevel($button['levels_allowed']) && !sesAdminAccess()
        ) {
            continue;
        }
        if ($submenu = $button['submenu'] ?? []) {
            ?>
            <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" id="navbar-dropdown-<?php echo $button['action'] ?? $i ?>" role="button" data-bs-toggle="dropdown" aria-expanded="false">
              <?php echo Translator::translate($button['title'])?>
            </a>
            <ul class="dropdown-menu" aria-labelledby="navbar-dropdown-<?php echo $button['action'] ?? $i ?>">
                <?php
                foreach ($submenu as $item) {
                    if (isset($item['levels_allowed'])
                        && !sesAccessLevel($item['levels_allowed']) && !sesAdminAccess()
                    ) {
                        continue;
                    }
                    $href = '';
                    if ($item['action'] ?? '') {
                        if (is_string($item['action'])) {
                            $href = ' href="index.php?func=' . $item['action'] . '"';
                        } else {
                            $href = ' href="index.php?' . http_build_query($item['action'], '', '&amp;') . '"';
                        }
                    } elseif ($item['link'] ?? '') {
                        $href = ' href="' . htmlspecialchars($item['link']) . '"';
                    }
                    ?>
                    <li>
                        <a class="dropdown-item"<?php echo $href?>>
                            <?php echo Translator::translate($item['title'])?>
                        </a>
                    </li>
                    <?php
                }
                ?>
            </ul>
          </li>
            <?php
        } else {
            $href = '';
            if ($button['action'] ?? '') {
                $href = ' href="index.php?func=' . $button['action'] . '"';
            } elseif ($button['link'] ?? '') {
                $href = ' href="' . htmlspecialchars($button['link']) . '"';
            }
            ?>
            <li class="nav-item">
                <a class="nav-link"<?php echo $href?>>
                    <?php echo Translator::translate($button['title'])?>
                </a>
            </li>
            <?php
        }
    }
    ?>
                 </ul>
    <?php
    $user = !empty($_SESSION['sesUSERID'])
        ? getUserById($_SESSION['sesUSERID']) : [];
    if ($user) {
        ?>
                  <hr class="d-md-none text-black-50">
                  <ul class="navbar-nav ms-md-auto">
                    <li class="nav-item">
                      <a class="nav-link" href="index.php?func=profile">
                        <?php echo $user && $user['name'] ? $user['name'] : Translator::translate('Profile'); ?>
                      </a>
                    </li>
                    <li class="nav-item">
                      <a class="nav-link" href="index.php?func=logout">
                        <?php echo Translator::translate('Logout'); ?>
                      </a>
                    </li>
                  </ul>
        <?php
    }
    ?>
                </div>
              </div>
            </nav>
        <?php
}

/**
 * Create Html-listbox
 *
 * @param string      $strName         Listbox name
 * @param array       $astrValues      Listbox values => descriptions
 * @param string      $strSelected     Selected value
 * @param string      $strStyle        Style
 * @param bool        $submitOnChange  Whether to submit the form when value is
 *                                     changed
 * @param bool|string $showEmpty       Whether to show "empty" value (string for
 *                                     translated value)
 * @param string      $additionalAttrs Any additional attributes
 * @param bool        $translate       Whether the options are translated
 *
 * @return string HTML
 */
function htmlListBox($strName, $astrValues, $strSelected, $strStyle = '',
    $submitOnChange = false, $showEmpty = true, $additionalAttrs = '',
    $translate = false
) {
    $strOnChange = '';
    if ($submitOnChange) {
        $strOnChange = " onchange='this.form.submit();'";
    }
    if ($additionalAttrs) {
        $additionalAttrs = " $additionalAttrs";
    }
    $strListBox = "<select class=\"$strStyle\" id=\"$strName\" name=\"$strName\"{$strOnChange}{$additionalAttrs}>\n";
    if ($showEmpty) {
        if (true === $showEmpty) {
            $showEmpty = ' - ';
        } else {
            $showEmpty = Translator::translate($showEmpty);
        }
        $strListBox .= '<option value=""' . ($strSelected ? '' : ' selected') .
             ">$showEmpty</option>\n";
    }

    foreach ($astrValues as $value => $desc) {
        $strSelect = $strSelected == $value ? ' selected' : '';
        if ($translate) {
            $desc = Translator::translate($desc);
        }
        $strListBox .= '<option value="' . htmlspecialchars($value) . "\"$strSelect>" .
             htmlspecialchars($desc) . "</option>\n";
    }
    $strListBox .= "</select>\n";

    return $strListBox;
}

/**
 * Create a listbox from an SQL query
 *
 * @param string $strName                  Element name
 * @param string $strQuery                 SQL query to get the list contents
 * @param string $strSelected              Selected value
 * @param string $strStyle                 CSS style
 * @param bool   $blnSubmitOnChange        Whether to submit the form when a value is
 *                                         selected
 * @param string $astrAdditionalAttributes Additional element attributes
 * @param bool   $translate                Whether to translate the choices
 *
 * @return string
 */
function htmlSQLListBox($strName, $strQuery, $strSelected, $strStyle = '',
    $blnSubmitOnChange = false, $astrAdditionalAttributes = '', $translate = false
) {

    $astrValues = [];
    $intRes = dbQueryCheck($strQuery);
    while ($row = mysqli_fetch_row($intRes)) {
        $astrValues[$row[0]] = $row[1];
    }
    $showEmpty = true;
    if (strstr($strStyle, ' noemptyvalue')) {
        $strStyle = str_replace(' noemptyvalue', '', $strStyle);
        $showEmpty = false;
    }
    $strListBox = htmlListBox(
        $strName, $astrValues, $strSelected, $strStyle,
        $blnSubmitOnChange, $showEmpty, $astrAdditionalAttributes, $translate
    );

    return $strListBox;
}

/**
 * Get the value for the specified option
 *
 * @param string $strQuery    SQL query
 * @param string $strSelected Selected option
 *
 * @return string
 */
function getSQLListBoxSelectedValue($strQuery, $strSelected)
{
    $intRes = dbQueryCheck($strQuery);
    while ($row = mysqli_fetch_row($intRes)) {
        if ($row[0] == $strSelected) {
            return $row[1];
        }
    }
    return '';
}

/**
 * Get the value for the specified option of a search list
 *
 * @param string $strQuery    SQL query
 * @param string $strSelected Selected option
 *
 * @return string
 */
function getSearchListSelectedValue($strQuery, $strSelected)
{
    parse_str($strQuery, $params);
    $result = createJSONSelectList($params['table'], 0, 1, '', '', '', $strSelected);
    return $result['records'][0]['text'] ?? '';
}

/**
 * Get the value for the specified option
 *
 * @param array $options  Options
 * @param array $selected Selected option
 *
 * @return string
 */
function getListBoxSelectedValue($options, $selected)
{
    if (isset($options[$selected])) {
        return $options[$selected];
    }
    return '';
}

/**
 * Create a form element
 *
 * @param string $strName                  Element name
 * @param string $strType                  Element type
 * @param string $strValue                 Element value
 * @param string $strStyle                 Element style
 * @param string $strListQuery             Query for list element
 * @param string $strMode                  Edit mode
 * @param string $strParentKey             Parent record ID
 * @param string $strTitle                 Element title
 * @param array  $astrDefaults             Unused TODO: remove
 * @param array  $astrAdditionalAttributes Additional HTML attributes
 * @param array  $options                  Options for a listbox or drop-down menu
 *
 * @return string
 */
function htmlFormElement($strName, $strType, $strValue, $strStyle, $strListQuery = '',
    $strMode = 'MODIFY', $strParentKey = null, $strTitle = '', $astrDefaults = [],
    $astrAdditionalAttributes = '', $options = null
) {

    if ($astrAdditionalAttributes) {
        $astrAdditionalAttributes = " $astrAdditionalAttributes";
    }
    $strFormElement = '';
    $readOnly = $strMode == 'MODIFY' ? '' : ' readonly="readonly"';
    $disabled = $strMode == 'MODIFY' ? '' : ' disabled="disabled"';

    switch ($strType) {
    case 'TEXT':
        if (strstr($strStyle, 'hasDateRangePicker')) {
            $autocomplete = ' autocomplete="off"';
        } else {
            $autocomplete = '';
        }

        $strFormElement = "<input type=\"text\" class=\"form-control $strStyle\"$autocomplete " .
             "id=\"$strName\" name=\"$strName\" value=\"" .
             htmlspecialchars($strValue) . "\"$astrAdditionalAttributes$readOnly>\n";
        break;

    case 'PASSWD':
    case 'PASSWD_STORED':
        $strFormElement = "<input type=\"password\" class=\"form-control $strStyle\" " .
             "id=\"$strName\" name=\"$strName\" value=\"\"$astrAdditionalAttributes$readOnly>\n";
        break;

    case 'CHECK':
        $strValue = $strValue ? 'checked' : '';
        $strFormElement = "<input type=\"checkbox\" id=\"$strName\" name=\"$strName\" value=\"1\" " .
             htmlspecialchars($strValue) . "$astrAdditionalAttributes$disabled>\n";
        break;

    case 'RADIO':
        $strChecked = $strValue ? 'checked' : '';
        $strFormElement = "<input type=\"radio\" id=\"$strName\" name=\"$strName\" value=\"" .
             htmlspecialchars($strValue) . "\"$astrAdditionalAttributes$disabled>\n";
        break;

    case 'INT':
        $hideZero = false;
        if (strstr($strStyle, ' hidezerovalue')) {
            $strStyle = str_replace(' hidezerovalue', '', $strStyle);
            $hideZero = true;
        }
        if ($hideZero && $strValue == 0) {
            $strValue = '';
        }
        $strFormElement = "<input type=\"text\" class=\"form-control $strStyle\" " .
             "id=\"$strName\" name=\"$strName\" value=\"" .
             htmlspecialchars($strValue) . "\"$astrAdditionalAttributes$readOnly>\n";
        break;

    case 'INTDATE':
        $strFormElement = "<input type=\"date\" class=\"form-control $strStyle\" " .
             "id=\"$strName\" name=\"$strName\" value=\"" .
             htmlspecialchars($strValue) . "\"$astrAdditionalAttributes$readOnly>\n";
        break;

    case 'HID_INT':
    case 'HID_UUID':
        $strFormElement = '<input type="hidden" ' .
             "id=\"$strName\" name=\"$strName\" value=\"" .
             htmlspecialchars($strValue ?? '') . "\">\n";
        break;

    case 'AREA':
        $strFormElement = '<textarea class="form-control ' . $strStyle . '" ' .
             'id="' . $strName . '" name="' . $strName .
             "\"$astrAdditionalAttributes$readOnly>" . $strValue . "</textarea>\n";
        break;

    case 'RESULT':
        $strListQuery = str_replace('_ID_', $strValue, $strListQuery);
        $res = dbQueryCheck($strListQuery);
        $strFormElement = htmlspecialchars(
            dbFetchValue($res)
        ) . "\n";
        break;

    case 'LIST':
        $translate = false;
        if (strstr($strStyle, ' translated')) {
            $translate = true;
            $strStyle = str_replace(' translated', '', $strStyle);
        }

        if ($strMode == 'MODIFY') {
            if (is_array($strListQuery)) {
                $showEmpty = true;
                if (strstr($strStyle, ' noemptyvalue')) {
                    $showEmpty = false;
                    $strStyle = str_replace(' noemptyvalue', '', $strStyle);
                }
                $strFormElement = htmlListBox(
                    $strName, $strListQuery, $strValue, $strStyle, false, $showEmpty,
                    $astrAdditionalAttributes, $translate
                );

            } else {
                $strFormElement = htmlSQLListBox(
                    $strName, $strListQuery, $strValue,
                    $strStyle, false, $astrAdditionalAttributes, $translate
                );
            }
        } else {
            $strFormElement = "<input type=\"text\" class=\"form-control $strStyle\" " .
                 "id=\"$strName\" name=\"$strName\" value=\"" .
                htmlspecialchars(
                    getSQLListBoxSelectedValue($strListQuery, $strValue, $translate)
                ) .
                 "\"$astrAdditionalAttributes$readOnly>\n";
        }
        break;

    case 'SEARCHLIST':
        if ($strMode == 'MODIFY') {
            $showEmpty = '1';
            if (strstr($strStyle, ' noemptyvalue')) {
                $strStyle = str_replace(' noemptyvalue', '', $strStyle);
                $showEmpty = '0';
            }
            $strValue = htmlspecialchars($strValue ?? '');
            $valueDesc = htmlspecialchars(
                getSearchListSelectedValue($strListQuery, $strValue, false)
            );
            $onChange = $astrAdditionalAttributes ? trim($astrAdditionalAttributes) : '';
            $encodedQuery = htmlspecialchars($strListQuery);
            $strFormElement = <<<EOT
<select autocomplete="off" class="$strStyle js-searchlist" id="$strName" name="$strName" data-list-query="$encodedQuery" data-show-empty="$showEmpty" data-on-change="$onChange">
  <option value="$strValue" selected>$valueDesc</option>
</select>
EOT;
        } else {
            $strFormElement = "<input type=\"text\" class=\"form-control $strStyle\" " .
                 "id=\"$strName\" name=\"$strName\" value=\"" .
                htmlspecialchars(
                    getSearchListSelectedValue($strListQuery, $strValue, false)
                ) .
                 "\"$astrAdditionalAttributes$readOnly>\n";
        }
        break;
    case 'SELECT':
        $translate = false;
        if (strstr($strStyle, ' translated')) {
            $translate = true;
            $strStyle = str_replace(' translated', '', $strStyle);
        }
        if ($strMode == 'MODIFY') {
            $strFormElement = htmlListBox(
                $strName, $options, $strValue, $strStyle,
                false, $astrAdditionalAttributes, $translate
            );
        } else {
            $strFormElement = "<input type=\"text\" class=\"form-control $strStyle\" " .
                "id=\"$strName\" name=\"$strName\" value=\"" . htmlspecialchars(
                    getListBoxSelectedValue($options, $strValue, $translate)
                ) .
                 "\"$astrAdditionalAttributes$readOnly>\n";
        }
        break;
    case 'TAGS':
        if ($strMode == 'MODIFY') {
            $showEmpty = '1';
            if (strstr($strStyle, 'noemptyvalue ')) {
                $strStyle = str_replace('noemptyvalue ', '', $strStyle);
                $showEmpty = '0';
            }
            $values = $strValue ? explode(',', $strValue) : [];
            $onChange = $astrAdditionalAttributes ? trim($astrAdditionalAttributes) : '';
            $encodedQuery = htmlspecialchars($strListQuery);
            $strFormElement = <<<EOT
<select multiple autocomplete="off" class="$strStyle js-searchlist select2 tags" id="$strName" name="$strName" data-list-query="$encodedQuery" data-show-empty="$showEmpty" data-on-change="$onChange">

EOT;
            foreach ($values as $value) {
                $value = htmlspecialchars($value);
                $strFormElement .= '<option value="' . $value . '" selected>' . $value . "</option>\n";
            }

            $strFormElement .= '</select>';
        } else {
            $strFormElement = "<input type=\"text\" class=\"form-control $strStyle\" " .
                 "id=\"$strName\" name=\"$strName\" value=\"" .
                 htmlspecialchars($strValue) .
                 "\"$astrAdditionalAttributes$readOnly>\n";
        }
        break;

    case 'BUTTON':
        $strListQuery = str_replace('_ID_', $strValue, $strListQuery);
        switch ($strStyle) {
        case 'custom':
            $strListQuery = str_replace("'", '', $strListQuery);
            $strHref = $strListQuery;
            $strOnClick = '';
            break;

        case 'redirect':
            $strHref = '#';
            $strOnClick = "onclick=\"MLInvoice.Form.saveRecord('$strListQuery', 'redirect'); return false;\"";
            break;

        case 'openwindow':
            $strHref = '#';
            $strOnClick = "onclick=\"MLInvoice.Form.saveRecord('$strListQuery', 'openwindow'); return false;\"";
            break;

        default:
            switch ($strStyle) {
            case 'tiny':
                $strHW = 'height=1,width=1,';
                break;
            case 'small':
                $strHW = 'height=200,width=200,';
                break;
            case 'medium':
                $strHW = 'height=400,width=400,';
                break;
            case 'large':
                $strHW = 'height=600,width=600,';
                break;
            case 'xlarge':
                $strHW = 'height=800,width=650,';
                break;
            case 'full':
                $strHW = '';
                break;
            default:
                $strHW = '';
                break;
            }
            $strHref = '#';
            $strOnClick = 'onclick="window.open(' . $strListQuery . ",'" . $strHW .
                 'menubar=no,scrollbars=no,' .
                 "status=no,toolbar=no'); return false;\"";
            break;
        }
        $strFormElement = "<a class=\"btn btn-secondary formbuttonlink\" href=\"$strHref\" $strOnClick$astrAdditionalAttributes>" .
             htmlspecialchars(Translator::translate($strTitle)) . "</a>\n";
        break;

    case 'JSBUTTON':
        if (strstr($strListQuery, '_ID_') && !$strValue) {
            $strFormElement = Translator::translate('SaveFirst');
        } else {
            if ($strValue) {
                $strListQuery = str_replace('_ID_', $strValue, $strListQuery);
            }
            $strOnClick = "onClick=\"$strListQuery\"";
            $strFormElement = "<a class=\"btn btn-secondary formbuttonlink\" href=\"#\" $strOnClick$astrAdditionalAttributes>" .
                 htmlspecialchars(Translator::translate($strTitle)) . "</a>\n";
        }
        break;

    case 'DROPDOWNMENU':
        if (strstr($strListQuery, '_ID_') && !$strValue) {
            $strFormElement = Translator::translate('SaveFirst');
        } else {
            $menuTitle = htmlspecialchars(Translator::translate($strTitle));
            $menuItems = '';
            foreach ($options as $option) {
                $strListQuery = str_replace('_ID_', $strValue, $option['listquery']);
                $menuItems .= '<li onClick="' . $strListQuery . '"><div>' . Translator::translate($option['label']) . '</div></li>';
            }
            $strFormElement = <<<EOT
<ul class="dropdownmenu" $astrAdditionalAttributes>
  <li>$menuTitle
    <ul>
      $menuItems
    </ul>
  </li>
</ul>
EOT;
        }
        break;

    case 'IMAGE':
        $strListQuery = str_replace('_ID_', $strValue, $strListQuery);
        $strFormElement = "<img id=\"$strName\" class=\"$strStyle\" src=\"$strListQuery\" title=\"" .
             htmlspecialchars(Translator::translate($strTitle)) . "\">\n";
        break;

    case 'FILE':
        $strFormElement = '<input type="file" class="form-control ' . $strStyle . '" ' .
             'id="' . $strName . '" name="' . $strName .
             "\"$astrAdditionalAttributes$readOnly>\n";
        break;

    default:
        $strFormElement = "&nbsp;\n";
    }

    return $strFormElement;
}
