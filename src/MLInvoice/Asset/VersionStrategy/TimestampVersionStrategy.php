<?php
/**
 * Timestamp-based Asset Version Strategy.
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
 * @package  MLInvoice\Asset
 * @author   Ere Maijala <ere@labs.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://labs.fi/mlinvoice.eng.php
 */

namespace MLInvoice\Asset\VersionStrategy;

use Symfony\Component\Asset\VersionStrategy\VersionStrategyInterface;

/**
 * Timestamp-based Asset Version Strategy.
 *
 * @category MLInvoice
 * @package  MLInvoice\Asset
 * @author   Ere Maijala <ere@labs.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://labs.fi/mlinvoice.eng.php
 */
class TimestampVersionStrategy implements VersionStrategyInterface
{
    /**
     * Get version.
     *
     * @param string $path Path
     *
     * @return string
     */
    public function getVersion(string $path): string
    {
        $file = MLINVOICE_BASE_DIR . "/assets/$path";
        return (string)(file_exists($file) ? (filemtime($file) ?: '0') : '0');
    }

    /**
     * Apply version to a path.
     *
     * @param string $path Path
     *
     * @return string
     */
    public function applyVersion(string $path): string
    {
        return sprintf('%s?v=%s', $path, $this->getVersion($path));
    }
}