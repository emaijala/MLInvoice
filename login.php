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

T‰m‰ ohjelma on vapaa. Lue oheinen LICENSE.

*******************************************************************************/

require "sqlfuncs.php";
require "miscfuncs.php";
require "htmlfuncs.php";
require "sessionfuncs.php";

require "localize.php";

//phpinfo();

$strLogin = getPost('flogin', FALSE);
$strPasswd = getPost('fpasswd', FALSE); 
$strMode = getPost('mode', 'normal');
$blnSubmit = getPost('logon_alt', FALSE) ? TRUE : FALSE;

$strMessage = 'Ole hyv&auml; ja sy&ouml;t&auml; tunnuksesi ja salasanasi ja kirjaudu klikkaamalla "Kirjaudu"-painiketta.';

if( $blnSubmit ) {
    if( $strLogin && $strPasswd ) {
        if ( sesValidateUser( $strLogin, $strPasswd ) ) {
            if( $strSesID = sesCreateSession() ) {
                header("Location: ". _PROTOCOL_ . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/index.php?ses=$strSesID");
                exit;
            }
            else {
                $strMessage = 
                    "<p>Hmmm, t&auml;m&auml;p&auml; mielenkiintoista. Kertoisitko meillekin mit&auml; teit...<p>\n";
            }
        }
        else {
            $strMessage = 
                "<p>K‰ytt‰j‰tunnus tai salasana v‰‰r‰.<p>\n";
        }
        }
    else {
        $strMessage = 
            "<p>Ole hyv&auml; ja sy&ouml;t&auml; kaikki tiedot.<p>\n";
    }
}


echo htmlPageStart( _PAGE_TITLE_ );
?>

<body style="margin: 30px;" onload="document.getElementById('flogin').focus();">

<h1>Tervetuloa</h1>
<p><?php echo $strMessage?>
</p>

<form action="login.php" method="post" name="login_form">
Tunnus <input class="medium" name="flogin" id="flogin" type="text" value=""><br><br>
Salasana <input class="medium" name="fpasswd" type="password" value=""><br><br>

<input type="hidden" name="logon_alt" value="1">
<input type="submit" name="logon" value="Kirjaudu">
</form>

<br/><br/>
<?php echo $GLOBALS['locLICENSENOTIFY']?>
<br/><br/>
<?php echo $GLOBALS['locCREDITS']?>
<br/><br/>

</body>
</html>
