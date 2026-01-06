<?php
/**
 * Twig Factory.
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

namespace MLInvoice\Twig;

use DI\Container;
use MLInvoice\Asset\VersionStrategy\TimestampVersionStrategy;
use MLInvoice\Twig\Extension\ConfigExtension;
use MLInvoice\Twig\Extension\CsrfExtension;
use MLInvoice\Twig\Extension\ListExtension;
use MLInvoice\Twig\Extension\NavBarExtension;
use MLInvoice\Twig\Extension\RoundingExtension;
use MLInvoice\Twig\Extension\SearchExtension;
use MLInvoice\Twig\Extension\TranslationExtension;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\UidProcessor;
use Odan\Session\SessionInterface;
use Odan\Session\SessionManagerInterface;
use Odan\Twig\TwigAssetsExtension;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Slim\Views\Twig;
use Symfony\Bridge\Twig\Extension\AssetExtension;
use Symfony\Component\Asset\Package;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Asset\PathPackage;
use Symfony\Component\Asset\VersionStrategy\StaticVersionStrategy;

/**
 * Twig Factory.
 *
 * @category MLInvoice
 * @package  MLInvoice\Twig
 * @author   Ere Maijala <ere@labs.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://labs.fi/mlinvoice.eng.php
 */
class TwigFactory
{
    /**
     * Create Twig.
     *
     * @param ContainerInterface $c Container
     *
     * @return Twig
     */
    public static function create(ContainerInterface $c): Twig
    {
        $twig = Twig::create(
            MLINVOICE_BASE_DIR . '/templates',
            [
                'cache' => 'production' === MLINVOICE_ENV
                    ? MLINVOICE_CACHE_DIR . '/templates'
                    : false,
                'auto_reload' => true,
                'use_yield' => true,
                'autoescape' => false,
            ]
        );

        $twigEnv = $twig->getEnvironment();
        $twigEnv->addGlobal('basePath', MLINVOICE_BASE_URL_PATH);

        if (!is_dir(MLINVOICE_CACHE_DIR . '/public')) {
            mkdir(MLINVOICE_CACHE_DIR . '/public');
        }
        /*$twig->addExtension(
            new TwigAssetsExtension(
                $twigEnv,
                [
                    'path' => MLINVOICE_CACHE_DIR . '/public',
                    'path_chmod' => 0750,
                    'url_base_path' => 'assets/cache/',
                    'cache_path' => MLINVOICE_CACHE_DIR,
                    'cache_name' => 'assets',
                    'cache_lifetime' => 600,
                    'minify' => 'production' === MLINVOICE_ENV ? 1 : 0,
                ]
            )
        );*/
        $twig->addExtension($c->get(AssetExtension::class));
        $twig->addExtension($c->get(ConfigExtension::class));
        $twig->addExtension($c->get(CsrfExtension::class));
        $twig->addExtension($c->get(ListExtension::class));
        $twig->addExtension($c->get(NavBarExtension::class));
        $twig->addExtension($c->get(RoundingExtension::class));
        $twig->addExtension($c->get(SearchExtension::class));
        $twig->addExtension($c->get(TranslationExtension::class));

        $twig->getEnvironment()->addGlobal(
            'flash',
            $c->get(SessionManagerInterface::class)->getFlash()
        );

        return $twig;
    }
}
