<?php
/**
 * Settings form
 *
 * PHP version 7
 *
 * Copyright (C) Samu Reinikainen 2004-2008
 * Copyright (C) Ere Maijala 2010-2021
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category MLInvoice
 * @package  MLInvoice\Base
 * @author   Ere Maijala <ere@labs.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://labs.fi/mlinvoice.eng.php
 */
require_once 'sqlfuncs.php';
require_once 'miscfuncs.php';
require_once 'translator.php';

/**
 * Create a list of settings
 *
 * @return void
 */
function createSettingsList()
{
    if (!sesAdminAccess()) {
        ?>
<div class="form_container">
        <?php echo Translator::translate('NoAccess') . "\n"?>
  </div>
        <?php
        return;
    }

    include 'settings_def.php';

    $messages = '';

    $blnSave = getPostOrQuery('saveact', false) ? true : false;
    if ($blnSave) {
        foreach ($arrSettings as $name => $elem) {
            $type = $elem['type'];
            $label = $elem['label'];
            if ($type == 'HEADING') {
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
            dbParamQuery('DELETE from {prefix}settings WHERE name=?', [$name]);
            dbParamQuery(
                'INSERT INTO {prefix}settings (name, value) VALUES (?, ?)',
                [$name, $newValue]
            );
        }
    }
    ?>
<div class="form_container">
    <?php if ($messages) {?>
        <div class="alert alert-danger"><?php echo $messages?></div>
    <?php }?>

    <script>
    <!--
    $(document).ready(function() {
      $('#settings_form')
        .find('input[type="text"],input[type="date"],input[type="checkbox"],select,textarea')
        .on('change', function() {
            MLInvoice.highlightButton('.save_button', true);
        });
    });
    -->
    </script>

    <?php createSettingsListButtons()?>
    <div class="form settings-list">
        <form method="post" name="settings_form" id="settings_form">
    <?php
    foreach ($arrSettings as $name => $elem) {
        $elemType = $elem['type'];
        if ($elemType == 'HEADING') {
            ?>
        <h2>
            <?php echo Translator::translate($elem['label'])?>
        </h2>
            <?php
            continue;
        }
        $value = getPost($name, null);
        if (!isset($value)) {
            if (isset($elem['session']) && $elem['session']) {
                $value = $_SESSION[$name]
                    ?? (isset($elem['default'])
                    ? condUtf8Decode($elem['default']) : '');
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
        <div class="field">
            <?php
            echo htmlFormElement(
                $name, $elemType, $value, $elem['style'], '', 'MODIFY', '', '', [],
                $elem['elem_attributes'] ?? '', $options
            );
            ?>
          <label for="<?php echo $name?>">
            <?php echo Translator::translate($elem['label'])?>
          </label>
        </div>
            <?php
        } else {
            ?>
            <div class="label">
                <label for="<?php echo $name?>">
                    <?php echo Translator::translate($elem['label'])?>
                </label>
            </div>
            <div class="field">
            <?php
            echo htmlFormElement(
                $name, $elemType, $value, $elem['style'] ?? '', '', 'MODIFY', '', '', [],
                $elem['elem_attributes'] ?? '', $options
            );
            ?>
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

/**
 * Create buttons
 *
 * @return void
 */
function createSettingsListButtons()
{
    ?>
<div class="btn-set form_buttons">
    <a class="btn btn-outline-primary save_button form-submit" href="#" data-set-field="saveact">
        <?php echo Translator::translate('Save')?>
    </a>
</div>
    <?php
}
