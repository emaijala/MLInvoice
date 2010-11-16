<?php
/*******************************************************************************
VLLasku: web-based invoicing application.
Copyright (C) 2010 Ere Maijala

Portions based on:
PkLasku : web-based invoicing software.
Copyright (C) 2004-2008 Samu Reinikainen

This program is free software. See attached LICENSE.

*******************************************************************************/

/*******************************************************************************
VLLasku: web-pohjainen laskutusohjelma.
Copyright (C) 2010 Ere Maijala

Perustuu osittain sovellukseen:
PkLasku : web-pohjainen laskutusohjelmisto.
Copyright (C) 2004-2008 Samu Reinikainen

Tämä ohjelma on vapaa. Lue oheinen LICENSE.

*******************************************************************************/

/********************************************************************
Includefile : settings.php
    Basic settings. 
    
********************************************************************/

// Tietokantapalvelimen osoite
define('_DB_SERVER_', 'localhost');

// Tunnus tietokantapalvelimelle
define('_DB_USERNAME_', 'vllasku');

// Salasana tietokantapalvelimelle
define('_DB_PASSWORD_', 'vllasku');

// Tietokannan nimi
define('_DB_NAME_', 'vllasku');

// Tietokantataulujen prefix
define ("_DB_PREFIX_", "vllasku");

// Sivujen otsikko
define ("_PAGE_TITLE_", "VLLasku 1.0");

// http vai https
define ("_PROTOCOL_", "http://");

//sekalaisia muuttujia:

//näytetäänkö viivakoodi
$showBarcode = TRUE; // TRUE = näytetään tai FALSE = ei näytetä

//näytetäänkö laskurivillä päivämäärä (pdf)
$showInvoiceRowDate = TRUE; // TRUE = näytetään tai FALSE = ei näytetä

//vasemman valikon rivimäärä
$leftNaviListRows = 40;

//pdf laskupohjan rivimäärä - kun ylittyy niin laskurivit tulostuvat erillisille sivulle(/sivuille)
$invoicePdfRows = 20;

//maksupvm lasketaan näin monta päivää tulevaisuuteen
$paymentDueDate = 14;

//asetetaanko laskun numero automaattisesti uutta laskua tehtäessä
$addInvoiceNumber = TRUE; // TRUE = kyllä tai FALSE = ei

//luodaanko viitenumero automaattisesti laskun numerosta uutta laskua tehtäessä
$addReferenceNumber = TRUE; // TRUE = kyllä tai FALSE = ei

//oletus-alv
$defaultVAT = 23;