<?php
/**
 * Invoice Printer Factory.
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
 * @package  MLInvoice\Base
 * @author   Ere Maijala <ere@labs.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://labs.fi/mlinvoice.eng.php
 */

namespace MLInvoice\InvoicePrinter;

use DI\Container;
use MLInvoice\Config\SettingsManager;
use MLInvoice\Database\Repository\InvoiceRepository;
use MLInvoice\I18n\Translator;
use MLInvoice\Mailer\Mailer;
use MLInvoice\Security\Hmac;

/**
 * Invoice Printer Factory.
 *
 * @category MLInvoice
 * @package  MLInvoice\Base
 * @author   Ere Maijala <ere@labs.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://labs.fi/mlinvoice.eng.php
 */
class InvoicePrinterFactory
{
    /**
     * Constructor
     *
     * @param Container $container Container
     */
    public function __construct(protected Container $container)
    {
    }

    /**
     * Get an invoice printer.
     *
     * @param string $printTemplateFile Print template
     *
     * @return ?AbstractInvoicePrinter
     */
    function getForTemplate(string $printTemplateFile): ?AbstractInvoicePrinter
    {
        $printTemplateFile = trim($printTemplateFile);
        if (!is_readable($printTemplateFile)) {
            return null;
        }

        $className = $printTemplateFile;
        $className = str_replace('.php', '', $className);
        $className = str_replace('_', ' ', $className);
        $className = ucwords($className);
        $className = str_replace(' ', '', $className);

        $result = new $className(
            $this->container->get(Translator::class),
            $this->container->get(SettingsManager::class),
            $this->container->get(InvoiceRepository::class),
            $this->container->get(Hmac::class),
        );
        if (is_callable($result, 'setMailer')) {
            $result->setMailer($this->container->get(Mailer::class));
        }
        return $result;
    }
}
