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

if( $strSesID ) {
    if( !sesCheckSession( $strSesID ) ) {
        die;
    }
}

require "localize.php";

setlocale(LC_TIME, "fi_FI");
$strField = getRequest('datefield', '');
$blnDoSubmit = getRequest('dosubmit', FALSE);
$blnPrevious = getRequest('prev', FALSE) ? TRUE : FALSE;
$blnNext = getRequest('forw', FALSE) ? TRUE : FALSE;
$strDate = getRequest('date', date('d.m.Y'));
$tmpValues = explode('.', $strDate);

$intDate = getRequest('dd', $tmpValues[0]);
$intMonth = getRequest('mm', $tmpValues[1]);
$intYear =  getRequest('yy', $tmpValues[2]);

$intCurrentYear = date("Y");
$intCurrentMonth = date("m");
$intCurrentDate = date("d");

$blnShowWeekNo = FALSE;

if( $blnPrevious ) {
    if( $intMonth == 1 ) {
        $intYear--;
        $intMonth = 12;
    }
    else {
        $intMonth--;
    }
}
if( $blnNext ) {
    if( $intMonth == 12 ) {
        $intYear++;
        $intMonth = 1;
    }
    else {
        $intMonth++;
    }
}

if( dateIsLeapYear( $intYear ) ) {
    $aintDaysPerMonth = array (0,31,29,31,30,31,30,31,31,30,31,30,31);
}
else {
    $aintDaysPerMonth = array (0,31,28,31,30,31,30,31,31,30,31,30,31);
}

$intDaysInMonth = $aintDaysPerMonth[$intMonth];

$intDayofWeek = dateGetWeekDayNumber( $intYear, $intMonth, 1 );

//$intDayofWeek = strftime("%u", mktime(0, 0, 0, $intMonth, 1, $intYear));

for( $i = 0; $i <= 80; $i++) {
    $astrYearListValues[$i] = ($intCurrentYear+10)-$i;
    $astrYearListOptions[$i] = ($intCurrentYear+10)-$i;
}
$strYearListBox = htmlListBox( "yy", $astrYearListValues, $astrYearListOptions, $intYear, "", TRUE, FALSE );

for( $i = 0; $i < 12; $i++) {
    $astrMonthListValues[$i] = $i+1;
    $astrMonthListOptions[$i] = $GLOBALS['locMONTHS'][$i];
}
$strMonthListBox = htmlListBox( "mm", $astrMonthListValues, $astrMonthListOptions, $intMonth, "", TRUE, FALSE );

echo htmlPageStart( _PAGE_TITLE_ );
?>
<script type="text/javascript">
<!--
function SetDate(strdate) {
    //alert(opener.document.forms[0].<?php echo $strField?>.value);
    opener.document.forms[0].<?php echo $strField?>.value = strdate;
    //alert()
    if( self.document.forms[0].dosubmit.value == 1 ) {
        opener.document.forms[0].submit();
    }
    self.close();
    return false;
}
-->
</script>
<body class="form">
<form method="post" action="calendar.php?ses=<?php echo $GLOBALS['sesID']?>" target="_self" name="cal_form">
<input type="hidden" name="datefield" value="<?php echo $strField?>">
<input type="hidden" name="dosubmit" value="<?php echo $blnDoSubmit?>">
<center>
<table>
<tr>
    <td>
        <input type="hidden" name="prev" value="0">
        <a class="tinyactionlink" href="#" onclick="self.document.forms[0].prev.value=1; self.document.forms[0].submit(); return false;"> < </a>
    </td>
    <td>
    <?php echo $strMonthListBox?>
    </td>
    <td>
    <?php echo $strYearListBox?>
    </td>
    <td>
        <input type="hidden" name="forw" value="0">
        <a class="tinyactionlink" href="#" onclick="self.document.forms[0].forw.value=1; self.document.forms[0].submit(); return false;"> > </a>
    </td>
</tr>
</table>
<table border="1">
<tr>
<?php
if( $blnShowWeekNo ) {
?>
<td class="calweekday">
        &nbsp;
    </td>
<?php
}
//show short daynames
for( $i = 0; $i < 7; $i++ ) {
?>
    <td class="calweekday">
        <?php echo $GLOBALS['locWEEKDAYSSHORT'][$i]?>
    </td>
<?php
}
?>
</tr>

<tr>
<?php
$blnStartWeek = TRUE;
$blnStartMonth = TRUE;
for( $i = 1; $i <= $intDaysInMonth; $i++ ) {
    $tmpDate = $i. ".". $intMonth. ".". $intYear;
    if( $blnStartWeek ) {
        $blnStartWeek = FALSE;
        $strWeekNo = date("W", mktime(0, 0, 0, $intMonth, $i, $intYear));
        if( $blnShowWeekNo ) {
?>
    <td class="calweekday">
        <?php echo $strWeekNo?>
    </td>
<?php
        }
    }
    //print "empty" days
    if ( $blnStartMonth ) {
        $blnStartMonth = FALSE;
        $x = $intDayofWeek;
        for( $j = 1; $j < $intDayofWeek; $j++ ) {
?>
    <td class="calemptyday">
        &nbsp;
    </td>
<?php
        }
    }
    if( $i == $intCurrentDate ) {
        $strClass = "calcurrentdate";
    }
    else {
        $strClass = "caldate";
    }
?>
    <td class="<?php echo $strClass?>">
        <a href="calendar.php" class="callink" title="<?php echo $tmpDate?>" onClick="SetDate('<?php echo $tmpDate?>'); return false;"><?php echo $i?></a>
    </td>
<?php
    if( $x == 7 ) {
        $blnStartWeek = TRUE;
        $x = 1;
        echo "</tr>\n<tr>\n";
    }
    else {
        $x++;
    }
    
}
$x = $x == 1 ? 8 : $x;
$intEmptyDaysLeft = 8 - $x;
for( $j = 0; $j < $intEmptyDaysLeft; $j++ ) {
?>
    <td class="calemptyday">
        &nbsp;
    </td>
<?php
}
?>
</tr>
<?php

?>
</table>
<a class="actionlink" href="#" onclick="self.close(); return false;"><?php echo $GLOBALS['locCLOSE']?></a>
</center>
</form>
</body>
</html>
