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
require_once 'form_funcs.php';

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
  
  $astrValues = getPostValues($astrFormElements, isset($intKeyValue) ? $intKeyValue : FALSE);
  
  $redirect = getRequest('redirect', null);
  if (isset($redirect))
  {
    // Redirect after save 
    foreach ($astrFormElements as $elem)
    {
      if ($elem['name'] == $redirect)
      {
        if ($elem['style'] == 'redirect')
          $newLocation = str_replace('_ID_', $intKeyValue, $elem['listquery']);
        elseif ($elem['style'] == 'openwindow')
          $openWindow = str_replace('_ID_', $intKeyValue, $elem['listquery']);
      }
    }
  }
  
  if ($blnSave) 
  { 
    $res = saveFormData($strTable, $intKeyValue, $astrFormElements, $astrValues);
    if ($res !== TRUE)
    {
      $strMessage .= $GLOBALS['locERRVALUEMISSING'] . ": $res<br>";
      unset($newLocation);
      unset($openWindow);
    }
    else
    {
      if (!$blnNew && getSetting('auto_close_form') && !isset($newLocation) && !isset($openWindow))
      {
        $qs = preg_replace('/&form=\w*/', '', $_SERVER['QUERY_STRING']);
        $qs = preg_replace('/&id=\w*/', '', $qs);
        header("Location: ". _PROTOCOL_ . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/index.php?$qs");
        return;
      }    
      $blnNew = FALSE;
      $blnInsertDone = TRUE;
    }
  }    
  elseif ($blnDelete && $intKeyValue) 
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
    $res = fetchRecord($strTable, &$intKeyValue, &$astrFormElements, &$astrValues);
    if ($res === 'deleted')
      $strMessage .= $GLOBALS['locDeletedRecord'] . '<br>';
    elseif ($res === 'notfound')
    {
      echo $GLOBALS['locENTRYDELETED']; 
      die;
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
  
  <?php createFormButtons($blnNew, $copyLinkOverride) ?>
  <div class="form_container">
  <table>
    <form method="post" action="" name="admin_form" id="admin_form">
    <input type="hidden" name="saveact" value="0">
    <input type="hidden" name="copyact" value="0">
    <input type="hidden" name="newact" value="<?php echo $blnNew ? 1 : 0?>">
    <input type="hidden" name="deleteact" value="0">
    <input type="hidden" name="redirect" id="redirect" value="">
    <input type="hidden" name="<?php echo $strPrimaryKey?>" value="<?php echo (isset($intKeyValue) && $intKeyValue) ? $intKeyValue : '' ?>">
<?php
  $formClosed = false;
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
      elseif ($elem['type'] == "IFORM" && !$blnNew) 
      {
        $formClosed = true;
        createIForm($astrFormElements, $elem, $intKeyValue);
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
        <?php if (isset($elem['quick_add'])) echo $elem['quick_add']?>
      </td>
<?php
      }
      
      if ($elem['position'] == 0 || $elem['position'] == 2) 
      {
        echo "    </tr>\n";
      }
    }
  }
  ?>
  </table>
  <?php if (!$formClosed) echo "  </form>\n"?>
  </div>
<script type="text/javascript">
$(document).ready(function() { 
<?php 
  if ($formClosed) 
  {
?>
  $('input[class~="hasCalendar"]').datepicker();
  $('#imessage').ajaxStart(function() {
    $('#spinner').css('visibility', 'visible');
  });
  $('#imessage').ajaxStop(function() {
    $('#spinner').css('visibility', 'hidden');
  });
  $('#imessage').ajaxError(function(event, request, settings) {
    alert('Server request failed: ' + request.status + ' - ' + request.statusText);
    $('#spinner').css('visibility', 'hidden');
  });
  init_rows();
<?php 
  } 
  elseif (isset($newLocation)) 
    echo "window.location='$newLocation';";
  if (isset($openWindow)) 
    echo "window.open('$openWindow');";
?>		
});
<?php 
  if ($formClosed) 
  {
?>
function init_rows_done()
{
<?php if (isset($newLocation)) 
  echo "window.location='$newLocation';"?>
}
<?php 
  }
?>
</script>
<?php 
  createFormButtons($blnNew, $copyLinkOverride);
}

