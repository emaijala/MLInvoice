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
} 
?>
