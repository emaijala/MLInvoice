<?php
/**
 * Account statement import
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
require_once 'import.php';

/**
 * Account statement import
 *
 * @category MLInvoice
 * @package  MLInvoice\Base
 * @author   Ere Maijala <ere@labs.fi>
 * @license  https://opensource.org/licenses/GPL-2.0 GNU Public License 2.0
 * @link     http://github.com/emaijala/MLInvoice
 */
class ImportStatement extends ImportFile
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();

        $this->tableName = 'account_statement';
        $this->allowServerFile = false;
        $this->duplicateControl = false;
        $this->dateFormat = true;
        $this->decimalSeparator = true;
        $this->ignoreEmptyRows = true;
        $this->requireDuplicateCheck = false;
        $this->mappingsForXml = true;
        $this->presets = [
            [
                'name' => 'Osuuspankki',
                'value' => 'Osuuspankki',
                'selections' => [
                    'charset' => 1,
                    'format' => 0,
                    'field_delim' => 1,
                    'enclosure_char' => 0,
                    'row_delim' => 0,
                    'date_format' => 0
                ],
                'mappings' => [
                    'map_column1' => 1,
                    'map_column2' => 2,
                    'map_column7' => 3
                ],
                'values' => [
                    'decimal_separator' => ',',
                    'skip_rows' => '0'
                ]
            ],
            [
                'name' => 'Nordea',
                'value' => 'Nordea',
                'selections' => [
                    'charset' => 0,
                    'format' => 0,
                    'field_delim' => 2,
                    'enclosure_char' => 2,
                    'row_delim' => 1,
                    'date_format' => 0
                ],
                'mappings' => [
                    'map_column1' => 1,
                    'map_column2' => 2,
                    'map_column8' => 3
                ],
                'values' => [
                    'decimal_separator' => ',',
                    'skip_rows' => '1'
                ]
            ],
            [
                'name' => 'Säästöpankki',
                'value' => 'Saastopankki',
                'selections' => [
                    'charset' => 1,
                    'format' => 0,
                    'field_delim' => 1,
                    'enclosure_char' => 0,
                    'row_delim' => 0,
                    'date_format' => 0
                ],
                'mappings' => [
                    'map_column0' => 1,
                    'map_column4' => 2,
                    'map_column3' => 3
                ],
                'values' => [
                    'decimal_separator' => ',',
                    'skip_rows' => '0'
                ],
            ],
            [

                'name' => 'KTL',
                'value' => 'KTL',
                'default_for' => 'fixed',
                'selections' => [
                    'charset' => 1,
                    'format' => 3,
                    'date_format' => 9
                ],
                'mappings' => [
                    'map_column3' => 1,
                    'map_column5' => 3,
                    'map_column9' => 2,
                    'map_column10' => 4
                ],
                'values' => [
                    'decimal_separator' => '',
                    'skip_rows' => '0'
                ]
            ],
            [
                'name' => 'camt.054.001.02',
                'value' => 'camt.054.001.02',
                'default_for' => 'xml',
                'selections' => [
                    'charset' => 1,
                    'format' => 3,
                    'date_format' => 4
                ],
                'mappings' => [
                    'map_column1' => 1,
                    'map_column2' => 2,
                    'map_column3' => 3
                ],
                'values' => [
                    'decimal_separator' => '.',
                    'skip_rows' => '0'
                ]
            ]
        ];

        $this->fixedWidthSettings = [
            ['name' => 'record_id', 'len' => 1, 'filter' => [3]],
            ['name' => 'account_nr', 'len' => 14],
            ['name' => 'entry_date', 'len' => 6],
            ['name' => 'date', 'len' => 6],
            ['name' => 'archival_id', 'len' => 16],
            ['name' => 'refnr', 'len' => 20],
            ['name' => 'payer', 'len' => 12],
            ['name' => 'currency', 'len' => 1, 'filter' => [1]],
            ['name' => 'name_source', 'len' => 1],
            ['name' => 'amount', 'len' => 10],
            ['name' => 'correction', 'len' => 1],
            ['name' => 'relaying_method', 'len' => 1],
            ['name' => 'response_code', 'len' => 1]
        ];
        $this->fixedWidthName = 'KTL';
    }

    /**
     * Get preview data for XML import
     *
     * @param SimpleXMLElement $xml      XML
     * @param array            $headings Resulting headings
     * @param array            $rows     Resulting rows
     * @param array            $errors   Any errors
     *
     * @return void
     */
    protected function getXmlPreviewData($xml, &$headings, &$rows, &$errors)
    {
        $headings = ['booking date', 'value date', 'amount', 'refnr'];
        $rows = [];
        $errors = [];
        $recNum = 0;
        if (!empty($xml->BkToCstmrDbtCdtNtfctn->Ntfctn)) {
            foreach ($xml->BkToCstmrDbtCdtNtfctn->Ntfctn as $notification) {
                foreach ($notification->Ntry as $entry) {
                    if (++$recNum > 10) {
                        break 2;
                    }
                    if ($entry->Sts != 'BOOK' || $entry->CdtDbtInd != 'CRDT') {
                        continue;
                    }
                    $transaction = $entry->NtryDtls->TxDtls;
                    $rows[] = [
                        (string)$entry->BookgDt->Dt,
                        (string)$entry->ValDt->Dt,
                        (string)$transaction->AmtDtls->TxAmt->Amt,
                        (string)$transaction->RmtInf->Strd->CdtrRefInf->Ref
                    ];
                }
                if (empty($rows)) {
                    $errors[] = "No entries with status 'BOOK' and type 'CRDT'"
                        . ' found';
                }
            }
        } else {
            $errors[] = 'No notifications found in file';
        }
    }

    /**
     * Import XML
     *
     * @param SimpleXMLElement $xml                   XML
     * @param string           $table                 Table name
     * @param array            $fieldDefs             Field definitions
     * @param array            $columnMappings        Column mappings
     * @param string           $duplicateMode         Duplicate handling mode
     *                                                ('ignore' or 'update')
     * @param array            $duplicateCheckColumns Columns to use for duplicate
     *                                                check
     * @param string           $importMode            Mode ('preview' or 'import')
     * @param string           $decimalSeparator      Decimal separator
     * @param array            $errors                Any errors
     *
     * @return void
     */
    protected function importXml($xml, $table, $fieldDefs, $columnMappings,
        $duplicateMode, $duplicateCheckColumns, $importMode, $decimalSeparator,
        &$errors
    ) {
        $errors = [];
        $recNum = 0;
        if (!empty($xml->BkToCstmrDbtCdtNtfctn->Ntfctn)) {
            foreach ($xml->BkToCstmrDbtCdtNtfctn->Ntfctn as $notification) {
                foreach ($notification->Ntry as $entry) {
                    if ($entry->Sts != 'BOOK' || $entry->CdtDbtInd != 'CRDT') {
                        continue;
                    }
                    ++$recNum;
                    $transaction = $entry->NtryDtls->TxDtls;
                    $row = [
                        (string)$entry->BookgDt->Dt,
                        (string)$entry->ValDt->Dt,
                        (string)$transaction->AmtDtls->TxAmt->Amt,
                        (string)$transaction->RmtInf->Strd->CdtrRefInf->Ref
                    ];

                    $mapped_row = [];
                    $haveMappings = false;
                    for ($i = 0; $i < count($row); $i++) {
                        if ($columnMappings[$i]) {
                            $haveMappings = true;
                            $mapped_row[$columnMappings[$i]] = $row[$i];
                        }
                    }
                    if (!$haveMappings) {
                        if (!$this->ignoreEmptyRows) {
                            echo "    Row $rowNum: " .
                                 Translator::translate('ImportNoMappedColumns') . "<br>\n";
                        }
                    } else {
                        $result = $this->processImportRow(
                            $table, $mapped_row, $duplicateMode,
                            $duplicateCheckColumns, $importMode, $decimalSeparator,
                            $fieldDefs[$table], $addedRecordId
                        );
                        if ($result) {
                            echo "    Record $recNum: $result<br>\n";
                            ob_flush();
                        }
                    }
                }
                if ($recNum == 0) {
                    $errors[] = "No entries with status 'BOOK' and type 'CRDT'"
                        . ' found';
                }
            }
        } else {
            $errors[] = 'No notifications found in file';
        }
    }

    /**
     * Add any custom fields to the form
     *
     * @return void
     */
    protected function addCustomFormFields()
    {
?>
      <div class="medium_label"><?php echo Translator::translate('ImportStatementMarkPaidInvoicesArchived')?></div>
      <div class="field">
        <input type="checkbox" id="archive" name="archive" value="1" <?php echo getSetting('invoice_auto_archive') ? 'checked="checked"' : '' ?>>
      </div>
      <div class="medium_label"><?php echo Translator::translate('Biller')?></div>
      <div class="field">
        <?php echo htmlSQLListBox('base_id', 'SELECT id, name FROM {prefix}base WHERE deleted=0', '', 'medium') ?>
      </div>
      <div class="medium_label"><?php echo Translator::translate('ImportStatementAcceptPartialPayments')?></div>
      <div class="field">
        <input type="checkbox" id="partial_payments" name="partial_payments" value="1">
      </div>
      <div class="medium_label"><?php echo Translator::translate('ImportStatementIgnorePaid')?></div>
      <div class="field">
        <input type="checkbox" id="ignore_paid" name="ignore_paid" value="1">
      </div>
<?php
    }

    /**
     * Get field definitions for a table
     *
     * @param string $table Table name
     *
     * @return array
     */
    protected function getFieldDefs($table)
    {
        return [
            'date' => true,
            'amount' => true,
            'refnr' => true,
            'correction' => true
        ];
    }

    /**
     * Check if the table name is valid
     *
     * @param string $table Table name
     *
     * @return bool
     */
    protected function isTableNameValid($table)
    {
        return $table == 'account_statement';
    }

    /**
     * Process a row to import
     *
     * @param string $table            Table name
     * @param array  $row              Row data
     * @param string $dupMode          Duplicate handling mode
     *                                 ('ignore' or 'update')
     * @param array  $dupCheckColumns  Columns to use for duplicate check
     * @param string $mode             Mode ('preview' or 'import')
     * @param string $decimalSeparator Decimal separator
     * @param array  $fieldDefs        Field definitions
     * @param int    $addedRecordId    ID of the added record
     *
     * @return string Result message
     */
    protected function processImportRow($table, $row, $dupMode, $dupCheckColumns,
        $mode, $decimalSeparator, $fieldDefs, &$addedRecordId
    ) {
        if (!isset($row['date']) || !isset($row['amount']) || !isset($row['refnr'])) {
            return Translator::translate('ImportStatementFieldMissing');
        }

        $refnr = str_replace([' ', "'"], '', $row['refnr']);
        $refnr = ltrim($refnr, '0');
        $date = date(
            'Ymd',
            DateTime::createFromFormat(
                getRequest('date_format', 'd.m.Y'), $row['date']
            )->getTimestamp()
        );
        $amount = trim($row['amount']);
        if (substr($amount, 0, 1) == '-') {
            return;
        }
        if (substr($amount, 0, 1) == '+') {
            $amount = substr($amount, 1);
        }

        if ($decimalSeparator == ' ' || $decimalSeparator == ',') {
            $amount = str_replace('.', '', $amount);
            $amount = str_replace($decimalSeparator, '.', $amount);
        } elseif ($decimalSeparator == '.') {
            $amount = str_replace(',', '', $amount);
        } elseif ($decimalSeparator == '') {
            $amount /= 100;
        }
        $amount = floatval($amount);

        if ($refnr === '') {
            return Translator::translate('ImportStatementFieldMissing');
        }

        $format = getRequest('format', '');
        if ($format == 'fixed' && isset($row['correction']) && $row['correction']) {
            $msg = str_replace(
                '{refnr}', $refnr, Translator::translate('ImportStatementNoCorrections')
            );
            $msg = str_replace(
                '{statementAmount}', miscRound2Decim($amount), $msg
            );
            return $msg;
        }

        $sql = 'SELECT i.*, ist.invoice_unpaid FROM {prefix}invoice i'
            . ' LEFT OUTER JOIN {prefix}invoice_state ist ON (i.state_id = ist.id)'
            . ' WHERE i.Deleted=0 AND REPLACE(i.ref_number, " ", "") = ?';
        $params = [$refnr];

        $baseId = getRequest('base_id', '');
        if ($baseId) {
            $sql .= ' AND i.base_id = ?';
            $params[] = $baseId;
        }

        $ignorePaid = getRequest('ignore_paid', '');
        if ($ignorePaid) {
            $sql .= ' AND ist.invoice_unpaid = 1';
        }

        $rows = dbParamQuery($sql, $params);
        $count = count($rows);
        if ($count == 0) {
            return str_replace(
                '{refnr}', $refnr, Translator::translate('ImportStatementInvoiceNotFound')
            );
        }
        if ($count > 1) {
            return str_replace(
                '{refnr}', $refnr,
                Translator::translate('ImportStatementMultipleInvoicesFound')
            );
        }

        $row = $rows[0];

        if (!$row['invoice_unpaid']) {
            return str_replace(
                '{refnr}', $refnr, Translator::translate('ImportStatementInvoiceAlreadyPaid')
            );
        }

        $rows2 = dbParamQuery(
            'SELECT ir.price, ir.pcs, ir.vat, ir.vat_included, ir.discount, ir.discount_amount, ir.partial_payment from {prefix}invoice_row ir where ir.deleted = 0 AND ir.invoice_id = ?',
            [
                $row['id']
            ]
        );
        $rowTotal = 0;
        $partialPayments = 0;
        foreach ($rows2 as $invoiceRow) {
            if ($invoiceRow['partial_payment']) {
                $partialPayments += $invoiceRow['price'];
            }
            list($rowSum, $rowVAT, $rowSumVAT) = calculateRowSum($invoiceRow);
            $rowTotal += $rowSumVAT;
        }

        $totalToPay = $rowTotal + $partialPayments;

        if (miscRound2Decim($totalToPay) != miscRound2Decim($amount)) {
            if (getRequest('partial_payments', false)
                && miscRound2Decim($totalToPay) > miscRound2Decim($amount)
            ) {
                if ($mode == 'import') {
                    $sql = <<<EOT
INSERT INTO {prefix}invoice_row
    (invoice_id, description, pcs, price, row_date, order_no, partial_payment)
    VALUES (?, ?, 0, ?, ?, 100000, 1)
EOT;

                    dbParamQuery(
                        $sql,
                        [
                            $row['id'],
                            Translator::translate('PartialPayment'),
                            -$amount,
                            $date
                        ]
                    );
                }

                $msg = str_replace(
                    '{statementAmount}', miscRound2Decim($amount),
                    Translator::translate(
                        $mode == 'import' ? 'ImportStatementPartialPayment'
                            : 'ImportStatementPartialPaymentSimulation'
                    )
                );
                $msg = str_replace(
                    '{invoiceAmount}', miscRound2Decim($totalToPay), $msg
                );
                $msg = str_replace('{id}', $row['id'], $msg);
                $msg = str_replace('{date}', dateConvDBDate2Date($date), $msg);
                $msg = str_replace('{refnr}', $refnr, $msg);

                return $msg;
            } else {
                $msg = str_replace(
                    '{statementAmount}', miscRound2Decim($amount),
                    Translator::translate('ImportStatementAmountMismatch')
                );
                $msg = str_replace(
                    '{invoiceAmount}', miscRound2Decim($totalToPay), $msg
                );
                $msg = str_replace('{refnr}', $refnr, $msg);
                return $msg;
            }
        }

        $archive = $row['interval_type'] == 0 && getRequest('archive', '');

        if ($mode == 'import') {
            $sql = 'UPDATE {prefix}invoice SET state_id=3, payment_date=?';
            if ($archive) {
                $sql .= ', archived=1';
            }
            $sql .= ' WHERE id = ?';
            dbParamQuery($sql, [$date, $row['id']]);
        }
        $msgId = $archive
            ? 'ImportStatementInvoiceMarkedAsPaidAndArchived'
            : 'ImportStatementInvoiceMarkedAsPaid';
        if ('import' !== $mode) {
            $msgId .= 'Simulation';
        }
        $msg = str_replace(
            '{amount}', miscRound2Decim($amount), Translator::translate($msgId)
        );
        $msg = str_replace('{id}', $row['id'], $msg);
        $msg = str_replace('{date}', dateConvDBDate2Date($date), $msg);
        $msg = str_replace('{refnr}', $refnr, $msg);
        return $msg;
    }
}
