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
Includefile : miscfuncs.php
    Miscallenous functions

Includes files: -none-

Todo : 
    sort functions, move funcs to appropriate files.
    Check function todo's
********************************************************************/    

function gpcAddSlashes( $strString ) {
   if ( !get_magic_quotes_gpc() )
       $strString = addslashes($strString);
   return $strString;
}

function gpcStripSlashes($strString) {
   if ( get_magic_quotes_gpc() )
       $strString = stripslashes($strString);
   return $strString;
}

function miscRound2Decim( $intValue, $intDecim = 2 ) {
    //$tmpValue = MyRound( $intValue, strlen($intValue) ,2);
    //$tmpValue = !strstr($tmpValue, ".") ? $tmpValue . ".00" : $tmpValue;
    //$tmpValue = str_replace(".", ",", $tmpValue);
    $tmpValue = number_format($intValue, $intDecim, ',', '');
    return $tmpValue;
}

function miscCalcCheckNo( $intValue ) {
    $astrWeight = array(
        '1','3','7','1','3','7','1','3','7','1','3','7','1','3','7',
        '1','3','7','1','3','7','1','3','7','1','3','7','1','3','7','1','3','7',
        '1','3','7','1','3','7','1','3','7','1','3','7','1','3','7','1','3','7',
        '1','3','7','1','3','7',
        '1','3','7','1','3','7','1','3','7','1','3','7','1','3','7',
        '1','3','7','1','3','7','1','3','7','1','3','7','1','3','7','1','3','7',
        '1','3','7','1','3','7','1','3','7','1','3','7','1','3','7','1','3','7',
        '1','3','7','1','3','7');
    $astrTmp = array_reverse(explode(".",substr(chunk_split($intValue, 1, '.'), 0, -1)));

    $intSum = 0;
    foreach ($astrTmp as $value) {
        $intSum += $value * array_pop($astrWeight);
    }
    $intCheckNo = ceil($intSum/10)*10 - $intSum;
    
    //echo "value : $intValue -- sum : $intSum -- check : $intCheckNo";
    
    return $intCheckNo;
}

function getPost($strKey, $varDefault)
{
  return isset($_POST[$strKey]) ? $_POST[$strKey] : $varDefault;
}

function getRequest($strKey, $varDefault)
{
  return isset($_REQUEST[$strKey]) ? $_REQUEST[$strKey] : $varDefault;
}

function getGet($strKey, $varDefault)
{
  return isset($_GET[$strKey]) ? $_GET[$strKey] : $varDefault;
}

function getPostRequest($strKey, $varDefault)
{
  return getPost($strKey, getRequest($strKey, $varDefault));
}

?>
