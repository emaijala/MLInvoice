<?php
/**
 * Postita.fi API client
 *
 * PHP version 5
 *
 * Copyright (C) 2018 Ere Maijala
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
require_once 'miscfuncs.php';
require_once 'crypt.php';

/**
 * Client for Postita.fi API
 *
 * @category MLInvoice
 * @package  MLInvoice\Base
 * @author   Ere Maijala <ere@labs.fi>
 * @license  https://opensource.org/licenses/GPL-2.0 GNU Public License 2.0
 * @link     http://github.com/emaijala/MLInvoice
 */
class Postitafi
{
    /**
     * API config
     *
     * @var array
     */
    protected $apiConfig;

    /**
     * Initialization
     *
     * @param array $apiConfig API config
     *
     * @return void
     */
    public function init($apiConfig)
    {
        $this->apiConfig = $apiConfig;
    }

    /**
     * Send a printout
     *
     * @param array $invoice  Invoice data
     * @param array $template Print template
     *
     * @return array Keyed array with results (success, message)
     */
    public function send($invoice, $template)
    {
        if (empty($this->apiConfig['password'])) {
            return [
                'success' => false,
                'message' => 'Postita.fi password missing from settings'
            ];
        }

        $printTemplateFile = $template['filename'];
        $printParameters = $template['parameters'];
        $printOutputFileName = $template['output_filename'];

        $printer = getInvoicePrinter($printTemplateFile);
        $printer->init(
            $invoice['id'], $printParameters, $printOutputFileName,
            0, $template, true
        );
        $result = $printer->createPrintout();
        $recipient = getCompany($invoice['company_id']);

        $httpClient = new GuzzleHttp\Client();

        if ($printer instanceof InvoicePrinterFinvoice) {
            $pdfPrinter = GetInvoicePrinter('invoice_printer.php');
            $pdfLang = 'fi-FI';
            if ($printParameters) {
                $parts = explode(',', $printParameters);
                if (!empty($parts[1])) {
                    $pdfLang = $parts[1];
                }
            }
            $pdfPrinter->init(
                $invoice['id'], "invoice,$pdfLang,N",
                $printOutputFileName, 0, $template, true
            );
            $pdfResult = $pdfPrinter->createPrintout();

            $endpoint = 'https://postita.fi/api/send_finvoice/';
            $request = [
                'job_name' => 'Finvoice'
                    . ($recipient ? ' / ' . $recipient['company_name'] : ''),
                'finvoice' => $this->base64urlEncode($result['data']),
                'confirm' => $this->apiConfig['add_to_queue'] ? 'False' : 'True',
                'use_snail_backup' => $this->apiConfig['finvoice_mail_backup']
                    ? 'True' : 'False',
                'invoice_pdf' => $this->base64urlEncode($pdfResult['data'])
            ];
        } else {
            $endpoint = 'https://postita.fi/api/send/';
            $request = [
                'job_name' => $printer->getHeaderTitle()
                    . ($recipient ? ' / ' . $recipient['company_name'] : ''),
                'pdf' => $this->base64urlEncode($result['data']),
                'receiver_id' => $this->apiConfig['reference']
                    ? $this->apiConfig['reference'] : '',
                'confirm' => $this->apiConfig['add_to_queue'] ? 'False' : 'True'
            ];
            if ($this->apiConfig['post_class']) {
                $request['post_class'] = $this->apiConfig['post_class'];
            }
        }

        $crypt = new Crypt();
        $password = $crypt->decrypt($this->apiConfig['password']);
        try {
            $res = $httpClient->post(
                $endpoint,
                [
                    'form_params' => $request,
                    'auth' => [$this->apiConfig['username'], $password]
                ]
            );
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
        if ($res->getStatusCode() !== 200) {
            return [
                'success' => false,
                'message' => $res->getStatusCode()
                    . ' (' . $res->getReasonPhrase() . '): '
                    . (string)$res->getBody()
            ];
        }
        $body = (string)$res->getBody();
        $result = json_decode($body, true);
        if ($printer instanceof InvoicePrinterFinvoice) {
            $result = $result[0];
        }
        $message = 'Status: ' . $this->getStatusString($result['status']);
        $warnings = '';
        if (!empty($result['errors'])) {
            $warnings = implode('. ', $result['errors']);
        }
        return [
            'success' => true,
            'message' => $message,
            'warnings' => $warnings
        ];
    }

    /**
     * Get a status string for a status code
     *
     * @param string $status Status code
     *
     * @return string
     */
    protected function getStatusString($status)
    {
        $statuses = [
            'DR' => 'Draft',
            'NE' => 'New',
            'CO' => 'Confirmed',
            'CA' => 'Canceled',
            'PR' => 'Being processed',
            'SE' => 'Sent'
        ];
        return isset($statuses[$status]) ? $statuses[$status] : $status;
    }

    /**
     * Encode input in URL-safe base64
     *
     * @param string $input Input data
     *
     * @return string
     */
    protected function base64urlEncode($input)
    {
        return strtr(base64_encode($input), '+/', '-_');
    }
}
