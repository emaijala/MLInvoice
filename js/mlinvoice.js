var MLInvoice = (function MLInvoice() {
    var _translations = {};
    var _dispatchNotePrintStyle = 'none';
    var _offerStatuses = [];

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

    var setOfferStatuses = function setOfferStatuses(statuses) {
        _offerStatuses = statuses;
    }

    var printInvoice = function printInvoice(template, func, printStyle, date) {
        var offer = _offerStatuses.indexOf($('#state_id').val()) !== -1;

        var form = $('#admin_form');
        if (typeof form.data('checkInvoiceDate') !== 'undefined') {
            var d = new Date();
            var dt = $('#invoice_date').val().split('.');
            if (parseInt(dt[0], 10) != d.getDate() || parseInt(dt[1], 10) != d.getMonth()+1 || parseInt(dt[2], 10) != d.getYear() + 1900) {
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
                var invoiceNo = $('#invoice_no').val();
                if (invoiceNo == '' || invoiceNo == 0) {
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
        if (_dispatchNotePrintStyle == 'none' || _offerStatuses.indexOf($('#state_id').val()) !== -1) {
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
            var link = $('<a class="formbuttonlink ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only"/>');
            var date = dates[i];
            link.data('date', date);
            link.click(function() { printInvoice(2, 'open_invoices', _dispatchNotePrintStyle, $(this).data('date'));});
            $('<span class="ui-button-text"/>').text(translate('SettingDispatchNotes') + ' ' + formatDate(date)).appendTo(link);
            container.append(link);
            container.append(' ');
        }
    }

    var _setupYtjSearch = function setupYtjSearch() {
        var button = $('a.ytj_search_button');
        if (button.length == 0) {
            return;
        }
        button.click(function() {
            var term = window.prompt(translate('SearchYTJPrompt'), '');
            if ('' == term || null == term) {
            return;
            }
            // Try business ID first
            jQuery.ajax(
            {
                url: 'https://avoindata.prh.fi/bis/v1',
                data: {
                maxResults: 1,
                businessId: term
                }
            }
            ).done(function(data) {
            if ('undefined' === typeof data.results[0]) {
                return;
            }
            _fillCompanyForm(data.results[0]);
            }).fail(function(jqXHR, textStatus) {
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
                ).done(function(data) {
                if ('undefined' === typeof data.results[0]) {
                    return;
                }
                _fillCompanyForm(data.results[0]);
                }).fail(function (jqXHR, textStatus) {
                if (404 === jqXHR.status) {
                    window.alert(translate('NoYTJResultsFound'));
                } else {
                    window.alert('Request failed: ' + jqXHR.status + ' - ' + textStatus);
                }
                });
            } else {
                window.alert('Request failed: ' + jqXHR.status + ' - ' + textStatus);
            }
            });
        });
    };

    var _fillCompanyForm = function _fillCompanyForm(data) {
        $('#company_id').val(data.businessId).change();
        $('#company_name').val(data.name);
        $.each(data.addresses, function(idx, address) {
            if (1 != address.version) {
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
        $.each(data.contactDetails, function(idx, contact) {
            if (1 != contact.version) {
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
    };

    var _setupDefaultTextSelection = function _setupDefaultTextSelection() {
        $('.select-default-text').each(function () {
            var target = $(this).data('target');
            var select = $('<input type="hidden" class="select-default-text"/>').appendTo($(this));
            select.select2({
                placeholder: '',
                ajax: {
                    url: 'json.php',
                    dataType: 'json',
                    quietMillis: 200,
                    data: function (term, page) { // page is the one-based page number tracked by Select2
                        return {
                            func: 'get_selectlist',
                            table: 'default_value',
                            q: term,
                            type: $(this).parent().data('type'),
                            pagelen: 50, // page size
                            page: page, // page number
                        };
                    },
                    results: function (data, page) {
                        var records = data.records;
                        return {results: records, more: data.moreAvailable};
                    }
                },
                dropdownCssClass: 'bigdrop',
                dropdownAutoWidth: true,
                escapeMarkup: function (m) { return m; },
                width: 'element'
            });
            select.on('change', function() {
                jQuery.ajax(
                {
                    url: 'json.php',
                    data: {
                        func: 'get_default_value',
                        id: select.select2('val')
                    }
                }
                ).done(function(data) {
                    $('#' + target).text(data.content);
                }).fail(function(jqXHR, textStatus) {
                    window.alert('Request failed: ' + jqXHR.status + ' - ' + textStatus);
                });
            });
        });
    };

    var init = function init() {
        _setupYtjSearch();
        _setupDefaultTextSelection();
    };

    return {
        init: init,
        addTranslation: addTranslation,
        addTranslations: addTranslations,
        setDispatchNotePrintStyle: setDispatchNotePrintStyle,
        setOfferStatuses: setOfferStatuses,
        translate: translate,
        printInvoice: printInvoice,
        updateDispatchByDateButtons: updateDispatchByDateButtons
    }
})();

$(document).ready(function() {
    MLInvoice.init();
});
