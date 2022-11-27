/* global MLInvoice, $, bootstrap, EasyMDE, google, Sortable, moment */
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

    $('#form')
      .find('input[type="text"],[type="date"]:not([name="payment_date"]),input[type="hidden"],input[type="checkbox"]:not([name="archived"]),select:not(.dropdownmenu),textarea')
      .one('change', startChanging);

    $('.save_button').on('click', function onClickSave() {
      MLInvoice.Form.saveRecord();
      return false;
    });

    $('#base_id').on('change', updateBaseDefaults);
    $('#state_id').on('change', updateBaseDefaults);

    // Company info
    if ($('#company_id.select2').val()) {
      _onChangeCompany();
    }
    // Stock balance
    $('.update-stock-balance').on('click', _updateStockBalance);

    // Link from base label
    $('#base_id.linked').on('change', _setupBaseLink);
    _setupBaseLink($('#base_id.linked'));

    // Link from company label
    $('#company_id.linked').on('change', _setupCompanyLink);
    _setupCompanyLink($('#company_id.linked'));

    $('[data-add-reminder-fees]').on('click', _addReminderFees);
    $('[data-add-partial-payment]').on('click', _addPartialPayment);
    $('[data-save-partial-payment]').on('click', _savePartialPayment);
    $('[data-quick-add-company]').on('click', _addCompany);
    $('[data-save-company]').on('click', _saveCompany);
    $('[data-save-stock-balance-change]').on('click', _saveStockBalance);

    var that = this;
    $('[data-iform-copy-row]').on('click', function onClickCopy() {
      that.saveRow($(this).data('iform-copy-row'), true);
      return false;
    });
    $('[data-iform-save-row]').on('click', function onClickSave() {
      that.saveRow($(this).data('iform-save-row'));
      return false;
    });
    $('[data-iform-delete-row]').on('click', function onClickDelete() {
      if (confirm(MLInvoice.translate('ConfirmDelete')) === true) {
        that.deleteRow($(this).data('iform-delete-row'));
      }
      return false;
    });
    $('[data-iform-save-rows]').on('click', function onClickSaveRows() {
      that.modifyRows($(this).data('iform-save-rows'));
      return false;
    });
    $('.modification-indicator .clear').on('click', function onClearModification() {
      var $ind = $(this).closest('.modification-indicator');
      $ind.addClass('hidden');
      var $container = $ind.closest('td');
      $container.find('input[type="text"],input[type="date"],input[type="hidden"]:not(.select-default-text),input[type="checkbox"],select:not(.dropdownmenu),textarea').data('modified', 0);
      $container.find('input[type="text"],input[type="date"],input[type="hidden"]:not(.select-default-text),textarea').val('');
      $container.find('input[type="checkbox"]').prop('checked', false);
      $container.find('select:not(.dropdownmenu)').val('');
      $container.find('input.select2').select2('val', null);
    });

    _setupYtjSearch();
    setupMarkdownEditor();
    setupDefaultTextSelection();
    setupSelect2();
    _setupInvoiceAttachments();
    _updateSendApiButtons();
    _setupPrintButtons();
  }

  function setupMarkdownEditor() {
    var ua = navigator.userAgent.toLowerCase();
    if (ua.indexOf('android') > -1) {
      // Disable MDE on Android due to problems with cursor handling
      return;
    }
    $('textarea.markdown').each(function initMarkdown() {
      var mde = new EasyMDE({
        element: this,
        minHeight: '50px',
        autoDownloadFontAwesome: false,
        indentWithTabs: false,
        forceSync: false,
        spellChecker: false,
        status: false,
        toolbarTips: false,
        toolbar: [
          {
            name: "bold",
            action: EasyMDE.toggleBold,
            className: "icon-bold",
            title: "Bold",
          },
          {
            name: "italic",
            action: EasyMDE.toggleItalic,
            className: "icon-italic",
            title: "Italic",
          },
          {
            name: 'headingc',
            action: EasyMDE.toggleHeadingSmaller,
            className: 'icon-header',
            title: 'Heading'
          },
          '|',
          {
            name: "blockquote",
            action: EasyMDE.toggleBlockquote,
            className: "icon-quote-left",
            title: "Quote",
          },
          {
            name: "unordered-list",
            action: EasyMDE.toggleUnorderedList,
            className: "icon-list-bullet",
            title: "Unordered list",
          },
          {
            name: "ordered-list",
            action: EasyMDE.toggleOrderedList,
            className: "icon-list-numbered",
            title: "Ordered list",
          },
          '|',
          {
            name: "preview",
            action: EasyMDE.togglePreview,
            className: "icon-eye",
            title: "Preview",
          },
          {
            name: "side-by-side",
            action: EasyMDE.toggleSideBySide,
            className: "icon-columns",
            title: "Side by side",
          },
          {
            name: "fullscreen",
            action: EasyMDE.toggleFullScreen,
            className: "icon-resize-full-alt",
            title: "Full screen",
          },
          '|'
        ]
      });
      mde.codemirror.options.extraKeys.Tab = false;
      mde.codemirror.options.extraKeys['Shift-Tab'] = false;
      $(this).data('mde', mde);
      mde.codemirror.on('change', function onMdeChange() {
        startChanging();
        MLInvoice.highlightButton('.save_button', true);
      });
    });
  }

  function updateDispatchByDateButtons(rows) {
    if (MLInvoice.getDispatchNotePrintStyle() === 'none' || MLInvoice.isOfferStatus($('#state_id').val())) {
      return;
    }
    var container = $('#dispatch_date_buttons');
    container.empty();
    var dates = [];
    $.each(rows, function handleRow(i, row) {
      if (row.reminder_row || row.partial_payment) {
        return true;
      }
      if (dates.indexOf(row.row_date) === -1) {
        dates.push(row.row_date);
      }
    });
    dates.sort();
    var that = this;
    var onLinkClick = function linkClick() {
      that.printInvoice(2, 'start_page', MLInvoice.getDispatchNotePrintStyle(), $(this).data('date'));
    };
    for (var i in dates) {
      if (dates.hasOwnProperty(i)) {
        var date = dates[i];
        var link = $('<a class="btn btn-outline-secondary" role="button">')
          .text(MLInvoice.translate('SettingDispatchNotes') + ' ' + MLInvoice.formatDate(date));
        link.data('date', date);
        link.on('click', onLinkClick);
        container.append(link);
        container.append(' ');
      }
    }
  }

  function _updateStockBalance()
  {
    var $dlg = $('#update_stock_balance');
    var bsModal = new bootstrap.Modal($dlg.get(0));
    bsModal.show();
  }

  function _saveStockBalance()
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
          bootstrap.Modal.getInstance($('#update_stock_balance').get(0)).hide();
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
    button.on('click', function ytjSearch() {
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
    $('#company_id').val(data.businessId).trigger('change');
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

  function setupDefaultTextSelection() {
    $('.select-default-text').each(function setupDefaultText() {
      var target = $(this).data('target');
      var formParam = $(this).data('sendFormParam');
      var select = $('<select class="select-default-text"/>').appendTo($(this));
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
            var $field = $('#' + target);
            var mde = $field.data('mde');
            if (mde) {
              mde.value(data.content);
            } else {
              $('#' + target).val(data.content);
              $('#' + target).trigger('change');
            }
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
      if (field.attr('id')) {
        var $label = $('label[for=' + field.attr('id') + ']');
        if ($label.length) {
          // Redirect label to the select2 field
          $label.attr('for', 's2id_' + $label.attr('for'));
        }
      }
      var tags = field.hasClass('tags');
      var query = field.data('listQuery');
      var showEmpty = field.data('showEmpty');
      var onChange = field.data('onChange');
      var options = {
        placeholder: '',
        ajax: {
          url: 'json.php?func=get_selectlist&' + query,
          quietMillis: 200,
          data: function getSelectListParams(term, page) {
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
          processResults: function processResults(data, page) {
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
        templateResult: function formatResult(state) {
          var $text = $('<span/>');
          $text.html(state.text);
          $(state.descriptions).each(function processDescription() {
            var $div = $('<div class="select-description"/>');
            $div.html(this).appendTo($text);
          });
          return $text;
        },
        dropdownAutoWidth: true,
        dropdownParent: $(container),
        width: '100%'
      };

      if (tags) {
        $.extend(options, {
          tags: true,
          tokenSeparators: [','],
          createSearchChoice: function createChoice(term) {
            return {
              id: String.prototype.trim(term),
              text: String.prototype.trim(term) + ' (+)'
            };
          },
          templateResult: function formatResult(state) {
            return state.text;
          }
        });
      }

      var select2 = field.select2(options);
      if (onChange && 'function' === typeof callbacks[onChange]) {
        select2.on('change', callbacks[onChange]);
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

      var changeEvent = new Event('change');
      if (json.description !== '' || document.getElementById(form_id + '_description').value === (null !== _defaultDescription ? _defaultDescription : '')) {
        var el = document.getElementById(form_id + '_description');
        el.value = json.description;
        el.dispatchEvent(changeEvent);
      }
      _defaultDescription = json.description;

      var type_id = document.getElementById(form_id + '_type_id');
      for (var i = 0; i < type_id.options.length; i++) {
        var item = type_id.options[i];
        if (item.value === (json.type_id === null ? '' : String(json.type_id))) {
          item.selected = true;
          type_id.dispatchEvent(changeEvent);
          break;
        }
      }
      var unitPrice = json.custom_price && json.custom_price.unit_price !== null
        ? json.custom_price.unit_price : json.unit_price;
      var elem = document.getElementById(form_id + '_price');
      elem.value = json.unit_price ? MLInvoice.formatCurrency(unitPrice) : '';
      elem.dispatchEvent(changeEvent);

      elem = document.getElementById(form_id + '_discount');
      elem.value = json.discount ? json.discount.replace('.', ',') : '';
      elem.dispatchEvent(changeEvent);

      elem = document.getElementById(form_id + '_discount_amount');
      elem.value = json.discount_amount ? MLInvoice.formatCurrency(json.discount_amount) : '';
      elem.dispatchEvent(changeEvent);

      var vatElem = document.getElementById(form_id + '_vat');
      var vatIncludedElem = document.getElementById(form_id + '_vat_included');
      if ($('#invoice_vatless').val() === '0') {
        elem = document.getElementById(form_id + '_vat');
        vatElem.value = json.vat_percent ? json.vat_percent.replace('.', ',') : '';
        vatIncludedElem.checked = !!((json.vat_included && json.vat_included === 1));
      } else {
        vatElem.value = '0';
        vatIncludedElem.checked = false;
      }
      vatElem.dispatchEvent(changeEvent);
      vatIncludedElem.dispatchEvent(changeEvent);
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
    $('<a class="btn btn-outline-secondary btn-sm info" role="button"></a>')
      .html('<i class="icon-info"></i>')
      .attr('title', content)
      .on('click', function infoClick() {
        MLInvoice.popupDialog(null, null, MLInvoice.translate('Info'), $('<div>').text(content).html().replace(/\n/g, '<br>'));
      })
      .appendTo($('#company_id_label'));
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
    $buttons.html('').addClass('hidden');
    var baseId = String($('#base_id').val());
    if (baseId === '') {
      return;
    }
    $.getJSON('json.php?func=get_send_api_services', {invoice_id: String($('#record_id').val()), base_id: baseId}, function getSendApiButtonsDone(json) {
      $.each(json.services, function addService(idx, service) {

        $('<a class="btn btn-secondary dropdown-toggle" href="#" role="button" id="dropdown-button-send-' + idx + '" data-bs-toggle="dropdown" aria-expanded="false">')
          .text(service.name + '...')
          .appendTo($buttons);

        var $menuitems = $('<ul class="dropdown-menu" aria-labelledby="dropdown-button-send-' + idx + '">');
        $.each(service.items, function addItem(idx2, item) {
          var $li = $('<li class="dropdown-item">')
            .text(item.name);
          $li.on('click', function liClick() {
            _sendPrintout('?' + item.href);
          });
          $menuitems.append($li);
        });
        $buttons.append($menuitems);
        $buttons.append(' ');
        $buttons.removeClass('hidden');
      });
    });
  }

  function _setupPrintButtons() {
    $('[data-print-id]').on('click', function print() {
      var $button = $(this);
      var id = $button.data('print-id');
      var func = $button.data('func');
      var style = $button.data('print-style');
      MLInvoice.Form.printInvoice(id, func, style);
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
    var form = $('#form');
    if (typeof form.data('readOnly') === 'undefined') {
      MLInvoice.Form.saveRecord(target, printStyle, true);
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

    var form = $('#form');
    if (typeof form.data('readOnly') === 'undefined') {
      MLInvoice.Form.saveRecord(url, '', true);
    } else {
      window.location.href = url;
    }
    return false;
  }

  function _verifyPrintable() {
    var form = $('#form');
    if (typeof form.data('checkInvoiceDate') !== 'undefined') {
      var invoiceDate = $('#invoice_date').val();
      if (invoiceDate !== moment().format('YYYY-MM-DD')) {
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
        var $remove = $('<a role="button" class="btn btn-outline-primary btn-sm remove-attachment">')
          .html('<i class="icon-minus"></i>')
          .attr('title', MLInvoice.translate('RemoveAttachment'))
          .attr('aria-label', MLInvoice.translate('RemoveAttachment'))
          .on('click', function removeAttachment() {
            $.getJSON('json.php?func=delete_invoice_attachment&id=' + item.id, function removeAttachmentDone() {
              _updateAttachmentList();
            });
          });
        $remove.appendTo($attachment);

        var $send = $('<input>').attr('type', 'checkbox').data('id', item.id).prop('checked', item.send);
        $send.on('change', function onSendChange() {
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
        $('<span/>').text(' ' + MLInvoice.translate('SendToClient')).appendTo($cbLabel);
        $cbLabel.appendTo($attachment);

        var $input = $('<input/>').addClass('form-control attachment-name').attr('type', 'text').data('id', item.id).val(item.name)
          .attr('placeholder', MLInvoice.translate('Description'));
        $input.on('change', function onNameChange() {
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

  function _setupInvoiceAttachments() {
    var invoiceId = $('#attachments-form').data('invoiceId');

    $('#attachments-button').on('click', function attachmentsClick() {
      $(this).attr('aria-expanded', $(this).attr('aria-expanded') === 'true' ? 'false' : 'true');
      if ($('#attachments-form').hasClass('hidden')) {
        _updateAttachmentList();
      }
      $('#attachments-button .dropdown-open').toggleClass('hidden');
      $('#attachments-button .dropdown-close').toggleClass('hidden');
      $('#attachments-form').toggleClass('hidden');
    });
    $('.add-attachment').on('click', function addAttachmentClick() {
      $.ajax({
        url: 'json.php?func=add_invoice_attachment&id=' + $(this).data('id') + '&invoice_id=' + invoiceId,
        type: 'POST',
        dataType: 'json',
        success: function addAttachmentDone() {
          _updateAttachmentList();
        }
      });
    });
    $('#new-attachment-file').on('change', function addNewAttachment() {
      if (this.files.length > 0) {
        var formdata = new FormData();
        formdata.append('filedata', this.files[0]);
        formdata.append('invoice_id', invoiceId);
        formdata.append('order_no', _maxAttachmentOrderNo + 5);
        $.ajax({
          url: 'json.php?func=put_invoice_attachment',
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
    $(input).on('blur', function onBlur() {
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
    var $form = $('#form');
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
      case 'INTDATE':
      case 'SELECT':
      case 'SEARCHLIST':
      case 'LIST':
      case 'TEXT':
      case 'PASSWD':
        formdata.append(field.name, value.val());
        break;
      case 'TAGS':
        $.each(value.select2('data'), function processOptions(idx, opt) {
          formdata.append(field.name + '[]', opt.id);
        });
        break;
      case 'AREA':
        if (value.hasClass('markdown')) {
          var mde = value.data('mde');
          formdata.append(field.name, (typeof mde !== 'undefined') ? mde.value() : value.val());
        } else {
          formdata.append(field.name, value.val());
        }
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
          $('.deleted-record-msg').remove();
          MLInvoice.highlightButton('.save_button', false);
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
    var subFormConfig = _subFormConfig;
    var listItems = _listItems;
    var that = this;
    $.getJSON('json.php?func=' + func + '&parent_id=' + _formConfig.id, function handleRows(json) {
      var $table = $('#itable');
      $('#itable > tbody > tr[id!=form_row]').remove();
      $('#itable > tfoot').remove();
      var $body = $table.find('tbody');

      $.each(json.records, function addItemRow(i, record) {
        var tr = $('<tr/>').addClass('item-row');
        if (!readOnly && 'invoice' === formType) {
          $('<td class="sort-col"><span class="sort-handle"><i class="icon-sort"></i><span class="visually-hidden">' + MLInvoice.translate('Sort') + '</span></span>')
            .appendTo(tr);
          var selectRow = MLInvoice.translate('SelectRow');
          var input = $('<input/>')
            .addClass('cb-select-row')
            .attr('type', 'checkbox')
            .attr('title', selectRow)
            .attr('aria-label', selectRow)
            .on('click', function selectRowClick() { MLInvoice.updateRowSelectedState($(this).closest('.list_container')); });
          input.val(record.id);
          var tdSelect = $('<td class="select-row"/>');
          tdSelect.append(input);
          tr.append(tdSelect);
        }

        $.each(subFormConfig.fields, function eachField(idx, field) {
          var td = $('<td/>').addClass(field.style + (record.deleted ? ' deleted' : ''));
          td.data('field', field.name);
          td.data('th', field.name);
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
              td.text(fieldData ? MLInvoice.formatCurrency(fieldData, field.decimals) : ' ');
            } else {
              td.text(fieldData ? String(fieldData).replace('.', MLInvoice.translate('DecimalSeparator')) : ' ');
            }
            td.appendTo(tr);
            break;
          case 'INTDATE':
            td.text(fieldData !== null ? MLInvoice.formatDate(fieldData) : ' ');
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
            var sumVAT2 = MLInvoice.formatCurrency(rowSum.sumVAT, 2);
            var title = MLInvoice.translate('VATLess') + ': ' + sum + ' + ' + MLInvoice.translate('VATPart') + ': ' + VAT + ' = ' + sumVAT;
            var sumSpan = $('<span/>').attr('title', title).text(sumVAT2);
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
          var editButton = $('<a/>').addClass('btn btn-outline-secondary btn-sm row-edit-button')
            .attr('role', 'button')
            .attr('href', '#')
            .attr('title', MLInvoice.translate('Edit'))
            .html('<i class="icon-pencil"></i>')
            .on('click', function editRowClick(event) {
              that.popupEditor(event, MLInvoice.translate('RowModification'), record.id);
              return false;
            });
          $('<td/>').addClass('button')
            .append(editButton)
            .appendTo(tr);
        }
        $body.append(tr);
      });

      if ('invoice_row' === subFormConfig.type) {
        var $footer = $('<tfoot>').appendTo($table);
        var summary = _calculateInvoiceRowSummary(json.records);
        var trSummary = $('<tr/>').addClass('summary');
        var modifyCol = $('<td/>').addClass('input modify-controls').attr('colspan', '6').attr('rowspan', '2');
        if (!readOnly) {
          modifyCol.text(MLInvoice.translate('ForSelected') + ': ');
          $('<button/>')
            .attr('id', 'delete-selected-rows')
            .addClass('btn btn-secondary selected-row-button')
            .text(MLInvoice.translate('Delete'))
            .on('click', function deleteSelectedClick() {
              that.deleteSelectedRows();
              return false;
            })
            .appendTo(modifyCol);
          modifyCol.append($('<span/>').text(' '));
          $('<button/>')
            .attr('id', 'update-selected-rows')
            .addClass('btn btn-secondary selected-row-button')
            .text(MLInvoice.translate('Modify'))
            .on('click', function updateSelectedClick(event) {
              that.multiEditor(event, MLInvoice.translate('ModifySelectedRows'));
              return false;
            })
            .appendTo(modifyCol);
        }
        modifyCol.appendTo(trSummary);

        $('<td/>').addClass('input summary-heading').attr('colspan', '7').text(MLInvoice.translate('TotalExcludingVAT')).appendTo(trSummary);
        $('<td/>').addClass('input currency').text(MLInvoice.formatCurrency(summary.totSum)).appendTo(trSummary);
        $('<td/>').attr('colspan', '2').appendTo(trSummary);
        $footer.append(trSummary);

        trSummary = $('<tr/>').addClass('summary');
        $('<td/>').addClass('input summary-heading').attr('colspan', '7').text(MLInvoice.translate('TotalVAT')).appendTo(trSummary);
        $('<td/>').addClass('input currency').text(MLInvoice.formatCurrency(summary.totVAT)).appendTo(trSummary);
        $('<td/>').attr('colspan', '2').appendTo(trSummary);
        $footer.append(trSummary);

        trSummary = $('<tr/>').addClass('summary');
        $('<td/>').addClass('input summary-heading').attr('colspan', '13').text(MLInvoice.translate('TotalIncludingVAT')).appendTo(trSummary);
        $('<td/>').addClass('input currency').text(MLInvoice.formatCurrency(summary.totSumVAT)).appendTo(trSummary);
        $('<td/>').attr('colspan', '2').appendTo(trSummary);
        $footer.append(trSummary);

        trSummary = $('<tr/>').addClass('summary');
        $('<td/>').addClass('input summary-heading').attr('colspan', '13').text(MLInvoice.translate('TotalToPay')).appendTo(trSummary);
        $('<td/>').addClass('input currency').text(MLInvoice.formatCurrency(summary.totSumVAT + summary.partialPayments)).appendTo(trSummary);
        $('<td/>').attr('colspan', '2').appendTo(trSummary);
        $footer.append(trSummary);

        if (summary.totWeight > 0) {
          trSummary = $('<tr/>').addClass('summary');
          $('<td/>').addClass('input summary-heading').attr('colspan', '13').text(MLInvoice.translate('ProductWeight')).appendTo(trSummary);
          $('<td/>').addClass('input currency').text(MLInvoice.formatCurrency(summary.totWeight, 3)).appendTo(trSummary);
          $('<td/>').attr('colspan', '2').appendTo(trSummary);
          $footer.append(trSummary);
        }
      }
      MLInvoice.updateRowSelectedState();

      var $headings = $table.find('thead th');
      $table.find('tbody tr:not(.summary)').each(function handleRow() {
        var $cols = $(this).find('td');
        for (var i = 0; i < $cols.length; i++) {
          // Use attr() instead of data(), the latter won't do it
          $($cols.get(i)).attr('data-th', $($headings.get(i)).text().trim() + ' ');
        }
      });

      if (!readOnly && 'invoice' === formType) {
        Sortable.create(
          $('#itable > tbody').get(0),
          {
            direction: 'vertical',
            draggable: 'tr.item-row',
            handle: '.sort-col',
            onEnd: function onSortStop() {
              that.updateRowOrder();
            }
          }
        );
      }

      $('#iform')
        .find('input[type="text"],input[type="date"],input[type="hidden"],input[type="checkbox"]:not(.cb-select-row):not(.cb-select-all),select:not(.dropdownmenu),textarea')
        .on('change', function onRowFieldChange() { MLInvoice.highlightButton('.row-add-button', true); });
      $('#iform')
        .find('input[type="text"],input[type="date"],input[type="hidden"],input[type="checkbox"],select:not(.dropdownmenu),textarea')
        .one('change', startChanging);

      $('#iform_popup')
        .find('input[type="text"],input[type="date"],input[type="hidden"]:not(.select-default-text),input[type="checkbox"],select:not(.dropdownmenu),textarea')
        .on('change', function onPopupFieldChange() {
          $(this).parent().parent().find('.modification-indicator').removeClass('hidden');
          $(this).data('modified', 1);
        });

      if (typeof doneCallback !== 'undefined') {
        doneCallback();
      }

      if (subFormConfig.dispatchByDateButtons) {
        that.updateDispatchByDateButtons(json.records);
      }
    });
  }

  function saveRow(formId, copy)
  {
    MLInvoice.clearMessages();
    var form = $('#' + formId);
    var obj = {};
    var rowId = (typeof copy !== 'undefined' && copy) ? 0 : form.data('rowId');
    $.each(_subFormConfig.fields, function processField(i, field) {
      var value = form.find('[name=' + formId + '_' + field.name + ']');
      switch (field.type) {
      case 'CHECK':
        obj[field.name] = value.prop('checked');
        break;
      case 'INT':
        obj[field.name] = value.val().replace(MLInvoice.translate('DecimalSeparator'), '.');
        break;
      case 'INTDATE':
        obj[field.name] = value.val();
        break;
      case 'SEARCHLIST':
      case 'LIST':
      case 'TEXT':
      case 'PASSWD':
      case 'PASSWD_STORED':
        obj[field.name] = value.val();
        break;
      case 'TAGS':
        obj[field.name] = [];
        $.each(value.select2('data'), function processOptions(idx, opt) {
          obj[field.name].push(opt.id);
        });
        break;
      case 'AREA':
        if (value.hasClass('markdown')) {
          var mde = value.data('mde');
          obj[field.name] = (typeof mde !== 'undefined') ? mde.value() : value.val();
        } else {
          obj[field.name] = value.val();
        }
        break;
      }
    });
    obj[_subFormConfig.parentKey] = _formConfig.id;
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
          MLInvoice.highlightButton('.row-add-button', false);
          that.initRows();
          if (form.data('popup')) {
            bootstrap.Modal.getInstance($('#popup_edit').get(0)).hide();
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
                value.val(today.toISOString());
              } else if (subFormConfig.clearAfterRowAdded) {
                switch (field.type) {
                case 'LIST':
                  value.val([]);
                  break;
                case 'SEARCHLIST':
                  value.select2('val', '');
                  break;
                case 'TAGS':
                  value.find('option').remove();
                  value.trigger('change');
                  break;
                case 'CHECK':
                  value.prop('checked', false);
                  break;
                case 'INT':
                case 'INTDATE':
                case 'TEXT':
                  value.val('');
                  break;
                case 'AREA':
                  if (value.hasClass('markdown')) {
                    var mde = value.data('mde');
                    if (typeof mde !== 'undefined') {
                      mde.value('');
                    } else {
                      value.val('');
                    }
                  } else {
                    value.val('');
                  }
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
      case 'INTDATE':
      case 'LIST':
      case 'TEXT':
        obj[field.name] = elem.val();
        break;
      case 'AREA':
        if (elem.hasClass('markdown')) {
          var mde = elem.data('mde');
          obj[field.name] = (typeof mde !== 'undefined') ? mde.value() : elem.val();
        } else {
          obj[field.name] = elem.val();
        }
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
          bootstrap.Modal.getInstance($('#popup_edit').get(0)).hide();
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
    var orderno = 1;
    $('.cb-select-row').each(function eachRow() {
      req.order[this.value] = orderno;
      orderno += 1;
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
          bootstrap.Modal.getInstance($('#popup_edit').get(0)).hide();
        }
      }
    });
  }

  function popupEditor(event, title, id)
  {
    startChanging();
    $('#iform_popup .modification-indicator').addClass('hidden');
    $('#iform_popup input').data('modified', '');
    var subFormConfig = _subFormConfig;
    $.getJSON('json.php?func=get_' + _subFormConfig.type + '&id=' + id, function initPopupEditor(json) {
      if (!json.id) {
        return;
      }
      var form = $('#iform_popup');

      form.data('rowId', id);
      $.each(subFormConfig.fields, function initPopupFields(i, field) {
        var elem = form.find('[name=iform_popup_' + field.name + ']');
        switch (field.type) {
        case 'CHECK':
          elem.prop('checked', json[field.name] ? 1 : 0);
          break;
        case 'INT':
          if (typeof field.decimals !== 'undefined') {
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
        case 'PASSWD_STORED':
          elem.val('');
          break;
        case 'TAGS':
          elem.find('option').remove();
          $(json[field.name]).each(function eachTag() {
            var opt = new Option(this, this, true, true);
            elem.append(opt);
          });
          elem.trigger('change');
          break;
        case 'INTDATE':
        case 'LIST':
        case 'TEXT':
          elem.val(json[field.name]);
          break;
        case 'AREA':
          if (elem.hasClass('markdown')) {
            var mde = elem.data('mde');
            if (typeof mde !== 'undefined') {
              mde.value(json[field.name]);
            } else {
              elem.val(json[field.name]);
            }
          } else {
            elem.val(json[field.name]);
          }
          break;
        }
      });
      var $popup = $('#popup_edit');
      $popup.find('.modal-title').text(title);
      $popup.find('.edit-single-buttons').removeClass('hidden');
      $popup.find('.edit-multi-buttons').addClass('hidden');

      var bsModal = new bootstrap.Modal($popup.get(0));
      bsModal.show();

      // Setup select2 only after modal is shown to ensure it gets the correct parent:
      setupSelect2($popup);

      // Reset change indicators that could have been triggered during setup:
      $('#iform_popup .modification-indicator').addClass('hidden');
      $('#iform_popup input').data('modified', '');
    });
  }

  function multiEditor(event, title)
  {
    startChanging();
    var form = $('#iform_popup');
    $.each(_subFormConfig.fields, function initPopupFields(i, field) {
      var elem = form.find('[name=iform_popup_' + field.name + ']');
      switch (field.type) {
      case 'CHECK':
        elem.prop('checked', false);
        break;
      case 'SEARCHLIST':
        elem.val(null).trigger('change');
        break;
      case 'TAGS':
        elem.find('option').remove();
        elem.trigger('change');
        break;
      case 'PASSWD_STORED':
      case 'INT':
      case 'INTDATE':
      case 'LIST':
      case 'TEXT':
        elem.val('');
        break;
      case 'AREA':
        if (elem.hasClass('markdown')) {
          var mde = elem.data('mde');
          if (typeof mde !== 'undefined') {
            mde.value('');
          } else {
            elem.val('');
          }
        } else {
          elem.val('');
        }
        break;
      }
    });
    form.find('.modification-indicator').addClass('hidden');
    var $popup = $('#popup_edit');
    $popup.find('.modal-title').text(title);
    $popup.find('.edit-single-buttons').addClass('hidden');
    $popup.find('.edit-multi-buttons').removeClass('hidden');

    var bsModal = new bootstrap.Modal($popup.get(0));
    bsModal.show();

    // Setup select2 only after modal is shown to ensure it gets the correct parent:
    setupSelect2($popup);

    $('#iform_popup .modification-indicator').addClass('hidden');
    $('#iform_popup input').data('modified', 0);
    $('#iform_popup select').data('modified', 0);
  }

  function startChanging()
  {
    if (_formConfig.type === 'invoice' && !_formConfig.modificationWarningShown && _formConfig.modificationWarning) {
      alert(_formConfig.modificationWarning);
      _formConfig.modificationWarningShown = true;
    }
  }

  function _addCompany()
  {
    var $dlg = $('#quick_add_company');
    var bsModal = new bootstrap.Modal($dlg.get(0));
    bsModal.show();
  }

  function _saveCompany()
  {
    var obj = {};
    obj.company_name = document.getElementById('quick_name').value;
    obj.company_id = document.getElementById('quick_vat_id').value;
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
          alert(MLInvoice.translate('ErrValueMissing') + ': ' + data.missing_fields);
        } else {
          _initCompanyList(data.id);
          bootstrap.Modal.getInstance($('#quick_add_company').get(0)).hide();
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


  function _addPartialPayment()
  {
    var $dlg = $('#add_partial_payment');
    var bsModal = new bootstrap.Modal($dlg.get(0));
    bsModal.show();
  }

  function _addReminderFees()
  {
    $.getJSON(
      'json.php?func=add_reminder_fees&id=' + $('#record_id').val(),
      function onAddReminderFeesDone(json) {
        if (json.errors) {
          MLInvoice.errormsg(json.errors);
        } else {
          MLInvoice.infomsg(MLInvoice.translate('ReminderFeesAdded'));
        }
        MLInvoice.Form.initRows();
      }
    );
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
          bootstrap.Modal.getInstance($('#add_partial_payment').get(0)).hide();
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
    getSelectedProductDefaults: getSelectedProductDefaults,
    updateStockBalanceLog: updateStockBalanceLog,
    printInvoice: printInvoice,
    updateDispatchByDateButtons: updateDispatchByDateButtons,
    setupSelect2: setupSelect2,
    setupDefaultTextSelection: setupDefaultTextSelection,
    setupMarkdownEditor: setupMarkdownEditor
  };
});
