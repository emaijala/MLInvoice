<?php
/*******************************************************************************
MLInvoice: web-based invoicing application.
Copyright (C) 2010-2015 Ere Maijala

This program is free software. See attached LICENSE.

*******************************************************************************/

/*******************************************************************************
MLInvoice: web-pohjainen laskutusohjelma.
Copyright (C) 2010-2015 Ere Maijala

Tämä ohjelma on vapaa. Lue oheinen LICENSE.

*******************************************************************************/

// buffered, so we can redirect later if necessary
ini_set('implicit_flush', 'Off');
ob_start();

require_once 'sessionfuncs.php';
require_once 'navi.php';
require_once 'list.php';
require_once 'form.php';
require_once 'open_invoices.php';
require_once 'settings.php';
require_once 'localize.php';
require_once 'settings_list.php';
require_once 'version.php';
require_once 'sqlfuncs.php';

sesVerifySession();

$strFunc = sanitize(getRequest('func', 'open_invoices'));
$strList = sanitize(getRequest('list', ''));
$strForm = sanitize(getRequest('form', ''));

if (!$strFunc)
  $strFunc = 'open_invoices';

if ($strFunc == 'logout')
{
  header('Location: ' . getSelfPath() . '/logout.php');
  exit;
}

if (!$strFunc && $strForm)
  $strFunc = 'invoices';

$title = getPageTitle($strFunc, $strList, $strForm);

if ($strFunc == 'system' && getRequest('operation', '') == 'dbdump' && sesAccessLevel(array(ROLE_BACKUPMGR, ROLE_ADMIN)))
{
  create_db_dump();
  exit;
}


echo htmlPageStart(_PAGE_TITLE_ . " - $title", getSetting('session_keepalive') ? array('js/keepalive.js') : null);

$normalMenuRights = array(ROLE_READONLY, ROLE_USER, ROLE_BACKUPMGR);
$astrMainButtons = array (
    array("name" => "invoice", "title" => "locShowInvoiceNavi", 'action' => 'open_invoices', "levels_allowed" => array(ROLE_READONLY, ROLE_USER, ROLE_BACKUPMGR) ),
    array("name" => "archive", "title" => "locShowArchiveNavi", 'action' => 'archived_invoices', "levels_allowed" => array(ROLE_READONLY, ROLE_USER, ROLE_BACKUPMGR) ),
    array("name" => "company", "title" => "locShowClientNavi", 'action' => 'companies', "levels_allowed" => array(ROLE_USER, ROLE_BACKUPMGR) ),
    array("name" => "reports", "title" => "locShowReportNavi", 'action' => 'reports', "levels_allowed" => array(ROLE_READONLY, ROLE_USER, ROLE_BACKUPMGR) ),
    array("name" => "settings", "title" => "locShowSettingsNavi", 'action' => 'settings', "action" => "settings", "levels_allowed" => array(ROLE_USER, ROLE_BACKUPMGR) ),
    array("name" => "system", "title" => "locShowSystemNavi", 'action' => 'system', "levels_allowed" => array(ROLE_BACKUPMGR, ROLE_ADMIN) ),
    array("name" => "logout", "title" => "locLogout", 'action' => 'logout', "levels_allowed" => null )
);

?>

<body>
<div class="pagewrapper ui-widget-content">
<div class="ui-widget">
  <div id="maintabs" class="navi ui-widget-header ui-tabs">
    <ul class="ui-tabs-nav ui-helper-clearfix ui-corner-all">
<?php
foreach ($astrMainButtons as $button)
{
  $strButton = '<li class="functionlink ui-state-default ui-corner-top';
  if ($button['action'] == $strFunc || ($button['action'] == 'open_invoices' && $strFunc == 'invoices'))
    $strButton .= ' ui-tabs-selected ui-state-active';
  $strButton .= '"><a class="functionlink" href="?func=' . $button['action'] . '">';
  $strButton .= $GLOBALS[$button['title']] . '</a></li>';

  if (!isset($button['levels_allowed']) || sesAccessLevel($button['levels_allowed']) || sesAdminAccess())
  {
    echo "      $strButton\n";
  }
}
?>
    </ul>
  </div>
