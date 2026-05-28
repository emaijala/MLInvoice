<?php
/**
 * Refund Invoice Action.
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

use DateTime;
use DI\Attribute\Inject;
use Doctrine\ORM\EntityManagerInterface;
use MLInvoice\Config\SettingsManager;
use MLInvoice\Database\DatabaseUpdater;
use MLInvoice\Database\Entity\EntityInterface;
use MLInvoice\Database\Entity\ExchangeArrayInterface;
use MLInvoice\Database\Entity\Invoice;
use MLInvoice\Database\Entity\InvoiceRow;
use MLInvoice\Database\Repository\AttachmentRepository;
use MLInvoice\Database\Repository\CustomPriceMapRepository;
use MLInvoice\Database\Repository\InvoiceRepository;
use MLInvoice\Database\Repository\InvoiceStateRepository;
use MLInvoice\Database\Repository\ProductRepository;
use MLInvoice\Database\Repository\StockBalanceLogRepository;
use MLInvoice\Database\Repository\UserRepository;
use MLInvoice\Database\Updater;
use MLInvoice\Form\FormService;
use MLInvoice\I18n\Translator;
use Odan\Session\SessionInterface;
use Odan\Session\SessionManagerInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\App;
use Slim\Routing\RouteContext;
use Slim\Views\Twig;

/**
 * Refund Invoice Action.
 *
 * @category MLInvoice
 * @package  MLInvoice\Action
 * @author   Ere Maijala <ere@labs.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://labs.fi/mlinvoice.eng.php
 */
class RefundInvoiceAction extends InvoiceAction
{
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
        if ($result = parent::__invoke($request, $response, $args)) {
            return $result;
        }

        if ($sourceId = $args['from'] ?? null) {
            if (!($sourceInvoice = $this->repository->find($sourceId))) {
                throw new \Exception('RecordNotFound');
            }
            try {
                $this->entityManager->beginTransaction();
                $this->updateFromInvoice($sourceInvoice, false, true);
                $this->repository->persistEntity($this->entity);
                $this->entityManager->commit();
            } catch (\Exception $e) {
                $this->entityManager->rollback();
                throw $e;
            }
        }

        $routeParser = RouteContext::fromRequest($request)->getRouteParser();
        return $response
            ->withHeader('Location', $routeParser->urlFor('invoice', ['id' => $this->entity->getId()]))
            ->withStatus(302);
    }
}
