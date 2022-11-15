/**
 * Select2 Swedish translation.
 *
 * Author: Jens Rantil <jens.rantil@telavox.com>
 */
(function ($) {
    "use strict";

    $.extend($.fn.select2.defaults.defaults.language, {
        noResults: function () { return "Inga träffar"; },
        inputTooShort: function (input, min) { var n = min - input.length; return "Var god skriv in " + n + (n>1 ? " till tecken" : " tecken till"); },
        inputTooLong: function (input, max) { var n = input.length - max; return "Var god sudda ut " + n + " tecken"; },
        maximumSelected: function (limit) { return "Du kan max välja " + limit + " element"; },
        loadingMore: function (pageNumber) { return "Laddar fler resultat..."; },
        searching: function () { return "Söker..."; }
    });
})(jQuery);
