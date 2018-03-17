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
    include 'form_switch.php';

    if (!sesAccessLevel($levelsAllowed) && !sesAdminAccess()) {
        ?>
<div class="form_container ui-widget-content">
    <?php echo Translator::translate('NoAccess') . "\n"?>
  </div>
<?php
        return;
    }

    $blnNew = getPostRequest('newact', false);
    $blnCopy = getPostRequest('copyact', false) ? true : false;
    $blnDelete = getPostRequest('deleteact', false) ? true : false;
    $intKeyValue = getPostRequest('id', false);
    if (!$intKeyValue) {
        $blnNew = true;
    }

    if (!sesWriteAccess() && ($blnNew || $blnCopy || $blnDelete)) {
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

    // if NEW is clicked clear existing form data
    if ($blnNew) {
        unset($intKeyValue);
        unset($astrValues);
        unset($_POST);
        unset($_REQUEST);
        $readOnlyForm = false;
    }

    $astrValues = getPostValues(
        $astrFormElements, isset($intKeyValue) ? $intKeyValue : false
    );

    $redirect = getRequest('redirect', null);
    if (isset($redirect)) {
        // Redirect after save
        foreach ($astrFormElements as $elem) {
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

    if ($blnDelete && $intKeyValue && !$readOnlyForm) {
        deleteRecord($strTable, $intKeyValue);
        unset($intKeyValue);
        unset($astrValues);
        $blnNew = true;
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
        $res = fetchRecord($strTable, $intKeyValue, $astrFormElements, $astrValues);
        if ($res === 'deleted') {
            $strMessage .= Translator::translate('DeletedRecord') . '<br>';
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

    if ($blnCopy) {
        unset($intKeyValue);
        unset($_POST);
        $blnNew = true;
        $readOnlyForm = false;
    }
?>

<div id="popup_dlg"
    style="display: none; width: 900px; overflow: hidden">
    <iframe id="popup_dlg_iframe" src="about:blank" style="width: 100%; height: 100%; overflow: hidden; border: 0"></iframe>
</div>
    <?php
    if (isset($popupHTML)) {
        echo $popupHTML;
    }
    ?>

<div class="form_container">

    <?php
    createFormButtons(
        $strForm, $blnNew, $copyLinkOverride, true, $readOnlyForm, $extraButtons,
        true
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
    if (!sesWriteAccess() || $readOnlyForm) {
        $formDataAttrs[] = 'read-only';
    }
    $dataAttrs = empty($formDataAttrs) ? '' : ' '
        . implode(
            ' ',
            array_map(
                function ($s) {
                    return "data-$s";
                },
                $formDataAttrs
            )
        );
?>
    <div class="form">
        <form method="post" name="admin_form" id="admin_form"<?php echo $dataAttrs?>>
            <input type="hidden" name="copyact" value="0"> <input type="hidden"
                name="newact" value="<?php echo $blnNew ? 1 : 0?>"> <input
                type="hidden" name="deleteact" value="0"> <input type="hidden"
                name="redirect" id="redirect" value=""> <input type="hidden"
                id="record_id" name="id"
                value="<?php echo (isset($intKeyValue) && $intKeyValue) ? $intKeyValue : '' ?>">
            <table>
    <?php
    $haveChildForm = false;
    $prevPosition = false;
    $prevColSpan = 1;
    $rowOpen = false;
    $formFieldMode = sesWriteAccess() && !$readOnlyForm ? 'MODIFY' : 'READONLY';
    foreach ($astrFormElements as $elem) {
        if ($elem['type'] === false) {
            continue;
        }
        $style = $elem['style'] !== '' ? ' ' . $elem['style'] : '';

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

        if ($blnNew
            && in_array($elem['type'], ['BUTTON', 'JSBUTTON', 'IMAGE', 'DROPDOWNMENU'])
        ) {
            echo '          <td class="label$style">&nbsp;</td>';
        } elseif (in_array($elem['type'], ['BUTTON', 'JSBUTTON', 'DROPDOWNMENU'])) {
            $intColspan = 1;
            ?>
          <td class="button">
            <?php

            echo htmlFormElement(
                $elem['name'], $elem['type'],
                $astrValues[$elem['name']], $elem['style'], $elem['listquery'],
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
          <td>&nbsp;</td>
        <?php
        } elseif ($elem['type'] == 'HID_INT' || strstr($elem['type'], 'HID_')) {
            echo htmlFormElement(
                $elem['name'], $elem['type'], $astrValues[$elem['name']],
                $elem['style'], $elem['listquery'], $fieldMode, $elem['parent_key'],
                $elem['label']
            );
        } elseif ($elem['type'] == 'IMAGE') {
            ?>
          <td class="image" colspan="<?php echo $intColspan?>">
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
            $haveChildForm = true;
            createIForm(
                $astrFormElements, $elem,
                isset($intKeyValue) ? $intKeyValue : 0, $blnNew, $strForm
            );
            break;
        } else {
            $value = $astrValues[$elem['name']];
            if ($elem['style'] == 'measurement') {
                $value = $value ? miscRound2Decim($value, 2) : '';
            }
            if ($elem['type'] == 'AREA') {
            ?>
          <td class="toplabel"><?php echo Translator::translate($elem['label'])?></td>
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
            ?>
          <td class="field"
                    <?php echo $strColspan ? " $strColspan" : ''?>>
            <?php

            echo htmlFormElement(
                $elem['name'], $elem['type'], $value,
                $elem['style'], $elem['listquery'], $fieldMode,
                isset($elem['parent_key']) ? $elem['parent_key'] : '', '', [],
                isset($elem['elem_attributes']) ? $elem['elem_attributes'] : '',
                isset($elem['options']) ? $elem['options'] : null
            );
            if (isset($elem['attached_elem'])) {
                echo '            ' . $elem['attached_elem'] . "\n";
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

    if (!$haveChildForm) {
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

  <script type="text/javascript">
/* <![CDATA[ */
var globals = {
    nonOpenModificationWarning: '<?php echo Translator::translate('NonOpenInvoiceModificationWarning')?>'
    <?php
    if ($strForm == 'invoice' && !empty($intKeyValue)) {
        $rows = dbParamQuery('SELECT invoice_open FROM {prefix}invoice_state WHERE id=?', [$astrValues['state_id']]);
        $open = isset($rows[0]) && $rows[0]['invoice_open'];
        echo '    , invoiceOpenStatus: ' . ($open ? 'true' : 'false') . "\n";
    }
?>
};

function startChanging()
{
    <?php
    if ($strForm == 'invoice') {
    ?>
      if (typeof globals.invoiceOpenStatus !== 'undefined' && !globals.invoiceOpenStatus && typeof globals.warningShown === 'undefined') {
        MLInvoice.errormsg(globals.nonOpenModificationWarning, 0);
        globals.warningShown = true;
      }
<?php
    }
?>
}
$(document).ready(function() {
    <?php
    if ($strMessage) {
?>
  MLInvoice.infomsg("<?php echo $strMessage?>");
<?php
    }
    if ($strErrorMessage) {
?>
      MLInvoice.errormsg("<?php echo $strErrorMessage?>");
<?php
    }
    if ($strForm == 'product') {
?>
  update_stock_balance_log();
<?php
    }
    if (sesWriteAccess()) {
        ?>
<?php
    }
?>

  $('#admin_form').find(
      'input[type="text"]:not([name="payment_date"]),input[type="hidden"],input[type="checkbox"]:not([name="archived"]),select:not(.dropdownmenu),textarea'
  ).one('change', startChanging);
    <?php
    if ($haveChildForm && !$blnNew) {
?>
  init_rows();
    <?php
    if (sesWriteAccess() && 'invoice' === $strForm) {
?>
  $('#itable > tbody').sortable({
    axis: 'y',
    handle: '.sort-col',
    items: 'tr.item-row',
    stop: function(event, ui) {
      update_row_order();
    }
  });
<?php
    }
?>
  $('#iform').find('input[type="text"],input[type="hidden"],input[type="checkbox"]:not(.cb-select-row):not(#cb-select-all),select:not(.dropdownmenu),textarea')
    .change(function() { $('.add_row_button').addClass('ui-state-highlight'); });
  $('#iform').find('input[type="text"],input[type="hidden"],input[type="checkbox"],select:not(.dropdownmenu),textarea').one('change', startChanging);

  $('#iform_popup').find('input[type="text"],input[type="hidden"]:not(.select-default-text),input[type="checkbox"],select:not(.dropdownmenu),textarea').change(function(e) {
    $(this).parent().find('.modification-indicator').removeClass('hidden');
    $(this).data('modified', 1);
  });

    <?php
    } elseif (isset($newLocation)) {
        echo "window.location='$newLocation';";
    }
    if (isset($openWindow)) {
        echo "window.open('$openWindow');";
    }
    ?>
});
    <?php
    if ($haveChildForm && !$blnNew) {
    ?>
function init_rows_done()
{
    <?php
    if (isset($newLocation)) {
        echo "window.location='$newLocation';";
    }
    ?>
}
<?php
    }
    ?>

function save_record(redirect_url, redir_style, on_print)
{
  var form = document.getElementById('admin_form');
  var obj = new Object();

    <?php
    foreach ($astrFormElements as $elem) {
        if ($elem['name']
            && !in_array(
                $elem['type'],
                [
                    'HID_INT',
                    'SECHID_INT',
                    'BUTTON',
                    'JSBUTTON',
                    'DROPDOWNMENU',
                    'LABEL',
                    'IMAGE',
                    'NEWLINE',
                    'ROWSUM',
                    'CHECK',
                    'IFORM',
                    'FILLER'
                ]
            )
        ) {
    ?>
  obj.<?php echo $elem['name']?> = form.<?php echo $elem['name']?>.value;
        <?php
        } elseif ($elem['type'] == 'CHECK') {
        ?>
  obj.<?php echo $elem['name']?> = form.<?php echo $elem['name']?>.checked ? 1 : 0;
        <?php
        }
    }
    ?>
  obj.id = form.id.value;
  if (typeof on_print !== 'undefined') {
    obj.onPrint = on_print;
  }
  $.ajax({
    'url': "json.php?func=put_<?php echo $strJSONType?>",
    'type': 'POST',
    'dataType': 'json',
    'data': $.toJSON(obj),
    'contentType': 'application/json; charset=utf-8',
    'success': function(data) {
      if (data.warnings) {
        alert(data.warnings);
      }
      if (data.missing_fields) {
        MLInvoice.errormsg('<?php echo Translator::translate('ErrValueMissing')?>: ' + data.missing_fields);
      } else {
        <?php if ($strJSONType == 'invoice') { ?>
          if (typeof on_print !== 'undefined' && on_print) {
            $('input#invoice_no').val(data.invoice_no);
            $('input#ref_number').val(data.ref_number);
          }
        <?php } ?>
        $('.save_button').removeClass('ui-state-highlight');
        MLInvoice.infomsg('<?php echo Translator::translate('RecordSaved')?>', 2000);
        if (redirect_url) {
          if (redir_style == 'openwindow') {
            window.open(redirect_url);
          } else {
            window.location = redirect_url;
          }
        }
        if (!obj.id) {
          obj.id = data.id;
          form.id.value = obj.id;
          if (!redirect_url || redir_style == 'openwindow') {
            var newloc = new String(window.location).split('#', 1)[0];
            window.location = newloc + '&id=' + obj.id;
          }
        }
      }
    },
    'error': function(XMLHTTPReq, textStatus, errorThrown) {
      if (XMLHTTPReq.status == 409) {
        MLInvoice.errormsg(jQuery.parseJSON(XMLHTTPReq.responseText).warnings);
      }
      else if (textStatus == 'timeout') {
        MLInvoice.errormsg('Timeout trying to save data');
      } else {
        MLInvoice.errormsg('Error trying to save data: ' + XMLHTTPReq.status + ' - ' + XMLHTTPReq.statusText);
      }
      return false;
    }
  });
}

/* ]]> */
</script>

    <?php
    createFormButtons($strForm, $blnNew, $copyLinkOverride, false, $readOnlyForm, '', false);
    echo "  </div>\n";

    if ($addressAutocomplete && getSetting('address_autocomplete')) {
    ?>
  <script type="text/javascript">
  $(document).ready(function() {
  var s = document.createElement("script");
    s.type = "text/javascript";
    s.src  = "https://maps.googleapis.com/maps/api/js?sensor=false&libraries=places&callback=gmapsready";
    window.gmapsready = function() {
      initAddressAutocomplete('');
      initAddressAutocomplete('quick_');
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
 * @param array  $astrFormElements Form elements
 * @param string $elem             Subform element
 * @param int    $intKeyValue      Record ID
 * @param bool   $newRecord        Whether a new record is being added
 * @param string $strForm          Form name
 *
 * @return void
 */
function createIForm($astrFormElements, $elem, $intKeyValue, $newRecord, $strForm)
{
    ?>
        <div class="iform <?php echo $elem['style']?>ui-corner-tl ui-corner-bl ui-corner-br ui-corner-tr ui-helper-clearfix"
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
<script type="text/javascript">
/* <![CDATA[ */

function init_rows()
{
  $('#cb-select-all').prop('checked', false);
    <?php
    $subFormElements = getFormElements($elem['name']);
    $strParentKey = getFormParentKey($elem['name']);
    $clearRowValuesAfterAdd = getFormClearRowValuesAfterAdd($elem['name']);
    $onAfterRowAdded = getFormOnAfterRowAdded($elem['name']);
    $formJSONType = getFormJSONType($elem['name']);
    foreach ($subFormElements as $subElem) {
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
        echo '  var arr_' . $subElem['name'] . ' = {"0":"-"';
        foreach ($values as $key => $value) {
            if ($translate) {
                $value = Translator::translate($value);
            }
            echo ',' . $key . ':"' . addcslashes($value, '\"\/') . '"';
        }
        echo "};\n";
    }
    ?>
  $.getJSON('json.php?func=get_<?php echo $elem['name']?>&parent_id=<?php echo $intKeyValue?>', function(json) {
    $('#itable > tbody > tr[id!=form_row]').remove();
    var table = $('#itable');
    for (var i = 0; i < json.records.length; i++)
    {
      var record = json.records[i];
      var tr = $('<tr/>').addClass('item-row');
    <?php
    if ($strForm == 'invoice' && sesWriteAccess()) {
        $selectRow = Translator::translate('SelectRow');
        echo <<<EOT
      var td = $('<td class="sort-col"><span class="sort-handle hidden">&#x25B2;&#x25BC;</span>');
      tr.append(td);
      td = $('<td class="select-row"/>');
      var input = $('<input type="checkbox" class="cb-select-row" title="$selectRow" aria-label="$selectRow">');
      input.val(record.id);
      td.append(input);
      tr.append(td);

EOT;
    }

    foreach ($subFormElements as $subElem) {
        if (true
            && in_array(
                $subElem['type'],
                [
                    'HID_INT',
                    'SECHID_INT',
                    'BUTTON',
                    'NEWLINE'
                ]
            )
        ) {
            continue;
        }
        $name = $subElem['name'];
        $class = $subElem['style'];
        echo "      var td = $('<td/>').addClass('$class' + (record.deleted == 1 ? ' deleted' : ''));\n";
        echo "      td.attr('data-field', '$name');\n";
        if ($subElem['type'] == 'LIST' || $subElem['type'] == 'SEARCHLIST') {
            echo "      if (record.${name}_text === null || typeof record.${name}_text === 'undefined')"
                . " record.${name}_text = (typeof arr_" . $subElem['name'] . "[record.${name}] !== 'undefined') ? arr_"
                . $subElem['name'] . "[record.${name}] : '';\n";
            if ($elem['name'] == 'invoice_rows' && $name == 'product_id') {
                echo <<<EOT
      if (record.$name !== null) {
        var link = $('<a/>').attr('href', '?func=settings&list=product&form=product&listid=list_product&id=' + record.$name).text(record.${name}_text);
        td.append(link);
      }
      td.appendTo(tr);

EOT;
            } else {
                echo "      td.text(record.${name}_text).appendTo(tr);\n";
            }
        } elseif ($subElem['type'] == 'INT') {
            if (isset($subElem['decimals'])) {
                echo "      td.text(record.$name ? MLInvoice.formatCurrency(record.$name, {$subElem['decimals']}) : '').appendTo(tr);\n";
            } else {
                echo "      td.text(record.$name ? String(record.$name).replace('.', '" . Translator::translate('DecimalSeparator') . "') : '').appendTo(tr);\n";
            }
        } elseif ($subElem['type'] == 'INTDATE') {
            echo "      if (record.$name === null) record.$name = '';\n";
            echo "      td.text(formatDate(record.$name)).appendTo(tr);\n";
        } elseif ($subElem['type'] == 'CHECK') {
            echo "      td.text(record.$name == 1 ? \"" .
                Translator::translate('YesButton') . '" : "' . Translator::translate('NoButton') .
                "\").appendTo(tr);\n";
        } elseif ($subElem['type'] == 'ROWSUM') {
?>
      var rowSum = MLInvoice.calcRowSum(record);
      sum = MLInvoice.formatCurrency(rowSum.sum, <?php echo isset($subElem['decimals']) ? $subElem['decimals'] : 2?>);
      VAT = MLInvoice.formatCurrency(rowSum.VAT, <?php echo isset($subElem['decimals']) ? $subElem['decimals'] : 2?>);
      sumVAT = MLInvoice.formatCurrency(rowSum.sumVAT, <?php echo isset($subElem['decimals']) ? $subElem['decimals'] : 2?>);
      var title = '<?php echo Translator::translate('VATLess') . ': '?>' + sum + ' &ndash; ' + '<?php echo Translator::translate('VATPart') . ': '?>' + VAT;
      var td = $('<td/>').addClass('<?php echo $class?>' + (record.deleted == 1 ? ' deleted' : '')).append('<span title="' + title + '">' + sumVAT + '<\/span>').appendTo(tr);
<?php
        } elseif ($subElem['type'] == 'TAGS') {
            echo "      var val = record.$name ? String(record.$name) : '';\n";
            echo "      val = val.replace(new RegExp(/,/, 'g'), ', ');\n";
            echo "      $('<td/>').addClass('$class' + (record.deleted == 1 ? ' deleted' : '')).text(val).appendTo(tr);\n";
        } else {
            echo "      $('<td/>').addClass('$class' + (record.deleted == 1 ? ' deleted' : '')).text(record.$name ? record.$name : '').appendTo(tr);\n";
        }
    }
    if (sesWriteAccess()) {
    ?>
      $('<td/>').addClass('button')
        .append(
          '<a class="tinyactionlink row_edit_button rec' + record.id
          + '" href="#"><?php echo Translator::translate('Edit')?><\/a>'
        ).appendTo(tr);
      $('<td/>').addClass('button')
        .append(
          '<a class="tinyactionlink row_copy_button rec' + record.id +
          '" href="#"><?php echo Translator::translate('Copy')?><\/a>'
        ).appendTo(tr);
    <?php
    }
    ?>
      table.append(tr);
    }
    <?php
    if ($elem['name'] == 'invoice_rows') {
    ?>
    var summary = MLInvoice.calculateInvoiceRowSummary(json.records);
    var tr = $('<tr/>').addClass('summary');
    var modifyCol = $('<td/>').addClass('input').attr('colspan', '6').attr('rowspan', '2');
    <?php
    if (sesWriteAccess()) {
    ?>
    modifyCol.text('<?php echo Translator::translate('ForSelected')?>: ');
    var button = $('<button id="delete-selected-rows" class="selected-row-button ui-button ui-corner-all ui-widget"/>')
        .text('<?php echo Translator::translate('Delete')?>')
        .click(function(event) {
            delete_selected_rows();
            return false;
        });
    button.appendTo(modifyCol);
    modifyCol.append($('<span/>').text(' '));
    var button = $('<button id="update-selected-rows" class="selected-row-button ui-button ui-corner-all ui-widget"/>')
        .text('<?php echo Translator::translate('Modify')?>')
        .click(function(event) {
            multi_editor(event, '<?php echo Translator::translate('ModifySelectedRows')?>');
            return false;
        });
    button.appendTo(modifyCol);
    <?php
    }
?>
    modifyCol.appendTo(tr);
    $('<td/>').addClass('input').attr('colspan', '6').attr('align', 'right').text('<?php echo Translator::translate('TotalExcludingVAT')?>').appendTo(tr);
    $('<td/>').addClass('input currency').attr('align', 'right').text(MLInvoice.formatCurrency(summary.totSum)).appendTo(tr);
    $('<td/>').attr('colspan', '2').appendTo(tr);
    $(table).append(tr);

    tr = $('<tr/>').addClass('summary');
    $('<td/>').addClass('input').attr('colspan', '6').attr('align', 'right').text('<?php echo Translator::translate('TotalVAT')?>').appendTo(tr);
    $('<td/>').addClass('input currency').attr('align', 'right').text(MLInvoice.formatCurrency(summary.totVAT)).appendTo(tr);
    $('<td/>').attr('colspan', '2').appendTo(tr);
    $(table).append(tr);

    var tr = $('<tr/>').addClass('summary');
    $('<td/>').addClass('input').attr('colspan', '12').attr('align', 'right').text('<?php echo Translator::translate('TotalIncludingVAT')?>').appendTo(tr);
    $('<td/>').addClass('input currency').attr('align', 'right').text(MLInvoice.formatCurrency(summary.totSumVAT)).appendTo(tr);
    $('<td/>').attr('colspan', '2').appendTo(tr);
    $(table).append(tr);

    var tr = $('<tr/>').addClass('summary');
    $('<td/>').addClass('input').attr('colspan', '12').attr('align', 'right').text('<?php echo Translator::translate('TotalToPay')?>').appendTo(tr);
    $('<td/>').addClass('input currency').attr('align', 'right').text(MLInvoice.formatCurrency(summary.totSumVAT + summary.partialPayments)).appendTo(tr);
    $('<td/>').attr('colspan', '2').appendTo(tr);
    $(table).append(tr);

    if (summary.totWeight > 0) {
        var tr = $('<tr/>').addClass('summary');
        $('<td/>').addClass('input').attr('colspan', '12').attr('align', 'right').text('<?php echo Translator::translate('ProductWeight')?>').appendTo(tr);
        $('<td/>').addClass('input currency').attr('align', 'right').text(MLInvoice.formatCurrency(summary.totWeight, 3)).appendTo(tr);
        $('<td/>').attr('colspan', '2').appendTo(tr);
        $(table).append(tr);
    }

    MLInvoice.updateRowSelectedState();
    $('.cb-select-row').click(MLInvoice.updateRowSelectedState);

    $('#itable tr')
      .mouseover(function() { $(this).find('.sort-handle').removeClass('hidden'); })
      .mouseout(function() { $(this).find('.sort-handle').addClass('hidden'); });

    <?php
    }
    ?>
    $('a[class~="row_edit_button"]').click(function(event) {
      var row_id = $(this).attr('class').match(/rec(\d+)/)[1];
      popup_editor(event, '<?php echo Translator::translate('RowModification')?>', row_id, false);
      return false;
    });

    $('a[class~="row_copy_button"]').click(function(event) {
      var row_id = $(this).attr('class').match(/rec(\d+)/)[1];
      popup_editor(event, '<?php echo Translator::translate('RowCopy')?>', row_id, true);
      return false;
    });

    $('a[class~="tinyactionlink"]').button();

    init_rows_done();
    <?php
    if (getSetting('invoice_show_dispatch_dates')
        && $elem['name'] == 'invoice_rows'
        && isset($intKeyValue)
    ) {
    ?>
    MLInvoice.updateDispatchByDateButtons();
    <?php
    }
    ?>
  });
}
    <?php
    if (sesWriteAccess()) {
        ?>
function save_row(form_id)
{
  var form = document.getElementById(form_id);
  var obj = new Object();
        <?php
        foreach ($subFormElements as $subElem) {
            if (true
                && !in_array(
                    $subElem['type'],
                    [
                        'HID_INT',
                        'SECHID_INT',
                        'BUTTON',
                        'NEWLINE',
                        'ROWSUM',
                        'CHECK',
                        'INT'
                    ]
                )
            ) {
                ?>
  obj.<?php echo $subElem['name']?> = document.getElementById(form_id + '_<?php echo $subElem['name']?>').value;
                <?php
            } elseif ($subElem['type'] == 'CHECK') {
                ?>
  obj.<?php echo $subElem['name']?> = document.getElementById(form_id + '_<?php echo $subElem['name']?>').checked ? 1 : 0;
                <?php
            } elseif ($subElem['type'] == 'INT') {
                ?>
  obj.<?php echo $subElem['name']?> = document.getElementById(form_id + '_<?php echo $subElem['name']?>').value.replace('<?php echo Translator::translate('DecimalSeparator')?>', '.');
                <?php
            }
        }
        ?>  obj.<?php echo $elem['parent_key'] . " = $intKeyValue"?>;
  if (form.row_id) {
    obj.id = form.row_id.value;
  }
  $.ajax({
    'url': "json.php?func=put_<?php echo $formJSONType?>",
    'type': 'POST',
    'dataType': 'json',
    'data': $.toJSON(obj),
    'contentType': 'application/json; charset=utf-8',
    'success': function(data) {
      if (data.missing_fields)
      {
        MLInvoice.errormsg('<?php echo Translator::translate('ErrValueMissing')?>: ' + data.missing_fields);
      }
      else
      {
        if (form_id == 'iform')
          $('.add_row_button').removeClass('ui-state-highlight');
        init_rows();
        if (form_id == 'iform_popup')
          $("#popup_edit").dialog('close');
        if (!obj.id)
        {
            <?php echo $onAfterRowAdded?>
        <?php

        foreach ($subFormElements as $subElem) {
            if (true
                && !in_array(
                    $subElem['type'],
                    [
                        'HID_INT',
                        'SECHID_INT',
                        'BUTTON',
                        'NEWLINE',
                        'ROWSUM'
                    ]
                )
            ) {
                if (isset($subElem['default']) && strstr($subElem['default'], 'ADD')) {
                    // The value is taken from whatever form was used but put into iform
                    ?>
          var fld = document.getElementById(form_id + '_<?php echo $subElem['name']?>');
          document.getElementById('iform_<?php echo $subElem['name']?>').value = parseInt(fld.value) + 5;
                    <?php
                } elseif ($clearRowValuesAfterAdd && $subElem['type'] != 'INTDATE') {
                    if ($subElem['type'] == 'LIST') {
                        ?>
          document.getElementById('iform_<?php echo $subElem['name']?>').selectedIndex = -1;
                        <?php
                    } elseif ($subElem['type'] == 'SEARCHLIST') {
                        ?>
          $('#iform_<?php echo $subElem['name']?>').select2('val', '');
                        <?php
                    } elseif ($subElem['type'] == 'CHECK') {
                        ?>
          document.getElementById('iform_<?php echo $subElem['name']?>').checked = 0;
                        <?php
                    } else {
                        ?>
          document.getElementById('iform_<?php echo $subElem['name']?>').value = '';
                        <?php
                    }
                }
            }
        }
?>
        }
      }
    },
    'error': function(XMLHTTPReq, textStatus, errorThrown) {
      if (textStatus == 'timeout') {
        MLInvoice.errormsg('Timeout trying to save row');
      } else {
        MLInvoice.errormsg('Error trying to save row: ' + XMLHTTPReq.status + ' - ' + XMLHTTPReq.statusText);
      }
      return false;
    }
  });
}

function modify_rows(form_id)
{
  var form = document.getElementById(form_id);
  var obj = new Object();
        <?php
        foreach ($subFormElements as $subElem) {
            if (true
                && !in_array(
                    $subElem['type'],
                    [
                        'HID_INT',
                        'SECHID_INT',
                        'BUTTON',
                        'NEWLINE',
                        'ROWSUM',
                        'CHECK',
                        'INT'
                    ]
                )
            ) {
                ?>
  if ($('#' + form_id + '_<?php echo $subElem['name']?>').data('modified')) {
    obj.<?php echo $subElem['name']?> = document.getElementById(form_id + '_<?php echo $subElem['name']?>').value;
  }
                <?php
            } elseif ($subElem['type'] == 'CHECK') {
                ?>
  if ($('#' + form_id + '_<?php echo $subElem['name']?>').data('modified')) {
    obj.<?php echo $subElem['name']?> = document.getElementById(form_id + '_<?php echo $subElem['name']?>').checked ? 1 : 0;
  }
                <?php
            } elseif ($subElem['type'] == 'INT') {
                ?>
  if ($('#' + form_id + '_<?php echo $subElem['name']?>').data('modified')) {
    obj.<?php echo $subElem['name']?> = document.getElementById(form_id + '_<?php echo $subElem['name']?>').value.replace('<?php echo Translator::translate('DecimalSeparator')?>', '.');
  }
                <?php
            }
        }
        ?>
  var req = new Object();
  req.table = '<?php echo $formJSONType?>';
  req.ids = $('.cb-select-row:checked').map(function() { return this.value; }).get();
  req.changes = obj;
  $.ajax({
    'url': "json.php?func=update_multiple",
    'type': 'POST',
    'dataType': 'json',
    'data': $.toJSON(req),
    'contentType': 'application/json; charset=utf-8',
    'success': function(data) {
      if (data.missing_fields) {
        MLInvoice.errormsg('<?php echo Translator::translate('ErrValueMissing')?>: ' + data.missing_fields);
      } else {
        $("#popup_edit").dialog('close');
          init_rows();
      }
    },
    'error': function(XMLHTTPReq, textStatus, errorThrown) {
      if (textStatus == 'timeout') {
        alert('Timeout trying to modify rows');
      } else {
        alert('Error trying to modify rows: ' + XMLHTTPReq.status + ' - ' + XMLHTTPReq.statusText);
      }
    }
  });
}

function update_row_order()
{
  var req = new Object();
  req.table = '<?php echo $formJSONType?>';
  req.order = {};
  var orderno = 5;
  $('.cb-select-row').each(function() {
    req.order[this.value] = orderno;
    orderno += 5;
  });
  $.ajax({
    'url': "json.php?func=update_row_order",
    'type': 'POST',
    'dataType': 'json',
    'data': $.toJSON(req),
    'contentType': 'application/json; charset=utf-8',
    'success': function(data) {
      init_rows();
    },
    'error': function(XMLHTTPReq, textStatus, errorThrown) {
      if (textStatus == 'timeout') {
        alert('Timeout trying to modify rows');
      } else {
        alert('Error trying to modify rows: ' + XMLHTTPReq.status + ' - ' + XMLHTTPReq.statusText);
      }
      init_rows();
    }
  });

}

function delete_selected_rows()
{
  var table = '<?php echo $formJSONType?>';
  var req = new Object();
  req.id = $('.cb-select-row:checked').map(function() { return this.value; }).get();
  $.ajax({
    'url': "json.php?func=delete_<?php echo $formJSONType?>",
    'type': 'POST',
    'dataType': 'json',
    'data': req,
    //'contentType': 'application/json; charset=utf-8',
    'success': function(data) {
      init_rows();
    },
    'error': function(XMLHTTPReq, textStatus, errorThrown) {
      init_rows();
      if (textStatus == 'timeout') {
        alert('Timeout trying to modify rows');
      } else {
        alert('Error trying to modify rows: ' + XMLHTTPReq.status + ' - ' + XMLHTTPReq.statusText);
      }
    }
  });
}

function delete_row(form_id)
{
  var form = document.getElementById(form_id);
  var id = form.row_id.value;
  $.ajax({
    'url': "json.php?func=delete_<?php echo $formJSONType?>&id=" + id,
    'type': 'GET',
    'dataType': 'json',
    'contentType': 'application/json; charset=utf-8',
    'success': function(data) {
      init_rows();
      if (form_id == 'iform_popup')
        $("#popup_edit").dialog('close');
    },
    'error': function(XMLHTTPReq, textStatus, errorThrown) {
      if (textStatus == 'timeout') {
        MLInvoice.errormsg('Timeout trying to save row');
      } else {
        MLInvoice.errormsg('Error trying to save row: ' + XMLHTTPReq.status + ' - ' + XMLHTTPReq.statusText);
      }
      return false;
    }
  });
}

function popup_editor(event, title, id, copy_row)
{
  startChanging();
  $('#iform_popup .modification-indicator').addClass('hidden');
  $('#iform_popup input').data('modified', '');
  $.getJSON('json.php?func=get_<?php echo $formJSONType?>&id=' + id, function(json) {
    if (!json.id) return;
    var form = document.getElementById('iform_popup');

    if (copy_row)
      form.row_id.value = '';
    else
      form.row_id.value = id;
        <?php
        foreach ($subFormElements as $subElem) {
            if (true
                && in_array(
                    $subElem['type'],
                    [
                        'HID_INT',
                        'SECHID_INT',
                        'CONST_HID_INT',
                        'BUTTON',
                        'NEWLINE',
                        'ROWSUM'
                    ]
                )
            ) {
                continue;
            }
            $name = $subElem['name'];
            if ($subElem['type'] == 'SEARCHLIST') {
                ?>
    var item = {
      id: json.<?php echo $name?>,
      text: json.<?php echo $name?>_text
    };
    $('#<?php echo "iform_popup_$name"?>').select2('data', item);
                <?php
            } elseif ($subElem['type'] == 'LIST') {
                ?>
    for (var i = 0; i < form.<?php echo "iform_popup_$name"?>.options.length; i++)
    {
      var item = form.<?php echo "iform_popup_$name"?>.options[i];
      if (item.value == json.<?php echo $name?>)
      {
        item.selected = true;
        break;
      }
    }
                <?php
            } elseif ($subElem['type'] == 'INT') {
                if (isset($subElem['default']) && strstr($subElem['default'], 'ADD')) {
                    ?>
    var value;
    if (copy_row)
      value = document.getElementById('<?php echo "iform_$name"?>').value;
    else
      value = json.<?php echo $name?> ? String(json.<?php echo $name?>).replace('.', '<?php Translator::translate('DecimalSeparator')?>') : '';
    form.<?php echo "iform_popup_$name"?>.value = value;
                    <?php
                } else {
                    if (isset($subElem['decimals'])) {
                        ?>
    form.<?php echo "iform_popup_$name"?>.value = json.<?php echo $name?> ? MLInvoice.formatCurrency(json.<?php echo $name?>, <?php echo $subElem['decimals']?>) : '';
                        <?php
                    } else {
                        ?>
    form.<?php echo "iform_popup_$name"?>.value = json.<?php echo $name?> ? String(json.<?php echo $name?>).replace('.', '<?php echo Translator::translate('DecimalSeparator')?>') : '';
                        <?php
                    }
                }
            } elseif ($subElem['type'] == 'INTDATE') {
                ?>
    form.<?php echo "iform_popup_$name"?>.value = json.<?php echo $name?> ? formatDate(json.<?php echo $name?>) : '';
                <?php
            } elseif ($subElem['type'] == 'CHECK') {
                ?>
    form.<?php echo "iform_popup_$name"?>.checked = json.<?php echo $name?> != 0 ? true : false;
                <?php
            } elseif ($subElem['type'] == 'TAGS') {
                ?>
    var items = [];
    $(json.<?php echo $name?>.split(',')).each(function () {
        items.push({id: this, text: this});
    });
    $('#<?php echo "iform_popup_$name"?>').select2('data', items);
                <?php
            } else {
                ?>
    form.<?php echo "iform_popup_$name"?>.value = json.<?php echo $name?>;
                <?php
            }
        }
    ?>
    MLInvoice.setupSelect2($("#popup_edit"));

    var buttons = new Object();
    buttons["<?php echo Translator::translate('Save')?>"] = function() { save_row('iform_popup'); };
    if (!copy_row)
      buttons["<?php echo Translator::translate('Delete')?>"] = function() { if(confirm('<?php echo Translator::translate('ConfirmDelete')?>')==true) { delete_row('iform_popup'); } return false; };
    buttons["<?php echo Translator::translate('Close')?>"] = function() { $("#popup_edit").dialog('close'); };
    $("#popup_edit").dialog({ modal: true, width: 1050, height: 180, resizable: true,
      buttons: buttons,
      title: title,
    });

  });
}

function multi_editor(event, title)
{
  startChanging();
  $('#iform_popup .modification-indicator').addClass('hidden');
  $('#iform_popup input').data('modified', 0);
  var form = document.getElementById('iform_popup');
        <?php
        foreach ($subFormElements as $subElem) {
            if (in_array($subElem['type'], ['HID_INT', 'SECHID_INT', 'CONST_HID_INT', 'BUTTON', 'NEWLINE', 'ROWSUM'])) {
                continue;
            }
            $name = $subElem['name'];
            if ($subElem['type'] == 'SEARCHLIST') {
                ?>
    $('#<?php echo "iform_popup_$name"?>').select2('data', {});
                <?php
            } elseif ($subElem['type'] == 'LIST') {
                ?>
    for (var i = 0; i < form.<?php echo "iform_popup_$name"?>.options.length; i++)
    {
      var item = form.<?php echo "iform_popup_$name"?>.options[i].selected = false;
    }
                <?php
            } elseif ($subElem['type'] == 'INT') {
                if (isset($subElem['default']) && strstr($subElem['default'], 'ADD')) {
                    ?>
    form.<?php echo "iform_popup_$name"?>.value = '';
                    <?php
                } else {
                    if (isset($subElem['decimals'])) {
                        ?>
    form.<?php echo "iform_popup_$name"?>.value = '';
                        <?php
                    } else {
                        ?>
    form.<?php echo "iform_popup_$name"?>.value = '';
                        <?php
                    }
                }
            } elseif ($subElem['type'] == 'INTDATE') {
                ?>
    form.<?php echo "iform_popup_$name"?>.value = '';
                <?php
            } elseif ($subElem['type'] == 'CHECK') {
                ?>
    form.<?php echo "iform_popup_$name"?>.checked = false;
                <?php
            } elseif ($subElem['type'] == 'TAGS') {
                ?>
    $('#<?php echo "iform_popup_$name"?>').select2('data', []);
                <?php
            } else {
                ?>
    form.<?php echo "iform_popup_$name"?>.value = '';
                <?php
            }
        }
        ?>
    MLInvoice.setupSelect2($("#popup_edit"));

    var buttons = new Object();
    buttons["<?php echo Translator::translate('Save')?>"] = function() { modify_rows('iform_popup'); };
    buttons["<?php echo Translator::translate('Close')?>"] = function() { $("#popup_edit").dialog('close'); };
    $("#popup_edit").dialog({
      modal: true, width: 1050, height: 180, resizable: true,
      buttons: buttons,
      title: title,
    });
}

<?php
    }
    ?>
/* ]]> */
</script>
                <form method="post" name="iform" id="iform">
                    <table class="iform" id="itable">
                        <thead>
                            <tr>
    <?php
    if ($strForm == 'invoice' && sesWriteAccess()) {
        $selectAll = Translator::translate('SelectAll');
        ?>
        <th class="label ui-state-default sort-col"> </th>
        <th class="label ui-state-default select-row"><input type="checkbox" id="cb-select-all" title="<?php echo $selectAll?>" aria-label="<?php echo $selectAll?>"></th>
        <?php
    }

    foreach ($subFormElements as $subElem) {
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

        foreach ($subFormElements as $subElem) {
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
              <td
                                    class="label <?php echo strtolower($subElem['style'])?>_label">
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
              <td
                                    class="label <?php echo strtolower($subElem['style'])?>_label">
                                    &nbsp;</td>
<?php
            }
        }
        if ($strForm == 'invoice') {
            ?>
              <td class="button" colspan="2">
                <a class="tinyactionlink add_row_button" href="#"
                  onclick="save_row('iform'); return false;">
                    <?php echo Translator::translate('AddRow')?>
                </a>
              </td>
<?php
        } else {
            ?>
              <td class="button" colspan="2">
                <a class="tinyactionlink add_row_button" href="#"
                  onclick="save_row('iform'); return false;">
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
                <div id="popup_edit"
                    style="display: none; width: 900px; overflow: hidden">
                    <form method="post" name="iform_popup" id="iform_popup">
                        <input type="hidden" name="row_id" value=""> <input type="hidden"
                            name="<?php echo $strParentKey?>"
                            value="<?php echo $intKeyValue?>">
                        <table class="iform">
                            <tr>
        <?php
        foreach ($subFormElements as $elem) {
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
      <a class="actionlink save_button" href="#" onclick="save_record(); return false;">
        <?php echo Translator::translate('Save')?>
      </a>
    <?php
    }

    if (!$new) {
        if ($copyLinkOverride) {
            ?>
            <a class="actionlink" href="<?php echo $copyLinkOverride?>">
                <?php echo Translator::translate('Copy')?>
            </a>
            <?php
        } else {
            ?>
            <a class="actionlink form-submit" href="#" data-form="admin_form" data-set-field="copyact">
                <?php echo Translator::translate('Copy')?>
            </a>
            <?php
        }
        ?>
        <a class="actionlink form-submit" href="#" data-form="admin_form" data-set-field="newact">
            <?php echo Translator::translate('New')?>
        </a>
        <?php
        if (!$readOnlyForm) {
            ?>
            <a class="actionlink form-submit" href="#" data-form="admin_form" data-set-field="deleteact"
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
            <a class="actionlink ytj_search_button" href="#"><?php echo Translator::translate('SearchYTJ')?></a>
            <?php
        }
        if ($top && !$new) {
            ?>
            <a id="cover-letter-button" class="actionlink" href="#">
                <?php echo Translator::translate('PrintCoverLetter')?>
            </a>
            <div id="cover-letter-form" class="ui-corner-all hidden">
            <div class="ui-corner-tl ui-corner-tr fg-toolbar ui-toolbar ui-widget-header">
                <?php echo Translator::translate('PrintCoverLetter')?>
            </div>
            <div id="cover-letter-form-inner">
                <form action="coverletter.php" method="POST">
                <input type="hidden" name="company" value="<?php echo getRequest('id')?>">
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
                <div class="form_buttons" style="clear: both">
                    <input type="submit" class="ui-button ui-corner-all" value="<?php echo Translator::translate('Print')?>">
                    <input type="button" class="ui-button ui-corner-all close-btn" value="<?php echo Translator::translate('Close')?>">
                </div>
                </form>
            </div>
        </div>
        <?php
        }
    }

    if (($id = getRequest('id', '')) && ($listId = getRequest('listid', ''))) {
        createListNavigationLinks($listId, $id);
    }

    if ($spinner) {
        echo '     <span id="spinner" style="visibility: hidden"><img src="images/spinner.gif" alt=""></span>' .
             "\n";
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
        echo '<a href="?' . $link . '" class="actionlink">'
            . Translator::translate('Previous')
            . '</a> ';
    } else {
        echo '<a class="actionlink ui-button ui-corner-all ui-state-disabled">'
            . Translator::translate('Previous')
            . '</a> ';
    }
    if (null !== $next) {
        $link = preg_replace('/&id=\d+/', "&id=$next", $qs);
        echo '<a href="?' . $link . '" class="actionlink ui-button">'
            . Translator::translate('Next')
            . '</a> ';
    } else {
        echo '<a class="actionlink ui-button ui-corner-all ui-state-disabled">'
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
    $rows = dbParamQuery($fullQuery, $params['params']);
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