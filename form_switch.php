<?php
/*******************************************************************************
VLLasku: web-based invoicing application.
Copyright (C) 2010-2011 Ere Maijala

Portions based on:
PkLasku : web-based invoicing software.
Copyright (C) 2004-2008 Samu Reinikainen

This program is free software. See attached LICENSE.

*******************************************************************************/

/*******************************************************************************
VLLasku: web-pohjainen laskutusohjelma.
Copyright (C) 2010-2011 Ere Maijala

Perustuu osittain sovellukseen:
PkLasku : web-pohjainen laskutusohjelmisto.
Copyright (C) 2004-2008 Samu Reinikainen

Tämä ohjelma on vapaa. Lue oheinen LICENSE.

*******************************************************************************/

/***********************************************************************
 form_switch.php
 
 provides switches for different forms
 
 supported element types:
 
 TEXT : normal textarea
 INTDATE : date textarea with calendar button
 CHECK : checkbox
 LIST : listbox
 IFORM : embedded ajaxified list-form
 BUTTON : button for various events
 
***********************************************************************/

$strListTableAlias = '';
$strOrder = '';
$levelsAllowed = array(1, 90);
$copyLinkOverride = '';
$strJSONType = '';
$clearRowValuesAfterAdd = true;
switch ( $strForm ) {

case 'company':
   $strTable = '{prefix}company';
   $strPrimaryKey = 'id';
   $strParentKey = "company_id";
   $astrSearchFields = 
    array( 
        array("name" => "company_name", "type" => "TEXT")
    );
    
   $defaultCustomerNo = FALSE;
   if (getSetting('add_customer_number'))
   {
     $strQuery = 'SELECT max(customer_no) FROM {prefix}company WHERE deleted=0';
     $intRes = mysql_query_check($strQuery);
     $intInvNo = mysql_fetch_value(mysql_query_check($strQuery)) + 1;
     $defaultCustomerNo = $intInvNo;
   }
    
   $astrFormElements =
    array(
     array(
        "name" => "company_name", "label" => $GLOBALS['locCOMPNAME'], "type" => "TEXT", "style" => "medium", "listquery" => "", "position" => 1, "default" => FALSE, "allow_null" => FALSE ),
     array(
        "name" => "inactive", "label" => $GLOBALS['locCompanyInactive'], "type" => "CHECK", "style" => "medium", "listquery" => "", "position" => 2, "default" => 0, "allow_null" => TRUE ),
     array(
        "name" => "company_id", "label" => $GLOBALS['locCOMPVATID'], "type" => "TEXT", "style" => "medium", "listquery" => "", "position" => 1, "default" => FALSE, "allow_null" => TRUE ),
     array(
        "name" => "email", "label" => $GLOBALS['locEMAIL'], "type" => "TEXT", "style" => "medium", "listquery" => "", "position" => 2, "default" => FALSE, "allow_null" => TRUE ),
     array(
        "name" => "customer_no", "label" => $GLOBALS['locCUSTOMERNO'], "type" => "INT", "style" => "medium", "listquery" => "", "position" => 1, "default" => $defaultCustomerNo, "allow_null" => TRUE ),
     array(
        "name" => "default_ref_number", "label" => $GLOBALS['locCUSTOMERDEFAULTREFNO'], "type" => "TEXT", "style" => "medium", "listquery" => "", "position" => 2, "default" => FALSE, "allow_null" => TRUE ),
     array(
        "name" => "street_address", "label" => $GLOBALS['locSTREETADDR'], "type" => "TEXT", "style" => "medium", "listquery" => "", "position" => 0, "default" => FALSE, "allow_null" => TRUE ),
     array(
        "name" => "zip_code", "label" => $GLOBALS['locZIPCODE'], "type" => "TEXT", "style" => "short", "listquery" => "", "position" => 1, "default" => FALSE, "allow_null" => TRUE ),
     array(
        "name" => "city", "label" => $GLOBALS['locCITY'], "type" => "TEXT", "style" => "medium", "listquery" => "", "position" => 2, "default" => FALSE, "allow_null" => TRUE ),
     array(
        "name" => "phone", "label" => $GLOBALS['locPHONE'], "type" => "TEXT", "style" => "medium", "listquery" => "", "position" => 1, "default" => FALSE, "allow_null" => TRUE ),
     array(
        "name" => "fax", "label" => $GLOBALS['locFAX'], "type" => "TEXT", "style" => "medium", "listquery" => "", "position" => 2, "default" => FALSE, "allow_null" => TRUE ),
     array(
        "name" => "gsm", "label" => $GLOBALS['locGSM'], "type" => "TEXT", "style" => "medium", "listquery" => "", "position" => 1, "default" => FALSE, "allow_null" => TRUE ),
     array(
        "name" => "www", "label" => $GLOBALS['locWWW'], "type" => "TEXT", "style" => "medium", "listquery" => "", "position" => 2, "default" => FALSE, "allow_null" => TRUE ),
     array(
        "name" => "billing_address", "label" => $GLOBALS['locBILLADDR'], "type" => "AREA", "style" => "medium", "listquery" => "", "position" => 1, "default" => FALSE, "allow_null" => TRUE ),
     array(
        "name" => "info", "label" => $GLOBALS['locINFO'], "type" => "AREA", "style" => "medium", "listquery" => "", "position" => 2, "default" => FALSE, "allow_null" => TRUE ),
     array(
        "name" => "company_contacts", "label" => $GLOBALS['locCONTACTS'], "type" => "IFORM", "style" => "full", "listquery" => "", "position" => 0, "default" => FALSE, "allow_null" => TRUE, "parent_key" => "company_id" )
    );
break;

case 'company_contact':
case 'company_contacts':
       $strTable = '{prefix}company_contact';
       $strJSONType = 'company_contact';
       $strPrimaryKey = "id";
       $strParentKey = "company_id";
       $astrFormElements =
        array(
         array(
            "name" => "id", "label" => "", "type" => "HID_INT",
            "style" => "medium", "listquery" => "", "position" => 0, "default" => FALSE, "allow_null" => FALSE ),
         array(
            "name" => "contact_person", "label" => $GLOBALS['locCONTACTPERSON'], "type" => "TEXT", "style" => "medium", "listquery" => "", "position" => 0, "default" => FALSE, "allow_null" => FALSE ),
         array(
            "name" => "person_title", "label" => $GLOBALS['locPERSONTITLE'], "type" => "TEXT", "style" => "small", "listquery" => '', "position" => 0, "default" => FALSE, "allow_null" => TRUE ),
         array(
            "name" => "phone", "label" => $GLOBALS['locPHONE'], "type" => "TEXT", "style" => "medium", "listquery" => "", "position" => 0, "default" => FALSE, "allow_null" => TRUE ),
         array(
            "name" => "gsm", "label" => $GLOBALS['locGSM'], "type" => "TEXT", "style" => "medium", "listquery" => "", "position" => 0, "default" => FALSE, "allow_null" => TRUE ),
         array(
            "name" => "email", "label" => $GLOBALS['locEMAIL'], "type" => "TEXT", "style" => "medium", "listquery" => "", "position" => 0, "default" => FALSE, "allow_null" => TRUE )
       );
break;

case 'product':
   $strTable = '{prefix}product';
   $strPrimaryKey = "id";
   $astrSearchFields = 
    array( 
        //array("name" => "first_name", "type" => "TEXT"),
        array("name" => "product_name", "type" => "TEXT")
    );
   $astrFormElements =
    array(
     array(
        "name" => "product_name", "label" => $GLOBALS['locPRODUCTNAME'], "type" => "TEXT", "style" => "medium", "listquery" => "", "position" => 1, "default" => FALSE, "allow_null" => FALSE ),
     array(
        "name" => "description", "label" => $GLOBALS['locPRODUCTDESCRIPTION'], "type" => "TEXT", "style" => "long", "listquery" => "", "position" => 2, "default" => FALSE, "allow_null" => TRUE ),
     array(
        "name" => "product_code", "label" => $GLOBALS['locPRODUCTCODE'], "type" => "TEXT", "style" => "medium", "listquery" => "", "position" => 1, "default" => FALSE, "allow_null" => TRUE ),
     array(
        "name" => "product_group", "label" => $GLOBALS['locPRODUCTGROUP'], "type" => "TEXT", "style" => "medium", "listquery" => "", "position" => 2, "default" => FALSE, "allow_null" => TRUE ),
     array(
        "name" => "internal_info", "label" => $GLOBALS['locINTERNALINFO'], "type" => "AREA", "style" => "xlarge", "listquery" => "", "position" => 0, "default" => FALSE, "allow_null" => TRUE ),
     array(
        "name" => "unit_price", "label" => $GLOBALS['locUNITPRICE'], "type" => "INT", "style" => "medium", "listquery" => "", "position" => 1, "default" => FALSE, "allow_null" => TRUE ),
     array(
        "name" => "discount", "label" => $GLOBALS['locDiscountPercent'], "type" => "INT", "style" => "percent", "listquery" => "", "position" => 2, "default" => 0, "allow_null" => TRUE ),
     array(
        "name" => "type_id", "label" => $GLOBALS['locUNIT'], "type" => "LIST", "style" => "short", "listquery" => "SELECT id, name FROM {prefix}row_type WHERE deleted=0 ORDER BY order_no;", "position" => 0, "default" => "POST", "allow_null" => FALSE ),
     array(
        "name" => "vat_percent", "label" => $GLOBALS['locVATPERCENT'], "type" => "INT", "style" => "short", "listquery" => "", "position" => 1, "default" => FALSE, "allow_null" => TRUE ),
     array(
        "name" => "vat_included", "label" => $GLOBALS['locVATINCLUDED'], "type" => "CHECK", "style" => "medium", "listquery" => "", "position" => 2, "default" => FALSE, "allow_null" => TRUE ),
    );
break;

case 'invoice':
   $strTable = '{prefix}invoice';
   $strListTableAlias = 'i.'; // this is for the search function
   $strPrimaryKey = "id";
   $strParentKey = "invoice_id";
   $strJSONType = 'invoice';
   
   $defaultInvNo = FALSE;
   $defaultRefNo = FALSE;
   
   $arrRefundedInvoice = array('allow_null' => TRUE);
   $arrRefundingInvoice = array('allow_null' => TRUE);
   $intInvoiceId = getRequest('id', 0);
   if ($intInvoiceId)
   {
     $strQuery = 
        "SELECT refunded_invoice_id ".
        "FROM {prefix}invoice ".
        "WHERE id=?"; // ok to maintain links to deleted invoices too
     $intRes = mysql_param_query($strQuery, array($intInvoiceId));
     $strBaseLink = '?' . preg_replace('/&id=\d*/', '', $_SERVER['QUERY_STRING']);
     $strBaseLink = preg_replace('/&/', '&amp;', $strBaseLink);
     if ($intRes) 
     {
       $intRefundedInvoiceId = mysql_fetch_value($intRes);
       if ($intRefundedInvoiceId)
         $arrRefundedInvoice = array(
           "name" => "get", "label" => $GLOBALS['locSHOWREFUNDEDINV'], "type" => "BUTTON", "style" => "custom", "listquery" => "$strBaseLink&amp;id=$intRefundedInvoiceId", "position" => 2, "default" => FALSE, "allow_null" => TRUE 
         );
     }
     $strQuery = 
        "SELECT id ".
        "FROM {prefix}invoice ".
        "WHERE deleted=0 AND refunded_invoice_id=?";
     $intRes = mysql_param_query($strQuery, array($intInvoiceId));
     if ($intRes && ($row = mysql_fetch_assoc($intRes))) 
     {
       $intRefundingInvoiceId = $row['id'];
       if ($intRefundingInvoiceId)
         $arrRefundingInvoice = array(
           "name" => "get", "label" => $GLOBALS['locSHOWREFUNDINGINV'], "type" => "BUTTON", "style" => "custom", "listquery" => "'$strBaseLink&amp;id=$intRefundingInvoiceId", "position" => 2, "default" => FALSE, "allow_null" => TRUE 
         );
     }
   }
   
   $companyOnChange = <<<EOS
onchange = "$.getJSON('json.php?func=get_company&amp;id=' + document.getElementById('company_id').value, function(json) { if (json && json.default_ref_number) document.getElementById('ref_number').value = json.default_ref_number;});"
EOS;

   $getInvoiceNo = <<<EOS
$.getJSON('json.php?func=get_invoice_defaults&amp;id=' + document.getElementById('record_id').value + '&amp;base_id=' + document.getElementById('base_id').value, function(json) { document.getElementById('invoice_no').value = json.invoice_no; document.getElementById('ref_number').value = json.ref_no; $('.save_button').addClass('unsaved'); }); return false;
EOS;

   $locUpdateDates = $GLOBALS['locUpdateDates'];
   $updateDates = <<<EOS
<a class="formbuttonlink" href="#" onclick="$.getJSON('json.php?func=get_invoice_defaults&amp;id=' + document.getElementById('record_id').value + '&amp;base_id=' + document.getElementById('base_id').value, function(json) { document.getElementById('invoice_date').value = json.date; document.getElementById('due_date').value = json.due_date; $('.save_button').addClass('unsaved'); }); return false;">$locUpdateDates</a>
EOS;

   $locNew = $GLOBALS['locNEW'] . '...';
   $locCompName = $GLOBALS['locCOMPNAME'];
   $locEmail = $GLOBALS['locEMAIL'];
   $locPhone = $GLOBALS['locPHONE'];
   $locSave = $GLOBALS['locSAVE'];
   $locClose = $GLOBALS['locCLOSE'];
   $locTitle = $GLOBALS['locNEWCOMPANY'];
   $locMissing = $GLOBALS['locERRVALUEMISSING'];
   $addCompanyCode = <<<EOS
<a class="formbuttonlink" href="#" onclick="add_company({'save': '$locSave', 'close': '$locClose', 'title': '$locTitle', 'missing': '$locMissing'})">$locNew</a>

EOS;
   $popupHTML = <<<EOS
<script type="text/javascript" src="js/add_company.js"></script>
<div id="quick_add_company" class="form_container" style="display: none">
  <div class="small_label">$locCompName</div> <div class="field"><input type="text" id="quick_name" class="medium"></div>
  <div class="small_label">$locEmail</div> <div class="field"><input type="text" id="quick_email" class="medium"></div>
  <div class="small_label">$locPhone</div> <div class="field"><input type="text" id="quick_phone" class="medium"></div>
</div>
EOS;
   
   $invoicePrintChecks = '';
   $invoiceNumberUpdatePrefix = '';
   $invoiceNumberUpdateSuffix = '';
   
   if (getSetting('invoice_warn_if_noncurrent_date'))
   {
     $invoicePrintChecks .= "var d = new Date(); var dt = document.getElementById('invoice_date').value.split('.'); if (parseInt(dt[0]) != d.getDate() || parseInt(dt[1]) != d.getMonth()+1 || parseInt(dt[2]) != d.getYear() + 1900) alert('" . $GLOBALS['locInvoiceDateNonCurrent'] . "'); ";
   }
   $invoicePrintChecks .= "var len = document.getElementById('ref_number').value.length; if (len > 0 && len < 4) alert('" . $GLOBALS['locInvoiceRefNumberTooShort'] . "'); ";
   
   if (getSetting('invoice_add_number') || getSetting('invoice_add_reference_number'))
   {
     $invoiceNumberUpdatePrefix = "$.getJSON('json.php?func=get_invoice_defaults&amp;id=' + document.getElementById('record_id').value + '&amp;base_id=' + document.getElementById('base_id').value, function(json) { ";
     if (getSetting('invoice_add_number')) 
       $invoiceNumberUpdatePrefix .= "var invoice_no = document.getElementById('invoice_no'); if (invoice_no.value == '' || invoice_no.value == 0) invoice_no.value = json.invoice_no; ";
     if (getSetting('invoice_add_reference_number'))
       $invoiceNumberUpdatePrefix .= "var ref_number = document.getElementById('ref_number'); if (ref_number.value == '' || ref_number.value == 0) ref_number.value = json.ref_no; ";
     $invoiceNumberUpdatePrefix .= "$('.save_button').addClass('unsaved'); ";
     $invoiceNumberUpdateSuffix = ' });';
   }
   if (!getSetting('invoice_add_number'))
     $invoiceNumberUpdatePrefix .= "invoice_no = document.getElementById('invoice_no'); if (invoice_no.value == '' || invoice_no.value == 0) alert('" . $GLOBALS['locInvoiceNumberNotDefined'] . "');";
   
   // Print buttons
   $printButtons = array();
   $printButtons2 = array();
   $res = mysql_query_check('SELECT * FROM {prefix}print_template WHERE type=\'invoice\' and inactive=0 ORDER BY order_no');
   $templateCount = mysql_num_rows($res);
   $templateFirstCol = max(floor($templateCount / 2 + 1), 3);
   $rowNum = 0;
   while ($row = mysql_fetch_assoc($res))
   {
     $templateId = $row['id'];
     $printStyle = $row['new_window'] ? 'openwindow' : 'redirect';
     $arr = array('name' => "print$templateId", 'label' => $row['name'], 'type' => 'JSBUTTON', 'style' => $printStyle, 'listquery' => "${invoicePrintChecks}${invoiceNumberUpdatePrefix}save_record('invoice.php?id=_ID_&amp;template=$templateId&amp;func=$strFunc', '$printStyle'); return false;${invoiceNumberUpdateSuffix}", 'position' => 3, 'default' => FALSE, 'allow_null' => TRUE );
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

   $companyListSelect = "SELECT id, IF(STRCMP(company_id,''), CONCAT(company_name, ' (', company_id, ')'), company_name) FROM {prefix}company WHERE deleted=0 AND (inactive=0";
   if ($intInvoiceId && is_numeric($intInvoiceId))
     $companyListSelect .= " OR id IN (SELECT company_id FROM {prefix}invoice i WHERE i.id=$intInvoiceId)";
   $companyListSelect .= ") ORDER BY company_name, company_id";
   
   $intRes = mysql_query_check('SELECT ID from {prefix}base WHERE deleted=0');
   if (mysql_num_rows($intRes) == 1)
     $defaultBase = mysql_fetch_value($intRes);
   else
     $defaultBase = FALSE;
     
   $copyLinkOverride = "copy_invoice.php?func=$strFunc&amp;list=$strList&amp;id=$intInvoiceId";
   
   $updateInvoiceNo = null;
   if (!getSetting('invoice_add_number') || !getSetting('invoice_add_reference_number'))
   {
     $updateInvoiceNo = '<a class="formbuttonlink" href="#" onclick="' . $getInvoiceNo . '">' . $GLOBALS['locGetInvoiceNo'] . '</a>';
   }
   
   $astrFormElements =
    array(
     array(
        "name" => "base_id", "label" => $GLOBALS['locBILLER'], "type" => "LIST", "style" => "medium", "listquery" => "SELECT id, name FROM {prefix}base WHERE deleted=0", "position" => 1, "default" => $defaultBase, "allow_null" => FALSE ),
     array(
        "name" => "name", "label" => $GLOBALS['locINVNAME'], "type" => "TEXT", "style" => "medium", "listquery" => "", "position" => 2, "default" => FALSE, "allow_null" => TRUE ),
     array(
        "name" => "company_id", "label" => $GLOBALS['locPAYER'], "type" => "LIST", "style" => "medium", "listquery" => $companyListSelect, "position" => 1, "default" => FALSE, 'allow_null' => TRUE, 'attached_elem' => $addCompanyCode, 'elem_attributes' => $companyOnChange ),
     array(
        "name" => "reference", "label" => $GLOBALS['locCLIENTSREFERENCE'], "type" => "TEXT", "style" => "medium", "listquery" => "", "position" => 2, "default" => FALSE, "allow_null" => TRUE ),
     array(
        "name" => "invoice_no", "label" => $GLOBALS['locINVNO'], "type" => "INT", "style" => "medium hidezerovalue", "listquery" => "", "position" => 1, "default" => $defaultInvNo, "allow_null" => TRUE ),
     array(
        "name" => "ref_number", "label" => $GLOBALS['locREFNO'], "type" => "INT", "style" => "medium hidezerovalue", "listquery" => "", "position" => 2, "default" => $defaultRefNo, 'attached_elem' => $updateInvoiceNo, "allow_null" => TRUE ),
     array(
        "name" => "invoice_date", "label" => $GLOBALS['locINVDATE'], "type" => "INTDATE", "style" => "date", "listquery" => "", "position" => 1, "default" => "DATE_NOW", "allow_null" => FALSE ),
     array(
        "name" => "due_date", "label" => $GLOBALS['locDUEDATE'], "type" => "INTDATE", "style" => "date", "listquery" => "", "position" => 2, "default" => 'DATE_NOW+' . getSetting('invoice_payment_days'), 'attached_elem' => $updateDates, "allow_null" => FALSE ),
     array(
        "name" => "state_id", "label" => $GLOBALS['locSTATUS'], "type" => "LIST", "style" => "medium", "listquery" => "SELECT id, name FROM {prefix}invoice_state WHERE deleted=0 ORDER BY order_no", "position" => 1, "default" => 1, "allow_null" => FALSE ),
     array(
        "name" => "payment_date", "label" => $GLOBALS['locPAYDATE'], "type" => "INTDATE", "style" => "date", "listquery" => "", "position" => 2, "default" => NULL, "allow_null" => TRUE ),
     array(
        "name" => "archived", "label" => $GLOBALS['locARCHIVED'], "type" => "CHECK", "style" => "medium", "listquery" => "", "position" => 1, "default" => 0, "allow_null" => TRUE ),
     array(
        "name" => "info", "label" => $GLOBALS['locVisibleInfo'], "type" => "AREA", "style" => "medium", "listquery" => "", "position" => 1, "default" => FALSE, "allow_null" => TRUE ),
     array(
        "name" => "internal_info", "label" => $GLOBALS['locINTERNALINFO'], "type" => "AREA", "style" => "medium", "listquery" => "", "position" => 2, "default" => FALSE, "allow_null" => TRUE ),
        
     array(
        "name" => "refundinvoice", "label" => $GLOBALS['locREFUNDINV'], "type" => "BUTTON", "style" => "redirect", "listquery" => "copy_invoice.php?func=$strFunc&list=$strList&id=_ID_&refund=1", "position" => 1, "default" => FALSE, "allow_null" => TRUE ),
     $arrRefundedInvoice,
     isset($printButtons[0]) ? $printButtons[0] : array(),   
     isset($printButtons2[0]) ? $printButtons2[0] : array(),   
     array(
        "name" => "addreminderfees", "label" => $GLOBALS['locADDREMINDERFEES'], "type" => "BUTTON", "style" => "redirect", "listquery" => "add_reminder_fees.php?func=$strFunc&list=$strList&id=_ID_", "position" => 1, "default" => FALSE, "allow_null" => TRUE ),
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
        "name" => "invoice_rows", "label" => $GLOBALS['locINVROWS'], "type" => "IFORM", "style" => "xfull", "listquery" => "", "position" => 0, "default" => FALSE, "allow_null" => TRUE, "parent_key" => "invoice_id" );
break;
case 'invoice_row':
case 'invoice_rows':
   $strTable = '{prefix}invoice_row';
   $strJSONType = 'invoice_row';
   $strPrimaryKey = "id";
   $strParentKey = "invoice_id";
   $strOrder = 'ORDER BY {prefix}invoice_row.order_no, {prefix}invoice_row.row_date';
   $clearRowValuesAfterAdd = getSetting('invoice_clear_row_values_after_add');
   
   $intInvoiceId = getRequest('invoice_id', 0);
   $productOnChange = <<<EOS
onchange = "var form_id = this.form.id; $.getJSON('json.php?func=get_product&amp;id=' + this.value, function(json) { 
  if (!json.id) return; 
  
  document.getElementById(form_id + '_description').value = json.description;
  
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
  document.getElementById(form_id + '_price').value = json.unit_price.replace('.', ','); 
  document.getElementById(form_id + '_discount').value = json.discount.replace('.', ','); 
  document.getElementById(form_id + '_vat').value = json.vat_percent.replace('.', ','); 
  document.getElementById(form_id + '_vat_included').checked = json.vat_included == 1 ? true : false;
});"
EOS;

   $multiplierColumn = 'pcs';
   $priceColumn = 'price';
   $discountColumn = 'discount';
   $VATColumn = 'vat';
   $VATIncludedColumn = 'vat_included';
   $showPriceSummary = TRUE;

   $astrFormElements =
    array(
     array(
        "name" => "id", "label" => "", "type" => "HID_INT", "style" => "medium", "listquery" => "", "position" => 0, "default" => FALSE, "allow_null" => FALSE ),
     array(
        "name" => "product_id", "label" => $GLOBALS['locPRODUCTNAME'], "type" => "LIST", "style" => "medium", "listquery" => "SELECT id, product_name FROM {prefix}product WHERE deleted=0 ORDER BY product_name", "position" => 0, "default" => FALSE, "allow_null" => TRUE, 'elem_attributes' => $productOnChange ),
     array(
        "name" => "description", "label" => $GLOBALS['locROWDESC'], "type" => "TEXT", "style" => "medium", "listquery" => "", "position" => 0, "default" => '', "allow_null" => TRUE ),
     array(
        "name" => "row_date", "label" => $GLOBALS['locDATE'], "type" => "INTDATE", "style" => "date", "listquery" => "", "position" => 0, "default" => 'DATE_NOW', "allow_null" => FALSE ),
     array(
        "name" => "pcs", "label" => $GLOBALS['locPCS'], "type" => "INT", "style" => "count", "listquery" => "", "position" => 0, "default" => FALSE, "allow_null" => FALSE ),
     array(
        "name" => "type_id", "label" => $GLOBALS['locUNIT'], "type" => "LIST", "style" => "short", "listquery" => "SELECT id, name FROM {prefix}row_type WHERE deleted=0 ORDER BY order_no", "position" => 0, "default" => 'POST', "allow_null" => TRUE ),
     array(
        "name" => "price", "label" => $GLOBALS['locPRICE'], "type" => "INT", "style" => "currency", "listquery" => "", "position" => 0, "default" => 'POST', "allow_null" => FALSE ),
     array(
        "name" => "discount", "label" => $GLOBALS['locDiscount'], "type" => "INT", "style" => "percent", "listquery" => "", "position" => 0, "default" => 0, "allow_null" => TRUE ),
     array(
        "name" => "vat", "label" => $GLOBALS['locVAT'], "type" => "INT", "style" => "percent", "listquery" => "", "position" => 0, "default" => getSetting('invoice_default_vat_percent'), "allow_null" => TRUE ),
     array(
        "name" => "vat_included", "label" => $GLOBALS['locVATINC'], "type" => "CHECK", "style" => "xshort", "listquery" => "", "position" => 0, "default" => 0, "allow_null" => TRUE ),
     array(
        "name" => "order_no", "label" => $GLOBALS['locROWNO'], "type" => "INT", "style" => "tiny", "listquery" => "SELECT max(order_no)+5 FROM {prefix}invoice_row WHERE deleted=0 AND invoice_id=_PARENTID_", "position" => 0, "default" => "ADD+5", "allow_null" => TRUE ),
     array(
        "name" => "row_sum", "label" => $GLOBALS['locROWTOTAL'], "type" => "ROWSUM", "style" => "currency", "listquery" => "", "position" => 0, "default" => "", "allow_null" => TRUE )
   );
