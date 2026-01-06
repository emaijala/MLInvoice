<?php

/**
 * Database Session Handler Middleware
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
 * @package  MLInvoice\Middleware
 * @author   Ere Maijala <ere@labs.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://labs.fi/mlinvoice.eng.php
 */

declare(strict_types = 1);

namespace MLInvoice\Middleware;

use DI\Attribute\Inject;
use GuzzleHttp\Psr7\Response;
use MLInvoice\Database\Repository\UserRepository;
use MLInvoice\Session\DatabaseSessionHandler;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Exception\HttpNotFoundException;
use Slim\Routing\RouteContext;

/**
 * Database Session Handler Middleware
 *
 * @category MLInvoice
 * @package  MLInvoice\Middleware
 * @author   Ere Maijala <ere@labs.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://labs.fi/mlinvoice.eng.php
 */
class SessionHandlerMiddleware implements MiddlewareInterface
{
    /**
     * Constructor
     *
     * @param DatabaseSessionHandler $sessionHandler Session handler
     */
    public function __construct(
        DatabaseSessionHandler $sessionHandler,
    ) {
        session_set_save_handler($sessionHandler);
    }

    /**
     * Process an incoming server request.
     *
     * @param ServerRequestInterface  $request Request
     * @param RequestHandlerInterface $handler Request handler
     *
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return $handler->handle($request);
    }
}
