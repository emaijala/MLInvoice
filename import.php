<?php
/**
 * Import base class
 *
 * PHP version 5
 *
 * Copyright (C) 2010-2018 Ere Maijala
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category MLInvoice
 * @package  MLInvoice\Base
 * @author   Ere Maijala <ere@labs.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://labs.fi/mlinvoice.eng.php
 */
require_once 'translator.php';
require_once 'miscfuncs.php';
require_once 'settings.php';

/**
 * Base class for import functions
 *
 * @category MLInvoice
 * @package  MLInvoice\Base
 * @author   Ere Maijala <ere@labs.fi>
 * @license  https://opensource.org/licenses/GPL-2.0 GNU Public License 2.0
 * @link     http://github.com/emaijala/MLInvoice
 */
class ImportFile
{
    protected $tableName = '';
    protected $allowServerFile = true;
    protected $duplicateControl = true;
    protected $dateFormat = false;
    protected $decimalSeparator = false;
    protected $ignoreEmptyRows = false;
    protected $presets = [];
    protected $requireDuplicateCheck = true;
    protected $mappingsForXml = false;

    /**
     * Settings for fixed width file import (format=fixed). Keyed array:
     *
     * [
     *     ['name' => 'heading', 'len' => 7, 'filter' => [3, 'ok']],
     *     ['name' => 'heading2', 'len' => 10]
     * ]
     *
     * @var array
     */
    protected $fixedWidthSettings = [];

    /**
     * Name of the fixed-width format
     *
     * @var string
     */
    protected $fixedWidthName = 'Fixed';

    /**
     * Available character sets
     */
    protected $charsets = [
        'UTF-8',
        'ISO-8859-1',
        'ISO-8859-15',
        'Windows-1251',
        'UTF-16',
        'UTF-16LE',
        'UTF-16BE'
    ];

    /**
     * Available date formats
     */
    protected $dateFormats = [
        'd.m.Y',
        'd-m-Y',
        'd/m/Y',
        'Y.m.d',
        'Y-m-d',
        'Y/m/d',
        'm.d.Y',
        'm-d-Y',
        'm/d/Y',
        'ymd'
    ];

    /**
     * Constructor
     */
    public function __construct()
    {
    }

    /**
     * Start import
     *
     * @return void
     */
    public function launch()
    {
        $filetype = getRequest('filetype', '');

        $error = '';
        if ($filetype == 'upload') {
            if ($_FILES['data']['error'] == UPLOAD_ERR_OK) {
                $_SESSION['import_file'] = $_FILES['data']['tmp_name']
                     . '-mlinvoice-import';
                move_uploaded_file(
                    $_FILES['data']['tmp_name'], $_SESSION['import_file']
                );
                $this->showSetupForm();
                return;
            }
            $error = Translator::translate('ErrFileUploadFailed');
        } elseif ($this->allowServerFile && $filetype == 'server_file') {
            if (_IMPORT_FILE_ && file_exists(_IMPORT_FILE_)) {
                $_SESSION['import_file'] = _IMPORT_FILE_;
                $this->showSetupForm();
                return;
            }
            $error = Translator::translate('ErrImportFileNotFound');
        }

        $importMode = getRequest('import', '');
        if (($importMode == 'import' || $importMode == 'preview')
            && isset($_SESSION['import_file'])
        ) {
            $this->importFile($importMode);
            return;
        }

        unset($_SESSION['import_file']);
        $maxUploadSize = getMaxUploadSize();
        $maxFileSize = fileSizeToHumanReadable($maxUploadSize);
        ?>

<div class="form_container">
    <?php
    if ($error) {
        echo "<div class=\"error\">$error</div>\n";
    }
    ?>
    <h1><?php echo Translator::translate('ImportFileSelection')?></h1>
    <span id="imessage" style="display: none"></span> <span id="spinner"
        style="visibility: hidden"><img src="images/spinner.gif" alt=""></span>
    <form id="form_import" enctype="multipart/form-data" method="POST">
        <input type="hidden" name="func"
            value="<?php echo htmlentities(getRequest('func', ''))?>"> <input
            type="hidden" name="operation" value="import">
        <div class="label"
            style="clear: both; margin-top: 10px; margin-bottom: 4px">
            <input type="radio" id="ft_upload" name="filetype" value="upload"
                checked="checked"><label for="ft_upload"><?php printf(Translator::translate('ImportUploadFile'), $maxFileSize)?></label>
        </div>
        <div class="long">
            <input name="data" type="file">
        </div>
<?php if ($this->allowServerFile) {?>
      <div class="label" style="clear: both; margin-top: 10px">
            <input type="radio" id="ft_server" name="filetype"
                value="server_file"><label for="ft_server"><?php echo Translator::translate('ImportUseServerFile')?></label>
        </div>
<?php }?>
      <div class="form_buttons" style="clear: both">
            <input type="submit" value="<?php echo Translator::translate('ImportNext')?>">
        </div>
    </form>
</div>
<?php
    }

