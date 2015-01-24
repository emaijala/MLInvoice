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

require_once 'invoice_printer_base.php';
require_once 'htmlfuncs.php';
require_once 'miscfuncs.php';

class InvoicePrinterOrderConfirmation extends InvoicePrinterBase
{
  public function init($invoiceId, $printParameters, $outputFileName, $senderData, $recipientData, $invoiceData, $invoiceRowData)
  {
  	parent::init($invoiceId, $printParameters, $outputFileName, $senderData, $recipientData, $invoiceData, $invoiceRowData);
  	$this->printStyle = 'order_confirmation';
  }

	public function printInvoice()
  {
    $this->invoiceRowMaxY = 260;
    if ($this->senderData['bank_iban'] && $this->senderData['bank_swiftbic']) {
      $bank = $this->senderData['bank_iban'] . '/' . $this->senderData['bank_swiftbic'];
    } else {
      $this->senderData['bank_iban'] . $this->senderData['bank_swiftbic'];
    }
    $this->senderAddressLine .= "\n$bank";

    parent::printInvoice();
  }

  protected function initPDF()
  {
    parent::initPDF();
    $this->pdf->printFooterOnFirstPage = true;
  }

  protected function printInfo()
  {
    $pdf = $this->pdf;
    $senderData = $this->senderData;
    $invoiceData = $this->invoiceData;
    $recipientData = $this->recipientData;

    // Invoice info headers
    $pdf->SetXY(115,10);
    $pdf->SetFont('Helvetica','B',12);
    $pdf->Cell(40, 5, $GLOBALS['locPDFOrderConfirmationHeader'], 0, 1, 'R');
    $pdf->SetFont('Helvetica','',10);
    $pdf->SetXY(115, $pdf->GetY()+5);
    if ($recipientData['customer_no'] != 0)
    {
      $pdf->Cell(40, 4, $GLOBALS['locPDFCustomerNumber'] .": ", 0, 0, 'R');
      $pdf->Cell(60, 4, $recipientData['customer_no'], 0, 1);
    }
    if ($recipientData['company_id'])
    {
      $pdf->SetX(115);
      $pdf->Cell(40, 4, $GLOBALS['locPDFClientVATID'] .": ", 0, 0, 'R');
      $pdf->Cell(60, 4, $recipientData['company_id'], 0, 1);
    }
    $pdf->SetX(115);
    $pdf->Cell(40, 4, $GLOBALS["locPDFOrderConfirmationNumber"] . ': ', 0, 0, 'R');
    $pdf->Cell(60, 4, $invoiceData['invoice_no'], 0, 1);

    $pdf->SetX(115);
    $pdf->Cell(40, 4, $GLOBALS["locPDFOrderConfirmationDate"] . ': ', 0, 0, 'R');
    $strInvoiceDate = $this->_formatDate($invoiceData['invoice_date']);
    $pdf->Cell(60, 4, $strInvoiceDate, 0, 1);

    $pdf->SetX(115);
    $pdf->Cell(40, 5, $GLOBALS['locPDFTermsOfPayment'] . ': ', 0, 0, 'R');
    $paymentDays = round(dbDate2UnixTime($invoiceData['due_date']) / 3600 / 24 - dbDate2UnixTime($invoiceData['invoice_date']) / 3600 / 24);
    if ($paymentDays < 0) {
      // This shouldn't happen, but try to be safe...
      $paymentDays = getPaymentDate($invoiceData['company_id']);
    }
    $pdf->Cell(60, 5, sprintf(getTermsOfPayment($invoiceData['company_id']), $paymentDays), 0, 1);

    if ($invoiceData['reference']) {
      $pdf->SetX(115);
      $pdf->Cell(40, 5, $GLOBALS['locPDFYourReference'] . ': ', 0, 0, 'R');
      $pdf->Cell(60, 5, $invoiceData['reference'], 0, 1);
    }

    if ($invoiceData['delivery_terms']) {
      $pdf->SetX(115);
      $pdf->Cell(40, 4, $GLOBALS["locPDFDeliveryTerms"] . ': ', 0, 0, 'R');
      $pdf->MultiCell(50, 4, $invoiceData['delivery_terms'], 0, 'L', 0);
    }

    if ($invoiceData['delivery_method']) {
      $pdf->SetX(115);
      $pdf->Cell(40, 4, $GLOBALS["locPDFDeliveryMethod"] . ': ', 0, 0, 'R');
      $pdf->MultiCell(50, 4, $invoiceData['delivery_method'], 0, 'L', 0);
    }

    if (isset($invoiceData['info']) && $invoiceData['info'])
    {
      $pdf->SetX(115);
      $pdf->Cell(40, 5, $GLOBALS['locPDFAdditionalInformation'] . ': ', 0, 0, 'R');
      $pdf->MultiCell(50, 4, $invoiceData['info'], 0, 'L', 0);
    }
  }

  protected function printForm()
  {
  }

