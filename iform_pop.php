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

$blnSave = getPostRequest('saveact', FALSE) ? TRUE : FALSE;
$blnCopy = getPostRequest('copyact', FALSE) ? TRUE : FALSE;
$blnDelete = getPostRequest('deleteact', FALSE) ? TRUE : FALSE;
$intKeyValue = getPostRequest($strPrimaryKey, FALSE);
$intParentKey = getPostRequest($strParentKey, FALSE);

$strOnLoad = '';

for( $i = 0; $i < count($astrFormElements); $i++ ) {
    if(array_key_exists($astrFormElements[$i]['name'],$astrDefaults)) {
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
           $strDefaultValue = getPost($astrFormElements[$i]['name'], FALSE);
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
//save the form values when user hits SAVE
if( $blnSave ) { 
    //check all form elements which save values
    $blnMissingValues = FALSE;
    for( $i = 0; $i < count($astrFormElements); $i++ ) {
        //lets shorten our if's and get array variables to tmp vars
        $strControlType = $astrFormElements[$i]['type'];
        $strControlName = $astrFormElements[$i]['name'];
        $mixControlValue = $astrValues[$strControlName];
                
        //don't handle IFORM, BUTTON, LABEL, HID_INT elements
        if( $strControlType != 'IFORM' && $strControlType != 'BUTTON' && $strControlType != 'LABEL' && $strControlType != 'HID_INT' && $strControlType != 'ROWSUM' ) {
            //if element hasn't value and null's aren't allowed raise error
            if( $strControlType == "INT" ) {
                if ( !isset($mixControlValue) && !$astrFormElements[$i]['allow_null'] ) {
                    $blnMissingValues = TRUE;
                    $strOnLoad .= "alert('".$GLOBALS['locERRVALUEMISSING']." : ".$astrFormElements[$i]['label']."');";
                    error_log('Value missing for field ' . $astrFormElements[$i]['name']);
                }
            }
            else {
                if ( !$mixControlValue && !$astrFormElements[$i]['allow_null'] ) {
                    $blnMissingValues = TRUE;
                    $strOnLoad .= "alert('".$GLOBALS['locERRVALUEMISSING']." : ".$astrFormElements[$i]['label']."');";
                    error_log('Value missing for field ' . $astrFormElements[$i]['name']);
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
                $tmpValue = is_null($mixControlValue) ? NULL : str_replace(",", ".", $mixControlValue);
                $strInsert .= '?, ';
                $strUpdateFields .= "$strControlName=?, ";
                $arrValues[] = $tmpValue;
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
        
        //subtract last loops' unnecessary ', '-parts 
        $strInsert = substr($strInsert, 0, -2);
        $strFields = substr($strFields, 0, -2);
        $strUpdateFields = substr($strUpdateFields, 0, -2);
        if( $blnCopy ) {
            $strQuery = "INSERT INTO $strTable ($strFields, $strParentKey) VALUES ($strInsert, ?)";
            $arrValues[] = $intParentKey;
        }
        else {
            $strQuery = "UPDATE $strTable SET $strUpdateFields, deleted=0 WHERE $strPrimaryKey=?";
            $arrValues[] = $intKeyValue;
        }
        
        $intRes = @mysql_param_query($strQuery, $arrValues, TRUE);
        
        if( $intRes ) {
            $blnUpdateDone = TRUE;
            $strOnLoad = "parent.document.forms[0].submit();";
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
    $strQuery = "UPDATE $strTable SET deleted=1 WHERE $strPrimaryKey=?";
    //send query to database
    mysql_param_query($strQuery, array($intKeyValue));
    //dispose the primarykey value
    $intKeyValue = '';
    //clear form elements
    unset($astrValues);
    $blnNew = TRUE;
    $strOnLoad = "parent.document.forms[0].submit();";
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
            elseif( $strControlType != 'LABEL' && $strControlType != 'ROWSUM' ) {
                if( $strControlType == 'INTDATE' ) {
                    $astrValues[$strControlName] = dateConvIntDate2Date( mysql_result( $intRes, 0, $strControlName ));
                }
                elseif( $strControlType == 'TIMESTAMP' ) {
                        $astrValues[$strControlName] = date("d.m.Y H:i", mysql_result( $intRes, 0, $strControlName ));
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

?>
<body class="iform_pop" onload="<?php echo $strOnLoad?>">
<script type="text/javascript">
<!--
$(function() {
  $('input[class~="hasCalendar"]').datepicker();
});
-->
</script>
<form method="post" action="iform_pop.php?selectform=<?php echo $strForm?>" target="_self" name="pop_iform" id="pop_iform">
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
    if( $astrFormElements[$j]['type'] != "HID_INT" && $astrFormElements[$j]['type'] != "SECHID_INT" && $astrFormElements[$j]['type'] != "BUTTON" && $astrFormElements[$j]['type'] != 'NEWLINE' && $astrFormElements[$j]['type'] != 'ROWSUM' ) {
?>
    <td class="label">
        <?php echo $astrFormElements[$j]['label']?><br>
        <?php echo htmlFormElement( $astrFormElements[$j]['name'],$astrFormElements[$j]['type'], gpcStripSlashes(isset($astrValues[$astrFormElements[$j]['name']]) ? $astrValues[$astrFormElements[$j]['name']] : ''), $astrFormElements[$j]['style'],$astrFormElements[$j]['listquery'])?>
    </td>
<?php
    }
    elseif( $astrFormElements[$j]['type'] == 'SECHID_INT' ) {
?>
    <?php echo htmlFormElement( $astrFormElements[$j]['name'],"HID_INT", gpcStripSlashes($astrValues[$astrFormElements[$j]['name']]), $astrFormElements[$j]['style'],$astrFormElements[$j]['listquery'])?>
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

</form>
</body>
</html>
