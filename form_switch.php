<?php
/*******************************************************************************
VLLasku: web-based invoicing application.
Copyright (C) 2010 Ere Maijala

Portions based on:
PkLasku : web-based invoicing software.
Copyright (C) 2004-2008 Samu Reinikainen

This program is free software. See attached LICENSE.

*******************************************************************************/

/*******************************************************************************
VLLasku: web-pohjainen laskutusohjelma.
Copyright (C) 2010 Ere Maijala

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
 IFORM : form in iframe
 BUTTON : button for various events
 
***********************************************************************/

$strListTableAlias = '';
$strOrder = '';
switch ( $strForm ) {

case 'company':
   $strTable = '{prefix}company';
   $strPrimaryKey = 'id';
   $astrSearchFields = 
    array( 
        array("name" => "company_name", "type" => "TEXT")
    );
    
   $defaultCustomerNo = FALSE;
   if (_ADD_CUSTOMER_NUMBER_)
   {
     $strQuery = "SELECT max(customer_no) FROM {prefix}company";
     $intRes = mysql_query_check($strQuery);
     $intInvNo = mysql_result($intRes, 0, 0) + 1;
     $defaultCustomerNo = $intInvNo;
   }
    
   $astrFormElements =
    array(
     array("label" => $GLOBALS['locLABELCONTACTINFO'], "type" => "LABEL"),
     array(
        "name" => "company_name", "label" => $GLOBALS['locCOMPNAME'], "type" => "TEXT", "style" => "medium", "listquery" => "", "position" => 1, "default" => FALSE, "allow_null" => FALSE ),
     array(
        "name" => "company_id", "label" => $GLOBALS['locCOMPVATID'], "type" => "TEXT", "style" => "medium", "listquery" => "", "position" => 2, "default" => FALSE, "allow_null" => TRUE ),
     array(
        "name" => "email", "label" => $GLOBALS['locEMAIL'], "type" => "TEXT", "style" => "medium", "listquery" => "", "position" => 1, "default" => FALSE, "allow_null" => TRUE ),
     array(
        "name" => "customer_no", "label" => $GLOBALS['locCUSTOMERNO'], "type" => "INT", "style" => "medium", "listquery" => "", "position" => 2, "default" => $defaultCustomerNo, "allow_null" => TRUE ),
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
        "name" => "company_contact", "label" => $GLOBALS['locCONTACTS'], "type" => "IFORM", "style" => "full", "listquery" => "", "position" => 0, "default" => FALSE, "allow_null" => TRUE, "parent_key" => "company_id" )
    );
break;

