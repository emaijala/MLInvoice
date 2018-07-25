<?php
/**
 * Finvoice
 *
 * PHP version 5
 *
 * Copyright (C) 2010-2018 Ere Maijala
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
require_once 'invoice_printer_xslt.php';
require_once 'htmlfuncs.php';
require_once 'miscfuncs.php';

/**
 * Finvoice
 *
 * @category MLInvoice
 * @package  MLInvoice\Base
 * @author   Ere Maijala <ere@labs.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://labs.fi/mlinvoice.eng.php
 */
class InvoicePrinterFinvoice extends InvoicePrinterXSLT
{
    /**
     * Main method for printing
     *
     * @return void
     */
    public function printInvoice()
    {
        $this->xsltParams['printTransmissionDetails'] = false;
        parent::transform('create_finvoice.xsl', 'Finvoice.xsd');
        header('Content-Type: text/xml; charset=ISO-8859-15');
        $filename = $this->getPrintoutFileName();
        if ($this->printStyle) {
            header("Content-Disposition: inline; filename=$filename");
        } else {
            header("Content-Disposition: attachment; filename=$filename");
        }
        echo $this->xml;
    }

    /**
     * Preprocess and return invoice rows
     *
     * @return array
     */
    protected function getInvoiceRowData()
    {
        $rows = parent::getInvoiceRowData();

        // Split long invoice rows
        $newData = [];
        foreach ($rows as $data) {
            if (mb_strlen($data['row_description'], 'UTF-8') > 100) {
                $parts = $this->splitDescription($data['row_description']);
                $data['row_description'] = array_shift($parts);
                $newRows[] = $data;
                foreach ($parts as $part) {
                    $row = [
                        'row_description' => $part,
                        'extended_description' => true
                    ];
                    $newRows[] = $row;
                }
            } else {
                $newRows[] = $data;
            }
        }
        return $newRows;
    }

    /**
     * Split row description to max 100 character chunks
     *
     * @param string $description Description
     *
     * @return array
     */
    protected function splitDescription($description)
    {
        $words = explode(' ', $description);
        $result = [];
        $current = [];
        foreach ($words as $word) {
            $wordLen = mb_strlen($word, 'UTF-8');
            if ($wordLen >= 100) {
                if ($current) {
                    $result[] = implode(' ', $current);
                    $current = [];
                }
                $pos = 0;
                while ($part = mb_substr($word, $pos, 100)) {
                    $result[] = $part;
                    $pos += 100;
                }
                continue;
            }
            $chunkLen = mb_strlen(implode(' ', $current), 'UTF-8');
            if ($chunkLen + $wordLen > 100) {
                $result[] = implode(' ', $current);
                $current = [];
            }
            $current[] = $word;
        }
        if ($current) {
            $result[] = implode(' ', $current);
        }
        return $result;
    }
}
