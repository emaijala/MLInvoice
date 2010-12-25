<?php
/*******************************************************************************
VLLasku: web-based invoicing application.
Copyright (C) 2010 Ere Maijala

This program is free software. See attached LICENSE.

*******************************************************************************/

/*******************************************************************************
VLLasku: web-pohjainen laskutusohjelma.
Copyright (C) 2010 Ere Maijala

Tämä ohjelma on vapaa. Lue oheinen LICENSE.

*******************************************************************************/

$arrSettings = array(
  array("name" => "start_sep", "label" => $GLOBALS['locSETTINGS'], "type" => "LABEL"),

  array("name" => "auto_close_form", "label" => $GLOBALS['locSettingAutoCloseForm'], "type" => "CHECK", "style" => "medium", "position" => 1, "default" => 1, "allow_null" => TRUE ),
  array("name" => "add_customer_number", "label" => $GLOBALS['locSettingAddCustomerNumber'], "type" => "CHECK", "style" => "medium", "position" => 1, "default" => 1, "allow_null" => TRUE ),
  
  array("name" => "invoice_sep", "label" => $GLOBALS['locSettingInvoices'], "type" => "LABEL"),
  
  array("name" => "invoice_add_number", "label" => $GLOBALS['locSettingInvoiceAddNumber'], "type" => "CHECK", "style" => "medium", "position" => 1, "default" => 1, "allow_null" => TRUE ),
  array("name" => "invoice_numbering_per_base", "label" => $GLOBALS['locSettingInvoiceNumberingPerBase'], "type" => "CHECK", "style" => "medium", "position" => 1, "default" => 0, "allow_null" => TRUE ),
  array("name" => "invoice_add_reference_number", "label" => $GLOBALS['locSettingInvoiceAddReferenceNumber'], "type" => "CHECK", "style" => "medium", "position" => 1, "default" => 1, "allow_null" => TRUE ),
  array("name" => "invoice_show_barcode", "label" => $GLOBALS['locSettingInvoiceShowBarcode'], "type" => "CHECK", "style" => "medium", "position" => 1, "default" => 1, "allow_null" => TRUE ),
  array("name" => "invoice_show_row_date", "label" => $GLOBALS['locSettingInvoiceShowRowDate'], "type" => "CHECK", "style" => "medium", "position" => 1, "default" => 1, "allow_null" => TRUE ),
  array("name" => "invoice_separate_statement", "label" => $GLOBALS['locSettingInvoiceSeparateStatement'], "type" => "CHECK", "style" => "medium", "position" => 1, "default" => 0, "allow_null" => TRUE ),
  array("name" => "invoice_default_vat_percent", "label" => $GLOBALS['locSettingInvoiceDefaultVATPercent'], "type" => "PERCENT", "style" => "percent", "position" => 1, "default" => 23, "allow_null" => FALSE ),
  array("name" => "invoice_payment_days", "label" => $GLOBALS['locSettingInvoicePaymentDays'], "type" => "INT", "style" => "tiny", "position" => 1, "default" => 14, "allow_null" => FALSE ),
  array("name" => "invoice_terms_of_payment", "label" => $GLOBALS['locSettingInvoiceTermsOfPayment'], "type" => "TEXT", "style" => "medium", "position" => 1, "default" => '%d pv netto', "allow_null" => FALSE ),
  array("name" => "invoice_period_for_complaints", "label" => $GLOBALS['locSettingInvoicePeriodForComplaints'], "type" => "TEXT", "style" => "medium", "position" => 1, "default" => '7 päivää', "allow_null" => FALSE ),
  array("name" => "invoice_penalty_interest", "label" => $GLOBALS['locSettingInvoicePenaltyInterestPercent'], "type" => "PERCENT", "style" => "percent", "position" => 1, "default" => 8, "allow_null" => FALSE ),
  array("name" => "invoice_notification_fee", "label" => $GLOBALS['locSettingInvoiceNotificationFee'], "type" => "CURRENCY", "style" => "currency", "position" => 1, "default" => 5, "allow_null" => FALSE ),
  array("name" => "invoice_pdf_filename", "label" => $GLOBALS['locSettingInvoicePDFFilename'], "type" => "TEXT", "style" => "medium", "position" => 1, "default" => 'lasku_%s.pdf', "allow_null" => FALSE ),
);
