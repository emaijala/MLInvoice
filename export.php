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

function str_putcsv($data, $delimiter = ',', $enclosure = '"') 
{
  $fp = fopen('php://temp', 'r+');
  fputcsv($fp, $data, $delimiter, $enclosure);
  rewind($fp);
  $data = '';
  while (!feof($fp))
    $data .= fread($fp, 1024); 
  fclose($fp);
  return rtrim($data,"\n");
}

function do_export()
{
  $table = getRequest('table', '');
  $format = getRequest('format', '');
  $fieldDelimiter = getRequest('field_delim', ',');
  $enclosureChar = getRequest('enclosure_char', '"');
  $rowDelimiter = getRequest('row_delim', "\n");
  $columns = getRequest('column', '');
  $charset = getRequest('charset', 'UTF-8');
  
  if ($table && $format && $columns)
  {
    if (!table_valid($table))
      die('Invalid table name');

    $columns_def = array();
    $field_defs = array();
    $res = mysql_query_check("select * from {prefix}$table where 1=2");
    $field_count = mysql_num_fields($res);
    for ($i = 0; $i < $field_count; $i++)
    {
      $field_def = mysql_fetch_field($res, $i);
      $columns_def[] = $field_def->name;
      $field_defs[$field_def->name] = $field_def->type;
    }

    foreach ($columns as $key => $column)
    { 
      if (!$column)
        unset($columns[$key]);
      elseif (!in_array($column, $columns_def))
        die('Invalid column name');
    }
  
    $columnStr = implode($columns, ',');

    ob_clean();    
    switch ($format)
    {
    case 'csv':
      header('Content-type: text/csv');
      $filename = isset($GLOBALS["locTable_$table"]) ? $GLOBALS["locTable_$table"] : $table;
      header("Content-Disposition: attachment; filename=\"$filename.csv\"");
      $field_delims = array(
        'comma' => ',',
        'semicolon' => ';',
        'tab' => "\t",
        'pipe' => '|',
        'colon' => ':'
      );
  
      $enclosure_chars = array(
        'doublequote' => '"',
        'singlequote' => '\'',
        'none' => '',
      );
  
      $row_delims = array(
        'lf' => "\n",
        'crlf' => "\r\n",
        'cr' => "\r"
      );
      if (!isset($field_delims[$fieldDelimiter]))
        die('Invalid field delimiter');
      $fieldDelimiter = $field_delims[$fieldDelimiter];
      if (!isset($enclosure_chars[$enclosureChar]))
        die('Invalid enclosure character');
      $enclosureChar = $enclosure_chars[$enclosureChar];
      if (!isset($row_delims[$rowDelimiter]))
        die('Invalid field delimiter');
      $rowDelimiter = $row_delims[$rowDelimiter];
      echo str_putcsv($columns, $fieldDelimiter, $enclosureChar) . $rowDelimiter;
      break;
    }
    
    $res = mysql_query_check("select $columnStr from {prefix}$table");
    while ($row = mysql_fetch_assoc($res))
    {
      foreach ($row as $key => $value)
      {
        if (is_null($value))
          continue;
        if ($field_defs[$key] == 'blob' && $value)
          $row[$key] = '0x' . bin2hex($value);
        elseif (!in_array($field_defs[$key], array('int', 'real')) && $value && $charset && $charset != _CHARSET_)
        {
          if (in_array($charset, array('UTF-8', 'UTF-16')))
            $row[$key] = substr(iconv(_CHARSET_, $charset, $value), 2);
          else
            $row[$key] = iconv(_CHARSET_, $charset, $value);
        }
      }
      switch ($format)
      {
      case 'csv':
        echo str_putcsv($row, $fieldDelimiter, $enclosureChar) . $rowDelimiter;
        break;
      case 'xml':
        break;
      case 'json':
        break;
      }
    }
    exit;
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
  reset_columns()
});

var g_column_id = 0;

