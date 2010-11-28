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
require "miscfuncs.php";
require "datefuncs.php";

$strSesID = sesVerifySession();


require "localize.php";

echo htmlPageStart( _PAGE_TITLE_ );

$strForm = getPostRequest('form', '');
$intYear = getPost('year', FALSE);
$intMonth = getPost('month', 0);
$intStateID = getPost('stateid', FALSE);
$intBaseId = getPost('base', FALSE); 

if( $intMonth == 0 ) {
    $intStartDate = "00";
    $intEndDate = "31";
    $intEndMonth = "12";
}
else {
    $intStartDate = "00";
    $intEndDate = date("t",mktime(0, 0, 0, $intMonth, 1, $intYear));
    $intEndMonth = $intMonth;
}
if( strlen($intMonth) == 1 ) {
    $intMonth = "0".$intMonth;
}
if( strlen($intEndMonth) == 1 ) {
    $intEndMonth = "0".$intEndMonth;
}
$intStart = $intYear. $intMonth. $intStartDate;
$intEnd = $intYear. $intEndMonth. $intEndDate;

$strLabel = '';

$strQuery = 
    "SELECT ". _DB_PREFIX_. "_invoice.id, invoice_no, invoice_date, due_date, ref_number, ". _DB_PREFIX_. "_invoice.name AS invoice_name, reference, company_name AS name, billing_address ".
    "FROM ". _DB_PREFIX_. "_invoice ".
    "LEFT OUTER JOIN ". _DB_PREFIX_. "_company ON ". _DB_PREFIX_. "_company.id = ". _DB_PREFIX_. "_invoice.company_id ".
    "WHERE ". _DB_PREFIX_. "_invoice.invoice_date > ".$intStart." AND ".
    "". _DB_PREFIX_. "_invoice.invoice_date <= ".$intEnd;

$strQuery2 = "";
$strQuery3 = 
    "SELECT id, name ".
    "FROM {prefix}invoice_state ".
    "ORDER BY order_no";
$intRes = mysql_query_check($strQuery3);
$intNumRows = mysql_numrows($intRes);
for( $i = 0; $i < $intNumRows; $i++ ) {
    $intStateId = mysql_result($intRes, $i, "id");
    $strStateName = mysql_result($intRes, $i, "name");
    //echo $strMemberType ."<br>\n";
    //$strChecked = $intStateId == $intSelectedStateId ? 'checked' : '';
    $strTemp = "stateid_". $intStateId;
    $tmpSelected = getPost($strTemp, FALSE) ? TRUE : FALSE;
    if( $tmpSelected ) {
        $strLabel = "";
        $strQuery2 .= 
            " ". _DB_PREFIX_. "_invoice.state_id = ". $intStateId. " OR ";
    }
}
if( $strQuery2 ) {
    $strQuery2 = " AND (". substr($strQuery2, 0, -3). ")";
}

if( $intBaseId ) {
    $strLabel = "";
    $strQuery .= 
        " AND ". _DB_PREFIX_. "_invoice.base_id = ". $intBaseId. " ";
}

/*
if( $intStateID ) {
    $strLabel = "";
    $strQuery .= 
        " AND ". _DB_PREFIX_. "_invoice.state_id = ". $intStateID. " ";
}
*/
$strQuery .= "$strQuery2 ORDER BY invoice_no";

$intRes = mysql_query_check($strQuery);
$intNumRows = mysql_numrows($intRes);
//echo $strQuery;
?>
<body class="form">
<table>
<tr>
    <td class="input" colspan="3">
        <h1><?php echo $strLabel?></h1>
    </td>
</tr>
<tr>
    <td class="label" align="center">
        <?php echo $GLOBALS['locINVNO']?>
    </td>
    <td class="label" align="center">
        <?php echo $GLOBALS['locINVDATE']?>
    </td>
    <td class="label" align="center">
        <?php echo $GLOBALS['locPAYER']?>
    </td>
    <td class="label" align="center">
        <?php echo $GLOBALS['locVATLESS']?>
    </td>
    <td class="label" align="center">
        <?php echo $GLOBALS['locVATPART']?>
    </td>
    <td class="label" align="center">
        <?php echo $GLOBALS['locWITHVAT']?>
    </td>
