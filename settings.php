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
define ("_PAGE_TITLE_", "VLLasku");

// http vai https - vaihda vain jos automaattinen valinta alla ei toimi
define ('_PROTOCOL_', isset($_SERVER['HTTPS']) ? 'https://' : 'http://');
//define ("_PROTOCOL_", "http://");

// Sekalaisia muuttujia:

// Näytetäänkö viivakoodi
$showBarcode = TRUE; // TRUE = näytetään tai FALSE = ei näytetä

// Näytetäänkö laskurivillä päivämäärä (pdf)
$showInvoiceRowDate = TRUE; // TRUE = näytetään tai FALSE = ei näytetä

// Vasemman valikon rivimäärä
$leftNaviListRows = 40;

// PDF-laskupohjan rivimäärä - kun ylittyy niin laskurivit tulostuvat erilliseen laskuerittelyyn.
// Normaalisti ei tarvitse muuttaa - erittely tehdään, jos rivit eivät mahdu niille varattuun tilaan.
$invoicePdfRows = 99;

// Maksupvm lasketaan näin monta päivää tulevaisuuteen
$paymentDueDate = 14;

// Asetetaanko laskun numero automaattisesti uutta laskua tehtäessä
$addInvoiceNumber = TRUE; // TRUE = kyllä tai FALSE = ei

// Luodaanko viitenumero automaattisesti laskun numerosta uutta laskua tehtäessä
$addReferenceNumber = TRUE; // TRUE = kyllä tai FALSE = ei

// Oletus-alv
$defaultVAT = 23;

// Maksuehdot
$termsOfPayment = '14 pv netto';

// Huomautusaika
$periodForComplaints = '7 päivää';

// Viivästyskorko
$penaltyInterest = '8 %';
// Viivästyskorko numerona
$penaltyInterestPercent = 8;

// Huomautusmaksu
$notificationFee = 5;
