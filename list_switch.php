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

$strTable = '';
$strFilter = '';
$levelsAllowed = array(ROLE_USER, ROLE_BACKUPMGR);
switch ( $strList ? $strList : $strFunc ) {

/***********************************************************************
    LISTS
***********************************************************************/
case 'companies':
   $strTable = '{prefix}company';
   $astrSearchFields = 
    array( 
        array("name" => "company_name", "type" => "TEXT"),
        array("name" => "company_id", "type" => "TEXT")
    );
   $astrHiddenSearchField = array("name" => "type_id", "type" => "INT");
   $strPrimaryKey = "id";
   $strDeletedField = 'deleted';
   $astrShowFields = 
    array( 
        array("name" => "company_name", 'width' => 150, "type" => "TEXT", "header" => $GLOBALS['locCOMPNAME']),
        array("name" => "company_id", 'width' => 100, "type" => "TEXT", "header" => $GLOBALS['locCOMPVATID']),
        array("name" => "inactive", 'width' => 100, "type" => "TEXT", "header" => $GLOBALS['locHeaderCompanyActive'],
          'mappings' => array('0' => $GLOBALS['locActive'], '1' => $GLOBALS['locInactive']) ),
        array("name" => "customer_no", 'width' => 100, "type" => "TEXT", "header" => $GLOBALS['locCUSTOMERNO']),
        array("name" => "email", 'width' => 150, "type" => "TEXT", "header" => $GLOBALS['locEMAIL']),
        array("name" => "phone", 'width' => 100, "type" => "TEXT", "header" => $GLOBALS['locPHONE']),
        array("name" => "gsm", 'width' => 100, "type" => "TEXT", "header" => $GLOBALS['locGSM'])
    );
   $strMainForm = "company";
   $strTitle = $GLOBALS['locCOMPANIES'];
break;

case 'archived_invoices':
case 'invoices':
   $levelsAllowed[] = ROLE_READONLY;

   $strFilter = ($strFunc == 'invoices') ? 'i.archived = 0' : 'i.archived = 1';
   $strTable = '{prefix}invoice i ' .
     'LEFT OUTER JOIN {prefix}base b on i.base_id=b.id ' .
     'LEFT OUTER JOIN {prefix}company c on i.company_id=c.id ' .
     'LEFT OUTER JOIN {prefix}invoice_state s on i.state_id=s.id';
   $astrSearchFields = 
    array(
        array("name" => "i.invoice_no", "type" => "TEXT"),
        array("name" => "i.ref_number", "type" => "TEXT"),
        array("name" => "i.name", "type" => "TEXT")
    );
   $strPrimaryKey = "i.id";
   $strDeletedField = 'i.deleted';
   $astrShowFields = 
    array( 
        array("name" => "i.invoice_date", 'width' => 100, "type" => "INTDATE", "order" => "DESC", "header" => $GLOBALS['locHEADERINVOICEDATE']),
        array("name" => "i.due_date", 'width' => 100, "type" => "INTDATE", "order" => "DESC", "header" => $GLOBALS['locHEADERINVOICEDUEDATE']),
        array("name" => "i.invoice_no", 'width' => 80, "type" => "TEXT", "header" => $GLOBALS['locHEADERINVOICENO']),
        array("name" => "b.name", 'width' => 150, "type" => "TEXT", "header" => $GLOBALS['locHEADERINVOICEBASE']),
        array("name" => "c.company_name", 'width' => 150, "type" => "TEXT", "header" => $GLOBALS['locHEADERINVOICECOMPANY']),
        array("name" => "i.name", 'width' => 100, "type" => "TEXT", "header" => $GLOBALS['locHEADERINVOICENAME']),
        array("name" => "s.name", 'width' => 120, "type" => "TEXT", "header" => $GLOBALS['locHEADERINVOICESTATE']),
        array("name" => "i.ref_number", 'width' => 100, "type" => "TEXT", "header" => $GLOBALS['locHEADERINVOICEREFERENCE'])
    );
   $strMainForm = "invoice";
   $strTitle = $GLOBALS['locINVOICES'];
break;

/***********************************************************************
    SETTINGS
***********************************************************************/
case 'base_info':
   $strTable = "{prefix}base";
   $astrSearchFields = 
    array( 
        array("name" => "name", "type" => "TEXT")
    );
   $strPrimaryKey = "id";
   $strDeletedField = 'deleted';
   $astrShowFields = 
    array( 
        array("name" => "name", 'width' => 200, "type" => "TEXT", "header" => $GLOBALS['locBaseName']),
        array("name" => "company_id", 'width' => 100, "type" => "TEXT", "header" => $GLOBALS['locCOMPVATID']),
        array("name" => "contact_person", 'width' => 150, "type" => "TEXT", "header" => $GLOBALS['locCONTACTPERS']),
        array("name" => "email", 'width' => 150, "type" => "TEXT", "header" => $GLOBALS['locEMAIL'])
    );
    //array('name');
   $strMainForm = "base_info";
   $strTitle = $GLOBALS['locBASES'];
break;

case 'invoice_state':
   $strTable = "{prefix}invoice_state";
   $astrSearchFields = 
    array( 
        array("name" => "name'", "type" => "TEXT")
    );
   $strPrimaryKey = "id";
   $strDeletedField = 'deleted';
   $astrShowFields = 
    array( 
        array("name" => "order_no", 'width' => 150, "type" => "TEXT", "header" => $GLOBALS['locORDERNO']),
        array("name" => "name", 'width' => 450, "type" => "TEXT", "header" => $GLOBALS['locSTATUS'])
    );
    //array('order_no','name');
   $strMainForm = "invoice_state";
   $strTitle = $GLOBALS['locINVOICESTATES'];
break;

case 'product':
   $strTable = '{prefix}product';
   $astrSearchFields = 
    array( 
        array("name" => "product_name", "type" => "TEXT"),
        array("name" => "product_code", "type" => "TEXT")
    );
   $strPrimaryKey = "id";
   $strDeletedField = 'deleted';
   $astrShowFields = 
    array( 
        array("name" => "product_name", 'width' => 200, "type" => "TEXT", "header" => $GLOBALS['locPRODUCTNAME']),
        array("name" => "product_code", 'width' => 150, "type" => "TEXT", "header" => $GLOBALS['locPRODUCTCODE']),
        array("name" => "product_group", 'width' => 150, "type" => "TEXT", "header" => $GLOBALS['locPRODUCTGROUP']),
        array("name" => "unit_price", 'width' => 100, "type" => "CURRENCY", "header" => $GLOBALS['locUNITPRICE'])
    );
   
   $strMainForm = "product";
   $strTitle = $GLOBALS['locPRODUCTS'];
break;

case 'row_type':
   $strTable = "{prefix}row_type";
   $astrSearchFields = 
    array( 
        array("name" => "name", "type" => "TEXT")
    );
   $strPrimaryKey = "id";
   $strDeletedField = 'deleted';
   $astrShowFields = 
    array( 
        array("name" => "order_no", 'width' => 150, "type" => "TEXT", "header" => $GLOBALS['locORDERNO']),
        array("name" => "name", 'width' => 450, "type" => "TEXT", "header" => $GLOBALS['locROWTYPE'])
    );
   $strMainForm = "row_type";
   $strTitle = $GLOBALS['locROWTYPES'];
break;

case 'print_template':
  $strTable = "{prefix}print_template";
  $astrSearchFields = 
  array( 
      array("name" => "name", "type" => "TEXT")
  );
  $strPrimaryKey = "id";
  $strDeletedField = 'deleted';
  $astrShowFields = 
  array( 
    array("name" => "order_no", 'width' => 150, "type" => "TEXT", "header" => $GLOBALS['locORDERNO']),
    array("name" => "type", 'width' => 150, "type" => "TEXT", "header" => $GLOBALS['locPrintTemplateType'], 
      'mappings' => array('invoice' => $GLOBALS['locPrintTemplateTypeInvoice']) ),
    array("name" => "name", 'width' => 200, "type" => "TEXT", "header" => $GLOBALS['locPrintTemplateName']),
    array("name" => "inactive", 'width' => 100, "type" => "TEXT", "header" => $GLOBALS['locHeaderPrintTemplateActive'],
      'mappings' => array('0' => $GLOBALS['locActive'], '1' => $GLOBALS['locInactive']) ),
    array("name" => "filename", 'width' => 200, "type" => "TEXT", "header" => $GLOBALS['locPrintTemplateFileName']),
    array("name" => "parameters", 'width' => 150, "type" => "TEXT", "header" => $GLOBALS['locPrintTemplateParameters']),
  );
  $strMainForm = "print_template";
  $strTitle = $GLOBALS['locPrintTemplates'];
  break;

/***********************************************************************
    SYSTEM
***********************************************************************/
case 'session_type':
   $levelsAllowed = array(99);
   $strTable = "{prefix}session_type";
   $astrSearchFields = 
    array( 
        array("name" => "name", "type" => "TEXT")
    );
   $strPrimaryKey = "id";
   $strDeletedField = 'deleted';
   $astrShowFields = 
    array( 
        array("name" => "order_no", 'width' => 150, "type" => "TEXT", "header" => $GLOBALS['locORDERNO']),
        array("name" => "name", 'width' => 450, "type" => "TEXT", "header" => $GLOBALS['locSESSIONTYPE'])
    );
    //array('order_no','name');
   $strMainForm = "session_type";
   $strTitle = $GLOBALS['locSESSIONTYPES'];
break;
   
case 'user':
   $levelsAllowed = array(99);
   $strTable = "{prefix}users";
   $astrSearchFields = 
    array( 
        array("name" => "name", "type" => "TEXT")
    );
   $strPrimaryKey = "id";
   $strDeletedField = 'deleted';
   $astrShowFields = 
    array( 
        array("name" => "name", 'width' => 350, "type" => "TEXT", "header" => $GLOBALS['locUSERNAME']),
        array("name" => "login", 'width' => 250, "type" => "TEXT", "header" => $GLOBALS['locLOGONNAME'])
    );
   $strMainForm = "user";
   $strTitle = $GLOBALS['locUSERS'];
break;

default :
    break;
}

?>