case 'company_contact':
       $strTable = '{prefix}company_contact';
       $strPrimaryKey = "id";
       $strParentKey = "company_id";
       $strMainForm = "iform.php?selectform=company_contact";
       $astrFormElements =
        array(
         array(
            "name" => "id", "label" => "", "type" => "HID_INT",
            "style" => "medium", "listquery" => "", "position" => 0, "default" => FALSE, "allow_null" => FALSE ),
         array(
            "name" => "contact_person", "label" => $GLOBALS['locCONTACTPERSON'], "type" => "TEXT", "style" => "medium", "listquery" => "", "position" => 0, "default" => FALSE, "allow_null" => FALSE ),
         array(
            "name" => "person_title", "label" => $GLOBALS['locPERSONTITLE'], "type" => "TEXT", "style" => "medium", "listquery" => "SELECT id, name FROM {prefix}person_title ORDER BY order_no;", "position" => 0, "default" => FALSE, "allow_null" => TRUE ),
         array(
            "name" => "phone", "label" => $GLOBALS['locPHONE'], "type" => "TEXT", "style" => "medium", "listquery" => "", "position" => 0, "default" => FALSE, "allow_null" => TRUE ),
         array("type" => "NEWLINE"),
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
        "name" => "internal_info", "label" => $GLOBALS['locINTERNALINFO'], "type" => "AREA", "style" => "medium", "listquery" => "", "position" => 0, "default" => FALSE, "allow_null" => TRUE ),
     array(
        "name" => "unit_price", "label" => $GLOBALS['locUNITPRICE'], "type" => "INT", "style" => "medium", "listquery" => "", "position" => 2, "default" => FALSE, "allow_null" => TRUE ),
     array(
        "name" => "type_id", "label" => $GLOBALS['locUNIT'], "type" => "LIST", "style" => "short", "listquery" => "SELECT id, name FROM {prefix}row_type ORDER BY order_no;", "position" => 0, "default" => "POST", "allow_null" => FALSE ),
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
   
   $defaultInvNo = FALSE;
   $defaultRefNo = FALSE;
   if (_ADD_INVOICE_NUMBER_ || _ADD_REFERENCE_NUMBER_)
   {
     $strQuery = "SELECT max(invoice_no) FROM {prefix}invoice";
     $intRes = mysql_query_check($strQuery);
     $intInvNo = mysql_result($intRes, 0, 0) + 1;
     if (_ADD_INVOICE_NUMBER_)
       $defaultInvNo = $intInvNo;
     if (_ADD_REFERENCE_NUMBER_)
       $defaultRefNo = $intInvNo . miscCalcCheckNo($intInvNo);
   }
   
   $arrRefundedInvoice = array('allow_null' => TRUE);
   $arrRefundingInvoice = array('allow_null' => TRUE);
   $intInvoiceId = getRequest('id', 0);
   if ($intInvoiceId)
   {
     $strQuery = 
        "SELECT refunded_invoice_id ".
        "FROM {prefix}invoice ".
        "WHERE id = ?";
     $intRes = mysql_param_query($strQuery, array($intInvoiceId));
     $strBaseLink = '?' . preg_replace('/&id=\d*/', '', $_SERVER['QUERY_STRING']);
     if( $intRes ) 
     {
       $intRefundedInvoiceId = mysql_result($intRes, 0, "refunded_invoice_id");
       if ($intRefundedInvoiceId)
         $arrRefundedInvoice = array(
           "name" => "get", "label" => $GLOBALS['locSHOWREFUNDEDINV'], "type" => "BUTTON", "style" => "medium", "listquery" => "'$strBaseLink&amp;id=$intRefundedInvoiceId', '_self'", "position" => 2, "default" => FALSE, "allow_null" => TRUE 
         );
     }
     $strQuery = 
        "SELECT id ".
        "FROM {prefix}invoice ".
        "WHERE refunded_invoice_id = ?";
     $intRes = mysql_param_query($strQuery, array($intInvoiceId));
     if( $intRes && ($row = mysql_fetch_assoc($intRes))) 
     {
       $intRefundingInvoiceId = $row['id'];
       if ($intRefundingInvoiceId)
         $arrRefundingInvoice = array(
           "name" => "get", "label" => $GLOBALS['locSHOWREFUNDINGINV'], "type" => "BUTTON", "style" => "medium", "listquery" => "'$strBaseLink&amp;id=$intRefundingInvoiceId', '_self'", "position" => 2, "default" => FALSE, "allow_null" => TRUE 
         );
     }
   }
   
   $astrFormElements =
    array(
     array(
        "name" => "base_id", "label" => $GLOBALS['locBILLER'], "type" => "LIST", "style" => "medium", "listquery" => "SELECT id, name FROM {prefix}base ORDER BY name;", "position" => 1, "default" => 2, "allow_null" => FALSE ),
     $arrRefundedInvoice,
     array(
        "name" => "name", "label" => $GLOBALS['locINVNAME'], "type" => "TEXT", "style" => "medium", "listquery" => "", "position" => 1, "default" => FALSE, "allow_null" => TRUE ),
     $arrRefundingInvoice,
     array(
        "name" => "company_id", "label" => $GLOBALS['locPAYER'], "type" => "LIST", "style" => "medium", "listquery" => "SELECT id, company_name FROM {prefix}company ORDER BY company_name;", "position" => 1, "default" => FALSE, "allow_null" => FALSE ),
     array(
        "name" => "reference", "label" => $GLOBALS['locCLIENTSREFERENCE'], "type" => "TEXT", "style" => "medium", "listquery" => "", "position" => 2, "default" => FALSE, "allow_null" => TRUE ),
     array(
        "name" => "invoice_date", "label" => $GLOBALS['locINVDATE'], "type" => "INTDATE", "style" => "date", "listquery" => "", "position" => 1, "default" => "DATE_NOW", "allow_null" => FALSE ),
     array(
        "name" => "due_date", "label" => $GLOBALS['locDUEDATE'], "type" => "INTDATE", "style" => "date", "listquery" => "", "position" => 2, "default" => 'DATE_NOW+' . _PAYMENT_DAYS_, "allow_null" => FALSE ),
     array(
        "name" => "invoice_no", "label" => $GLOBALS['locINVNO'], "type" => "INT", "style" => "medium", "listquery" => "", "position" => 1, "default" => $defaultInvNo, "allow_null" => TRUE ),
     array(
        "name" => "ref_number", "label" => $GLOBALS['locREFNO'], "type" => "INT", "style" => "medium", "listquery" => "", "position" => 2, "default" => $defaultRefNo, "allow_null" => TRUE ),
     array(
        "name" => "state_id", "label" => $GLOBALS['locSTATUS'], "type" => "LIST", "style" => "medium", "listquery" => "SELECT id, name FROM {prefix}invoice_state ORDER BY order_no;", "position" => 1, "default" => 1, "allow_null" => FALSE ),
     array(
        "name" => "payment_date", "label" => $GLOBALS['locPAYDATE'], "type" => "INTDATE", "style" => "date", "listquery" => "", "position" => 2, "default" => NULL, "allow_null" => TRUE ),
     array(
        "name" => "archived", "label" => $GLOBALS['locARCHIVED'], "type" => "CHECK", "style" => "medium", "listquery" => "", "position" => 1, "default" => 0, "allow_null" => TRUE ),
     array(
        "name" => "get", "label" => $GLOBALS['locGETINVNO'], "type" => "BUTTON", "style" => "medium", "listquery" => "'get_invoiceno.php?id=_ID_', '_new'", "position" => 1, "default" => FALSE, "allow_null" => TRUE ),
     array(
        "name" => "get", "label" => $GLOBALS['locPRINTINV'], "type" => "BUTTON", "style" => "medium", "listquery" => "'invoice.php?id=_ID_', '_self'", "position" => 2, "default" => FALSE, "allow_null" => TRUE ),
     array(
        "name" => "get", "label" => $GLOBALS['locCOPYINV'], "type" => "BUTTON", "style" => "medium", "listquery" => "'copy_invoice.php?func=$strFunc&amp;list=$strList&amp;id=_ID_', '_self'", "position" => 1, "default" => FALSE, "allow_null" => TRUE ),
     array(
        "name" => "get", "label" => $GLOBALS['locADDREMINDERFEES'], "type" => "BUTTON", "style" => "medium", "listquery" => "'add_reminder_fees.php?id=_ID_', '_self'", "position" => 2, "default" => FALSE, "allow_null" => TRUE ),
     array(
        "name" => "get", "label" => $GLOBALS['locREFUNDINV'], "type" => "BUTTON", "style" => "medium", "listquery" => "'copy_invoice.php?func=$strFunc&amp;list=$strList&amp;id=_ID_&amp;refund=1', '_self'", "position" => 2, "default" => FALSE, "allow_null" => TRUE ),
     array(
        "name" => "invoice_rows", "label" => $GLOBALS['locINVROWS'], "type" => "IFORM", "style" => "xfull", "listquery" => "", "position" => 0, "default" => FALSE, "allow_null" => TRUE, "parent_key" => "invoice_id" )
    );
