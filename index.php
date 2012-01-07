<?php
/*******************************************************************************
VLLasku: web-based invoicing application.
Copyright (C) 2010-2012 Ere Maijala

This program is free software. See attached LICENSE.

*******************************************************************************/

/*******************************************************************************
VLLasku: web-pohjainen laskutusohjelma.
Copyright (C) 2010-2012 Ere Maijala

Tämä ohjelma on vapaa. Lue oheinen LICENSE.

*******************************************************************************/

require_once 'sessionfuncs.php';
require_once 'navi.php';
require_once 'list.php';
require_once 'form.php';
require_once 'open_invoices.php';
require_once 'settings.php';
require_once 'localize.php';
require_once 'settings_list.php';

sesVerifySession();

// buffered, so we can redirect later if necessary
ini_set('implicit_flush', 'Off');
ob_start(); 

$strFunc = sanitize(getRequest('func', 'open_invoices'));
$strList = sanitize(getRequest('list', ''));
$strForm = sanitize(getRequest('form', ''));

if (!$strFunc)
  $strFunc = 'open_invoices';

if ($strFunc == 'logout')
{
  header("Location: ". _PROTOCOL_ . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/logout.php");
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
    array("name" => "invoice", "title" => "locSHOWINVOICENAVI", 'action' => 'open_invoices', "levels_allowed" => array(ROLE_READONLY, ROLE_USER, ROLE_BACKUPMGR) ),
    array("name" => "archive", "title" => "locSHOWARCHIVENAVI", 'action' => 'archived_invoices', "levels_allowed" => array(ROLE_READONLY, ROLE_USER, ROLE_BACKUPMGR) ),
    array("name" => "company", "title" => "locSHOWCOMPANYNAVI", 'action' => 'companies', "levels_allowed" => array(ROLE_USER, ROLE_BACKUPMGR) ),
    array("name" => "reports", "title" => "locSHOWREPORTNAVI", 'action' => 'reports', "levels_allowed" => array(ROLE_READONLY, ROLE_USER, ROLE_BACKUPMGR) ),
    array("name" => "settings", "title" => "locSHOWSETTINGSNAVI", 'action' => 'settings', "action" => "settings", "levels_allowed" => array(ROLE_USER, ROLE_BACKUPMGR) ),
    array("name" => "system", "title" => "locSHOWSYSTEMNAVI", 'action' => 'system', "levels_allowed" => array(ROLE_BACKUPMGR, ROLE_ADMIN) ),
    array("name" => "logout", "title" => "locLOGOUT", 'action' => 'logout', "levels_allowed" => null )
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
    echo "    $strButton\n";
  }
}
echo "  </ul>\n";

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
  </div>
  <div class="breadcrumbs">
    <?php echo $strBreadcrumbs . "\n"?>
  </div>

<?php
if ($strFunc == 'system' && getRequest('operation', '') == 'export' && sesAdminAccess())
{
  createFuncMenu($strFunc);
  require_once 'export.php';
  do_export();
}
elseif ($strFunc == 'system' && getRequest('operation', '') == 'import' && sesAdminAccess())
{
  createFuncMenu($strFunc);
  require_once 'import.php';
  do_import();
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
      $invoiceReport = new InvoiceReport;
      $invoiceReport->createReport(); 
      break;
    case 'product': 
      require_once 'product_report.php'; 
      $productReport = new ProductReport;
      $productReport->createReport(); 
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
      if ($strFunc == 'open_invoices')
        createOpenInvoiceList();
      else
      {
        if ($strList == 'settings')
          createSettingsList();
        else
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
