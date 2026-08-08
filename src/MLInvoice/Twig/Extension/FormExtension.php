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
use DateTime;
use DI\Attribute\Inject;
use Exception;
use InvalidArgumentException;
use MLInvoice\Database\Entity\EntityInterface;
use MLInvoice\Database\Repository\BaseRepository;
use MLInvoice\Database\Repository\CustomPriceRepository;
use MLInvoice\Database\Repository\InvoiceAttachmentRepository;
use MLInvoice\Database\Repository\InvoiceRepository;
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
use Slim\Routing\RouteContext;
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
        protected Memory $memory,
        protected BaseRepository $baseRepository,
        protected InvoiceRepository $invoiceRepository,
        protected InvoiceAttachmentRepository $invoiceAttachmentRepository,
        protected ListExtension $listExtension,
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
            new TwigFunction('getFormConfig', $this->createFormConfig(...)),
            new TwigFunction('getCompanyInvoiceSearchLinks', $this->getCompanyInvoiceSearchLinks(...)),
            new TwigFunction('getListNavigationLinks', $this->getListNavigationLinks(...)),
            new TwigFunction('getBaseList', $this->getBaseList(...)),
            new TwigFunction('getInvoiceAttachmentCount', $this->invoiceAttachmentRepository->getCountByInvoiceId(...)),
            new TwigFunction('getLinkedInvoiceCount', $this->invoiceRepository->getCountByTemplateInvoiceId(...)),
            new TwigFunction('fileSizeToHumanReadable', $this->fileSizeToHumanReadable(...)),
            new TwigFunction('getMaxUploadSize', $this->getMaxUploadSize(...)),
            new TwigFunction('htmlAttributes', $this->htmlAttributes(...)),
            new TwigFunction('dataAttributes', $this->dataAttributes(...)),
            new TwigFunction('htmlFormElement', $this->htmlFormElement(...)),
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
     * @param ServerRequestInterface $request Request
     *
     * @return array
     */
    protected function getCompanyInvoiceSearchLinks(ServerRequestInterface $request, int $companyId): array
    {
        $routeContext = RouteContext::fromRequest($request);
        $routeParser = $routeContext->getRouteParser();
        $urlParamsBase = [
            's_type1' => 'company_id',
            's_field1' => $companyId,
        ];
        $invoiceLinks = [
            [
                'name' => 'NonArchivedInvoices',
                'url' => $routeParser->relativeUrlFor(
                    'search-invoices-results',
                    queryParams: $urlParamsBase + ['search_id' => SearchService::SEARCH_NON_ARCHIVED_INVOICES]
                ),
            ],
            [
                'name' => 'ArchivedInvoices',
                'url' => $routeParser->relativeUrlFor(
                    'search-invoices-results',
                    queryParams: $urlParamsBase + ['search_id' => SearchService::SEARCH_ARCHIVED_INVOICES]
                ),
            ],
            [
                'name' => 'NonArchivedOffers',
                'url' => $routeParser->relativeUrlFor(
                    'search-invoices-results',
                    queryParams: $urlParamsBase + ['search_id' => SearchService::SEARCH_NON_ARCHIVED_OFFERS]
                ),
            ],
            [
                'name' => 'ArchivedOffers',
                'url' => $routeParser->relativeUrlFor(
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

    /**
     * Format an array of HTML attributes as a string.
     *
     * @param ?array $attrs Attributes
     *
     * @return string
     */
    protected function htmlAttributes(?array $attrs): string
    {
        $result = '';
        foreach ($attrs ?? [] as $key => $value) {
            $key = htmlspecialchars((string)$key);
            $value = htmlspecialchars((string)$value);
            $result .= " $key=\"$value\"";
        }

        return $result;
    }

    /**
     * Format an array of data attributes as a string.
     *
     * @param array $attrs Attributes
     *
     * @return string
     */
    protected function dataAttributes(array $attrs): string
    {
        $result = '';
        if (array_is_list($attrs)) {
            foreach ($attrs as $key) {
                $key = htmlspecialchars('data-' . $key);
                $result .= " $key";
            }
        } else {
            foreach ($attrs as $key => $value) {
                $key = htmlspecialchars('data-' . $key);
                $value = htmlspecialchars($value);
                $result .= " $key=\"$value\"";
            }
        }

        return $result;
    }

    protected function getBaseList(): array
    {
        $result = [];
        foreach ($this->baseRepository->findAllNonDeletedActive() as $base) {
            $result[] = $base->toArray();
        }
        return $result;
    }

    /**
     * Create a form element
     *
     * @param string $strName                  Element name
     * @param string $strType                  Element type
     * @param string $strValue                 Element value
     * @param string $strStyle                 Element style
     * @param string $strListQuery             Query for list element
     * @param string $strMode                  Edit mode
     * @param string $strParentKey             Parent record ID
     * @param string $strTitle                 Element title
     * @param array  $astrDefaults             Unused TODO: remove
     * @param array  $astrAdditionalAttributes Additional HTML attributes
     * @param array  $options                  Options for a listbox or drop-down menu
     *
     * @return string
     */
    function htmlFormElement($strName, $strType, $strValue, $strStyle, $strListQuery = '',
        $strMode = 'MODIFY', $strParentKey = null, $strTitle = '', $astrDefaults = [],
        $astrAdditionalAttributes = '', $options = null
    ) {

        if ($astrAdditionalAttributes) {
            $astrAdditionalAttributes = " $astrAdditionalAttributes";
        }
        $strFormElement = '';
        $readOnly = $strMode == 'MODIFY' ? '' : ' readonly="readonly"';
        $disabled = $strMode == 'MODIFY' ? '' : ' disabled="disabled"';

        switch ($strType) {
        case 'TEXT':
            if (strstr($strStyle, 'hasDateRangePicker')) {
                $autocomplete = ' autocomplete="off"';
            } else {
                $autocomplete = '';
            }

            $strFormElement = "<input type=\"text\" class=\"form-control $strStyle\"$autocomplete " .
                "id=\"$strName\" name=\"$strName\" value=\"" .
                htmlspecialchars($strValue ?? '') . "\"$astrAdditionalAttributes$readOnly>\n";
            break;

        case 'PASSWD':
        case 'PASSWD_STORED':
            $strFormElement = "<input type=\"password\" class=\"form-control $strStyle\" " .
                "id=\"$strName\" name=\"$strName\" value=\"\"$astrAdditionalAttributes$readOnly>\n";
            break;

        case 'CHECK':
            $strValue = $strValue ? 'checked' : '';
            $strFormElement = "<input type=\"checkbox\" id=\"$strName\" name=\"$strName\" value=\"1\" " .
                htmlspecialchars($strValue ?? '') . "$astrAdditionalAttributes$disabled>\n";
            break;

        case 'RADIO':
            $strFormElement = "<input type=\"radio\" id=\"$strName\" name=\"$strName\" value=\"" .
                htmlspecialchars($strValue ?? '') . "\"$astrAdditionalAttributes$disabled>\n";
            break;

        case 'INT':
            $hideZero = false;
            if (strstr($strStyle, ' hidezerovalue')) {
                $strStyle = str_replace(' hidezerovalue', '', $strStyle);
                $hideZero = true;
            }
            if ($hideZero && $strValue == 0) {
                $strValue = '';
            }
            $strFormElement = "<input type=\"text\" class=\"form-control $strStyle\" " .
                "id=\"$strName\" name=\"$strName\" value=\"" .
                htmlspecialchars($strValue ?? '') . "\"$astrAdditionalAttributes$readOnly>\n";
            break;

        case 'INTDATE':
            $strValue = $strValue
                ? DateTime::createFromFormat(MLINVOICE_DATABASE_DATETIME_FORMAT, (string)$strValue)->format('Y-m-d')
                : '';
            $strFormElement = "<input type=\"date\" class=\"form-control $strStyle\" " .
                "id=\"$strName\" name=\"$strName\" value=\"" .
                htmlspecialchars((string)($strValue ?? '')) . "\"$astrAdditionalAttributes$readOnly>\n";
            break;

        case 'HID_INT':
        case 'HID_UUID':
            $strFormElement = '<input type="hidden" ' .
                "id=\"$strName\" name=\"$strName\" value=\"" .
                htmlspecialchars($strValue ?? '') . "\">\n";
            break;

        case 'AREA':
            $strFormElement = '<textarea class="form-control ' . $strStyle . '" ' .
                'id="' . $strName . '" name="' . $strName .
                "\"$astrAdditionalAttributes$readOnly>" . $strValue . "</textarea>\n";
            break;

        case 'RESULT':
            $strListQuery = str_replace('_ID_', $strValue, $strListQuery);
            $res = dbQueryCheck($strListQuery);
            $strFormElement = htmlspecialchars(dbFetchValue($res) ?? '') . "\n";
            break;

        case 'LIST':
            $translate = false;
            if (strstr($strStyle, ' translated')) {
                $translate = true;
                $strStyle = str_replace(' translated', '', $strStyle);
            }

            if ($strMode == 'MODIFY') {
                if (is_array($strListQuery)) {
                    $showEmpty = true;
                    if (strstr($strStyle, ' noemptyvalue')) {
                        $showEmpty = false;
                        $strStyle = str_replace(' noemptyvalue', '', $strStyle);
                    }
                    $strFormElement = $this->htmlListBox(
                        $strName, $strListQuery, $strValue, $strStyle, false, $showEmpty,
                        $astrAdditionalAttributes, $translate
                    );

                } else {
                    throw new InvalidArgumentException('listquery must be an array in field ' . $strName);
                }
            } else {
                $strFormElement = "<input type=\"text\" class=\"form-control $strStyle\" "
                    . "id=\"$strName\" name=\"$strName\" value=\""
                    . htmlspecialchars($strListQuery[$strValue] ?? '')
                    . "\"$astrAdditionalAttributes$readOnly>\n";
            }
            break;

        case 'SEARCHLIST':
            if ($strMode == 'MODIFY') {
                $showEmpty = '1';
                if (strstr($strStyle, ' noemptyvalue')) {
                    $strStyle = str_replace(' noemptyvalue', '', $strStyle);
                    $showEmpty = '0';
                }
                $strValue = htmlspecialchars($strValue ?? '');
                $valueDesc = htmlspecialchars(
                    $this->listExtension->getSearchListValueFor($strListQuery, $strValue)
                );
                $onChange = $astrAdditionalAttributes ? trim($astrAdditionalAttributes) : '';
                $encodedQuery = htmlspecialchars($strListQuery);
                $strFormElement = <<<EOT
    <select autocomplete="off" class="$strStyle js-searchlist" id="$strName" name="$strName" data-list-query="$encodedQuery" data-show-empty="$showEmpty" data-on-change="$onChange">
    <option value="$strValue" selected>$valueDesc</option>
    </select>
    EOT;
            } else {
                $strFormElement = "<input type=\"text\" class=\"form-control $strStyle\" " .
                    "id=\"$strName\" name=\"$strName\" value=\"" .
                    htmlspecialchars(
                        $this->listExtension->getSearchListValueFor($strListQuery, $strValue)
                    ) .
                    "\"$astrAdditionalAttributes$readOnly>\n";
            }
            break;
        case 'SELECT':
            $translate = false;
            if (strstr($strStyle, ' translated')) {
                $translate = true;
                $strStyle = str_replace(' translated', '', $strStyle);
            }
            if ($strMode == 'MODIFY') {
                $strFormElement = $this->htmlListBox(
                    $strName, $options, $strValue, $strStyle,
                    false, $astrAdditionalAttributes, $translate
                );
            } else {
                $strFormElement = "<input type=\"text\" class=\"form-control $strStyle\" "
                    . "id=\"$strName\" name=\"$strName\" value=\"" . htmlspecialchars($options[$strValue] ?? '')
                    . "\"$astrAdditionalAttributes$readOnly>\n";
            }
            break;
        case 'TAGS':
            if ($strMode == 'MODIFY') {
                $showEmpty = '1';
                if (strstr($strStyle, 'noemptyvalue ')) {
                    $strStyle = str_replace('noemptyvalue ', '', $strStyle);
                    $showEmpty = '0';
                }
                $values = $strValue ? explode(',', $strValue) : [];
                $onChange = $astrAdditionalAttributes ? trim($astrAdditionalAttributes) : '';
                $encodedQuery = htmlspecialchars($strListQuery);
                $strFormElement = <<<EOT
    <select multiple autocomplete="off" class="$strStyle js-searchlist select2 tags" id="$strName" name="$strName" data-list-query="$encodedQuery" data-show-empty="$showEmpty" data-on-change="$onChange">

    EOT;
                foreach ($values as $value) {
                    $value = htmlspecialchars($value);
                    $strFormElement .= '<option value="' . $value . '" selected>' . $value . "</option>\n";
                }

                $strFormElement .= '</select>';
            } else {
                $strFormElement = "<input type=\"text\" class=\"form-control $strStyle\" " .
                    "id=\"$strName\" name=\"$strName\" value=\"" .
                    htmlspecialchars($strValue) .
                    "\"$astrAdditionalAttributes$readOnly>\n";
            }
            break;

        case 'BUTTON':
            $strListQuery = str_replace('_ID_', $strValue, $strListQuery);
            switch ($strStyle) {
            case 'custom':
                $strListQuery = str_replace("'", '', $strListQuery);
                $strHref = $strListQuery;
                $strOnClick = '';
                break;

            case 'redirect':
                $strHref = '#';
                $strOnClick = "onclick=\"MLInvoice.Form.saveRecord('$strListQuery', 'redirect'); return false;\"";
                break;

            case 'openwindow':
                $strHref = '#';
                $strOnClick = "onclick=\"MLInvoice.Form.saveRecord('$strListQuery', 'openwindow'); return false;\"";
                break;

            default:
                switch ($strStyle) {
                case 'tiny':
                    $strHW = 'height=1,width=1,';
                    break;
                case 'small':
                    $strHW = 'height=200,width=200,';
                    break;
                case 'medium':
                    $strHW = 'height=400,width=400,';
                    break;
                case 'large':
                    $strHW = 'height=600,width=600,';
                    break;
                case 'xlarge':
                    $strHW = 'height=800,width=650,';
                    break;
                case 'full':
                    $strHW = '';
                    break;
                default:
                    $strHW = '';
                    break;
                }
                $strHref = '#';
                $strOnClick = 'onclick="window.open(' . $strListQuery . ",'" . $strHW .
                    'menubar=no,scrollbars=no,' .
                    "status=no,toolbar=no'); return false;\"";
                break;
            }
            $strFormElement = "<a class=\"btn btn-secondary formbuttonlink\" href=\"$strHref\" $strOnClick$astrAdditionalAttributes>" .
                htmlspecialchars($this->translator->translate($strTitle)) . "</a>\n";
            break;

        case 'JSBUTTON':
            if (strstr($strListQuery, '_ID_') && !$strValue) {
                $strFormElement = $this->translator->translate('SaveFirst');
            } else {
                if ($strValue) {
                    $strListQuery = str_replace('_ID_', $strValue, $strListQuery);
                }
                $strOnClick = "onClick=\"$strListQuery\"";
                $strFormElement = "<a class=\"btn btn-secondary formbuttonlink\" href=\"#\" $strOnClick$astrAdditionalAttributes>" .
                    htmlspecialchars($this->translator->translate($strTitle)) . "</a>\n";
            }
            break;

        case 'DROPDOWNMENU':
            if (strstr($strListQuery, '_ID_') && !$strValue) {
                $strFormElement = $this->translator->translate('SaveFirst');
            } else {
                $menuTitle = htmlspecialchars($this->translator->translate($strTitle));
                $menuItems = '';
                foreach ($options as $option) {
                    $strListQuery = str_replace('_ID_', $strValue, $option['listquery']);
                    $menuItems .= '<li onClick="' . $strListQuery . '"><div>' . $this->translator->translate($option['label']) . '</div></li>';
                }
                $strFormElement = <<<EOT
    <ul class="dropdownmenu" $astrAdditionalAttributes>
    <li>$menuTitle
        <ul>
        $menuItems
        </ul>
    </li>
    </ul>
    EOT;
            }
            break;

        case 'IMAGE':
            $strListQuery = str_replace('_ID_', $strValue, $strListQuery);
            $strFormElement = "<img id=\"$strName\" class=\"$strStyle\" src=\"$strListQuery\" title=\"" .
                htmlspecialchars($this->translator->translate($strTitle)) . "\">\n";
            break;

        case 'FILE':
            $strFormElement = '<input type="file" class="form-control ' . $strStyle . '" ' .
                'id="' . $strName . '" name="' . $strName .
                "\"$astrAdditionalAttributes$readOnly>\n";
            break;

        default:
            $strFormElement = "&nbsp;\n";
        }

        return $strFormElement;
    }

    /**
     * Create Html listbox
     *
     * @param string      $strName         Listbox name
     * @param array       $astrValues      Listbox values => descriptions
     * @param string      $strSelected     Selected value
     * @param string      $strStyle        Style
     * @param bool        $submitOnChange  Whether to submit the form when value is
     *                                     changed
     * @param bool|string $showEmpty       Whether to show "empty" value (string for
     *                                     translated value)
     * @param string      $additionalAttrs Any additional attributes
     * @param bool        $translate       Whether the options are translated
     *
     * @return string HTML
     */
    function htmlListBox($strName, $astrValues, $strSelected, $strStyle = '',
        $submitOnChange = false, $showEmpty = true, $additionalAttrs = '',
        $translate = false
    ) {
        $strOnChange = '';
        if ($submitOnChange) {
            $strOnChange = " onchange='this.form.submit();'";
        }
        if ($additionalAttrs) {
            $additionalAttrs = " $additionalAttrs";
        }
        $strListBox = "<select class=\"$strStyle\" id=\"$strName\" name=\"$strName\"{$strOnChange}{$additionalAttrs}>\n";
        if ($showEmpty) {
            if (true === $showEmpty) {
                $showEmpty = ' - ';
            } else {
                $showEmpty = $this->translator->translate($showEmpty);
            }
            $strListBox .= '<option value=""' . ($strSelected ? '' : ' selected') .
                ">$showEmpty</option>\n";
        }

        foreach ($astrValues as $value => $desc) {
            if ($desc instanceof EntityInterface) {
                $value = (string)$desc->getId();
                $desc = $desc->getName();
            }
            $value ??= '';
            $desc ??= '-';
            $strSelect = $strSelected == $value ? ' selected' : '';
            if ($translate) {
                $desc = $this->translator->translate($desc);
            }
            $strListBox .= '<option value="' . htmlspecialchars((string)$value) . "\"$strSelect>" .
                htmlspecialchars($desc) . "</option>\n";
        }
        $strListBox .= "</select>\n";

        return $strListBox;
    }
}