</tr>
<?php
if( $intNumRows ) {
    $intTotSum = 0;
    $intTotVAT = 0;
    $intTotSumVAT = 0;
    for( $j = 0; $j < $intNumRows; $j++ ) {

        $intInvoiceID = mysql_result($intRes, $j, "id");
        $strInvoiceName = mysql_result($intRes, $j, "invoice_name");
        $strInvoiceNo = mysql_result($intRes, $j, "invoice_no");
        $strRefNumber = mysql_result($intRes, $j, "ref_number");
        $strInvoiceDate = dateConvIntDate2Date(mysql_result($intRes, $j, "invoice_date"));
        $strDueDate = dateConvIntDate2Date(mysql_result($intRes, $j, "due_date"));
        $strName = mysql_result($intRes, $j, "name");
        if( !$strName ) {
            $strName = mysql_result($intRes, $j, "client_name");
        }
        $strRefNumber = chunk_split($strRefNumber, 5, ' ');
        $strQuery = 
            "SELECT description, pcs, price, row_date, vat, ". _DB_PREFIX_. "_row_type.name AS type ".
            "FROM ". _DB_PREFIX_. "_invoice_row ".
            "INNER JOIN ". _DB_PREFIX_. "_row_type ON ". _DB_PREFIX_. "_row_type.id = ". _DB_PREFIX_. "_invoice_row.type_id ".
            "WHERE ". _DB_PREFIX_. "_invoice_row.invoice_id = ". $intInvoiceID;
        $intRes2 = mysql_query_check($strQuery);
        if( $intRes2 ) {
            $intRowSum = 0;
            $intRowVAT = 0;
            $intRowSumVAT = 0;
            $intNRes2 = mysql_num_rows($intRes2);
            for( $i = 0; $i < $intNRes2; $i++ ) {
                $astrRowPrice = mysql_result($intRes2, $i, "price");
                $astrPieces = mysql_result($intRes2, $i, "pcs");
                $astrVAT = mysql_result($intRes2, $i, "vat");
                $astrRowType = mysql_result($intRes2, $i, "type");
                $intSum = $astrPieces * $astrRowPrice;
                $intVAT = $intSum * ($astrVAT / 100);
                $intSumVAT = $intSum + $intVAT;
                $intRowSum += $intSum;
                $intRowVAT += $intVAT;
                $intRowSumVAT += $intSumVAT;
                $intTotSum += $intSum;
                $intTotVAT += $intVAT;
                $intTotSumVAT += $intSumVAT;
            }
        }
?>
<tr>
    <td class="input">
        <?php echo $strInvoiceNo?>
    </td>
    <td class="input">
        <?php echo $strInvoiceDate?>
    </td>
    <td class="input">
        <?php echo $strName?>
    </td>
    <td class="input" align="right">
        <?php echo miscRound2Decim($intRowSum)?>
    </td>
    <td class="input" align="right">
        <?php echo miscRound2Decim($intRowVAT)?>
    </td>
    <td class="input" align="right">
        <?php echo miscRound2Decim($intRowSumVAT)?>
    </td>
</tr>
<?php
    }
?>
<tr>
    <td class="input" colspan="3" align="right">
        <h3><?php echo $GLOBALS['locTOTAL']?>&nbsp;</h3>
    </td>
    <td class="input" align="right">
        <h3><?php echo miscRound2Decim($intTotSum)?>&nbsp;</h3>
    </td>
    <td class="input" align="right">
        <h3><?php echo miscRound2Decim($intTotVAT)?>&nbsp;</h3>
    </td>
    <td class="input" align="right">
        <h3><?php echo miscRound2Decim($intTotSumVAT)?>&nbsp;</h3>
    </td>
</tr>
<?php
}


//print_r($astrSearchElements);
?>

</body>
</html>

