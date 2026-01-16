<?php
/**
 * Login Action.
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

use DI\Attribute\Inject;
use MLInvoice\Database\DatabaseUpdater;
use MLInvoice\Database\Repository\UserRepository;
use MLInvoice\Database\Updater;
use MLInvoice\I18n\Translator;
use Odan\Session\SessionInterface;
use Odan\Session\SessionManagerInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Routing\RouteContext;
use Slim\Views\Twig;

/**
 * Login Action.
 *
 * @category MLInvoice
 * @package  MLInvoice\Action
 * @author   Ere Maijala <ere@labs.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://labs.fi/mlinvoice.eng.php
 */
class LoginAction extends AbstractAction
{
    /**
     * Constructor
     *
     * @param Translator      $translator      Translator
     * @param DatabaseUpdater $databaseUpdater Database updater
     * @param UserRepository  $userRepositoru  User database repository
     * @param SessionInterface $session Session
     */
    public function __construct(
        Translator $translator,
        protected DatabaseUpdater $databaseUpdater,
        protected UserRepository $userRepository,
        protected SessionInterface $session,
    ) {
        parent::__construct($translator);
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
        if ($request->getAttribute('user')) {
            $routeParser = RouteContext::fromRequest($request)->getRouteParser();
            return $response
                ->withHeader('Location', $routeParser->urlFor('home'))
                ->withStatus(302);
        }

        $loginFailed = false;
        if ($request->getMethod() === 'POST') {
            // Process login request
            $body = $request->getParsedBody();
            if (($login = $body['login'] ?? null) && ($password = $body['passwd'] ?? null)) {
                // Delay to make brute-force attacks less practical
                usleep(rand(500, 1000) * 1000 * MLINVOICE_LOGIN_DELAY_MULTIPLIER);
                if ($user = $this->userRepository->findByLogin($login)) {
                    if (
                        password_verify($password, $user->getPasswd())
                        || md5($password) === $user->getPasswd()
                    ) {
                        // Login successful
                        $this->session->set('user', $user->getId());
                        $this->session->set('accessLevel', $user->getAccessLevel());
                        $routeParser = RouteContext::fromRequest($request)->getRouteParser();
                        $url = $this->session->get('redirect', $routeParser->urlFor('home'));
                        return $response
                            ->withHeader('Location', $url)
                            ->withStatus(302);
                    }
                }
                $this->session->getFlash()->add('error', 'InvalidCredentials');
                $loginFailed = true;
            } else {
                $this->session->getFlash()->add('error', 'MissingFields');
            }
        }

        $upgradeFailed = false;
        $upgradeMessage = '';
        if ($request->getMethod() === 'GET') {
            switch ($this->databaseUpdater->verifyDatabase()) {
            case 'OK':
                break;
            case 'UPGRADED':
                $upgradeMessage = $this->translator->translate('DatabaseUpgraded');
                break;
            case 'FAILED':
                $upgradeFailed = true;
                $upgradeMessage = $this->translator->translate('DatabaseUpgradeFailed');
                break;
            }
        }

        $response = $this->getView($request)->render($response, 'login.html.twig', compact('upgradeFailed', 'upgradeMessage'));
        return $loginFailed ? $response->withStatus(401) : $response;
    }
}
