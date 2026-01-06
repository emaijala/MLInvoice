<?php
/**
 * Twig CSRF Extension.
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

use Closure;
use DI\Attribute\Inject;
use MLInvoice\I18n\Translator;
use Slim\Csrf\Guard;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * Twig CSRF Extension.
 *
 * @category MLInvoice
 * @package  MLInvoice\Twig
 * @author   Ere Maijala <ere@labs.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://labs.fi/mlinvoice.eng.php
 */
class CsrfExtension extends AbstractExtension
{
    /**
     * Constructor
     *
     * @param Closure $csrfFactory CSRF guard factory callback
     */
    public function __construct(protected Closure $csrfFactory)
    {
    }

    /**
     * Get Twig functions.
     *
     * @return array
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('csrf', $this->getCsrfData(...)),
        ];
    }

    /**
     * Get CSRF data.
     *
     * @return array
     */
    protected function getCsrfData(): array
    {
        $csrf = ($this->csrfFactory)();
        return [
            'keys' => [
                'name'  => $csrf->getTokenNameKey(),
                'value' => $csrf->getTokenValueKey(),
            ],
            'name'  => $csrf->getTokenName(),
            'value' => $csrf->getTokenValue(),
        ];
    }
}
