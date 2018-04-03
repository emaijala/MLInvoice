<?php
/**
 * Form config
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
require_once 'settings.php';
require_once 'vendor/autoload.php';

$strListTableAlias = '';
$strOrder = '';
$levelsAllowed = [
    ROLE_USER,
    ROLE_BACKUPMGR
];
$copyLinkOverride = '';
$strJSONType = '';
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

switch ($strForm) {

case 'company' :
    $strTable = '{prefix}company';
    $strJSONType = 'company';
    $strParentKey = 'company_id';
    $addressAutocomplete = true;
    $astrSearchFields = [
        [
            'name' => 'company_name',
            'type' => 'TEXT'
        ]
    ];

    $defaultCustomerNr = null;
    if (getSetting('add_customer_number')) {
        $strQuery = 'SELECT max(customer_no) FROM {prefix}company WHERE deleted=0';
        $intRes = dbQueryCheck($strQuery);
        $defaultCustomerNr = dbFetchValue($intRes) + 1;
    }

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
            'default' => $defaultCustomerNr,
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
            'style' => 'short',
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
            'style' => 'medium',
            'position' => 1,
            'allow_null' => true
        ],
        [
            'name' => 'info',
            'label' => 'Info',
            'type' => 'AREA',
            'style' => 'medium',
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
            'name' => 'company_contacts',
            'label' => 'Contacts',
            'type' => 'IFORM',
            'style' => 'full',
            'position' => 0,
            'allow_null' => true,
            'parent_key' => 'company_id'
        ]
    ];
    break;

case 'company_contact' :
case 'company_contacts' :
    $strTable = '{prefix}company_contact';
    $strJSONType = 'company_contact';
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
            'style' => 'small',
            'position' => 0,
            'allow_null' => true
        ],
        [
            'name' => 'gsm',
            'label' => 'GSM',
            'type' => 'TEXT',
            'style' => 'small',
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

case 'product' :
    $strTable = '{prefix}product';
    $strJSONType = 'product';
    $astrSearchFields = [
        [
            'name' => 'product_name',
            'type' => 'TEXT'
        ]
    ];

    if (sesWriteAccess()) {
        $locStockBalanceChange = Translator::translate('StockBalanceChange');
        $locStockBalanceChangeDescription = Translator::translate('StockBalanceChangeDescription');
        $locUpdateStockBalance = Translator::translate('UpdateStockBalance');
        $locSave = Translator::translate('Save');
        $locClose = Translator::translate('Close');
        $locTitle = Translator::translate('UpdateStockBalance');
        $locMissing = Translator::translate('ErrValueMissing');
        $locDecimalSeparator = Translator::translate('DecimalSeparator');
        $popupHTML = <<<EOS
<script type="text/javascript" src="js/stock_balance.js"></script>
<div id="update_stock_balance" class="form_container ui-widget-content" style="display: none">
  <div class="medium_label">$locStockBalanceChange</div> <div class="field"><input type='TEXT' id="stock_balance_change" class='short'></div>
  <div class="medium_label">$locStockBalanceChangeDescription</div> <div class="field"><textarea id="stock_balance_change_desc" class="large"></textarea></div>
  </div>
EOS;

        $updateStockBalanceCode = <<<EOS
<a class="formbuttonlink" href="#"
  onclick="update_stock_balance({'save': '$locSave', 'close': '$locClose', 'title': '$locTitle', 'missing': '$locMissing: ', 'decimal_separator': '$locDecimalSeparator'})">
    $locUpdateStockBalance
</a>

EOS;
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
            'allow_null' => true
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
            'type' => 'TEXT',
            'style' => 'medium',
            'position' => 1
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
            'type' => 'TEXT',
            'style' => 'long',
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
            'default' => 'POST'
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
            'style' => 'percent',
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
            'style' => 'short',
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

case 'invoice' :
    $levelsAllowed[] = ROLE_READONLY;
    $strTable = '{prefix}invoice';
    $strListTableAlias = 'i.'; // this is for the search function
    $strParentKey = 'invoice_id';
    $strJSONType = 'invoice';
    $addressAutocomplete = true;
    $defaultState = 1;
    $isOffer = false;

    $arrRefundedInvoice = [
        'allow_null' => true
    ];
    $arrRefundingInvoice = [
        'allow_null' => true
    ];
    $intInvoiceId = getRequest('id', 0);
    if ($intInvoiceId) {
        $isOffer = isOffer($intInvoiceId);

        if ($isOffer) {
            $locCopyAsInvoice = Translator::translate('CopyAsInvoice');
            $extraButtons = <<<EOT
<a class="actionlink" href="copy_invoice.php?func=$strFunc&list=$strList&id=$intInvoiceId&invoice=1">$locCopyAsInvoice</a>

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
        if (getRequest('offer', false)) {
            $defaultState = getInitialOfferState();
            $isOffer = true;
        }
    }

    $companyOnChange = '';
    $getInvoiceNr = '';
    $updateDates = '';
    $addCompanyCode = '';
    $addPartialPaymentCode = '';

    if (sesWriteAccess()) {
        $locUpdateDates = Translator::translate('UpdateDates');
        $updateDates = '<a class="formbuttonlink update-dates" href="#">' . $locUpdateDates . '</a>';

        $locNew = Translator::translate('New') . '...';
        $locClientName = Translator::translate('ClientName');
        $locEmail = Translator::translate('Email');
        $locPhone = Translator::translate('Phone');
        $locAddress = Translator::translate('StreetAddr');
        $locZip = Translator::translate('ZipCode');
        $locCity = Translator::translate('City');
        $locCountry = Translator::translate('Country');
        $locSave = Translator::translate('Save');
        $locClose = Translator::translate('Close');
        $locTitle = Translator::translate('NewClient');
        $locMissing = Translator::translate('ErrValueMissing');
        $addCompanyCode = <<<EOS
<a class="formbuttonlink" href="#"
  onclick="add_company({'save': '$locSave', 'close': '$locClose', 'title': '$locTitle', 'missing': '$locMissing: '})">
    $locNew
</a>

EOS;

        $popupHTML = <<<EOS
<div id="quick_add_company" class="form_container ui-widget-content" style="display: none">
  <div class="medium_label">$locClientName</div> <div class="field"><input type='TEXT' id="quick_name" class='medium'></div>
  <div class="medium_label">$locEmail</div> <div class="field"><input type='TEXT' id="quick_email" class='medium'></div>
  <div class="medium_label">$locPhone</div> <div class="field"><input type='TEXT' id="quick_phone" class='medium'></div>
  <div class="medium_label">$locAddress</div> <div class="field"><input type='TEXT' id="quick_street_address" class='medium'></div>
  <div class="medium_label">$locZip</div> <div class="field"><input type='TEXT' id="quick_zip_code" class='medium'></div>
  <div class="medium_label">$locCity</div> <div class="field"><input type='TEXT' id="quick_city" class='medium'></div>
  <div class="medium_label">$locCountry</div> <div class="field"><input type='TEXT' id="quick_country" class='medium'></div>
</div>

EOS;

        if (!$isOffer) {
            $companyOnChange = '_onChangeCompany';

            $locPartialPayment = Translator::translate('PartialPayment');
            $locDecimalSeparator = Translator::translate('DecimalSeparator');
            $addPartialPaymentCode = "add_partial_payment({'save': '$locSave', 'close': '$locClose',"
                . " 'title': '{$locPartialPayment}', 'missing': '$locMissing: ', "
                . "'partial_payment': '{$locPartialPayment}', 'decimal_separator': '{$locDecimalSeparator}'});"
                . " return false;";

            $locPaymentAmount = Translator::translate('PaymentAmount');
            $locPaymentDate = Translator::translate('PayDate');
            $popupHTML .= <<<EOS
<div id="add_partial_payment" class="form_container ui-widget-content" style="display: none">
  <div class="medium_label">{$locPaymentAmount}</div>
    <div class="field"><input type='TEXT' id="add_partial_payment_amount" class='medium'></div>
  <div class="medium_label">{$locPaymentDate}</div>
  <div class="field"><input type='TEXT' id="add_partial_payment_date" class='date hasCalendar'></div>
</div>

EOS;

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
        $today = dateConvDBDate2Date(date('Ymd'));
        $markPaidToday = "if ([1, 2, 5, 6, 7].indexOf(parseInt($('#state_id').val())) !== -1) {"
            . " $('#state_id').val(3); }"
            . " if (!$(this).is('#payment_date')) { $('#payment_date').val('$today'); }";

        if (getSetting('invoice_auto_archive')) {
            $markPaidToday .= <<<EOS
if ($('#interval_type').val() == 0) { $('#archived').prop('checked', true); }
EOS;
        }
        $markPaidToday .= <<<EOS
$('.save_button').addClass('ui-state-highlight'); return false;
EOS;
        $markPaidTodayButton = '<a class="formbuttonlink" href="#" onclick="' .
             $markPaidToday . '">' . Translator::translate('MarkAsPaidToday') . '</a>';
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

    // Print buttons
    $printButtons = [];
    $printButtons2 = [];
    $rows = dbParamQuery(
        'SELECT * FROM {prefix}print_template WHERE deleted=0 and type=? and inactive=0 ORDER BY order_no',
        [$isOffer ? 'offer' : 'invoice']
    );
    $templateCount = count($rows);
    $templateFirstCol = 3;
    $rowNum = 0;
    foreach ($rows as $row) {
        $templateId = $row['id'];
        $printStyle = $row['new_window'] ? 'openwindow' : 'redirect';

        if (sesWriteAccess()) {
            $printFunc = "MLInvoice.printInvoice('$templateId', '$strFunc', '$printStyle'); return false;";
        } else {
            // Check if this print template is safe for read-only use
            $printer = instantiateInvoicePrinter($row['filename']);
            if (null === $printer || !$printer->getReadOnlySafe()) {
                continue;
            }

            if ($printStyle == 'openwindow') {
                $printFunc = "window.open('invoice.php?id=_ID_&amp;template=$templateId&amp;func=$strFunc'); return false;";
            } else {
                $printFunc = "window.location = 'invoice.php?id=_ID_&amp;template=$templateId&amp;func=$strFunc'; return false;";
            }
        }

        $arr = [
            'name' => "print$templateId",
            'label' => $row['name'],
            'type' => 'JSBUTTON',
            'style' => $printStyle,
            'listquery' => $printFunc,
            'position' => 3,
            'allow_null' => true
        ];
        if (++$rowNum > $templateFirstCol) {
            $arr['position'] = 4;
            $printButtons2[] = $arr;
        } else {
            $printButtons[] = $arr;
        }
    }

    if (count($printButtons2) > 3) {
         $printButtons2[2] = [
             'name' => 'printmenu',
             'label' => 'PrintOther',
             'type' => 'DROPDOWNMENU',
             'style' => '',
             'position' => 4,
             'options' => array_splice($printButtons2, 2)
         ];
    }

    $intRes = dbQueryCheck(
        'SELECT ID from {prefix}base WHERE deleted=0 AND inactive=0'
    );
    if (mysqli_num_rows($intRes) == 1) {
        $defaultBase = dbFetchValue($intRes);
    } else {
        $defaultBase = false;
    }

    $copyLinkOverride = "copy_invoice.php?func=$strFunc&amp;list=$strList&amp;id=$intInvoiceId";

    $updateInvoiceNr = null;
    if (sesWriteAccess() && !$isOffer) {
        if (!getSetting('invoice_add_number')
            || !getSetting('invoice_add_reference_number')
        ) {
            $updateInvoiceNr = '<a class="formbuttonlink update-invoice-nr" href="#">'
                . Translator::translate('GetInvoiceNr') . '</a>';
        }
    }

    $locReminderFeesAdded = Translator::translate('ReminderFeesAdded');
    $addReminderFees = "$.getJSON('json.php?func=add_reminder_fees&amp;id=' + document.getElementById('record_id').value, function(json) {"
        . " if (json.errors) { MLInvoice.errormsg(json.errors); } else { MLInvoice.showmsg('$locReminderFeesAdded'); } init_rows(); });"
        . " return false;";

    $intervalOptions = [
        '0' => Translator::translate('InvoiceIntervalNone'),
        '2' => Translator::translate('InvoiceIntervalMonth'),
        '3' => Translator::translate('InvoiceIntervalYear')
    ];
    for ($i = 4; $i <= 8; $i++) {
        $intervalOptions[(string)$i]
            = sprintf(Translator::translate('InvoiceIntervalMonths'), $i - 2);
    }

    $stateQuery = 'SELECT id, name FROM {prefix}invoice_state WHERE deleted=0 AND ';
    $stateQuery .= $isOffer ? 'invoice_offer=1' : 'invoice_offer!=1';
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
            'type' => 'LIST',
            'style' => 'long linked',
            'listquery' => 'SELECT id, name FROM {prefix}base WHERE deleted=0 AND inactive=0 ORDER BY name, id',
            'position' => 1,
            'default' => $defaultBase
        ],
        [
            'name' => 'name',
            'label' => $isOffer ? 'OfferName' : 'InvName',
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
            'elem_attributes' => $companyOnChange
        ],
        [
            'name' => 'reference',
            'label' => 'ClientsReference',
            'type' => 'TEXT',
            'style' => 'medium' . ($isOffer ? ' hidden' : ''),
            'position' => 2,
            'allow_null' => true
        ],
        [
            'name' => 'invoice_no',
            'label' => 'InvoiceNumber',
            'type' => 'INT',
            'style' => 'medium hidezerovalue' . ($isOffer ? ' hidden' : ''),
            'position' => 1,
            'default' => null,
            'allow_null' => true
        ],
        [
            'name' => 'ref_number',
            'label' => 'ReferenceNumber',
            'type' => 'TEXT',
            'style' => 'medium hidezerovalue' . ($isOffer ? ' hidden' : ''),
            'position' => 2,
            'default' => null,
            'attached_elem' => $updateInvoiceNr,
            'allow_null' => true
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
            'style' => 'long' . ($isOffer ? ' hidden' : ''),
            'position' => 1,
            'options' => $intervalOptions,
            'default' => '0',
            'allow_null' => true
        ],
        [
            'name' => 'next_interval_date',
            'label' => 'InvoiceNextIntervalDate',
            'type' => 'INTDATE',
            'style' => 'date' . ($isOffer ? ' hidden' : ''),
            'position' => 2,
            'default' => '',
            'allow_null' => true
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
            'style' => 'date' . ($isOffer ? ' hidden' : ''),
            'position' => 2,
            'allow_null' => true,
            'attached_elem' => $markPaidTodayButton,
            'elem_attributes' => 'onchange="' . $markPaidTodayEvent . '" data-no-future="1"'
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
            'name' => 'delivery_time',
            'label' => 'DeliveryTime',
            'type' => 'TEXT',
            'style' => 'medium hidezerovalue' . (!$isOffer ? ' hidden' : ''),
            'position' => 2,
            'default' => null,
            'allow_null' => true
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
            'style' => 'medium',
            'listquery' => 'SELECT id, name FROM {prefix}delivery_method WHERE deleted=0 ORDER BY order_no;',
            'position' => 2,
            'default' => null,
            'allow_null' => true
        ],
        [
            'name' => 'info',
            'label' => 'VisibleInfo',
            'type' => 'AREA',
            'style' => 'large',
            'position' => 1,
            'attached_elem' => '<span class="select-default-text" data-type="info" data-target="info"></span>',
            'allow_null' => true
        ],
        [
            'name' => 'internal_info',
            'label' => 'InternalInfo',
            'type' => 'AREA',
            'style' => 'large',
            'position' => 2,
            'allow_null' => true
        ],
        [
            'name' => 'foreword',
            'label' => 'Foreword',
            'type' => 'AREA',
            'style' => 'large',
            'position' => 1,
            'attached_elem' => '<span class="select-default-text" data-type="foreword" data-target="foreword"></span>',
            'allow_null' => true
        ],
        [
            'name' => 'afterword',
            'label' => 'Afterword',
            'type' => 'AREA',
            'style' => 'large',
            'position' => 2,
            'attached_elem' => '<span class="select-default-text" data-type="afterword" data-target="afterword"></span>',
            'allow_null' => true
        ],

        !sesWriteAccess() || $isOffer ? [
            'name' => 'refundinvoice',
            'label' => '',
            'type' => 'FILLER',
            'position' => 1
        ] : [
            'name' => 'refundinvoice',
            'label' => 'RefundInvoice',
            'type' => 'BUTTON',
            'style' => 'redirect',
            'listquery' => "copy_invoice.php?func=$strFunc&list=$strList&id=_ID_&refund=1",
            'position' => 1,
            'default' => false,
            'allow_null' => true
        ],
        $arrRefundedInvoice,
        isset($printButtons[0]) ? $printButtons[0] : [],
        isset($printButtons2[0]) ? $printButtons2[0] : [],
        !sesWriteAccess() || $isOffer ? [
            'name' => 'addreminderfees',
            'label' => '',
            'type' => 'FILLER',
            'position' => 1
        ] : [
            'name' => 'addreminderfees',
            'label' => 'AddReminderFees',
            'type' => 'JSBUTTON',
            'style' => 'redirect',
            'listquery' => $addReminderFees,
            'position' => 1,
            'default' => false,
            'allow_null' => true
        ],
        $arrRefundingInvoice,
        isset($printButtons[1]) ? $printButtons[1] : [],
        isset($printButtons2[1]) ? $printButtons2[1] : [],
        !sesWriteAccess() || $isOffer ? [
            'name' => 'addpartialpayment',
            'label' => '',
            'type' => 'FILLER',
            'position' => 1
        ] : [
            'name' => 'addpartialpayment',
            'label' => 'AddPartialPayment',
            'type' => 'JSBUTTON',
            'style' => 'redirect',
            'listquery' => $addPartialPaymentCode,
            'position' => 1,
            'default' => false,
            'allow_null' => true
        ],
    ];

    for ($i = 2; $i < count($printButtons); $i ++) {
        $astrFormElements[] = $printButtons[$i];
        if (isset($printButtons2[$i])) {
            $astrFormElements[] = $printButtons2[$i];
        }
    }

    $astrFormElements[] = [
        'name' => 'invoice_rows',
        'label' => 'InvRows',
        'type' => 'IFORM',
        'style' => 'xfull',
        'position' => 0,
        'allow_null' => true,
        'parent_key' => 'invoice_id'
    ];
    break;

case 'invoice_row' :
case 'invoice_rows' :
    $strTable = '{prefix}invoice_row';
    $strJSONType = 'invoice_row';
    $strParentKey = 'invoice_id';
    $strOrder = 'ORDER BY {prefix}invoice_row.order_no, {prefix}invoice_row.row_date';

    switch (getSetting('invoice_clear_row_values_after_add')) {
    case 0 :
        break;
    case 1 :
        $clearRowValuesAfterAdd = true;
        break;
    case 2 :
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
            'label' => 'ProductName',
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
            'default' => 'POST',
            'allow_null' => true
        ],
        [
            'name' => 'price',
            'label' => 'Price',
            'type' => 'INT',
            'style' => 'currency',
            'position' => 0,
            'default' => 'POST',
            'decimals' => getSetting('unit_price_decimals')
        ],
        [
            'name' => 'discount',
            'label' => 'DiscountPct',
            'type' => 'INT',
            'style' => 'percent',
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
            'style' => 'percent',
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
            'style' => 'tiny',
            'listquery' => 'SELECT max(order_no)+5 FROM {prefix}invoice_row WHERE deleted=0 AND invoice_id=_PARENTID_',
            'position' => 0,
            'default' => 'ADD+5',
            'allow_null' => true
        ],
        [
            'name' => 'partial_payment',
            'label' => 'PartialPayment',
            'type' => 'HID_INT',
            'style' => 'xshort',
            'position' => 0,
            'default' => 0,
            'allow_null' => true
        ],
        [
            'name' => 'row_sum',
            'label' => 'RowTotal',
            'type' => 'ROWSUM',
            'style' => 'currency',
            'position' => 0,
            'decimals' => 2,
            'allow_null' => true
        ]
    ];

    break;

/* SYSTEM FORMS */
case 'base' :
    $strTable = '{prefix}base';
    $strJSONType = 'base';
    $addressAutocomplete = true;

    $locTitle = Translator::translate('BaseLogoTitle');
    $openPopJS = <<<EOF
    MLInvoice.popupDialog('base_logo.php?func=edit&amp;id=_ID_', '$(\\'img\\').attr(\\'src\\', \\'base_logo.php?func=view&id=_ID_\\')', '$locTitle', event, 600, 400); return false;
