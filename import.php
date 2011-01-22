<?php
/*******************************************************************************
VLLasku: web-based invoicing application.
Copyright (C) 2010-2011 Ere Maijala

This program is free software. See attached LICENSE.

*******************************************************************************/

/*******************************************************************************
VLLasku: web-pohjainen laskutusohjelma.
Copyright (C) 2010-2011 Ere Maijala

Tämä ohjelma on vapaa. Lue oheinen LICENSE.

*******************************************************************************/

require_once 'localize.php';
require_once 'miscfuncs.php';
require_once 'settings.php';

function get_row_delims()
{
  return array(
    'lf' => array('char' => "\n", 'name' => 'LF'), 
    'crlf' => array('char' => "\r\n", 'name' => 'CR+LF'),
    'cr' => array('char' => "\r", 'name' => 'CR'),
  );
}

function get_field_delims()
{
  return array(
    'comma' => array('char' => ',', 'name' => $GLOBALS['locImportExportFieldDelimiterComma']),
    'semicolon' => array('char' => ';', 'name' => $GLOBALS['locImportExportFieldDelimiterSemicolon']),
    'tab' => array('char' => "\t", 'name' => $GLOBALS['locImportExportFieldDelimiterTab']),
    'pipe' => array('char' => '|', 'name' => $GLOBALS['locImportExportFieldDelimiterPipe']),
    'colon' => array('char' => ':', 'name' => $GLOBALS['locImportExportFieldDelimiterColon']),
  );
}

function get_enclosure_chars()
{
  return array(
    'doublequote' => array('char' => '"', 'name' =>  $GLOBALS['locImportExportEnclosureDoubleQuote']),
    'singlequote' => array('char' => '\'', 'name' =>  $GLOBALS['locImportExportEnclosureSingleQuote']),
    'none' => array('char' => '', 'name' =>  $GLOBALS['locImportExportEnclosureNone']),
  );
}

function do_import()
{
  $filetype = getRequest('filetype', '');

  $error = '';
  if ($filetype == 'upload')
  {
    if ($_FILES['data']['error'] == UPLOAD_ERR_OK)
    {
      $_SESSION['import_file'] = $_FILES['data']['tmp_name'];
      show_setup_form();
      return;
    }
    $error = $GLOBALS['locErrFileUploadFailed'];
  }
  elseif ($filetype == 'server_file')
  {
    if (_IMPORT_FILE_ && file_exists(_IMPORT_FILE_))
    {
      $_SESSION['import_file'] = _IMPORT_FILE_;
      show_setup_form();
      return;
    }
    $error = $GLOBALS['locErrImportFileNotFound'];
  }

  $importMode = getRequest('import_mode', '');
  if ($importMode)
  {
    import_file($importMode);
    return;
  }

unset($_SESSION['import_file']);
$maxUploadSize = getMaxUploadSize();
$maxFileSize = fileSizeToHumanReadable($maxUploadSize);
?>

  <div class="form_container">
    <?php if ($error) echo "<div class=\"error\">$error</div>\n"?>
    <h1><?php echo $GLOBALS['locImportFileSelection']?></h1>
    <span id="imessage" style="display: none"></span>
    <span id="spinner" style="visibility: hidden"><img src="images/spinner.gif" alt=""></span>
    <form id="form_import" enctype="multipart/form-data" action="" method="POST">
      <input type="hidden" name="func" value="system">
      <input type="hidden" name="operation" value="import">
      <div class="label" style="clear: both; margin-top: 10px; margin-bottom: 4px">
        <input type="radio" id="ft_upload" name="filetype" value="upload" checked="checked"><label for="ft_upload"><?php printf($GLOBALS['locImportUploadFile'], $maxFileSize)?></label>
      </div>  
      <div class="long"><input name="data" type="file"></div>
      <div class="label" style="clear: both; margin-top: 10px">
        <input type="radio" id="ft_server" name="filetype" value="server_file"><label for="ft_server"><?php echo $GLOBALS['locImportUseServerFile']?></label>
      </div>
      <div class="form_buttons" style="clear: both">
        <input type="submit" value="<?php echo $GLOBALS['locImportNext']?>">
      </div>
    </form>
  </div>
<?php
}

