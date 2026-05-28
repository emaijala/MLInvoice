<?php

/**
 * Dependency Configuration
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

declare(strict_types=1);

use DI\ContainerBuilder;
use Doctrine\ORM\EntityManagerInterface;
use MLInvoice\Action\LoginAction;
use MLInvoice\Asset\VersionStrategy\TimestampVersionStrategy;
use MLInvoice\Config\ConfigManager;
use MLInvoice\Config\ConfigManagerInterface;
use MLInvoice\Config\SettingsManager;
use MLInvoice\Database\DatabaseUpdater;
use MLInvoice\Database\Entity\Attachment;
use MLInvoice\Database\Entity\Base;
use MLInvoice\Database\Entity\Company;
use MLInvoice\Database\Entity\CompanyContact;
use MLInvoice\Database\Entity\CompanyContactTag;
use MLInvoice\Database\Entity\CompanyTag;
use MLInvoice\Database\Entity\CustomPrice;
use MLInvoice\Database\Entity\CustomPriceMap;
use MLInvoice\Database\Entity\DeliveryMethod;
use MLInvoice\Database\Entity\DeliveryTerms;
use MLInvoice\Database\Entity\Invoice;
use MLInvoice\Database\Entity\InvoiceAttachment;
use MLInvoice\Database\Entity\InvoiceRow;
use MLInvoice\Database\Entity\InvoiceState;
use MLInvoice\Database\Entity\PrintTemplate;
use MLInvoice\Database\Entity\Product;
use MLInvoice\Database\Entity\QuickSearch;
use MLInvoice\Database\Entity\RowType;
use MLInvoice\Database\Entity\Session;
use MLInvoice\Database\Entity\SessionType;
use MLInvoice\Database\Entity\Setting;
use MLInvoice\Database\Entity\User;
use MLInvoice\Database\EntityManagerFactory;
use MLInvoice\Database\Repository\AttachmentRepository;
use MLInvoice\Database\Repository\BaseRepository;
use MLInvoice\Database\Repository\CompanyContactRepository;
use MLInvoice\Database\Repository\CompanyContactTagRepository;
use MLInvoice\Database\Repository\CompanyRepository;
use MLInvoice\Database\Repository\CompanyTagRepository;
use MLInvoice\Database\Repository\CustomPriceMapRepository;
use MLInvoice\Database\Repository\CustomPriceRepository;
use MLInvoice\Database\Repository\DeliveryMethodRepository;
use MLInvoice\Database\Repository\DeliveryTermsRepository;
use MLInvoice\Database\Repository\InvoiceAttachmentRepository;
use MLInvoice\Database\Repository\InvoiceRepository;
use MLInvoice\Database\Repository\InvoiceRowRepository;
use MLInvoice\Database\Repository\InvoiceStateRepository;
use MLInvoice\Database\Repository\PrintTemplateRepository;
use MLInvoice\Database\Repository\ProductRepository;
use MLInvoice\Database\Repository\QuickSearchRepository;
use MLInvoice\Database\Repository\RowTypeRepository;
use MLInvoice\Database\Repository\SessionRepository;
use MLInvoice\Database\Repository\SessionTypeRepository;
use MLInvoice\Database\Repository\SettingRepository;
use MLInvoice\Database\Repository\UserRepository;
use MLInvoice\Database\Updater;
use MLInvoice\I18n\NumberFormatter;
use MLInvoice\I18n\Translator;
use MLInvoice\InvoicePrinter\InvoicePrinterFactory;
use MLInvoice\Logger\LoggerFactory;
use MLInvoice\Mailer\Mailer;
use MLInvoice\Middleware\SessionHandlerMiddleware;
use MLInvoice\Session\DatabaseSessionHandler;
use MLInvoice\Middleware\SessionMiddleware;
use MLInvoice\Security\Hmac;
use MLInvoice\Twig\Extension\ConfigExtension;
use MLInvoice\Twig\Extension\CsrfExtension;
use MLInvoice\Twig\Extension\FormatterExtension;
use MLInvoice\Twig\Extension\FormExtension;
use MLInvoice\Twig\Extension\ListExtension;
use MLInvoice\Twig\Extension\NavBarExtension;
use MLInvoice\Twig\Extension\NumberFormatterExtension;
use MLInvoice\Twig\Extension\SearchExtension;
use MLInvoice\Twig\Extension\TranslationExtension;
use MLInvoice\Twig\TwigFactory;
use MLInvoice\Utils\DateUtils;
use MLInvoice\Utils\DateUtilsFactory;
use Odan\Session\PhpSession;
use Odan\Session\SessionInterface;
use Odan\Session\SessionManagerInterface;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Slim\Views\Twig;
use Symfony\Bridge\Twig\Extension\AssetExtension;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Asset\PathPackage;

return function (ContainerBuilder $containerBuilder) {
    $containerBuilder->addDefinitions([
        'config' => function (ContainerInterface $c) {
            return $c->get(ConfigManagerInterface::class)->get('config');
        },
        'dbPrefix' => function (ContainerInterface $c) {
            $config = $c->get(ConfigManagerInterface::class)->get('config');
            return $config['Database']['table_prefix'] ?? 'mlinvoice_';
        },

        DatabaseSessionHandler::class => DI\autowire(),
        DateUtils::class => DI\factory(DateUtilsFactory::class . '::create'),
        EntityManagerInterface::class => DI\factory(EntityManagerFactory::class . '::create'),
        Hmac::class => DI\autowire(),
        InvoicePrinterFactory::class => DI\autowire(),
        LoggerInterface::class => DI\factory(LoggerFactory::class . '::create'),
        Mailer::class => DI\autowire(),
        NumberFormatter::class => DI\autowire(),
        SessionHandlerMiddleware::class => DI\autowire(),
        SessionManagerInterface::class => function (ContainerInterface $container) {
            return $container->get(SessionInterface::class);
        },
        SessionInterface::class => function (ContainerInterface $container) {
            $settings = $container->get('config')['Session'] ?? [];
            $settings['name'] ??= 'MLINVOICESESSION';
            $settings['lifetime'] ??= 600;
            if ($settings['restrict_path'] ?? true) {
                $settings['path'] = MLINVOICE_BASE_URL_PATH;
                unset($settings['restrict_path']);
            }
            return new PhpSession($settings);
        },
        SettingsManager::class => DI\autowire(),
        Translator::class => DI\autowire(),
        Twig::class => DI\factory(TwigFactory::class . '::create'),

        // Database repositories:
        AttachmentRepository::class => function (ContainerInterface $c) {
            return $c->get(EntityManagerInterface::class)->getRepository(Attachment::class);
        },
        BaseRepository::class => function (ContainerInterface $c) {
            return $c->get(EntityManagerInterface::class)->getRepository(Base::class);
        },
        CompanyContactRepository::class => function (ContainerInterface $c) {
            return $c->get(EntityManagerInterface::class)->getRepository(CompanyContact::class);
        },
        CompanyContactTagRepository::class => function (ContainerInterface $c) {
            return $c->get(EntityManagerInterface::class)->getRepository(CompanyContactTag::class);
        },
        CompanyRepository::class => function (ContainerInterface $c) {
            return $c->get(EntityManagerInterface::class)->getRepository(Company::class);
        },
        CompanyTagRepository::class => function (ContainerInterface $c) {
            return $c->get(EntityManagerInterface::class)->getRepository(CompanyTag::class);
        },
        CustomPriceRepository::class => function (ContainerInterface $c) {
            return $c->get(EntityManagerInterface::class)->getRepository(CustomPrice::class);
        },
        CustomPriceMapRepository::class => function (ContainerInterface $c) {
            return $c->get(EntityManagerInterface::class)->getRepository(CustomPriceMap::class);
        },
        DeliveryMethodRepository::class => function (ContainerInterface $c) {
            return $c->get(EntityManagerInterface::class)->getRepository(DeliveryMethod::class);
        },
        DeliveryTermsRepository::class => function (ContainerInterface $c) {
            return $c->get(EntityManagerInterface::class)->getRepository(DeliveryTerms::class);
        },
        InvoiceRepository::class => function (ContainerInterface $c) {
            return $c->get(EntityManagerInterface::class)->getRepository(Invoice::class);
        },
        InvoiceAttachmentRepository::class => function (ContainerInterface $c) {
            return $c->get(EntityManagerInterface::class)->getRepository(InvoiceAttachment::class);
        },
        InvoiceRowRepository::class => function (ContainerInterface $c) {
            return $c->get(EntityManagerInterface::class)->getRepository(InvoiceRow::class);
        },
        InvoiceStateRepository::class => function (ContainerInterface $c) {
            return $c->get(EntityManagerInterface::class)->getRepository(InvoiceState::class);
        },
        PrintTemplateRepository::class => function (ContainerInterface $c) {
            return $c->get(EntityManagerInterface::class)->getRepository(PrintTemplate::class);
        },
        ProductRepository::class => function (ContainerInterface $c) {
            return $c->get(EntityManagerInterface::class)->getRepository(Product::class);
        },
        QuickSearchRepository::class => function (ContainerInterface $c) {
            return $c->get(EntityManagerInterface::class)->getRepository(QuickSearch::class);
        },
        RowTypeRepository::class => function (ContainerInterface $c) {
            return $c->get(EntityManagerInterface::class)->getRepository(RowType::class);
        },
        SessionRepository::class => function (ContainerInterface $c) {
            return $c->get(EntityManagerInterface::class)->getRepository(Session::class);
        },
        SessionTypeRepository::class => function (ContainerInterface $c) {
            return $c->get(EntityManagerInterface::class)->getRepository(SessionType::class);
        },
        SettingRepository::class => function (ContainerInterface $c) {
            return $c->get(EntityManagerInterface::class)->getRepository(Setting::class);
        },
        UserRepository::class => function (ContainerInterface $c) {
            return $c->get(EntityManagerInterface::class)->getRepository(User::class);
        },

        DatabaseUpdater::class => DI\autowire(),

        // Twig extensions:
        AssetExtension::class => function (ContainerInterface $c) {
            $packages = [
                'assets' => new PathPackage(MLINVOICE_BASE_URL_PATH . '/assets', new TimestampVersionStrategy()),
                'icons' => new PathPackage(MLINVOICE_BASE_URL_PATH . '/fonts/icons', new TimestampVersionStrategy()),
            ];
            return new AssetExtension(new Packages($packages['assets'], packages: $packages));
        },
        ConfigExtension::class => DI\autowire(),
        'CsrfGuardFactory' => function (ContainerInterface $c) {
            return Closure::fromCallable(fn () => $c->get('csrf'));
        },
        CsrfExtension::class => function (ContainerInterface $c) {
            // Callback for lazy Guard creation:
            return new CsrfExtension($c->get('CsrfGuardFactory'));
        },
        FormExtension::class => DI\autowire(),
        FormatterExtension::class => DI\autowire(),
        ListExtension::class => DI\autowire(),
        NavBarExtension::class => DI\autowire(),
        SearchExtension::class => DI\autowire(),
        TranslationExtension::class => DI\autowire(),

        // Aliases:
        ConfigManagerInterface::class => DI\get(ConfigManager::class),
    ]);
};
