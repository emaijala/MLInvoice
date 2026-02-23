<?php
/**
 * Abstract base class for form actions.
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

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Dom\EntityReference;
use MLInvoice\Config\SettingsManager;
use MLInvoice\Database\Entity\EntityInterface;
use MLInvoice\Database\Entity\ExchangeArrayInterface;
use MLInvoice\Database\Entity\SoftDeleteInterface;
use MLInvoice\Form\FormService;
use MLInvoice\I18n\NumberFormatter;
use MLInvoice\I18n\Translator;
use Odan\Session\SessionInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\App;
use Slim\Routing\Route;
use Slim\Routing\RouteContext;
use Slim\Views\Twig;

/**
 * Abstract base class for form actions.
 *
 * @category MLInvoice
 * @package  MLInvoice\Action
 * @author   Ere Maijala <ere@labs.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://labs.fi/mlinvoice.eng.php
 */
abstract class AbstractFormAction extends AbstractAction
{
    /**
     * Record ID, or null for new.
     *
     * @var ?int
     */
    protected ?int $id;

    /**
     * Form configuration
     *
     * @var array
     */
    protected array $formConfig;

    /**
     * Requested action.
     *
     * @var ?string
     */
    protected ?string $action;

    /**
     * Current entity
     *
     * @var EntityInterface&ExchangeArrayInterface
     */
    protected EntityInterface&ExchangeArrayInterface $entity;

    /**
     * Constructor
     *
     * @param Translator      $translator      Translator
     * @param string $formType Form type
     * @param FormService $formService Form service
     * @param SessionInterface $session Session
     */
    public function __construct(
        Translator $translator,
        protected string $formType,
        protected FormService $formService,
        protected SessionInterface $session,
        protected EntityRepository $repository,
        protected EntityManagerInterface $entityManager,
        protected SettingsManager $settingsManager,
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
        $this->request = $request;
        $this->id = $this->getIdFromArgs($args);
        $this->entity = $this->id ? $this->repository->find($this->id) : new ($this->repository->getClassName());
        if ($this->id && !$this->entity) {
            return $response->withStatus(404, $this->translator->translate('RecordNotFound'));
        }
        $this->formConfig = $this->formService->getFormConfig($this->formType, $this->id, $request);
        $this->action = $this->getPostOrQuery('action');

        if (!$this->checkAccess()) {
            return $response->withStatus(403, $this->translator->translate('NoAccess'));
        }

        if ('delete' === $this->action) {
            // TODO: switch to gedmo/doctrine-extensions
            if ($this->entity instanceof SoftDeleteInterface) {
                $this->entity->setDeleted(true);
            } else {
                $this->entityManager->remove($this->entity);
            }
            if ($this->settingsManager->get('auto_close_after_delete')) {
                $routeContext = RouteContext::fromRequest($request);
                $routeParser = $routeContext->getRouteParser();
                // TODO: correct redirect?
                return $response
                    ->withHeader('Location', $routeParser->urlFor($routeContext->getRoute()->getIdentifier()))
                    ->withStatus(302);
            }
            $this->session->getFlash()->add('info', 'RecordDeleted');
        } elseif ('copy' === $this->action) {
            unset($this->data['id']);
            foreach ($this->formConfig['fields'] as $field) {
                if ($field['unique'] ?? false) {
                    $data[$field['name']] = null;
                }
            }
            $this->id = null;
            $res = saveFormData(
                $this->formConfig['table'], $id, $this->formConfig, $astrValues, $warnings
            );
            if ($res === true) {
                $routeContext = RouteContext::fromRequest($request);
                $routeParser = $routeContext->getRouteParser();
                return $response
                    ->withHeader(
                        'Location',
                        $routeParser->urlFor($routeContext->getRoute()->getIdentifier(), ['id' => $this->id])
                    )
                    ->withStatus(302);
            }
            $this->session->getFlash()->add('error', $this->translator->translate('ErrValueMissing') . ': ' . $res);
        }
    }

    /**
     * Get record ID from args.
     *
     * @param array $args Args
     *
     * @return ?int
     */
    protected function getIdFromArgs(array $args): ?int
    {
        $id = $args['id'];
        return 'new' === $id ? null : (int)$id;
    }

    /**
     * Check permissions.
     *
     * @return bool
     */
    protected function checkAccess(): bool
    {
        $writeAccess = $this->request->getAttribute('write_access');
        return in_array($this->session->get('accessLevel'), $this->formConfig['accessLevels'])
            && ($this->id || $writeAccess)
            && (!$this->action || $writeAccess)
            && ($this->entity->getId() || !$this->action);
    }
}
