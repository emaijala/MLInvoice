<?php
/**
 * "API client" for storing results in a server directory
 *
 * PHP version 7
 *
 * Copyright (C) Ere Maijala 2023
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
 * "API client" for storing results in a server directory
 *
 * @category MLInvoice
 * @package  MLInvoice\Base
 * @author   Ere Maijala <ere@labs.fi>
 * @license  https://opensource.org/licenses/GPL-2.0 GNU Public License 2.0
 * @link     http://github.com/emaijala/MLInvoice
 */
class ServerDirectory
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
        if (!($path = $this->apiConfig['directory'])) {
            return [
                'success' => false,
                'message' => Translator::translate('DirectoryNotConfigured')
            ];
        }

        $printTemplateFile = $template['filename'];
        $printParameters = $template['parameters'];
        $printOutputFileName = $path . $template['output_filename'];

        $printer = getInvoicePrinter($printTemplateFile);
        $printer->init(
            $invoice['id'], $printParameters, $printOutputFileName,
            0, $template, true
        );
        $result = $printer->createPrintout();
        if (false === file_put_contents($result['filename'], $result['data'])) {
            return [
                'success' => false,
                'message' => Translator::translate('FileWriteFailed', ['%%filename%%' => $result['filename']])
            ];
        }
        return [
            'success' => true,
            'message' => Translator::translate('FileWritten', ['%%filename%%' => $result['filename']])
        ];
    }
}
