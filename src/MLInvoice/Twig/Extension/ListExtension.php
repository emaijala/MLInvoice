<?php
/**
 * Twig List Extension.
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
use Psr\Http\Message\ServerRequestInterface;
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
class ListExtension extends AbstractExtension
{
    /**
     * Constructor
     */
    public function __construct(
        protected ListService $listService,
        protected PrintTemplateRepository $printTemplateRepository,
        protected CustomPriceRepository $customPriceRepository,
        protected InvoicePrinterFactory $invoicePrinterFactory,
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
            new TwigFunction('list_config', $this->createListConfig(...)),
            new TwigFunction('search_list_value_for', $this->getSearchListValueFor(...)),
        ];
    }

    /**
     * Create list configuration
     *
     * @param string $listType         List type
     * @param string $listId           List ID
     * @param array  $searchParams     Search params
     * @param string $titleOverride    Default title override
     * @param int    $searchId         Saved search id
     * @param bool   $highlightOverdue Whether to highlight overdue rows
     * @param string $printType        Print template type for printing multiple
     * @param ?int   $companyId        Selected company ID
     *
     * @return array
     */
    function createListConfig(string $listType, string $listId, array $searchParams,
        string $titleOverride = '', ?int $searchId = null, bool $highlightOverdue = false,
        string $printType = '', ?int $companyId = null
    ) {
        $listConfig = $this->listService->getListConfig($listType);
        if (!$listConfig) {
            return [];
        }
        $listConfig['fields'] = array_filter(
            $listConfig['fields'],
            fn ($field) => 'HIDDEN' !== $field
        );

        $printTemplates = [];
        if ($printType) {
            foreach ($this->printTemplateRepository->findActiveByType($printType) as $candidate) {
                $printer = $this->invoicePrinterFactory->getForTemplate($candidate->getFilename());
                if (null === $printer) {
                    continue;
                }
                $uses = class_uses($printer);
                if (in_array('InvoicePrinterEmailTrait', $uses)
                    || $printer instanceof InvoicePrinterXslt
                    || $printer instanceof InvoicePrinterBlank
                ) {
                    continue;
                }
                $printTemplates[] = $candidate;
            }
        }

        if (!$listId) {
            $listId = "list_$listType";
        }
        $listId .= '_3';

        $listTitle = $titleOverride ? $titleOverride : $listConfig['title'];

        $params = [
            'listtype' => $listType,
            'table' => $listType,
            'tableid' => $listId,
            'searchId' => $searchId,
        ];
        if ($highlightOverdue) {
            $params['highlight_overdue'] = 1;
        }
        $customPriceSettings = null;
        if (null !== $companyId) {
            $params['company'] = $companyId;
            $customPriceSettings = $this->customPriceRepository->findOneByCompanyId($companyId);
        }
        $params['query'] = $searchParams;

        // Adjust fields for the template:
        array_map(
            function ($field) use ($customPriceSettings) {
                if (!empty($field['width'])) {
                    $field['width'] .= 'px';
                }
                $field['sortable'] = ($field['sort'] ?? true) ? 'true' : 'false';
                $field['visible'] = ($field['visible'] ?? true) ? 'true' : 'false';
                if (($customPriceSettings['valid'] ?? false) && 'custom_price' === $field['name']) {
                    $field['class'] = 'editable';
                } elseif ('i.due_date' === $field['name']) {
                    $field['class'] = 'due-date';
                } elseif ('i.next_interval_date' === $field['name']) {
                    $field['class'] = 'next-interval-date';
                } else {
                    $field['class'] = '';
                }
            },
            $listConfig['fields']
        );

        $result = [
            'list_id' => $listId,
            'list_config' => $listConfig,
            'print_templates' => $printTemplates,
            'list_title' => $listTitle,
            'params' => $params,
            'custom_price_settings' => $customPriceSettings,
        ];
        return $result;
    }

    /**
     * Get the value of a search list selection.
     *
     * @param string  $listId List ID
     * @param ?string $key  Selection
     *
     * @return string
     */
    protected function getSearchListValueFor(string $listId, ?string $key): string
    {
        $result = $this->listService->createJSONSelectList($listId, 0, 1, '', '', '', $key);
        return $result['records'][0]['text'] ?? '';
    }
}
