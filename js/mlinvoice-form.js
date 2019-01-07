/* global MLInvoice, $, google */
MLInvoice.addModule('Form', function mlinvoiceForm() {
  var _formConfig = {};
  var _subFormConfig = {};
  var _listItems = {};

  function initForm(formConfig, subFormConfig, listItems)
  {
    _formConfig = formConfig;
    _subFormConfig = subFormConfig;
    _listItems = listItems;

    _formConfig.modificationWarningShown = false;

    $('#admin_form')
      .find('input[type="text"]:not([name="payment_date"]),input[type="hidden"],input[type="checkbox"]:not([name="archived"]),select:not(.dropdownmenu),textarea')
      .one('change', startChanging);

    $('.save_button').click(function onClickSave() {
      MLInvoice.Form.saveRecord();
      return false;
    });

    $('#base_id').change(updateBaseDefaults);
    $('#state_id').change(updateBaseDefaults);
  }

  function initAddressAutocomplete(prefix)
  {
    var input = document.getElementById(prefix + 'street_address');
    if (input === null) {
      return;
    }
    $(input).attr('placeholder', '');
    $(input).blur(function onBlur() {
      var val = $(input).val();
      setTimeout(function deferInputUpdate() {
        $(input).val(val);
      }, 0);
    });

    var options = {
      types: ['geocode']
    };
    var autocomplete = new google.maps.places.Autocomplete(input, options);

    google.maps.event.addListener(autocomplete, 'place_changed', function onPlaceChanged() {
      var place = autocomplete.getPlace();
      setTimeout(
        function deferPlaceUpdate() {
          $('#' + prefix + 'street_address').val(place.name);
          $.each(place.address_components, function handlePlace(index, component) {
            if ($.inArray('postal_code', component.types) >= 0) {
              $('#' + prefix + 'zip_code').val(component.long_name).trigger('change');
            } else if ($.inArray('locality', component.types) >= 0 || $.inArray('administrative_area_level_3', component.types) >= 0) {
              $('#' + prefix + 'city').val(component.long_name).trigger('change');
            } else if ($.inArray('country', component.types) >= 0) {
              $('#' + prefix + 'country').val(component.long_name).trigger('change');
            }
          });
        },
        0
      );
    });
  }

  function saveRecord(redirectUrl, redirectStyle, onPrint)
  {
    MLInvoice.clearMessages();
    var $form = $('#admin_form');
    var formdata = new FormData();
    $.each(_formConfig.fields, function processField(i, field) {
      var value = $form.find('[name=' + field.name + ']');
      switch (field.type) {
      case 'CHECK':
        formdata.append(field.name, value.prop('checked'));
        break;
      case 'INT':
        formdata.append(field.name, value.val().replace(MLInvoice.translate('DecimalSeparator'), '.'));
        break;
      case 'FILE':
        if (value.get(0).files.length > 0) {
          formdata.append(field.name, value.get(0).files[0]);
        }
        break;
      case 'SEARCHLIST':
      case 'INTDATE':
      case 'LIST':
      case 'TEXT':
      case 'AREA':
        formdata.append(field.name, value.val());
        break;
      }
    });
    formdata.append('id', $form.find('#record_id').val());
    if (typeof onPrint !== 'undefined') {
      formdata.append('onPrint', onPrint);
    }
    $.ajax({
      'url': 'json.php?func=put_' + _formConfig.type,
      'type': 'POST',
      'dataType': 'json',
      'data': formdata,
      'processData': false,
      'contentType': false,
      'success': function onSaveFormSuccess(data) {
        if (data.warnings) {
          alert(data.warnings);
        }
        if (data.missing_fields) {
          MLInvoice.errormsg(MLInvoice.translate('ErrValueMissing') + ': ' + data.missing_fields);
        } else {
          if ('invoice' === _formConfig.type) {
            if (typeof onPrint !== 'undefined' && onPrint) {
              $('input#invoice_no').val(data.invoice_no);
              $('input#ref_number').val(data.ref_number);
            }
          }
          $('.save_button').removeClass('ui-state-highlight');
          MLInvoice.infomsg(MLInvoice.translate('RecordSaved'), 2000);
          if (redirectUrl) {
            if ('openwindow' === redirectStyle) {
              window.open(redirectUrl);
            } else {
              window.location = redirectUrl;
            }
          }
          if (!$form.find('#record_id').val()) {
            $form.find('#record_id').val(data.id);
            if (!redirectUrl || 'openwindow' === redirectStyle) {
              var newloc = new String(window.location).split('#', 1)[0];
              window.location = newloc + '&id=' + data.id;
            }
          }
        }
      }
    });
  }

  function initRows(doneCallback)
  {
    $('.cb-select-all').prop('checked', false);
    var func = '';
    switch (_subFormConfig.type) {
    case 'company':
      func = 'get_companies';
      break;
    case 'company_contact':
      func = 'get_company_contacts';
      break;
    case 'invoice_row':
      func = 'get_invoice_rows';
      break;
    case 'send_api_config':
      func = 'get_send_api_configs';
      break;
    }
    var readOnly = _formConfig.readOnly;
    var formType = _formConfig.type;
    var parentId = _formConfig.id;
    var subFormConfig = _subFormConfig;
    var listItems = _listItems;
    var that = this;
    $.getJSON('json.php?func=' + func + '&parent_id=' + _formConfig.id, function handleRows(json) {
      var $table = $('#itable');
      $('#itable > tbody > tr[id!=form_row]').remove();
      $.each(json.records, function addItemRow(i, record) {
        var tr = $('<tr/>').addClass('item-row');
        if (!readOnly && 'invoice' === formType) {
          $('<td class="sort-col"><span class="sort-handle hidden">&#x25B2;&#x25BC;</span>')
            .appendTo(tr);
          var selectRow = MLInvoice.translate('SelectRow');
          var input = $('<input/>')
            .addClass('cb-select-row')
            .attr('type', 'checkbox')
            .attr('title', selectRow)
            .attr('aria-label', selectRow)
            .click(function selectRowClick() { MLInvoice.updateRowSelectedState($(this).closest('.list_container')); });
          input.val(record.id);
          var tdSelect = $('<td class="select-row"/>');
          tdSelect.append(input);
          tr.append(tdSelect);
        }

        $.each(subFormConfig.fields, function eachField(idx, field) {
          var td = $('<td/>').addClass(field.style + (record.deleted ? ' deleted' : ''));
          td.data('field', field.name);
          var fieldData = record[field.name];
          switch (field.type) {
          case 'LIST':
          case 'SEARCHLIST':
            var textFieldName = field.name + '_text';
            var fieldText = '';
            if ((typeof record[textFieldName] === 'undefined' || null === record[textFieldName]) &&
              typeof listItems[field.name][fieldData] !== 'undefined'
            ) {
              fieldText = listItems[field.name][fieldData];
            } else {
              fieldText = record[textFieldName];
            }
            if ('invoice_row' === subFormConfig.type && 'product_id' === field.name) {
              if (fieldData !== null) {
                var link = $('<a/>').attr('href', '?func=settings&list=product&form=product&listid=list_product&id=' + record[field.name])
                  .text(fieldText);
                td.append(link);
              }
            } else {
              td.text(fieldText);
            }
            td.appendTo(tr);
            break;
          case 'PASSWD_STORED':
            td.text('');
            td.appendTo(tr);
            break;
          case 'INT':
            if (typeof field.decimals !== 'undefined') {
              td.text(fieldData ? MLInvoice.formatCurrency(fieldData, field.decimals) : '');
            } else {
              td.text(fieldData ? String(fieldData).replace('.', MLInvoice.translate('DecimalSeparator')) : '');
            }
            td.appendTo(tr);
            break;
          case 'INTDATE':
            td.text(fieldData !== null ? MLInvoice.formatDate(fieldData) : '');
            td.appendTo(tr);
            break;
          case 'CHECK':
            td.text(MLInvoice.translate(fieldData ? 'YesButton' : 'NoButton'));
            td.appendTo(tr);
            break;
          case 'ROWSUM':
            var rowSum = MLInvoice.calcRowSum(record);
            var sum = MLInvoice.formatCurrency(rowSum.sum);
            var VAT = MLInvoice.formatCurrency(rowSum.VAT);
            var sumVAT = MLInvoice.formatCurrency(rowSum.sumVAT);
            var title = MLInvoice.translate('VATLess') + ': ' + sum + ' &ndash; ' + MLInvoice.translate('VATPart') + ': ' + VAT;
            var sumSpan = $('<span/>').attr('title', title).text(sumVAT);
            td.append(sumSpan);
            td.appendTo(tr);
            break;
          case 'TAGS':
            var val = fieldData ? String(fieldData) : '';
            val = val.replace(new RegExp(/,/, 'g'), ', ');
            td.text(val);
            td.appendTo(tr);
            break;
          case 'TEXT':
          case 'AREA':
            td.text(fieldData ? fieldData : '');
            td.appendTo(tr);
            break;
          }
        });

        if (!readOnly) {
          var editButton = $('<a/>').addClass('tinyactionlink ui-button ui-corner-all ui-widget row-edit-button')
            .attr('href', '#')
            .text(MLInvoice.translate('Edit'))
            .click(function editRowClick(event) {
              that.popupEditor(event, MLInvoice.translate('RowModification'), record.id, false);
              return false;
            });
          $('<td/>').addClass('button')
            .append(editButton)
            .appendTo(tr);

          var copyButton = $('<a/>').addClass('tinyactionlink ui-button ui-corner-all ui-widget row-copy-button')
            .attr('href', '#')
            .data('id', parentId)
            .text(MLInvoice.translate('Copy'))
            .click(function copyRowClick(event) {
              that.popupEditor(event, MLInvoice.translate('RowCopy'), record.id, true);
              return false;
            });

          $('<td/>').addClass('button')
            .append(copyButton)
            .appendTo(tr);
        }
        $table.append(tr);
      });

      if ('invoice_row' === subFormConfig.type) {
        var summary = MLInvoice.calculateInvoiceRowSummary(json.records);
        var trSummary = $('<tr/>').addClass('summary');
        var modifyCol = $('<td/>').addClass('input').attr('colspan', '6').attr('rowspan', '2');
        if (!readOnly) {
          modifyCol.text(MLInvoice.translate('ForSelected') + ': ');
          $('<button/>')
            .attr('id', 'delete-selected-rows')
            .addClass('selected-row-button ui-button ui-corner-all ui-widget')
            .text(MLInvoice.translate('Delete'))
            .click(function deleteSelectedClick() {
              that.deleteSelectedRows();
              return false;
            })
            .appendTo(modifyCol);
          modifyCol.append($('<span/>').text(' '));
          $('<button/>')
            .attr('id', 'update-selected-rows')
            .addClass('selected-row-button ui-button ui-corner-all ui-widget')
            .text(MLInvoice.translate('Modify'))
            .click(function updateSelectedClick(event) {
              that.multiEditor(event, MLInvoice.translate('ModifySelectedRows'));
              return false;
            })
            .appendTo(modifyCol);
        }
        modifyCol.appendTo(trSummary);

        $('<td/>').addClass('input').attr('colspan', '6').attr('align', 'right').text(MLInvoice.translate('TotalExcludingVAT')).appendTo(trSummary);
        $('<td/>').addClass('input currency').attr('align', 'right').text(MLInvoice.formatCurrency(summary.totSum)).appendTo(trSummary);
        $('<td/>').attr('colspan', '2').appendTo(trSummary);
        $table.append(trSummary);

        trSummary = $('<tr/>').addClass('summary');
        $('<td/>').addClass('input').attr('colspan', '6').attr('align', 'right').text(MLInvoice.translate('TotalVAT')).appendTo(trSummary);
        $('<td/>').addClass('input currency').attr('align', 'right').text(MLInvoice.formatCurrency(summary.totVAT)).appendTo(trSummary);
        $('<td/>').attr('colspan', '2').appendTo(trSummary);
        $table.append(trSummary);

        trSummary = $('<tr/>').addClass('summary');
        $('<td/>').addClass('input').attr('colspan', '12').attr('align', 'right').text(MLInvoice.translate('TotalIncludingVAT')).appendTo(trSummary);
        $('<td/>').addClass('input currency').attr('align', 'right').text(MLInvoice.formatCurrency(summary.totSumVAT)).appendTo(trSummary);
        $('<td/>').attr('colspan', '2').appendTo(trSummary);
        $table.append(trSummary);

        trSummary = $('<tr/>').addClass('summary');
        $('<td/>').addClass('input').attr('colspan', '12').attr('align', 'right').text(MLInvoice.translate('TotalToPay')).appendTo(trSummary);
        $('<td/>').addClass('input currency').attr('align', 'right').text(MLInvoice.formatCurrency(summary.totSumVAT + summary.partialPayments)).appendTo(trSummary);
        $('<td/>').attr('colspan', '2').appendTo(trSummary);
        $table.append(trSummary);

        if (summary.totWeight > 0) {
          trSummary = $('<tr/>').addClass('summary');
          $('<td/>').addClass('input').attr('colspan', '12').attr('align', 'right').text(MLInvoice.translate('ProductWeight')).appendTo(trSummary);
          $('<td/>').addClass('input currency').attr('align', 'right').text(MLInvoice.formatCurrency(summary.totWeight, 3)).appendTo(trSummary);
          $('<td/>').attr('colspan', '2').appendTo(trSummary);
          $table.append(trSummary);
        }
      }
      MLInvoice.updateRowSelectedState();

      $('#itable tr')
        .mouseover(function onTrMouseOver() { $(this).find('.sort-handle').removeClass('hidden'); })
        .mouseout(function onTrMouseOut() { $(this).find('.sort-handle').addClass('hidden'); });

      if (!readOnly && 'invoice' === formType) {
        $('#itable > tbody').sortable({
          axis: 'y',
          handle: '.sort-col',
          items: 'tr.item-row',
          stop: function onSortStop() {
            that.updateRowOrder();
          }
        });
      }

      $('#iform')
        .find('input[type="text"],input[type="hidden"],input[type="checkbox"]:not(.cb-select-row):not(.cb-select-all),select:not(.dropdownmenu),textarea')
        .change(function onRowFieldChange() { $('.row-add-button').addClass('ui-state-highlight'); });
      $('#iform')
        .find('input[type="text"],input[type="hidden"],input[type="checkbox"],select:not(.dropdownmenu),textarea')
        .one('change', startChanging);

      $('#iform_popup')
        .find('input[type="text"],input[type="hidden"]:not(.select-default-text),input[type="checkbox"],select:not(.dropdownmenu),textarea')
        .change(function onPopupFieldChange() {
          $(this).parent().find('.modification-indicator').removeClass('hidden');
          $(this).data('modified', 1);
        });

      if (typeof doneCallback !== 'undefined') {
        doneCallback();
      }

      if (subFormConfig.dispatchByDateButtons) {
        MLInvoice.updateDispatchByDateButtons();
      }
    });
  }

  function saveRow(formId)
  {
    MLInvoice.clearMessages();
    var form = $('#' + formId);
    var obj = {};
    $.each(_subFormConfig.fields, function processField(i, field) {
      var value = form.find('[name=' + formId + '_' + field.name + ']');
      switch (field.type) {
      case 'CHECK':
        obj[field.name] = value.prop('checked');
        break;
      case 'INT':
        obj[field.name] = value.val().replace(MLInvoice.translate('DecimalSeparator'), '.');
        break;
      case 'TAGS':
      case 'SEARCHLIST':
      case 'INTDATE':
      case 'LIST':
      case 'TEXT':
      case 'AREA':
        obj[field.name] = value.val();
        break;
      }
    });
    obj[_subFormConfig.parentKey] = _formConfig.id;
    var rowId = form.data('rowId');
    if (rowId) {
      obj.id = rowId;
    }
    var subFormConfig = _subFormConfig;
    var that = this;
    $.ajax({
      'url': 'json.php?func=put_' + _subFormConfig.type,
      'type': 'POST',
      'dataType': 'json',
      'data': JSON.stringify(obj),
      'contentType': 'application/json; charset=utf-8',
      'success': function onSaveSuccess(data) {
        if (data.error) {
          MLInvoice.errormsg(data.error);
          return;
        }
        if (data.missing_fields) {
          MLInvoice.errormsg(MLInvoice.translate('ErrValueMissing') + ': ' + data.missing_fields);
        } else {
          MLInvoice.infomsg(MLInvoice.translate('RecordSaved'), 2000);
          form.find('.row-add-button').removeClass('ui-state-highlight');
          that.initRows();
          if (form.data('popup')) {
            $('#popup_edit').dialog('close');
          }
          if (!obj.id) {
            if (subFormConfig.onAfterRowAdded !== '') {
              subFormConfig.onAfterRowAdded();
            }
            $.each(subFormConfig.fields, function updateAfterSave(i, field) {
              var value = form.find('[name=' + field.name + ']');
              if (typeof field.default !== 'undefined' && String(field.default).indexOf('ADD') !== -1) {
                value.val(parseInt(value.val(), 10) + 5);
              } else if (subFormConfig.clearAfterRowAdded) {
                switch (field.type) {
                case 'LIST':
                  value.val([]);
                  break;
                case 'SEARCHLIST':
                  value.select2('val', '');
                  break;
                case 'CHECK':
                  value.prop('checked', false);
                  break;
                case 'INT':
                case 'INTDATE':
                case 'TEXT':
                case 'AREA':
                  value.val('');
                  break;
                }
              }
            });
          }
        }
      }
    });
  }

  function modifyRows(formId)
  {
    MLInvoice.clearMessages();
    var form = $('#' + formId);
    var obj = {};
    $.each(_subFormConfig.fields, function processField(i, field) {
      var elem = form.find('[name=' + formId + '_' + field.name + ']');
      if (!elem.data('modified')) {
        return;
      }
      switch (field.type) {
      case 'CHECK':
        obj[field.name] = elem.prop('checked');
        break;
      case 'INT':
        obj[field.name] = elem.val().replace(MLInvoice.translate('DecimalSeparator'), '.');
        break;
      case 'SEARCHLIST':
        obj[field.name] = elem.select2('data');
        break;
      case 'LIST':
      case 'INTDATE':
      case 'TEXT':
      case 'AREA':
        obj[field.name] = elem.val();
        break;
      }
    });

    var req = {};
    req.table = _subFormConfig.type;
    req.ids = $('#iform .cb-select-row:checked').map(
      function mapChecked() { return this.value; }
    ).get();
    req.changes = obj;
    $.ajax({
      'url': 'json.php?func=update_multiple',
      'type': 'POST',
      'dataType': 'json',
      'data': JSON.stringify(req),
      'contentType': 'application/json; charset=utf-8',
      'success': function onUpdateMultipleSuccess(data) {
        if (data.missing_fields) {
          MLInvoice.errormsg(MLInvoice.translate('ErrValueMissing') + ': ' + data.missing_fields);
        } else {
          $("#popup_edit").dialog('close');
          MLInvoice.Form.initRows();
        }
      }
    });
  }

  function updateRowOrder()
  {
    var req = {};
    req.table = _subFormConfig.type;
    req.order = {};
    var orderno = 5;
    $('.cb-select-row').each(function eachRow() {
      req.order[this.value] = orderno;
      orderno += 5;
    });
    $.ajax({
      'url': 'json.php?func=update_row_order',
      'type': 'POST',
      'dataType': 'json',
      'data': JSON.stringify(req),
      'contentType': 'application/json; charset=utf-8',
      'success': function onUpdateRowOrderSuccess() {
        MLInvoice.Form.initRows();
      }
    });
  }

  function deleteSelectedRows()
  {
    var req = {};
    req.id = $('.cb-select-row:checked').map(
      function mapIds() { return this.value; }
    ).get();
    $.ajax({
      'url': 'json.php?func=delete_' + _subFormConfig.type,
      'type': 'POST',
      'dataType': 'json',
      'data': req,
      'success': function onDeleteSelectedSuccess() {
        MLInvoice.Form.initRows();
      }
    });
  }

  function deleteRow(formId)
  {
    var form = $('#' + formId);
    var id = form.data('rowId');
    $.ajax({
      'url': 'json.php?func=delete_' + _subFormConfig.type + '&id=' + id,
      'type': 'GET',
      'dataType': 'json',
      'contentType': 'application/json; charset=utf-8',
      'success': function onDeleteSuccess() {
        MLInvoice.Form.initRows();
        if (formId == 'iform_popup') {
          $('#popup_edit').dialog('close');
        }
      }
    });
  }

  function popupEditor(event, title, id, copyRow)
  {
    startChanging();
    $('#iform_popup .modification-indicator').addClass('hidden');
    $('#iform_popup input').data('modified', '');
    var subFormConfig = _subFormConfig;
    var that = this;
    $.getJSON('json.php?func=get_' + _subFormConfig.type + '&id=' + id, function initPopupEditor(json) {
      if (!json.id) {
        return;
      }
      var form = $('#iform_popup');

      if (copyRow) {
        form.data('rowId', '');
      } else {
        form.data('rowId', id);
      }
      $.each(subFormConfig.fields, function initPopupFields(i, field) {
        var elem = form.find('[name=iform_popup_' + field.name + ']');
        switch (field.type) {
        case 'CHECK':
          elem.prop('checked', json[field.name] ? 1 : 0);
          break;
        case 'INT':
          if (copyRow && field.default && field.default.indexOf('ADD') !== -1) {
            elem.val(parseInt(json[field.name], 10) + 5);
          } else if (typeof field.decimals !== 'undefined') {
            elem.val(json[field.name] ? MLInvoice.formatCurrency(json[field.name], field.decimals) : '');
          } else {
            elem.val(json[field.name] ? String(json[field.name]).replace('.', MLInvoice.translate('DecimalSeparator')) : '');
          }
          break;
        case 'SEARCHLIST':
          var item = {
            id: json[field.name],
            text: json[field.name + '_text']
          };
          elem.select2('data', item);
          break;
        case 'INTDATE':
          elem.val(json[field.name] ? MLInvoice.formatDate(json[field.name]) : '');
          break;
        case 'PASSWD_STORED':
          elem.val('');
          break;
        case 'TAGS':
          var items = [];
          $(json[field.name].split(',')).each(function eachTag() {
            items.push({id: this, text: this});
          });
          elem.select2('data', items);
          break;
        case 'LIST':
        case 'TEXT':
        case 'AREA':
          elem.val(json[field.name]);
          break;
        }
      });
      MLInvoice.setupSelect2($('#popup_edit'));

      var buttons = {};
      buttons[MLInvoice.translate('Save')] = function onClickSave() {
        that.saveRow('iform_popup');
      };
      if (!copyRow) {
        buttons[MLInvoice.translate('Delete')] = function onClickDelete() {
          if (confirm(MLInvoice.translate('ConfirmDelete')) === true) {
            that.deleteRow('iform_popup');
          }
          return false;
        };
      }
      buttons[MLInvoice.translate('Close')] = function onClickClose() {
        $('#popup_edit').dialog('close');
      };
      $('#popup_edit').dialog({
        modal: true,
        width: subFormConfig.popupWidth,
        height: 180,
        resizable: true,
        buttons: buttons,
        title: title
      });
    });
  }

  function multiEditor(event, title)
  {
    startChanging();
    $('#iform_popup .modification-indicator').addClass('hidden');
    $('#iform_popup input').data('modified', 0);
    $('#iform_popup select').data('modified', 0);
    var form = $('#iform_popup');
    $.each(_subFormConfig.fields, function initPopupFields(i, field) {
      var elem = form.find('[name=iform_popup_' + field.name + ']');
      switch (field.type) {
      case 'CHECK':
        elem.prop('checked', false);
        break;
      case 'SEARCHLIST':
        elem.select2('data', {});
        break;
      case 'TAGS':
        elem.select2('data', []);
        break;
      case 'PASSWD_STORED':
      case 'INT':
      case 'INTDATE':
      case 'LIST':
      case 'TEXT':
      case 'AREA':
        elem.val('');
        break;
      }
    });
    form.find('.modification-indicator').addClass('hidden');
    MLInvoice.setupSelect2($('#popup_edit'));

    var buttons = {};
    var that = this;
    buttons[MLInvoice.translate('Save')] = function onClickSave() {
      that.modifyRows('iform_popup');
    };
    buttons[MLInvoice.translate('Close')] = function onClickClose() {
      $('#popup_edit').dialog('close');
    };
    $('#popup_edit').dialog({
      modal: true,
      width: _subFormConfig.popupWidth,
      height: 180,
      resizable: true,
      buttons: buttons,
      title: title,
    });
  }

  function startChanging()
  {
    if (_formConfig.type === 'invoice' && !_formConfig.modificationWarningShown && _formConfig.modificationWarning) {
      alert(_formConfig.modificationWarning);
      _formConfig.modificationWarningShown = true;
    }
  }

  function addCompany(translations)
  {
    var buttons = {};
    buttons[translations.save] = function onSaveCompany() {
      _saveCompany(translations);
    };
    buttons[translations.close] = function onCloseCompany() {
      $('#quick_add_company').dialog('close');
    };
    $('#quick_add_company').dialog({ modal: true, width: 420, height: 320, resizable: false, zIndex: 900,
      buttons: buttons,
      title: translations.title,
    });
  }

  function _saveCompany(translations)
  {
    var obj = {};
    obj.company_name = document.getElementById('quick_name').value;
    obj.email = document.getElementById('quick_email').value;
    obj.phone = document.getElementById('quick_phone').value;
    obj.street_address = document.getElementById('quick_street_address').value;
    obj.zip_code = document.getElementById('quick_zip_code').value;
    obj.city = document.getElementById('quick_city').value;
    obj.country = document.getElementById('quick_country').value;
    $.ajax({
      url: 'json.php?func=put_company',
      type: 'POST',
      dataType: 'json',
      data: JSON.stringify(obj),
      contentType: 'application/json; charset=utf-8',
      success: function putCompanyDone(data) {
        if (data.missing_fields) {
          alert(translations.missing + data.missing_fields);
        } else {
          _initCompanyList(data.id);
          $('#quick_add_company').dialog('close');
        }
      }
    });
  }

  function _initCompanyList(selected_id)
  {
    $.getJSON(
      'json.php?func=get_company',
      {id: selected_id},
      function getCompanyDone(record) {
        var text = record.company_name;
        if (record.company_id) {
          text += ' (' + record.company_id + ')';
        }
        var company_id = $('#company_id');
        company_id.select2('data', {'id': record.id, 'text': text});
        company_id.trigger('change');
      }
    );
  }


  function addPartialPayment()
  {
    var buttons = {};
    buttons[MLInvoice.translate('Save')] = function onSavePartialPayment() {
      _savePartialPayment();
    };
    buttons[MLInvoice.translate('Close')] = function onClosePartialPayment() {
      $('#add_partial_payment').dialog('close');
    };
    $('#add_partial_payment').dialog({ modal: true, width: 420, height: 160, resizable: false, zIndex: 900,
      buttons: buttons,
      title: MLInvoice.translate('PartialPayment')
    });
  }

  function _savePartialPayment()
  {
    var obj = {};
    obj.invoice_id = $('#record_id').val();
    obj.description = MLInvoice.translate('PartialPayment');
    obj.row_date = $('#add_partial_payment_date').val();
    obj.price = -parseFloat($('#add_partial_payment_amount').val().replace(MLInvoice.translate('DecimalSeparator'), '.'));
    obj.pcs = 0;
    obj.vat = 0;
    obj.vat_included = 0;
    obj.order_no = 100000;
    obj.partial_payment = 1;
    $.ajax({
      url: 'json.php?func=put_invoice_row',
      type: 'POST',
      dataType: 'json',
      data: JSON.stringify(obj),
      contentType: 'application/json; charset=utf-8',
      success: function putInvoiceRowDone(data) {
        if (data.missing_fields) {
          alert(MLInvoice.translate('ErrValueMissing') + ': ' + data.missing_fields);
        } else {
          MLInvoice.Form.initRows();
          $('#add_partial_payment').dialog('close');
        }
      }
    });
  }

  function updateBaseDefaults()
  {
    MLInvoice.updateSendApiButtons();
    var baseId = String($('#base_id').val());
    if (baseId === '') {
      return;
    }

    $.ajax({
      'url': 'json.php?func=get_base',
      'data': {
        'id': baseId
      },
      'type': 'GET',
      'success': function getBaseDone(data) {
        var state = $('#state_id').val();
        $.ajax({
          'url': 'json.php?func=get_invoice_state',
          'data': {
            'id': state
          },
          'type': 'GET',
          'success': function getInvoiceStateDone(state_data) {
            if (state_data.invoice_open == 0) {
              return;
            }
            var prefix = state_data.invoice_offer == 1 ? 'offer' : 'invoice';
            if (data[prefix + '_default_foreword']) {
              var oldfw = $('#foreword').val();
              if (oldfw === '' || oldfw === data.invoice_default_foreword || oldfw === data.offer_default_foreword) {
                $('#foreword').val(data[prefix + '_default_foreword']);
              }
            }
            if (data[prefix + '_default_afterword']) {
              var oldaw = $('#afterword').val();
              if (oldaw === '' || oldaw === data.invoice_default_afterword || oldaw === data.offer_default_afterword) {
                $('#afterword').val(data[prefix + '_default_afterword']);
              }
            }
            if (data.invoice_default_info && $('#info').val() == '') {
              $('#info').val(data.invoice_default_info);
            }
          }
        });
      }
    });
  }

  return {
    initForm: initForm,
    initAddressAutocomplete: initAddressAutocomplete,
    saveRecord: saveRecord,
    initRows: initRows,
    updateRowOrder: updateRowOrder,
    saveRow: saveRow,
    deleteRow: deleteRow,
    deleteSelectedRows: deleteSelectedRows,
    popupEditor: popupEditor,
    multiEditor: multiEditor,
    modifyRows: modifyRows,
    addCompany: addCompany,
    addPartialPayment: addPartialPayment
  };
});
