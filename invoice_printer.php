<?php

require_once 'sessionfuncs.php';

sesVerifySession();

class InvoicePrinter
{
  protected $pdf = null;
  protected $printStyle = '';
  protected $invoiceData = null;
  protected $senderData = null;
  protected $invoiceRowData = null;
  protected $separateStatement = false;
  
  protected $senderAddress = '';
  protected $senderAddressLine = '';
  protected $senderContactInfo = '';
  protected $billingAddress = '';
  protected $refNumber = '';

  protected $totalSum = 0;
  protected $totalVAT = 0;
  protected $totalSumVAT = 0;

  public function init($invoiceId, $printParameters, $outputFileName, $invoiceData, $senderData, $invoiceRowData)
  {
    $this->invoiceId = $invoiceId;
    $this->printStyle = $printParameters;
    $this->outputFileName = $outputFileName;
    $this->invoiceData = $invoiceData;
    $this->senderData = $senderData;
    $this->invoiceRowData = $invoiceRowData;
 
    $this->totalSum = 0;
    $this->totalVAT = 0;
    $this->totalSumVAT = 0;
    foreach ($invoiceRowData as $row)
    {
      $rowSum = 0;
      $rowVAT = 0;
      $rowSumVAT = 0;
      if ($row['vat_included'])
      {
        $rowSumVAT = $row['pcs'] * $row['price'];
        
        $rowSum = $rowSumVAT / (1 + $row['vat'] / 100);
        $rowVAT = $rowSumVAT - $rowSum;
        
        $row['price'] /= (1 + $row['vat'] / 100);
      }
      else
      {
        $rowSum = $row['pcs'] * $row['price'];
        $rowVAT = $rowSum * ($row['vat'] / 100);
        $rowSumVAT = $rowSum + $rowVAT;
      }
      $this->totalSum += $rowSum;
      $this->totalVAT += $rowVAT;
      $this->totalSumVAT += $rowSumVAT;
    }
    $this->separateStatement = ($this->printStyle == 'invoice') && getSetting('invoice_separate_statement');
  }
  
