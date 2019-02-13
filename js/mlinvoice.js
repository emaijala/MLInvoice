/* global $, jQuery */
/* exported MLInvoice */
var MLInvoice = (function MLInvoice() {
  var _modules = [];
  var _initDone = false;
  var _translations = {};
  var _dispatchNotePrintStyle = 'none';
  var _offerStatuses = [];
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

  function getDispatchNotePrintStyle() {
    return _dispatchNotePrintStyle
  }

  function setOfferStatuses(statuses) {
    _offerStatuses = statuses;
  }

  function isOfferStatus(status) {
    return _offerStatuses.indexOf(status) !== -1;
  }

  function parseDate(dateString, _sep) {
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
        valid_until: parseDate(form.find('#valid_until').val())
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
            var dt = new Date(parseDate(val, '-'));
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
    if (_keepAliveEnabled) {
      window.setTimeout(_keepAlive, 60 * 1000);
    }

    // Init menus
    $('.dropdownmenu').menu({}).find('li:first').addClass('formbuttonlink ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only');

    _setupSelectAll();
    _setupCoverLetterForm();
    _setupCustomPricesForm();
    _setupListMultiSelect();
    _setupFormButtons();
    _initDone = true;
  }

  function _setupFormButtons() {
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
    getDispatchNotePrintStyle: getDispatchNotePrintStyle,
    setOfferStatuses: setOfferStatuses,
    isOfferStatus: isOfferStatus,
    translate: translate,
    formatCurrency: formatCurrency,
    setKeepAlive: setKeepAlive,
    updateRowSelectedState: updateRowSelectedState,
    infomsg: infomsg,
    errormsg: errormsg,
    editUnitPrice: editUnitPrice,
    setCurrencyDecimals: setCurrencyDecimals,
    checkForUpdates: checkForUpdates,
    calcRowSum: calcRowSum,
    popupDialog: popupDialog,
    clearMessages: clearMessages,
    ajaxErrorHandler: ajaxErrorHandler,
    parseDate: parseDate,
    formatDate: formatDate,
    addModule: addModule
  }
})();
