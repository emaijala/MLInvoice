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

sesVerifySession();

require_once "localize.php";

$strFunc = getRequest('func', '');
if ($strFunc == 'open_invoices')
  $strFunc = 'invoices';

$strQuery = 
    'SELECT * FROM {prefix}quicksearch ' .
    'WHERE func=? AND user_id=? ' .  
    'ORDER BY name';
$intRes = mysql_param_query($strQuery, array($strFunc, $_SESSION['sesUSERID']));
$intNumRows = mysql_num_rows($intRes);

for( $i = 0; $i < $intNumRows; $i++ ) {
    $intId = mysql_result($intRes, $i, "id");
    $blnDelete = getPost("delete_". $intId. "_x", FALSE) ? TRUE : FALSE;
    if( $blnDelete && $intId ) {
        $strDelQuery =
            'DELETE FROM {prefix}quicksearch ' .
            'WHERE id=?';
        $intDelRes = mysql_param_query($strDelQuery, array($intId));
    }
}

$intRes = mysql_param_query($strQuery, array($strFunc, $_SESSION['sesUSERID']));
$intNumRows = mysql_num_rows($intRes);

echo htmlPageStart( _PAGE_TITLE_ );
?>

<body class="form">
<form method="post" action="quick_search.php?func=<?php echo $strFunc?>" target="_self" name="search_form">
<table style="width: 100%">
<tr>
    <td class="sublabel" colspan="4">
    <?php echo $GLOBALS['locLABELQUICKSEARCH']?><br><br>
    </td>
</tr>
<?php
if( $intNumRows ) {
    for( $i = 0; $i < $intNumRows; $i++ ) {
        $intID = mysql_result($intRes, $i, "id");
        $strName = mysql_result($intRes, $i, "name");
        $strFunc = mysql_result($intRes, $i, "func");
        $strWhereClause = mysql_result($intRes, $i, "whereclause");
        $strLink = "index.php?func=$strFunc&where=$strWhereClause";
        $strOnClick = "opener.location.href='$strLink'";
?>
<tr>
    <td class="label">
        <a href="quick_search.php" onClick="<?php echo $strOnClick?>; return false;"><?php echo $strName?></a> 
    </td>
    <td>
        <input type="hidden" name="delete_<?php echo $intID?>_x" value="0">
        <a class="tinyactionlink" href="#" title="<?php echo $GLOBALS['locDELROW']?>" onclick="self.document.forms[0].delete_<?php echo $intID?>_x.value=1; self.document.forms[0].submit(); return false;"> X </a>
    </td>
</tr>
<?php
    }
}
else {
?>
<tr>
    <td class="label">
        <?php echo $GLOBALS['locNOQUICKSEARCHES']?> 
    </td>
</tr>
<?php
}
?>
</table>

<center>
<table>
<tr>
    <td>
        <a class="actionlink" href="#" onclick="self.close(); return false;"><?php echo $GLOBALS['locCLOSE']?></a>
    </td>
</tr>
</table>
</center>
</form>
</body>
</html>
