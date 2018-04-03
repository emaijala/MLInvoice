/* global save_record, formatDate, $, jQuery */
/* exported MLInvoice */
var MLInvoice = (function MLInvoice() {
  var _translations = {};
  var _dispatchNotePrintStyle = 'none';
  var _offerStatuses = [];
  var _selectedProduct = null;
  var _defaultDescription = null;
  var _keepAliveEnabled = true;
  var _currencyDecimals = 2;

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

    var id = $('#record_id').val();
    var target = 'invoice.php?id=' + id + '&template=' + template + '&func=' + func;
    if (typeof date !== 'undefined') {
      target += '&date=' + date;
    }
    if (typeof form.data('readOnly') === 'undefined') {
      save_record(target, printStyle, true);
    } else if (printStyle === 'openwindow') {
      window.open(target);
    } else {
      window.location = target;
    }
    return false;
  }

  function _onChangeCompany(_initialLoad) {
    var initialLoad = typeof _initialLoad === 'undefined' ? false : _initialLoad;
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
        }
        if (json.info) {
          _addCompanyInfoTooltip(json.info);
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
      }
    });
  }

  function _onChangeProduct() {
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
        if (item.value === json.type_id) {
          item.selected = true;
          break;
        }
      }
      var unitPrice = json.custom_price && json.custom_price.unit_price !== null
        ? json.custom_price.unit_price : json.unit_price;
      document.getElementById(form_id + '_price').value = json.unit_price ? formatCurrency(unitPrice) : '';
      document.getElementById(form_id + '_discount').value = json.discount ? json.discount.replace('.', ',') : '';
      document.getElementById(form_id + '_discount_amount').value = json.discount_amount ? formatCurrency(json.discount_amount) : '';
      document.getElementById(form_id + '_vat').value = json.vat_percent ? json.vat_percent.replace('.', ',') : '';
      document.getElementById(form_id + '_vat_included').checked = !!((json.vat_included && json.vat_included === 1));
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
          }
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
              }
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
        width: 'element'
      });
      select.on('change', function selectChange() {
        jQuery.ajax(
          {
            url: 'json.php',
            data: {
              func: 'get_default_value',
              id: select.select2('val')
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
            $('#' + target).text(data.content);
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

  function _setupSelectAll() {
    $('#cb-select-all').click(function selectAllClick() {
      var table = $(this).closest('table');
      table.find('.cb-select-row').prop('checked', $(this).prop('checked'));
      updateRowSelectedState();
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
        data: $.toJSON(values),
        contentTypes: 'application/json; charset=utf-8',
        success: function saveCustomPricesDone(/*data*/) {
          infomsg(translate('RecordSaved'), 2000);
          window.location.reload();
        },
        error: function saveCustomPricesFail(XMLHTTPReq, textStatus/*, errorThrown*/) {
          if (textStatus === 'timeout') {
            errormsg('Timeout trying to save record');
          } else {
            errormsg('Error trying to save record: ' + XMLHTTPReq.status + ' - ' + XMLHTTPReq.statusText);
          }
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
        data: $.toJSON(values),
        contentType: 'application/json; charset=utf-8',
        success: function deleteCustomPricesDone(/*data*/) {
          infomsg(translate('RecordDeleted'), 2000);
          window.location.reload();
        },
        error: function deleteCustomPricesFail(XMLHTTPReq, textStatus/*, errorThrown*/) {
          if (textStatus === 'timeout') {
            errormsg('Timeout trying to delete record');
          } else {
            errormsg('Error trying to delete record: ' + XMLHTTPReq.status + ' - ' + XMLHTTPReq.statusText);
          }
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
        data: $.toJSON(values),
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
        error: function customPriceFail(XMLHTTPReq, textStatus/*, errorThrown*/) {
          if (textStatus === 'timeout') {
            errormsg('Timeout trying to save record');
          } else {
            errormsg('Error trying to save record: ' + XMLHTTPReq.status + ' - ' + XMLHTTPReq.statusText);
          }
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

  function updateRowSelectedState() {
    var disabled = $('.cb-select-row:checked').length === 0;
    if (disabled) {
      $('.selected-row-button').attr('disabled', 'disabled');
      $('.selected-row-button').addClass('ui-state-disabled');
    } else {
      $('.selected-row-button').removeAttr('disabled');
      $('.selected-row-button').removeClass('ui-state-disabled');
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
        $form.find('[name=' + setField + ']').val(1);
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
      time: typeof(timeout) != 'undefined' ? timeout : 5000
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
      time: typeof(timeout) != 'undefined' ? timeout : 5000
    });
  }

  function checkForUpdates(url, currentVersion)
  {
    if ($.cookie('updateversion')) {
      _updateVersionMessage($.parseJSON($.cookie('updateversion')), currentVersion);
      return;
    }
    $.getJSON(url + '?callback=?', function getVersionInfoDone(data) {
      _updateVersionMessage(data, currentVersion);
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
    $.cookie('updateversion', $.toJSON(data), { expires: 1 });
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
    // Buttons
    $('a.actionlink').not('.ui-state-disabled').button();
    $('a.tinyactionlink').button();
    $('a.buttonlink').button();
    $('a.formbuttonlink').button();
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
      if ($('.save_button').hasClass('ui-state-highlight') || $('.add_row_button').hasClass('ui-state-highlight')) {
        e.returnValue = translate('UnsavedData');
        return e.returnValue;
      }
    });
    // AJAX progress and errors
    $('#message').ajaxStart(function onAjaxStart() {
      $('#spinner').css('visibility', 'visible');
    });
    $('#message').ajaxStop(function onAjaxStop() {
      $('#spinner').css('visibility', 'hidden');
    });
    $('#errormsg').ajaxError(function onAjaxError(event, request) {
      MLInvoice.errormsg('Server request failed: ' + request.status + ' - ' + request.statusText);
      $('#spinner').css('visibility', 'hidden');
    });
    // Company info
    if ($('#company_id').val()) {
      _onChangeCompany(true);
    }
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
    calculateInvoiceRowSummary: calculateInvoiceRowSummary
  }
})();
