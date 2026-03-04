<?php
/**
 * JSON Action.
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
use Exception;
use GuzzleHttp\Psr7\Response;
use MLInvoice\Config\SettingsManager;
use MLInvoice\Database\DatabaseUpdater;
use MLInvoice\Database\Entity\Invoice;
use MLInvoice\Database\Entity\InvoiceRow;
use MLInvoice\Database\Repository\BaseRepository;
use MLInvoice\Database\Repository\InvoiceRepository;
use MLInvoice\Database\Repository\InvoiceRowRepository;
use MLInvoice\Database\Repository\InvoiceStateRepository;
use MLInvoice\Database\Repository\ProductRepository;
use MLInvoice\Database\Repository\UserRepository;
use MLInvoice\Form\FormService;
use MLInvoice\I18n\NumberFormatter;
use MLInvoice\I18n\Translator;
use MLInvoice\Import\ImportFile;
use MLInvoice\Import\ImportStatement;
use MLInvoice\InvoicePrinter\InvoicePrinterBlank;
use MLInvoice\InvoicePrinter\InvoicePrinterFinvoiceSOAP;
use MLInvoice\List\ListService;
use MLInvoice\Search\SearchService;
use MLInvoice\Session\Memory;
use MLInvoice\Updater\Updater;
use MLInvoice\Utils\DateUtils;
use Odan\Session\SessionInterface;
use Odan\Session\SessionManagerInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Search;
use Slim\Routing\RouteContext;
use Slim\Views\Twig;

/**
 * JSON Action.
 *
 * @category MLInvoice
 * @package  MLInvoice\Action
 * @author   Ere Maijala <ere@labs.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://labs.fi/mlinvoice.eng.php
 */
class JsonAction extends AbstractAction
{
    /**
     * Result as an array
     *
     * @var ?array
     */
    protected ?array $result = [];

    /**
     * HTTP status code
     *
     * @var int
     */
    protected int $httpStatusCode = 200;