function show_setup_form()
{
  $fp = fopen($_SESSION['import_file'], 'r');
  if (!$fp)
    die("Could not open import file for reading");

  $data = fread($fp, 8192);
  $bytesRead = ftell($fp);
  
  fclose($fp);
  
  $charset = 'UTF-8';
  
  if ($bytesRead > 3)
  {
    if (ord($data[0]) == 0xFE && ord($data[1]) == 0xFF)
    {
      $charset = 'UTF-16BE';
      $data = iconv('UTF-16BE', _CHARSET_, $data);
    }
    elseif (ord($data[0]) == 0xFF && ord($data[1]) == 0xFE)
    {
      $charset = 'UTF-16LE';
      $data = iconv('UTF-16LE', _CHARSET_, $data);
    }
    elseif (ord($data[0]) == 0 && ord($data[2]) == 0)
    {
      $charset = 'UTF-16BE';
      $data = iconv('UTF-16BE', _CHARSET_, $data);
      error_log('UTF-16BE');
    }
    elseif (ord($data[1]) == 0 && ord($data[2]) == 0)
    {
      $charset = 'UTF-16LE';
      $data = iconv('UTF-16LE', _CHARSET_, $data);
    }
  }
  
  if (strtolower(substr(ltrim($data), 0, 5)) == '<?xml')
  {
    $format = 'xml';
  }
  elseif (strtolower(substr(ltrim($data), 0, 1)) == '{')
  {
    $format = 'json';
  }
  else
  {
    $format = 'csv';
  
    $row_delims = get_row_delims();
    foreach ($row_delims as $key => $value)
    {
      $row_delims[$key]['count'] = substr_count($data, $value['char']);
    }
    $selected = reset($row_delims);
    foreach ($row_delims as $key => $value)
    {
      if ($value['count'] > 0 && $value['count'] >= $selected['count'] && strlen($value['char']) >= strlen($selected['char']))
        $selected = $value;
    }
    $row_delim = $selected;
  
    $field_delims = get_field_delims();
    $rows = explode($row_delim['char'], $data);
    foreach ($rows as $row)
    {
      foreach ($field_delims as $key => $value)
      {
        if (!isset($field_delims[$key]['count']))
          $field_delims[$key]['count'] = 0;
        $field_delims[$key]['count'] += substr_count($row, $value['char']);
      }
    }
    $selected = reset($field_delims);
    foreach ($field_delims as $key => $value)
    {
      if ($value['count'] > 0 && $value['count'] >= $selected['count'])
        $selected = $value;
    }
    $field_delim = $selected;
  
    $enclosure_chars = get_enclosure_chars();
    foreach ($rows as $row)
    {
      foreach (explode($field_delim['char'], $row) as $field)
      {
        foreach ($enclosure_chars as $key => $value)
        {
          if (!isset($enclosure_chars[$key]['count']))
            $enclosure_chars[$key]['count'] = 0;
          if (substr($field, 0, strlen($value['char'])) == $value['char'] && substr($field, -strlen($value['char'])) == $value['char'])
            $enclosure_chars[$key]['count']++;
        }
      }
    }
    $selected = $enclosure_chars['none'];
    foreach ($enclosure_chars as $key => $value)
    {
      if ($value['count'] > 0 && $value['count'] >= $selected['count'])
        $selected = $value;
    }
    $enclosure_char = $selected;
  }  
?>
<script type="text/javascript">

$(document).ready(function() {
  $('#imessage').ajaxStart(function() {
    $('#spinner').css('visibility', 'visible');
  });
  $('#imessage').ajaxStop(function() {
    $('#spinner').css('visibility', 'hidden');
  });
  $('#imessage').ajaxError(function(event, request, settings) {
    alert('Server request failed: ' + request.status + ' - ' + request.statusText);
    $('#spinner').css('visibility', 'hidden');
  });
  reset_columns();
  update_field_states();
  update_mapping_table();
});

function update_columns()
{
  if (this.value == "" && $("#columns > select").size() > 1)
    $(this).remove();
  else if (this.id == "column" + g_column_id)
    add_column();
}

function update_field_states()
{
  var type = document.getElementById('format').value;
  document.getElementById('field_delim').disabled = type != 'csv';
  document.getElementById('enclosure_char').disabled = type != 'csv';
  document.getElementById('row_delim').disabled = type != 'csv';
}

var g_column_id = 0;

function reset_columns()
{
  $("#columns > select").remove();
  g_column_id = 0;
  add_column();
}

function add_column()
{
  var table = document.getElementById("sel_table").value;
  $.getJSON("json.php?func=get_table_columns&table=" + table, function(json) { 
    var index = ++g_column_id;
    var columns = document.getElementById("columns");
    var select = document.createElement("select");
    select.id = "column" + index;
    select.name = "column[]";
    select.onchange = update_columns;
    var option = document.createElement("option");
    option.value = "";
    option.text = "<?php echo $GLOBALS['locImportColumnUnused']?>";
    select.options.add(option);
    for (var i = 0; i < json.columns.length; i++)
    {
      var option = document.createElement("option");
      option.value = json.columns[i].name;
      option.text = json.columns[i].name;
      select.options.add(option);
    }
    columns.appendChild(document.createTextNode(' '));
    columns.appendChild(select);
  });
}

function update_mapping_table()
{
  var charset = document.getElementById("charset").value;
  var table = document.getElementById("sel_table").value;
  var format = document.getElementById("format").value;
  var field_delim = document.getElementById("field_delim").value;
  var enclosure_char = document.getElementById("enclosure_char").value;
  var row_delim = document.getElementById("row_delim").value;
  
  $("#column_table > tr").remove();
  $("#mapping_errors").text("");
  
  $.getJSON("json.php?func=get_import_preview&charset=" + charset + "&table=" + table + 
   "&format=" + format + "&field_delim=" + field_delim + "&enclosure_char=" + enclosure_char + 
   "&row_delim=" + row_delim, function(json) { 
    if (!json)
    {
      $("#mapping_errors").html("Could not fetch preview");
      return;
    }
    if (json.errors)
    {
      for (var i = 0; i < json.errors.length; i++)
      {
        $("#mapping_errors").html($(this).html() + "<br>" + $("<span/>").text(json.errors[i]));
      }
    }
    var table = document.getElementById("column_table");
    if (json.headings)
    {
      var tr = document.createElement("tr");
      for (var i = 0; i < json.headings.length; i++)
      {
        var th = document.createElement("th");
        th.appendChild(document.createTextNode(json.headings[i]));
        tr.appendChild(th);
      }
      table.appendChild(tr);
    }
    if (json.rows)
    {
      for (var i = 0; i < json.rows.length; i++)
      {
        var tr = document.createElement("tr");
        for (var j = 0; j < json.rows[0].length; j++)
        {
          var td = document.createElement("td");
          td.appendChild(document.createTextNode(json.rows[i][j] ? json.rows[i][j] : ''));
          tr.appendChild(td);
        }
        table.appendChild(tr);
      }
    }
    add_mapping_columns();
  });
}

function add_mapping_columns()
{
  var table = document.getElementById("sel_table").value;
  $.getJSON("json.php?func=get_table_columns&table=" + table, function(json) { 
    var columns = document.getElementById("columns");
    var select = document.createElement("select");
    select.name = "map_column[]";
    var option = document.createElement("option");
    option.value = "";
    option.text = "<?php echo $GLOBALS['locImportExportColumnNone']?>";
    select.options.add(option);
    for (var i = 0; i < json.columns.length; i++)
    {
      var option = document.createElement("option");
      option.value = json.columns[i].name;
      option.text = json.columns[i].name;
      select.options.add(option);
    }
    var table = document.getElementById("column_table");
    var tr = table.insertRow(1);
    for (var i = 0; i < table.rows[0].cells.length; i++)
    {
      var td = document.createElement('td');
      var clone = select.cloneNode(true);
      clone.id = "map_column" + i;
      td.appendChild(clone);
      tr.appendChild(td);
    }
  });
}

</script>

  <div class="form_container">
    <h1><?php echo $GLOBALS['locImportFileParameters']?></h1>
    <span id="imessage" style="display: none"></span>
    <span id="spinner" style="visibility: hidden"><img src="images/spinner.gif" alt=""></span>
    <form id="import_form" name="import_form" method="GET" action="">
      <input type="hidden" name="func" value="system">
      <input type="hidden" name="operation" value="import">

      <div class="medium_label"><?php echo $GLOBALS['locImportExportCharacterSet']?></div>
      <div class="field">
        <select id="charset" name="charset" onchange="update_mapping_table()">
          <option value="UTF-8"<?php if ($charset == 'UTF-8') echo ' selected="selected"'?>>UTF-8</option>
          <option value="ISO-8859-1"<?php if ($charset == 'ISO-8859-1') echo ' selected="selected"'?>>ISO-8859-1</option>
          <option value="ISO-8859-15"<?php if ($charset == 'ISO-8859-15') echo ' selected="selected"'?>>ISO-8859-15</option>
          <option value="Windows-1251"<?php if ($charset == 'Windows-1251') echo ' selected="selected"'?>>Windows-1251</option>
          <option value="UTF-16"<?php if ($charset == 'UTF-16') echo ' selected="selected"'?>>UTF-16</option>
          <option value="UTF-16LE"<?php if ($charset == 'UTF-16LE') echo ' selected="selected"'?>>UTF-16 LE</option>
          <option value="UTF-16BE"<?php if ($charset == 'UTF-16BE') echo ' selected="selected"'?>>UTF-16 BE</option>
        </select>
      </div>
      
      <div class="medium_label"><?php echo $GLOBALS['locImportExportTable']?></div>
      <div class="field">
        <select id="sel_table" name="table" onchange="reset_columns(); update_mapping_table()">
          <option value="company"><?php echo $GLOBALS['locImportExportTableCompanies']?></option>
          <option value="company_contact"><?php echo $GLOBALS['locImportExportTableCompanyContacts']?></option>
          <option value="base"><?php echo $GLOBALS['locImportExportTableBases']?></option>
          <option value="invoice"><?php echo $GLOBALS['locImportExportTableInvoices']?></option>
          <option value="invoice_row"><?php echo $GLOBALS['locImportExportTableInvoiceRows']?></option>
          <option value="product"><?php echo $GLOBALS['locImportExportTableProducts']?></option>
          <option value="row_type"><?php echo $GLOBALS['locImportExportTableRowTypes']?></option>
          <option value="invoice_state"><?php echo $GLOBALS['locImportExportTableInvoiceStates']?></option>
        </select>
      </div>
      
      <div class="medium_label"><?php echo $GLOBALS['locImportExportFormat']?></div>
      <div class="field">
        <select id="format" name="format" onchange="update_field_states(); update_mapping_table()">
          <option value="csv"<?php if ($format == 'csv') echo ' selected="selected"'?>>CSV</option>
          <option value="xml"<?php if ($format == 'xml') echo ' selected="selected"'?>>XML</option>
          <option value="json"<?php if ($format == 'json') echo ' selected="selected"'?>>JSON</option>
        </select>
      </div>

      <div class="medium_label"><?php echo $GLOBALS['locImportExportFieldDelimiter']?></div>
      <div class="field">
        <select id="field_delim" name="field_delim" onchange="update_mapping_table()">
<?php
  $field_delims = get_field_delims();
  foreach ($field_delims as $key => $delim)
  {
    $selected = (isset($field_delim) && $field_delim['name'] == $delim['name']) ? ' selected="selected"' : '';
    echo "<option value=\"$key\"$selected>" . $delim['name'] . "</option>\n";
  }
?>
        </select>
      </div>
      
      <div class="medium_label"><?php echo $GLOBALS['locImportExportEnclosureCharacter']?></div> 
      <div class="field">
        <select id="enclosure_char" name="enclosure_char" onchange="update_mapping_table()">
<?php
  $enclosure_chars = get_enclosure_chars();
  foreach ($enclosure_chars as $key => $delim)
  {
    $selected = (isset($enclosure_char) && $enclosure_char['name'] == $delim['name']) ? ' selected="selected"' : '';
    echo "<option value=\"$key\"$selected>" . $delim['name'] . "</option>\n";
  }
?>
        </select>
      </div>

      <div class="medium_label"><?php echo $GLOBALS['locImportExportRowDelimiter']?></div>
      <div class="field">
        <select id="row_delim" name="row_delim" onchange="update_mapping_table()">
<?php
  $row_delims = get_row_delims();
  foreach ($row_delims as $key => $delim)
  {
    $selected = (isset($row_delim) && $row_delim['name'] == $delim['name']) ? ' selected="selected"' : '';
    echo "<option value=\"$key\"$selected>" . $delim['name'] . "</option>\n";
  }
?>
        </select>
      </div>

      <div class="medium_label"><?php echo $GLOBALS['locImportExistingRowHandling']?></div>
      <div class="field">
        <select id="duplicate_processing" name="duplicate_processing">
          <option value="ignore" selected="selected"><?php echo $GLOBALS['locImportExistingRowIgnore']?></option>
          <option value="update"><?php echo $GLOBALS['locImportExistingRowUpdate']?></option>
        </select>
      </div>
      
      <div class="medium_label"><?php echo $GLOBALS['locImportIdentificationColumns']?></div>
      <div id="columns" class="field">
      </div>

      <div class="medium_label"><?php echo $GLOBALS['locImportMode']?></div> 
      <div class="field">
        <input id="import_type_report" name="import_mode" type="radio" value="report" checked="checked"><label for="import_mode_report"><?php echo $GLOBALS['locImportModeReport']?></label><br>
        <input id="import_type_import" name="import_mode" type="radio" value="import"><label for="import_mode_import"><?php echo $GLOBALS['locImportModeImport']?></label>
      </div>

      <div class="medium_label"><?php echo $GLOBALS['locImportColumnMapping']?></div>
      <div class="column_mapping">
        <div id="mapping_errors"></div>
        <table id="column_table">
        </table>
      </div>

      <div class="form_buttons" style="clear: both">
        <input type="submit" value="<?php echo $GLOBALS['locImportStart']?>">
      </div>
    </form>
  </div>
<?php
}

