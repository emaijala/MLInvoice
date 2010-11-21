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

$strForm = getPostRequest('selectform', ''); 

require "form_switch.php";

echo htmlPageStart( _PAGE_TITLE_ );

$blnAdd = getPost('add_x', FALSE) ? TRUE : FALSE;
$blnDelete = getPost('delete_x', FALSE) ? TRUE : FALSE;

$strValue = getPost($astrFormElements['name'], FALSE);
$intKeyValue = getPostRequest($strPrimaryKey, FALSE);
$intParentKey = getPostRequest($strParentKey, FALSE);

if( $blnAdd ) {    
    if( $strValue ) {
        $strQuery =
            "INSERT INTO ". $strTable. " ( ". $strPrimaryKey. ", ".
            $strParentKey. " ) ". "VALUES ( ". $intKeyValue. ", ". $intParentKey. " );";
        $intRes = mysql_query($strQuery);
    }
}

if( $intParentKey ) {
    $strQuery =
        "SELECT ". $strInnerTable. ".id, ". $strShowField. " FROM ". $strTable. " ". "INNER JOIN ". $strInnerTable. " ON ". 
        $strInnerTable. ".id=". $strTable. ".". $astrFormElements['name']. " ".
        "WHERE ". $strParentKey. "=". $intParentKey. ";";
    $intRes = mysql_query($strQuery);
    if( $intRes ) {
        $x = 0;
        $intNRes = mysql_num_rows($intRes);
        for( $j = 0; $j < $intNRes; $j++ ) {
            $strId = mysql_result($intRes, $j, 0);
            $strName = mysql_result($intRes, $j, 1);
            $blnDelete = getPost("delete_". $strId. "_x", FALSE) ? TRUE : FALSE;
            if( $blnDelete && $intParentKey ) {
                $strQuery =
                    "DELETE FROM " . $strTable . " ".
                    "WHERE ". $strPrimaryKey. "=". $strId. " AND ".
                    $strParentKey. "=". $intParentKey. ";";
                $intDelRes = @mysql_query($strQuery);
            }
            else {
                $astrId[$x] = $strId;
                $strPrimaryIDList .= $strId. ",";
                $astrName[$x] = $strName;
                $x++;
            }
        }
    }
}
$strPrimaryIDList = $strPrimaryIDList ? substr($strPrimaryIDList, 0, -1) : 0;
$astrFormElements['listquery'] = str_replace("_PRIMARYIDLIST_", $strPrimaryIDList, $astrFormElements['listquery']);
?>
<body class="iframe" onload="<?php echo $strOnLoad?>">
<form method="post" action="<?php echo $strMainForm?>&ses=<?php echo $GLOBALS['sesID'] ?>&<?php echo $strParentKey?>=<?php echo $intParentKey?>" target="_self" name="iframe_form">

<table>
    <tr>
        <td class="button">
            <input type="image" name="add" src="./<?php echo $GLOBALS['sesLANG']?>_images/add.gif"  title="<?php echo $GLOBALS['locADD']?>" alt="<?php echo $GLOBALS['locADD']?>" style="cursor:pointer;cursor:hand;">
        </td>
        <td class="field">
            <?php echo htmlFormElement( $astrFormElements['name'],$astrFormElements['type'], $strValue, $astrFormElements['style'],$astrFormElements['listquery'])?>
        </td>
    </tr>
</table>
<table>
<?php
    for($i = 0; $i < count($astrId); $i++ ) {
        
?>
<tr>
    <td class="button">
        <img name="delete_<?php echo $astrId[$i]?>" src="./<?php echo $GLOBALS['sesLANG']?>_images/x.gif"  title="<?php echo $GLOBALS['locDELROW']?>" alt="<?php echo $GLOBALS['locDELROW']?>" style="cursor:pointer;cursor:hand;">
    </td>
    <td>
        <?php echo $astrName[$i]?>
    </td>
</tr>
<?php
}
?>    
</table>
</form>
</body>
</html>
