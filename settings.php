<?php
/*******************************************************************************
VLLasku: web-based invoicing application.
Copyright (C) 2010 Ere Maijala

This program is free software. See attached LICENSE.

*******************************************************************************/

/*******************************************************************************
VLLasku: web-pohjainen laskutusohjelma.
Copyright (C) 2010 Ere Maijala

Tämä ohjelma on vapaa. Lue oheinen LICENSE.

*******************************************************************************/

// Tietokantapalvelimen osoite
define('_DB_SERVER_', 'localhost');

// Tunnus tietokantapalvelimelle
define('_DB_USERNAME_', 'vllasku');

// Salasana tietokantapalvelimelle
define('_DB_PASSWORD_', 'vllasku');

// Tietokannan nimi
define('_DB_NAME_', 'vllasku');

// Tietokantataulujen prefix
define ('_DB_PREFIX_', 'vllasku');

// Merkistö: UTF-8 tai ISO-8859-15
define ('_CHARSET_', 'UTF-8');

// Sivujen otsikko
define ("_PAGE_TITLE_", "VLLasku");

// http vai https - vaihda vain jos automaattinen valinta alla ei toimi
define ('_PROTOCOL_', isset($_SERVER['HTTPS']) ? 'https://' : 'http://');
//define ("_PROTOCOL_", "http://");

// HUOM! Asetukset löytyvät nyt käyttöliittymästä kohdasta Asetukset - Yleiset asetukset

function getSetting($name)
{
  require 'settings_def.php';
  
  $res = mysql_param_query('SELECT value from {prefix}settings WHERE name=?', array($name));
  if ($row = mysql_fetch_assoc($res))
    $value = $row['value'];
  else
    $value = isset($arrSettings[$name]['default']) ? cond_utf8_encode($arrSettings[$name]['default']) : '';
  return $value;
}