break;
case 'invoice_rows':
   $strTable = '{prefix}invoice_row';
   $strPrimaryKey = "id";
   $strParentKey = "invoice_id";
   $strMainForm = "iform.php?selectform=invoice_rows";
   $strOrder = 'ORDER BY {prefix}invoice_row.order_no, {prefix}invoice_row.row_date';
   
   $intProductId = getRequest('new_product', 0);
   $strDescription = '';
   $intTypeId = 'POST';
   $intPrice = 'POST';
   $intVAT = _DEFAULT_VAT_;
   $intVATIncluded = 0;
   if ($intProductId)
   {
     // Retrieve default values from the specified product
     $strQuery = 
        "SELECT * ".
        "FROM {prefix}product ".
        "WHERE id = ?";
     $intRes = mysql_param_query($strQuery, array($intProductId));
     if ($row = mysql_fetch_assoc($intRes)) 
     {
       $strDescription = trim($row['description']);
       $intTypeId = $row['type_id'];
       $intPrice = $row['unit_price'];
       $intVAT = $row['vat_percent'];
       $intVATIncluded = $row['vat_included'];
     }
   }
   
   $intInvoiceId = getRequest('invoice_id', 0);
   $productOnChange = <<<EOS
onChange = "var loc = new String(window.location); loc = loc.replace(/&new_product=\d+/, '').replace(/&invoice_id=\d+/, ''); loc += '&invoice_id=$intInvoiceId&new_product=' + document.forms[0].product_id.value; window.location = loc;"
EOS;

   $astrFormElements =
    array(
     array(
        "name" => "id", "label" => "", "type" => "HID_INT",
        "style" => "medium", "listquery" => "", "position" => 0, "default" => FALSE, "allow_null" => FALSE ),
     array(
        "name" => "product_id", "label" => $GLOBALS['locPRODUCTNAME'], "type" => "LIST", "style" => "small", "listquery" => "SELECT id, product_name FROM {prefix}product ORDER BY product_name;", "position" => 0, "default" => $intProductId, "allow_null" => TRUE, 'elem_attributes' => $productOnChange ),
     array(
        "name" => "description", "label" => $GLOBALS['locROWDESC'], "type" => "TEXT", "style" => "medium", "listquery" => "", "position" => 0, "default" => $strDescription, "allow_null" => TRUE ),
     array(
        "name" => "row_date", "label" => $GLOBALS['locDATE'], "type" => "INTDATE", "style" => "date", "listquery" => "", "position" => 0, "default" => 'DATE_NOW', "allow_null" => FALSE ),
     array(
        "name" => "pcs", "label" => $GLOBALS['locPCS'], "type" => "INT", "style" => "short", "listquery" => "", "position" => 0, "default" => FALSE, "allow_null" => FALSE ),
     array(
        "name" => "type_id", "label" => $GLOBALS['locUNIT'], "type" => "LIST", "style" => "short", "listquery" => "SELECT id, name FROM {prefix}row_type ORDER BY order_no;", "position" => 0, "default" => $intTypeId, "allow_null" => FALSE ),
     array(
        "name" => "price", "label" => $GLOBALS['locPRICE'], "type" => "INT", "style" => "short", "listquery" => "", "position" => 0, "default" => $intPrice, "allow_null" => FALSE ),
     array(
        "name" => "vat", "label" => $GLOBALS['locVAT'], "type" => "INT", "style" => "tiny", "listquery" => "", "position" => 0, "default" => $intVAT, "allow_null" => TRUE ),
     array(
        "name" => "vat_included", "label" => $GLOBALS['locVATINC'], "type" => "CHECK", "style" => "short", "listquery" => "", "position" => 0, "default" => $intVATIncluded, "allow_null" => TRUE ),
     array(
        "name" => "order_no", "label" => $GLOBALS['locROWNO'], "type" => "INT", "style" => "tiny", "listquery" => "SELECT max(order_no)+5 FROM {prefix}invoice_row WHERE invoice_id = _PARENTID_", "position" => 0, "default" => "ADD+5", "allow_null" => TRUE )
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
   $astrFormElements =
    array(
     array(
        "name" => "name", "label" => $GLOBALS['locCOMPNAME'], "type" => "TEXT", "style" => "medium", "listquery" => "", "position" => 1, "default" => FALSE, "allow_null" => FALSE ),
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
        "name" => "bank_iban", "label" => $GLOBALS['locACCOUNTIBAN'], "type" => "TEXT", "style" => "medium", "listquery" => "", "position" => 3, "default" => FALSE, "allow_null" => FALSE ),
     array(
        "name" => "bank_swiftbic", "label" => $GLOBALS['locSWIFTBIC'], "type" => "TEXT", "style" => "medium", "listquery" => "", "position" => 4, "default" => FALSE, "allow_null" => FALSE ),
     array(
        "name" => "banksep2", "label" => $GLOBALS['locSECONDBANK'], "type" => "LABEL"),
     array(
        "name" => "bank_name2", "label" => $GLOBALS['locBANK'], "type" => "TEXT", "style" => "medium", "listquery" => "", "position" => 1, "default" => FALSE, "allow_null" => TRUE ),
     array(
        "name" => "bank_account2", "label" => $GLOBALS['locACCOUNT'], "type" => "TEXT", "style" => "medium", "listquery" => "", "position" => 2, "default" => FALSE, "allow_null" => TRUE ),
     array(
        "name" => "bank_iban2", "label" => $GLOBALS['locACCOUNTIBAN'], "type" => "TEXT", "style" => "medium", "listquery" => "", "position" => 3, "default" => FALSE, "allow_null" => TRUE ),
     array(
        "name" => "bank_swiftbic2", "label" => $GLOBALS['locSWIFTBIC'], "type" => "TEXT", "style" => "medium", "listquery" => "", "position" => 4, "default" => FALSE, "allow_null" => TRUE ),
     array(
        "name" => "banksep3", "label" => $GLOBALS['locTHIRDBANK'], "type" => "LABEL"),
     array(
        "name" => "bank_name3", "label" => $GLOBALS['locBANK'], "type" => "TEXT", "style" => "medium", "listquery" => "", "position" => 1, "default" => FALSE, "allow_null" => TRUE ),
     array(
        "name" => "bank_account3", "label" => $GLOBALS['locACCOUNT'], "type" => "TEXT", "style" => "medium", "listquery" => "", "position" => 2, "default" => FALSE, "allow_null" => TRUE ),
     array(
        "name" => "bank_iban3", "label" => $GLOBALS['locACCOUNTIBAN'], "type" => "TEXT", "style" => "medium", "listquery" => "", "position" => 3, "default" => FALSE, "allow_null" => TRUE ),
     array(
        "name" => "bank_swiftbic3", "label" => $GLOBALS['locSWIFTBIC'], "type" => "TEXT", "style" => "medium", "listquery" => "", "position" => 4, "default" => FALSE, "allow_null" => TRUE ),
    );
