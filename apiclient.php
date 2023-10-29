<?php
/**
 * Client interface for online mailing APIs
 *
 * PHP version 7
 *
 * Copyright (C) Ere Maijala 2018-2021
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
require_once 'sqlfuncs.php';

/**
 * Client interface for online mailing APIs
 *
 * @category MLInvoice
 * @package  MLInvoice\Base
 * @author   Ere Maijala <ere@labs.fi>
 * @license  https://opensource.org/licenses/GPL-2.0 GNU Public License 2.0
 * @link     http://github.com/emaijala/MLInvoice
 */
class ApiClient
{
    /**
     * API config ID
     *
     * @var int
     */
    protected $apiConfigId;

    /**
     * Invoice
     *
     * @var array
     */
    protected $invoice;

    /**
     * Template
     *
     * @var array
     */
    protected $template;

    /**
     * Send a printout via an API
     *
     * @param string $apiConfigId API config ID
     * @param int    $invoiceId   Invoice ID
     * @param int    $templateId  Print template ID
     *
     * @return void
     */
    public function __construct($apiConfigId, $invoiceId, $templateId)
    {
        $this->apiConfigId = $apiConfigId;
        $this->invoice = getInvoice($invoiceId);
        if (!$this->invoice) {
            throw new Exception('Invoice not found');
        }
        $this->template = getPrintTemplate($templateId);
        if (!$this->template) {
            throw new Exception('Print template not found');
        }
    }

    /**
     * Send a printout via an API
     *
     * @return array Keyed array with results (success, message)
     */
    public function send()
    {
        $client = $this->getClient();
        $result = $client->send($this->invoice, $this->template);

        if ($result['success'] && sesWriteAccess()) {
            dbParamQuery(
                'UPDATE {prefix}invoice SET print_date=? where id=?',
                [
                    date('Ymd'),
                    $this->invoice['id']
                ]
            );
            if ($this->invoice['state_id'] == 1) {
                // Mark invoice sent
                dbParamQuery(
                    'UPDATE {prefix}invoice SET state_id=2 WHERE id=?',
                    [$this->invoice['id']]
                );
            }
        }

        return $result;
    }

    /**
     * Create a client
     *
     * @return object
     */
    protected function getClient()
    {
        $apiConfig = getSendApiConfig($this->invoice['base_id'], $this->apiConfigId);
        if (!$apiConfig) {
            throw new Exception('API settings not found for the invoicing company');
        }

        $className = ucfirst(str_replace('.', '', $apiConfig['method']));
        $fileName = strtolower($className);
        $classFile = __DIR__ . DIRECTORY_SEPARATOR . 'send_api' . DIRECTORY_SEPARATOR
            . $fileName . '.php';
        include_once $classFile;
        $class = new $className();

        $class->init($apiConfig);

        return $class;
    }
}
