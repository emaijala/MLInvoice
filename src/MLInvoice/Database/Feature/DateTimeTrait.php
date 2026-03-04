<?php

/**
 * Trait providing date handling support functions.
 *
 * PHP version 8
 *
 * Copyright (C) Ere Maijala 2026.
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
 * along with this program; if not, see
 * <https://www.gnu.org/licenses/>.
 *
 * @category MLInvoice
 * @package  Database
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:database_gateways Wiki
 */

namespace MLInvoice\Database\Feature;

use DateTime;
use DateTimeZone;

/**
 * Trait providing date handling support functions.
 *
 * @category MLInvoice
 * @package  Database
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:database_gateways Wiki
 */
trait DateTimeTrait
{
    /**
     * Get a date or null from database format.
     *
     * @param DateTime $date Date
     *
     * @return ?DateTime
     */
    protected function getDateTimeFromDbFormat(int $date): ?DateTime
    {
        return null !== $date ? DateTime::createFromFormat(MLINVOICE_DATABASE_DATETIME_FORMAT, (string)$date) : null;
    }

    /**
     * Get database format or null from DateTime.
     *
     * @param ?DateTime $date Date
     *
     * @return ?int
     */
    protected function getDbFormatFromDateTime(?DateTime $date): ?int
    {
        return null !== $date ? (int)$date?->format(MLINVOICE_DATABASE_DATETIME_FORMAT) : null;
    }

    /**
     * Return a clone for DateTime, or null if provided null.
     *
     * @param ?DateTime $dateTime DateTime or null
     *
     * @return ?DateTime
     */
    protected function getDateTimeClone(?DateTime $dateTime): ?DateTime
    {
        return $dateTime ? clone $dateTime : null;
    }
}
