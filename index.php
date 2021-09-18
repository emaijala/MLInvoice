<?php
/**
 * Main script
 *
 * PHP version 5
 *
 * Copyright (C) 2010-2019 Ere Maijala
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

// buffered, so we can redirect later if necessary
ini_set('implicit_flush', 'Off');
ob_start();

if (!file_exists(__DIR__ . DIRECTORY_SEPARATOR . 'config.php')) {
    include_once 'setup.php';
    $setup = new Setup();
    $setup->initialSetup();
    exit();
}

require_once 'config.php';

if (defined('_PROFILING_') && is_callable('tideways_enable')) {
    tideways_enable(TIDEWAYS_FLAGS_CPU + TIDEWAYS_FLAGS_MEMORY);
}

require_once 'vendor/autoload.php';
require_once 'sessionfuncs.php';
require_once 'navi.php';
require_once 'list.php';
require_once 'form.php';
require_once 'open_invoices.php';
require_once 'settings.php';
require_once 'translator.php';
require_once 'settings_list.php';
require_once 'version.php';
require_once 'sqlfuncs.php';

sesVerifySession();

$strFunc = sanitize(getPostOrQuery('func', 'open_invoices'));
$strList = sanitize(getPostOrQuery('list', ''));
$strForm = sanitize(getPostOrQuery('form', ''));

if (!$strFunc) {
    $strFunc = 'open_invoices';
}

if ($strFunc == 'logout') {
    header('Location: logout.php');
    exit();
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

$title = getPageTitle($strFunc, $strList, $strForm);

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
if ($strFunc == 'reports') {
    $extraJs[] = 'datatables/JSZip-2.5.0/jszip.min.js';
    $extraJs[] = 'datatables/pdfmake-0.1.36/pdfmake.min.js';
    $extraJs[] = 'datatables/pdfmake-0.1.36//vfs_fonts.js';
}

echo htmlPageStart($title, $extraJs);
?>

<body>
    <div class="pagewrapper ui-widget-content">
        <div class="ui-widget">
            <?php echo htmlMainTabs($strFunc); ?>
            <div id="content">
<?php

$level = 1;
if ($strList && ($strFunc == 'settings' || $strFunc == 'system' || $strFunc == 'multiedit')) {
    ++$level;
}
if ($strForm) {
    ++$level;
}
$arrHistory = updateNavigationHistory($title, $_SERVER['QUERY_STRING'], $level);

$strBreadcrumbs = '';
foreach ($arrHistory as $arrHE) {
    if ($strBreadcrumbs) {
        $strBreadcrumbs .= '&gt; ';
    }
    $url = $arrHE['url'] . '&bc=1';
    $strBreadcrumbs .= '<a href="index.php?' .
         str_replace('&', '&amp;', $url) . '">' . $arrHE['title'] .
         '</a>&nbsp;';
}

?>
  <div class="breadcrumbs">
    <?php echo $strBreadcrumbs . "\n"?>
  </div>
<?php
if ($strFunc == 'open_invoices' && !$strForm) {
    ?>
  <div id="version">
    MLInvoice <?php echo $softwareVersion?>
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

$operation = getPostOrQuery('operation', '');
if ($strFunc == 'system' && $operation == 'export' && sesAdminAccess()) {
    createFuncMenu($strFunc);
    include_once 'export.php';
    $export = new ExportData();
    $export->launch();
} elseif ($strFunc == 'system' && $operation == 'import' && sesAdminAccess()) {
    createFuncMenu($strFunc);
    include_once 'import.php';
    $import = new ImportFile();
    $import->launch();
} elseif ($strFunc == 'system' && $operation == 'update' && sesAdminAccess()) {
    createFuncMenu($strFunc);
    include_once 'updater.php';
    $updater = new Updater();
    $updater->launch();
} elseif ($strFunc == 'import_statement') {
    createFuncMenu($strFunc);
    include_once 'import_statement.php';
    $import = new ImportStatement();
    $import->launch();
} elseif ($strFunc === 'reports') {
    createFuncMenu($strFunc);
    switch ($strForm) {
    case 'invoice' :
        include_once 'invoice_report.php';
        $invoiceReport = new InvoiceReport();
        $invoiceReport->createReport();
        break;
    case 'product' :
        include_once 'product_report.php';
        $productReport = new ProductReport();
        $productReport->createReport();
        break;
    case 'product_stock' :
        include_once 'product_stock_report.php';
        $productStockReport = new ProductStockReport();
        $productStockReport->createReport();
        break;
    case 'accounting' :
        include_once 'accounting_report.php';
        $accountingReport = new AccountingReport();
        $accountingReport->createReport();
        break;
    }
} elseif ($strFunc == 'profile') {
    createFuncMenu($strFunc);
    include_once 'profile.php';
    $profile = new Profile();
    $profile->launch();
} elseif ($strFunc == 'multiedit') {
    createFuncMenu($strFunc);
    include_once 'multiedit.php';
    $multiedit = new MultiEdit();
    $multiedit->launch();
} else {
    if ($strForm) {
        if ($strFunc == 'settings') {
            createFuncMenu($strFunc);
        }
        createForm($strFunc, $strList, $strForm);
    } else {
        createFuncMenu($strFunc);
        if ($strFunc == 'open_invoices') {
            createOpenInvoiceList();
        } elseif ($strFunc == 'invoices') {
            createList($strFunc, $strList, '', '', '', false, false, 'invoice');
        } elseif ($strFunc == 'archived_invoices') {
            createList('archived_invoices', 'archived_invoices', 'archived_invoices', '', '', false, false, 'invoice');
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
    </div>
</body>
</html>

<?php
if (defined('_PROFILING_') && is_callable('tideways_disable')) {
    $data = tideways_disable();
    file_put_contents(
        sys_get_temp_dir() . '/' . uniqid() . '.mlinvoice-index.xhprof',
        serialize($data)
    );
}