    /**
     * Constructor
     *
     * @param array $config Configuration
     * @param string $dbPrefix Database table prefix
     * @param Translator      $translator      Translator
     * @param ContainerInterface $container Container
     * @param SessionInterface $session Session
     */
    public function __construct(
        Translator $translator,
        #[Inject('config')] protected array $config,
        #[Inject('dbPrefix')] protected string $prefix,
        protected DateUtils $dateUtils,
        protected NumberFormatter $numberFormatter,
        protected ContainerInterface $container,
        protected SessionInterface $session,
        protected SettingsManager $settingsManager,
        protected EntityManagerInterface $entityManager,
        protected ListService $listService,
        protected FormService $formService,
        protected InvoiceRepository $invoiceRepository,
        protected InvoiceRowRepository $invoiceRowRepository,
        protected InvoiceStateRepository $invoiceStateRepository,
        protected ProductRepository $productRepository,
        protected BaseRepository $baseRepository,
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
        parent::__invoke($request, $response, $args);

        $func = $this->getPostOrQuery('func');

        switch ($func) {
        case 'get_company':
        case 'get_company_contact':
        case 'get_product':
        case 'get_invoice':
        case 'get_invoice_row':
        case 'get_base':
        case 'get_print_template':
        case 'get_invoice_state':
        case 'get_invoice_type':
        case 'get_row_type':
        case 'get_print_template':
        case 'get_company':
        case 'get_session_type':
        case 'get_delivery_terms':
        case 'get_delivery_method':
        case 'get_default_value':
        case 'get_attachment':
        case 'get_send_api_config':
            $this->printJsonRecord(substr($func, 4));
            break;
        case 'get_user':
            $this->printJsonRecord('users');
            break;

        case 'put_company':
        case 'put_product':
        case 'put_invoice':
        case 'put_base':
        case 'put_print_template':
        case 'put_invoice_state':
        case 'put_invoice_type':
        case 'put_row_type':
        case 'put_print_template':
        case 'put_user':
        case 'put_session_type':
        case 'put_delivery_terms':
        case 'put_delivery_method':
        case 'put_default_value':
        case 'put_attachment':
        case 'put_invoice_attachment':
            $this->saveJsonRecord(substr($func, 4), '');
            break;

        case 'delete_invoice_row':
        case 'delete_default_value':
        case 'delete_send_api_config':
        case 'delete_attachment':
        case 'delete_invoice_attachment':
            $this->deleteJsonRecord(substr($func, 7));
            break;

        case 'put_send_api_config':
            $this->saveJsonRecord(substr($func, 4), 'base_id');
            break;
        case 'get_send_api_configs':
            $this->printJsonRecords('send_api_config', 'base_id', 'name');
            break;

        case 'session_type':
        case 'user':
            if ($this->session->get('accessLevel') != MLINVOICE_USER_ROLE_ADMIN) {
                $this->setHttpStatus(400);
                break;
            }
            $this->saveJsonRecord($func, '');
            break;

        case 'get_companies':
            $this->printJsonRecords('company', '', 'company_name');
            break;

        case 'get_company_contacts':
            $this->printJsonRecords('company_contact', 'company_id', 'contact_person');
            break;

        case 'delete_company_contact':
            $this->deleteJsonRecord('company_contact');
            break;

        case 'put_company_contact':
            $this->saveJsonRecord('company_contact', 'company_id');
            break;

        case 'get_products':
            $this->printJsonRecords('product', '', 'product_name');
            break;

        case 'get_row_types':
            $this->printJsonRecords('row_type', '', 'order_no');
            break;

        case 'get_invoice_rows':
            $this->printJsonRecords('invoice_row', 'invoice_id', 'order_no');
            break;

        case 'put_invoice_row':
            $this->saveJsonRecord('invoice_row', 'invoice_id');
            break;

        case 'get_invoice_template':
            $this->printJsonRecord('invoice');
            break;
        case 'put_invoice_template':
            $this->saveJsonRecord('invoice', '');
            break;
        case 'get_invoice_template_row':
            $this->printJsonRecord('invoice_template_row');
            break;
        case 'get_invoice_template_rows':
            $this->printJsonRecords('invoice_template_row', 'invoice_id', 'order_no');
            break;
        case 'put_invoice_template_row':
            $this->saveJsonRecord('invoice_template_row', 'invoice_id');
            break;
        case 'delete_invoice_template_row':
            $this->deleteJsonRecord('invoice_template_row');
            break;
        case 'delete_invoice_template_attachment':
            $this->deleteJsonRecord('invoice_attachment');
            break;

        case 'get_offer':
            $this->printJsonRecord('offer');
            break;
        case 'put_offer':
            $this->saveJsonRecord('offer', '');
            break;
        case 'get_offer_row':
            $this->printJsonRecord('offer_row');
            break;
        case 'get_offer_rows':
            $this->printJsonRecords('offer_row', 'invoice_id', 'order_no');
            break;
        case 'put_offer_row':
            $this->saveJsonRecord('offer_row', 'invoice_id');
            break;
        case 'delete_offer_row':
            $this->deleteJsonRecord('offer_row');
            break;
        case 'delete_offer_attachment':
            $this->deleteJsonRecord('invoice_attachment');
            break;

        case 'get_custom_prices':
            $customPrice = getCustomPriceSettings(
                $this->getPostOrQuery('companyId')
            );
            header('Content-Type: application/json');
            $this->setResult($customPrice);
            break;

        case 'put_custom_prices':
            if ($this->session->get('accessLevel') == MLINVOICE_USER_ROLE_READONLY) {
                return $response->withStatus(403);
            }
            $data = $this->request->getBody();
            if (!$data) {
                return $response->withStatus(400);
            }
            setCustomPriceSettings(
                $data['company_id'],
                $data['discount'],
                $data['multiplier'],
                dateConvYmd2DBDate($data['valid_until'])
            );
            header('Content-Type: application/json');
            $this->setResult(['status' => 'ok']);
            break;

        case 'delete_custom_prices':
            if ($this->session->get('accessLevel') == MLINVOICE_USER_ROLE_READONLY) {
                return $response->withStatus(403);
            }
            $data = $this->request->getBody();
            if (!$data) {
                return $response->withStatus(400);
            }
            deleteCustomPriceSettings($data['company_id']);
            header('Content-Type: application/json');
            $this->setResult(['status' => 'ok']);
            break;

        case 'get_custom_price':
            $customPrice = getCustomPrice(
                $this->getPostOrQuery('company_id'),
                $this->getPostOrQuery('product_id')
            );
            header('Content-Type: application/json');
            $this->setResult($customPrice);
            break;

        case 'put_custom_price':
            if ($this->session->get('accessLevel') == MLINVOICE_USER_ROLE_READONLY) {
                return $response->withStatus(403);
            }
            $data = $this->request->getBody();
            if (!$data) {
                return $response->withStatus(400);
                return;
            }
            $unitPrice = (float)$data['unit_price'];
            setCustomPrice(
                $data['company_id'],
                $data['product_id'],
                $unitPrice
            );
            header('Content-Type: application/json');
            $this->setResult(
                [
                    'status' => 'ok',
                    'unit_price' => $unitPrice
                ]
            );
            break;

        case 'delete_custom_price':
            if ($this->session->get('accessLevel') == MLINVOICE_USER_ROLE_READONLY) {
                return $response->withStatus(403);
            }
            $data = $this->request->getBody();
            if (!$data) {
                return $response->withStatus(403);
            }
            deleteCustomPrice($data['company_id'], $data['product_id']);
            $product = getProduct($data['product_id']);
            $unitPrice = $product['unit_price'];
            if ($unitPrice) {
                $customPrice = getCustomPriceSettings($data['company_id']);
                if ($customPrice && $customPrice['valid']) {
                    $unitPrice -= $unitPrice * $customPrice['discount'] / 100;
                    $unitPrice *= $customPrice['multiplier'];
                }
            }
            header('Content-Type: application/json');
            $this->setResult(
                [
                    'status' => 'ok',
                    'unit_price' => $unitPrice
                ]
            );
            break;

        case 'add_reminder_fees':
            $invoiceId = $this->getPostOrQuery('id', '0');
            $errors = $this->addReminderFees((int)$invoiceId);
            if ($errors) {
                $ret = ['status' => 'error', 'errors' => $errors];
            } else {
                $ret = ['status' => 'ok'];
            }
            $this->setResult($ret);
            break;

        case 'get_invoice_defaults':
            $baseId = (int)$this->getPostOrQuery('base_id', '0');
            $companyId = (int)$this->getPostOrQuery('company_id', '0');
            $invoiceId = (int)$this->getPostOrQuery('id', '0');
            $invoiceDate = $this->getPostOrQuery('invoice_date', date('Y-m-d'));
            $intervalType = (int)$this->getPostOrQuery('interval_type', '0');
            $invoiceNumber = $this->getPostOrQuery('invoice_no', '0');

            $defaults = $this->getInvoiceDefaults(
                $invoiceId, $baseId, $companyId, $invoiceDate, $intervalType, $invoiceNumber
            );

            $this->setResult($defaults);
            break;

        case 'get_table_columns':
            $table = $this->getPostOrQuery('table', '');
            if (!$table) {
                return $response->withStatus(400);
            }
            if ($this->session->get('accessLevel') != MLINVOICE_USER_ROLE_ADMIN && 'account_statement' !== $table) {
                return $response->withStatus(403);
            }
            // account_statement is a pseudo table for account statement "import"
            if ($table == 'account_statement') {
                $this->setResult(
                    [
                        'columns' => [
                            [
                                'id' => 'date',
                                'name' => $this->translator->translate('ImportStatementPaymentDate')
                            ],
                            [
                                'id' => 'amount',
                                'name' => $this->translator->translate('ImportStatementAmount')
                            ],
                            [
                                'id' => 'refnr',
                                'name' => $this->translator->translate('ImportStatementRefNr')
                            ],
                            [
                                'id' => 'correction',
                                'name' => $this->translator->translate('ImportStatementCorrectionRow')
                            ]
                        ]
                    ]
                );
                break;
            }

            if (!tableNameValid($table)) {
                $this->setResult(['error' => 'Invalid table name'])
                    ->setHttpStatus(400);
            }

            $columns = [];
            $res = dbQueryCheck("select * from {$this->prefix}$table where 1=2");
            $field_count = mysqli_num_fields($res);
            for ($i = 0; $i < $field_count; $i ++) {
                $field_def = mysqli_fetch_field($res);
                $columns[] = ['name' => $field_def->name];
            }
            if ('company' === $table || 'company_contact' === $table) {
                $columns[] = ['name' => 'tags'];
            } elseif ('custom_price_map' === $table) {
                $columns[] = ['name' => 'company_id'];
            }
            $this->setResult(compact('columns'));
            break;

        case 'get_import_preview':
            $table = $this->getPostOrQuery('table', '');
            if ($table == 'account_statement') {
                include 'import_statement.php';
                $import = $this->container->get(ImportStatement::class);
            } else {
                if ($this->session->get('accessLevel') != MLINVOICE_USER_ROLE_ADMIN) {
                    return $response->withStatus(403);
                }
                include 'import.php';
                $import = $this->container->get(ImportFile::class);
            }
            $import->createImportPreview();
            break;

        case 'get_list':
            $listFunc = $this->getPostOrQuery('listfunc', '');

            $strList = $this->getPostOrQuery('table', '');
            if (!$strList) {
                return $response->withStatus(400, 'Table must be defined');
            }

            $tableId = $this->getPostOrQuery('tableid', '');

            $listService = $this->container->get(ListService::class);
            $listConfig = $listService->getListConfig($strList);
            if (!$listConfig) {
                return $response->withStatus(400, 'Invalid table name');
            }

            $startRow = intval($this->getPostOrQuery('start', '-1'));
            $rowCount = intval($this->getPostOrQuery('length', '-1'));
            $sort = [];
            $columns = $this->getPostOrQuery('columns', []);
            if ($orderCols = $this->getPostOrQuery('order', [])) {
                foreach ($orderCols as $orderCol) {
                    if (!isset($orderCol['column'])) {
                        continue;
                    }
                    $sortColumn = $orderCol['column'];
                    $sortDir = $orderCol['dir'];
                    $sort[] = [
                        'column' => intval($sortColumn),
                        'direction' => $sortDir === 'desc' ? 'desc' : 'asc'
                    ];
                }
            }
            $search = $this->getPostOrQuery('search');
            $searchId = $this->getPostOrQuery('searchId');
            $format = $this->getPostOrQuery('format');
            $filter = empty($search['value']) ? '' : $search['value'];
            $query = json_decode($this->getPostOrQuery('query', '{}'), true);
            $companyId = 'product' === $strList ? $this->getPostOrQuery('company', null) : null;

            $listData = $listService->createJSONList(
                $listFunc, $strList, $startRow, $rowCount, $sort, $filter, $query,
                intval($this->getPostOrQuery('draw', '1')), $tableId, $companyId,
                $searchId ? intval($searchId) : null,
                $format
            );
            $this->setResult($listData);
            $this->container->get(Memory::class)->set(
                $tableId,
                compact(
                    'strList', 'startRow', 'rowCount', 'sort', 'filter'
                )
            );
            break;

        case 'get_invoice_total_sum':
            $search = $this->getPostOrQuery('searchId');
            $query = json_decode($this->getPostOrQuery('query', '{}'), true);
            header('Content-Type: application/json');
            $totals = $this->container->get(ListService::class)->getInvoiceListTotal(
                $query,
                $search ? intval($search) : null
            );
            $this->setResult($totals);
            break;

        case 'get_selectlist':
            $table = $this->getPostOrQuery('table', '');
            if (!$table) {
                return $response->withStatus(400);
            }

            $pageLen = intval($this->getPostOrQuery('pagelen', '10'));
            $page = intval($this->getPostOrQuery('page', '1')) - 1;
            $q = $this->getPostOrQuery('q', []);
            $filter = $q['term'] ?? '';
            $sort = $this->getPostOrQuery('sort', '');
            $id = $this->getPostOrQuery('id', '');
            $filterType = $this->getPostOrQuery('type', '');

            header('Content-Type: application/json');
            $listData = $this->listService->createJSONSelectList(
                $table, $page * $pageLen, $pageLen, $filter, $filterType, $sort, $id, $this->request
            );
            $this->setResult($listData);
            break;

        case 'update_multiple':
            header('Content-Type: application/json');
            $this->updateMultipleRows();
            break;

        case 'update_row_order':
            header('Content-Type: application/json');
            $this->updateRowOrder();
            break;

        case 'update_stock_balance':
            if ($this->session->get('accessLevel') == MLINVOICE_USER_ROLE_READONLY) {
                return $response->withStatus(403);
            }
            $productId = $this->getPostOrQuery('product_id', 0);
            $change = $this->getPostOrQuery('stock_balance_change', 0);
            $desc = $this->getPostOrQuery('stock_balance_change_desc', '');
            header('Content-Type: application/json');
            $this->updateStockBalance($productId, $change, $desc);
            break;

        case 'get_stock_balance_rows':
            $productId = $this->getPostOrQuery('product_id', 0);
            if (!$productId) {
                break;
            }
            $rows = dbParamQuery(
                <<<EOT
        SELECT l.time, u.name, l.stock_change, l.description FROM {$this->prefix}stock_balance_log l
        INNER JOIN {$this->prefix}users u ON l.user_id=u.id WHERE product_id=? ORDER BY time DESC
        EOT
                ,
                [$productId]
            );
            $html = '';
            foreach ($rows as $row) {
                ?>
        <tr>
            <td><?php echo $this->dateUtils->dbDateTimeToDateTimeString($row['time'])?></td>
            <td><?php echo $row['name']?></td>
            <td><?php echo $this->numberFormatter->roundNumber($row['stock_change'])?></td>
            <td><?php echo $row['description']?></td>
        </tr>
                <?php
            }
            break;

        case 'get_send_api_services':
            header('Content-Type: application/json');
            echo $this->getSendApiServices($this->getPostOrQuery('invoice_id'), $this->getPostOrQuery('base_id'));
            break;

        case 'add_invoice_attachment':
            if ($this->session->get('accessLevel') == MLINVOICE_USER_ROLE_READONLY) {
                return $response->withStatus(403);
            }
            $this->addInvoiceAttachment();
            break;

        case 'get_invoice_attachments':
            $this->printJsonRecords('invoice_attachment', 'invoice_id', 'order_no');
            break;

        case 'get_update_info':
            include 'updater.php';
            $updater = $this->container->get(Updater::class);
            $res = $updater->checkForUpdates();
            echo json_encode($res);
            break;

        case 'save_search':
            include_once 'search.php';
            $search = $this->container->get(SearchService::class);
            $res = $search->saveSearch(getQuery('name'), $search->getSearchGroups($_GET));
            echo json_encode($res);
            break;

        case 'noop':
            // Session keep-alive
            $this->setResult(null)->setHttpStatus(204);
            break;

        default:
            $this->setResult(['error' => 'Not found'])->setHttpStatus(404);
        }

        $response = $response->withStatus($this->httpStatusCode);
        if (null !== $this->result) {
            $body = json_encode(
                $this->result,
                JSON_INVALID_UTF8_IGNORE
            ) ?: json_encode(['error' => 'Encode failed: ' . json_last_error_msg()]);
            $response->getBody()->write($body);
        }
        return $response;
    }

