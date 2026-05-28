<?php
/**
 * Form Service.
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
 * @package  MLInvoice\Base
 * @author   Ere Maijala <ere@labs.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://labs.fi/mlinvoice.eng.php
 */

declare(strict_types=1);

namespace MLInvoice\Form;

use DI\Attribute\Inject;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use InvalidArgumentException;
use MLInvoice\Config\ConfigManagerInterface;
use MLInvoice\Config\SettingsManager;
use MLInvoice\Database\Entity\Company;
use MLInvoice\Database\Entity\EntityInterface;
use MLInvoice\Database\Repository\BaseRepository;
use MLInvoice\Database\Repository\CompanyContactRepository;
use MLInvoice\Database\Repository\CompanyContactTagRepository;
use MLInvoice\Database\Repository\CompanyRepository;
use MLInvoice\Database\Repository\CompanyTagRepository;
use MLInvoice\Database\Repository\DeliveryMethodRepository;
use MLInvoice\Database\Repository\DeliveryTermsRepository;
use MLInvoice\Database\Repository\InvoiceRepository;
use MLInvoice\Database\Repository\InvoiceRowRepository;
use MLInvoice\Database\Repository\InvoiceStateRepository;
use MLInvoice\Database\Repository\PrintTemplateRepository;
use MLInvoice\Database\Repository\ProductRepository;
use MLInvoice\Database\Repository\RowTypeRepository;
use MLInvoice\Database\Repository\SessionTypeRepository;
use MLInvoice\I18n\NumberFormatter;
use MLInvoice\I18n\Translator;
use MLInvoice\Markdown\MLMarkdown;
use MLInvoice\Search\Search;
use MLInvoice\Search\SearchService;
use MLInvoice\Security\Crypt;
use MLInvoice\Session\Memory;
use MLInvoice\Utils\DateUtils;
use Odan\Session\SessionInterface;
use ProductReport;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Form Service.
 *
 * @category MLInvoice
 * @package  MLInvoice\Base
 * @author   Ere Maijala <ere@labs.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://labs.fi/mlinvoice.eng.php
 */
class FormService
{
    /**
     * Cache for form configs.
     *
     * @var array
     */
    protected array $cache = [];

    /**
     * Constructor
     *
     * @param array $config Configuration
     * @param string $dbPrefix Database table prefix
     */
    public function __construct(
        #[Inject('config')] protected array $config,
        #[Inject('dbPrefix')] protected string $prefix,
        protected Translator $translator,
        protected SettingsManager $settingsManager,
        protected SessionInterface $session,
        protected Crypt $crypt,
        protected DateUtils $dateUtils,
        protected NumberFormatter $numberFormatter,
        protected EntityManagerInterface $entityManager,
        protected InvoiceRepository $invoiceRepository,
        protected InvoiceStateRepository $invoiceStateRepository,
        protected BaseRepository $baseRepository,
        protected DeliveryTermsRepository $deliveryTermsRepository,
        protected DeliveryMethodRepository $deliveryMethodRepository,
        protected PrintTemplateRepository $printTemplateRepository,
        protected RowTypeRepository $rowTypeRepository,
        protected SessionTypeRepository $sessionTypeRepository,
        protected CompanyRepository $companyRepository,
        protected CompanyTagRepository $companyTagRepository,
        protected CompanyContactRepository $companyContactRepository,
        protected CompanyContactTagRepository $companyContactTagRepository,
        protected InvoiceRowRepository $invoiceRowRepository,
        protected ProductRepository $productRepository,
    ) {
    }