break;

case 'invoice_state':
    $strTable = '{prefix}invoice_state';
    $strPrimaryKey = "id";
    
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
    $strTable = '{prefix}session_type';
    $strPrimaryKey = "id";
    $astrFormElements =
        array(
            array(
            "name" => "name", "label" => $GLOBALS['locSESSIONTYPE'], "type" => "TEXT", "style" => "medium", "listquery" => "", "position" => 1, "default" => FALSE, "allow_null" => FALSE ),
            array(
            "name" => "order_no", "label" => $GLOBALS['locORDERNO'], "type" => "INT", "style" => "short", "listquery" => "", "position" => 2, "default" => FALSE, "allow_null" => FALSE ),
            array(
            "name" => "time_out", "label" => $GLOBALS['locTIMEOUT'], "type" => "INT", "style" => "short", "listquery" => "", "position" => 1, "default" => "5400", "allow_null" => FALSE ),
            array(
            "name" => "access_level", "label" => $GLOBALS['locACCESSLEVEL'], "type" => "INT", "style" => "short", "listquery" => "", "position" => 2, "default" => "1", "allow_null" => FALSE )
    );
break;

case 'user':
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
            "name" => "type_id", "label" => $GLOBALS['locTYPE'], "type" => "LIST", "style" => "medium", "listquery" => "SELECT id, name FROM {prefix}session_type ORDER BY order_no", "position" => 0, "default" => FALSE, "allow_null" => FALSE )
    );
break;

case 'company_type':
    $strTable = '{prefix}company_type';
    $strPrimaryKey = "id";
    $astrFormElements =
        array(
            array(
            "name" => "name", "label" => $GLOBALS['locCOMPTYPE'], "type" => "TEXT", "style" => "medium", "listquery" => "", "position" => 1, "default" => FALSE, "allow_null" => FALSE ),
            array(
            "name" => "order_no", "label" => $GLOBALS['locORDERNO'], "type" => "INT", "style" => "short", "listquery" => "", "position" => 2, "default" => FALSE, "allow_null" => FALSE )
        );
break;

    default :
        echo "What would you like me to do?"; die;
    break;
}

// Clean up the array
$akeys = array('name', 'type', 'position', 'style', 'label', 'default', 'defaults', 'parent_key', 'listquery', 'allow_null', 'elem_attributes');
for( $j = 0; $j < count($astrFormElements); $j++ ) {
  for( $i = 0; $i < count($akeys); $i++ ) {
    if (!isset($astrFormElements[$j][$akeys[$i]]))
      $astrFormElements[$j][$akeys[$i]] = FALSE;
  }
}


?>
