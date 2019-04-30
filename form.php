<?php
/**
 * Form display
 *
 * PHP version 5
 *
 * Copyright (C) 2004-2008 Samu Reinikainen
 * Copyright (C) 2010-2018 Ere Maijala
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
require_once 'datefuncs.php';
require_once 'translator.php';
require_once 'form_funcs.php';
require_once 'form_config.php';
require_once 'sessionfuncs.php';
require_once "memory.php";

/**
 * Create a form. A huge function that outputs the form.
 *
 * @param string $strFunc Function
 * @param string $strList List name
 * @param string $strForm Form name
 *
 * @return void
 */
function createForm($strFunc, $strList, $strForm)
{
    $formConfig = getFormConfig($strForm, $strFunc);

    if (!sesAccessLevel($formConfig['accessLevels']) && !sesAdminAccess()) {
        ?>
<div class="form_container ui-widget-content">
        <?php echo Translator::translate('NoAccess') . "\n"?>
  </div>
        <?php
        return;
    }

    $action = getPostOrQuery('action', false);
    $intKeyValue = getPostOrQuery('id', false);
    if (!$intKeyValue) {
        $action = 'new';
    }

    if ($action && !sesWriteAccess()) {
        ?>
<div class="form_container ui-widget-content">
        <?php echo Translator::translate('NoAccess') . "\n"?>
  </div>
        <?php
        return;
    }

    $strMessage = '';
    if (isset($_SESSION['formMessage']) && $_SESSION['formMessage']) {
        $strMessage = Translator::translate($_SESSION['formMessage']);
        unset($_SESSION['formMessage']);
    }

    $strErrorMessage = '';
    if (isset($_SESSION['formErrorMessage']) && $_SESSION['formErrorMessage']) {
        $strErrorMessage = Translator::translate($_SESSION['formErrorMessage']);
        unset($_SESSION['formErrorMessage']);
    }

    if ('new' === $action) {
        $formConfig['readOnly'] = false;
    }

    $redirect = getPostOrQuery('redirect', null);
    if (isset($redirect)) {
        // Redirect after save
        foreach ($formConfig['fields'] as $elem) {
            if ($elem['name'] == $redirect) {
                if ($elem['style'] == 'redirect') {
                    $newLocation = str_replace(
                        '_ID_', $intKeyValue, $elem['listquery']
                    );
                } elseif ($elem['style'] == 'openwindow') {
                    $openWindow = str_replace(
                        '_ID_', $intKeyValue, $elem['listquery']
                    );
                }
            }
        }
    }

    if ('delete' === $action && $intKeyValue && !$formConfig['readOnly']) {
        deleteRecord($formConfig['table'], $intKeyValue);
        unset($intKeyValue);
        unset($astrValues);
        if (getSetting('auto_close_after_delete')) {
            $qs = preg_replace('/&form=\w*/', '', $_SERVER['QUERY_STRING']);
            $qs = preg_replace('/&id=\w*/', '', $qs);
            header("Location: index.php?$qs");
            return;
        }
        ?>
<div class="form_container ui-widget-content">
        <?php echo Translator::translate('RecordDeleted') . "\n"?>
  </div>
        <?php
        return;
    }

    if (isset($intKeyValue) && $intKeyValue) {
        $res = fetchRecord($formConfig['table'], $intKeyValue, $formConfig['fields'], $astrValues);
        if ($res === 'deleted') {
            $strMessage .= Translator::translate('DeletedRecord');
        } elseif ($res === 'notfound') {
            $msg = Translator::translate('RecordNotFound');
            echo <<<EOT
<div class="form_container">
  <div class="message">$msg</div>
</div>
EOT;
            die();
        }
    }

    if ('copy' === $action) {
        unset($astrValues['id']);
        $id = 0;
        $res = saveFormData(
            $formConfig['table'], $id, $formConfig['fields'], $astrValues, $warnings
        );
        if ($res === true) {
            $qs = preg_replace('/&id=\w*/', "&id=$id", $_SERVER['QUERY_STRING']);
            header("Location: index.php?$qs");
            return;
        }
        $strErrorMessage = $warnings ? $warnings
            : (Translator::translate('ErrValueMissing') . ': ' . $res);
    }

    if (!isset($astrValues)) {
        $astrValues = getFormDefaultValues($formConfig['fields']);
    }
    ?>

<div id="popup_dlg" style="display: none">
    <iframe id="popup_dlg_iframe" src="about:blank"></iframe>
</div>
    <?php
    if ($formConfig['popupHTML']) {
        echo $formConfig['popupHTML'];
    }
    ?>

<div class="form_container">

    <?php
    createFormButtons(
        $strForm, $intKeyValue ? false : true, $formConfig['copyLink'], true, $formConfig['readOnly'],
        $formConfig['extraButtons'], true
    );

    if ($strForm == 'invoice' && !empty($astrValues['next_interval_date'])
        && strDate2UnixTime($astrValues['next_interval_date']) <= time()
    ) {
        ?>
    <div class="ui-state-highlight ui-border-all message">
        <?php echo Translator::translate('CreateCopyForNextInvoice')?>
    </div>
        <?php
    }
    if (!sesWriteAccess() || $formConfig['readOnly']) {
        $formDataAttrs[] = 'read-only';
    }
    $dataAttrs = empty($formConfig['dataAttrs']) ? '' : ' '
        . implode(
            ' ',
            array_map(
                function ($s) {
                    return "data-$s";
                },
                $formConfig['dataAttrs']
            )
        );
    ?>
    <div class="form">
        <form method="post" name="admin_form" id="admin_form"<?php echo $dataAttrs?>>
            <input type="hidden" name="action" value="">
            <input type="hidden" name="redirect" id="redirect" value="">
            <input type="hidden" id="record_id" name="id" value="<?php echo (isset($intKeyValue) && $intKeyValue) ? $intKeyValue : '' ?>">
    <?php
    if ('invoice' === $strForm) {
        ?>
            <input type="hidden" id="invoice_vatless" value="0">
        <?php
    }
    foreach ($formConfig['fields'] as $elem) {
        if ($elem['type'] == 'HID_INT' || strstr($elem['type'], 'HID_')) {
            echo htmlFormElement(
                $elem['name'], $elem['type'], $astrValues[$elem['name']],
                $elem['style'], $elem['listquery'], 'READONLY', $elem['parent_key'],
                $elem['label']
            );
        }
    }
    ?>
            <table>
    <?php
    $childFormConfig = false;
    $prevPosition = false;
    $prevColSpan = 1;
    $rowOpen = false;
    $formFieldMode = sesWriteAccess() && !$formConfig['readOnly'] ? 'MODIFY' : 'READONLY';
    foreach ($formConfig['fields'] as $elem) {
        if ($elem['type'] === false) {
            continue;
        }
        $style = $elem['style'] !== '' ? ' ' . $elem['style'] : '';
        $fieldClass = '';
        $fieldClassAttr = '';

        if (!empty($elem['hidden'])) {
            $style .= ' hidden';
            $fieldClass = ' hidden';
            $fieldClassAttr = ' class="hidden"';
        }

        $fieldMode = isset($elem['read_only']) && $elem['read_only'] ? 'READONLY' : $formFieldMode;

        if ($elem['type'] == 'LABEL') {
            if ($rowOpen) {
                echo "        </tr>\n";
            }
            $rowOpen = false;
            ?>
        <tr>
          <td class="ui-widget-header ui-state-default sublabel$style" colspan="4">
            <?php echo Translator::translate($elem['label'])?>
          </td>
                </tr>
            <?php
            continue;
        }

        if ($elem['position'] == 0 || $elem['position'] <= $prevPosition) {
            $prevPosition = 0;
            $prevColSpan = 1;
            echo "        </tr>\n";
            $rowOpen = false;
        }

        if ($elem['type'] != 'IFORM') {
            if (!$rowOpen) {
                $rowOpen = true;
                echo "        <tr>\n";
            }
            if ($prevPosition !== false && $elem['position'] > 0) {
                for ($i = $prevPosition + $prevColSpan; $i < $elem['position']; $i ++) {
                    echo "          <td class=\"label\">&nbsp;</td>\n";
                }
            }

            if ($elem['position'] == 0 && !strstr($elem['type'], 'HID_')) {
                $strColspan = 'colspan="3"';
                $intColspan = 3;
            } elseif ($elem['position'] == 1 && !strstr($elem['type'], 'HID_')) {
                $strColspan = '';
                $intColspan = 2;
            } else {
                $intColspan = 2;
            }
        }

        if (!$intKeyValue
            && in_array($elem['type'], ['BUTTON', 'JSBUTTON', 'IMAGE', 'DROPDOWNMENU'])
        ) {
            echo '          <td class="label$style">&nbsp;</td>';
        } elseif (in_array($elem['type'], ['BUTTON', 'JSBUTTON', 'DROPDOWNMENU'])) {
            $intColspan = 1;
            ?>
          <td class="button<?php echo $fieldClass?>">
            <?php

            echo htmlFormElement(
                $elem['name'], $elem['type'],
                isset($astrValues[$elem['name']]) ? $astrValues[$elem['name']] : '',
                $elem['style'], $elem['listquery'],
                $fieldMode, $elem['parent_key'], $elem['label'], [],
                isset($elem['elem_attributes']) ? $elem['elem_attributes'] : '',
                isset($elem['options']) ? $elem['options'] : null
            )
            ?>
          </td>
            <?php
        } elseif ($elem['type'] == 'FILLER') {
            $intColspan = 1;
            ?>
          <td<?php echo $fieldClassAttr?>>&nbsp;</td>
            <?php
        } elseif ($elem['type'] == 'HID_INT' || strstr($elem['type'], 'HID_')) {
            // Done outside the table
        } elseif ($elem['type'] == 'IMAGE') {
            ?>
          <td class="image<?php echo $fieldClass?>" colspan="<?php echo $intColspan?>">
            <?php echo htmlFormElement(
                $elem['name'], $elem['type'], $astrValues[$elem['name']],
                $elem['style'], $elem['listquery'], $fieldMode, $elem['parent_key'],
                $elem['label'], [],
                isset($elem['elem_attributes']) ? $elem['elem_attributes'] : '',
                isset($elem['options']) ? $elem['options'] : null
            ); ?>
          </td>
            <?php
        } elseif ($elem['type'] == 'IFORM') {
            if ($rowOpen) {
                echo "        </tr>\n";
            }
            echo "      </table>\n      </form>\n";
            echo '<div id="dispatch_date_buttons"></div>';
            $childFormConfig = getFormConfig($elem['name'], $strFunc);
            createIForm(
                $formConfig, $childFormConfig, $elem,
                isset($intKeyValue) ? $intKeyValue : 0, $intKeyValue ? false : true,
                $strForm,
                $strFunc
            );
            break;
        } else {
            $value = $astrValues[$elem['name']];
            if ($elem['style'] == 'measurement') {
                $value = $value ? miscRound2Decim($value, 2) : '';
            }
            if ($elem['type'] == 'AREA') {
                ?>
          <td class="toplabel<?php echo $style?>"><?php echo Translator::translate($elem['label'])?></td>
                <?php
            } else {
                ?>
          <td id="<?php echo htmlentities($elem['name']) . '_label' ?>" class="label<?php echo $style?>"
                <?php
                if (isset($elem['title'])) {
                    echo ' title="' . Translator::translate($elem['title']) . '"';
                }
                echo '>';
                echo Translator::translate($elem['label'])
                ?>
          </td>
                <?php
            }
            $contentsClasses = htmlentities(strtolower($elem['type']))
                . ' ' . $elem['style']
                . (isset($elem['attached_elem']) ? ' attached' : '');
            ?>
          <td class="field<?php echo $fieldClass?>"<?php echo $strColspan ? " $strColspan" : ''?>>
            <div class="field-contents <?php echo $contentsClasses?>">
                <?php

                echo htmlFormElement(
                    $elem['name'], $elem['type'], $value,
                    $elem['style'], $elem['listquery'], $fieldMode,
                    isset($elem['parent_key']) ? $elem['parent_key'] : '', '', [],
                    isset($elem['elem_attributes']) ? $elem['elem_attributes'] : '',
                    isset($elem['options']) ? $elem['options'] : null
                );
                ?>
            </div>
            <?php
            if (isset($elem['attached_elem'])) {
                echo $elem['attached_elem'] . "\n";
            }
            ?>
          </td>
            <?php
        }
        $prevPosition = is_int($elem['position']) ? $elem['position'] : 0;
        if ($prevPosition == 0) {
            $prevPosition = 255;
        }
        $prevColSpan = $intColspan;
    }

    if (!$childFormConfig) {
        if ($rowOpen) {
            echo "        </tr>\n";
        }
        echo "      </table>\n      </form>\n";
    }
    if ($strForm == 'product') {
        // Special case for product: show stock balance change log
        ?>
      <div class="iform ui-corner-tl ui-corner-bl ui-corner-br ui-corner-tr ui-helper-clearfix" id="stock_balance_log">
        <div class="ui-corner-tl ui-corner-tr fg-toolbar ui-toolbar ui-widget-header">
            <?php echo Translator::translate('StockBalanceUpdates')?>
        </div>
        <table id="stock_balance_change_log" class="iform">
        <tr>
            <th class="medium"><?php echo Translator::translate('HeaderChangeLogDateTime')?></th>
            <th class="medium"><?php echo Translator::translate('HeaderChangeLogUser')?></th>
            <th class="small"><?php echo Translator::translate('HeaderChangeLogAmount')?></th>
            <th class="long"><?php echo Translator::translate('HeaderChangeLogDescription')?></th>
        </tr>
        </table>
      </div>
        <?php
    }
    ?>
  </div>

  <script>

$(document).ready(function() {
    <?php
    if ($strMessage) {
        ?>
  MLInvoice.infomsg("<?php echo htmlentities($strMessage)?>");
        <?php
    }
    if ($strErrorMessage) {
        ?>
      MLInvoice.errormsg("<?php echo htmlentities($strErrorMessage)?>");
        <?php
    }
    if ($strForm == 'product') {
        ?>
  MLInvoice.Form.updateStockBalanceLog();
        <?php
    }
    if (sesWriteAccess()) {
        ?>
        <?php
    }

    $mainFormConfig = [
        'type' => $formConfig['type'],
        'id' => $intKeyValue,
        'readOnly' => $formConfig['readOnly']
    ];
    foreach ($formConfig['fields'] as $field) {
        $new = [
            'type' => $field['type'],
            'name' => $field['name'],
            'label' => $field['label'],
            'allow_null' => $field['allow_null']
        ];
        if (isset($field['default'])) {
            $new['default'] = $field['default'];
        }
        $mainFormConfig['fields'][] = $new;
    }

    $subFormConfig = [];
    $listItems = [];
    if ($childFormConfig) {
        $subFormConfig = [
            'type' => $childFormConfig['type'],
            'parentKey' => $childFormConfig['parentKey'],
            'onAfterRowAdded' => $childFormConfig['onAfterRowAdded'],
            'clearAfterRowAdded' => $childFormConfig['clearAfterRowAdded'],
            'dispatchByDateButtons' => getSetting('invoice_show_dispatch_dates'),
            'popupWidth' => 'send_api_config' === $childFormConfig['type'] ? 1200 : 1050,
        ];

        foreach ($childFormConfig['fields'] as $subElem) {
            $new = [
                'type' => $subElem['type'],
                'name' => $subElem['name'],
                'style' => $subElem['style'],
                'label' => $subElem['label'],
                'allow_null' => $subElem['allow_null']
            ];
            if (isset($subElem['default'])) {
                $new['default'] = $subElem['default'];
            }
            $subFormConfig['fields'][] = $new;

            if ($subElem['type'] != 'LIST') {
                continue;
            }
            if (is_array($subElem['listquery'])) {
                $values = $subElem['listquery'];
            } else {
                $res = dbQueryCheck($subElem['listquery']);
                $values = [];
                while ($row = mysqli_fetch_row($res)) {
                    $values[$row[0]] = $row[1];
                }
            }
            $translate = strstr($subElem['style'], ' translated');
            $items = [
                '0' => '-'
            ];
            foreach ($values as $key => $value) {
                if ($translate) {
                    $value = Translator::translate($value);
                }
                $items[$key] = $value;
            }
            $listItems[$subElem['name']] = $items;
        }
    }

    $mainFormConfig['modificationWarning'] = '';
    if ($strForm == 'invoice' && !empty($intKeyValue) && !isInvoiceOpen($intKeyValue)) {
        $mainFormConfig['modificationWarning'] = Translator::translate('NonOpenInvoiceModificationWarning');
    }
    ?>

  MLInvoice.Form.initForm(
    <?php echo json_encode($mainFormConfig)?>,
    <?php echo json_encode($subFormConfig)?>,
    <?php echo json_encode($listItems)?>
  );
    <?php

    if ($childFormConfig && $intKeyValue) {
        ?>
  MLInvoice.Form.initRows(
    function initRowsDone() {
        <?php
        if (isset($newLocation)) {
            echo "window.location='$newLocation';";
        }
        ?>
    }
  );

        <?php
    } elseif (isset($newLocation)) {
        echo "window.location='$newLocation';";
    }
    if (isset($openWindow)) {
        echo "window.open('$openWindow');";
    }
    ?>
});
</script>

    <?php
    createFormButtons($strForm, $intKeyValue ? false : true, $formConfig['copyLink'], false, $formConfig['readOnly'], '', false);
    echo "  </div>\n";

    if ($formConfig['addressAutocomplete'] && getSetting('address_autocomplete')) {
        ?>
  <script>
  $(document).ready(function() {
  var s = document.createElement("script");
    s.type = "text/javascript";
    s.src  = "https://maps.googleapis.com/maps/api/js?sensor=false&libraries=places&callback=gmapsready";
    window.gmapsready = function() {
      MLInvoice.Form.initAddressAutocomplete('');
      MLInvoice.Form.initAddressAutocomplete('quick_');
    };
    $('head').append(s);
  });
  </script>
        <?php
    }
}