    /**
     * Get form configuration.
     *
     * @param string $form Form
     * @param ?int   $id   Record ID
     * @param ?ServerRequestInterface $request Request,
     * @param bool $forSearch Getting fields for search?
     * @param ?int   $parentId Parent record ID
     *
     * @return ?array
     */
    public function getFormConfig(
        string $form,
        ?int $id = null,
        ?ServerRequestInterface $request = null,
        bool $forSearch = false,
        ?int $parentId = null,
    ): array {
        $cacheKey = $form . '||' . (string)$id . '||' . ($forSearch ? '1' : '0') . '||' . (string)$parentId;
        if ($cached = $this->cache[$cacheKey] ?? null) {
            return $cached;
        }

        $writeAccess = $request?->getAttribute('write_access') ?? false;
        $inputFieldTypes = [
            'AREA',
            'CHECK',
            'FILE',
            'INT',
            'INTDATE',
            'LIST',
            'PASSWD',
            'PASSWD_STORED',
            'SEARCHLIST',
            'TAGS',
            'TEXT',
            'SELECT',
        ];

        $searchFieldTypes = array_diff(
            $inputFieldTypes,
            [
                'FILE',
                'PASSWD',
                'PASSWD_STORED'
            ]
        );

        $strListTableAlias = '';
        $levelsAllowed = [
            MLINVOICE_USER_ROLE_USER,
            MLINVOICE_USER_ROLE_BACKUPMGR
        ];
        $copyLinkOverride = null;
        $clearRowValuesAfterAdd = false;
        $onAfterRowAdded = '';
        $readOnlyForm = false;
        $addressAutocomplete = false;
        $formDataAttrs = [];
        if (!$writeAccess) {
            $formDataAttrs[] = 'read-only';
        }
        $extraButtons = '';
        if (!isset($strFunc)) {
            $strFunc = '';
        }
        if (!isset($strList)) {
            $strList = '';
        }
        $mdClass = $this->settingsManager->get('printout_markdown')
            && $this->settingsManager->get('markdown_editor') ? ' markdown' : '';

        switch ($form) {

        case 'company':
            $strTable = "{$this->prefix}company";
            $strParentKey = 'company_id';
            $addressAutocomplete = true;
            $astrSearchFields = [
                [
                    'name' => 'company_name',
                    'type' => 'TEXT'
                ]
            ];

            $astrFormElements = [
                [
                    'name' => 'company_name',
                    'label' => 'ClientName',
                    'type' => 'TEXT',
                    'style' => 'medium',
                    'position' => 1
                ],
                [
                    'name' => 'inactive',
                    'label' => 'ClientInactive',
                    'type' => 'CHECK',
                    'style' => 'medium',
                    'position' => 2,
                    'default' => 0,
                    'allow_null' => true
                ],
                [
                    'name' => 'company_id',
                    'label' => 'ClientVATID',
                    'type' => 'TEXT',
                    'style' => 'medium',
                    'position' => 1,
                    'allow_null' => true
                ],
                [
                    'name' => 'email',
                    'label' => 'Email',
                    'type' => 'TEXT',
                    'style' => 'medium',
                    'position' => 2,
                    'allow_null' => true
                ],
                [
                    'name' => 'customer_no',
                    'label' => 'CustomerNr',
                    'type' => 'INT',
                    'style' => 'medium',
                    'position' => 1,
                    'default' => $this->settingsManager->get('add_customer_number')
                        ? $this->companyRepository->getNextAvailableCustomerNumber()
                        : null,
                    'allow_null' => true
                ],
                [
                    'name' => 'default_ref_number',
                    'label' => 'CustomerDefaultRefNr',
                    'type' => 'TEXT',
                    'style' => 'medium',
                    'position' => 2,
                    'allow_null' => true
                ],
                [
                    'name' => 'org_unit_number',
                    'label' => 'OrgUnitNumber',
                    'type' => 'TEXT',
                    'style' => 'medium',
                    'position' => 1,
                    'allow_null' => true
                ],
                [
                    'name' => 'payment_intermediator',
                    'label' => 'PaymentIntermediator',
                    'type' => 'TEXT',
                    'style' => 'medium',
                    'position' => 2,
                    'allow_null' => true
                ],
                [
                    'name' => 'delivery_terms_id',
                    'label' => 'DeliveryTerms',
                    'type' => 'LIST',
                    'style' => 'medium',
                    'list' => $this->deliveryTermsRepository->findAllNonDeleted(),
                    'position' => 1,
                    'default' => null,
                    'allow_null' => true
                ],
                [
                    'name' => 'delivery_method_id',
                    'label' => 'DeliveryMethod',
                    'type' => 'LIST',
                    'style' => 'medium',
                    'list' => $this->deliveryMethodRepository->findAllNonDeleted(),
                    'position' => 2,
                    'default' => null,
                    'allow_null' => true
                ],
                [
                    'name' => 'payment_days',
                    'label' => 'PaymentDays',
                    'type' => 'INT',
                    'style' => 'short',
                    'position' => 1,
                    'default' => null,
                    'allow_null' => true
                ],
                [
                    'name' => 'terms_of_payment',
                    'label' => 'TermsOfPayment',
                    'type' => 'TEXT',
                    'style' => 'medium',
                    'position' => 2,
                    'default' => null,
                    'allow_null' => true
                ],
                [
                    'name' => 'invoice_vatless',
                    'label' => 'InvoiceVATLess',
                    'type' => 'CHECK',
                    'style' => 'medium',
                    'position' => 1,
                    'default' => null,
                    'allow_null' => true
                ],
                [
                    'name' => 'street_address',
                    'label' => 'StreetAddr',
                    'type' => 'TEXT',
                    'style' => 'medium',
                    'position' => 1,
                    'allow_null' => true
                ],
                [
                    'name' => 'zip_code',
                    'label' => 'ZipCode',
                    'type' => 'TEXT',
                    'style' => 'medium',
                    'position' => 2,
                    'allow_null' => true
                ],
                [
                    'name' => 'city',
                    'label' => 'City',
                    'type' => 'TEXT',
                    'style' => 'medium',
                    'position' => 1,
                    'allow_null' => true
                ],
                [
                    'name' => 'country',
                    'label' => 'Country',
                    'type' => 'TEXT',
                    'style' => 'medium',
                    'position' => 2,
                    'allow_null' => true
                ],
                [
                    'name' => 'phone',
                    'label' => 'Phone',
                    'type' => 'TEXT',
                    'style' => 'medium',
                    'position' => 1,
                    'allow_null' => true
                ],
                [
                    'name' => 'fax',
                    'label' => 'FAX',
                    'type' => 'TEXT',
                    'style' => 'medium',
                    'position' => 2,
                    'allow_null' => true
                ],
                [
                    'name' => 'gsm',
                    'label' => 'GSM',
                    'type' => 'TEXT',
                    'style' => 'medium',
                    'position' => 1,
                    'allow_null' => true
                ],
                [
                    'name' => 'www',
                    'label' => 'WWW',
                    'type' => 'TEXT',
                    'style' => 'medium',
                    'position' => 2,
                    'allow_null' => true
                ],
                [
                    'name' => 'billing_address',
                    'label' => 'BillAddr',
                    'type' => 'AREA',
                    'style' => 'wide',
                    'position' => 1,
                    'allow_null' => true
                ],
                [
                    'name' => 'delivery_address',
                    'label' => 'DeliveryAddress',
                    'type' => 'AREA',
                    'style' => 'wide',
                    'position' => 2,
                    'allow_null' => true
                ],
                [
                    'name' => 'info',
                    'label' => 'Info',
                    'type' => 'AREA',
                    'style' => 'wide',
                    'position' => 1,
                    'allow_null' => true
                ],
                [
                    'name' => 'invoice_default_reference',
                    'label' => 'InvoiceDefaultReference',
                    'type' => 'TEXT',
                    'style' => 'medium',
                    'position' => 1,
                    'allow_null' => true
                ],
                [
                    'name' => 'invoice_default_foreword',
                    'label' => 'InvoiceDefaultForeword',
                    'type' => 'AREA',
                    'style' => "large$mdClass",
                    'position' => 1,
                    'allow_null' => true
                ],
                [
                    'name' => 'invoice_default_afterword',
                    'label' => 'InvoiceDefaultAfterword',
                    'type' => 'AREA',
                    'style' => "large$mdClass",
                    'position' => 2,
                    'allow_null' => true
                ],
                [
                    'name' => 'offer_default_foreword',
                    'label' => 'OfferDefaultForeword',
                    'type' => 'AREA',
                    'style' => "large$mdClass",
                    'position' => 1,
                    'allow_null' => true
                ],
                [
                    'name' => 'offer_default_afterword',
                    'label' => 'OfferDefaultAfterword',
                    'type' => 'AREA',
                    'style' => "large$mdClass",
                    'position' => 2,
                    'allow_null' => true
                ],
                [
                    'name' => 'tags',
                    'label' => 'Tags',
                    'type' => 'TAGS',
                    'style' => 'noemptyvalue long',
                    'listquery' => 'table=company_tag&sort=tag',
                    'position' => 0,
                    'default' => null,
                    'allow_null' => true
                ],
                [
                    'name' => 'company_contact',
                    'label' => 'Contacts',
                    'type' => 'IFORM',
                    'style' => 'full',
                    'position' => 0,
                    'allow_null' => true,
                    'parent_key' => 'company_id'
                ]
            ];
            break;

        case 'company_contact':
            $strTable = "{$this->prefix}company_contact";
            $strParentKey = 'company_id';
            $clearRowValuesAfterAdd = true;
            $astrFormElements = [
                [
                    'name' => 'contact_type',
                    'label' => 'ContactType',
                    'type' => 'LIST',
                    'style' => 'medium translated',
                    'listquery' => [
                        'invoice' => 'ContactTypeInvoice',
                        'dispatch' => 'ContactTypeDispatchNote',
                        'receipt' => 'ContactTypeReceipt',
                        'order_confirmation' => 'ContactTypeOrderConfirmation',
                        'reminder' => 'ContactTypeReminder',
                        'offer' => 'ContactTypeOffer'
                    ],
                    'position' => 0,
                    'allow_null' => true
                ],
                [
                    'name' => 'id',
                    'label' => '',
                    'type' => 'HID_INT',
                    'style' => 'medium',
                    'position' => 0
                ],
                [
                    'name' => 'contact_person',
                    'label' => 'ContactPerson',
                    'type' => 'TEXT',
                    'style' => 'medium',
                    'position' => 0,
                    'allow_null' => true
                ],
                [
                    'name' => 'person_title',
                    'label' => 'PersonTitle',
                    'type' => 'TEXT',
                    'style' => 'short',
                    'listquery' => '',
                    'position' => 0,
                    'allow_null' => true
                ],
                [
                    'name' => 'phone',
                    'label' => 'Phone',
                    'type' => 'TEXT',
                    'style' => 'mediumshort',
                    'position' => 0,
                    'allow_null' => true
                ],
                [
                    'name' => 'gsm',
                    'label' => 'GSM',
                    'type' => 'TEXT',
                    'style' => 'mediumshort',
                    'position' => 0,
                    'allow_null' => true
                ],
                [
                    'name' => 'email',
                    'label' => 'Email',
                    'type' => 'TEXT',
                    'style' => 'medium',
                    'position' => 0,
                    'allow_null' => true
                ],
                [
                    'name' => 'tags',
                    'label' => 'Tags',
                    'type' => 'TAGS',
                    'style' => 'noemptyvalue medium',
                    'listquery' => 'table=contact_tag&sort=tag',
                    'position' => 0,
                    'default' => null,
                    'allow_null' => true
                ]
            ];
            break;

        case 'product':
            $strTable = "{$this->prefix}product";
            $astrSearchFields = [
                [
                    'name' => 'product_name',
                    'type' => 'TEXT'
                ]
            ];

            if ($writeAccess) {
                $updateStockBalanceCode = '<button type="button" class="btn btn-secondary update-stock-balance">' . $this->translator->translate('UpdateStockBalance') . '</button>';
            }

            $barcodeTypes = [
                'EAN13' => 'EAN13',
                'C39' => 'CODE 39',
                'C39E' => 'CODE 39 Extended',
                'C128' => 'CODE 128',
                'C128A' => 'CODE 128 A',
                'C128B' => 'CODE 128 B',
                'C128C' => 'CODE 128 C'
            ];

            $astrFormElements = [
                [
                    'name' => 'order_no',
                    'label' => 'OrderNr',
                    'type' => 'INT',
                    'style' => 'short',
                    'position' => 1,
                    'allow_null' => true,
                    'listquery' => "SELECT max(order_no)+5 FROM {$this->prefix}product WHERE deleted=0",
                    'default' => 'ADD+5'
                ],
                [
                    'name' => 'product_code',
                    'label' => 'ProductCode',
                    'type' => 'TEXT',
                    'style' => 'medium',
                    'position' => 2,
                    'allow_null' => true
                ],
                [
                    'name' => 'product_name',
                    'label' => 'ProductName',
                    'type' => 'AREA',
                    'style' => "large$mdClass",
                    'position' => 1,
                    'default' => '',
                    'allow_null' => true
                ],
                [
                    'name' => 'product_group',
                    'label' => 'ProductGroup',
                    'type' => 'TEXT',
                    'style' => 'medium',
                    'position' => 2,
                    'allow_null' => true
                ],
                [
                    'name' => 'vendor',
                    'label' => 'ProductVendor',
                    'type' => 'TEXT',
                    'style' => 'medium',
                    'position' => 1,
                    'allow_null' => true
                ],
                [
                    'name' => 'vendors_code',
                    'label' => 'ProductVendorsCode',
                    'type' => 'TEXT',
                    'style' => 'medium',
                    'position' => 2,
                    'allow_null' => true
                ],
                [
                    'name' => 'barcode1',
                    'label' => 'FirstBarcode',
                    'type' => 'TEXT',
                    'style' => 'medium',
                    'position' => 1,
                    'allow_null' => true
                ],
                [
                    'name' => 'barcode1_type',
                    'label' => 'BarcodeType',
                    'type' => 'LIST',
                    'style' => 'medium',
                    'position' => 2,
                    'listquery' => $barcodeTypes,
                    'allow_null' => true
                ],
                [
                    'name' => 'barcode2',
                    'label' => 'SecondBarcode',
                    'type' => 'TEXT',
                    'style' => 'medium',
                    'position' => 1,
                    'allow_null' => true
                ],
                [
                    'name' => 'barcode2_type',
                    'label' => 'BarcodeType',
                    'type' => 'LIST',
                    'style' => 'medium',
                    'position' => 2,
                    'listquery' => $barcodeTypes,
                    'allow_null' => true
                ],
                [
                    'name' => 'description',
                    'label' => 'ProductDescription',
                    'type' => 'AREA',
                    'style' => "large$mdClass",
                    'position' => 1,
                    'allow_null' => true
                ],
                [
                    'name' => 'internal_info',
                    'label' => 'InternalInfo',
                    'type' => 'AREA',
                    'style' => 'xlarge',
                    'position' => 0,
                    'allow_null' => true
                ],
                [
                    'name' => 'unit_price',
                    'label' => 'UnitPrice',
                    'type' => 'INT',
                    'style' => 'medium',
                    'position' => 1,
                    'decimals' => (int)$this->settingsManager->get('unit_price_decimals'),
                    'allow_null' => true
                ],
                [
                    'name' => 'type_id',
                    'label' => 'Unit',
                    'type' => 'LIST',
                    'style' => 'short translated',
                    'listquery' => $this->rowTypeRepository->findAllNonDeleted(),
                    'position' => 2,
                    'allow_null' => true
                ],
                [
                    'name' => 'price_decimals',
                    'label' => 'PriceInvoiceDecimals',
                    'type' => 'INT',
                    'style' => 'short',
                    'position' => 1,
                    'default' => 2
                ],
                [
                    'name' => 'discount',
                    'label' => 'DiscountPercent',
                    'type' => 'INT',
                    'style' => 'currency',
                    'position' => 1,
                    'decimals' => 1,
                    'allow_null' => true
                ],
                [
                    'name' => 'discount_amount',
                    'label' => 'DiscountAmount',
                    'type' => 'INT',
                    'style' => 'medium',
                    'position' => 2,
                    'decimals' => (int)$this->settingsManager->get('unit_price_decimals'),
                    'allow_null' => true
                ],
                [
                    'name' => 'vat_percent',
                    'label' => 'VATPercent',
                    'type' => 'INT',
                    'style' => 'currency',
                    'position' => 1,
                    'default' => $this->settingsManager->get('invoice_default_vat_percent'),
                    'decimals' => 1
                ],
                [
                    'name' => 'vat_included',
                    'label' => 'VATIncluded',
                    'type' => 'CHECK',
                    'style' => 'medium',
                    'position' => 2,
                    'default' => false,
                    'allow_null' => true
                ],
                [
                    'name' => 'purchase_price',
                    'label' => 'PurchasePrice',
                    'type' => 'INT',
                    'style' => 'medium',
                    'position' => 1,
                    'decimals' => (int)$this->settingsManager->get('unit_price_decimals'),
                    'allow_null' => true
                ],
                [
                    'name' => 'stock_balance',
                    'label' => 'StockBalance',
                    'type' => 'INT',
                    'style' => 'medium',
                    'position' => 2,
                    'decimals' => 2,
                    'allow_null' => true,
                    'read_only' => true,
                    'attached_elem' => $updateStockBalanceCode
                ],
                [
                    'name' => 'weight',
                    'label' => 'Weight',
                    'type' => 'INT',
                    'style' => 'medium',
                    'position' => 1,
                    'decimals' => 3,
                    'allow_null' => true
                ],
            ];
            break;

        case 'invoice':
        case 'offer':
        case 'invoice_template':
            $levelsAllowed[] = MLINVOICE_USER_ROLE_READONLY;
            $strTable = "{$this->prefix}invoice";
            $strListTableAlias = 'i.'; // this is for the search function
            $strParentKey = 'invoice_id';
            $addressAutocomplete = true;
            $defaultState = 1;
            $isOffer = 'offer' === $form;
            $isTemplate = 'invoice_template' === $form;
            $invoice = null;

            if ($id) {
                $invoice = $this->invoiceRepository->find($id);
                $isOffer = $invoice?->getState()?->getOffer();
                $isTemplate = $invoice?->getState()?->getTemplate();

                if ($isOffer) {
                    $locCopyAsInvoice = $this->translator->translate('CopyAsInvoice');
                    $extraButtons = <<<EOT
        <a role="button" class="btn btn-secondary" href="copy_invoice.php?func=$strFunc&amp;list=$strList&amp;id=$id&amp;invoice=1">$locCopyAsInvoice</a>

        EOT;
                }
            } else {
                if ($isTemplate) {
                    $defaultState = $this->invoiceStateRepository->findInitialInvoiceTemplateState();
                } elseif ($isOffer) {
                    $defaultState = $this->invoiceStateRepository->findInitialOfferState();
                }
            }

            $companyOnChange = '';
            $getInvoiceNr = '';
            $updateDates = '';
            $addCompanyCode = '';

            if ($writeAccess) {
                $locUpdateDates = $this->translator->translate('UpdateDates');
                $updateDates = '<button type="button" class="btn btn-outline-secondary update-dates">' . $locUpdateDates . '</button>';

                $locNew = $this->translator->translate('New') . '...';
                $addCompanyCode = '<button type="button" class="btn btn-outline-secondary" data-quick-add-company>' . $locNew . '</button>';

                if (!$isOffer && !$isTemplate) {
                    $companyOnChange = '_onChangeCompany';

                    if ($this->settingsManager->get('invoice_warn_if_noncurrent_date')) {
                        $formDataAttrs[] = 'check-invoice-date';
                    }

                    if (!$this->settingsManager->get('invoice_add_number')) {
                        $formDataAttrs[] = 'check-invoice-number';
                    }
                } else {
                    $companyOnChange = '_onChangeCompanyOfferOrTemplate';
                }
            }

            if ($writeAccess && !$isOffer && !$isTemplate) {
                $today = date('Y-m-d');
                $markPaidToday = "if ([1, 2, 5, 6, 7].indexOf(parseInt($('#state_id').val())) !== -1) {"
                    . " $('#state_id').val(3); }"
                    . " if (!$(this).is('#payment_date')) { $('#payment_date').val('$today'); }";

                if ($this->settingsManager->get('invoice_auto_archive')) {
                    $markPaidToday .= <<<EOS
        if ($('#interval_type').val() == 0) { $('#archived').prop('checked', true); }
        EOS;
                }
                $markPaidToday .= <<<EOS
        MLInvoice.highlightButton('.save_button', true); return false;
        EOS;
                $markPaidTodayButton = '<button type="button" class="btn btn-outline-secondary" onclick="' .
                    $markPaidToday . '">' . $this->translator->translate('MarkAsPaidToday') . '</button>';
                if ($this->settingsManager->get('invoice_mark_paid_when_payment_date_set')) {
                    $markPaidTodayEvent = <<<EOF
        if ($(this).val()) { $markPaidToday }
        EOF;
                } else {
                    $markPaidTodayEvent = '';
                }
            } else {
                $markPaidTodayEvent = '';
                $markPaidTodayButton = '';
            }

            $defaultValues = [
                'base' => null,
                'info' => '',
                'foreword' => '',
                'afterword' => ''
            ];
            $baseCount = $this->baseRepository->count();
            if ($baseCount === 1
                || ($this->settingsManager->get('remember_last_base')
                && ($defaultBaseId = $this->session->get('default_base_id')))
            ) {
                if ($base = $this->baseRepository->getNonDeletedById((int)$defaultBaseId)) {
                    $defaultValues['base'] = $base->getId();
                    $defaultValues['info'] = $base->getInvoiceDefaultInfo();
                    $defaultValues['foreword'] = $isOffer
                        ? $base->getOfferDefaultForeword()
                        : $base->getInvoiceDefaultForeword();
                    $defaultValues['afterword'] = $isOffer
                        ? $base->getOfferDefaultAfterword()
                        : $base->getInvoiceDefaultAfterword();
                }
            }

            $copyLinkOverride = $id ? [
                'route' => 'invoice-copy',
                'routeArgs' => ['from' => $id],
                'queryParams' => [],
            ] : null;

            $updateInvoiceNr = null;
            if ($writeAccess && !$isOffer && !$isTemplate) {
                if (!$this->settingsManager->get('invoice_add_number')
                    || !$this->settingsManager->get('invoice_add_reference_number')
                ) {
                    $updateInvoiceNr = '<button type="button" class="btn btn-outline-secondary update-invoice-nr">'
                        . $this->translator->translate('GetInvoiceNr') . '</button>';
                }
            }

            $locReminderFeesAdded = $this->translator->translate('ReminderFeesAdded');
            $addReminderFees = "$.getJSON(MLInvoice.getPath() + '/json?func=add_reminder_fees&amp;id=' + document.getElementById('record_id').value, function(json) {"
                . " if (json.errors) { MLInvoice.errormsg(json.errors); } else { MLInvoice.infomsg('$locReminderFeesAdded'); } MLInvoice.Form.initRows(); });"
                . " return false;";

            $invoiceStates = $this->invoiceStateRepository->findAllNonDeleted();
            if (!$forSearch) {
                $invoiceStates = array_values(
                    array_filter(
                        $invoiceStates,
                        fn ($s) => $s->getOffer() === $isOffer && $s->getTemplate() === $isTemplate
                    )
                );
            }

            $hideRecurrence = $isOffer;
            $group1 = [];
            if (!$isOffer && !$isTemplate && ($template = $invoice?->getTemplateInvoice())) {
                $group1[] = [
                    'name' => 'showRecurringInvoiceTemplate',
                    'label' => 'ShowRecurringInvoiceTemplate',
                    'route' => 'invoice',
                    'routeArgs' => ['id' => $template->getId()],
                ];
                $hideRecurrence = true;
            }

            $astrFormElements = [
                [
                    'name' => 'uuid',
                    'label' => 'uuid',
                    'type' => 'HID_UUID',
                    'style' => '',
                    'position' => 1,
                    'allow_null' => false,
                    'default' => \Ramsey\Uuid\Uuid::uuid4()->toString()
                ],
                /*[
                    'name' => 'template_invoice_id',
                    'label' => 'template_invoice_id',
                    'type' => 'HID_INT',
                    'style' => '',
                    'position' => 1,
                    'allow_null' => true,
                    'default' => null,
                ],*/
                [
                    'name' => 'base_id',
                    'label' => 'Biller',
                    'type' => 'SEARCHLIST',
                    'style' => 'long linked' . ($defaultValues['base'] ? ' noemptyvalue' : ''),
                    'listquery' => 'table=base&sort=name,company_id',
                    'position' => 1,
                    'default' => $defaultValues['base']
                ],
                [
                    'name' => 'name',
                    'label' => 'ext_search' === $strFunc ? 'Name' : ($isOffer ? 'OfferName' : 'InvName'),
                    'type' => 'TEXT',
                    'style' => 'medium',
                    'position' => 2,
                    'allow_null' => true
                ],
                [
                    'name' => 'company_id',
                    'label' => 'Payer',
                    'type' => 'SEARCHLIST',
                    'style' => 'long linked',
                    'listquery' => 'table=company&sort=company_name,company_id',
                    'position' => 1,
                    'allow_null' => true,
                    'attached_elem' => $addCompanyCode,
                    'elem_attributes' => $companyOnChange,
                    'default' => $request->getQueryParams()['company_id'] ?? null,
                ],
                [
                    'name' => 'reference',
                    'label' => 'ClientsReference',
                    'type' => 'TEXT',
                    'style' => 'medium',
                    'position' => 2,
                    'allow_null' => true,
                    'hidden' => $isOffer,
                ],
                [
                    'name' => 'invoice_no',
                    'label' => 'InvoiceNumber',
                    'type' => 'INT',
                    'style' => 'medium hidezerovalue',
                    'position' => 1,
                    'default' => null,
                    'allow_null' => true,
                    'hidden' => $isOffer,
                ],
                [
                    'name' => 'ref_number',
                    'label' => 'ReferenceNumber',
                    'type' => 'TEXT',
                    'style' => 'medium hidezerovalue',
                    'position' => 2,
                    'default' => null,
                    'attached_elem' => $updateInvoiceNr,
                    'allow_null' => true,
                    'hidden' => $isOffer,
                ],
                [
                    'name' => 'invoice_date',
                    'label' => 'InvDate',
                    'type' => 'INTDATE',
                    'style' => 'date',
                    'position' => 1,
                    'default' => 'DATE_NOW',
                    'hidden' => $isTemplate,
                ],
                [
                    'name' => 'due_date',
                    'label' => $isOffer ? 'ValidUntilDate' : 'DueDate',
                    'type' => 'INTDATE',
                    'style' => 'date',
                    'position' => 2,
                    'default' => 'DATE_NOW+' . $this->settingsManager->get('invoice_payment_days'),
                    'attached_elem' => $updateDates,
                    'hidden' => $isTemplate,
                ],
                [
                    'name' => 'interval_type',
                    'label' => 'InvoiceIntervalType',
                    'type' => 'SELECT',
                    'style' => 'long',
                    'position' => 1,
                    'options' => $this->geInvoiceIntervalOptions(),
                    'default' => '0',
                    'allow_null' => false,
                    'hidden' => $hideRecurrence,
                ],
                [
                    'name' => 'next_interval_date',
                    'label' => 'InvoiceNextIntervalDate',
                    'type' => 'INTDATE',
                    'style' => 'date',
                    'position' => 2,
                    'default' => '',
                    'allow_null' => true,
                    'hidden' => $hideRecurrence,
                ],
                [
                    'name' => 'state_id',
                    'label' => 'Status',
                    'type' => 'LIST',
                    'style' => 'long translated noemptyvalue',
                    'list' => $invoiceStates,
                    'position' => 1,
                    'default' => $defaultState,
                    'hidden' => $isTemplate,
                ],
                [
                    'name' => 'payment_date',
                    'label' => 'PayDate',
                    'type' => 'INTDATE',
                    'style' => 'date',
                    'position' => 2,
                    'allow_null' => true,
                    'attached_elem' => !$isTemplate ? $markPaidTodayButton : null,
                    'elem_attributes' => 'max="' . date('Y-m-d') . '"'
                        . (!$isTemplate ? ' onchange="' . $markPaidTodayEvent . '"' : ''),
                    'hidden' => $isOffer || $isTemplate,
                ],
                [
                    'name' => 'archived',
                    'label' => 'Archived',
                    'type' => 'CHECK',
                    'style' => 'medium',
                    'position' => 1,
                    'default' => 0,
                    'allow_null' => true,
                    'elem_attributes' =>  $this->settingsManager->get('invoice_auto_archive') ? 'data-auto-archive' : '',
                ],
                [
                    'name' => 'type_id',
                    'label' => 'InvoiceType',
                    'type' => 'SEARCHLIST',
                    'style' => 'long',
                    'listquery' => 'table=invoice_type&sort=order_no,name',
                    'position' => 2,
                    'default' => null,
                    'allow_null' => true,
                    'hidden' => $isOffer,
                ],
                [
                    'name' => 'delivery_time',
                    'label' => 'DeliveryTime',
                    'type' => 'TEXT',
                    'style' => 'medium hidezerovalue',
                    'position' => 3,
                    'default' => null,
                    'allow_null' => true,
                    'hidden' => !$isOffer,
                ],
                [
                    'name' => 'delivery_terms_id',
                    'label' => 'DeliveryTerms',
                    'type' => 'LIST',
                    'style' => 'long',
                    'list' => $this->deliveryTermsRepository->findAllNonDeleted(),
                    'position' => 1,
                    'default' => null,
                    'allow_null' => true
                ],
                [
                    'name' => 'delivery_method_id',
                    'label' => 'DeliveryMethod',
                    'type' => 'LIST',
                    'style' => 'long',
                    'list' => $this->deliveryMethodRepository->findAllNonDeleted(),
                    'position' => 2,
                    'default' => null,
                    'allow_null' => true
                ],
                [
                    'name' => 'delivery_address',
                    'label' => 'DeliveryAddress',
                    'type' => 'AREA',
                    'style' => 'wide',
                    'position' => 1,
                    'default' => null,
                    'allow_null' => true,
                ],
                [
                    'name' => 'info',
                    'label' => 'VisibleInfo',
                    'type' => 'AREA',
                    'style' => "wide$mdClass",
                    'position' => 1,
                    'attached_elem' => '<span class="select-default-text" data-type="info" data-target="info"></span>',
                    'default' => $defaultValues['info'],
                    'allow_null' => true,
                ],
                [
                    'name' => 'internal_info',
                    'label' => 'InternalInfo',
                    'type' => 'AREA',
                    'style' => "wide$mdClass",
                    'position' => 2,
                    'allow_null' => true
                ],
                [
                    'name' => 'foreword',
                    'label' => 'Foreword',
                    'type' => 'AREA',
                    'style' => "wide$mdClass",
                    'position' => 1,
                    'attached_elem' => '<span class="select-default-text" data-type="foreword" data-target="foreword"></span>',
                    'default' => $defaultValues['foreword'],
                    'allow_null' => true
                ],
                [
                    'name' => 'afterword',
                    'label' => 'Afterword',
                    'type' => 'AREA',
                    'style' => "wide$mdClass",
                    'position' => 2,
                    'attached_elem' => '<span class="select-default-text" data-type="afterword" data-target="afterword"></span>',
                    'default' => $defaultValues['afterword'],
                    'allow_null' => true
                ],
                [
                    'name' => $form . '_row',
                    'label' => 'InvRows',
                    'type' => 'IFORM',
                    'style' => 'xfull',
                    'position' => 0,
                    'allow_null' => true,
                    'parent_key' => 'invoice_id'
                ],
            ];

            $buttonGroups = [];

            if ($id && $writeAccess && !$isOffer && !$isTemplate) {
                $group1[] = [
                    'name' => 'refundinvoice',
                    'label' => 'RefundInvoice',
                    'route' => 'invoice-refund',
                    'routeArgs' => ['from' => $id],
                ];
            }
            if ($refundingInvoice = $invoice ? $this->invoiceRepository->getRefundingInvoice($invoice) : null) {
                $group1[] = [
                    'name' => 'get',
                    'label' => 'ShowRefundingInvoice',
                    'route' => 'invoice',
                    'routeArgs' => ['id' => $refundingInvoice->getId()],
                ];
            }
            if ($refundedInvoice = $invoice?->getRefundedInvoice()) {
                $group1[] = [
                    'name' => 'get',
                    'label' => 'ShowRefundedInvoice',
                    'route' => 'invoice',
                    'routeArgs' => ['id' => $refundedInvoice->getId()],
                    'position' => 2,
                    'allow_null' => true
                ];
            }

            if ($writeAccess && !$isOffer && !$isTemplate) {
                $group1[] = [
                    'name' => 'addreminderfees',
                    'label' => 'AddReminderFees',
                    'url' => '#',
                    'attrs' => [
                        'data-add-reminder-fees' => '1'
                    ],
                ];
                $group1[] = [
                    'name' => 'addpartialpayment',
                    'label' => 'AddPartialPayment',
                    'url' => '#',
                    'attrs' => [
                        'data-add-partial-payment' => '1'
                    ],
                ];
            }

            if ($group1) {
                $buttonGroups[] = [
                    'buttons' => $group1,
                ];
            }

            $group2 = [];
            if (!$isTemplate) {
                $printTemplates = $this->printTemplateRepository->findActiveByType($isOffer ? 'offer' : 'invoice');
                foreach ($printTemplates as $printTemplate) {
                    if (!$writeAccess) {
                        // Check if this print template is safe for read-only use
                        $printer = getInvoicePrinter($printTemplate->getFilename());
                        if (null === $printer || !$printer->getReadOnlySafe()) {
                            continue;
                        }
                    }
                    $templateId = $printTemplate->getId();
                    $printStyle = $printTemplate->getNewWindow() ? 'openwindow' : 'redirect';
                    $attrs = [];
                    $attrs['data-print-id'] = $templateId;
                    $attrs['data-print-style'] = $printStyle;

                    $group2[] = [
                        'name' => "print$templateId",
                        'label' => $printTemplate->getName(),
                        'url' => '#',
                        'attrs' => $attrs,
                    ];
                }
                if ($group2) {
                    $buttonGroups[] = [
                        'buttons' => $group2,
                        'overflow' => 5,
                        'overflowLabel' => 'PrintOther',
                    ];
                }
            }

            break;

        case 'invoice_row':
        case 'offer_row':
        case 'invoice_template_row':
            $strTable = "{$this->prefix}invoice_row";
            $strParentKey = 'invoice_id';

            $isTemplate = 'invoice_template_row' === $form;

            switch ($this->settingsManager->get('invoice_clear_row_values_after_add')) {
            case 0:
                break;
            case 1:
                $clearRowValuesAfterAdd = true;
                break;
            case 2:
                $onAfterRowAdded = 'MLInvoice.getSelectedProductDefaults(form_id);';
            }

            $astrFormElements = [
                [
                    'name' => 'id',
                    'label' => '',
                    'type' => 'HID_INT',
                    'style' => 'medium',
                    'position' => 0
                ],
                [
                    'name' => 'product_id',
                    'label' => 'Product',
                    'type' => 'SEARCHLIST',
                    'style' => 'medium translated',
                    'listquery' => 'table=product&sort=order_no,product_code,product_name',
                    'position' => 0,
                    'allow_null' => true,
                    'elem_attributes' => '_onChangeProduct'
                ],
                [
                    'name' => 'description',
                    'label' => 'RowDesc',
                    'type' => 'TEXT',
                    'style' => 'medium',
                    'position' => 0,
                    'allow_null' => true
                ],
                [
                    'name' => 'row_date',
                    'label' => 'Date',
                    'type' => 'INTDATE',
                    'style' => 'date',
                    'position' => 0,
                    'default' => $isTemplate ? null : 'DATE_NOW',
                    'allow_null' => true
                ],
                [
                    'name' => 'pcs',
                    'label' => 'PCS',
                    'type' => 'INT',
                    'style' => 'count',
                    'position' => 0
                ],
                [
                    'name' => 'type_id',
                    'label' => 'Unit',
                    'type' => 'LIST',
                    'style' => 'short translated',
                    'list' => $this->rowTypeRepository->findAllNonDeleted(),
                    'position' => 0,
                    'allow_null' => true
                ],
                [
                    'name' => 'price',
                    'label' => 'Price',
                    'type' => 'INT',
                    'style' => 'currency',
                    'position' => 0,
                    'decimals' => (int)$this->settingsManager->get('unit_price_decimals'),
                    'allow_null' => $isTemplate,
                ],
                [
                    'name' => 'discount',
                    'label' => 'DiscountPct',
                    'type' => 'INT',
                    'style' => 'currency',
                    'position' => 0,
                    'default' => 0,
                    'allow_null' => true
                ],
                [
                    'name' => 'discount_amount',
                    'label' => 'DiscountAmount',
                    'type' => 'INT',
                    'style' => 'currency',
                    'position' => 0,
                    'default' => 0,
                    'allow_null' => true,
                    'decimals' => (int)$this->settingsManager->get('unit_price_decimals')
                ],
                [
                    'name' => 'vat',
                    'label' => 'VAT',
                    'type' => 'INT',
                    'style' => 'currency',
                    'position' => 0,
                    'default' => $this->settingsManager->get('invoice_default_vat_percent'),
                    'allow_null' => false
                ],
                [
                    'name' => 'vat_included',
                    'label' => 'VATInc',
                    'type' => 'CHECK',
                    'style' => 'xshort',
                    'position' => 0,
                    'default' => 0,
                    'allow_null' => true
                ],
                [
                    'name' => 'order_no',
                    'label' => 'RowNr',
                    'type' => 'INT',
                    'style' => 'count',
                    'position' => 0,
                    'default' => $parentId ? $this->invoiceRowRepository->getNextAvailableOrderNo($parentId) : 1,
                    'allow_null' => true
                ],
                [
                    'name' => 'partial_payment',
                    'label' => 'PartialPayment',
                    'type' => 'HID_INT',
                    'style' => '',
                    'position' => 0,
                    'default' => 0,
                    'allow_null' => true
                ],
                [
                    'name' => 'row_sum',
                    'label' => 'RowTotal',
                    'type' => 'ROWSUM',
                    'style' => 'currency row-summary',
                    'position' => 0,
                    'decimals' => 2,
                    'allow_null' => true
                ]
            ];

            break;

        /* SYSTEM FORMS */
        case 'base':
            $strTable = "{$this->prefix}base";
            $addressAutocomplete = true;

            $baseId = $id ?? intval(getPostOrQuery('id'));
            $locTitle = $this->translator->translate('BaseLogoTitle');
            if ($baseId) { // TODO
                $openPopJS = <<<EOF
                MLInvoice.popupDialog('base_logo.php?func=edit&amp;id=$baseId', MLInvoice.updateBaseLogo, '$locTitle'); return false;
        EOF;
            } else {
                $openPopJS = '';
            }

            $imageElement = [
                'name' => 'logo',
                'label' => '',
                'type' => 'IMAGE',
                'listquery' => $this->getBaseLogoSize($baseId) ? "base_logo.php?func=view&amp;id=$baseId" : '', // TODO
                'style' => 'image',
                'position' => 0,
                'allow_null' => true
            ];
            $noImageElement = [
                'name' => 'no_logo',
                'label' => 'BaseLogoNotSet',
                'type' => 'LABEL',
                'position' => 0,
                'allow_null' => true
            ];
            if ($this->getBaseLogoSize($baseId)) {
                $noImageElement['style'] = 'hidden';
            } else {
                $imageElement['style'] .= ' hidden';
            }

            $astrFormElements = [
                [
                    'name' => 'name',
                    'label' => 'BaseName',
                    'type' => 'TEXT',
                    'style' => 'medium',
                    'position' => 1
                ],
                [
                    'name' => 'company_id',
                    'label' => 'ClientVATID',
                    'type' => 'TEXT',
                    'style' => 'medium',
                    'position' => 2,
                    'allow_null' => true
                ],
                [
                    'name' => 'inactive',
                    'label' => 'Inactive',
                    'type' => 'CHECK',
                    'style' => 'medium',
                    'position' => 1,
                    'default' => 0,
                    'allow_null' => true
                ],
                [
                    'name' => 'vat_registered',
                    'label' => 'VATRegistered',
                    'title' => 'VATRegisteredHint',
                    'type' => 'CHECK',
                    'style' => 'short',
                    'position' => 2,
                    'allow_null' => true
                ],
                [
                    'name' => 'org_unit_number',
                    'label' => 'OrgUnitNumber',
                    'type' => 'TEXT',
                    'style' => 'medium',
                    'position' => 1,
                    'allow_null' => true
                ],
                [
                    'name' => 'payment_intermediator',
                    'label' => 'PaymentIntermediator',
                    'type' => 'TEXT',
                    'style' => 'medium',
                    'position' => 2,
                    'allow_null' => true
                ],
                [
                    'name' => 'contact_person',
                    'label' => 'ContactPerson',
                    'type' => 'TEXT',
                    'style' => 'medium',
                    'position' => 1,
                    'allow_null' => true
                ],
                [
                    'name' => 'email',
                    'label' => 'Email',
                    'type' => 'TEXT',
                    'style' => 'medium',
                    'position' => 2,
                    'allow_null' => true
                ],
                [
                    'name' => 'street_address',
                    'label' => 'StreetAddr',
                    'type' => 'TEXT',
                    'style' => 'medium',
                    'position' => 1,
                    'allow_null' => true
                ],
                [
                    'name' => 'zip_code',
                    'label' => 'ZipCode',
                    'type' => 'TEXT',
                    'style' => 'medium',
                    'position' => 2,
                    'allow_null' => true
                ],
                [
                    'name' => 'city',
                    'label' => 'City',
                    'type' => 'TEXT',
                    'style' => 'medium',
                    'position' => 1,
                    'allow_null' => true
                ],
                [
                    'name' => 'country',
                    'label' => 'Country',
                    'type' => 'TEXT',
                    'style' => 'medium',
                    'position' => 2,
                    'allow_null' => true
                ],
                [
                    'name' => 'phone',
                    'label' => 'Phone',
                    'type' => 'TEXT',
                    'style' => 'medium',
                    'position' => 1,
                    'allow_null' => true
                ],
                [
                    'name' => 'www',
                    'label' => 'WWW',
                    'type' => 'TEXT',
                    'style' => 'medium',
                    'position' => 2,
                    'allow_null' => true
                ],
                [
                    'name' => 'payment_recipient_name',
                    'label' => 'InvoicePaymentRecipientName',
                    'type' => 'TEXT',
                    'style' => 'medium',
                    'position' => 1,
                    'allow_null' => true
                ],
                [
                    'name' => 'banksep1',
                    'label' => 'FirstBank',
                    'type' => 'HEADING'
                ],
                [
                    'name' => 'bank_name',
                    'label' => 'Bank',
                    'type' => 'TEXT',
                    'style' => 'medium',
                    'position' => 1
                ],
                [
                    'name' => 'bank_account',
                    'label' => 'Account',
                    'type' => 'TEXT',
                    'style' => 'medium',
                    'position' => 2,
                    'allow_null' => true
                ],
                [
                    'name' => 'bank_iban',
                    'label' => 'AccountIBAN',
                    'type' => 'TEXT',
                    'style' => 'medium',
                    'position' => 1
                ],
                [
                    'name' => 'bank_swiftbic',
                    'label' => 'SWIFTBIC',
                    'type' => 'TEXT',
                    'style' => 'medium',
                    'position' => 2
                ],
                [
                    'name' => 'banksep2',
                    'label' => 'SecondBank',
                    'type' => 'HEADING'
                ],
                [
                    'name' => 'bank_name2',
                    'label' => 'Bank',
                    'type' => 'TEXT',
                    'style' => 'medium',
                    'position' => 1,
                    'allow_null' => true
                ],
                [
                    'name' => 'bank_account2',
                    'label' => 'Account',
                    'type' => 'TEXT',
                    'style' => 'medium',
                    'position' => 2,
                    'allow_null' => true
                ],
                [
                    'name' => 'bank_iban2',
                    'label' => 'AccountIBAN',
                    'type' => 'TEXT',
                    'style' => 'medium',
                    'position' => 1,
                    'allow_null' => true
                ],
                [
                    'name' => 'bank_swiftbic2',
                    'label' => 'SWIFTBIC',
                    'type' => 'TEXT',
                    'style' => 'medium',
                    'position' => 2,
                    'allow_null' => true
                ],
                [
                    'name' => 'banksep3',
                    'label' => 'ThirdBank',
                    'type' => 'HEADING'
                ],
                [
                    'name' => 'bank_name3',
                    'label' => 'Bank',
                    'type' => 'TEXT',
                    'style' => 'medium',
                    'position' => 1,
                    'allow_null' => true
                ],
                [
                    'name' => 'bank_account3',
                    'label' => 'Account',
                    'type' => 'TEXT',
                    'style' => 'medium',
                    'position' => 2,
                    'allow_null' => true
                ],
                [
                    'name' => 'bank_iban3',
                    'label' => 'AccountIBAN',
                    'type' => 'TEXT',
                    'style' => 'medium',
                    'position' => 1,
                    'allow_null' => true
                ],
                [
                    'name' => 'bank_swiftbic3',
                    'label' => 'SWIFTBIC',
                    'type' => 'TEXT',
                    'style' => 'medium',
                    'position' => 2,
                    'allow_null' => true
                ],
                [
                    'name' => 'invoicesep',
                    'label' => 'BaseInvoiceTexts',
                    'type' => 'HEADING'
                ],
                [
                    'name' => 'invoice_default_info',
                    'label' => 'InvoiceDefaultInfo',
                    'type' => 'AREA',
                    'style' => "large$mdClass",
                    'position' => 1,
                    'allow_null' => true
                ],
                [
                    'name' => 'invoice_default_foreword',
                    'label' => 'InvoiceDefaultForeword',
                    'type' => 'AREA',
                    'style' => "large$mdClass",
                    'position' => 1,
                    'allow_null' => true
                ],
                [
                    'name' => 'invoice_default_afterword',
                    'label' => 'InvoiceDefaultAfterword',
                    'type' => 'AREA',
                    'style' => "large$mdClass",
                    'position' => 2,
                    'allow_null' => true
                ],
                [
                    'name' => 'offer_default_foreword',
                    'label' => 'OfferDefaultForeword',
                    'type' => 'AREA',
                    'style' => "large$mdClass",
                    'position' => 1,
                    'allow_null' => true
                ],
                [
                    'name' => 'offer_default_afterword',
                    'label' => 'OfferDefaultAfterword',
                    'type' => 'AREA',
                    'style' => "large$mdClass",
                    'position' => 2,
                    'allow_null' => true
                ],
                [
                    'name' => 'terms_of_payment',
                    'label' => 'SettingInvoiceTermsOfPayment',
                    'type' => 'TEXT',
                    'style' => 'long',
                    'position' => 1,
                    'allow_null' => true
                ],
                [
                    'name' => 'period_for_complaints',
                    'label' => 'SettingInvoicePeriodForComplaints',
                    'type' => 'TEXT',
                    'style' => 'long',
                    'position' => 2,
                    'allow_null' => true
                ],
                [
                    'name' => 'emailsep',
                    'label' => 'BaseEmailTitle',
                    'type' => 'HEADING'
                ],
                [
                    'name' => 'invoice_email_from',
                    'label' => 'BaseEmailFrom',
                    'type' => 'TEXT',
                    'style' => 'medium',
                    'position' => 1,
                    'allow_null' => true
                ],
                [
                    'name' => 'invoice_email_bcc',
                    'label' => 'BaseEmailBCC',
                    'type' => 'TEXT',
                    'style' => 'medium',
                    'position' => 2,
                    'allow_null' => true
                ],
                [
                    'name' => 'invoice_email_subject',
                    'label' => 'BaseInvoiceEmailSubject',
                    'type' => 'TEXT',
                    'style' => 'long',
                    'position' => 0,
                    'allow_null' => true
                ],
                [
                    'name' => 'invoice_email_body',
                    'label' => 'BaseInvoiceEmailBody',
                    'type' => 'AREA',
                    'style' => 'email email_body',
                    'position' => 0,
                    'allow_null' => true
                ],
                [
                    'name' => 'receipt_email_subject',
                    'label' => 'BaseReceiptEmailSubject',
                    'type' => 'TEXT',
                    'style' => 'long',
                    'position' => 0,
                    'allow_null' => true
                ],
                [
                    'name' => 'receipt_email_body',
                    'label' => 'BaseReceiptEmailBody',
                    'type' => 'AREA',
                    'style' => 'email email_body',
                    'position' => 0,
                    'allow_null' => true
                ],
                [
                    'name' => 'order_confirmation_email_subject',
                    'label' => 'BaseOrderConfirmationEmailSubject',
                    'type' => 'TEXT',
                    'style' => 'long',
                    'position' => 0,
                    'allow_null' => true
                ],
                [
                    'name' => 'order_confirmation_email_body',
                    'label' => 'BaseOrderConfirmationEmailBody',
                    'type' => 'AREA',
                    'style' => 'email email_body',
                    'position' => 0,
                    'allow_null' => true
                ],
                [
                    'name' => 'offer_email_subject',
                    'label' => 'BaseOfferEmailSubject',
                    'type' => 'TEXT',
                    'style' => 'long',
                    'position' => 0,
                    'allow_null' => true
                ],
                [
                    'name' => 'offer_email_body',
                    'label' => 'BaseOfferEmailBody',
                    'type' => 'AREA',
                    'style' => 'email email_body',
                    'position' => 0,
                    'allow_null' => true
                ],
                [
                    'name' => 'logosep',
                    'label' => 'BaseLogoTitle',
                    'type' => 'HEADING'
                ],
                $imageElement,
                $noImageElement,
                $openPopJS ? [
                    'name' => 'edit_logo',
                    'label' => 'BaseChangeImage',
                    'type' => 'JSBUTTON',
                    'style' => 'medium',
                    'listquery' => $openPopJS,
                    'position' => 1,
                    'allow_null' => true
                ] : [
                    'name' => 'edit_logo',
                    'label' => 'SaveRecordFirst',
                    'type' => 'LABEL',
                    'style' => 'medium',
                    'position' => 1,
                    'allow_null' => true
                ],
                [
                    'name' => 'logo_left',
                    'label' => 'BaseLogoLeft',
                    'type' => 'INT',
                    'style' => 'measurement',
                    'position' => 0,
                    'allow_null' => true
                ],
                [
                    'name' => 'logo_top',
                    'label' => 'BaseLogoTop',
                    'type' => 'INT',
                    'style' => 'measurement',
                    'position' => 0,
                    'allow_null' => true
                ],
                [
                    'name' => 'logo_width',
                    'label' => 'BaseLogoWidth',
                    'type' => 'INT',
                    'style' => 'measurement',
                    'position' => 0,
                    'allow_null' => true
                ],
                [
                    'name' => 'send_api_config',
                    'label' => 'SendAPISettings',
                    'type' => 'IFORM',
                    'style' => 'full',
                    'position' => 0,
                    'allow_null' => true,
                    'parent_key' => 'base_id'
                ]
            ];
            break;

        case 'send_api_config':
            $strTable = "{$this->prefix}send_api_config";
            $strParentKey = 'base_id';
            $clearRowValuesAfterAdd = true;
            $astrFormElements = [
                [
                    'name' => 'id',
                    'label' => '',
                    'type' => 'HID_INT',
                    'style' => 'medium',
                    'position' => 0
                ],
                [
                    'name' => 'name',
                    'label' => 'DisplayName',
                    'type' => 'TEXT',
                    'style' => 'medium',
                    'position' => 0,
                    'allow_null' => true
                ],
                [
                    'name' => 'method',
                    'label' => 'APIName',
                    'type' => 'LIST',
                    'style' => 'medium translated',
                    'listquery' => [
                        'postita.fi' => 'Postita.fi',
                        'ServerDirectory' => 'ServerDirectory',
                    ],
                    'position' => 0,
                    'allow_null' => false,
                    'elem_attributes' => 'data-api-method'
                ],
                [
                    'name' => 'username',
                    'label' => 'UserNameOrID',
                    'type' => 'TEXT',
                    'style' => 'medium',
                    'position' => 0,
                    'allow_null' => true,
                    'elem_attributes' => 'data-enabled="postita.fi"'
                ],
                [
                    'name' => 'password',
                    'label' => 'PasswordOrKey',
                    'type' => 'PASSWD_STORED',
                    'style' => 'medium',
                    'position' => 0,
                    'allow_null' => true,
                    'elem_attributes' => 'data-enabled="postita.fi"'
                ],
                [
                    'name' => 'reference',
                    'label' => 'ReferenceOrUnitID',
                    'type' => 'TEXT',
                    'style' => 'medium',
                    'position' => 0,
                    'allow_null' => true,
                    'elem_attributes' => 'data-enabled="postita.fi"'
                ],
                [
                    'name' => 'directory',
                    'label' => 'Directory',
                    'type' => 'TEXT',
                    'style' => 'medium',
                    'position' => 0,
                    'allow_null' => true,
                    'elem_attributes' => 'data-enabled="ServerDirectory"'
                ],
                [
                    'name' => 'post_class',
                    'label' => 'PostalClass',
                    'type' => 'LIST',
                    'style' => 'medium translated noemptyvalue',
                    'listquery' => [
                        '0' => 'Unspecified',
                        '1' => 'FirstClassBW',
                        '2' => 'SecondClassBW',
                        '3' => 'FirstClassColor',
                        '4' => 'SecondClassColor'
                    ],
                    'position' => 0,
                    'allow_null' => false,
                    'elem_attributes' => 'data-enabled="postita.fi"'
                ],
                [
                    'name' => 'add_to_queue',
                    'label' => 'SendToQueue',
                    'type' => 'CHECK',
                    'style' => 'short',
                    'position' => 0,
                    'elem_attributes' => 'data-enabled="postita.fi"'
                ],
                [
                    'name' => 'finvoice_mail_backup',
                    'label' => 'FinvoiceMailBackup',
                    'type' => 'CHECK',
                    'style' => 'short',
                    'position' => 0,
                    'elem_attributes' => 'data-enabled="postita.fi"'
                ]
            ];
            break;

        case 'invoice_state':
            $levelsAllowed = [
                MLINVOICE_USER_ROLE_ADMIN
            ];
            $strTable = "{$this->prefix}invoice_state";

            $intId = $id ?? getPostOrQuery('id');
            $readOnly = ($intId && $intId <= 8);
            $astrFormElements = [
                [
                    'name' => 'name',
                    'label' => 'Status',
                    'type' => 'TEXT',
                    'style' => 'medium',
                    'position' => 1,
                    'read_only' => $readOnly
                ],
                [
                    'name' => 'order_no',
                    'label' => 'OrderNr',
                    'type' => 'INT',
                    'style' => 'short',
                    'position' => 2,
                    'read_only' => $readOnly
                ],
                [
                    'name' => 'invoice_open',
                    'label' => 'InvoiceStatusOpen',
                    'type' => 'CHECK',
                    'style' => 'short',
                    'position' => 1
                ],
                [
                    'name' => 'invoice_unpaid',
                    'label' => 'InvoiceStatusUnpaid',
                    'type' => 'CHECK',
                    'style' => 'short',
                    'position' => 2
                ],
                [
                    'name' => 'invoice_offer',
                    'label' => 'InvoiceStatusOffer',
                    'type' => 'CHECK',
                    'style' => 'short',
                    'position' => 1
                ]

            ];
            break;

        case 'invoice_type':
            $levelsAllowed = [
                MLINVOICE_USER_ROLE_ADMIN
            ];
            $strTable = "{$this->prefix}invoice_type";

            $intId = $id ?? getPostOrQuery('id');
            $astrFormElements = [
                [
                    'name' => 'identifier',
                    'label' => 'Identifier',
                    'type' => 'TEXT',
                    'style' => 'medium',
                    'position' => 1
                ],
                [
                    'name' => 'name',
                    'label' => 'Name',
                    'type' => 'TEXT',
                    'style' => 'medium',
                    'position' => 1
                ],
                [
                    'name' => 'order_no',
                    'label' => 'OrderNr',
                    'type' => 'INT',
                    'style' => 'short',
                    'position' => 2,
                    'listquery' => "SELECT max(order_no)+5 FROM {$this->prefix}invoice_type WHERE deleted=0",
                ]
            ];
            break;

        case 'row_type':
            $levelsAllowed = [
                MLINVOICE_USER_ROLE_ADMIN
            ];
            $strTable = "{$this->prefix}row_type";

            $astrFormElements = [
                [
                    'name' => 'name',
                    'label' => 'RowType',
                    'type' => 'TEXT',
                    'style' => 'medium',
                    'position' => 1
                ],
                [
                    'name' => 'order_no',
                    'label' => 'OrderNr',
                    'type' => 'INT',
                    'style' => 'short',
                    'position' => 2
                ]
            ];
            break;

        case 'session_type':
            $levelsAllowed = [
                MLINVOICE_USER_ROLE_ADMIN
            ];
            $strTable = "{$this->prefix}session_type";

            $intId = getPostOrQuery('id');
            if ($intId && $intId <= 4) {
                $readOnlyForm = true;
            }
            $astrFormElements = [
                [
                    'name' => 'name',
                    'label' => 'SessionType',
                    'type' => 'TEXT',
                    'style' => 'medium',
                    'position' => 1
                ],
                [
                    'name' => 'order_no',
                    'label' => 'OrderNr',
                    'type' => 'INT',
                    'style' => 'short',
                    'position' => 2
                ],
                [
                    'name' => 'access_level',
                    'label' => 'AccessLevel',
                    'type' => 'INT',
                    'style' => 'short',
                    'position' => 1,
                    'default' => 1
                ]
            ];
            break;

        case 'delivery_terms':
            $levelsAllowed = [
                MLINVOICE_USER_ROLE_ADMIN
            ];
            $strTable = "{$this->prefix}delivery_terms";

            $astrFormElements = [
                [
                    'name' => 'name',
                    'label' => 'DeliveryTerms',
                    'type' => 'TEXT',
                    'style' => 'medium',
                    'position' => 1
                ],
                [
                    'name' => 'order_no',
                    'label' => 'OrderNr',
                    'type' => 'INT',
                    'style' => 'short',
                    'position' => 2
                ]
            ];
            break;

        case 'delivery_method':
            $levelsAllowed = [
                MLINVOICE_USER_ROLE_ADMIN
            ];
            $strTable = "{$this->prefix}delivery_method";

            $astrFormElements = [
                [
                    'name' => 'name',
                    'label' => 'DeliveryMethod',
                    'type' => 'TEXT',
                    'style' => 'medium',
                    'position' => 1
                ],
                [
                    'name' => 'order_no',
                    'label' => 'OrderNr',
                    'type' => 'INT',
                    'style' => 'short',
                    'position' => 2
                ]
            ];
            break;

        case 'default_value':
            $strTable = "{$this->prefix}default_value";

            $astrFormElements = [
                [
                    'name' => 'type',
                    'label' => 'DefaultValueType',
                    'type' => 'LIST',
                    'style' => 'medium translated',
                    'listquery' => [
                        'info' => 'Info',
                        'foreword' => 'Foreword',
                        'afterword' => 'Afterword',
                        'email' => 'Email'
                    ],
                    'position' => 1
                ],
                [
                    'name' => 'name',
                    'label' => 'Name',
                    'type' => 'TEXT',
                    'style' => 'medium',
                    'position' => 1
                ],
                [
                    'name' => 'order_no',
                    'label' => 'OrderNr',
                    'type' => 'INT',
                    'style' => 'short',
                    'position' => 2,
                    'listquery' => "SELECT max(order_no)+5 FROM {$this->prefix}default_value WHERE deleted=0",
                    'default' => 'ADD+5'
                ],
                [
                    'name' => 'content',
                    'label' => 'Content',
                    'type' => 'AREA',
                    'style' => "xxlarge$mdClass",
                    'position' => 0
                ],
                [
                    'name' => 'additional',
                    'label' => 'AddInfo',
                    'type' => 'AREA',
                    'style' => 'xxlarge',
                    'position' => 0,
                    'allow_null' => true
                ]
            ];
            break;

        case 'attachment':
            $strTable = "{$this->prefix}attachment";

            $intId = (int)($id ?? getPostOrQuery('id', '0'));
            if ($intId) {
                $showAttachment = $this->translator->translate('ShowAttachment');
                $extraButtons = <<<EOT
            <a role="button" class="btn btn-secondary" href="attachment.php?id=$intId" target="_blank">$showAttachment</a>

        EOT;
            }

            $astrFormElements = [
                [
                    'name' => 'name',
                    'label' => 'Name',
                    'type' => 'TEXT',
                    'style' => 'medium',
                    'position' => 1,
                    'allow_null' => true
                ],
                [
                    'name' => 'order_no',
                    'label' => 'OrderNr',
                    'type' => 'INT',
                    'style' => 'short',
                    'position' => 2,
                    'listquery' => "SELECT max(order_no)+5 FROM {$this->prefix}attachment",
                    'default' => 'ADD+5'
                ],
                [
                    'name' => 'description',
                    'label' => 'Description',
                    'type' => 'AREA',
                    'style' => 'medium',
                    'position' => 1,
                    'allow_null' => true
                ],
                [
                    'name' => 'date',
                    'label' => 'Date',
                    'type' => 'INTDATE',
                    'style' => 'date',
                    'position' => 2,
                    'default' => 'DATE_NOW',
                    'allow_null' => false,
                    'read_only' => true
                ],
                [
                    'name' => 'filedata',
                    'label' => $this->translator->Translate(
                        'FileWithSize',
                        ['%%maxsize%%' => fileSizeToHumanReadable(getMaxUploadSize())]
                    ),
                    'type' => 'FILE',
                    'style' => 'long',
                    'position' => 1,
                    'mimetypes' => [
                        'application/pdf',
                        'image/jpeg',
                        'image/png'
                    ]
                ]
            ];
            break;

        case 'invoice_attachment':
            $strTable = "{$this->prefix}invoice_attachment";
            $strParentKey = 'invoice_id';

            $astrFormElements = [
                [
                    'name' => 'invoice_id',
                    'label' => 'InvoiceId',
                    'type' => 'INT',
                    'style' => 'medium',
                    'position' => 1,
                    'allow_null' => false
                ],
                [
                    'name' => 'name',
                    'label' => 'Name',
                    'type' => 'TEXT',
                    'style' => 'medium',
                    'position' => 1,
                    'allow_null' => true
                ],
                [
                    'name' => 'order_no',
                    'label' => 'OrderNr',
                    'type' => 'INT',
                    'style' => 'short',
                    'position' => 2,
                    'listquery' => "SELECT max(order_no)+5 FROM {$this->prefix}attachment",
                    'default' => 'ADD+5'
                ],
                [
                    'name' => 'description',
                    'label' => 'Description',
                    'type' => 'AREA',
                    'style' => 'medium',
                    'position' => 1,
                    'allow_null' => true
                ],
                [
                    'name' => 'date',
                    'label' => 'Date',
                    'type' => 'INTDATE',
                    'style' => 'date',
                    'position' => 2,
                    'default' => 'DATE_NOW',
                    'allow_null' => false,
                    'read_only' => true
                ],
                [
                    'name' => 'filedata',
                    'label' => $this->translator->Translate(
                        'FileWithSize',
                        ['%%maxsize%%' => fileSizeToHumanReadable(getMaxUploadSize())]
                    ),
                    'type' => 'FILE',
                    'style' => 'long',
                    'position' => 1,
                    'mimetypes' => [
                        'application/pdf',
                        'image/jpeg',
                        'image/png'
                    ]
                ],
                [
                    'name' => 'send',
                    'label' => 'Send',
                    'type' => 'INT',
                    'style' => 'medium',
                    'position' => 1,
                    'allow_null' => false,
                    'default' => 0
                ]
            ];
            break;

        case 'user':
            $levelsAllowed = [
                MLINVOICE_USER_ROLE_ADMIN
            ];
            $strTable = "{$this->prefix}users";
            $astrFormElements = [
                [
                    'name' => 'name',
                    'label' => 'UserName',
                    'type' => 'TEXT',
                    'style' => 'medium',
                    'position' => 1
                ],
                [
                    'name' => 'email',
                    'label' => 'Email',
                    'type' => 'TEXT',
                    'style' => 'medium',
                    'position' => 2
                ],
                [
                    'name' => 'login',
                    'label' => 'LoginName',
                    'type' => 'TEXT',
                    'style' => 'medium',
                    'position' => 1,
                    'unique' => true
                ],
                [
                    'name' => 'passwd',
                    'label' => 'Password',
                    'type' => 'PASSWD',
                    'style' => 'medium',
                    'position' => 2,
                    'allow_null' => true
                ],
                [
                    'name' => 'type_id',
                    'label' => 'Type',
                    'type' => 'LIST',
                    'style' => 'long translated',
                    'list' => $this->sessionTypeRepository->findAllNonDeleted(),
                    'position' => 0
                ]
            ];
            break;

        case 'print_template':
            $strTable = "{$this->prefix}print_template";

            $elem_attributes = '';
            $astrFormElements = [
                [
                    'name' => 'type',
                    'label' => 'PrintTemplateType',
                    'type' => 'LIST',
                    'style' => 'medium noemptyvalue translated',
                    'listquery' => [
                        'invoice' => 'PrintTemplateTypeInvoice',
                        'offer' => 'PrintTemplateTypeOffer'
                    ],
                    'position' => 1
                ],
                [
                    'name' => 'order_no',
                    'label' => 'OrderNr',
                    'type' => 'INT',
                    'style' => 'short',
                    'position' => 2
                ],
                [
                    'name' => 'name',
                    'label' => 'PrintTemplateName',
                    'type' => 'TEXT',
                    'style' => 'medium',
                    'position' => 1
                ],
                [
                    'name' => 'filename',
                    'label' => 'PrintTemplateFileName',
                    'type' => 'TEXT',
                    'style' => 'medium',
                    'position' => 2,
                    'allow_null' => true
                ],
                [
                    'name' => 'parameters',
                    'label' => 'PrintTemplateParameters',
                    'type' => 'TEXT',
                    'style' => 'medium',
                    'position' => 1,
                    'allow_null' => true
                ],
                [
                    'name' => 'output_filename',
                    'label' => 'PrintTemplateOutputFileName',
                    'type' => 'TEXT',
                    'style' => 'medium',
                    'position' => 2,
                    'allow_null' => true
                ],
                [
                    'name' => 'new_window',
                    'label' => 'PrintTemplateOpenInNewWindow',
                    'type' => 'CHECK',
                    'style' => 'medium',
                    'position' => 1,
                    'allow_null' => true
                ],
                [
                    'name' => 'inactive',
                    'label' => 'PrintTemplateInactive',
                    'type' => 'CHECK',
                    'style' => 'medium',
                    'position' => 2,
                    'default' => 0,
                    'allow_null' => true
                ]
            ];
            break;
        default:
            throw new \Exception("Invalid form: $form");
        }

        // Clean up the array
        $akeys = [
            'type',
            'position',
            'style',
            'label',
            'parent_key',
            'listquery',
            'allow_null',
            'elem_attributes'
        ];
        foreach ($astrFormElements as &$element) {
            if (!isset($element['name'])) {
                throw new \Exception('Element must have a name');
            }
            foreach ($akeys as $key) {
                if (!isset($element[$key])) {
                    $element[$key] = false;
                }
            }
        }

        $fields = [];
        $hiddenFields = [];
        $childFormField = null;
        foreach ($astrFormElements as $field) {
            if (str_starts_with($field['type'], 'HID_')) {
                $hiddenFields[$field['name']] = $field;
            } elseif ($field['type'] === 'IFORM') {
                $childFormField = $field;
            } else {
                $fields[$field['name']] = $field;
            }
        }
        // Admin always allowed:
        if (!in_array(MLINVOICE_USER_ROLE_ADMIN, $levelsAllowed)) {
            $levelsAllowed[] = MLINVOICE_USER_ROLE_ADMIN;
        }

        $childFormConfig = $childFormField
            ? $this->getFormConfig($childFormField['name'], null, $request, $forSearch, $id)
            : null;

        $formConfig = [
            'type' => $form,
            'title' => $locTitle ?? '',
            'readOnly' => $readOnlyForm,
            'accessLevels' => $levelsAllowed,
            'table' => $strTable,
            'parentKey' => $strParentKey ?? null,
            'tableAlias' => $strListTableAlias,
            'copyLink' => $copyLinkOverride,
            'extraButtons' => $extraButtons,
            'fields' => $fields,
            'hiddenFields' => $hiddenFields,
            'dataAttrs' => $formDataAttrs,
            'searchFields' => $astrSearchFields ?? null,
            'addressAutocomplete' => $addressAutocomplete && $this->settingsManager->get('address_autocomplete'),
            'clearAfterRowAdded' => $clearRowValuesAfterAdd,
            'onAfterRowAdded' => $onAfterRowAdded,
            'popupHTML' => $popupHTML ?? '',
            'buttonGroups' => $buttonGroups ?? [],
            'inputFieldTypes' => $inputFieldTypes,
            'searchFieldTypes' => $searchFieldTypes,
            'childFormField' => $childFormField,
            'childFormConfig' => $childFormConfig,
        ];

        // Create form JS configuration:
        $mainFormConfig = [
            'type' => $formConfig['type'],
            'id' => $id,
            'readOnly' => $formConfig['readOnly']
        ];
        foreach ($formConfig['fields'] as $field) {
            $new = [
                'type' => $field['type'],
                'name' => $field['name'],
                'label' => $field['label'],
                'allow_null' => $field['allow_null']
            ];
            if (isset($field['default'])) {
                $new['default'] = $field['default'];
            }
            $mainFormConfig['fields'][] = $new;
        }

        $subFormConfig = [];
        $listItems = [];
        if ($childFormConfig) {
            $subFormConfig = [
                'type' => $childFormConfig['type'],
                'parentKey' => $childFormConfig['parentKey'],
                'onAfterRowAdded' => $childFormConfig['onAfterRowAdded'],
                'clearAfterRowAdded' => $childFormConfig['clearAfterRowAdded'],
                'dispatchByDateButtons' => $this->settingsManager->get('invoice_show_dispatch_dates'),
                'popupWidth' => 'send_api_config' === $childFormConfig['type'] ? 1200 : 1050,
            ];

            foreach ($childFormConfig['fields'] as $subElem) {
                $new = [
                    'type' => $subElem['type'],
                    'name' => $subElem['name'],
                    'style' => $subElem['style'],
                    'label' => $subElem['label'],
                    'allow_null' => $subElem['allow_null'],
                ];
                if (isset($subElem['default'])) {
                    $new['default'] = $subElem['default'];
                }
                if (isset($subElem['decimals'])) {
                    $new['decimals'] = $subElem['decimals'];
                }
                $subFormConfig['fields'][] = $new;

                if ($subElem['type'] != 'LIST') {
                    continue;
                }
                $list = $subElem['list'] ?? $subElem['listquery'];
                if (is_array($list)) {
                    $values = $list;
                } else {
                    throw new Exception('Invalid list/listquery for ' . $subElem['name']);
                }
                $translate = strstr($subElem['style'], ' translated');
                $items = [
                    '0' => '-'
                ];
                foreach ($values as $key => $value) {
                    if ($value instanceof EntityInterface) {
                        $key = $value->getId();
                        $value = $value->getName();
                    }
                    if ($translate) {
                        $value = $this->translator->translate($value);
                    }
                    $items[$key] = $value;
                }
                $listItems[$subElem['name']] = $items;
            }
        }

        $mainFormConfig['modificationWarning'] = '';
        if ('invoice' === $form && $invoice && !$invoice->getState()->getOpen()) {
            $mainFormConfig['modificationWarning'] = $this->translator->translate('NonOpenInvoiceModificationWarning');
        }

        $formConfig['js'] = compact('mainFormConfig', 'subFormConfig', 'listItems');

        return $this->cache[$cacheKey] = $formConfig;
    }

