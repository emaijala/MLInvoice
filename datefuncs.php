<?php
/*******************************************************************************
VLLasku: web-based invoicing application.
Copyright (C) 2010-2012 Ere Maijala

This program is free software. See attached LICENSE.

*******************************************************************************/

/*******************************************************************************
VLLasku: web-pohjainen laskutusohjelma.
Copyright (C) 2010-2012 Ere Maijala

Tämä ohjelma on vapaa. Lue oheinen LICENSE.

*******************************************************************************/

function dateConvDBDate2Date($intDate) 
{
  if (!$intDate)
    return '';
  $day = substr($intDate, 6);
  $mon = substr($intDate, 4, 2);
  $year = substr($intDate, 0, 4);
  return "$day.$mon.$year";
}

function dateConvDate2DBDate($strDate) 
{
  $arr = explode('.', $strDate);
  if (count($arr) < 3)
    return $arr[0];
  return sprintf('%04d%02d%02d', (int)$arr[2], (int)$arr[1], (int)$arr[0]);
}

// Convert the pretty date to unix time
function strDate2UnixTime($strDate) 
{
    $arr = explode('.', $strDate);
    if (count($astrTmp) < 3)
      return 0;
    return mktime(0, 0, 0, $arr[1], $arr[0], $arr[2]); 
}

?>