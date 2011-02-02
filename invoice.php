<?php
/*******************************************************************************
VLLasku: web-based invoicing application.
Copyright (C) 2010-2011 Ere Maijala

Portions based on:
PkLasku : web-based invoicing software.
Copyright (C) 2004-2008 Samu Reinikainen

This program is free software. See attached LICENSE.

*******************************************************************************/

/*******************************************************************************
VLLasku: web-pohjainen laskutusohjelma.
Copyright (C) 2010-2011 Ere Maijala

Perustuu osittain sovellukseen:
PkLasku : web-pohjainen laskutusohjelmisto.
Copyright (C) 2004-2008 Samu Reinikainen

Tämä ohjelma on vapaa. Lue oheinen LICENSE.

*******************************************************************************/

require_once "sessionfuncs.php";

sesVerifySession();

require_once "sqlfuncs.php";
require_once "localize.php";
require_once "pdf.php";
require_once "datefuncs.php";
require_once "miscfuncs.php";

$intInvoiceId = getRequest('id', FALSE);
$printTemplate = getRequest('template', 1);

if (!$intInvoiceId) 
  return;

$res = mysql_param_query('SELECT filename, parameters, output_filename from {prefix}print_template WHERE id=?', array($printTemplate));
if (!$row = mysql_fetch_row($res))
  return;
$printTemplateFile = $row[0];
$printParameters = $row[1];
$printOutputFileName = $row[2];
  
$strQuery = 
  "SELECT inv.invoice_no, inv.invoice_date, inv.due_date, inv.ref_number, inv.name AS invoice_name, inv.reference, comp.company_name AS name, '' AS contact_person, comp.email, comp.billing_address, comp.company_name, comp.street_address, comp.zip_code, comp.city, inv.base_id, inv.state_id, inv.print_date, comp.customer_no, ref.invoice_no as refunded_invoice_no, inv.info as invoice_info " .
  "FROM {prefix}invoice inv " .
  "LEFT OUTER JOIN {prefix}company comp ON comp.id = inv.company_id ".
  "LEFT OUTER JOIN {prefix}invoice ref ON ref.id = inv.refunded_invoice_id ".
  "WHERE inv.id=?";
$intRes = mysql_param_query($strQuery, array($intInvoiceId));
$invoiceData = mysql_fetch_assoc($intRes);
if (!$invoiceData)
  die('Could not find invoice data');

$strInvoiceName = $invoiceData['invoice_name'];
$intBaseId = $invoiceData['base_id'];
$intStateId = $invoiceData['state_id'];
$intCustomerNo = $invoiceData['customer_no'];
$strInvoiceNo = $invoiceData['invoice_no'];
$strRefundedInvoiceNo = $invoiceData['refunded_invoice_no'];
$strRefNumber = $invoiceData['ref_number'];
$strInvoiceDate = dateConvIntDate2Date($invoiceData['invoice_date']);
$strDueDate = dateConvIntDate2Date($invoiceData['due_date']);
$strFormDueDate = ($intStateId == 5 || $intStateId == 6) ? $GLOBALS['locDUEDATENOW'] : $strDueDate;
$strPrintDate = $invoiceData['print_date'];
$strReference = $invoiceData['reference'];
$strBillingAddress = $invoiceData['billing_address'];
if (!$strBillingAddress) 
  $strBillingAddress = $invoiceData['company_name'] . "\n" . $invoiceData['street_address'] . "\n" . $invoiceData['zip_code'] . ' ' . $invoiceData['city'];
$strCompanyName = substr($strBillingAddress, 0, strpos($strBillingAddress, "\n"));
$strCompanyAddress = substr($strBillingAddress, strpos($strBillingAddress, "\n")+1);
$strName = $invoiceData['name'];
$strContactPerson = $invoiceData['contact_person'];
$strCompanyEmail = $invoiceData['email'];
$strReference = $strReference ? $strReference : $strContactPerson;
$strRefNumber = trim(strrev(chunk_split(strrev($strRefNumber),5,' ')));

mysql_param_query('UPDATE {prefix}invoice SET print_date=? where id=?', array(date('Ymd'), $intInvoiceId));