    /**
     * Get invoice interval options
     *
     * N.B. Update copy_invoice accordingly too!
     *
     * @return array
     */
    public function geInvoiceIntervalOptions(): array
    {
        $intervalOptions = [
            '0' => $this->translator->translate('InvoiceIntervalNone'),
            '2' => $this->translator->translate('InvoiceIntervalMonth'),
            '3' => $this->translator->translate('InvoiceIntervalYear')
        ];
        for ($i = 2; $i <= 6; $i++) {
            $intervalOptions[(string)($i + 2)]
                = str_replace('%d', (string)$i, $this->translator->translate('InvoiceIntervalMonths'));
        }
        // We don't currently have 7-11 months, but leave room for them in keys 9 - 13 just in case!
        for ($i = 2; $i <= 3; $i++) {
            $intervalOptions[(string)($i + 12)]
                = str_replace('%d', (string)$i, $this->translator->translate('InvoiceIntervalYears'));
        }
        return $intervalOptions;
    }

    /**
     * Save form data.
     *
     * If primaryKey is not set, add a new record and set it, otherwise update existing
     * record.
     * Return true on success. Return false on conflict or a string of missing values if
     * encountered. In these cases, the record is not saved.
     *
     * @param string $table         Table name
     * @param int    $primaryKey    Primary key value
     * @param array  $formConfig    Form configuration
     * @param array  $values        Values
     * @param array  $warnings      Any warnings encountered
     * @param string $parentKeyName Parent key field name, if any
     * @param ?int    $parentKey     Parent key value, if any
     * @param bool   $onPrint       Whether the save is happening on print
     * @param bool   $partial       Whether values contain only updated fields
     *
     * @return mixed
     *
     * @todo Convert to use ORM
     */
    public function saveFormData($table, &$primaryKey, $formConfig, &$values, &$warnings,
        $parentKeyName = '', $parentKey = null, $onPrint = false, $partial = false
    ) {
        $missingValues = '';
        $fields = [];
        $insert = [];
        $updateFields = [];
        $arrValues = [];

        if (!isset($primaryKey) || !$primaryKey) {
            if ($partial) {
                $warnings = 'Unable to do partial update without ID';
                return false;
            }
            unset($values['id']);
        }

        if ($partial) {
            $origValues = [];
            $res = $this->fetchRecord($table, $primaryKey, $formConfig['fields'], $origValues);
            if ('notfound' === $res) {
                $warnings = "Row $primaryKey not found";
                return false;
            }
            foreach ($origValues as $key => $value) {
                if (!isset($values[$key])) {
                    $values[$key] = $origValues[$key];
                }
            }
            unset($values['id']);
        }

        foreach ($formConfig['fields'] as $elem) {
            $type = $elem['type'];

            if (!in_array($type, $formConfig['inputFieldTypes'])
                || ($elem['read_only'] ?? false)
            ) {
                continue;
            }

            $name = $elem['name'];
            if ($type !== 'FILE') {
                if (!$elem['allow_null']
                    && (!isset($values[$name]) || $values[$name] === '')
                ) {
                    if (array_key_exists('default', $elem)) {
                        $values[$name] = $this->getFormDefaultValue($elem, $parentKey);
                    }
                    if (!isset($values[$name]) || $values[$name] === '') {
                        if ($missingValues) {
                            $missingValues .= ', ';
                        }
                        $missingValues .= $this->translator->translate($elem['label']);
                        continue;
                    }
                }
            } else {
                if (!$elem['allow_null'] && !$primaryKey && !isset($_FILES[$name])) {
                    if ($missingValues) {
                        $missingValues .= ', ';
                    }
                    $missingValues .= $this->translator->translate($elem['label']);
                    continue;
                }
            }

            if ('FILE' !== $type) {
                if (array_key_exists($name, $values)) {
                    if (empty($primaryKey) && '' === $values[$name]) {
                        $value = $this->getFormDefaultValue($elem, $parentKey);
                    } else {
                        $value = $values[$name];
                    }
                } else {
                    if (isset($primaryKey) && $primaryKey != 0) {
                        continue;
                    }
                    $value = $this->getFormDefaultValue($elem, $parentKey);
                }
            }

            if (($type == 'PASSWD' || $type == 'PASSWD_STORED') && !$value) {
                continue; // Don't save empty password
            }

            if ('TAGS' === $type) {
                // Tags are processed separately
                continue;
            }

            if (isset($elem['unique']) && $elem['unique']) {
                $query = "SELECT * FROM $table WHERE deleted=0 AND $name=?";
                $params = [
                    $value
                ];
                if (isset($primaryKey) && $primaryKey) {
                    $query .= ' AND id!=?';
                    $params[] = $primaryKey;
                }
                $checkRows = $this->entityManager->getConnection()->executeQuery($query, $params)->fetchOne();
                if ($checkRows) {
                    $warnings = str_replace(
                        '%s',
                        $this->translator->translate($elem['label']),
                        $this->translator->translate('DuplicateValue')
                    );
                    return false;
                }
            }

            switch ($type) {
            case 'PASSWD':
                $arrValues[] = password_hash($values[$name], PASSWORD_DEFAULT);
                break;
            case 'PASSWD_STORED':
                $arrValues[] = $this->crypt->encrypt($values[$name]);
                break;
            case 'INT':
            case 'HID_INT':
            case 'LIST':
            case 'SEARCHLIST':
                $converted = isset($values[$name])
                    ? ($value !== '' && $value !== null ? str_replace(',', '.', (string)$value) : null)
                    : null;
                $arrValues[] = null !== $converted
                    ? (($elem['decimals'] ?? 0) ? (float)$converted : (int)$converted)
                    : null;
                break;
            case 'CHECK':
                $arrValues[] = $value && 'false' !== $value ? 1 : 0;
                break;
            case 'INTDATE':
                if ($value) {
                    $converted = $this->dateUtils->ymdToDbDate($value);
                    if (null === $converted) {
                        $warnings = $this->translator->translate('ErrInvalidValue') . ': '
                            . $this->translator->translate($elem['label']);
                        return false;
                    }
                    $arrValues[] = $converted;
                } else {
                    $arrValues[] = null;
                }
                break;
            case 'FILE':
                if (!isset($_FILES[$name])) {
                    continue 2;
                }
                if ($_FILES[$name]['error'] != UPLOAD_ERR_OK) {
                    $warnings = $this->translator->translate('ErrFileUploadFailed')
                        . ' (' . $_FILES[$name]['error'] . ')';
                    return false;
                }

                $mimetype = getMimeType(
                    $_FILES[$name]['tmp_name'], $_FILES[$name]['name']
                );
                if (!empty($elem['mimetypes'])
                    && !in_array($mimetype, $elem['mimetypes'])
                ) {
                    $warnings = $this->translator->translate(
                        'FileTypeInvalid', ['%%mimetype%%' => $mimetype]
                    );
                    return false;
                }

                $file = fopen($_FILES[$name]['tmp_name'], 'rb');
                if ($file === false) {
                    $warnings = 'Could not process file upload - temp file missing';
                    return false;
                }
                $fsize = filesize($_FILES[$name]['tmp_name']);

                // Additional fields for file information
                $fields[] = 'filename';
                $insert[] = '?';
                $updateFields[] = 'filename=?';
                $arrValues[] = $_FILES[$name]['name'];

                $fields[] = 'filesize';
                $insert[] = '?';
                $updateFields[] = 'filesize=?';
                $arrValues[] = $fsize;

                $fields[] = 'mimetype';
                $insert[] = '?';
                $updateFields[] = 'mimetype=?';
                $arrValues[] = $mimetype;

                $arrValues[] = fread($file, $fsize);
                fclose($file);
                break;
            case 'SELECT':
                $arrValues[] = '' !== $value ? $value : null;
                break;
            default:
                $arrValues[] = null !== $value ? $value : '';
            }
            $fields[] = $name;
            $insert[] = '?';
            $updateFields[] = "$name=?";
        }

        if ($missingValues) {
            return $missingValues;
        }

        $conn = $this->entityManager->getConnection();
        if ($fields) {
            $strFields = implode(', ', $fields);
            $strInsert = implode(', ', $insert);
            $strUpdateFields = implode(', ', $updateFields);

            $conn->beginTransaction();
            try {
                // Special case for invoice rows - update product stock balance
                if ($table == "{$this->prefix}invoice_row") {
                    $invoiceRow = $primaryKey ? $this->invoiceRowRepository->find($primaryKey) : null;
                    $productId = $values['product_id'] ?? null;
                    $product = $productId ? $this->productRepository->find((int)$productId) : null;
                    $this->productRepository->updateStockBalance(
                        $invoiceRow,
                        $product,
                        $values['pcs']
                    );
                }

                if (!isset($primaryKey) || !$primaryKey) {
                    if ($parentKeyName) {
                        $strFields .= ", $parentKeyName";
                        $strInsert .= ', ?';
                        $arrValues[] = $parentKey;
                    }
                    $strQuery = "INSERT INTO $table ($strFields) VALUES ($strInsert)";
                    $conn->executeQuery($strQuery, $arrValues);
                    $primaryKey = $conn->lastInsertId();
                } else {
                    // Special case for invoice - update product stock balance for all
                    // invoice rows if the invoice was previously deleted
                    if ($table == "{$this->prefix}invoice") {
                        $deleted = $conn->executeQuery(
                            "SELECT deleted FROM {$this->prefix}invoice WHERE id=?",
                            [$primaryKey]
                        )->fetchOne();
                        if ($deleted) {
                            $rows = $conn->executeQuery(
                                "SELECT product_id, pcs FROM {$this->prefix}invoice_row WHERE invoice_id=? AND deleted=0",
                                [$primaryKey]
                            )->fetchAllAssociative();
                            foreach ($rows as $row) {
                                updateProductStockBalance(
                                    null, $row['product_id'], $row['pcs']
                                );
                            }
                        }
                    }

                    if ("{$this->prefix}send_api_config" === $table
                        || "{$this->prefix}attachment" === $table
                        || "{$this->prefix}invoice_attachment" === $table
                    ) {
                        $strQuery = "UPDATE $table SET $strUpdateFields WHERE id=?";
                    } else {
                        $strQuery = "UPDATE $table SET $strUpdateFields, deleted=0 WHERE id=?";
                    }
                    $arrValues[] = $primaryKey;
                    $conn->executeQuery($strQuery, $arrValues);
                }
                if ($table === "{$this->prefix}company") {
                    $newTags = $values['tags'] ?? [];
                    $company = $this->companyRepository->find($primaryKey);
                    foreach ($company->getTags() as $tag) {
                        if (!in_array($tag->getTag(), $newTags)) {
                            $company->removeTag($tag);
                        }
                    }
                    foreach ($newTags as $newTag) {
                        $company->addTag($this->companyTagRepository->findOrCreateByTag($newTag));
                    }
                    $this->entityManager->persist($company);
                } elseif ($table === "{$this->prefix}company_contact") {
                    $newTags = $values['tags'] ?? [];
                    $companyContact = $this->companyContactRepository->find($primaryKey);
                    foreach ($companyContact->getTags() as $tag) {
                        if (!in_array($tag->getTag(), $newTags)) {
                            $companyContact->removeTag($tag);
                        }
                    }
                    foreach ($newTags as $newTag) {
                        $companyContact->addTag($this->companyContactTagRepository->findOrCreateByTag($newTag));
                    }
                    $this->entityManager->persist($companyContact);
                }
            } catch (Exception $e) {
                $conn->rollBack();
                throw $e;
            }
            $conn->commit();
        }

        // Special case for invoices - check for duplicate invoice numbers
        if ($table == "{$this->prefix}invoice" && isset($values['invoice_no'])) {
            $query = "SELECT ID FROM {$this->prefix}invoice where deleted=0 AND id!=? AND invoice_no=?";
            $params = [
                $primaryKey,
                $values['invoice_no']
            ];
            if ($this->settingsManager->get('invoice_numbering_per_base')) {
                $query .= ' AND base_id=?';
                $params[] = $values['base_id'];
            }
            if ($this->settingsManager->get('invoice_numbering_per_year')) {
                $query .= ' AND invoice_date >= ' . date('Y') . '0101';
            }

            $check = $conn->executeQuery($query, $params)->fetchOne();
            if ($check) {
                $warnings = $this->translator->translate('InvoiceNumberAlreadyInUse');
            }
        }

        // Special case for invoices - check, according to settings, that the invoice has
        // an invoice number and a reference number
        if ($table == "{$this->prefix}invoice" && $onPrint && !isOffer($primaryKey)) {
            verifyInvoiceDataForPrinting($primaryKey);
        }

        // Special case for invoices: store base_id to session as a default invoicer
        if ("{$this->prefix}invoice" === $table && !empty($values['base_id'])) {
            $this->session->set('default_base_id', $values['base_id']);
        }

        return true;
    }

