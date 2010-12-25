<?php
/*******************************************************************************
VLLasku: web-based invoicing application.
Copyright (C) 2010 Ere Maijala

Portions based on:
PkLasku : web-based invoicing software.
Copyright (C) 2004-2008 Samu Reinikainen

This program is free software. See attached LICENSE.

*******************************************************************************/

/*******************************************************************************
VLLasku: web-pohjainen laskutusohjelma.
Copyright (C) 2010 Ere Maijala

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
  $strLabel = '';
  $strNewButton = '';
  $strFormName = '';
  $strExtSearchTerm = "";
  $blnShowSearch = FALSE;   
  $strSearchTerms = getRequest('searchterms', '');
  
  switch ( $strFunc ) {
      
  case "system" :
      $strLabel = $GLOBALS['locSHOWSYSTEMNAVI'];
      $astrNaviLinks = 
      array( 
          array("href" => "list=user", "text" => $GLOBALS['locUSERS'], "levels_allowed" => array(99)),
          array("href" => "list=session_type", "text" => $GLOBALS['locSESSIONTYPES'], "levels_allowed" => array(99)),
          array("href" => "operation=dbdump", "text" => $GLOBALS['locBACKUPDATABASE'], "levels_allowed" => array(99))
      );
      $strNewText = '';
      $strList = getRequest('list', '');
      switch ($strList)
      {
      case 'user': $strNewText = $GLOBALS['locNEWUSER']; break;
      case 'session_type': $strNewText = $GLOBALS['locNEWSESSIONTYPE']; break;
      }
      if ($strNewText)
        $strNewButton = "<a class=\"actionlink\" href=\"?func=system&amp;list=$strList&amp;form=$strList\">$strNewText</a>";
  break;
  case "settings" :
      $strLabel = $GLOBALS['locSHOWSETTINGSNAVI'];
      $astrNaviLinks = 
      array( 
          array("href" => "list=settings", "text" => $GLOBALS['locGeneralSettings'], "levels_allowed" => array(1)),
          array("href" => "list=base_info", "text" => $GLOBALS['locBASES'], "levels_allowed" => array(1)),
          array("href" => "list=invoice_state", "text" => $GLOBALS['locINVOICESTATES'], "levels_allowed" => array(1)),
          array("href" => "list=product", "text" => $GLOBALS['locPRODUCTS'], "levels_allowed" => array(1)),
          array("href" => "list=row_type", "text" => $GLOBALS['locROWTYPES'], "levels_allowed" => array(1)),
      );
      $strNewText = '';
      $strList = getRequest('list', '');
      switch ($strList)
      {
      case 'base_info': $strNewText = $GLOBALS['locNEWBASE']; break;
      case 'invoice_state': $strNewText = $GLOBALS['locNEWINVOICESTATE']; break;
      case 'product': $strNewText = $GLOBALS['locNEWPRODUCT']; break;
      case 'row_type': $strNewText = $GLOBALS['locNEWROWTYPE']; break;
      }
      if ($strNewText)
        $strNewButton = "<a class=\"actionlink\" href=\"?func=settings&amp;list=$strList&amp;form=$strList\">$strNewText</a>";
  break;
  
  case "reports" :
      $strLabel = $GLOBALS['locSHOWREPORTNAVI'];
      $astrNaviLinks = 
      array( 
          array("href" => "form=invoice", "text" => $GLOBALS['locINVOICEREPORT'], "levels_allowed" => array(1)),
          array("href" => "form=product", "text" => $GLOBALS['locPRODUCTREPORT'], "levels_allowed" => array(1))
      );
  break;
  
  case "companies":
      $blnShowSearch = TRUE;
      $strOpenForm = "company";
      $strFormName = "company";
      $strFormSwitch = "company";
      $astrNaviLinks = array();
      
      $strNewButton = '<a class="actionlink" href="?func=companies&amp;form=company">' . $GLOBALS['locNEWCOMPANY'] . '</a>';
  break;
  default :
      $blnShowSearch = TRUE;
      $strFormName = "invoice";
      $astrNaviLinks = array();
      if ($strFunc == 'invoices')
        $astrNaviLinks[] = array("href" => "index.php?func=open_invoices", "text" => $GLOBALS['locDISPLAYOPENINVOICES'], "levels_allowed" => array(1));
      else
        $astrNaviLinks[] = array("href" => "index.php?func=invoices", "text" => $GLOBALS['locDISPLAYALLINVOICES'], "levels_allowed" => array(1));
      if ($strFunc != 'archived_invoices')  
        $strNewButton = '<a class="actionlink" href="?func=invoices&amp;form=invoice">' . $GLOBALS['locNEWINVOICE'] . '</a>';
      $strFunc = 'invoices';
  break;
  }
  if ($strNewButton)
    $strNewButton = "    $strNewButton\n";
  
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
          width = '500';
          windowname = 'ext';
      }
      if( mode == 'quick' ) {
          strLink = 'quick_search.php';
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
  <form method="get" action="" name="form_search">
  <input type="hidden" name="func" value="<?php echo $strFunc?>">
  <div class="function_navi">
    <b><?php echo $strLabel?></b>
<?php
  if( $blnShowSearch ) {
?>
    <input type="hidden" name="changed" value="0">
    <?php echo $strHiddenTerm?>
    <input type="text" class="small" name="searchterms" value="<?php echo gpcAddSlashes($strSearchTerms)?>" title="<?php echo $GLOBALS['locENTERTERMS']?>">
    <a class="actionlink" href="#" onClick="self.document.forms[0].submit();"><?php echo $GLOBALS['locSEARCH']?></a>
<?php
  }
  for( $i = 0; $i < count($astrNaviLinks); $i++ ) {
    if( in_array($_SESSION['sesACCESSLEVEL'], $astrNaviLinks[$i]["levels_allowed"]) || $_SESSION['sesACCESSLEVEL'] == 99 ) {
      if (strchr($astrNaviLinks[$i]['href'], '?') === FALSE)
        $strHref = "?func=$strFunc&amp;" . $astrNaviLinks[$i]['href'];
      else
        $strHref = $astrNaviLinks[$i]['href'];
?>    
    <a class="buttonlink" href="<?php echo $strHref?>"><?php echo $astrNaviLinks[$i]['text']?></a>
<?php            
    }
  }
  if( $blnShowSearch ) {
?>
    <a class="buttonlink" href="#" onClick="openSearchWindow('ext',event); return false;"><?php echo $GLOBALS['locEXTSEARCH']?></a>
    <a class="buttonlink" href="#" onClick="openSearchWindow('quick',event); return false;"><?php echo $GLOBALS['locQUICKSEARCH']?></a>
<?php
  }
  echo $strNewButton;
?>
  </div>
  </form>
<?php
}