    /**
     * Output a JSON record
     *
     * @param string $table    Table name
     * @param int    $id       Record ID
     * @param array  $warnings Warnings to include in the output
     *
     * @return void
     *
     * @todo Convert to use ORM
     */
    protected function printJsonRecord($table, $id = false, $warnings = null): void
    {
        if ($id === false) {
            $id = $this->getPostOrQuery('id', '');
        }
        if ($id) {
            if (str_starts_with($table, $this->prefix)) {
                $table = substr($table, strlen($this->prefix));
            }
            $select = 'SELECT t.*';
            $from = "FROM {$this->prefix}$table t";
            $where = 'WHERE t.id=?';

            if ($table === 'invoice_row') {
                // Include product name and code
                $select .= ", CASE WHEN LENGTH(p.product_code) = 0 THEN IFNULL(p.product_name, '') ELSE CONCAT_WS(' ', p.product_code, IFNULL(p.product_name, '')) END as product_id_text";
                $from .= " LEFT OUTER JOIN {$this->prefix}product p on (p.id = t.product_id)";
            }

            $query = "$select $from $where";
            $rows = $this->entityManager->getConnection()->executeQuery($query, [$id]);
            if (!$rows->rowCount()) {
                $this->setResult([])->setHttpStatus(404);
                return;
            }
            $row = $rows->fetchAssociative();
            $row = $this->convertToApi($row, $table);

            // Include any custom price for a product
            if ($table === 'product' && ($companyId = $this->getPostOrQuery('company_id'))) {
                $customPriceSettings = getCustomPriceSettings($companyId);
                if (empty($customPriceSettings['valid'])) {
                    $customPriceSettings = null;
                }
                $customPrice = null;
                if ($customPriceSettings) {
                    $customPrice = getCustomPrice($companyId, $id);
                    if (!$customPrice) {
                        $unitPrice = $row['unit_price'];
                        $unitPrice -= $unitPrice * $customPriceSettings['discount']
                            / 100;
                        $unitPrice *= $customPriceSettings['multiplier'];
                        $customPrice = [
                            'unit_price' => $unitPrice
                        ];
                    }
                }
                $row['custom_price'] = $customPrice ? $customPrice : null;
            }

            header('Content-Type: application/json');
            $row['warnings'] = $warnings;
            $this->setResult($row);
        }
    }