function get_csv($handle, $delimiter, $enclosure, $charset, $line_ending)
{
  $str = fgets_charset($handle, $charset, $line_ending);
  return str_getcsv($str);
}

function create_import_preview()
{
  $charset = getRequest('charset', 'UTF-8');
  $table = getRequest('table', '');
  $format = getRequest('format', '');
  $fieldDelimiter = getRequest('field_delim', 'comma');
  $enclosureChar = getRequest('enclosure_char', 'doublequote');
  $rowDelimiter = getRequest('row_delim', "lf");

  if (!$charset || !$table || !$format || !$fieldDelimiter || !$enclosureChar || !$rowDelimiter)
  {
    header('HTTP/1.1 400 Bad Request');
    exit;
  }
  if (!table_valid($table))
  {
    header('HTTP/1.1 400 Bad Request');
    die('Invalid table name');
  }

  header('Content-Type: application/json');

  $fp = fopen($_SESSION['import_file'], 'r');
  if (!$fp)
  {
    echo json_encode(array('errors' => array('Could not open import file for reading'))); 
    die("Could not open import file '" + $_SESSION['import_file'] + "' for reading");
  }

  if ($format == 'csv')
  {
    $field_delims = get_field_delims();
    $enclosure_chars = get_enclosure_chars();
    $row_delims = get_row_delims();
    
    if (!isset($field_delims[$fieldDelimiter]))
      die('Invalid field delimiter');
    $fieldDelimiter = $field_delims[$fieldDelimiter]['char'];
    if (!isset($enclosure_chars[$enclosureChar]))
      die('Invalid enclosure character');
    $enclosureChar = $enclosure_chars[$enclosureChar]['char'];
    if (!isset($row_delims[$rowDelimiter]))
      die('Invalid field delimiter');
    $rowDelimiter = $row_delims[$rowDelimiter]['char'];
    
    // Force enclosure char, otherwise fgetcsv would balk.
    if ($enclosureChar == '')
      $enclosureChar = '\x01';
      
    $errors = array();
    $headings = get_csv($fp, $fieldDelimiter, $enclosureChar, $charset, $rowDelimiter);    
    $rows = array();
    for ($i = 0; $i < 10 && !feof($fp); $i++)
    {
      $row = get_csv($fp, $fieldDelimiter, $enclosureChar, $charset, $rowDelimiter);    
      if (!isset($row))
      {
        $errors[] = 'Could not read row from import file';
        break;
      }
      $rows[] = $row;
    }
    $response = array(
      'errors' => $errors,
      'headings' => $headings,
      'rows' => $rows,
    );
  }
  fclose($fp);
  echo json_encode($response);
}

