<?php
/*******************************************************************************
 MLInvoice: web-based invoicing application.
 Copyright (C) 2010-2017 Ere Maijala

 Portions based on:
 PkLasku : web-based invoicing software.
 Copyright (C) 2004-2008 Samu Reinikainen

 This program is free software. See attached LICENSE.

 *******************************************************************************/

/*******************************************************************************
 MLInvoice: web-pohjainen laskutusohjelma.
 Copyright (C) 2010-2017 Ere Maijala

 Perustuu osittain sovellukseen:
 PkLasku : web-pohjainen laskutusohjelmisto.
 Copyright (C) 2004-2008 Samu Reinikainen

 Tämä ohjelma on vapaa. Lue oheinen LICENSE.

 *******************************************************************************/
require_once 'htmlfuncs.php';
require_once 'sqlfuncs.php';
require_once 'miscfuncs.php';
require_once 'datefuncs.php';
require_once 'translator.php';
require_once 'list.php';
require_once 'settings.php';

function createOpenInvoiceList()
{
    $currentDate = date('Ymd');

    $res = mysqli_query_check(
        "select count(*) as cnt from {prefix}invoice i where i.deleted = 0 AND i.interval_type > 0 AND i.next_interval_date <= $currentDate AND i.archived = 0"
    );
    $row = mysqli_fetch_assoc($res);
    if ($row['cnt'] > 0) {
        createList(
            'open_invoices', 'invoice', 'resultlist_repeating_invoices',
            Translator::translate('LabelInvoicesWithIntervalDue'),
            "i.interval_type > 0 AND i.next_interval_date <= $currentDate AND i.archived = 0",
            true
        );
    }

    $open = '';
    $res = mysqli_query_check(
        'SELECT id FROM {prefix}invoice_state WHERE invoice_open=1 AND invoice_offer=0'
    );
    while ($id = mysqli_fetch_value($res)) {
        if ($open) {
            $open .= ', ';
        }
        $open .= $id;
    }

    $unpaid = '';
    $res = mysqli_query_check(
        'SELECT id FROM {prefix}invoice_state WHERE invoice_open=0 AND invoice_unpaid=1 AND invoice_offer=0'
    );
    while ($id = mysqli_fetch_value($res)) {
        if ($unpaid) {
            $unpaid .= ', ';
        }
        $unpaid .= $id;
    }

    $openOffers = '';
    $res = mysqli_query_check(
        'SELECT id FROM {prefix}invoice_state WHERE invoice_open=1 AND invoice_offer=1'
    );
    while ($id = mysqli_fetch_value($res)) {
        if ($openOffers) {
            $openOffers .= ', ';
        }
        $openOffers .= $id;
    }

    if ($open) {
        createList(
            'open_invoices', 'invoice', 'resultlist_open_invoices',
            Translator::translate('LabelOpenInvoices'),
            "i.state_id IN ($open) AND i.archived=0", true
        );
    }

    if ($unpaid) {
        createList(
            'open_invoices', 'invoice', 'resultlist_unpaid_invoices',
            Translator::translate('LabelUnpaidInvoices'),
            "i.state_id IN ($unpaid) AND i.archived=0", true, true
        );
    }

    if ($openOffers) {
        createList(
            'open_invoices', 'offer', 'resultlist_offers',
            Translator::translate('LabelUnfinishedOffers'),
            "i.state_id IN ($openOffers) AND i.archived=0", true
        );
    }
}
