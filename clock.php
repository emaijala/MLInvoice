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

require "htmlfuncs.php";
require "sqlfuncs.php";
require "sessionfuncs.php";
require "miscfuncs.php";
require "datefuncs.php";

$strSesID = sesVerifySession();


require "localize.php";

setlocale(LC_TIME, "fi_FI");

$strField = $_REQUEST['timefield'] ? $_REQUEST['timefield'] : $_POST['timefield'];

$intCurrentHour = date("H");
$intCurrentMinute = date("i");
//todo : do quarterhour system...

if( $intCurrentMinute < 15 ) {
    $intCurrentMinute = 15;
}
elseif( $intCurrentMinute < 30 ) {
    $intCurrentMinute = 30;
}
elseif( $intCurrentMinute < 45 ) {
    $intCurrentMinute = 45;
}
else {
    $intCurrentMinute = 00;
}

for( $i = 0; $i < 24; $i++) {
    $strHour = $i + 1;
    if( strlen($strHour) == 1 ) {
       $strHour = "0". $strHour;
    }
    
    $astrHourListValues[$i] = $strHour;
    $astrHourListOptions[$i] = $strHour;
}
$strHourListBox = htmlListBox( "hh", $astrHourListValues, $astrHourListOptions, $intCurrentHour, "normal", FALSE, FALSE );

$astrMinuteListValues = array('00','15','30','45');
$astrMinuteListOptions = array('00','15','30','45');

$strMinuteListBox = htmlListBox( "mm", $astrMinuteListValues, $astrMinuteListOptions, $intCurrentMinute, "normal", FALSE, FALSE );

echo htmlPageStart( _PAGE_TITLE_ );
?>
<script type="text/javascript">
<!--
function SetTime() {
    //alert(opener.document.forms[0].<?php echo $strField?>.value);
    strtime = this.document.clock_form.hh.options[this.document.clock_form.hh.selectedIndex].value + ':' + this.document.clock_form.mm.options[this.document.clock_form.mm.selectedIndex].value
    
    opener.document.forms[0].<?php echo $strField?>.value = strtime;
    self.close();
    return false;
}
-->
</script>
<body class="form" onload="<?php echo $strOnLoad?>">
<form method="post" action="calendar.php?ses=<?php echo $GLOBALS['sesID']?>" target="_self" name="clock_form">
<input type="hidden" name="datefield" value="<?php echo $strField?>">
<center>
<?php echo $strHourListBox?> : <?php echo $strMinuteListBox?>
<br><br>
<a class="actionlink" href="#" onclick="SetTime(); return false;"><?php echo $GLOBALS['locSETTIME']?></a>
<a class="actionlink" href="#" onclick="self.close(); return false;"><?php echo $GLOBALS['locCLOSE']?></a>
</center>
</form>
</body>
</html>
