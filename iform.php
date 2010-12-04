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

$strForm = getPost('selectform', getRequest('selectform', ''));

$strMode = getPost('mode', getGet('mode', 'MODIFY'));

$strDefaults = getPost('defaults', getRequest('defaults', '')); 

if( $strDefaults ) {
    $tmpDefaults = explode(" ", urldecode($strDefaults));
    //print_r($tmpDefaults);
    $x = 0;
    while (list($key, $val) = each($tmpDefaults)) {
        if( $val ) {
            $tmpValues = explode(">", $val);
            $astrDefaults[$tmpValues[0]] = $tmpValues[1];
            $x++;
        }
    }
    //print_r($astrDefaults);
}
else {
    $astrDefaults = array();
}
require "form_switch.php";

echo htmlPageStart( _PAGE_TITLE_ );

$blnCopy = getPost('copy_x', FALSE) ? TRUE : FALSE;
$blnAdd = getPost('add_x', FALSE) ? TRUE : FALSE;
$blnDelete = getPost('del_x', FALSE) ? TRUE : FALSE;
$intKeyValue = getPostRequest($strPrimaryKey, FALSE);
$intParentKey = getPostRequest($strParentKey, FALSE);

$blnInsertDone = FALSE;
$strOnLoad = '';
for( $i = 0; $i < count($astrFormElements); $i++ ) {
    if($astrFormElements[$i]['name'] != '' && array_key_exists($astrFormElements[$i]['name'],$astrDefaults)) {
        $astrValues[$astrFormElements[$i]['name']] = $astrDefaults[$astrFormElements[$i]['name']];
    }
    elseif( !$astrFormElements[$i]['default'] ) {
        if( $astrFormElements[$i]['type'] == "INT" ) {
            $tmpValue = str_replace(",", ".", getPost($astrFormElements[$i]['name'], ''));
            $astrValues[$astrFormElements[$i]['name']] = $tmpValue ? (float)$tmpValue : FALSE;
        }
        elseif( $astrFormElements[$i]['type'] == "LIST" ) {
            $tmpValue = getPost($astrFormElements[$i]['name'], '');
            $astrValues[$astrFormElements[$i]['name']] = $tmpValue !== '' ? $tmpValue : NULL;
        }
        else {
            $astrValues[$astrFormElements[$i]['name']] = getPost($astrFormElements[$i]['name'], FALSE);
        }
    }
    else {
        if( $astrFormElements[$i]['default'] == "DATE_NOW" ) {
           $strDefaultValue = date("d.m.Y");
        }
        elseif( $astrFormElements[$i]['default'] == "DATE_NOW+14" ) {
           $strDefaultValue = date("d.m.Y",mktime(0, 0, 0, date("m"), date("d")+14, date("Y")));
        }
        elseif( $astrFormElements[$i]['default'] == "DATE_NOW+31" ) {
           $strDefaultValue = date("d.m.Y",mktime(0, 0, 0, date("m"), date("d")+31, date("Y")));
        }
        elseif( $astrFormElements[$i]['default'] == "TIME_NOW" ) {
           $strDefaultValue = date("H:i");
        }
        elseif( $astrFormElements[$i]['default'] == "TIMESTAMP_NOW" ) {
           $strDefaultValue = date("d.m.Y H:i");
        }
        elseif( $astrFormElements[$i]['default'] == "POST" ) {
           $strDefaultValue = getPost($astrFormElements[$i]['name'], '');
        }
        elseif( strstr($astrFormElements[$i]['default'], "ADD") ) {
           $strQuery = str_replace("_PARENTID_", $intParentKey, $astrFormElements[$i]['listquery']);
           $intRes = mysql_query_check($strQuery);
           $intAdd = mysql_result($intRes, 0, 0);
           $strDefaultValue = $intAdd;
        }
        else {
            $strDefaultValue = $astrFormElements[$i]['default'];
        }
        
        if( $astrFormElements[$i]['type'] == "INT" ) {
            $tmpValue = str_replace(",", ".", getPost($astrFormElements[$i]['name'], ''));
            $astrValues[$astrFormElements[$i]['name']] = $tmpValue !== '' ? (float)$tmpValue : $strDefaultValue;
        }
        else {
            $astrValues[$astrFormElements[$i]['name']] = getPost($astrFormElements[$i]['name'], $strDefaultValue);
        }
    }
}
if( $blnAdd ) {
    $strFields = '';
    $strInsert = '';
    $arrValues = array();
    $blnMissingValues = FALSE;
    for( $i = 0; $i < count($astrFormElements); $i++ ) {
        $strControlType = $astrFormElements[$i]['type'];
        $strControlName = $astrFormElements[$i]['name'];
        $mixControlValue = isset($astrValues[$strControlName]) ? $astrValues[$strControlName] : NULL;
        if(isset($mixControlValue)) {
            if( $strControlType != 'NEWLINE' ) {
                if( $strControlType == 'TEXT' || $strControlType == 'AREA' ) {
                  $strFields .= "$strControlName, ";
                  $strInsert .= '?, ';
                  $arrValues[] = $mixControlValue;
                }
                elseif( $strControlType == 'INT' || $strControlType == 'HID_INT' || $strControlType == 'SECHID_INT' ) {
                  //build the insert into fields
                  $strFields .= "$strControlName, ";
                  //format the numbers to right format - finnish use ,-separator
                  $flttmpValue = 
                      $mixControlValue ? str_replace(",", ".", $mixControlValue) : 0;
                  $strInsert .= '?, ';
                  $arrValues[] = $flttmpValue;
                }
                elseif( $strControlType == 'LIST' || $strControlType == 'CHECK' ) {
                    $strFields .= "$strControlName, ";
                    $tmpValue = str_replace(",", ".", $mixControlValue);
                    $strInsert .= '?, ';
                    $arrValues[] = $tmpValue;
                }
                elseif( $strControlType == 'INTDATE' ) {
                    if( !$mixControlValue ) {
                        $mixControlValue = 'NULL';
                    }
                    $strFields .= "$strControlName, ";
                    $strInsert .= '?, ';
                    //convert user input to right format
                    $arrValues[] = dateConvDate2IntDate($mixControlValue);
                }
                elseif( $strControlType == 'TIMESTAMP' ) {
                    $strFields .= $strControlName.", ";
                    //    $strUpdateFields .= $strControlName;
                    $strInsert .= '?, ';
                    $arrValues[] = time();
                }
                elseif( $strControlType == 'TIME' ) {
                    $strFields .= $strControlName.", ";
                    //convert user input to right format
                    $strInsert .= '?, ';
                    //convert user input to right format
                    $astrSearch = array('.', ',', ' ');
                    $arrValues[] = str_replace($astrSearch, ":", $mixControlValue);
                }
            }
        }
        elseif( $strControlType != 'IFORM' && $strControlType != 'HID_INT' && $strControlType != 'NEWLINE' && $strControlType != 'BUTTON' ) {
            if ( !$astrFormElements[$i]['allow_null'] ) {
                $blnMissingValues = TRUE;
                $strOnLoad .= "alert('".$GLOBALS['locERRVALUEMISSING']." : ".$astrFormElements[$i]['label']."');";
            }
        }
    }
    if( !$blnMissingValues ) {
        $strInsert = substr($strInsert, 0, -2);
        $strFields = substr($strFields, 0, -2);

        $strQuery = "INSERT INTO $strTable ($strFields, $strParentKey) VALUES ($strInsert, ?)";
        $arrValues[] = $intParentKey;

        //echo $strQuery."<br>\n";
        $intRes = mysql_param_query($strQuery, $arrValues, TRUE);
        if( $intRes ) {
            $intKeyValue = mysql_insert_id();
            $blnInsertDone = TRUE;
        }
        else {
            $strOnLoad = "alert('".$GLOBALS['locDBERROR']."');";
        }
    }
}
if( $blnInsertDone ) {
    //    unset($astrValues);
    for( $i = 0; $i < count($astrFormElements); $i++ ) {
        if( !$astrFormElements[$i]['default'] ) {
            unset($astrValues[$astrFormElements[$i]['name']]);
        }
        
        else {
            if( $astrFormElements[$i]['default'] == "DATE_NOW" ) {
               $strDefaultValue = date("Ymd");
            }
            elseif( strstr($astrFormElements[$i]['default'], "ADD") ) {
               $intAdd = substr($astrFormElements[$i]['default'], 4);
               $_POST[$astrFormElements[$i]['name']] += $intAdd;
               
            }
            else {
                $strDefaultValue = $astrFormElements[$i]['default'];
            }
            $astrValues[$astrFormElements[$i]['name']] = getPost($astrFormElements[$i]['name'], $strDefaultValue);
        }
    }
}
if( $blnDelete && $intKeyValue ) {
    //create the delete query
    $strQuery = "DELETE FROM $strTable WHERE $strPrimaryKey=?";
    //send query to database
    mysql_param_query($strQuery, array($intKeyValue));
    //dispose the primarykey value
    unset($intKeyValue);
    //clear form elements
    unset($astrValues);
    $blnNew = TRUE;
    $strOnLoad = "top.frset_bottom.f_list.document.forms[0].key_values.value=''; top.frset_bottom.f_list.document.forms[0].submit();";
}
if( $intParentKey ) {
    $strQuery =
        "SELECT * FROM $strTable ".
        "WHERE $strParentKey = ? $strOrder";
    $intRes = mysql_param_query($strQuery, array($intParentKey));
    $intNumRows = mysql_num_rows($intRes);
    $astrOldValues = array();
    if( $intRes ) {
        for($i = 0; $i < $intNumRows; $i++ ) {
            $tmpID = mysql_result( $intRes, $i, "id");
            for( $j = 0; $j < count($astrFormElements); $j++ ) {
                if( $astrFormElements[$j]['type'] != 'NEWLINE' ) {
                    if( $astrFormElements[$j]['type'] == 'INTDATE' ) {
                        $astrOldValues[$i][$astrFormElements[$j]['name']] = dateConvIntDate2Date( mysql_result( $intRes, $i, $astrFormElements[$j]['name'] ));
                    }
                    elseif( $astrFormElements[$j]['type'] == 'TIMESTAMP' ) {
                        $astrOldValues[$i][$astrFormElements[$j]['name']] = date("d.m.Y H:i", mysql_result( $intRes, $i, $astrFormElements[$j]['name'] ));
                    }
                    elseif( $astrFormElements[$j]['type'] == 'BUTTON' ) {
                       $astrOldValues[$i][$astrFormElements[$j]['name']] = $tmpID;
                    }
                    
                    else {
                        $astrOldValues[$i][$astrFormElements[$j]['name']] = mysql_result( $intRes, $i, $astrFormElements[$j]['name'] );
                    }
                }
                else {
                    $astrOldValues[$i][$astrFormElements[$j]['name']] = $intKeyValue;
                }
            }
        }
    }
}

