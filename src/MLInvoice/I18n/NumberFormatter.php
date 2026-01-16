<?php
/**
 * Number Formatter
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
class NumberFormatter
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
     * Round currency.
     *
     * @param float  $value Value
     * @param int  $decimals Decimals to display
     * @param ?string $decimalSeparator  Decimal separator
     * @param ?string $thousandSeparator Thousand separator
     * @param bool $hideZeroFraction Hide the fractional part if zero?
     *
     * @return string
     */
    public function roundCurrency(
        float $value,
        int $decimals = 2,
        $decimalSeparator = null,
        $thousandSeparator = null,
        bool $hideZeroFraction = false,
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

    /**
     * Round a number to given decimals.
     *
     * @param ?float $value             Value
     * @param int    $decimals          Number of decimals
     * @param ?string $decimalSeparator  Decimal separator
     * @param ?string $thousandSeparator Thousand separator
     *
     * @return string
     */
    function roundNumber(
        ?float $value,
        int $decimals = 2,
        ?string $decimalSeparator = null,
        ?string $thousandSeparator = null,
    ) {
        return number_format(
            $value ?? 0, $decimals,
            $decimalSeparator ?? $this->translator->translate('DecimalSeparator'),
            $thousandSeparator ?? $this->translator->translate('ThousandSeparator')
        );
    }
}
