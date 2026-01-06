<?php
/**
 * Date Utilities.
 *
 * PHP version 8
 *
 * Copyright (C) Ere Maijala 2010-2026.
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
 * @package  MLInvoice\Utils
 * @author   Ere Maijala <ere@labs.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://labs.fi/mlinvoice.eng.php
 */

declare(strict_types=1);

namespace MLInvoice\Utils;

/**
 * Date Utilities.
 *
 * @category MLInvoice
 * @package  MLInvoice\Utils
 * @author   Ere Maijala <ere@labs.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://labs.fi/mlinvoice.eng.php
 */
class DateUtils
{
    /**
     * Constructor
     *
     * @param string $dateFormat Date format
     */
    public function __construct(protected string $dateFormat)
    {
    }

    /**
     * Convert Y-m-d date to database format
     *
     * @param string $date Date
     *
     * @return ?int
     */
    public function ymdToDbDate(string $date): ?int
    {
        $arr = date_parse_from_format('Y-m-d', $date);
        if (!$arr['year'] || !$arr['month'] || !$arr['day']
            || !empty($arr['warnings'])
        ) {
            return null;
        }
        if ($arr['year'] < 100) {
            $arr['year'] += 2000;
        }
        return (int)sprintf('%04d%02d%02d', $arr['year'], $arr['month'], $arr['day']);
    }

    /**
     * Convert database date to user-readable
     *
     * @param int    $date    Date in database format
     * @param string $format  Date format (optional)
     *
     * @return string
     */
    function dbDateToDate($date, $format = '')
    {
        if (!$date) {
            return '';
        }
        $day = intval(substr($date, 6));
        $mon = intval(substr($date, 4, 2));
        $year = intval(substr($date, 0, 4));
        return date(
            $format ?: Translator::translate('DateFormat'),
            mktime(0, 0, 0, $mon, $day, $year)
        );
    }
}
