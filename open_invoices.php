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

?>
<body class="list">

<h2><?php echo $GLOBALS['locLABELOPENINVOICES']?></h2>

<table class="list">
<?php
//invoices todo...
$strQuery = 
    "SELECT * FROM ". _DB_PREFIX_. "_invoice ".
    "WHERE state_id = 1 ".
    "ORDER BY invoice_date, name";
$intRes = mysql_query($strQuery);
$intNumRows = mysql_num_rows($intRes);
if( $intNumRows ) {
?>
<tr>
    <th class="label">
        <?php echo $GLOBALS['locHEADERINVOICEDATE']?>&nbsp;
    </th>
    <th class="label">
        <?php echo $GLOBALS['locHEADERINVOICENO']?>&nbsp;
    </th>
    <th class="label">
        <?php echo $GLOBALS['locHEADERINVOICENAME']?>&nbsp;
    </th>
    <th class="label">
        <?php echo $GLOBALS['locHEADERINVOICEREFERENCE']?>&nbsp;
    </th>
</tr>
<?php
    for( $i = 0; $i < $intNumRows; $i++ ) {
        $intID = mysql_result($intRes, $i, "id");
        $strDate = dateConvIntDate2Date(mysql_result($intRes, $i, "invoice_date"));
        $strLabel = mysql_result($intRes, $i, "name");
        $strInvNo = mysql_result($intRes, $i, "invoice_no");
        $strReference = mysql_result($intRes, $i, "ref_number");
        $strLink = 
            "form.php?ses=". $GLOBALS['sesID']. "&selectform=invoice&key_name=id&id=". $intID;
?>

<tr class="listrow">
    <td class="label">
        <a class="navilink" href="<?php echo $strLink?>" target="f_main"><?php echo $strDate?>&nbsp;&nbsp;</a> 
    </td>
    <td class="label">
        <a class="navilink" href="<?php echo $strLink?>" target="f_main"><?php echo $strInvNo?>&nbsp;&nbsp;</a> 
    </td>
    <td class="label">
        <a class="navilink" href="<?php echo $strLink?>" target="f_main"><?php echo $strLabel?>&nbsp;&nbsp;</a> 
    </td>
    <td class="label">
        <a class="navilink" href="<?php echo $strLink?>" target="f_main"><?php echo $strReference?></a> 
    </td>
</tr>

<?php
    }
}
else {
?>
<tr>
    <td class="label">
        <?php echo $GLOBALS['locNOOPENINVOICES']?> 
    </td>
</tr>
<?php
}

?>
</table>

<h2><?php echo $GLOBALS['locLABELUNPAIDINVOICES']?></h2>

<table class="list">
<?php
//invoices todo...
$strQuery = 
    "SELECT * FROM ". _DB_PREFIX_. "_invoice ".
    "WHERE state_id = 2 or state_id = 5 or state_id = 6 ".
    "ORDER BY invoice_date, name";
$intRes = mysql_query($strQuery);
$intNumRows = mysql_num_rows($intRes);
if( $intNumRows ) {
?>
<tr>
    <th class="label">
        <?php echo $GLOBALS['locHEADERINVOICEDATE']?>&nbsp;
    </th>
    <th class="label">
        <?php echo $GLOBALS['locHEADERINVOICENO']?>&nbsp;
    </th>
    <th class="label">
        <?php echo $GLOBALS['locHEADERINVOICENAME']?>&nbsp;
    </th>
    <th class="label">
        <?php echo $GLOBALS['locHEADERINVOICEREFERENCE']?>&nbsp;
    </th>
</tr>
<?php
    for( $i = 0; $i < $intNumRows; $i++ ) {
        $intID = mysql_result($intRes, $i, "id");
        $strDate = dateConvIntDate2Date(mysql_result($intRes, $i, "invoice_date"));
        $strLabel = mysql_result($intRes, $i, "name");
        $strInvNo = mysql_result($intRes, $i, "invoice_no");
        $strReference = mysql_result($intRes, $i, "ref_number");
        $strLink = 
            "form.php?ses=". $GLOBALS['sesID']. "&selectform=invoice&key_name=id&id=". $intID;
?>

<tr class="listrow">
    <td class="label">
        <a class="navilink" href="<?php echo $strLink?>" target="f_main"><?php echo $strDate?>&nbsp;&nbsp;</a> 
    </td>
    <td class="label">
        <a class="navilink" href="<?php echo $strLink?>" target="f_main"><?php echo $strInvNo?>&nbsp;&nbsp;</a> 
    </td>
    <td class="label">
        <a class="navilink" href="<?php echo $strLink?>" target="f_main"><?php echo $strLabel?>&nbsp;&nbsp;</a> 
    </td>
    <td class="label">
        <a class="navilink" href="<?php echo $strLink?>" target="f_main"><?php echo $strReference?></a> 
    </td>
</tr>

<?php
    }
}
else {
?>
<tr>
    <td class="label">
        <?php echo $GLOBALS['locNOUNPAIDINVOICES']?> 
    </td>
</tr>
<?php
}

?>
</table>

</body>
</html>
