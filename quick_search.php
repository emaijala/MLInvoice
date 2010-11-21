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

$strQuery = 
    "SELECT * FROM ". _DB_PREFIX_. "_quicksearch ".
    "WHERE user_id = ". $GLOBALS['sesUSERID']. 
    " ORDER BY name";
$intRes = mysql_query($strQuery);
$intNumRows = mysql_num_rows($intRes);

for( $i = 0; $i < $intNumRows; $i++ ) {
    $intId = mysql_result($intRes, $i, "id");
    $blnDelete = (int)$_POST["delete_". $intId. "_x"] ? TRUE : FALSE;
    if( $blnDelete && $intId ) {
        $strDelQuery =
            "DELETE FROM " . _DB_PREFIX_. "_quicksearch ".
            "WHERE id=". $intId ;
        $intDelRes = @mysql_query($strDelQuery);
    }
}

$intRes = mysql_query($strQuery);
$intNumRows = mysql_num_rows($intRes);

echo htmlPageStart( _PAGE_TITLE_ );
?>
<script type="text/javascript">
<!--
function openHelpWindow(event) {
    x = event.screenX;
    y = event.screenY;
    strLink = 'help.php?ses=<?php echo $GLOBALS['sesID']?>&topic=quicksearch'; window.open(strLink, '_blank', 'height=400,width=400,screenX=' + x + ',screenY=' + y + ',left=' + x + ',top=' + y + ',menubar=no,scrollbars=yes,status=no,toolbar=no');
    
    return true;
}
-->
</script>

<body class="form" onload="<?php echo $strOnLoad?>">
<form method="post" action="quick_search.php?ses=<?php echo $GLOBALS['sesID']?>&form=<?php echo $strForm?>" target="_self" name="search_form">
<input type="hidden" name="fields" value="<?php echo $strFields?>">
<table>
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
        $strForm = mysql_result($intRes, $i, "form");
        $strWhereClause = mysql_result($intRes, $i, "whereclause");
        $strLink = 
            "list.php?ses=". $GLOBALS['sesID']."&selectform=". 
            $strForm. "&where=". $strWhereClause;
        $strOnClick = "opener.top.frset_bottom.f_list.location.href='".$strLink. "'";
?>
<tr>
    <td class="label">
        <a href="quick_search.php" onClick="<?php echo $strOnClick?>; return false;"><?php echo $strName?></a> 
    </td>
    <td>
        <input type="hidden" name="delete_<?php echo $intID?>_x" value="0">
        <a class="tinyactionlink" href="#" title="<?php echo $GLOBALS['locDELROW']?>" onclick="self.document.forms[0].delete_<?php echo $intID?>_x.value=1; self.document.forms[0].submit(); return false;"> X </a>
        <!--
        <input type="image" name="delete_<?php echo $intID?>" src="./<?php echo $GLOBALS['sesLANG']?>_images/x.gif"  title="<?php echo $GLOBALS['locDELROW']?>" alt="<?php echo $GLOBALS['locDELROW']?>" style="cursor:pointer;cursor:hand;">-->
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
        <!--
        <img name="close_button"  src="./<?php echo $GLOBALS['sesLANG']?>_images/close.gif"  alt="<?php echo $GLOBALS['locCLOSE']?>" title="<?php echo $GLOBALS['locCLOSE']?>" onClick="self.close();" style="cursor:pointer;cursor:hand;">-->
    </td>
    <td>
        <a class="actionlink" href="#" onclick="openHelpWindow(event); return false;"><?php echo $GLOBALS['locHELP']?></a>
        <!--
        <img name="help" src="./<?php echo $GLOBALS['sesLANG']?>_images/detail_help.gif" alt="<?php echo $GLOBALS['locHELP']?>" title="<?php echo $GLOBALS['locHELP']?>" onClick="openHelpWindow(event);" style="cursor:pointer;cursor:hand;">-->
    </td>
</tr>
</table>
</center>
</form>
</body>
</html>
