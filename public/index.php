<?php

/**
 * Main script (index)
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
use Slim\Csrf\Guard;
use Slim\Factory\AppFactory;
use Slim\Factory\ServerRequestCreatorFactory;
use Slim\ResponseEmitter;

require __DIR__ . '/../vendor/autoload.php';

$localAppConfig = __DIR__ . '/../app-config/application.local.config.php';
if (file_exists($localAppConfig)) {
  require $localAppConfig;
}
require __DIR__ . '/../app-config/version.php';
require __DIR__ . '/../app-config/application.config.php';

$containerBuilder = new ContainerBuilder();
$containerBuilder->useAttributes(true);
if ('production' === MLINVOICE_ENV) {
	$containerBuilder->enableCompilation(MLINVOICE_CACHE_DIR . '/container');
}

$dependencies = require __DIR__ . '/../app-config/dependencies.php';
$dependencies($containerBuilder);
$container = $containerBuilder->build();

AppFactory::setContainer($container);
$app = AppFactory::create();
$app->setBasePath(MLINVOICE_BASE_URL_PATH);
$responseFactory = $app->getResponseFactory();

// CSRF requires App, so register it only now:
$container->set('csrf', function () use ($responseFactory) {
    return new Guard($responseFactory, persistentTokenMode: true);
});

$middleware = require __DIR__ . '/../app-config/middleware.php';
$middleware($app);

$routes = require __DIR__ . '/../app-config/routes.php';
$routes($app);

$serverRequestCreator = ServerRequestCreatorFactory::create();
$request = $serverRequestCreator->createServerRequestFromGlobals();

$response = $app->handle($request);
$responseEmitter = new ResponseEmitter();
$responseEmitter->emit($response);
