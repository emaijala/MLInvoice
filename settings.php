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
define ('_SHOW_BARCODE_', TRUE); // TRUE = näytetään tai FALSE = ei näytetä

// Näytetäänkö laskurivillä päivämäärä (pdf)
define ('_SHOW_INVOICE_ROW_DATE_', TRUE); // TRUE = näytetään tai FALSE = ei näytetä

// listojen rivimäärä
define ('_NAVI_LIST_ROWS_', 0); 

// PDF-laskupohjan rivimäärä - kun ylittyy niin laskurivit tulostuvat erilliseen laskuerittelyyn.
// Normaalisti ei tarvitse muuttaa - erittely tehdään, jos rivit eivät mahdu niille varattuun tilaan.
define ('_INVOICE_PDF_ROWS_', 99); 

// Maksupvm lasketaan näin monta päivää tulevaisuuteen
define ('_PAYMENT_DAYS_', 14); 

// Asetetaanko laskun numero automaattisesti uutta laskua tehtäessä
define ('_ADD_INVOICE_NUMBER_', TRUE); // TRUE = kyllä tai FALSE = ei

// Luodaanko viitenumero automaattisesti laskun numerosta uutta laskua tehtäessä
define ('_ADD_REFERENCE_NUMBER_', TRUE); // TRUE = kyllä tai FALSE = ei

// Oletus-alv
define ('_DEFAULT_VAT_', 23);

// Maksuehdot
define ('_TERMS_OF_PAYMENT_', '14 pv netto'); 

// Huomautusaika
define ('_PERIOD_FOR_COMPLAINTS_', '7 päivää'); 

// Viivästyskorko
define ('_PENALTY_INTEREST_', '8 %');
// Viivästyskorko numerona
define ('_PENALTY_INTEREST_PERCENT_', 8);

// Huomautusmaksu
define ('_NOTIFICATION_FEE_', 5); 