    /**
     *  Get default values for a form
     *
     * @param array $formConfig Form configuration
     * @param ?int  $parentKey  Parent key value, if any
     *
     * @return array
     */
    public function getFormDefaultValues(array $formConfig, ?int $parentKey = null)
    {
        $values = [];

        foreach ($formConfig['fields'] as $elem) {
            $values[$elem['name']] = $this->getFormDefaultValue($elem, $parentKey);
        }
        return $values;
    }

    /**
     * Get the default value for the given form element
     *
     * @param array $elem      Form element
     * @param ?int  $parentKey Parent record id
     *
     * @return mixed Default value
     */
    public function getFormDefaultValue(array $elem, ?int $parentKey)
    {
        if (!isset($elem['default'])) {
            return null;
        }
        if ($elem['default'] === 'DATE_NOW') {
            return date('Y-m-d');
        } elseif (str_contains((string)$elem['default'], 'DATE_NOW+')) {
            $atmpValues = explode('+', $elem['default']);
            return date(
                'Y-m-d',
                mktime(0, 0, 0, (int)date('m'), date('d') + $atmpValues[1], (int)date('Y'))
            );
        } elseif (str_starts_with((string)$elem['default'], 'ADD+')) {
            $strQuery = str_replace('_PARENTID_', (string)($parentKey ?? ''), $elem['listquery']);
            $res = dbQueryCheck($strQuery);
            $intAdd = dbFetchValue($res);
            if (isset($intAdd)) {
                return $intAdd;
            }
            $intAdd = substr($elem['default'], 4);
            if (ctype_digit($intAdd)) {
                return $intAdd;
            }
        } elseif ($elem['default'] === 'POST') {
            // POST has special treatment in iform
            return '';
        }
        $result = $elem['default'];
        if ($elem['type'] == 'INT') {
            $decimals = $elem['decimals'] ?? 2;
            $result = $this->numberFormatter->roundNumber((float)$result, $decimals);
        }
        return $result;
    }

