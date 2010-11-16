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

$strForm = $_POST['selectform'] ? $_POST['selectform'] : $_REQUEST['selectform'];

$strMode = $_GET['mode'] ? $_GET['mode'] : 'MODIFY';
$strMode = $_POST['mode'] ? $_POST['mode'] : $strMode;

$strDefaults = $_POST['defaults'] ? $_POST['defaults'] : $_REQUEST['defaults'];

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

//print_r($_POST);

$blnCopy = (int)$_POST['copy_x'] ? TRUE : FALSE;
$blnAdd = (int)$_POST['add_x'] ? TRUE : FALSE;
$blnDelete = (int)$_POST['del_x'] ? TRUE : FALSE;
$intKeyValue = (int)$_POST[$strPrimaryKey] ? (int)$_POST[$strPrimaryKey] : (int)$_REQUEST[$strPrimaryKey];
$intParentKey = (int)$_POST[$strParentKey] ? (int)$_POST[$strParentKey] : (int)$_REQUEST[$strParentKey];

for( $i = 0; $i < count($astrFormElements); $i++ ) {
    if( $astrFormElements[$i]['type'] == 'IFRAME' ) {
        $astrValues[$astrFormElements[$i]['name']] = $intKeyValue ? $intKeyValue : FALSE;
    }
    else {
        if(array_key_exists($astrFormElements[$i]['name'],$astrDefaults)) {
            $astrValues[$astrFormElements[$i]['name']] = $astrDefaults[$astrFormElements[$i]['name']];
        }
        elseif( !$astrFormElements[$i]['default'] ) {
            if( $astrFormElements[$i]['type'] == "INT" ) {
                $tmpValue = str_replace(",", ".", $_POST[$astrFormElements[$i]['name']]);
                $astrValues[$astrFormElements[$i]['name']] =             $tmpValue ? (float)$tmpValue : FALSE;
            }
            else {
                $astrValues[$astrFormElements[$i]['name']] =             $_POST[$astrFormElements[$i]['name']] ? $_POST[$astrFormElements[$i]['name']] : FALSE;
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
               $strDefaultValue = $_POST[$astrFormElements[$i]['name']];
            }
            elseif( strstr($astrFormElements[$i]['default'], "ADD") ) {
               $strQuery = str_replace("_PARENTID_", $intParentKey, $astrFormElements[$i]['listquery']);
               $intRes = @mysql_query($strQuery);
               $intAdd = mysql_result($intRes, 0, 0);
               $strDefaultValue = $intAdd;
            }
            else {
                $strDefaultValue = $astrFormElements[$i]['default'];
            }
            if( $astrFormElements[$i]['type'] == "INT" ) {
                $tmpValue = str_replace(",", ".", $_POST[$astrFormElements[$i]['name']]);
                $astrValues[$astrFormElements[$i]['name']] =             $tmpValue !== '' ? (float)$tmpValue : $strDefaultValue;
            }
            else {
                $astrValues[$astrFormElements[$i]['name']] =             $_POST[$astrFormElements[$i]['name']] ? $_POST[$astrFormElements[$i]['name']] : $strDefaultValue;
            }
        }
    }
}
if( $blnAdd ) {
    for( $i = 0; $i < count($astrFormElements); $i++ ) {
        if( $astrValues[$astrFormElements[$i]['name']] || ($astrFormElements[$i]['type'] == "INT" && $astrValues[$astrFormElements[$i]['name']] == 0) ) {
            if( $astrFormElements[$i]['type'] != 'IFRAME' && $astrFormElements[$i]['type'] != 'NEWLINE' ) {
                if( $astrFormElements[$i]['type'] == 'TEXT' || $astrFormElements[$i]['type'] == 'AREA' ) {
                    $strFields .= $astrFormElements[$i]['name'].", ";
                    $strUpdateFields .= $astrFormElements[$i]['name'];
                
                    $strInsert .= "'" . gpcAddSlashes($astrValues[$astrFormElements[$i]['name']]) . "', ";
                    $strUpdateFields .= "='" . gpcAddSlashes($astrValues[$astrFormElements[$i]['name']]) .  "', ";
                }
                elseif( $astrFormElements[$i]['type'] == 'INT' || $astrFormElements[$i]['type'] == 'HID_INT' || $astrFormElements[$i]['type'] == 'SECHID_INT' ) {
                    $strFields .= $astrFormElements[$i]['name'].", ";
                    $strUpdateFields .= $astrFormElements[$i]['name'];
                    $tmpValue = str_replace(",", ".", $astrValues[$astrFormElements[$i]['name']]);
                    $strInsert .= (float)$tmpValue.", ";
                    $strUpdateFields .= "=". (float)$tmpValue.", ";
                    //echo $astrFormElements[$i]['name']." = ". $astrValues[$astrFormElements[$i]['name']]. " -> ". $tmpValue. " --> ". (float)$tmpValue." <br>";
                }
                elseif( $astrFormElements[$i]['type'] == 'LIST' || $astrFormElements[$i]['type'] == 'CHECK' ) {
                    $strFields .= $astrFormElements[$i]['name'].", ";
                    $strUpdateFields .= $astrFormElements[$i]['name'];
                    $tmpValue = str_replace(",", ".", $astrValues[$astrFormElements[$i]['name']]);
                    if( !is_numeric($tmpValue) ) {
                        $strInsert .= "'". $tmpValue."', ";
                        $strUpdateFields .= "='".$tmpValue."', ";
                    }
                    else {
                        $strInsert .= $tmpValue.", ";
                        $strUpdateFields .= "=".$tmpValue.", ";
                    }
                }
                elseif( $astrFormElements[$i]['type'] == 'INTDATE' ) {
                    $strFields .= $astrFormElements[$i]['name'].", ";
                    $strUpdateFields .= $astrFormElements[$i]['name'];
                    $strInsert .= dateConvDate2IntDate( $astrValues[$astrFormElements[$i]['name']] ).", ";
                    $strUpdateFields .= "=". dateConvDate2IntDate( $astrValues[$astrFormElements[$i]['name']] ).", ";
                }
                elseif( $astrFormElements[$i]['type'] == 'TIMESTAMP' ) {
                    $strFields .= $astrFormElements[$i]['name'].", ";
                    //    $strUpdateFields .= $astrFormElements[$i]['name'];
                    $strInsert .= time(). ", ";
                    //build the update fields & values
                    //convert user input to right format
                    /*$strUpdateFields .= 
                        $strControlName. "=". dateConvDate2IntDate($mixControlValue). ", ";*/
                }
                elseif( $astrFormElements[$i]['type'] == 'TIME' ) {
                    $strFields .= $astrFormElements[$i]['name'].", ";
                    $strUpdateFields .= $astrFormElements[$i]['name'];
                    $astrSearch = array('.', ',', ' ');
                    //build the insert into fields
                    //$strFields .= $strControlName. ", ";
                    //build the insert into fieldvalues
                    //convert user input to right format
                    
                    $strInsert .= "'". str_replace($astrSearch, ":", $astrValues[$astrFormElements[$i]['name']]). "', ";
                    //build the update fields & values
                    //convert user input to right format
                    $strUpdateFields .= 
                        $strControlName. "='". str_replace($astrSearch, ":", $astrValues[$astrFormElements[$i]['name']]). "', ";
                }
                
            }
        }
        elseif( $astrFormElements[$i]['type'] != 'IFRAME' && $astrFormElements[$i]['type'] != 'IFORM' && $astrFormElements[$i]['type'] != 'HID_INT' && $astrFormElements[$i]['type'] != 'NEWLINE' && $astrFormElements[$i]['type'] != 'BUTTON' ) {
            if ( !$astrFormElements[$i]['allow_null'] ) {
                $blnMissingValues = TRUE;
                $strOnLoad .= "alert('".$GLOBALS['locERRVALUEMISSING']." : ".$astrFormElements[$i]['label']."');";
            }
        }
    }
    if( !$blnMissingValues ) {
        $strInsert = substr($strInsert, 0, -2);
        $strFields = substr($strFields, 0, -2);
        $strUpdateFields = substr($strUpdateFields, 0, -2);

        $strQuery =
            "INSERT INTO " . $strTable . " ( ".
            $strFields . ", ". $strParentKey ." ) ".
            "VALUES ( ".
            $strInsert . ", " . $intParentKey . " );";

        //echo $strQuery."<br>\n";
        $intRes = @mysql_query($strQuery);
        if( $intRes ) {
            if( $blnNew ) {
                /*$oid = mysql_insert_id();
                $strQuery = 
                    "SELECT " . $strPrimaryKey . 
                    " FROM ". $strTable .
                    " WHERE " . $strPrimaryKey ."=" . $oid . ";";
                $intRes = mysql_query($strQuery);*/
                $intKeyValue = mysql_insert_id();
                //$intKeyValue = mysql_result( $intRes, 0, $strPrimaryKey );
            }
//            $strOnLoad = "window.open('list.php?form=" . $strForm . "','f_list');";
            $blnInsertDone = TRUE;
        }
        else {
            $strOnLoad = "alert('".$GLOBALS['locERRINSERTFAILED']."');";
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
        $astrValues[$astrFormElements[$i]['name']] = $_POST[$astrFormElements[$i]['name']] ? $_POST[$astrFormElements[$i]['name']] : $strDefaultValue;
    }
}

}
if( $blnDelete && $intKeyValue ) {
    $strQuery =
        "DELETE FROM " . $strTable . " ".
        "WHERE " . $strPrimaryKey . "=" . $intKeyValue . ";";
    $intRes = @mysql_query($strQuery);
    if( $intRes ) {
        //unset($intKeyValue);
        //unset($astrValues);
        //$strOnLoad = "window.open('list.php?form=" . $strForm . "','f_list');";
    }
    else {
        $strOnLoad = "alert('".$GLOBALS['locERRDELREFERENCE']."');";
    }
}
if( $intParentKey ) {
    $strQuery =
        "SELECT * FROM $strTable ".
        "WHERE $strParentKey = $intParentKey $strOrder";
    $intRes = mysql_query($strQuery);
    $intNumRows = mysql_num_rows($intRes);
    if( $intRes ) {
        for($i = 0; $i < $intNumRows; $i++ ) {
            $tmpID = mysql_result( $intRes, $i, "id");
            for( $j = 0; $j < count($astrFormElements); $j++ ) {
                if( $astrFormElements[$j]['type'] != 'IFRAME' && $astrFormElements[$j]['type'] != 'NEWLINE' ) {
                    if( $astrFormElements[$j]['type'] == 'INTDATE' ) {
                        $astrOldValues[$i][$astrFormElements[$j]['name']] =                         dateConvIntDate2Date( mysql_result( $intRes, $i, $astrFormElements[$j]['name'] ));
                    }
                    elseif( $astrFormElements[$j]['type'] == 'TIMESTAMP' ) {
                        $astrOldValues[$i][$astrFormElements[$j]['name']] =                         date("d.m.Y H:i", mysql_result( $intRes, $i, $astrFormElements[$j]['name'] ));
                    }
                    elseif( $astrFormElements[$j]['type'] == 'BUTTON' ) {
                       $astrOldValues[$i][$astrFormElements[$j]['name']] = $tmpID;
                    }
                    
                    else {
                        $astrOldValues[$i][$astrFormElements[$j]['name']] =                         mysql_result( $intRes, $i, $astrFormElements[$j]['name'] );
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
function OpenCalendar(datefield, event) {
    x = event.screenX;
    y = event.screenY;
    strLink = 'calendar.php?ses=<?php echo $GLOBALS['sesID']?>&datefield=' + datefield;
    
    window.open(strLink, 'calendar', 'height=260,width=280,screenX=' + x + ',screenY=' + y + ',left=' + x + ',top=' + y + ',menubar=no,scrollbars=yes,status=no,toolbar=no');
    
    return true;
}
function OpenClock(timefield, event) {
    x = event.screenX;
    y = event.screenY;
    strLink = 'clock.php?ses=<?php echo $GLOBALS['sesID']?>&timefield=' + timefield;
    
    window.open(strLink, 'clock', 'height=150,width=200,screenX=' + x + ',screenY=' + y + ',left=' + x + ',top=' + y + ',menubar=no,scrollbars=yes,status=no,toolbar=no');
    
    return true;
}
function OpenPop(strLink, event) {
    x = event.screenX;
    y = event.screenY;
    
    window.open(strLink, 'pop_iform', 'height=200,width=800,screenX=' + x + ',screenY=' + y + ',left=' + x + ',top=' + y + ',menubar=no,scrollbars=yes,status=no,toolbar=no');
    
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
for( $j = 0; $j < count($astrFormElements); $j++ ) {
    if( $astrFormElements[$j]['type'] != "HID_INT" && $astrFormElements[$j]['type'] != "SECHID_INT" && $astrFormElements[$j]['type'] != "BUTTON" && $astrFormElements[$j]['type'] != 'NEWLINE' ) {
?>
    <td class="label <?php echo strtolower($astrFormElements[$j]['style'])?>_label">
        <?php echo $astrFormElements[$j]['label']?><br>
        <?php echo htmlFormElement( $astrFormElements[$j]['name'],$astrFormElements[$j]['type'], gpcStripSlashes($astrValues[$astrFormElements[$j]['name']]), $astrFormElements[$j]['style'],$astrFormElements[$j]['listquery'], "MODIFY", 0, '', array(), $astrFormElements[$j]['elem_attributes'])?>
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
error_log("ELEM: ". $astrFormElements[$j]['name'] . ' - ' .$astrFormElements[$j]['elem_attributes']);
?>
    <td class="<?php echo $astrFormElements[$j]['style']?>" <?php echo $strColspan?>>
        <?php echo htmlFormElement( $astrFormElements[$j]['name'],$astrFormElements[$j]['type'], gpcStripSlashes($astrOldValues[$i][$astrFormElements[$j]['name']]), $astrFormElements[$j]['style'],$astrFormElements[$j]['listquery'], "NO_MOD", 0, $astrFormElements[$j]['label'], array(), $astrFormElements[$j]['elem_attributes'])?>
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
