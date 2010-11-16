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

$strSesID = $_REQUEST['ses'] ? $_REQUEST['ses'] : FALSE;

if( !sesCheckSession( $strSesID ) ) {
    die;
}
require "localize.php";

$strType = $_POST['type'] ? $_POST['type'] : $_REQUEST['type'];
$intBaseId = $_POST['base'] ? $_POST['base'] : $_REQUEST['base'];
$intYear = $_POST['year'] ? $_POST['year'] : date("Y");
$intMonth = isset($_POST['month']) ? $_POST['month'] : date("n");
//echo $_POST['stateid'];
$intSelectedStateId = $_POST['stateid'] ? $_POST['stateid'] : 1;

//print_r($_POST);

$intCurrentYear = date("Y");
for( $i = 0; $i <= 20; $i++) {
    $astrYearListValues[$i] = ($intCurrentYear+10)-$i;
    $astrYearListOptions[$i] = ($intCurrentYear+10)-$i;
}
$strYearListBox = htmlListBox( "year", $astrYearListValues, $astrYearListOptions, $intYear, "", TRUE, FALSE );

switch ( $strType ) {

case 'report':
   $strMainForm = "print_report.php";
   $strTopLabel = $GLOBALS['locPRINTREPORTFORYEAR'];
   $strMidLabel = $GLOBALS['locPRINTREPORTTO'];
   $strListQuery = 
        "SELECT '0' AS id, '".$GLOBALS['locALL']."' AS name UNION ".
        "SELECT '01' AS id, '".$GLOBALS['locJAN']."' AS name UNION ".
        "SELECT '02' AS id, '".$GLOBALS['locFEB']."' AS name UNION ".
        "SELECT '03' AS id, '".$GLOBALS['locMAR']."' AS name UNION ".
        "SELECT '04' AS id, '".$GLOBALS['locAPR']."' AS name UNION ".
        "SELECT '05' AS id, '".$GLOBALS['locMAY']."' AS name UNION ".
        "SELECT '06' AS id, '".$GLOBALS['locJUN']."' AS name UNION ".
        "SELECT '07' AS id, '".$GLOBALS['locJUL']."' AS name UNION ".
        "SELECT '08' AS id, '".$GLOBALS['locAUG']."' AS name UNION ".
        "SELECT '09' AS id, '".$GLOBALS['locSEP']."' AS name UNION ".
        "SELECT '10' AS id, '".$GLOBALS['locOCT']."' AS name UNION ".
        "SELECT '11' AS id, '".$GLOBALS['locNOV']."' AS name UNION ".
        "SELECT '12' AS id, '".$GLOBALS['locDEC']."' AS name ";
   $astrSearchElements =
    array(
     array("type" => "ELEMENT", "element" => $strYearListBox, "label" => $GLOBALS['locYEAR']),
     array("name" => "month", "label" => $GLOBALS['locMONTH'], "type" => "SUBMITLIST", "style" => "medium", "listquery" => $strListQuery, "value" => $intMonth),
     array("name" => "base", "label" => $GLOBALS['locBILLER'], "type" => "SUBMITLIST", "style" => "medium", "listquery" => "SELECT id, name FROM ". _DB_PREFIX_. "_base ORDER BY name", "value" => $intBaseId)
    );
    
    $strQuery = 
        "SELECT id, name ".
        "FROM ". _DB_PREFIX_. "_invoice_state ".
        "ORDER BY order_no";
    $intRes = mysql_query($strQuery);
    $intNumRows = mysql_numrows($intRes);
    for( $i = 0; $i < $intNumRows; $i++ ) {
        $intStateId = mysql_result($intRes, $i, "id");
        $strStateName = mysql_result($intRes, $i, "name");
        //echo $strMemberType ."<br>\n";
        //$strChecked = $intStateId == $intSelectedStateId ? 'checked' : '';
        $strTemp = "stateid_". $intStateId;
        $tmpSelected = $_POST[$strTemp] ? TRUE : FALSE;
        $strChecked = $tmpSelected ? 'checked' : '';
        $astrHtmlElements[$i] = 
        array("label" => $strStateName, "html" => "<input type=\"checkbox\" name=\"stateid_{$intStateId}\" value=\"1\" $strChecked>\n");
    }
break;
case 'payment':
   $strMainForm = "enter_payment.php";
   $strTopLabel = $GLOBALS['locENTERPAYMENT'];
   $strMidLabel = $GLOBALS['locENTERREFNODATE'];
   /*$astrSearchElements =
    array(
     array("name" => "year", "label" => $GLOBALS['locYEAR'], "type" => "SUBMITLIST", "style" => "medium", "listquery" => "SELECT year AS id, year AS name FROM ". _DB_PREFIX_. "_board ORDER BY year", "value" => $intYear)
    );*/
   $astrShowElements =
    array(
     array("name" => "refno", "label" => $GLOBALS['locREFNO'], "type" => "INT", "style" => "short", "listquery" => "", "value" => ''),
     array("name" => "date", "label" => $GLOBALS['locPAYDATE'], "type" => "INTDATE", "style" => "date", "listquery" => "", "value" => date("d.m.Y"))
    );
break;
}
    echo htmlPageStart( _PAGE_TITLE_ );

