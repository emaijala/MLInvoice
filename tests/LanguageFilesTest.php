<?php
/**
 * Language files tests
 *
 * PHP version 7
 *
 * Copyright (C) Ere Maijala 2022.
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

/**
 * Language files tests
 *
 * @category MLInvoice
 * @package  MLInvoice\Test
 * @author   Ere Maijala <ere@labs.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://labs.fi/mlinvoice.eng.php
 */
final class LanguageFilesTest extends TestCase
{
    /**
     * Check that all language files contain all translations
     *
     * @return void
     */
    public function testLanguageFiles()
    {
        $domains = [
            '',
            'invoice_',
            'offer_',
            'order_confirmation_',
        ];
        $languages = [
            'fi-FI',
            'en-US',
            'sv-FI',
        ];
        foreach ($domains as $domain) {
            $files = [];
            foreach ($languages as $lng) {
                $files[$lng] = parse_ini_file(__DIR__ . "/../lang/$domain$lng.ini");
            }
            $keys = array_keys($files);
            $primary = reset($languages);
            $primaryTranslations = $files[$primary];
            foreach ($keys as $key) {
                foreach (array_keys($primaryTranslations) as $translation) {
                    $this->assertArrayHasKey(
                        $translation,
                        $files[$key],
                        "$domain$primary: translation $translation not in $key"
                    );
                }
                foreach (array_keys($files[$key]) as $translation) {
                    $this->assertArrayHasKey(
                        $translation,
                        $primaryTranslations,
                        "$domain$key: translation $translation not in $primary"
                    );
                }
            }
        }
    }
}
