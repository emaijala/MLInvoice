<?php
/**
 * Main script
 *
 * PHP version 7
 *
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
if (($_SERVER['MLINVOICE_REMOTE_COVERAGE'] ?? $_SERVER['REDIRECT_MLINVOICE_REMOTE_COVERAGE'] ?? false)
    && file_exists(__DIR__ . DIRECTORY_SEPARATOR . 'c3.php')
) {
    include_once __DIR__ . DIRECTORY_SEPARATOR . 'c3.php';
}

// buffered, so we can redirect later if necessary
ini_set('implicit_flush', 'Off');
ob_start();

require_once 'vendor/autoload.php';

if (!file_exists(__DIR__ . DIRECTORY_SEPARATOR . 'config.php')) {
    include_once 'setup.php';
    $setup = new Setup();
    $setup->initialSetup();
    exit();
}

require_once 'config.php';

if (defined('_PROFILING_') && is_callable('xhprof_enable')) {
    xhprof_enable();
}

require_once 'sessionfuncs.php';
require_once 'navi.php';
require_once 'list.php';
require_once 'form.php';
require_once 'start_page.php';
require_once 'settings.php';
require_once 'translator.php';
require_once 'settings_list.php';
require_once 'version.php';
require_once 'sqlfuncs.php';

initDbConnection();
sesVerifySession();

$strFunc = sanitize(getPostOrQuery('func', '')) ?: 'start_page';
$strList = sanitize(getPostOrQuery('list', ''));
$strForm = sanitize(getPostOrQuery('form', ''));

if ($strFunc == 'logout') {
    header('Location: logout.php');
    exit();
}

// Back-compatibility:
if ('open_invoices' === $strFunc) {
    $strFunc = 'start_page';
}

if (!$strList) {
    $strList = getListFromFunc($strFunc);
}

if ($strFunc == 'send_api') {
    include_once 'apiclient.php';
    $invoiceId = getPostOrQuery('invoice_id');
    $apiId = getPostOrQuery('api_id');
    $templateId = getPostOrQuery('template_id');
    $invoice = getInvoice($invoiceId);
    $client = new ApiClient($apiId, $invoiceId, $templateId);
    $result = $client->send();
    if ($result['success']) {
        $_SESSION['formMessage'] = Translator::Translate('SendSuccess');
        if ($result['message']) {
            $_SESSION['formMessage'] .= ' (' . $result['message'] . ')';
        }
        if (!empty($result['warnings'])) {
            $_SESSION['formErrorMessage'] = $result['warnings'];
        }
    } else {
        $_SESSION['formErrorMessage'] = Translator::Translate('SendFailure');
        if ($result['message']) {
            $_SESSION['formErrorMessage'] .= ' (' . $result['message'] . ')';
        }
    }
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit();
}

if (!$strFunc && $strForm) {
    $strFunc = 'invoices';
}

$title = getPageTitle($strFunc, $strList, $strForm, sanitize(getPostOrQuery('operation', '')));

if ($strFunc == 'system' && getPostOrQuery('operation', '') == 'dbdump'
    && sesAccessLevel(
        [
            ROLE_BACKUPMGR,
            ROLE_ADMIN
        ]
    )
) {
    createDbDump();
    exit();
}

$extraJs = [];
if (substr($strFunc, -7) === '_report') {
    $extraJs[] = 'node_modules/jszip/dist/jszip.min.js';
    $extraJs[] = 'node_modules/pdfmake/build/pdfmake.min.js';
    $extraJs[] = 'node_modules/pdfmake/build/vfs_fonts.js';
}

echo htmlPageStart($title, $extraJs);
?>

<body>
    <div class="pagewrapper mb-4">
        <?php echo htmlMainTabs($strFunc); ?>
        <div id="content" class="container-fluid">
<?php

$level = 1;
if ($strList && $strFunc == 'multiedit') {
    ++$level;
}
if ($strForm && 'search' !== $strFunc) {
    ++$level;
}
if ('results' === $strFunc) {
    $level += 0.5;
}
$requestUri = $_SERVER['REQUEST_URI'];
$query = $_SERVER['QUERY_STRING'];
if (substr($requestUri, -1) === '/' || strpos($requestUri, 'index.php') !== false || strpos($query, 'func=') !== false) {
    $arrHistory = updateNavigationHistory($strFunc, $title, $query, $level);
}
?>
  <nav aria-label="<?php echo Translator::translate('Breadcrumbs')?>">
    <ol class="breadcrumb">
        <?php foreach ($arrHistory as $entry) { ?>
            <?php $url = str_replace('&', '&amp;', $entry['url']) . '&amp;bc=1'; ?>
            <?php if ($entry['active']) { ?>
                <li class="breadcrumb-item active" aria-current="page">
                    <h1 class="d-inline"><?php echo $entry['title']?></h1>
                </li>
            <?php } else { ?>
                <li class="breadcrumb-item">
                    <a href="index.php?<?php echo $url?>"><?php echo $entry['title']?></a>
                </li>
            <?php } ?>
        <?php } ?>
    </ol>
  </nav>
<?php
if ($strFunc == 'start_page' && !$strForm) {
    ?>
  <div id="version">
    v<?php echo $softwareVersion?>
  </div>
    <?php
    if (getSetting('check_updates')) {
        ?>
  <script>
    $(document).ready(function() {
        MLInvoice.checkForUpdates('<?php echo $softwareVersion?>');
    });
  </script>
        <?php
    }
}
if (!$strForm) {
    createFuncMenu($strFunc);
}

$operation = getPostOrQuery('operation', '');
if ($strFunc == 'system' && $operation == 'export' && sesAdminAccess()) {
    include_once 'export.php';
    $export = new ExportData();
    $export->launch();
} elseif ($strFunc == 'system' && $operation == 'import' && sesAdminAccess()) {
    include_once 'import.php';
    $import = new ImportFile();
    $import->launch();
} elseif ($strFunc == 'system' && $operation == 'update' && sesAdminAccess()) {
    include_once 'updater.php';
    $updater = new Updater();
    $updater->launch();
} elseif ($strFunc == 'system' && $operation == 'backup'
    && sesAccessLevel(
        [
            ROLE_BACKUPMGR,
            ROLE_ADMIN
        ]
    )
) {
    include_once 'backup.php';
    $backup = new Backup();
    $backup->launch();
} elseif ($strFunc == 'import_statement') {
    include_once 'import_statement.php';
    $import = new ImportStatement();
    $import->launch();
} elseif ($strFunc === 'invoice_report') {
    include_once 'invoice_report.php';
    $invoiceReport = new InvoiceReport();
    $invoiceReport->createReport();
} elseif ($strFunc === 'product_report') {
    include_once 'product_report.php';
    $productReport = new ProductReport();
    $productReport->createReport();
} elseif ($strFunc === 'product_stock_report') {
    include_once 'product_stock_report.php';
    $productStockReport = new ProductStockReport();
    $productStockReport->createReport();
} elseif ($strFunc === 'accounting_report') {
    include_once 'accounting_report.php';
    $accountingReport = new AccountingReport();
    $accountingReport->createReport();
} elseif ($strFunc == 'profile') {
    include_once 'profile.php';
    $profile = new Profile();
    $profile->launch();
} elseif ($strFunc == 'multiedit') {
    include_once 'multiedit.php';
    $multiedit = new MultiEdit();
    $multiedit->launch();
} elseif ($strFunc == 'search') {
    include_once 'search.php';
    $search = new Search();
    $search->formAction();
} elseif ($strFunc == 'results') {
    include_once 'search.php';
    $search = new Search();
    $search->resultsAction();
} elseif ($strFunc == 'edit_searches') {
    include_once 'search.php';
    $search = new Search();
    $search->editSearchesAction();
} else {
    if ($strForm) {
        createForm($strFunc, $strList, $strForm);
    } else {
        if ($strFunc == 'start_page') {
            createStartPage();
        } elseif ($strFunc == 'invoices') {
            createList($strFunc, $strList, '', '', Search::SEARCH_NON_ARCHIVED_INVOICES, false, false, 'invoice');
        } elseif ($strFunc == 'archived_invoices') {
            createList('archived_invoices', 'archived_invoices', 'archived_invoices', '', Search::SEARCH_ARCHIVED_INVOICES, false, false, 'invoice');
        } elseif ($strFunc == 'offers') {
            createList($strFunc, $strList, '', '', Search::SEARCH_NON_ARCHIVED_OFFERS, false, false, 'invoice');
        } elseif ($strFunc == 'archived_offers') {
            createList('archived_offers', 'archived_offers', 'archived_offers', '', Search::SEARCH_ARCHIVED_OFFERS, false, false, 'invoice');
        } else {
            if ($strList == 'settings') {
                createSettingsList();
            } else {
                createList($strFunc, $strList);
            }
        }
    }
}
?>
        </div>
    </div>
</body>
</html>

<?php
if (defined('_PROFILING_') && is_callable('xhprof_disable')) {
    $data = xhprof_disable();
    file_put_contents(
        sys_get_temp_dir() . '/' . uniqid() . '.mlinvoice-index.xhprof',
        serialize($data)
    );
}
