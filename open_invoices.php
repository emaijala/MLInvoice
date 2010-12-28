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
require_once 'list.php';
require_once 'settings.php';

function createOpenInvoiceList()
{
  $arrParams = array();

  $strQuery = 
    "SELECT id FROM {prefix}invoice " .
    "WHERE state_id=1 AND archived=0 " . (getSetting('show_deleted_records') ? '' : 'AND deleted=0 ');
    "ORDER BY invoice_date, name";
  createHtmlList('open_invoices', 'invoices', $strQuery, $arrParams, $GLOBALS['locLABELOPENINVOICES'], $GLOBALS['locNOOPENINVOICES'], 'resultlist_open_invoices');

  $strQuery = 
    "SELECT id FROM {prefix}invoice " .
    "WHERE state_id IN (2, 5, 6, 7) AND archived=0 " . (getSetting('show_deleted_records') ? '' : 'AND deleted=0 ');
    "ORDER BY invoice_date, name";
  createHtmlList('open_invoices', 'invoices', $strQuery, $arrParams, $GLOBALS['locLABELUNPAIDINVOICES'], $GLOBALS['locNOUNPAIDINVOICES'], 'resultlist_unpaid_invoices');
}
