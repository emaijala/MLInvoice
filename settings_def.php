<?php
/*******************************************************************************
MLInvoice: web-based invoicing application.
Copyright (C) 2010-2015 Ere Maijala

This program is free software. See attached LICENSE.

*******************************************************************************/

/*******************************************************************************
MLInvoice: web-pohjainen laskutusohjelma.
Copyright (C) 2010-2015 Ere Maijala

Tämä ohjelma on vapaa. Lue oheinen LICENSE.

*******************************************************************************/

$arrSettings = array(
  'start_sep' => array('label' => $GLOBALS['locSettings'], 'type' => 'LABEL'),

  'auto_close_after_delete' => array('label' => $GLOBALS['locSettingAutoCloseFormAfterDelete'], 'type' => 'CHECK', 'style' => 'medium', 'position' => 1, 'default' => 1, 'allow_null' => TRUE ),
  'add_customer_number' => array('label' => $GLOBALS['locSettingAddCustomerNumber'], 'type' => 'CHECK', 'style' => 'medium', 'position' => 1, 'default' => 1, 'allow_null' => TRUE ),
  'show_deleted_records' => array('label' => $GLOBALS['locSettingShowDeletedRecords'], 'type' => 'CHECK', 'style' => 'medium', 'session' => 1, 'position' => 1, 'default' => FALSE, 'allow_null' => TRUE ),
  'session_keepalive' => array('label' => $GLOBALS['locSettingSessionKeepalive'], 'type' => 'CHECK', 'style' => 'medium', 'position' => 1, 'default' => TRUE, 'allow_null' => TRUE ),
  'unit_price_decimals' => array('label' => $GLOBALS['locSettingUnitPriceDecimals'], 'type' => 'INT', 'style' => 'currency', 'position' => 1, 'default' => 2, 'allow_null' => TRUE ),
  'default_list_rows' => array('label' => $GLOBALS['locSettingDefaultListRows'], 'type' => 'SELECT', 'style' => 'long noemptyvalue', 'position' => 1, 'default' => 10, 'allow_null' => TRUE, 'options' => Array(
    10 => '10',
    25 => '25',
    50 => '50',
    100 => '100')
  ),
  'check_updates' => array('label' => $GLOBALS['locSettingCheckForUpdates'], 'type' => 'CHECK', 'style' => 'medium', 'position' => 1, 'default' => 0, 'allow_null' => TRUE ),
  'address_autocomplete' => array('label' => $GLOBALS['locSettingAddressAutocomplete'], 'type' => 'CHECK', 'style' => 'medium', 'position' => 1, 'default' => 0, 'allow_null' => TRUE ),
  'dynamic_select_search_in_middle' => array('label' => $GLOBALS['locSettingSearchInMiddleOfFields'], 'type' => 'CHECK', 'style' => 'medium', 'position' => 1, 'default' => 0, 'allow_null' => TRUE ),

  'invoice_sep' => array('label' => $GLOBALS['locSettingInvoices'], 'type' => 'LABEL'),

  'invoice_add_number' => array('label' => $GLOBALS['locSettingInvoiceAddNumber'], 'type' => 'CHECK', 'style' => 'medium', 'position' => 1, 'default' => 1, 'allow_null' => TRUE ),
  'invoice_numbering_per_base' => array('label' => $GLOBALS['locSettingInvoiceNumberingPerBase'], 'type' => 'CHECK', 'style' => 'medium', 'position' => 1, 'default' => 1, 'allow_null' => TRUE ),
  'invoice_numbering_per_year' => array('label' => $GLOBALS['locSettingInvoiceNumberingPerYear'], 'type' => 'CHECK', 'style' => 'medium', 'position' => 1, 'default' => 0, 'allow_null' => TRUE ),
  'invoice_update_row_dates_on_copy' => array('label' => $GLOBALS['locSettingInvoiceUpdateRowDateOnCopy'], 'type' => 'CHECK', 'style' => 'medium', 'position' => 1, 'default' => 1, 'allow_null' => TRUE ),
  'invoice_add_reference_number' => array('label' => $GLOBALS['locSettingInvoiceAddReferenceNumber'], 'type' => 'CHECK', 'style' => 'medium', 'position' => 1, 'default' => 1, 'allow_null' => TRUE ),
  'invoice_show_barcode' => array('label' => $GLOBALS['locSettingInvoiceShowBarcode'], 'type' => 'CHECK', 'style' => 'medium', 'position' => 1, 'default' => 1, 'allow_null' => TRUE ),
  'invoice_show_recipient_email' => array('label' => $GLOBALS['locSettingInvoiceShowRecipientEmail'], 'type' => 'CHECK', 'style' => 'medium', 'position' => 1, 'default' => 1, 'allow_null' => TRUE ),
  'invoice_display_product_codes' => array('label' => $GLOBALS['locSettingInvoiceDisplayProductCodes'], 'type' => 'CHECK', 'style' => 'medium', 'position' => 1, 'default' => 0, 'allow_null' => TRUE ),
	'invoice_show_row_date' => array('label' => $GLOBALS['locSettingInvoiceShowRowDate'], 'type' => 'CHECK', 'style' => 'medium', 'position' => 1, 'default' => 1, 'allow_null' => TRUE ),
  'invoice_separate_statement' => array('label' => $GLOBALS['locSettingInvoiceSeparateStatement'], 'type' => 'CHECK', 'style' => 'medium', 'position' => 1, 'default' => 0, 'allow_null' => TRUE ),
  'invoice_warn_if_noncurrent_date' => array('label' => $GLOBALS['locSettingInvoiceWarnIfNonCurrentDate'], 'type' => 'CHECK', 'style' => 'medium', 'position' => 1, 'default' => 1, 'allow_null' => TRUE ),
  'invoice_send_reminder_to_invoicing_address' => array('label' => $GLOBALS['locSettingInvoiceSendReminderToInvoicingAddress'], 'type' => 'CHECK', 'style' => 'medium', 'position' => 1, 'default' => 0, 'allow_null' => TRUE ),
  'invoice_display_vatless_price_in_list' => array('label' => $GLOBALS['locSettingInvoiceDisplayVATLessPriceInList'], 'type' => 'CHECK', 'style' => 'medium', 'position' => 1, 'default' => 0, 'allow_null' => TRUE ),
  'invoice_mark_paid_when_payment_date_set' => array('label' => $GLOBALS['locSettingInvoiceMarkPaidWhenPaymentDateIsSet'], 'type' => 'CHECK', 'style' => 'medium', 'position' => 1, 'default' => 1, 'allow_null' => TRUE ),
  'invoice_auto_archive' => array('label' => $GLOBALS['locSettingInvoiceAutoArchive'], 'type' => 'CHECK', 'style' => 'medium', 'position' => 1, 'default' => 1, 'allow_null' => TRUE ),

	'invoice_default_vat_percent' => array('label' => $GLOBALS['locSettingInvoiceDefaultVATPercent'], 'type' => 'PERCENT', 'style' => 'percent', 'position' => 1, 'default' => 24, 'allow_null' => FALSE ),
  'invoice_payment_days' => array('label' => $GLOBALS['locSettingInvoicePaymentDays'], 'type' => 'INT', 'style' => 'tiny', 'position' => 1, 'default' => 14, 'allow_null' => FALSE ),
  'invoice_terms_of_payment' => array('label' => $GLOBALS['locSettingInvoiceTermsOfPayment'], 'type' => 'TEXT', 'style' => 'medium', 'position' => 1, 'default' => '%d pv netto', 'allow_null' => FALSE ),
  'invoice_period_for_complaints' => array('label' => $GLOBALS['locSettingInvoicePeriodForComplaints'], 'type' => 'TEXT', 'style' => 'medium', 'position' => 1, 'default' => '7 päivää', 'allow_null' => FALSE ),
  'invoice_penalty_interest' => array('label' => $GLOBALS['locSettingInvoicePenaltyInterestPercent'], 'type' => 'PERCENT', 'style' => 'percent', 'position' => 1, 'default' => 8, 'allow_null' => FALSE ),
  'invoice_notification_fee' => array('label' => $GLOBALS['locSettingInvoiceNotificationFee'], 'type' => 'CURRENCY', 'style' => 'currency', 'position' => 1, 'default' => 5, 'allow_null' => FALSE ),
  'invoice_pdf_filename' => array('label' => $GLOBALS['locSettingInvoicePDFFilename'], 'type' => 'TEXT', 'style' => 'medium', 'position' => 1, 'default' => 'lasku_%s.pdf', 'allow_null' => FALSE ),
  'invoice_address_x_offset' => array('label' => $GLOBALS['locSettingInvoiceAddressXOffset'], 'type' => 'INT', 'style' => 'currency', 'position' => 1, 'default' => 0, 'allow_null' => TRUE ),
  'invoice_address_y_offset' => array('label' => $GLOBALS['locSettingInvoiceAddressYOffset'], 'type' => 'INT', 'style' => 'currency', 'position' => 1, 'default' => 0, 'allow_null' => TRUE ),
  'invoice_clear_row_values_after_add' => array('label' => $GLOBALS['locSettingInvoiceClearRowValuesAfterAdd'], 'type' => 'SELECT', 'style' => 'long noemptyvalue', 'position' => 1, 'default' => 0, 'allow_null' => TRUE, 'options' => Array(
    0 => $GLOBALS['locSettingInvoiceKeepRowValues'],
    1 => $GLOBALS['locSettingInvoiceClearRowValues'],
    2 => $GLOBALS['locSettingInvoiceUseProductDefaults'])
  ),

	'order_confirmation_sep' => array('label' => $GLOBALS['locSettingOrderConfirmations'], 'type' => 'LABEL'),

	'order_confirmation_terms' => array('label' => $GLOBALS['locSettingOrderConfirmationTerms'], 'type' => 'AREA', 'style' => 'xlarge', 'position' => 1, 'default' => '', 'allow_null' => TRUE ),

	'dispatch_note_sep' => array('label' => $GLOBALS['locSettingDispatchNotes'], 'type' => 'LABEL'),

  'dispatch_note_show_barcodes' => array('label' => $GLOBALS['locSettingDispatchNoteShowBarcodes'], 'type' => 'CHECK', 'style' => 'medium', 'position' => 1, 'default' => 0, 'allow_null' => TRUE ),
);
