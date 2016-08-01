<?php
/*******************************************************************************
 MLInvoice: web-based invoicing application.
 Copyright (C) 2010-2016 Ere Maijala

 This program is free software. See attached LICENSE.

 *******************************************************************************/

/*******************************************************************************
 MLInvoice: web-pohjainen laskutusohjelma.
 Copyright (C) 2010-2015 Ere Maijala

 Tämä ohjelma on vapaa. Lue oheinen LICENSE.

 *******************************************************************************/
require_once 'import.php';

/**
 * Account statement import
 *
 * @category MLInvoice
 * @package  MLInvoice\Import
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

    protected function get_xml_preview_data($xml, &$headings, &$rows, &$errors)
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

    protected function import_xml($xml, $table, $field_defs, $columnMappings,
        $duplicateMode, $duplicateCheckColumns, $importMode, &$errors
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
                                 $GLOBALS['locImportNoMappedColumns'] . "<br>\n";
                        }
                    } else {
                        $result = $this->process_import_row(
                            $table, $mapped_row, $duplicateMode,
                            $duplicateCheckColumns, $importMode, $addedRecordId
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

    protected function add_custom_form_fields()
    {
?>
      <div class="medium_label"><?php echo $GLOBALS['locImportStatementMarkPaidInvoicesArchived']?></div>
      <div class="field">
        <input type="checkbox" id="archive" name="archive" value="1" <?=getSetting('invoice_auto_archive') ? 'checked="checked"' : '' ?>>
      </div>
      <div class="medium_label"><?php echo $GLOBALS['locBiller']?></div>
      <div class="field">
        <?=htmlSQLListBox('base_id', 'SELECT id, name FROM {prefix}base WHERE deleted=0', '', 'medium') ?>
      </div>
      <div class="medium_label"><?php echo $GLOBALS['locImportStatementAcceptPartialPayments']?></div>
      <div class="field">
        <input type="checkbox" id="partial_payments" name="partial_payments" value="1">
      </div>
<?php
    }

    protected function get_field_defs($table)
    {
        return [
            'date' => true,
            'amount' => true,
            'refnr' => true,
            'correction' => true
        ];
    }

    protected function table_valid($table)
    {
        return $table == 'account_statement';
    }

    protected function process_import_row($table, $row, $dupMode, $dupCheckColumns,
        $mode, &$addedRecordId
    ) {
        if (!isset($row['date']) || !isset($row['amount']) || !isset($row['refnr'])) {
            return $GLOBALS['locImportStatementFieldMissing'];
        }

        $refnr = str_replace(' ', '', $row['refnr']);
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

        $sep = getRequest('decimal_separator', ',');
        if ($sep == ' ' || $sep == ',') {
            $amount = str_replace('.', '', $amount);
            $amount = str_replace($sep, '.', $amount);
        } elseif ($sep == '.') {
            $amount = str_replace(',', '', $amount);
        } elseif ($sep == '') {
            $amount /= 100;
        }
        $amount = floatval($amount);

        if ($refnr === '') {
            return $GLOBALS['locImportStatementFieldMissing'];
        }

        $format = getRequest('format', '');
        if ($format == 'fixed' && isset($row['correction']) && $row['correction']) {
            $msg = str_replace(
                '{refnr}', $refnr, $GLOBALS['locImportStatementNoCorrections']
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

        $intRes = mysqli_param_query($sql, $params);
        $count = mysqli_num_rows($intRes);
        if ($count == 0) {
            return str_replace(
                '{refnr}', $refnr, $GLOBALS['locImportStatementInvoiceNotFound']
            );
        }
        if ($count > 1) {
            return str_replace(
                '{refnr}', $refnr,
                $GLOBALS['locImportStatementMultipleInvoicesFound']
            );
        }

        $row = mysqli_fetch_assoc($intRes);

        if (!$row['invoice_unpaid']) {
            return str_replace(
                '{refnr}', $refnr, $GLOBALS['locImportStatementInvoiceAlreadyPaid']
            );
        }

        $res2 = mysqli_param_query(
            'SELECT ir.price, ir.pcs, ir.vat, ir.vat_included, ir.discount, ir.partial_payment from {prefix}invoice_row ir where ir.deleted = 0 AND ir.invoice_id = ?',
            [
                $row['id']
            ]
        );
        $rowTotal = 0;
        $partialPayments = 0;
        while ($invoiceRow = mysqli_fetch_assoc($res2)) {
            if ($invoiceRow['partial_payment']) {
                $partialPayments += $invoiceRow['price'];
            }
            list ($rowSum, $rowVAT, $rowSumVAT) = calculateRowSum(
                $invoiceRow['price'], $invoiceRow['pcs'], $invoiceRow['vat'],
                $invoiceRow['vat_included'], $invoiceRow['discount']
            );
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

                    mysqli_param_query(
                        $sql,
                        [
                            $row['id'],
                            $GLOBALS['locPartialPayment'],
                            -$amount,
                            $date
                        ]
                    );
                }

                $msg = str_replace(
                    '{statementAmount}', miscRound2Decim($amount),
                    $GLOBALS['locImportStatementPartialPayment']
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
                    $GLOBALS['locImportStatementAmountMismatch']
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
            mysqli_param_query(
                $sql,
                [
                    $date,
                    $row['id']
                ]
            );
        }
        $msg = str_replace(
            '{amount}', miscRound2Decim($amount),
            $archive
                ? $GLOBALS['locImportStatementInvoiceMarkedAsPaidAndArchived']
                : $GLOBALS['locImportStatementInvoiceMarkedAsPaid']
        );
        $msg = str_replace('{id}', $row['id'], $msg);
        $msg = str_replace('{date}', dateConvDBDate2Date($date), $msg);
        $msg = str_replace('{refnr}', $refnr, $msg);
        return $msg;
    }
}