<?php
/*******************************************************************************
MLInvoice: web-based invoicing application.
Copyright (C) 2010-2015 Ere Maijala

Portions based on:
PkLasku : web-based invoicing software.
Copyright (C) 2004-2008 Samu Reinikainen

This program is free software. See attached LICENSE.

*******************************************************************************/

/*******************************************************************************
MLInvoice: web-pohjainen laskutusohjelma.
Copyright (C) 2010-2015 Ere Maijala

Perustuu osittain sovellukseen:
PkLasku : web-pohjainen laskutusohjelmisto.
Copyright (C) 2004-2008 Samu Reinikainen

Tämä ohjelma on vapaa. Lue oheinen LICENSE.

*******************************************************************************/

require_once 'settings.php';

$strListTableAlias = '';
$strOrder = '';
$levelsAllowed = array(ROLE_USER, ROLE_BACKUPMGR);
$copyLinkOverride = '';
$strJSONType = '';
$clearRowValuesAfterAdd = false;
$onAfterRowAdded = '';
$readOnlyForm = false;
$addressAutocomplete = false;

switch ($strForm) {

case 'company':
  $strTable = '{prefix}company';
  $strJSONType = 'company';
  $strParentKey = 'company_id';
  $addressAutocomplete = true;
  $astrSearchFields =
  array(
    array('name' => 'company_name', 'type' => 'TEXT')
  );

  $defaultCustomerNr = FALSE;
  if (getSetting('add_customer_number'))
  {
    $strQuery = 'SELECT max(customer_no) FROM {prefix}company WHERE deleted=0';
    $intRes = mysqli_query_check($strQuery);
    $defaultCustomerNr = mysqli_fetch_value(mysqli_query_check($strQuery)) + 1;
  }

  $astrFormElements = array(
    array(
      'name' => 'company_name', 'label' => $GLOBALS['locClientName'], 'type' => 'TEXT', 'style' => 'medium', 'position' => 1 ),
    array(
      'name' => 'inactive', 'label' => $GLOBALS['locClientInactive'], 'type' => 'CHECK', 'style' => 'medium', 'position' => 2, 'default' => 0, 'allow_null' => true ),
    array(
      'name' => 'company_id', 'label' => $GLOBALS['locClientVATID'], 'type' => 'TEXT', 'style' => 'medium', 'position' => 1, 'allow_null' => true ),
    array(
      'name' => 'email', 'label' => $GLOBALS['locEmail'], 'type' => 'TEXT', 'style' => 'medium', 'position' => 2, 'allow_null' => true ),
    array(
      'name' => 'customer_no', 'label' => $GLOBALS['locCustomerNr'], 'type' => 'INT', 'style' => 'medium', 'position' => 1, 'default' => $defaultCustomerNr, 'allow_null' => true ),
    array(
      'name' => 'default_ref_number', 'label' => $GLOBALS['locCustomerDefaultRefNr'], 'type' => 'TEXT', 'style' => 'medium', 'position' => 2, 'allow_null' => true ),
    array(
      'name' => 'org_unit_number', 'label' => $GLOBALS['locOrgUnitNumber'], 'type' => 'TEXT', 'style' => 'medium', 'position' => 1, 'allow_null' => true ),
    array(
      'name' => 'payment_intermediator', 'label' => $GLOBALS['locPaymentIntermediator'], 'type' => 'TEXT', 'style' => 'medium', 'position' => 2, 'allow_null' => true ),
    array(
      'name' => 'delivery_terms_id', 'label' => $GLOBALS['locDeliveryTerms'], 'type' => 'LIST', 'style' => 'medium', 'listquery' => 'SELECT id, name FROM {prefix}delivery_terms WHERE deleted=0 ORDER BY order_no;', 'position' => 1, 'default' => null, 'allow_null' => true ),
    array(
      'name' => 'delivery_method_id', 'label' => $GLOBALS['locDeliveryMethod'], 'type' => 'LIST', 'style' => 'medium', 'listquery' => 'SELECT id, name FROM {prefix}delivery_method WHERE deleted=0 ORDER BY order_no;', 'position' => 2, 'default' => null, 'allow_null' => true ),
    array(
      'name' => 'payment_days', 'label' => $GLOBALS['locPaymentDays'], 'type' => 'INT', 'style' => 'short', 'position' => 1, 'default' => null, 'allow_null' => true ),
    array(
      'name' => 'terms_of_payment', 'label' => $GLOBALS['locTermsOfPayment'], 'type' => 'TEXT', 'style' => 'medium', 'position' => 2, 'default' => null, 'allow_null' => true ),
    array(
      'name' => 'street_address', 'label' => $GLOBALS['locStreetAddr'], 'type' => 'TEXT', 'style' => 'medium', 'position' => 1, 'allow_null' => true ),
    array(
      'name' => 'zip_code', 'label' => $GLOBALS['locZipCode'], 'type' => 'TEXT', 'style' => 'short', 'position' => 2, 'allow_null' => true ),
    array(
      'name' => 'city', 'label' => $GLOBALS['locCity'], 'type' => 'TEXT', 'style' => 'medium', 'position' => 1, 'allow_null' => true ),
    array(
      'name' => 'country', 'label' => $GLOBALS['locCountry'], 'type' => 'TEXT', 'style' => 'medium', 'position' => 2, 'allow_null' => true ),
    array(
      'name' => 'phone', 'label' => $GLOBALS['locPhone'], 'type' => 'TEXT', 'style' => 'medium', 'position' => 1, 'allow_null' => true ),
    array(
      'name' => 'fax', 'label' => $GLOBALS['locFAX'], 'type' => 'TEXT', 'style' => 'medium', 'position' => 2, 'allow_null' => true ),
    array(
      'name' => 'gsm', 'label' => $GLOBALS['locGSM'], 'type' => 'TEXT', 'style' => 'medium', 'position' => 1, 'allow_null' => true ),
    array(
      'name' => 'www', 'label' => $GLOBALS['locWWW'], 'type' => 'TEXT', 'style' => 'medium', 'position' => 2, 'allow_null' => true ),
    array(
      'name' => 'billing_address', 'label' => $GLOBALS['locBillAddr'], 'type' => 'AREA', 'style' => 'medium', 'position' => 1, 'allow_null' => true ),
    array(
      'name' => 'info', 'label' => $GLOBALS['locInfo'], 'type' => 'AREA', 'style' => 'medium', 'position' => 2, 'allow_null' => true ),
    array(
      'name' => 'company_contacts', 'label' => $GLOBALS['locContacts'], 'type' => 'IFORM', 'style' => 'full', 'position' => 0, 'allow_null' => true, 'parent_key' => 'company_id' )
  );
break;

case 'company_contact':
case 'company_contacts':
  $strTable = '{prefix}company_contact';
  $strJSONType = 'company_contact';
  $strParentKey = 'company_id';
  $clearRowValuesAfterAdd = true;
  $astrFormElements = array(
    array(
      'name' => 'id', 'label' => '', 'type' => 'HID_INT', 'style' => 'medium', 'position' => 0 ),
    array(
      'name' => 'contact_person', 'label' => $GLOBALS['locContactPerson'], 'type' => 'TEXT', 'style' => 'medium', 'position' => 0 ),
    array(
      'name' => 'person_title', 'label' => $GLOBALS['locPersonTitle'], 'type' => 'TEXT', 'style' => 'small', 'listquery' => '', 'position' => 0, 'allow_null' => true ),
    array(
      'name' => 'phone', 'label' => $GLOBALS['locPhone'], 'type' => 'TEXT', 'style' => 'medium', 'position' => 0, 'allow_null' => true ),
    array(
      'name' => 'gsm', 'label' => $GLOBALS['locGSM'], 'type' => 'TEXT', 'style' => 'medium', 'position' => 0, 'allow_null' => true ),
    array(
      'name' => 'email', 'label' => $GLOBALS['locEmail'], 'type' => 'TEXT', 'style' => 'medium', 'position' => 0, 'allow_null' => true )
  );
break;

case 'product':
  $strTable = '{prefix}product';
  $strJSONType = 'product';
  $astrSearchFields = array(
    array('name' => 'product_name', 'type' => 'TEXT')
  );

  if (sesWriteAccess()) {
    $locStockBalanceChange = $GLOBALS['locStockBalanceChange'];
    $locStockBalanceChangeDescription = $GLOBALS['locStockBalanceChangeDescription'];
    $locUpdateStockBalance = $GLOBALS['locUpdateStockBalance'];
    $locSave = $GLOBALS['locSave'];
    $locClose = $GLOBALS['locClose'];
    $locTitle = $GLOBALS['locUpdateStockBalance'];
    $locMissing = $GLOBALS['locErrValueMissing'];
    $locDecimalSeparator = $GLOBALS['locDecimalSeparator'];
  	$popupHTML = <<<EOS
<script type="text/javascript" src="js/stock_balance.js"></script>
<div id="update_stock_balance" class="form_container ui-widget-content" style="display: none">
  <div class="medium_label">$locStockBalanceChange</div> <div class="field"><input type='TEXT' id="stock_balance_change" class='short'></div>
  <div class="medium_label">$locStockBalanceChangeDescription</div> <div class="field"><textarea id="stock_balance_change_desc" class="large"></textarea></div>
  </div>
EOS;

    $updateStockBalanceCode = <<<EOS
<a class="formbuttonlink" href="#" onclick="update_stock_balance({'save': '$locSave', 'close': '$locClose', 'title': '$locTitle', 'missing': '$locMissing: ', 'decimal_separator': '$locDecimalSeparator'})">$locUpdateStockBalance</a>

EOS;
  }

  $barcodeTypeQuery = "SELECT 'EAN13', 'EAN13' UNION ALL SELECT 'C39', 'CODE 39' UNION ALL SELECT 'C39E', 'CODE 39 Extended' UNION ALL SELECT 'C128', 'CODE 128' UNION ALL SELECT 'C128A', 'CODE 128 A' UNION ALL SELECT 'C128B', 'CODE 128 B' UNION ALL SELECT 'C128C', 'CODE 128 C'";

  $astrFormElements = array(
    array(
      'name' => 'order_no', 'label' => $GLOBALS['locOrderNr'], 'type' => 'INT', 'style' => 'short', 'position' => 1, 'allow_null' => true ),
    array(
      'name' => 'product_code', 'label' => $GLOBALS['locProductCode'], 'type' => 'TEXT', 'style' => 'medium', 'position' => 2, 'allow_null' => true ),
    array(
      'name' => 'product_name', 'label' => $GLOBALS['locProductName'], 'type' => 'TEXT', 'style' => 'medium', 'position' => 1 ),
    array(
      'name' => 'product_group', 'label' => $GLOBALS['locProductGroup'], 'type' => 'TEXT', 'style' => 'medium', 'position' => 2, 'allow_null' => true ),
    array(
      'name' => 'barcode1', 'label' => $GLOBALS['locFirstBarcode'], 'type' => 'TEXT', 'style' => 'medium', 'position' => 1, 'allow_null' => true ),
    array(
      'name' => 'barcode1_type', 'label' => $GLOBALS['locBarcodeType'], 'type' => 'LIST', 'style' => 'medium', 'position' => 2, 'listquery' => $barcodeTypeQuery, 'allow_null' => true ),
    array(
      'name' => 'barcode2', 'label' => $GLOBALS['locSecondBarcode'], 'type' => 'TEXT', 'style' => 'medium', 'position' => 1, 'allow_null' => true ),
    array(
      'name' => 'barcode2_type', 'label' => $GLOBALS['locBarcodeType'], 'type' => 'LIST', 'style' => 'medium', 'position' => 2, 'listquery' => $barcodeTypeQuery, 'allow_null' => true ),
    array(
      'name' => 'description', 'label' => $GLOBALS['locProductDescription'], 'type' => 'TEXT', 'style' => 'long', 'position' => 1, 'allow_null' => true ),
    array(
      'name' => 'internal_info', 'label' => $GLOBALS['locInternalInfo'], 'type' => 'AREA', 'style' => 'xlarge', 'position' => 0, 'allow_null' => true ),
    array(
      'name' => 'unit_price', 'label' => $GLOBALS['locUnitPrice'], 'type' => 'INT', 'style' => 'medium', 'position' => 1, 'decimals' => getSetting('unit_price_decimals'), 'allow_null' => true ),
  	array(
      'name' => 'type_id', 'label' => $GLOBALS['locUnit'], 'type' => 'LIST', 'style' => 'short translated', 'listquery' => 'SELECT id, name FROM {prefix}row_type WHERE deleted=0 ORDER BY order_no;', 'position' => 2, 'default' => 'POST' ),
    array(
      'name' => 'price_decimals', 'label' => $GLOBALS['locPriceInvoiceDecimals'], 'type' => 'INT', 'style' => 'short', 'position' => 1, 'default' => 2 ),
    array(
      'name' => 'discount', 'label' => $GLOBALS['locDiscountPercent'], 'type' => 'INT', 'style' => 'percent', 'position' => 2, 'decimals' => 1, 'allow_null' => true ),
    array(
      'name' => 'vat_percent', 'label' => $GLOBALS['locVATPercent'], 'type' => 'INT', 'style' => 'short', 'position' => 1, 'default' => getSetting('invoice_default_vat_percent'), 'decimals' => 1 ),
    array(
      'name' => 'vat_included', 'label' => $GLOBALS['locVATIncluded'], 'type' => 'CHECK', 'style' => 'medium', 'position' => 2, 'default' => FALSE, 'allow_null' => true ),
    array(
      'name' => 'purchase_price', 'label' => $GLOBALS['locPurchasePrice'], 'type' => 'INT', 'style' => 'medium', 'position' => 1, 'decimals' => getSetting('unit_price_decimals'), 'allow_null' => true ),
    array(
      'name' => 'stock_balance', 'label' => $GLOBALS['locStockBalance'], 'type' => 'INT', 'style' => 'small', 'position' => 2, 'decimals' => 2, 'allow_null' => true, 'read_only' => true, 'attached_elem' => $updateStockBalanceCode ),
  );
break;

case 'invoice':
  $levelsAllowed[] = ROLE_READONLY;
  $strTable = '{prefix}invoice';
  $strListTableAlias = 'i.'; // this is for the search function
  $strParentKey = 'invoice_id';
  $strJSONType = 'invoice';
  $addressAutocomplete = true;

  $arrRefundedInvoice = array('allow_null' => true);
  $arrRefundingInvoice = array('allow_null' => true);
  $intInvoiceId = getRequest('id', 0);
  if ($intInvoiceId)
  {
    $strQuery =
      'SELECT refunded_invoice_id '.
      'FROM {prefix}invoice '.
      'WHERE id=?'; // ok to maintain links to deleted invoices too
    $intRes = mysqli_param_query($strQuery, array($intInvoiceId));
    $strBaseLink = '?' . preg_replace('/&id=\d*/', '', $_SERVER['QUERY_STRING']);
    $strBaseLink = preg_replace('/&/', '&amp;', $strBaseLink);
    if ($intRes)
    {
      $intRefundedInvoiceId = mysqli_fetch_value($intRes);
      if ($intRefundedInvoiceId)
        $arrRefundedInvoice = array(
         'name' => 'get', 'label' => $GLOBALS['locShowRefundedInvoice'], 'type' => 'BUTTON', 'style' => 'custom', 'listquery' => "$strBaseLink&amp;id=$intRefundedInvoiceId", 'position' => 2, 'allow_null' => true
        );
    }
    $strQuery =
      'SELECT id '.
      'FROM {prefix}invoice '.
      'WHERE deleted=0 AND refunded_invoice_id=?';
    $intRes = mysqli_param_query($strQuery, array($intInvoiceId));
    if ($intRes && ($row = mysqli_fetch_assoc($intRes)))
    {
      $intRefundingInvoiceId = $row['id'];
      if ($intRefundingInvoiceId)
        $arrRefundingInvoice = array(
          'name' => 'get', 'label' => $GLOBALS['locShowRefundingInvoice'], 'type' => 'BUTTON', 'style' => 'custom', 'listquery' => "'$strBaseLink&amp;id=$intRefundingInvoiceId", 'position' => 2, 'allow_null' => true
        );
    }
  }

  $invoicePrintChecks = '';
  $invoiceNumberUpdatePrefix = '';
  $invoiceNumberUpdateSuffix = '';
  $companyOnChange = '';
  $getInvoiceNr = '';
  $updateDates = '';
  $addCompanyCode = '';

  if (sesWriteAccess())
  {
    $companyOnChange = <<<EOS
  function() {
    $.getJSON('json.php?func=get_company', {id: $('#company_id').val() }, function(json) {
      if (json) {
        if (json.default_ref_number) {
          $('#ref_number').val(json.default_ref_number);
        }
        if (json.delivery_terms_id) {
          $('#delivery_terms_id').val(json.delivery_terms_id);
        }
        if (json.delivery_method_id) {
          $('#delivery_method_id').val(json.delivery_method_id);
        }
        if (json.payment_days) {
          $.getJSON('json.php?func=get_invoice_defaults', {id: $('#record_id').val(), invoice_no: $('#invoice_no').val(), invoice_date: $('#invoice_date').val(), base_id: $('#base_id').val(), company_id: $('#company_id').val(), interval_type: $('#interval_type').val()}, function(json) {
            $('#due_date').val(json.due_date);
          });
        }
      }
    });
  }
EOS;

    $getInvoiceNr = <<<EOS
$.getJSON('json.php?func=get_invoice_defaults', {id: $('#record_id').val(), invoice_no: $('#invoice_no').val(), invoice_date: $('#invoice_date').val(), base_id: $('#base_id').val(), company_id: $('#company_id').val(), interval_type: $('#interval_type').val()}, function(json) { $('#invoice_no').val(json.invoice_no); $('#ref_number').val(json.ref_no); $('.save_button').addClass('ui-state-highlight'); }); return false;
EOS;

    $locUpdateDates = $GLOBALS['locUpdateDates'];
    $updateDates = <<<EOS
<a class="formbuttonlink" href="#" onclick="$.getJSON('json.php?func=get_invoice_defaults', {id: $('#record_id').val(), invoice_no: $('#invoice_no').val(), invoice_date: $('#invoice_date').val(), base_id: $('#base_id').val(), company_id: $('#company_id').val(), interval_type: $('#interval_type').val()}, function(json) { $('#invoice_date').val(json.date); $('#due_date').val(json.due_date); $('#next_interval_date').val(json.next_interval_date); $('.save_button').addClass('ui-state-highlight'); }); return false;">$locUpdateDates</a>
EOS;

    $locNew = $GLOBALS['locNew'] . '...';
    $locClientName = $GLOBALS['locClientName'];
    $locEmail = $GLOBALS['locEmail'];
    $locPhone = $GLOBALS['locPhone'];
    $locAddress = $GLOBALS['locStreetAddr'];
    $locZip = $GLOBALS['locZipCode'];
    $locCity = $GLOBALS['locCity'];
    $locCountry = $GLOBALS['locCountry'];
    $locSave = $GLOBALS['locSave'];
    $locClose = $GLOBALS['locClose'];
    $locTitle = $GLOBALS['locNewClient'];
    $locMissing = $GLOBALS['locErrValueMissing'];
    $addCompanyCode = <<<EOS
<a class="formbuttonlink" href="#" onclick="add_company({'save': '$locSave', 'close': '$locClose', 'title': '$locTitle', 'missing': '$locMissing: '})">$locNew</a>

EOS;

    $popupHTML = <<<EOS
<script type="text/javascript" src="js/add_company.js"></script>
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

    if (getSetting('invoice_warn_if_noncurrent_date'))
    {
      $invoicePrintChecks .= "var d = new Date(); var dt = document.getElementById('invoice_date').value.split('.'); if (parseInt(dt[0], 10) != d.getDate() || parseInt(dt[1], 10) != d.getMonth()+1 || parseInt(dt[2], 10) != d.getYear() + 1900) { if (!confirm('" . $GLOBALS['locInvoiceDateNonCurrent'] . "')) return false; } ";
    }
    $invoicePrintChecks .= "var len = document.getElementById('ref_number').value.length; if (len > 0 && len < 4) { if (!confirm('" . $GLOBALS['locInvoiceRefNumberTooShort'] . "')) return false; } ";

    if (getSetting('invoice_add_number') || getSetting('invoice_add_reference_number'))
    {
      $invoiceNumberUpdatePrefix = "$.getJSON('json.php?func=get_invoice_defaults', {id: $('#record_id').val(), invoice_no: $('#invoice_no').val(), invoice_date: $('#invoice_date').val(), base_id: $('#base_id').val(), company_id: $('#company_id').val(), interval_type: $('#interval_type').val()}, function(json) { ";
      if (getSetting('invoice_add_number'))
        $invoiceNumberUpdatePrefix .= "var invoice_no = document.getElementById('invoice_no'); if (invoice_no.value == '' || invoice_no.value < 100) invoice_no.value = json.invoice_no; ";
      if (getSetting('invoice_add_reference_number'))
        $invoiceNumberUpdatePrefix .= "var ref_number = document.getElementById('ref_number'); if (ref_number.value == '' || ref_number.value == 0) ref_number.value = json.ref_no; ";
      $invoiceNumberUpdatePrefix .= "$('.save_button').addClass('ui-state-highlight'); ";
      $invoiceNumberUpdateSuffix = ' });';
    }
    if (!getSetting('invoice_add_number')) {
      $invoiceNumberUpdatePrefix .= "invoice_no = document.getElementById('invoice_no'); if (invoice_no.value == '' || invoice_no.value == 0) { if (!confirm('" . $GLOBALS['locInvoiceNumberNotDefined'] . "')) return false; }";
    }
  }

  $today = dateConvDBDate2Date(date('Ymd'));
  $markPaidToday = <<<EOS
$('#state_id').val(3); if (!$(this).is('#payment_date')) { $('#payment_date').val('$today'); }
EOS;
  if (getSetting('invoice_auto_archive')) {
    $markPaidToday .= <<<EOS
$('#archived').prop('checked', true);
EOS;
  }
  $markPaidToday .= <<<EOS
$('.save_button').addClass('ui-state-highlight'); return false;
EOS;
  $markPaidTodayButton = '<a class="formbuttonlink" href="#" onclick="' . $markPaidToday .'">' . $GLOBALS['locMarkAsPaidToday'] . '</a>';
  if (getSetting('invoice_mark_paid_when_payment_date_set')) {
    $markPaidTodayEvent = <<<EOF
if ($(this).val()) { $markPaidToday }
EOF;
  } else {
    $markPaidTodayEvent = '';
  }

  // Print buttons
  $printButtons = array();
  $printButtons2 = array();
  $res = mysqli_query_check('SELECT * FROM {prefix}print_template WHERE deleted=0 and type=\'invoice\' and inactive=0 ORDER BY order_no');
  $templateCount = mysqli_num_rows($res);
  $templateFirstCol = max(floor($templateCount / 2 + 1), 3);
  $rowNum = 0;
  while ($row = mysqli_fetch_assoc($res))
  {
    $templateId = $row['id'];
    $printStyle = $row['new_window'] ? 'openwindow' : 'redirect';

    if (sesWriteAccess())
    {
      $printFunc = "${invoicePrintChecks}${invoiceNumberUpdatePrefix}save_record('invoice.php?id=_ID_&amp;template=$templateId&amp;func=$strFunc', '$printStyle'); ${invoiceNumberUpdateSuffix} return false;";
    }
    else
    {
      // Check if this print template is safe for read-only use
      $printer = instantiateInvoicePrinter($row['filename']);
      if (!$printer->getReadOnlySafe()) {
        continue;
      }

      if ($printStyle == 'openwindow')
        $printFunc = "window.open('invoice.php?id=_ID_&amp;template=$templateId&amp;func=$strFunc'); return false;";
      else
        $printFunc = "window.location = 'invoice.php?id=_ID_&amp;template=$templateId&amp;func=$strFunc'; return false;";
    }

    $arr = array('name' => "print$templateId", 'label' => isset($GLOBALS["loc{$row['name']}"]) ? $GLOBALS["loc{$row['name']}"] : $row['name'], 'type' => 'JSBUTTON', 'style' => $printStyle, 'listquery' => $printFunc, 'position' => 3, 'allow_null' => true );
    if (++$rowNum > $templateFirstCol)
    {
      $arr['position'] = 4;
      $printButtons2[] = $arr;
    }
    else
    {
      $printButtons[] = $arr;
    }
  }

  $intRes = mysqli_query_check('SELECT ID from {prefix}base WHERE deleted=0');
  if (mysqli_num_rows($intRes) == 1)
    $defaultBase = mysqli_fetch_value($intRes);
  else
    $defaultBase = FALSE;

  $copyLinkOverride = "copy_invoice.php?func=$strFunc&amp;list=$strList&amp;id=$intInvoiceId";

  $updateInvoiceNr = null;
  if (sesWriteAccess())
  {
    if (!getSetting('invoice_add_number') || !getSetting('invoice_add_reference_number'))
    {
      $updateInvoiceNr = '<a class="formbuttonlink" href="#" onclick="' . $getInvoiceNr . '">' . $GLOBALS['locGetInvoiceNr'] . '</a>';
    }
  }

  $addReminderFees = "$.getJSON('json.php?func=add_reminder_fees&amp;id=' + document.getElementById('record_id').value, function(json) { if (json.errors) { $('#errormsg').text(json.errors).show() } else { showmsg('{$GLOBALS['locReminderFeesAdded']}'); } init_rows(); }); return false;";

  $intervalOptions = array(
    '0' => $GLOBALS['locInvoiceIntervalNone'],
    '2' => $GLOBALS['locInvoiceIntervalMonth'],
    '3' => $GLOBALS['locInvoiceIntervalYear']
  );

  $astrFormElements = array(
    array(
      'name' => 'base_id', 'label' => $GLOBALS['locBiller'], 'type' => 'LIST', 'style' => 'medium linked', 'listquery' => 'SELECT id, name FROM {prefix}base WHERE deleted=0', 'position' => 1, 'default' => $defaultBase ),
    array(
      'name' => 'name', 'label' => $GLOBALS['locInvName'], 'type' => 'TEXT', 'style' => 'medium', 'position' => 2, 'allow_null' => true ),
    array(
      'name' => 'company_id', 'label' => $GLOBALS['locPayer'], 'type' => 'SEARCHLIST', 'style' => 'medium linked', 'listquery' => "table=company&sort=company_name,company_id", 'position' => 1, 'allow_null' => true, 'attached_elem' => $addCompanyCode, 'elem_attributes' => $companyOnChange  ),
    array(
      'name' => 'reference', 'label' => $GLOBALS['locClientsReference'], 'type' => 'TEXT', 'style' => 'medium', 'position' => 2, 'allow_null' => true ),
    array(
      'name' => 'invoice_no', 'label' => $GLOBALS['locInvoiceNumber'], 'type' => 'INT', 'style' => 'medium hidezerovalue', 'position' => 1, 'default' => null, 'allow_null' => true ),
    array(
      'name' => 'ourreference', 'label' => $GLOBALS['locOurReference'], 'type' => 'TEXT', 'style' => 'medium', 'position' => 2, 'allow_null' => true ),
    array(
      'name' => 'invoice_date', 'label' => $GLOBALS['locInvDate'], 'type' => 'INTDATE', 'style' => 'date', 'position' => 1, 'default' => 'DATE_NOW' ),
    array(
      'name' => 'ref_number', 'label' => $GLOBALS['locReferenceNumber'], 'type' => 'TEXT', 'style' => 'medium hidezerovalue', 'position' => 2, 'default' => null, 'attached_elem' => $updateInvoiceNr, 'allow_null' => true ),
    array(
      'name' => 'interval_type', 'label' => $GLOBALS['locInvoiceIntervalType'], 'type' => 'SELECT', 'style' => 'medium', 'position' => 1, 'options' => $intervalOptions, 'default' => '0', 'allow_null' => true ),
    array(
      'name' => 'due_date', 'label' => $GLOBALS['locDueDate'], 'type' => 'INTDATE', 'style' => 'date', 'position' => 2, 'default' => 'DATE_NOW+' . getSetting('invoice_payment_days'), 'attached_elem' => $updateDates ),
    array(
      'name' => 'state_id', 'label' => $GLOBALS['locStatus'], 'type' => 'LIST', 'style' => 'medium translated', 'listquery' => 'SELECT id, name FROM {prefix}invoice_state WHERE deleted=0 ORDER BY order_no', 'position' => 1, 'default' => 1 ),
    array(
      'name' => 'next_interval_date', 'label' => $GLOBALS['locInvoiceNextIntervalDate'], 'type' => 'INTDATE', 'style' => 'date', 'position' => 2, 'default' => '', 'allow_null' => true ),
    array(
      'name' => 'delivery_terms_id', 'label' => $GLOBALS['locDeliveryTerms'], 'type' => 'LIST', 'style' => 'medium', 'listquery' => 'SELECT id, name FROM {prefix}delivery_terms WHERE deleted=0 ORDER BY order_no;', 'position' => 1, 'default' => null, 'allow_null' => true ),
    array(
      'name' => 'payment_date', 'label' => $GLOBALS['locPayDate'], 'type' => 'INTDATE', 'style' => 'date', 'position' => 2, 'allow_null' => true, 'attached_elem' => $markPaidTodayButton, 'elem_attributes' => 'onchange="' . $markPaidTodayEvent . '"' ),
    array(
      'name' => 'archived', 'label' => $GLOBALS['locArchived'], 'type' => 'CHECK', 'style' => 'medium', 'position' => 1, 'default' => 0, 'allow_null' => true ),
    array(
      'name' => 'delivery_method_id', 'label' => $GLOBALS['locDeliveryMethod'], 'type' => 'LIST', 'style' => 'medium', 'listquery' => 'SELECT id, name FROM {prefix}delivery_method WHERE deleted=0 ORDER BY order_no;', 'position' => 2, 'default' => null, 'allow_null' => true ),
    array(
      'name' => 'info', 'label' => $GLOBALS['locVisibleInfo'], 'type' => 'AREA', 'style' => 'medium', 'position' => 1, 'allow_null' => true ),
    array(
      'name' => 'internal_info', 'label' => $GLOBALS['locInternalInfo'], 'type' => 'AREA', 'style' => 'medium', 'position' => 2, 'allow_null' => true ),

    !sesWriteAccess() ? array('name' => 'refundinvoice', 'label' => '', 'type' => 'FILLER', 'position' => 1) : array(
      'name' => 'refundinvoice', 'label' => $GLOBALS['locRefundInvoice'], 'type' => 'BUTTON', 'style' => 'redirect', 'listquery' => "copy_invoice.php?func=$strFunc&list=$strList&id=_ID_&refund=1", 'position' => 1, 'default' => FALSE, 'allow_null' => true ),
    $arrRefundedInvoice,
    isset($printButtons[0]) ? $printButtons[0] : array(),
    isset($printButtons2[0]) ? $printButtons2[0] : array(),
    !sesWriteAccess() ? array('name' => 'addreminderfees', 'label' => '', 'type' => 'FILLER', 'position' => 1) : array(
      'name' => 'addreminderfees', 'label' => $GLOBALS['locAddReminderFees'], 'type' => 'JSBUTTON', 'style' => 'redirect', 'listquery' => $addReminderFees, 'position' => 1, 'default' => FALSE, 'allow_null' => true ),
    $arrRefundingInvoice,
    isset($printButtons[1]) ? $printButtons[1] : array(),
    isset($printButtons2[1]) ? $printButtons2[1] : array(),
  );

  for ($i = 2; $i < count($printButtons); $i++)
  {
    $astrFormElements[] = $printButtons[$i];
    if (isset($printButtons2[$i]))
      $astrFormElements[] = $printButtons2[$i];
  }

  $astrFormElements[] = array(
    'name' => 'invoice_rows', 'label' => $GLOBALS['locInvRows'], 'type' => 'IFORM', 'style' => 'xfull', 'position' => 0, 'allow_null' => true, 'parent_key' => 'invoice_id'
  );
break;

case 'invoice_row':
case 'invoice_rows':
  $strTable = '{prefix}invoice_row';
  $strJSONType = 'invoice_row';
  $strParentKey = 'invoice_id';
  $strOrder = 'ORDER BY {prefix}invoice_row.order_no, {prefix}invoice_row.row_date';

  switch (getSetting('invoice_clear_row_values_after_add'))
  {
  case 0: break;
  case 1:
    $clearRowValuesAfterAdd = true;
    break;
  case 2:
    $onAfterRowAdded = <<<EOS
  if (globals.selectedProduct)
  {
    var prod = globals.selectedProduct;
    document.getElementById(form_id + '_description').value = prod.description;
    globals.defaultDescription = prod.description;

    var type_id = document.getElementById(form_id + '_type_id');
    for (var i = 0; i < type_id.options.length; i++)
    {
      var item = type_id.options[i];
      if (item.value == prod.type_id)
      {
        item.selected = true;
        break;
      }
    }
    document.getElementById(form_id + '_price').value = prod.unit_price.replace('.', ',');
    document.getElementById(form_id + '_discount').value = prod.discount.replace('.', ',');
    document.getElementById(form_id + '_vat').value = prod.vat_percent.replace('.', ',');
    document.getElementById(form_id + '_vat_included').checked = prod.vat_included == 1 ? true : false;
  }
EOS;
  }

   $productOnChange = <<<EOS
  function() {
    var form_id = this.form.id;
    $.getJSON('json.php?func=get_product&id=' + this.value, function(json) {
      globals.selectedProduct = json;
      if (!json || !json.id) return;

      if (json.description != '' || document.getElementById(form_id + '_description').value == (globals.defaultDescription != null ? globals.defaultDescription : ''))
        document.getElementById(form_id + '_description').value = json.description;
      globals.defaultDescription = json.description;

      var type_id = document.getElementById(form_id + '_type_id');
      for (var i = 0; i < type_id.options.length; i++)
      {
        var item = type_id.options[i];
        if (item.value == json.type_id)
        {
          item.selected = true;
          break;
        }
      }
      document.getElementById(form_id + '_price').value = json.unit_price ? json.unit_price.replace('.', ',') : '';
      document.getElementById(form_id + '_discount').value = json.discount ? json.discount.replace('.', ',') : '';
      document.getElementById(form_id + '_vat').value = json.vat_percent ? json.vat_percent.replace('.', ',') : '';
      document.getElementById(form_id + '_vat_included').checked = (json.vat_included && json.vat_included == 1) ? true : false;
    });
  }
EOS;

   $multiplierColumn = 'pcs';
   $priceColumn = 'price';
   $discountColumn = 'discount';
   $VATColumn = 'vat';
   $VATIncludedColumn = 'vat_included';
   $showPriceSummary = true;

   $astrFormElements =
    array(
     array(
        'name' => 'id', 'label' => '', 'type' => 'HID_INT', 'style' => 'medium', 'position' => 0 ),
     array(
        'name' => 'product_id', 'label' => $GLOBALS['locProductName'], 'type' => 'SEARCHLIST', 'style' => 'medium', 'listquery' => "table=product&sort=order_no,product_code,product_name", 'position' => 0, 'allow_null' => true, 'elem_attributes' => $productOnChange ),
     array(
        'name' => 'description', 'label' => $GLOBALS['locRowDesc'], 'type' => 'TEXT', 'style' => 'medium', 'position' => 0, 'allow_null' => true ),
     array(
        'name' => 'row_date', 'label' => $GLOBALS['locDate'], 'type' => 'INTDATE', 'style' => 'date', 'position' => 0, 'default' => 'DATE_NOW' ),
     array(
        'name' => 'pcs', 'label' => $GLOBALS['locPCS'], 'type' => 'INT', 'style' => 'count', 'position' => 0 ),
     array(
        'name' => 'type_id', 'label' => $GLOBALS['locUnit'], 'type' => 'LIST', 'style' => 'short translated', 'listquery' => 'SELECT id, name FROM {prefix}row_type WHERE deleted=0 ORDER BY order_no', 'position' => 0, 'default' => 'POST', 'allow_null' => true ),
     array(
        'name' => 'price', 'label' => $GLOBALS['locPrice'], 'type' => 'INT', 'style' => 'currency', 'position' => 0, 'default' => 'POST', 'decimals' => getSetting('unit_price_decimals') ),
     array(
        'name' => 'discount', 'label' => $GLOBALS['locDiscount'], 'type' => 'INT', 'style' => 'percent', 'position' => 0, 'default' => 0, 'allow_null' => true ),
     array(
        'name' => 'vat', 'label' => $GLOBALS['locVAT'], 'type' => 'INT', 'style' => 'percent', 'position' => 0, 'default' => str_replace('.', $GLOBALS['locDecimalSeparator'], getSetting('invoice_default_vat_percent')), 'allow_null' => true ),
     array(
        'name' => 'vat_included', 'label' => $GLOBALS['locVATInc'], 'type' => 'CHECK', 'style' => 'xshort', 'position' => 0, 'default' => 0, 'allow_null' => true ),
     array(
        'name' => 'order_no', 'label' => $GLOBALS['locRowNr'], 'type' => 'INT', 'style' => 'tiny', 'listquery' => 'SELECT max(order_no)+5 FROM {prefix}invoice_row WHERE deleted=0 AND invoice_id=_PARENTID_', 'position' => 0, 'default' => 'ADD+5', 'allow_null' => true ),
     array(
        'name' => 'row_sum', 'label' => $GLOBALS['locRowTotal'], 'type' => 'ROWSUM', 'style' => 'currency', 'position' => 0, 'decimals' => 2, 'allow_null' => true )
   );

break;

/******************************************************************************
    SYSTEM FORMS - SYSTEEMILOMAKKEET
******************************************************************************/
case 'base':
  $strTable = '{prefix}base';
  $strJSONType = 'base';
  $addressAutocomplete = true;

  $title = $GLOBALS['locBaseLogoTitle'];
  $openPopJS = <<<EOF
popup_dialog('base_logo.php?func=edit&amp;id=_ID_', '$(\\'img\\').attr(\\'src\\', \\'base_logo.php?func=view&id=_ID_\\')', '$title', event, 600, 400); return false;
EOF;

  $astrFormElements = array(
    array(
      'name' => 'name', 'label' => $GLOBALS['locBaseName'], 'type' => 'TEXT', 'style' => 'medium', 'position' => 1 ),
    array(
      'name' => 'company_id', 'label' => $GLOBALS['locClientVATID'], 'type' => 'TEXT', 'style' => 'medium', 'position' => 2, 'allow_null' => true ),
    array(
      'name' => 'vat_registered', 'label' => $GLOBALS['locVATRegistered'], 'title' => $GLOBALS['locVATRegisteredHint'], 'type' => 'CHECK', 'style' => 'short', 'position' => 1, 'allow_null' => true ),
    array(
      'name' => 'org_unit_number', 'label' => $GLOBALS['locOrgUnitNumber'], 'type' => 'TEXT', 'style' => 'medium', 'position' => 1, 'allow_null' => true ),
  	array(
      'name' => 'payment_intermediator', 'label' => $GLOBALS['locPaymentIntermediator'], 'type' => 'TEXT', 'style' => 'medium', 'position' => 2, 'allow_null' => true ),
  	array(
      'name' => 'contact_person', 'label' => $GLOBALS['locContactPerson'], 'type' => 'TEXT', 'style' => 'medium', 'position' => 1, 'allow_null' => true ),
    array(
      'name' => 'email', 'label' => $GLOBALS['locEmail'], 'type' => 'TEXT', 'style' => 'medium', 'position' => 2, 'allow_null' => true ),
    array(
      'name' => 'street_address', 'label' => $GLOBALS['locStreetAddr'], 'type' => 'TEXT', 'style' => 'medium', 'position' => 1, 'allow_null' => true ),
    array(
      'name' => 'zip_code', 'label' => $GLOBALS['locZipCode'], 'type' => 'TEXT', 'style' => 'short', 'position' => 2, 'allow_null' => true ),
    array(
      'name' => 'city', 'label' => $GLOBALS['locCity'], 'type' => 'TEXT', 'style' => 'medium', 'position' => 1, 'allow_null' => true ),
    array(
      'name' => 'country', 'label' => $GLOBALS['locCountry'], 'type' => 'TEXT', 'style' => 'medium', 'position' => 2, 'allow_null' => true ),
    array(
      'name' => 'phone', 'label' => $GLOBALS['locPhone'], 'type' => 'TEXT', 'style' => 'medium', 'position' => 1, 'allow_null' => true ),
    array(
      'name' => 'www', 'label' => $GLOBALS['locWWW'], 'type' => 'TEXT', 'style' => 'medium', 'position' => 2, 'allow_null' => true ),
    array(
      'name' => 'banksep1', 'label' => $GLOBALS['locFirstBank'], 'type' => 'LABEL'),
    array(
      'name' => 'bank_name', 'label' => $GLOBALS['locBank'], 'type' => 'TEXT', 'style' => 'medium', 'position' => 1 ),
    array(
      'name' => 'bank_account', 'label' => $GLOBALS['locAccount'], 'type' => 'TEXT', 'style' => 'medium', 'position' => 2 ),
    array(
      'name' => 'bank_iban', 'label' => $GLOBALS['locAccountIBAN'], 'type' => 'TEXT', 'style' => 'medium', 'position' => 1 ),
    array(
      'name' => 'bank_swiftbic', 'label' => $GLOBALS['locSWIFTBIC'], 'type' => 'TEXT', 'style' => 'medium', 'position' => 2 ),
    array(
      'name' => 'banksep2', 'label' => $GLOBALS['locSecondBank'], 'type' => 'LABEL'),
    array(
      'name' => 'bank_name2', 'label' => $GLOBALS['locBank'], 'type' => 'TEXT', 'style' => 'medium', 'position' => 1, 'allow_null' => true ),
    array(
      'name' => 'bank_account2', 'label' => $GLOBALS['locAccount'], 'type' => 'TEXT', 'style' => 'medium', 'position' => 2, 'allow_null' => true ),
    array(
      'name' => 'bank_iban2', 'label' => $GLOBALS['locAccountIBAN'], 'type' => 'TEXT', 'style' => 'medium', 'position' => 1, 'allow_null' => true ),
    array(
      'name' => 'bank_swiftbic2', 'label' => $GLOBALS['locSWIFTBIC'], 'type' => 'TEXT', 'style' => 'medium', 'position' => 2, 'allow_null' => true ),
    array(
      'name' => 'banksep3', 'label' => $GLOBALS['locThirdBank'], 'type' => 'LABEL'),
    array(
      'name' => 'bank_name3', 'label' => $GLOBALS['locBank'], 'type' => 'TEXT', 'style' => 'medium', 'position' => 1, 'allow_null' => true ),
    array(
      'name' => 'bank_account3', 'label' => $GLOBALS['locAccount'], 'type' => 'TEXT', 'style' => 'medium', 'position' => 2, 'allow_null' => true ),
    array(
      'name' => 'bank_iban3', 'label' => $GLOBALS['locAccountIBAN'], 'type' => 'TEXT', 'style' => 'medium', 'position' => 1, 'allow_null' => true ),
    array(
      'name' => 'bank_swiftbic3', 'label' => $GLOBALS['locSWIFTBIC'], 'type' => 'TEXT', 'style' => 'medium', 'position' => 2, 'allow_null' => true ),
    array(
      'name' => 'emailsep', 'label' => $GLOBALS['locBaseEmailTitle'], 'type' => 'LABEL'),
    array(
      'name' => 'invoice_email_from', 'label' => $GLOBALS['locBaseEmailFrom'], 'type' => 'TEXT', 'style' => 'medium', 'position' => 1, 'allow_null' => true ),
    array(
      'name' => 'invoice_email_bcc', 'label' => $GLOBALS['locBaseEmailBCC'], 'type' => 'TEXT', 'style' => 'medium', 'position' => 2, 'allow_null' => true ),
    array(
      'name' => 'invoice_email_subject', 'label' => $GLOBALS['locBaseInvoiceEmailSubject'], 'type' => 'TEXT', 'style' => 'long', 'position' => 0, 'allow_null' => true ),
    array(
      'name' => 'invoice_email_body', 'label' => $GLOBALS['locBaseInvoiceEmailBody'], 'type' => 'AREA', 'style' => 'email email_body', 'position' => 0, 'allow_null' => true ),
    array(
      'name' => 'receipt_email_subject', 'label' => $GLOBALS['locBaseReceiptEmailSubject'], 'type' => 'TEXT', 'style' => 'long', 'position' => 0, 'allow_null' => true ),
  	array(
      'name' => 'receipt_email_body', 'label' => $GLOBALS['locBaseReceiptEmailBody'], 'type' => 'AREA', 'style' => 'email email_body', 'position' => 0, 'allow_null' => true ),
    array(
      'name' => 'order_confirmation_email_subject', 'label' => $GLOBALS['locBaseOrderConfirmationEmailSubject'], 'type' => 'TEXT', 'style' => 'long', 'position' => 0, 'allow_null' => true ),
  	array(
      'name' => 'order_confirmation_email_body', 'label' => $GLOBALS['locBaseOrderConfirmationEmailBody'], 'type' => 'AREA', 'style' => 'email email_body', 'position' => 0, 'allow_null' => true ),
    array(
      'name' => 'logosep', 'label' => $GLOBALS['locBaseLogoTitle'], 'type' => 'LABEL'),
    array(
      'name' => 'logo', 'label' => '', 'type' => 'IMAGE', 'style' => 'image', 'listquery' => 'base_logo.php?func=view&amp;id=_ID_', 'position' => 0, 'allow_null' => true ),
    array(
      'name' => 'edit_logo', 'label' => $GLOBALS['locBaseChangeImage'], 'type' => 'JSBUTTON', 'style' => 'medium', 'listquery' => $openPopJS, 'position' => 1, 'allow_null' => true ),
    array(
      'name' => 'logo_left', 'label' => $GLOBALS['locBaseLogoLeft'], 'type' => 'INT', 'style' => 'measurement', 'position' => 1, 'allow_null' => true ),
    array(
      'name' => 'logo_top', 'label' => $GLOBALS['locBaseLogoTop'], 'type' => 'INT', 'style' => 'measurement', 'position' => 2, 'allow_null' => true ),
    array(
      'name' => 'logo_width', 'label' => $GLOBALS['locBaseLogoWidth'], 'type' => 'INT', 'style' => 'measurement', 'position' => 1, 'allow_null' => true ),
    array(
      'name' => 'logo_bottom_margin', 'label' => $GLOBALS['locBaseLogoBottomMargin'], 'type' => 'INT', 'style' => 'measurement', 'position' => 2, 'allow_null' => true ),
  );
break;

case 'invoice_state':
  $levelsAllowed = array(ROLE_ADMIN);
  $strTable = '{prefix}invoice_state';
  $strJSONType = 'invoice_state';

  $intId = isset($id) ? $id : getRequest('id', FALSE);
  $readOnly = ($intId && $intId <= 8);
  $astrFormElements = array(
    array(
      'name' => 'name', 'label' => $GLOBALS['locStatus'], 'type' => 'TEXT', 'style' => 'medium', 'position' => 1, 'read_only' => $readOnly ),
    array(
      'name' => 'order_no', 'label' => $GLOBALS['locOrderNr'], 'type' => 'INT', 'style' => 'short', 'position' => 2, 'read_only' => $readOnly ),
    array(
      'name' => 'invoice_open', 'label' => $GLOBALS['locShowInOpenInvoices'], 'type' => 'CHECK', 'style' => 'short', 'position' => 1 ),
    array(
      'name' => 'invoice_unpaid', 'label' => $GLOBALS['locShowInUnpaidInvoices'], 'type' => 'CHECK', 'style' => 'short', 'position' => 2 )
  );
break;

case 'row_type':
  $levelsAllowed = array(ROLE_ADMIN);
  $strTable = '{prefix}row_type';
  $strJSONType = 'row_type';

  $astrFormElements = array(
    array(
      'name' => 'name', 'label' => $GLOBALS['locRowType'], 'type' => 'TEXT', 'style' => 'medium', 'position' => 1 ),
    array(
      'name' => 'order_no', 'label' => $GLOBALS['locOrderNr'], 'type' => 'INT', 'style' => 'short', 'position' => 2 )
  );
break;

case 'session_type':
  $levelsAllowed = array(ROLE_ADMIN);
  $strTable = '{prefix}session_type';
  $strJSONType = 'session_type';

  $intId = getRequest('id', FALSE);
  if ($intId && $intId <= 4)
  {
    $readOnlyForm = true;
  }
  $astrFormElements = array(
    array(
      'name' => 'name', 'label' => $GLOBALS['locSessionType'], 'type' => 'TEXT', 'style' => 'medium', 'position' => 1 ),
    array(
      'name' => 'order_no', 'label' => $GLOBALS['locOrderNr'], 'type' => 'INT', 'style' => 'short', 'position' => 2 ),
    array(
      'name' => 'access_level', 'label' => $GLOBALS['locAccessLevel'], 'type' => 'INT', 'style' => 'short', 'position' => 1, 'default' => 1 )
  );
break;

case 'delivery_terms':
  $levelsAllowed = array(ROLE_ADMIN);
  $strTable = '{prefix}delivery_terms';
  $strJSONType = 'delivery_terms';

  $astrFormElements = array(
    array(
      'name' => 'name', 'label' => $GLOBALS['locDeliveryTerms'], 'type' => 'TEXT', 'style' => 'medium', 'position' => 1 ),
    array(
      'name' => 'order_no', 'label' => $GLOBALS['locOrderNr'], 'type' => 'INT', 'style' => 'short', 'position' => 2 )
  );
break;

case 'delivery_method':
  $levelsAllowed = array(ROLE_ADMIN);
  $strTable = '{prefix}delivery_method';
  $strJSONType = 'delivery_method';

  $astrFormElements = array(
    array(
      'name' => 'name', 'label' => $GLOBALS['locDeliveryMethod'], 'type' => 'TEXT', 'style' => 'medium', 'position' => 1 ),
    array(
      'name' => 'order_no', 'label' => $GLOBALS['locOrderNr'], 'type' => 'INT', 'style' => 'short', 'position' => 2 )
  );
break;

case 'user':
  $levelsAllowed = array(ROLE_ADMIN);
  $strTable = '{prefix}users';
  $strJSONType = 'user';
  $astrFormElements = array(
    array(
      'name' => 'name', 'label' => $GLOBALS['locUserName'], 'type' => 'TEXT', 'style' => 'medium', 'position' => 1 ),
    array(
      'name' => 'login', 'label' => $GLOBALS['locLoginName'], 'type' => 'TEXT', 'style' => 'medium', 'position' => 1, 'unique' => true ),
    array(
      'name' => 'passwd', 'label' => $GLOBALS['locPassword'], 'type' => 'PASSWD', 'style' => 'medium', 'position' => 2, 'allow_null' => true ),
    array(
      'name' => 'type_id', 'label' => $GLOBALS['locType'], 'type' => 'LIST', 'style' => 'medium translated', 'listquery' => 'SELECT id, name FROM {prefix}session_type WHERE deleted=0 ORDER BY order_no', 'position' => 0 )
  );
break;

case 'print_template':
  $strTable = '{prefix}print_template';
  $strJSONType = 'print_template';

  $elem_attributes = '';
  $astrFormElements = array(
    array(
      'name' => 'type', 'label' => $GLOBALS['locPrintTemplateType'], 'type' => 'LIST', 'style' => 'medium', 'listquery' => "SELECT 'invoice' as id, '". $GLOBALS['locPrintTemplateTypeInvoice'] . "' as name", 'position' => 1 ),
    array(
      'name' => 'order_no', 'label' => $GLOBALS['locOrderNr'], 'type' => 'INT', 'style' => 'short', 'position' => 2 ),
    array(
      'name' => 'name', 'label' => $GLOBALS['locPrintTemplateName'], 'type' => 'TEXT', 'style' => 'medium', 'position' => 1 ),
    array(
      'name' => 'filename', 'label' => $GLOBALS['locPrintTemplateFileName'], 'type' => 'TEXT', 'style' => 'medium', 'position' => 2, 'allow_null' => true ),
    array(
      'name' => 'parameters', 'label' => $GLOBALS['locPrintTemplateParameters'], 'type' => 'TEXT', 'style' => 'medium', 'position' => 1, 'allow_null' => true ),
    array(
      'name' => 'output_filename', 'label' => $GLOBALS['locPrintTemplateOutputFileName'], 'type' => 'TEXT', 'style' => 'medium', 'position' => 2, 'allow_null' => true ),
    array(
      'name' => 'new_window', 'label' => $GLOBALS['locPrintTemplateOpenInNewWindow'], 'type' => 'CHECK', 'style' => 'medium', 'position' => 1, 'allow_null' => true ),
   array(
      'name' => 'inactive', 'label' => $GLOBALS['locPrintTemplateInactive'], 'type' => 'CHECK', 'style' => 'medium', 'position' => 2, 'default' => 0, 'allow_null' => true ),
   );
   break;
}

// Clean up the array
$akeys = array('name', 'type', 'position', 'style', 'label', 'parent_key', 'listquery', 'allow_null', 'elem_attributes');
foreach ($astrFormElements as &$element) {
  foreach ($akeys as $key) {
    if (!isset($element[$key])) {
      $element[$key] = false;
    }
  }
}
