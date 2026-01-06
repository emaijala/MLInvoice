<?php
/**
 * Twig Translation Extension.
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
 * @package  MLInvoice\Twig
 * @author   Ere Maijala <ere@labs.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://labs.fi/mlinvoice.eng.php
 */

declare(strict_types=1);

namespace MLInvoice\Twig\Extension;

use MLInvoice\I18n\Translator;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * Twig Translation Extension.
 *
 * @category MLInvoice
 * @package  MLInvoice\Twig
 * @author   Ere Maijala <ere@labs.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://labs.fi/mlinvoice.eng.php
 */
class TranslationExtension extends AbstractExtension
{
    /**
     * Constructor
     *
     * @param Translator $translator Translator
     */
    public function __construct(protected Translator $translator)
    {
    }

    /**
     * Get Twig filters.
     *
     * @return array
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('trans', $this->trans(...)),
        ];
    }

    /**
     * Translate a string.
     *
     * @param string|\Stringable|null $string       String
     * @param array                   $placeholders Placeholder values
     * @param string|\Stringable|null $default      Default value
     *
     * @return string
     */
    protected function trans(
        string|\Stringable|null $string,
        array $placeholders = [],
        string|\Stringable|null $default = null
    ): string {
        return $this->translator->translate($string, $placeholders, $default);
    }
}
