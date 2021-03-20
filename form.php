<?php
/**
 * Form display
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
<div class="form_container">
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
<div class="form_container">
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
<div class="form_container">
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

<div id="popup_dlg" class="modal" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">-</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php echo Translator::translate('Close')?>"></button>
            </div>
            <div class="modal-body"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo Translator::translate('Close')?></button>
            </div>
        </div>
    </div>
</div>
    <?php
    if ($formConfig['popupHTML']) {
        echo $formConfig['popupHTML'];
    }
    ?>

<div class="form_container">

    <?php
    createFormButtons(
        $strForm, $formConfig, $intKeyValue ? false : true, true, true
    );

    if ($strForm == 'invoice' && !empty($astrValues['next_interval_date'])
        && strDate2UnixTime($astrValues['next_interval_date']) <= time()
    ) {
        ?>
    <div class="alert alert-warning message" role="alert">
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
    $childFormConfig = false;
    $prevPosition = false;
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

        if ($elem['type'] == 'HEADING') {
            ?>
            <div class="sublabel<?php echo $style?> mt-4"<?php echo empty($elem['name']) ? '' : (' id="' . $elem['name'] . '"')?>>
              <h2><?php echo Translator::translate($elem['label'])?></h2>
            </div>
            <?php
            continue;
        } elseif ($elem['type'] == 'LABEL') {
            ?>
            <div class="sublabel<?php echo $style?>"<?php echo empty($elem['name']) ? '' : (' id="' . $elem['name'] . '"')?>>
              <?php echo Translator::translate($elem['label'])?>
            </div>
            <?php
            continue;
        }

        if ($elem['position'] == 0 || $elem['position'] <= $prevPosition) {
            echo '        <div class="clear-row"></div>';
            $prevPosition = 0;
        }

        $contentsClasses = htmlentities(strtolower($elem['type']))
            . ' ' . $elem['style']
            . (isset($elem['attached_elem']) ? ' attached' : '');

        if (!$intKeyValue
            && in_array($elem['type'], ['BUTTON', 'JSBUTTON', 'IMAGE', 'DROPDOWNMENU'])
        ) {
            echo '          <div class="label$style">&nbsp;</div>';
        } elseif (in_array($elem['type'], ['BUTTON', 'JSBUTTON', 'DROPDOWNMENU'])) {
            ?>
          <div class="form-field button<?php echo $fieldClass?>">
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
          </div>
            <?php
        } elseif ($elem['type'] == 'HID_INT' || strstr($elem['type'], 'HID_')) {
            // Already done above
        } elseif ($elem['type'] == 'IMAGE') {
            ?>
          <div class="field image<?php echo $fieldClass?>">
            <div class="field-contents <?php echo $contentsClasses?>">
            <?php echo htmlFormElement(
                $elem['name'], $elem['type'], $astrValues[$elem['name']],
                $elem['style'], $elem['listquery'], $fieldMode, $elem['parent_key'],
                $elem['label'], [],
                isset($elem['elem_attributes']) ? $elem['elem_attributes'] : '',
                isset($elem['options']) ? $elem['options'] : null
            ); ?>
            </div>
          </div>
            <?php
        } elseif ($elem['type'] == 'IFORM') {
            echo "      </form>\n";
            $childFormConfig = getFormConfig($elem['name'], $strFunc);
            createIForm(
                $formConfig, $childFormConfig, $elem,
                isset($intKeyValue) ? $intKeyValue : 0, $intKeyValue ? false : true,
                $strForm,
                $strFunc
            );
            break;
        } else {
            ?>
        <div class="field<?php echo $fieldClass?>">
            <?php
            $value = $astrValues[$elem['name']];
            if ($elem['style'] == 'measurement') {
                $value = $value ? miscRound2Decim($value, 2) : '';
            }
            if ($elem['type'] == 'AREA') {
                ?>
          <label for="<?php echo htmlentities($elem['name'])?>" class="toplabel<?php echo $style?>"><?php echo Translator::translate($elem['label'])?></label>
                <?php
            } else {
                ?>
          <label id="<?php echo htmlentities($elem['name'])?>_label" for="<?php echo htmlentities($elem['name'])?>" class="label<?php echo $style?>"
                <?php
                if (isset($elem['title'])) {
                    echo ' title="' . Translator::translate($elem['title']) . '"';
                }
                echo '>';
                echo Translator::translate($elem['label'])
                ?>
          </label>
                <?php
            }
            ?>
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
                <?php
                if (isset($elem['attached_elem']) && $elem['type'] !== 'AREA') {
                    echo $elem['attached_elem'] . "\n";
                }
                ?>
            </div>
            <?php
            if (isset($elem['attached_elem']) && $elem['type'] === 'AREA') {
                echo $elem['attached_elem'] . "\n";
            }
            ?>
        </div>
            <?php
        }
        $prevPosition = is_int($elem['position']) ? $elem['position'] : 0;
        if ($prevPosition == 0) {
            $prevPosition = 255;
        }
    }
    if (!$childFormConfig) {
        echo "      </form>\n";
    }
    if ($strForm == 'product') {
        // Special case for product: show stock balance change log
        ?>
      <div class="card p-2 mb-2 iform clearfix" id="stock_balance_log">
        <h2>
            <?php echo Translator::translate('StockBalanceUpdates')?>
        </h2>
        <table id="stock_balance_change_log" class="table">
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
  MLInvoice.infomsg(<?php echo json_encode($strMessage)?>);
        <?php
    }
    if ($strErrorMessage) {
        ?>
      MLInvoice.errormsg(<?php echo json_encode($strErrorMessage)?>);
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
    createFormButtons($strForm, $formConfig, $intKeyValue ? false : true, false, false);
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

    if ('invoice' === $strForm) {
        ?>
        <div id="quick_add_company" class="modal" aria-hidden="true">
            <div class="modal-dialog modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2 class="modal-title"><?php echo Translator::translate('NewClient')?></h2>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php echo Translator::translate('Close')?>"></button>
                    </div>
                    <div class="modal-body">
                        <div class="medium_label"><?php echo Translator::translate('ClientName')?></div>
                        <div class="field"><input type="text" id="quick_name" class='form-control medium'></div>
                        <div class="medium_label"><?php echo Translator::translate('ClientVATID')?></div>
                        <div class="field"><input type="text" id="quick_vat_id" class='form-control medium'></div>
                        <div class="medium_label"><?php echo Translator::translate('Email')?></div>
                        <div class="field"><input type="text" id="quick_email" class='form-control medium'></div>
                        <div class="medium_label"><?php echo Translator::translate('Phone')?></div>
                        <div class="field"><input type="text" id="quick_phone" class='form-control medium'></div>
                        <div class="medium_label"><?php echo Translator::translate('StreetAddr')?></div>
                        <div class="field"><input type="text" id="quick_street_address" class='form-control medium'></div>
                        <div class="medium_label"><?php echo Translator::translate('ZipCode')?></div>
                        <div class="field"><input type="text" id="quick_zip_code" class='form-control medium'></div>
                        <div class="medium_label"><?php echo Translator::translate('City')?></div>
                        <div class="field"><input type="text" id="quick_city" class='form-control medium'></div>
                        <div class="medium_label"><?php echo Translator::translate('Country')?></div>
                        <div class="field"><input type="text" id="quick_country" class='form-control medium'></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo Translator::translate('Cancel')?></button>
                        <button type="button" class="btn btn-primary" data-save-company><?php echo Translator::translate('Save')?></button>
                    </div>
                </div>
            </div>
        </div>

        <div id="add_partial_payment" class="modal" aria-hidden="true">
            <div class="modal-dialog modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2 class="modal-title"><?php echo Translator::translate('PartialPayment')?></h2>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php echo Translator::translate('Close')?>"></button>
                    </div>
                    <div class="modal-body">
                        <div class="medium_label"><?php echo Translator::translate('PaymentAmount')?></div>
                        <div class="field"><input type="text" id="add_partial_payment_amount" class='form-control medium'></div>
                        <div class="medium_label"><?php echo Translator::translate('PayDate')?></div>
                        <div class="field"><input type="text" id="add_partial_payment_date" class='form-control date hasCalendar'></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo Translator::translate('Cancel')?></button>
                        <button type="button" class="btn btn-primary" data-save-partial-payment><?php echo Translator::translate('Save')?></button>
                    </div>
                </div>
            </div>
        </div>
        <?php
    } elseif ('product' === $strForm) {
        ?>
        <div id="update_stock_balance" class="modal" aria-hidden="true">
            <div class="modal-dialog modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2 class="modal-title"><?php echo Translator::translate('PartialPayment')?></h2>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php echo Translator::translate('Close')?>"></button>
                    </div>
                    <div class="modal-body">
                        <div class="medium_label"><?php echo Translator::translate('StockBalanceChange')?></div> <div class="field">
                        <input type="text" id="stock_balance_change" class='form-control short'></div>
                        <div class="medium_label"><?php echo Translator::translate('StockBalanceChangeDescription')?></div>
                        <div class="field"><textarea id="stock_balance_change_desc" class="form-control large"></textarea></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo Translator::translate('Cancel')?></button>
                        <button type="button" class="btn btn-primary" data-save-stock-balance-change><?php echo Translator::translate('Save')?></button>
                    </div>
                </div>
            </div>
        </div>
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
        <div class="mb-2 p-2 border list_container <?php echo $elem['style']?> clearfix"
          id="<?php echo $elem['name']?>" <?php echo $elem['elem_attributes'] ? ' ' . $elem['elem_attributes'] : ''?>>
            <h2><?php echo Translator::translate($elem['label'])?></h2>
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
                    <table class="iform<?php echo 'invoice' === $strForm ? ' iform-sort-select' : ''?>" id="itable">
                        <thead>
                            <tr>
    <?php
    if ($strForm == 'invoice' && sesWriteAccess()) {
        $selectAll = Translator::translate('SelectAll');
        ?>
        <th class="label sort-col"> </th>
        <th class="label select-row"><input type="checkbox" class="cb-select-all" title="<?php echo $selectAll?>" aria-label="<?php echo $selectAll?>"></th>
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
                <th class="label <?php echo strtolower($subElem['style'])?>">
                    <?php echo Translator::translate($subElem['label'])?>
                </th>
            <?php
        }
    }
    ?>
                              <th class="label"></th>
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
              <td class="label <?php echo strtolower($subElem['style'])?>">
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
              <td class="label <?php echo strtolower($subElem['style'])?>">&nbsp;</td>
                <?php
            }
        }
        ?>
                                <td class="button">
                                    <a role="button" class="btn btn-outline-primary btn-sm row-add-button" href="#"
                                        data-iform-save-row="iform"
                                        title="<?php echo Translator::translate('AddRow')?>">
                                        <i class="fa fa-plus"></i>
                                    </a>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </form>
        </div>

<div id="popup_edit" class="modal" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">-</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php echo Translator::translate('Close')?>"></button>
            </div>
            <div class="modal-body">
                <form method="post" name="iform_popup" id="iform_popup" data-popup="1">
                    <table class="iform-popup">
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
            <td class="label <?php echo strtolower($elem['style'])?>">
                <?php echo Translator::translate($elem['label'])?>
                <div class="field-container">
                  <?php echo htmlFormElement('iform_popup_' . $elem['name'], $elem['type'], '', $elem['style'], $elem['listquery'], 'MODIFY', 0, '', [], $elem['elem_attributes'])?>
                </div>
                <div class="modification-indicator hidden">
                    <span class="modification-text"><?php echo Translator::translate('Modified')?></span>
                    <button type="button" class="btn btn-small btn-outline-secondary clear" aria-label="<?php echo Translator::translate('Clear')?>">
                        <i class="fa fa-undo" aria-hidden="true"></i>
                    </button>
                </div>
            </td>
                <?php
            } elseif ($elem['type'] == 'SECHID_INT') {
                ?>
            <input type="hidden" name="<?php echo 'iform_popup_' . $elem['name']?>" value="<?php echo htmlspecialchars($astrValues[$elem['name']])?>">
                <?php
            } elseif ($elem['type'] == 'BUTTON') {
                ?>
            <td class="label button">&nbsp;</td>
                <?php
            }
        }
    }
    ?>
          </tr>
                        </table>
                    </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo Translator::translate('Cancel')?></button>
                <span class="edit-single-buttons">
                    <button type="button" class="btn btn-secondary" data-iform-delete-row="iform_popup"><?php echo Translator::translate('Delete')?></button>
                    <button type="button" class="btn btn-secondary" data-iform-copy-row="iform_popup"><?php echo Translator::translate('SaveAsCopy')?></button>
                    <button type="button" class="btn btn-primary" data-iform-save-row="iform_popup"><?php echo Translator::translate('Save')?></button>
                </span>
                <span class="edit-multi-buttons">
                    <button type="button" class="btn btn-primary" data-iform-save-rows="iform_popup"><?php echo Translator::translate('Save')?></button>
                </span>
            </div>
        </div>
    </div>
</div>

            <div id="popup_date_edit" style="display: none; width: 300px; overflow: hidden">
                <form method="post" name="form_date_popup" id="form_date_popup">
                    <input id="popup_date_edit_field" type="text" class="form-control medium hasCalendar">
                </form>
            </div>
    <?php
}

/**
 * Create form buttons
 *
 * @param string $form       Form name
 * @param array  $formConfig Form configuration
 * @param bool   $new        Whether a new record is being added
 * @param bool   $top        Whether adding top buttons
 *
 * @return void
 */
