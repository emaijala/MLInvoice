<?php
/*******************************************************************************
 MLInvoice: web-based invoicing application.
 Copyright (C) 2010-2017 Ere Maijala

 This program is free software. See attached LICENSE.

 *******************************************************************************/

/*******************************************************************************
 MLInvoice: web-pohjainen laskutusohjelma.
 Copyright (C) 2010-2017 Ere Maijala

 Tämä ohjelma on vapaa. Lue oheinen LICENSE.

 *******************************************************************************/
require_once 'translator.php';
require_once 'datefuncs.php';
require_once 'miscfuncs.php';

function addReminderFees($intInvoiceId)
{
    $strAlert = '';
    $strQuery = 'SELECT inv.due_date, inv.state_id, inv.print_date ' .
         'FROM {prefix}invoice inv ' . 'WHERE inv.id = ?';
    $rows = db_param_query($strQuery, [$intInvoiceId]);
    if ($rows) {
        $intStateId = $rows[0]['state_id'];
        $strDueDate = dateConvDBDate2Date($rows[0]['due_date']);
        $strPrintDate = $rows[0]['print_date'];
    } else {
        return Translator::translate('RecordNotFound');
    }

    $intDaysOverdue = floor((time() - strtotime($strDueDate)) / 60 / 60 / 24);
    if ($intDaysOverdue <= 0) {
        $strAlert = addslashes(Translator::translate('InvoiceNotOverdue'));
    } elseif ($intStateId == 3 || $intStateId == 4) {
        $strAlert = addslashes(Translator::translate('WrongStateForReminderFee'));
    } else {
        // Update invoice state
        if ($intStateId == 1 || $intStateId == 2)
            $intStateId = 5;
        elseif ($intStateId == 5)
            $intStateId = 6;
        db_param_query(
            'UPDATE {prefix}invoice SET state_id=? where id=?',
            [
                $intStateId,
                $intInvoiceId
            ]
        );

        // Add reminder fee
        if (getSetting('invoice_notification_fee')) {
            // Remove old fee from same day
            db_param_query(
                'UPDATE {prefix}invoice_row SET deleted=1 WHERE invoice_id=? AND reminder_row=2 AND row_date = ?',
                [
                    $intInvoiceId,
                    date('Ymd')
                ]
            );

            $strQuery = 'INSERT INTO {prefix}invoice_row (invoice_id, description, pcs, price, row_date, vat, vat_included, order_no, reminder_row) ' .
                 'VALUES (?, ?, 1, ?, ?, 0, 0, -2, 2)';
            db_param_query(
                $strQuery,
                [
                    $intInvoiceId,
                    Translator::translate('ReminderFeeDesc'),
                    getSetting('invoice_notification_fee'),
                    date('Ymd')
                ]
            );
        }
        // Add penalty interest
        $penaltyInterest = getSetting('invoice_penalty_interest');
        if ($penaltyInterest) {
            // Remove old penalty interest
            db_param_query(
                'UPDATE {prefix}invoice_row SET deleted=1 WHERE invoice_id=? AND reminder_row=1',
                [$intInvoiceId]
            );

            // Add new interest
            $intTotSumVAT = 0;
            $strQuery = 'SELECT ir.pcs, ir.price, ir.discount, ir.discount_amount, ir.vat, ir.vat_included, ir.reminder_row ' .
                 'FROM {prefix}invoice_row ir ' .
                 'WHERE ir.deleted=0 AND ir.invoice_id=?';
            $rows = db_param_query($strQuery, [$intInvoiceId]);
            foreach ($rows as $row) {
                if ($row['reminder_row']) {
                    continue;
                }
                list ($rowSum, $rowVAT, $rowSumVAT) = calculateRowSum($row);
                $intTotSumVAT += $rowSumVAT;
            }
            $intPenalty = $intTotSumVAT * $penaltyInterest / 100 * $intDaysOverdue /
                 360;

            $strQuery = 'INSERT INTO {prefix}invoice_row (invoice_id, description, pcs, price, discount, discount_amount, row_date, vat, vat_included, order_no, reminder_row) ' .
                 'VALUES (?, ?, 1, ?, 0, 0, ?, 0, 0, -1, 1)';
            db_param_query(
                $strQuery,
                [
                    $intInvoiceId,
                    Translator::translate('PenaltyInterestDesc'),
                    $intPenalty,
                    date('Ymd')
                ]
            );
        }
    }
    return $strAlert;
}
