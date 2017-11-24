var MLInvoice = (function MLInvoice() {
    var _translations = {};
    var _dispatchNotePrintStyle = 'none';
    var _offerStatuses = [];
    var _selectedProduct = null;
    var _defaultDescription = null;
    var _keepAliveEnabled = true;

    function addTranslation(key, value) {
        _translations[key] = value;
    };

    function addTranslations(translations) {
        for (var item in translations) {
            if (typeof translations[item] === 'string') {
                addTranslation(item, translations[item]);
            }
        }
    };

    var translate = function translate(key, placeholders) {
        var translated = _translations[key] || key;
        if (typeof placeholders === 'object') {
            $.each(placeholders, function(key, value) {
                translated = translated.replace(new RegExp(key, 'g'), value);
            });
        }
        return translated;
    };

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

    function _onChangeCompany() {
        $.getJSON('json.php?func=get_company', {id: $('#company_id').val() }, function(json) {
        if (json) {
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
                $.getJSON('json.php?func=get_invoice_defaults', {id: $('#record_id').val(), invoice_no: $('#invoice_no').val(), invoice_date: $('#invoice_date').val(), base_id: $('#base_id').val(), company_id: $('#company_id').val(), interval_type: $('#interval_type').val()}, function(json) {
                    $('#due_date').val(json.due_date);
                });
            }
        }
        });
    };

    function _onChangeProduct() {
        var form_id = this.form.id;
        $.getJSON('json.php?func=get_product&id=' + this.value, function(json) {
            _selectedProduct = json;
            if (!json || !json.id) return;

            if (json.description != '' || document.getElementById(form_id + '_description').value == (null !== _defaultDescription ? _defaultDescription : '')) {
                document.getElementById(form_id + '_description').value = json.description;
            }
            _defaultDescription = json.description;

            var type_id = document.getElementById(form_id + '_type_id');
            for (var i = 0; i < type_id.options.length; i++) {
                var item = type_id.options[i];
                if (item.value == json.type_id) {
                    item.selected = true;
                    break;
                }
            }
            document.getElementById(form_id + '_price').value = json.unit_price ? formatCurrency(json.unit_price) : '';
            document.getElementById(form_id + '_discount').value = json.discount ? json.discount.replace('.', ',') : '';
            document.getElementById(form_id + '_discount_amount').value = json.discount_amount ? formatCurrency(json.discount_amount) : '';
            document.getElementById(form_id + '_vat').value = json.vat_percent ? json.vat_percent.replace('.', ',') : '';
            document.getElementById(form_id + '_vat_included').checked = (json.vat_included && json.vat_included == 1) ? true : false;
        });
    };

    function getSelectedProductDefaults(form_id) {
        if (null === _selectedProduct) {
            return;
        }
        document.getElementById(form_id + '_description').value = _selectedProduct.description;
        _defaultDescription = _selectedProduct.description;

        var type_id = document.getElementById(form_id + '_type_id');
        for (var i = 0; i < type_id.options.length; i++) {
            var item = type_id.options[i];
            if (item.value == _selectedProduct.type_id) {
                item.selected = true;
                break;
            }
        }
        document.getElementById(form_id + '_price').value = _selectedProduct.unit_price.replace('.', ',');
        document.getElementById(form_id + '_discount').value = _selectedProduct.discount.replace('.', ',');
        document.getElementById(form_id + '_vat').value = _selectedProduct.vat_percent.replace('.', ',');
        document.getElementById(form_id + '_vat_included').checked = _selectedProduct.vat_included == 1 ? true : false;
    };

    function _parseDate(dateString) {
        return dateString.substr(6, 4) + dateString.substr(3, 2) + dateString.substr(0, 2);
    };

    function updateDispatchByDateButtons() {
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

    function _setupYtjSearch() {
        var button = $('a.ytj_search_button');
        if (button.length == 0) {
            return;
        }
        button.click(function() {
            var term = $('#company_id').val();
            if (!term) {
                term = $('#company_name').val();
            }
            term = window.prompt(translate('SearchYTJPrompt'), term);
            if ('' == term || null == term) {
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

    function _fillCompanyForm(data) {
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

    function _setupDefaultTextSelection() {
        $('.select-default-text').each(function () {
            var target = $(this).data('target');
            var formParam = $(this).data('sendFormParam');
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
                }).fail(function(jqXHR, textStatus) {
                    window.alert('Request failed: ' + jqXHR.status + ' - ' + textStatus);
                });
            });
        });
    };

    function setupSelect2(container) {
        if ('undefined' === typeof container) {
            container = 'body';
        }
        var callbacks = {
            _onChangeCompany: _onChangeCompany,
            _onChangeProduct: _onChangeProduct
        };
        $(container).find('.select2').each(function () {
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
                    data: function (term, page) {
                        return {
                            q: term,
                            pagelen: 50,
                            page: page
                        };
                    },
                    results: function (data, page) {
                        var records = [];
                        if (tags) {
                            $(data.records).each(function () {
                                records.push({
                                    id: this.text,
                                    text: this.text,
                                    descriptions: []
                                });
                            });
                        } else {
                            records = data.records;
                        }
                        if (showEmpty && page == 1 && data.filter == '') {
                            records.unshift({id: '', text: '-'});
                        }
                        return {results: records, more: data.moreAvailable};
                    }
                },
                initSelection: function(element, callback) {
                    var id = $(element).val();
                    if (id !== '') {
                        $.ajax('json.php?func=get_selectlist&' + query + '&id=' + id, {
                            dataType: "json"
                        }).done(function(data) {
                            callback(data.records[0]);
                        });
                    }
                },
                formatResult: function (object) {
                    var text = $('<div/>').text(object.text).html();
                    $(object.descriptions).each(function () {
                        var desc = $('<div/>').text(this).html();
                        text += '<div class="select-description">' + desc + '</div>';
                    });
                    return text;
                },
                dropdownCssClass: 'bigdrop',
                dropdownAutoWidth: true,
                width: 'element'
            };

            if (tags) {
                $.extend(options, {
                    tags: true,
                    tokenSeparators: [','],
                    createSearchChoice: function (term) {
                        return {
                            id: $.trim(term),
                            text: $.trim(term) + ' (+)'
                        };
                    },
                    initSelection: function (element, callback) {
                        var data = [];
                        var tags = element.val();
                        if (!tags) {
                            return data;
                        }
                        $(tags.split(',')).each(function () {
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
                    formatSelection: function (object) {
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
    };

    function formatCurrency(value, decimals) {
        if (typeof decimals === 'undefined') {
            decimals = 2;
        }
        var decimalSep = translate('DecimalSeparator');
        var thousandSep = translate('ThousandSeparator');
        var s = parseFloat(value).toFixed(decimals).replace('.', decimalSep);
        if (thousandSep) {
            var parts = s.split(decimalSep);
            var regexp = new RegExp('(\d+)(\d{3})' + decimalSep + '?');
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
        $.getJSON('json.php?func=noop').done(function() {
            window.setTimeout(_keepAlive, 60*1000);
        });
    }

    function setKeepAlive(enable) {
        _keepAliveEnabled = enable;
    }

    function _setupSelectAll() {
        $('#cb-select-all').click(function() {
            var table = $(this).closest('table');
            table.find('.cb-select-row').prop('checked', $(this).prop('checked'));
            updateRowSelectedState();
        });
    }

    function _setupCoverLetterForm() {
        $('#cover-letter-button').click(function() {
            $('#cover-letter-form').toggleClass('hidden');
        });
        $('#cover-letter-form .close-btn').click(function() {
            $('#cover-letter-form').addClass('hidden');
        });
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

    function init() {
        _setupYtjSearch();
        _setupDefaultTextSelection();
        setupSelect2();
        if (_keepAliveEnabled) {
            window.setTimeout(_keepAlive, 60*1000);
        }
        _setupSelectAll();
        _setupCoverLetterForm();
    };

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
        updateRowSelectedState: updateRowSelectedState
    }
})();

$(document).ready(function() {
    MLInvoice.init();
});
