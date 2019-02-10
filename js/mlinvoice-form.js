/* global MLInvoice, $, google */
MLInvoice.addModule('Form', function mlinvoiceForm() {
  var _formConfig = {};
  var _subFormConfig = {};
  var _listItems = {};
  var _selectedProduct = null;
  var _defaultDescription = null;
  var _maxAttachmentOrderNo = 0;

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

    // Company info
    if ($('#company_id.select2').val()) {
      _onChangeCompany();
    }
    // Stock balance
    $('.update-stock-balance').click(function updateStockBalanceClick() {
      updateStockBalance();
      return false;
    });

    // Link from base label
    $('#base_id.linked').change(_setupBaseLink);
    _setupBaseLink($('#base_id.linked'));

    // Link from company label
    $('#company_id.linked').change(_setupCompanyLink);
    _setupCompanyLink($('#company_id.linked'));

    _setupYtjSearch();
    _setupDefaultTextSelection();
    setupSelect2();
    _setupInvoiceAttachments();
    _updateSendApiButtons();
  }

  function updateDispatchByDateButtons() {
    if (MLInvoice.getDispatchNotePrintStyle() === 'none' || MLInvoice.isOfferStatus($('#state_id').val())) {
      return;
    }
    var container = $('#dispatch_date_buttons');
    container.empty();
    var dates = [];
    $('#iform td').each(function handleCol(i, td) {
      var field = $(td);
      if (field.data('field') === 'row_date') {
        var date = MLInvoice.parseDate(field.text());
        if (dates.indexOf(date) === -1) {
          dates.push(date);
        }
      }
    });
    dates.sort();
    var that = this;
    var onLinkClick = function linkClick() {
      that.printInvoice(2, 'open_invoices', MLInvoice.getDispatchNotePrintStyle(), $(this).data('date'));
    };
    for (var i in dates) {
      if (dates.hasOwnProperty(i)) {
        var link = $('<a class="formbuttonlink ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only"/>');
        var date = dates[i];
        link.data('date', date);
        link.click(onLinkClick);
        $('<span class="ui-button-text"/>').text(MLInvoice.translate('SettingDispatchNotes') + ' ' + MLInvoice.formatDate(date)).appendTo(link);
        container.append(link);
        container.append(' ');
      }
    }
  }

  function updateStockBalance()
  {
    var buttons = {};
    buttons[MLInvoice.translate('Save')] = function onSaveStockBalance() {
      saveStockBalance();
    };
    buttons[MLInvoice.translate('Close')] = function onCloseStockBalance() {
      $('#update_stock_balance').dialog('close');
    };
    $('#update_stock_balance').dialog(
      {
        modal: true, width: 400, height: 240, resizable: false, zIndex: 900,
        buttons: buttons,
        title: MLInvoice.translate('UpdateStockBalance'),
      }
    );
  }

  function saveStockBalance()
  {
    $.ajax({
      url: 'json.php?func=update_stock_balance',
      type: 'POST',
      data: {
        product_id: $('#record_id').val(),
        stock_balance_change: document.getElementById('stock_balance_change').value.replace(MLInvoice.translate('DecimalSeparator'), '.'),
        stock_balance_change_desc: document.getElementById('stock_balance_change_desc').value
      },
      success: function updateStockBalanceDone(data) {
        if (data.missing_fields) {
          alert(MLInvoice.translate('ErrValueMissing') + ': ' + data.missing_fields);
        } else {
          var new_balance = parseFloat(data.new_stock_balance).toFixed(2).replace('.', MLInvoice.translate('DecimalSeparator'));
          $('#stock_balance').val(new_balance);
          updateStockBalanceLog();
          $('#update_stock_balance').dialog('close');
        }
      }
    });
  }

  function updateStockBalanceLog()
  {
    $('#stock_balance_change_log  > tbody > tr').slice(1).remove();
    $.ajax({
      url: 'json.php?func=get_stock_balance_rows',
      type: 'POST',
      data: {
        product_id: $('#record_id').val(),
      },
      success: function getStockBalanceRowsDone(data) {
        $('#stock_balance_change_log').append(data);
      }
    });
  }

  function _setupYtjSearch() {
    var button = $('a.ytj_search_button');
    if (button.length === 0) {
      return;
    }
    button.click(function ytjSearch() {
      var term = $('#company_id').val();
      if (!term) {
        term = $('#company_name').val();
      }
      term = window.prompt(MLInvoice.translate('SearchYTJPrompt'), term);
      if ('' === term || null === term) {
        return;
      }
      // Try business ID first
      var businessId = term.replace(/FI-?/i, '');
      $.ajax(
        {
          url: 'https://avoindata.prh.fi/bis/v1',
          data: {
            maxResults: 1,
            businessId: businessId
          },
          global: false
        }
      ).done(function ytjSearchDone(data) {
        if ('undefined' === typeof data.results[0]) {
          return;
        }
        _fillCompanyForm(data.results[0]);
      }).fail(function ytjSearchFail(jqXHR, textStatus) {
        if (404 === jqXHR.status) {
          // Try company name second
          $.ajax(
            {
              url: 'https://avoindata.prh.fi/bis/v1',
              data: {
                maxResults: 1,
                name: term
              },
              global: false
            }
          ).done(function ytjSearchDone2(data) {
            if ('undefined' === typeof data.results[0]) {
              return;
            }
            _fillCompanyForm(data.results[0]);
          }).fail(function ytjSearchFail2(jqXHR2, textStatus2) {
            if (404 === jqXHR2.status) {
              window.alert(MLInvoice.translate('NoYTJResultsFound'));
            } else {
              window.alert('Request failed: ' + jqXHR2.status + ' - ' + textStatus2);
            }
          });
        } else {
          window.alert('Request failed: ' + jqXHR.status + ' - ' + textStatus);
        }
      });
    });
  }

  function _setupBaseLink()
  {
    var base_id = $('#base_id.linked');
    if (base_id.val() == '') {
      $('#base_id_label').text($('#base_id_label').text());
    } else {
      $('#base_id_label').html('<a href="index.php?func=settings&list=base&form=base&id=' + base_id.val() + '">' + $('#base_id_label').text() + '</a>');
    }
  }

  function _setupCompanyLink()
  {
    var company_id = $('#company_id.linked');
    if (company_id.length === 0) {
      return;
    }
    if (company_id.val() === '') {
      $('#company_id_label').text($('#company_id_label').text());
    } else {
      $('#company_id_label').html('<a href="index.php?func=company&list=&form=company&id=' + company_id.val() + '">' + $('#company_id_label').text() + '</a>');
    }
  }

  function _fillCompanyForm(data) {
    $('#company_id').val(data.businessId).change();
    $('#company_name').val(data.name);
    $.each(data.addresses, function handleAddress(idx, address) {
      if (1 !== address.version) {
        return;
      }
      if (1 === address.type) {
        $('#street_address').val(address.street);
        $('#zip_code').val(address.postCode);
        $('#city').val(address.city);
        $('#country').val(address.country);
      }
      if (2 === address.type) {
        var parts = [];
        parts.push(data.name);
        if (address.careOf) {
          parts.push('c/o ' + address.careOf);
        }
        if (address.street) {
          parts.push(address.street);
        }
        if (address.postCode) {
          var post = address.postCode + ' ' + address.city;
          parts.push(post.trim());
        }
        if (address.country) {
          parts.push(address.country);
        }
        $('#billing_address').val(parts.join("\n"));
      }
    });
    $.each(data.contactDetails, function handleContact(idx, contact) {
      if (1 !== parseInt(contact.version, 10)) {
        return;
      }
      switch (contact.type) {
      case 'Matkapuhelin':
        $('#gsm').val(contact.value);
        break;
      case 'Kotisivun www-osoite':
        $('#www').val(contact.value);
        break;
      case 'Puhelin':
        $('#phone').val(contact.value);
        break;
      case 'Faksi':
        $('#fax').val(contact.value);
        break;
      }
    });
  }

  function _setupDefaultTextSelection() {
    $('.select-default-text').each(function setupDefaultText() {
      var target = $(this).data('target');
      var formParam = $(this).data('sendFormParam');
      var select = $('<input type="hidden" class="select-default-text"/>').appendTo($(this));
      select.select2({
        placeholder: '',
        ajax: {
          url: 'json.php',
          dataType: 'json',
          quietMillis: 200,
          data: function defaultTextDone(term, page) { // page is the one-based page number tracked by Select2
            return {
              func: 'get_selectlist',
              table: 'default_value',
              q: term,
              type: $(this).parent().data('type'),
              pagelen: 50, // page size
              page: page, // page number
            };
          },
          results: function processResults(data/*, page*/) {
            var records = data.records;
            return {results: records, more: data.moreAvailable};
          }
        },
        dropdownCssClass: 'bigdrop',
        dropdownAutoWidth: true,
        escapeMarkup: function escapeString(m) { return m; },
        width: 'element',
        minimumResultsForSearch: -1
      });
      select.on('change', function selectChange() {
        var id = select.select2('val');
        if (!id) {
          return;
        }
        // Reset selection so that the same entry can be re-selected at will
        select.select2('val', null);
        $.ajax(
          {
            url: 'json.php',
            data: {
              func: 'get_default_value',
              id: id
            }
          }
        ).done(function getDefaultValueDone(data) {
          if (formParam) {
            var input = $('<input type="hidden"/>');
            input.attr('name', formParam);
            input.attr('value', data.id);
            $('#' + target).append(input);
            $('#' + target).submit();
          } else {
            $('#' + target).val(data.content);
            $('#' + target).change();
          }
        }).fail(function getDefaultValueFail(jqXHR, textStatus) {
          window.alert('Request failed: ' + jqXHR.status + ' - ' + textStatus);
        });
      });
    });
  }

  function setupSelect2(_container) {
    var container = 'undefined' === typeof _container ? 'body' : _container;

    var callbacks = {
      _onChangeCompany: _onChangeCompany,
      _onChangeCompanyOffer: _onChangeCompanyOffer,
      _onChangeProduct: _onChangeProduct,
      _onChangeCompanyReload: _onChangeCompanyReload
    };
    $(container).find('.select2').each(function setupSelect2Field() {
      var field = $(this);
      var tags = field.hasClass('tags');
      var query = field.data('query');
      var showEmpty = field.data('showEmpty');
      var onChange = field.data('onChange');
      var options = {
        placeholder: '',
        ajax: {
          url: 'json.php?func=get_selectlist&' + query,
          dataType: 'json',
          quietMillis: 200,
          data: function getSelectListDone(term, page) {
            var params = {
              q: term,
              pagelen: 50,
              page: page
            }
            if ('iform_product_id' === field[0].id) {
              var $companyId = $('#company_id');
              if ($companyId.length && $companyId.val()) {
                params.company = $companyId.val();
              }
            }
            return params;
          },
          results: function processResults(data, page) {
            var records = [];
            if (tags) {
              $(data.records).each(function processRecord() {
                records.push({
                  id: this.text,
                  text: this.text,
                  descriptions: []
                });
              });
            } else {
              records = data.records;
            }
            if (showEmpty && page === 1 && data.filter === '') {
              records.unshift({id: '', text: '-'});
            }
            return {results: records, more: data.moreAvailable};
          }
        },
        initSelection: function initSelection(element, callback) {
          var id = $(element).val();
          if (id !== '') {
            $.ajax('json.php?func=get_selectlist&' + query + '&id=' + id, {
              dataType: 'json'
            }).done(function getSelectListDone(data) {
              callback(data.records[0]);
            });
          }
        },
        formatResult: function formatResult(object) {
          var text = object.text;
          $(object.descriptions).each(function processDescription() {
            text += '<div class="select-description">' + this + '</div>';
          });
          return text;
        },
        dropdownCssClass: 'bigdrop',
        dropdownAutoWidth: true,
        escapeMarkup: function escapeString(m) { return m; },
        width: 'element'
      };

      if (tags) {
        $.extend(options, {
          tags: true,
          tokenSeparators: [','],
          createSearchChoice: function createChoice(term) {
            return {
              id: $.trim(term),
              text: $.trim(term) + ' (+)'
            };
          },
          initSelection: function initSelection(element, callback) {
            var data = [];
            var tagSet = element.val();
            if (!tagSet) {
              return data;
            }
            $(tagSet.split(',')).each(function handleTag() {
              var val = $.trim(this);
              if ('' !== val) {
                data.push({
                  id: this,
                  text: this
                });
              }
            });
            callback(data);
          },
          formatSelection: function formatSelection(object) {
            var text = object.text;
            text = text.replace(/ \(\+\)$/, '');
            return $('<div/>').text(text).html();
          }
        });
      }

      var select2 = field.select2(options);
      if (onChange && 'function' === typeof callbacks[onChange]) {
        select2.change(callbacks[onChange]);
      }
    });
  }

  function _onChangeCompany(eventData) {
    var initialLoad = typeof eventData === 'undefined';
    $('#invoice_vatless').val('0');
    _addCompanyInfoTooltip('');
    $.getJSON('json.php?func=get_company', {id: $('#company_id').val() }, function setCompanyData(json) {
      if (json) {
        if (!initialLoad) {
          if (json.default_ref_number) {
            $('#ref_number').val(json.default_ref_number);
          }
          if (json.delivery_terms_id) {
            $('#delivery_terms_id').val(json.delivery_terms_id);
          }
          if (json.delivery_method_id) {
            $('#delivery_method_id').val(json.delivery_method_id);
          }
          if (json.payment_days) {
            $.getJSON(
              'json.php?func=get_invoice_defaults',
              {
                id: $('#record_id').val(),
                invoice_no: $('#invoice_no').val(),
                invoice_date: $('#invoice_date').val(),
                base_id: $('#base_id').val(),
                company_id: $('#company_id').val(),
                interval_type: $('#interval_type').val()
              },
              function getInvoiceDefaultsDone(data) {
                $('#due_date').val(data.due_date);
              }
            );
          }
          $('#delivery_address').val(json.delivery_address ? json.delivery_address : '');
        }
        if (json.info) {
          _addCompanyInfoTooltip(json.info);
        }
        if (json.invoice_default_foreword) {
          $('#foreword').val(json.invoice_default_foreword);
        }
        if (json.invoice_default_afterword) {
          $('#afterword').val(json.invoice_default_afterword);
        }
        if (json.invoice_vatless) {
          $('#invoice_vatless').val('1');
        }
      }
    });
  }

  function _onChangeCompanyOffer() {
    _addCompanyInfoTooltip('');
    $.getJSON('json.php?func=get_company', {id: $('#company_id').val() }, function setCompanyData(json) {
      if (json) {
        if (json.info) {
          _addCompanyInfoTooltip(json.info);
        }
        if (json.offer_default_foreword) {
          $('#foreword').val(json.offer_default_foreword);
        }
        if (json.offer_default_afterword) {
          $('#afterword').val(json.offer_default_afterword);
        }
      }
    });
  }

  function _onChangeProduct() {
    if ('' === this.value) {
      return;
    }
    var form_id = this.form.id;
    var company_id = $('#company_id').val();
    $.getJSON('json.php?func=get_product&id=' + this.value + '&company_id=' + company_id, function setProductData(json) {
      _selectedProduct = json;
      if (!json || !json.id) return;

      if (json.description !== '' || document.getElementById(form_id + '_description').value === (null !== _defaultDescription ? _defaultDescription : '')) {
        document.getElementById(form_id + '_description').value = json.description;
      }
      _defaultDescription = json.description;

      var type_id = document.getElementById(form_id + '_type_id');
      for (var i = 0; i < type_id.options.length; i++) {
        var item = type_id.options[i];
        if (item.value === (json.type_id === null ? '' : String(json.type_id))) {
          item.selected = true;
          break;
        }
      }
      var unitPrice = json.custom_price && json.custom_price.unit_price !== null
        ? json.custom_price.unit_price : json.unit_price;
      document.getElementById(form_id + '_price').value = json.unit_price ? MLInvoice.formatCurrency(unitPrice) : '';
      document.getElementById(form_id + '_discount').value = json.discount ? json.discount.replace('.', ',') : '';
      document.getElementById(form_id + '_discount_amount').value = json.discount_amount ? MLInvoice.formatCurrency(json.discount_amount) : '';
      if ($('#invoice_vatless').val() === '0') {
        document.getElementById(form_id + '_vat').value = json.vat_percent ? json.vat_percent.replace('.', ',') : '';
        document.getElementById(form_id + '_vat_included').checked = !!((json.vat_included && json.vat_included === 1));
      } else {
        document.getElementById(form_id + '_vat').value = '0';
        document.getElementById(form_id + '_vat_included').checked = false;
      }
    });
  }

  function _onChangeCompanyReload() {
    var loc = window.location.href;
    loc = loc.replace(/[\\?&]company=\d*/, '');
    loc += loc.indexOf('?') >= 0 ? '&' : '?';
    loc += 'company=' + this.value;
    window.location.href = loc;
  }

  function _addCompanyInfoTooltip(content)
  {
    if (!content) {
      $('#company_id_label>span.info').remove();
      return;
    }
    var info = $('<span/>').addClass('info ui-state-highlight ui-corner-all')
      .attr('title', content).text(' ').click(function infoClick() {
        alert(content);
      });
    info.appendTo($('#company_id_label'));
  }

  function getSelectedProductDefaults(form_id) {
    if (null === _selectedProduct) {
      return;
    }
    document.getElementById(form_id + '_description').value = _selectedProduct.description;
    _defaultDescription = _selectedProduct.description;

    var type_id = document.getElementById(form_id + '_type_id');
    for (var i = 0; i < type_id.options.length; i++) {
      var item = type_id.options[i];
      if (item.value === _selectedProduct.type_id) {
        item.selected = true;
        break;
      }
    }
    document.getElementById(form_id + '_price').value = _selectedProduct.unit_price.replace('.', ',');
    document.getElementById(form_id + '_discount').value = _selectedProduct.discount.replace('.', ',');
    document.getElementById(form_id + '_vat').value = _selectedProduct.vat_percent.replace('.', ',');
    document.getElementById(form_id + '_vat_included').checked = _selectedProduct.vat_included === 1;
  }

  function _updateSendApiButtons()
  {
    var $buttons = $('.send-buttons');
    if ($buttons.length === 0) {
      return;
    }
    $buttons.html('');
    var baseId = String($('#base_id').val());
    if (baseId === '') {
      return;
    }
    $.getJSON('json.php?func=get_send_api_services', {invoice_id: String($('#record_id').val()), base_id: baseId}, function getSendApiButtonsDone(json) {
      $.each(json.services, function addService(idx, service) {
        var $ul = $('<ul class="dropdownmenu"/>');
        var $heading = $('<li/>');
        $heading.text(service.name + '...');
        $ul.append($heading);
        var $menuitems = $('<ul/>');
        $.each(service.items, function addItem(idx2, item) {
          var $li = $('<li/>');
          $li.click(function liClick() {
            _sendPrintout('?' + item.href);
          });
          var $name = $('<div>');
          $name.text(item.name);
          $li.append($name);
          $menuitems.append($li);
        });
        $heading.append($menuitems);
        $buttons.append($ul);
      });
      $('.send-buttons .dropdownmenu').each(function initMenu() {
        $(this).menu({}).find('li:first').addClass('formbuttonlink ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only');
      });
    });
  }

  function printInvoice(template, func, printStyle, date) {
    if (!_verifyPrintable()) {
      return;
    }

    var id = $('#record_id').val();
    var target = 'invoice.php?id=' + id + '&template=' + template + '&func=' + func;
    if (typeof date !== 'undefined') {
      target += '&date=' + date;
    }
    var form = $('#admin_form');
    if (typeof form.data('readOnly') === 'undefined') {
      this.saveRecord(target, printStyle, true);
    } else if (printStyle === 'openwindow') {
      window.open(target);
    } else {
      window.location.href = target;
    }
    return false;
  }


  function _sendPrintout(url) {
    if (!_verifyPrintable()) {
      return false;
    }

    var form = $('#admin_form');
    if (typeof form.data('readOnly') === 'undefined') {
      this.saveRecord(url, '', true);
    } else {
      window.location.href = url;
    }
    return false;
  }

  function _verifyPrintable() {
    var form = $('#admin_form');
    if (typeof form.data('checkInvoiceDate') !== 'undefined') {
      var d = new Date();
      var dt = $('#invoice_date').val().split('.');
      if (parseInt(dt[0], 10) !== d.getDate() || parseInt(dt[1], 10) !== d.getMonth() + 1 || parseInt(dt[2], 10) !== d.getYear() + 1900) {
        if (!confirm(MLInvoice.translate('InvoiceDateNonCurrent'))) {
          return false;
        }
      }
    }

    if (!MLInvoice.isOfferStatus($('#state_id').val())) {
      var len = $('#ref_number').val().length;
      if (len > 0 && len < 4) {
        if (!confirm(MLInvoice.translate('InvoiceRefNumberTooShort'))) {
          return false;
        }
      }

      if (typeof form.data('checkInvoiceNumber') !== 'undefined') {
        var invoiceNo = String($('#invoice_no').val());
        if (invoiceNo === '' || invoiceNo === '0') {
          if (!confirm(MLInvoice.translate('InvoiceNumberNotDefined'))) {
            return false;
          }
        }
      }
    }
    return true;
  }

  function _updateAttachmentList() {
    var invoiceId = $('#attachments-form').data('invoiceId');
    var $list = $('<div/>');
    _maxAttachmentOrderNo = 0;
    $.getJSON('json.php?func=get_invoice_attachments&parent_id=' + invoiceId, function getAttachmentsDone(json) {
      var cnt = 0;
      $.each(json.records, function handleAttachment(idx, item) {
        cnt += 1;
        if (item.order_no > _maxAttachmentOrderNo) {
          _maxAttachmentOrderNo = item.order_no;
        }
        var $attachment = $('<div/>').addClass('attachment');
        var $remove = $('<a/>').addClass('tinyactionlink ui-button ui-corner-all ui-widget remove-attachment')
          .text(' X ')
          .attr('title', MLInvoice.translate('RemoveAttachment'))
          .click(function removeAttachment() {
            $.getJSON('json.php?func=delete_invoice_attachment&id=' + item.id, function removeAttachmentDone() {
              _updateAttachmentList();
            });
          });
        $remove.appendTo($attachment);

        var $send = $('<input>').attr('type', 'checkbox').data('id', item.id).prop('checked', item.send);
        $send.change(function onSendChange() {
          $.ajax({
            url: 'json.php?func=put_invoice_attachment',
            data: {
              id: $(this).data('id'),
              send: $(this).prop('checked') ? '1' : '0'
            },
            type: 'POST',
            dataType: 'json'
          });
        });
        var $cbLabel = $('<label/>').addClass('attachment-send');
        $send.appendTo($cbLabel);
        $('<span/>').text(MLInvoice.translate('SendToClient')).appendTo($cbLabel);
        $cbLabel.appendTo($attachment);

        var $input = $('<input/>').addClass('attachment-name').attr('type', 'text').data('id', item.id).val(item.name)
          .attr('placeholder', MLInvoice.translate('Description'));
        $input.change(function onNameChange() {
          $.ajax({
            url: 'json.php?func=put_invoice_attachment',
            data: {
              id: $(this).data('id'),
              name: $(this).val()
            },
            type: 'POST',
            dataType: 'json'
          });
        });
        $input.appendTo($attachment);
        var $fileinfo = $('<div/>').addClass('attachment-fileinfo');
        var $link = $('<a/>').attr('href', 'attachment.php?type=invoice&id=' + item.id).attr('target', '_blank')
          .text(item.filename);
        $link.appendTo($fileinfo);

        var $filesize = $('<span/>').text(' (' + item.filesize_readable + ')');
        if (item.filesize > 1024 * 1024) {
          $filesize.addClass('large-file');
          $filesize.attr('title', MLInvoice.translate('LargeFile'));
        }
        $filesize.appendTo($fileinfo);
        $fileinfo.appendTo($attachment);
        $attachment.appendTo($list);
      });
      if (cnt === 0) {
        $list.text(MLInvoice.translate('NoEntries'));
      }
      $('.attachment-list').empty().append($list);
      $('.attachment-count').text(cnt);
    });
  }

  function _toggleAttachmentForm(open)
  {
    if (open) {
      $('#attachments-button .dropdown-open').hide();
      $('#attachments-button .dropdown-close').show();
      $('#attachments-form').removeClass('hidden');
    } else {
      $('#attachments-button .dropdown-open').show();
      $('#attachments-button .dropdown-close').hide();
      $('#attachments-form').addClass('hidden');
    }
  }

  function _setupInvoiceAttachments() {
    var invoiceId = $('#attachments-form').data('invoiceId');

    $('#attachments-button').click(function attachmentsClick() {
      if ($('#attachments-form').hasClass('hidden')) {
        _updateAttachmentList();
        _toggleAttachmentForm(true);
      } else {
        _toggleAttachmentForm(false);
      }
    });
    $('a.add-attachment').click(function addAttachmentClick() {
      $.ajax({
        url: 'json.php?func=add_invoice_attachment&id=' + $(this).data('id') + '&invoice_id=' + invoiceId,
        type: 'POST',
        dataType: 'json',
        success: function addAttachmentDone() {
          _updateAttachmentList();
        }
      });
    });
    $('#new-attachment-file').change(function addNewAttachment() {
      if (this.files.length > 0) {
        var formdata = new FormData();
        formdata.append('filedata', this.files[0]);
        formdata.append('invoice_id', invoiceId);
        formdata.append('order_no', _maxAttachmentOrderNo + 5);
        $.ajax({
          url: "json.php?func=put_invoice_attachment",
          type: 'POST',
          dataType: 'json',
          data: formdata,
          processData: false,
          contentType: false,
          success: function addAttachmentFileDone(data) {
            if (data.warnings) {
              alert(data.warnings);
            }
            if (data.missing_fields) {
              MLInvoice.errormsg(MLInvoice.translate('ErrValueMissing') + ': ' + data.missing_fields);
            } else {
              _updateAttachmentList();
            }
          }
        });
        $(this).val(null);
      }
    });
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
      case 'SELECT':
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
        var summary = _calculateInvoiceRowSummary(json.records);
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
        that.updateDispatchByDateButtons();
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
              var value = form.find('[name=' + formId + '_' + field.name + ']');
              if (typeof field.default !== 'undefined' && String(field.default).startsWith('ADD+')) {
                value.val(parseInt(value.val(), 10) + parseInt(String(field.default).substr(4)));
              } else if (typeof field.default !== 'undefined' && field.default === 'DATE_NOW') {
                var today = new Date();
                var dbdate = today.toISOString().replace(/-/g, '').substr(0, 8);
                value.val(MLInvoice.formatDate(dbdate));
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
      setupSelect2($('#popup_edit'));

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
    setupSelect2($('#popup_edit'));

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
    _updateSendApiButtons();
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

  function _calculateInvoiceRowSummary(records)
  {
    var totSum = 0;
    var totVAT = 0;
    var totSumVAT = 0;
    var totWeight = 0;
    var partialPayments = 0;
    for (var i = 0; i < records.length; i++) {
      var record = records[i];

      if (record.partial_payment) {
        partialPayments += parseFloat(record.price);
        continue;
      }

      var items = record.pcs;
      var price = record.price;
      var discount = record.discount || 0;
      var discountAmount = record.discount_amount || 0;
      var VATPercent = record.vat;
      var VATIncluded = record.vat_included;

      if (record.product_weight !== null) {
        totWeight += items * parseFloat(record.product_weight);
      }

      price *= (1 - discount / 100);
      price -= discountAmount;
      var sum = 0;
      var sumVAT = 0;
      var VAT = 0;
      if (VATIncluded == 1) {
        sumVAT = items * price;
        sum = sumVAT / (1 + VATPercent / 100);
        VAT = sumVAT - sum;
      } else {
        sum = items * price;
        VAT = sum * (VATPercent / 100);
        sumVAT = sum + VAT;
      }

      totSum += sum;
      totVAT += VAT;
      totSumVAT += sumVAT;
    }
    return {
      totSum: totSum,
      totVAT: totVAT,
      totSumVAT: totSumVAT,
      totWeight: totWeight,
      partialPayments: partialPayments
    }
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
    addPartialPayment: addPartialPayment,
    getSelectedProductDefaults: getSelectedProductDefaults,
    updateStockBalance: updateStockBalance,
    updateStockBalanceLog: updateStockBalanceLog,
    printInvoice: printInvoice,
    updateDispatchByDateButtons: updateDispatchByDateButtons,
    setupSelect2: setupSelect2
  };
});
