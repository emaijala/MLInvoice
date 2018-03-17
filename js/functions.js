/* global $, jQuery, google, init_rows */
/* exported add_company, add_partial_payment, formatDate, initAddressAutocomplete, intVal, round_number */
$(document).ready(function docReady() {
  // Link from base label
  $('#base_id.linked').change(setup_base_link);
  setup_base_link($('#base_id.linked'));
  $('#base_id').change(update_base_defaults);

  $('#state_id').change(update_base_defaults);

  // Link from company label
  $('#company_id.linked').change(setup_company_link);
  setup_company_link($('#company_id.linked'));

  // Init menus
  $('.dropdownmenu').menu({}).find('li:first').addClass('formbuttonlink ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only');
});

function setup_base_link()
{
  var base_id = $('#base_id.linked');
  if (base_id.val() == '') {
    $('#base_id_label').text($('#base_id_label').text());
  } else {
    $('#base_id_label').html('<a href="index.php?func=settings&list=base&form=base&id=' + base_id.val() + '">' + $('#base_id_label').text() + '</a>');
  }
}

function setup_company_link()
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

function sort_multi(_a, _b)
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

jQuery.fn.dataTableExt.oSort['html-multi-asc'] = function htmlSortAsc(a, b) {
  return sort_multi(a, b);
};

jQuery.fn.dataTableExt.oSort['html-multi-desc'] = function htmlSortDesc(a, b) {
  return -sort_multi(a, b);
};

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

function add_company(translations)
{
  var buttons = {};
  buttons[translations.save] = function onSaveCompany() {
    save_company(translations);
  };
  buttons[translations.close] = function onCloseCompany() {
    $('#quick_add_company').dialog('close');
  };
  $('#quick_add_company').dialog({ modal: true, width: 420, height: 320, resizable: false, zIndex: 900,
    buttons: buttons,
    title: translations.title,
  });
}

function save_company(translations)
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
    data: $.toJSON(obj),
    contentType: 'application/json; charset=utf-8',
    success: function putCompanyDone(data) {
      if (data.missing_fields) {
        alert(translations.missing + data.missing_fields);
      } else {
        init_company_list(data.id);
        $('#quick_add_company').dialog('close');
      }
    },
    'error': function putCompanyFail(XMLHTTPReq, textStatus/*, errorThrown*/) {
      if (textStatus === 'timeout') {
        alert('Timeout trying to save company');
      } else {
        alert('Error trying to save company: ' + XMLHTTPReq.status + ' - ' + XMLHTTPReq.statusText);
      }
      return false;
    }
  });
}

function init_company_list(selected_id)
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

function add_partial_payment(translations)
{
  var buttons = {};
  buttons[translations.save] = function onSavePartialPayment() {
    save_partial_payment(translations);
  };
  buttons[translations.close] = function onClosePartialPayment() {
    $('#add_partial_payment').dialog('close');
  };
  $('#add_partial_payment').dialog({ modal: true, width: 420, height: 160, resizable: false, zIndex: 900,
    buttons: buttons,
    title: translations.title,
  });
}

function save_partial_payment(translations)
{
  var obj = {};
  obj.invoice_id = $('#record_id').val();
  obj.description = translations.partial_payment;
  obj.row_date = $('#add_partial_payment_date').val();
  obj.price = -parseFloat($('#add_partial_payment_amount').val().replace(translations.decimal_separator, '.'));
  obj.pcs = 0;
  obj.vat = 0;
  obj.vat_included = 0;
  obj.order_no = 100000;
  obj.partial_payment = 1;
  $.ajax({
    url: 'json.php?func=put_invoice_row',
    type: 'POST',
    dataType: 'json',
    data: $.toJSON(obj),
    contentType: 'application/json; charset=utf-8',
    success: function putInvoiceRowDone(data) {
      if (data.missing_fields) {
        alert(translations.missing + data.missing_fields);
      } else {
        init_rows();
        $('#add_partial_payment').dialog('close');
      }
    },
    'error': function putInvoiceRowFail(XMLHTTPReq, textStatus/*, errorThrown*/) {
      if (textStatus === 'timeout') {
        alert('Timeout trying to add a partial payment');
      } else {
        alert('Error trying to add a partial payment: ' + XMLHTTPReq.status + ' - ' + XMLHTTPReq.statusText);
      }
      return false;
    }
  });
}

function round_number(num, dec)
{
  return Math.round(num * Math.pow(10, dec)) / Math.pow(10, dec);
}

function update_base_defaults()
{
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
        },
        'error': ajaxErrorHandler
      });
    },
    'error': ajaxErrorHandler
  });
}

function ajaxErrorHandler(XMLHTTPReq, textStatus/*, errorThrown*/)
{
  if (textStatus === 'timeout') {
    alert('Timeout trying to fetch a record from the server');
  } else {
    alert('Error trying to fetch a record from the server: ' + XMLHTTPReq.status + ' - ' + XMLHTTPReq.statusText);
  }
}

// Remove the formatting to get integer data for summation
function intVal(i) {
  if (typeof i === 'string') {
    return i.replace(/[.,]/g, '') * 1;
  } else if (typeof i === 'number') {
    return i * 1;
  }
  return 0;
}

function formatDate(date)
{
  var dateString = new String(date);
  return dateString.substr(6, 2) + '.' + dateString.substr(4, 2) + '.' + dateString.substr(0, 4);
}
