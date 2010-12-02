<?php
/*******************************************************************************
PkLasku : web-based invoicing software.
Copyright (C) 2004-2008 Samu Reinikainen

This program is free software. See attached LICENSE.

*******************************************************************************/

/*******************************************************************************
PkLasku : web-pohjainen laskutusohjelmisto.
Copyright (C) 2004-2008 Samu Reinikainen

Tämä ohjelma on vapaa. Lue oheinen LICENSE.

*******************************************************************************/

require_once 'sessionfuncs.php';
require_once 'navi.php';
require_once 'list.php';
require_once 'form.php';
require_once 'open_invoices.php';
require_once 'settings.php';
require_once 'localize.php';

if (!getRequest('ses', ''))
{
  header("Location: ". _PROTOCOL_ . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/login.php");
  exit;
}

$strSesID = sesVerifySession();

$strFunc = getRequest('func', '');
$strList = getRequest('list', '');
$strForm = getRequest('form', '');

if ($strFunc == 'logout')
{
  header("Location: ". _PROTOCOL_ . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/logout.php?ses=$strSesID");
  exit;
}

if (!$strFunc && $strForm)
  $strFunc = 'invoices';

$title = '';
switch($strFunc)
{
case '':
  if ($strForm)
    $title = $GLOBALS['locINVOICE'];
  else
    $title = $GLOBALS['locOPENANDUNPAIDINVOICES'];
  break;
case 'invoices':
  if ($strForm)
    $title = $GLOBALS['locINVOICE'];
  else
    $title = $GLOBALS['locINVOICES'];
  break;
case 'companies':
  if ($strForm)
    $title = $GLOBALS['locCOMPANY'];
  else
    $title = $GLOBALS['locCOMPANIES'];
  break;
case 'reports':
  if ($strForm)
    $title = $GLOBALS['locREPORT'];
  else
    $title = $GLOBALS['locREPORTS'];
  break;
case 'settings':
  if ($strForm)
  {
    switch ($strForm)
    {
    case 'base_info': $title = $GLOBALS['locBASEINFO']; break;
    case 'invoice_state': $title = $GLOBALS['locINVOICESTATE']; break;
    case 'product': $title = $GLOBALS['locPRODUCT']; break;
    case 'row_type': $title = $GLOBALS['locROWTYPE']; break;
    default: $title = $GLOBALS['locSETTINGS'];
    }
  }
  else
  {
    switch ($strList)
    {
    case 'base_info': $title = $GLOBALS['locBASES']; break;
    case 'invoice_state': $title = $GLOBALS['locINVOICESTATES']; break;
    case 'products': $title = $GLOBALS['locPRODUCTS']; break;
    case 'row_type': $title = $GLOBALS['locROWTYPES']; break;
    default: $title = $GLOBALS['locSETTINGS'];
    }
  }
  break;
case 'system':
  if ($strForm)
  {
    switch ($strForm)
    {
    case 'user': $title = $GLOBALS['locUSER']; break;
    case 'session_type': $title = $GLOBALS['locSESSIONTYPE']; break;
    default: $title = $GLOBALS['locSYSTEM'];
    }
  }
  else
  {
    switch ($strList)
    {
    case 'users': $title = $GLOBALS['locUSERS']; break;
    case 'session_types': $title = $GLOBALS['locSESSIONTYPES']; break;
    default: $title = $GLOBALS['locSYSTEM'];
    }
  }
  break;
}

echo htmlPageStart(_PAGE_TITLE_ . " - $title");

$astrMainButtons = array (
    array("name" => "invoice", "title" => "locSHOWINVOICENAVI", 'action' => '', "levels_allowed" => array(1) ),
    array("name" => "company", "title" => "locSHOWCOMPANYNAVI", 'action' => 'companies', "levels_allowed" => array(1) ),
    array("name" => "reports", "title" => "locSHOWREPORTNAVI", 'action' => 'reports', "levels_allowed" => array(1) ),
    array("name" => "settings", "title" => "locSHOWSETTINGSNAVI", 'action' => 'settings', "action" => "settings", "levels_allowed" => array(1) ),
    array("name" => "system", "title" => "locSHOWSYSTEMNAVI", 'action' => 'system', "levels_allowed" => array(99) ),
    array("name" => "help", "title" => "locSHOWHELP", 'action' => 'help.php?ses=' . $GLOBALS['sesID'] . '&amp;topic=' . ($strForm ? 'form' : 'main'), 'popup' => 1, "levels_allowed" => array(1) ),
    array("name" => "logout", "title" => "locLOGOUT", 'action' => 'logout', "levels_allowed" => array(1) )
);

?>

<body>
<div class="navi">
<?php
for( $i = 0; $i < count($astrMainButtons); $i++ ) {
    $strButton = '<a class="functionlink'; 
    if ($astrMainButtons[$i]['action'] == $strFunc || ($astrMainButtons[$i]['action'] == '' && $strFunc == 'invoices'))
      $strButton .= ' selected';
    if (isset($astrMainButtons[$i]['popup']) && $astrMainButtons[$i]['popup'])
      $strButton .= '" href="#" onClick="window.open(\'' . $astrMainButtons[$i]['action'] . '\', \'vllasku_help\', \'height=400,width=400,menubar=no,scrollbars=yes,status=no,toolbar=no\'); return false;">';
    else
      $strButton .= '" href="?ses=' . $GLOBALS['sesID'] . '&amp;func=' . $astrMainButtons[$i]['action'] . '">';
    $strButton .= $GLOBALS[$astrMainButtons[$i]['title']] . '</a>';
        
    if( in_array($GLOBALS['sesACCESSLEVEL'], $astrMainButtons[$i]['levels_allowed']) || $GLOBALS['sesACCESSLEVEL'] == 99 ) {
      echo "  $strButton\n";
    }
}

$level = 1;
if ($strList)
  ++$level;
if ($strForm) 
  ++$level;
$arrHistory = sesUpdateHistory($strSesID, $title, $_SERVER['QUERY_STRING'], $level);
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
  <?php echo $strBreadcrumbs?>
  
</div>

<?php
switch ($strFunc)
{
case '':
  createFuncMenu('invoices');
  createOpenInvoiceList();
  break;
case 'reports':
  createFuncMenu($strFunc);
  require_once 'invoice_report.php';
  if ($strForm == 'invoice')
    createInvoiceReport('report');
  break;
default:
  if ($strForm)
  {
    if ($strFunc == 'settings')
      createFuncMenu($strFunc);
    createForm($strFunc, $strForm);
  }
  else
  {
    createFuncMenu($strFunc);
    createList($strList ? $strList : $strFunc, $strFunc);
  }
}
?>


</body>
</html>
