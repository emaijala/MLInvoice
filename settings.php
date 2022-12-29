<?php
/**
 * Settings handling
 *
 * PHP version 7
 *
 * Copyright (C) Ere Maijala 2010-2021
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
require_once 'config.php';
require_once 'translator.php';

mb_internal_encoding(_CHARSET_);

/**
 * Get a value for a setting
 *
 * @param string $name    Setting
 * @param string $default Default if there's no default in known settings
 * @param bool   $noCache Whether to skip cache
 *
 * @return mixed
 */
function getSetting($name, $default = '', $noCache = false)
{
    // The cache only lives for a single request to speed up repeated requests for a setting
    static $settingsCache = [];
    if (!$noCache && isset($settingsCache[$name])) {
        return $settingsCache[$name];
    }

    include 'settings_def.php';

    if (isset($arrSettings[$name]) && isset($arrSettings[$name]['session'])
        && $arrSettings[$name]['session']
    ) {
        if (isset($_SESSION[$name])) {
            return $_SESSION[$name];
        }
    } else {
        $rows = dbParamQuery(
            'SELECT value from {prefix}settings WHERE name=?', [$name]
        );
        if ($rows) {
            $settingsCache[$name] = $rows[0]['value'];
            return $settingsCache[$name];
        }
    }
    $settingsCache[$name] = isset($arrSettings[$name])
        && isset($arrSettings[$name]['default'])
        ? condUtf8Decode($arrSettings[$name]['default']) : $default;

    return $settingsCache[$name];
}

/**
 * Set a value for a setting
 *
 * @param string $name  Setting
 * @param string $value Value
 *
 * @return void
 */
function setSetting(string $name, string $value)
{
    dbParamQuery('DELETE FROM {prefix}settings WHERE name=?', [$name]);
    dbParamQuery(
        'INSERT INTO {prefix}settings (name, value) VALUES (?, ?)',
        [$name, $value]
    );
    // Flush cache:
    getSetting($name, '', true);
}
