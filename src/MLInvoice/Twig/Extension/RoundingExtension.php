<?php
/**
 * Twig Rounding Extension.
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
 * Twig Rounding Extension.
 *
 * @category MLInvoice
 * @package  MLInvoice\Twig
 * @author   Ere Maijala <ere@labs.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://labs.fi/mlinvoice.eng.php
 */
class RoundingExtension extends AbstractExtension
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
            new TwigFilter('round_currency', $this->roundCurrency(...)),
        ];
    }

    /**
     * Round currency.
     *
     * @param float  $value Value
     * @param int  $decimals Decimals to display
     * @param bool $hideZeroFraction Hide the fractional part if zero?
     * @param ?string $decimalSeparator  Decimal separator
     * @param ?string $thousandSeparator Thousand separator
     *
     * @return string
     */
    protected function roundCurrency(
        float $value,
        int $decimals = 2,
        bool $hideZeroFraction = false,
        $decimalSeparator = null,
        $thousandSeparator = null,
    ): string {
        if ($hideZeroFraction && $value === floor($value)) {
            $decimals = 0;
        }
        return number_format(
            $value,
            $decimals,
            $decimalSeparator ?? $this->translator->translate('DecimalSeparator'),
            $thousandSeparator ?? $this->translator->translate('ThousandSeparator')
        );
    }
}
