function keepalive() 
{ 
  $.getJSON("json.php?func=noop"); 
  window.setTimeout(keepalive, 60*1000); 
} 
window.setTimeout(keepalive, 60*1000); 