<?php
/**
 * Extended TCPDF class
 *
 * PHP version 5
 *
 * Copyright (C) Ere Maijala 2010-2017.
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
 * @package  Printing
 * @author   Ere Maijala <ere@labs.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://labs.fi/mlinvoice.eng.php
 */

/**
 * Extended TCPDF class
 *
 * @category MLInvoice
 * @package  Printing
 * @author   Ere Maijala <ere@labs.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://labs.fi/mlinvoice.eng.php
 */
class PDF extends TCPDF
{
    public $headerLeft = '', $headerCenter = '', $headerRight = '';
    public $footerLeft = '', $footerCenter = '', $footerRight = '';
    public $printHeaderOnFirstPage = false;
    public $printFooterOnFirstPage = false;
    public $headerLeftPos = 4;
    public $headerRightPos = 143;
    public $footerLeftPos = 4;
    public $footerRightPos = 143;

    /**
     * This method is used to render the page header.
     * It is automatically called by AddPage().
     *
     * @return void
     */
    // @codingStandardsIgnoreLine
    public function Header()
    {
        if ($this->PageNo() == 1 && !$this->printHeaderOnFirstPage) {
            return;
        }
        $this->SetY(10);
        $this->SetFont('Helvetica', '', 7);
        $this->SetX($this->headerLeftPos);
        $this->MultiCell(
            120, 5, $this->handlePageNum($this->headerLeft), 0, 'L', 0, 0
        );
        $this->SetX(75);
        $this->MultiCell(
            65, 5, $this->handlePageNum($this->headerCenter), 0, 'C', 0, 0
        );
        $this->SetX($this->headerRightPos);
        $this->MultiCell(
            60, 5, $this->handlePageNum($this->headerRight), 0, 'R', 0, 0
        );
    }

    /**
     * This method is used to render the page footer.
     * It is automatically called by AddPage().
     *
     * @return void
     */
    // @codingStandardsIgnoreLine
    public function Footer()
    {
        if ($this->PageNo() == 1 && !$this->printFooterOnFirstPage) {
            return;
        }
        $this->SetY(-17);
        $this->SetFont('Helvetica', '', 7);
        $this->SetX($this->footerLeftPos);
        $this->MultiCell(120, 5, $this->footerLeft, 0, 'L', 0, 0);
        $this->SetX(75);
        $this->MultiCell(65, 5, $this->footerCenter, 0, 'C', 0, 0);
        $this->SetX($this->footerRightPos);
        $this->MultiCell(60, 5, $this->footerRight, 0, 'R', 0, 0);
    }

    /**
     * Include page number in a header string if appropriate
     *
     * @param string $str Header string
     *
     * @return string
     */
    protected function handlePageNum($str)
    {
        return sprintf($str, $this->PageNo());
    }
}
