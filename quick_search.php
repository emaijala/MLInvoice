<?php
/*******************************************************************************
 MLInvoice: web-based invoicing application.
 Copyright (C) 2010-2015 Ere Maijala
 
 Portions based on:
 PkLasku : web-based invoicing software.
 Copyright (C) 2004-2008 Samu Reinikainen
 
 This program is free software. See attached LICENSE.
 
 *******************************************************************************/

/*******************************************************************************
 MLInvoice: web-pohjainen laskutusohjelma.
 Copyright (C) 2010-2015 Ere Maijala
 
 Perustuu osittain sovellukseen:
 PkLasku : web-pohjainen laskutusohjelmisto.
 Copyright (C) 2004-2008 Samu Reinikainen
 
 Tämä ohjelma on vapaa. Lue oheinen LICENSE.
 
 *******************************************************************************/
require_once 'htmlfuncs.php';
require_once 'sqlfuncs.php';
require_once 'sessionfuncs.php';
require_once 'miscfuncs.php';
require_once 'datefuncs.php';

sesVerifySession();

require_once 'localize.php';

$strFunc = getRequest('func', '');
if ($strFunc == 'open_invoices')
    $strFunc = 'invoices';

$strQuery = 'SELECT * FROM {prefix}quicksearch ' . 'WHERE func=? AND user_id=? ' .
     'ORDER BY name';
$intRes = mysqli_param_query($strQuery, 
    [
        $strFunc, 
        $_SESSION['sesUSERID']
    ]);

while ($row = mysqli_fetch_assoc($intRes)) {
    $intId = $row['id'];
    $blnDelete = getPost('delete_' . $intId . '_x', FALSE) ? TRUE : FALSE;
    if ($blnDelete && $intId) {
        $strDelQuery = 'DELETE FROM {prefix}quicksearch ' . 'WHERE id=?';
        $intDelRes = mysqli_param_query($strDelQuery, [
            $intId
        ]);
    }
}

echo htmlPageStart(_PAGE_TITLE_);
?>

<body>
	<div class="form_container ui-widget-content">
		<div class="form ui-widget">
			<form method="post"
				action="quick_search.php?func=<?php echo $strFunc?>" target="_self"
				name="search_form">
				<table style="width: 100%">
					<tr>
						<td class="sublabel" colspan="4">
    <?php echo $GLOBALS['locLabelQuickSearch']?><br> <br>
						</td>
					</tr>
<?php
$intRes = mysqli_param_query($strQuery, 
    [
        $strFunc, 
        $_SESSION['sesUSERID']
    ]);
while ($row = mysqli_fetch_assoc($intRes)) {
    $intID = $row['id'];
    $strName = $row['name'];
    $strFunc = $row['func'];
    $strWhereClause = $row['whereclause'];
    $strLink = "index.php?func=$strFunc&where=$strWhereClause";
    $strOnClick = "opener.location.href='$strLink'";
    ?>
<tr class="search_row">
						<td class="label"><a href="quick_search.php"
							onClick="<?php echo $strOnClick?>; return false;"><?php echo $strName?></a>
						</td>
						<td><input type="hidden" name="delete_<?php echo $intID?>_x"
							value="0"> <a class="tinyactionlink" href="#"
							title="<?php echo $GLOBALS['locDelRow']?>"
							onclick="self.document.forms[0].delete_<?php echo $intID?>_x.value=1; self.document.forms[0].submit(); return false;">
								X </a></td>
					</tr>
<?php
}
if (!isset($intID)) {
    ?>
<tr>
						<td class="label">
        <?php echo $GLOBALS['locNoQuickSearches']?> 
    </td>
					</tr>
<?php
}
?>
</table>

				<center>
					<table>
						<tr>
							<td><a class="actionlink" href="#"
								onclick="self.close(); return false;"><?php echo $GLOBALS['locClose']?></a>
							</td>
						</tr>
					</table>
				</center>
			</form>
		</div>
	</div>
</body>
</html>
