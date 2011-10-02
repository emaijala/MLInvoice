function add_company(translations)
{
  var save = translations["save"];
  var close = translations["close"];
  $("#quick_add_company").dialog({ modal: true, width: 420, height: 300, resizable: false, 
    buttons: {
        save: function() { save_company(translations); },
        close: function() { $("#quick_add_company").dialog("close"); }
    },
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
  $.getJSON("json.php?func=get_companies", function(json) { 
    var company_id = document.getElementById("company_id");
    company_id.options.length = 0;
    for (var i = 0; i < json.records.length; i++)
    {
      var record = json.records[i];
      if (record.inactive == 1 && record.id != selected_id)
        continue;
      var option = document.createElement("option");
      option.value = record.id;
      option.text = record.company_name;
      if (record.company_id)
        option.text += " (" + record.company_id + ")";
      if (record.id == selected_id)
        option.selected = true;
      company_id.options.add(option);
    }
  });
}
