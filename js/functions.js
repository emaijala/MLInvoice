function sort_multi(a,b) 
{
  a = a.replace( /<.*?>/g, "" );
  b = b.replace( /<.*?>/g, "" );
  var date_re = /^(\d{1,2})\.(\d{1,2})\.(\d{4})$/;
  var am = a.match(date_re);
  var bm = b.match(date_re);
  if (am && bm)
  {
    ad = am[3] + '.' + am[2] + '.' + am[1];
    bd = bm[3] + '.' + bm[2] + '.' + bm[1];
    return ((ad < bd) ? -1 : ((ad > bd) ?  1 : 0));
  }
  var float_re = /^\d+[\.\,]?\d*$/;
  if (a.match(float_re) && b.match(float_re))
  {
    a = parseFloat(a);
    b = parseFloat(b);
    return ((a < b) ? -1 : ((a > b) ?  1 : 0));
  }
  a = a.toLowerCase();
  b = b.toLowerCase();
  return ((a < b) ? -1 : ((a > b) ?  1 : 0));
};
 
jQuery.fn.dataTableExt.oSort['html-multi-asc']  = function(a,b) {
  return sort_multi(a, b);
};

jQuery.fn.dataTableExt.oSort['html-multi-desc'] = function(a,b) {
  return -sort_multi(a, b);
};

var input = document.getElementById('addr');
var options = {
  types: ['geocode']
};

function initAddressAutocomplete(prefix)
{
  var input = document.getElementById(prefix + "street_address");
  if (input == null) {
	return;  
  }
  $(input).attr("placeholder", "");
  $(input).blur(function() {
	var val = $(input).val();
	setTimeout(function() {
      $(input).val(val); 
    }, 0); 
  });
  
  var options = {
    types: ["geocode"]
  };
  autocomplete = new google.maps.places.Autocomplete(input, options);
  
  google.maps.event.addListener(autocomplete, "place_changed", function() {
	var place = autocomplete.getPlace();
	setTimeout(function() {
	  $("#" + prefix + "street_address").val(place.name);
	  $.each(place.address_components, function(index, component) {
	    if ($.inArray("postal_code", component.types) >= 0) {
	      $("#" + prefix + "zip_code").val(component.long_name);
	    } else if ($.inArray("locality", component.types) >= 0 || $.inArray("administrative_area_level_3", component.types) >= 0) {
	      $("#" + prefix + "city").val(component.long_name);
	    } else if ($.inArray("country", component.types) >= 0) {
	      $("#" + prefix + "country").val(component.long_name);
	    };
	  });
	}, 0);
  });
}