    /**
     * Output multiple records
     *
     * @param string $table       Table name
     * @param string $parentIdCol Parent ID column name
     * @param string $sort        Sort rules
     *
     * @return void
     */
    protected function printJsonRecords($table, $parentIdCol, $sort)
    {
        $select = 'SELECT t.*';
        $from = "FROM {$this->prefix}$table t";

        if ($table == 'invoice_row') {
            // Include product name, product code, product weight and row type name
            $select .= <<<EOT
    , CASE WHEN LENGTH(p.product_code) = 0 THEN IFNULL(p.product_name, '')
    ELSE CONCAT_WS(' ', p.product_code, IFNULL(p.product_name, ''))
    END as product_id_text, p.weight as product_weight
    EOT;
            $from .= " LEFT OUTER JOIN {$this->prefix}product p on (p.id = t.product_id)";
            $select .= ', rt.name as type_id_text';
            $from .= " LEFT OUTER JOIN {$this->prefix}row_type rt on (rt.id = t.type_id)";
        }

        $where = '';
        $params = [];
        $id = $this->getPostOrQuery('parent_id', '');
        if ($id && $parentIdCol) {
            $where .= " WHERE t.$parentIdCol=?";
            $params[] = $id;
        }
        if (!$this->settingsManager->get('show_deleted_records') && 'send_api_config' !== $table
            && 'attachment' !== $table && 'invoice_attachment' !== $table
        ) {
            if ($where) {
                $where .= ' AND t.deleted=0';
            } else {
                $where = ' WHERE t.deleted=0';
            }
        }

        $query = "$select $from $where";
        if ($sort) {
            $query .= " ORDER BY $sort";
        }
        $rows = $this->entityManager->getConnection()->executeQuery($query, $params)->fetchAllAssociative();
        $records = [];
        foreach ($rows as $row) {
            $records[] = $this->convertToApi($row, $table);
        }
        $this->setResult(compact('records'));
    }