    /**
     * Fetch a record. Values in $values, may modify $formElements.
     *
     * Returns true on success, 'deleted' for deleted records and 'notfound' if record is
     * not found.
     *
     * @param string $table        Table name
     * @param int    $primaryKey   Record ID
     * @param array  $formElements Form elements
     * @param array  $values       Record data
     *
     * @return mixed
     */
    protected function fetchRecord($table, $primaryKey, $formElements, &$values)
    {
        $result = true;
        $strQuery = "SELECT * FROM $table WHERE id=?";
        $row = $this->entityManager->getConnection->executeQuery($strQuery, [$primaryKey])->fetchAssociative();
        if (!$row) {
            return 'notfound';
        }

        if (!empty($row['deleted'])) {
            $result = 'deleted';
        }

        foreach ($formElements as $elem) {
            $type = $elem['type'];
            $name = $elem['name'];

            if (!$type || $type == 'LABEL' || $type == 'DROPDOWNMENU' || $type == 'HEADING') {
                continue;
            }

            switch ($type) {
            case 'ROWSUM':
                break;
            case 'IFORM':
            case 'RESULT':
                $values[$name] = $primaryKey;
                break;
            case 'BUTTON':
            case 'JSBUTTON':
            case 'IMAGE':
            case 'FILE':
                if (strstr($elem['listquery'], '=_ID_')) {
                    $values[$name] = $primaryKey;
                } else {
                    $tmpListQuery = $elem['listquery'];
                    $strReplName = substr($tmpListQuery, strpos($tmpListQuery, '_'));
                    $strReplName = strtolower(
                        substr($strReplName, 1, strrpos($strReplName, '_') - 1)
                    );
                    $values[$name] = $values[$strReplName] ?? '';
                    $elem['listquery'] = str_replace(
                        strtoupper($strReplName), 'ID', $elem['listquery']
                    );
                }
                break;
            case 'INTDATE':
                $values[$name] = dateConvDBDate2Ymd($row[$name]);
                break;
            case 'INT':
                if (isset($elem['decimals'])) {
                    $values[$name] = miscRound2Decim($row[$name], $elem['decimals']);
                } else {
                    $values[$name] = $row[$name];
                }
                break;
            case 'TAGS':
                $values[$name] = '';
                if ("{$this->prefix}company" === $table) {
                    $values[$name] = $this->getTags('company', $primaryKey);
                } elseif ("{$this->prefix}company_contact" === $table) {
                    $values[$name] = $this->getTags('contact', $primaryKey);
                }
                break;
            default:
                $values[$name] = $row[$name];
            }
        }
        return $result;
    }

    /**
     * Get logo size for a base
     *
     * @param int $id Base ID
     *
     * @return int
     */
    protected function getBaseLogoSize($id)
    {
        $row = $this->entityManager->getConnection()->executeQuery(
            "SELECT logo_filename, logo_filesize, logo_filetype, logo_filedata FROM {$this->prefix}base WHERE id=?",
            [$id]
        )->fetchAssociative();
        if ($row) {
            if (isset($row['logo_filename']) && isset($row['logo_filesize'])
                && isset($row['logo_filetype']) && isset($row['logo_filedata'])
            ) {
                return $row['logo_filesize'];
            }
        }
        return 0;
    }
}