function process_import_row($table, $row, $dupMode, $dupCheckColumns, $mode)
{
  $result = '';
  if ($dupMode != '' && count($dupCheckColumns) > 0)
  {
    $query = "select id from {prefix}$table where Deleted=0";
    $where = '';
    $params = array();
    foreach ($dupCheckColumns as $dupCol)
    {
      $where .= " AND $dupCol=?";
      $params[] = $row[$dupCol];
    }
    $res = mysql_param_query($query . $where, $params);
    if ($dupRow = mysql_fetch_row($res))
    {
      $id = $dupRow[0];
      if ($dupMode == 'update')
        $result = "Update existing row id $id in table $table";
      else
        $result = "Not updating existing row id $id in table $table";
      
      if ($mode == 'import' && $dupMode == 'update')
      {
        // Update existing row
        $query = "UPDATE {prefix}$table SET ";
        $columns = '';
        $params = array();
        foreach ($row as $key => $value)
        {
          if ($key == 'id')
            continue;
          if ($columns)
            $columns .= ', ';
          $columns .= "$key=?";
          $params[] = $value;
        }
        $query .= "$columns WHERE id=?";
        $params[] = $id;
        mysql_param_query($query, $params);
      }
      return $result;
    }
  }
  // Add new row
  $result = "Add as new into table $table";
  $query = "INSERT INTO {prefix}$table ";
  $columns = '';
  $values = '';
  $params = array();
  foreach ($row as $key => $value)
  {
    if ($columns)
      $columns .= ', ';
    if ($values)
      $values .= ', ';
    $columns .= $key;
    $values .= '?';
    $params[] = $value;
  }
  $query .= "($columns) VALUES ($values)";
  mysql_param_query($query, $params);
  
  return $result;
}