    /**
     * Create an import preview
     *
     * @return string JSON
     */
    public function createImportPreview()
    {
        $charset = getRequest('charset', 'UTF-8');
        $table = getRequest('table', '');
        $format = getRequest('format', '');
        $fieldDelimiter = getRequest('field_delim', 'comma');
        $enclosureChar = getRequest('enclosure_char', 'doublequote');
        $rowDelimiter = getRequest('row_delim', 'lf');
        $skipRows = getRequest('skip_rows', 0);

        if (!$charset || !$table || !$format || !$fieldDelimiter || !$enclosureChar
            || !$rowDelimiter
        ) {
            header('HTTP/1.1 400 Bad Request');
            exit();
        }
        if (!$this->isTableNameValid($table)) {
            header('HTTP/1.1 400 Bad Request');
            die('Invalid table name');
        }

        header('Content-Type: application/json');
        $response = [];

        if ($format == 'csv') {
            $fp = fopen($_SESSION['import_file'], 'r');
            if (!$fp) {
                echo json_encode(
                    ['errors' => ['Could not open import file for reading']]
                );
                die(
                    "Could not open import file '" . $_SESSION['import_file']
                    . "' for reading"
                );
            }

            $field_delims = $this->getFieldDelims();
            $enclosure_chars = $this->getEnclosureChars();
            $row_delims = $this->getRowDelims();

            if (!isset($field_delims[$fieldDelimiter])) {
                die('Invalid field delimiter');
            }
            $fieldDelimiter = $field_delims[$fieldDelimiter]['char'];
            if (!isset($enclosure_chars[$enclosureChar])) {
                die('Invalid enclosure character');
            }
            $enclosureChar = $enclosure_chars[$enclosureChar]['char'];
            if (!isset($row_delims[$rowDelimiter])) {
                die('Invalid field delimiter');
            }
            $rowDelimiter = $row_delims[$rowDelimiter]['char'];

            // Force enclosure char, otherwise fgetcsv would balk.
            if ($enclosureChar == '') {
                $enclosureChar = "\x01";
            }

            for ($i = 0; $i < $skipRows; $i ++) {
                $this->getCsv(
                    $fp, $fieldDelimiter, $enclosureChar, $charset, $rowDelimiter
                );
            }

            $errors = [];
            $headings = $this->getCsv(
                $fp, $fieldDelimiter, $enclosureChar, $charset, $rowDelimiter
            );
            if (!$headings) {
                $errors[] = 'Could not parse headings row from import file';
            }
            $rows = [];
            for ($i = 0; $i < 10 && !feof($fp); $i ++) {
                $row = $this->getCsv(
                    $fp, $fieldDelimiter, $enclosureChar, $charset, $rowDelimiter
                );
                if (null === $row) {
                    $errors[] = 'Could not read row from import file';
                    break;
                }
                if ([] === $row) {
                    continue;
                }
                $rows[] = $row;
            }
            $response = [
                'errors' => $errors,
                'headings' => $headings,
                'rows' => $rows
            ];
            fclose($fp);
        } elseif ($format == 'xml') {
            $data = file_get_contents($_SESSION['import_file']);
            if ($data === false) {
                echo json_encode(
                    ['errors' => ['Could not open import file for reading']]
                );
                die(
                    "Could not open import file '" . $_SESSION['import_file']
                    . "' for reading"
                );
            }

            if ($charset != _CHARSET_) {
                $data = iconv($charset, _CHARSET_, $data);
            }

            try {
                $xml = new SimpleXMLElement($data);
            } catch (Exception $e) {
                echo json_encode(['errors' => [$e->getMessage()]]);
                die('XML parsing failed: ' . htmlspecialchars($e->getMessage()));
            }
            $this->getXmlPreviewData($xml, $headings, $rows, $errors);
            $response = [
                'errors' => $errors,
                'headings' => $headings,
                'rows' => $rows
            ];
        } elseif ($format == 'json') {
            $data = file_get_contents($_SESSION['import_file']);
            if ($data === false) {
                echo json_encode(
                    ['errors' => ['Could not open import file for reading']]
                );
                error_log(
                    "Could not open import file '" . $_SESSION['import_file']
                    . "' for reading"
                );
                exit();
            }

            if ($charset != _CHARSET_) {
                $data = iconv($charset, _CHARSET_, $data);
            }

            $data = json_decode($data, true);
            if ($data === null) {
                echo json_encode(['errors' => ['Could not decode JSON']]);
                error_log('JSON parsing failed');
                exit();
            }
            $recNum = 0;
            $headings = [];
            $rows = [];

            foreach (reset($data) as $record) {
                if (++$recNum > 10) {
                    break;
                }

                $row = [];
                foreach ($record as $column => $value) {
                    if (is_array($value)) {
                        continue;
                    }
                    if ($recNum == 1) {
                        $headings[] = $column;
                    }
                    $row[] = $value;
                }
                $rows[] = $row;
            }
            $response = [
                'errors' => [],
                'headings' => $headings,
                'rows' => $rows
            ];
        } elseif ($format == 'fixed') {
            $data = file_get_contents($_SESSION['import_file']);

            if ($charset != _CHARSET_) {
                $data = iconv($charset, _CHARSET_, $data);
            }
            $recNum = 0;
            $rows = [];
            foreach (explode("\n", $data) as $line) {
                if (++$recNum > 10) {
                    break;
                }
                $line = trim($line, "\r");
                $pos = 0;
                $row = [];
                foreach ($this->fixedWidthSettings as $column) {
                    $value = substr($line, $pos, $column['len']);
                    if (!empty($column['filter'])
                        && !in_array($value, $column['filter'])
                    ) {
                        // Ignore line
                        --$recNum;
                        continue 2;
                    }
                    $row[] = $value;
                    $pos += $column['len'];
                }
                $rows[] = $row;
            }
            $headings = array_map(
                function ($row) {
                    return $row['name'];
                },
                $this->fixedWidthSettings
            );

            $response = [
                'errors' => [],
                'headings' => $headings,
                'rows' => $rows
            ];
        }
        echo json_encode($response);
    }

    /**
     * Get row delimiters
     *
     * @return array
     */
    public function getRowDelims()
    {
        return [
            'lf' => [
                'char' => "\n",
                'name' => 'LF'
            ],
            'crlf' => [
                'char' => "\r\n",
                'name' => 'CR+LF'
            ],
            'cr' => [
                'char' => "\r",
                'name' => 'CR'
            ]
        ];
    }

    /**
     * Get field delimiters
     *
     * @return array
     */
    public function getFieldDelims()
    {
        return [
            'comma' => [
                'char' => ',',
                'name' => Translator::translate('ImportExportFieldDelimiterComma')
            ],
            'semicolon' => [
                'char' => ';',
                'name' => Translator::translate('ImportExportFieldDelimiterSemicolon')
            ],
            'tab' => [
                'char' => "\t",
                'name' => Translator::translate('ImportExportFieldDelimiterTab')
            ],
            'pipe' => [
                'char' => '|',
                'name' => Translator::translate('ImportExportFieldDelimiterPipe')
            ],
            'colon' => [
                'char' => ':',
                'name' => Translator::translate('ImportExportFieldDelimiterColon')
            ]
        ];
    }

    /**
     * Get enclosure characters
     *
     * @return array
     */
    public function getEnclosureChars()
    {
        return [
            'doublequote' => [
                'char' => '"',
                'name' => Translator::translate('ImportExportEnclosureDoubleQuote')
            ],
            'singlequote' => [
                'char' => '\'',
                'name' => Translator::translate('ImportExportEnclosureSingleQuote')
            ],
            'none' => [
                'char' => '',
                'name' => Translator::translate('ImportExportEnclosureNone')
            ]
        ];
    }

    /**
     * Add any custom fields to the form
     *
     * @return void
     */
    protected function addCustomFormFields()
    {
    }