break;
/******************************************************************************
    END SEARCH FORMS - HAUN LOMAKKEET
******************************************************************************/

/******************************************************************************
    SYSTEM FORMS - SYSTEEMILOMAKKEET
******************************************************************************/
case 'base_info':
   $strTable = '{prefix}base';
   $strPrimaryKey = "id";

   $title = $GLOBALS['locBaseLogoTitle'];   
   $openPopJS = <<<EOF
popup_dialog('base_logo.php?func=edit&amp;id=_ID_', '$(\\'img\\').attr(\\'src\\', \\'base_logo.php?func=view&id=_ID_\\')', '$title', event, 600, 400); return false;
EOF;
   
   $astrFormElements =
    array(
     array(
        "name" => "name", "label" => $GLOBALS['locBaseName'], "type" => "TEXT", "style" => "medium", "listquery" => "", "position" => 1, "default" => FALSE, "allow_null" => FALSE ),
     array(
        "name" => "company_id", "label" => $GLOBALS['locCOMPVATID'], "type" => "TEXT", "style" => "medium", "listquery" => "", "position" => 2, "default" => FALSE, "allow_null" => FALSE ),
     array(
        "name" => "contact_person", "label" => $GLOBALS['locCONTACTPERS'], "type" => "TEXT", "style" => "medium", "listquery" => "", "position" => 1, "default" => FALSE, "allow_null" => TRUE ),
     array(
        "name" => "vat_registered", "label" => $GLOBALS['locVATREGISTERED'], "type" => "CHECK", "style" => "short", "listquery" => "", "position" => 2, "default" => FALSE, "allow_null" => TRUE ),
     array(
        "name" => "email", "label" => $GLOBALS['locEMAIL'], "type" => "TEXT", "style" => "medium", "listquery" => "", "position" => 1, "default" => FALSE, "allow_null" => TRUE ),
     array(
        "name" => "www", "label" => $GLOBALS['locWWW'], "type" => "TEXT", "style" => "medium", "listquery" => "", "position" => 2, "default" => FALSE, "allow_null" => TRUE ),
     array(
        "name" => "street_address", "label" => $GLOBALS['locSTREETADDR'], "type" => "TEXT", "style" => "medium", "listquery" => "", "position" => 0, "default" => FALSE, "allow_null" => TRUE ),
     array(
        "name" => "zip_code", "label" => $GLOBALS['locZIPCODE'], "type" => "TEXT", "style" => "short", "listquery" => "", "position" => 1, "default" => FALSE, "allow_null" => TRUE ),
     array(
        "name" => "city", "label" => $GLOBALS['locCITY'], "type" => "TEXT", "style" => "medium", "listquery" => "", "position" => 2, "default" => FALSE, "allow_null" => TRUE ),
     array(
        "name" => "phone", "label" => $GLOBALS['locPHONE'], "type" => "TEXT", "style" => "medium", "listquery" => "", "position" => 1, "default" => FALSE, "allow_null" => TRUE ),
     array(
        "name" => "banksep1", "label" => $GLOBALS['locFIRSTBANK'], "type" => "LABEL"),
     array(
        "name" => "bank_name", "label" => $GLOBALS['locBANK'], "type" => "TEXT", "style" => "medium", "listquery" => "", "position" => 1, "default" => FALSE, "allow_null" => FALSE ),
     array(
        "name" => "bank_account", "label" => $GLOBALS['locACCOUNT'], "type" => "TEXT", "style" => "medium", "listquery" => "", "position" => 2, "default" => FALSE, "allow_null" => FALSE ),
     array(
        "name" => "bank_iban", "label" => $GLOBALS['locACCOUNTIBAN'], "type" => "TEXT", "style" => "medium", "listquery" => "", "position" => 1, "default" => FALSE, "allow_null" => FALSE ),
     array(
        "name" => "bank_swiftbic", "label" => $GLOBALS['locSWIFTBIC'], "type" => "TEXT", "style" => "medium", "listquery" => "", "position" => 2, "default" => FALSE, "allow_null" => FALSE ),
     array(
        "name" => "banksep2", "label" => $GLOBALS['locSECONDBANK'], "type" => "LABEL"),
     array(
        "name" => "bank_name2", "label" => $GLOBALS['locBANK'], "type" => "TEXT", "style" => "medium", "listquery" => "", "position" => 1, "default" => FALSE, "allow_null" => TRUE ),
     array(
        "name" => "bank_account2", "label" => $GLOBALS['locACCOUNT'], "type" => "TEXT", "style" => "medium", "listquery" => "", "position" => 2, "default" => FALSE, "allow_null" => TRUE ),
     array(
        "name" => "bank_iban2", "label" => $GLOBALS['locACCOUNTIBAN'], "type" => "TEXT", "style" => "medium", "listquery" => "", "position" => 1, "default" => FALSE, "allow_null" => TRUE ),
     array(
        "name" => "bank_swiftbic2", "label" => $GLOBALS['locSWIFTBIC'], "type" => "TEXT", "style" => "medium", "listquery" => "", "position" => 2, "default" => FALSE, "allow_null" => TRUE ),
     array(
        "name" => "banksep3", "label" => $GLOBALS['locTHIRDBANK'], "type" => "LABEL"),
     array(
        "name" => "bank_name3", "label" => $GLOBALS['locBANK'], "type" => "TEXT", "style" => "medium", "listquery" => "", "position" => 1, "default" => FALSE, "allow_null" => TRUE ),
     array(
        "name" => "bank_account3", "label" => $GLOBALS['locACCOUNT'], "type" => "TEXT", "style" => "medium", "listquery" => "", "position" => 2, "default" => FALSE, "allow_null" => TRUE ),
     array(
        "name" => "bank_iban3", "label" => $GLOBALS['locACCOUNTIBAN'], "type" => "TEXT", "style" => "medium", "listquery" => "", "position" => 1, "default" => FALSE, "allow_null" => TRUE ),
     array(
        "name" => "bank_swiftbic3", "label" => $GLOBALS['locSWIFTBIC'], "type" => "TEXT", "style" => "medium", "listquery" => "", "position" => 2, "default" => FALSE, "allow_null" => TRUE ),
     array(
        "name" => "emailsep", "label" => $GLOBALS['locBaseEmailTitle'], "type" => "LABEL"),
     array(
        "name" => "invoice_email_from", "label" => $GLOBALS['locBaseEmailFrom'], "type" => "TEXT", "style" => "medium", "listquery" => "", "position" => 1, "default" => FALSE, "allow_null" => TRUE ),
     array(
        "name" => "invoice_email_bcc", "label" => $GLOBALS['locBaseEmailBCC'], "type" => "TEXT", "style" => "medium", "listquery" => "", "position" => 2, "default" => FALSE, "allow_null" => TRUE ),
     array(
        "name" => "invoice_email_subject", "label" => $GLOBALS['locBaseEmailSubject'], "type" => "TEXT", "style" => "long", "listquery" => "", "position" => 0, "default" => FALSE, "allow_null" => TRUE ),
     array(
        "name" => "invoice_email_body", "label" => $GLOBALS['locBaseEmailBody'], "type" => "AREA", "style" => "email", "listquery" => "", "position" => 0, "default" => FALSE, "allow_null" => TRUE ),
     array(
        "name" => "logosep", "label" => $GLOBALS['locBaseLogoTitle'], "type" => "LABEL"),
     array(
        "name" => "logo", "label" => '', "type" => "IMAGE", "style" => "image", "listquery" => 'base_logo.php?func=view&amp;id=_ID_', "position" => 0, "default" => FALSE, "allow_null" => TRUE ),
     array(
        "name" => "edit_logo", "label" => $GLOBALS['locBaseChangeImage'], "type" => "JSBUTTON", "style" => "medium", "listquery" => $openPopJS, "position" => 1, "default" => FALSE, "allow_null" => TRUE ),
     array(
        "name" => "logo_left", "label" => $GLOBALS['locBaseLogoLeft'], "type" => "INT", "style" => "measurement", "listquery" => "", "position" => 1, "default" => NULL, "allow_null" => TRUE ),
     array(
        "name" => "logo_top", "label" => $GLOBALS['locBaseLogoTop'], "type" => "INT", "style" => "measurement", "listquery" => "", "position" => 2, "default" => NULL, "allow_null" => TRUE ),
     array(
        "name" => "logo_width", "label" => $GLOBALS['locBaseLogoWidth'], "type" => "INT", "style" => "measurement", "listquery" => "", "position" => 1, "default" => NULL, "allow_null" => TRUE ),
     array(
        "name" => "logo_bottom_margin", "label" => $GLOBALS['locBaseLogoBottomMargin'], "type" => "INT", "style" => "measurement", "listquery" => "", "position" => 2, "default" => NULL, "allow_null" => TRUE ),
    );
