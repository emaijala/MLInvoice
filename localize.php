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

require_once 'sessionfuncs.php';

if (!session_id()) {
    session_start();
}

$language = isset($_SESSION['sesLANG']) ? $_SESSION['sesLANG'] : (defined(
    '_UI_LANGUAGE_') ? _UI_LANGUAGE_ : 'fi-FI');
if (!file_exists("lang/$language.ini")) {
    $language = 'fi-FI';
}
$languageStrings = parse_ini_file("lang/$language.ini");
if (file_exists("lang/$language.local.ini")) {
    $languageStrings = array_merge($languageStrings,
        parse_ini_file("lang/$language.local.ini"));
}
foreach ($languageStrings as $key => $value) {
    $GLOBALS["loc$key"] = $value;
}

if (_CHARSET_ != 'UTF-8') {
    foreach ($GLOBALS as $key => &$tr) {
        if (substr($key, 0, 3) == 'loc' && is_string($tr)) {
            $tr = utf8_decode($tr);
        }
    }
}
