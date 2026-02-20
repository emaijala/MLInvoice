<?php
/**
 * Twig Formatting Extension.
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

use DateTime;
use MLInvoice\I18n\NumberFormatter;
use MLInvoice\I18n\Translator;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * Twig Formatting Extension.
 *
 * @category MLInvoice
 * @package  MLInvoice\Twig
 * @author   Ere Maijala <ere@labs.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://labs.fi/mlinvoice.eng.php
 */
class FormatterExtension extends AbstractExtension
{
    /**
     * Constructor
     *
     * @param NumberFormatter $numberFormatter Number formatter
     */
    public function __construct(
        protected NumberFormatter $numberFormatter,
        protected Translator $translator,
    ) {
    }

    /**
     * Get Twig filters.
     *
     * @return array
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('format_date', $this->formatDate(...)),
            new TwigFilter('round_currency', $this->numberFormatter->roundCurrency(...)),
            new TwigFilter('round_number', $this->numberFormatter->roundNumber(...)),
        ];
    }

    /**
     * Get Twig functions.
     *
     * @return array
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('formatDate', $this->formatDate(...)),
            new TwigFunction('roundNumber', $this->numberFormatter->roundNumber(...)),
        ];
    }

    /**
     * Format DateTime.
     *
     * @param ?DateTime $dateTime DateTime, or null for current time
     * @param ?string   $format   DateTime Format (overrides default from translations)
     *
     * @return string
     */
    protected function formatDate(?DateTime $dateTime, ?string $format = null): string
    {
        $datetime ??= new DateTime();
        return $dateTime->format($format ?? $this->translator->translate('DateFormat'));
    }
}
