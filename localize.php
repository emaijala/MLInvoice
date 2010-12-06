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

switch (isset($_POST['sesLANG']) ? $_POST['sesLANG'] : 'fi') {

case 'en' :
    
break;

default :
    
    $GLOBALS['locLICENSENOTIFY'] = 'Tämä ohjelma on vapaa - Katso LICENSE ja gpl.txt asennushakemistosta.<br/>Copyright 2010 Ere Maijala<br/>Portions  Copyright 2004-2008 Samu Reinikainen';
    $GLOBALS['locCREDITS'] = 'Kiitos avusta:<br/> Olivier Plathey - FPDF - http://www.fpdf.org/<br/> GuinuX - Barcode128 + PDF Barcode128 - http://phpclasses.org/browse.html/package/592/';
    
    //FORM LABELS
    $GLOBALS['locCOMPNAME'] = 'Yrityksen nimi';
    $GLOBALS['locCOMPVATID'] = 'Y-tunnus';
    $GLOBALS['locCLIENTNO'] = 'Asiakasnumero';
    $GLOBALS['locCONTACTPERS'] = 'Kontakti';
    $GLOBALS['locCOMPTYPE'] = 'Yritystyyppi';
    $GLOBALS['locEMAIL'] = 'Email';
    $GLOBALS['locVATREGISTERED'] = 'ALV-rekisteröity';
    $GLOBALS['locWWW'] = 'WWW';
    $GLOBALS['locNEARADDR'] = 'Lähiosoite';
    $GLOBALS['locADDRESS'] = 'Osoite';
    $GLOBALS['locSTREETADDR'] = 'Katuosoite';
    $GLOBALS['locHOMECOMMUNE'] = 'Kotikunta';
    $GLOBALS['locZIPCODE'] = 'Postinro';
    $GLOBALS['locCITY'] = 'Postitoimipaikka';
    $GLOBALS['locPOSTALADDR'] = 'Postitoimipaikka';
    $GLOBALS['locPHONE'] = 'Puh.';
    $GLOBALS['locPHONEHOME'] = 'Puh. (koti)';
    $GLOBALS['locPHONEWORK'] = 'Puh. (työ)';
    $GLOBALS['locGSM'] = 'GSM';
    $GLOBALS['locBILLADDR'] = 'Laskutusosoite';
    $GLOBALS['locINFO'] = 'Lisätiedot';
    $GLOBALS['locNAMELAST'] = 'Sukunimi';
    $GLOBALS['locNAMEFIRST'] = 'Etunimet';
    $GLOBALS['locPERSONID'] = 'Henk.tunnus';
    $GLOBALS['locINVNAME'] = 'Laskun nimi';
    $GLOBALS['locINVNO'] = 'Laskunro';
    $GLOBALS['locPAYER'] = 'Maksaja';
    $GLOBALS['locCLIENTSREFERENCE'] = 'Asiakkaan viite';
    $GLOBALS['locYOURREFERENCE'] = 'Viitteenne';
    $GLOBALS['locINVDATE'] = 'Laskupvm';
    $GLOBALS['locDUEDATE'] = 'Eräpvm';
    $GLOBALS['locREFNO'] = 'Viitenro';
    $GLOBALS['locPAYDATE'] = 'Maksupvm';
    $GLOBALS['locSTATUS'] = 'Tila';
    $GLOBALS['locARCHIVED'] = 'Arkistoitu';
    $GLOBALS['locGETINVNO'] = 'Hae laskunro ja laskupvm';
    $GLOBALS['locINVROWS'] = 'Laskurivit';
    $GLOBALS['locORDERNO'] = 'Järj.nro';
    $GLOBALS['locROWTYPE'] = 'Rivityyppi';
    $GLOBALS['locSHOWINVOICES'] = 'Näytä laskut';
    $GLOBALS['locDESCRIPTION'] = 'Kuvaus';
    $GLOBALS['locDATE'] = 'Pvm';
    $GLOBALS['locPRICE'] = 'Hinta';
    $GLOBALS['locPCS'] = 'Lkm';
    $GLOBALS['locUNIT'] = 'Yksikkö';
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
    $GLOBALS['locLOGIN'] = 'Kirjaudu';
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
    $GLOBALS['locUNITTYPES'] = 'Yksiköt';
    $GLOBALS['locUNITTYPE'] = 'Yksikkö';
    $GLOBALS['locPERIOD'] = 'Ajalla';
    $GLOBALS['locMM'] = 'kk';
    $GLOBALS['locYY'] = 'vuosi';
    $GLOBALS['locLABELGENERALINFO'] = 'Perustiedot';
    $GLOBALS['locLABELOTHERINFO'] = 'Muut tiedot';
    $GLOBALS['locCATEGORY'] = 'Kategoria';
    $GLOBALS['locSTARTDATE'] = 'Alkupvm';
    $GLOBALS['locENDDATE'] = 'Loppupvm';
    $GLOBALS['locTASKNAME'] = 'Tehtävä';
    $GLOBALS['locTODODATE'] = 'Deadline pvm';
    $GLOBALS['locTODOTIME'] = 'Deadline klo';
    $GLOBALS['locTASKINFO'] = 'Tehtävän lisätiedot';
    $GLOBALS['locJOB'] = 'Tehtävä';
    $GLOBALS['locCONTACTS'] = 'Kontaktit';
    $GLOBALS['locCONTACTPERSON'] = 'Kontaktihenkilö';
    $GLOBALS['locSOCIALSECNO'] = 'Henkilötunnus';
    $GLOBALS['locPERSONTITLE'] = 'Titteli';
    $GLOBALS['locFAX'] = 'Fax';
    $GLOBALS['locMORE'] = '* Asetukset ->';
    $GLOBALS['locPREVIOUS'] = '<- Asetukset *';
    $GLOBALS['locBANKNAME'] = 'Pankki';
    $GLOBALS['locBANKACCOUNT'] = 'Tilinro';
    $GLOBALS['locAMOUNT'] = 'Summa(&euro;)';
    $GLOBALS['locWEEKDATE'] = 'Viikonpäivä';
    $GLOBALS['locLABELCONTACTINFO'] = 'Yhteystiedot';
    $GLOBALS['locCOMPANY'] = 'Yritys';
    $GLOBALS['locNOTINUSE'] = 'Poissa käytöstä';
    $GLOBALS['locNOTICE'] = 'Huomautukset';
    $GLOBALS['locPAYMENTTYPE'] = 'Maksutapa';
    $GLOBALS['locREALINVNO'] = 'Laskunro (juokseva)';
    $GLOBALS['locBILLER'] = 'Laskuttaja';
    $GLOBALS['locROWNO'] = 'Rivinro';
    $GLOBALS['locCOPYINV'] = 'Kopioi lasku ja laskurivit';
    $GLOBALS['locREFUNDINV'] = 'Mitätöi ja luo hyvityslasku';
    $GLOBALS['locADDREMINDERFEES'] = 'Lisää huomautus- ja viivästysmaksu';
    $GLOBALS['locSHOWREFUNDEDINV'] = 'Näytä hyvitetty lasku';
    $GLOBALS['locSHOWREFUNDINGINV'] = 'Näytä hyvittävä lasku';
    $GLOBALS['locPRODUCTNAME'] = 'Tuotteen nimi';
    $GLOBALS['locPRODUCTDESCRIPTION'] = 'Tuotekuvaus';
    $GLOBALS['locPRODUCTCODE'] = 'Tuotekoodi';
    $GLOBALS['locPRODUCTGROUP'] = 'Tuoteryhmä';
    $GLOBALS['locINTERNALINFO'] = 'Lisätiedot (ei näytetä)';
    $GLOBALS['locUNITPRICE'] = 'Yksikköhinta';
    $GLOBALS['locVATPERCENT'] = 'ALV %';
    $GLOBALS['locVATINCLUDED'] = 'Hinta sisältää ALV:n';
    $GLOBALS['locVATINC'] = 'Sis.&nbsp;ALV';
    $GLOBALS['locACCESSLEVEL'] = 'Oikeustaso';
    
    
    //FORM ERRORS & MESSAGES
    $GLOBALS['locERRVALUEMISSING'] = 'Virhe! Arvo puuttuu';
    $GLOBALS['locERRDUPLUNIQUE'] = 'Virhe! Arvo on jo tietokannassa...';
    $GLOBALS['locERRDELREFERENCE'] = 'Tietuetta ei voi poistaa! Viittaukset poistettava ensin...';
    $GLOBALS['locSAVEFIRST'] = 'Tallenna muut tiedot ensin';
    $GLOBALS['locDBERROR'] = 'Tietokantavirhe';
    $GLOBALS['locDBERRORDESC'] = 'Tietokantavirhe: ';
    $GLOBALS['locDBERRORFOREIGNKEY'] = 'Toiminto ei onnistunut, koska tietueeseen on viittauksia muualta.';
    $GLOBALS['locFEE'] = 'Maksu';
    $GLOBALS['locMAKEINVFORYEAR'] = 'Tee laskut vuodelle';
    $GLOBALS['locMAKEINVTO'] = 'Laskutettavat jäsentyypit';
    $GLOBALS['locGENERAL'] = 'Yleistä';
    $GLOBALS['locSYSTEMONLY'] = 'Järjestelmän sisäinen - ei muokattavissa';
    $GLOBALS['locWRONGSTATEFORREMINDERFEED'] = 'Maksuja ei voida lisätä laskun nykyisessä tilassa.';
    $GLOBALS['locRECORDDELETED'] = 'Tiedot on poistettu.';
    
    //FORM BUTTON HELPERS
    $GLOBALS['locSAVE'] = 'Tallenna';
    $GLOBALS['locCOPY'] = 'Kopioi';
    $GLOBALS['locNEW'] = 'Uusi';
    $GLOBALS['locNEWINVOICE'] = 'Uusi lasku';
    $GLOBALS['locNEWCOMPANY'] = 'Uusi asiakas';
    $GLOBALS['locNEWPRODUCT'] = 'Uusi tuote';
    $GLOBALS['locNEWBASE'] = 'Uusi yritys';
    $GLOBALS['locNEWINVOICESTATE'] = 'Uusi laskun tila';
    $GLOBALS['locNEWROWTYPE'] = 'Uusi laskurivityyppi';
    $GLOBALS['locNEWUSER'] = 'Uusi käyttäjä';
    $GLOBALS['locNEWSESSIONTYPE'] = 'Uusi istunnon tyyppi';
    $GLOBALS['locDELETE'] = 'Poista';
    $GLOBALS['locPRINTADDR'] = 'Tulosta osoite';
    $GLOBALS['locPRINTINV'] = 'Tulosta lasku';
    $GLOBALS['locADD'] = 'Lisää rivi';
    $GLOBALS['locDELROW'] = 'Poista rivi';
    $GLOBALS['locYES'] = 'Kyllä';
    $GLOBALS['locNO'] = 'Ei';
    $GLOBALS['locHELP'] = 'Ohje';
    $GLOBALS['locCONFIRMDELETE'] = 'Haluatko varmasti poistaa nämä tiedot?\n\rTiedot poistetaan lopullisesti!';
    $GLOBALS['locENTRYDELETED'] = 'Tiedot on poistettu!';
    $GLOBALS['locSAVESEARCH'] = 'Tallenna haku';
    $GLOBALS['locCLOSE'] = 'Sulje';
   
    $GLOBALS['locLOGON'] = 'KIRJAUDU';
    $GLOBALS['locCANCEL'] = 'Peruuta';
    $GLOBALS['locSEND'] = 'Lähetä';
    $GLOBALS['locMAKEINVOICE'] = 'Tee lasku';
    
    
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
    $GLOBALS['locCOMPANYTYPES'] = 'Yritystyypit';
    $GLOBALS['locCOMPANYTYPE'] = 'Yritystyyppi';
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
    $GLOBALS['locSYSTEM'] = 'Järjestelmä';
    $GLOBALS['locSHOWPREVRES'] = 'Näytä edellinen tulosjoukko';
    $GLOBALS['locSHOWNEXTRES'] = 'Näytä seuraava tulosjoukko';
    $GLOBALS['locNOENTRIES'] = 'Hakuehdoilla ei löytynyt tietoja';
    $GLOBALS['locENTERTERMS'] = 'Syötä hakusanat välilyönnillä eroteltuina. Tyhjä kenttä tai * näyttää kaikki tietueet.';
    $GLOBALS['locSEARCH'] = 'Etsi';
    $GLOBALS['locSHOWADMINNAVI'] = 'Näytä hallintavalikko';
    $GLOBALS['locMAINNAVI'] = 'Takaisin päävalikkoon';
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
    
    // LIST HEADERS
    $GLOBALS['locHEADERINVOICEDATE'] = 'Pvm';       
    $GLOBALS['locHEADERINVOICENO'] = 'Nro';       
    $GLOBALS['locHEADERINVOICENAME'] = 'Laskun nimi';       
    $GLOBALS['locHEADERINVOICEREFERENCE'] = 'Viitenro';       
    $GLOBALS['locHEADERINVOICESTATE'] = 'Tila';
    $GLOBALS['locHEADERINVOICEBASE'] = 'Laskuttaja';
    $GLOBALS['locHEADERINVOICECOMPANY'] = 'Maksaja';

    // TABLE TEXTS
    $GLOBALS['locTABLETEXTS'] = 
			'"sLengthMenu": "_MENU_ per sivu",' .
			'"sZeroRecords": "Ei löytyneitä",' .
			'"sInfo": "_START_ - _END_ / _TOTAL_",' .
			'"sInfoEmpty": "Ei näytettäviä tietueita",' .
			'"sInfoFiltered": "(suodatettu _MAX_ tietueesta)",' .
			'"sSearch": "Haku",' .
			'"oPaginate": {' .
      '  "sFirst":    "Ensimmäinen",' .
      '  "sPrevious": "Edellinen",' .
      '  "sNext":     "Seuraava",' .
      '  "sLast":     "Viimeinen"' .
      '}';

    //GETINVOICE
    $GLOBALS['locREEMPLOYEE'] = 'Muista tallentaa!';
    $GLOBALS['locMAYCLOSE'] = 'Voit sulkea tämän ikkunan!';
    $GLOBALS['locGET'] = 'Hae';
    
    //INVOICE-PDF        
    $GLOBALS['locINVOICEHEADER'] = 'LASKU';
    $GLOBALS['locSENDER'] = 'Lähettäjä';
    $GLOBALS['locPERIODFORCOMPLAINTS'] = 'Huomautusaika';
    $GLOBALS['locPENALTYINTEREST'] = 'Viivästyskorko';
    $GLOBALS['locTERMSOFPAYMENT'] = 'Maksuehdot';
    $GLOBALS['locSUM'] = 'Hinta €';
    $GLOBALS['locVATPERCENT'] = 'ALV %';
    $GLOBALS['locTAX'] = 'ALV';
    $GLOBALS['locROWTOTAL'] = 'Yhteensä';
    $GLOBALS['locROWNAME'] = 'Nimike';
    $GLOBALS['locROWDESC'] = 'Tarkenne';
    $GLOBALS['locROWPRICE'] = 'Hinta';
    $GLOBALS['locTOTALEXCLUDINGVAT'] = 'Veroton kokonaishinta';
    $GLOBALS['locTOTALVAT'] = 'Arvonlisävero';
    $GLOBALS['locTOTALINCLUDINGVAT'] = 'Verollinen kokonaishinta';
    $GLOBALS['locSEESEPARATESTATEMENT'] = 'ks. erillinen laskuerittely';
    $GLOBALS['locVATREG'] = 'ALV-rek.';
    $GLOBALS['locINVNUMBER'] = 'Laskun numero';
    $GLOBALS['locPDFINVDATE'] = 'Laskun päivämäärä';
    $GLOBALS['locPDFDUEDATE'] = 'Eräpäivä';
    $GLOBALS['locPDFINVREFNO'] = 'Viitenumero';
    $GLOBALS['locREFUNDSINVOICE'] = 'Tämä lasku hyvittää laskun %d';
    $GLOBALS['locDUEDATENOW'] = 'HETI';
    $GLOBALS['locFIRSTREMINDERHEADER'] = 'MAKSUKEHOTUS';
    $GLOBALS['locSECONDREMINDERHEADER'] = 'MAKSUKEHOTUS';
    $GLOBALS['locFIRSTREMINDERNOTE'] = "Kirjanpitomme mukaan laskunne on vielä maksamatta. Pyydämme teitä maksamaan laskun pikaisesti samaa viitenumeroa käyttäen. Jos lasku on jo maksettu, on tämä kehotus aiheeton.";
    $GLOBALS['locSECONDREMINDERNOTE'] = "Kirjanpitomme mukaan laskunne on edelleen maksamatta. Olkaa hyvä ja maksakaa lasku välittömästi samaa viitenumeroa käyttäen.";
    $GLOBALS['locREMINDERFEEDESC'] = 'Maksukehotus';
    $GLOBALS['locPENALTYINTERESTDESC'] = 'Viivästyskorko';
    
    //??
    $GLOBALS['locPAYMENTS'] = 'Maksut';
    $GLOBALS['locPAYMENT'] = 'Maksu';
    $GLOBALS['locNOVALUES'] = 'Ehdoilla ei löytynyt laskuja tehtäväksi';
    $GLOBALS['locINVOICESDONE'] = "Laskut tehty.";
    $GLOBALS['locMAKEINVOICES'] = 'Tarkista että tiedot ovat oikein ja tee laskut "VAHVISTA"-painikkeella.';
    $GLOBALS['locPRINTINVFORYEAR'] = 'Tulosta laskut vuodelta';
    $GLOBALS['locPRINTINVTO'] = 'Tulostettavat laskut';
    $GLOBALS['locSETTIME'] = 'Aseta';
    $GLOBALS['locPERCENT'] = 'Prosentti';
    $GLOBALS['locEDIT'] = 'Muokkaa';
    
    
    //REPORTS
    $GLOBALS['locSELECTYEAR'] = 'Virhe! Valitse vuosi...';
    $GLOBALS['locENTERVALUES'] = 'Virhe! Syötä kaikki tiedot.';
    $GLOBALS['locPRINTREPORTFORYEAR'] = 'Tulosta raportit vuodelle';
    $GLOBALS['locPRINTREPORTTO'] = 'Valitse tulostettavat raportit';
    $GLOBALS['locOPEN'] = 'Avoimet';
    $GLOBALS['locSENT'] = 'Laskutetut';
    $GLOBALS['locPAID'] = 'Maksetut';
    $GLOBALS['locVATLESS'] = 'Alviton';
    $GLOBALS['locVATPART'] = 'Alv osuus';
    $GLOBALS['locWITHVAT'] = 'Alvillinen';
    $GLOBALS['locTOTAL'] = 'Yhteensä';
    $GLOBALS['locALL'] = 'Kaikki       ';
    $GLOBALS['locENTERPAYMENT'] = 'Ilmoita lasku maksetuksi';
    $GLOBALS['locENTERREFNODATE'] = 'Syötä viitenro ja maksupvm';
    $GLOBALS['locCONFIRMPAYMENT'] = 'Tarkista että tiedot ovat oikein ja<br>merkitse lasku maksetuksi "VAHVISTA"-painikkeella.';
    $GLOBALS['locPAYMENTDONE'] = 'Lasku on merkitty maksetuksi.<br>Voit ilmoittaa lisää laskuja maksetuksi.';
    $GLOBALS['locDATED'] = 'Päiväys';
    $GLOBALS['locINVOICES'] = 'Laskut';
    $GLOBALS['locREMEMBER'] = 'Muista tallentaa tiedot "Tallenna"-painikkeella!';
    $GLOBALS['locLABELINVOICEHISTORY'] = 'Yrityksen laskuhistoria';
    
    //select_report
    $GLOBALS['locSELECTREPORTYEAR'] = 'Valitse raportin vuosi';
    $GLOBALS['locSELECTREPORT'] = 'Valitse raportti';
    
    //extended search
    $GLOBALS['locLABELEXTSEARCH'] = 'Valitse listasta kentät, joista haluat haun tehdä.';
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
    $GLOBALS['locMONTHS'] = array($GLOBALS['locJAN'], $GLOBALS['locFEB'],  $GLOBALS['locMAR'], $GLOBALS['locAPR'], $GLOBALS['locMAY'], $GLOBALS['locJUN'], $GLOBALS['locJUL'], $GLOBALS['locAUG'], $GLOBALS['locSEP'], $GLOBALS['locOCT'], $GLOBALS['locNOV'], $GLOBALS['locDEC']);
    $GLOBALS['locMONTHS2'] = array('',$GLOBALS['locJAN'], $GLOBALS['locFEB'],  $GLOBALS['locMAR'], $GLOBALS['locAPR'], $GLOBALS['locMAY'], $GLOBALS['locJUN'], $GLOBALS['locJUL'], $GLOBALS['locAUG'], $GLOBALS['locSEP'], $GLOBALS['locOCT'], $GLOBALS['locNOV'], $GLOBALS['locDEC']);
            
    //WEEKDAYS
    $GLOBALS['locWEEKDAYSSHORT'] = array('Ma','Ti','Ke','To','Pe','La','Su');
    $GLOBALS['locWEEKDAYS'] = array('Maanantai', 'Tiistai', 'Keskiviikko', 'Torstai', 'Perjantai', 'Lauantai', 'Sunnuntai');
    
    
    $GLOBALS['locWEEK'] = 'Viikko';
    $GLOBALS['locTIMESTART'] = 'Alku';
    $GLOBALS['locTIMEEND'] = 'Loppu';
    $GLOBALS['locPAYMENTDATE'] = 'Maksupvm';
    
    //open_invoices.php
    $GLOBALS['locLABELOPENINVOICES'] = 'Avoimet laskut';
    $GLOBALS['locNOOPENINVOICES'] = 'Ei avoimia laskuja';
    $GLOBALS['locLABELUNPAIDINVOICES'] = 'Maksamattomat laskut';
    $GLOBALS['locNOUNPAIDINVOICES'] = 'Ei maksamattomia laskuja';
    
    //organizer
    $GLOBALS['locPREVMONTH'] = 'Edellinen kuukausi';
    $GLOBALS['locNEXTMONTH'] = 'Seuraava kuukausi';
    $GLOBALS['locSHOWTODAY'] = 'Siirry tähän päivään';
    $GLOBALS['locNOEVENTS'] = 'Ei tapahtumia';
    $GLOBALS['locSHOWEVENTS'] = 'Näytä tapahtumat';
    $GLOBALS['locADDEVENT'] = 'Lisää tapahtuma';
    $GLOBALS['locDELEVENT'] = 'Poista tapahtuma';
    $GLOBALS['locSHOWEVENT'] = 'Näytä tapahtuman tiedot';
    
    //create_invoice.php
    $GLOBALS['locWEEKS'] = 'Viikot';
    
    //pic uploader
    $GLOBALS['locROTATE'] = 'Käännä';
    $GLOBALS['locROTATECWISE'] = 'Käännä myötäpäivään';
    $GLOBALS['locROTATEACWISE'] = 'Käännä vastapäivään';
    $GLOBALS['locRESIZE'] = 'Muuta koko';
    $GLOBALS['locADDTEN'] = '+10%';
    $GLOBALS['locMINTEN'] = '-10%';
    $GLOBALS['locRESIZEWIDTH'] = 'Muuta pituutta';
    $GLOBALS['locRESIZEHEIGHT'] = 'Muuta korkeutta';
    $GLOBALS['locRESIZEWITHGIVENVALUES'] = 'Muuta annettuihin arvoihin';
break;

}

?>
