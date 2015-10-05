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
function initInvoicePDFLocalizations($language)
{
    if (!isset($language)) {
        $language = 'fi-FI';
    } elseif ($language == 'en') {
        $language = 'en-US';
    } elseif ($language == 'fi') {
        $language = 'fi-FI';
    }
    if (!file_exists("lang/invoice_$language.ini")) {
        $language = 'fi-FI';
    }
    
    $languageStrings = parse_ini_file("lang/invoice_$language.ini");
    if (file_exists("lang/invoice_$language.local.ini")) {
        $languageStrings = array_merge($languageStrings, 
            parse_ini_file("lang/invoice_$language.local.ini"));
    }
    foreach ($languageStrings as $key => $value) {
        $GLOBALS["locPDF$key"] = $value;
    }
    
    if (_CHARSET_ != 'UTF-8') {
        foreach ($GLOBALS as $key => &$tr) {
            if (substr($key, 0, 3) == 'locPDF' && is_string($tr)) {
                $tr = utf8_decode($tr);
            }
        }
    }
}
