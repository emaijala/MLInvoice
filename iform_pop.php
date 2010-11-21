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

$strForm = $_POST['selectform'] ? $_POST['selectform'] : $_REQUEST['selectform'];

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

/*
var_dump($_POST);
echo "<br />";
var_dump($_GET);
echo "<br />";
var_dump($_REQUEST);
*/

$blnSave = (int)$_POST['saveact'] ? TRUE : FALSE;
$blnCopy = (int)$_POST['copyact'] || (int)$_GET['copyact'] ? TRUE : FALSE;
$blnDelete = (int)$_POST['deleteact'] ? TRUE : FALSE;
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
if( $blnSave ) {
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
        
        if( $blnCopy ) {
            $strQuery =
                "INSERT INTO " . $strTable . " ( ".
                $strFields . ", ". $strParentKey ." ) ".
                "VALUES ( ".
                $strInsert . ", " . $intParentKey . " );";
    
            $strQuery."<br>\n";
            //$intRes = @mysql_query($strQuery);
            
        }
        else {
            $strQuery =
                "UPDATE " . $strTable . " SET ".
                $strUpdateFields . " ".
                "WHERE ". $strPrimaryKey . "=" . $intKeyValue . "";
    
            //echo $strQuery."<br>\n";die;
            
        }
        $intRes = @mysql_query($strQuery);
        if( $intRes ) {
            $blnUpdateDone = TRUE;
            $strOnLoad = "opener.document.forms[0].submit(); self.close();";
        }
        else {
            $strOnLoad = "alert('".$GLOBALS['locDBERROR']."');";
        }
    }
}

if( $blnDelete && $intKeyValue ) {
    $strQuery =
        "DELETE FROM " . $strTable . " ".
        "WHERE " . $strPrimaryKey . "=" . (int)$intKeyValue . ";";
    $intRes = @mysql_query($strQuery);
    if( $intRes ) {
        //unset($intKeyValue);
        //unset($astrValues);
        //$strOnLoad = "window.open('list.php?form=" . $strForm . "','f_list');";
        $strOnLoad = "opener.document.forms[0].submit(); self.close();";
    }
    else {
        $strOnLoad = "alert('".$GLOBALS['locERRDELREFERENCE']."');";
    }
}

if( $intKeyValue ) {
    $strQuery =
        "SELECT * FROM " . $strTable . " ".
        "WHERE " . $strPrimaryKey . "=" . $intKeyValue . ";";
    $intRes = mysql_query($strQuery);
    $intNumRows = mysql_num_rows($intRes);
    if( $intRes ) {
        for($i = 0; $i < $intNumRows; $i++ ) {
            $tmpID = mysql_result( $intRes, $i, "id");
            for( $j = 0; $j < count($astrFormElements); $j++ ) {
                if( $astrFormElements[$j]['type'] != 'IFRAME' && $astrFormElements[$j]['type'] != 'NEWLINE' ) {
                    if( $astrFormElements[$j]['type'] == 'INTDATE' ) {
                        $astrValues[$astrFormElements[$j]['name']] =                         dateConvIntDate2Date( mysql_result( $intRes, $i, $astrFormElements[$j]['name'] ));
                    }
                    elseif( $astrFormElements[$j]['type'] == 'BUTTON' ) {
                       $astrValues[$astrFormElements[$j]['name']] = $tmpID;
                    }
                    
                    else {
                        $astrValues[$astrFormElements[$j]['name']] =                         mysql_result( $intRes, $i, $astrFormElements[$j]['name'] );
                    }
                }
                else {
                    $astrValues[$astrFormElements[$j]['name']] = $intKeyValue;
                }
            }
        }
    }
}
//print_r($astrValues);
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

-->
</script>
<form method="post" action="iform_pop.php?selectform=<?php echo $strForm?>&ses=<?php echo $GLOBALS['sesID']?>" target="_self" name="pop_iform">
<?php
if (!$blnCopy) {
    echo "<input type=\"hidden\" name=\"{$strPrimaryKey}\" value=\"{$intKeyValue}\">\n";
}
?>
<input type="hidden" name="<?php echo $strParentKey?>" value="<?php echo $intParentKey?>">
<input type="hidden" name="defaults" value="<?php echo $strDefaults?>">
<table class="iform">
    <tr>
<?php

for( $j = 0; $j < count($astrFormElements); $j++ ) {
    if( $astrFormElements[$j]['type'] != "HID_INT" && $astrFormElements[$j]['type'] != "SECHID_INT" && $astrFormElements[$j]['type'] != "BUTTON" && $astrFormElements[$j]['type'] != 'NEWLINE' ) {
?>
    <td class="label">
        <?php echo $astrFormElements[$j]['label']?><br>
        <?php echo htmlFormElement( $astrFormElements[$j]['name'],$astrFormElements[$j]['type'],                               gpcStripSlashes($astrValues[$astrFormElements[$j]['name']]),                               $astrFormElements[$j]['style'],$astrFormElements[$j]['listquery'])?>
    </td>
<?php
    }
    elseif( $astrFormElements[$j]['type'] == 'SECHID_INT' ) {
?>
    <?php echo htmlFormElement( $astrFormElements[$j]['name'],"HID_INT",                               gpcStripSlashes($astrValues[$astrFormElements[$j]['name']]),                               $astrFormElements[$j]['style'],$astrFormElements[$j]['listquery'])?>
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
</tr>
</table>
<input type="hidden" name="saveact" value="0">
<input type="hidden" name="copyact" value="<?php echo $blnCopy?>">
<input type="hidden" name="deleteact" value="0">
<table>
<tr>
    <td>
        <a href="#" onclick="self.document.forms[0].saveact.value=1; self.document.forms[0].submit(); return false;" class="actionlink"><?php echo $GLOBALS['locSAVE']?></a>
    </td>
    <td>
        <a href="#" onClick="if(confirm('<?php echo $GLOBALS['locCONFIRMDELETE']?>')==true) {  self.document.forms[0].deleteact.value=1; self.document.forms[0].submit(); return false; } else{ return false; }" class="actionlink"><?php echo $GLOBALS['locDELETE']?></a>
    </td>
    <td>
        <a href="#" onClick="self.close(); return false;" class="actionlink"><?php echo $GLOBALS['locCLOSE']?></a>
    </td>
</tr>
</table>


</form>
</body>
</html>