/**
 * Create subform
 *
 * @param array  $mainFormConfig Main form config
 * @param array  $formConfig     Child form config
 * @param string $elem           Subform element
 * @param int    $intKeyValue    Record ID
 * @param bool   $newRecord      Whether a new record is being added
 * @param string $strForm        Form name
 * @param string $strFunc        Current function
 *
 * @return void
 */
function createIForm($mainFormConfig, $formConfig, $elem, $intKeyValue, $newRecord, $strForm, $strFunc)
{
    ?>
        <div class="iform list_container <?php echo $elem['style']?>ui-corner-tl ui-corner-bl ui-corner-br ui-corner-tr ui-helper-clearfix"
          id="<?php echo $elem['name']?>" <?php echo $elem['elem_attributes'] ? ' ' . $elem['elem_attributes'] : ''?>>
            <div class="ui-corner-tl ui-corner-tr fg-toolbar ui-toolbar ui-widget-header">
                <?php echo Translator::translate($elem['label'])?>
            </div>
    <?php
    if ($newRecord) {
        ?>
            <div id="inewmessage" class="new_message">
                <?php echo Translator::translate('SaveRecordToAddRows')?>
            </div>
        </div>
        <?php
        return;
    }
    ?>
                <form method="post" name="iform" id="iform">
                    <table class="iform" id="itable">
                        <thead>
                            <tr>
    <?php
    if ($strForm == 'invoice' && sesWriteAccess()) {
        $selectAll = Translator::translate('SelectAll');
        ?>
        <th class="label ui-state-default sort-col"> </th>
        <th class="label ui-state-default select-row"><input type="checkbox" class="cb-select-all" title="<?php echo $selectAll?>" aria-label="<?php echo $selectAll?>"></th>
        <?php
    }

    foreach ($formConfig['fields'] as $subElem) {
        if (true
            && !in_array(
                $subElem['type'],
                [
                    'HID_INT',
                    'CONST_HID_INT',
                    'SECHID_INT',
                    'BUTTON',
                    'NEWLINE'
                ]
            )
        ) {
            ?>
                <th class="label ui-state-default <?php echo strtolower($subElem['style'])?>_label">
                    <?php echo Translator::translate($subElem['label'])?>
                </th>
            <?php
        }
    }
    ?>
                            <th class="label ui-state-default" colspan="2"></th>
                            </tr>
                        </thead>
                        <tbody>
    <?php
    if (sesWriteAccess()) {
        ?>
            <tr id="form_row">
        <?php
        if ($strForm == 'invoice') {
            ?>
            <td></td>
            <td class="select-row"></td>
            <?php
        }

        foreach ($formConfig['fields'] as $subElem) {
            if (true
                && !in_array(
                    $subElem['type'],
                    [
                        'HID_INT',
                        'CONST_HID_INT',
                        'SECHID_INT',
                        'BUTTON',
                        'NEWLINE',
                        'ROWSUM'
                    ]
                )
            ) {
                $value = getFormDefaultValue($subElem, $intKeyValue);
                if (null === $value) {
                    $value = '';
                }
                ?>
              <td class="label <?php echo strtolower($subElem['style'])?>_label">
                <?php
                echo htmlFormElement(
                    'iform_' . $subElem['name'], $subElem['type'], $value,
                    $subElem['style'], $subElem['listquery'], 'MODIFY', 0, '', [],
                    $subElem['elem_attributes']
                );
                ?>
              </td>
                <?php
            } elseif ($subElem['type'] == 'ROWSUM') {
                ?>
              <td class="label <?php echo strtolower($subElem['style'])?>_label">&nbsp;</td>
                <?php
            }
        }
        if ($strForm == 'invoice') {
            ?>
              <td class="button" colspan="2">
                <a class="tinyactionlink ui-button ui-corner-all ui-widget row-add-button" href="#"
                  onclick="MLInvoice.Form.saveRow('iform'); return false;">
                    <?php echo Translator::translate('AddRow')?>
                </a>
              </td>
            <?php
        } else {
            ?>
              <td class="button" colspan="2">
                <a class="tinyactionlink ui-button ui-corner-all ui-widget row-add-button" href="#"
                  onclick="MLInvoice.Form.saveRow('iform'); return false;">
                    <?php echo Translator::translate('AddRow')?>
                </a>
              </td>
            <?php
        }
        ?>
            </tr>
                        </tbody>
                    </table>
                </form>
                </div>
                <div id="popup_edit" style="display: none;">
                    <form method="post" name="iform_popup" id="iform_popup" data-popup="1">
                        <table class="iform">
                            <tr>
        <?php
        foreach ($formConfig['fields'] as $elem) {
            if (true
                && !in_array(
                    $elem['type'],
                    [
                        'HID_INT',
                        'CONST_HID_INT',
                        'SECHID_INT',
                        'BUTTON',
                        'NEWLINE',
                        'ROWSUM'
                    ]
                )
            ) {
                ?>
            <td class="label <?php echo strtolower($elem['style'])?>_label">
                <?php echo Translator::translate($elem['label'])?><br>
                <?php echo htmlFormElement('iform_popup_' . $elem['name'], $elem['type'], '', $elem['style'], $elem['listquery'], 'MODIFY', 0, '', [], $elem['elem_attributes'])?>
                <br/>
                <span class="modification-indicator ui-state-highlight hidden"><?php echo Translator::translate('Modified')?></span>&nbsp;
            </td>
                <?php
            } elseif ($elem['type'] == 'SECHID_INT') {
                ?>
            <input type="hidden" name="<?php echo 'iform_popup_' . $elem['name']?>" value="<?php echo gpcStripSlashes($astrValues[$elem['name']])?>">
                <?php
            } elseif ($elem['type'] == 'BUTTON') {
                ?>
            <td class="label">&nbsp;</td>
                <?php
            }
        }
    }
    ?>
          </tr>
                        </table>
                    </form>
                </div>
                <div id="popup_date_edit" style="display: none; width: 300px; overflow: hidden">
                    <form method="post" name="form_date_popup" id="form_date_popup">
                        <input id="popup_date_edit_field" type="text" class="medium hasCalendar">
                    </form>
                </div>
    <?php
}

