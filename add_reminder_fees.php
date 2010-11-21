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

$strSesID = $_REQUEST['ses'] ? $_REQUEST['ses'] : FALSE;

if( !sesCheckSession( $strSesID ) ) {
    die;
}
require "localize.php";
require "datefuncs.php";
require "miscfuncs.php";

$intInvoiceId = (int)$_REQUEST['id'] ? (int)$_REQUEST['id'] : FALSE;

$strAlert = '';
if( $intInvoiceId ) {
    $strQuery = 
        'SELECT inv.due_date, inv.state_id, inv.print_date ' .
        'FROM {prefix}invoice inv ' .
        'WHERE inv.id = ?';
    $intRes = mysql_param_query($strQuery, array($intInvoiceId));
    $intNRows = mysql_numrows($intRes);
    if( $intNRows ) {
       $intStateId = mysql_result($intRes, 0, "state_id");
       $strDueDate = dateConvIntDate2Date(mysql_result($intRes, 0, "due_date"));
       $strPrintDate = (mysql_result($intRes, 0, "print_date"));
     }
    $strRefNumber = trim(strrev(chunk_split(strrev($strRefNumber),5,' ')));
    
    $intDaysOverdue = floor((time() - strtotime($strDueDate)) / 60 / 60 / 24);
    if ($intDaysOverdue > 0 && ($intStateId == 1 || $intStateId == 2 || $intStateId == 5 || $intStateId == 6))
    {
      // Update invoice state
      if ($intStateId == 1 || $intStateId == 2)
        $intStateId = 5;
      else
        $intStateId = 6;
      mysql_param_query('UPDATE {prefix}invoice SET state_id = ? where id = ?', array($intStateId, $intInvoiceId));
      
      // Add reminder fee
      if ($notificationFee)
      {
        // Remove old fee from same day
        mysql_param_query('DELETE FROM {prefix}invoice_row WHERE invoice_id = ? AND reminder_row = 2 AND row_date = ?', array($intInvoiceId, date('Ymd')));
        
        $strQuery = 'INSERT INTO {prefix}invoice_row (invoice_id, description, pcs, price, row_date, vat, vat_included, reminder_row) ' .
          'VALUES (?, ?, 1, ?, ?, 0, 0, 2)';
        mysql_param_query($strQuery, array($intInvoiceId, $GLOBALS['locREMINDERFEEDESC'], $notificationFee, date('Ymd')));
      }
      // Add penalty interest
      if ($penaltyInterest)
      {
        // Remove old penalty interest
        mysql_param_query('DELETE FROM {prefix}invoice_row WHERE invoice_id = ? AND reminder_row = 1', array($intInvoiceId));
        
        // Add new interest
        $intTotSumVAT = 0;
        $strQuery = 
            'SELECT ir.pcs, ir.price, ir.vat, ir.vat_included '.
            'FROM {prefix}invoice_row ir '.
            'WHERE ir.invoice_id = ?';
        $intRes = mysql_param_query($strQuery, array($intInvoiceId));
        if( $intRes ) {
            $intNRes = mysql_num_rows($intRes);
            for( $i = 0; $i < $intNRes; $i++ ) {
                $strRowPrice = mysql_result($intRes, $i, "price");
                $strPieces = mysql_result($intRes, $i, "pcs");
                $strVAT = mysql_result($intRes, $i, "vat");
                $boolVATIncluded = mysql_result($intRes, $i, "vat_included");
                $intRowSum = $strPieces * $strRowPrice;
                $intRowVAT = $intRowSum * ($strVAT / 100);
                if ($boolVATIncluded)
                  $intRowSum -= $intRowVAT;
                $intRowSumVAT = $intRowSum + $intRowVAT;
                $intTotSumVAT += $intRowSumVAT;
            }
        }
        $intPenalty = $intTotSumVAT * $penaltyInterest / 100 * $intDaysOverdue / 360;
        
        $strQuery = 'INSERT INTO {prefix}invoice_row (invoice_id, description, pcs, price, row_date, vat, vat_included, reminder_row) ' .
          'VALUES (?, ?, 1, ?, ?, 0, 0, 1)';
        mysql_param_query($strQuery, array($intInvoiceId, $GLOBALS['locPENALTYINTERESTDESC'], $intPenalty, date('Ymd')));
      }
    }
    else
    {
      $strAlert = $GLOBALS['locWRONGSTATEFORREMINDERFEED'];
    }
}

$strOnLoad = "window.location='form.php?ses=$strSesID&selectform=invoice&id=$intInvoiceId';";
if ($strAlert)
  $strOnLoad = "alert('$strAlert'); $strOnLoad";

echo htmlPageStart( _PAGE_TITLE_ );
?>

<body class="form" onload="<?php echo $strOnLoad?>">
</body>
</html>