EOF;

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
            'style' => 'short',
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
            'type' => 'LABEL'
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
            'position' => 2
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
            'type' => 'LABEL'
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
            'type' => 'LABEL'
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
            'type' => 'LABEL'
        ],
        [
            'name' => 'invoice_default_info',
            'label' => 'InvoiceDefaultInfo',
            'type' => 'AREA',
            'style' => 'medium',
            'position' => 1,
            'allow_null' => true
        ],
        [
            'name' => 'invoice_default_foreword',
            'label' => 'InvoiceDefaultForeword',
            'type' => 'AREA',
            'style' => 'large',
            'position' => 1,
            'allow_null' => true
        ],
        [
            'name' => 'invoice_default_afterword',
            'label' => 'InvoiceDefaultAfterword',
            'type' => 'AREA',
            'style' => 'large',
            'position' => 2,
            'allow_null' => true
        ],
        [
            'name' => 'offer_default_foreword',
            'label' => 'OfferDefaultForeword',
            'type' => 'AREA',
            'style' => 'large',
            'position' => 1,
            'allow_null' => true
        ],
        [
            'name' => 'offer_default_afterword',
            'label' => 'OfferDefaultAfterword',
            'type' => 'AREA',
            'style' => 'large',
            'position' => 2,
            'allow_null' => true
        ],
        [
            'name' => 'terms_of_payment',
            'label' => 'SettingInvoiceTermsOfPayment',
            'type' => 'TEXT',
            'style' => 'large',
            'position' => 1,
            'allow_null' => true
        ],
        [
            'name' => 'period_for_complaints',
            'label' => 'SettingInvoicePeriodForComplaints',
            'type' => 'TEXT',
            'style' => 'large',
            'position' => 2,
            'allow_null' => true
        ],
        [
            'name' => 'emailsep',
            'label' => 'BaseEmailTitle',
            'type' => 'LABEL'
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
            'type' => 'LABEL'
        ],
        [
            'name' => 'logo',
            'label' => '',
            'type' => 'IMAGE',
            'style' => 'image',
            'listquery' => 'base_logo.php?func=view&amp;id=_ID_',
            'position' => 0,
            'allow_null' => true
        ],
        [
            'name' => 'edit_logo',
            'label' => 'BaseChangeImage',
            'type' => 'JSBUTTON',
            'style' => 'medium',
            'listquery' => $openPopJS,
            'position' => 1,
            'allow_null' => true
        ],
        [
            'name' => 'logo_left',
            'label' => 'BaseLogoLeft',
            'type' => 'INT',
            'style' => 'measurement',
            'position' => 1,
            'allow_null' => true
        ],
        [
            'name' => 'logo_top',
            'label' => 'BaseLogoTop',
            'type' => 'INT',
            'style' => 'measurement',
            'position' => 2,
            'allow_null' => true
        ],
        [
            'name' => 'logo_width',
            'label' => 'BaseLogoWidth',
            'type' => 'INT',
            'style' => 'measurement',
            'position' => 1,
            'allow_null' => true
        ]
    ];
    break;