    /**
     * Convert a record to API format
     *
     * @param array  $row   Record row
     * @param string $table Table name
     *
     * @return array
     */
    protected function convertToApi($row, $table)
    {
        $form = $table;
        $parentId = null;
        switch ($table) {
        case 'base':
            $row['logo_filedata'] = $row['logo_filedata'] ? base64_encode($row['logo_filedata']) : null;
            break;
        case 'attachment':
        case 'invoice_attachment':
            unset($row['filedata']);
            $row['filesize_readable'] = fileSizeToHumanReadable($row['filesize']);
            $parentId = $row['invoice_id'];
            break;
        case 'company':
            $row['tags'] = $this->getTagsArray('company', $row['id']);
            break;
        case 'company_contact':
            $row['tags'] = $this->getTagsArray('contact', $row['id']);
            $parentId = $row['company_id'];
            break;
        case 'invoice_row':
            if (isset($row['type_id_text'])) {
                $row['type_id_text'] = $this->translator->translate($row['type_id_text']);
            }
            $parentId = $row['invoice_id'];
            break;
        case 'users':
            unset($row['password']);
            $form = 'user';
            break;
        }

        $formConfig = $this->formService->getFormConfig($form, $row['id'] ?? null, $this->request, false, $parentId);
        foreach ($formConfig['fields'] as $field) {
            $name = $field['name'];
            if ('INTDATE' === $field['type'] && isset($row[$name])) {
                $row[$name] = $this->dateUtils->dbDateToDate($row[$name], 'Y-m-d');
            }
        }

        return $row;
    }

    /**
     * Convert a record from API format
     *
     * @param array  $row   Record row
     * @param string $table Table name
     *
     * @return array
     */
    protected function convertFromApi($row, $table)
    {
        return $row;
    }

    /**
     * Save a record
     *
     * @param string $table         Table name
     * @param string $parentKeyName Parent ID column name
     *
     * @return void
     */
    protected function saveJsonRecord($table, $parentKeyName)
    {
        if ($this->session->get('accessLevel') == MLINVOICE_USER_ROLE_READONLY) {
            $this->setHttpStatus(403);
            return;
        }

        $data = $this->request->getParsedBody();
        if (!$data) {
            $this->setHttpStatus(400);
            return;
        }
        $id = !empty($data['id']) ? (int)$data['id'] : null;
        $new = $id ? false : true;
        unset($data['id']);
        $formConfig = $this->formService
            ->getFormConfig($table, $id, $this->request, false, $parentKeyName ? $data[$parentKeyName] : null);

        $onPrint = false;
        if (isset($data['onPrint'])) {
            $onPrint = $data['onPrint'];
            unset($data['onPrint']);
        }

        // Allow partial update for invoice attachments. This is a safety check since the
        // partial update mechanism might hide issues with other record types.
        $partial = !$new && 'invoice_attachment' === $table;

        $data = $this->convertFromApi($data, $table);

        $warnings = '';
        try {
            $res = $this->formService->saveFormData(
                $formConfig['table'], $id, $formConfig, $data, $warnings, $parentKeyName,
                $parentKeyName ? $data[$parentKeyName] : null, $onPrint, $partial
            );
        } catch (\Exception $e) {
            $this->setResult(['error' => $e->getMessage()])->setHttpStatus(500);
            return;
        }
        if ($res !== true) {
            $this->setResult(['missing_fields' => $res, 'warnings' => $warnings])
                ->setHttpStatus($warnings ? 409 : 200);
            return;
        }

        if ($new) {
            $this->setHttpStatus(201);
        }
        $this->printJsonRecord($formConfig['table'], $id, $warnings);
    }

    /**
     * Delete a record
     *
     * @param string $form Form name
     *
     * @return void
     */
    protected function deleteJsonRecord($form)
    {
        if ($this->session->get('accessLevel') == MLINVOICE_USER_ROLE_READONLY) {
            $this->setHttpStatus(403);
            return;
        }

        $ids = $this->getPostOrQuery('id', '');
        if ($ids) {
            foreach ((array)$ids as $id) {
                $this->deleteRecord($form, (int)$id);
            }
            $this->setResult(['status' => 'ok']);
        }
    }

    /**
     * Update multiple rows
     *
     * @return void
     */
    protected function updateMultipleRows()
    {
        if ($this->session->get('accessLevel') == MLINVOICE_USER_ROLE_READONLY) {
            $this->setHttpStatus(403);
            return;
        }

        $request = $this->request->getBody();
        if (!$request) {
            $this->setHttpStatus(400);
            return;
        }

        $strForm = $request['table'];
        $formConfig = getFormConfig($strForm, 'json', null, $request['parentId']);

        $warnings = '';
        foreach ($request['ids'] as $id) {
            $id = (int)$id;
            // Set fields anew for every row since saveFormData returns the whole record
            $data = $this->convertFromApi($request['changes'], $request['table']);

            $res = saveFormData(
                $this->prefix . $request['table'], $id, $formConfig, $data, $warnings,
                false, false, false, true
            );
            if ($res !== true) {
                $this->setResult(['missing_fields' => $res, 'warnings' => $warnings])
                    ->setHttpStatus($warnings ? 409 : 200);
            }
        }

        $this->setResult(['status' => 'ok']);
    }

