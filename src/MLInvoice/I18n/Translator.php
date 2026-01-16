<?php
/**
 * Translator
 *
 * PHP version 8
 *
 * Copyright (C) Ere Maijala 2017-2026.
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
 * @package  MLInvoice\I18n
 * @author   Ere Maijala <ere@labs.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://labs.fi/mlinvoice.eng.php
 */

namespace MLInvoice\I18n;

use DI\Attribute\Inject;
use Odan\Session\SessionInterface;
use Odan\Session\SessionManagerInterface;

/**
 * Translator
 *
 * @category MLInvoice
 * @package  MLInvoice\I18n
 * @author   Ere Maijala <ere@labs.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://labs.fi/mlinvoice.eng.php
 */
class Translator
{
    /**
     * Storage for translations
     *
     * @var array
     */
    protected array $translations = [];

    /**
     * Any active languages for domains
     *
     * @var array
     */
    protected array $activeLanguages = [];

    /**
     * Constructor
     *
     * @param SessionInterface $session Session
     */
    public function __construct(
        protected SessionInterface $session,
    ) {
    }

    /**
     * Translate a string
     *
     * @param string $str          String to translate
     * @param array  $placeholders Any key/value pairs to replace in the translation
     * @param string $default      Optional default value if translation doesn't
     *                             exist
     *
     * @return string
     */
    public function translate(string $str, array $placeholders = [], ?string $default = null)
    {
        $domain = 'default';
        $p = strpos($str, '::');
        if (false !== $p) {
            $domain = substr($str, 0, $p);
            $str = substr($str, $p + 2);
        }

        if (empty($str)) {
            return $str;
        }

        if (!isset($this->translations[$domain])) {
            $this->loadTranslations($domain);
        }
        if (isset($this->translations[$domain][$str])) {
            $str = $this->translations[$domain][$str];
        } elseif (null !== $default) {
            $str = $default;
        }
        if ($placeholders) {
            $str = str_replace(
                array_keys($placeholders), array_values($placeholders), $str
            );
        }
        return $str;
    }

    /**
     * Get active language for a domain
     *
     * @param string $domain Translation domain
     *
     * @return string
     */
    public function getActiveLanguage($domain)
    {
        return $this->activeLanguages[$domain] ?? '';
    }

    /**
     * Set active language for a domain
     *
     * @param string $domain   Translation domain
     * @param string $language Language
     *
     * @return void
     */
    public function setActiveLanguage($domain, $language)
    {
        if ('en' === $language) {
            $language = 'en-US';
        } elseif ('fi' === $language) {
            $language = 'fi-FI';
        } elseif ('sv' === $language) {
            $language = 'sv-FI';
        }

        $this->activeLanguages[$domain] = $language;
        unset($this->translations[$domain]);
    }

    /**
     * Load translations for a domain
     *
     * @param string $domain Translation domain
     *
     * @return void
     */
    protected function loadTranslations($domain)
    {
        $file = MLINVOICE_FALLBACK_LOCALE;

        if (!empty($this->activeLanguages[$domain])) {
            $file = $this->activeLanguages[$domain];
        } elseif ('default' !== $domain
            && !empty($this->activeLanguages['non-default'])
        ) {
            $file = $this->activeLanguages['non-default'];
        } elseif ($locale = $this->session->get('locale')) {
            $file = $locale;
        } elseif (defined('MLINVOICE_FALLBACK_LOCALE')) {
            $file = MLINVOICE_FALLBACK_LOCALE;
        }
        if ('default' !== $domain) {
            $file = $domain . '_' . $file;
        }
        if (!file_exists(MLINVOICE_BASE_DIR . "/languages/$file.ini")) {
            if ('default' !== $domain) {
                $file = $domain . '_fi-FI';
            } else {
                $file = 'fi-FI';
            }
        }
        $file = MLINVOICE_BASE_DIR . "/languages/$file.ini";
        $this->translations[$domain] = $this->parseIniFileIfExists($file);

        $localFile = MLINVOICE_BASE_DIR . "/local/languages/$file.ini";
        if (file_exists($localFile)) {
            $this->translations[$domain] = array_merge(
                $this->translations[$domain],
                $this->parseIniFileIfExists($localFile)
            );
        }
    }

    /**
     * Parse ini file.
     *
     * @param string $file File
     *
     * @return array
     */
    protected function parseIniFileIfExists(string $file): array
    {
        if (!file_exists($file)) {
            return [];
        }
        $result = parse_ini_file($file, true);
        if ($parent = $result['@parent_ini'] ?? null) {
            $result = array_merge(
                $this->parseIniFileIfExists(dirname($file) . '/' . $parent),
                $result
            );
        }
        return $result;
    }
}
