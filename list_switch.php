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

switch ( $strForm ) {

case 'company':
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
        array("name" => "company_name", "type" => "TEXT", "header" => $GLOBALS['locCOMPNAME']),
        array("name" => "company_id", "type" => "TEXT", "header" => $GLOBALS['locCOMPVATID'])
    );
   $strMainForm = "form.php?ses=".$GLOBALS['sesID']."&selectform=company";
   $strTitle = $GLOBALS['locCOMPANIES'];
break;

case 'product':
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
        array("name" => "product_name", "type" => "TEXT", "header" => $GLOBALS['locPRODUCTNAME']),
        array("name" => "unit_price", "type" => "TEXT", "header" => $GLOBALS['locUNITPRICE'])
    );
   $strMainForm = "form.php?ses=".$GLOBALS['sesID']."&selectform=product";
   $strTitle = $GLOBALS['locPRODUCTS'];
break;

case 'invoice':
   $strTable = _DB_PREFIX_. "_invoice";
   $astrSearchFields = 
    array(
        array("name" => "invoice_no", "type" => "TEXT"),
        array("name" => "ref_number", "type" => "TEXT"),
        array("name" => "name", "type" => "TEXT")
    );
   $strPrimaryKey = "id";
   $astrShowFields = 
    array( 
        array("name" => "invoice_date", "type" => "INTDATE", "order" => "DESC", "header" => $GLOBALS['locHEADERINVOICEDATE']),
        array("name" => "invoice_no", "type" => "TEXT", "header" => $GLOBALS['locHEADERINVOICENO']),
        array("name" => "name", "type" => "TEXT", "header" => $GLOBALS['locHEADERINVOICENAME']),
        array("name" => "ref_number", "type" => "TEXT", "header" => $GLOBALS['locHEADERINVOICEREFERENCE'])
    );
   $strMainForm = "form.php?ses=".$GLOBALS['sesID']."&selectform=invoice";
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
        array("name" => "name", "type" => "TEXT", "header" => $GLOBALS['locCOMPNAME'])
    );
    //array('name');
   $strMainForm = "form.php?ses=".$GLOBALS['sesID']."&selectform=base_info";
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
        array("name" => "order_no", "type" => "TEXT", "header" => $GLOBALS['locORDERNO']),
        array("name" => "name", "type" => "TEXT", "header" => $GLOBALS['locSTATUS'])
    );
    //array('order_no','name');
   $strMainForm = "form.php?ses=".$GLOBALS['sesID']."&selectform=invoice_state";
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
        array("name" => "order_no", "type" => "TEXT", "header" => $GLOBALS['locORDERNO']),
        array("name" => "name", "type" => "TEXT", "header" => $GLOBALS['locROWTYPE'])
    );
    //array('order_no','name');
   $strMainForm = "form.php?ses=".$GLOBALS['sesID']."&selectform=row_type";
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
   $strMainForm = "form.php?ses=".$GLOBALS['sesID']."&selectform=company_type";
   $strTitle = $GLOBALS['locCOMPANYTYPES'];
break;
   
case 'session_type':
   $strTable = "". _DB_PREFIX_. "_session_type";
   $astrSearchFields = 
    array( 
        array("name" => "name", "type" => "TEXT")
    );
   $strPrimaryKey = "id";
   $astrShowFields = 
    array( 
        array("name" => "order_no", "type" => "TEXT", "header" => $GLOBALS['locORDERNO']),
        array("name" => "name", "type" => "TEXT", "header" => $GLOBALS['locSESSIONTYPE'])
    );
    //array('order_no','name');
   $strMainForm = "form.php?ses=".$GLOBALS['sesID']."&selectform=session_type";
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
        array("name" => "name", "type" => "TEXT", "header" => $GLOBALS['locUSERNAME'])
    );
    //array('name');
   $strMainForm = "form.php?ses=".$GLOBALS['sesID']."&selectform=users";
   $strTitle = $GLOBALS['locUSERS'];
break;

/***********************************************************************
    END SYSTEM LISTS - JÄRJESTELMÄ
***********************************************************************/

default :
    echo "What would you like me to do?"; die;
    break;
}

?>