?>
<body class="iform" onload="<?php echo $strOnLoad?>">
<script type="text/javascript">
<!--
$(function() {
  $('input[class~="hasCalendar"]').datepicker();
});

function OpenPop(strLink, event) {
    x = event.screenX;
    y = event.screenY;
    
    var win = window.open(strLink, 'pop_iform', 'height=200,width=800,screenX=' + x + ',screenY=' + y + ',left=' + x + ',top=' + y + ',menubar=no,scrollbars=yes,status=no,toolbar=no');
    win.focus();
    
    return true;
}

-->
</script>
<form method="post" action="<?php echo $strMainForm?>&ses=<?php echo $GLOBALS['sesID']?>" target="_self" name="iform">
<input type="hidden" name="<?php echo $strParentKey?>" value="<?php echo $intParentKey?>">
<input type="hidden" name="defaults" value="<?php echo $strDefaults?>">
<table class="iform">
    <tr>
<?php
if( $strMode == "MODIFY" ) {
$strRowSpan = '';
for( $j = 0; $j < count($astrFormElements); $j++ ) {
    if( $astrFormElements[$j]['type'] != "HID_INT" && $astrFormElements[$j]['type'] != "SECHID_INT" && $astrFormElements[$j]['type'] != "BUTTON" && $astrFormElements[$j]['type'] != 'NEWLINE' ) {
?>
    <td class="label <?php echo strtolower($astrFormElements[$j]['style'])?>_label">
        <?php echo $astrFormElements[$j]['label']?><br>
        <?php echo htmlFormElement( $astrFormElements[$j]['name'],$astrFormElements[$j]['type'], gpcStripSlashes(isset($astrValues[$astrFormElements[$j]['name']]) ? $astrValues[$astrFormElements[$j]['name']] : ''), $astrFormElements[$j]['style'],$astrFormElements[$j]['listquery'], "MODIFY", 0, '', array(), $astrFormElements[$j]['elem_attributes'])?>
    </td>
<?php
    }
    elseif( $astrFormElements[$j]['type'] == 'SECHID_INT' ) {
?>
    <input type="hidden" name="<?php echo $astrFormElements[$j]['name']?>" value="<?php echo gpcStripSlashes($astrValues[$astrFormElements[$j]['name']])?>">
<?php
    }
    elseif( $astrFormElements[$j]['type'] == 'BUTTON' ) {
?>
    <td class="label">
        &nbsp;
    </td>
<?php
    }
    elseif( $astrFormElements[$j]['type'] == 'NEWLINE' ) {
        $strRowSpan = "rowspan=\"2\"";
?>
</tr>
<tr>
<?php
    }
}

?>
    <td class="button" <?php echo $strRowSpan?>>
        <br/>
        <input type="hidden" name="add_x" value="0">
        <a class="tinyactionlink" href="#" title="<?php echo $GLOBALS['locADD']?>" onclick="self.document.forms[0].add_x.value=1; self.document.forms[0].submit(); return false;"> + </a>
    </td>
</tr>

<?php
}
/*
for( $j = 0; $j < count($astrFormElements); $j++ ) {
    if( $astrFormElements[$j]['type'] != "HID_INT" ) {
?>
    <td class="<?php echo $astrFormElements[$j]['style']?>" <?php echo $strColspan?>>
        <?php echo htmlFormElement( $astrFormElements[$j]['name'],$astrFormElements[$j]['type'],                               gpcStripSlashes($astrValues[$astrFormElements[$j]['name']]),                               $astrFormElements[$j]['style'],$astrFormElements[$j]['listquery'])?>
    </td>
<?php

    }
}

?>
    <td <?php echo $strRowSpan?>>
        <button name="add" type="submit" value="1" title="<?php echo $GLOBALS['locADD']?>"  onMouseOver="document.iform.add_button.src='./<?php echo $GLOBALS['sesLANG']?>_images/add_act.gif'" onMouseOut="document.iform.add_button.src='./<?php echo $GLOBALS['sesLANG']?>_images/add.gif'" ><img name="add_button" src="./<?php echo $GLOBALS['sesLANG']?>_images/add.gif"  title="<?php echo $GLOBALS['locADD']?>" alt="<?php echo $GLOBALS['locADD']?>" ></button><img src="./<?php echo $GLOBALS['sesLANG']?>_images/add_act.gif" alt="" width="1" height="1">
    </td>
<?php

*/
?>