break;

case 'invoice_state':
    $strTable = '{prefix}invoice_state';
    $strPrimaryKey = "id";
    
    $elem_attributes = '';
    $intId = getRequest('id', FALSE);
    if ($intId && $intId <= 7)
    {
      $elem_attributes = 'readonly';
      $strPrimaryKey = '';
      $astrFormElements =
        array(
         array(
            "name" => "label", "label" => $GLOBALS['locSYSTEMONLY'], "type" => "LABEL")
        );
    }
    else
    {
      $astrFormElements =
        array(
         array(
            "name" => "name", "label" => $GLOBALS['locSTATUS'], "type" => "TEXT", "style" => "medium", "listquery" => "", "position" => 1, "default" => FALSE, "allow_null" => FALSE, "elem_attributes" => $elem_attributes ),
         array(
            "name" => "order_no", "label" => $GLOBALS['locORDERNO'], "type" => "INT", "style" => "short", "listquery" => "", "position" => 2, "default" => FALSE, "allow_null" => FALSE, "elem_attributes" => $elem_attributes )
       );
     }
break;

case 'row_type':
    $strTable = '{prefix}row_type';
    $strPrimaryKey = "id";
    $astrFormElements =
        array(
         array(
            "name" => "name", "label" => $GLOBALS['locROWTYPE'], "type" => "TEXT", "style" => "medium", "listquery" => "", "position" => 1, "default" => FALSE, "allow_null" => FALSE ),
         array(
            "name" => "order_no", "label" => $GLOBALS['locORDERNO'], "type" => "INT", "style" => "short", "listquery" => "", "position" => 2, "default" => FALSE, "allow_null" => FALSE )
    );
