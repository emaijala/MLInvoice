var MLInvoice = (function MLInvoice() {
    var _translations = {};
    var _dispatchNotePrintStyle = 'none';

    var addTranslation = function addTranslation(key, value) {
        _translations[key] = value;
    };

    var addTranslations = function addTranslations(translations) {
        for (var item in translations) {
            if (typeof translations[item] === 'string') {
                addTranslation(item, translations[item]);
            }
        }
    };

    var translate = function translate(key) {
        return _translations[key] || key;
    };

    var setDispatchNotePrintStyle = function setDispatchNotePrintStyle(style) {
        _dispatchNotePrintStyle = style;
    }

    var printInvoice = function printInvoice(template, func, printStyle, date) {
        var form = $('#admin_form');

        var len = $('#ref_number').val().length;
        if (len > 0 && len < 4) {
            if (!confirm(translate('InvoiceRefNumberTooShort'))) {
                return false;
            }
        }

        if (typeof form.data('checkInvoiceDate') !== 'undefined') {
            var d = new Date();
            var dt = $('#invoice_date').val().split('.');
            if (parseInt(dt[0], 10) != d.getDate() || parseInt(dt[1], 10) != d.getMonth()+1 || parseInt(dt[2], 10) != d.getYear() + 1900) {
                if (!confirm(translate('InvoiceDateNonCurrent'))) {
                    return false;
                }
            }
        }

        if (typeof form.data('checkInvoiceNumber') !== 'undefined') {
            var invoiceNo = $('#invoice_no').val();
            if (invoiceNo == '' || invoiceNo == 0) {
                if (!confirm(translate('InvoiceNumberNotDefined'))) {
                    return false;
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
        } else {
            if (printStyle == 'openwindow') {
                window.open(target);
            } else {
                window.location = target;
            }
        }
        return false;
    };

    function _parseDate(dateString)
    {
        return dateString.substr(6, 4) + dateString.substr(3, 2) + dateString.substr(0, 2);
    }

    var updateDispatchByDateButtons = function updateDispatchDateButtons() {
        if (_dispatchNotePrintStyle == 'none') {
            return;
        }
        var container = $('#dispatch_date_buttons');
        container.empty();
        var id = $('#record_id').val();
        var dates = [];
        $('#iform td').each(function(i, td) {
            var field = $(td);
            if (field.data('field') == 'row_date') {
                var date = _parseDate(field.text());
                if (dates.indexOf(date) == -1) {
                    dates.push(date);
                }
            }
        });
        dates.sort();
        for (var i in dates) {
            // TODO: Print style and use printInvoice!
            var link = $('<a class="formbuttonlink ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only"/>');
            var date = dates[i];
            link.data('date', date);
            link.click(function() { printInvoice(2, 'open_invoices', _dispatchNotePrintStyle, $(this).data('date'));});
            $('<span class="ui-button-text"/>').text(translate('SettingDispatchNotes') + ' ' + formatDate(date)).appendTo(link);
            //container.append('<a class="formbuttonlink ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only" href="invoice.php?id=' + id + '&amp;template=2&amp;func=open_invoices&date='+ dates[i] +'"><span class="ui-button-text">' + translate('SettingDispatchNotes') + ' ' + formatDate(dates[i]) + '</span></a> ');
            container.append(link);
            container.append(' ');
        }
    }

    return {
        addTranslation: addTranslation,
        addTranslations: addTranslations,
        setDispatchNotePrintStyle: setDispatchNotePrintStyle,
        translate: translate,
        printInvoice: printInvoice,
        updateDispatchByDateButtons: updateDispatchByDateButtons
    }
})();
