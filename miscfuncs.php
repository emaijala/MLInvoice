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
       return addslashes($strString);
   return $strString;
}

function gpcStripSlashes($strString) {
   if ( get_magic_quotes_gpc() )
       return stripslashes($strString);
   return $strString;
}


function cond_utf8_encode($str)
{
  if (_CHARSET_ == 'UTF-8')
    return utf8_encode($str);
  return $str;
}

function miscRound2Decim( $intValue, $intDecim = 2 ) {
    return number_format($intValue, $intDecim, ',', '');
}

function miscRound2OptDecim( $intValue, $intDecim = 2 ) {
    if ($intValue == floor($intValue))
      $intDecim = 0;
    return number_format($intValue, $intDecim, ',', '');
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
  return isset($_POST[$strKey]) ? gpcStripSlashes($_POST[$strKey]) : $varDefault;
}

function getRequest($strKey, $varDefault)
{
  return isset($_REQUEST[$strKey]) ? gpcStripSlashes($_REQUEST[$strKey]) : $varDefault;
}

function getGet($strKey, $varDefault)
{
  return isset($_GET[$strKey]) ? gpcStripSlashes($_GET[$strKey]) : $varDefault;
}

function getPostRequest($strKey, $varDefault)
{
  return getPost($strKey, getRequest($strKey, $varDefault));
}

function getPageTitle($strFunc, $strList, $strForm)
{
  switch($strFunc)
  {
  case 'open_invoices':
    if ($strForm)
      return $GLOBALS['locINVOICE'];
    else
      return $GLOBALS['locOPENANDUNPAIDINVOICES'];
    break;
  case 'invoices':
    if ($strForm)
      return $GLOBALS['locINVOICE'];
    else
      return $GLOBALS['locINVOICES'];
    break;
  case 'archived_invoices':
    if ($strForm)
      return $GLOBALS['locINVOICE'];
    else
      return $GLOBALS['locARCHIVEDINVOICES'];
    break;
  case 'companies':
    if ($strForm)
      return $GLOBALS['locCOMPANY'];
    else
      return $GLOBALS['locCOMPANIES'];
    break;
  case 'reports':
    switch ($strForm)
    {
    case 'invoice': return $GLOBALS['locINVOICEREPORT']; 
    case 'product': return $GLOBALS['locPRODUCTREPORT']; 
    default: return $GLOBALS['locREPORTS']; 
    }
    break;
  case 'settings':
    if ($strForm)
    {
      switch ($strForm)
      {
      case 'base_info': return $GLOBALS['locBASE']; 
      case 'invoice_state': return $GLOBALS['locINVOICESTATE']; 
      case 'product': return $GLOBALS['locPRODUCT'];
      case 'row_type': return $GLOBALS['locROWTYPE']; 
      default: return $GLOBALS['locSETTINGS'];
      }
    }
    else
    {
      switch ($strList)
      {
      case 'base_info': return $GLOBALS['locBASES']; 
      case 'invoice_state': return $GLOBALS['locINVOICESTATES']; 
      case 'product': return $GLOBALS['locPRODUCTS']; 
      case 'row_type': return $GLOBALS['locROWTYPES']; 
      default: return $GLOBALS['locSETTINGS'];
      }
    }
    break;
  case 'system':
    if ($strForm)
    {
      switch ($strForm)
      {
      case 'user': return $GLOBALS['locUSER'];
      case 'session_type': return $GLOBALS['locSESSIONTYPE']; 
      default: return $GLOBALS['locSYSTEM'];
      }
    }
    else
    {
      switch ($strList)
      {
      case 'user': return $GLOBALS['locUSERS'];
      case 'session_type': return $GLOBALS['locSESSIONTYPES'];
      default: return $GLOBALS['locSYSTEM'];
      }
    }
    break;
  }
  return '';
}

?>
