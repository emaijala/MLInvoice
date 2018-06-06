<?php
/**
 * Date handling functions
 *
 * PHP version 5
 *
 * Copyright (C) 2010-2018 Ere Maijala
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category MLInvoice
 * @package  MLInvoice\Base
 * @author   Ere Maijala <ere@labs.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://labs.fi/mlinvoice.eng.php
 */

/**
 * Convert database date to user-readable
 *
 * @param int    $intDate Date in database format
 * @param string $format  Date format (optional)
 *
 * @return string
 */
function dateConvDBDate2Date($intDate, $format = '')
{
    if (!$intDate) {
        return '';
    }
    $day = substr($intDate, 6);
    $mon = substr($intDate, 4, 2);
    $year = substr($intDate, 0, 4);
    return date(
        $format ? $format : Translator::translate('DateFormat'),
        mktime(0, 0, 0, $mon, $day, $year)
    );
}

/**
 * Convert database timestamp to user-readable
 *
 * @param int    $dateTime Timestamp
 * @param string $format   Date format (optional)
 *
 * @return string
 */
function dateConvDBTimestamp2DateTime($dateTime, $format = '')
{
    if (!$dateTime) {
        return '';
    }
    return date(
        $format ? $format : Translator::translate('DateTimeFormat'),
        strtotime($dateTime)
    );
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
    $arr = date_parse_from_format(Translator::translate('DateFormat'), $strDate);
    if ($arr['error_count'] > 0) {
        return false;
    }
    return sprintf('%04d%02d%02d', $arr['year'], $arr['month'], $arr['day']);
}

/**
 * Convert user-readable date to unix time
 *
 * @param string $strDate Date
 *
 * @return int
 */
function strDate2UnixTime($strDate)
{
    $arr = date_parse_from_format(Translator::translate('DateFormat'), $strDate);
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
