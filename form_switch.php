<?php
/**
 * Form config
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
require_once 'settings.php';
require_once 'vendor/autoload.php';

$inputFieldTypes = [
    'AREA',
    'CHECK',
    'FILE',
    'INT',
    'INTDATE',
    'LIST',
    'PASSWD_STORED',
    'SEARCHLIST',
    'TAGS',
    'TEXT',
];

$searchFieldTypes = array_diff(
    $inputFieldTypes,
    [
        'FILE',
        'PASSWD_STORED'
    ]
);

$strListTableAlias = '';
$levelsAllowed = [
    ROLE_USER,
    ROLE_BACKUPMGR
];
$copyLinkOverride = '';
$clearRowValuesAfterAdd = false;
$onAfterRowAdded = '';
$readOnlyForm = false;
$addressAutocomplete = false;
$formDataAttrs = [];
$extraButtons = '';
if (!isset($strFunc)) {
    $strFunc = '';
}
if (!isset($strList)) {
    $strList = '';
}
$mdClass = getSetting('printout_markdown') && getSetting('markdown_editor') ? ' markdown' : '';

switch ($strForm) {

case 'company':
    $strTable = '{prefix}company';
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
            'default' => null,
            'default_query' => getSetting('add_customer_number')
                ? 'SELECT max(customer_no)+1 FROM {prefix}company WHERE deleted=0'
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
            'listquery' => 'SELECT id, name FROM {prefix}delivery_terms WHERE deleted=0 ORDER BY order_no;',
            'position' => 1,
            'default' => null,
            'allow_null' => true
        ],
        [
            'name' => 'delivery_method_id',
            'label' => 'DeliveryMethod',
            'type' => 'LIST',
            'style' => 'medium',
            'listquery' => 'SELECT id, name FROM {prefix}delivery_method WHERE deleted=0 ORDER BY order_no;',
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
    $strTable = '{prefix}company_contact';
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
    $strTable = '{prefix}product';
    $astrSearchFields = [
        [
            'name' => 'product_name',
            'type' => 'TEXT'
        ]
    ];

    if (sesWriteAccess()) {
        $updateStockBalanceCode = '<button type="button" class="btn btn-secondary update-stock-balance">' . Translator::translate('UpdateStockBalance') . '</button>';
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
            'listquery' => 'SELECT max(order_no)+5 FROM {prefix}product WHERE deleted=0',
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
            'decimals' => getSetting('unit_price_decimals'),
            'allow_null' => true
        ],
        [
            'name' => 'type_id',
            'label' => 'Unit',
            'type' => 'LIST',
            'style' => 'short translated',
            'listquery' => 'SELECT id, name FROM {prefix}row_type WHERE deleted=0 ORDER BY order_no;',
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
            'decimals' => getSetting('unit_price_decimals'),
            'allow_null' => true
        ],
        [
            'name' => 'vat_percent',
            'label' => 'VATPercent',
            'type' => 'INT',
            'style' => 'currency',
            'position' => 1,
            'default' => getSetting('invoice_default_vat_percent'),
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
            'decimals' => getSetting('unit_price_decimals'),
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
    $levelsAllowed[] = ROLE_READONLY;
    $strTable = '{prefix}invoice';
    $strListTableAlias = 'i.'; // this is for the search function
    $strParentKey = 'invoice_id';
    $addressAutocomplete = true;
    $defaultState = 1;
    $isOffer = false;

    $arrRefundedInvoice = [
        'allow_null' => true
    ];
    $arrRefundingInvoice = [
        'allow_null' => true
    ];
    $intInvoiceId = intval(getPostOrQuery('id', 0));
    if ($intInvoiceId) {
        $intInvoiceId = is_array($intInvoiceId) ? $intInvoiceId[0] : $intInvoiceId;
        $isOffer = isOffer($intInvoiceId);

        if ($isOffer) {
            $locCopyAsInvoice = Translator::translate('CopyAsInvoice');
            $extraButtons = <<<EOT
<a role="button" class="btn btn-secondary" href="copy_invoice.php?func=$strFunc&amp;list=$strList&amp;id=$intInvoiceId&amp;invoice=1">$locCopyAsInvoice</a>

EOT;
        }

        $strQuery = 'SELECT refunded_invoice_id ' . 'FROM {prefix}invoice ' .
             'WHERE id=?'; // ok to maintain links to deleted invoices too
        $rows = dbParamQuery($strQuery, [$intInvoiceId]);
        $strBaseLink = '?' . preg_replace('/&id=\d*/', '', $_SERVER['QUERY_STRING']);
        $strBaseLink = preg_replace('/&/', '&amp;', $strBaseLink);
        if ($rows) {
            $intRefundedInvoiceId = $rows[0]['refunded_invoice_id'];
            if ($intRefundedInvoiceId) {
                $arrRefundedInvoice = [
                    'name' => 'get',
                    'label' => 'ShowRefundedInvoice',
                    'type' => 'BUTTON',
                    'style' => 'custom',
                    'listquery' => "$strBaseLink&amp;id=$intRefundedInvoiceId",
                    'position' => 2,
                    'allow_null' => true
                ];
            }
        }
        $strQuery = 'SELECT id ' . 'FROM {prefix}invoice ' .
             'WHERE deleted=0 AND refunded_invoice_id=?';
        $rows = dbParamQuery($strQuery, [$intInvoiceId]);
        if ($rows) {
            $intRefundingInvoiceId = $rows[0]['id'];
            if ($intRefundingInvoiceId) {
                $arrRefundingInvoice = [
                    'name' => 'get',
                    'label' => 'ShowRefundingInvoice',
                    'type' => 'BUTTON',
                    'style' => 'custom',
                    'listquery' => "'$strBaseLink&amp;id=$intRefundingInvoiceId",
                    'position' => 2,
                    'allow_null' => true
                ];
            }
        }
    } else {
        if (getPostOrQuery('offer', false) || getPostOrQuery('form', '') === 'offer') {
            $defaultState = getInitialOfferState();
            $isOffer = true;
        }
    }

    $companyOnChange = '';
    $getInvoiceNr = '';
    $updateDates = '';
    $addCompanyCode = '';

    if (sesWriteAccess()) {
        $locUpdateDates = Translator::translate('UpdateDates');
        $updateDates = '<button type="button" class="btn btn-outline-secondary update-dates">' . $locUpdateDates . '</button>';

        $locNew = Translator::translate('New') . '...';
        $addCompanyCode = '<button type="button" class="btn btn-outline-secondary" data-quick-add-company>' . $locNew . '</button>';

        if (!$isOffer) {
            $companyOnChange = '_onChangeCompany';

            if (getSetting('invoice_warn_if_noncurrent_date')) {
                $formDataAttrs[] = 'check-invoice-date';
            }

            if (!getSetting('invoice_add_number')) {
                $formDataAttrs[] = 'check-invoice-number';
            }
        } else {
            $companyOnChange = '_onChangeCompanyOffer';
        }
    }

    if (sesWriteAccess() && !$isOffer) {
        $today = date('Y-m-d');
        $markPaidToday = "if ([1, 2, 5, 6, 7].indexOf(parseInt($('#state_id').val())) !== -1) {"
            . " $('#state_id').val(3); }"
            . " if (!$(this).is('#payment_date')) { $('#payment_date').val('$today'); }";

        if (getSetting('invoice_auto_archive')) {
            $markPaidToday .= <<<EOS
if ($('#interval_type').val() == 0) { $('#archived').prop('checked', true); }
EOS;
        }
        $markPaidToday .= <<<EOS
MLInvoice.highlightButton('.save_button', true); return false;
EOS;
        $markPaidTodayButton = '<button type="button" class="btn btn-outline-secondary" onclick="' .
             $markPaidToday . '">' . Translator::translate('MarkAsPaidToday') . '</button>';
        if (getSetting('invoice_mark_paid_when_payment_date_set')) {
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
        'base' => false,
        'info' => '',
        'foreword' => '',
        'afterword' => ''
    ];
    $baseCnt = dbParamQuery(
        'SELECT count(*) as cnt from {prefix}base WHERE deleted=0 AND inactive=0'
    );
    if ($baseCnt[0]['cnt'] == 1 || (getSetting('remember_last_base') && !empty($_SESSION['default_base_id']))) {
        $prefix = $isOffer ? 'offer' : 'invoice';
        $baseSql = "SELECT id, invoice_default_info info, {$prefix}_default_foreword foreword,"
                . " {$prefix}_default_afterword afterword from {prefix}base";
        $baseParams = [];
        if ($baseCnt[0]['cnt'] == 1) {
            $baseSql .= ' WHERE deleted=0 AND inactive=0';
        } else {
            $baseSql .= ' WHERE id=?';
            $baseParams[] = $_SESSION['default_base_id'];
        }
        $baseData = dbParamQuery($baseSql, $baseParams);
        $defaultValues['base'] = $baseData[0]['id'];
        $defaultValues['info'] = $baseData[0]['info'];
        $defaultValues['foreword'] = $baseData[0]['foreword'];
        $defaultValues['afterword'] = $baseData[0]['afterword'];
    }

    $copyLinkOverride = $intInvoiceId ? "copy_invoice.php?func=$strFunc&amp;list=$strList&amp;id=$intInvoiceId" : '';

    $updateInvoiceNr = null;
    if (sesWriteAccess() && !$isOffer) {
        if (!getSetting('invoice_add_number')
            || !getSetting('invoice_add_reference_number')
        ) {
            $updateInvoiceNr = '<button type="button" class="btn btn-outline-secondary update-invoice-nr">'
                . Translator::translate('GetInvoiceNr') . '</button>';
        }
    }

    $locReminderFeesAdded = Translator::translate('ReminderFeesAdded');
    $addReminderFees = "$.getJSON('json.php?func=add_reminder_fees&amp;id=' + document.getElementById('record_id').value, function(json) {"
        . " if (json.errors) { MLInvoice.errormsg(json.errors); } else { MLInvoice.infomsg('$locReminderFeesAdded'); } MLInvoice.Form.initRows(); });"
        . " return false;";

    $intervalOptions = [
        '0' => Translator::translate('InvoiceIntervalNone'),
        '2' => Translator::translate('InvoiceIntervalMonth'),
        '3' => Translator::translate('InvoiceIntervalYear')
    ];
    for ($i = 4; $i <= 8; $i++) {
        $intervalOptions[(string)$i]
            = str_replace('%d', $i - 2, Translator::translate('InvoiceIntervalMonths'));
    }

    $stateQuery = 'SELECT id, name FROM {prefix}invoice_state WHERE deleted=0';
    if ('ext_search' !== $strFunc) {
        $stateQuery .= $isOffer ? ' AND invoice_offer=1' : ' AND invoice_offer!=1';
    }
    $stateQuery .= ' ORDER BY order_no';

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
            'default' => getQuery('company_id', null)
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
            'default' => 'DATE_NOW'
        ],
        [
            'name' => 'due_date',
            'label' => $isOffer ? 'ValidUntilDate' : 'DueDate',
            'type' => 'INTDATE',
            'style' => 'date',
            'position' => 2,
            'default' => 'DATE_NOW+' . getSetting('invoice_payment_days'),
            'attached_elem' => $updateDates
        ],
        [
            'name' => 'interval_type',
            'label' => 'InvoiceIntervalType',
            'type' => 'SELECT',
            'style' => 'long',
            'position' => 1,
            'options' => $intervalOptions,
            'default' => '0',
            'allow_null' => true,
            'hidden' => $isOffer,
        ],
        [
            'name' => 'next_interval_date',
            'label' => 'InvoiceNextIntervalDate',
            'type' => 'INTDATE',
            'style' => 'date',
            'position' => 2,
            'default' => '',
            'allow_null' => true,
            'hidden' => $isOffer,
        ],
        [
            'name' => 'state_id',
            'label' => 'Status',
            'type' => 'LIST',
            'style' => 'long translated noemptyvalue',
            'listquery' => $stateQuery,
            'position' => 1,
            'default' => $defaultState
        ],
        [
            'name' => 'payment_date',
            'label' => 'PayDate',
            'type' => 'INTDATE',
            'style' => 'date',
            'position' => 2,
            'allow_null' => true,
            'attached_elem' => $markPaidTodayButton,
            'elem_attributes' => 'onchange="' . $markPaidTodayEvent . '" max="' . date('Y-m-d') . '"',
            'hidden' => $isOffer,
        ],
        [
            'name' => 'archived',
            'label' => 'Archived',
            'type' => 'CHECK',
            'style' => 'medium',
            'position' => 1,
            'default' => 0,
            'allow_null' => true
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
            'listquery' => 'SELECT id, name FROM {prefix}delivery_terms WHERE deleted=0 ORDER BY order_no;',
            'position' => 1,
            'default' => null,
            'allow_null' => true
        ],
        [
            'name' => 'delivery_method_id',
            'label' => 'DeliveryMethod',
            'type' => 'LIST',
            'style' => 'long',
            'listquery' => 'SELECT id, name FROM {prefix}delivery_method WHERE deleted=0 ORDER BY order_no;',
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
            'name' => 'invoice_row',
            'label' => 'InvRows',
            'type' => 'IFORM',
            'style' => 'xfull',
            'position' => 0,
            'allow_null' => true,
            'parent_key' => 'invoice_id'
        ],
    ];

    $buttonGroups = [];

    $group1 = [];
    if ($intInvoiceId && sesWriteAccess() && !$isOffer) {
        $group1[] = [
            'name' => 'refundinvoice',
            'label' => 'RefundInvoice',
            'url' => "copy_invoice.php?func=$strFunc&list=$strList&id=$intInvoiceId&refund=1",
        ];
    }
    $query = 'SELECT id FROM {prefix}invoice WHERE deleted=0 AND refunded_invoice_id=?';
    $rows = dbParamQuery($query, [$intInvoiceId]);
    if ($rows) {
        if ($refundingInvoiceId = $rows[0]['id']) {
            $strBaseLink = '?' . preg_replace('/&id=\d*/', '', $_SERVER['QUERY_STRING']);
            $group1[] = [
                'name' => 'get',
                'label' => 'ShowRefundingInvoice',
                'url' => "$strBaseLink&id=$refundingInvoiceId",
            ];
        }
    }
    // ok to maintain links to deleted invoices too
    $query = 'SELECT refunded_invoice_id FROM {prefix}invoice WHERE id=?';
    $rows = dbParamQuery($query, [$intInvoiceId]);
    if ($rows) {
        if ($refundedInvoiceId = $rows[0]['refunded_invoice_id']) {
            $strBaseLink = '?' . preg_replace('/&id=\d*/', '', $_SERVER['QUERY_STRING']);
            $group1[] = [
                'name' => 'get',
                'label' => 'ShowRefundedInvoice',
                'url' => "$strBaseLink&id=$refundedInvoiceId",
                'position' => 2,
                'allow_null' => true
            ];
        }
    }

    if (sesWriteAccess() && !$isOffer) {
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
    $rows = dbParamQuery(
        'SELECT * FROM {prefix}print_template WHERE deleted=0 and type=? and inactive=0 ORDER BY order_no',
        [$isOffer ? 'offer' : 'invoice']
    );
    $templateCount = count($rows);
    $templateFirstCol = 3;
    $rowNum = 0;
    foreach ($rows as $row) {
        if (!sesWriteAccess()) {
            // Check if this print template is safe for read-only use
            $printer = getInvoicePrinter($row['filename']);
            if (null === $printer || !$printer->getReadOnlySafe()) {
                continue;
            }
        }
        $templateId = $row['id'];
        $printStyle = $row['new_window'] ? 'openwindow' : 'redirect';
        $printFunc = null;
        $attrs = [];
        $attrs['data-print-id'] = $templateId;
        $attrs['data-func'] = $strFunc;
        $attrs['data-print-style'] = $printStyle;

        $group2[] = [
            'name' => "print$templateId",
            'label' => $row['name'],
            'url' => '#',
            'attrs' => $attrs,
        ];
    }
    if ($group2) {
        $buttonGroups[] = [
            'buttons' => $group2,
            'overflow' => 5,
            'overflow-label' => 'PrintOther',
        ];
    }

    break;

case 'invoice_row':
    $strTable = '{prefix}invoice_row';
    $strParentKey = 'invoice_id';

    switch (getSetting('invoice_clear_row_values_after_add')) {
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
            'default' => 'DATE_NOW'
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
            'listquery' => 'SELECT id, name FROM {prefix}row_type WHERE deleted=0 ORDER BY order_no',
            'position' => 0,
            'allow_null' => true
        ],
        [
            'name' => 'price',
            'label' => 'Price',
            'type' => 'INT',
            'style' => 'currency',
            'position' => 0,
            'decimals' => getSetting('unit_price_decimals')
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
            'decimals' => getSetting('unit_price_decimals')
        ],
        [
            'name' => 'vat',
            'label' => 'VAT',
            'type' => 'INT',
            'style' => 'currency',
            'position' => 0,
            'default' => getSetting('invoice_default_vat_percent'),
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
            'listquery' => 'SELECT max(order_no)+1 FROM {prefix}invoice_row WHERE deleted=0 AND invoice_id=_PARENTID_',
            'position' => 0,
            'default' => 'ADD+1',
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
    $strTable = '{prefix}base';
    $addressAutocomplete = true;

    $baseId = $id ?? intval(getPostOrQuery('id', false));
    $locTitle = Translator::translate('BaseLogoTitle');
    if ($baseId) {
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
        'listquery' => getBaseLogoSize($baseId) ? "base_logo.php?func=view&amp;id=$baseId" : '',
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
    if (getBaseLogoSize($baseId)) {
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
    $strTable = '{prefix}send_api_config';
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
                'serverdirectory' => 'ServerDirectory',
            ],
            'position' => 0,
            'allow_null' => false
        ],
        [
            'name' => 'username',
            'label' => 'UserNameOrID',
            'type' => 'TEXT',
            'style' => 'medium',
            'position' => 0,
            'allow_null' => true
        ],
        [
            'name' => 'password',
            'label' => 'PasswordOrKey',
            'type' => 'PASSWD_STORED',
            'style' => 'medium',
            'position' => 0,
            'allow_null' => true
        ],
        [
            'name' => 'reference',
            'label' => 'ReferenceOrUnitID',
            'type' => 'TEXT',
            'style' => 'medium',
            'position' => 0,
            'allow_null' => true
        ],
        [
            'name' => 'directory',
            'label' => 'Directory',
            'type' => 'TEXT',
            'style' => 'medium',
            'position' => 0,
            'allow_null' => true
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
            'allow_null' => false
        ],
        [
            'name' => 'add_to_queue',
            'label' => 'SendToQueue',
            'type' => 'CHECK',
            'style' => 'short',
            'position' => 0
        ],
        [
            'name' => 'finvoice_mail_backup',
            'label' => 'FinvoiceMailBackup',
            'type' => 'CHECK',
            'style' => 'short',
            'position' => 0
        ]
    ];
    break;

case 'invoice_state':
    $levelsAllowed = [
        ROLE_ADMIN
    ];
    $strTable = '{prefix}invoice_state';

    $intId = $id ?? getPostOrQuery('id', false);
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
        ROLE_ADMIN
    ];
    $strTable = '{prefix}invoice_type';

    $intId = $id ?? getPostOrQuery('id', false);
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
            'listquery' => 'SELECT max(order_no)+5 FROM {prefix}invoice_type WHERE deleted=0',
        ]
    ];
    break;

