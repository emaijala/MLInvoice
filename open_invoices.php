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

require_once "htmlfuncs.php";
require_once "sqlfuncs.php";
require_once "miscfuncs.php";
require_once "datefuncs.php";
require_once "localize.php";
require_once 'list.php';
require_once 'settings.php';

function createOpenInvoiceList()
{
  $currentDate = date("Ymd");

  createList('open_invoices', 'invoice', 'resultlist_repeating_invoices', $GLOBALS['locLabelInvoicesWithIntervalDue'],
    "i.interval_type > 0 AND i.next_interval_date <= $currentDate AND i.archived = 0", true);

  createList('open_invoices', 'invoice', 'resultlist_open_invoices', $GLOBALS['locLabelOpenInvoices'],
    'i.state_id=1 AND i.archived=0', true);

  createList('open_invoices', 'invoice', 'resultlist_unpaid_invoices', $GLOBALS['locLabelUnpaidInvoices'],
    'i.state_id IN (2, 5, 6, 7) AND i.archived=0', true, true);
}