function createFormButtons($form, $formConfig, $new, $top)
{
    $id = getPostOrQuery('id', '');
    $listId = getPostOrQuery('listid', '');

    $copyLinkOverride = $formConfig['copyLink'];
    $readOnlyForm = $formConfig['readOnly'];
    $extraButtons = $formConfig['extraButtons'];
    if (!sesWriteAccess()) {
        ?>
    <div class="btn-set" role="group"></div>
        <?php
        return;
    }
    ?>
    <div class="btn-set" role="group">
    <?php
    if (!$readOnlyForm) {
        ?>
      <a role="button" class="btn btn-outline-primary save_button" href="#">
        <?php echo Translator::translate('Save')?>
      </a>
        <?php
    }

    if (!$new) {
        if ($copyLinkOverride) {
            ?>
            <a role="button" class="btn btn-secondary" href="<?php echo $copyLinkOverride?>">
                <?php echo Translator::translate('Copy')?>
            </a>
            <?php
        } else {
            ?>
            <a role="button" class="btn btn-secondary form-submit" href="#" data-form="admin_form" data-set-field="action=copy">
                <?php echo Translator::translate('Copy')?>
            </a>
            <?php
        }
        $newLink = 'index.php?' . $_SERVER['QUERY_STRING'];
        $newLink = preg_replace('/&id=\w*/', '', $newLink);
        $newLink = preg_replace('/&offer=\w*/', '', $newLink);
        $newLink = htmlspecialchars($newLink);
        if ('invoice' === $form) {
            $idSuffix = $top ? '' : '-bottom';
            ?>
            <a role="button" class="dropdown-toggle btn btn-secondary" href="#" data-bs-toggle="dropdown" id="dropdown-button-new<?php echo $idSuffix?>" aria-expanded="false">
                <?php echo Translator::translate('New')?>
            </a>
            <ul class="dropdown-menu" aria-labelledby="dropdown-button-new<?php echo $idSuffix?>">
                <li class="dropdown-item">
                    <a role="button" class="dropdown-item" href="<?php echo $newLink?>">
                        <?php echo Translator::translate('NewInvoice')?>
                    </a>
                </li>
                <li class="dropdown-item">
                    <a role="button" class="dropdown-item" href="<?php echo $newLink?>&amp;offer=1">
                        <?php echo Translator::translate('NewOffer')?>
                    </a>
                </li>
            </ul>
            <?php
        } else {
            ?>
            <a role="button" class="btn btn-secondary" href="<?php echo $newLink?>">
                <?php echo Translator::translate('New')?>
            </a>
            <?php
        }
        if (!$readOnlyForm) {
            ?>
            <a role="button" class="btn btn-secondary" href="#" data-form="admin_form" data-set-field="action=delete"
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
            <a role="button" class="btn btn-secondary ytj_search_button" href="#"><?php echo Translator::translate('SearchYTJ')?></a>
            <?php
        }
    }
    ?>
    </div>
    <?php
    if ($form === 'company' && $top && !$new) {
        ?>
        <div class="btn-set">
            <a role="button" id="cover-letter-button" class="btn btn-secondary" href="#">
                <?php echo Translator::translate('PrintCoverLetter')?>
            </a>
            <a role="button" class="btn btn-secondary" href="?func=invoices&amp;form=invoice&amp;company_id=<?php echo $id?>">
                <?php echo Translator::translate('AddInvoice')?>
            </a>
            <a role="button" class="btn btn-secondary" href="?func=invoices&amp;form=invoice&amp;company_id=<?php echo $id?>&amp;offer=1">
                <?php echo Translator::translate('AddOffer')?>
            </a>
        </div>
        <?php
    }

    if ($id && $listId) {
        createListNavigationLinks($listId, $id);
    }

    if ($top && !$new && 'company' === $form) {
        ?>
        <div id="cover-letter-form" class="card hidden">
            <div class="card-body">
                <div class="card-title">
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
                    <div class="btn-set" role="group">
                        <input type="submit" class="btn btn-primary" value="<?php echo Translator::translate('Print')?>">
                        <input type="button" class="btn btn-secondary close-btn" value="<?php echo Translator::translate('Close')?>">
                    </div>
                    </form>
                </div>
            </div>
        </div>
        <?php
    }

    if ($form === 'invoice' && $top && !$new) {
        $attachmentCount = GetInvoiceAttachmentCount($id);
        ?>
        <div class="btn-set">
            <button type="button" id="attachments-button" class="btn btn-secondary" aria-expanded="false">
                <?php echo Translator::translate('Attachments')?>
                (<span class="attachment-count"><?php echo $attachmentCount?></span>)
                <span class="dropdown-open"><i class="fa fa-caret-down"></i><span class="sr-only"><?php echo Translator::translate('Show')?></span></span>
                <span class="dropdown-close hidden"><i class="fa fa-caret-up"></i><span class="sr-only"><?php echo Translator::translate('Hide')?></span></span>
            </button>
        </div>
        <div class="btn-set send-buttons hidden"></div>
        <?php
    }

    if ($form === 'invoice' && $top && !$new) {
        ?>
        <div id="attachments-form" class="card p-2 hidden" data-invoice-id="<?php echo $id?>">
            <h2 class="card-title"><?php echo Translator::translate('Attachments')?></h2>
            <div class="card-body attachments-form-inner">
                <h3>
                    <?php echo Translator::translate('AddedAttachments')?>
                </h3>
                <div class="attachment-list"></div>
                <h3><?php echo Translator::translate('AddAttachment')?></h3>
                <div class="attachment-add">
                    <div class="attachments">
                        <?php foreach (getAttachments() as $attachment) { ?>
                            <div class="attachment">
                                <a role="button" class="btn btn-primary btn-sm add-attachment" data-id="<?php echo $attachment['id']?>"
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
                        <?php $maxFileSize = fileSizeToHumanReadable(getMaxUploadSize()); ?>
                        <div class="unlimited_label"><?php echo Translator::translate('NewAttachmentWithSize', ['%%maxsize%%' => $maxFileSize])?></div>
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
    <?php
    if ($top) {
        foreach ($formConfig['buttonGroups'] as $buttonGroup) {
            ?>
            <div class="btn-set" role="group">
            <?php
            $rendered = [];
            foreach ($buttonGroup['buttons'] as $button) {
                $attrs = '';
                foreach ($button['attrs'] ?? [] as $key => $value) {
                    $attrs .= " $key=\"" . htmlentities($value) . '"';
                }
                $rendered[] = '<a role="button" id="' . $button['name'] . '-button" class="btn btn-secondary" href="' . htmlentities($button['url'])
                    . '"' . $attrs . '>' . Translator::translate($button['label']) . '</a>';
            }
            $overflow = $buttonGroup['overflow'] ?? false;
            if ($overflow) {
                $renderedOverflow = array_splice($rendered, $overflow - 1);
            }
            echo implode(' ', $rendered);
            if ($renderedOverflow ?? false) {
                ?>
                    <a role="button" class="dropdown-toggle btn btn-secondary" href="#" id="dropdown-button-overflow" data-bs-toggle="dropdown" aria-expanded="false">
                        <?php echo Translator::translate($buttonGroup['overflow-label'] ?? 'More')?>
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="dropdown-button-overflow">
                        <?php
                        foreach ($renderedOverflow as $button) {
                            ?>
                            <li class="dropdown-item">
                                <?php echo str_replace('btn btn-secondary', 'dropdown-item', $button)?>
                            </li>
                            <?php
                        }
                        ?>
                    </ul>
                <?php
            }
            ?>
            </div>
            <?php
        }
    }

    if ($form === 'invoice' && $top && !$new) {
        echo '<div id="dispatch_date_buttons" class="btn-set"></div>';
    }

    if ($top) {
        echo '<span id="spinner" class="hidden" aria-hidden="true"><span class="spinner-border spinner-border-sm" role="status"></span></span>';
    }

    ?>

    <div class="clear-row"></div>
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
    echo '<div class="btn-set">';
    if (null !== $previous) {
        $link = preg_replace('/&id=\d+/', "&id=$previous", $qs);
        echo '<a role="button" href="?' . $link . '" class="btn btn-outline-secondary">'
            . Translator::translate('Previous')
            . '</a> ';
    } else {
        echo '<a role="button" class="btn btn-outline-secondary disabled" aria-disabled="true">'
            . Translator::translate('Previous')
            . '</a> ';
    }
    if (null !== $next) {
        $link = preg_replace('/&id=\d+/', "&id=$next", $qs);
        echo '<a role="button" href="?' . $link . '" class="btn btn-outline-secondary">'
            . Translator::translate('Next')
            . '</a> ';
    } else {
        echo '<a role="button" class="btn btn-light disabled" aria-disabled="true">'
            . Translator::translate('Next')
            . '</a> ';
    }
    echo '</div>';
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
    if (isset($params['filteredTerms'])) {
        $queryTerms = $params['filteredTerms'];
        $queryParams = $params['filteredParams'];
    } else {
        $queryTerms = $params['terms'];
        $queryParams = $params['params'];
    }
    $groupBy = !empty($params['group']) ? " GROUP BY {$params['group']}" : '';
    $primaryKey = $params['primaryKey'];

    $fullQuery = "SELECT $primaryKey FROM {$params['table']} $join"
        . " WHERE $queryTerms$groupBy";

    if ($params['order']) {
        $fullQuery .= ' ORDER BY ' . $params['order'];
    }

    if ($startRow >= 0 && $rowCount >= 0) {
        $fullQuery .= " LIMIT $startRow, $rowCount";
    }

    $ids = [];
    $rows = dbParamQuery($fullQuery, $queryParams, false, true);
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