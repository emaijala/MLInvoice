<?php
require_once 'tcpdf/tcpdf.php';

class PDF extends TCPDF
{
  public $footerLeft = '', $footerCenter = '', $footerRight = '';

  function Header()
  {
  }

  function Footer()
  {
    if ($this->PageNo() == 1)
      return;
    $this->SetY(-15);
    $this->SetFont('Helvetica','',7);
    $this->SetX(7);
    $this->Cell(75, 5, $this->footerLeft, 0, 0, "L");
    $this->SetX(75);
    $this->Cell(75, 5, $this->footerCenter, 0, 0, "C");
    $this->SetX(150);
    $this->Cell(50, 5, $this->footerRight, 0, 0, "R");
  }

  // Disable openssl_random_pseudo_bytes call as it's very slow on Windows  
  protected function getRandomSeed($seed='') {
    $seed .= microtime();
    //if (function_exists('openssl_random_pseudo_bytes')) {
    //  $seed .= openssl_random_pseudo_bytes(512);
    //}
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
?>