break;

case 'session_type':
    $levelsAllowed = array(99);
    $strTable = '{prefix}session_type';
    $strPrimaryKey = "id";
    $astrFormElements =
        array(
            array(
            "name" => "name", "label" => $GLOBALS['locSESSIONTYPE'], "type" => "TEXT", "style" => "medium", "listquery" => "", "position" => 1, "default" => FALSE, "allow_null" => FALSE ),
            array(
            "name" => "order_no", "label" => $GLOBALS['locORDERNO'], "type" => "INT", "style" => "short", "listquery" => "", "position" => 2, "default" => FALSE, "allow_null" => FALSE ),
            array(
            "name" => "access_level", "label" => $GLOBALS['locACCESSLEVEL'], "type" => "INT", "style" => "short", "listquery" => "", "position" => 1, "default" => "1", "allow_null" => FALSE )
    );
break;

case 'user':
    $levelsAllowed = array(99);
    $strTable = '{prefix}users';
    $strPrimaryKey = "id";
    $astrFormElements =
        array(
            array(
            "name" => "name", "label" => $GLOBALS['locUSERNAME'], "type" => "TEXT", "style" => "medium", "listquery" => "", "position" => 1, "default" => FALSE, "allow_null" => FALSE ), 
            array(
            "name" => "login", "label" => $GLOBALS['locLOGONNAME'], "type" => "TEXT", "style" => "medium", "listquery" => "", "position" => 1, "default" => FALSE, "allow_null" => FALSE ),
            array(
            "name" => "passwd", "label" => $GLOBALS['locPASSWD'], "type" => "PASSWD", "style" => "medium", "listquery" => "", "position" => 2, "default" => FALSE, "allow_null" => TRUE ),
            array(
            "name" => "type_id", "label" => $GLOBALS['locTYPE'], "type" => "LIST", "style" => "medium", "listquery" => "SELECT id, name FROM {prefix}session_type WHERE deleted=0 ORDER BY order_no", "position" => 0, "default" => FALSE, "allow_null" => FALSE )
    );
