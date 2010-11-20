<?php
/*******************************************************************************
VLLasku: web-based invoicing application.
Copyright (C) 2010 Ere Maijala

Portions based on:
PkLasku : web-based invoicing software.
Copyright (C) 2004-2008 Samu Reinikainen

This program is free software. See attached LICENSE.

*******************************************************************************/

/*******************************************************************************
VLLasku: web-pohjainen laskutusohjelma.
Copyright (C) 2010 Ere Maijala

Perustuu osittain sovellukseen:
PkLasku : web-pohjainen laskutusohjelmisto.
Copyright (C) 2004-2008 Samu Reinikainen

Tämä ohjelma on vapaa. Lue oheinen LICENSE.

*******************************************************************************/

require "htmlfuncs.php";
require "sqlfuncs.php";
require "sessionfuncs.php";
require_once "pdfbarcode128.php";

$strSesID = $_REQUEST['ses'] ? $_REQUEST['ses'] : FALSE;

if( !sesCheckSession( $strSesID ) ) {
    die;
}
require "localize.php";
define('FPDF_FONTPATH','./font/');
require_once "fpdf.php";

require "datefuncs.php";
require "miscfuncs.php";

class PDF extends FPDF
{
  public $footerLeft = '', $footerCenter = '', $footerRight = '';

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

$intInvoiceId = (int)$_REQUEST['id'] ? (int)$_REQUEST['id'] : FALSE;
$strType = $_REQUEST['type'] ? $_REQUEST['type'] : 'comp';

$prefix = _DB_PREFIX_;

if( $intInvoiceId ) {
    $strQuery = 
        "SELECT inv.invoice_no, inv.invoice_date, inv.due_date, inv.ref_number, inv.name AS invoice_name, inv.reference, comp.company_name AS name, '' AS contact_person, comp.email, comp.billing_address, CONCAT(comp.company_name, '\n', comp.street_address, '\n', comp.zip_code, ' ', comp.city) AS billing_address2, inv.base_id, comp.id as company_id, ref.invoice_no as refunded_invoice_no " .
        "FROM ${prefix}_invoice inv " .
        "INNER JOIN ${prefix}_company comp ON comp.id = inv.company_id ".
        "LEFT OUTER JOIN ${prefix}_invoice ref ON ref.id = inv.refunded_invoice_id ".
        "WHERE inv.id = $intInvoiceId";
    $intRes = mysql_query_check($strQuery);
    $intNRows = mysql_numrows($intRes);
    if( $intNRows ) {
       $strInvoiceName = mysql_result($intRes, 0, "invoice_name");
       $intBaseId = mysql_result($intRes, 0, "base_id");
       $strClientId = mysql_result($intRes, 0, "company_id");
       $strInvoiceNo = mysql_result($intRes, 0, "invoice_no");
       $strRefundedInvoiceNo = mysql_result($intRes, 0, "refunded_invoice_no");
       $strRefNumber = mysql_result($intRes, 0, "ref_number");
       $strInvoiceDate = dateConvIntDate2Date(mysql_result($intRes, 0, "invoice_date"));
       $strDueDate = dateConvIntDate2Date(mysql_result($intRes, 0, "due_date"));
       $strReference = mysql_result($intRes, 0, "reference");
       $strBillingAddress = mysql_result($intRes, 0, "billing_address");
       if( !$strBillingAddress ) {
           $strBillingAddress = mysql_result($intRes, 0, "billing_address2");
       }
       $strCompanyName = substr($strBillingAddress, 0, strpos($strBillingAddress, "\n"));
       $strCompanyAddress = substr($strBillingAddress, strpos($strBillingAddress, "\n")+1);
       $strName = mysql_result($intRes, 0, "name");
       $strContactPerson = mysql_result($intRes, 0, "contact_person");
       $strCompanyEmail = mysql_result($intRes, 0, "email");
       
       $strReference = $strReference ? $strReference : $strContactPerson;
    }
    $strRefNumber = trim(strrev(chunk_split(strrev($strRefNumber),5,' ')));
    
    $strSelect = "SELECT * FROM ". _DB_PREFIX_. "_base WHERE id = $intBaseId";
    $intRes = mysql_query($strSelect);
    
    $strAssociation = mysql_result($intRes, 0, "name");
    $strCompanyID = mysql_result($intRes, 0, "company_id");
    $strAssociation = mysql_result($intRes, 0, "name");
    $strContactPerson = mysql_result($intRes, 0, "contact_person");
    $strStreetAddress = mysql_result($intRes, 0, "street_address");
    $strZipCode = mysql_result($intRes, 0, "zip_code");
    $strCity = mysql_result($intRes, 0, "city");
    $strPhone = mysql_result($intRes, 0, "phone");
    $strBankName1 = mysql_result($intRes, 0, "bank_name");
    $strBankAccount1 = mysql_result($intRes, 0, "bank_account");
    $strBankIBAN1 = mysql_result($intRes, 0, "bank_iban");
    $strBankSWIFTBIC1 = mysql_result($intRes, 0, "bank_swiftbic");
    $strBankName2 = mysql_result($intRes, 0, "bank_name2");
    $strBankAccount2 = mysql_result($intRes, 0, "bank_account2");
    $strBankIBAN2 = mysql_result($intRes, 0, "bank_iban2");
    $strBankSWIFTBIC2 = mysql_result($intRes, 0, "bank_swiftbic2");
    $strBankName3 = mysql_result($intRes, 0, "bank_name3");
    $strBankAccount3 = mysql_result($intRes, 0, "bank_account3");
    $strBankIBAN3 = mysql_result($intRes, 0, "bank_iban3");
    $strBankSWIFTBIC3 = mysql_result($intRes, 0, "bank_swiftbic3");
    $strWww = mysql_result($intRes, 0, "www");
    $strEmail = mysql_result($intRes, 0, "email");
    $boolVATReg = mysql_result($intRes, 0, "vat_registered");
        
    $strAssocAddressLine = 
        $strAssociation. "   ". $strStreetAddress. " ". $strZipCode. " ". $strCity;
    $strAssocAddress = 
        $strAssociation. "\n". $strStreetAddress. "\n". $strZipCode. " ". $strCity;
    $strContactInfo = 
        $GLOBALS['locCOMPVATID']. ": $strCompanyID";
    if ($boolVATReg)
      $strContactInfo .= ' ' . $GLOBALS['locVATREG'];
    if ($strPhone)
      $strContactInfo .= '  ' . $GLOBALS['locPHONE']. ": $strPhone";
    if ($strEmail)
      $strContactInfo .= '  ' . $GLOBALS['locEMAIL']. ": $strEmail";
    
    $strQuery = 
        "SELECT pr.product_name, ir.description, ir.pcs, ir.price, ir.row_date, ir.vat, ir.vat_included, rt.name type ".
        "FROM ${prefix}_invoice_row ir ".
        "INNER JOIN ${prefix}_row_type rt ON rt.id = ir.type_id ".
        "LEFT OUTER JOIN ${prefix}_product pr ON ir.product_id = pr.id ".
        "WHERE ir.invoice_id = ". $intInvoiceId. " ORDER BY ir.order_no, row_date, pr.product_name DESC, ir.description DESC";
    $intRes = mysql_query_check($strQuery);
    if( $intRes ) {
        $intNRes = mysql_num_rows($intRes);
        for( $i = 0; $i < $intNRes; $i++ ) {
            $strProduct = trim(mysql_result($intRes, $i, "product_name"));
            $astrDescription[$i] = trim(mysql_result($intRes, $i, "description"));
            if ($strProduct)
            {
              if ($astrDescription[$i]) 
                $astrDescription[$i] = $strProduct .  ' (' . $astrDescription[$i] . ')';
              else
                $astrDescription[$i] = $strProduct;
            }
            $astrRowDate[$i] = dateConvIntDate2Date(mysql_result($intRes, $i, "row_date"));
            $astrRowPrice[$i] = mysql_result($intRes, $i, "price");
            $astrPieces[$i] = mysql_result($intRes, $i, "pcs");
            $astrVAT[$i] = mysql_result($intRes, $i, "vat");
            $aboolVATIncluded[$i] = mysql_result($intRes, $i, "vat_included");
            $astrRowType[$i] = mysql_result($intRes, $i, "type");
            $intRowSum[$i] = $astrPieces[$i] * $astrRowPrice[$i];
            $intRowVAT[$i] = $intRowSum[$i] * ($astrVAT[$i] / 100);
            if ($aboolVATIncluded[$i])
              $intRowSum[$i] -= $intRowVAT[$i];
            $intRowSumVAT[$i] = $intRowSum[$i] + $intRowVAT[$i];
            $intTotSum += $intRowSum[$i];
            $intTotVAT += $intRowVAT[$i];
            $intTotSumVAT += $intRowSumVAT[$i];
        }
    }
}
else {
    die("Sorry, no can do!");
}
$pdf=new PDF('P','mm','A4');
$pdf->AddPage();
$pdf->SetAutoPageBreak(FALSE);
$pdf->footerLeft = $strAssocAddressLine;
$pdf->footerCenter = $strContactInfo;
$pdf->footerRight = $strWww;

//TOP
//$pdf->Image($GLOBALS['sesLANG']."_images/banner.jpg", 10, 5, 40);

//$pdf->SetFont('Helvetica','B',14);


//sender
$pdf->SetTextColor(125);
$pdf->SetFont('Helvetica','B',10);
$pdf->SetY($pdf->GetY()+5);
//$pdf->SetX(50);
$pdf->Cell(120, 5, $strAssociation, 0, 1);
$pdf->SetFont('Helvetica','',10);
$pdf->MultiCell(120, 5, $strStreetAddress. "\n". $strZipCode. " ". $strCity,0,1);

//receiver
$pdf->SetTextColor(0);
$pdf->SetFont('Helvetica','B',14);
$pdf->SetY($pdf->GetY()+5);
$pdf->Cell(120, 6, $strCompanyName,0,1);
$pdf->SetFont('Helvetica','',14);
$pdf->MultiCell(120, 6, $strCompanyAddress,0,1);
//$pdf->MultiCell(60, 5, $strBillingAddress,0,1);
$pdf->SetFont('Helvetica','',12);
$pdf->SetY($pdf->GetY() + 4);
$pdf->Cell(120, 6, $strCompanyEmail,0,1);

//invoiceinfo headers
$pdf->SetXY(115,10);
$pdf->SetFont('Helvetica','B',12);
$pdf->Cell(40, 5, $GLOBALS['locINVOICEHEADER'], 0, 1, 'R');
$pdf->SetFont('Helvetica','',10);
$pdf->SetXY(115, $pdf->GetY()+5);
/*
$pdf->Cell(40, 5, $GLOBALS['locSENDER'] .": ", 0, 0, 'R');
$pdf->Cell(60, 5, $strAssociation, 0, 1);
$pdf->SetX(115);
*/
$pdf->SetX(115);
$pdf->Cell(40, 5, $GLOBALS['locCLIENTNO'] .": ", 0, 0, 'R');
$pdf->Cell(60, 5, $strClientId, 0, 1);
$pdf->SetX(115);
$pdf->Cell(40, 5, $GLOBALS['locINVNUMBER'] .": ", 0, 0, 'R');
$pdf->Cell(60, 5, $strInvoiceNo, 0, 1);
$pdf->SetX(115);
$pdf->Cell(40, 5, $GLOBALS['locPDFINVDATE'] .": ", 0, 0, 'R');
$pdf->Cell(60, 5, $strInvoiceDate, 0, 1);
$pdf->SetX(115);
$pdf->Cell(40, 5, $GLOBALS['locPDFDUEDATE'] .": ", 0, 0, 'R');
$pdf->Cell(60, 5, $strDueDate, 0, 1);
$pdf->SetX(115);
$pdf->Cell(40, 5, $GLOBALS['locTERMSOFPAYMENT'] .": ", 0, 0, 'R');
$pdf->Cell(60, 5, $termsOfPayment, 0, 1);
$pdf->SetX(115);
$pdf->Cell(40, 5, $GLOBALS['locPERIODFORCOMPLAINTS'] .": ", 0, 0, 'R');
$pdf->Cell(60, 5, $periodForComplaints, 0, 1);
$pdf->SetX(115);
$pdf->Cell(40, 5, $GLOBALS['locPENALTYINTEREST'] .": ", 0, 0, 'R');
$pdf->Cell(60, 5, $penaltyInterest, 0, 1);
$pdf->SetX(115);
$pdf->Cell(40, 5, $GLOBALS['locPDFINVREFNO'] .": ", 0, 0, 'R');
$pdf->Cell(60, 5, $strRefNumber, 0, 1);
$pdf->SetX(115);
$pdf->Cell(40, 5, $GLOBALS['locYOURREFERENCE'] .": ", 0, 0, 'R');
$pdf->Cell(60, 5, $strReference, 0, 1);

if ($strRefundedInvoiceNo)
{
  $pdf->SetX(115);
  $pdf->Cell(40, 5, sprintf($GLOBALS['locREFUNDSINVOICE'], $strRefundedInvoiceNo), 0, 0, 'R');
}

$pdf->SetY($pdf->GetY()+5);
$pdf->Line(5, $pdf->GetY(), 200, $pdf->GetY());
$pdf->SetY($pdf->GetY()+5);

if( $intNRes <= $invoicePdfRows ) {

  //middle - invoicerows
  //invoiceinfo headers
  $pdf->SetXY(7,$pdf->GetY());
  if( $showInvoiceRowDate ) {
      $pdf->Cell(60, 5, $GLOBALS['locROWNAME'], 0, 0, "L");
      $pdf->Cell(20, 5, $GLOBALS['locDATE'], 0, 0, "L");
  }
  else {
      $pdf->Cell(80, 5, $GLOBALS['locROWNAME'], 0, 0, "L");
  }
  $pdf->Cell(15, 5, $GLOBALS['locROWPRICE'], 0, 0, "R");
  $pdf->Cell(15, 5, $GLOBALS['locPCS'], 0, 0, "R");
  $pdf->Cell(15, 5, $GLOBALS['locUNIT'], 0, 0, "R");
  $pdf->Cell(20, 5, $GLOBALS['locROWTOTAL'], 0, 0, "R");
  $pdf->Cell(15, 5, $GLOBALS['locVATPERCENT'], 0, 0, "R");
  $pdf->Cell(15, 5, $GLOBALS['locTAX'], 0, 0, "R");
  $pdf->Cell(20, 5, $GLOBALS['locROWTOTAL'], 0, 1, "R");
  
  //rows
  $pdf->SetY($pdf->GetY()+5);
  for( $i = 0; $i < $intNRes; $i++ ) {
      if( $astrRowPrice[$i] == 0 && $astrPieces[$i] == 0 ) {
          $pdf->SetX(7);
          $pdf->MultiCell(0, 5, $astrDescription[$i], 0, 'L');
      }
      else {
          //$pdf->SetY($pdf->GetY()+5);
          if( $showInvoiceRowDate ) {
              $pdf->SetX(67);
              $pdf->Cell(20, 5, $astrRowDate[$i], 0, 0, "L");
          }
          else {
              $pdf->SetX(87);
          }
          $pdf->Cell(15, 5, miscRound2Decim($astrRowPrice[$i]), 0, 0, "R");
          $pdf->Cell(15, 5, miscRound2Decim($astrPieces[$i]), 0, 0, "R");
          $pdf->Cell(15, 5, $astrRowType[$i], 0, 0, "R");
          $pdf->Cell(20, 5, miscRound2Decim($intRowSum[$i]), 0, 0, "R");
          $pdf->Cell(15, 5, $astrVAT[$i], 0, 0, "R");
          $pdf->Cell(15, 5, miscRound2Decim($intRowVAT[$i]), 0, 0, "R");
          $pdf->Cell(20, 5, miscRound2Decim($intRowSumVAT[$i]), 0, 0, "R");
          $pdf->SetX(7);
          if( $showInvoiceRowDate ) {
              $pdf->MultiCell(60, 5, $astrDescription[$i], 0, 'L');
          }
          else {
              $pdf->MultiCell(80, 5, $astrDescription[$i], 0, 'L');
          }
          
          
      }
  }
  $pdf->SetFont('Helvetica','',10);
  $pdf->SetY($pdf->GetY()+10);
  $pdf->Cell(162, 5, $GLOBALS['locTOTALEXCLUDINGVAT'] .": ", 0, 0, "R");
  $pdf->SetX(182);
  $pdf->Cell(20, 5, miscRound2Decim($intTotSum), 0, 0, "R");
  
  $pdf->SetFont('Helvetica','',10);
  $pdf->SetY($pdf->GetY()+5);
  $pdf->Cell(162, 5, $GLOBALS['locTOTALVAT'] .": ", 0, 0, "R");
  $pdf->SetX(182);
  $pdf->Cell(20, 5, miscRound2Decim($intTotVAT), 0, 0, "R");
  
  $pdf->SetFont('Helvetica','B',10);
  $pdf->SetY($pdf->GetY()+5);
  $pdf->Cell(162, 5, $GLOBALS['locTOTALINCLUDINGVAT'] .": ", 0, 0, "R");
  $pdf->SetX(182);
  $pdf->Cell(20, 5, miscRound2Decim($intTotSumVAT), 0, 1, "R");

}
else {
    $pdf->SetFont('Helvetica','B',20);
    $pdf->SetXY(20, $pdf->GetY()+40);
    $pdf->MultiCell(180, 5, $GLOBALS['locSEESEPARATESTATEMENT'], 0, "L", 0);
}

if( $showBarcode ) {
    $intStartY = 190;
    $intStartYBorders = 195;
}
else {
    $intStartY = 205;
    $intStartYBorders = 210;
}

//bottom - paymentinfo
$pdf->SetFont('Helvetica','',7);
$pdf->SetXY(7, $intStartY);
$pdf->Cell(66, 5, $strAssocAddressLine, 0, 1, "L");
$pdf->SetXY(75, $intStartY);
$pdf->Cell(75, 5, $strContactInfo, 0, 1, "C");
$pdf->SetXY(150, $intStartY);
$pdf->Cell(50, 5, $strWww, 0, 1, "R");


//borders...
$intStartY = $intStartYBorders;
$intStartX = 7;

$intMaxX = 200;
//1. hor.line - full width
$pdf->SetLineWidth(0.13);
$pdf->Line($intStartX, $intStartY - 1, $intMaxX, $intStartY - 1);
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
$pdf->Cell(15, 4, $strBankName1, 0, 0, "L");
$pdf->SetX($intStartX + 52);
$pdf->Cell(40, 4, $strBankAccount1, 0, 0, "L");
$pdf->SetX($intStartX + 120.4);
$pdf->Cell(50, 4, $strBankIBAN1, 0, 0, "L");
$pdf->SetX($intStartX + 170.4);
$pdf->Cell(15, 4, $strBankSWIFTBIC1, 0, 0, "L");

// account 2
$pdf->SetXY($intStartX + 22, $intStartY + 5);
$pdf->Cell(15, 4, $strBankName2, 0, 0, "L");
$pdf->SetX($intStartX + 52);
$pdf->Cell(40, 4, $strBankAccount2, 0, 0, "L");
$pdf->SetX($intStartX + 120.4);
$pdf->Cell(50, 4, $strBankIBAN2, 0, 0, "L");
$pdf->SetX($intStartX + 170.4);
$pdf->Cell(15, 4, $strBankSWIFTBIC2, 0, 0, "L");

// account 3
$pdf->SetXY($intStartX + 22, $intStartY + 9);
$pdf->Cell(15, 4, $strBankName3, 0, 0, "L");
$pdf->SetX($intStartX + 52);
$pdf->Cell(40, 4, $strBankAccount3, 0, 0, "L");
$pdf->SetX($intStartX + 120.4);
$pdf->Cell(50, 4, $strBankIBAN3, 0, 0, "L");
$pdf->SetX($intStartX + 170.4);
$pdf->Cell(15, 4, $strBankSWIFTBIC3, 0, 0, "L");

//receiver
$pdf->SetFont('Helvetica','',7);
$pdf->SetXY($intStartX, $intStartY + 18);
$pdf->Cell(19, 5, "Saaja", 0, 1, "R");
$pdf->SetXY($intStartX, $intStartY + 22);
$pdf->Cell(19, 5, "Mottagare", 0, 1, "R");
$pdf->SetFont('Helvetica','',10);
$pdf->SetXY($intStartX + 22,$intStartY + 18);
$pdf->MultiCell(100, 4, $strAssocAddress,0,1);

//payer
$pdf->SetFont('Helvetica','',7);
$pdf->SetXY($intStartX, $intStartY + 35);
$pdf->MultiCell(19, 2.8, "Maksajan\nnimi ja\nosoite", 0, "R", 0);
$pdf->SetXY($intStartX, $intStartY + 45);
$pdf->MultiCell(19, 2.8, "Betalarens\nnamn och\naddress", 0, "R", 0);
$pdf->SetFont('Helvetica','',10);
$pdf->SetXY($intStartX + 22, $intStartY + 35);
$pdf->MultiCell(100, 4, $strBillingAddress,0,1);

//underscript
$intStartY = $intStartYBorders;
$pdf->SetFont('Helvetica','',7);
$pdf->SetXY($intStartX, $intStartY + 60);
$pdf->Cell(19, 5, "Allekirjoitus", 0, 1, "R");
//from account
$pdf->SetXY($intStartX, $intStartY + 68);
$pdf->Cell(19, 5, "Tililtä", 0, 1, "R");

//info
$pdf->SetFont('Helvetica','',10);
$pdf->SetXY($intStartX + 112.4, $intStartY + 20);
$pdf->Cell(70, 5, "Laskunumero ".$strInvoiceNo, 0, 1, "L");
$pdf->SetXY($intStartX + 112.4, $intStartY + 30);
$pdf->Cell(70, 5, "Viitenumero on aina mainittava maksettaessa.", 0, 1, "L");
$pdf->SetXY($intStartX + 112.4, $intStartY + 35);
$pdf->Cell(70, 5, "Referensnumret bör alltid anges vid betalning.", 0, 1, "L");
//terms
$pdf->SetFont('Helvetica','',5);
$pdf->SetXY($intStartX + 133, $intStartY + 85);
$pdf->MultiCell(70, 2, "Maksu välitetään saajalle maksujenvälityksen ehtojen mukaisesti ja vain\nmaksajan ilmoittaman tilinumeron perusteella",0,1);
$pdf->SetXY($intStartX + 133, $intStartY + 90);
$pdf->MultiCell(70, 2, "Betalningen förmedlas till mottagaren enligt villkoren för betalnings-\nförmedling och endast till det kontonummer som betalaren angivit",0,1);
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
$pdf->Cell(15, 5, $strRefNumber, 0, 1, "L");

//duedate
$pdf->SetFont('Helvetica','',7);
$pdf->SetXY($intStartX + 112.4, $intStartY + 68);
$pdf->Cell(15, 5, "Eräpäivä", 0, 1, "L");
$pdf->SetFont('Helvetica','',10);
$pdf->SetXY($intStartX + 131.4, $intStartY + 68);
$pdf->Cell(25, 5, $strDueDate, 0, 1, "L");

//eur
$pdf->SetFont('Helvetica','',7);
$pdf->SetXY($intStartX + 161, $intStartY + 68);
$pdf->Cell(15, 5, "Euro", 0, 1, "L");
$pdf->SetFont('Helvetica','',10);
$pdf->SetXY($intStartX + 151, $intStartY + 68);
$pdf->Cell(40, 5, miscRound2Decim($intTotSumVAT), 0, 1, "R");

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
if( $showBarcode ) {
    $tmpAccount = str_replace("-", str_repeat('0', 14 -(strlen($strBankAccount1)-1)),$strBankAccount1);
    $tmpSum = str_replace(",", "", miscRound2Decim($intTotSumVAT));
    $tmpSum = str_repeat('0', 8 - strlen($tmpSum)). $tmpSum;
    $tmpRefNumber = str_replace(" ", "", $strRefNumber);
    $tmpRefNumber = str_repeat('0', 20 - strlen($tmpRefNumber)). $tmpRefNumber;
    $atmdDueDate = explode(".", $strDueDate);
    $tmpDueDate = substr($atmdDueDate[2], -2). $atmdDueDate[1]. $atmdDueDate[0];
    
    $code_string = "2". $tmpAccount. $tmpSum. $tmpRefNumber. $tmpDueDate. "0000";
    $code_string = $code_string. miscCalcCheckNo($code_string);
    
    $code = new pdfbarcode128($code_string, 3 );
    
    $code->set_pdf_document($pdf);
    $width = $code->get_width();
    $code->draw_barcode(24, 280, 11, FALSE );
    //$code->_dump_pattern();
    //echo "<br><br>". $code_string;
}

if( $intNRes > $invoicePdfRows ) {
    $pdf->AddPage();
    $pdf->SetAutoPageBreak(TRUE, 20);
  //middle - invoicerows
  //invoiceinfo headers
  $pdf->SetXY(7,20);
  
  $pdf->SetFont('Helvetica','B',20);
  $pdf->SetXY(20, $pdf->GetY());
  $pdf->Cell(80, 5, "Laskuerittely", 0, 0, "L");
  $pdf->SetFont('Helvetica','',10);
  $pdf->Cell(80, 5, "Laskunro: $strInvoiceNo", 0, 1, "L");
  $pdf->SetXY(7, $pdf->GetY()+10);
  if( $showInvoiceRowDate ) {
      $pdf->Cell(60, 5, $GLOBALS['locROWNAME'], 0, 0, "L");
      $pdf->Cell(20, 5, $GLOBALS['locDATE'], 0, 0, "L");
  }
  else {
      $pdf->Cell(80, 5, $GLOBALS['locROWNAME'], 0, 0, "L");
  }
  $pdf->Cell(15, 5, $GLOBALS['locPRICE'], 0, 0, "R");
  $pdf->Cell(15, 5, $GLOBALS['locPCS'], 0, 0, "R");
  $pdf->Cell(15, 5, $GLOBALS['locUNIT'], 0, 0, "R");
  $pdf->Cell(20, 5, $GLOBALS['locTOTAL'], 0, 0, "R");
  $pdf->Cell(15, 5, $GLOBALS['locVATPERCENT'], 0, 0, "R");
  $pdf->Cell(15, 5, $GLOBALS['locTAX'], 0, 0, "R");
  $pdf->Cell(20, 5, $GLOBALS['locTOTAL'], 0, 1, "R");
  
  //rows
  $pdf->SetY($pdf->GetY()+5);
  for( $i = 0; $i < $intNRes; $i++ ) {
      if( $astrRowPrice[$i] == 0 && $astrPieces[$i] == 0 ) {
          $pdf->SetX(7);
          $pdf->MultiCell(0, 5, $astrDescription[$i], 0, 'L');
      }
      else {
          //$pdf->SetY($pdf->GetY()+5);
          if( $showInvoiceRowDate ) {
              $pdf->SetX(67);
              $pdf->Cell(20, 5, $astrRowDate[$i], 0, 0, "L");
          }
          else {
              $pdf->SetX(87);
          }
          $pdf->Cell(15, 5, miscRound2Decim($astrRowPrice[$i]), 0, 0, "R");
          $pdf->Cell(15, 5, miscRound2Decim($astrPieces[$i]), 0, 0, "R");
          $pdf->Cell(15, 5, $astrRowType[$i], 0, 0, "R");
          $pdf->Cell(20, 5, miscRound2Decim($intRowSum[$i]), 0, 0, "R");
          $pdf->Cell(15, 5, $astrVAT[$i], 0, 0, "R");
          $pdf->Cell(15, 5, miscRound2Decim($intRowVAT[$i]), 0, 0, "R");
          $pdf->Cell(20, 5, miscRound2Decim($intRowSumVAT[$i]), 0, 0, "R");
          $pdf->SetX(7);
          if( $showInvoiceRowDate ) {
              $pdf->MultiCell(60, 5, $astrDescription[$i], 0, 'L');
          }
          else {
              $pdf->MultiCell(80, 5, $astrDescription[$i], 0, 'L');
          }
          
          
      }
  }
  $pdf->SetFont('Helvetica','',10);
  $pdf->SetY($pdf->GetY()+10);
  $pdf->Cell(162, 5, $GLOBALS['locTOTALEXCLUDINGVAT'] .": ", 0, 0, "R");
  $pdf->SetX(182);
  $pdf->Cell(20, 5, miscRound2Decim($intTotSum), 0, 0, "R");
  
  $pdf->SetFont('Helvetica','',10);
  $pdf->SetY($pdf->GetY()+5);
  $pdf->Cell(162, 5, $GLOBALS['locTOTALVAT'] .": ", 0, 0, "R");
  $pdf->SetX(182);
  $pdf->Cell(20, 5, miscRound2Decim($intTotVAT), 0, 0, "R");
  
  $pdf->SetFont('Helvetica','B',10);
  $pdf->SetY($pdf->GetY()+5);
  $pdf->Cell(162, 5, $GLOBALS['locTOTALINCLUDINGVAT'] .": ", 0, 0, "R");
  $pdf->SetX(182);
  $pdf->Cell(20, 5, miscRound2Decim($intTotSumVAT), 0, 1, "R");

}

$pdf->Output("invoice_". $strInvoiceNo .".pdf","I");
?>