<?php
/**
 * Invoice Action.
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
use MLInvoice\I18n\NumberFormatter;
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
 * Invoice Action.
 *
 * @category MLInvoice
 * @package  MLInvoice\Action
 * @author   Ere Maijala <ere@labs.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://labs.fi/mlinvoice.eng.php
 */
class InvoiceAction extends AbstractFormAction
{
    /**
     * Constructor
     *
     * @param Translator      $translator      Translator
     * @param SessionInterface $session Session
     */
    public function __construct(
        Translator $translator,
        FormService $formService,
        SessionInterface $session,
        InvoiceRepository $invoiceRepository,
        EntityManagerInterface $entityManager,
        SettingsManager $settingsManager,
        NumberFormatter $numberFormatter,
        protected InvoiceStateRepository $invoiceStateRepository,
        protected CustomPriceMapRepository $customPriceMapRepository,
        protected ProductRepository $productRepository,
        protected AttachmentRepository $attachmentRepository,
    ) {
        parent::__construct(
            $translator,
            'invoice',
            $formService,
            $session,
            $invoiceRepository,
            $entityManager,
            $settingsManager,
            $numberFormatter,
        );
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
        if ($result = parent::__invoke($request, $response, $args)) {
            return $result;
        }

        $newLocation = null;
        $openWindow = null;
        if ($redirect = $request->getParsedBody()['redirect'] ?? $request->getQueryParams()['redirect'] ?? null) {
            foreach ($this->formConfig['fields'] as $elem) {
                if ($elem['name'] == $redirect) {
                    if ($elem['style'] == 'redirect') {
                        $newLocation = str_replace(
                            '_ID_', (string)$this->id, $elem['listquery']
                        );
                    } elseif ($elem['style'] == 'openwindow') {
                        $openWindow = str_replace(
                            '_ID_', (string)$this->id, $elem['listquery']
                        );
                    }
                }
            }
        }

        // Update from template if requested:
        if (!$this->id && ($templateId = $request->getQueryParams()['template'] ?? null)) {
            if (!($invoiceTemplate = $this->repository->find($templateId))) {
                throw new \Exception('RecordNotFound');
            }
            try {
                $this->entityManager->beginTransaction();
                $this->updateFromInvoice($invoiceTemplate, true);
                $invoiceTemplate->advanceInvoiceIntervalDate();
                $this->repository->persistEntity($invoiceTemplate);
                $this->entityManager->commit();
            } catch (\Exception $e) {
                $this->entityManager->rollback();
                throw $e;
            }
        }

        $data = [
            'data' => $this->entity->toArray(true) ?? $this->formService->getFormDefaultValues($this->formConfig),
            'childDefaults' => $this->formService->getFormDefaultValues($this->formConfig['childFormConfig']),
            'formConfig' => $this->formConfig,
            'id' => $this->id,
            'newLocation' => $newLocation,
            'openWindow' => $openWindow,
            'attachments' => $this->attachmentRepository->findAll(),
        ];
        return $this->getView($request)->render($response, 'invoice.html.twig', $data);
    }

    /**
     * Update the invoice from another invoice.
     *
     * @param Invoice $templateInvoice Source invoice
     * @param bool    $isTemplate      Is source invoice a template?
     *
     * @return void
     */
    protected function updateFromInvoice(Invoice $templateInvoice, bool $isTemplate): void
    {
        assert($this->entity instanceof Invoice);
        $invoiceData = $templateInvoice->toArray();
        unset($invoiceData['id']);
        unset($invoiceData['invoice_no']);
        $invoiceData['deleted'] = false;
        $invoiceData['archived'] = false;
        $invoiceData['paymentDate'] = null;
        $invoiceData['archived'] = false;
        $invoiceData['refundedInvoiceId'] = null;
        $invoiceData['intervalType'] = 0;
        $invoiceData['nextIntervalDate'] = null;

        $this->entity->exchangeArray($invoiceData);
        $paymentDays = $templateInvoice->getCompany()?->getPaymentDays()
            ?? $this->settingsManager->get('invoice_payment_days');
        $dueDate = new DateTime("+$paymentDays days");
        $this->entity->setInvoiceDate(new DateTime());
        $this->entity->setDueDate($dueDate);
        $this->entity->setState($this->invoiceStateRepository->find(1));
        if ($isTemplate) {
            $this->entity->setTemplateInvoice($templateInvoice);
        }
        $company = $this->entity->getCompany();

        foreach ($templateInvoice->getRows() as $templateRow) {
            $rowData = $templateRow->toArray();
            unset($rowData['id']);
            unset($rowData['invoiceId']);
            $row = new InvoiceRow();
            $row->exchangeArray($rowData);
            $product = $row->getProduct();
            // Take price from product, if any:
            if (null === $row->getPrice() && $product) {
                if (
                    $company
                    && ($price = $this->customPriceMapRepository->findByCompanyAndProduct($company, $product))
                ) {
                    $row->setPrice($price->getUnitPrice());
                    $row->setDiscount($price->getDiscount());
                    $row->setDiscountAmount($price->getDiscountAmount());
                } else {
                    $row->setPrice($product->getUnitPrice());
                    $row->setDiscount($product->getDiscount());
                    $row->setDiscountAmount($product->getDiscountAmount());
                }
                $row->setVat($product->getVatPercent());
                $row->setVatIncluded($product->getVatIncluded());
            }
            if (null !== $row->getDate()) {
                $row->setDate(new DateTime());
            }
            $this->entity->addRow($row);

        }

        // Update product stock balance
        if (null !== $product) {
            $this->productRepository->updateStockBalance(null, $row['product_id'], $row['pcs']);
        }
    }
}
