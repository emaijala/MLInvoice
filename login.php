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

require "sqlfuncs.php";
require "miscfuncs.php";
require "htmlfuncs.php";
require "sessionfuncs.php";

require "localize.php";

//phpinfo();

$strLogin = $_POST['flogin'] ? $_POST['flogin'] : FALSE;
$strPasswd = $_POST['fpasswd'] ? $_POST['fpasswd'] : FALSE;
$strMode = $_POST['mode'] ? $_POST['mode'] : "normal";
$blnSubmit = (int)$_POST['logon_alt'] ? TRUE : FALSE;

if( $blnSubmit ) {
    if( $strLogin && $strPasswd ) {
        if ( sesValidateUser( $strLogin, $strPasswd ) ) {
            if( $strSesID = sesCreateSession() ) {
                $strFrset = "frset.php";
                
                header("Location: ". _PROTOCOL_ . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/". $strFrset. "?ses=".$strSesID);
                exit;
            }
            else {
                $strMessage = 
                    "<p>Hmmm, t&auml;m&auml;p&auml; mielenkiintoista. Kertoisitko meillekin mit&auml; teit...<p>\n";
            }
        }
        else {
            $strMessage = 
                "<p>Valitettavasti emme voi laskea sinua sis&auml;&auml;n systeemiin.<p>\n";
        }
        }
    else {
        $strMessage = 
            "<p>Ole hyv&auml; ja sy&ouml;t&auml; kaikki tiedot.<p>\n".
            "<p>Please enter all fields.<p>\n";
    }
}
echo htmlPageStart( _PAGE_TITLE_ );

?>

<body class="form" onload="<?php echo $strOnLoad?>" style="margin: 30px;">

<?php
if( !$strMessage ) {
?>

<h1>Tervetuloa</h1>
<p>
Ole hyv&auml; ja sy&ouml;t&auml; tunnuksesi ja salasanasi ja kirjaudu klikkaamalla "KIRJAUDU"-painiketta.
</p>

<form action="login.php" method="post" name="login_form">
Tunnus <input class="medium" name="flogin" type="text" value=""><br><br>
Salasana <input class="medium" name="fpasswd" type="password" value=""><br><br>

<input type="hidden" name="logon_alt" value="1">
<input type="submit" name="logon" value="KIRJAUDU">
</form>
<?php
}

else {
    echo $strMessage;
}
?>

<br/><br/>
<?php echo $GLOBALS['locLICENSENOTIFY']?>
<br/><br/>
<?php echo $GLOBALS['locCREDITS']?>
<br/><br/>

</body>
</html>
