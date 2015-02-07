<?php
/*******************************************************************************
MLInvoice: web-based invoicing application.
Copyright (C) 2010-2015 Ere Maijala

This program is free software. See attached LICENSE.

*******************************************************************************/

/*******************************************************************************
MLInvoice: web-pohjainen laskutusohjelma.
Copyright (C) 2010-2015 Ere Maijala

Tämä ohjelma on vapaa. Lue oheinen LICENSE.

*******************************************************************************/

require_once 'localize.php';
require_once 'miscfuncs.php';
require_once 'settings.php';

class ImportFile
{
  protected $tableName = '';
  protected $allowServerFile = true;
  protected $duplicateControl = true;
  protected $dateFormat = false;
  protected $decimalSeparator = false;
  protected $ignoreEmptyRows = false;
  protected $presets = array();

  public function __construct()
  {
  }

  public function launch()
  {
    $filetype = getRequest('filetype', '');

    $error = '';
    if ($filetype == 'upload')
    {
      if ($_FILES['data']['error'] == UPLOAD_ERR_OK)
      {
        $_SESSION['import_file'] = $_FILES['data']['tmp_name'] . '-mlinvoice-import';
        move_uploaded_file($_FILES['data']['tmp_name'], $_SESSION['import_file']);
        $this->show_setup_form();
        return;
      }
      $error = $GLOBALS['locErrFileUploadFailed'];
    }
    elseif ($this->allowServerFile && $filetype == 'server_file')
    {
      if (_IMPORT_FILE_ && file_exists(_IMPORT_FILE_))
      {
        $_SESSION['import_file'] = _IMPORT_FILE_;
        $this->show_setup_form();
        return;
      }
      $error = $GLOBALS['locErrImportFileNotFound'];
    }

    $importMode = getRequest('import', '');
    if ($importMode == 'import' || $importMode == 'preview') {
      $this->import_file($importMode);
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
    <form id="form_import" enctype="multipart/form-data" method="POST">
      <input type="hidden" name="func" value="<?php echo htmlentities(getRequest('func', ''))?>">
      <input type="hidden" name="operation" value="import">
      <div class="label" style="clear: both; margin-top: 10px; margin-bottom: 4px">
        <input type="radio" id="ft_upload" name="filetype" value="upload" checked="checked"><label for="ft_upload"><?php printf($GLOBALS['locImportUploadFile'], $maxFileSize)?></label>
      </div>
      <div class="long"><input name="data" type="file"></div>
<?php if ($this->allowServerFile) {?>
      <div class="label" style="clear: both; margin-top: 10px">
        <input type="radio" id="ft_server" name="filetype" value="server_file"><label for="ft_server"><?php echo $GLOBALS['locImportUseServerFile']?></label>
      </div>
<?php }?>
      <div class="form_buttons" style="clear: both">
        <input type="submit" value="<?php echo $GLOBALS['locImportNext']?>">
      </div>
    </form>
  </div>
<?php
  }

  public function create_import_preview()
  {
    $charset = getRequest('charset', 'UTF-8');
    $table = getRequest('table', '');
    $format = getRequest('format', '');
    $fieldDelimiter = getRequest('field_delim', 'comma');
    $enclosureChar = getRequest('enclosure_char', 'doublequote');
    $rowDelimiter = getRequest('row_delim', 'lf');
    $skipRows = getRequest('skip_rows', 0);

    if (!$charset || !$table || !$format || !$fieldDelimiter || !$enclosureChar || !$rowDelimiter)
    {
      header('HTTP/1.1 400 Bad Request');
      exit;
    }
    if (!$this->table_valid($table))
    {
      header('HTTP/1.1 400 Bad Request');
      die('Invalid table name');
    }

    header('Content-Type: application/json');

    if ($format == 'csv')
    {
      $fp = fopen($_SESSION['import_file'], 'r');
      if (!$fp)
      {
        echo json_encode(array('errors' => array('Could not open import file for reading')));
        die("Could not open import file '" + $_SESSION['import_file'] + "' for reading");
      }

      $field_delims = $this->get_field_delims();
      $enclosure_chars = $this->get_enclosure_chars();
      $row_delims = $this->get_row_delims();

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
        $enclosureChar = "\x01";

      for ($i = 0; $i < $skipRows; $i++) {
        $this->get_csv($fp, $fieldDelimiter, $enclosureChar, $charset, $rowDelimiter);
      }

      $errors = array();
      $headings = $this->get_csv($fp, $fieldDelimiter, $enclosureChar, $charset, $rowDelimiter);
      if (!$headings)
        $errors[] = 'Could not parse headings row from import file';
      $rows = array();
      for ($i = 0; $i < 10 && !feof($fp); $i++)
      {
        $row = $this->get_csv($fp, $fieldDelimiter, $enclosureChar, $charset, $rowDelimiter);
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
      fclose($fp);
    }
    elseif ($format == 'xml')
    {
      $data = file_get_contents($_SESSION['import_file']);
      if ($data === false)
      {
        echo json_encode(array('errors' => array('Could not open import file for reading')));
        die("Could not open import file '" + $_SESSION['import_file'] + "' for reading");
      }

      if ($charset != _CHARSET_)
        $data = iconv($charset, _CHARSET_, $data);

      try
      {
        $xml = new SimpleXMLElement($data);
      }
      catch (Exception $e)
      {
        echo json_encode(array('errors' => array($e->getMessage())));
        die('XML parsing failed: ' . htmlspecialchars($e->getMessage()));
      }
      $recNum = 0;
      $headings = array();
      $rows = array();
      foreach ($xml as $record)
      {
        if (++$recNum > 10)
          break;
        $record = get_object_vars($record);

        $row = array();
        foreach ($record as $column => $value)
        {
          if (!is_array($value) && !is_object($value))
          {
            if ($recNum == 1)
              $headings[] = $column;
            $row[] = $value;
          }
        }
        $rows[] = $row;
      }
      $response = array(
        'errors' => array(),
        'headings' => $headings,
        'rows' => $rows,
      );
    }
    elseif ($format == 'json')
    {
      $data = file_get_contents($_SESSION['import_file']);
      if ($data === false)
      {
        echo json_encode(array('errors' => array('Could not open import file for reading')));
        error_log("Could not open import file '" + $_SESSION['import_file'] + "' for reading");
        exit;
      }

      if ($charset != _CHARSET_)
        $data = iconv($charset, _CHARSET_, $data);

      $data = json_decode($data, true);
      if ($data === null)
      {
        echo json_encode(array('errors' => array('Could not decode JSON')));
        error_log('JSON parsing failed');
        exit;
      }
      $recNum = 0;
      $headings = array();
      $rows = array();

      foreach (reset($data) as $record)
      {
        if (++$recNum > 10)
          break;

        $row = array();
        foreach ($record as $column => $value)
        {
          if (is_array($value))
            continue;
          if ($recNum == 1)
            $headings[] = $column;
          $row[] = $value;
        }
        $rows[] = $row;
      }
      $response = array(
        'errors' => array(),
        'headings' => $headings,
        'rows' => $rows,
      );
    }
    echo json_encode($response);
  }

  public function get_row_delims()
  {
    return array(
      'lf' => array('char' => "\n", 'name' => 'LF'),
      'crlf' => array('char' => "\r\n", 'name' => 'CR+LF'),
      'cr' => array('char' => "\r", 'name' => 'CR'),
    );
  }

  public function get_field_delims()
  {
    return array(
      'comma' => array('char' => ',', 'name' => $GLOBALS['locImportExportFieldDelimiterComma']),
      'semicolon' => array('char' => ';', 'name' => $GLOBALS['locImportExportFieldDelimiterSemicolon']),
      'tab' => array('char' => "\t", 'name' => $GLOBALS['locImportExportFieldDelimiterTab']),
      'pipe' => array('char' => '|', 'name' => $GLOBALS['locImportExportFieldDelimiterPipe']),
      'colon' => array('char' => ':', 'name' => $GLOBALS['locImportExportFieldDelimiterColon']),
    );
  }

  public function get_enclosure_chars()
  {
    return array(
      'doublequote' => array('char' => '"', 'name' =>  $GLOBALS['locImportExportEnclosureDoubleQuote']),
      'singlequote' => array('char' => '\'', 'name' =>  $GLOBALS['locImportExportEnclosureSingleQuote']),
      'none' => array('char' => '', 'name' =>  $GLOBALS['locImportExportEnclosureNone']),
    );
  }

  public function get_date_formats()
  {
    return array (
      'd.m.Y',
      'd-m-Y',
      'd/m/Y',
      'Y.m.d',
      'Y-m-d',
      'Y/m/d',
      'm.d.Y',
      'm-d-Y',
      'm/d/Y'
    );
  }

  protected function get_field_defs($table)
  {
    if (!$this->table_valid($table)) {
      return array();
    }
    $res = mysqli_query_check("show fields from {prefix}$table");
    $field_defs = array();
    while ($row = mysqli_fetch_assoc($res))
    {
      $field_defs[$row['Field']] = $row;
    }
    return $field_defs;
  }

  protected function show_setup_form()
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

      $row_delims = $this->get_row_delims();
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

      $field_delims = $this->get_field_delims();
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

      $enclosure_chars = $this->get_enclosure_chars();
      foreach ($rows as $row)
      {
        if ($charset == 'UTF-8' && try_iconv($charset, _CHARSET_, $row) === false)
        {
          if (try_iconv('ISO-8859-1', _CHARSET_, $row) !== false)
            $charset = 'ISO-8859-1';
        }
        foreach (explode($field_delim['char'], $row) as $field)
        {
          foreach ($enclosure_chars as $key => $value)
          {
            if (!isset($enclosure_chars[$key]['count']))
              $enclosure_chars[$key]['count'] = 0;
            if ($value['char'] === '') {
              continue;
            }
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

g_presets = <?php echo json_encode($this->presets) . ';'?>

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
  var columns = document.getElementById("columns");
  if (!columns) {
    return;
  }
  var table = document.getElementById("sel_table").value;
  $.getJSON("json.php?func=get_table_columns&table=" + table, function(json) {
    var index = ++g_column_id;
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
      option.value = json.columns[i].id ? json.columns[i].id : json.columns[i].name;
      option.text = json.columns[i].name;
      select.options.add(option);
    }
    columns.appendChild(document.createTextNode(' '));
    columns.appendChild(select);
  });
}

function settings_changed()
{
  $("#preset").val(0);
}

function update_mapping_table()
{
  var charset = document.getElementById("charset").value;
  var table = document.getElementById("sel_table").value;
  var format = document.getElementById("format").value;
  var field_delim = document.getElementById("field_delim").value;
  var enclosure_char = document.getElementById("enclosure_char").value;
  var row_delim = document.getElementById("row_delim").value;
  var skip_rows = document.getElementById("skip_rows").value;

  $("#column_table > tr").remove();
  $("#mapping_errors").text("");

  $.getJSON("json.php?func=get_import_preview&charset=" + charset + "&table=" + table +
   "&format=" + format + "&field_delim=" + field_delim + "&enclosure_char=" + enclosure_char +
   "&row_delim=" + row_delim + "&skip_rows=" + skip_rows, function(json) {
    if (!json)
    {
      $("#mapping_errors").html("Could not fetch preview");
      return;
    }
    if (json.errors)
    {
      for (var i = 0; i < json.errors.length; i++)
      {
        $("#mapping_errors").append($("<span/>").text(json.errors[i])).append("<br>");
      }
    }
    var table = document.getElementById("column_table");
    if (json.headings)
    {
      var tr = document.createElement("tr");
      for (var i = 0; i < json.headings.length; i++)
      {
        var th = document.createElement("th");
        if (json.headings[i] == '')
          json.headings[i] = '-';
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
    add_mapping_columns(json.headings);
  });
}

function add_mapping_columns(headings)
{
  var type = document.getElementById('format').value;
  if (type != 'csv')
    return;
  var table = document.getElementById("sel_table").value;
  $.getJSON("json.php?func=get_table_columns&table=" + table, function(json) {
    var columns = document.getElementById("columns");
    var select = document.createElement("select");
    select.name = "map_column[]";
    select.onchange = "settings_changed()";
    var option = document.createElement("option");
    option.value = "";
    option.text = "<?php echo $GLOBALS['locImportExportColumnNone']?>";
    select.options.add(option);
    for (var i = 0; i < json.columns.length; i++)
    {
      var option = document.createElement("option");
      option.value = json.columns[i].id ? json.columns[i].id : json.columns[i].name;
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
      if (headings && headings[i]) {
        $(clone).find('option').each(function() {
          if (this.value == headings[i]) {
            $(clone).val(headings[i]);
            return false;
          }
        });
      }
      td.appendChild(clone);
      tr.appendChild(td);
    }
    var name = $("#preset").val();
    $.each(g_presets, function(index, preset) {
      if (preset['name'] == name) {
        $.each(preset['mappings'], function(element, value) {
          var elem = $('#' + element).get(0);
          if (elem) elem.selectedIndex = value;
        });
      }
    });
  });
}

function select_preset()
{
  var name = $("#preset").val();
  $.each(g_presets, function(index, preset) {
    if (preset['name'] == name) {
      $.each(preset['selections'], function(element, value) {
        var elem = $('#' + element).get(0);
        if (elem) elem.selectedIndex = value;
      });
      $.each(preset['values'], function(element, value) {
        $('#' + element).val(value);
      });
      update_mapping_table();
    }
  });
}

</script>

  <div class="form_container">
    <h1><?php echo $GLOBALS['locImportFileParameters']?></h1>
    <span id="imessage" style="display: none"></span>
    <span id="spinner" style="visibility: hidden"><img src="images/spinner.gif" alt=""></span>
    <form id="import_form" name="import_form" method="GET">
      <input type="hidden" name="func" value="<?php echo htmlentities(getRequest('func', ''))?>">
      <input type="hidden" name="operation" value="import">
<?php if ($this->presets) { ?>
      <div class="medium_label"><?php echo $GLOBALS['locImportExportPreset']?></div>
      <div class="field">
        <select id="preset" name="preset" onchange="select_preset()">
          <option value="" selected="selected"><?php echo $GLOBALS['locImportExportPresetNone']?></option>
<?php
    foreach ($this->presets as $preset)
    {
      echo "<option value=\"${preset['name']}\">" . $preset['name'] . "</option>\n";
    }
?>
        </select>
      </div>
<?php } ?>

      <div class="medium_label"><?php echo $GLOBALS['locImportExportCharacterSet']?></div>
      <div class="field">
        <select id="charset" name="charset" onchange="settings_changed(); update_mapping_table()">
          <option value="UTF-8"<?php if ($charset == 'UTF-8') echo ' selected="selected"'?>>UTF-8</option>
          <option value="ISO-8859-1"<?php if ($charset == 'ISO-8859-1') echo ' selected="selected"'?>>ISO-8859-1</option>
          <option value="ISO-8859-15"<?php if ($charset == 'ISO-8859-15') echo ' selected="selected"'?>>ISO-8859-15</option>
          <option value="Windows-1251"<?php if ($charset == 'Windows-1251') echo ' selected="selected"'?>>Windows-1251</option>
          <option value="UTF-16"<?php if ($charset == 'UTF-16') echo ' selected="selected"'?>>UTF-16</option>
          <option value="UTF-16LE"<?php if ($charset == 'UTF-16LE') echo ' selected="selected"'?>>UTF-16 LE</option>
          <option value="UTF-16BE"<?php if ($charset == 'UTF-16BE') echo ' selected="selected"'?>>UTF-16 BE</option>
        </select>
      </div>
<?php
  if ($this->tableName) {
?>
      <input id="sel_table" name="table" type="hidden" value="<?php echo htmlentities($this->tableName)?>"></input>
<?php
  } else {
?>
      <div class="medium_label"><?php echo $GLOBALS['locImportExportTable']?></div>
      <div class="field">
        <select id="sel_table" name="table" onchange="reset_columns(); settings_changed(); update_mapping_table()">
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
<?php
  }
?>

      <div class="medium_label"><?php echo $GLOBALS['locImportExportFormat']?></div>
      <div class="field">
        <select id="format" name="format" onchange="update_field_states(); reset_columns(); settings_changed(); update_mapping_table()">
          <option value="csv"<?php if ($format == 'csv') echo ' selected="selected"'?>>CSV</option>
          <option value="xml"<?php if ($format == 'xml') echo ' selected="selected"'?>>XML</option>
          <option value="json"<?php if ($format == 'json') echo ' selected="selected"'?>>JSON</option>
        </select>
      </div>

      <div class="medium_label"><?php echo $GLOBALS['locImportExportFieldDelimiter']?></div>
      <div class="field">
        <select id="field_delim" name="field_delim" onchange="settings_changed(); update_mapping_table()">
<?php
    $field_delims = $this->get_field_delims();
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
        <select id="enclosure_char" name="enclosure_char" onchange="settings_changed(); update_mapping_table()">
<?php
    $enclosure_chars = $this->get_enclosure_chars();
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
        <select id="row_delim" name="row_delim" onchange="settings_changed(); update_mapping_table()">
<?php
    $row_delims = $this->get_row_delims();
    foreach ($row_delims as $key => $delim)
    {
      $selected = (isset($row_delim) && $row_delim['name'] == $delim['name']) ? ' selected="selected"' : '';
      echo "<option value=\"$key\"$selected>" . $delim['name'] . "</option>\n";
    }
?>
        </select>
      </div>

<?php if ($this->dateFormat) {?>
      <div class="medium_label"><?php echo $GLOBALS['locImportExportDateFormat']?></div>
      <div class="field">
        <select id="date_format" name="date_format" onchange="settings_changed()">
<?php
        $selected = ' selected="selected"';
        foreach ($this->get_date_formats() as $fmt) {
?>
          <option value="<?php echo $fmt?>"<?php echo $selected?>><?php echo $fmt?></option>
<?php
          $selected = '';
        }
?>
          </select>
      </div>
<?php } ?>

      <div class="medium_label"><?php echo $GLOBALS['locImportDecimalSeparator']?></div>
      <div class="field">
        <input id="decimal_separator" name="decimal_separator" maxlength="1" value="<?php echo htmlentities($GLOBALS['locDecimalSeparator'])?>" onchange="settings_changed()"></input>
      </div>

      <div class="medium_label"><?php echo $GLOBALS['locImportSkipRows']?></div>
      <div class="field">
        <input id="skip_rows" name="skip_rows" onchange="settings_changed(); update_mapping_table()" value="0"></input>
      </div>

<?php if ($this->duplicateControl) { ?>
      <div class="medium_label"><?php echo $GLOBALS['locImportExistingRowHandling']?></div>
      <div class="field">
        <select id="duplicate_processing" name="duplicate_processing" onchange="settings_changed()">
          <option value="ignore" selected="selected"><?php echo $GLOBALS['locImportExistingRowIgnore']?></option>
          <option value="update"><?php echo $GLOBALS['locImportExistingRowUpdate']?></option>
        </select>
      </div>

      <div class="medium_label"><?php echo $GLOBALS['locImportIdentificationColumns']?></div>
      <div id="columns" class="field">
      </div>
<?php } ?>

      <div class="unlimited_label"><?php echo $GLOBALS['locImportColumnMapping']?></div>
      <div class="column_mapping">
        <div id="mapping_errors"></div>
        <table id="column_table">
        </table>
      </div>

      <div class="form_buttons" style="clear: both">
        <button name="import" type="submit" value="preview"><?php echo $GLOBALS['locImportButtonPreview']?></button>
        <button name="import" type="submit" value="import"><?php echo $GLOBALS['locImportButtonImport']?></button>
      </div>
    </form>
  </div>
<?php
  }

  protected function get_csv($handle, $delimiter, $enclosure, $charset, $line_ending)
  {
    $line = '';
    do {
      $str = fgets_charset($handle, $charset, $line_ending);
      $line .= $str;
      // We must be at EOF or have balanced number of enclosure characters to have a completed string
    } while ($str !== '' && $enclosure !== '' && substr_count($line, $enclosure) % 2 !== 0);
    return str_getcsv($line, $delimiter, $enclosure);
  }

  protected function process_import_row($table, $row, $dupMode, $dupCheckColumns, $mode, &$addedRecordId)
  {
  	global $dblink;

    $result = '';
    $recordId = null;
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
      $res = mysqli_param_query($query . $where, $params);
      if ($dupRow = mysqli_fetch_row($res))
      {
        $id = $dupRow[0];
        $found_dup = true;
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
          mysqli_param_query($query, $params);
        }
        return $result;
      }
    }
    // Add new row
    $query = "INSERT INTO {prefix}$table ";
    $columns = '';
    $values = '';
    $params = array();
    foreach ($row as $key => $value)
    {
      if ($key == 'id')
        continue;
      if ($columns)
        $columns .= ', ';
      if ($values)
        $values .= ', ';
      $columns .= $key;
      $values .= '?';
      $params[] = $value;
    }
    $query .= "($columns) VALUES ($values)";
    if ($mode == 'import')
    {
      mysqli_param_query($query, $params);
      $addedRecordId = mysqli_insert_id($dblink);
    }
    else
      $addedRecordId = 'x';
    $result = "Add as new (ID $addedRecordId) into table $table";
    return $result;
  }

  protected function process_child_records($parentTable, $parentId, $childRecords, $duplicateMode, $importMode, &$field_defs)
  {
    switch ($parentTable)
    {
      case 'invoice': $childTable = 'invoice_row'; break;
      case 'company': $childTable = 'company_contact'; break;
      default: die('Unsupported child table');
    }
    $childNum = 0;
    foreach ($childRecords as $childColumns)
    {
      ++$childNum;
      $childColumns["${parentTable}_id"] = $parentId;

      if (!isset($field_defs[$childTable]))
      {
        $field_defs[$childTable] = $this->get_field_defs($childTable);
      }

      foreach ($childColumns as $column => $value)
      {
        if (!isset($field_defs[$childTable][$column]))
          die("Invalid column name: $childTable." . htmlspecialchars($column));
      }
      $childDupColumns = array();
      $addedChildRecordId = null;
      $result = $this->process_import_row($childTable, $childColumns, $duplicateMode, $childDupColumns, $importMode, $addedChildrecordId);
     echo "    &nbsp; Child Record $childNum: $result<br>\n";
    }
  }

  protected function import_file($importMode)
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
    $skipRows = getRequest('skip_rows', 0);

    if (!$charset || !$format || !$fieldDelimiter || !$enclosureChar || !$rowDelimiter)
    {
      die('Invalid parameters');
    }

    if (!$this->table_valid($table))
      die('Invalid table name: ' . htmlspecialchars($table));

?>
  <div class="form_container">
    <h1><?php echo $GLOBALS['locImportResults']?></h1>
<?php
    if ($importMode != 'import') {
      echo '<p>' . $GLOBALS['locImportSimulation'] . "</p>\n";
    }

    $field_defs[$table] = $this->get_field_defs($table);

    foreach ($duplicateCheckColumns as $key => $column)
    {
      if (!$column)
        unset($duplicateCheckColumns[$key]);
      elseif (!isset($field_defs[$table][$column]))
        die('Invalid duplicate check column name: ' . htmlspecialchars($column));
    }

    if ($format == 'csv')
    {
      $fp = fopen($_SESSION['import_file'], 'r');
      if (!$fp)
        die("Could not open import file for reading");

      foreach ($columnMappings as $key => $column)
      {
        if ($column && !isset($field_defs[$table][$column]))
          die('Invalid column name: ' . htmlspecialchars($column));
      }

      $field_delims = $this->get_field_delims();
      $enclosure_chars = $this->get_enclosure_chars();
      $row_delims = $this->get_row_delims();

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
        $enclosureChar = "\x01";

      $rowNum = 1;
      for ($i = 0; $i < $skipRows; $i++) {
        $this->get_csv($fp, $fieldDelimiter, $enclosureChar, $charset, $rowDelimiter);
        ++$rowNum;
      }

      $errors = array();
      $headings = $this->get_csv($fp, $fieldDelimiter, $enclosureChar, $charset, $rowDelimiter);
      while (!feof($fp))
      {
        $row = $this->get_csv($fp, $fieldDelimiter, $enclosureChar, $charset, $rowDelimiter);
        if (!isset($row))
          break;

        ++$rowNum;
        $mapped_row = array();
        $haveMappings = false;
        for ($i = 0; $i < count($row); $i++)
        {
          if ($columnMappings[$i])
          {
            $haveMappings = true;
            $mapped_row[$columnMappings[$i]] = $row[$i];
          }
        }
        if (!$haveMappings) {
          if (!$this->ignoreEmptyRows) {
            echo "    Row $rowNum: " . $GLOBALS['locImportNoMappedColumns'] . "<br>\n";
          }
        }
        else
        {
          $addedRecordId = null;
          $result = $this->process_import_row($table, $mapped_row, $duplicateMode, $duplicateCheckColumns, $importMode, $addedRecordId);
          if ($result) {
            echo $GLOBALS['locImportRow'] . " $rowNum: " . htmlspecialchars($result) . "<br>\n";
          }
        }
        ob_flush();
      }
      fclose($fp);
      if ($_SESSION['import_file'] != _IMPORT_FILE_ && $importMode == 'import')
        unlink($_SESSION['import_file']);
    }
    elseif ($format == 'xml')
    {
      $data = file_get_contents($_SESSION['import_file']);
      if ($charset != _CHARSET_)
        $data = iconv($charset, _CHARSET_, $data);

      try
      {
        $xml = new SimpleXMLElement($data);
      }
      catch (Exception $e)
      {
        die('XML parsing failed: ' . htmlspecialchars($e->getMessage()));
      }
      $errors = array();
      $recNum = 0;
      foreach ($xml as $record)
      {
        $record = get_object_vars($record);

        $childRecords = array();
        $mapped_row = array();
        foreach ($record as $column => $value)
        {
          if (is_array($value))
          {
            foreach ($value as $subRecord)
            {
              $childRecords[] = get_object_vars($subRecord);
            }
          }
          elseif (is_object($value))
            $childRecords[] = get_object_vars($value);
          else
          {
            if (!isset($field_defs[$table][$column]))
              die("Invalid column name: $table." . htmlspecialchars($column));
            $mapped_row[$column] = $value;
          }
        }

        ++$recNum;
        $addedRecordId = null;
        $result = $this->process_import_row($table, $mapped_row, $duplicateMode, $duplicateCheckColumns, $importMode, $addedRecordId);
        if ($result) {
          echo "    Record $recNum: $result<br>\n";
        }
        if (isset($addedRecordId)) // Updating not feasible || $duplicateMode == 'update')
        {
          $this->process_child_records($table, $addedRecordId, $childRecords, $duplicateMode, $importMode, $field_defs);
        }
        ob_flush();
      }
    }
    elseif ($format == 'json')
    {
      $data = file_get_contents($_SESSION['import_file']);
      if ($data === false)
      {
        echo json_encode(array('errors' => array('Could not open import file for reading')));
        error_log("Could not open import file '" + $_SESSION['import_file'] + "' for reading");
        exit;
      }

      if ($charset != _CHARSET_)
        $data = iconv($charset, _CHARSET_, $data);

      $data = json_decode($data, true);
      if ($data === null)
      {
        echo json_encode(array('errors' => array('Could not decode JSON')));
        error_log('JSON parsing failed');
        exit;
      }
      $recNum = 0;
      $headings = array();
      $rows = array();

      foreach (reset($data) as $record)
      {
        $childRecords = array();
        $mapped_row = array();
        foreach ($record as $column => $value)
        {
          if (is_array($value))
          {
            foreach ($value as $subRecord)
            {
              $childRecords[] = $subRecord;
            }
          }
          elseif (is_object($value))
            $childRecords[] = get_object_vars($value);
          else
          {
            if (!isset($field_defs[$table][$column]))
              die("Invalid column name: $table." . htmlspecialchars($column));
            $mapped_row[$column] = $value;
          }
        }

        ++$recNum;
        $addedRecordId = null;
        $result = $this->process_import_row($table, $mapped_row, $duplicateMode, $duplicateCheckColumns, $importMode, $addedRecordId);
        if ($result) {
          echo "    Record $recNum: $result<br>\n";
        }
        if (isset($addedRecordId)) // Updating not feasible || $duplicateMode == 'update')
        {
          process_child_records($table, $addedRecordId, $childRecords, $duplicateMode, $importMode, $field_defs);
        }
        ob_flush();
      }
    }
    echo '    ' . $GLOBALS['locImportDone'] . "\n";
  ?>
    </div>
  <?php
  }

  protected function table_valid($table)
  {
    return table_valid($table);
  }
}
