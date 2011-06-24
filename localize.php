<?php
/*******************************************************************************
VLLasku: web-based invoicing application.
Copyright (C) 2010-2011 Ere Maijala

Portions based on:
PkLasku : web-based invoicing software.
Copyright (C) 2004-2008 Samu Reinikainen

This program is free software. See attached LICENSE.

*******************************************************************************/

/*******************************************************************************
VLLasku: web-pohjainen laskutusohjelma.
Copyright (C) 2010-2011 Ere Maijala

Perustuu osittain sovellukseen:
PkLasku : web-pohjainen laskutusohjelmisto.
Copyright (C) 2004-2008 Samu Reinikainen

Tämä ohjelma on vapaa. Lue oheinen LICENSE.

*******************************************************************************/

require_once "settings.php";

switch (isset($_SESSION['sesLANG']) ? $_SESSION['sesLANG'] : 'fi') 
{
case 'en':
  break;

default:
  //LOGIN
  $GLOBALS['locWELCOMEMESSAGE'] = 'Ole hyvä ja syötä tunnuksesi ja salasanasi.';
  $GLOBALS['locINVALIDCREDENTIALS'] = 'Käyttäjätunnus tai salasana väärä.';
  $GLOBALS['locLOGINTIMEOUT'] = 'Kirjautumislomakkeen täyttöaika ylittynyt. Ole hyvä ja kirjaudu uudelleen.';
  $GLOBALS['locMISSINGFIELDS'] = 'Ole hyvä ja syötä kaikki tiedot.';
  $GLOBALS['locLoggingIn'] = 'Kirjaudutaan...';
  $GLOBALS['locWELCOME'] = 'Tervetuloa';
  $GLOBALS['locLICENSENOTIFY'] = '';
  $GLOBALS['locCREDITS'] = '';
  $GLOBALS['locUSERID'] = 'Tunnus';
  $GLOBALS['locPASSWORD'] = 'Salasana';
  $GLOBALS['locLOGIN'] = 'Kirjaudu';
  
  //LOGOUT
  $GLOBALS['locTHANKYOU'] = 'Kiitos';
  $GLOBALS['locSESSIONCLOSED'] = 'Istunto on päätetty.';
  $GLOBALS['locBACKTOLOGIN'] = 'Takaisin kirjautumiseen';
  
  //FORM LABELS
  $GLOBALS['locCOMPNAME'] = 'Asiakkaan nimi';
  $GLOBALS['locCOMPVATID'] = 'Y-tunnus';
  $GLOBALS['locCONTACTPERS'] = 'Kontakti';
  $GLOBALS['locEMAIL'] = 'Email';
  $GLOBALS['locCUSTOMERNO'] = 'Asiakasnro';
  $GLOBALS['locCUSTOMERDEFAULTREFNO'] = 'Asiakaskoht. viitenro';
  $GLOBALS['locVATREGISTERED'] = 'ALV-rekisteröity';
  $GLOBALS['locWWW'] = 'WWW';
  $GLOBALS['locADDRESS'] = 'Osoite';
  $GLOBALS['locSTREETADDR'] = 'Katuosoite';
  $GLOBALS['locHOMECOMMUNE'] = 'Kotikunta';
  $GLOBALS['locZIPCODE'] = 'Postinro';
  $GLOBALS['locCITY'] = 'Postitoimipaikka';
  $GLOBALS['locPOSTALADDR'] = 'Postitoimipaikka';
  $GLOBALS['locPHONE'] = 'Puh.';
  $GLOBALS['locPHONEHOME'] = 'Puh. (koti)';
  $GLOBALS['locPHONEWORK'] = 'Puh. (työ)';
  $GLOBALS['locGSM'] = 'Puh. (matka)';
  $GLOBALS['locBILLADDR'] = 'Laskutusosoite';
  $GLOBALS['locINFO'] = 'Lisätiedot';
  $GLOBALS['locNAMELAST'] = 'Sukunimi';
  $GLOBALS['locNAMEFIRST'] = 'Etunimet';
  $GLOBALS['locPERSONID'] = 'Henk.tunnus';
  $GLOBALS['locINVNAME'] = 'Laskun nimi';
  $GLOBALS['locINVNO'] = 'Laskunro';
  $GLOBALS['locPAYER'] = 'Asiakas';
  $GLOBALS['locCLIENTSREFERENCE'] = 'Asiakkaan viite';
  $GLOBALS['locINVDATE'] = 'Laskupvm';
  $GLOBALS['locDUEDATE'] = 'Eräpvm';
  $GLOBALS['locREFNO'] = 'Viitenro';
  $GLOBALS['locPAYDATE'] = 'Maksupvm';
  $GLOBALS['locSTATUS'] = 'Tila';
  $GLOBALS['locARCHIVED'] = 'Arkistoitu';
  $GLOBALS['locGetInvoiceNo'] = 'Päivitä laskunro ja viitenro';
  $GLOBALS['locUpdateDates'] = 'Päivitä päivämäärät';
  $GLOBALS['locINVROWS'] = 'Laskurivit';
  $GLOBALS['locORDERNO'] = 'Järj.nro';
  $GLOBALS['locROWTYPE'] = 'Rivityyppi';
  $GLOBALS['locSHOWINVOICES'] = 'Näytä laskut';
  $GLOBALS['locDESCRIPTION'] = 'Kuvaus';
  $GLOBALS['locDATE'] = 'Pvm';
  $GLOBALS['locPRICE'] = 'Hinta';
  $GLOBALS['locPCS'] = 'Lkm';
  $GLOBALS['locUNIT'] = 'Yksikkö';
  $GLOBALS['locDiscount'] = 'Ale';
  $GLOBALS['locDiscountPercent'] = 'Alennusprosentti';
  $GLOBALS['locVAT'] = 'Alv';
  $GLOBALS['locSESSIONTYPE'] = 'Istunnon tyyppi';
  $GLOBALS['locTIMEOUT'] = 'Aikaraja';
  $GLOBALS['locLANGS'] = 'Kielet';
  $GLOBALS['locUSERNAME'] = 'Käyttäjä';
  $GLOBALS['locNAME'] = 'Nimi';
  $GLOBALS['locNAMED'] = 'Nimike';
  $GLOBALS['locLANG'] = 'Kieli';
  $GLOBALS['locLOGONNAME'] = 'Tunnus';
  $GLOBALS['locPASSWD'] = 'Salasana';
  $GLOBALS['locTYPE'] = 'Käyttäjätyyppi';
  $GLOBALS['locBANK'] = 'Pankki';
  $GLOBALS['locACCOUNT'] = 'Tilinro';
  $GLOBALS['locACCOUNTIBAN'] = 'IBAN';
  $GLOBALS['locSWIFTBIC'] = 'SWIFT/BIC';
  $GLOBALS['locFIRSTBANK'] = '1. Pankkiyhteys';
  $GLOBALS['locSECONDBANK'] = '2. Pankkiyhteys';
  $GLOBALS['locTHIRDBANK'] = '3. Pankkiyhteys';
  $GLOBALS['locYEAR'] = 'Vuosi';
  $GLOBALS['locOTHERINFO'] = 'Muuta';
  $GLOBALS['locADDINFO'] = 'Lisätiedot';
  $GLOBALS['locCATEGORY'] = 'Kategoria';
  $GLOBALS['locCONTACTS'] = 'Kontaktit';
  $GLOBALS['locCONTACTPERSON'] = 'Kontaktihenkilö';
  $GLOBALS['locPERSONTITLE'] = 'Titteli';
  $GLOBALS['locFAX'] = 'Fax';
  $GLOBALS['locLABELCONTACTINFO'] = 'Yhteystiedot';
  $GLOBALS['locCOMPANY'] = 'Asiakas';
  $GLOBALS['locNOTINUSE'] = 'Poissa käytöstä';
  $GLOBALS['locNOTICE'] = 'Huomautukset';
  $GLOBALS['locPAYMENTTYPE'] = 'Maksutapa';
  $GLOBALS['locBILLER'] = 'Laskuttaja';
  $GLOBALS['locROWNO'] = 'Rivinro';
  $GLOBALS['locCOPYINV'] = 'Kopioi lasku ja laskurivit';
  $GLOBALS['locREFUNDINV'] = 'Mitätöi ja luo hyvityslasku';
  $GLOBALS['locADDREMINDERFEES'] = 'Lisää viivästysmaksut';
  $GLOBALS['locSHOWREFUNDEDINV'] = 'Näytä hyvitetty lasku';
  $GLOBALS['locSHOWREFUNDINGINV'] = 'Näytä hyvittävä lasku';
  $GLOBALS['locPRODUCTNAME'] = 'Tuotteen nimi';
  $GLOBALS['locPRODUCTDESCRIPTION'] = 'Tuotekuvaus';
  $GLOBALS['locPRODUCTCODE'] = 'Tuotekoodi';
  $GLOBALS['locPRODUCTGROUP'] = 'Tuoteryhmä';
  $GLOBALS['locINTERNALINFO'] = 'Lisätiedot (ei<br>näytetä laskussa)';
  $GLOBALS['locVisibleInfo'] = 'Laskussa näytettävät<br>lisätiedot';
  $GLOBALS['locUNITPRICE'] = 'Yksikköhinta';
  $GLOBALS['locVATPERCENT'] = 'ALV %';
  $GLOBALS['locVATINCLUDED'] = 'Hinta sisältää ALV:n';
  $GLOBALS['locVATINC'] = 'Sis.&nbsp;ALV';
  $GLOBALS['locACCESSLEVEL'] = 'Oikeustaso';
  $GLOBALS['locCompanyInactive'] = 'Asiakas pois käytöstä';
  $GLOBALS['locBaseName'] = 'Yrityksen nimi';
  $GLOBALS['locROWTYPE'] = 'Rivityyppi';

  $GLOBALS['locPrintTemplate'] = 'Tulostusmalli';
  $GLOBALS['locPrintTemplates'] = 'Tulostusmallit';
  $GLOBALS['locPrintTemplateName'] = 'Tulostusmallin nimi';
  $GLOBALS['locPrintTemplateFileName'] = 'Tiedosto';
  $GLOBALS['locPrintTemplateParameters'] = 'Parametrit';
  $GLOBALS['locPrintTemplateOutputFileName'] = 'Tulostiedosto';
  $GLOBALS['locPrintTemplateType'] = 'Tyyppi';
  $GLOBALS['locPrintTemplateTypeInvoice'] = 'Lasku';
  $GLOBALS['locPrintTemplateOpenInNewWindow'] = 'Avaa uudessa ikkunassa';
  $GLOBALS['locPrintTemplateInactive'] = 'Pois käytöstä';
  
  //FORM ERRORS & MESSAGES
  $GLOBALS['locERRVALUEMISSING'] = 'Virhe! Arvo puuttuu';
  $GLOBALS['locERRDUPLUNIQUE'] = 'Virhe! Arvo on jo tietokannassa.';
  $GLOBALS['locSAVEFIRST'] = 'Tallenna muut tiedot ensin';
  $GLOBALS['locDBERROR'] = 'Tietokantavirhe';
  $GLOBALS['locDBERRORDESC'] = 'Tietokantavirhe: ';
  $GLOBALS['locSYSTEMONLY'] = 'Järjestelmän sisäinen - ei muokattavissa';
  $GLOBALS['locINVOICENOTOVERDUE'] = 'Maksuja ei voida lisätä laskulle, jonka eräpäivä on tänään ta myöhemmin.';
  $GLOBALS['locWRONGSTATEFORREMINDERFEED'] = 'Maksuja ei voida lisätä maksetulle tai mitätöidylle laskulle.';
  $GLOBALS['locRECORDDELETED'] = 'Tiedot on poistettu.';
  $GLOBALS['locNOACCESS'] = 'Käyttöoikeudet eivät riitä.';
  $GLOBALS['locErrFileUploadFailed'] = 'Tiedoston lataus epäonnistui';
  $GLOBALS['locErrFileTypeInvalid'] = 'Tiedoston tyyppi ei kelpaa';
  $GLOBALS['locErrImportFileNotFound'] = 'Tiedostoa ei löytynyt - tarkista asetukset';
  $GLOBALS['locInvoiceDateNonCurrent'] = 'Huom! Laskun päivämäärä poikkeaa nykyisestä';
  $GLOBALS['locInvoiceNumberAlreadyInUse'] = 'Huom! Laskun numero on jo käytössä toisessa laskussa';
  $GLOBALS['locSaveRecordToAddRows'] = 'Tietue on tallennettava kerran, jotta näiden tietojen lisääminen on mahdollista.';
  $GLOBALS['locInvoiceNumberNotDefined'] = 'Huom! Laskun numero puuttuu';
  $GLOBALS['locInvoiceRefNumberTooShort'] = 'Huom! Laskun viitenumero on liian lyhyt (minimi 4 numeroa)';
  
  //FORM BUTTON HELPERS
  $GLOBALS['locSAVE'] = 'Tallenna';
  $GLOBALS['locCOPY'] = 'Kopioi';
  $GLOBALS['locEDIT'] = 'Muokkaa';
  $GLOBALS['locNEW'] = 'Uusi';
  $GLOBALS['locNEWINVOICE'] = 'Uusi lasku';
  $GLOBALS['locNEWCOMPANY'] = 'Uusi asiakas';
  $GLOBALS['locNEWPRODUCT'] = 'Uusi tuote';
  $GLOBALS['locNEWBASE'] = 'Uusi yritys';
  $GLOBALS['locNEWINVOICESTATE'] = 'Uusi laskun tila';
  $GLOBALS['locNEWROWTYPE'] = 'Uusi laskurivityyppi';
  $GLOBALS['locNEWUSER'] = 'Uusi käyttäjä';
  $GLOBALS['locNEWSESSIONTYPE'] = 'Uusi istunnon tyyppi';
  $GLOBALS['locNewPrintTemplate'] = 'Uusi tulostusmalli';
  $GLOBALS['locDELETE'] = 'Poista';
  $GLOBALS['locPRINTADDR'] = 'Tulosta osoite';
  $GLOBALS['locPRINTINV'] = 'Tulosta lasku';
  $GLOBALS['locPRINTDISPATCHNOTE'] = 'Tulosta lähetysluettelo';
  $GLOBALS['locPRINTRECEIPT'] = 'Tulosta kuitti';
  $GLOBALS['locADDROW'] = 'Lisää&nbsp;rivi';
  $GLOBALS['locDELROW'] = 'Poista rivi';
  $GLOBALS['locYES'] = 'Kyllä';
  $GLOBALS['locNO'] = 'Ei';
  $GLOBALS['locHELP'] = 'Ohje';
  $GLOBALS['locCONFIRMDELETE'] = 'Haluatko varmasti poistaa nämä tiedot?';
  $GLOBALS['locENTRYDELETED'] = 'Tiedot on poistettu';
  $GLOBALS['locDeletedRecord'] = 'Poistettu tietue';
  $GLOBALS['locSAVESEARCH'] = 'Tallenna haku';
  $GLOBALS['locCLOSE'] = 'Sulje';
  $GLOBALS['locRowModification'] = 'Rivin muokkaus';
  $GLOBALS['locRowCopy'] = 'Rivin kopiointi';
 
  
  //MAIN FUNCTIONS
  $GLOBALS['locSHOWSEARCH'] = 'HAKU';
  $GLOBALS['locSHOWREPORTNAVI'] = 'RAPORTIT';
  $GLOBALS['locSHOWSETTINGSNAVI'] = 'ASETUKSET';
  $GLOBALS['locSHOWSYSTEMNAVI'] = 'JÄRJESTELMÄ';
  $GLOBALS['locSHOWINVOICENAVI'] = 'LASKUTUS';
  $GLOBALS['locSHOWARCHIVENAVI'] = 'ARKISTO';
  $GLOBALS['locSHOWCOMPANYNAVI'] = 'ASIAKKAAT';
  $GLOBALS['locSHOWPRODUCTSNAVI'] = 'TUOTTEET';
  $GLOBALS['locSHOWHELP'] = 'OHJE';
  
  //NAVIGATION & LIST
  $GLOBALS['locINVOICESTATES'] = 'Laskun tilat';
  $GLOBALS['locINVOICESTATE'] = 'Laskun tila';
  $GLOBALS['locROWTYPES'] = 'Laskurivityypit';
  $GLOBALS['locROWTYPE'] = 'Laskurivityyppi';
  $GLOBALS['locCOMPANIES'] = 'Asiakkaat';
  $GLOBALS['locCOMPANY'] = 'Asiakas';
  $GLOBALS['locPRODUCTS'] = 'Tuotteet';
  $GLOBALS['locPRODUCT'] = 'Tuote';
  $GLOBALS['locOPENANDUNPAIDINVOICES'] = 'Avoimet ja maksamattomat laskut';
  $GLOBALS['locINVOICES'] = 'Laskut';
  $GLOBALS['locARCHIVEDINVOICES'] = 'Arkistoidut laskut';
  $GLOBALS['locDISPLAYOPENINVOICES'] = 'Avoimet laskut';
  $GLOBALS['locDISPLAYALLINVOICES'] = 'Kaikki laskut';
  $GLOBALS['locDISPLAYARCHIVEDINVOICES'] = 'Arkistoidut laskut';
  $GLOBALS['locINVOICE'] = 'Laskun tiedot';
  $GLOBALS['locSETTINGS'] = 'Asetukset';
  $GLOBALS['locGeneralSettings'] = 'Yleiset asetukset';
  $GLOBALS['locSYSTEM'] = 'Järjestelmä';
  $GLOBALS['locNOENTRIES'] = 'Ei löytyneitä';
  $GLOBALS['locENTERTERMS'] = 'Syötä hakusanat välilyönnillä eroteltuina. Tyhjä kenttä tai * näyttää kaikki tietueet.';
  $GLOBALS['locSEARCH'] = 'Etsi';
  $GLOBALS['locSESSIONTYPES'] = 'Istunnon tyypit';
  $GLOBALS['locUSER'] = 'Käyttäjä';
  $GLOBALS['locUSERS'] = 'Käyttäjät';
  $GLOBALS['locLOGOUT'] = 'KIRJAUDU ULOS';
  $GLOBALS['locBASE'] = 'Yritys';
  $GLOBALS['locBASES'] = 'Yritykset';
  $GLOBALS['locREPORTS'] = 'Raportit';
  $GLOBALS['locREPORT'] = 'Raportti';
  $GLOBALS['locADDPAYMENT'] = 'Syötä maksetut';
  $GLOBALS['locCONFIRM'] = 'Vahvista';
  $GLOBALS['locPRINTINVOICE'] = 'Tulosta laskut';
  $GLOBALS['locEXTSEARCH'] = 'Laaja haku';
  $GLOBALS['locQUICKSEARCH'] = 'Pikahaku';
  $GLOBALS['locINVOICEREPORT'] = 'Laskutusraportti'; 
  $GLOBALS['locPRODUCTREPORT'] = 'Tuoteraportti';
  $GLOBALS['locBACKUPDATABASE'] = 'Tietokannan varmuuskopiointi';
  $GLOBALS['locImportData'] = 'Tietojen tuonti';
  $GLOBALS['locExportData'] = 'Tietojen vienti';
  $GLOBALS['locHeaderCompanyActive'] = 'Käytössä';
  $GLOBALS['locActive'] = 'Käytössä';
  $GLOBALS['locInactive'] = 'Ei käytössä';
  $GLOBALS['locBackToInvoice'] = 'Takaisin laskun tietoihin';
  $GLOBALS['locVATPERCENT'] = 'ALV %';
  $GLOBALS['locREMINDERFEEDESC'] = 'Maksukehotus';
  $GLOBALS['locPENALTYINTERESTDESC'] = 'Viivästyskorko';
  $GLOBALS['locROWDESC'] = 'Tarkenne';
  $GLOBALS['locROWTOTAL'] = 'Yhteensä';
  $GLOBALS['locTOTALEXCLUDINGVAT'] = 'Arvonlisäveroton hinta yhteensä';
  $GLOBALS['locTOTALVAT'] = 'Arvonlisävero yhteensä';
  $GLOBALS['locTOTALINCLUDINGVAT'] = 'Arvonlisäverollinen hinta yhteensä';
  $GLOBALS['locHeaderPrintTemplateActive'] = 'Käytössä';


  // Email
  $GLOBALS['locSendEmail'] = 'Sähköpostin lähetys';
  $GLOBALS['locEmailFrom'] = 'Lähettäjä *';
  $GLOBALS['locEmailTo'] = 'Vastaanottajat *';
  $GLOBALS['locEmailCC'] = 'Kopio';
  $GLOBALS['locEmailBCC'] = 'Piilokopio';
  $GLOBALS['locEmailSubject'] = 'Otsikko *';
  $GLOBALS['locEmailBody'] = 'Viesti *';
  $GLOBALS['locSend'] = 'Lähetä';
  $GLOBALS['locCancel'] = 'Peruuta';
  $GLOBALS['locEmailFillRequiredFields'] = 'Täytä pakolliset kentät (*)';
  $GLOBALS['locEmailSent'] = 'Sähköposti lähetetty';
  $GLOBALS['locEmailFailed'] = 'Sähköpostin lähetys epäonnistui';

  
  // LIST HEADERS
  $GLOBALS['locHEADERINVOICEDATE'] = 'Päivämäärä';       
  $GLOBALS['locHEADERINVOICEDUEDATE'] = 'Eräpäivä';       
  $GLOBALS['locHEADERINVOICENO'] = 'Nro';       
  $GLOBALS['locHEADERINVOICENAME'] = 'Laskun nimi';       
  $GLOBALS['locHEADERINVOICEREFERENCE'] = 'Viitenro';       
  $GLOBALS['locHEADERINVOICESTATE'] = 'Tila';
  $GLOBALS['locHEADERINVOICEBASE'] = 'Laskuttaja';
  $GLOBALS['locHEADERINVOICECOMPANY'] = 'Asiakas';

  // TABLE TEXTS
  $GLOBALS['locTABLETEXTS'] = 
    '"sLengthMenu": "_MENU_ per sivu",' .
    '"sZeroRecords": "Ei löytyneitä",' .
    '"sInfo": "_START_ - _END_ / _TOTAL_",' .
    '"sInfoEmpty": "Ei näytettäviä tietueita",' .
    '"sInfoFiltered": "(suodatettu _MAX_ tietueesta)",' .
    '"sSearch": "Haku",' .
    '"oPaginate": {' .
    '  "sFirst":    "&laquo;",' .
    '  "sPrevious": "&lsaquo;",' .
    '  "sNext":     "&rsaquo;",' .
    '  "sLast":     "&raquo;"' .
    '}';

  //GETINVOICE
  $GLOBALS['locREEMPLOYEE'] = 'Muista tallentaa!';
  $GLOBALS['locMAYCLOSE'] = 'Voit sulkea tämän ikkunan!';
  $GLOBALS['locGET'] = 'Hae';
          
  //REPORTS
  $GLOBALS['locProductReport'] = 'Tuoteraportti';
  $GLOBALS['locOPEN'] = 'Avoimet';
  $GLOBALS['locSENT'] = 'Laskutetut';
  $GLOBALS['locPAID'] = 'Maksetut';
  $GLOBALS['locVATLESS'] = 'Alviton';
  $GLOBALS['locVATPART'] = 'Alv osuus';
  $GLOBALS['locWITHVAT'] = 'Alvillinen';
  $GLOBALS['locTOTAL'] = 'Yhteensä';
  $GLOBALS['locALL'] = 'Kaikki       ';
  $GLOBALS['locDateInterval'] = 'Aikaväli';
  $GLOBALS['locPrintStateSums'] = 'Välisummat tiloittain';
  $GLOBALS['locPrintFormat'] = 'Tulostusmuoto';
  $GLOBALS['locPrintFormatHTML'] = 'HTML';
  $GLOBALS['locPrintFormatPDF'] = 'PDF';
  $GLOBALS['locPrintReportStates'] = 'Raportoitavat tilat';
  $GLOBALS['locReportPage'] = 'Sivu %d';
  
  //extended search
  $GLOBALS['locSearchEqual'] = 'on yhtä kuin';
  $GLOBALS['locSearchNotEqual'] = 'on eri kuin';
  $GLOBALS['locSearchLessThan'] = 'on pienempi kuin';
  $GLOBALS['locSearchGreaterThan'] = 'on suurempi kuin';
  $GLOBALS['locSearchSaved'] = 'Haku tallennettu';
  $GLOBALS['locLABELEXTSEARCH'] = 'Valitse listasta kentät, joista haluat tehdä haun.';
  $GLOBALS['locSELECTSEARCHFIELD'] = 'Hakukenttä';
  $GLOBALS['locSEARCHFIELD'] = 'Hakukenttä';
  $GLOBALS['locSEARCHMATCH'] = ' - ';
  $GLOBALS['locSEARCHTERM'] = 'Hakuehto';
  $GLOBALS['locSEARCHNAME'] = 'Haun nimi';
  $GLOBALS['locERRORNOSEARCHNAME'] = 'VIRHE:\n\rAnna nimi tallennettavalle haulle.';
  $GLOBALS['locLABELQUICKSEARCH'] = 'Tallennetut pikahaut: ';
  $GLOBALS['locNOQUICKSEARCHES'] = 'Tallennettuja pikahakuja ei löytynyt. Voit tallentaa uusia pikahakuja laajan haun kautta.';
 
  //MONTHS
  $GLOBALS['locMONTH'] = 'Kuukausi';
  $GLOBALS['locJAN'] = 'Tammikuu ';
  $GLOBALS['locFEB'] = 'Helmikuu';
  $GLOBALS['locMAR'] = 'Maaliskuu';
  $GLOBALS['locAPR'] = 'Huhtikuu';
  $GLOBALS['locMAY'] = 'Toukokuu';
  $GLOBALS['locJUN'] = 'Kesäkuu';
  $GLOBALS['locJUL'] = 'Heinäkuu';
  $GLOBALS['locAUG'] = 'Elokuu';
  $GLOBALS['locSEP'] = 'Syyskuu';
  $GLOBALS['locOCT'] = 'Lokakuu';
  $GLOBALS['locNOV'] = 'Marraskuu';
  $GLOBALS['locDEC'] = 'Joulukuu';
          
  //open_invoices.php
  $GLOBALS['locLABELOPENINVOICES'] = 'Avoimet laskut';
  $GLOBALS['locNOOPENINVOICES'] = 'Ei avoimia laskuja';
  $GLOBALS['locLABELUNPAIDINVOICES'] = 'Maksamattomat laskut';
  $GLOBALS['locNOUNPAIDINVOICES'] = 'Ei maksamattomia laskuja';
  
  // General Settings
  $GLOBALS['locSettingAutoCloseFormAfterSave'] = 'Sulje lomake automaattisesti tallennuksen jälkeen';
  $GLOBALS['locSettingAutoCloseFormAfterDelete'] = 'Sulje lomake automaattisesti poiston jälkeen';
  $GLOBALS['locSettingAddCustomerNumber'] = 'Lisää uudelle asiakkaalle juokseva asiakasnumero automaattisesti';
  $GLOBALS['locSettingShowDeletedRecords'] = 'Näytä poistetut tietueet (istuntokohtainen asetus)';
  $GLOBALS['locSettingSessionKeepalive'] = 'Vältä istunnon aikakatkaisua niin kauan kuin sovellus on auki selaimessa';
  $GLOBALS['locSettingInvoices'] = 'Laskutusasetukset';
  $GLOBALS['locSettingInvoiceAddNumber'] = 'Lisää laskulle juokseva laskunumero automaattisesti tulostettaessa';
  $GLOBALS['locSettingInvoiceAddReferenceNumber'] = 'Lisää laskulle viitenumero automaattisesti tulostettaessa';
  $GLOBALS['locSettingInvoiceNumberingPerBase'] = 'Numeroi laskut laskuttajakohtaisesti';
  $GLOBALS['locSettingInvoiceShowBarcode'] = 'Näytä viivakoodi laskun tilisiirtolomakkeessa';
  $GLOBALS['locSettingInvoiceShowRowDate'] = 'Näytä laskurivin päiväys laskussa';
  $GLOBALS['locSettingInvoiceSeparateStatement'] = 'Tulosta laskurivit aina erilliseen laskuerittelyyn';
  $GLOBALS['locSettingInvoiceDefaultVATPercent'] = 'Oletus-ALV-prosentti';
  $GLOBALS['locSettingInvoicePaymentDays'] = 'Oletusmaksuaika päivinä';
  $GLOBALS['locSettingInvoiceTermsOfPayment'] = 'Maksuehdot (%d = maksuaika päivinä)';
  $GLOBALS['locSettingInvoicePeriodForComplaints'] = 'Huomautusaika';
  $GLOBALS['locSettingInvoicePenaltyInterestPercent'] = 'Viivästyskorkoprosentti';
  $GLOBALS['locSettingInvoiceNotificationFee'] = 'Huomautusmaksu';
  $GLOBALS['locSettingInvoicePDFFilename'] = 'Laskun tiedostonimi (%s = laskun numero, tulostusmallin määritys ohittaa tämän asetuksen)';
  $GLOBALS['locSettingInvoiceWarnIfNonCurrentDate'] = 'Varoita tulostettaessa, jos laskun päivämäärä poikkeaa nykyisestä';
  $GLOBALS['locSettingInvoiceClearRowValuesAfterAdd'] = 'Laskurivin syöttökenttien käyttäytyminen rivin lisäyksen jälkeen';
  $GLOBALS['locSettingInvoiceKeepRowValues'] = 'Säilytä arvot';
  $GLOBALS['locSettingInvoiceClearRowValues'] = 'Tyhjennä kentät';
  $GLOBALS['locSettingInvoiceUseProductDefaults'] = 'Käytä tuotteen oletusarvoja';
  

  // Base email
  $GLOBALS['locBaseEmailTitle'] = 'Laskujen lähetys sähköpostilla';
  $GLOBALS['locBaseEmailFrom'] = 'Lähettäjän sähköpostiosoite';
  $GLOBALS['locBaseEmailBCC'] = 'Piilokopion vastaanottaja';
  $GLOBALS['locBaseEmailSubject'] = 'Aihe';
  $GLOBALS['locBaseEmailBody'] = 'Viesti';

  // Base logo
  $GLOBALS['locBaseLogoTitle'] = 'Logo';
  $GLOBALS['locBaseChangeImage'] = 'Vaihda kuva...';
  $GLOBALS['locBaseLogo'] = 'Yrityksen logo (suositeltu leveys-korkeussuhde n. 4:1, png tai jpg, tiedoston maksimikoko %s)';
  $GLOBALS['locBaseLogoSizeDBLimited'] = '(tietokantayhteyden rajoitus)';
  $GLOBALS['locBaseSaveLogo'] = 'Tallenna logo';
  $GLOBALS['locBaseEraseLogo'] = 'Poista logo';
  $GLOBALS['locBaseLogoSaved'] = 'Logo tallennettu';
  $GLOBALS['locBaseLogoErased'] = 'Logo poistettu';
  $GLOBALS['locBaseLogoTop'] = 'Yläreunasta (mm, oletus 15)';
  $GLOBALS['locBaseLogoLeft'] = 'Vasemmalta (mm, oletus 10)';
  $GLOBALS['locBaseLogoWidth'] = 'Logon leveys (mm, oletus 80)';
  $GLOBALS['locBaseLogoBottomMargin'] = 'Logon alamarginaali (mm, oletus 5)';

  // Import / Export
  $GLOBALS['locImportExportFieldDelimiterComma'] = 'Pilkku';
  $GLOBALS['locImportExportFieldDelimiterSemicolon'] = 'Puolipiste';
  $GLOBALS['locImportExportFieldDelimiterTab'] = 'Sarkain'; 
  $GLOBALS['locImportExportFieldDelimiterPipe'] = 'Putki'; 
  $GLOBALS['locImportExportFieldDelimiterColon'] = 'Kaksoispiste';
  $GLOBALS['locImportExportEnclosureDoubleQuote'] = 'Lainausmerkki';
  $GLOBALS['locImportExportEnclosureSingleQuote'] = 'Heittomerkki';
  $GLOBALS['locImportExportEnclosureNone'] = 'Ei mitään';
  $GLOBALS['locImportExportCharacterSet'] = 'Merkistö';    
  $GLOBALS['locImportExportTable'] = 'Taulu';    
  $GLOBALS['locImportExportTableBases'] = 'Laskuttajat';    
  $GLOBALS['locImportExportTableCompanies'] = 'Asiakkaat';    
  $GLOBALS['locImportExportTableCompanyContacts'] = 'Asiakkaiden yhteyshenkilöt';    
  $GLOBALS['locImportExportTableInvoices'] = 'Laskut';    
  $GLOBALS['locImportExportTableInvoiceRows'] = 'Laskurivit';    
  $GLOBALS['locImportExportTableProducts'] = 'Tuotteet';    
  $GLOBALS['locImportExportTableRowTypes'] = 'Laskurivityypit';    
  $GLOBALS['locImportExportTableInvoiceStates'] = 'Laskun tilat';    
  $GLOBALS['locImportExportFormat'] = 'Formaatti';    
  $GLOBALS['locImportExportFieldDelimiter'] = 'Kentän erotin';    
  $GLOBALS['locImportExportEnclosureCharacter'] = 'Tekstimerkki';    
  $GLOBALS['locImportExportRowDelimiter'] = 'Rivinvaihto';    
  $GLOBALS['locImportExportColumnNone'] = '(ei mitään)';
  
  // Import
  $GLOBALS['locImportFileSelection'] = 'Tuotavan tiedoston valinta';
  $GLOBALS['locImportFileParameters'] = 'Tuotavan tiedoston asetukset';
  $GLOBALS['locImportUploadFile'] = 'Lataa tiedosto palvelimelle (tiedoston maksimikoko %s)';
  $GLOBALS['locImportUseServerFile'] = 'Käytä palvelimella olevaa tiedostoa (määritelty asetustiedostossa)';
  $GLOBALS['locImportColumnMapping'] = 'Sarakkeiden kohdistus (ensimmäiset 10 riviä)';
  $GLOBALS['locImportIdentificationColumns'] = 'Olemassaolevien rivien tunnistukseen käytettävät sarakkeet';
  $GLOBALS['locImportExistingRowHandling'] = 'Olemassaolevien rivien käsittely';
  $GLOBALS['locImportExistingRowIgnore'] = 'Ohita';
  $GLOBALS['locImportExistingRowUpdate'] = 'Päivitä';
  $GLOBALS['locImportColumnUnused'] = '(ei käytössä)';
  $GLOBALS['locImportMode'] = 'Suoritettava toiminto';
  $GLOBALS['locImportModeReport'] = 'Vain raportti (ei tee varsinaista tuontia)';
  $GLOBALS['locImportModeImport'] = 'Tietojen tuonti';
  $GLOBALS['locImportResults'] = 'Tuonnin tulokset';
  $GLOBALS['locImportNext'] = 'Seuraava';
  $GLOBALS['locImportStart'] = 'Käynnistä';
  $GLOBALS['locImportDone'] = 'Tuonti valmis';
  $GLOBALS['locImportNoMappedColumns'] = 'Yhtään sarakkeen kohdistusta ei löytynyt, tyhjää tietuetta ei lisätty';

  // Export
  $GLOBALS['locExport'] = 'Vienti';
  $GLOBALS['locTable_base'] = 'Laskuttajat';    
  $GLOBALS['locTable_company'] = 'Asiakkaat';    
  $GLOBALS['locTable_company_contact'] = 'Yhteyshenkilöt';    
  $GLOBALS['locTable_invoice'] = 'Laskut';    
  $GLOBALS['locTable_invoice_row'] = 'Laskurivit';    
  $GLOBALS['locTable_product'] = 'Tuotteet';    
  $GLOBALS['locTable_row_type'] = 'Laskurivityypit';    
  $GLOBALS['locTable_invoice_state'] = 'Laskun tilat';    
  $GLOBALS['locExportIncludeChildRows'] = 'Sisällytä lapsirivit';
  $GLOBALS['locExportIncludeDeletedRecords'] = 'Sisällytä poistetut tietueet';
  $GLOBALS['locExportColumns'] = 'Sarakkeet';    
  $GLOBALS['locExportAddAllColumns'] = 'Lisää kaikki';
  $GLOBALS['locExportDo'] = 'Vie';
  break;
}

foreach ($GLOBALS as $key => &$tr)
{
  if (substr($key, 0, 3) == 'loc' && is_string($tr))
  {
    if (_CHARSET_ != 'UTF-8')
      $tr = utf8_decode($tr);
  }
}

?>
