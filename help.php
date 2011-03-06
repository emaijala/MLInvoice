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

require "htmlfuncs.php";
require "sqlfuncs.php";
require "miscfuncs.php";
require "sessionfuncs.php";

sesVerifySession();
$strTopic = getRequest('topic', FALSE);



switch ( $strTopic ) {
    case 'main':
        $strHeadTitle = "Ohjeet";
        $strTopicText =
            "Ohjelma koostuu ylävalikosta, jossa on ohjelman pääosien painikkeet, toimintovalikosta, jossa näytetään valitun osion toiminnot ja toimintoikkunoista, joissa suoritetaan valitun toiminnon käsittely.<br>(Esim. 'Laaja haku'-painikkeesta avataan hakunäkymä, josta 'Etsi'-painikkeella avataan tulokset toimintoikkunaan.)<br><br>Ohje-painikkeella avautuvat tarkemmat ohjeet kyseiseen näkymään tai toimintoon.";
    break;
    case 'search':
        $strHeadTitle = "Haku";
        $strTopicText =
            "Valitse toiminto, jossa haluat suorittaa haun (esim. 'ASIAKKAAT').<br><br>Syötä tyhjään kenttään hakusana(t), joilla tietoa haetaan (esim. sukunimi).<br>Voit jättää kentän tyhjäksi tai käyttää '*'-merkkiä, jolloin kaikki valitun toiminnon tiedot haetaan.<br><br>Haku suoritetaan 'Etsi'-painikkeesta.<br><br>'Laaja haku'-painikkeella avautuu ikkuna, jolla voi tehdä tarkkoja hakuja laajoilla hakuehdoilla (valitse ensin toiminto, jossa haluat tehdä haun).<br><br>'Pikahaku'-painikkeella avataan ikkuna, josta voi valita tallennetun pikahaun (pikahaut tallennetaan tarkan haun kautta).";
    break;
    case 'navi':
        $strHeadTitle = "Toimintovalikko";
        $strTopicText =
            "Valitse toiminto painamalla sen nimeä.";
    break;
    case 'list':
        $strHeadTitle = "Hakutulokset";
        $strTopicText =
            "Listan toiminnot ovat seuraavat (ylhäältä lukien) :<br><br>1. Otsikko näyttää tietueen, johon haku tehtiin<br><br>2. Numerot kertovat löytyneiden tietojen määrän.<br>Kun tietoja on paljon, näytetään ne osittain.<br> Nuolilla siirrytään tulosjoukosta toiseen.<br><br>3. Löytyneet tiedot listataan allekkain ja nimeä klikkaamalla avataan tietojen käsittelyyn tarkoitettu lomake.<br><br>4. 'UUSI'-painikkeella voit luoda uuden tietueen. (Esim. uuden asiakkaan tiedot)";
    break;
    case 'form':
        $strHeadTitle = "Lomake";
        $strTopicText =
            "Lomake koostuu seuraavista osista (ylhäältä lukien) :<br><br>1. Lomakkeella on vaihteleva määrä syöttökenttiä otsikoineen, joihin tiedot lisätään tai joiden tietoja muokataan.<br><br>2. 'TEE'-painikkeella suoritetaan monimutkaisempia toimintoja.<br> <br><br>3. Tiedot tallennetaan 'TALLETA'-painikkeella.<br>Muista aina tallentaa tekemäsi muutokset ennenkuin vaihdat ohjelman osiota. Tiedot tallentuvat vain painiketta painamalla.<br><br>4. 'UUSI'-painikkeella voit luoda uuden tietueen. (Esim. uuden asiakkaan tiedot)<br><br>5. 'POISTA'-painikkeella hävitetään lomakkeen tiedot. Tiedot poistuvat lopullisesti.<br>KÄYTÄ HARKITEN!";
    break;
    case 'extsearch':
        $strHeadTitle = "Tarkka haku";
        $strTopicText =
            "Valitse listasta kentät, joista haluat haun suorittaa.(esim 'Sukunimi')<br><br>Valitse listasta tapa, jolla haku suoritetaan. Esim. 'on yhtä kuin' hakee hakusanaasi vastaavat tiedot ja 'on eri kuin'-valinta hakee tiedot joista ei löydy hakusanaa<br><br>Syötä tyhjään kenttään hakusana(t) tai valitse ehto listasta, joilla tietoa haetaan.(esim sukunimi tms.)<br><br>Haku suoritetaan 'ETSI'-painikkeesta.<br><br>Voit tallentaa hakuehdot myöhemmin nopeasti käytettäväksi pikahauksi 'TALLENNA HAKU'-painikkeella.(muista antaa pikahaulle kuvavaa nimi)";
    break;
    case 'quicksearch':
        $strHeadTitle = "Pikahaku";
        $strTopicText =
            "Suorita pikahaku klikkaamalla haluamasi haun nimeä<br><br>Voit poistaa turhat pikahaut painamalla nimen vieressä olevaa 'X'-painiketta<br><br>Pikahakuja voit tallentaa tarkan haun kautta.";
    break;
    default :
        $strHeadTitle = "Aiheeseen liittyviä ohjeita ei löytynyt!";
    break;
}


echo htmlPageStart( _PAGE_TITLE_ );

echo '<div class="form_container">';
echo "<center><h1>".$strHeadTitle."</h1></center>";

echo "<p>".$strTopicText."</p>";

?>
<center>
<a class="actionlink" href="#" onclick="self.close(); return false;">SULJE</a>
</center>
</div>
</body>
</html>
