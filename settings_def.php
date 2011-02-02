<?php
/*******************************************************************************
VLLasku: web-based invoicing application.
Copyright (C) 2010-2011 Ere Maijala

This program is free software. See attached LICENSE.

*******************************************************************************/

/*******************************************************************************
VLLasku: web-pohjainen laskutusohjelma.
Copyright (C) 2010-2011 Ere Maijala

Tämä ohjelma on vapaa. Lue oheinen LICENSE.

*******************************************************************************/

$arrSettings = array(
  'start_sep' => array('label' => $GLOBALS['locSETTINGS'], 'type' => 'LABEL'),

  'auto_close_form' => array('label' => $GLOBALS['locSettingAutoCloseForm'], 'type' => 'CHECK', 'style' => 'medium', 'position' => 1, 'default' => 1, 'allow_null' => TRUE ),
  'add_customer_number' => array('label' => $GLOBALS['locSettingAddCustomerNumber'], 'type' => 'CHECK', 'style' => 'medium', 'position' => 1, 'default' => 1, 'allow_null' => TRUE ),
  'show_deleted_records' => array('label' => $GLOBALS['locSettingShowDeletedRecords'], 'type' => 'CHECK', 'style' => 'medium', 'session' => 1, 'position' => 1, 'default' => FALSE, 'allow_null' => TRUE ),
  
  'invoice_sep' => array('label' => $GLOBALS['locSettingInvoices'], 'type' => 'LABEL'),
  
  'invoice_add_number' => array('label' => $GLOBALS['locSettingInvoiceAddNumber'], 'type' => 'CHECK', 'style' => 'medium', 'position' => 1, 'default' => 1, 'allow_null' => TRUE ),
  'invoice_numbering_per_base' => array('label' => $GLOBALS['locSettingInvoiceNumberingPerBase'], 'type' => 'CHECK', 'style' => 'medium', 'position' => 1, 'default' => 0, 'allow_null' => TRUE ),
  'invoice_add_reference_number' => array('label' => $GLOBALS['locSettingInvoiceAddReferenceNumber'], 'type' => 'CHECK', 'style' => 'medium', 'position' => 1, 'default' => 1, 'allow_null' => TRUE ),
  'invoice_show_barcode' => array('label' => $GLOBALS['locSettingInvoiceShowBarcode'], 'type' => 'CHECK', 'style' => 'medium', 'position' => 1, 'default' => 1, 'allow_null' => TRUE ),
  'invoice_show_row_date' => array('label' => $GLOBALS['locSettingInvoiceShowRowDate'], 'type' => 'CHECK', 'style' => 'medium', 'position' => 1, 'default' => 1, 'allow_null' => TRUE ),
  'invoice_separate_statement' => array('label' => $GLOBALS['locSettingInvoiceSeparateStatement'], 'type' => 'CHECK', 'style' => 'medium', 'position' => 1, 'default' => 0, 'allow_null' => TRUE ),
  'invoice_default_vat_percent' => array('label' => $GLOBALS['locSettingInvoiceDefaultVATPercent'], 'type' => 'PERCENT', 'style' => 'percent', 'position' => 1, 'default' => 23, 'allow_null' => FALSE ),
  'invoice_payment_days' => array('label' => $GLOBALS['locSettingInvoicePaymentDays'], 'type' => 'INT', 'style' => 'tiny', 'position' => 1, 'default' => 14, 'allow_null' => FALSE ),
  'invoice_terms_of_payment' => array('label' => $GLOBALS['locSettingInvoiceTermsOfPayment'], 'type' => 'TEXT', 'style' => 'medium', 'position' => 1, 'default' => '%d pv netto', 'allow_null' => FALSE ),
  'invoice_period_for_complaints' => array('label' => $GLOBALS['locSettingInvoicePeriodForComplaints'], 'type' => 'TEXT', 'style' => 'medium', 'position' => 1, 'default' => '7 päivää', 'allow_null' => FALSE ),
  'invoice_penalty_interest' => array('label' => $GLOBALS['locSettingInvoicePenaltyInterestPercent'], 'type' => 'PERCENT', 'style' => 'percent', 'position' => 1, 'default' => 8, 'allow_null' => FALSE ),
  'invoice_notification_fee' => array('label' => $GLOBALS['locSettingInvoiceNotificationFee'], 'type' => 'CURRENCY', 'style' => 'currency', 'position' => 1, 'default' => 5, 'allow_null' => FALSE ),
  'invoice_pdf_filename' => array('label' => $GLOBALS['locSettingInvoicePDFFilename'], 'type' => 'TEXT', 'style' => 'medium', 'position' => 1, 'default' => 'lasku_%s.pdf', 'allow_null' => FALSE ),
  'invoice_new_window' => array('label' => $GLOBALS['locSettingInvoiceOpenInNewWindow'], 'type' => 'CHECK', 'style' => 'medium', 'position' => 1, 'default' => 0, 'allow_null' => TRUE ),
  'invoice_warn_if_noncurrent_date' => array('label' => $GLOBALS['locSettingInvoiceWarnIfNonCurrentDate'], 'type' => 'CHECK', 'style' => 'medium', 'position' => 1, 'default' => 1, 'allow_null' => TRUE ),
);