/**
 * Create form buttons
 *
 * @param string $form             Form name
 * @param bool   $new              Whether a new record is being added
 * @param string $copyLinkOverride Override command for copy record link
 * @param bool   $spinner          Whether to add a spinner
 * @param bool   $readOnlyForm     Whether the form is read-only
 * @param string $extraButtons     Any extra buttons
 * @param bool   $top              Whether adding top buttons
 *
 * @return void
 */
function createFormButtons($form, $new, $copyLinkOverride, $spinner, $readOnlyForm,
    $extraButtons, $top
) {
    if (!sesWriteAccess()) {
        ?>
    <div class="form_buttons"></div>
        <?php
        return;
    }
    ?>
    <div class="form_buttons">
    <?php
    if (!$readOnlyForm) {
        ?>
      <a class="actionlink ui-button ui-corner-all ui-widget save_button" href="#">
        <?php echo Translator::translate('Save')?>
      </a>
        <?php
    }

    if (!$new) {
        if ($copyLinkOverride) {
            ?>
            <a class="actionlink ui-button ui-corner-all ui-widget" href="<?php echo $copyLinkOverride?>">
                <?php echo Translator::translate('Copy')?>
            </a>
            <?php
        } else {
            ?>
            <a class="actionlink ui-button ui-corner-all ui-widget form-submit" href="#" data-form="admin_form" data-set-field="action=copy">
                <?php echo Translator::translate('Copy')?>
            </a>
            <?php
        }
        $newLink = 'index.php?' . $_SERVER['QUERY_STRING'];
        $newLink = preg_replace('/&id=\w*/', '', $newLink);
        ?>
        <a class="actionlink ui-button ui-corner-all ui-widget" href="<?php echo $newLink?>">
            <?php echo Translator::translate('New')?>
        </a>
        <?php
        if (!$readOnlyForm) {
            ?>
            <a class="actionlink ui-button ui-corner-all ui-widget form-submit" href="#" data-form="admin_form" data-set-field="action=delete"
              data-confirm="ConfirmDelete">
                <?php echo Translator::translate('Delete')?>
            </a>
            <?php
            if ($extraButtons) {
                echo $extraButtons;
            }
        }
    }
    if ($form === 'company') {
        if (!$readOnlyForm) {
            ?>
            <a class="actionlink ui-button ui-corner-all ui-widget ytj_search_button" href="#"><?php echo Translator::translate('SearchYTJ')?></a>
            <?php
        }
        if ($top && !$new) {
            ?>
            <a id="cover-letter-button" class="actionlink ui-button ui-corner-all ui-widget" href="#">
                <?php echo Translator::translate('PrintCoverLetter')?>
            </a>
            <div id="cover-letter-form" class="ui-corner-all hidden">
            <div class="ui-corner-tl ui-corner-tr fg-toolbar ui-toolbar ui-widget-header">
                <?php echo Translator::translate('PrintCoverLetter')?>
            </div>
            <div id="cover-letter-form-inner">
                <form action="coverletter.php" method="POST">
                <input type="hidden" name="company" value="<?php echo getPostOrQuery('id')?>">
                <div class="medium_label"><?php echo Translator::translate('Sender')?></div>
                <div class="field">
                    <?php echo htmlFormElement(
                        'base', 'LIST', '', 'long noemptyvalue',
                        'SELECT id, name FROM {prefix}base WHERE deleted=0 AND inactive=0 ORDER BY name, id'
                    );?>
                </div>
                <div class="medium_label"><?php echo Translator::translate('Foreword')?></div>
                <div class="field">
                    <?php echo htmlFormElement('foreword', 'AREA', '', 'large', '');?>
                    <span class="select-default-text" data-type="foreword" data-target="foreword"></span>
                </div>
                <div class="form_buttons">
                    <input type="submit" class="ui-button ui-corner-all" value="<?php echo Translator::translate('Print')?>">
                    <input type="button" class="ui-button ui-corner-all close-btn" value="<?php echo Translator::translate('Close')?>">
                </div>
                </form>
            </div>
        </div>
            <?php
        }
    }

    $id = getPostOrQuery('id', '');

    if ($form === 'invoice' && $top && !$new) {
        $attachmentCount = GetInvoiceAttachmentCount($id);
        ?>
        <span class="send-buttons">
        </span>
        <a id="attachments-button" class="actionlink ui-button ui-corner-all ui-widget" href="#">
            <?php echo Translator::translate('Attachments')?>
            (<span class="attachment-count"><?php echo $attachmentCount?></span>)
            <span class="dropdown-open"><i class="fa fa-caret-down"></i><span class="sr-only"><?php echo Translator::translate('Show')?></span></span>
            <span class="dropdown-close hidden"><i class="fa fa-caret-up"></i><span class="sr-only"><?php echo Translator::translate('Hide')?></span></span>
        </a>
        <?php
    }

    if ($id && ($listId = getPostOrQuery('listid', ''))) {
        createListNavigationLinks($listId, $id);
    }

    if ($spinner) {
        echo '     <span id="spinner" class="hidden"><img src="images/spinner.gif" alt=""></span>' .
            "\n";
    }

    if ($form === 'invoice' && $top && !$new) {
        ?>
        <div id="attachments-form" class="ui-widget-content ui-corner-all hidden" data-invoice-id="<?php echo $id?>">
            <div class="ui-corner-tl ui-corner-tr fg-toolbar ui-toolbar ui-widget-header">
                <?php echo Translator::translate('Attachments')?>
            </div>
            <div id="attachments-form-inner">
                <h1>
                    <?php echo Translator::translate('AddedAttachments')?>
                </h1>
                <div class="attachment-list"></div>
                <h1><?php echo Translator::translate('AddAttachment')?></h1>
                <div class="attachment-add">
                    <div class="attachments">
                        <?php foreach (getAttachments() as $attachment) { ?>
                            <div class="attachment">
                                <a class="tinyactionlink ui-button ui-corner-all ui-widget add-attachment" data-id="<?php echo $attachment['id']?>"
                                    title="<?php echo Translator::translate('AddAttachment')?>"> + </a>
                                <div class="attachment-fileinfo">
                                <?php
                                    echo $attachment['name'] . ' ('
                                        . $attachment['filename'] . ', '
                                        . fileSizeToHumanReadable($attachment['filesize'])
                                        . ')';
                                ?>
                                </div>
                            </div>
                        <?php } ?>
                    </div>
                    <div class="attachment-new">
                        <div class="medium_label"><?php echo Translator::translate('NewAttachment')?></div>
                        <div class="field">
                            <?php echo htmlFormElement('new-attachment-file', 'FILE', '', 'long');?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    ?>
    </div>
    <?php
}

/**
 * Create navigation buttons for next/previous record
 *
 * @param string $listId    List ID
 * @param int    $currentId Current record ID
 *
 * @return void
 */
function createListNavigationLinks($listId, $currentId)
{
    $listInfo = Memory::get("{$listId}_info");
    if (null === $listInfo) {
        // No list info for the current list
        return;
    }
    $pos = array_search($currentId, $listInfo['ids']);
    if (false === $pos) {
        // We've lost track of our position
        return;
    }
    $previous = null;
    $next = null;
    if (0 === $pos) {
        // If we're not at the beginning, fetch previous page and try again
        if (0 != $listInfo['startRow']) {
            $startRow = max([$listInfo['startRow'] - $listInfo['rowCount'], 0]);
            augmentListInfo($listId, $listInfo, $startRow, $listInfo['rowCount']);
            createListNavigationLinks($listId, $currentId);
            return;
        }
    } else {
        $previous = $listInfo['ids'][$pos - 1];
    }
    if ($pos === count($listInfo['ids']) - 1) {
        // If we're not at the end, fetch next page and try again
        if ($listInfo['startRow'] + $pos < $listInfo['recordCount'] - 1) {
            $startRow = min(
                [$listInfo['startRow'] + $listInfo['rowCount'],
                $listInfo['recordCount'] - 1]
            );
            augmentListInfo($listId, $listInfo, $startRow, $listInfo['rowCount']);
            createListNavigationLinks($listId, $currentId);
            return;
        }
    } else {
        $next = $listInfo['ids'][$pos + 1];
    }
    $qs = $_SERVER['QUERY_STRING'];
    echo '<span class="prev-next">';
    if (null !== $previous) {
        $link = preg_replace('/&id=\d+/', "&id=$previous", $qs);
        echo '<a href="?' . $link . '" class="actionlink ui-button ui-corner-all ui-widget">'
            . Translator::translate('Previous')
            . '</a> ';
    } else {
        echo '<a class="actionlink ui-button ui-corner-all ui-widget ui-button ui-corner-all ui-state-disabled">'
            . Translator::translate('Previous')
            . '</a> ';
    }
    if (null !== $next) {
        $link = preg_replace('/&id=\d+/', "&id=$next", $qs);
        echo '<a href="?' . $link . '" class="actionlink ui-button ui-corner-all ui-widget ui-button">'
            . Translator::translate('Next')
            . '</a> ';
    } else {
        echo '<a class="actionlink ui-button ui-corner-all ui-widget ui-button ui-corner-all ui-state-disabled">'
            . Translator::translate('Next')
            . '</a> ';
    }
    echo '</span>';
}

/**
 * Add data to list info memory (record to record navigation)
 *
 * @param string $listId   List name
 * @param array  $listInfo List info
 * @param int    $startRow Start row
 * @param int    $rowCount Row count
 *
 * @return void
 */
function augmentListInfo($listId, $listInfo, $startRow, $rowCount)
{
    $params = $listInfo['queryParams'];
    $join = $params['join'];
    $where = 'WHERE ' . (isset($params['filteredTerms']) ? $params['filteredTerms']
        : $params['terms']);
    $groupBy = !empty($params['group']) ? " GROUP BY {$params['group']}" : '';
    $primaryKey = $params['primaryKey'];

    $fullQuery = "SELECT $primaryKey FROM {$params['table']} $join"
        . " $where$groupBy";

    if ($params['order']) {
        $fullQuery .= ' ORDER BY ' . $params['order'];
    }

    if ($startRow >= 0 && $rowCount >= 0) {
        $fullQuery .= " LIMIT $startRow, $rowCount";
    }

    $ids = [];
    $rows = dbParamQuery($fullQuery, $params['params'], false, true);
    foreach ($rows as $row) {
        $ids[] = $row[$primaryKey];
    }

    if ($listInfo['startRow'] > $startRow) {
        $listInfo['startRow'] = $startRow;
        $listInfo['ids'] = array_merge($ids, $listInfo['ids']);
    } else {
        $listInfo['ids'] = array_merge($listInfo['ids'], $ids);
    }
    Memory::set("{$listId}_info", $listInfo);
}