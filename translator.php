<?php
/**
 * Translator
 *
 * PHP version 5
 *
 * Copyright (C) Ere Maijala 2017.
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
require_once 'sessionfuncs.php';

/**
 * Translator
 *
 * @category MLInvoice
 * @package  MLInvoice\Base
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
    public static $translations = [];

    /**
     * Any active languages for domains
     *
     * @var array
     */
    public static $activeLanguages = [];

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
    public static function translate($str, $placeholders = [], $default = null)
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

        if (empty(self::$translations[$domain])) {
            self::loadTranslations($domain);
        }
        if (isset(self::$translations[$domain][$str])) {
            $str = self::$translations[$domain][$str];
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
    public static function getActiveLanguage($domain)
    {
        return isset(self::$activeLanguages[$domain])
            ? self::$activeLanguages[$domain]
            : '';
    }

    /**
     * Set active language for a domain
     *
     * @param string $domain   Translation domain
     * @param string $language Language
     *
     * @return void
     */
    public static function setActiveLanguage($domain, $language)
    {
        if ('en' === $language) {
            $language = 'en-US';
        } elseif ('fi' === $language) {
            $language = 'fi-FI';
        } elseif ('sv' === $language) {
            $language = 'sv-FI';
        }

        self::$activeLanguages[$domain] = $language;
        unset(self::$translations[$domain]);
    }

    /**
     * Load translations for a domain
     *
     * @param string $domain Translation domain
     *
     * @return void
     */
    protected static function loadTranslations($domain)
    {
        $file = 'fi-FI';

        if (!session_id()) {
            session_start();
        }

        if (!empty(self::$activeLanguages[$domain])) {
            $file = self::$activeLanguages[$domain];
        } elseif (isset($_SESSION['sesLANG'])) {
            $file = $_SESSION['sesLANG'];
        } elseif (defined('_UI_LANGUAGE_')) {
            $file = _UI_LANGUAGE_;
        }
        if ('default' !== $domain) {
            $file = $domain . '_' . $file;
        }
        if (!file_exists("lang/$file.ini")) {
            if ('default' !== $domain) {
                $file = $domain . '_fi-FI';
            } else {
                $file = 'fi-FI';
            }
        }
        self::$translations[$domain] = parse_ini_file("lang/$file.ini");

        if (file_exists("lang/$file.local.ini")) {
            self::$translations[$domain] = array_merge(
                self::$translations[$domain],
                parse_ini_file("lang/$file.local.ini")
            );
        }

        if (_CHARSET_ != 'UTF-8') {
            foreach (self::$translations[$domain] as $key => &$tr) {
                if (is_string($tr)) {
                    $tr = utf8_decode($tr);
                }
            }
        }
    }
}
