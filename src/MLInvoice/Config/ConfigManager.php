<?php

/**
 * Configuration Manager
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category MLInvoice
 * @package  MLInvoice\Config
 * @author   Ere Maijala <ere@labs.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://labs.fi/mlinvoice.eng.php
 */

declare(strict_types=1);

namespace MLInvoice\Config;

/**
 * Configuration Manager
 *
 * @category MLInvoice
 * @package  MLInvoice\Config
 * @author   Ere Maijala <ere@labs.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://labs.fi/mlinvoice.eng.php
 */
class ConfigManager implements ConfigManagerInterface
{
    /**
     * Get configuration by name.
     *
     * @param string $configName Configuration name (without file extension)
     */
    public function get(string $configName): array
    {
        $configFile = MLINVOICE_BASE_DIR . "/local/config/$configName.ini";
        if (!file_exists($configFile)) {
            $configFile = MLINVOICE_BASE_DIR . "/config/$configName.ini";
        }
        return parse_ini_file($configFile, true) ?: [];
    }
}
