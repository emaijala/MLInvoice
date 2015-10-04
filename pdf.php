<?php
/*******************************************************************************
 MLInvoice: web-based invoicing application.
 Copyright (C) 2010-2015 Ere Maijala
 
 This program is free software. See attached LICENSE.
 
 *******************************************************************************/

/*******************************************************************************
 MLInvoice: web-pohjainen laskutusohjelma.
 Copyright (C) 2010-2015 Ere Maijala
 
 Tämä ohjelma on vapaa. Lue oheinen LICENSE.
 
 *******************************************************************************/
require_once 'tcpdf/tcpdf.php';

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

    public function Header()
    {
        if ($this->PageNo() == 1 && !$this->printHeaderOnFirstPage)
            return;
        $this->SetY(10);
        $this->SetFont('Helvetica', '', 7);
        $this->SetX($this->headerLeftPos);
        $this->MultiCell(120, 5, $this->handlePageNum($this->headerLeft), 0, 'L', 0, 
            0);
        $this->SetX(75);
        $this->MultiCell(65, 5, $this->handlePageNum($this->headerCenter), 0, 'C', 0, 
            0);
        $this->SetX($this->headerRightPos);
        $this->MultiCell(60, 5, $this->handlePageNum($this->headerRight), 0, 'R', 0, 
            0);
    }

    public function Footer()
    {
        if ($this->PageNo() == 1 && !$this->printFooterOnFirstPage)
            return;
        $this->SetY(-17);
        $this->SetFont('Helvetica', '', 7);
        $this->SetX($this->footerLeftPos);
        $this->MultiCell(120, 5, $this->footerLeft, 0, 'L', 0, 0);
        $this->SetX(75);
        $this->MultiCell(65, 5, $this->footerCenter, 0, 'C', 0, 0);
        $this->SetX($this->footerRightPos);
        $this->MultiCell(60, 5, $this->footerRight, 0, 'R', 0, 0);
    }

    protected function handlePageNum($str)
    {
        return sprintf($str, $this->PageNo());
    }
    
    // Disable openssl_random_pseudo_bytes call as it's very slow on Windows
    protected function getRandomSeed($seed = '')
    {
        $seed .= microtime();
        // if (function_exists('openssl_random_pseudo_bytes')) {
        // $seed .= openssl_random_pseudo_bytes(512);
        // }
        $seed .= uniqid('', true);
        $seed .= rand();
        $seed .= getmypid();
        $seed .= __FILE__;
        $seed .= $this->bufferlen;
        if (isset($_SERVER['REMOTE_ADDR'])) {
            $seed .= $_SERVER['REMOTE_ADDR'];
        }
        if (isset($_SERVER['HTTP_USER_AGENT'])) {
            $seed .= $_SERVER['HTTP_USER_AGENT'];
        }
        if (isset($_SERVER['HTTP_ACCEPT'])) {
            $seed .= $_SERVER['HTTP_ACCEPT'];
        }
        if (isset($_SERVER['HTTP_ACCEPT_ENCODING'])) {
            $seed .= $_SERVER['HTTP_ACCEPT_ENCODING'];
        }
        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $seed .= $_SERVER['HTTP_ACCEPT_LANGUAGE'];
        }
        if (isset($_SERVER['HTTP_ACCEPT_CHARSET'])) {
            $seed .= $_SERVER['HTTP_ACCEPT_CHARSET'];
        }
        $seed .= rand();
        $seed .= uniqid('', true);
        $seed .= microtime();
        return $seed;
    }
}
