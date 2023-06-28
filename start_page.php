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
require_once 'search.php';

/**
 * Create start page
 *
 * @return void
 */
function createStartPage()
{
    $search = new Search();
    $searches = $search->getStartPageSearches();
    foreach ($searches as $searchId) {
        $searchId = intval($searchId);
        $search = getQuickSearch($searchId);
        if (null === $search) {
            continue;
        }
        switch ($searchId) {
        case Search::SEARCH_REPEATING_INVOICES:
            $listName = 'resultlist_repeating_invoices';
            break;
        case Search::SEARCH_OPEN_INVOICES:
            $listName = 'resultlist_open_invoices';
            break;
        case Search::SEARCH_UNPAID_INVOICES:
            $listName = 'resultlist_unpaid_invoices';
            break;
        case Search::SEARCH_OPEN_OFFERS:
            $listName = 'resultlist_offers';
            break;
        default:
            $listName = "resultlist_$searchId";
            break;
        }
        createList(
            'start_page', $search['func'], $listName,
            $search['name'],
            $searchId,
            'invoice' === $search['func'],
            'invoice' === $search['func'],
            $search['func']
        );
    }
}
