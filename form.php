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

require_once "sqlfuncs.php";
require_once "miscfuncs.php";
require_once "datefuncs.php";
require_once "localize.php";

function createForm($strFunc, $strForm)
{
  require "form_switch.php";
  
  $blnNew = getPostRequest('newact', FALSE) || getPostRequest('new', FALSE) ? TRUE : FALSE;
  $blnCopy = getPostRequest('copyact', FALSE) ? TRUE : FALSE;
  $blnSave = getPostRequest('saveact', FALSE) ? TRUE : FALSE;
  $blnDelete = getPostRequest('deleteact', FALSE) ? TRUE : FALSE;
  $intKeyValue = getPostRequest($strPrimaryKey, FALSE);
  
  $strMessage = '';
  
  //if NEW is clicked clear existing form data
  if( $blnNew && !$blnSave ) {
      unset($intKeyValue);
      unset($astrValues);
      unset($_POST);
  }
  
  //initialize elements
  for( $i = 0; $i < count($astrFormElements); $i++ ) {
      if( $astrFormElements[$i]['type'] == 'IFORM' || $astrFormElements[$i]['type'] == 'RESULT' || $astrFormElements[$i]['type'] == 'BUTTON' ) {
          $astrValues[$astrFormElements[$i]['name']] = isset($intKeyValue) ? $intKeyValue : FALSE;
      }
      else {
           if( !$astrFormElements[$i]['default'] ) {
              $astrValues[$astrFormElements[$i]['name']] = getPostRequest($astrFormElements[$i]['name'], FALSE);
           }
           else {
              if( $astrFormElements[$i]['default'] == "DATE_NOW" ) {
                 $strDefaultValue = date("d.m.Y");
              }
              elseif( strstr($astrFormElements[$i]['default'], "DATE_NOW+") ) {
                  $atmpValues = explode("+", $astrFormElements[$i]['default']);
                 $strDefaultValue = date("d.m.Y",mktime(0, 0, 0, date("m"), date("d")+$atmpValues[1], date("Y")));
              }            
              elseif( $astrFormElements[$i]['default'] == "TIME_NOW" ) {
                 $strDefaultValue = date("H:i");
              }
              elseif( $astrFormElements[$i]['default'] == "TIMESTAMP_NOW" ) {
                 $strDefaultValue = date("d.m.Y H:i");
              }
              else {
                  $strDefaultValue = $astrFormElements[$i]['default'];
              }
              $astrValues[$astrFormElements[$i]['name']] = getPostRequest($astrFormElements[$i]['name'], $strDefaultValue);
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
                      $strMessage .= $GLOBALS['locERRVALUEMISSING'] . ': ' . $astrFormElements[$i]['label'] . '<br>';
                  }
              }
              else {
                  if ( !$mixControlValue && !$astrFormElements[$i]['allow_null'] ) {
                      $blnMissingValues = TRUE;
                      $strMessage .= $GLOBALS['locERRVALUEMISSING'] . ': ' . $astrFormElements[$i]['label'] . '<br>';
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
          
          //subtract last loops' unnecessary ', '-parts 
          $strInsert = substr($strInsert, 0, -2);
          $strFields = substr($strFields, 0, -2);
          $strUpdateFields = substr($strUpdateFields, 0, -2);
          if( $blnNew ) {
              $strQuery = "INSERT INTO $strTable ($strFields) VALUES ($strInsert)";
          }
          else {
              $strQuery = "UPDATE $strTable SET $strUpdateFields WHERE $strPrimaryKey = ?";
              $arrValues[] = $intKeyValue;
          }
          
          $intRes = mysql_param_query($strQuery, $arrValues, TRUE);
          
          if( $intRes ) {
              if( $blnNew ) {
                  //get the latest insert ID from mysql
                  $intKeyValue = mysql_insert_id();   
              }
              
              //insert is now done - set the new flag to FALSE
              //then the next query will be update
              $blnNew = FALSE;
              //insert went fine - let the user know it
              $blnInsertDone = TRUE;
          }
          //if there's no resource identifier something went wrong
          else {
              $strMessage = $GLOBALS['locDBERRORDESC'] . addslashes(mysql_error()) . '<br>';
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
      echo $GLOBALS['locRECORDDELETED'];
      return;
  }
  
  if( isset($intKeyValue) && $intKeyValue ) {
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
  
  //print_r($astrFormElements[16]);
  
  if( $blnCopy ) {
      unset($intKeyValue);
      unset($_POST);
      $blnNew = TRUE;
  }
  
  ?>
  <div class="form">
  <?php echo $strMessage ?>
  
  
  <script type="text/javascript">
  <!--
	$(function() {
		$('input[class~="hasCalendar"]').datepicker();
	});
  -->
  </script>
  
  <form method="post" action="" name="admin_form" id="admin_form">
  <?php createFormButtons($blnNew) ?>
  <input type="hidden" name="<?php echo $strPrimaryKey?>" value="<?php echo isset($intKeyValue) ? $intKeyValue : '' ?>">
  <div class="form_container">
  <table>
  <?php
  for( $j = 0; $j < count($astrFormElements); $j++ ) {
      if($astrFormElements[$j]['type'] == "LABEL") {
  ?>
      <tr>
          <td class="sublabel" colspan="4">
              <?php echo $astrFormElements[$j]['label']?> 
          </td>
      </tr>
  <?php
      }
      else {
          if( $astrFormElements[$j]['position'] == 0 && !strstr($astrFormElements[$j]['type'], "HID_")) {
              echo "\t<tr>\n";
              $strColspan = "colspan=\"3\"";
              $intColspan = 4;
          }
          elseif( $astrFormElements[$j]['position'] == 1 && !strstr($astrFormElements[$j]['type'], "HID_")) {
              echo "\t<tr>\n";
              $strColspan = "colspan=\"0\"";
              $intColspan = 2;
          }
          else {
              $intColspan = 2;
          }
      
          if( $blnNew && ( $astrFormElements[$j]['type'] == "BUTTON" || $astrFormElements[$j]['type'] == "IFORM" || $astrFormElements[$j]['type'] == "PIC" ) ) {
              echo "<td class=\"label\" colspan=\"2\">&nbsp;</td>";
          }
          elseif( $astrFormElements[$j]['type'] == "IFORM" ) {
   ?>
          <td class="label" colspan="<?php echo $intColspan?>">
              <?php echo $astrFormElements[$j]['label']?>
              <br>
              <?php echo htmlFormElement($astrFormElements[$j]['name'], $astrFormElements[$j]['type'], gpcStripSlashes($astrValues[$astrFormElements[$j]['name']]), $astrFormElements[$j]['style'],$astrFormElements[$j]['listquery'], "MODIFY", $astrFormElements[$j]['parent_key'],'',$astrFormElements[$j]['defaults'], $astrFormElements[$j]['elem_attributes'])?>
          </td>
  <?php          
          }
          elseif( $astrFormElements[$j]['type'] == "BUTTON" ) {
   ?>
          <td class="button" colspan="<?php echo $intColspan?>">
              <?php echo htmlFormElement($astrFormElements[$j]['name'], $astrFormElements[$j]['type'], gpcStripSlashes($astrValues[$astrFormElements[$j]['name']]), $astrFormElements[$j]['style'],$astrFormElements[$j]['listquery'], "MODIFY", $astrFormElements[$j]['parent_key'],$astrFormElements[$j]['label'], array(), isset($astrFormElements[$j]['elem_attributes']) ? $astrFormElements[$j]['elem_attributes'] : '')?>
          </td>
  <?php          
          }
          
          elseif( $astrFormElements[$j]['type'] == "HID_INT" || $astrFormElements[$j]['type'] == "HID_TEXT" || strstr($astrFormElements[$j]['type'], "HID_") ) {
   ?>
          <?php echo htmlFormElement($astrFormElements[$j]['name'], $astrFormElements[$j]['type'], gpcStripSlashes($astrValues[$astrFormElements[$j]['name']]), $astrFormElements[$j]['style'],$astrFormElements[$j]['listquery'], "MODIFY", $astrFormElements[$j]['parent_key'],$astrFormElements[$j]['label'])?>
  <?php          
          }
          else {
  ?>
          <td class="label">
              <?php echo $astrFormElements[$j]['label']?>
          </td>
          <td class="field" <?php echo $strColspan?>>
              <?php echo htmlFormElement($astrFormElements[$j]['name'], $astrFormElements[$j]['type'], gpcStripSlashes($astrValues[$astrFormElements[$j]['name']]), $astrFormElements[$j]['style'],$astrFormElements[$j]['listquery'], "MODIFY", isset($astrFormElements[$j]['parent_key']) ? $astrFormElements[$j]['parent_key'] : '', '', array(), isset($astrFormElements[$j]['elem_attributes']) ? $astrFormElements[$j]['elem_attributes'] : '')?>
          </td>
  <?php
          }
          
          if( $astrFormElements[$j]['position'] == 0 || $astrFormElements[$j]['position'] == 2 ) {
              echo "\t</tr>\n";
          }
      }
  }
  if( $blnNew ) {
      $intNew = 1;
  }
  else {
      $intNew = 0;
  }
  ?>
  </table>
  </div>
  <input type="hidden" name="saveact" value="0">
  <input type="hidden" name="copyact" value="0">
  <input type="hidden" name="newact" value="<?php echo $intNew?>">
  <input type="hidden" name="deleteact" value="0">
  <?php createFormButtons($blnNew) ?>
<?php
}

function createFormButtons($boolNew)
{
?>
  <div class="form_buttons">
  <table>
  <tr>
      <td>
          <a class="actionlink" href="#" onclick="document.getElementById('admin_form').saveact.value=1; document.getElementById('admin_form').submit(); return false;"><?php echo $GLOBALS['locSAVE']?></a>
      </td>
  <?php
  if( !$boolNew ) {
  ?>    
      <td>
          <a class="actionlink" href="#" onclick="document.getElementById('admin_form').copyact.value=1; document.getElementById('admin_form').submit(); return false;"><?php echo $GLOBALS['locCOPY']?></a>
      </td>
      <td>
          <a class="actionlink" href="#" onclick="document.getElementById('admin_form').newact.value=1; document.getElementById('admin_form').submit(); return false;"><?php echo $GLOBALS['locNEW']?></a>
      </td>
      <td>
          <a class="actionlink" href="#" onclick="if(confirm('<?php echo $GLOBALS['locCONFIRMDELETE']?>')==true) {  document.getElementById('admin_form').deleteact.value=1; document.getElementById('admin_form').submit(); return false;} else{ return false; }"><?php echo $GLOBALS['locDELETE']?></a>        
      </td>
  <?php
  }
  ?>
  <td>
      <a class="actionlink" href="#" onclick="var win = window.open('help.php?ses=<?php echo $GLOBALS['sesID']?>&amp;topic=form', '_blank', 'height=400,width=400,menubar=no,scrollbars=yes,status=no,toolbar=no'); win.focus(); return false;"><?php echo $GLOBALS['locHELP']?></a>
  </td>
  </tr>        
  </table>
  </div>
<?php
}