case 'row_type':
    $levelsAllowed = [
        ROLE_ADMIN
    ];
    $strTable = '{prefix}row_type';

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
        ROLE_ADMIN
    ];
    $strTable = '{prefix}session_type';

    $intId = getPostOrQuery('id', false);
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
        ROLE_ADMIN
    ];
    $strTable = '{prefix}delivery_terms';

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
        ROLE_ADMIN
    ];
    $strTable = '{prefix}delivery_method';

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
    $strTable = '{prefix}default_value';

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
            'listquery' => 'SELECT max(order_no)+5 FROM {prefix}default_value WHERE deleted=0',
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
    $strTable = '{prefix}attachment';

    $intId = (int)($id ?? getPostOrQuery('id', 0));
    if ($intId) {
        $showAttachment = Translator::translate('ShowAttachment');
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
            'listquery' => 'SELECT max(order_no)+5 FROM {prefix}attachment',
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
            'label' => Translator::Translate(
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
    $strTable = '{prefix}invoice_attachment';
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
            'listquery' => 'SELECT max(order_no)+5 FROM {prefix}attachment',
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
            'label' => Translator::Translate(
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
            'allow_null' => true,
            'default' => 0
        ]
    ];
    break;

case 'user':
    $levelsAllowed = [
        ROLE_ADMIN
    ];
    $strTable = '{prefix}users';
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
            'listquery' => 'SELECT id, name FROM {prefix}session_type WHERE deleted=0 ORDER BY order_no',
            'position' => 0
        ]
    ];
    break;

case 'print_template':
    $strTable = '{prefix}print_template';

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
    throw new \Exception("Invalid form: $strForm");
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