<?php

$level = 1;
if ($strList && ($strFunc == 'settings' || $strFunc == 'system'))
  ++$level;
if ($strForm)
  ++$level;
$arrHistory = sesUpdateHistory($title, $_SERVER['QUERY_STRING'], $level);

$strBreadcrumbs = '';
foreach ($arrHistory as $arrHE)
{
  if ($strBreadcrumbs)
    $strBreadcrumbs .= '&gt; ';
  $strBreadcrumbs .= '<a href="index.php?' . str_replace('&', '&amp;', $arrHE['url']) . '">' . $arrHE['title'] . '</a>&nbsp;';
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
  <script type="text/javascript">
    $(document).ready(function() {
      if ($.cookie("updateversion")) {
        updateVersionMessage($.parseJSON($.cookie("updateversion")));
        return;
      }
    	$.getJSON('http://www.labs.fi/mlinvoice_version.php?callback=?', function(data) {
        updateVersionMessage(data);
    	});
    });

    function compareVersionNumber(v1, v2)
    {
      v1 = v1.split('.');
      v2 = v2.split('.');

      while (v1.length < v2.length) {
        v1.push(0);
      }
      while (v2.length < v1.length) {
        v2.push(0);
      }

      for (i = 0; i < v1.length; i++)
      {
        if (v1[i] === v2[i]) {
          continue;
        }
        return parseInt(v1[i]) > parseInt(v2[i]) ? 1 : -1;
      }
      return 0;
    }

    function updateVersionMessage(data)
    {
    	var title = new String("<?php echo $GLOBALS['locUpdateAvailableTitle']?>").replace("{version}", data.version).replace("{date}", data.date);
    	var result = compareVersionNumber(data.version, "<?php echo $softwareVersion?>");
      if (result > 0) {
        $("<a/>").attr("href", data.url).attr("title", title).text("<?php echo $GLOBALS['locUpdateAvailable']?>").appendTo("#version");
      } else if (result < 0) {
        $("<span/>").text("<?php echo $GLOBALS['locPrereleaseVersion']?>").appendTo("#version");
      }
      $.cookie("updateversion", $.toJSON(data), { expires: 1 });
    }
  </script>
<?php
  }
}
if ($strFunc == 'system' && getRequest('operation', '') == 'export' && sesAdminAccess())
{
  createFuncMenu($strFunc);
  require_once 'export.php';
  $export = new ExportData();
  $export->launch();
}
elseif ($strFunc == 'system' && getRequest('operation', '') == 'import' && sesAdminAccess())
{
  createFuncMenu($strFunc);
  require_once 'import.php';
  $import = new ImportFile();
  $import->launch();
}
elseif ($strFunc == 'import_statement')
{
  createFuncMenu($strFunc);
  require_once 'import_statement.php';
  $import = new ImportStatement();
  $import->launch();
}
else
{
  switch ($strFunc)
  {
  case 'reports':
    createFuncMenu($strFunc);
    switch ($strForm)
    {
    case 'invoice':
      require_once 'invoice_report.php';
      $invoiceReport = new InvoiceReport();
      $invoiceReport->createReport();
      break;
    case 'product':
      require_once 'product_report.php';
      $productReport = new ProductReport();
      $productReport->createReport();
      break;
    case 'product_stock':
      require_once 'product_stock_report.php';
      $productStockReport = new ProductStockReport();
      $productStockReport->createReport();
      break;
    }
    break;
  default:
    if ($strForm)
    {
      if ($strFunc == 'settings')
        createFuncMenu($strFunc);
      createForm($strFunc, $strList, $strForm);
    }
    else
    {
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