    /**
     * Update row order based on POST data
     *
     * @return void
     */
    protected function updateRowOrder()
    {
        if ($this->session->get('accessLevel') == MLINVOICE_USER_ROLE_READONLY) {
            $this->setHttpStatus(403);
            return;
        }

        $request = $this->request->getBody();
        if (!$request) {
            $this->setHttpStatus(400);
            return;
        }

        foreach ($request['order'] as $id => $orderNo) {
            dbParamQuery(
                "UPDATE {$this->prefix}{$request['table']} SET order_no=? WHERE id=?",
                [$orderNo, $id]
            );
        }

        $this->setResult(['status' => 'ok']);
    }

    /**
     * Update product stock balance
     *
     * @param int    $productId Product ID
     * @param int    $change    Change in balance
     * @param string $desc      Change description
     *
     * @return void
     */
    protected function updateStockBalance($productId, $change, $desc)
    {
        $missing = [];
        if (!$change) {
            $missing[] = $this->translator->translate('StockBalanceChange');
        }
        if (!$desc) {
            $missing[] = $this->translator->translate('StockBalanceChangeDescription');
        }

        if ($missing) {
            return $this->setResult(['missing_fields' => $missing]);
        }

        $rows = dbParamQuery(
            "SELECT stock_balance FROM {$this->prefix}product WHERE id=?",
            [$productId]
        );
        if (!$rows) {
            $this->setResult(
                ['status' => 'error', 'errors' => $this->translator->translate('ErrInvalidValue')]
            );
        }
        $row = $rows[0];
        $balance = $row['stock_balance'];
        $balance += $change;
        dbParamQuery(
            "UPDATE {$this->prefix}product SET stock_balance=? where id=?",
            [$balance, $productId]
        );
        dbParamQuery(
            <<<EOT
    INSERT INTO {$this->prefix}stock_balance_log
    (user_id, product_id, stock_change, description) VALUES (?, ?, ?, ?)
    EOT
            ,
            [
                $_SESSION['sesUSERID'],
                $productId,
                $change,
                $desc
            ]
        );
        $this->setResult(['status' => 'ok', 'new_stock_balance' => $balance]);
    }

    /**
     * Delete a record by ID
     *
     * @param string $form Form name
     * @param int    $id   Record ID
     *
     * @return void
     */
    protected function deleteRecord(string $form, int $id): void
    {
        $conn = $this->entityManager->getConnection();
        $conn->beginTransaction();
        try {
            // Special case for invoice_row - update product stock balance
            if ($form === 'invoice_row') {
                $invoiceRow = $this->invoiceRowRepository->find((int)$id);
                $this->productRepository->updateStockBalance($invoiceRow, null, 0);
            }

            // Special case for invoice - update all products in invoice rows
            if ($form === 'invoice') {
                $invoice = $this->invoiceRepository->find($id);
                $rows = $invoice->getRows();
                foreach ($rows as $row) {
                    updateProductStockBalance($row, null, 0);
                }
            }
            $formConfig = $this->formService->getFormConfig($form);
            $table = $formConfig['table'];
            if ("[$this->prefix}send_api_config" === $table
                || "[$this->prefix}attachment" === $table
                || "[$this->prefix}invoice_attachment" === $table
            ) {
                $query = "DELETE FROM $table WHERE id=?";
            } else {
                $query = "UPDATE $table SET deleted=1 WHERE id=?";
            }
            $conn->executeQuery($query, [$id]);
        } catch (Exception $e) {
            $conn->rollBack();
            throw $e;
        }
        $conn->commit();
    }

    /**
     * Get send API services for the given invoice and base
     *
     * @param int $invoiceId Invoice ID
     * @param int $baseId    Base ID
     *
     * @return string
     */
    protected function getSendApiServices($invoiceId, $baseId)
    {
        $templateCandidates = dbParamQuery(
            "SELECT * FROM {$this->prefix}print_template WHERE deleted=0 and type=? and inactive=0 ORDER BY order_no",
            [isOffer($invoiceId) ? 'offer' : 'invoice']
        );
        $templates = [];
        foreach ($templateCandidates as $candidate) {
            $printer = getInvoicePrinter($candidate['filename']);
            if (null === $printer) {
                continue;
            }
            $uses = class_uses($printer);
            if (in_array('InvoicePrinterEmailTrait', $uses)
                || $printer instanceof InvoicePrinterFinvoiceSOAP
                || $printer instanceof InvoicePrinterBlank
            ) {
                continue;
            }
            $templates[] = $candidate;
        }

        $services = [];
        foreach (getSendApiConfigs($baseId) as $config) {
            $urlBase = [
                'func' => 'send_api',
                'invoice_id' => $invoiceId,
                'api_id' => $config['id']
            ];
            $items = [];
            foreach ($templates as $template) {
                $item = $urlBase;
                $item['template_id'] = $template['id'];
                $items[] = [
                    'href' => http_build_query($item),
                    'name' => $this->translator->translate($template['name'])
                ];
            }
            $services[] = [
                'name' => $config['name'] ? $config['name'] : $this->translator->translate($config['method']),
                'items' => $items
            ];
        }

        $this->setResult(['services' => $services]);
    }

    /**
     * Add an attachment to an invoice and return the new record
     *
     * @return string
     */
    protected function addInvoiceAttachment()
    {
        $newId = addAttachmentToInvoice($this->getPostOrQuery('id'), $this->getPostOrQuery('invoice_id'));
        $this->printJsonRecord('invoice_attachment', $newId);

    }

    /**
     * Handle error without disturbing actual output
     *
     * @param string $errno   Error code number
     * @param string $errstr  Error message
     * @param string $errfile File where error occurred
     * @param string $errline Line number of error
     *
     * @return bool           Always true to cancel default error handling
     */
    protected function handleError($errno, $errstr, $errfile, $errline)
    {
        global $phpErrors;
        $phpErrors[] = "[$errno] $errstr at $errfile:$errline";
        return true;
    }

