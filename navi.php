<?php
/*******************************************************************************
MLInvoice: web-based invoicing application.
Copyright (C) 2010-2012 Ere Maijala

Portions based on:
PkLasku : web-based invoicing software.
Copyright (C) 2004-2008 Samu Reinikainen

This program is free software. See attached LICENSE.

*******************************************************************************/

/*******************************************************************************
MLInvoice: web-pohjainen laskutusohjelma.
Copyright (C) 2010-2012 Ere Maijala

Perustuu osittain sovellukseen:
PkLasku : web-pohjainen laskutusohjelmisto.
Copyright (C) 2004-2008 Samu Reinikainen

Tämä ohjelma on vapaa. Lue oheinen LICENSE.

*******************************************************************************/

require_once "sqlfuncs.php";
require_once "sessionfuncs.php";
require_once "miscfuncs.php";

function createFuncMenu($strFunc)
{
  $strHiddenTerm = '';
  $strNewButton = '';
  $strFormName = '';
  $strExtSearchTerm = "";
  $blnShowSearch = FALSE;
  $strSearchTerms = getRequest('searchterms', '');

  switch ($strFunc)
  {
  case "system":
    $astrNaviLinks =  array(
      array("href" => "list=user", "text" => $GLOBALS['locUsers'], "levels_allowed" => array(ROLE_ADMIN)),
      array("href" => "list=invoice_state", "text" => $GLOBALS['locInvoiceStates'], "levels_allowed" => array(ROLE_ADMIN)),
      array("href" => "list=row_type", "text" => $GLOBALS['locRowTypes'], "levels_allowed" => array(ROLE_ADMIN)),
      array("href" => "list=delivery_terms", "text" => $GLOBALS['locDeliveryTerms'], "levels_allowed" => array(ROLE_ADMIN)),
      array("href" => "list=delivery_method", "text" => $GLOBALS['locDeliveryMethods'], "levels_allowed" => array(ROLE_ADMIN)),
      array("href" => "list=print_template", "text" => $GLOBALS['locPrintTemplates'], "levels_allowed" => array(ROLE_ADMIN)),
      array("href" => "operation=dbdump", "text" => $GLOBALS['locBackupDatabase'], "levels_allowed" => array(ROLE_BACKUPMGR, ROLE_ADMIN)),
      array("href" => "operation=import", "text" => $GLOBALS['locImportData'], "levels_allowed" => array(ROLE_ADMIN)),
      array("href" => "operation=export", "text" => $GLOBALS['locExportData'], "levels_allowed" => array(ROLE_ADMIN))
    );
    $strNewText = '';
    $strList = getRequest('list', '');
    switch ($strList)
    {
    case 'user': $strNewText = $GLOBALS['locNewUser']; break;
    case 'session_type': $strNewText = $GLOBALS['locNewSessionType']; break;
    case 'invoice_state':
    case 'row_type':
    case 'delivery_terms':
    case 'delivery_method':
    case 'print_template':
      $strNewText = $GLOBALS['locAddNew'];
      break;
    }
    if ($strNewText)
      $strNewButton = "<br/><br/><a class=\"buttonlink new_button\" href=\"?func=system&amp;list=$strList&amp;form=$strList\">$strNewText</a>";
    break;

  case "settings":
    $astrNaviLinks = array(
      array("href" => "list=settings", "text" => $GLOBALS['locGeneralSettings'], "levels_allowed" => array(ROLE_USER, ROLE_BACKUPMGR)),
      array("href" => "list=base", "text" => $GLOBALS['locBases'], "levels_allowed" => array(ROLE_USER, ROLE_BACKUPMGR)),
      array("href" => "list=product", "text" => $GLOBALS['locProducts'], "levels_allowed" => array(ROLE_USER, ROLE_BACKUPMGR)),
    );
    $strNewText = '';
    $strList = getRequest('list', '');
    switch ($strList)
    {
    case 'base': $strNewText = $GLOBALS['locNewBase']; break;
    case 'product': $strNewText = $GLOBALS['locNewProduct']; break;
    }
    if ($strNewText)
      $strNewButton = "<br/><br/><a class=\"buttonlink\" href=\"?func=settings&amp;list=$strList&amp;form=$strList\">$strNewText</a>";
    break;

  case "reports":
    $astrNaviLinks = array(
      array("href" => "form=invoice", "text" => $GLOBALS['locInvoiceReport'], "levels_allowed" => array(ROLE_READONLY, ROLE_USER, ROLE_BACKUPMGR)),
      array("href" => "form=product", "text" => $GLOBALS['locProductReport'], "levels_allowed" => array(ROLE_READONLY, ROLE_USER, ROLE_BACKUPMGR))
    );
    break;

  case "companies":
    $blnShowSearch = TRUE;
    $strOpenForm = "company";
    $strFormName = "company";
    $strFormSwitch = "company";
    $astrNaviLinks = array();
    $strNewButton = '<a class="buttonlink" href="?func=companies&amp;form=company">' . $GLOBALS['locNewClient'] . '</a>';
    break;

  default:
    $blnShowSearch = TRUE;
    $strFormName = "invoice";
    $astrNaviLinks = array();
    if ($strFunc == 'open_invoices')
      $astrNaviLinks[] = array("href" => "index.php?func=invoices", "text" => $GLOBALS['locDisplayAllInvoices'], "levels_allowed" => array(ROLE_USER, ROLE_BACKUPMGR));
    else
      $astrNaviLinks[] = array("href" => "index.php?func=open_invoices", "text" => $GLOBALS['locDisplayOpenInvoices'], "levels_allowed" => array(ROLE_USER, ROLE_BACKUPMGR));
    if ($strFunc != 'archived_invoices') {
      $strNewButton = '<a class="buttonlink" href="?func=invoices&amp;form=invoice">' . $GLOBALS['locNewInvoice'] . '</a>';
      $astrNaviLinks[] = array("href" => "index.php?func=import_statement", "text" => $GLOBALS['locImportAccountStatement'], "levels_allowed" => array(ROLE_USER, ROLE_BACKUPMGR));
    }
    $strFunc = 'invoices';
    break;
  }

  ?>
  <script type="text/javascript">
  <!--
  function openSearchWindow(mode, event) {
      x = event.screenX;
      y = event.screenY;
      if( mode == 'ext' ) {
          strLink = 'ext_search.php?func=<?php echo $strFunc?>&form=<?php echo $strFormName?>';
          strLink = strLink + '<?php echo $strExtSearchTerm?>';
          height = '400';
          width = '600';
          windowname = 'ext';
      }
      if( mode == 'quick' ) {
          strLink = 'quick_search.php?func=<?php echo $strFunc?>';
          height = '400';
          width = '250';
          windowname = 'quicksearch';
      }

      var win = window.open(strLink, windowname, 'height='+height+',width='+width+',screenX=' + x + ',screenY=' + y + ',left=' + x + ',top=' + y + ',menubar=no,scrollbars=yes,status=no,toolbar=no');
      win.focus();

      return true;
  }
  -->
  </script>
  <form method="get" name="form_search">
  <input type="hidden" name="func" value="<?php echo $strFunc?>">
  <div class="function_navi">
<?php
  if ($blnShowSearch)
  {
?>
    <input type="hidden" name="changed" value="0">
    <?php echo $strHiddenTerm?>
    <input type="text" class="small" name="searchterms" value="<?php echo gpcAddSlashes($strSearchTerms)?>" title="<?php echo $GLOBALS['locEnterTermsHint']?>">
    <a class="actionlink" href="#" onClick="self.document.forms[0].submit();"><?php echo $GLOBALS['locSearch']?></a>
<?php
  }
  foreach ($astrNaviLinks as $link)
  {
    if (sesAccessLevel($link["levels_allowed"]) || sesAdminAccess())
    {
      if (strchr($link['href'], '?') === FALSE)
        $strHref = "?func=$strFunc&amp;" . $link['href'];
      else
        $strHref = $link['href'];
      $class = (strstr($_SERVER['QUERY_STRING'], $link['href'])) ? ' ui-state-highlight' : '';
?>
    <a class="buttonlink<?php echo $class?>" href="<?php echo $strHref?>"><?php echo $link['text']?></a>
<?php
    }
  }
  if ($blnShowSearch)
  {
?>
    <a class="buttonlink" href="#" onClick="openSearchWindow('ext', event); return false;"><?php echo $GLOBALS['locExtSearch']?></a>
    <a class="buttonlink" href="#" onClick="openSearchWindow('quick', event); return false;"><?php echo $GLOBALS['locQuickSearch']?></a>
<?php
  }
  if (sesWriteAccess())
  {
    echo "&nbsp; &nbsp; $strNewButton\n";
  }
?>
  </div>
  </form>
<?php
}
