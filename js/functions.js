function sort_multi(a,b) 
{
  a = a.replace( /<.*?>/g, "" );
  b = b.replace( /<.*?>/g, "" );
  var float_re = /^\d+\.?\d*$/;
  var date_re = /^(\d{1,2})\.(\d{1,2})\.(\d{4})$/;
  if (a.match(float_re) && b.match(float_re))
  {
    a = parseFloat(a);
    b = parseFloat(b);
    return ((a < b) ? -1 : ((a > b) ?  1 : 0));
  }
  var am = a.match(date_re);
  var bm = b.match(date_re);
  if (am && bm)
  {
    ad = am[2] + '.' + am[1] + '.' + am[0];
    bd = bm[2] + '.' + bm[1] + '.' + bm[0];
    return ((ad < bd) ? -1 : ((ad > bd) ?  1 : 0));
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

