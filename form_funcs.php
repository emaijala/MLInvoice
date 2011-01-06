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

require_once 'sqlfuncs.php';
require_once 'datefuncs.php';
require_once 'miscfuncs.php';

// Get post values or defaults for unspecified values
function getPostValues(&$formElements, $primaryKey, $parentKey = FALSE)
{
  $values = array();

  foreach ($formElements as $elem) 
  {
    if (in_array($elem['type'], array('', 'IFORM', 'RESULT', 'BUTTON', 'JSBUTTON', 'IMAGE', 'ROWSUM', 'NEWLINE'))) 
    {
      $values[$elem['name']] = isset($primaryKey) ? $primaryKey : FALSE;
    }
    else 
    {
      $values[$elem['name']] = getPostRequest($elem['name'], FALSE);
      if ($elem['default'] && ($values[$elem['name']] === FALSE || ($elem['type'] == 'INT' && $values[$elem['name']] === ''))) 
      {
        if ($elem['default'] == 'DATE_NOW') 
        {
          $values[$elem['name']] = date('d.m.Y');
        }
        elseif (strstr($elem['default'], 'DATE_NOW+')) 
        {
          $atmpValues = explode('+', $elem['default']);
          $values[$elem['name']] = date('d.m.Y', mktime(0, 0, 0, date('m'), date('d') + $atmpValues[1], date('Y')));
        }            
        elseif ($elem['default'] == 'TIME_NOW') 
        {
          $values[$elem['name']] = date('H:i');
        }
        elseif (strstr($elem['default'], 'ADD')) 
        {
          $strQuery = str_replace("_PARENTID_", $parentKey, $elem['listquery']);
          $intRes = mysql_query_check($strQuery);
          $intAdd = reset(mysql_fetch_row($intRes));
          $values[$elem['name']] = isset($intAdd) ? $intAdd : 0;
        }
        elseif ($elem['default'] == 'POST')
        {
          // POST has special treatment in iform
          $values[$elem['name']] = '';
        }
        else 
        {
          $values[$elem['name']] = $elem['default'];
        }
      }
      elseif ($elem['type'] == 'INT')
      {
        $values[$elem['name']] = str_replace(",", ".", $values[$elem['name']]);
      }
      elseif ($elem['type'] == 'LIST' && $values[$elem['name']] === FALSE)
      {
        $values[$elem['name']] = '';
      }
    }
  }
  return $values;
}

// Save form data. If primaryKey is not set, add a new record and set it, otherwise update existing record.
// Return a string of missing values of encountered. In that case, the record was not saved. Otherwise return true.
function saveFormData($table, &$primaryKey, &$formElements, &$values, $parentKeyName = '', $parentKey = FALSE)
{  
  $missingValues = '';
  $strFields = '';
  $strInsert = '';
  $strUpdateFields = '';
  $arrValues = array();
  
  if (!isset($primaryKey) || !$primaryKey)
    unset($values['id']);
  
  foreach ($formElements as $elem) 
  {
    $type = $elem['type'];
    $name = $elem['name'];
    $value = $values[$name];
            
    if (in_array($type, array('', 'IFORM', 'RESULT', 'BUTTON', 'JSBUTTON', 'IMAGE', 'ROWSUM', 'NEWLINE')))
      continue;
  
    if (!$elem['allow_null'] && (!isset($value) || $value === ''))
    {
      if ($missingValues)
        $missingValues .= ', ';
      $missingValues .= $elem['label'];
      continue;
    }
    if ($strFields)
    {
      $strFields .= ', ';
      $strInsert .= ', ';
      $strUpdateFields .= ', ';
    }
    $strFields .= $name;
    $fieldPlaceholder = '?';
    switch ($type)
    {
    case 'PASSWD':
      $fieldPlaceholder = 'md5(?)';
      $arrValues[] = $value;
      break;
    case 'INT':
    case 'HID_INT':
    case 'SECHID_INT':
      $arrValues[] = $value !== '' ? str_replace(",", ".", $value) : 0;
      break;
    case 'LIST':
      $arrValues[] = $value !== '' ? str_replace(",", ".", $value) : NULL;
      break;
    case 'CHECK':
      $arrValues[] = $value ? 1 : 0;
      break;
    case 'INTDATE':
      $arrValues[] = $value ? dateConvDate2IntDate($value) : NULL;
      break;
    default: 
      $arrValues[] = $value;
    }
    $strInsert .= $fieldPlaceholder;
    $strUpdateFields .= "$name=$fieldPlaceholder";
  }

  if ($missingValues)
    return $missingValues;

  if (!isset($primaryKey) || !$primaryKey) 
  {
    if ($parentKeyName)
    {
      $strFields .= ", $parentKeyName";
      $strInsert .= ', ?';
      $arrValues[] = $parentKey;
    }
    $strQuery = "INSERT INTO $table ($strFields) VALUES ($strInsert)";
    mysql_param_query($strQuery, $arrValues);
    $primaryKey = mysql_insert_id();   
  }
  else 
  {
    $strQuery = "UPDATE $table SET $strUpdateFields, deleted=0 WHERE id=?";
    $arrValues[] = $primaryKey;
    mysql_param_query($strQuery, $arrValues);
  }
  return TRUE;
}

// Fetch a record. Values in $values, may modify $formElements.
// Returns TRUE on success, 'deleted' for deleted records and 'notfound' if record is not found.
function fetchRecord($table, $primaryKey, &$formElements, &$values)
{
  $result = TRUE;
  $strQuery = "SELECT * FROM $table WHERE id=?";
  $intRes = mysql_param_query($strQuery, array($primaryKey));
  $row = mysql_fetch_assoc($intRes);
  if (!$row)
    return 'notfound';
  
  if ($row['deleted'])
    $result = 'deleted';

  foreach ($formElements as $elem)
  {
    $type = $elem['type'];
    $name = $elem['name'];

    if (!$type || $type == 'LABEL')
      continue;

    switch ($type)
    {
    case 'IFORM':
    case 'RESULT':
      $values[$name] = $primaryKey;
      break;
    case 'BUTTON':
    case 'JSBUTTON':
    case 'IMAGE':
      if (strstr($elem['listquery'], "=_ID_")) 
      {
        $values[$name] = $primaryKey;
      }
      else 
      {
        $tmpListQuery = $elem['listquery'];
        $strReplName = substr($tmpListQuery, strpos($tmpListQuery, "_"));
        $strReplName = strtolower(substr($strReplName, 1, strrpos($strReplName, "_")-1));
        $values[$name] = isset($values[$strReplName]) ? $values[$strReplName] : '';
        $elem['listquery'] = str_replace(strtoupper($strReplName), "ID", $elem['listquery']);
      }
      break;
    case 'INTDATE':
      $values[$name] = dateConvIntDate2Date($row[$name]);
      break;
    default:
      $values[$name] = $row[$name];
    }
  }
  return $result;
}
