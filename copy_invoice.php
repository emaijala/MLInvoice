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

require "htmlfuncs.php";
require "sqlfuncs.php";
require "sessionfuncs.php";

$strSesID = $_REQUEST['ses'] ? $_REQUEST['ses'] : FALSE;

if( !sesCheckSession( $strSesID ) ) {
    die;
}
require "localize.php";

require "datefuncs.php";
require "miscfuncs.php";

$intInvoiceId = (int)$_REQUEST['id'] ? (int)$_REQUEST['id'] : FALSE;

echo htmlPageStart( _PAGE_TITLE_ );

if( $intInvoiceId ) {
    $strQuery = 
        "SELECT * ".
        "FROM ". _DB_PREFIX_. "_invoice ".
        "WHERE ". _DB_PREFIX_. "_invoice.id = $intInvoiceId";
    $intRes = mysql_query($strQuery);
    $intNRows = mysql_numrows($intRes);
    if( $intNRows ) {
        
        $strname = mysql_result($intRes, 0, "name");
        $intCompanyId = mysql_result($intRes, 0, "company_id");
        $intInvoiceNo = mysql_result($intRes, 0, "invoice_no");
        $intRealInvoiceNo = mysql_result($intRes, 0, "real_invoice_no");
        $intInvoiceDate = mysql_result($intRes, 0, "invoice_date");
        $intDueDate = mysql_result($intRes, 0, "due_date");
        $intPaymentDate = mysql_result($intRes, 0, "payment_date");
        $intRefNumber = mysql_result($intRes, 0, "ref_number");
        $intStateId = mysql_result($intRes, 0, "state_id");
        $strReference = mysql_result($intRes, 0, "reference");
        $intBaseId = mysql_result($intRes, 0, "base_id");
    }
    
    $intDate = date("Ymd");
    $intDueDate = date("Ymd",mktime(0, 0, 0, date("m"), date("d")+14, date("Y")));
    
    
    $strQuery = 
        "INSERT INTO ". _DB_PREFIX_. "_invoice(name, company_id, invoice_no, real_invoice_no, invoice_date, due_date, payment_date, ref_number, state_id, reference, base_id) ".
        "VALUES('$strname', $intCompanyId, 0, 0, $intDate, $intDueDate, NULL, 0, 1, '$strReference', $intBaseId )";
    
    $intRes = mysql_query($strQuery);
    $intNewId = mysql_insert_id();
    
    if( $intNewId ) {    
        $strQuery = 
            "SELECT * ".
            "FROM ". _DB_PREFIX_. "_invoice_row ".
            "WHERE invoice_id = $intInvoiceId";
        $intRes = mysql_query($strQuery);
        $intNRows = mysql_numrows($intRes);
        for( $i = 0; $i < $intNRows; $i++ ) {
            $strDescription = mysql_result($intRes, $i, "description");
            $intTypeId = mysql_result($intRes, $i, "type_id");
            $intPcs = mysql_result($intRes, $i, "pcs");
            $intPrice = mysql_result($intRes, $i, "price");
            $intRowDate = mysql_result($intRes, $i, "row_date");
            $intVat = mysql_result($intRes, $i, "vat");
            $intOrderNo = mysql_result($intRes, $i, "order_no");
            
            $strQuery = 
                "INSERT INTO ". _DB_PREFIX_. "_invoice_row(invoice_id, description, type_id, pcs, price, row_date, vat, order_no) ".
                "VALUES($intNewId, '$strDescription', $intTypeId, $intPcs, $intPrice, $intRowDate, $intVat, $intOrderNo )";
            mysql_query($strQuery);
            //echo "$strQuery <br>";
        }
    }
}

$strLink = "form.php?ses=". $strSesID. "&selectform=invoice&id=". $intNewId. "&key_name=id";

?>
<script language="javascript">
<!--
function updateOpener() {
    //alert('<?php echo $GLOBALS['locREMEMBER']?>');
    window.opener.location.href='<?php echo $strLink?>';
    self.close();
    return 1;
}
-->
</script>

<body class="navi" onload="updateOpener();">

<?php echo $GLOBALS['locMAYCLOSE']?>

</body>
</html>
