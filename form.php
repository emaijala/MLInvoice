<?php
/*******************************************************************************
VLLasku: web-based invoicing application.
Copyright (C) 2010-2011 Ere Maijala

Portions based on:
PkLasku : web-based invoicing software.
Copyright (C) 2004-2008 Samu Reinikainen

This program is free software. See attached LICENSE.

*******************************************************************************/

/*******************************************************************************
VLLasku: web-pohjainen laskutusohjelma.
Copyright (C) 2010-2011 Ere Maijala

Perustuu osittain sovellukseen:
PkLasku : web-pohjainen laskutusohjelmisto.
Copyright (C) 2004-2008 Samu Reinikainen

Tämä ohjelma on vapaa. Lue oheinen LICENSE.

*******************************************************************************/

require_once "sqlfuncs.php";
require_once "miscfuncs.php";
require_once "datefuncs.php";
require_once "localize.php";

function createForm($strFunc, $strList, $strForm)
{
  require "form_switch.php";
  
  if (!in_array($_SESSION['sesACCESSLEVEL'], $levelsAllowed) && $_SESSION['sesACCESSLEVEL'] != 99 )
  {
?>
  <div class="form_container">
    <?php echo $GLOBALS['locNOACCESS'] . "\n"?>
  </div>
<?php
    return;
  }
  
  $blnNew = getPostRequest('newact', FALSE);
  $blnCopy = getPostRequest('copyact', FALSE) ? TRUE : FALSE;
  $blnSave = getPostRequest('saveact', FALSE) ? TRUE : FALSE;
  $blnDelete = getPostRequest('deleteact', FALSE) ? TRUE : FALSE;
  $intKeyValue = getPostRequest($strPrimaryKey, FALSE);
  if (!$intKeyValue)
    $blnNew = TRUE;
  
  $strMessage = '';
  
  // if NEW is clicked clear existing form data
  if ($blnNew && !$blnSave)
  {
    unset($intKeyValue);
    unset($astrValues);
    unset($_POST);
    unset($_REQUEST);
  }
  
  // initialize elements
  foreach ($astrFormElements as $elem) 
  {
    if ($elem['type'] == 'IFORM' || $elem['type'] == 'RESULT' || $elem['type'] == 'BUTTON' || $elem['type'] == 'JSBUTTON' || $elem['type'] == 'IMAGE') 
    {
      $astrValues[$elem['name']] = isset($intKeyValue) ? $intKeyValue : FALSE;
    }
    else 
    {
      if (!$elem['default']) 
      {
        $astrValues[$elem['name']] = getPostRequest($elem['name'], FALSE);
      }
      else 
      {
        if ($elem['default'] == "DATE_NOW") 
        {
          $strDefaultValue = date("d.m.Y");
        }
        elseif (strstr($elem['default'], "DATE_NOW+")) 
        {
          $atmpValues = explode("+", $elem['default']);
          $strDefaultValue = date("d.m.Y",mktime(0, 0, 0, date("m"), date("d")+$atmpValues[1], date("Y")));
        }            
        elseif ($elem['default'] == "TIME_NOW") 
        {
          $strDefaultValue = date("H:i");
        }
        else 
        {
          $strDefaultValue = $elem['default'];
        }
        $astrValues[$elem['name']] = getPostRequest($elem['name'], $strDefaultValue);
      }
    }
  }
  
  $redirect = getRequest('redirect', null);
  if (isset($redirect))
  {
    // Redirect after save
    foreach ($astrFormElements as $elem)
    {
      if ($elem['name'] == $redirect && $elem['style'] == 'redirect')
      {
        $newLocation = str_replace("_ID_", $intKeyValue, $elem['listquery']);
      }
    }
  }
  
  // save the form values when user hits SAVE
  if ($blnSave) 
  { 
    $blnMissingValues = FALSE;
    $strFields = '';
    $strInsert = '';
    $strUpdateFields = '';
    $arrValues = array();
    foreach ($astrFormElements as $elem) 
    {
      $type = $elem['type'];
      $name = $elem['name'];
      $value = $astrValues[$name];
              
      if ($type == '' || $type == 'IFORM' || $type == 'BUTTON' || $type == 'JSBUTTON' || $type == 'LABEL' || $type == 'IMAGE')
        continue;
    
      if (!$elem['allow_null'] && (!isset($value) || $value === ''))
      {
        $blnMissingValues = TRUE;
        $strMessage .= $GLOBALS['locERRVALUEMISSING'] . ': ' . $elem['label'] . '<br>';
        break;
      }
      if ($strFields)
      {
        $strFields .= ', ';
        $strInsert .= ', ';
        $strUpdateFields .= ', ';
      }
      $strFields .= $name;
      $fieldPlaceholder = '?';
      switch ($type)
      {
      case 'PASSWD':
        $fieldPlaceholder = 'md5(?)';
        $arrValues[] = $value;
        break;
      case 'INT':
      case 'LIST':
        $arrValues[] = $value !== '' ? str_replace(",", ".", $value) : NULL;
        break;
      case 'CHECK':
        $arrValues[] = $value ? 1 : 0;
        break;
      case 'INTDATE':
        $arrValues[] = $value ? dateConvDate2IntDate($value) : NULL;
        break;
      default: 
        $arrValues[] = $value;
      }
      $strInsert .= $fieldPlaceholder;
      $strUpdateFields .= "$name=$fieldPlaceholder";
    }

    if (!$blnMissingValues)
    {
      if ($blnNew) 
      {
        $strQuery = "INSERT INTO $strTable ($strFields) VALUES ($strInsert)";
      }
      else 
      {
        $strQuery = "UPDATE $strTable SET $strUpdateFields, deleted=0 WHERE $strPrimaryKey=?";
        $arrValues[] = $intKeyValue;
      }
      mysql_param_query($strQuery, $arrValues);
        
      if ($blnNew) 
      {
        $intKeyValue = mysql_insert_id();   
      }
      else
      {
        if (getSetting('auto_close_form') && !isset($newLocation))
        {
          $qs = preg_replace('/&form=\w*/', '', $_SERVER['QUERY_STRING']);
          $qs = preg_replace('/&id=\w*/', '', $qs);
          header("Location: ". _PROTOCOL_ . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/index.php?$qs");
          return;
        }
      }    
      $blnNew = FALSE;
      $blnInsertDone = TRUE;
    }
  }
  if ($strMessage)
    unset($newLocation);
  
  if ($blnDelete && $intKeyValue) 
  {
    $strQuery = "UPDATE $strTable SET deleted=1 WHERE $strPrimaryKey=?";
    mysql_param_query($strQuery, array($intKeyValue));
    unset($intKeyValue);
    unset($astrValues);
    $blnNew = TRUE;
    if (getSetting('auto_close_form'))
    {
      $qs = preg_replace('/&form=\w*/', '', $_SERVER['QUERY_STRING']);
      $qs = preg_replace('/&id=\w*/', '', $qs);
      header("Location: ". _PROTOCOL_ . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/index.php?$qs");
      return;
    }
?>
  <div class="form_container">
    <?php echo $GLOBALS['locRECORDDELETED'] . "\n"?>
  </div>
<?php
    return;
  }
  
  if (isset($intKeyValue) && $intKeyValue) 
  {
    $strQuery = "SELECT * FROM $strTable WHERE $strPrimaryKey=?";
    $intRes = mysql_param_query($strQuery, array($intKeyValue));
    $row = mysql_fetch_assoc($intRes);
    if ($row) 
    {
      if ($row['deleted'])
         $strMessage .= $GLOBALS['locDeletedRecord'] . '<br>';
  
      foreach ($astrFormElements as $elem)
      {
        $type = $elem['type'];
        $name = $elem['name'];
  
        if (!$type || $type == 'LABEL')
          continue;

        switch ($type)
        {
        case 'IFORM':
        case 'RESULT':
          $astrValues[$name] = $intKeyValue;
          break;
        case 'BUTTON':
        case 'JSBUTTON':
        case 'IMAGE':
          if (strstr($elem['listquery'], "=_ID_")) 
          {
            $astrValues[$name] = $intKeyValue ? $intKeyValue : FALSE;
          }
          else 
          {
            $tmpListQuery = $elem['listquery'];
            $strReplName = substr($tmpListQuery, strpos($tmpListQuery, "_"));
            $strReplName = strtolower(substr($strReplName, 1, strrpos($strReplName, "_")-1));
            $astrValues[$name] = isset($astrValues[$strReplName]) ? $astrValues[$strReplName] : '';
            $elem['listquery'] = str_replace(strtoupper($strReplName), "ID", $elem['listquery']);
          }
          break;
        case 'INTDATE':
          $astrValues[$name] = dateConvIntDate2Date($row[$name]);
          break;
        default:
          $astrValues[$name] = $row[$name];
        }
      }
    }
    else 
    {
      echo $GLOBALS['locENTRYDELETED']; die;
    }
  }
  
  if ($blnCopy) 
  {
    unset($intKeyValue);
    unset($_POST);
    $blnNew = TRUE;
  }
  
  ?>
  <div class="form">
    <div class="message"><?php echo $strMessage ?></div>
  
  <script type="text/javascript">
  <!--
  $(document).ready(function() {
		$('input[class~="hasCalendar"]').datepicker();
		$('iframe[class~="resizable"]').load(function() {
		  var iframe = $(this);
		  var body = iframe.contents().find("body");
		  var newHeight = body.outerHeight(true) + 10;
		  // Leave room for calendar popup
		  if (newHeight < 350)
		    newHeight = 350;
		  iframe.css("height", newHeight + 'px');
		  body.css("overflow", "hidden");
		});   
  });
  $(window).load(function() {
    <?php if (isset($newLocation)) echo "setTimeout(\"window.location='$newLocation'\", 0);"?>		
  });
  function OpenPop(strLink, strOnClose, strTitle, event) {
      $("#popup_edit").dialog({ modal: true, width: 810, height: 520, resizable: true, 
        position: [50, 50], 
        buttons: {
          "<?php echo $GLOBALS['locCLOSE']?>": function() { $("#popup_edit").dialog('close'); }
        },
        title: strTitle,
        close: function(event, ui) { eval(strOnClose); }
      }).find("#popup_edit_iframe").attr("src", strLink);
      
      return true;
  }
  -->
  </script>
  
  <div id="popup_edit" style="display: none; width: 900px; overflow: hidden">
  <iframe marginheight="0" marginwidth="0" frameborder="0" id="popup_edit_iframe" src="about:blank" style="width: 100%; height: 100%; overflow: hidden; border: 0"></iframe>
  </div>

  <form method="post" action="" name="admin_form" id="admin_form">
  <?php createFormButtons($blnNew, $copyLinkOverride) ?>
  <input type="hidden" name="redirect" id="redirect" value="">
  <input type="hidden" name="<?php echo $strPrimaryKey?>" value="<?php echo (isset($intKeyValue) && $intKeyValue) ? $intKeyValue : '' ?>">
  <div class="form_container">
  <table>
  <?php
  foreach ($astrFormElements as $elem) 
  {
    if ($elem['type'] == "LABEL") 
    {
  ?>
      <tr>
          <td class="sublabel" colspan="4">
              <?php echo $elem['label']?> 
          </td>
      </tr>
  <?php
    }
    else 
    {
      if ($elem['position'] == 0 && !strstr($elem['type'], "HID_")) 
      {
        echo "    <tr>\n";
        $strColspan = "colspan=\"3\"";
        $intColspan = 4;
      }
      elseif ($elem['position'] == 1 && !strstr($elem['type'], "HID_")) 
      {
        echo "    <tr>\n";
        $strColspan = '';
        $intColspan = 2;
      }
      else 
      {
        $intColspan = 2;
      }
  
      if ($blnNew && ($elem['type'] == 'BUTTON' || $elem['type'] == 'JSBUTTON' || $elem['type'] == 'IFORM' || $elem['type'] == 'IMAGE')) 
      {
        echo "      <td class=\"label\" colspan=\"2\">&nbsp;</td>";
      }
      elseif ($elem['type'] == "IFORM") 
      {
?>
      <td class="label" colspan="<?php echo $intColspan?>">
        <?php echo $elem['label']?>
        <br>
        <?php echo htmlFormElement($elem['name'], $elem['type'], $astrValues[$elem['name']], $elem['style'], $elem['listquery'], "MODIFY", $elem['parent_key'],'',$elem['defaults'], $elem['elem_attributes'])?>
      </td>
<?php          
      }
      elseif ($elem['type'] == "BUTTON" || $elem['type'] == "JSBUTTON") 
      {
?>
      <td class="button" colspan="<?php echo $intColspan?>">
        <?php echo htmlFormElement($elem['name'], $elem['type'], $astrValues[$elem['name']], $elem['style'], $elem['listquery'], "MODIFY", $elem['parent_key'],$elem['label'], array(), isset($elem['elem_attributes']) ? $elem['elem_attributes'] : '')?>
      </td>
<?php          
      }
      elseif ($elem['type'] == "HID_INT" || strstr($elem['type'], "HID_")) 
      {
?>
      <?php echo htmlFormElement($elem['name'], $elem['type'], $astrValues[$elem['name']], $elem['style'], $elem['listquery'], "MODIFY", $elem['parent_key'],$elem['label'])?>
<?php          
      }
      elseif ($elem['type'] == "IMAGE") 
      {
?>
      <td class="image" colspan="<?php echo $intColspan?>">
          <?php echo htmlFormElement($elem['name'], $elem['type'], $astrValues[$elem['name']], $elem['style'], $elem['listquery'], "MODIFY", $elem['parent_key'],$elem['label'], array(), isset($elem['elem_attributes']) ? $elem['elem_attributes'] : '')?>
      </td>
<?php          
      }
      else 
      {
        $value = $astrValues[$elem['name']];
        if ($elem['style'] == 'measurement')
          $value = $value ? miscRound2Decim($value, 2) : '';
?>
      <td class="label">
        <?php echo $elem['label']?>
      </td>
      <td class="field" <?php echo $strColspan?>>
        <?php echo htmlFormElement($elem['name'], $elem['type'], $value, $elem['style'], $elem['listquery'], "MODIFY", isset($elem['parent_key']) ? $elem['parent_key'] : '', '', array(), isset($elem['elem_attributes']) ? $elem['elem_attributes'] : '')?>
      </td>
<?php
      }
      
      if ($elem['position'] == 0 || $elem['position'] == 2) 
      {
        echo "    </tr>\n";
      }
    }
  }
  $intNew = $blnNew ? 1 : 0;
  ?>
  </table>
  </div>
  <input type="hidden" name="saveact" value="0">
  <input type="hidden" name="copyact" value="0">
  <input type="hidden" name="newact" value="<?php echo $intNew?>">
  <input type="hidden" name="deleteact" value="0">
  <?php createFormButtons($blnNew, $copyLinkOverride) ?>
<?php
}

function createFormButtons($boolNew, $copyLinkOverride)
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
      $copyCmd = $copyLinkOverride ? "window.location='$copyLinkOverride'; return false;" : "document.getElementById('admin_form').copyact.value=1; document.getElementById('admin_form').submit(); return false;";
  ?>    
      <td>
        <a class="actionlink" href="#" onclick="<?php echo $copyCmd?>"><?php echo $GLOBALS['locCOPY']?></a>
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
    </tr>        
  </table>
  </div>
<?php
}
