/**
 * Select2 Finnish translation
 */
(function ($) {
    "use strict";
    $.extend($.fn.select2.defaults.defaults.language, {
        noResults: function () {
            return "Ei tuloksia";
        },
        inputTooShort: function (input, min) {
            var n = min - input.length;
            return "Ole hyvä ja anna " + n + " merkkiä lisää.";
        },
        inputTooLong: function (input, max) {
            var n = input.length - max;
            return "Ole hyvä ja annar " + n + " merkkiä vähemmän.";
        },
        maximumSelected: function (limit) {
            return "Voit valita ainoastaan " + limit + " kpl";
        },
        loadingMore: function (pageNumber) {
            return "Ladataan lisää tuloksia...";
        },
        searching: function () {
            return "Etsitään...";
        }
    });
})(jQuery);