function reset_columns()
{
  $("#columns > select").remove();
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
    option.text = "<?php echo $GLOBALS['locExportColumnNone']?>";
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

function update_columns()
{
  if (this.value == "" && $("#columns > select").size() > 1)
    $(this).remove();
  else if (this.id == "column" + g_column_id)
    add_column();
}

</script>

  <div class="form_container">
    <span id="imessage" style="display: none"></span>
    <span id="spinner" style="visibility: hidden"><img src="images/spinner.gif" alt=""></span>
    <form id="export_form" name="export_form" method="GET" action="">
      <input type="hidden" name="func" value="system">
      <input type="hidden" name="operation" value="export">

      <div class="small_label"><?php echo $GLOBALS['locExportCharacterSet']?></div>
      <div class="field">
        <select name="charset">
          <option value="UTF-8">UTF-8</option>
          <option value="UTF-16">UTF-16</option>
          <option value="ISO-8859-1">ISO-8859-1</option>
          <option value="ISO-8859-15">ISO-8859-15</option>
          <option value="Windows-1251">Windows-1251</option>
        </select>
      </div>
      
      <div class="small_label"><?php echo $GLOBALS['locExportTable']?></div>
      <div class="field">
        <select id="sel_table" name="table" onchange="reset_columns()">
          <option value="base"><?php echo $GLOBALS['locExportTableBases']?></option>
          <option value="company"><?php echo $GLOBALS['locExportTableCompanies']?></option>
          <option value="invoice"><?php echo $GLOBALS['locExportTableInvoices']?></option>
          <option value="invoice_row"><?php echo $GLOBALS['locExportTableInvoiceRows']?></option>
          <option value="product"><?php echo $GLOBALS['locExportTableProducts']?></option>
          <option value="row_type"><?php echo $GLOBALS['locExportTableRowTypes']?></option>
          <option value="invoice_state"><?php echo $GLOBALS['locExportTableInvoiceStates']?></option>
        </select>
      </div>
      
      <div class="small_label"><?php echo $GLOBALS['locExportFormat']?></div>
      <div class="field">
        <select name="format">
          <option value="csv">CSV</option>
          <option value="xml">XML</option>
          <option value="json">JSON</option>
        </select>
      </div>

      <div class="small_label"><?php echo $GLOBALS['locExportFieldDelimiter']?></div>
      <div class="field">
        <select name="field_delim">
          <option value="comma"><?php echo $GLOBALS['locExportFieldDelimiterComma']?></option>
          <option value="semicolon"><?php echo $GLOBALS['locExportFieldDelimiterSemicolon']?></option>
          <option value="tab"><?php echo $GLOBALS['locExportFieldDelimiterTab']?></option>
          <option value="pipe"><?php echo $GLOBALS['locExportFieldDelimiterPipe']?></option>
          <option value="colon"><?php echo $GLOBALS['locExportFieldDelimiterColon']?></option>
        </select>
      </div>
      
      <div class="small_label"><?php echo $GLOBALS['locExportEnclosureCharacter']?></div> 
      <div class="field">
        <select name="enclosure_char">
          <option value="doublequote"><?php echo $GLOBALS['locExportEnclosureDoubleQuote']?></option>
          <option value="singlequote"><?php echo $GLOBALS['locExportEnclosureSingleQuote']?></option>
          <option value="none"><?php echo $GLOBALS['locExportEnclosureNone']?></option>
        </select>
      </div>
      
      <div class="small_label"><?php echo $GLOBALS['locExportRowDelimiter']?></div>
      <div class="field">
        <select name="row_delim">
          <option value="lf">LF</option>
          <option value="crlf">CR+LF</option>
          <option value="cr">CR</option>
        </select>
      </div>

      <div class="small_label"><?php echo $GLOBALS['locExportColumns']?></div>
      <div id="columns" class="field">
      </div>
      
      <div class="small_label"><input type="submit" value="Export"></div>
    </form>
  </div>
<?php
}
