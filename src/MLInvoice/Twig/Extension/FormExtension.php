<?php
/**
 * Twig Form Extension.
 *
 * PHP version 8
 *
 * Copyright (C) Ere Maijala 2026.
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

use Closure;
use DI\Attribute\Inject;
use MLInvoice\Database\Repository\BaseRepository;
use MLInvoice\Database\Repository\CustomPriceRepository;
use MLInvoice\Database\Repository\InvoiceAttachmentRepository;
use MLInvoice\Database\Repository\PrintTemplateRepository;
use MLInvoice\Form\FormService;
use MLInvoice\I18n\Translator;
use MLInvoice\InvoicePrinter\InvoicePrinterBlank;
use MLInvoice\InvoicePrinter\InvoicePrinterFactory;
use MLInvoice\InvoicePrinter\InvoicePrinterXslt;
use MLInvoice\List\ListService;
use MLInvoice\Search\SearchService;
use MLInvoice\Session\Memory;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Interfaces\RouteParserInterface;
use Slim\Interfaces\RouteResolverInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Twig Form Extension.
 *
 * @category MLInvoice
 * @package  MLInvoice\Twig
 * @author   Ere Maijala <ere@labs.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://labs.fi/mlinvoice.eng.php
 */
class FormExtension extends AbstractExtension
{
    /**
     * Constructor
     *
     * @param Closure $csrfFactory CSRF guard factory callback
     */
    public function __construct(
        protected Translator $translator,
        protected FormService $formService,
        protected PrintTemplateRepository $printTemplateRepository,
        protected CustomPriceRepository $customPriceRepository,
        protected InvoicePrinterFactory $invoicePrinterFactory,
        #[Inject('CsrfGuardFactory')] protected Closure $csrfFactory,
        protected RouteParserInterface $routeParser,
        protected Memory $memory,
        protected BaseRepository $baseRepository,
        protected InvoiceAttachmentRepository $invoiceAttachmentRepository,
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
            new TwigFunction('form_config', $this->createFormConfig(...)),
            new TwigFunction('getCompanyInvoiceSearchLinks', $this->getCompanyInvoiceSearchLinks(...)),
            new TwigFunction('getListNavigationLinks', $this->getListNavigationLinks(...)),
            new TwigFunction('getBaseList', $this->baseRepository->findAllNonDeletedActive()),
            new TwigFunction('getInvoiceAttachmentCount', $this->invoiceAttachmentRepository->countByInvoiceId(...)),
            new TwigFunction('fileSizeToHumanReadable', $this->fileSizeToHumanReadable(...)),
            new TwigFunction('getMaxUploadSize', $this->getMaxUploadSize(...)),
        ];
    }

    /**
     * Create form configuration
     *
     * @param string $form     Form name
     * @param ?int   $id       Record ID (if any)
     * @param ?int   $parentId Parent record ID (if any)
     * @param RequestInterface $request Request
     *
     * @return array
     */
    protected function createFormConfig(
        string $form,
        ?int $id,
        ?int $parentId,
        ServerRequestInterface $request,
    ): array {
        $formConfig = $this->formService->getFormConfig($form, $id, $request, false, $parentId);
        return $formConfig;
    }

    /**
     * Get invoice search links for a company
     *
     * @return array
     */
    protected function getCompanyInvoiceSearchLinks(int $companyId): array
    {
        $urlParamsBase = [
            's_type1' => 'company_id',
            's_field1' => $companyId,
        ];
        $invoiceLinks = [
            [
                'name' => 'NonArchivedInvoices',
                'url' => $this->routeParser->relativeUrlFor(
                    'search-invoices-results',
                    queryParams: $urlParamsBase + ['search_id' => SearchService::SEARCH_NON_ARCHIVED_INVOICES]
                ),
            ],
            [
                'name' => 'ArchivedInvoices',
                'url' => $this->routeParser->relativeUrlFor(
                    'search-invoices-results',
                    queryParams: $urlParamsBase + ['search_id' => SearchService::SEARCH_ARCHIVED_INVOICES]
                ),
            ],
            [
                'name' => 'NonArchivedOffers',
                'url' => $this->routeParser->relativeUrlFor(
                    'search-invoices-results',
                    queryParams: $urlParamsBase + ['search_id' => SearchService::SEARCH_NON_ARCHIVED_OFFERS]
                ),
            ],
            [
                'name' => 'ArchivedOffers',
                'url' => $this->routeParser->relativeUrlFor(
                    'search-invoices-results',
                    queryParams: $urlParamsBase + ['search_id' => SearchService::SEARCH_ARCHIVED_OFFERS]
                ),
            ],
        ];
        return $invoiceLinks;
    }

    protected function getListNavigationLinks(?int $listId, ?int $currentId): array
    {
        $listInfo = $this->memory->get("{$listId}_info");
        if (null === $listInfo) {
            // No list info for the current list
            return [];
        }
        $pos = array_search($currentId, $listInfo['ids']);
        if (false === $pos) {
            // We've lost track of our position
            return [];
        }
        $previous = null;
        $next = null;
        if (0 === $pos) {
            // If we're not at the beginning, fetch previous page and try again
            if (0 != $listInfo['startRow']) {
                $startRow = max([$listInfo['startRow'] - $listInfo['rowCount'], 0]);
                $this->augmentListInfo($listId, $listInfo, $startRow, $listInfo['rowCount']);
                return $this->getListNavigationLinks($listId, $currentId);
            }
        } else {
            $previous = $listInfo['ids'][$pos - 1];
        }
        if ($pos === count($listInfo['ids']) - 1) {
            // If we're not at the end, fetch next page and try again
            if ($listInfo['startRow'] + $pos < $listInfo['recordCount'] - 1) {
                $startRow = min(
                    [$listInfo['startRow'] + $listInfo['rowCount'],
                    $listInfo['recordCount'] - 1]
                );
                $this->augmentListInfo($listId, $listInfo, $startRow, $listInfo['rowCount']);
                return $this->getListNavigationLinks($listId, $currentId);
            }
        } else {
            $next = $listInfo['ids'][$pos + 1];
        }
        return [
            [
                'label' => 'Previous',
                'id' => $previous,
            ],
            [
                'label' => 'Next',
                'id' => $next,
            ],
        ];
    }

    /**
     * Add data to list info memory (record to record navigation)
     *
     * @param string $listId   List name
     * @param array  $listInfo List info
     * @param int    $startRow Start row
     * @param int    $rowCount Row count
     *
     * @return void
     */
    protected function augmentListInfo($listId, $listInfo, $startRow, $rowCount)
    {
        if (!($params = $listInfo['createParams'] ?? null)) {
            return;
        }
        $queries = createListQuery(
            $params['func'],
            $params['list'],
            $startRow,
            $rowCount,
            $params['sort'],
            $params['filter'],
            $params['query'],
            $params['searchId']
        );

        $listConfig = getListConfig($params['list']);
        $filteredQuery = $queries['filteredQuery'];
        $prefix = _DB_PREFIX_ . '_';
        $idField = $listConfig['primaryKey'];

        $filteredQuery->select($idField)
            ->from($prefix . $listConfig['table'], $listConfig['alias']);
        if ($startRow >= 0 && $rowCount >= 0) {
            $filteredQuery->setFirstResult($startRow)->setMaxResults($rowCount);
        }

        $ids = [];
        $result = $filteredQuery->executeQuery();
        foreach ($result->fetchAllAssociative() as $row) {
            $ids[] = array_shift($row);
        }

        if ($listInfo['startRow'] > $startRow) {
            $listInfo['startRow'] = $startRow;
            $listInfo['ids'] = array_merge($ids, $listInfo['ids']);
        } else {
            $listInfo['ids'] = array_merge($listInfo['ids'], $ids);
        }
        $this->memory->set("{$listId}_info", $listInfo);
    }

    /**
     * Convert a file size to a human-readable value
     *
     * @param int $value File size
     *
     * @return string
     */
    protected function fileSizeToHumanReadable(int $value): string
    {
        $suffixes = [
            'SizeB',
            'SizeKB',
            'SizeMB',
            'SizeGB',
            'SizeTB',
            'SizePB'
        ];

        $idx = 0;
        while ($idx < count($suffixes) - 1 && $value / 1024 > 0.9) {
            $value /= 1024;
            ++$idx;
        }
        return miscRound2Decim($value, 0) . ' ' . $this->translator->translate($suffixes[$idx]);
    }

    /**
     * Get maximum file upload size
     *
     * @return int
     */
    protected function getMaxUploadSize(): int
    {
        return min(
            phpIniValueToInteger(ini_get('post_max_size')),
            phpIniValueToInteger(ini_get('upload_max_filesize'))
        );
    }

    /**
     * Convert a PHP ini value to integer
     *
     * @param string $value Value
     *
     * @return int
     */
    protected function phpIniValueToInteger(string $value): int
    {
        $unit = strtoupper(substr($value, -1));
        if (!in_array(
            $unit,
            [
                'P',
                'T',
                'G',
                'M',
                'K'
            ]
        )
        ) {
            return (int)$value;
        }
        $value = substr($value, 0, -1);
        switch ($unit) {
        case 'P':
            $value *= 1024;
        case 'T':
            $value *= 1024;
        case 'G':
            $value *= 1024;
        case 'M':
            $value *= 1024;
        case 'K':
            $value *= 1024;
        }
        return $value;
    }
}
