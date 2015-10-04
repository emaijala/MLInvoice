<?php

/*******************************************************************************
 MLInvoice: web-based invoicing application.
 Copyright (C) 2010-2015 Ere Maijala
 
 This program is free software. See attached LICENSE.
 
 *******************************************************************************/

/*******************************************************************************
 MLInvoice: web-pohjainen laskutusohjelma.
 Copyright (C) 2010-2015 Ere Maijala
 
 Tämä ohjelma on vapaa. Lue oheinen LICENSE.
 
 *******************************************************************************/

/**
 * Convert database date to user-readable
 *
 * @param int $intDate Date in database format
 * @param string $format Date format (optional)
 *
 * @return string
 */
function dateConvDBDate2Date($intDate, $format = '')
{
    if (!$intDate)
        return '';
    $day = substr($intDate, 6);
    $mon = substr($intDate, 4, 2);
    $year = substr($intDate, 0, 4);
    return date($format ? $format : $GLOBALS['locDateFormat'], 
        mktime(0, 0, 0, $mon, $day, $year));
}

/**
 * Convert database timestamp to user-readable
 *
 * @param int $dateTime Timestamp
 * @param string $format Date format (optional)
 *
 * @return string
 */
function dateConvDBTimestamp2DateTime($dateTime, $format = '')
{
    if (!$dateTime) {
        return '';
    }
    return date($format ? $format : $GLOBALS['locDateTimeFormat'], 
        strtotime($dateTime));
}

/**
 * Convert user-readable date to database format
 *
 * @param string $strDate Date
 *
 * @return int
 */
function dateConvDate2DBDate($strDate)
{
    $arr = date_parse_from_format($GLOBALS['locDateFormat'], $strDate);
    if ($arr['error_count'] > 0) {
        return false;
    }
    return sprintf('%04d%02d%02d', $arr['year'], $arr['month'], $arr['day']);
}

/**
 * Convert user-readable date to unix time
 *
 * @param string $strDate
 *
 * @return int
 */
function strDate2UnixTime($strDate)
{
    $arr = date_parse_from_format($GLOBALS['locDateFormat'], $strDate);
    return mktime(0, 0, 0, $arr['month'], $arr['day'], $arr['year']);
}

/**
 * Convert database format to unix time
 *
 * @param int $date Date
 *
 * @return int
 */
function dbDate2UnixTime($date)
{
    $day = substr($date, 6);
    $mon = substr($date, 4, 2);
    $year = substr($date, 0, 4);
    return mktime(0, 0, 0, $mon, $day, $year);
}
