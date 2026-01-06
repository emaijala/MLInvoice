<?php

/**
 * Settings Manager
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

use MLInvoice\Database\Repository\SettingRepository;
use MLInvoice\Settings\SettingsDefinitions;
use Odan\Session\SessionInterface;

/**
 * Settings Manager
 *
 * @category MLInvoice
 * @package  MLInvoice\Config
 * @author   Ere Maijala <ere@labs.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://labs.fi/mlinvoice.eng.php
 */
class SettingsManager
{
    /**
     * Settings cache.
     *
     * The cache only lives for a single request to speed up repeated requests for a setting.
     *
     * @var array
     */
    protected array $settingsCache = [];

    /**
     * Constructor
     *
     * @param SessionInterface $session Session
     * @param SettingRepository $settingRepository Settings repository
     */
    public function __construct(
        protected SessionInterface $session,
        protected SettingRepository $settingRepository,
    ) {
    }

    /**
     * Get the value for a setting.
     *
     * @param string  $name    Setting
     * @param ?string $default Default if there's no default in known settings
     * @param bool    $noCache Whether to skip cache
     *
     * @return mixed
     */
    function get(string $name, mixed $default = null, bool $noCache = false): mixed
    {
        if (!$noCache && isset($settingsCache[$name])) {
            return $this->settingsCache[$name];
        }

        $settingDefinitions = SettingsDefinitions::getAll();

        if ($settingDefinitions[$name]['session'] ?? false) {
            return $this->session->get($name);
        }

        $value = $this->settingRepository->findOneByName($name)?->getValue() ?? $default;
        $value = match ($settingDefinitions['type'] ?? 'TEXT') {
            'INT' => (int)$value,
            'CURRENCY' => (float)$value,
            'PERCENT' => (float)$value,
            'CHECK' => (bool)$value,
            default => $value,
        };
        return $settingsCache[$name] = $value;
    }

    /**
     * Set the value for a setting.
     *
     * @param string $name  Setting
     * @param mixed  $value
     *
     * @return mixed
     */
    function set(string $name, mixed $value): void
    {
        $settingsCache[$name] = $value;
        if ($setting = $this->settingRepository->findOneByName($name)) {
            $setting->setValue((string)$value);
        } else {
            $setting = $this->settingRepository->createEntity()
                ->setName($name)
                ->setValue($value);
        }
        $this->settingRepository->persistEntity($setting);
    }
}