  protected function printRows()
  {
    $pdf = $this->pdf;
    $invoiceData = $this->invoiceData;

    $pdf->printFooterOnFirstPage = true;
    $pdf->SetAutoPageBreak(true, 22);

/*
 * +tuotekoodi,
 * +tuote,
 * +määrä,
 * +yksikkö,
 * +yksikköhinta,
 * +rivin yhteishinta,
 * +kokonaishinta tilaukselle (periaatteessa riittää alv 0% ja loppusummalle 0% ja 24%)
 * +maksuehto,
 * +toimitusehto,
 * +toimituspäivä,
 * +kuljetustapa,
 * +asiakkaan viite (osa näistä voisi olla lisätietorivillä, jos muuten hankala).
 * Mahdollinen vapaa teksti kenttä loppuun: (esim.) "Ellei toisin ole sovittu tässä kaupassa noudatetaan "Teknisen Kaupan yleiset myyntiehdot (TK Yleiset 2010)" -myyntiehtoja"
  */
    $left = 10;
    $nameColWidth = 110;

    $pdf->Cell($nameColWidth, 5, $GLOBALS['locPDFRowName'], 0, 0, 'L');
    $pdf->Cell(20, 5, $GLOBALS['locPDFOrderConfirmationRowDate'], 0, 0, 'L');
    $pdf->Cell(17, 5, $GLOBALS['locPDFRowPrice'], 0, 0, 'R');
    $pdf->Cell(20, 5, $GLOBALS['locPDFRowPieces'], 0, 0, 'R');
    $pdf->Cell(20, 5, $GLOBALS['locPDFRowTotal'], 0, 1, 'R');
    $pdf->Cell(20, 5, '', 0, 1, 'R'); // line feed

    foreach ($this->invoiceRowData as $row)
    {
      // Product / description
      $description = '';
      switch ($row['reminder_row'])
      {
      case 1:
        $description = $GLOBALS['locPDFPenaltyInterestDesc'];
        break;
      case 2:
        $description = $GLOBALS['locPDFReminderFeeDesc'];
        break;
      default:
        if ($row['product_name'])
        {
          if ($row['description'])
            $description = $row['product_name'] . ' (' . $row['description'] . ')';
          else
            $description = $row['product_name'];
          if (getSetting('invoice_display_product_codes') && $row['product_code']) {
            $description = $row['product_code'] . ' ' . $description;
          }
        }
        else
          $description = $row['description'];
      }

      // Sums
      list($rowSum, $rowVAT, $rowSumVAT) = calculateRowSum($row['price'], $row['pcs'], $row['vat'], $row['vat_included'], $row['discount']);
      if ($row['vat_included'])
        $row['price'] /= (1 + $row['vat'] / 100);

      if ($row['price'] == 0 && $row['pcs'] == 0)
      {
        $pdf->SetX($left);
        $pdf->MultiCell(0, 5, $description, 0, 'L');
      }
      else
      {
        $pdf->SetX($nameColWidth + $left);
        $pdf->Cell(20, 5, $this->_formatDate($row['row_date']), 0, 0, 'L');
        $decimals = isset($row['price_decimals']) ? $row['price_decimals'] : 2;
        $pdf->Cell(17, 5, $this->_formatCurrency($row['price'], $decimals), 0, 0, 'R');
        $pdf->Cell(13, 5, $this->_formatNumber($row['pcs'], 2, true), 0, 0, 'R');
        $pdf->Cell(7, 5, isset($GLOBALS["locPDF{$row['type']}"]) ? $GLOBALS["locPDF{$row['type']}"] : $row['type'], 0, 0, 'L');
        $pdf->Cell(20, 5, $this->_formatCurrency($rowSum), 0, 0, 'R');
        $pdf->SetX($left);
        $pdf->MultiCell($nameColWidth, 5, $description, 0, 'L');
      }
    }
    if ($this->printStyle != 'dispatch')
    {
      if ($this->senderData['vat_registered'])
      {
        $pdf->SetFont('Helvetica','',10);
        $pdf->SetY($pdf->GetY()+10);
        $pdf->Cell(162, 5, $GLOBALS['locPDFTotalExcludingVAT'] . ': ', 0, 0, 'R');
        $pdf->SetX(187 - $left);
        $pdf->Cell(20, 5, $this->_formatCurrency($this->totalSum), 0, 0, 'R');

        $pdf->SetFont('Helvetica','',10);
        $pdf->SetY($pdf->GetY()+5);
        $pdf->Cell(162, 5, $GLOBALS['locPDFTotalVAT'] . ': ', 0, 0, 'R');
        $pdf->SetX(187 - $left);
        $pdf->Cell(20, 5, $this->_formatCurrency($this->totalVAT), 0, 0, 'R');

        $pdf->SetFont('Helvetica','B',10);
        $pdf->SetY($pdf->GetY()+5);
        $pdf->Cell(162, 5, $GLOBALS['locPDFTotalIncludingVAT'] . ': ', 0, 0, 'R');
        $pdf->SetX(187 - $left);
        $pdf->Cell(20, 5, $this->_formatCurrency($this->totalSumVAT), 0, 1, 'R');
        $pdf->SetFont('Helvetica','',10);
      }
      else
      {
        $pdf->SetFont('Helvetica','B',10);
        $pdf->SetY($pdf->GetY()+5);
        $pdf->Cell(162, 5, $GLOBALS['locPDFTotalPrice'] . ': ', 0, 0, 'R');
        $pdf->SetX(187 - $left);
        $pdf->Cell(20, 5, $this->_formatCurrency($this->totalSumVAT), 0, 1, 'R');
        $pdf->SetFont('Helvetica','',10);
      }
    }

    $terms = getSetting('order_confirmation_terms');
    if ($terms) {
      $pdf->SetY($pdf->GetY() + 10);
      $pdf->MultiCell(187, 4, $terms, 0, 'L', 0);
    }
  }

}