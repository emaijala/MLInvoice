<?php
/**
 * Abstract base class for actions.
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
 * @package  MLInvoice\Action
 * @author   Ere Maijala <ere@labs.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://labs.fi/mlinvoice.eng.php
 */

declare(strict_types=1);

namespace MLInvoice\Action;

use MLInvoice\I18n\Translator;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Routing\Route;
use Slim\Routing\RouteContext;
use Slim\Views\Twig;

/**
 * Abstract base class for actions.
 *
 * @category MLInvoice
 * @package  MLInvoice\Action
 * @author   Ere Maijala <ere@labs.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://labs.fi/mlinvoice.eng.php
 */
abstract class AbstractAction
{
    /**
     * Current request
     *
     * @var ?ServerRequestInterface
     */
    protected ?ServerRequestInterface $request = null;

    /**
     * Constructor
     *
     * @param Translator $translator Translator
     */
    public function __construct(protected Translator $translator)
    {
    }

    /**
     * Invoke the action.
     *
     * @param ServerRequestInterface $request  Request
     * @param ResponseInterface      $response Response
     * @param array                  $args     Arguments
     *
     * @return mixed
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, array $args = [])
    {
        $this->request = $request;
    }

    /**
     * Get Twig view for a request.
     *
     * @param ServerRequestInterface $request Request
     *
     * @return Twig
     */
    protected function getView(ServerRequestInterface $request): Twig
    {
        $twig = Twig::fromRequest($request);
        $twigEnv = $twig->getEnvironment();
        $twigEnv->addGlobal('user', $request->getAttribute('user'));
        $twigEnv->addGlobal('writeAccess', $request->getAttribute('write_access'));
        $twigEnv->addGlobal('route', RouteContext::fromRequest($request)->getRoute());
        $twigEnv->addGlobal('searchParams', $this->getSearchParamsFromRequest($request));

        return $twig;
    }

    /**
     * Get search parameters from query parameters
     *
     * @param ServerRequestInterface $request Request
     *
     * @return array
     */
    protected function getSearchParamsFromRequest(ServerRequestInterface $request): array
    {
        return array_filter(
            $request->getQueryParams(),
            function ($key) {
                return strncmp($key, 's_', 2) === 0;
            },
            ARRAY_FILTER_USE_KEY
        );
    }

    /**
     * Get POST or GET param value.
     *
     * @param string  $param   Param name
     * @param string|array|null $default Default value
     *
     * @return string|array|null
     */
    protected function getPostOrQuery(string $param, string|array|null $default = null): string|array|null
    {
        return $this->request->getParsedBody()[$param]
            ?? $this->request->getQueryParams()[$param]
            ?? $default;
    }
}
