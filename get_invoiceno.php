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

$strSesID = sesVerifySession();


require "localize.php";

require "datefuncs.php";
require "miscfuncs.php";

$intInvoiceId = (int)$_REQUEST['id'] ? (int)$_REQUEST['id'] : FALSE;

echo htmlPageStart( _PAGE_TITLE_ );

//näitä käytetään jos halutaan laskun viitenumero muuten kuin juoksevasta laskunumeroinnista - esim. y-tunnus + juoksevanro = viitenro
/*
if( $intInvoiceId ) {
    $strQuery = 
        "SELECT ". _DB_PREFIX_. "_company.company_id ".
        "FROM ". _DB_PREFIX_. "_invoice ".
        "INNER JOIN ". _DB_PREFIX_. "_company ON ". _DB_PREFIX_. "_company.id = ". _DB_PREFIX_. "_invoice.company_id ".
        "WHERE ". _DB_PREFIX_. "_invoice.id = $intInvoiceId";
    $intRes = mysql_query($strQuery);
    $intNRows = mysql_numrows($intRes);
    if( $intNRows ) {
        $strCompanyId = mysql_result($intRes, 0, "company_id");
        $strPattern = "/[^0-9]/";
        $strCompanyId = preg_replace($strPattern, "", $strCompanyId);
        $intCompanyId = $strCompanyId;
        //$intCompanyId = str_replace(array("-","."," "), "", $strCompanyId);
        //$strCompanyId = str_replace(array("-","."," "), "", $strCompanyId);
    }
}
$strQuery = "SELECT max(real_invoice_no) FROM ". _DB_PREFIX_. "_invoice";
$intRes = mysql_query($strQuery);
$intRealInvNo = mysql_result($intRes, 0, 0) + 1;

$intInvNo = $intCompanyId. $intRealInvNo;
$intRefNo = $intInvNo . miscCalcCheckNo($intInvNo);
$strRefNo = $strCompanyId. "-". $intRealInvNo . "-" . miscCalcCheckNo($intInvNo);

*/

$strQuery = "SELECT max(invoice_no) FROM ". _DB_PREFIX_. "_invoice";

$intRes = mysql_query($strQuery);
$intInvNo = mysql_result($intRes, 0, 0) + 1;

$intRefNo = $intInvNo . miscCalcCheckNo($intInvNo);


$strDate = date("d.m.Y");
$strDueDate = date("d.m.Y",mktime(0, 0, 0, date("m"), date("d")+14, date("Y")));


?>
<script language="javascript">
<!--
function updateOpener() {
    window.opener.document.admin_form.invoice_date.value="<?php echo $strDate?>";
    window.opener.document.admin_form.due_date.value="<?php echo $strDueDate?>";    
    //window.opener.document.admin_form.real_invoice_no.value="<?php echo $intRealInvNo?>";
    //window.opener.document.admin_form.invoice_no.value="<?php echo $strRefNo?>";
    window.opener.document.admin_form.invoice_no.value="<?php echo $intInvNo?>";
    window.opener.document.admin_form.ref_number.value="<?php echo $intRefNo?>";
    window.opener.document.admin_form.saveact.value=1; window.opener.document.admin_form.submit();
    self.close();
    return 1;
}
-->
</script>

<body class="navi" onload="updateOpener();">

<?php echo $GLOBALS['locMAYCLOSE']?>

</body>
</html>
