<?php

/**
 * Slim Middleware Configuration.
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

use MLInvoice\Config\ConfigManagerInterface;
use MLInvoice\Error\Renderers\HtmlErrorRenderer;
use MLInvoice\Middleware\AuthMiddleware;
use MLInvoice\Middleware\LocaleMiddleware;
use MLInvoice\Middleware\SessionHandlerMiddleware;
use Odan\Session\Middleware\SessionStartMiddleware;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\App;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;

// Return a callable to add the Middleware:
return function (App $app): void
{
    // Use a callback to ensure that Twig is only created after session initialization etc.:
    $app->add(function (ServerRequestInterface $request, RequestHandlerInterface $handler) use ($app) {
        $twigMiddleware = TwigMiddleware::create($app, $app->getContainer()->get(Twig::class));
        return $twigMiddleware->process($request, $handler);
    });
    $app->add('csrf');
    $app->add(AuthMiddleware::class);
    $app->add(LocaleMiddleware::class);
    $app->add(SessionStartMiddleware::class);
    $app->add(SessionHandlerMiddleware::class);

    $app->addRoutingMiddleware();
    $app->addBodyParsingMiddleware();

    $config = $app->getContainer()->get(ConfigManagerInterface::class)->get('config');
    $errorMiddleware = $app->addErrorMiddleware(
        'production' !== MLINVOICE_ENV,
        true,
        (bool)($config['Logging']['log_error_details'] ?? true)
    );
    $errorMiddleware->getDefaultErrorHandler()->registerErrorRenderer('text/html', HtmlErrorRenderer::class);
};
