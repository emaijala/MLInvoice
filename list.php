<?php
/*******************************************************************************
MLInvoice: web-based invoicing application.
Copyright (C) 2010-2015 Ere Maijala

Portions based on:
PkLasku : web-based invoicing software.
Copyright (C) 2004-2008 Samu Reinikainen

This program is free software. See attached LICENSE.

*******************************************************************************/

/*******************************************************************************
MLInvoice: web-pohjainen laskutusohjelma.
Copyright (C) 2010-2015 Ere Maijala

Perustuu osittain sovellukseen:
PkLasku : web-pohjainen laskutusohjelmisto.
Copyright (C) 2004-2008 Samu Reinikainen

Tämä ohjelma on vapaa. Lue oheinen LICENSE.

*******************************************************************************/

require_once "sqlfuncs.php";
require_once "miscfuncs.php";
require_once "datefuncs.php";
require_once "localize.php";

function createList($strFunc, $strList, $strTableName = '', $strTitleOverride = '', $prefilter = '', $invoiceTotal = false, $highlightOverdue = false)
{
  $strWhereClause = $prefilter ? $prefilter : getRequest('where', '');

  require "list_switch.php";

  if (!$strList) {
    $strList = $strFunc;
  }

  if (!$strTable) {
    return;
  }

  if ($strListFilter) {
    if ($strWhereClause) {
      $strWhereClause .= " AND $strListFilter";
    } else {
      $strWhereClause = $strListFilter;
    }
  }

  if (!$strTableName) {
    $strTableName = "list_$strList";
  }

  if ($strTitleOverride) {
    $strTitle = $strTitleOverride;
  } else {
    $strTitle = '';
  }

  $params = array(
    'listfunc' => $strFunc,
    'table' => $strList
  );
  if ($strWhereClause) {
    $params['where'] = $strWhereClause;
  }
  if ($highlightOverdue) {
    $params['highlight_overdue'] = 1;
  }

  $params = http_build_query($params);
?>
  <script type="text/javascript">

  $(document).ready(function() {
    $('#<?php echo $strTableName?>').dataTable( {
      "oLanguage": {
        <?php echo $GLOBALS['locTableTexts']?>
      },
      "bStateSave": true,
      "bJQueryUI": true,
      "iDisplayLength": <?php echo getSetting('default_list_rows')?>,
      "sPaginationType": "full_numbers",
      "aoColumnDefs": [
<?php
  foreach ($astrShowFields as $key => $field)
  {
    $strWidth = isset($field['width']) ? ($field['width'] . 'px') : '';
?>
        { "aTargets": [ <?php echo ($key + 1)?> ], "sWidth": "<?php echo $strWidth?>" },
<?php
  }
?>
        { "aTargets": [ 0 ], "bSearchable": false, "bVisible": false }
      ],
  		"aaSorting": [[ 1, "asc" ]],
      "bProcessing": true,
      "bServerSide": true,
      "sAjaxSource": "json.php?func=get_list<?php echo "&$params"?>"
    });
    $(document).on('click', '#<?php echo $strTableName?> tbody tr', function(e) {
      var data = $('#<?php echo $strTableName?>').dataTable().fnGetData(this);
      document.location.href = data[0];
    });
<?php
  if ($invoiceTotal) {
?>
    $.ajax({
      url: "json.php?func=get_invoice_total_sum<?php echo "&$params"?>"
    }).done(function(data) {
      $('#<?php echo $strTableName?>_title').append(' ' + data['sum_str']);
    });
<?php
  }
?>
  });
  </script>

  <div class="list_container">
    <div id="<?php echo $strTableName?>_title" class="table_header"><?php echo $strTitle?></div>
    <table id="<?php echo $strTableName?>" class="list">
      <thead>
        <tr>
          <th>Link</th>
<?php
  foreach ($astrShowFields as $field)
  {
    $strWidth = isset($field['width']) ? (' style="width: ' . $field['width'] . 'px"') : '';
?>
          <th<?php echo $strWidth?>><?php echo $field['header']?></th>
<?php
  }
?>
        </tr>
      </thead>
      <tbody>
      </tbody>
    </table>
    <br>
  </div>
  <?php
}