    /**
     * Get field definitions for a table
     *
     * @param string $table Table name
     *
     * @return array
     */
    protected function getFieldDefs($table)
    {
        if (!$this->isTableNameValid($table)) {
            return [];
        }
        $res = dbQueryCheck("show fields from {prefix}$table");
        $fieldDefs = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $fieldDefs[$row['Field']] = $row;
        }
        if ('company' === $table || 'company_contact' === $table) {
            $fieldDefs['tags'] = ['Type' => 'text'];
        }
        if ('custom_price_map' === $table) {
            $fieldDefs['company_id'] = ['Type' => 'int'];
        }
        return $fieldDefs;
    }

    /**
     * Get preview data for XML import
     *
     * @param SimpleXMLElement $xml      XML
     * @param array            $headings Resulting headings
     * @param array            $rows     Resulting rows
     * @param array            $errors   Any errors
     *
     * @return void
     */
    protected function getXmlPreviewData($xml, &$headings, &$rows, &$errors)
    {
        $headings = [];
        $rows = [];
        $errors = [];
        $recNum = 0;
        foreach ($xml as $record) {
            if (++$recNum > 10) {
                break;
            }
            $record = get_object_vars($record);

            $row = [];
            foreach ($record as $column => $value) {
                if (!is_array($value) && !is_object($value)) {
                    if ($recNum == 1) {
                        $headings[] = $column;
                    }
                    $row[] = $value;
                }
            }
            $rows[] = $row;
        }
    }

    /**
     * Display the import setup form
     *
     * @return void
     */
    protected function showSetupForm()
    {
        $fp = fopen($_SESSION['import_file'], 'r');
        if (!$fp) {
            die('Could not open import file for reading');
        }

        $data = fread($fp, 8192);
        $bytesRead = ftell($fp);

        fclose($fp);

        $charset = 'UTF-8';
        $dateFormat = $this->dateFormats[0];
        $decimalSeparator = Translator::translate('DecimalSeparator');

        if ($bytesRead > 3) {
            if (ord($data[0]) == 0xFE && ord($data[1]) == 0xFF) {
                $charset = 'UTF-16BE';
                $data = iconv('UTF-16BE', _CHARSET_, $data);
            } elseif (ord($data[0]) == 0xFF && ord($data[1]) == 0xFE) {
                $charset = 'UTF-16LE';
                $data = iconv('UTF-16LE', _CHARSET_, $data);
            } elseif (ord($data[0]) == 0 && ord($data[2]) == 0) {
                $charset = 'UTF-16BE';
                $data = iconv('UTF-16BE', _CHARSET_, $data);
            } elseif (ord($data[1]) == 0 && ord($data[2]) == 0) {
                $charset = 'UTF-16LE';
                $data = iconv('UTF-16LE', _CHARSET_, $data);
            }
        }

        if (strtolower(substr(ltrim($data), 0, 5)) == '<?xml') {
            $format = 'xml';
        } elseif (strtolower(substr(ltrim($data), 0, 1)) == '{') {
            $format = 'json';
        } elseif ($this->fixedWidthSettings && $this->getDelimiterCount($data) == 0
        ) {
            $format = 'fixed';
        } else {
            $format = 'csv';

            $row_delims = $this->getRowDelims();
            foreach ($row_delims as $key => $value) {
                $row_delims[$key]['count'] = substr_count($data, $value['char']);
            }
            $selected = reset($row_delims);
            foreach ($row_delims as $key => $value) {
                if ($value['count'] > 0 && $value['count'] >= $selected['count']
                    && strlen($value['char']) >= strlen($selected['char'])
                ) {
                    $selected = $value;
                }
            }
            $row_delim = $selected;

            $field_delims = $this->getFieldDelims();
            $rows = explode($row_delim['char'], $data);
            foreach ($rows as $row) {
                foreach ($field_delims as $key => $value) {
                    if (!isset($field_delims[$key]['count'])) {
                        $field_delims[$key]['count'] = 0;
                    }
                    $field_delims[$key]['count']
                        += substr_count($row, $value['char']);
                }
            }
            $selected = reset($field_delims);
            foreach ($field_delims as $key => $value) {
                if ($value['count'] > 0 && $value['count'] >= $selected['count']) {
                    $selected = $value;
                }
            }
            $field_delim = $selected;

            $enclosure_chars = $this->getEnclosureChars();
            foreach ($rows as $row) {
                if ($charset == 'UTF-8'
                    && $this->tryIconv($charset, _CHARSET_, $row) === false
                ) {
                    if ($this->tryIconv('ISO-8859-1', _CHARSET_, $row) !== false) {
                        $charset = 'ISO-8859-1';
                    }
                }
                foreach (explode($field_delim['char'], $row) as $field) {
                    foreach ($enclosure_chars as $key => $value) {
                        if (!isset($enclosure_chars[$key]['count'])) {
                            $enclosure_chars[$key]['count'] = 0;
                        }
                        $char = $value['char'];
                        if ($char === '') {
                            continue;
                        }
                        if (substr($field, 0, strlen($char)) == $char
                            && substr($field, -strlen($char)) == $char
                        ) {
                            $enclosure_chars[$key]['count']++;
                        }
                    }
                }
            }
            $selected = $enclosure_chars['none'];
            foreach ($enclosure_chars as $key => $value) {
                if ($value['count'] > 0 && $value['count'] >= $selected['count']) {
                    $selected = $value;
                }
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
    option.value = '';
    option.text = "<?php echo Translator::translate('ImportColumnUnused')?>";
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
    if (g_column_id == 1) {
        $(select).find('option[value="id"]').attr('selected', 'selected');
        $(select).change();
    }
  });
}