  public function printInvoice()
  {
    $invoiceData = $this->invoiceData;
    $senderData = $this->senderData;
    
    $this->senderAddressLine = $senderData['name'];
    $strCompanyID = trim($senderData['company_id']);
    if ($strCompanyID)
      $strCompanyID = $GLOBALS['locCOMPVATID'] . ": $strCompanyID";
    if ($strCompanyID && $senderData['vat_registered'])
      $strCompanyID .= ', ';
    if ($senderData['vat_registered'])
      $strCompanyID .= $GLOBALS['locVATREG'];
    if ($strCompanyID)
      $this->senderAddressLine .= " ($strCompanyID)";
    $this->senderAddressLine .= "\n" . $senderData['street_address'];
    if ($senderData['street_address'] && ($senderData['zip_code'] || $senderData['city']))
      $this->senderAddressLine .= ', ';
    if ($senderData['zip_code'])
      $this->senderAddressLine .= $senderData['zip_code'] . ' ';
    $this->senderAddressLine .= $senderData['city'];
    
    $this->senderAddress = $senderData['name'] . "\n" . $senderData['street_address'] . "\n" . $senderData['zip_code'] . ' ' . $senderData['city'];
    if ($senderData['phone'])
      $this->senderContactInfo = "\n" . $GLOBALS['locPHONE'] . ' ' . $senderData['phone'];
    else
      $this->senderContactInfo = '';
    
    $this->refNumber = trim(strrev(chunk_split(strrev($invoiceData['ref_number']),5,' ')));
    
    $this->billingAddress = $invoiceData['billing_address'];
    if (!$this->billingAddress) 
      $this->billingAddress = $invoiceData['company_name'] . "\n" . $invoiceData['street_address'] . "\n" . $invoiceData['zip_code'] . ' ' . $invoiceData['city'];
    $strCompanyName = substr($this->billingAddress, 0, strpos($this->billingAddress, "\n"));
    $strCompanyAddress = substr($this->billingAddress, strpos($this->billingAddress, "\n")+1);
    
    
    $pdf=new PDF('P','mm','A4', _CHARSET_ == 'UTF-8', _CHARSET_, false);
    $this->pdf = $pdf;
    $pdf->AddPage(); 
    $pdf->SetAutoPageBreak(FALSE);
    $pdf->footerLeft = $this->senderAddressLine;
    $pdf->footerCenter = $this->senderContactInfo;
    $pdf->footerRight = $senderData['www'] . "\n" . $senderData['email'];
    
    // TOP
    
    // Sender
    if (isset($senderData['logo_filedata']))
    {
      if (!isset($senderData['logo_top']))
        $senderData['logo_top'] = $pdf->GetY()+5;
      if (!isset($senderData['logo_left']))
        $senderData['logo_left'] = $pdf->GetX();
      if (!isset($senderData['logo_width']) || $senderData['logo_width'] == 0)
        $senderData['logo_width'] = 80;
      if (!isset($senderData['logo_bottom_margin']))
        $senderData['logo_bottom_margin'] = 5;
    
      $pdf->Image('@' . $senderData['logo_filedata'], $senderData['logo_left'], $senderData['logo_top'], $senderData['logo_width'], 0, '', '', 'N', false, 300, '', false, false, 0, true);
      $pdf->SetY($pdf->GetY() + $senderData['logo_bottom_margin']);
    }
    else
    {
      $pdf->SetTextColor(125);
      $pdf->SetFont('Helvetica','B',10);
      $pdf->SetY($pdf->GetY()+5);
      $pdf->Cell(120, 5, $senderData['name'], 0, 1);
      $pdf->SetFont('Helvetica','',10);
      $pdf->MultiCell(120, 5, $senderData['street_address'] . "\n" . $senderData['zip_code'] . ' ' . $senderData['city'],0,1);
      $pdf->SetY($pdf->GetY()+5);
    }
    
    // Recipient
    $pdf->SetTextColor(0);
    $pdf->SetFont('Helvetica','B',14);
    $pdf->Cell(120, 6, $strCompanyName,0,1);
    $pdf->SetFont('Helvetica','',14);
    $pdf->MultiCell(120, 6, $strCompanyAddress,0,1);
    $pdf->SetFont('Helvetica','',12);
    if ($invoiceData['email'])
    {
      $pdf->SetY($pdf->GetY() + 4);
      $pdf->Cell(120, 6, $invoiceData['email'], 0, 1);
    }
    
    $recipientMaxY = $pdf->GetY();
    
    if ($this->printStyle == 'dispatch')
      $locStr = 'DispatchNote';
    elseif ($this->printStyle == 'receipt')
      $locStr = 'Receipt';
    else
      $locStr = 'Invoice';
    
    // Invoice info headers
    $pdf->SetXY(115,10);
    $pdf->SetFont('Helvetica','B',12);
    if ($this->printStyle == 'dispatch')
      $pdf->Cell(40, 5, $GLOBALS['locDispatchNoteHeader'], 0, 1, 'R');
    elseif ($this->printStyle == 'receipt')
      $pdf->Cell(40, 5, $GLOBALS['locReceiptHeader'], 0, 1, 'R');
    elseif ($invoiceData['state_id'] == 5)
      $pdf->Cell(40, 5, $GLOBALS['locFIRSTREMINDERHEADER'], 0, 1, 'R');
    elseif ($invoiceData['state_id'] == 6)
      $pdf->Cell(40, 5, $GLOBALS['locSECONDREMINDERHEADER'], 0, 1, 'R');
    else
      $pdf->Cell(40, 5, $GLOBALS['locINVOICEHEADER'], 0, 1, 'R');
    $pdf->SetFont('Helvetica','',10);
    $pdf->SetXY(115, $pdf->GetY()+5);
    if ($invoiceData['customer_no'] != 0)
    {
      $pdf->Cell(40, 5, $GLOBALS['locCUSTOMERNUMBER'] .": ", 0, 0, 'R');
      $pdf->Cell(60, 5, $invoiceData['customer_no'], 0, 1);
    }
    $pdf->SetX(115);
    $pdf->Cell(40, 5, $GLOBALS["loc${locStr}Number"] . ': ', 0, 0, 'R');
    $pdf->Cell(60, 5, $invoiceData['invoice_no'], 0, 1);
    $pdf->SetX(115);
    $pdf->Cell(40, 5, $GLOBALS["locPDF${locStr}Date"] . ': ', 0, 0, 'R');
    $strInvoiceDate = dateConvIntDate2Date($invoiceData['invoice_date']);
    $strDueDate = dateConvIntDate2Date($invoiceData['due_date']);
    $pdf->Cell(60, 5, $strInvoiceDate, 0, 1);
    if ($this->printStyle == 'invoice')
    {
      $pdf->SetX(115);
      $pdf->Cell(40, 5, $GLOBALS['locPDFDUEDATE'] .": ", 0, 0, 'R');
      $pdf->Cell(60, 5, $strDueDate, 0, 1);
      $pdf->SetX(115);
      $pdf->Cell(40, 5, $GLOBALS['locTERMSOFPAYMENT'] .": ", 0, 0, 'R');
      $paymentDays = strDate2UnixTime($strDueDate)/3600/24 - strDate2UnixTime($strInvoiceDate)/3600/24;
      if ($paymentDays < 0) //weird
        $paymentDays = getSetting('invoice_payment_days');
      $pdf->Cell(60, 5, sprintf(getSetting('invoice_terms_of_payment'), $paymentDays), 0, 1);
      $pdf->SetX(115);
      $pdf->Cell(40, 5, $GLOBALS['locPERIODFORCOMPLAINTS'] .": ", 0, 0, 'R');
      $pdf->Cell(60, 5, getSetting('invoice_period_for_complaints'), 0, 1);
      $pdf->SetX(115);
      $pdf->Cell(40, 5, $GLOBALS['locPENALTYINTEREST'] .": ", 0, 0, 'R');
      $pdf->Cell(60, 5, miscRound2OptDecim(getSetting('invoice_penalty_interest'), 1) . ' %', 0, 1);
      $pdf->SetX(115);
      $pdf->Cell(40, 5, $GLOBALS['locPDFINVREFNO'] .": ", 0, 0, 'R');
      $pdf->Cell(60, 5, $this->refNumber, 0, 1);
    }
    
    $strReference = $invoiceData['reference'] ? $invoiceData['reference'] : $invoiceData['contact_person'];
    if ($strReference && $this->printStyle != 'dispatch')
    {
      $pdf->SetX(115);
      $pdf->Cell(40, 5, $GLOBALS['locYOURREFERENCE'] .": ", 0, 0, 'R');
      $pdf->Cell(60, 5, $strReference, 0, 1);
    }
    if (isset($invoiceData['invoice_info']) && $invoiceData['invoice_info'])
    {
      $pdf->SetX(115);
      $pdf->Cell(40, 5, $GLOBALS['locPDFAdditionalInformation'] . ': ', 0, 0, 'R');
      $pdf->MultiCell(50, 5, $invoiceData['invoice_info'], 0, 'L', 0);
    }
    
    if ($this->printStyle == 'invoice')
    {
      if ($invoiceData['refunded_invoice_no'])
      {
        $pdf->SetX(115);
        $pdf->Cell(40, 5, sprintf($GLOBALS['locREFUNDSINVOICE'], $invoiceData['refunded_invoice_no']), 0, 1, 'R');
      }
      
      if ($invoiceData['state_id'] == 5)
      {
        $pdf->SetX(60);
        $pdf->SetFont('Helvetica','B',10);
        $pdf->MultiCell(150, 5, $GLOBALS['locFIRSTREMINDERNOTE'], 0, 'L', 0);
        $pdf->SetFont('Helvetica','',10);
      }
      elseif ($invoiceData['state_id'] == 6)
      {
        $pdf->SetX(60);
        $pdf->SetFont('Helvetica','B',10);
        $pdf->MultiCell(150, 5, $GLOBALS['locSECONDREMINDERNOTE'], 0, 'L', 0);
        $pdf->SetFont('Helvetica','',10);
      }
    }
    
    $pdf->SetY(max($pdf->GetY(), $recipientMaxY) + 5);
    $pdf->Line(5, $pdf->GetY(), 202, $pdf->GetY());
    $pdf->SetY($pdf->GetY()+5);
  
    if (!$this->separateStatement)
    {
      $this->printRows();
    }
    else 
    {
      $pdf->SetFont('Helvetica','B',20);
      $pdf->SetXY(20, $pdf->GetY()+40);
      $pdf->MultiCell(180, 5, $GLOBALS['locSEESEPARATESTATEMENT'], 0, "L", 0);
    }

    if ($this->printStyle == 'invoice')
      $this->printForm();  

    if ($this->separateStatement)
      $this->printRows();
    
    $filename = $this->outputFileName ? $this->outputFileName : getSetting('invoice_pdf_filename');
    $pdf->Output(sprintf($filename, $invoiceData['invoice_no']), 'I');
  }  
  