$strSelect = 'SELECT * FROM {prefix}base WHERE id=?';
$intRes = mysql_param_query($strSelect, array($intBaseId));
$senderData = mysql_fetch_assoc($intRes);
$strAssociation = $senderData['name'];
$strCompanyID = trim($senderData['company_id']);
$strAssociation = $senderData['name'];
$strContactPerson = $senderData['contact_person'];
$strStreetAddress = $senderData['street_address'];
$strZipCode = $senderData['zip_code'];
$strCity = $senderData['city'];
$strPhone = $senderData['phone'];
$strBankName1 = $senderData['bank_name'];
$strBankAccount1 = $senderData['bank_account'];
$strBankIBAN1 = $senderData['bank_iban'];
$strBankSWIFTBIC1 = $senderData['bank_swiftbic'];
$strBankName2 = $senderData['bank_name2'];
$strBankAccount2 = $senderData['bank_account2'];
$strBankIBAN2 = $senderData['bank_iban2'];
$strBankSWIFTBIC2 = $senderData['bank_swiftbic2'];
$strBankName3 = $senderData['bank_name3'];
$strBankAccount3 = $senderData['bank_account3'];
$strBankIBAN3 = $senderData['bank_iban3'];
$strBankSWIFTBIC3 = $senderData['bank_swiftbic3'];
$strWww = $senderData['www'];
$strEmail = $senderData['email'];
$boolVATReg = $senderData['vat_registered'];
$logo_filedata = $senderData['logo_filedata'];
$logo_top = $senderData['logo_top'];
$logo_left = $senderData['logo_left'];
$logo_width = $senderData['logo_width'];
$logo_bottom_margin = $senderData['logo_bottom_margin'];
    
$strAssocAddressLine = "$strAssociation";
if ($strCompanyID)
  $strCompanyID = $GLOBALS['locCOMPVATID'] . ": $strCompanyID";
if ($strCompanyID && $boolVATReg)
  $strCompanyID .= ', ';
if ($boolVATReg)
  $strCompanyID .= $GLOBALS['locVATREG'];
if ($strCompanyID)
  $strAssocAddressLine .= " ($strCompanyID)";
$strAssocAddressLine .= "\n$strStreetAddress";
if ($strStreetAddress && ($strZipCode || $strCity))
  $strAssocAddressLine .= ', ';
if ($strZipCode)
  $strAssocAddressLine .= "$strZipCode ";
$strAssocAddressLine .= $strCity;

$strAssocAddress = "$strAssociation\n$strStreetAddress\n$strZipCode $strCity";
if ($strPhone)
  $strContactInfo = "\n" . $GLOBALS['locPHONE'] . " $strPhone";
else
  $strContactInfo = '';

$strQuery = 
    "SELECT pr.product_name, ir.description, ir.pcs, ir.price, ir.row_date, ir.vat, ir.vat_included, rt.name type ".
    "FROM {prefix}invoice_row ir ".
    "LEFT OUTER JOIN {prefix}row_type rt ON rt.id = ir.type_id ".
    "LEFT OUTER JOIN {prefix}product pr ON ir.product_id = pr.id ".
    "WHERE ir.invoice_id=? AND ir.deleted=0 ORDER BY ir.order_no, row_date, pr.product_name DESC, ir.description DESC";
$intTotSum = 0;
$intTotVAT = 0;
$intTotSumVAT = 0;
$intRes = mysql_param_query($strQuery, array($intInvoiceId));
$intNRes = mysql_num_rows($intRes);
$i = 0;
while ($row = mysql_fetch_assoc($intRes)) 
{
  $strProduct = trim($row['product_name']);
  $astrDescription[$i] = trim($row['description']);
  if ($strProduct)
  {
    if ($astrDescription[$i]) 
      $astrDescription[$i] = $strProduct .  ' (' . $astrDescription[$i] . ')';
    else
      $astrDescription[$i] = $strProduct;
  }
  $astrRowDate[$i] = dateConvIntDate2Date($row['row_date']);
  $astrRowPrice[$i] = $row['price'];
  $astrPieces[$i] = $row['pcs'];
  $astrVAT[$i] = $row['vat'];
  $aboolVATIncluded[$i] = $row['vat_included'];
  $astrRowType[$i] = $row['type'];

  if ($aboolVATIncluded[$i])
  {
    $intRowSumVAT[$i] = $astrPieces[$i] * $astrRowPrice[$i];
    
    $intRowSum[$i] = $intRowSumVAT[$i] / (1 + $astrVAT[$i] / 100);
    $intRowVAT[$i] = $intRowSumVAT[$i] - $intRowSum[$i];
    
    $astrRowPrice[$i] /= (1 + $astrVAT[$i] / 100);
  }
  else
  {
    $intRowSum[$i] = $astrPieces[$i] * $astrRowPrice[$i];
    $intRowVAT[$i] = $intRowSum[$i] * ($astrVAT[$i] / 100);
    $intRowSumVAT[$i] = $intRowSum[$i] + $intRowVAT[$i];
  }
  $intTotSum += $intRowSum[$i];
  $intTotVAT += $intRowVAT[$i];
  $intTotSumVAT += $intRowSumVAT[$i];
  ++$i;
}

require $printTemplateFile;

?>
