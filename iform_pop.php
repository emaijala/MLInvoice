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

$strDefaults = getPostRequest('defaults', '');
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

$blnSave = getPost('saveact', FALSE) ? TRUE : FALSE;
$blnCopy = getPost('copyact', FALSE) ? TRUE : FALSE;
$blnDelete = getPost('deleteact', FALSE) ? TRUE : FALSE;
$intKeyValue = getPostRequest($strPrimaryKey, FALSE);
$intParentKey = getPostRequest($strParentKey, FALSE);

for( $i = 0; $i < count($astrFormElements); $i++ ) {
    if(array_key_exists($astrFormElements[$i]['name'],$astrDefaults)) {
        $astrValues[$astrFormElements[$i]['name']] = $astrDefaults[$astrFormElements[$i]['name']];
    }
    elseif( !$astrFormElements[$i]['default'] ) {
        if( $astrFormElements[$i]['type'] == "INT" ) {
            $tmpValue = str_replace(",", ".", getPost($astrFormElements[$i]['name'], ''));
            $astrValues[$astrFormElements[$i]['name']] = $tmpValue ? (float)$tmpValue : FALSE;
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
           $strDefaultValue = getPost($astrFormElements[$i]['name'], FALSE);
        }
        elseif( strstr($astrFormElements[$i]['default'], "ADD") ) {
           $strQuery = str_replace("_PARENTID_", $intParentKey, $astrFormElements[$i]['listquery']);
           $intRes = mysql_query($strQuery);
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
//save the form values when user hits SAVE
if( $blnSave ) { 
    //check all form elements which save values
    $blnMissingValues = FALSE;
    for( $i = 0; $i < count($astrFormElements); $i++ ) {
        //lets shorten our if's and get array variables to tmp vars
        $strControlType = $astrFormElements[$i]['type'];
        $strControlName = $astrFormElements[$i]['name'];
        $mixControlValue = $astrValues[$strControlName];
                
        //don't handle IFORM, BUTTON, LABEL elements
        if( $strControlType != 'IFORM' && $strControlType != 'BUTTON' && $strControlType != 'LABEL' ) {
            //if element hasn't value and null's aren't allowed raise error
            if( $strControlType == "INT" ) {
                if ( !isset($mixControlValue) && !$astrFormElements[$i]['allow_null'] ) {
                    $blnMissingValues = TRUE;
                    $strOnLoad .= "alert('".$GLOBALS['locERRVALUEMISSING']." : ".$astrFormElements[$i]['label']."');";
                }
            }
            else {
                if ( !$mixControlValue && !$astrFormElements[$i]['allow_null'] ) {
                    $blnMissingValues = TRUE;
                    $strOnLoad .= "alert('".$GLOBALS['locERRVALUEMISSING']." : ".$astrFormElements[$i]['label']."');";
                }
            }
        }
    }
    //if no required values missing -> create the sql-query fields 
    if( !$blnMissingValues ) {
        $strFields = '';
        $strInsert = '';
        $strUpdateFields = '';
        $arrValues = array();
        for( $i = 0; $i < count($astrFormElements); $i++ ) {
            $strControlType = $astrFormElements[$i]['type'];
            $strControlName = $astrFormElements[$i]['name'];
            $mixControlValue = $astrValues[$strControlName];
            if( $strControlType == 'TEXT' || $strControlType == 'AREA' ) {
                $strFields .= "$strControlName, ";
                $strInsert .= '?, ';
                $strUpdateFields .= "$strControlName=?, ";
                $arrValues[] = $mixControlValue;
            }
            elseif( $strControlType == 'PASSWD' && $mixControlValue ) {
                $strFields .= "$strControlName, ";
                $strInsert .= '?, ';
                $strUpdateFields .= "$strControlName=md5(?), ";
                $arrValues[] = $mixControlValue;
            }
            //elements that are numeric TODO: do we need to save 0(zero)
            elseif( $strControlType == 'INT' || $strControlType == 'LIST' ) {
                //build the insert into fields
                $strFields .= "$strControlName, ";
                //format the numbers to right format - finnish use ,-separator
                $flttmpValue = 
                    $mixControlValue ? str_replace(",", ".", $mixControlValue) : 0;
                $strInsert .= '?, ';
                $strUpdateFields .= "$strControlName=?, ";
                $arrValues[] = $flttmpValue;
            }
            //checkboxelements handled bit differently than other int's
            elseif( $strControlType == 'CHECK' ) {
                $strFields .= "$strControlName, ";
                //if checkbox checked save 1 else 0 TODO: consider TRUE/FALSE
                $tmpValue = $mixControlValue ? 1 : 0;
                $strInsert .= '?, ';
                $strUpdateFields .= "$strControlName=?, ";
                $arrValues[] = $tmpValue;
            }
            //date-elements need own formatting too
            elseif( $strControlType == 'INTDATE' ) {
                if( !$mixControlValue ) {
                    $mixControlValue = 'NULL';
                }
                $strFields .= "$strControlName, ";
                $strInsert .= '?, ';
                //convert user input to right format
                $strUpdateFields .= "$strControlName=?, ";
                $arrValues[] = dateConvDate2IntDate($mixControlValue);
            }
            elseif( $strControlType == 'TIMESTAMP' ) {
                $strFields .= "$strControlName, ";
                $strInsert .= '?, ';
                if ($blnNew)
                  $arrValues[] = dateConvDate2IntDate($mixControlValue);
            }
            //time-elements need own formatting too
            elseif( $strControlType == 'TIME' ) {
                $astrSearch = array('.', ',', ' ');
                $strFields .= "$strControlName, ";
                $strInsert .= '?, ';
                //convert user input to right format
                $strUpdateFields .= "$strControlName=?, ";
                $arrValues[] = str_replace($astrSearch, ":", $mixControlValue);
            }
        }
        
        //subtract last loops unnecessary ', '-parts 
        $strInsert = substr($strInsert, 0, -2);
        $strFields = substr($strFields, 0, -2);
        $strUpdateFields = substr($strUpdateFields, 0, -2);
        if( $blnCopy ) {
            $strQuery = "INSERT INTO $strTable ($strFields) VALUES ($strInsert)";
        }
        else {
            $strQuery = "UPDATE $strTable SET $strUpdateFields WHERE $strPrimaryKey = ?";
            $arrValues[] = $intKeyValue;
        }
        
        $intRes = @mysql_param_query($strQuery, $arrValues, TRUE);
        
        if( $intRes ) {
            $blnUpdateDone = TRUE;
            $strOnLoad = "opener.document.forms[0].submit(); self.close();";
        }
        //if there's no resource identifier something went wrong
        else {
            $strOnLoad = "alert('" . $GLOBALS['locDBERRORDESC'] . addslashes(mysql_error()) . "');";
        }
    }
}

//did the user press delete-button
//if we have primarykey we can fulfill his commands
if( $blnDelete && $intKeyValue ) {
    //create the delete query
    $strQuery = "DELETE FROM $strTable WHERE $strPrimaryKey=?";
    //send query to database
    $intRes = @mysql_param_query($strQuery, array($intKeyValue));
    //if delete was succesfull we have res-id
    if( $intRes ) {
        //dispose the primarykey value
        unset($intKeyValue);
        //clear form elements
        unset($astrValues);
        $blnNew = TRUE;
        $strOnLoad = "top.frset_bottom.f_list.document.forms[0].key_values.value=''; top.frset_bottom.f_list.document.forms[0].submit();";
    }
    //if delete-query didn't workout
    else {
        $strOnLoad = "alert('" . $GLOBALS['locDBERRORDESC'] . addslashes(mysql_error()) . "');";
    }
}

if( $intKeyValue ) {
    $strQuery =
        "SELECT * FROM $strTable WHERE $strPrimaryKey=?";
    $intRes = mysql_param_query($strQuery, array($intKeyValue));
    $intNRows = mysql_num_rows($intRes);
    if( $intNRows ) {
        for( $j = 0; $j < count($astrFormElements); $j++ ) {
            $strControlType = $astrFormElements[$j]['type'];
            $strControlName = $astrFormElements[$j]['name'];
            
            if( $strControlType == 'IFORM' || $strControlType == 'RESULT' ) {
               $astrValues[$strControlName] = $intKeyValue;
               if( isset($astrFormElements[$j]['defaults']) && is_array($astrFormElements[$j]['defaults']) ) {
                   $strDefaults = "defaults=";
                    while (list($key, $val) = each($astrFormElements[$j]['defaults'])) {
                        if($astrFormElements[$j]['types'][$key] == 'INT' ) {
                            $astrFormElements[$j]['defaults'][$key] = $astrValues[$astrFormElements[$j]['mapping'][$key]];
                        }
                        elseif( $astrFormElements[$j]['types'][$key] == 'INTDATE' ) {
                            $astrFormElements[$j]['defaults'][$key] = dateConvDate2IntDate( $astrValues[$astrFormElements[$j]['mapping'][$key]]);
                        }
                    }
               }
            }
            elseif( $strControlType == 'BUTTON' ) {
                if( strstr($astrFormElements[$j]['listquery'], "=_ID_") ) {
                    $astrValues[$strControlName] = $intKeyValue ? $intKeyValue : FALSE;
                }
                else {
                    $tmpListQuery = $astrFormElements[$j]['listquery'];
                    $strReplName = substr($tmpListQuery, strpos($tmpListQuery, "_"));
                    $strReplName = strtolower(substr($strReplName, 1, strrpos($strReplName, "_")-1));
                    $astrValues[$strControlName] = isset($astrValues[$strReplName]) ? $astrValues[$strReplName] : '';
                    $astrFormElements[$j]['listquery'] = str_replace(strtoupper($strReplName), "ID", $astrFormElements[$j]['listquery']);
                }
            }
            elseif( $strControlType != 'LABEL' ) {
                if( $strControlType == 'INTDATE' ) {
                    $astrValues[$strControlName] = dateConvIntDate2Date( mysql_result( $intRes, 0, $strControlName ));
                }
                elseif( $strControlType == 'TIMESTAMP' ) {
                        $astrValues[$strControlName] = date("d.m.Y H:i", mysql_result( $intRes, $i, $strControlName ));
                }
                else { 
                    if ($strControlName)
                        $astrValues[$strControlName] = mysql_result($intRes, 0, $strControlName);
                }
            }
        }
    }
    else {
        echo $GLOBALS['locENTRYDELETED']; die;
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
