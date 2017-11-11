<?php
/*******************************************************************************
 MLInvoice: web-based invoicing application.
 Copyright (C) 2010-2017 Ere Maijala

 This program is free software. See attached LICENSE.

 *******************************************************************************/

/*******************************************************************************
 MLInvoice: web-pohjainen laskutusohjelma.
 Copyright (C) 2010-2017 Ere Maijala

 Tämä ohjelma on vapaa. Lue oheinen LICENSE.

 *******************************************************************************/
require_once 'sqlfuncs.php';
require_once 'miscfuncs.php';
require_once 'translator.php';

function createSettingsList()
{
    if (!sesAdminAccess()) {
        ?>
<div class="form_container ui-widget-content">
    <?php echo Translator::translate('NoAccess') . "\n"?>
  </div>
<?php
        return;
    }

    include 'settings_def.php';

    $messages = '';

    $blnSave = getPostRequest('saveact', false) ? true : false;
    if ($blnSave) {
        foreach ($arrSettings as $name => $elem) {
            $type = $elem['type'];
            $label = $elem['label'];
            if ($type == 'LABEL') {
                continue;
            }

            $newValue = getPost($name, null);
            if (!isset($newValue) || $newValue === '') {
                if (!$elem['allow_null']) {
                    $messages .= Translator::translate('ErrValueMissing')
                        . ": '" . Translator::translate($label) . "'<br>\n";
                    continue;
                } else {
                    $newValue = '';
                }
            }
            if (in_array($type, ['CURRENCY', 'PERCENT'])) {
                $newValue = str_replace(
                    Translator::translate('DecimalSeparator'), '.', $newValue
                );
            }
            if (in_array($type, ['CURRENCY', 'PERCENT', 'INT'])) {
                $newValue = trim($newValue);
                if (!is_numeric($newValue)) {
                    $messages .= Translator::translate('ErrInvalidValue')
                        . ": '" . Translator::translate($label) . "'<br>\n";
                    continue;
                }
            }

            if (isset($elem['session']) && $elem['session']) {
                $_SESSION[$name] = $newValue;
            }
            db_param_query('DELETE from {prefix}settings WHERE name=?', [$name]);
            db_param_query(
                'INSERT INTO {prefix}settings (name, value) VALUES (?, ?)',
                [$name, $newValue]
            );
        }
    }
    ?>
<div class="form_container ui-widget-content">
<?php if ($messages) {?>
    <div class="ui-widget ui-state-error"><?php echo $messages?></div>
<?php }?>

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
      $('#admin_form').find('input[type="text"],input[type="checkbox"],select,textarea').change(function() { $('.save_button').addClass('unsaved'); });
    });
    -->
    </script>

    <?php createSettingsListButtons()?>
    <div class="form">
        <form method="post" name="admin_form" id="admin_form">
    <?php
    foreach ($arrSettings as $name => $elem) {
        $elemType = $elem['type'];
        if ($elemType == 'LABEL') {
    ?>
        <div class="sublabel ui-widget-header ui-state-default"><?php echo Translator::translate($elem['label'])?></div>
    <?php
            continue;
        }
        $value = getPost($name, null);
        if (!isset($value)) {
            if (isset($elem['session']) && $elem['session']) {
                $value = isset($_SESSION[$name]) ? $_SESSION[$name]
                    : (isset($elem['default'])
                    ? cond_utf8_decode($elem['default']) : '');
            } else {
                $value = getSetting($name);
            }

            if ($elemType == 'CURRENCY') {
                $value = miscRound2Decim($value);
            } elseif ($elemType == 'PERCENT') {
                $value = miscRound2Decim($value, 1);
            }
        }
        if ($elemType == 'CURRENCY' || $elemType == 'PERCENT') {
            $elemType = 'INT';
        }
        $options = null;
        if (isset($elem['options'])) {
            $options = $elem['options'];
            foreach ($options as &$option) {
                $option = Translator::translate($option);
            }
        }
        if ($elemType == 'CHECK') {
        ?>
      <div class="field" style="clear: both">
        <?php echo htmlFormElement($name, $elemType, $value, $elem['style'], '', 'MODIFY', '', '', [], isset($elem['elem_attributes']) ? $elem['elem_attributes'] : '', $options)?>
        <label for="<?php echo $name?>"><?php echo Translator::translate($elem['label'])?></label>
            </div>
        <?php
        } else {
            ?>
      <div class="label" style="clear: both">
                <label for="<?php echo $name?>"><?php echo Translator::translate($elem['label'])?></label>
            </div>
            <div class="field" style="clear: both">
        <?php echo htmlFormElement($name, $elemType, $value, $elem['style'], '', 'MODIFY', '', '', [], isset($elem['elem_attributes']) ? $elem['elem_attributes'] : '', $options)?>
      </div>
<?php
        }
    }
    ?>
    <input type="hidden" name="saveact" value="0">
    <?php createSettingsListButtons()?>
    </form>
    </div>
</div>
<?php
}

function createSettingsListButtons()
{
    ?>
<div class="form_buttons" style="clear: both">
    <a class="actionlink save_button" href="#"
        onclick="document.getElementById('admin_form').saveact.value=1; document.getElementById('admin_form').submit(); return false;"><?php echo Translator::translate('Save')?></a>
</div>
<?php
}