function createIForm($astrFormElements, $elem, $intKeyValue)
{
?>
    </form>
    <tr>
      <td class="label" colspan="4">
        <?php echo $elem['label']?> <span id="spinner" style="visibility: hidden"><img src="images/spinner.gif" alt=""></span>
      </td>
    </tr>
    <tr>
      <td class="label" colspan="4">
<script type="text/javascript">

function init_rows()
{
<?php
  $subFormElements = getFormElements($elem['name']);
  $rowSumColumns = getFormRowSumColumns($elem['name']);
  foreach ($subFormElements as $subElem)
  {
    if ($subElem['type'] != 'LIST')
      continue;
    echo '  var arr_' . $subElem['name'] . ' = {"0":"-"';
    $res = mysql_query_check($subElem['listquery']);
    while ($row = mysql_fetch_row($res))
    {
      echo ',' . $row[0] . ':"' . $row[1] . '"';
    }
    echo "};\n";
  }
?> 
  $.getJSON('json.php?func=get_<?php echo $elem['name']?>&parent_id=<?php echo $intKeyValue?>', function(json) { 
    $('#itable > tbody > tr:gt(0)').remove();
    var table = document.getElementById('itable');
    for (var i = 0; i < json.records.length; i++)
    {
      var record = json.records[i];
      var tr = $('<tr></tr>');
<?php
  foreach ($subFormElements as $subElem)
  {
    if (in_array($subElem['type'], array('HID_INT', 'SECHID_INT', 'BUTTON', 'NEWLINE')))
      continue;
    $name = $subElem['name'];
    $class = $subElem['style'];
    if ($subElem['type'] == 'LIST')
    {
      echo "      if (record.${name} == null) record.${name} = 0; $('<td></td>').addClass('$class' + (record.deleted == 1 ? ' deleted' : '')).text(arr_${name}[record.${name}]).appendTo(tr);\n";
    }
    elseif ($subElem['type'] == 'INT')
    {
      echo "      $('<td></td>').addClass('$class' + (record.deleted == 1 ? ' deleted' : '')).text(record.$name ? record.$name.replace('.', ',') : '').appendTo(tr);\n";
    }
    elseif ($subElem['type'] == 'INTDATE')
    {
      echo "      $('<td></td>').addClass('$class' + (record.deleted == 1 ? ' deleted' : '')).text(record.$name.substr(6, 2) + '.' + record.$name.substr(4, 2) + '.' + record.$name.substr(0, 4)).appendTo(tr);\n";
    }
    elseif ($subElem['type'] == 'CHECK')
    {
      echo "      $('<td></td>').addClass('$class' + (record.deleted == 1 ? ' deleted' : '')).text(record.$name == 1 ? \"" . $GLOBALS['locYES'] . '" : "' . $GLOBALS['locNO'] . "\").appendTo(tr);\n";
    }
    elseif ($subElem['type'] == 'ROWSUM')
    {
?>          
      var items = record.<?php echo $rowSumColumns['multiplier']?>;
      var price = record.<?php echo $rowSumColumns['price']?>;
      var VATPercent = record.<?php echo $rowSumColumns['vat']?>;
      var VATIncluded = record.<?php echo $rowSumColumns['vat_included']?>;

      var sum = 0;
      var sumVAT = 0;
      var VAT = 0;
      if (VATIncluded == 1)
      {
        sumVAT = items * price;
        sum = sumVAT / (1 + VATPercent / 100);
        VAT = sumVAT - sum;
      }
      else
      {
        sum = items * price;
        VAT = sum * (VATPercent / 100);
        sumVAT = sum + VAT;
      }
      var title = '<?php echo $GLOBALS['locVATLESS'] . ': '?>' + sum.toFixed(2).replace('.', ',') + ' &ndash; ' + '<?php echo $GLOBALS['locVATPART'] . ': '?>' + VAT.toFixed(2).replace('.', ',');         
      $('<td></td>').addClass('<?php echo $class?>' + (record.deleted == 1 ? ' deleted' : '')).append('<span title="' + title + '">' + sumVAT.toFixed(2).replace('.', ',') + '</span').appendTo(tr); 
<?php          
    }
    else
    {
      echo "      $('<td></td>').addClass('$class' + (record.deleted == 1 ? ' deleted' : '')).text(record.$name).appendTo(tr);\n";
    }
  }
?>
      $('<td></td>').addClass('button').append('<a class="tinyactionlink row_edit_button rec' + record.id + '" href="#"><?php echo $GLOBALS['locEDIT']?></a>').appendTo(tr);
      $('<td></td>').addClass('button').append('<a class="tinyactionlink row_copy_button rec' + record.id + '" href="#"><?php echo $GLOBALS['locCOPY']?></a>').appendTo(tr);
      $(table).append(tr);
    }
<?php
  if (isset($rowSumColumns['show_summary']) && $rowSumColumns['show_summary'])
  {
?>
    var totSum = 0;
    var totVAT = 0;
    var totSumVAT = 0;
    for (var i = 0; i < json.records.length; i++)
    {
      var record = json.records[i];
      
      var items = record.<?php echo $rowSumColumns['multiplier']?>;
      var price = record.<?php echo $rowSumColumns['price']?>;
      var VATPercent = record.<?php echo $rowSumColumns['vat']?>;
      var VATIncluded = record.<?php echo $rowSumColumns['vat_included']?>;

      var sum = 0;
      var sumVAT = 0;
      var VAT = 0;
      if (VATIncluded == 1)
      {
        sumVAT = items * price;
        sum = sumVAT / (1 + VATPercent / 100);
        VAT = sumVAT - sum;
      }
      else
      {
        sum = items * price;
        VAT = sum * (VATPercent / 100);
        sumVAT = sum + VAT;
      }
      
      totSum += sum;
      totVAT += VAT;
      totSumVAT += sumVAT;
    }
    var tr = $('<tr></tr>').addClass('summary');
    $('<td></td>').addClass('input').attr('colspan', '9').attr('align', 'right').text('<?php echo $GLOBALS['locTOTALEXCLUDINGVAT']?>').appendTo(tr);
    $('<td></td>').addClass('input').attr('align', 'right').text(totSum.toFixed(2).replace('.', ',')).appendTo(tr);
    $(table).append(tr);
    
    tr = $('<tr></tr>').addClass('summary');
    $('<td></td>').addClass('input').attr('colspan', '9').attr('align', 'right').text('<?php echo $GLOBALS['locTOTALVAT']?>').appendTo(tr);
    $('<td></td>').addClass('input').attr('align', 'right').text(totVAT.toFixed(2).replace('.', ',')).appendTo(tr);
    $(table).append(tr);
    
    var tr = $('<tr></tr>').addClass('summary');
    $('<td></td>').addClass('input').attr('colspan', '9').attr('align', 'right').text('<?php echo $GLOBALS['locTOTALINCLUDINGVAT']?>').appendTo(tr);
    $('<td></td>').addClass('input').attr('align', 'right').text(totSumVAT.toFixed(2).replace('.', ',')).appendTo(tr);
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
    init_rows_done();
  });
}

function save_row(formId)
{
  var form = document.getElementById(formId);
  var obj = new Object();
<?php
  foreach ($subFormElements as $subElem)
  {
    if (!in_array($subElem['type'], array('HID_INT', 'SECHID_INT', 'BUTTON', 'NEWLINE', 'ROWSUM', 'CHECK')))
    {
?>
  obj.<?php echo $subElem['name']?> = form.<?php echo $subElem['name']?>.value;
<?php
    }
    elseif ($subElem['type'] == 'CHECK')
    {
?>
  obj.<?php echo $subElem['name']?> = form.<?php echo $subElem['name']?>.checked ? 1 : 0;
<?php
    }
  }
?>  obj.<?php echo $elem['parent_key'] . " = $intKeyValue"?>;
  obj.id = form.id.value;
  $('#imessage').text('').hide();
  $.ajax({
    'url': "json.php?func=put_<?php echo getFormJSONType($elem['name'])?>",
    'type': 'POST',
    'dataType': 'json',
    'data': $.toJSON(obj),
    'contentType': 'application/json; charset=utf-8',
    'success': function(data) {
      if (data.missing_fields)
      {
        if (formId == 'iform_popup')
          alert('<?php echo $GLOBALS['locERRVALUEMISSING']?>: ' + data.missing_fields);
        else
          $('#imessage').text('<?php echo $GLOBALS['locERRVALUEMISSING']?>: ' + data.missing_fields).show();
      }
      else
      {
        init_rows();
        if (formId == 'iform_popup')
          $("#popup_edit").dialog('close');
      }
      if (!obj.id)
      {
<?php
  foreach ($subFormElements as $subElem)
  {
    if (!in_array($subElem['type'], array('HID_INT', 'SECHID_INT', 'BUTTON', 'NEWLINE', 'ROWSUM', 'CHECK', 'LIST')))
    {
      if (strstr($subElem['default'], 'ADD'))
        echo '      form.' . $subElem['name'] . ".value = parseInt(form." . $subElem['name'] . ".value) + 5;\n";
    }
  }
?>      
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

function delete_row(formId)
{
  var form = document.getElementById(formId);
  var id = form.id.value;
  $('#imessage').text('').hide();
  $.ajax({
    'url': "json.php?func=delete_<?php echo getFormJSONType($elem['name'])?>&id=" + id,
    'type': 'GET',
    'dataType': 'json',
    'contentType': 'application/json; charset=utf-8',
    'success': function(data) {
      init_rows();
      if (formId == 'iform_popup')
        $("#popup_edit").dialog('close');
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

function popup_editor(event, title, id, copy_row)
{
  $.getJSON('json.php?func=get_<?php echo getFormJSONType($elem['name'])?>&id=' + id, function(json) { 
    if (!json.id) return; 
    var form = document.getElementById('iform_popup');
    
    if (copy_row)
      form.id.value = '';
    else
      form.id.value = id;
<?php
  foreach ($subFormElements as $elem)
  {
    if (in_array($elem['type'], array('HID_INT', 'SECHID_INT', 'BUTTON', 'NEWLINE', 'ROWSUM')))
      continue;
    $name = $elem['name'];
    if ($elem['type'] == 'LIST')
    {
?>
    for (var i = 0; i < form.<?php echo $name?>.options.length; i++)
    {  
      var item = form.<?php echo $name?>.options[i];
      if (item.value == json.<?php echo $name?>)
      {
        item.selected = true;
        break;
      }
    }
<?php
    }
    elseif ($elem['type'] == 'INT')
    {
?> 
    form.<?php echo $name?>.value = json.<?php echo $name?> ? json.<?php echo $name?>.replace('.', ',') : '';
<?php
    }
    else
    {
?> 
    form.<?php echo $name?>.value = json.<?php echo $name?>;
<?php
    }
  }
?>    
    $("#popup_edit").dialog({ modal: true, width: 810, height: 150, resizable: false, 
      buttons: {
          "<?php echo $GLOBALS['locSAVE']?>": function() { save_row('iform_popup'); },
          "<?php echo $GLOBALS['locDELETE']?>": function() { if(confirm('<?php echo $GLOBALS['locCONFIRMDELETE']?>')==true) { delete_row('iform_popup'); } return false; },
          "<?php echo $GLOBALS['locCLOSE']?>": function() { $("#popup_edit").dialog('close'); }
      },
      title: title,
    });

  });
}  

</script>

        <div class="iform <?php echo $elem['style']?>" id="<?php echo $elem['name']?>" name="<?php echo $elem['name']?>"<?php echo $elem['elem_attributes'] ? ' ' . $elem['elem_attributes'] : ''?>>

          <span id="imessage" class="message" style="display: none"></span>
        
          <form method="post" action="#" name="iform" id="iform">
          <table class="iform" id="itable">
            <tr id="form_row">
<?php
  $strRowSpan = '';
  foreach ($subFormElements as $subElem)
  {
    if (!in_array($subElem['type'], array('HID_INT', 'SECHID_INT', 'BUTTON', 'NEWLINE', 'ROWSUM')))
    { 
      $value = getFormDefaultValue($subElem, $intKeyValue);
?>
              <td class="label <?php echo strtolower($subElem['style'])?>_label">
                <?php echo $subElem['label']?><br>
                <?php echo htmlFormElement($subElem['name'], $subElem['type'], $value, $subElem['style'], $subElem['listquery'], "MODIFY", 0, '', array(), $subElem['elem_attributes'])?>
              </td>
<?php
    }
    elseif ($subElem['type'] == 'ROWSUM') 
    {
?>
              <td class="label <?php echo strtolower($subElem['style'])?>_label">
                <?php echo $subElem['label']?><br>
              </td>
<?php
    }
  }
?>
              <td class="button" <?php echo $strRowSpan?>>
                <br>
                <input type="hidden" name="addact" value="0">
                <a class="tinyactionlink" href="#" onclick="save_row('iform'); return false;"><?php echo $GLOBALS['locADDROW']?></a>
              </td>
            </tr>
          </table>
          </form>
        </div>
        <div id="popup_edit" style="display: none; width: 900px; overflow: hidden">
        <form method="post" action="" name="iform_popup" id="iform_popup">
        <input type="hidden" name="id" value="">
        <input type="hidden" name="<?php echo $strParentKey?>" value="<?php echo $intKeyValue?>">
        <table class="iform">
          <tr>
<?php
  $strRowSpan = '';
  foreach ($subFormElements as $elem)
  {
    if (!in_array($elem['type'], array('HID_INT', 'SECHID_INT', 'BUTTON', 'NEWLINE', 'ROWSUM')))
    {
?>
            <td class="label <?php echo strtolower($elem['style'])?>_label">
              <?php echo $elem['label']?><br>
              <?php echo htmlFormElement($elem['name'], $elem['type'], '', $elem['style'], $elem['listquery'], "MODIFY", 0, '', array(), $elem['elem_attributes'])?>
            </td>
<?php
    }
    elseif( $elem['type'] == 'SECHID_INT' ) 
    {
?>
            <input type="hidden" name="<?php echo $elem['name']?>" value="<?php echo gpcStripSlashes($astrValues[$elem['name']])?>">
<?php
    }
    elseif( $elem['type'] == 'BUTTON' ) 
    {
?>
            <td class="label">
              &nbsp;
            </td>
<?php
    }
  }
?>
          </tr>
        </table>
        </form>
        </div>
      </td>
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