    /**
     * Set result.
     *
     * @param ?array $result Result array
     *
     * @return static
     */
    protected function setResult(?array $result): static
    {
        global $phpErrors;
        if ($phpErrors && null !== $result) {
            $result['php_errors'] = $phpErrors;
        }
        $this->result = $result;
        return $this;
    }

    /**
     * Set HTTP status code.
     *
     * @param int $statusCode Status code
     *
     * @return static
     */
    protected function setHttpStatus(int $statusCode): static
    {
        $this->httpStatusCode = $statusCode;
        return $this;
    }

    /**
     * Add reminder fees
     *
     * @param int $intInvoiceId Invoice ID
     *
     * @return ?string Any error messages
     */
    protected function addReminderFees(int $invoiceId): ?string
    {
        if (!($invoice = $this->invoiceRepository->find($invoiceId))) {
            return $this->translator->translate('RecordNotFound');
        }
        $state = $invoice->getState();
        $stateId = $state?->getId();
        if (in_array($stateId, [3, 4])) {
            return $this->translator->translate('WrongStateForReminderFee');
        }

        if (!($dueDate = $invoice->getDueDate())) {
            return $this->translator->translate('InvoiceNotOverdue');
        }
        $daysOverdue = $dueDate->diff(new DateTime(), true)->d;
        if ($daysOverdue <= 0) {
            return $this->translator->translate('InvoiceNotOverdue');
        }

        // Update invoice state
        if ($stateId == 1 || $stateId == 2) {
            $invoice->setState($this->invoiceStateRepository->find(5));
        } elseif ($stateId == 5) {
            $invoice->setState($this->invoiceStateRepository->find(6));
        }
        $this->invoiceRepository->persistEntity($invoice);

        // Remove any old notification fee and/or penalty interest:
        $today = (new DateTime())->format('y-m-d');
        foreach ($invoice->getRows() as $row) {
            if (
                $row->getReminder() == 1
                || ($row->getReminder() == 2 && $row->getDate()?->format('Y-m-d') == $today)
            ) {
                $row->setDeleted(true);
                $this->invoiceRowRepository->persistEntity($row);
            }
        }

        // Add reminder fee
        if ($this->settingsManager->get('invoice_notification_fee')) {
            $notificationFee = $this->settingsManager->get('invoice_notification_fee');
            if ((float)$notificationFee !== 0.0) {
                $row = new InvoiceRow();
                $row->setDescription($this->translator->translate('ReminderFeeDesc'))
                    ->setDate(new DateTime())
                    ->setPcs('1')
                    ->setPrice($notificationFee)
                    ->setVat('0')
                    ->setVatIncluded(0)
                    ->setDiscount('0')
                    ->setDiscountAmount('0')
                    ->setOrderNo(-2)
                    ->setReminder(2);
                $invoice->addRow($row);
                $this->invoiceRowRepository->persistEntity($row);
            }
        }
        // Add penalty interest
        $penaltyInterest = getSetting('invoice_penalty_interest');
        if ($penaltyInterest) {
            $totSumVAT = 0;
            foreach ($invoice->getRows() as $row) {
                if ($row->getReminder()) {
                    continue;
                }
                $rowSum = $row->calculateRowSum($row);
                $totSumVAT += $rowSum['sumVat'];
            }
            $penaltyInterestAmount = $totSumVAT * $penaltyInterest / 100 * $daysOverdue / 360;

            if ($penaltyInterestAmount) {
                $row = new InvoiceRow();
                $row->setDescription($this->translator->translate('PenaltyInterestDesc'))
                    ->setDate(new DateTime())
                    ->setPcs('1')
                    ->setPrice((string)$penaltyInterestAmount)
                    ->setVat('0')
                    ->setVatIncluded(0)
                    ->setDiscount('0')
                    ->setDiscountAmount('0')
                    ->setOrderNo(-1)
                    ->setReminder(1);
                $invoice->addRow($row);
                $this->invoiceRowRepository->persistEntity($row);
            }
        }
        return null;
    }

    /**
     * Get default values for an invoice
     *
     * @param int    $invoiceId     Invoice ID
     * @param ?int    $baseId        Base ID
     * @param ?int    $companyId     Company ID
     * @param string $invoiceDate   Invoice date (Y-m-d)
     * @param int    $intervalType  Invoice interval
     * @param string $invoiceNumber Invoice number
     *
     * @return array
     */
    protected function getInvoiceDefaults(
        int $invoiceId,
        ?int $baseId,
        ?int $companyId,
        string $invoiceDate,
        int $intervalType,
        string $invoiceNumber
    ): array {
        $perYear = (bool)$this->settingsManager->get('invoice_numbering_per_year');

        // If the invoice already has an invoice number, verify that it's not in use in another invoice
        if ($invoiceNumber) {
            $dql = 'SELECT i FROM ' . Invoice::class . ' i WHERE i.deleted = 0 AND i.id != :id AND i.invoiceNo = :no';
            $params = [
                'id' => $invoiceId,
                'no' => $invoiceNumber
            ];
            if ($this->settingsManager->get('invoice_numbering_per_base') && $baseId) {
                if ($base = $this->baseRepository->find($baseId)) {
                    $dql .= ' AND i.base = :base';
                    $params['base'] = $base;
                }
            }
            if ($perYear) {
                $dql .= ' AND i.invoiceDate >= ' . $this->dateUtils->ymdToDbDate($invoiceDate);
            }
            $query = $this->entityManager->createQuery($dql)
                ->setParameters($params)
                ->setMaxResults(1);
            $rows = $query->getResult();
            if ($rows) {
                $invoiceNumber = 0;
            }
        }

        if (!$invoiceNumber) {
            $numberingBaseId = $this->settingsManager->get('invoice_numbering_per_base') && $baseId
                ? (int)$baseId
                : null;
            $maxNr = $this->getMaxInvoiceNumber($invoiceId, $numberingBaseId, $perYear);
            if ($maxNr === null && $perYear) {
                $maxNr = $this->getMaxInvoiceNumber($invoiceId, (int)$numberingBaseId, false);
            }
            $invoiceNumber = $maxNr + 1;
        }
        if ($invoiceNumber < 100) {
            $invoiceNumber = 100; // min ref number length is 3 + check digit, make sure invoice number matches that
        }

        $refNr = $invoiceNumber . $this->getRefNumberCheckDigit((string)$invoiceNumber);
        if ($this->settingsManager->get('invoice_create_rf_references')) {
            // RF Reference
            $refNr = createRFReference($refNr);
        }

        $strDate = date('Y-m-d');
        $strDueDate = date(
            'Y-m-d',
            mktime(0, 0, 0, (int)date('m'), date('d') + $this->getPaymentDays($companyId), (int)date('Y'))
        );
        switch ($intervalType) {
        case 2:
            $nextIntervalDate = date(
                'Y-m-d',
                mktime(0, 0, 0, date('m') + 1, (int)date('d'), (int)date('Y'))
            );
            break;
        case 3:
            $nextIntervalDate = date(
                'Y-m-d',
                mktime(0, 0, 0, (int)date('m'), (int)date('d'), date('Y') + 1)
            );
            break;
        case 4:
        case 5:
        case 6:
        case 7:
        case 8:
            $nextIntervalDate = date(
                'Y-m-d',
                mktime(0, 0, 0, date('m') + $intervalType - 2, (int)date('d'), (int)date('Y'))
            );
            break;
        default:
            $nextIntervalDate = '';
        }
        return [
            'invoice_no' => $invoiceNumber,
            'ref_no' => $refNr,
            'date' => $strDate,
            'due_date' => $strDueDate,
            'next_interval_date' => $nextIntervalDate
        ];
    }

