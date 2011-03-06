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

require_once 'config.php';

mb_internal_encoding(_CHARSET_);

function getSetting($name)
{
  require_once 'localize.php';
  require 'settings_def.php';
  
  if (isset($arrSettings[$name]) && isset($arrSettings[$name]['session']) && $arrSettings[$name]['session'])
  {
    if (isset($_SESSION[$name]))
      return $_SESSION[$name];
  }
  else
  {
    $res = mysql_param_query('SELECT value from {prefix}settings WHERE name=?', array($name));
    if ($row = mysql_fetch_assoc($res))
      return $row['value'];
  }
  return isset($arrSettings[$name]) && isset($arrSettings[$name]['default']) ? cond_utf8_decode($arrSettings[$name]['default']) : '';
}
