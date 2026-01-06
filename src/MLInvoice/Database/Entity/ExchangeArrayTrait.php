<?php

/**
 * Trait providing a basic implementation of ExchangeArrayInterface.
 *
 * PHP version 8
 *
 * Copyright (C) Villanova University 2025.
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
 * @category VuFind
 * @package  MLInvoice
 * @author   Padmasree Gade <pgade@villanova.edu>
 * @author   Ere Maijala <ere@labs.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:database_gateways Wiki
 */

namespace MLInvoice\Database\Entity;

/**
 * Trait providing a basic implementation of ExchangeArrayInterface.
 *
 * @category MLInvoice
 * @package  Database
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @author   Ere Maijala <ere@labs.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:database_gateways Wiki
 */
trait ExchangeArrayTrait
{
    /**
     * Populate entity data from an associative array.
     *
     * @param array $data Key-value pairs representing entity properties.
     *
     * @return void
     */
    public function exchangeArray(array $data): void
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }

    /**
     * Get an array representation of the entity.
     *
     * @param bool $convertToSnakeCase Convert the keys to snake_case ?
     *
     * @return array
     */
    public function toArray(bool $convertToSnakeCase = false): array
    {
        $result = array_map(
            function ($value) {
                return ($value instanceof ExchangeArrayInterface)
                    ? $value->toArray()
                    : $value;
            },
            get_object_vars($this)
        );
        if ($convertToSnakeCase) {

        }
        return $convertToSnakeCase ? $this->convertToSnakeCase($result) : $result;
    }

    /**
     * Convert an array's keys to snake_case.
     *
     * @param array $array Array
     *
     * @return array
     */
    protected function convertToSnakeCase(array $array): array
    {
        $result = [];
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $value = $this->convertToSnakeCase($value);
            }
            $key = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $key));
            $result[$key] = $value;
        }
        return $result;
    }
}
