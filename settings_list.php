<?php
/*******************************************************************************
VLLasku: web-based invoicing application.
Copyright (C) 2010 Ere Maijala

This program is free software. See attached LICENSE.

*******************************************************************************/

/*******************************************************************************
VLLasku: web-pohjainen laskutusohjelma.
Copyright (C) 2010 Ere Maijala

Tämä ohjelma on vapaa. Lue oheinen LICENSE.

*******************************************************************************/

require_once "sqlfuncs.php";
require_once "miscfuncs.php";
require_once "localize.php";

function createSettingsList()
{
  if ($_SESSION['sesACCESSLEVEL'] != 99 )
  {
?>
  <div class="form_container">
    <?php echo $GLOBALS['locNOACCESS'] . "\n"?>
  </div>
<?php
    return;
  }
  
  require 'settings_def.php';

  $blnSave = getPostRequest('saveact', FALSE) ? TRUE : FALSE;

  $errors = '';
  if ($blnSave)
  {
    foreach ($arrSettings as $elem)
    {
      if ($elem['type'] == 'LABEL')
        continue;
      $newValue = getPost($elem['name'], NULL);
      if (!isset($newValue))
      {
        if (!$elem['allow_null'])
        {
          $errors .= $GLOBALS['locERRVALUEMISSING'] . ': ' . $elem['name'] . "<br>\n";
          continue;
        }
        else
        {
          $newValue = '';
        }
      }
      if ($elem['type'] == 'CURRENCY' || $elem['type'] == 'PERCENT')
        $newValue = str_replace(",", ".", $newValue);
      mysql_param_query('DELETE from {prefix}settings WHERE name=?', array($elem['name']));
      mysql_param_query('INSERT INTO {prefix}settings (name, value) VALUES (?, ?)', array($elem['name'], $newValue));
    }
  }
?>
  <div class="form">
    <span class="error"><?php echo $errors?>
  
    <script type="text/javascript">
    <!--
    $(document).ready(function() {
      $('input[class~="hasCalendar"]').datepicker();
      $('iframe[class~="resizable"]').load(function() {
        var iframe = $(this);
        var body = iframe.contents().find("body");
        var newHeight = body.outerHeight(true) + 10;
        // Leave room for calendar popup
        if (newHeight < 250)
          newHeight = 250;
        iframe.css("height", newHeight + 'px');
        body.css("overflow", "hidden");
      });   
    });
    -->
    </script>
  
    <form method="post" action="" name="admin_form" id="admin_form">
    <?php createSettingsListButtons()?>
    <div class="form_container">
<?php
    foreach ($arrSettings as $elem)
    {
      $elemType = $elem['type'];
      if ($elemType == 'LABEL')
      {
?>
        <div class="sublabel" style="clear: both; margin-top: 10px"><?php echo $elem['label']?></div>
<?php        
        continue;
      }
      $value = getPost($elem['name'], NULL);
      if (!isset($value))
      {
        $res = mysql_param_query('SELECT value from {prefix}settings WHERE name=?', array($elem['name']));
        if ($row = mysql_fetch_assoc($res))
          $value = $row['value'];
        else
          $value = isset($elem['default']) ? cond_utf8_encode($elem['default']) : '';
          
        if ($elemType == 'CURRENCY')
          $value = miscRound2Decim($value);
        elseif ($elemType == 'PERCENT')
          $value = miscRound2Decim($value, 1);
      }
      if ($elemType == 'CURRENCY' || $elemType == 'PERCENT')
        $elemType = 'INT';
?>
      <div class="label" style="clear: both"><?php echo $elem['label']?></div>
      <div class="field" style="clear: both">
        <?php echo htmlFormElement($elem['name'], $elemType, $value, $elem['style'], '', "MODIFY", '', '', array(), isset($elem['elem_attributes']) ? $elem['elem_attributes'] : '')?>
      </div>
<?php        
    }
?>      
    </div>
    <input type="hidden" name="saveact" value="0">
    <?php createSettingsListButtons()?>
    </form>
  </div>
<?php
}

function createSettingsListButtons()
{
?>
<div class="form_buttons" style="clear: both">
      <a class="actionlink" href="#" onclick="document.getElementById('admin_form').saveact.value=1; document.getElementById('admin_form').submit(); return false;"><?php echo $GLOBALS['locSAVE']?></a>
    </div>
<?php
}
