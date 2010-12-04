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

$strFilter = '';
switch ( $strList ) {

case 'companies':
   $strTable = _DB_PREFIX_. "_company";
   $astrSearchFields = 
    array( 
        array("name" => "company_name", "type" => "TEXT"),
        array("name" => "company_id", "type" => "TEXT")
    );
   $astrHiddenSearchField = array("name" => "type_id", "type" => "INT");
   $strPrimaryKey = "id";
   $astrShowFields = 
    array( 
        array("name" => "company_name", 'width' => 150, "type" => "TEXT", "header" => $GLOBALS['locCOMPNAME']),
        array("name" => "company_id", 'width' => 100, "type" => "TEXT", "header" => $GLOBALS['locCOMPVATID']),
        array("name" => "email", 'width' => 150, "type" => "TEXT", "header" => $GLOBALS['locEMAIL']),
        array("name" => "phone", 'width' => 100, "type" => "TEXT", "header" => $GLOBALS['locPHONE']),
        array("name" => "gsm", 'width' => 100, "type" => "TEXT", "header" => $GLOBALS['locGSM'])
    );
   $strMainForm = "company";
   $strTitle = $GLOBALS['locCOMPANIES'];
break;

case 'products':
   $strTable = _DB_PREFIX_. "_product";
   $astrSearchFields = 
    array( 
        array("name" => "product_name", "type" => "TEXT"),
        array("name" => "product_id", "type" => "TEXT")
    );
   //$astrHiddenSearchField = array("name" => "type_id", "type" => "INT");
   $strPrimaryKey = "id";
   $astrShowFields = 
    array( 
        array("name" => "product_name", 'width' => 200, "type" => "TEXT", "header" => $GLOBALS['locPRODUCTNAME']),
        array("name" => "product_code", 'width' => 150, "type" => "TEXT", "header" => $GLOBALS['locPRODUCTCODE']),
        array("name" => "product_group", 'width' => 150, "type" => "TEXT", "header" => $GLOBALS['locPRODUCTGROUP']),
        array("name" => "unit_price", 'width' => 100, "type" => "TEXT", "header" => $GLOBALS['locUNITPRICE'])
    );
   
   $strMainForm = "product";
   $strTitle = $GLOBALS['locPRODUCTS'];
break;

case 'archived_invoices':
case 'invoices':
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
   $astrShowFields = 
    array( 
        array("name" => "i.invoice_date", 'width' => 100, "type" => "INTDATE", "order" => "DESC", "header" => $GLOBALS['locHEADERINVOICEDATE']),
        array("name" => "i.invoice_no", 'width' => 80, "type" => "TEXT", "header" => $GLOBALS['locHEADERINVOICENO']),
        array("name" => "i.name", 'width' => 100, "type" => "TEXT", "header" => $GLOBALS['locHEADERINVOICENAME']),
        array("name" => "s.name", 'width' => 80, "type" => "TEXT", "header" => $GLOBALS['locHEADERINVOICESTATE']),
        array("name" => "i.ref_number", 'width' => 100, "type" => "TEXT", "header" => $GLOBALS['locHEADERINVOICEREFERENCE']),
        array("name" => "b.name", 'width' => 100, "type" => "TEXT", "header" => $GLOBALS['locHEADERINVOICEBASE']),
        array("name" => "c.company_name", 'width' => 100, "type" => "TEXT", "header" => $GLOBALS['locHEADERINVOICECOMPANY'])
    );
   $strMainForm = "invoice";
   $strTitle = $GLOBALS['locINVOICES'];
   
break;
/***********************************************************************
    END SEARCH LISTS - HAKU
***********************************************************************/

/***********************************************************************
    SYSTEM LISTS - JÄRJESTELMÄ
***********************************************************************/
case 'base_info':
   $strTable = "". _DB_PREFIX_. "_base";
   $astrSearchFields = 
    array( 
        array("name" => "name", "type" => "TEXT")
    );
   $strPrimaryKey = "id";
   $astrShowFields = 
    array( 
        array("name" => "name", 'width' => 200, "type" => "TEXT", "header" => $GLOBALS['locCOMPNAME']),
        array("name" => "company_id", 'width' => 100, "type" => "TEXT", "header" => $GLOBALS['locCOMPVATID']),
        array("name" => "contact_person", 'width' => 150, "type" => "TEXT", "header" => $GLOBALS['locCONTACTPERS']),
        array("name" => "email", 'width' => 150, "type" => "TEXT", "header" => $GLOBALS['locEMAIL'])
    );
    //array('name');
   $strMainForm = "base_info";
   $strTitle = $GLOBALS['locBASEINFO'];
break;

case 'invoice_state':
   $strTable = "". _DB_PREFIX_. "_invoice_state";
   $astrSearchFields = 
    array( 
        array("name" => "name'", "type" => "TEXT")
    );
   $strPrimaryKey = "id";
   $astrShowFields = 
    array( 
        array("name" => "order_no", 'width' => 150, "type" => "TEXT", "header" => $GLOBALS['locORDERNO']),
        array("name" => "name", 'width' => 450, "type" => "TEXT", "header" => $GLOBALS['locSTATUS'])
    );
    //array('order_no','name');
   $strMainForm = "invoice_state";
   $strTitle = $GLOBALS['locINVOICESTATES'];
break;
   
case 'row_type':
   $strTable = "". _DB_PREFIX_. "_row_type";
   $astrSearchFields = 
    array( 
        array("name" => "name", "type" => "TEXT")
    );
   $strPrimaryKey = "id";
   $astrShowFields = 
    array( 
        array("name" => "order_no", 'width' => 150, "type" => "TEXT", "header" => $GLOBALS['locORDERNO']),
        array("name" => "name", 'width' => 450, "type" => "TEXT", "header" => $GLOBALS['locROWTYPE'])
    );
    //array('order_no','name');
   $strMainForm = "row_type";
   $strTitle = $GLOBALS['locROWTYPES'];
break;

case 'company_type':
   $strTable = "". _DB_PREFIX_. "_company_type";
   $astrSearchFields = 
    array( 
        array("name" => "name", "type" => "TEXT")
    );
   $strPrimaryKey = "id";
   $astrShowFields = 
    array( 
        array("name" => "order_no", "type" => "TEXT", "header" => $GLOBALS['locORDERNO']),
        array("name" => "name", "type" => "TEXT", "header" => $GLOBALS['locCOMPTYPE'])
    );
    //array('order_no','name');
   $strMainForm = "company_type";
   $strTitle = $GLOBALS['locCOMPANYTYPES'];
break;
   
case 'session_types':
   $strTable = "". _DB_PREFIX_. "_session_type";
   $astrSearchFields = 
    array( 
        array("name" => "name", "type" => "TEXT")
    );
   $strPrimaryKey = "id";
   $astrShowFields = 
    array( 
        array("name" => "order_no", 'width' => 150, "type" => "TEXT", "header" => $GLOBALS['locORDERNO']),
        array("name" => "name", 'width' => 450, "type" => "TEXT", "header" => $GLOBALS['locSESSIONTYPE'])
    );
    //array('order_no','name');
   $strMainForm = "session_type";
   $strTitle = $GLOBALS['locSESSIONTYPES'];
break;
   
case 'users':
   $strTable = "". _DB_PREFIX_. "_users";
   $astrSearchFields = 
    array( 
        array("name" => "name", "type" => "TEXT")
    );
   $strPrimaryKey = "id";
   $astrShowFields = 
    array( 
        array("name" => "name", 'width' => 350, "type" => "TEXT", "header" => $GLOBALS['locUSERNAME']),
        array("name" => "login", 'width' => 250, "type" => "TEXT", "header" => $GLOBALS['locLOGONNAME'])
    );
    //array('name');
   $strMainForm = "user";
   $strTitle = $GLOBALS['locUSERS'];
break;

/***********************************************************************
    END SYSTEM LISTS - JÄRJESTELMÄ
***********************************************************************/

default :
    break;
}

?>
