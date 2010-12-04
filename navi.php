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
          array("href" => "list=users", "text" => $GLOBALS['locUSERS'], "levels_allowed" => array(99)),
          array("href" => "list=session_types", "text" => $GLOBALS['locSESSIONTYPES'], "levels_allowed" => array(99))
      );
  break;
  case "settings" :
      $strLabel = $GLOBALS['locSHOWSETTINGSNAVI'];
      $astrNaviLinks = 
      array( 
          array("href" => "list=base_info", "text" => $GLOBALS['locBASEINFO'], "levels_allowed" => array(1)),
          array("href" => "list=invoice_state", "text" => $GLOBALS['locINVOICESTATES'], "levels_allowed" => array(1)),
          array("href" => "list=products", "text" => $GLOBALS['locPRODUCTS'], "levels_allowed" => array(1)),
          array("href" => "list=row_type", "text" => $GLOBALS['locROWTYPES'], "levels_allowed" => array(1)),
      );
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
      $astrNaviLinks = 
      array( 
          /*array("href" => "select_invoice.php?ses=".$GLOBALS['sesID']. "&type=payment", "text" => $GLOBALS['locADDPAYMENT'], "target" => "f_list", "levels_allowed" => array(1))*/
      );
      
      $strNewButton = '<a class="actionlink" href="?ses=' . $GLOBALS['sesID'] . '&amp;func=companies&amp;form=company&amp;new=1">Uusi asiakas</a>';
  break;
  case "products":
      $blnShowSearch = TRUE;
      $strOpenForm = "product";
      $strFormName = "product";
      $strFormSwitch = "product";
      $astrNaviLinks = 
      array( 
      );
      
      $strNewButton = '<a class="actionlink" href="?ses=' . $GLOBALS['sesID'] . '&amp;func=settings&amp;list=products&amp;form=product&amp;new=1">Uusi tuote</a>';
  break;
  default :
      $blnShowSearch = TRUE;
      $strFormName = "invoice";
      $astrNaviLinks = 
      array( 
          /*array("href" => "select_invoice.php?ses=".$GLOBALS['sesID']. "&type=payment", "text" => $GLOBALS['locADDPAYMENT'], "target" => "f_list", "levels_allowed" => array(1))*/
      );
      $strNewButton = '<a class="actionlink" href="?ses=' . $GLOBALS['sesID'] . '&amp;func=invoices&amp;form=invoice&amp;new=1">Uusi lasku</a>';
      
  break;
  }
  
  ?>
  <script type="text/javascript">
  <!--
  function openHelpWindow(event) {
      x = event.screenX;
      y = event.screenY;
      strLink = 'help.php?ses=<?php echo $GLOBALS['sesID']?>&amp;topic=search'; 
      var win = window.open(strLink, '_blank', 'height=400,width=400,screenX=' + x + ',screenY=' + y + ',left=' + x + ',top=' + y + ',menubar=no,scrollbars=yes,status=no,toolbar=no');
      win.focus();
      
      return true;
  }
  function openSearchWindow(mode, event) {
      x = event.screenX;
      y = event.screenY;
      if( mode == 'ext' ) {
          strLink = 'ext_search.php?ses=<?php echo $GLOBALS['sesID']?>&func=<?php echo $strFunc?>&form=<?php echo $strFormName?>';
          strLink = strLink + '<?php echo $strExtSearchTerm?>';
          height = '400';
          width = '500';
          windowname = 'ext';
      }
      if( mode == 'quick' ) {
          strLink = 'quick_search.php?ses=<?php echo $GLOBALS['sesID']?>';
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
  <input type="hidden" name="ses" value="<?php echo $GLOBALS['sesID']?>">
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
  <?php echo $strNewButton?>
  <a class="buttonlink" href="#" onClick="openSearchWindow('ext',event); return false;"><?php echo $GLOBALS['locEXTSEARCH']?></a>
  <a class="buttonlink" href="#" onClick="openSearchWindow('quick',event); return false;"><?php echo $GLOBALS['locQUICKSEARCH']?></a>
  <a class="buttonlink" href="#" onClick="openHelpWindow(event); return false;"><?php echo $GLOBALS['locHELP']?></a>
  <?php
  }
  
  for( $i = 0; $i < count($astrNaviLinks); $i++ ) {
      if( in_array($GLOBALS['sesACCESSLEVEL'], $astrNaviLinks[$i]["levels_allowed"]) || $GLOBALS['sesACCESSLEVEL'] == 99 ) {
  ?>    
  <a class="buttonlink" href="?ses=<?php echo $GLOBALS['sesID']?>&amp;func=<?php echo $strFunc?>&<?php echo $astrNaviLinks[$i]['href']?>"><?php echo $astrNaviLinks[$i]['text']?></a>
  <?php            
      }
  }
  ?>
  </form>
  </div>
  <?php
}
