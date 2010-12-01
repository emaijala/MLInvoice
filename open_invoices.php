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

require_once "htmlfuncs.php";
require_once "sqlfuncs.php";
require_once "miscfuncs.php";
require_once "datefuncs.php";
require_once "localize.php";

function createOpenInvoiceList()
{
  $strQuery = 
      "SELECT id FROM {prefix}invoice ".
      "WHERE state_id = 1 ".
      "ORDER BY invoice_date, name";
  $intRes = mysql_query_check($strQuery);
  $intNumRows = mysql_num_rows($intRes);
  if( $intNumRows ) {
    $astrKeyValues = array();
    for( $i = 0; $i < $intNumRows; $i++ ) {
        $astrKeyValues[$i] = mysql_result($intRes, $i, 0);
    }
    require_once 'list.php';
    createHtmlList('invoices', 'invoices', $astrKeyValues, $intNumRows, $GLOBALS['locLABELOPENINVOICES']);
  }
  else {
    echo '<b>' . $GLOBALS['locNOOPENINVOICES'] . "</b>\n";
  }

  $strQuery = 
      "SELECT id FROM {prefix}invoice ".
      "WHERE state_id = 2 or state_id = 5 or state_id = 6 ".
      "ORDER BY invoice_date, name";
  $intRes = mysql_query_check($strQuery);
  $intNumRows = mysql_num_rows($intRes);
  if( $intNumRows ) {
    $astrKeyValues = array();
    for( $i = 0; $i < $intNumRows; $i++ ) {
        $astrKeyValues[$i] = mysql_result($intRes, $i, 0);
    }
    require_once 'list.php';
    createHtmlList('invoices', 'invoices', $astrKeyValues, $intNumRows, $GLOBALS['locLABELUNPAIDINVOICES']);
  }
  else {
    echo '<b>' . $GLOBALS['locLABELUNPAIDINVOICES'] . ":</b><br><br>\n";
    echo '<b>' . $GLOBALS['locNOUNPAIDINVOICES'] . "</b>\n";
  }
}