function import_file($importMode)
{
  $charset = getRequest('charset', 'UTF-8');
  $table = getRequest('table', '');
  $format = getRequest('format', '');
  $fieldDelimiter = getRequest('field_delim', 'comma');
  $enclosureChar = getRequest('enclosure_char', 'doublequote');
  $rowDelimiter = getRequest('row_delim', "lf");
  $duplicateMode = getRequest('duplicate_processing', '');
  $duplicateCheckColumns = getRequest('column', array());
  $columnMappings = getRequest('map_column', array());

  if (!$charset || !$format || !$fieldDelimiter || !$enclosureChar || !$rowDelimiter)
  {
    die('Invalid parameters');
  }
  $fp = fopen($_SESSION['import_file'], 'r');
  if (!$fp)
  {
    die("Could not open import file for reading");
  }

?>
  <div class="form_container">
    <h1><?php echo $GLOBALS['locImportResults']?></h1>
<?php
  ob_end_flush();
  
  if ($format == 'csv')
  {
    if (!table_valid($table))
    {
      die('Invalid table name: ' . htmlspecialchars($table));
    }
  
    $res = mysql_query_check("show fields from {prefix}$table");
    $field_count = mysql_num_rows($res);
    $field_defs = array();
    while ($row = mysql_fetch_assoc($res))
    {
      $field_defs[$row['Field']] = $row;
    }
    
    foreach ($columnMappings as $key => $column)
    { 
      if ($column && !isset($field_defs[$column]))
        die('Invalid column name: ' . htmlspecialchars($column));
    }

    foreach ($duplicateCheckColumns as $key => $column)
    { 
      if (!$column)
        unset($duplicateCheckColumns[$key]);
      elseif (!isset($field_defs[$column]))
        die('Invalid duplicate check column name: ' . htmlspecialchars($column));
    }
  
    $field_delims = get_field_delims();
    $enclosure_chars = get_enclosure_chars();
    $row_delims = get_row_delims();
    
    if (!isset($field_delims[$fieldDelimiter]))
      die('Invalid field delimiter');
    $fieldDelimiter = $field_delims[$fieldDelimiter]['char'];
    if (!isset($enclosure_chars[$enclosureChar]))
      die('Invalid enclosure character');
    $enclosureChar = $enclosure_chars[$enclosureChar]['char'];
    if (!isset($row_delims[$rowDelimiter]))
      die('Invalid field delimiter');
    $rowDelimiter = $row_delims[$rowDelimiter]['char'];
    
    // Force enclosure char, otherwise fgetcsv would balk.
    if ($enclosureChar == '')
      $enclosureChar = '\x01';
      
    $errors = array();
    $headings = get_csv($fp, $fieldDelimiter, $enclosureChar, $charset, $rowDelimiter);    
    $rowNum = 0;
    while (!feof($fp))
    {
      $row = get_csv($fp, $fieldDelimiter, $enclosureChar, $charset, $rowDelimiter);    
      if (!isset($row))
        break;

      ++$rowNum;        
      $mapped_row = array();
      for ($i = 0; $i < count($row); $i++)
      {
        if ($columnMappings[$i])
          $mapped_row[$columnMappings[$i]] = $row[$i];
      }
      $result = process_import_row($table, $mapped_row, $duplicateMode, $duplicateCheckColumns, $importMode);
      if ($result)
        echo "    Row $rowNum: $result<br>\n";
    }
  }
  fclose($fp);
  echo '    ' . $GLOBALS['locImportDone'] . "\n";
?>
  </div>
<?php
}