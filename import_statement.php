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
require_once 'import.php';

class ImportStatement extends ImportFile
{

    public function __construct()
    {
        parent::__construct();

        $this->tableName = 'account_statement';
        $this->allowServerFile = false;
        $this->duplicateControl = false;
        $this->dateFormat = true;
        $this->decimalSeparator = true;
        $this->ignoreEmptyRows = true;
        $this->presets = [
            [
                'name' => 'Osuuspankki',
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
            ]
        ];
    }

    protected function add_custom_form_fields()
    {
?>
      <div class="medium_label"><?php echo $GLOBALS['locMarkPaidInvoicesArchived']?></div>
      <div class="field">
        <input type="checkbox" id="archive" name="archive" value="1" <?=getSetting('invoice_auto_archive') ? 'checked="checked"' : '' ?>>
      </div>
      <div class="medium_label"><?php echo $GLOBALS['locBiller']?></div>
      <div class="field">
        <?=htmlSQLListBox('base_id', 'SELECT id, name FROM {prefix}base WHERE deleted=0', '', 'medium') ?>
      </div>
<?php
    }

    protected function get_field_defs($table)
    {
        return [
            'date' => true,
            'amount' => true,
            'refnr' => true
        ];
    }

    protected function table_valid($table)
    {
        return $table == 'account_statement';
    }

    protected function process_import_row($table, $row, $dupMode, $dupCheckColumns,
        $mode, &$addedRecordId)
    {
        if (!isset($row['date']) || !isset($row['amount']) || !isset($row['refnr'])) {
            return $GLOBALS['locImportStatementFieldMissing'];
        }

        $refnr = str_replace(' ', '', $row['refnr']);
        $refnr = ltrim($refnr, '0');
        $date = date('Ymd',
            DateTime::createFromFormat(getRequest('date_format', 'd.m.Y'),
                $row['date'])->getTimestamp());
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
        }
        $amount = floatval($amount);

        if ($row['refnr'] === '') {
            return $GLOBALS['locImportStatementFieldMissing'];
        }

        $sql = 'SELECT i.* FROM {prefix}invoice i' .
            ' WHERE i.Deleted=0 AND REPLACE(i.ref_number, " ", "") = ?';
        $params = [$refnr];

        $baseId = getRequest('base_id', '');
        if ($baseId) {
            $sql .= ' AND i.base_id = ?';
            $params[] = $baseId;
        }

        $intRes = mysqli_param_query($sql, $params);
        $count = mysqli_num_rows($intRes);
        if ($count == 0) {
            return str_replace('{refnr}', $refnr,
                $GLOBALS['locImportStatementInvoiceNotFound']);
        }
        if ($count > 1) {
            return str_replace('{refnr}', $refnr,
                $GLOBALS['locImportStatementMultipleInvoicesFound']);
        }

        $row = mysqli_fetch_assoc($intRes);

        if ($row['state_id'] == 3) {
            return str_replace('{refnr}', $refnr,
                $GLOBALS['locImportStatementInvoiceAlreadyPaid']);
        }

        $res2 = mysqli_param_query(
            'SELECT ir.price, ir.pcs, ir.vat, ir.vat_included, ir.discount from {prefix}invoice_row ir where ir.deleted = 0 AND ir.invoice_id = ?',
            [
                $row['id']
            ]);
        $rowTotal = 0;
        while ($invoiceRow = mysqli_fetch_assoc($res2)) {
            list ($rowSum, $rowVAT, $rowSumVAT) = calculateRowSum(
                $invoiceRow['price'], $invoiceRow['pcs'], $invoiceRow['vat'],
                $invoiceRow['vat_included'], $invoiceRow['discount']);
            $rowTotal += $rowSumVAT;
        }

        if (miscRound2Decim($rowTotal) != miscRound2Decim($amount)) {
            $msg = str_replace('{statementAmount}', miscRound2Decim($amount),
                $GLOBALS['locImportStatementAmountMismatch']);
            $msg = str_replace('{invoiceAmount}', miscRound2Decim($rowTotal), $msg);
            $msg = str_replace('{refnr}', $refnr, $msg);
            return $msg;
        }

        $archive = $row['interval_type'] == 0 && getRequest('archive', '');

        if ($mode == 'import') {
            $sql = 'UPDATE {prefix}invoice SET state_id=3, payment_date=?';
            if ($archive) {
                $sql .= ', archived=1';
            }
            $sql .= ' WHERE id = ?';
            mysqli_param_query($sql,
                [
                    $date,
                    $row['id']
                ]);
        }
        $msg = str_replace('{amount}', miscRound2Decim($amount),
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