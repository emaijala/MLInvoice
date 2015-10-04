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
require_once 'config.php';
require_once 'localize.php';

mb_internal_encoding(_CHARSET_);

function getSetting($name)
{
    // The cache only lives for a single request to speed up repeated requests for a setting
    static $settingsCache = [];
    if (isset($settingsCache[$name])) {
        return $settingsCache[$name];
    }
    
    require 'settings_def.php';
    
    if (isset($arrSettings[$name]) && isset($arrSettings[$name]['session']) &&
         $arrSettings[$name]['session']) {
        if (isset($_SESSION[$name]))
            return $_SESSION[$name];
    } else {
        $res = mysqli_param_query('SELECT value from {prefix}settings WHERE name=?', 
            [
                $name
            ]);
        if ($row = mysqli_fetch_assoc($res)) {
            $settingsCache[$name] = $row['value'];
            return $settingsCache[$name];
        }
    }
    $settingsCache[$name] = isset($arrSettings[$name]) &&
         isset($arrSettings[$name]['default']) ? cond_utf8_decode(
            $arrSettings[$name]['default']) : '';
    return $settingsCache[$name];
}
