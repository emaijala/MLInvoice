<?php
/**
 * Self-updating mechanism tests
 *
 * PHP version 5
 *
 * Copyright (C) Ere Maijala 2021.
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
 * @package  MLInvoice\Test
 * @author   Ere Maijala <ere@labs.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://labs.fi/mlinvoice.eng.php
 */

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/CallProtectedTrait.php';
require_once __DIR__ . '/../updater.php';

/**
 * Self-updating mechanism
 *
 * @category MLInvoice
 * @package  MLInvoice\Test
 * @author   Ere Maijala <ere@labs.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://labs.fi/mlinvoice.eng.php
 */
final class UpdaterTest extends TestCase
{
    use CallProtectedTrait;

    /**
     * Tests for compareVersionNumber
     *
     * @dataProvider testCompareVersionNumberDataProvider
     *
     * @return void
     */
    public function testCompareVersionNumber($v1, $v2, $expected)
    {
        $updater = new Updater();
        $this->assertEquals(
            $expected,
            $this->callProtected(
                $updater,
                'compareVersionNumber',
                [$v1, $v2]
            )
        );
    }

    /**
     * Data provider for compareVersionNumber tests
     *
     * @return array
     */
    public function testCompareVersionNumberDataProvider()
    {
        return [
            ['1.0.0', '1.0.0', 0],
            ['1.0.1', '1.0.0', 3],
            ['1.0.0', '1.0.1', -3],
            ['1.24.0', '1.23.0', 2],
            ['1.23.0', '1.24.0', -2],
            ['2.0.0', '1.24.0', 1],
            ['1.24.0', '2.0.0', -1],
            ['2.0.0', '2.0.0b1', 3],
            ['2.0.0b2', '2.0.0b1', 3]
        ];
    }
}
