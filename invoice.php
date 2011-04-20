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
  "SELECT inv.*, ref.invoice_no as refunded_invoice_no " .
  "FROM {prefix}invoice inv " .
  "LEFT OUTER JOIN {prefix}invoice ref ON ref.id = inv.refunded_invoice_id ".
  "WHERE inv.id=?";
$intRes = mysql_param_query($strQuery, array($intInvoiceId));
$invoiceData = mysql_fetch_assoc($intRes);
if (!$invoiceData)
  die('Could not find invoice data');

$strQuery = 'SELECT * FROM {prefix}company WHERE id=?';
$intRes = mysql_param_query($strQuery, array($invoiceData['company_id']));
$recipientData = mysql_fetch_assoc($intRes);

//invoice_no, inv.invoice_date, inv.due_date, inv.ref_number, inv.name AS invoice_name, inv.reference, inv.base_id, inv.state_id, inv.print_date, ref.invoice_no as refunded_invoice_no, inv.info as invoice_info
//comp.company_name AS name, '' AS contact_person, comp.email, comp.billing_address, comp.company_name, comp.street_address, comp.zip_code, comp.city, comp.company_id, comp.customer_no, 

$strQuery = 'SELECT * FROM {prefix}base WHERE id=?';
$intRes = mysql_param_query($strQuery, array($invoiceData['base_id']));
$senderData = mysql_fetch_assoc($intRes);
if (!$senderData)
  die('Could not find invoice sender data');
    
$strQuery = 
    "SELECT pr.product_name, ir.description, ir.pcs, ir.price, ir.discount, ir.row_date, ir.vat, ir.vat_included, rt.name type ".
    "FROM {prefix}invoice_row ir ".
    "LEFT OUTER JOIN {prefix}row_type rt ON rt.id = ir.type_id ".
    "LEFT OUTER JOIN {prefix}product pr ON ir.product_id = pr.id ".
    "WHERE ir.invoice_id=? AND ir.deleted=0 ORDER BY ir.order_no, row_date, pr.product_name DESC, ir.description DESC";
$intRes = mysql_param_query($strQuery, array($intInvoiceId));
$invoiceRowData = array();
while ($row = mysql_fetch_assoc($intRes)) 
{
  $invoiceRowData[] = $row;
}

mysql_param_query('UPDATE {prefix}invoice SET print_date=? where id=?', array(date('Ymd'), $intInvoiceId));

require $printTemplateFile;
$printer = new InvoicePrinter;
$printer->init($intInvoiceId, $printParameters, $printOutputFileName, $senderData, $recipientData, $invoiceData, $invoiceRowData);
$printer->printInvoice();

?>
