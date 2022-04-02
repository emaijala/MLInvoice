<?php
/**
 * Start page
 *
 * PHP version 7
 *
 * Copyright (C) Samu Reinikainen 2004-2008
 * Copyright (C) Ere Maijala 2010-2022
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
 * Create start page
 *
 * @return void
 */
function createStartPage()
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
            'start_page', 'invoice', 'resultlist_repeating_invoices',
            Translator::translate('LabelInvoicesWithIntervalDue'),
            -1,
            true,
            false,
            'invoice'
        );
    }

    createList(
        'start_page', 'invoice', 'resultlist_open_invoices',
        Translator::translate('LabelOpenInvoices'),
        -2,
        true,
        false,
        'invoice'
    );

    createList(
        'start_page', 'invoice', 'resultlist_unpaid_invoices',
        Translator::translate('LabelUnpaidInvoices'),
        -3,
        true,
        false,
        'invoice'
    );

    createList(
        'start_page', 'offer', 'resultlist_offers',
        Translator::translate('LabelUnfinishedOffers'),
        -4,
        true,
        false,
        'offer'
    );
}