function createJSONList($strFunc, $strList, $startRow, $rowCount, $sort, $filter, $where, $requestId)
{
  require "list_switch.php";

  global $dblink;

  if (!sesAccessLevel($levelsAllowed) && !sesAdminAccess())
  {
?>
  <div class="form_container ui-widget-content">
    <?php echo $GLOBALS['locNoAccess'] . "\n"?>
  </div>
<?php
    return;
  }

  if (!$strTable)
    return;

  $strWhereClause = '';
  $joinOp = 'WHERE';
  $arrQueryParams = array();
  if ($where) {
    // Validate and build query parameters
    $boolean = '';
    while (extractSearchTerm($where, $field, $operator, $term, $nextBool))
    {
      if (strcasecmp($operator, 'IN') === 0) {
        $strWhereClause .= "$boolean$field $operator " . mysqli_real_escape_string($dblink, $term);
      } else {
        $strWhereClause .= "$boolean$field $operator ?";
        $arrQueryParams[] = str_replace("%-", "%", $term);
      }
      if (!$nextBool)
        break;
      $boolean = " $nextBool";
    }
    if ($strWhereClause) {
      $strWhereClause = "WHERE ($strWhereClause)";
      $joinOp = ' AND';
    }
  }

  if ($filter) {
    $strWhereClause .= "$joinOp (" . createWhereClause($astrSearchFields, $filter, $arrQueryParams) . ')';
    $joinOp = ' AND';
  }

  if (!getSetting('show_deleted_records')) {
    $strWhereClause .= "$joinOp $strDeletedField=0";
    $joinOp = ' AND';
  }

  if ($strGroupBy) {
    $strGroupBy = " GROUP BY $strGroupBy";
  }

  if (!isset($strCountJoin)) {
    $strCountJoin = $strJoin;
  }

  // Total count
  $fullQuery = "SELECT COUNT(*) AS cnt FROM $strTable $strCountJoin $strWhereClause";
  $res = mysqli_param_query($fullQuery, $arrQueryParams);
  $row = mysqli_fetch_assoc($res);
  $totalCount = $filteredCount = $row['cnt'];

  // Add Filter
  if ($filter) {
    $strWhereClause .= "$joinOp " . createWhereClause($astrSearchFields, $filter, $arrQueryParams);

    // Filtered count
    $fullQuery = "SELECT COUNT(*) as cnt FROM $strTable $strCountJoin $strWhereClause";
    $res = mysqli_param_query($fullQuery, $arrQueryParams);
    $row = mysqli_fetch_assoc($res);
    $filteredCount = $row['cnt'];
  }

  // Add sort options
  $orderBy = array();
  foreach ($sort as $sortField) {
    // Ignore invisible first column
    $column = key($sortField) - 1;
    if (isset($astrShowFields[$column])) {
      $fieldName = $astrShowFields[$column]['name'];
      $direction = current($sortField) === 'desc' ? 'DESC' : 'ASC';
      if (substr($fieldName, 0, 1) == '.') {
        $fieldName = substr($fieldName, 1);
      }
      // Special case for natural ordering of invoice number and reference number
      if (in_array($fieldName, array('i.invoice_no', 'i.ref_number'))) {
        $orderBy[] = "LENGTH($fieldName) $direction";
      }
      $orderBy[] = "$fieldName $direction";
    }
  }

  // Build the final select clause
  $strSelectClause = "$strPrimaryKey, $strDeletedField";
  foreach ($astrShowFields as $field) {
    $strSelectClause .= ', ' . (isset($field['sql']) ? $field['sql'] : $field['name']);
  }

  $fullQuery = "SELECT $strSelectClause FROM $strTable $strJoin $strWhereClause$strGroupBy";

  if ($orderBy) {
    $fullQuery .= ' ORDER BY ' . implode(', ', $orderBy);
  }

  if ($startRow >= 0 && $rowCount >= 0) {
    $fullQuery .= " LIMIT $startRow, $rowCount";
  }

  $res = mysqli_param_query($fullQuery, $arrQueryParams);

  $astrListValues = array();
  $i = -1;
  while ($row = mysqli_fetch_prefixed_assoc($res)) {
    ++$i;
    $astrPrimaryKeys[$i] = $row[$strPrimaryKey];
    $aboolDeleted[$i] = $row[$strDeletedField];
    foreach ($astrShowFields as $field)
    {
      $name = $field['name'];
      if ($field['type'] == 'TEXT' || $field['type'] == 'INT')
      {
        $value = $row[$name];
        if (isset($field['mappings']) && isset($field['mappings'][$value]))
          $value = $field['mappings'][$value];
        $astrListValues[$i][$name] = $value;
      }
      elseif ($field['type'] == 'CURRENCY')
      {
        $value = $row[$name];
        $value = miscRound2Decim($value, isset($field['decimals']) ? $field['decimals'] : 2);
        $astrListValues[$i][$name] = $value;
      }
      elseif ($field['type'] == 'INTDATE')
      {
        $astrListValues[$i][$name] = dateConvDBDate2Date($row[$name]);
      }
    }
  }

  $records = array();
  $highlight = getRequest('highlight_overdue', false);
  for ($i = 0; $i < count($astrListValues); $i++) {
    $row = $astrListValues[$i];
    $strLink = "?func=$strFunc&list=$strList&form=$strMainForm&id=" . $astrPrimaryKeys[$i];
    $resultValues = array($strLink);
    $overdue = '';
    foreach ($astrShowFields as $field)
    {
      $name = $field['name'];

      // Special colouring for overdue invoices
      if ($highlight && $name == 'i.due_date') {
        $rowDue = strDate2UnixTime($row['i.due_date']);
        if ($rowDue < mktime(0, 0, 0, date("m"), date("d") - 14, date("Y"))) {
          $overdue = ' overdue14';
        } elseif ($rowDue < mktime(0, 0, 0, date("m"), date("d") - 7, date("Y"))) {
          $overdue = ' overdue7';
        } elseif ($rowDue < mktime(0, 0, 0, date("m"), date("d"), date("Y"))) {
          $overdue = ' overdue';
        }
      }

      if (isset($field['translate']) && $field['translate'] && isset($GLOBALS["loc{$row[$name]}"])) {
        $value = $GLOBALS["loc{$row[$name]}"];
      } else {
        $value = trim($row[$name]) ? htmlspecialchars($row[$name]) : '&nbsp;';
      }
      $resultValues[] = $value;
    }
    $deleted = $aboolDeleted[$i] ? ' deleted' : '';
    $class = "$overdue$deleted";
    if ($class) {
      $resultValues['DT_RowClass'] = $class;
    }

    $records[] = $resultValues;
  }

  $results = array(
    'sEcho' => $requestId,
    'iTotalRecords' => $totalCount,
		'iTotalDisplayRecords' => isset($filteredCount) ? $filteredCount : $totalCount,
		'aaData' => $records
  );
  return json_encode($results);
}