  protected function printRows()
  {
    $pdf = $this->pdf;
    $invoiceData = $this->invoiceData;

    if ($this->separateStatement)
    {
      $pdf->AddPage();
      $pdf->SetAutoPageBreak(TRUE, 22);
      
      $pdf->SetFont('Helvetica','B',20);
      $pdf->SetXY(7, $pdf->GetY());
      $pdf->Cell(80, 5, $GLOBALS['locINVOICESTATEMENT'], 0, 0, "L");
      $pdf->SetFont('Helvetica','',10);
      $pdf->SetX(115);
      
      if ($this->printStyle == 'dispatch')
        $locStr = 'DispatchNote';
      elseif ($this->printStyle == 'receipt')
        $locStr = 'Receipt';
      else
        $locStr = 'Invoice';
      
      $pdf->Cell(40, 5, $GLOBALS["loc${locStr}Number"] . ': ', 0, 0, 'R');
      $pdf->Cell(60, 5, $invoiceData['invoice_no'], 0, 1);
      $pdf->SetXY(7, $pdf->GetY()+10);
    }
    elseif ($this->printStyle != 'invoice')
    {
      $pdf->printFooterOnFirstPage = true;
      $pdf->SetAutoPageBreak(TRUE, $this->printStyle == 'receipt' ? 32 : 22);
    }
  
    if ($this->printStyle == 'dispatch')
      $nameColWidth = 120;
    else
      $nameColWidth = 80;
  
    $showDate = getSetting('invoice_show_row_date');
    $pdf->SetX(7);
    if ($showDate) 
    {
      $pdf->Cell($nameColWidth - 20, 5, $GLOBALS['locROWNAME'], 0, 0, "L");
      $pdf->Cell(20, 5, $GLOBALS['locDATE'], 0, 0, "L");
    }
    else {
        $pdf->Cell($nameColWidth, 5, $GLOBALS['locROWNAME'], 0, 0, "L");
    }
    if ($this->printStyle != 'dispatch')
      $pdf->Cell(15, 5, $GLOBALS['locROWPRICE'], 0, 0, "R");
    $pdf->Cell(15, 5, $GLOBALS['locPCS'], 0, 0, "R");
    $pdf->Cell(15, 5, $GLOBALS['locUNIT'], 0, 0, "R");
    if ($this->printStyle != 'dispatch')
    {
      $pdf->Cell(20, 5, $GLOBALS['locROWTOTAL'], 0, 0, "R");
      $pdf->Cell(15, 5, $GLOBALS['locVATPERCENT'], 0, 0, "R");
      $pdf->Cell(15, 5, $GLOBALS['locTAX'], 0, 0, "R");
      $pdf->Cell(20, 5, $GLOBALS['locROWTOTAL'], 0, 1, "R");
    }
    else
    {
      $pdf->Cell(20, 5, '', 0, 1, "R"); // line feed
    }
  
    $pdf->SetY($pdf->GetY()+5);
    foreach ($this->invoiceRowData as $row) 
    {
      if (!$this->separateStatement && $this->printStyle == 'invoice' && $pdf->GetY() > 152)
      {
        $this->separateStatement = true;
        $this->printInvoice();
        exit;
      }
    
      // Product / description
      $description = '';
      if ($row['product_name'])
      {
        if ($row['description']) 
          $description = $row['product_name'] . ' (' . $row['description'] . ')';
        else
          $description = $row['product_name'];
      }
      else
        $description = $row['description'];
  
      // Sums
      $rowSum = 0;
      $rowVAT = 0;
      $rowSumVAT = 0;
      if ($row['vat_included'])
      {
        $rowSumVAT = $row['pcs'] * $row['price'];
        
        $rowSum = $rowSumVAT / (1 + $row['vat'] / 100);
        $rowVAT = $rowSumVAT - $rowSum;
        
        $row['price'] /= (1 + $row['vat'] / 100);
      }
      else
      {
        $rowSum = $row['pcs'] * $row['price'];
        $rowVAT = $rowSum * ($row['vat'] / 100);
        $rowSumVAT = $rowSum + $rowVAT;
      }
      
      if ($row['price'] == 0 && $row['pcs'] == 0) 
      {
        $pdf->SetX(7);
        $pdf->MultiCell(0, 5, $description, 0, 'L');
      }
      else 
      {
        if ($showDate) 
        {
          $pdf->SetX($nameColWidth - 20 + 7);
          $pdf->Cell(20, 5, dateConvIntDate2Date($row['row_date']), 0, 0, "L");
        }
        else 
        {
          $pdf->SetX($nameColWidth + 7);
        }
        if ($this->printStyle != 'dispatch')
          $pdf->Cell(15, 5, miscRound2Decim($row['price']), 0, 0, "R");
        $pdf->Cell(15, 5, miscRound2Decim($row['pcs']), 0, 0, "R");
        $pdf->Cell(15, 5, $row['type'], 0, 0, "R");
        if ($this->printStyle != 'dispatch')
        {
          $pdf->Cell(20, 5, miscRound2Decim($rowSum), 0, 0, "R");
          $pdf->Cell(15, 5, miscRound2OptDecim($row['vat'], 1), 0, 0, "R");
          $pdf->Cell(15, 5, miscRound2Decim($rowVAT), 0, 0, "R");
          $pdf->Cell(20, 5, miscRound2Decim($rowSumVAT), 0, 0, "R");
        }
        $pdf->SetX(7);
        if($showDate) 
        {
          $pdf->MultiCell($nameColWidth - 20, 5, $description, 0, 'L');
        }
        else 
        {
          $pdf->MultiCell($nameColWidth, 5, $description, 0, 'L');
        }
      }
    }
    if ($this->printStyle != 'dispatch')
    {
      $pdf->SetFont('Helvetica','',10);
      $pdf->SetY($pdf->GetY()+10);
      $pdf->Cell(162, 5, $GLOBALS['locTOTALEXCLUDINGVAT'] .": ", 0, 0, "R");
      $pdf->SetX(182);
      $pdf->Cell(20, 5, miscRound2Decim($this->totalSum), 0, 0, "R");
      
      $pdf->SetFont('Helvetica','',10);
      $pdf->SetY($pdf->GetY()+5);
      $pdf->Cell(162, 5, $GLOBALS['locTOTALVAT'] .": ", 0, 0, "R");
      $pdf->SetX(182);
      $pdf->Cell(20, 5, miscRound2Decim($this->totalVAT), 0, 0, "R");
      
      $pdf->SetFont('Helvetica','B',10);
      $pdf->SetY($pdf->GetY()+5);
      $pdf->Cell(162, 5, $GLOBALS['locTOTALINCLUDINGVAT'] .": ", 0, 0, "R");
      $pdf->SetX(182);
      $pdf->Cell(20, 5, miscRound2Decim($this->totalSumVAT), 0, 1, "R");
    }
  }
  
