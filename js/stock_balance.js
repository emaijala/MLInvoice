/* global $ */
/* exported update_stock_balance */
function update_stock_balance(translations)
{
  var buttons = {};
  buttons[translations.save] = function onSaveStockBalance() {
    save_stock_balance(translations);
  };
  buttons[translations.close] = function onCloseStockBalance() {
    $('#update_stock_balance').dialog('close');
  };
  $('#update_stock_balance').dialog(
    {
      modal: true, width: 400, height: 240, resizable: false, zIndex: 900,
      buttons: buttons,
      title: translations.title,
    }
  );
}

function save_stock_balance(translations)
{
  $.ajax({
    url: 'json.php?func=update_stock_balance',
    type: 'POST',
    data: {
      product_id: $('#record_id').val(),
      stock_balance_change: document.getElementById('stock_balance_change').value.replace(translations.decimal_separator, '.'),
      stock_balance_change_desc: document.getElementById('stock_balance_change_desc').value
    },
    success: function updateStockBalanceDone(data) {
      if (data.missing_fields) {
        alert(translations.missing + data.missing_fields);
      } else {
        var new_balance = parseFloat(data.new_stock_balance).toFixed(2).replace('.', translations.decimal_separator);
        $('#stock_balance').val(new_balance);
        update_stock_balance_log();
        $('#update_stock_balance').dialog('close');
      }
    },
    error: function updateStockBalanceFail(XMLHTTPReq, textStatus/*, errorThrown*/) {
      if (textStatus === 'timeout')
      {
        alert('Timeout trying to save stock balance change');
      } else {
        alert('Error trying to save stock balance change: ' + XMLHTTPReq.status + ' - ' + XMLHTTPReq.statusText);
      }
      return false;
    }
  });
}

function update_stock_balance_log()
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
    },
    error: function getStockBalanceRowsFail(XMLHTTPReq, textStatus/*, errorThrown*/) {
      if (textStatus === 'timeout') {
        alert('Timeout trying to save stock balance change');
      } else {
        alert('Error trying to save stock balance change: ' + XMLHTTPReq.status + ' - ' + XMLHTTPReq.statusText);
      }
      return false;
    }
  });
}