break;

case 'print_template':
    $strTable = '{prefix}print_template';
    $strPrimaryKey = "id";
    
    $elem_attributes = '';
    $astrFormElements =
      array(
        array(
          "name" => "type", "label" => $GLOBALS['locPrintTemplateType'], "type" => "LIST", "style" => "medium", "listquery" => "SELECT 'invoice' as id, '". $GLOBALS['locPrintTemplateTypeInvoice'] . "' as name", "position" => 1, "default" => FALSE, "allow_null" => FALSE ),
        array(
          "name" => "order_no", "label" => $GLOBALS['locORDERNO'], "type" => "INT", "style" => "short", "listquery" => "", "position" => 2, "default" => FALSE, "allow_null" => FALSE ),
        array(
          "name" => "name", "label" => $GLOBALS['locPrintTemplateName'], "type" => "TEXT", "style" => "medium", "listquery" => "", "position" => 1, "default" => FALSE, "allow_null" => FALSE ),
        array(
          "name" => "filename", "label" => $GLOBALS['locPrintTemplateFileName'], "type" => "TEXT", "style" => "medium", "listquery" => "", "position" => 2, "default" => FALSE, "allow_null" => TRUE ),
        array(
          "name" => "parameters", "label" => $GLOBALS['locPrintTemplateParameters'], "type" => "TEXT", "style" => "medium", "listquery" => "", "position" => 1, "default" => FALSE, "allow_null" => TRUE ),
        array(
          "name" => "output_filename", "label" => $GLOBALS['locPrintTemplateOutputFileName'], "type" => "TEXT", "style" => "medium", "listquery" => "", "position" => 2, "default" => FALSE, "allow_null" => TRUE ),
        array(
          "name" => "new_window", "label" => $GLOBALS['locPrintTemplateOpenInNewWindow'], "type" => "CHECK", "style" => "medium", "listquery" => "", "position" => 1, "default" => FALSE, "allow_null" => TRUE ),
       array(
          "name" => "inactive", "label" => $GLOBALS['locPrintTemplateInactive'], "type" => "CHECK", "style" => "medium", "listquery" => "", "position" => 2, "default" => 0, "allow_null" => TRUE ),
     );
     break;
}

// Clean up the array
$akeys = array('name', 'type', 'position', 'style', 'label', 'default', 'parent_key', 'listquery', 'allow_null', 'elem_attributes');
for( $j = 0; $j < count($astrFormElements); $j++ ) {
  for( $i = 0; $i < count($akeys); $i++ ) {
    if (!isset($astrFormElements[$j][$akeys[$i]]))
      $astrFormElements[$j][$akeys[$i]] = FALSE;
  }
}


?>