</table>
</form>
<table class="iform">
<?php

//print_r($astrOldValues);
for($i = 0; $i < count($astrOldValues); $i++ ) {
?>
<tr>
<?php

    for( $j = 0; $j < count($astrFormElements); $j++ ) {
        if( $astrFormElements[$j]['type'] != "HID_INT" && $astrFormElements[$j]['type'] != "SECHID_INT" && $astrFormElements[$j]['type'] != 'NEWLINE' ) {
?>
    <td class="<?php echo $astrFormElements[$j]['style']?>" >
        <?php echo htmlFormElement( $astrFormElements[$j]['name'],$astrFormElements[$j]['type'], gpcStripSlashes($astrOldValues[$i][$astrFormElements[$j]['name']]), $astrFormElements[$j]['style'],$astrFormElements[$j]['listquery'], "NO_MOD", 0, $astrFormElements[$j]['label'], array(), isset($astrFormElements[$j]['elem_attributes']) ? $astrFormElements[$j]['elem_attributes'] : '')?>
    </td>
<?php

        }
        elseif( $astrFormElements[$j]['type'] == "HID_INT" ||  $astrFormElements[$j]['type'] == "SECHID_INT" ) {
            $strHidField = htmlFormElement( $astrFormElements[$j]['name'],"HID_INT", gpcStripSlashes($astrOldValues[$i][$astrFormElements[$j]['name']]), $astrFormElements[$j]['style'],$astrFormElements[$j]['listquery'], "NO_MOD", 0, $astrFormElements[$j]['label']);
            if( $astrFormElements[$j]['type'] == "HID_INT" ) {
                $strPrimaryName = $astrFormElements[$j]['name'];
                $intPrimaryId = gpcStripSlashes($astrOldValues[$i][$astrFormElements[$j]['name']]);
            }
        }
        elseif( $astrFormElements[$j]['type'] == 'NEWLINE' ) {
            $strRowSpan = "rowspan=\"2\"";
?>
</tr>
<tr>
<?php

        }
    }
    $strPopLink = "iform_pop.php?ses=". $GLOBALS['sesID']. "&selectform=". $strForm. "&". $strParentKey ."=" .$intParentKey. "&". $strPrimaryName. "=". $intPrimaryId. "&defaults=". $strDefaults;
    $strPopLink2 = "iform_pop.php?ses=". $GLOBALS['sesID']. "&selectform=". $strForm. "&". $strParentKey ."=" .$intParentKey. "&". $strPrimaryName. "=". $intPrimaryId. "&defaults=". $strDefaults. "&copyact=1";
?>
    
<?php
if( $strMode == "MODIFY" ) {
?>
    <td>
        <a class="tinyactionlink" href="#" onclick="OpenPop('<?php echo $strPopLink?>', event); return false;">Muokkaa</a>
    </td>
    <td>
        <a class="tinyactionlink" href="#" onclick="OpenPop('<?php echo $strPopLink2?>', event); return false;">Kopioi</a>
    </td>
<?php
}
?>
</tr>
<tr>
    <td colspan="3">
        <hr />
    </td>
</tr>
<?php

}

?>
</table>
</body>
</html>
