<?php
/**
 * Twig Auth Extension.
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
use MLInvoice\Database\Entity\User;
use MLInvoice\Database\Repository\CustomPriceRepository;
use MLInvoice\Database\Repository\PrintTemplateRepository;
use MLInvoice\Form\FormService;
use MLInvoice\InvoicePrinter\InvoicePrinterBlank;
use MLInvoice\InvoicePrinter\InvoicePrinterFactory;
use MLInvoice\InvoicePrinter\InvoicePrinterXslt;
use MLInvoice\List\ListService;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ServerRequestInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Twig Auth Extension.
 *
 * @category MLInvoice
 * @package  MLInvoice\Twig
 * @author   Ere Maijala <ere@labs.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://labs.fi/mlinvoice.eng.php
 */
class AuthExtension extends AbstractExtension
{
    /**
     * Get Twig functions.
     *
     * @return array
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('is_authorized', $this->isAuthorized(...)),
        ];
    }

    /**
     * Is user authorized?
     *
     * @param User $user User
     * @param array $accessLevels Access levels
     *
     * @return array
     */
    function isAuthorized(
        User $user,
        array $accessLevels,
    ) {
        return in_array($user->getAccessLevel(), $accessLevels);
    }
}