function settings_changed()
{
  $("#preset").val('');
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
  if (type != 'csv' && type != 'fixed'<?php echo $this->mappingsForXml ? " && type != 'xml'" : '' ?>)
    return;
  var table = document.getElementById("sel_table").value;
  $.getJSON("json.php?func=get_table_columns&table=" + table, function(json) {
    var columns = document.getElementById("columns");
    var select = document.createElement("select");
    select.name = "map_column[]";
    select.onchange = "settings_changed()";
    var option = document.createElement("option");
    option.value = "";
    option.text = "<?php echo Translator::translate('ImportExportColumnNone')?>";
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
    var value = $("#preset").val();
    $.each(g_presets, function(index, preset) {
      if (preset['value'] == value) {
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
  var value = $("#preset").val();
  $.each(g_presets, function(index, preset) {
    if (preset['value'] == value) {
      $.each(preset['selections'], function(element, value) {
        var elem = $('#' + element).get(0);
        if (elem) elem.selectedIndex = value;
      });
      $.each(preset['values'], function(element, value) {
        $('#' + element).val(value);
      });
      update_field_states();
      update_mapping_table();
    }
  });
}

</script>

<div class="form_container">
    <h1><?php echo Translator::translate('ImportFileParameters')?></h1>
    <span id="imessage" style="display: none"></span> <span id="spinner"
       style="visibility: hidden"><img src="images/spinner.gif" alt=""></span>
    <form id="import_form" name="import_form" method="GET">
        <input type="hidden" name="func" value="<?php echo htmlentities(getRequest('func', ''))?>">
        <input type="hidden" name="operation" value="import">
    <?php
    if ($this->presets) {
        $presets = $this->presets;
        $selectedPreset = null;
        array_unshift($presets, ['name' => Translator::translate('ImportExportPresetNone'), 'value' => '']);
        foreach ($presets as $preset) {
            if (isset($preset['default_for']) && $format == $preset['default_for']) {
                $selectedPreset = $preset;
                if (isset($preset['selections']['charset'])) {
                    $charset = $this->charsets[$preset['selections']['charset']];
                }
                if (isset($preset['selections']['date_format'])) {
                    $dateFormat = $this->dateFormats[$preset['selections']['date_format']];
                }
                if (isset($preset['values']['decimal_separator'])) {
                    $decimalSeparator = $preset['values']['decimal_separator'];
                }
                break;
            }
        }
        ?>
        <div class="medium_label"><?php echo Translator::translate('ImportExportPreset')?></div>
        <div class="field">
            <select id="preset" name="preset" onchange="select_preset()">
            <?php
            foreach ($this->presets as $preset) {
                echo "<option value=\"{$preset['value']}\""
                    . ($selectedPreset['value'] == $preset['value'] ? ' selected="selected"' : '')
                    . '>' . $preset['name'] . "</option>\n";
            }
            ?>
            </select>
        </div>
    <?php
    }
    ?>

        <div class="medium_label"><?php echo Translator::translate('ImportExportCharacterSet')?></div>
        <div class="field">
            <select id="charset" name="charset"
                onchange="settings_changed(); update_mapping_table()">
            <?php foreach ($this->charsets as $value) { ?>
                <option value="<?php echo $value ?>"<?php if ($value == $charset) echo ' selected="selected"'?>><?php echo $value ?></option>
            <?php } ?>
            </select>
        </div>
        <?php
        if ($this->tableName) {
        ?>
        <input id="sel_table" name="table" type="hidden" value="<?php echo htmlentities($this->tableName)?>"></input>
        <?php
        } else {
        ?>
        <div class="medium_label"><?php echo Translator::translate('ImportExportTable')?></div>
        <div class="field">
            <select id="sel_table" name="table"
                onchange="reset_columns(); settings_changed(); update_mapping_table()">
                <option value="company"><?php echo Translator::translate('ImportExportTableCompanies')?></option>
                <option value="company_contact"><?php echo Translator::translate('ImportExportTableCompanyContacts')?></option>
                <option value="base"><?php echo Translator::translate('ImportExportTableBases')?></option>
                <option value="invoice"><?php echo Translator::translate('ImportExportTableInvoices')?></option>
                <option value="invoice_row"><?php echo Translator::translate('ImportExportTableInvoiceRows')?></option>
                <option value="product"><?php echo Translator::translate('ImportExportTableProducts')?></option>
                <option value="row_type"><?php echo Translator::translate('ImportExportTableRowTypes')?></option>
                <option value="invoice_state"><?php echo Translator::translate('ImportExportTableInvoiceStates')?></option>
                <option value="delivery_terms"><?php echo Translator::translate('ImportExportTableDeliveryTerms')?></option>
                <option value="delivery_method"><?php echo Translator::translate('ImportExportTableDeliveryMethods')?></option>
                <option value="stock_balance_log"><?php echo Translator::translate('ImportExportTableStockBalanceLog')?></option>
                <option value="default_value"><?php echo Translator::translate('ImportExportTableDefaultValues')?></option>
                <option value="custom_price"><?php echo Translator::translate('ImportExportTableCustomPrices')?></option>
                <option value="custom_price_map"><?php echo Translator::translate('ImportExportTableCustomPriceMaps')?></option>
            </select>
        </div>
        <?php
        }
        ?>

        <div class="medium_label"><?php echo Translator::translate('ImportExportFormat')?></div>
        <div class="field">
            <select id="format" name="format"
                onchange="update_field_states(); reset_columns(); settings_changed(); update_mapping_table()">
                <option value="csv"<?php if ($format == 'csv') echo ' selected="selected"'?>>CSV</option>
                <option value="xml"<?php if ($format == 'xml') echo ' selected="selected"'?>>XML</option>
                <option value="json"<?php if ($format == 'json') echo ' selected="selected"'?>>JSON</option>
        <?php
        if ($this->fixedWidthSettings) {
        ?>
                <option value="fixed"<?php if ($format == 'fixed') echo ' selected="selected"'?>><?php echo $this->fixedWidthName ?></option>
        <?php
        }
        ?>
            </select>
        </div>

        <div class="medium_label"><?php echo Translator::translate('ImportExportFieldDelimiter')?></div>
        <div class="field">
            <select id="field_delim" name="field_delim"
                onchange="settings_changed(); update_mapping_table()">
        <?php
        $field_delims = $this->getFieldDelims();
        foreach ($field_delims as $key => $delim) {
            $selected = (isset($field_delim) && $field_delim['name'] ==
                 $delim['name']) ? ' selected="selected"' : '';
            echo "                <option value=\"$key\"$selected>" . $delim['name'] . "</option>\n";
        }
        ?>
            </select>
        </div>

        <div class="medium_label"><?php echo Translator::translate('ImportExportEnclosureCharacter')?></div>
        <div class="field">
            <select id="enclosure_char" name="enclosure_char"
                onchange="settings_changed(); update_mapping_table()">
        <?php
        $enclosure_chars = $this->getEnclosureChars();
        foreach ($enclosure_chars as $key => $delim) {
            $selected = (isset($enclosure_char) &&
                 $enclosure_char['name'] == $delim['name']) ? ' selected="selected"' : '';
            echo "                <option value=\"$key\"$selected>" . $delim['name'] . "</option>\n";
        }
        ?>
            </select>
        </div>

        <div class="medium_label"><?php echo Translator::translate('ImportExportRowDelimiter')?></div>
        <div class="field">
            <select id="row_delim" name="row_delim"
                onchange="settings_changed(); update_mapping_table()">
        <?php
        $row_delims = $this->getRowDelims();
        foreach ($row_delims as $key => $delim) {
            $selected = (isset($row_delim) && $row_delim['name'] == $delim['name']) ? ' selected="selected"' : '';
            echo "                <option value=\"$key\"$selected>" . $delim['name'] . "</option>\n";
        }
        ?>
            </select>
        </div>

        <?php
        if ($this->dateFormat) {
        ?>
        <div class="medium_label"><?php echo Translator::translate('ImportExportDateFormat')?></div>
        <div class="field">
            <select id="date_format" name="date_format" onchange="settings_changed()">
            <?php
            foreach ($this->dateFormats as $fmt) {
            ?>
                <option value="<?php echo $fmt?>" <?php if ($fmt == $dateFormat) echo 'selected="selected"' ?>><?php echo $fmt?></option>
            <?php
            }
            ?>
            </select>
        </div>
        <?php
        }
        ?>

        <div class="medium_label"><?php echo Translator::translate('ImportDecimalSeparator')?></div>
        <div class="field">
            <input id="decimal_separator" name="decimal_separator" maxlength="1"
                value="<?php echo htmlentities($decimalSeparator)?>"
                onchange="settings_changed()"></input>
        </div>

        <div class="medium_label"><?php echo Translator::translate('ImportSkipRows')?></div>
        <div class="field">
            <input id="skip_rows" name="skip_rows"
                onchange="settings_changed(); update_mapping_table()" value="0">
            </input>
        </div>

<?php if ($this->duplicateControl) { ?>
      <div class="medium_label"><?php echo Translator::translate('ImportExistingRowHandling')?></div>
        <div class="field">
            <select id="duplicate_processing" name="duplicate_processing"
                onchange="settings_changed()">
                <option value="ignore" selected="selected"><?php echo Translator::translate('ImportExistingRowIgnore')?></option>
                <option value="update"><?php echo Translator::translate('ImportExistingRowUpdate')?></option>
            </select>
        </div>

        <div class="medium_label"><?php echo Translator::translate('ImportIdentificationColumns')?></div>
        <div id="columns" class="field"></div>
<?php } ?>

<?php $this->addCustomFormFields(); ?>

        <div class="unlimited_label"><?php echo Translator::translate('ImportColumnMapping')?></div>
        <div class="column_mapping">
            <div id="mapping_errors"></div>
            <table id="column_table">
            </table>
        </div>

        <div class="form_buttons" style="clear: both">
            <button name="import" type="submit" value="preview"><?php echo Translator::translate('ImportButtonPreview')?></button>
            <button name="import" type="submit" value="import"><?php echo Translator::translate('ImportButtonImport')?></button>
        </div>
    </form>
</div>
<?php
    }

    /**
     * Get a line from file with the given charset and line ending
     *
     * @param resource $handle     File handle
     * @param string   $charset    Character set
     * @param string   $lineEnding Line ending
     *
     * @return string
     */
    protected function fgetsCharset($handle, $charset, $lineEnding = "\n")
    {
        if (strncmp($charset, 'UTF-16', 6) == 0) {
            $be = $charset == 'UTF-16' || $charset == 'UTF-16BE';
            $str = '';
            $le_pos = 0;
            $le_len = strlen($lineEnding);
            while (!feof($handle)) {
                $c1 = fgetc($handle);
                $c2 = fgetc($handle);
                if ($c1 === false || $c2 === false) {
                    break;
                }
                $str .= $c1 . $c2;
                if (($be && ord($c1) == 0 && $c2 == $lineEnding[$le_pos])
                    || (!$be && ord($c2) == 0 && $c1 == $lineEnding[$le_pos])
                ) {
                    if (++$le_pos >= $le_len) {
                        break;
                    }
                } else {
                    $le_pos = 0;
                }
            }
            $str = iconv($charset, _CHARSET_, $str);
        } else {
            $str = '';
            $le_pos = 0;
            $le_len = strlen($lineEnding);
            while (!feof($handle)) {
                $c1 = fgetc($handle);
                if ($c1 === false) {
                    break;
                }
                $str .= $c1;
                if ($c1 == $lineEnding[$le_pos]) {
                    if (++$le_pos >= $le_len) {
                        break;
                    }
                } else {
                    $le_pos = 0;
                }
            }
            $conv_str = iconv($charset, _CHARSET_, $str);
            if ($str && !$conv_str) {
                error_log(
                    "Conversion from '$charset' to '" . _CHARSET_
                    . "' failed for string '$str'"
                );
            } else {
                $str = $conv_str;
            }
        }
        return $str;
    }

    /**
     * Get CSV data from a file
     *
     * @param resource $handle     File handle
     * @param string   $delimiter  Field delimiter
     * @param string   $enclosure  Enclosure character
     * @param string   $charset    Character set
     * @param string   $lineEnding Line ending style
     *
     * @return array
     */
    protected function getCsv($handle, $delimiter, $enclosure, $charset, $lineEnding
    ) {
        $line = '';
        do {
            $str = $this->fgetsCharset($handle, $charset, $lineEnding);
            $line .= $str;
            // We must be at EOF or have balanced number of enclosure characters to
            // have a completed string
        } while ($str !== '' && $enclosure !== ''
             && substr_count($line, $enclosure) % 2 !== 0
        );
        if ('' === $line) {
            return [];
        }

        // Polyfill for str_getcsv
        if (!function_exists('str_getcsv')) {
            $strGetCsv = function ($input, $delimiter = ',', $enclosure = '"') {
                $temp = fopen('php://memory', 'rw');
                fwrite($temp, $input);
                fseek($temp, 0);
                $r = fgetcsv($temp, 4096, $delimiter, $enclosure);
                fclose($temp);
                return $r;
            };
        } else {
            $strGetCsv = str_getcsv;
        }
        return $strGetCsv($line, $delimiter, $enclosure);
    }

    /**
     * Process a row to import
     *
     * @param string $table            Table name
     * @param array  $row              Row data
     * @param string $dupMode          Duplicate handling mode ('ignore' or 'update')
     * @param array  $dupCheckColumns  Columns to use for duplicate check
     * @param string $mode             Mode ('preview' or 'import')
     * @param string $decimalSeparator Decimal separator
     * @param array  $fieldDefs        Field definitions
     * @param int    $addedRecordId    ID of the added record
     *
     * @return string Result message
     */
    protected function processImportRow($table, $row, $dupMode, $dupCheckColumns,
        $mode, $decimalSeparator, $fieldDefs, &$addedRecordId
    ) {
        global $dblink;

        if ('custom_price_map' === $table && !isset($row['custom_price_id'])
            && isset($row['company_id'])
        ) {
            static $customPrice = null;
            if (!$customPrice || $customPrice['company_id'] != $row['company_id']) {
                $customPrice = getCustomPriceSettings($row['company_id']);
                if (!$customPrice) {
                    $customPrice = setCustomPriceSettings(
                        $row['company_id'],
                        0,
                        1,
                        null
                    );
                    $customPrice = getCustomPriceSettings($row['company_id']);
                }
            }
            if ($customPrice) {
                $row['custom_price_id'] = $customPrice['id'];
            }
            unset($row['company_id']);
        }

        foreach ($row as $key => &$value) {
            if (isset($fieldDefs[$key])) {
                $fieldDef = $fieldDefs[$key];
                list($type) = explode('(', $fieldDef['Type'], 2);
                if ($decimalSeparator != '.'
                    && in_array($type, ['decimal', 'numeric', 'float', 'double'])
                ) {
                    $value = str_replace($decimalSeparator, '.', $value);
                }
                if ('' === $value
                    && in_array(
                        $type, ['int', 'decimal', 'numeric', 'float', 'double']
                    )
                ) {
                    $value = null;
                }
            }
        }
        unset($value);

        $result = '';
        $recordId = null;
        if ('' != $dupMode && $dupCheckColumns) {
            $query = "select id from {prefix}$table where Deleted=0";
            $where = '';
            $params = [];
            foreach ($dupCheckColumns as $dupCol) {
                if (!isset($row[$dupCol])) {
                    continue;
                }
                $where .= " AND $dupCol=?";
                $params[] = $row[$dupCol];
            }
            if ($params) {
                $dupRows = dbParamQuery($query . $where, $params);
                if ($dupRows) {
                    $id = $dupRows[0][0];
                    $found_dup = true;
                    if ($dupMode == 'update') {
                        $result = "Update existing row id $id in table $table";
                    } else {
                        $result = "Not updating existing row id $id in table $table";
                    }

                    if ($mode == 'import' && $dupMode == 'update') {
                        // Update existing row
                        $query = "UPDATE {prefix}$table SET ";
                        $columns = [];
                        $params = [];
                        foreach ($row as $key => $value) {
                            if ('id' === $key || 'tags' === $key) {
                                continue;
                            }
                            $columns[] = "$key=?";
                            $params[] = $value;
                        }
                        $query .= implode(',', $columns) . ' WHERE id=?';
                        $params[] = $id;
                        dbParamQuery($query, $params);
                        if (in_array($table, ['company', 'company_contact'])
                            && isset($row['tags'])
                        ) {
                            $type = $table === 'company' ? 'company' : 'contact';
                            saveTags($type, $id, $row['tags']);
                        }
                    }
                    return $result;
                }
            }
        }
        // Add new row
        $query = "INSERT INTO {prefix}$table ";
        $columns = [];
        $values = [];
        $params = [];
        foreach ($row as $key => $value) {
            if ('id' === $key || 'tags' === $key) {
                continue;
            }
            $columns[] = $key;
            $values[] = '?';
            $params[] = $value;
        }
        $query .= '(' . implode(',', $columns) . ') VALUES (' . implode(',', $values)
            . ')';
        if ($mode == 'import') {
            dbParamQuery($query, $params);
            $addedRecordId = mysqli_insert_id($dblink);
            if (in_array($table, ['company', 'company_contact'])
                && !empty($row['tags'])
            ) {
                $type = $table === 'company' ? 'company' : 'contact';
                saveTags($type, $addedRecordId, $row['tags']);
            }
        } else {
            $addedRecordId = 'x';
        }
        $result = "Add as new (ID $addedRecordId) into table $table";
        return $result;
    }

    /**
     * Import child records
     *
     * @param string $parentTable      Table name
     * @param int    $parentId         Parent record ID
     * @param array  $childRecords     Child records to import
     * @param string $duplicateMode    Duplicate handling mode ('ignore' or 'update')
     * @param string $importMode       Mode ('preview' or 'import')
     * @param string $decimalSeparator Decimal separator
     * @param array  $fieldDefs        Field definitions
     *
     * @return void
     */
    protected function processChildRecords($parentTable, $parentId, $childRecords,
        $duplicateMode, $importMode, $decimalSeparator, &$fieldDefs
    ) {
        switch ($parentTable) {
        case 'invoice' :
            $childTable = 'invoice_row';
            break;
        case 'company' :
            $childTable = 'company_contact';
            break;
        default :
            die('Unsupported child table');
        }
        $childNum = 0;
        foreach ($childRecords as $childColumns) {
            ++$childNum;
            $childColumns["${parentTable}_id"] = $parentId;

            if (!isset($fieldDefs[$childTable])) {
                $fieldDefs[$childTable] = $this->getFieldDefs($childTable);
            }

            foreach ($childColumns as $column => $value) {
                if (!isset($fieldDefs[$childTable][$column])) {
                    die(
                        "Invalid column name: $childTable." .
                        htmlspecialchars($column)
                    );
                }
            }
            $childDupColumns = [];
            $addedChildRecordId = null;
            $result = $this->processImportRow(
                $childTable, $childColumns, $duplicateMode, $childDupColumns,
                $importMode, $decimalSeparator, $fieldDefs[$childTable],
                $addedChildrecordId
            );
            echo "    &nbsp; Child Record $childNum: $result<br>\n";
        }
    }

    /**
     * Import a file
     *
     * @param $string $importMode Mode ('preview' or 'import')
     *
     * @return void
     */
    protected function importFile($importMode)
    {
        // Try to disable maximum execution time
        set_time_limit(0);

        // Disable output buffering
        ob_end_flush();

        $charset = getRequest('charset', 'UTF-8');
        $table = getRequest('table', '');
        $format = getRequest('format', '');
        $fieldDelimiter = getRequest('field_delim', 'comma');
        $enclosureChar = getRequest('enclosure_char', 'doublequote');
        $rowDelimiter = getRequest('row_delim', 'lf');
        $duplicateMode = getRequest('duplicate_processing', '');
        $duplicateCheckColumns = getRequest('column', []);
        $columnMappings = getRequest('map_column', []);
        $skipRows = getRequest('skip_rows', 0);
        $decimalSeparator = getRequest('decimal_separator', ',');

        if (!$charset || !$format || !$fieldDelimiter || !$enclosureChar
            || !$rowDelimiter
        ) {
            die('Invalid parameters');
        }

        if (!$this->isTableNameValid($table)) {
            die('Invalid table name: ' . htmlspecialchars($table));
        }

        ?>
<div class="form_container">
    <h1><?php echo Translator::translate('ImportResults')?></h1>
        <?php

        if ($importMode != 'import') {
            echo '<p>' . Translator::translate('ImportSimulation') . "</p>\n";
        }

        $fieldDefs[$table] = $this->getFieldDefs($table);
        // Add type_id for company so that it's handled properly even though it's not
        // currently used.
        if ($table == 'company') {
            $fieldDefs[] = [
                'name' => 'type_id',
                'type' => 'INT',
                'style' => 'short'
            ];
        }

        foreach ($duplicateCheckColumns as $key => $column) {
            if (empty($column)) {
                unset($duplicateCheckColumns[$key]);
            } elseif (!isset($fieldDefs[$table][$column])) {
                die(
                    'Invalid duplicate check column name: '
                    . htmlspecialchars($column)
                );
            }
        }

        if ($this->requireDuplicateCheck && empty($duplicateCheckColumns)) {
            die('At least one duplicate check column is required');
        }

        if ($format == 'csv') {
            $fp = fopen($_SESSION['import_file'], 'r');
            if (!$fp) {
                die('Could not open import file for reading');
            }

            foreach ($columnMappings as $key => $column) {
                if ($column && !isset($fieldDefs[$table][$column])) {
                    die('Invalid column name: ' . htmlspecialchars($column));
                }
            }

            $field_delims = $this->getFieldDelims();
            $enclosure_chars = $this->getEnclosureChars();
            $row_delims = $this->getRowDelims();

            if (!isset($field_delims[$fieldDelimiter])) {
                die('Invalid field delimiter');
            }
            $fieldDelimiter = $field_delims[$fieldDelimiter]['char'];
            if (!isset($enclosure_chars[$enclosureChar])) {
                die('Invalid enclosure character');
            }
            $enclosureChar = $enclosure_chars[$enclosureChar]['char'];
            if (!isset($row_delims[$rowDelimiter])) {
                die('Invalid field delimiter');
            }
            $rowDelimiter = $row_delims[$rowDelimiter]['char'];

            // Force enclosure char, otherwise fgetcsv would balk.
            if ($enclosureChar == '') {
                $enclosureChar = "\x01";
            }

            $rowNum = 1;
            for ($i = 0; $i < $skipRows; $i ++) {
                $this->getCsv(
                    $fp, $fieldDelimiter, $enclosureChar, $charset, $rowDelimiter
                );
                ++$rowNum;
            }

            $errors = [];
            $headings = $this->getCsv(
                $fp, $fieldDelimiter, $enclosureChar, $charset, $rowDelimiter
            );
            if ('import' === $importMode) {
                dbQueryCheck('BEGIN');
            }
            while (!feof($fp)) {
                $row = $this->getCsv(
                    $fp, $fieldDelimiter, $enclosureChar, $charset, $rowDelimiter
                );
                if (empty($row)) {
                    break;
                }

                ++$rowNum;
                if ('import' === $importMode && $rowNum % 5000 == 0) {
                    dbQueryCheck('COMMIT');
                    dbQueryCheck('BEGIN');
                }
                $mapped_row = [];
                $haveMappings = false;
                for ($i = 0; $i < count($row); $i++) {
                    if ($columnMappings[$i]) {
                        $haveMappings = true;
                        $mapped_row[$columnMappings[$i]] = $row[$i];
                    }
                }
                if (!$haveMappings) {
                    if (!$this->ignoreEmptyRows) {
                        echo "    Row $rowNum: " .
                             Translator::translate('ImportNoMappedColumns') . "<br>\n";
                    }
                } else {
                    $addedRecordId = null;
                    $result = $this->processImportRow(
                        $table, $mapped_row, $duplicateMode, $duplicateCheckColumns,
                        $importMode, $decimalSeparator, $fieldDefs[$table],
                        $addedRecordId
                    );
                    if ($result) {
                        echo Translator::translate('ImportRow') . " $rowNum: " .
                             htmlspecialchars($result) . "<br>\n";
                    }
                }
            }
            if ('import' === $importMode) {
                dbQueryCheck('COMMIT');
            }
            fclose($fp);
            if ($_SESSION['import_file'] != _IMPORT_FILE_
                && $importMode == 'import'
            ) {
                unlink($_SESSION['import_file']);
            }
        } elseif ($format == 'xml') {
            $data = file_get_contents($_SESSION['import_file']);
            if ($charset != _CHARSET_) {
                $data = iconv($charset, _CHARSET_, $data);
            }

            try {
                $xml = new SimpleXMLElement($data);
            } catch (Exception $e) {
                die('XML parsing failed: ' . htmlspecialchars($e->getMessage()));
            }
            $this->importXml(
                $xml, $table, $fieldDefs, $columnMappings, $duplicateMode,
                $duplicateCheckColumns, $importMode, $decimalSeparator, $errors
            );
        } elseif ($format == 'json') {
            $data = file_get_contents($_SESSION['import_file']);
            if ($data === false) {
                echo json_encode(
                    [
                        'errors' => [
                            'Could not open import file for reading'
                        ]
                    ]
                );
                error_log(
                    "Could not open import file '" + $_SESSION['import_file']
                         + "' for reading"
                );
                exit();
            }

            if ($charset != _CHARSET_) {
                $data = iconv($charset, _CHARSET_, $data);
            }

            $data = json_decode($data, true);
            if ($data === null) {
                echo json_encode(
                    [
                        'errors' => [
                            'Could not decode JSON'
                        ]
                    ]
                );
                error_log('JSON parsing failed');
                exit();
            }
            $recNum = 0;
            $headings = [];
            $rows = [];

            if ('import' === $importMode) {
                dbQueryCheck('BEGIN');
            }
            foreach (reset($data) as $record) {
                $childRecords = [];
                $mapped_row = [];
                foreach ($record as $column => $value) {
                    if (is_array($value)) {
                        foreach ($value as $subRecord) {
                            $childRecords[] = $subRecord;
                        }
                    } elseif (is_object($value)) {
                        $childRecords[] = get_object_vars($value);
                    } else {
                        if (!isset($fieldDefs[$table][$column])) {
                            die(
                                "Invalid column name: $table." .
                                     htmlspecialchars($column)
                            );
                        }
                        $mapped_row[$column] = $value;
                    }
                }

                ++$recNum;
                if ('import' === $importMode && $recNum % 5000 == 0) {
                    dbQueryCheck('COMMIT');
                    dbQueryCheck('BEGIN');
                }

                $addedRecordId = null;
                $result = $this->processImportRow(
                    $table, $mapped_row, $duplicateMode, $duplicateCheckColumns,
                    $importMode, $decimalSeparator, $fieldDefs[$table],
                    $addedRecordId
                );
                if ($result) {
                    echo "    Record $recNum: $result<br>\n";
                }
                // Updating not feasible || $duplicateMode == 'update')
                if (isset($addedRecordId)) {
                    $this->processChildRecords(
                        $table, $addedRecordId, $childRecords, $duplicateMode,
                        $importMode, $decimalSeparator, $fieldDefs
                    );
                }
            }
            if ('import' === $importMode) {
                dbQueryCheck('COMMIT');
            }
        } elseif ($format == 'fixed') {
            $data = file_get_contents($_SESSION['import_file']);

            if ($charset != _CHARSET_) {
                $data = iconv($charset, _CHARSET_, $data);
            }

            $rowNum = 0;

            if ('import' === $importMode) {
                dbQueryCheck('BEGIN');
            }
            foreach (explode("\n", $data) as $line) {
                $line = trim($line, "\r");
                $pos = 0;
                $row = [];
                foreach ($this->fixedWidthSettings as $column) {
                    $value = substr($line, $pos, $column['len']);
                    if (!empty($column['filter'])
                        && !in_array($value, $column['filter'])
                    ) {
                        // Ignore line
                        continue 2;
                    }
                    $row[] = $value;
                    $pos += $column['len'];
                }

                ++$rowNum;
                if ('import' === $importMode && $rowNum % 5000 == 0) {
                    dbQueryCheck('COMMIT');
                    dbQueryCheck('BEGIN');
                }

                $mapped_row = [];
                $haveMappings = false;
                for ($i = 0; $i < count($row); $i ++) {
                    if ($columnMappings[$i]) {
                        $haveMappings = true;
                        $mapped_row[$columnMappings[$i]] = $row[$i];
                    }
                }
                if (!$haveMappings) {
                    if (!$this->ignoreEmptyRows) {
                        echo "    Row $rowNum: " .
                             Translator::translate('ImportNoMappedColumns') . "<br>\n";
                    }
                } else {
                    $addedRecordId = null;
                    $result = $this->processImportRow(
                        $table, $mapped_row, $duplicateMode, $duplicateCheckColumns,
                        $importMode, $decimalSeparator, $fieldDefs[$table],
                        $addedRecordId
                    );
                    if ($result) {
                        echo Translator::translate('ImportRow') . " $rowNum: " .
                             htmlspecialchars($result) . "<br>\n";
                    }
                }
            }
            if ('import' === $importMode) {
                dbQueryCheck('COMMIT');
            }
        }

        if ('import' === $importMode) {
            echo '    ' . Translator::translate('ImportDone') . "\n";
        } else {
            echo '    ' . Translator::translate('ImportSimulationDone') . "\n";
        }
        ?>
    </div>
<?php
    }

    /**
     * Import XML
     *
     * @param SimpleXMLElement $xml                   XML
     * @param string           $table                 Table name
     * @param array            $fieldDefs             Field definitions
     * @param array            $columnMappings        Column mappings
     * @param string           $duplicateMode         Duplicate handling mode
     *                                                ('ignore' or 'update')
     * @param array            $duplicateCheckColumns Columns to use for duplicate
     *                                                check
     * @param string           $importMode            Mode ('preview' or 'import')
     * @param string           $decimalSeparator      Decimal separator
     * @param array            $errors                Any errors
     *
     * @return void
     */
    protected function importXml($xml, $table, $fieldDefs, $columnMappings,
        $duplicateMode, $duplicateCheckColumns, $importMode, $decimalSeparator,
        &$errors
    ) {
        $errors = [];
        $recNum = 0;
        if ('import' === $importMode) {
            dbQueryCheck('BEGIN');
        }
        foreach ($xml as $record) {
            $record = get_object_vars($record);

            $childRecords = [];
            $mapped_row = [];
            foreach ($record as $column => $value) {
                if (count($value) > 1) {
                    foreach ($value as $subRecord) {
                        $childRecords[] = get_object_vars($subRecord);
                    }
                } else {
                    if (!isset($fieldDefs[$table][$column])) {
                        die(
                            "Invalid column name: $table."
                            . htmlspecialchars($column)
                        );
                    }
                    $mapped_row[$column] = (string)$value;
                }
            }

            ++$recNum;
            if ('import' === $importMode && $recNum % 5000 == 0) {
                dbQueryCheck('COMMIT');
                dbQueryCheck('BEGIN');
            }
            $addedRecordId = null;
            $result = $this->processImportRow(
                $table, $mapped_row, $duplicateMode, $duplicateCheckColumns,
                $importMode, $decimalSeparator, $fieldDefs[$table], $addedRecordId
            );
            if ($result) {
                echo "    Record $recNum: $result<br>\n";
            }
            // Updating not feasible || $duplicateMode == 'update')
            if (isset($addedRecordId)) {
                $this->processChildRecords(
                    $table, $addedRecordId, $childRecords, $duplicateMode,
                    $importMode, $decimalSeparator, $fieldDefs
                );
            }
        }
        if ('import' === $importMode) {
            dbQueryCheck('COMMIT');
        }
    }

    /**
     * Check if the table name is valid
     *
     * @param string $table Table name
     *
     * @return bool
     */
    protected function isTableNameValid($table)
    {
        return tableNameValid($table);
    }

    /**
     * Return the count of field delimiters in the given data piece
     *
     * @param string $data Data
     *
     * @return int
     */
    protected function getDelimiterCount($data)
    {
        $count = 0;
        foreach ($this->getFieldDelims() as $key => $value) {
            $count += substr_count($data, $value['char']);
        }
        return $count;
    }

    /**
     * Try to convert a string using iconv
     *
     * @param string $from From charset
     * @param string $to   To charset
     * @param string $str  String to convert
     *
     * @return string
     */
    protected function tryIconv($from, $to, $str)
    {
        set_error_handler(
            function ($errno, $errstr, $errfile, $errline) {
                throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
            }
        );
        try {
            $str = iconv($from, $to, $str);
        } catch (ErrorException $e) {
            restore_error_handler();
            return false;
        }
        restore_error_handler();
        return $str;
    }
}