//print_r($astrSearchElements);
?>

<body class="list">
<form method="post" action="<?php echo $_SERVER["PHP_SELF"]?>?ses=<?php echo $GLOBALS['sesID']?>" target="f_list" name="selectinv">
<input name="type" type="hidden" value="<?php echo $strType?>">

<br>
<b><?php echo $strTopLabel?></b>
<table>
<?php
for( $j = 0; $j < count($astrSearchElements); $j++ ) {
    if( $astrSearchElements[$j]['type'] == "ELEMENT" ) {
?>
    <tr>
        <td class="label">
            <?php echo $astrSearchElements[$j]['label']?> :
        </td>
        <td class="field" <?php echo $strColspan?>>
            <?php echo $astrSearchElements[$j]['element']?>
        </td>
    </tr>
<?php
    }
    else {
?>
    <tr>
        <td class="label">
            <?php echo $astrSearchElements[$j]['label']?> :
        </td>
<?php /*
    <tr>
    </tr>
*/ ?>
        <td class="field" <?php echo $strColspan?>>
            <?php echo htmlFormElement($astrSearchElements[$j]['name'], $astrSearchElements[$j]['type'], $astrSearchElements[$j]['value'], $astrSearchElements[$j]['style'], $astrSearchElements[$j]['listquery'], "MODIFY", $astrSearchElements[$j]['parent_key'])?>
        </td>
    </tr>
<?php
    }
}

?>
</table>
</form>
<form method="post" action="<?php echo $strMainForm?>?ses=<?php echo $GLOBALS['sesID']?>" target="f_main" name="invoice">
<input name="year" type="hidden" value="<?php echo $intYear?>">
<input name="month" type="hidden" value="<?php echo $intMonth?>">
<input name="base" type="hidden" value="<?php echo $intBaseId?>">
<b><?php echo $strMidLabel?></b>
<table>
<?php
for( $j = 0; $j < count($astrShowElements); $j++ ) {
?>
    <tr>
        <td class="label">
            <?php echo $astrShowElements[$j]['label']?> :
        </td>
        <td class="field" <?php echo $strColspan?>>
            <?php echo htmlFormElement($astrShowElements[$j]['name'], $astrShowElements[$j]['type'], $astrShowElements[$j]['value'], $astrShowElements[$j]['style'], $astrShowElements[$j]['listquery'], "MODIFY", $astrShowElements[$j]['parent_key'])?>
        </td>
    </tr>
<?php
}
for( $j = 0; $j < count($astrHtmlElements); $j++ ) {
?>
    <tr>
        <td class="label">
            <?php echo $astrHtmlElements[$j]['label']?> :
        </td>
        <td class="field" <?php echo $strColspan?>>
            <?php echo $astrHtmlElements[$j]['html']?>
        </td>
    </tr>
<?php
}
?>
    <tr>
        <td>
            <input type="hidden" name="get_x" value="0">
            <a class="actionlink" href="#" onclick="self.document.invoice.get_x.value=1; self.document.invoice.submit(); return false;"><?php echo $GLOBALS['locGET']?></a>
        <!--
            <input type="image" name="get" src="./<?php echo $GLOBALS['sesLANG']?>_images/get.png" title="<?php echo $GLOBALS['locGET']?>" alt="<?php echo $GLOBALS['locGET']?>">-->
        </td>
    </tr>

</table>
</form>
</body>
</html>