  protected function printForm()
  {
    $pdf = $this->pdf;
    $senderData = $this->senderData;
    $invoiceData = $this->invoiceData;
    
    $intStartY = 187;
    $pdf->SetFont('Helvetica','',7);
    $pdf->SetXY(7, $intStartY);
    $pdf->MultiCell(120, 5, $this->senderAddressLine, 0, "L", 0);
    $pdf->SetXY(75, $intStartY);
    $pdf->MultiCell(65, 5, $this->senderContactInfo, 0, "C", 0);
    $pdf->SetXY(140, $intStartY);
    $pdf->MultiCell(60, 5, $senderData['www'] . "\n" . $senderData['email'], 0, "R", 0);

    // Invoice form
    $intStartY = $intStartY + 8;
    $intStartX = 7;
  
    $intMaxX = 200;
    //1. hor.line - full width
    $pdf->SetLineWidth(0.13);
    $pdf->Line($intStartX, $intStartY - 0.5, $intMaxX, $intStartY - 0.5);
    $pdf->SetLineWidth(0.50);
    //2. hor.line - full width
    $pdf->Line($intStartX, $intStartY+16, $intMaxX, $intStartY+16);
    //3. hor.line - start-half page
    $pdf->Line($intStartX, $intStartY+32, $intStartX+111.4, $intStartY+32);
    //4. hor.line - half-end page
    $pdf->Line($intStartX+111.4, $intStartY+57.5, $intMaxX, $intStartY+57.5);
    //5. hor.line - full width
    $pdf->Line($intStartX, $intStartY+66, $intMaxX, $intStartY+66);
    //6. hor.line - full width
    $pdf->Line($intStartX, $intStartY+74.5, $intMaxX, $intStartY+74.5);
    
    //1. ver.line - 1.hor - 3.hor
    $pdf->Line($intStartX+20, $intStartY, $intStartX+20, $intStartY+32);
    //2. ver.line - 5.hor - 6.hor
    $pdf->Line($intStartX+20, $intStartY+66, $intStartX+20, $intStartY+74.5);
    //3. ver.line - 1.hor - 2.hor
    $pdf->SetLineWidth(0.13);
    $pdf->Line($intStartX+162, $intStartY, $intStartX+162, $intStartY+16);
    $pdf->SetLineWidth(0.50);
    //4. ver.line - full height
    $pdf->Line($intStartX+111.4, $intStartY, $intStartX+111.4, $intStartY+74.5);
    //5. ver.line - 4.hor - 6. hor
    $pdf->Line($intStartX+130, $intStartY+57.5, $intStartX+130, $intStartY+74.5);
    //6. ver.line - 5.hor - 6. hor
    $pdf->Line($intStartX+160, $intStartY+66, $intStartX+160, $intStartY+74.5);
    
    //underscript
    $pdf->SetLineWidth(0.13);
    $pdf->Line($intStartX+23, $intStartY+63, $intStartX+90, $intStartY+63);
    
    //receiver bank
    $pdf->SetFont('Helvetica','',7);
    $pdf->SetXY($intStartX, $intStartY + 1);
    $pdf->MultiCell(19, 2.8, "Saajan\ntilinumero", 0, "R", 0);
    $pdf->SetXY($intStartX, $intStartY + 8);
    $pdf->MultiCell(19, 2.8, "Mottagarens\nkontonummer", 0, "R", 0);
    $pdf->SetXY($intStartX + 112.4, $intStartY + 0.5);
    $pdf->Cell(10, 2.8, "IBAN", 0, 1, "L");
    $pdf->SetXY($intStartX + 162.4, $intStartY + 0.5);
    $pdf->Cell(10, 2.8, "BIC", 0, 1, "L");
    
    // account 1
    $pdf->SetFont('Helvetica','',10);
    $pdf->SetXY($intStartX + 22, $intStartY + 1);
    $pdf->Cell(15, 4, $senderData['bank_name'], 0, 0, "L");
    $pdf->SetX($intStartX + 65);
    $pdf->Cell(40, 4, $senderData['bank_account'], 0, 0, "L");
    $pdf->SetX($intStartX + 120.4);
    $pdf->Cell(50, 4, $senderData['bank_iban'], 0, 0, "L");
    $pdf->SetX($intStartX + 170.4);
    $pdf->Cell(15, 4, $senderData['bank_swiftbic'], 0, 0, "L");
    
    // account 2
    $pdf->SetXY($intStartX + 22, $intStartY + 5);
    $pdf->Cell(15, 4, $senderData['bank_name2'], 0, 0, "L");
    $pdf->SetX($intStartX + 65);
    $pdf->Cell(40, 4, $senderData['bank_account2'], 0, 0, "L");
    $pdf->SetX($intStartX + 120.4);
    $pdf->Cell(50, 4, $senderData['bank_iban2'], 0, 0, "L");
    $pdf->SetX($intStartX + 170.4);
    $pdf->Cell(15, 4, $senderData['bank_swiftbic2'], 0, 0, "L");
    
    // account 3
    $pdf->SetXY($intStartX + 22, $intStartY + 9);
    $pdf->Cell(15, 4, $senderData['bank_name3'], 0, 0, "L");
    $pdf->SetX($intStartX + 65);
    $pdf->Cell(40, 4, $senderData['bank_account3'], 0, 0, "L");
    $pdf->SetX($intStartX + 120.4);
    $pdf->Cell(50, 4, $senderData['bank_iban3'], 0, 0, "L");
    $pdf->SetX($intStartX + 170.4);
    $pdf->Cell(15, 4, $senderData['bank_swiftbic3'], 0, 0, "L");
    
    //payment recipient
    $pdf->SetFont('Helvetica','',7);
    $pdf->SetXY($intStartX, $intStartY + 18);
    $pdf->Cell(19, 5, "Saaja", 0, 1, "R");
    $pdf->SetXY($intStartX, $intStartY + 22);
    $pdf->Cell(19, 5, "Mottagare", 0, 1, "R");
    $pdf->SetFont('Helvetica','',10);
    $pdf->SetXY($intStartX + 22,$intStartY + 18);
    $pdf->MultiCell(100, 4, $this->senderAddress,0,1);
    
    //payer
    $pdf->SetFont('Helvetica','',7);
    $pdf->SetXY($intStartX, $intStartY + 35);
    $pdf->MultiCell(19, 2.8, "Maksajan\nnimi ja\nosoite", 0, "R", 0);
    $pdf->SetXY($intStartX, $intStartY + 45);
    $pdf->MultiCell(19, 2.8, "Betalarens\nnamn och\naddress", 0, "R", 0);
    $pdf->SetFont('Helvetica','',10);
    $pdf->SetXY($intStartX + 22, $intStartY + 35);
    $pdf->MultiCell(100, 4, $this->billingAddress,0,1);
    
    //underscript
    $pdf->SetFont('Helvetica','',7);
    $pdf->SetXY($intStartX, $intStartY + 60);
    $pdf->Cell(19, 5, "Allekirjoitus", 0, 1, "R");
    //from account
    $pdf->SetXY($intStartX, $intStartY + 68);
    $pdf->Cell(19, 5, cond_utf8_encode('Tililtä'), 0, 1, "R");
    
    //info
    $pdf->SetFont('Helvetica','',10);
    $pdf->SetXY($intStartX + 112.4, $intStartY + 20);
    $pdf->Cell(70, 5, "Laskunumero " . $invoiceData['invoice_no'], 0, 1, "L");
    $pdf->SetXY($intStartX + 112.4, $intStartY + 30);
    $pdf->Cell(70, 5, "Viitenumero on aina mainittava maksettaessa.", 0, 1, "L");
    $pdf->SetXY($intStartX + 112.4, $intStartY + 35);
    $pdf->Cell(70, 5, cond_utf8_encode('Referensnumret bör alltid anges vid betalning.'), 0, 1, "L");
    //terms
    $pdf->SetFont('Helvetica','',5);
    $pdf->SetXY($intStartX + 133, $intStartY + 85);
    $pdf->MultiCell(70, 2, cond_utf8_encode("Maksu välitetään saajalle maksujenvälityksen ehtojen mukaisesti ja vain\nmaksajan ilmoittaman tilinumeron perusteella"),0,1);
    $pdf->SetXY($intStartX + 133, $intStartY + 90);
    $pdf->MultiCell(70, 2, cond_utf8_encode("Betalningen förmedlas till mottagaren enligt villkoren för betalnings-\nförmedling och endast till det kontonummer som betalaren angivit"),0,1);
    $pdf->SetFont('Helvetica','',6);
    $pdf->SetXY($intStartX + 133, $intStartY + 95);
    $pdf->Cell($intMaxX + 1 - 133 - $intStartX, 5, "PANKKI BANKEN", 0, 1, "R");
    
    
    $pdf->SetFont('Helvetica','',7);
    //refno
    $pdf->SetFont('Helvetica','',7);
    $pdf->SetXY($intStartX + 112.4, $intStartY + 59);
    $pdf->Cell(15, 5, "Viitenro", 0, 1, "L");
    $pdf->SetFont('Helvetica','',10);
    $pdf->SetXY($intStartX + 131, $intStartY + 59);
    $pdf->Cell(15, 5, $this->refNumber, 0, 1, "L");
    
    //duedate
    $pdf->SetFont('Helvetica','',7);
    $pdf->SetXY($intStartX + 112.4, $intStartY + 68);
    $pdf->Cell(15, 5, cond_utf8_encode('Eräpäivä'), 0, 1, "L");
    $pdf->SetFont('Helvetica','',10);
    $pdf->SetXY($intStartX + 131.4, $intStartY + 68);
    $pdf->Cell(25, 5, ($invoiceData['state_id'] == 5 || $invoiceData['state_id'] == 6) ? $GLOBALS['locDUEDATENOW'] : dateConvIntDate2Date($invoiceData['due_date']), 0, 1, "L");
    
    //eur
    $pdf->SetFont('Helvetica','',7);
    $pdf->SetXY($intStartX + 161, $intStartY + 68);
    $pdf->Cell(15, 5, "Euro", 0, 1, "L");
    $pdf->SetFont('Helvetica','',10);
    $pdf->SetXY($intStartX + 151, $intStartY + 68);
    $pdf->Cell(40, 5, miscRound2Decim($this->totalSumVAT), 0, 1, "R");
    
    //barcode
    /*
    1  	Currency (1=FIM, 2=EURO. EURO must not be used before 1.1.1999!)
    14 	Zero-padded account number. The zeroes are added after the sixth number except in numbers that begin with 4 or 5. Those are padded after the seventh number.
    8 	Amount. The format is xxxxxx.xx, so you can't charge your customers millions ;)
    20 	Reference Number
    6 	Due Date. Format is YYMMDD.
    4 	Zero padding
    1 	Check code 1
    */
    if( getSetting('invoice_show_barcode') && $this->totalSumVAT > 0) 
    {
      if (strpos($senderData['bank_account'], '-') === false)
      {
        error_log('No dash in account number, barcode not created');
      }
      else
      {
        if ($intTotSumVAT >= 1000000)
        {
          error_log('Sum too large, barcode not created');
        }
        else
        {
          $tmpAccount = str_replace("-", str_repeat('0', 14 -(strlen($$senderData['bank_account'])-1)),$$senderData['bank_account']);
          $tmpSum = str_replace(",", "", miscRound2Decim($this->totalSumVAT));
          $tmpSum = str_repeat('0', 8 - strlen($tmpSum)). $tmpSum;
          $tmpRefNumber = str_replace(" ", "", $this->refNumber);
          $tmpRefNumber = str_repeat('0', 20 - strlen($tmpRefNumber)). $tmpRefNumber;
          $atmdDueDate = explode(".", $invoiceData['due_date']);
          $tmpDueDate = substr($atmdDueDate[2], -2). $atmdDueDate[1]. $atmdDueDate[0];
          
          $code_string = "2". $tmpAccount. $tmpSum. $tmpRefNumber. $tmpDueDate. "0000";
          $code_string = $code_string. miscCalcCheckNo($code_string);
      
          $style = array(
            'position' => '',
            'align' => 'C',
            'stretch' => true,
            'fitwidth' => true,
            'cellfitalign' => '',
            'border' => false,
            'hpadding' => 'auto',
            'vpadding' => 'auto',
            'fgcolor' => array(0,0,0),
            'bgcolor' => false, //array(255,255,255),
            'text' => false,
            'font' => 'helvetica',
            'fontsize' => 8,
            'stretchtext' => 4
          );
          $pdf->write1DBarcode($code_string, 'C128C', 20, 284, 105, 11, 0.34, $style, 'N');
        }
      }
    }
  }
}
  
