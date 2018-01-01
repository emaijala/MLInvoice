<?php
/*******************************************************************************
 MLInvoice: web-based invoicing application.
 Copyright (C) 2010-2017 Ere Maijala

 This program is free software. See attached LICENSE.

 *******************************************************************************/

/*******************************************************************************
 MLInvoice: web-pohjainen laskutusohjelma.
 Copyright (C) 2010-2017 Ere Maijala

 Tämä ohjelma on vapaa. Lue oheinen LICENSE.

 *******************************************************************************/

// buffered, so we can redirect later if necessary
ini_set('implicit_flush', 'Off');
ob_start();

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

$strFunc = sanitize(getRequest('func', 'open_invoices'));
$strList = sanitize(getRequest('list', ''));
$strForm = sanitize(getRequest('form', ''));

if (!$strFunc)
    $strFunc = 'open_invoices';

if ($strFunc == 'logout') {
    header('Location: logout.php');
    exit();
}

if (!$strFunc && $strForm)
    $strFunc = 'invoices';

$title = getPageTitle($strFunc, $strList, $strForm);

if ($strFunc == 'system' && getRequest('operation', '') == 'dbdump'
    && sesAccessLevel(
        [
            ROLE_BACKUPMGR,
            ROLE_ADMIN
        ]
    )
) {
    create_db_dump();
    exit();
}

$extraJs = [];
if ($strFunc == 'reports') {
    $extraJs[] = 'datatables/dataTables.buttons.min.js';
    $extraJs[] = 'datatables/buttons.html5.min.js';
    $extraJs[] = 'js/jszip.min.js';
    $extraJs[] = 'js/pdfmake.min.js';
    $extraJs[] = 'js/vfs_fonts.js';
}

echo htmlPageStart($title, $extraJs);
?>

<body>
    <div class="pagewrapper ui-widget-content">
        <div class="ui-widget">
            <?php echo htmlMainTabs($strFunc); ?>
<?php

$level = 1;
if ($strList && ($strFunc == 'settings' || $strFunc == 'system'))
    ++$level;
if ($strForm)
    ++$level;
$arrHistory = updateNavigationHistory($title, $_SERVER['QUERY_STRING'], $level);

$strBreadcrumbs = '';
foreach ($arrHistory as $arrHE) {
    if ($strBreadcrumbs)
        $strBreadcrumbs .= '&gt; ';
    $strBreadcrumbs .= '<a href="index.php?' .
         str_replace('&', '&amp;', $arrHE['url']) . '">' . $arrHE['title'] .
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
        $address = defined('_UPDATE_ADDRESS_') ? _UPDATE_ADDRESS_
            : 'https://www.labs.fi/mlinvoice_version.php';
        ?>
  <script type="text/javascript">
    $(document).ready(function() {
      MLInvoice.checkForUpdates('<?php echo $address?>', '<?php echo $softwareVersion?>');
    });
  </script>
<?php
    }
}
if ($strFunc == 'system' && getRequest('operation', '') == 'export'
    && sesAdminAccess()
) {
    createFuncMenu($strFunc);
    include_once 'export.php';
    $export = new ExportData();
    $export->launch();
} elseif ($strFunc == 'system' && getRequest('operation', '') == 'import'
    && sesAdminAccess()
) {
    createFuncMenu($strFunc);
    include_once 'import.php';
    $import = new ImportFile();
    $import->launch();
} elseif ($strFunc == 'system' && getRequest('operation', '') == 'update'
    && sesAdminAccess()
) {
    createFuncMenu($strFunc);
    include_once 'updater.php';
    $updater = new Updater();
    $updater->launch();
} elseif ($strFunc == 'import_statement') {
    createFuncMenu($strFunc);
    include_once 'import_statement.php';
    $import = new ImportStatement();
    $import->launch();
} else {
    switch ($strFunc) {
    case 'reports' :
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
        break;
    default :
        if ($strForm) {
            if ($strFunc == 'settings')
                createFuncMenu($strFunc);
            createForm($strFunc, $strList, $strForm);
        } else {
            createFuncMenu($strFunc);
            if ($strFunc == 'open_invoices') {
                createOpenInvoiceList();
            } elseif ($strFunc == 'archived_invoices') {
                createList('archived_invoices', 'invoice', 'archived_invoices', '');
            } else {
                if ($strList == 'settings') {
                    createSettingsList();
                } else {
                    createList($strFunc, $strList);
                }
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
if (defined('_PROFILING_') && is_callable('tideways_disable')) {
    $data = tideways_disable();
    file_put_contents(
        sys_get_temp_dir() . '/' . uniqid() . '.mlinvoice-index.xhprof',
        serialize($data)
    );
}
