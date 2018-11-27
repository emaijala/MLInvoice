<?php
/**
 * Open invoices etc.
 *
 * PHP version 5
 *
 * Copyright (C) 2004-2008 Samu Reinikainen
 * Copyright (C) 2010-2018 Ere Maijala
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category MLInvoice
 * @package  MLInvoice\Base
 * @author   Ere Maijala <ere@labs.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://labs.fi/mlinvoice.eng.php
 */
require_once 'htmlfuncs.php';
require_once 'sqlfuncs.php';
require_once 'miscfuncs.php';
require_once 'datefuncs.php';
require_once 'translator.php';
require_once 'list.php';
require_once 'settings.php';

/**
 * Create open invoice list
 *
 * @return void
 */
function createOpenInvoiceList()
{
    $currentDate = date('Ymd');

    $res = dbQueryCheck(
        "select count(*) as cnt from {prefix}invoice i where i.deleted = 0"
        . " AND i.interval_type > 0 AND i.next_interval_date <= $currentDate"
        . " AND i.archived = 0"
    );
    $row = mysqli_fetch_assoc($res);
    if ($row['cnt'] > 0) {
        createList(
            'open_invoices', 'invoice', 'resultlist_repeating_invoices',
            Translator::translate('LabelInvoicesWithIntervalDue'),
            "i.interval_type > 0 AND i.next_interval_date <= $currentDate"
                . " AND i.archived = 0",
            true
        );
    }

    $open = '';
    $res = dbQueryCheck(
        'SELECT id FROM {prefix}invoice_state WHERE invoice_open=1'
        . ' AND invoice_offer=0'
    );
    while ($id = dbFetchValue($res)) {
        if ($open) {
            $open .= ', ';
        }
        $open .= $id;
    }

    $unpaid = '';
    $res = dbQueryCheck(
        'SELECT id FROM {prefix}invoice_state WHERE invoice_open=0'
        . ' AND invoice_unpaid=1 AND invoice_offer=0'
    );
    while ($id = dbFetchValue($res)) {
        if ($unpaid) {
            $unpaid .= ', ';
        }
        $unpaid .= $id;
    }

    $openOffers = '';
    $res = dbQueryCheck(
        'SELECT id FROM {prefix}invoice_state WHERE invoice_open=1'
        . ' AND invoice_offer=1'
    );
    while ($id = dbFetchValue($res)) {
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
