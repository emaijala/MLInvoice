<?php
/**
 * Add reminder fees
 *
 * PHP version 5
 *
 * Copyright (C) 2010-2018 Ere Maijala
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category MLInvoice
 * @package  MLInvoice\Base
 * @author   Ere Maijala <ere@labs.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://labs.fi/mlinvoice.eng.php
 */
require_once 'translator.php';
require_once 'datefuncs.php';
require_once 'miscfuncs.php';

/**
 * Add reminder fees
 *
 * @param int $intInvoiceId Invoice ID
 *
 * @return string Any error messages
 */
function addReminderFees($intInvoiceId)
{
    $strAlert = '';
    $strQuery = 'SELECT inv.due_date, inv.state_id, inv.print_date ' .
         'FROM {prefix}invoice inv ' . 'WHERE inv.id = ?';
    $rows = dbParamQuery($strQuery, [$intInvoiceId]);
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
        if ($intStateId == 1 || $intStateId == 2) {
            $intStateId = 5;
        } elseif ($intStateId == 5) {
            $intStateId = 6;
        }
        dbParamQuery(
            'UPDATE {prefix}invoice SET state_id=? where id=?',
            [
                $intStateId,
                $intInvoiceId
            ]
        );

        // Add reminder fee
        if (getSetting('invoice_notification_fee')) {
            // Remove old fee from same day
            dbParamQuery(
                'UPDATE {prefix}invoice_row SET deleted=1 WHERE invoice_id=? AND reminder_row=2 AND row_date = ?',
                [
                    $intInvoiceId,
                    date('Ymd')
                ]
            );

            $strQuery = 'INSERT INTO {prefix}invoice_row (invoice_id, description, pcs, price, row_date, vat, vat_included, order_no, reminder_row) ' .
                 'VALUES (?, ?, 1, ?, ?, 0, 0, -2, 2)';
            dbParamQuery(
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
            dbParamQuery(
                'UPDATE {prefix}invoice_row SET deleted=1 WHERE invoice_id=? AND reminder_row=1',
                [$intInvoiceId]
            );

            // Add new interest
            $intTotSumVAT = 0;
            $strQuery = 'SELECT ir.pcs, ir.price, ir.discount, ir.discount_amount, ir.vat, ir.vat_included, ir.reminder_row ' .
                 'FROM {prefix}invoice_row ir ' .
                 'WHERE ir.deleted=0 AND ir.invoice_id=?';
            $rows = dbParamQuery($strQuery, [$intInvoiceId]);
            foreach ($rows as $row) {
                if ($row['reminder_row']) {
                    continue;
                }
                list($rowSum, $rowVAT, $rowSumVAT) = calculateRowSum($row);
                $intTotSumVAT += $rowSumVAT;
            }
            $intPenalty = $intTotSumVAT * $penaltyInterest / 100 * $intDaysOverdue /
                 360;

            $strQuery = 'INSERT INTO {prefix}invoice_row (invoice_id, description, pcs, price, discount, discount_amount, row_date, vat, vat_included, order_no, reminder_row) ' .
                 'VALUES (?, ?, 1, ?, 0, 0, ?, 0, 0, -1, 1)';
            dbParamQuery(
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
