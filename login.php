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

session_start();

$strLogin = getPost('flogin', FALSE);
$strPasswd = getPost('fpasswd', FALSE); 
$strLogon = getPost('logon', '');

$strMessage = $GLOBALS['locWELCOMEMESSAGE'];

if ($strLogon) 
{
    if ($strLogin && $strPasswd)
    {
        switch (sesCreateSession($strLogin, $strPasswd))
        {
        case 'OK':
            header("Location: ". _PROTOCOL_ . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/index.php");
            exit;
        case 'FAIL': 
            $strMessage = $GLOBALS['locINVALIDCREDENTIALS'];
            break;
        case 'TIMEOUT':
            $strMessage = $GLOBALS['locLOGINTIMEOUT'];
            break;
        }
    }
    else 
    {
        $strMessage = $GLOBALS['locMISSINGFIELDS'];
    }
}

$key = sesCreateKey();

echo htmlPageStart(_PAGE_TITLE_, array('jquery/js/jquery.md5.js'));
?>

<body style="margin: 30px;" onload="document.getElementById('flogin').focus();">

<h1><?php echo $GLOBALS['locWELCOME']?></h1>
<p>
  <?php echo $strMessage?>
  
</p>

<script type="text/javascript">  
function createHash() 
{  
  var pass_md5 = $.md5(document.getElementById('passwd').value);  
  var key = document.getElementById('key').value;  
  document.getElementById('fpasswd').value = $.md5(key + pass_md5);
  document.getElementById('passwd').value = '';
  document.getElementById('key').value = '';
}  
</script>  

<form action="login.php" method="post" name="login_form" onsubmit="createHash();">
  <input type="hidden" name="fpasswd" id="fpasswd" value="">
  <input type="hidden" name="key" id="key" value="<?php echo $key?>">
  <p>
    <span style="width: 90px; display: inline-block;">Tunnus</span> <input class="medium" name="flogin" id="flogin" type="text" value="">
  </p>
  <p>
    <span style="width: 90px; display: inline-block;">Salasana</span> <input class="medium" name="passwd" id="passwd" type="password" value="">
  </p>
  <input type="submit" name="logon" value="Kirjaudu">
</form>

<br>
<br>
<?php echo $GLOBALS['locLICENSENOTIFY']?>
<br>
<br>
<?php echo $GLOBALS['locCREDITS']?>
<br>
<br>

</body>
</html>
