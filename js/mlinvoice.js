/* global $, jQuery */
/* exported MLInvoice */
var MLInvoice = (function MLInvoice() {
  var _modules = [];
  var _initDone = false;
  var _translations = {};
  var _dispatchNotePrintStyle = 'none';
  var _offerStatuses = [];
  var _selectedProduct = null;
  var _defaultDescription = null;
  var _keepAliveEnabled = true;
  var _currencyDecimals = 2;
  var _maxAttachmentOrderNo = 0;

  function addTranslation(key, value) {
    _translations[key] = value;
  }

  function addTranslations(translations) {
    for (var item in translations) {
      if (typeof translations[item] === 'string') {
        addTranslation(item, translations[item]);
      }
    }
  }

  function addModule(moduleName, module) {
    if (typeof this[moduleName] === 'undefined') {
      _modules.push(moduleName);
      this[moduleName] = typeof module === 'function' ? module() : module;
      if (_initDone && this[moduleName].init) {
        this[moduleName].init();
      }
    }
  }

  function translate(key, placeholders, defaultValue) {
    var translated = _translations[key] || key;
    if (translated === key && typeof defaultValue !== 'undefined') {
      translated = defaultValue;
    }
    if (typeof placeholders === 'object') {
      $.each(placeholders, function replacePlaceHolder(pkey, value) {
        translated = translated.replace(new RegExp(pkey, 'g'), value);
      });
    }
    return translated;
  }

  function setDispatchNotePrintStyle(style) {
    _dispatchNotePrintStyle = style;
  }

  function setOfferStatuses(statuses) {
    _offerStatuses = statuses;
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
      this.Form.saveRecord(target, printStyle, true);
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
    var offer = _offerStatuses.indexOf($('#state_id').val()) !== -1;

    var form = $('#admin_form');
    if (typeof form.data('checkInvoiceDate') !== 'undefined') {
      var d = new Date();
      var dt = $('#invoice_date').val().split('.');
      if (parseInt(dt[0], 10) !== d.getDate() || parseInt(dt[1], 10) !== d.getMonth() + 1 || parseInt(dt[2], 10) !== d.getYear() + 1900) {
        if (!confirm(translate('InvoiceDateNonCurrent'))) {
          return false;
        }
      }
    }

    if (!offer) {
      var len = $('#ref_number').val().length;
      if (len > 0 && len < 4) {
        if (!confirm(translate('InvoiceRefNumberTooShort'))) {
          return false;
        }
      }

      if (typeof form.data('checkInvoiceNumber') !== 'undefined') {
        var invoiceNo = String($('#invoice_no').val());
        if (invoiceNo === '' || invoiceNo === '0') {
          if (!confirm(translate('InvoiceNumberNotDefined'))) {
            return false;
          }
        }
      }
    }
    return true;
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
      document.getElementById(form_id + '_price').value = json.unit_price ? formatCurrency(unitPrice) : '';
      document.getElementById(form_id + '_discount').value = json.discount ? json.discount.replace('.', ',') : '';
      document.getElementById(form_id + '_discount_amount').value = json.discount_amount ? formatCurrency(json.discount_amount) : '';
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

  function _parseDate(dateString, _sep) {
    if (!dateString) {
      return null;
    }
    var sep = typeof _sep === 'undefined' ? '' : _sep
    return dateString.substr(6, 4) + sep + dateString.substr(3, 2) + sep + dateString.substr(0, 2);
  }

  function _parseFloat(value) {
    var valueString = new String(value);
    return valueString.replace(',', '.');
  }

  function updateDispatchByDateButtons() {
    if (_dispatchNotePrintStyle === 'none' || _offerStatuses.indexOf($('#state_id').val()) !== -1) {
      return;
    }
    var container = $('#dispatch_date_buttons');
    container.empty();
    var dates = [];
    $('#iform td').each(function handleCol(i, td) {
      var field = $(td);
      if (field.data('field') === 'row_date') {
        var date = _parseDate(field.text());
        if (dates.indexOf(date) === -1) {
          dates.push(date);
        }
      }
    });
    dates.sort();
    var onLinkClick = function linkClick() {
      printInvoice(2, 'open_invoices', _dispatchNotePrintStyle, $(this).data('date'));
    };
    for (var i in dates) {
      if (dates.hasOwnProperty(i)) {
        var link = $('<a class="formbuttonlink ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only"/>');
        var date = dates[i];
        link.data('date', date);
        link.click(onLinkClick);
        $('<span class="ui-button-text"/>').text(translate('SettingDispatchNotes') + ' ' + formatDate(date)).appendTo(link);
        container.append(link);
        container.append(' ');
      }
    }
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
      term = window.prompt(translate('SearchYTJPrompt'), term);
      if ('' === term || null === term) {
        return;
      }
      // Try business ID first
      var businessId = term.replace(/FI-?/i, '');
      jQuery.ajax(
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
          jQuery.ajax(
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
              window.alert(translate('NoYTJResultsFound'));
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
        jQuery.ajax(
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

  function formatCurrency(value, _decimals) {
    var decimals = 'undefined' === typeof _decimals ? _currencyDecimals : _decimals;
    var decimalSep = translate('DecimalSeparator');
    var thousandSep = translate('ThousandSeparator', [], '');
    var s = parseFloat(value).toFixed(decimals).replace('.', decimalSep);
    if (thousandSep) {
      var parts = s.split(decimalSep);
      var regexp = new RegExp('(\\d+)(\\d{3})' + decimalSep + '?');
      while (regexp.test(parts[0])) {
        parts[0] = parts[0].replace(regexp, '$1' + thousandSep + '$2');
      }
      s = parts[0];
      if (parts.length > 1) {
        s += decimalSep + parts[1];
      }
    }
    return s;
  }

  function _keepAlive() {
    $.getJSON('json.php?func=noop').done(function noopDone() {
      window.setTimeout(_keepAlive, 60 * 1000);
    });
  }

  function setKeepAlive(enable) {
    _keepAliveEnabled = enable;
  }

  function setCurrencyDecimals(value) {
    _currencyDecimals = value;
  }

  function ajaxErrorHandler(XMLHTTPReq) {
    $('#spinner').addClass('hidden');
    if (XMLHTTPReq.status == 409) {
      errormsg(jQuery.parseJSON(XMLHTTPReq.responseText).warnings);
    } else {
      errormsg('Error trying to access the server: ' + XMLHTTPReq.status + ' - ' + XMLHTTPReq.statusText);
    }
    return false;
  }

  function _setupSelectAll() {
    $('.cb-select-all').click(function selectAllClick() {
      var table = $(this).closest('table');
      table.find('.cb-select-row').prop('checked', $(this).prop('checked'));
      updateRowSelectedState(table.closest('.list_container'));
    });
  }

  function _setupCoverLetterForm() {
    $('#cover-letter-button').click(function coverLetterClick() {
      $('#cover-letter-form').toggleClass('hidden');
    });
    $('#cover-letter-form .close-btn').click(function coverLetterCloseClick() {
      $('#cover-letter-form').addClass('hidden');
    });
  }

  function _setupCustomPricesForm() {
    $('#add-custom-prices').click(function addCustomPricesClick() {
      $('#no-custom-prices').addClass('hidden');
      $('#custom-prices-form').removeClass('hidden');
    });
    $('#custom-prices-form .save-button').click(function saveCustomPricesClick() {
      var form = $('#custom-prices-form');
      var values = {
        company_id: $('#company_id').val(),
        discount: _parseFloat(form.find('#discount').val()),
        multiplier: _parseFloat(form.find('#multiplier').val()),
        valid_until: _parseDate(form.find('#valid_until').val())
      };
      $.ajax({
        url: 'json.php?func=put_custom_prices',
        type: 'POST',
        dataType: 'json',
        data: JSON.stringify(values),
        contentTypes: 'application/json; charset=utf-8',
        success: function saveCustomPricesDone(/*data*/) {
          infomsg(translate('RecordSaved'), 2000);
          window.location.reload();
        }
      });
      return false;
    });
    $('#custom-prices-form .delete-button').click(function deleteCustomPricesClick() {
      if (!confirm(translate('ConfirmDelete'))) {
        return;
      }
      var values = {
        company_id: $('#company_id').val(),
      };
      $.ajax({
        url: 'json.php?func=delete_custom_prices',
        type: 'POST',
        dataType: 'json',
        data: JSON.stringify(values),
        contentType: 'application/json; charset=utf-8',
        success: function deleteCustomPricesDone(/*data*/) {
          infomsg(translate('RecordDeleted'), 2000);
          window.location.reload();
        }
      });
      return false;
    });
  }

  function editUnitPrice()
  {
    return _editUnitPrice(this);
  }

  function _editUnitPrice(cell)
  {
    var $item = $(cell);
    var $tr = $item.parents('tr');
    var $table = $item.parents('table').dataTable();
    var origValue = $item.text();
    var rowValues = $table.fnGetData($tr);

    var cancelEdit = function cancelEdit() {
      $item.text(origValue);
      $item.removeClass('editing');
    }

    var saveEdit = function saveEdit() {
      var $input = $item.find('input');
      $input.css('width', $item.innerWidth() - 36)
      $item.append('<img src="images/spinner.gif" alt="">');
      var value = String($input.val());
      if (value === origValue) {
        cancelEdit();
        return;
      }
      var values = {
        company_id: $('#company_id').val(),
        product_id: rowValues[0],
        unit_price: _parseFloat(value)
      };
      $.ajax({
        url: 'json.php?func=' + ('' === value ? 'delete_custom_price' : 'put_custom_price'),
        type: 'POST',
        dataType: 'json',
        data: JSON.stringify(values),
        contentType: 'application/json; charset=utf-8',
        success: function customPriceDone(data) {
          $item.removeClass('editing');
          var newPrice = data.unit_price !== null ? formatCurrency(data.unit_price) : $table.fnGetData($item.prev().get(0));
          if ('' === value) {
            $item.text(newPrice);
            $tr.removeClass('custom-price');
          } else {
            $item.text(newPrice);
            $tr.addClass('custom-price');
          }
        },
        error: function customPriceFail() {
          $item.text(value);
          $item.removeClass('editing');
        }
      });
    }

    var $input = $('<input/>').attr('type', 'text').attr('value', origValue)
      .css('width', $item.innerWidth() - 12)
      .keydown(function customPriceKeyDown(event) {
        if (event.which === 13) {
          $(this).data('handled', true);
          saveEdit();
        } else if (event.which === 27) {
          $(this).data('handled', true);
          cancelEdit();
        } else if (event.which === 9) {
          var $editables = $('td.editable');
          var index = $editables.index(cell);
          if (event.shiftKey) {
            if (index > 0) {
              $editables[index - 1].click();
            }
          } else if ($editables.length > index + 1) {
            $editables[index + 1].click();
          }
          return false;
        }
      })
      .click(function customPriceClick() {
        return false;
      })
      .blur(function customPriceBlur(/*event*/) {
        if (!$(this).data('handled')) {
          saveEdit();
        }
      });
    $item.empty().addClass('editing').append($input);
    $input.select().focus();
    return false;
  }

  function updateRowSelectedState(_container) {
    var $container = typeof _container === 'undefined' ? $('body') : $(_container);
    var disabled = $container.find('.cb-select-row:checked').length === 0;
    if (disabled) {
      $container.find('.selected-row-button').attr('disabled', 'disabled');
      $container.find('.selected-row-button').addClass('ui-state-disabled');
    } else {
      $container.find('.selected-row-button').removeAttr('disabled');
      $container.find('.selected-row-button').removeClass('ui-state-disabled');
    }
  }

  function _initFormButtons() {
    $('a.form-submit').click(function formButtonClick() {
      var $a = $(this);

      var confirmAction = $a.data('confirm');
      if (confirmAction && !confirm(translate(confirmAction))) {
        return false;
      }

      var formName = $a.data('form');
      var $form = formName ? $('#' + formName) : $('form');
      var target = $a.data('formTarget');
      if (typeof target !== 'undefined') {
        $form.attr('target', target);
      }
      var setField = $a.data('setField');
      if (typeof setField !== 'undefined') {
        var setValue = '1';
        var parts = setField.split('=', 2);
        if (parts.length === 2) {
          setField = parts[0];
          setValue = parts[1];
        }
        $form.find('[name=' + setField + ']').val(setValue);
      }
      $('.save_button').removeClass('ui-state-highlight');
      $form.submit();
      return false;
    });
    $('a.popup-close').click(function popupCloseClick() {
      window.close();
      return false;
    });
    $('a.update-dates').click(function updateDatesClick() {
      $.getJSON(
        'json.php?func=get_invoice_defaults',
        {
          id: $('#record_id').val(),
          invoice_no: $('#invoice_no').val(),
          invoice_date: $('#invoice_date').val(),
          base_id: $('#base_id').val(),
          company_id: $('#company_id').val(),
          interval_type: $('#interval_type').val()
        }, function getInvoiceDefaultsDone(json) {
          $('#invoice_date').val(json.date);
          $('#due_date').val(json.due_date);
          $('#next_interval_date').val(json.next_interval_date);
          $('.save_button').addClass('ui-state-highlight');
        }
      );
      return false;
    });
    $('a.update-invoice-nr').click(function updateInvoiceNrClick() {
      $.getJSON(
        'json.php?func=get_invoice_defaults',
        {
          id: $('#record_id').val(),
          invoice_no: $('#invoice_no').val(),
          invoice_date: $('#invoice_date').val(),
          base_id: $('#base_id').val(),
          company_id: $('#company_id').val(),
          interval_type: $('#interval_type').val()
        }, function getInvoiceDefaultsDone(json) {
          $('#invoice_no').val(json.invoice_no);
          $('#ref_number').val(json.ref_no);
          $('.save_button').addClass('ui-state-highlight');
        }
      );
      return false;
    });
  }

  function infomsg(msg, timeout)
  {
    $.floatingMessage('<span>' + msg + '</span>', {
      position: 'top-right',
      className: 'ui-widget ui-state-highlight',
      show: 'show',
      hide: 'fade',
      stuffEaseTime: 200,
      moveEaseTime: 0,
      time: typeof(timeout) != 'undefined' ? timeout : 10000
    });
  }

  function errormsg(msg, timeout)
  {
    $.floatingMessage('<span>' + msg + '</span>', {
      position: 'top-right',
      className: 'ui-widget ui-state-error',
      show: 'show',
      hide: 'fade',
      stuffEaseTime: 200,
      moveEaseTime: 0,
      time: typeof timeout !== 'undefined' ? timeout : 10000
    });
  }

  function _sortMulti(_a, _b)
  {
    var a = _a.replace(/<.*?>/g, '');
    var b = _b.replace(/<.*?>/g, '');
    var date_re = /^(\d{1,2})\.(\d{1,2})\.(\d{4})$/;
    var am = a.match(date_re);
    var bm = b.match(date_re);
    if (am && bm) {
      var ad = am[3] + '.' + am[2] + '.' + am[1];
      var bd = bm[3] + '.' + bm[2] + '.' + bm[1];
      if (ad !== bd) {
        return ad < bd ? -1 : 1;
      }
      return 0;
    }
    var float_re = /^\\d+[\\.\\,]?\\d*$/;
    if (a.match(float_re) && b.match(float_re)) {
      a = parseFloat(a);
      b = parseFloat(b);
    } else {
      a = a.toLowerCase();
      b = b.toLowerCase();
    }
    if (a !== b) {
      return a < b ? -1 : 1;
    }
    return 0;
  }

  function clearMessages()
  {
    $('.ui-floating-message').trigger('destroy');
  }

  function checkForUpdates(url, currentVersion)
  {
    if ($.cookie('updateversion') && $.cookie('currentversion') === currentVersion) {
      _updateVersionMessage($.parseJSON($.cookie('updateversion')), currentVersion);
      return;
    }
    $.getJSON(url + '?callback=?', function getVersionInfoDone(data) {
      _updateVersionMessage(data, currentVersion);
      $.cookie('currentversion', currentVersion);
    });
  }

  function _compareVersionNumber(_v1, _v2)
  {
    var v1 = _v1.split('.');
    var v2 = _v2.split('.');

    while (v1.length < v2.length) {
      v1.push(0);
    }
    while (v2.length < v1.length) {
      v2.push(0);
    }

    for (var i = 0; i < v1.length; i++)
    {
      if (v1[i] === v2[i]) {
        continue;
      }
      return parseInt(v1[i]) > parseInt(v2[i]) ? 1 : -1;
    }
    return 0;
  }

  function _updateVersionMessage(data, currentVersion)
  {
    var result = _compareVersionNumber(data.version, currentVersion);
    if (result > 0) {
      var title = translate(
        'UpdateAvailableTitle',
        {
          '{version}': data.version,
          '{date}': data.date
        }
      );
      var $span = $('<span/>').attr('title', title).text(translate('UpdateAvailable') + ' ');
      $('<br/>').appendTo($span);
      $('<a/>').attr('href', data.url).text(translate('UpdateInformation')).appendTo($span);
      $('<br/>').appendTo($span);
      $('<a/>').attr('href', 'index.php?func=system&operation=update').text(translate('UpdateNow')).appendTo($span);
      $span.appendTo('#version');
    } else if (result < 0) {
      $('<span/>').text(translate('PrereleaseVersion')).appendTo('#version');
    }
    $.cookie('updateversion', JSON.stringify(data), { expires: 1 });
  }

  function calcRowSum(row)
  {
    var items = row.pcs;
    var price = row.price;
    var discount = row.discount || 0;
    var discountAmount = row.discount_amount || 0;
    var VATPercent = row.vat;
    var VATIncluded = Number(row.vat_included);

    price *= (1 - discount / 100);
    price -= discountAmount;
    var sum = 0;
    var sumVAT = 0;
    var VAT = 0;
    if (VATIncluded === 1) {
      sumVAT = items * price;
      sum = sumVAT / (1 + VATPercent / 100);
      VAT = sumVAT - sum;
    } else {
      sum = items * price;
      VAT = sum * (VATPercent / 100);
      sumVAT = sum + VAT;
    }
    return {
      sum: sum,
      VAT: VAT,
      sumVAT: sumVAT
    };
  }

  function popupDialog(url, on_close, dialog_title, event, width, height)
  {
    var buttons = {};
    buttons[translate('Close')] = function popupClose() {
      $("#popup_dlg").dialog('close');
    };
    $("#popup_dlg").find("#popup_dlg_iframe").html('');
    $("#popup_dlg").dialog({ modal: true, width: width, height: height, resizable: true,
      buttons: buttons,
      title: dialog_title,
      'close': function onPopupClose() { eval(on_close); } // eslint-disable-line no-eval
    }).find("#popup_dlg_iframe").attr("src", url);

    return true;
  }

  function calculateInvoiceRowSummary(records)
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
      $('#company_id_label').html('<a href="index.php?func=companies&list=&form=company&id=' + company_id.val() + '">' + $('#company_id_label').text() + '</a>');
    }
  }

  function _initUI()
  {
    // Calendar fields
    $('input.hasCalendar').each(function setupCalendar() {
      var settings = {};
      if ($(this).data('noFuture')) {
        settings.maxDate = 0;
      }
      $(this).datepicker(settings);
    });
    // Date fields
    $('input.date').each(function setupDate() {
      if ($(this).data('noFuture')) {
        $(this).change(function changeDate() {
          var val = $(this).val();
          if (val.length === 10) {
            var dt = new Date(_parseDate(val, '-'));
            if (dt > new Date()) {
              errormsg(translate('FutureDateEntered'));
            }
          }
        });
      }
    });
    // Main tabs
    $('#maintabs ul li').hover(
      function onHover() {
        $(this).addClass('ui-state-hover');
      },
      function onBlur() {
        $(this).removeClass('ui-state-hover');
      }
    );
    // Page exit data confirmation
    $('#admin_form').find('input[type="text"],input[type="hidden"]:not(.select-default-text),input[type="checkbox"],select:not(.dropdownmenu),textarea')
      .change(function onFormFieldChange() {
        $('.save_button').addClass('ui-state-highlight');
      });
    $(window).bind('beforeunload', function onBeforeUnload(e) {
      if ($('.save_button').hasClass('ui-state-highlight') || $('.row-add-button').hasClass('ui-state-highlight')) {
        e.returnValue = translate('UnsavedData');
        return e.returnValue;
      }
    });
    // AJAX progress and errors
    $(document).ajaxStart(function onAjaxStart() {
      $('#spinner').removeClass('hidden');
    });
    $(document).ajaxStop(function onAjaxStop() {
      $('#spinner').addClass('hidden');
    });
    $(document).ajaxError(function onAjaxError(event, request) {
      ajaxErrorHandler(request);
    });
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

    // Init menus
    $('.dropdownmenu').menu({}).find('li:first').addClass('formbuttonlink ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only');

    // Datatables sorting
    jQuery.fn.dataTableExt.oSort['html-multi-asc'] = function htmlSortAsc(a, b) {
      return _sortMulti(a, b);
    };

    jQuery.fn.dataTableExt.oSort['html-multi-desc'] = function htmlSortDesc(a, b) {
      return -_sortMulti(a, b);
    };
  }

  function updateSendApiButtons()
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
          .attr('title', translate('RemoveAttachment'))
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
        $('<span/>').text(translate('SendToClient')).appendTo($cbLabel);
        $cbLabel.appendTo($attachment);

        var $input = $('<input/>').addClass('attachment-name').attr('type', 'text').data('id', item.id).val(item.name)
          .attr('placeholder', translate('Description'));
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
          $filesize.attr('title', translate('LargeFile'));
        }
        $filesize.appendTo($fileinfo);
        $fileinfo.appendTo($attachment);
        $attachment.appendTo($list);
      });
      if (cnt === 0) {
        $list.text(translate('NoEntries'));
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
              errormsg(translate('ErrValueMissing') + ': ' + data.missing_fields);
            } else {
              _updateAttachmentList();
            }
          }
        });
        $(this).val(null);
      }
    });
  }

  function _setupListMultiSelect() {
    $('.print-selected-rows .print-selected-item').click(function printSelectedClick() {
      var ids = $(this).closest('.list_container').find('.cb-select-row:checked').map(function mapChecked() {
        return 'id[]=' + encodeURIComponent(this.value);
      }).get();
      var target = 'invoice.php?template=' + encodeURIComponent($(this).data('templateId')) + '&' + ids.join('&');
      if ($(this).data('style') === 'openwindow') {
        window.open(target);
      } else {
        window.location.href = target;
      }
      return false;
    });
  }

  function init() {
    _initUI();
    _setupYtjSearch();
    _setupDefaultTextSelection();
    setupSelect2();
    if (_keepAliveEnabled) {
      window.setTimeout(_keepAlive, 60 * 1000);
    }
    _setupSelectAll();
    _setupCoverLetterForm();
    _setupCustomPricesForm();
    _initFormButtons();
    updateSendApiButtons();
    _setupInvoiceAttachments();
    _setupListMultiSelect();
    _initDone = true;
  }

  function updateStockBalance()
  {
    var buttons = {};
    buttons[translate('Save')] = function onSaveStockBalance() {
      saveStockBalance();
    };
    buttons[translate('Close')] = function onCloseStockBalance() {
      $('#update_stock_balance').dialog('close');
    };
    $('#update_stock_balance').dialog(
      {
        modal: true, width: 400, height: 240, resizable: false, zIndex: 900,
        buttons: buttons,
        title: translate('UpdateStockBalance'),
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
        stock_balance_change: document.getElementById('stock_balance_change').value.replace(translate('DecimalSeparator'), '.'),
        stock_balance_change_desc: document.getElementById('stock_balance_change_desc').value
      },
      success: function updateStockBalanceDone(data) {
        if (data.missing_fields) {
          alert(translate('ErrValueMissing') + ': ' + data.missing_fields);
        } else {
          var new_balance = parseFloat(data.new_stock_balance).toFixed(2).replace('.', translate('DecimalSeparator'));
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

  function formatDate(date)
  {
    var dateString = new String(date);
    return dateString.length === 8
      ? dateString.substr(6, 2) + '.' + dateString.substr(4, 2) + '.' + dateString.substr(0, 4)
      : '';
  }

  return {
    init: init,
    addTranslation: addTranslation,
    addTranslations: addTranslations,
    setDispatchNotePrintStyle: setDispatchNotePrintStyle,
    setOfferStatuses: setOfferStatuses,
    translate: translate,
    printInvoice: printInvoice,
    updateDispatchByDateButtons: updateDispatchByDateButtons,
    getSelectedProductDefaults: getSelectedProductDefaults,
    formatCurrency: formatCurrency,
    setKeepAlive: setKeepAlive,
    setupSelect2: setupSelect2,
    updateRowSelectedState: updateRowSelectedState,
    infomsg: infomsg,
    errormsg: errormsg,
    editUnitPrice: editUnitPrice,
    setCurrencyDecimals: setCurrencyDecimals,
    checkForUpdates: checkForUpdates,
    calcRowSum: calcRowSum,
    popupDialog: popupDialog,
    calculateInvoiceRowSummary: calculateInvoiceRowSummary,
    updateSendApiButtons: updateSendApiButtons,
    clearMessages: clearMessages,
    ajaxErrorHandler: ajaxErrorHandler,
    updateStockBalance: updateStockBalance,
    updateStockBalanceLog: updateStockBalanceLog,
    formatDate: formatDate,
    addModule: addModule
  }
})();
