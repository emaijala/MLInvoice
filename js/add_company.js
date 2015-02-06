function add_company(translations)
{
  var buttons = new Object();   
  buttons[translations["save"]] = function() { save_company(translations); };
  buttons[translations["close"]] = function() { $("#quick_add_company").dialog("close"); };
  $("#quick_add_company").dialog({ modal: true, width: 420, height: 320, resizable: false, zIndex: 900,
    buttons: buttons,
    title: translations["title"],
  });  
}

function save_company(translations)
{
  var obj = new Object();
  obj.company_name = document.getElementById("quick_name").value;
  obj.email = document.getElementById("quick_email").value;
  obj.phone = document.getElementById("quick_phone").value;
  obj.street_address = document.getElementById("quick_street_address").value;
  obj.zip_code = document.getElementById("quick_zip_code").value;
  obj.city = document.getElementById("quick_city").value;
  obj.country = document.getElementById("quick_country").value;
  $.ajax({
    "url": "json.php?func=put_company",
    "type": "POST",
    "dataType": "json",
    "data": $.toJSON(obj),
    "contentType": "application/json; charset=utf-8",
    "success": function(data) {
      if (data.missing_fields)
      {
        alert(translations["missing"] + data.missing_fields);
      }
      else
      {
        init_company_list(data.id);
        $("#quick_add_company").dialog("close");
      }
    },
    "error": function(XMLHTTPReq, textStatus, errorThrown) {
      if (textStatus == "timeout")
        alert("Timeout trying to save company");
      else
        alert("Error trying to save company: " + XMLHTTPReq.status + " - " + XMLHTTPReq.statusText);
      return false;
    }
  });    
}

function init_company_list(selected_id)
{
  $.getJSON("json.php?func=get_company", {"id": selected_id}, function(record) { 
    var text = record.company_name;
    if (record.company_id) {
      text += " (" + record.company_id + ")";
    }
    var company_id = $("#company_id");
    company_id.select2('data', {"id": record.id, "text": text});
    company_id.trigger('change');
  });
}
