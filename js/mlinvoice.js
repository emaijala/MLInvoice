/* global $, bootstrap, Cookies, moment */
/* exported MLInvoice */
var MLInvoice = (function MLInvoice() {
  var _modules = [];
  var _initDone = false;
  var _translations = {};
  var _dispatchNotePrintStyle = 'none';
  var _offerStates = [];
  var _keepAliveEnabled = true;
  var _currencyDecimals = 2;
  var _datePickerDefaults = {};
  var _dateFormat = 'dd.mm.yyyy';

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
    return _dispatchNotePrintStyle;
  }

  function setDatePickerDefaults(defaults) {
    _datePickerDefaults = defaults;
  }

  function getDatePickerDefaults() {
    var settings = _datePickerDefaults;
    settings.singleDatePicker = true;
    settings.ranges = null;
    settings.showCustomRangeLabel = false;
    settings.autoApply = true;
    return settings;
  }

  function getDateRangePickerDefaults() {
    return _datePickerDefaults;
  }

  function setOfferStates(states) {
    _offerStates = states;
  }

  function isOfferStatus(status) {
    return _offerStates.indexOf(status) !== -1;
  }

  function parseDate(dateString, _sep) {
    if (!dateString) {
      return null;
    }
    var sep = typeof _sep === 'undefined' ? '' : _sep
    var date = moment(dateString, _dateFormat);
    return date.format('YYYY' + sep + 'MM' + sep + 'DD');
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

  function setDateFormat(value) {
    _dateFormat = value.toUpperCase();
  }

  function ajaxErrorHandler(XMLHTTPReq) {
    $('#spinner').addClass('hidden');
    if (XMLHTTPReq.status == 409) {
      errormsg(JSON.parse(XMLHTTPReq.responseText).warnings);
    } else {
      errormsg('Error trying to access the server: ' + XMLHTTPReq.status + ' - ' + XMLHTTPReq.statusText);
    }
    return false;
  }

  function _setupSelectAll() {
    $('.cb-select-all').off('click').on('click', function selectAllClick() {
      var table = $(this).closest('table');
      table.find('.cb-select-row').prop('checked', $(this).prop('checked'));
      updateRowSelectedState(table.closest('.list_container'));
    });
  }

  function _setupCoverLetterForm() {
    $('#cover-letter-button').on('click', function coverLetterClick() {
      $('#cover-letter-form').toggleClass('hidden');
    });
    $('#cover-letter-form .close-btn').on('click', function coverLetterCloseClick() {
      $('#cover-letter-form').addClass('hidden');
    });
  }

  function _setupCustomPricesForm() {
    $('#add-custom-prices').on('click', function addCustomPricesClick() {
      $('#no-custom-prices').addClass('hidden');
      $('#custom-prices-form').removeClass('hidden');
    });
    $('#custom-prices-form .save-button').on('click', function saveCustomPricesClick() {
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
    $('#custom-prices-form .delete-button').on('click', function deleteCustomPricesClick() {
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
      $input.css('width', ($item.innerWidth() - 36) + 'px');
      $item.append('<i class="icon-spinner animate-spin"></i>');
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
      .css('width', ($item.innerWidth() - 24) + 'px')
      .on('keydown', function customPriceKeyDown(event) {
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
              $($editables[index - 1]).trigger('click');
            }
          } else if ($editables.length > index + 1) {
            $($editables[index + 1]).trigger('click');
          }
          return false;
        }
      })
      .on('click', function customPriceClick() {
        return false;
      })
      .on('blur', function customPriceBlur(/*event*/) {
        if (!$(this).data('handled')) {
          saveEdit();
        }
      });
    $item.empty().addClass('editing').append($input);
    $input.trigger('select').trigger('focus');
    return false;
  }

  function updateRowSelectedState(_container) {
    var $container = typeof _container === 'undefined' ? $('body') : $(_container);
    var disabled = $container.find('.cb-select-row:checked').length === 0;
    if (disabled) {
      $container.find('.selected-row-button').attr('disabled', 'disabled');
      $container.find('.selected-row-button').addClass('disabled');
    } else {
      $container.find('.selected-row-button').removeAttr('disabled');
      $container.find('.selected-row-button').removeClass('disabled');
    }
  }

  function infomsg(msg, timeout, colorClasses)
  {
    var $toast = $('<div class="toast align-items-center" role="alert" aria-live="polite" aria-atomic="true">');
    if (typeof colorClasses !== 'undefined') {
      $toast.addClass(colorClasses);
    } else {
      $toast.addClass('text-white bg-success');
    }
    var $flex = $('<div class="d-flex">')
      .appendTo($toast);
    $('<div class="toast-body">')
      .text(msg)
      .appendTo($flex);
    $('<button type="button" class="btn-close m-auto me-2" data-bs-dismiss="toast">')
      .attr('aria-label', translate('Close'))
      .appendTo($flex);

    var $toastContainer = $('#toasts');
    if ($toastContainer.length === 0) {
      $toastContainer = $('<div id="toasts" aria-live="polite" class="toast-container position-fixed p-3 top-0 end-0" style="z-index: 5">')
        .appendTo($('body'));
    }

    $toast.appendTo($toastContainer);

    var options = {
      autohide: typeof timeout !== 'undefined',
      delay: typeof timeout !== 'undefined' ? timeout : 0
    };
    var toast = new bootstrap.Toast($toast.get(0), options);
    toast.show();
  }

  function errormsg(msg, timeout)
  {
    infomsg(msg, timeout, 'text-white bg-danger');
  }

  function clearMessages()
  {
    $('#toasts').html('');
  }

  function checkForUpdates(url, currentVersion)
  {
    if (Cookies.get('updateversion') && Cookies.get('currentversion') === currentVersion) {
      _updateVersionMessage(JSON.parse(Cookies.get('updateversion')), currentVersion);
      return;
    }
    $.getJSON(url + '?callback=?', function getVersionInfoDone(data) {
      _updateVersionMessage(data, currentVersion);
      Cookies.set('currentversion', currentVersion);
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
    Cookies.set('updateversion', JSON.stringify(data), { expires: 1 });
  }

  function calcRowSum(row)
  {
    var items = row.partial_payment ? 1 : row.pcs;
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

  function setModalBody(html)
  {
    var $body = $('#popup_dlg .modal-body');
    $body.html(html);
    $body.find('form').on('submit', function handleSubmit(event) {
      var $form = $(this);
      var method = $form.attr('method') || 'GET';
      var action = $form.attr('action') || '';
      var formData = new FormData(this);
      $.ajax({
        type: method,
        url: action,
        processData: false,
        contentType: false,
        data: formData,
        success: function onSuccess(data) {
          setModalBody(data);
        },
        error: function onError() {
          MLInvoice.errormsg('Request failed');
        }
      });
      event.preventDefault();
      return false;
    });

    $body.find('a').off('click').on('click', function handleLink(event) {
      var $a = $(this);
      var url = $a.attr('href');
      $.get(url, setModalBody)
        .fail(function handleError() {
          MLInvoice.errormsg('Request failed');
        });
      event.preventDefault();
      return false;
    });
  }

  function popupDialog(url, on_close, dialog_title)
  {
    var $dlg = $('#popup_dlg');
    var $body = $dlg.find('.modal-body');
    $body.html('<i class="icon-spinner animate-spin"></i>');
    $dlg.find('.modal-title').text(dialog_title);
    $dlg.off('hidden.bs.modal').on('hidden.bs.modal', on_close);

    var bsModal = new bootstrap.Modal($dlg.get(0));
    bsModal.show();

    $.get(url, setModalBody)
      .fail(function handleError() {
        MLInvoice.errormsg('Dialog contents could not be loaded');
      });

    return true;
  }

  function _initUI()
  {
    // Calendar fields
    $('input.hasCalendar').each(function setupCalendar() {
      var settings = getDatePickerDefaults();
      if ($(this).data('noFuture')) {
        settings.maxDate = moment();
      }
      $(this).daterangepicker(settings);
    });
    // Date fields
    $('input.date').each(function setupDate() {
      if ($(this).data('noFuture')) {
        $(this).on('change', function changeDate() {
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
    // Page exit data confirmation
    $('#admin_form').find('input[type="text"],input[type="hidden"]:not(.select-default-text),input[type="checkbox"],select:not(.dropdownmenu),textarea')
      .on('change', function onFormFieldChange() {
        highlightButton('.save_button', true);
      });
    $(window).on('beforeunload', function onBeforeUnload(e) {
      if (isHighlighted('.save_button') || isHighlighted('.row-add-button')) {
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
    $('.print-selected-rows .print-selected-item').on('click', function printSelectedClick() {
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

    _setupSelectAll();
    _setupCoverLetterForm();
    _setupCustomPricesForm();
    _setupListMultiSelect();
    _setupFormButtons();
    _initDone = true;
  }

  function _setupFormButtons() {
    $('.form-submit').on('click', function formButtonClick() {
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
      highlightButton('.save_button', false);
      $form.trigger('submit');
      return false;
    });
    $('button.popup-close').on('click', function popupCloseClick() {
      window.close();
      return false;
    });
    $('[data-form-cancel]').on('click', function formCancel() {
      if (window.opener) {
        window.close();
      } else {
        history.back();
      }
    });
    $('a.update-dates').on('click', function updateDatesClick() {
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
          highlightButton('.save_button', true);
        }
      );
      return false;
    });
    $('a.update-invoice-nr').on('click', function updateInvoiceNrClick() {
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
          highlightButton('.save_button', true);
        }
      );
      return false;
    });
  }

  function formatDate(date)
  {
    var dateString = new String(date);
    if (dateString.length === 8) {
      dateString = dateString.substr(0, 4) + '-' + dateString.substr(4, 2) + '-' + dateString.substr(6, 2);
    }
    return moment(dateString).format(_dateFormat);
  }

  function initTableExportButtons(table) {
    var buttons = new $.fn.dataTable.Buttons(table, {
      buttons: [
        'copy',
        'csv',
        $.extend(
          true,
          {},
          {
            exportOptions: {
              format: {
                body: function formatCell (data, row, column, node) {
                  var exp = $(node).data('export');
                  if (typeof exp !== 'undefined') {
                    return exp;
                  }
                  var sort = $(node).data('sort');
                  return typeof sort !== 'undefined' ? sort : data;
                }
              }
            }
          },
          {
            extend: 'excelHtml5'
          }
        ),
        'pdf'
      ]
    });

    buttons.container().appendTo($('#DataTables_Table_0_length'));
  }

  function highlightButton(_button, highlight)
  {
    var $button = $(_button);
    var className = 'primary';
    if ($button.hasClass('btn-secondary')) {
      className = 'secondary';
    }
    if (highlight) {
      $button.removeClass('btn-outline-' + className).addClass('btn-' + className).addClass('text-light');
    } else {
      $button.removeClass('btn-' + className).removeClass('text-light').addClass('btn-outline-' + className);
    }
  }

  function isHighlighted(_button)
  {
    var $button = $(_button);
    return $button.hasClass('btn-primary') || $button.hasClass('btn-secondary');
  }

  function updateBaseLogo()
  {
    var $logo = $('#logo');
    var $noLogo = $('#no_logo');
    var id = $('#record_id').val();
    if (!id) {
      return;
    }
    $.get('json.php?func=get_base&id=' + id, function handleResult(data) {
      if (data.logo_filename && data.logo_filesize && data.logo_filetype && data.logo_filedata) {
        $logo.find('img')
          .attr('src', 'data:image/' + data.logo_filetype + ';base64,' + data.logo_filedata)
        $logo.removeClass('hidden');
        $noLogo.addClass('hidden');
      } else {
        $logo.addClass('hidden');
        $noLogo.removeClass('hidden');
      }
    });
  }


  return {
    init: init,
    addTranslation: addTranslation,
    addTranslations: addTranslations,
    setDispatchNotePrintStyle: setDispatchNotePrintStyle,
    getDispatchNotePrintStyle: getDispatchNotePrintStyle,
    setDatePickerDefaults: setDatePickerDefaults,
    getDatePickerDefaults: getDatePickerDefaults,
    getDateRangePickerDefaults: getDateRangePickerDefaults,
    setOfferStates: setOfferStates,
    isOfferStatus: isOfferStatus,
    translate: translate,
    formatCurrency: formatCurrency,
    setKeepAlive: setKeepAlive,
    updateRowSelectedState: updateRowSelectedState,
    infomsg: infomsg,
    errormsg: errormsg,
    editUnitPrice: editUnitPrice,
    setCurrencyDecimals: setCurrencyDecimals,
    setDateFormat: setDateFormat,
    checkForUpdates: checkForUpdates,
    calcRowSum: calcRowSum,
    popupDialog: popupDialog,
    clearMessages: clearMessages,
    ajaxErrorHandler: ajaxErrorHandler,
    parseDate: parseDate,
    formatDate: formatDate,
    addModule: addModule,
    initTableExportButtons: initTableExportButtons,
    highlightButton: highlightButton,
    updateBaseLogo: updateBaseLogo
  }
})();