case 'invoice_state' :
    $levelsAllowed = [
        ROLE_ADMIN
    ];
    $strTable = '{prefix}invoice_state';
    $strJSONType = 'invoice_state';

    $intId = isset($id) ? $id : getRequest('id', false);
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

case 'row_type' :
    $levelsAllowed = [
        ROLE_ADMIN
    ];
    $strTable = '{prefix}row_type';
    $strJSONType = 'row_type';

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

case 'session_type' :
    $levelsAllowed = [
        ROLE_ADMIN
    ];
    $strTable = '{prefix}session_type';
    $strJSONType = 'session_type';

    $intId = getRequest('id', false);
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

case 'delivery_terms' :
    $levelsAllowed = [
        ROLE_ADMIN
    ];
    $strTable = '{prefix}delivery_terms';
    $strJSONType = 'delivery_terms';

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

case 'delivery_method' :
    $levelsAllowed = [
        ROLE_ADMIN
    ];
    $strTable = '{prefix}delivery_method';
    $strJSONType = 'delivery_method';

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

case 'default_value' :
    $strTable = '{prefix}default_value';
    $strJSONType = 'default_value';

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
            'position' => 2
        ],
        [
            'name' => 'content',
            'label' => 'Content',
            'type' => 'AREA',
            'style' => 'xxlarge',
            'position' => 0
        ],
        [
            'name' => 'additional',
            'label' => 'AddInfo',
            'type' => 'AREA',
            'style' => 'xxlarge',
            'position' => 0
        ]
    ];
    break;

case 'user' :
    $levelsAllowed = [
        ROLE_ADMIN
    ];
    $strTable = '{prefix}users';
    $strJSONType = 'user';
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
            'style' => 'medium translated',
            'listquery' => 'SELECT id, name FROM {prefix}session_type WHERE deleted=0 ORDER BY order_no',
            'position' => 0
        ]
    ];
    break;

case 'print_template' :
    $strTable = '{prefix}print_template';
    $strJSONType = 'print_template';

    $elem_attributes = '';
    $astrFormElements = [
        [
            'name' => 'type',
            'label' => 'PrintTemplateType',
            'type' => 'LIST',
            'style' => 'medium noemptyvalue',
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
}

// Clean up the array
$akeys = [
    'name',
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
    foreach ($akeys as $key) {
        if (!isset($element[$key])) {
            $element[$key] = false;
        }
    }
}
