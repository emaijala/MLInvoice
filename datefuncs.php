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

//setlocale(LC_TIME, 'fi-FI');

/********************************************************************
Includefile : datefuncs.php
    Date related functions

Provides functions : 
    
    
Includes files: -none-

Todo : 
    
********************************************************************/

function dateConvIntDate2Date( $intDate ) {
/********************************************************************
Function : dateConvIntDate2Date
    Converts date from intdate-format to readable format

Args : 
    $intDate (int): date in intdate-format

Return : $strDate (string) : date in readable-format (dd.mm.yyyy)

Todo : might want to check the dateformat before substracting
********************************************************************/
    $strDate = '';
    if( $intDate ) {
        $strDate = 
            substr($intDate, -2).".".
            substr($intDate, -4, 2).".".
            substr($intDate, 0, 4);
    }
    return $strDate;
}

function dateConvDate2IntDate( $strDate ) {
/********************************************************************
Function : dateConvIntDate2Date
    Converts date from intdate-format to readable format

Args : 
    $strDate (string) : date in readable-format (dd.mm.yyyy)

Return : $intDate (int): date in intdate-format

Todo : might want to check the dateformat before substracting
********************************************************************/
    $astrTmp = explode ( ".", $strDate );
    if (count($astrTmp) < 3)
      return $astrTmp[0];
    if( $astrTmp[0] < 10 && strlen($astrTmp[0]) == 1 ) {
        $astrTmp[0] = "0". $astrTmp[0];
    }
    if( $astrTmp[1] < 10 && strlen($astrTmp[1]) == 1 ) {
        $astrTmp[1] = "0". $astrTmp[1];
    }
    $intDate = $astrTmp[2];
    $intDate .= $astrTmp[1];
    $intDate .= $astrTmp[0];
 
    return $intDate;
}

function dateIsLeapYear( $intYear ) {
/********************************************************************
Function : dateIsLeapYear
    Check if year is leapyear or not

Args : 
    $intYear (int) : year (yyyy)

Return : $blnIsLeapYear (boolean): is the year leapyear

Todo : might want to check the year
********************************************************************/

    /*If the year is before 1601&nbspAD then; if the number of the year can be divided by four without leaving any remainder, it is a leap year and, if it can't, then it isn't a leap year*/
    if( $intYear < 1601 ) {
        if( $intYear % 4 == 0 ) {
            $blnIsLeapYear = TRUE;
        }
        else {
            $blnIsLeapYear = FALSE;
        }
    }
    else {
        //otherwise
        
        /*If the number of the year can be divided by 400 without leaving any remainder; it is a leap year*/
        if( $intYear % 400 == 0 ) {
            $blnIsLeapYear = TRUE;
        }
        
        //otherwise
        
        /*If the number of the year can be divided by 100 without leaving any remainder; it is not a leap year*/
        elseif( $intYear % 100 == 0 ) {
            $blnIsLeapYear = FALSE;
        }
        
        //otherwise
        
        /*If the number of the year can be divided by four without leaving any remainder; it is a leap year*/
        elseif( $intYear % 4 == 0 ) {
            $blnIsLeapYear = TRUE;
        }
        
        //otherwise
        //not a leap year
        else {
            $blnIsLeapYear = FALSE;
        }
    }
    
    return $blnIsLeapYear;
}

function dateGetWeekDayNumber( $intYear, $intMonth, $intDate ) {
/********************************************************************
Function : dateGetWeekDayNumber
    returns number of weekday (1-7) of given year, month & date

Args : 
    $intYear (int) : year
    $intMonth (int) : month
    $intDate (int) : date

Return : $intWeekDay (int): number of weekday

Todo : ?php
********************************************************************/
    
    //month values for calculation
    //leap years are different
    if( dateIsLeapYear( $intYear ) ) {
        $aintMonthValues = array(0,6,2,3,6,1,4,6,2,5,0,3,5);
    }
    else {
        $aintMonthValues = array(0,0,3,3,6,1,4,6,2,5,0,3,5);
    }
    
    //Divide the year by 4 and ignore any remainder. Add this result onto the year
    $tmpCalc = $intYear + floor($intYear / 4);
    //Divide the year by 100, ignore any remainder and subtract this from the total
    $tmpCalc -= floor($intYear / 100);
    //Divide the year by 400, ignore any remainder and add this to the total
    $tmpCalc += floor($intYear / 400);
    //Add the date (day of the month) to total
    $tmpCalc += $intDate;
    //Add the month value from the table
    $tmpCalc += $aintMonthValues[$intMonth];
    //Subtract 1 from the total
    $tmpCalc -= 1;
    //Divide by seven, and the remainder gives you the day of the week (starting with Monday as day 1):
    $tmpCalc = $tmpCalc % 7;
    //we in finland use 7 as sunday...
    if( $tmpCalc == 0 ) {
        $intWeekDay = 7;
    }
    else {
        $intWeekDay = $tmpCalc;
    }
    
    return $intWeekDay;
}


function dateGetWeekNumber( $intYear, $intMonth, $intDate ) {

        $intWeeknumber = strftime("%V", mktime(0,0,0,$intMonth, $intDate,$intYear));
        //echo "check :  $intDate.$intMonth.$intYear  == week : $intWeeknumber<br>";
        if( $intWeeknumber == 1 && $intMonth == 12) {
            $intWeeknumber = "1*";
        }
        
        return $intWeeknumber;
}

?>