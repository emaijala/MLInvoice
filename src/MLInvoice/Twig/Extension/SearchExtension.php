<?php
/**
 * Twig Search Extension.
 *
 * PHP version 8
 *
 * Copyright (C) Ere Maijala 2010-2026.
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
 * @package  MLInvoice\Twig
 * @author   Ere Maijala <ere@labs.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://labs.fi/mlinvoice.eng.php
 */

declare(strict_types=1);

namespace MLInvoice\Twig\Extension;

use MLInvoice\Database\Repository\CustomPriceRepository;
use MLInvoice\Database\Repository\PrintTemplateRepository;
use MLInvoice\InvoicePrinter\InvoicePrinterBlank;
use MLInvoice\InvoicePrinter\InvoicePrinterFactory;
use MLInvoice\InvoicePrinter\InvoicePrinterXslt;
use MLInvoice\List\ListService;
use MLInvoice\Search\SearchService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Twig List Extension.
 *
 * @category MLInvoice
 * @package  MLInvoice\Twig
 * @author   Ere Maijala <ere@labs.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://labs.fi/mlinvoice.eng.php
 */
class SearchExtension extends AbstractExtension
{
    /**
     * Constructor
     */
    public function __construct(
        protected SearchService $searchService,
    ) {
    }

    /**
     * Get Twig functions.
     *
     * @return array
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('get_start_page_searches', $this->getStartPageSearches(...)),
        ];
    }

    /**
     * Get searches active on the start page
     *
     * @param mixed $default True to get the default set for all users, false to get
     *                       only the user-specific set, null to get user-specific
     *                       or default if no user-specific set exists.
     *
     * @return array
     */
    public function getStartPageSearches($default = null): array
    {
        $searchIds = $this->searchService->getStartPageSearchIds($default);
        $result = [];
        foreach ($searchIds as $searchId) {
            $searchId = intval($searchId);
            $search = $this->searchService->getQuickSearch($searchId);
            if (null === $search) {
                continue;
            }
            switch ($searchId) {
            case SearchService::SEARCH_REPEATING_INVOICES:
                $listName = 'resultlist_repeating_invoices';
                break;
            case SearchService::SEARCH_OPEN_INVOICES:
                $listName = 'resultlist_open_invoices';
                break;
            case SearchService::SEARCH_UNPAID_INVOICES:
                $listName = 'resultlist_unpaid_invoices';
                break;
            case SearchService::SEARCH_OPEN_OFFERS:
                $listName = 'resultlist_offers';
                break;
            default:
                $listName = "resultlist_$searchId";
                break;
            }
            $result[] = [
                'list_id' => $listName,
                'search' => $search,
            ];
        }
        return $result;
    }
}