function createJSONSelectList($strList, $startRow, $rowCount, $filter, $sort, $id = null)
{
  global $dblink;
  require "list_switch.php";

  if (!sesAccessLevel($levelsAllowed) && !sesAdminAccess())
  {
?>
  <div class="form_container ui-widget-content">
    <?php echo $GLOBALS['locNoAccess'] . "\n"?>
  </div>
<?php
    return;
  }

  if ($sort) {
    if (!preg_match('/^[\w_,]+$/', $sort)) {
      header('HTTP/1.1 400 Bad Request');
      die('Invalid sort type');
    }
    $sortValid = 0;
    $sortFields = explode(',', $sort);
    foreach ($sortFields as $sortField) {
      foreach ($astrShowFields as $field) {
        if ($sortField === $field['name']) {
          ++$sortValid;
          break;
        }
      }
    }
    if ($sortValid != count($sortFields)) {
      header('HTTP/1.1 400 Bad Request');
      die('Invalid sort type');
    }
  } else {
    foreach ($astrShowFields as $field) {
      if ($field['name'] == 'order_no') {
        $sort = 'order_no';
      }
    }
  }

  $arrQueryParams = array();

  $strWhereClause = '';

  if (!getSetting('show_deleted_records') && empty($id))
  {
    $strWhereClause = " WHERE $strDeletedField=0";
  }

  if ($strGroupBy) {
    $strGroupBy = " GROUP BY $strGroupBy";
  }

  // Add Filter
  if ($filter) {
    $strWhereClause .= ($strWhereClause ? ' AND ' : ' WHERE ')
      . createWhereClause($astrSearchFields, $filter, $arrQueryParams, !getSetting('dynamic_select_search_in_middle'));
  }

  if ($id) {
    $strWhereClause .= ($strWhereClause ? ' AND ' : ' WHERE ') . 'id=' . mysqli_real_escape_string($dblink, $id);
  }

  // Build the final select clause
  $strSelectClause = "$strPrimaryKey, $strDeletedField";
  foreach ($astrShowFields as $field) {
    $strSelectClause .= ', ' . (isset($field['sql']) ? $field['sql'] : $field['name']);
  }

  $fullQuery = "SELECT $strSelectClause FROM $strTable $strWhereClause$strGroupBy";
  if ($sort) {
    $fullQuery .= " ORDER BY $sort";
  }

  if ($startRow >= 0 && $rowCount >= 0) {
    $fullQuery .= " LIMIT $startRow, " . ($rowCount + 1);
  }

  $res = mysqli_param_query($fullQuery, $arrQueryParams);

  $astrListValues = array();
  $i = -1;
  $moreAvailable = false;
  while ($row = mysqli_fetch_prefixed_assoc($res)) {
    ++$i;
    if ($startRow >= 0 && $rowCount >= 0 && $i >= $rowCount) {
      $moreAvailable = true;
      break;
    }
    $astrPrimaryKeys[$i] = $row[$strPrimaryKey];
    $aboolDeleted[$i] = $row[$strDeletedField];
    foreach ($astrShowFields as $field)
    {
      $name = $field['name'];
      if ($field['type'] == 'TEXT' || $field['type'] == 'INT')
      {
        $value = $row[$name];
        if (isset($field['mappings']) && isset($field['mappings'][$value]))
          $value = $field['mappings'][$value];
        $astrListValues[$i][$name] = $value;
      }
      elseif ($field['type'] == 'CURRENCY')
      {
        $value = $row[$name];
        $value = miscRound2Decim($value, isset($field['decimals']) ? $field['decimals'] : 2);
        $astrListValues[$i][$name] = $value;
      }
      elseif ($field['type'] == 'INTDATE')
      {
        $astrListValues[$i][$name] = dateConvDBDate2Date($row[$name]);
      }
    }
  }

  $records = array();
  for ($i = 0; $i < count($astrListValues); $i++) {
    $row = $astrListValues[$i];
    $resultValues = array();
    foreach ($astrShowFields as $field)
    {
      if (!isset($field['select']) || !$field['select']) {
        continue;
      }
      $name = $field['name'];

      if (isset($field['translate']) && $field['translate'] && isset($GLOBALS["loc{$row[$name]}"])) {
        $value = $GLOBALS["loc{$row[$name]}"];
      } else {
        $value = htmlspecialchars($row[$name]);
      }
      $resultValues[$name] = $value;
    }

    $records[] = array(
      'id' => $astrPrimaryKeys[$i],
      'text' => implode(' ', $resultValues)
    );
  }

  $results = array(
    'moreAvailable' => $moreAvailable,
    'records' => $records,
    'filter' => $filter
  );
  return json_encode($results);
}
