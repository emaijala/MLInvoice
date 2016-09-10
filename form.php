<?php
/*******************************************************************************
 MLInvoice: web-based invoicing application.
 Copyright (C) 2010-2015 Ere Maijala

 Portions based on:
 PkLasku : web-based invoicing software.
 Copyright (C) 2004-2008 Samu Reinikainen

 This program is free software. See attached LICENSE.

 *******************************************************************************/

/*******************************************************************************
 MLInvoice: web-pohjainen laskutusohjelma.
 Copyright (C) 2010-2015 Ere Maijala

 Perustuu osittain sovellukseen:
 PkLasku : web-pohjainen laskutusohjelmisto.
 Copyright (C) 2004-2008 Samu Reinikainen

 Tämä ohjelma on vapaa. Lue oheinen LICENSE.

 *******************************************************************************/
require_once 'sqlfuncs.php';
require_once 'miscfuncs.php';
require_once 'datefuncs.php';
require_once 'localize.php';
require_once 'form_funcs.php';
require_once 'sessionfuncs.php';

function createForm($strFunc, $strList, $strForm)
{
    require 'form_switch.php';

    if (!sesAccessLevel($levelsAllowed) && !sesAdminAccess()) {
        ?>
<div class="form_container ui-widget-content">
    <?php echo $GLOBALS['locNoAccess'] . "\n"?>
  </div>
<?php
        return;
    }

    $blnNew = getPostRequest('newact', FALSE);
    $blnCopy = getPostRequest('copyact', FALSE) ? TRUE : FALSE;
    $blnDelete = getPostRequest('deleteact', FALSE) ? TRUE : FALSE;
    $intKeyValue = getPostRequest('id', FALSE);
    if (!$intKeyValue)
        $blnNew = TRUE;

    if (!sesWriteAccess() && ($blnNew || $blnCopy || $blnDelete)) {
        ?>
<div class="form_container ui-widget-content">
    <?php echo $GLOBALS['locNoAccess'] . "\n"?>
  </div>
<?php
        return;
    }

    $strMessage = '';
    if (isset($_SESSION['formMessage']) && $_SESSION['formMessage']) {
        $strMessage = $GLOBALS['loc' . $_SESSION['formMessage']];
        unset($_SESSION['formMessage']);
    }

    $strErrorMessage = '';
    if (isset($_SESSION['formErrorMessage']) && $_SESSION['formErrorMessage']) {
        $strErrorMessage = $GLOBALS['loc' . $_SESSION['formErrorMessage']];
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

    $astrValues = getPostValues($astrFormElements,
        isset($intKeyValue) ? $intKeyValue : FALSE);

    $redirect = getRequest('redirect', null);
    if (isset($redirect)) {
        // Redirect after save
        foreach ($astrFormElements as $elem) {
            if ($elem['name'] == $redirect) {
                if ($elem['style'] == 'redirect')
                    $newLocation = str_replace('_ID_', $intKeyValue,
                        $elem['listquery']);
                elseif ($elem['style'] == 'openwindow')
                    $openWindow = str_replace('_ID_', $intKeyValue,
                        $elem['listquery']);
            }
        }
    }

    if ($blnDelete && $intKeyValue && !$readOnlyForm) {
        deleteRecord($strTable, $intKeyValue);
        unset($intKeyValue);
        unset($astrValues);
        $blnNew = TRUE;
        if (getSetting('auto_close_after_delete')) {
            $qs = preg_replace('/&form=\w*/', '', $_SERVER['QUERY_STRING']);
            $qs = preg_replace('/&id=\w*/', '', $qs);
            header(
                'Location: ' . _PROTOCOL_ . $_SERVER['HTTP_HOST'] .
                     dirname($_SERVER['PHP_SELF']) . "/index.php?$qs");
            return;
        }
        ?>
<div class="form_container ui-widget-content">
    <?php echo $GLOBALS['locRecordDeleted'] . "\n"?>
  </div>
<?php
        return;
    }

    if (isset($intKeyValue) && $intKeyValue) {
        $res = fetchRecord($strTable, $intKeyValue, $astrFormElements, $astrValues);
        if ($res === 'deleted')
            $strMessage .= $GLOBALS['locDeletedRecord'] . '<br>';
        elseif ($res === 'notfound') {
            echo $GLOBALS['locEntryDeleted'];
            die();
        }
    }

    if ($blnCopy) {
        unset($intKeyValue);
        unset($_POST);
        $blnNew = TRUE;
        $readOnlyForm = false;
    }
    ?>

<div id="popup_dlg"
    style="display: none; width: 900px; overflow: hidden">
    <iframe id="popup_dlg_iframe" src="about:blank"
        style="width: 100%; height: 100%; overflow: hidden; border: 0"></iframe>
</div>
<?php if (isset($popupHTML)) echo $popupHTML;?>

<div class="form_container">

<?php createFormButtons($blnNew, $copyLinkOverride, true, $readOnlyForm)?>
    <div class="form">
        <form method="post" name="admin_form" id="admin_form">
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
        if ($elem['type'] === false)
            continue;

        $fieldMode = isset($elem['read_only']) && $elem['read_only'] ? 'READONLY' : $formFieldMode;

        if ($elem['type'] == 'LABEL') {
            if ($rowOpen)
                echo "        </tr>\n";
            $rowOpen = false;
            ?>
        <tr>
                    <td class="sublabel ui-widget-header ui-state-default" colspan="4">
            <?php echo $elem['label']?>
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
            if ($prevPosition !== FALSE && $elem['position'] > 0) {
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

        if ($blnNew && ($elem['type'] == 'BUTTON' || $elem['type'] == 'JSBUTTON' ||
             $elem['type'] == 'IMAGE')) {
            echo '          <td class="label">&nbsp;</td>';
        } elseif ($elem['type'] == 'BUTTON' || $elem['type'] == 'JSBUTTON') {
            $intColspan = 1;
            ?>
          <td class="button">
            <?php

            echo htmlFormElement($elem['name'], $elem['type'],
                $astrValues[$elem['name']], $elem['style'], $elem['listquery'],
                $fieldMode, $elem['parent_key'], $elem['label'], [],
                isset($elem['elem_attributes']) ? $elem['elem_attributes'] : '',
                isset($elem['options']) ? $elem['options'] : null)?>
          </td>
<?php
        } elseif ($elem['type'] == 'FILLER') {
            $intColspan = 1;
            ?>
          <td>&nbsp;</td>
<?php
        } elseif ($elem['type'] == 'HID_INT' || strstr($elem['type'], 'HID_')) {
            ?>
          <?php echo htmlFormElement($elem['name'], $elem['type'], $astrValues[$elem['name']], $elem['style'], $elem['listquery'], $fieldMode, $elem['parent_key'],$elem['label'])?>
<?php
        } elseif ($elem['type'] == 'IMAGE') {
            ?>
          <td class="image" colspan="<?php echo $intColspan?>">
            <?php echo htmlFormElement($elem['name'], $elem['type'], $astrValues[$elem['name']], $elem['style'], $elem['listquery'], $fieldMode, $elem['parent_key'],$elem['label'], [], isset($elem['elem_attributes']) ? $elem['elem_attributes'] : '', isset($elem['options']) ? $elem['options'] : null)?>
          </td>
<?php
        } elseif ($elem['type'] == 'IFORM') {
            if ($rowOpen)
                echo "        </tr>\n";
            echo "      </table>\n      </form>\n";
            $haveChildForm = true;
            createIForm($astrFormElements, $elem,
                isset($intKeyValue) ? $intKeyValue : 0, $blnNew, $strForm);
            break;
        } else {
            $value = $astrValues[$elem['name']];
            if ($elem['style'] == 'measurement')
                $value = $value ? miscRound2Decim($value, 2) : '';
            if ($elem['type'] == 'AREA') {
                ?>
          <td class="toplabel"><?php echo $elem['label']?></td>
<?php
            } else {
                ?>
          <td id="<?php echo htmlentities($elem['name']) . '_label' ?>"
                    class="label"
                    <?php if (isset($elem['title'])) echo ' title="' . $elem['title'] . '"'?>><?php echo $elem['label']?></td>
<?php
            }
            ?>
          <td class="field"
                    <?php echo $strColspan ? " $strColspan" : ''?>>
            <?php

            echo htmlFormElement($elem['name'], $elem['type'], $value,
                $elem['style'], $elem['listquery'], $fieldMode,
                isset($elem['parent_key']) ? $elem['parent_key'] : '', '', [],
                isset($elem['elem_attributes']) ? $elem['elem_attributes'] : '',
                isset($elem['options']) ? $elem['options'] : null);
            if (isset($elem['attached_elem']))
                echo '            ' . $elem['attached_elem'] . "\n";
            ?>
          </td>
<?php
        }
        $prevPosition = is_int($elem['position']) ? $elem['position'] : 0;
        if ($prevPosition == 0)
            $prevPosition = 255;
        $prevColSpan = $intColspan;
    }

    if (!$haveChildForm) {
        if ($rowOpen)
            echo "        </tr>\n";
        echo "      </table>\n      </form>\n";
    }
    if ($strForm == 'product') {
        // Special case for product: show stock balance change log
        ?>
      <div
                    class="iform ui-corner-tl ui-corner-bl ui-corner-br ui-corner-tr ui-helper-clearfix"
                    id="stock_balance_log">
                    <div
                        class="ui-corner-tl ui-corner-tr fg-toolbar ui-toolbar ui-widget-header"><?php echo $GLOBALS['locStockBalanceUpdates']?></div>
                    <table id="stock_balance_change_log">
                        <tr>
                            <th class="medium"><?php echo $GLOBALS['locHeaderChangeLogDateTime']?></th>
                            <th class="medium"><?php echo $GLOBALS['locHeaderChangeLogUser']?></th>
                            <th class="small"><?php echo $GLOBALS['locHeaderChangeLogAmount']?></th>
                            <th class="long"><?php echo $GLOBALS['locHeaderChangeLogDescription']?></th>
                        </tr>
                    </table>
                </div>
                </div>
<?php
    }
    ?>
  </div>

                <script type="text/javascript">
/* <![CDATA[ */
var globals = {};

$(window).bind('beforeunload', function(e) {
  if ($('.save_button').hasClass('ui-state-highlight') || $('.add_row_button').hasClass('ui-state-highlight'))
  {
    e.returnValue = "<?php echo $GLOBALS['locUnsavedData']?>";
    return "<?php echo $GLOBALS['locUnsavedData']?>";
  }
});

function showmsg(msg, timeout)
{
  $.floatingMessage("<span>" + msg + "</span>", {
    position: "top-right",
    className: "ui-widget ui-state-highlight",
    show: "show",
    hide: "fade",
    stuffEaseTime: 200,
    moveEaseTime: 0,
    time: typeof(timeout) != 'undefined' ? timeout : 5000
  });
}

function errormsg(msg, timeout)
{
  $.floatingMessage("<span>" + msg + "</span>", {
    position: "top-right",
    className: "ui-widget ui-state-error",
    show: "show",
    hide: "fade",
    stuffEaseTime: 200,
    moveEaseTime: 0,
    time: typeof(timeout) != 'undefined' ? timeout : 5000
  });
}

$(document).ready(function() {
<?php
    if ($strMessage) {
        ?>
  showmsg("<?php echo $strMessage?>");
<?php
    }
    if ($strErrorMessage) {
        ?>
  errormsg("<?php echo $strErrorMessage?>");
<?php
    }
    if ($strForm == 'product') {
        ?>
  update_stock_balance_log();
<?php
    }
    if (sesWriteAccess()) {
        ?>
  $('input[class~="hasCalendar"]').datepicker();
<?php
    }
    ?>
  $('#message').ajaxStart(function() {
    $('#spinner').css('visibility', 'visible');
  });
  $('#message').ajaxStop(function() {
    $('#spinner').css('visibility', 'hidden');
  });
  $('#errormsg').ajaxError(function(event, request, settings) {
    errormsg('Server request failed: ' + request.status + ' - ' + request.statusText);
    $('#spinner').css('visibility', 'hidden');
  });

  $('#admin_form').find('input[type="text"],input[type="hidden"],input[type="checkbox"],select,textarea').change(function() { $('.save_button').addClass('ui-state-highlight'); });
<?php
    if ($haveChildForm && !$blnNew) {
        ?>
  init_rows();
  $('#iform').find('input[type="text"],input[type="hidden"],input[type="checkbox"],select,textarea').change(function() { $('.add_row_button').addClass('ui-state-highlight'); });
<?php
    } elseif (isset($newLocation))
        echo "window.location='$newLocation';";
    if (isset($openWindow))
        echo "window.open('$openWindow');";
    ?>
});
<?php
    if ($haveChildForm && !$blnNew) {
        ?>
function init_rows_done()
{
<?php

        if (isset($newLocation))
            echo "window.location='$newLocation';"?>
}
<?php
    }
    ?>

function save_record(redirect_url, redir_style)
{
  var form = document.getElementById('admin_form');
  var obj = new Object();

<?php
    foreach ($astrFormElements as $elem) {
        if ($elem['name'] && !in_array($elem['type'],
            [
                'HID_INT',
                'SECHID_INT',
                'BUTTON',
                'JSBUTTON',
                'LABEL',
                'IMAGE',
                'NEWLINE',
                'ROWSUM',
                'CHECK',
                'IFORM'
            ])) {
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
  $.ajax({
    'url': "json.php?func=put_<?php echo $strJSONType?>",
    'type': 'POST',
    'dataType': 'json',
    'data': $.toJSON(obj),
    'contentType': 'application/json; charset=utf-8',
    'success': function(data) {
      if (data.warnings)
        alert(data.warnings);
      if (data.missing_fields)
      {
        errormsg('<?php echo $GLOBALS['locErrValueMissing']?>: ' + data.missing_fields);
      }
      else
      {
        $('.save_button').removeClass('ui-state-highlight');
        showmsg('<?php echo $GLOBALS['locRecordSaved']?>', 2000);
        if (redirect_url)
        {
          if (redir_style == 'openwindow')
            window.open(redirect_url);
          else
            window.location = redirect_url;
        }
        if (!obj.id)
        {
          obj.id = data.id;
          form.id.value = obj.id;
          if (!redirect_url || redir_style == 'openwindow')
          {
            var newloc = new String(window.location).split('#', 1)[0];
            window.location = newloc + '&id=' + obj.id;
          }
        }
      }
    },
    'error': function(XMLHTTPReq, textStatus, errorThrown) {
      if (XMLHTTPReq.status == 409) {
        errormsg(jQuery.parseJSON(XMLHTTPReq.responseText).warnings);
      }
      else if (textStatus == 'timeout')
        errormsg('Timeout trying to save data');
      else
        errormsg('Error trying to save data: ' + XMLHTTPReq.status + ' - ' + XMLHTTPReq.statusText);
      return false;
    }
  });
}

function popup_dialog(url, on_close, dialog_title, event, width, height)
{
  $("#popup_dlg").find("#popup_dlg_iframe").html('');
  $("#popup_dlg").dialog({ modal: true, width: width, height: height, resizable: true,
    buttons: {
      "<?php echo $GLOBALS['locClose']?>": function() { $("#popup_dlg").dialog('close'); }
    },
    title: dialog_title,
    close: function(event, ui) { eval(on_close); }
  }).find("#popup_dlg_iframe").attr("src", url);

  return true;
}

/* ]]> */
</script>

<?php
    createFormButtons($blnNew, $copyLinkOverride, false, $readOnlyForm);
    echo "  </div>\n";

    if ($addressAutocomplete && getSetting('address_autocomplete')) {
        ?>
  <script type="text/javascript">
  $(document).ready(function() {
  var s = document.createElement("script");
    s.type = "text/javascript";
    s.src  = "https://maps.googleapis.com/maps/api/js?sensor=false&libraries=places&callback=gmapsready";
    window.gmapsready = function(){
        initAddressAutocomplete("");
        initAddressAutocomplete("quick_");
    };
    $("head").append(s);
  });
  </script>
<?php
    }
}

function createIForm($astrFormElements, $elem, $intKeyValue, $newRecord, $strForm)
{
    ?>
      <div
                    class="iform <?php echo $elem['style']?> ui-corner-tl ui-corner-bl ui-corner-br ui-corner-tr ui-helper-clearfix"
                    id="<?php echo $elem['name']?>"
                    <?php echo $elem['elem_attributes'] ? ' ' . $elem['elem_attributes'] : ''?>>
                    <div
                        class="ui-corner-tl ui-corner-tr fg-toolbar ui-toolbar ui-widget-header"><?php echo $elem['label']?></div>
<?php
    if ($newRecord) {
        ?>
        <div id="inewmessage" class="new_message"><?php echo $GLOBALS['locSaveRecordToAddRows']?></div>
                </div>
<?php
        return;
    }
    ?>
<script type="text/javascript">
/* <![CDATA[ */

function init_rows()
{
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
        echo '  var arr_' . $subElem['name'] . ' = {"0":"-"';
        $res = mysqli_query_check($subElem['listquery']);
        $translate = strstr($subElem['style'], ' translated');
        while ($row = mysqli_fetch_row($res)) {
            if ($translate && isset($GLOBALS["loc{$row[1]}"])) {
                $row[1] = $GLOBALS["loc{$row[1]}"];
            }
            echo ',' . $row[0] . ':"' . addcslashes($row[1], '\"\/') . '"';
        }
        echo "};\n";
    }
    ?>
  $.getJSON('json.php?func=get_<?php echo $elem['name']?>&parent_id=<?php echo $intKeyValue?>', function(json) {
    $('#itable > tbody > tr[id!=form_row]').remove();
    var table = document.getElementById('itable');
    for (var i = 0; i < json.records.length; i++)
    {
      var record = json.records[i];
      var tr = $('<tr/>');
<?php
    foreach ($subFormElements as $subElem) {
        if (in_array($subElem['type'],
            [
                'HID_INT',
                'SECHID_INT',
                'BUTTON',
                'NEWLINE'
            ]
        )) {
            continue;
        }
        $name = $subElem['name'];
        $class = $subElem['style'];
        if ($subElem['type'] == 'LIST' || $subElem['type'] == 'SEARCHLIST') {
            echo "      if (record.${name}_text === null) record.${name}_text = ''; $('<td/>').addClass('$class' + (record.deleted == 1 ? ' deleted' : '')).text(record.${name}_text).appendTo(tr);\n";
        } elseif ($subElem['type'] == 'INT') {
            if (isset($subElem['decimals'])) {
                echo "      $('<td/>').addClass('$class' + (record.deleted == 1 ? ' deleted' : '')).text(record.$name ? format_currency(record.$name, {$subElem['decimals']}, '{$GLOBALS['locDecimalSeparator']}', '{$GLOBALS['locThousandSeparator']}') : '').appendTo(tr);\n";
            } else {
                echo "      $('<td/>').addClass('$class' + (record.deleted == 1 ? ' deleted' : '')).text(record.$name ? record.$name.replace('.', '{$GLOBALS['locDecimalSeparator']}') : '').appendTo(tr);\n";
            }
        } elseif ($subElem['type'] == 'INTDATE') {
            echo "      if (record.$name === null) record.$name = '';\n";
            echo "      $('<td/>').addClass('$class' + (record.deleted == 1 ? ' deleted' : '')).text(record.$name.substr(6, 2) + '.' + record.$name.substr(4, 2) + '.' + record.$name.substr(0, 4)).appendTo(tr);\n";
        } elseif ($subElem['type'] == 'CHECK') {
            echo "      $('<td/>').addClass('$class' + (record.deleted == 1 ? ' deleted' : '')).text(record.$name == 1 ? \"" .
                 $GLOBALS['locYesButton'] . '" : "' . $GLOBALS['locNoButton'] .
                 "\").appendTo(tr);\n";
        } elseif ($subElem['type'] == 'ROWSUM') {
            ?>
      var items = record.pcs;
      var price = record.price;
      var discount = record.discount || 0;
      var VATPercent = record.vat;
      var VATIncluded = record.vat_included;

      price *= (1 - discount / 100);
      var sum = 0;
      var sumVAT = 0;
      var VAT = 0;
      if (VATIncluded == 1)
      {
        sumVAT = round_number(items * price, 2);
        sum = round_number(sumVAT / (1 + VATPercent / 100), 2);
        VAT = sumVAT - sum;
      }
      else
      {
        sum = round_number(items * price, 2);
        VAT = round_number(sum * (VATPercent / 100), 2);
        sumVAT = sum + VAT;
      }
      sum = format_currency(sum, <?php echo isset($subElem['decimals']) ? $subElem['decimals'] : 2?>, '<?php echo $GLOBALS['locDecimalSeparator']?>', '<?php echo $GLOBALS['locThousandSeparator']?>');
      VAT = format_currency(VAT, <?php echo isset($subElem['decimals']) ? $subElem['decimals'] : 2?>, '<?php echo $GLOBALS['locDecimalSeparator']?>', '<?php echo $GLOBALS['locThousandSeparator']?>');
      sumVAT = format_currency(sumVAT, <?php echo isset($subElem['decimals']) ? $subElem['decimals'] : 2?>, '<?php echo $GLOBALS['locDecimalSeparator']?>', '<?php echo $GLOBALS['locThousandSeparator']?>');
      var title = '<?php echo $GLOBALS['locVATLess'] . ': '?>' + sum + ' &ndash; ' + '<?php echo $GLOBALS['locVATPart'] . ': '?>' + VAT;
      $('<td/>').addClass('<?php echo $class?>' + (record.deleted == 1 ? ' deleted' : '')).append('<span title="' + title + '">' + sumVAT + '<\/span>').appendTo(tr);
<?php
        } else {
            echo "      $('<td/>').addClass('$class' + (record.deleted == 1 ? ' deleted' : '')).text(record.$name ? record.$name : '').appendTo(tr);\n";
        }
    }
    if (sesWriteAccess()) {
        ?>
      $('<td/>').addClass('button').append('<a class="tinyactionlink row_edit_button rec' + record.id + '" href="#"><?php echo $GLOBALS['locEdit']?><\/a>').appendTo(tr);
      $('<td/>').addClass('button').append('<a class="tinyactionlink row_copy_button rec' + record.id + '" href="#"><?php echo $GLOBALS['locCopy']?><\/a>').appendTo(tr);
<?php
    }
    ?>
      $(table).append(tr);
    }
<?php
    if ($elem['name'] == 'invoice_rows') {
        ?>
    var totSum = 0;
    var totVAT = 0;
    var totSumVAT = 0;
    var partialPayments = 0;
    for (var i = 0; i < json.records.length; i++)
    {
      var record = json.records[i];

      if (record.partial_payment == 1) {
          partialPayments += parseFloat(record.price);
          continue;
      }

      var items = record.pcs;
      var price = record.price;
      var discount = record.discount;
      var VATPercent = record.vat;
      var VATIncluded = record.vat_included;

      price *= (1 - discount / 100);
      var sum = 0;
      var sumVAT = 0;
      var VAT = 0;
      if (VATIncluded == 1)
      {
        sumVAT = round_number(items * price, 2);
        sum = round_number(sumVAT / (1 + VATPercent / 100), 2);
        VAT = sumVAT - sum;
      }
      else
      {
        sum = round_number(items * price, 2);
        VAT = round_number(sum * (VATPercent / 100), 2);
        sumVAT = sum + VAT;
      }

      totSum += sum;
      totVAT += VAT;
      totSumVAT += sumVAT;
    }
    var tr = $('<tr/>').addClass('summary');
    $('<td/>').addClass('input').attr('colspan', '10').attr('align', 'right').text('<?php echo $GLOBALS['locTotalExcludingVAT']?>').appendTo(tr);
    $('<td/>').addClass('input').attr('align', 'right').text(format_currency(totSum, 2, '<?php echo $GLOBALS['locDecimalSeparator']?>', '<?php echo $GLOBALS['locThousandSeparator']?>')).appendTo(tr);
    $(table).append(tr);

    tr = $('<tr/>').addClass('summary');
    $('<td/>').addClass('input').attr('colspan', '10').attr('align', 'right').text('<?php echo $GLOBALS['locTotalVAT']?>').appendTo(tr);
    $('<td/>').addClass('input').attr('align', 'right').text(format_currency(totVAT, 2, '<?php echo $GLOBALS['locDecimalSeparator']?>', '<?php echo $GLOBALS['locThousandSeparator']?>')).appendTo(tr);
    $(table).append(tr);

    var tr = $('<tr/>').addClass('summary');
    $('<td/>').addClass('input').attr('colspan', '10').attr('align', 'right').text('<?php echo $GLOBALS['locTotalIncludingVAT']?>').appendTo(tr);
    $('<td/>').addClass('input').attr('align', 'right').text(format_currency(totSumVAT, 2, '<?php echo $GLOBALS['locDecimalSeparator']?>', '<?php echo $GLOBALS['locThousandSeparator']?>')).appendTo(tr);
    $(table).append(tr);

    var tr = $('<tr/>').addClass('summary');
    $('<td/>').addClass('input').attr('colspan', '10').attr('align', 'right').text('<?php echo $GLOBALS['locTotalToPay']?>').appendTo(tr);
    $('<td/>').addClass('input').attr('align', 'right').text(format_currency(totSumVAT + partialPayments, 2, '<?php echo $GLOBALS['locDecimalSeparator']?>', '<?php echo $GLOBALS['locThousandSeparator']?>')).appendTo(tr);
    $(table).append(tr);

<?php
    }
    ?>
    $('a[class~="row_edit_button"]').click(function(event) {
      var row_id = $(this).attr('class').match(/rec(\d+)/)[1];
      popup_editor(event, '<?php echo $GLOBALS['locRowModification']?>', row_id, false);
      return false;
    });

    $('a[class~="row_copy_button"]').click(function(event) {
      var row_id = $(this).attr('class').match(/rec(\d+)/)[1];
      popup_editor(event, '<?php echo $GLOBALS['locRowCopy']?>', row_id, true);
      return false;
    });

    $('a[class~="tinyactionlink"]').button();

    init_rows_done();
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
            if (!in_array($subElem['type'],
                [
                    'HID_INT',
                    'SECHID_INT',
                    'BUTTON',
                    'NEWLINE',
                    'ROWSUM',
                    'CHECK',
                    'INT'
                ])) {
                ?>
  obj.<?php echo $subElem['name']?> = document.getElementById(form_id + '_<?php echo $subElem['name']?>').value;
<?php
            } elseif ($subElem['type'] == 'CHECK') {
                ?>
  obj.<?php echo $subElem['name']?> = document.getElementById(form_id + '_<?php echo $subElem['name']?>').checked ? 1 : 0;
<?php
            } elseif ($subElem['type'] == 'INT') {
                ?>
  obj.<?php echo $subElem['name']?> = document.getElementById(form_id + '_<?php echo $subElem['name']?>').value.replace('<?php echo $GLOBALS['locDecimalSeparator']?>', '.');
<?php
            }
        }
        ?>  obj.<?php echo $elem['parent_key'] . " = $intKeyValue"?>;
  if (form.row_id)
    obj.id = form.row_id.value;
  $.ajax({
    'url': "json.php?func=put_<?php echo $formJSONType?>",
    'type': 'POST',
    'dataType': 'json',
    'data': $.toJSON(obj),
    'contentType': 'application/json; charset=utf-8',
    'success': function(data) {
      if (data.missing_fields)
      {
        errormsg('<?php echo $GLOBALS['locErrValueMissing']?>: ' + data.missing_fields);
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
            if (!in_array($subElem['type'],
                [
                    'HID_INT',
                    'SECHID_INT',
                    'BUTTON',
                    'NEWLINE',
                    'ROWSUM'
                ])
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
      if (textStatus == 'timeout')
        alert('Timeout trying to save row');
      else
        alert('Error trying to save row: ' + XMLHTTPReq.status + ' - ' + XMLHTTPReq.statusText);
      return false;
    }
  });
}

function update_row_dates(id)
{
  var buttons = new Object();
  buttons["<?php echo $GLOBALS['locUpdateRowDates']?>"] = function() {
    var date = $("#popup_date_edit_field").val();
    if (date == '') {
      alert('<?php echo $GLOBALS['locErrValueMissing'] ?>');
      return;
    }
    var params = {
      func: 'update_invoice_row_dates',
      id: <?php echo $intKeyValue?>,
      date: date
    };
    $.ajax({
      'url': 'json.php',
      'data': params,
      'type': 'GET',
      'dataType': 'json',
      'contentType': 'application/json; charset=utf-8',
      'success': function(data) {
        if (data.status != 'ok') {
          alert(data.errors);
        } else {
          $("#popup_date_edit").dialog('close');
          init_rows();
        }
      },
      'error': function(XMLHTTPReq, textStatus, errorThrown) {
        if (textStatus == 'timeout')
          errormsg('Timeout trying to update row dates');
        else
          errormsg('Error trying to update row dates: ' + XMLHTTPReq.status + ' - ' + XMLHTTPReq.statusText);
        return false;
      }
    });
  };
  buttons["<?php echo $GLOBALS['locClose']?>"] = function() { $("#popup_date_edit").dialog('close'); };
  $("#popup_date_edit").dialog({ modal: true, width: 420, height: 120, resizable: false,
    buttons: buttons,
    title: '<?php echo $GLOBALS['locUpdateAllRowDates'] ?>'
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
      if (textStatus == 'timeout')
        errormsg('Timeout trying to save row');
      else
        errormsg('Error trying to save row: ' + XMLHTTPReq.status + ' - ' + XMLHTTPReq.statusText);
      return false;
    }
  });
}

function popup_editor(event, title, id, copy_row)
{
  $.getJSON('json.php?func=get_<?php echo $formJSONType?>&id=' + id, function(json) {
    if (!json.id) return;
    var form = document.getElementById('iform_popup');

    if (copy_row)
      form.row_id.value = '';
    else
      form.row_id.value = id;
<?php
        foreach ($subFormElements as $subElem) {
            if (in_array($subElem['type'],
                [
                    'HID_INT',
                    'SECHID_INT',
                    'CONST_HID_INT',
                    'BUTTON',
                    'NEWLINE',
                    'ROWSUM'
                ]))
                continue;
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
      value = json.<?php echo $name?> ? json.<?php echo $name?>.replace('.', '<?php $GLOBALS['locDecimalSeparator']?>') : '';
    form.<?php echo "iform_popup_$name"?>.value = value;
<?php
                } else {
                    if (isset($subElem['decimals'])) {
                        ?>
    form.<?php echo "iform_popup_$name"?>.value = json.<?php echo $name?> ? format_currency(json.<?php echo $name?>, <?php echo $subElem['decimals']?>, '<?php echo $GLOBALS['locDecimalSeparator']?>', '<?php echo $GLOBALS['locThousandSeparator']?>') : '';
<?php
                    } else {
                        ?>
    form.<?php echo "iform_popup_$name"?>.value = json.<?php echo $name?> ? json.<?php echo $name?>.replace('.', '<?php echo $GLOBALS['locDecimalSeparator']?>') : '';
<?php
                    }
                }
            } elseif ($subElem['type'] == 'INTDATE') {
                ?>
    form.<?php echo "iform_popup_$name"?>.value = json.<?php echo $name?> ? json.<?php echo $name?>.substr(6, 2) + '.' + json.<?php echo $name?>.substr(4, 2) + '.' + json.<?php echo $name?>.substr(0, 4) : '';
<?php
            } elseif ($subElem['type'] == 'CHECK') {
                ?>
    form.<?php echo "iform_popup_$name"?>.checked = json.<?php echo $name?> != 0 ? true : false;
<?php
            } else {
                ?>
    form.<?php echo "iform_popup_$name"?>.value = json.<?php echo $name?>;
<?php
            }
        }
        ?>
    var buttons = new Object();
    buttons["<?php echo $GLOBALS['locSave']?>"] = function() { save_row('iform_popup'); };
    if (!copy_row)
      buttons["<?php echo $GLOBALS['locDelete']?>"] = function() { if(confirm('<?php echo $GLOBALS['locConfirmDelete']?>')==true) { delete_row('iform_popup'); } return false; };
    buttons["<?php echo $GLOBALS['locClose']?>"] = function() { $("#popup_edit").dialog('close'); };
    $("#popup_edit").dialog({ modal: true, width: 840, height: 150, resizable: false,
      buttons: buttons,
      title: title,
    });

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
    foreach ($subFormElements as $subElem) {
        if (!in_array($subElem['type'],
            [
                'HID_INT',
                'CONST_HID_INT',
                'SECHID_INT',
                'BUTTON',
                'NEWLINE'
            ])) {
            ?>
              <th
                                    class="label ui-state-default <?php echo strtolower($subElem['style'])?>_label"><?php echo $subElem['label']?></th>
<?php
        }
    }
    ?>
            </tr>
                        </thead>
                        <tbody>
<?php
    if (sesWriteAccess()) {
        ?>
            <tr id="form_row">
<?php
        foreach ($subFormElements as $subElem) {
            if (!in_array($subElem['type'],
                [
                    'HID_INT',
                    'CONST_HID_INT',
                    'SECHID_INT',
                    'BUTTON',
                    'NEWLINE',
                    'ROWSUM'
                ])) {
                $value = getFormDefaultValue($subElem, $intKeyValue);
                ?>
              <td
                                    class="label <?php echo strtolower($subElem['style'])?>_label">
                <?php echo htmlFormElement('iform_' . $subElem['name'], $subElem['type'], $value, $subElem['style'], $subElem['listquery'], 'MODIFY', 0, '', [], $subElem['elem_attributes'])?>
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
              <td class="button"><a
                                    class="tinyactionlink add_row_button" href="#"
                                    onclick="save_row('iform'); return false;"><?php echo $GLOBALS['locAddRow']?></a>
                                </td>
                                <td class="button"><a class="tinyactionlink update_row_dates"
                                    href="#" onclick="update_row_dates(); return false;"><?php echo $GLOBALS['locUpdateRowDates']?></a>
                                </td>
<?php
        } else {
            ?>
              <td class="button" colspan="2"><a
                                    class="tinyactionlink add_row_button" href="#"
                                    onclick="save_row('iform'); return false;"><?php echo $GLOBALS['locAddRow']?></a>
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
            if (!in_array($elem['type'],
                [
                    'HID_INT',
                    'CONST_HID_INT',
                    'SECHID_INT',
                    'BUTTON',
                    'NEWLINE',
                    'ROWSUM'
                ])) {
                ?>
            <td
                                    class="label <?php echo strtolower($elem['style'])?>_label">
              <?php echo $elem['label']?><br>
              <?php echo htmlFormElement('iform_popup_' . $elem['name'], $elem['type'], '', $elem['style'], $elem['listquery'], 'MODIFY', 0, '', [], $elem['elem_attributes'])?>
            </td>
<?php
            } elseif ($elem['type'] == 'SECHID_INT') {
                ?>
            <input type="hidden"
                                    name="<?php echo 'iform_popup_' . $elem['name']?>"
                                    value="<?php echo gpcStripSlashes($astrValues[$elem['name']])?>">
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
                <div id="popup_date_edit"
                    style="display: none; width: 300px; overflow: hidden">
                    <form method="post" name="form_date_popup" id="form_date_popup">
                        <input id="popup_date_edit_field" type="text"
                            class="medium hasCalendar">
                    </form>
                </div>
<?php
}

function createFormButtons($boolNew, $copyLinkOverride, $spinner, $readOnlyForm)
{
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
      <a class="actionlink save_button" href="#"
                        onclick="save_record(); return false;"><?php echo $GLOBALS['locSave']?></a>
<?php
    }

    if (!$boolNew) {
        $copyCmd = $copyLinkOverride ? "window.location='$copyLinkOverride'; return false;" : "document.getElementById('admin_form').copyact.value=1; document.getElementById('admin_form').submit(); return false;";
        ?>      <a class="actionlink" href="#"
                        onclick="<?php echo $copyCmd?>"><?php echo $GLOBALS['locCopy']?></a>
                    <a class="actionlink" href="#"
                        onclick="document.getElementById('admin_form').newact.value=1; document.getElementById('admin_form').submit(); return false;"><?php echo $GLOBALS['locNew']?></a>
<?php
        if (!$readOnlyForm) {
            ?>
      <a class="actionlink" href="#"
                        onclick="if(confirm('<?php echo $GLOBALS['locConfirmDelete']?>')==true) {  document.getElementById('admin_form').deleteact.value=1; document.getElementById('admin_form').submit(); return false;} else{ return false; }"><?php echo $GLOBALS['locDelete']?></a>
<?php
        }
    }
    if ($spinner)
        echo '     <span id="spinner" style="visibility: hidden"><img src="images/spinner.gif" alt=""></span>' .
             "\n";
    ?>
    </div>
<?php
}
