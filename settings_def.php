<?php
/**
 * Settings definitions
 *
 * PHP version 5
 *
 * Copyright (C) Ere Maijala 2010-2018.
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
$arrSettings = [
    'start_sep' => [
        'label' => 'Settings',
        'type' => 'LABEL'
    ],

    'auto_close_after_delete' => [
        'label' => 'SettingAutoCloseFormAfterDelete',
        'type' => 'CHECK',
        'style' => 'medium',
        'position' => 1,
        'default' => 1,
        'allow_null' => true
    ],
    'add_customer_number' => [
        'label' => 'SettingAddCustomerNumber',
        'type' => 'CHECK',
        'style' => 'medium',
        'position' => 1,
        'default' => 1,
        'allow_null' => true
    ],
    'show_deleted_records' => [
        'label' => 'SettingShowDeletedRecords',
        'type' => 'CHECK',
        'style' => 'medium',
        'session' => 1,
        'position' => 1,
        'default' => false,
        'allow_null' => true
    ],
    'session_keepalive' => [
        'label' => 'SettingSessionKeepalive',
        'type' => 'CHECK',
        'style' => 'medium',
        'position' => 1,
        'default' => true,
        'allow_null' => true
    ],
    'unit_price_decimals' => [
        'label' => 'SettingUnitPriceDecimals',
        'type' => 'INT',
        'style' => 'currency',
        'position' => 1,
        'default' => 2,
        'allow_null' => true
    ],
    'default_list_rows' => [
        'label' => 'SettingDefaultListRows',
        'type' => 'SELECT',
        'style' => 'long noemptyvalue',
        'position' => 1,
        'default' => 10,
        'allow_null' => true,
        'options' => [
            10 => '10',
            25 => '25',
            50 => '50',
            100 => '100'
        ]
    ],
    'check_updates' => [
        'label' => 'SettingCheckForUpdates',
        'type' => 'CHECK',
        'style' => 'medium',
        'position' => 1,
        'default' => 0,
        'allow_null' => true
    ],
    'address_autocomplete' => [
        'label' => 'SettingAddressAutocomplete',
        'type' => 'CHECK',
        'style' => 'medium',
        'position' => 1,
        'default' => 0,
        'allow_null' => true
    ],
    'dynamic_select_search_in_middle' => [
        'label' => 'SettingSearchInMiddleOfFields',
        'type' => 'CHECK',
        'style' => 'medium',
        'position' => 1,
        'default' => 0,
        'allow_null' => true
    ],
    'password_recovery' => [
        'label' => 'SettingPasswordRecovery',
        'type' => 'CHECK',
        'style' => 'medium',
        'position' => 1,
        'default' => 1,
        'allow_null' => true
    ],

    'invoice_sep' => [
        'label' => 'SettingInvoices',
        'type' => 'LABEL'
    ],

    'invoice_numbering_per_base' => [
        'label' => 'SettingInvoiceNumberingPerBase',
        'type' => 'CHECK',
        'style' => 'medium',
        'position' => 1,
        'default' => 1,
        'allow_null' => true
    ],
    'invoice_numbering_per_year' => [
        'label' => 'SettingInvoiceNumberingPerYear',
        'type' => 'CHECK',
        'style' => 'medium',
        'position' => 1,
        'default' => 0,
        'allow_null' => true
    ],
    'invoice_update_row_dates_on_copy' => [
        'label' => 'SettingInvoiceUpdateRowDateOnCopy',
        'type' => 'CHECK',
        'style' => 'medium',
        'position' => 1,
        'default' => 1,
        'allow_null' => true
    ],
    'invoice_display_vatless_price_in_list' => [
        'label' => 'SettingInvoiceDisplayVATLessPriceInList',
        'type' => 'CHECK',
        'style' => 'medium',
        'position' => 1,
        'default' => 0,
        'allow_null' => true
    ],
    'invoice_mark_paid_when_payment_date_set' => [
        'label' => 'SettingInvoiceMarkPaidWhenPaymentDateIsSet',
        'type' => 'CHECK',
        'style' => 'medium',
        'position' => 1,
        'default' => 1,
        'allow_null' => true
    ],
    'invoice_auto_archive' => [
        'label' => 'SettingInvoiceAutoArchive',
        'type' => 'CHECK',
        'style' => 'medium',
        'position' => 1,
        'default' => 1,
        'allow_null' => true
    ],

    'invoice_default_vat_percent' => [
        'label' => 'SettingInvoiceDefaultVATPercent',
        'type' => 'PERCENT',
        'style' => 'percent',
        'position' => 1,
        'default' => 24,
        'allow_null' => false
    ],
    'invoice_payment_days' => [
        'label' => 'SettingInvoicePaymentDays',
        'type' => 'INT',
        'style' => 'tiny',
        'position' => 1,
        'default' => 14,
        'allow_null' => false
    ],
    'invoice_terms_of_payment' => [
        'label' => 'SettingInvoiceTermsOfPayment',
        'type' => 'TEXT',
        'style' => 'medium',
        'position' => 1,
        'default' => '%d pv netto',
        'allow_null' => false
    ],
    'invoice_period_for_complaints' => [
        'label' => 'SettingInvoicePeriodForComplaints',
        'type' => 'TEXT',
        'style' => 'medium',
        'position' => 1,
        'default' => '7 päivää',
        'allow_null' => false
    ],
    'invoice_penalty_interest' => [
        'label' => 'SettingInvoicePenaltyInterestPercent',
        'type' => 'PERCENT',
        'style' => 'percent',
        'position' => 1,
        'default' => 8,
        'allow_null' => false
    ],
    'invoice_notification_fee' => [
        'label' => 'SettingInvoiceNotificationFee',
        'type' => 'CURRENCY',
        'style' => 'currency',
        'position' => 1,
        'default' => 5,
        'allow_null' => false
    ],
    'invoice_clear_row_values_after_add' => [
        'label' => 'SettingInvoiceClearRowValuesAfterAdd',
        'type' => 'SELECT',
        'style' => 'long noemptyvalue',
        'position' => 1,
        'default' => 0,
        'allow_null' => true,
        'options' => [
            0 => 'SettingInvoiceKeepRowValues',
            1 => 'SettingInvoiceClearRowValues',
            2 => 'SettingInvoiceUseProductDefaults'
        ]
    ],

    'printing_sep' => [
        'label' => 'SettingPrinting',
        'type' => 'LABEL'
    ],

    'invoice_add_number' => [
        'label' => 'SettingInvoiceAddNumber',
        'type' => 'CHECK',
        'style' => 'medium',
        'position' => 1,
        'default' => 1,
        'allow_null' => true
    ],
    'invoice_add_reference_number' => [
        'label' => 'SettingInvoiceAddReferenceNumber',
        'type' => 'CHECK',
        'style' => 'medium',
        'position' => 1,
        'default' => 1,
        'allow_null' => true
    ],
    'invoice_show_barcode' => [
        'label' => 'SettingInvoiceShowBarcode',
        'type' => 'CHECK',
        'style' => 'medium',
        'position' => 1,
        'default' => 1,
        'allow_null' => true
    ],
    'invoice_print_senders_logo_and_address' => [
        'label' => 'SettingInvoiceShowSendersLogoAndAddress',
        'type' => 'CHECK',
        'style' => 'medium',
        'position' => 1,
        'default' => 0,
        'allow_null' => true
    ],
    'invoice_show_recipient_contact_person' => [
        'label' => 'SettingInvoiceShowRecipientContactPerson',
        'type' => 'CHECK',
        'style' => 'medium',
        'position' => 1,
        'default' => 1,
        'allow_null' => true
    ],
    'invoice_show_recipient_email' => [
        'label' => 'SettingInvoiceShowRecipientEmail',
        'type' => 'CHECK',
        'style' => 'medium',
        'position' => 1,
        'default' => 1,
        'allow_null' => true
    ],
    'invoice_display_product_codes' => [
        'label' => 'SettingInvoiceDisplayProductCodes',
        'type' => 'CHECK',
        'style' => 'medium',
        'position' => 1,
        'default' => 0,
        'allow_null' => true
    ],
    'invoice_show_sequential_number' => [
        'label' => 'SettingInvoiceRowNumbering',
        'type' => 'SELECT',
        'style' => 'long noemptyvalue',
        'position' => 1,
        'default' => 0,
        'allow_null' => true,
        'options' => [
            0 => 'SettingInvoiceRowNumberingNone',
            1 => 'SettingInvoiceRowNumberingSequential',
            2 => 'SettingInvoiceRowNumberingOrderNo'
        ]
    ],
    'invoice_show_row_date' => [
        'label' => 'SettingInvoiceShowRowDate',
        'type' => 'CHECK',
        'style' => 'medium',
        'position' => 1,
        'default' => 1,
        'allow_null' => true
    ],
    'invoice_show_vat_breakdown' => [
        'label' => 'SettingInvoiceShowVATBreakdown',
        'type' => 'CHECK',
        'style' => 'medium',
        'position' => 1,
        'default' => 1,
        'allow_null' => true
    ],
    'invoice_show_dispatch_dates' => [
        'label' => 'SettingInvoiceShowDispatchDates',
        'type' => 'CHECK',
        'style' => 'medium',
        'position' => 1,
        'default' => 0,
        'allow_null' => true
    ],
    'invoice_row_description_first_line_only' => [
        'label' => 'SettingInvoiceRowDescriptionFirstLineOnly',
        'type' => 'CHECK',
        'style' => 'medium',
        'position' => 1,
        'default' => 0,
        'allow_null' => true
    ],
    'invoice_separate_statement' => [
        'label' => 'SettingInvoiceSeparateStatement',
        'type' => 'CHECK',
        'style' => 'medium',
        'position' => 1,
        'default' => 0,
        'allow_null' => true
    ],
    'invoice_show_info_in_form' => [
        'label' => 'SettingInvoiceShowInfoInForm',
        'type' => 'CHECK',
        'style' => 'medium',
        'position' => 1,
        'default' => 0,
        'allow_null' => true
    ],
    'invoice_show_delivery_info_in_invoice' => [
        'label' => 'SettingInvoiceShowDeliveryInfoInInvoice',
        'type' => 'CHECK',
        'style' => 'medium',
        'position' => 1,
        'default' => 0,
        'allow_null' => true
    ],

    'invoice_warn_if_noncurrent_date' => [
        'label' => 'SettingInvoiceWarnIfNonCurrentDate',
        'type' => 'CHECK',
        'style' => 'medium',
        'position' => 1,
        'default' => 1,
        'allow_null' => true
    ],
    'invoice_send_reminder_to_invoicing_address' => [
        'label' => 'SettingInvoiceSendReminderToInvoicingAddress',
        'type' => 'CHECK',
        'style' => 'medium',
        'position' => 1,
        'default' => 0,
        'allow_null' => true
    ],
    'invoice_pdf_filename' => [
        'label' => 'SettingInvoicePDFFilename',
        'type' => 'TEXT',
        'style' => 'medium',
        'position' => 1,
        'default' => 'lasku_%s.pdf',
        'allow_null' => false
    ],
    'invoice_address_x_offset' => [
        'label' => 'SettingInvoiceSenderAddressXOffset',
        'type' => 'INT',
        'style' => 'currency',
        'position' => 1,
        'default' => 0,
        'allow_null' => true
    ],
    'invoice_address_y_offset' => [
        'label' => 'SettingInvoiceSenderAddressYOffset',
        'type' => 'INT',
        'style' => 'currency',
        'position' => 1,
        'default' => 0,
        'allow_null' => true
    ],
    'invoice_recipient_address_x_offset' => [
        'label' => 'SettingInvoiceRecipientAddressXOffset',
        'type' => 'INT',
        'style' => 'currency',
        'position' => 1,
        'default' => 0,
        'allow_null' => true
    ],
    'invoice_recipient_address_y_offset' => [
        'label' => 'SettingInvoiceRecipientAddressYOffset',
        'type' => 'INT',
        'style' => 'currency',
        'position' => 1,
        'default' => 0,
        'allow_null' => true
    ],
    'invoice_address_max_width' => [
        'label' => 'SettingInvoiceAddressMaxWidth',
        'type' => 'INT',
        'style' => 'currency',
        'position' => 1,
        'default' => 85,
        'allow_null' => true
    ],
    'printout_markdown' => [
        'label' => 'SettingMarkdown',
        'type' => 'CHECK',
        'style' => 'medium',
        'position' => 1,
        'default' => 0,
        'allow_null' => true
    ],
    'pdf_link_base_url' => [
        'label' => 'SettingPDFLinkTemplate',
        'type' => 'TEXT',
        'style' => 'xlong',
        'position' => 1,
        'default' => '',
        'allow_null' => true
    ],

    'order_confirmation_sep' => [
        'label' => 'SettingOrderConfirmations',
        'type' => 'LABEL'
    ],

    'order_confirmation_terms' => [
        'label' => 'SettingOrderConfirmationTerms',
        'type' => 'AREA',
        'style' => 'xlarge',
        'position' => 1,
        'default' => '',
        'allow_null' => true
    ],

    'dispatch_note_sep' => [
        'label' => 'SettingDispatchNotes',
        'type' => 'LABEL'
    ],

    'dispatch_note_show_barcodes' => [
        'label' => 'SettingDispatchNoteShowBarcodes',
        'type' => 'CHECK',
        'style' => 'medium',
        'position' => 1,
        'default' => 0,
        'allow_null' => true
    ]
];