    /**
     * Get the maximum invoice number with the given arguments
     *
     * @param int   $invoiceId Invoice ID
     * @param ?int  $baseId    Base ID
     * @param bool  $perYear   Whether to use year-based invoice numbering
     *
     * @return int
     */
    protected function getMaxInvoiceNumber(int $invoiceId, ?int $baseId, bool $perYear): int
    {
        $sql = 'SELECT max(cast(invoice_no as unsigned integer)) as maxnum'
            . " FROM {$this->prefix}invoice WHERE deleted = 0 AND id != :invoiceId";
        $params = [
            'invoiceId' => $invoiceId,
        ];
        if ($baseId !== null) {
            $sql .= ' AND base_id = :baseId';
            $params['baseId'] = $baseId;
        }
        if ($perYear) {
            $sql .= ' AND invoice_date >= ' . date('Y') . '0101';
        }
        return (int)$this->entityManager->getConnection()->executeQuery($sql, $params)->fetchOne();
    }

    /**
     * Calculate check digit for a reference number.
     *
     * @param string $refNo Reference number
     *
     * @return int
     */
    function getRefNumberCheckDigit(string $refNo): int
    {
        $astrWeight = [
            '1',
            '3',
            '7',
            '1',
            '3',
            '7',
            '1',
            '3',
            '7',
            '1',
            '3',
            '7',
            '1',
            '3',
            '7',
            '1',
            '3',
            '7',
            '1',
            '3',
            '7',
            '1',
            '3',
            '7',
            '1',
            '3',
            '7',
            '1',
            '3',
            '7',
            '1',
            '3',
            '7',
            '1',
            '3',
            '7',
            '1',
            '3',
            '7',
            '1',
            '3',
            '7',
            '1',
            '3',
            '7',
            '1',
            '3',
            '7',
            '1',
            '3',
            '7',
            '1',
            '3',
            '7',
            '1',
            '3',
            '7',
            '1',
            '3',
            '7',
            '1',
            '3',
            '7',
            '1',
            '3',
            '7',
            '1',
            '3',
            '7',
            '1',
            '3',
            '7',
            '1',
            '3',
            '7',
            '1',
            '3',
            '7',
            '1',
            '3',
            '7',
            '1',
            '3',
            '7',
            '1',
            '3',
            '7',
            '1',
            '3',
            '7',
            '1',
            '3',
            '7',
            '1',
            '3',
            '7',
            '1',
            '3',
            '7',
            '1',
            '3',
            '7',
            '1',
            '3',
            '7',
            '1',
            '3',
            '7',
            '1',
            '3',
            '7',
            '1',
            '3',
            '7'
        ];
        $charsReversed = array_reverse(
            explode('.', substr(chunk_split($refNo, 1, '.'), 0, -1))
        );

        $sum = 0;
        foreach ($charsReversed as $value) {
            $sum += $value * array_pop($astrWeight);
        }
        return (int)(ceil($sum / 10) * 10 - $sum);
    }

    /**
     * Get payment days for a company
     *
     * @param int $companyId Company ID
     *
     * @return int
     */
    protected function getPaymentDays(?int $companyId): int
    {
        if ($companyId) {
            $days = $this->entityManager->getConnection()->executeQuery(
                "SELECT payment_days FROM {$this->prefix}company WHERE id = ?",
                [$companyId]
            )->fetchOne();
            if ($days) {
                return (int)$days;
            }
        }
        return (int)$this->settingsManager->get('invoice_payment_days');
    }

    /**
     * Get tags for a record
     *
     * @param string $type Record type (company, contact)
     * @param int    $id   Record ID
     *
     * @return array
     */
    protected function getTagsArray(string $type, int $id): array
    {
        $tags = [];
        $rows = $this->entityManager->getConnection()->executeQuery(
            <<<EOT
    SELECT tag FROM {$this->prefix}{$type}_tag WHERE id IN (
        SELECT tag_id FROM {$this->prefix}{$type}_tag_link WHERE {$type}_id=?
    )
    EOT
            ,
            [$id]
        )->fetchAllAssociative();
        foreach ($rows as $tagRow) {
            $tags[] = $tagRow['tag'];
        }
        return $tags;
    }
}
