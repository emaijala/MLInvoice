<?php
/**
 * Reports base class
 *
 * PHP version 7
 *
 * Copyright (C) Ere Maijala 2010-2021
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
 * @package  MLInvoice\Reports
 * @author   Ere Maijala <ere@labs.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://labs.fi/mlinvoice.eng.php
 */
require_once 'sqlfuncs.php';
require_once 'translator.php';
require_once 'pdf.php';

/**
 * Reports base class
 *
 * @category MLInvoice
 * @package  MLInvoice\Reports
 * @author   Ere Maijala <ere@labs.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://labs.fi/mlinvoice.eng.php
 */
abstract class AbstractReport
{
    protected $pdf = null;

    /**
     * Create the report form
     *
     * @return void
     */
    abstract public function createReport();

    /**
     * Create a limit query
     *
     * @return array
     */
    protected function createLimitQuery()
    {
        $strQuery = '';
        $arrParams = [];

        $intBaseId = getPostOrQuery('base', false);
        if ($intBaseId) {
            $strQuery .= ' AND i.base_id = ?';
            $arrParams[] = $intBaseId;
        }
        $intCompanyId = getPostOrQuery('company', false);
        if ($intCompanyId) {
            $strQuery .= ' AND i.company_id = ?';
            $arrParams[] = $intCompanyId;
        }

        $dateRange = explode(' - ', getPostOrQuery('date', ''));
        $startDate = $dateRange[0];
        $endDate = isset($dateRange[1]) ? $dateRange[1] : $startDate;
        if ($startDate) {
            $strQuery .= ' AND i.invoice_date >= ?';
            $arrParams[] = dateConvDate2DBDate($startDate);
        }
        if ($endDate) {
            $strQuery .= ' AND i.invoice_date <= ?';
            $arrParams[] = dateConvDate2DBDate($endDate);
        }

        if ($tags = getPostOrQuery('tags')) {
            $tagsQuery = [];
            foreach (explode(',', $tags) as $tag) {
                $tag = trim($tag);
                $tagsQuery[] = <<<EOT
c.id IN (
    SELECT ctl.company_id FROM {prefix}company_tag_link ctl WHERE ctl.tag_id=(
        SELECT ct.id FROM {prefix}company_tag ct WHERE ct.tag=?
    )
)
EOT;
                $arrParams[] = $tag;
            }
            $strQuery .= ' AND (' . implode(' OR ', $tagsQuery) . ')';
        }

        return [$strQuery, $arrParams];
    }

    /**
     * Get a string with the selected parameters
     *
     * @param bool $html Whether to return the parameters as HTML
     *
     * @return string
     */
    protected function getParamsStr($html)
    {
        $mappings = [
            'date' => ['name' => 'InvoiceDateInterval'],
            'accounting_date' => ['name' => 'DateInterval'],
            'row_date' => ['name' => 'InvoiceRowDateInterval'],
            'payment_date' => ['name' => 'PaymentDateInterval'],
            'tags' => ['name' => 'Tags'],
            'base' => [
                'name' => 'Biller',
                'sql' => 'SELECT name as v FROM {prefix}base WHERE id = ?'
            ],
            'company' => [
                'name' => 'Client',
                'sql' => 'SELECT company_name as v FROM {prefix}company WHERE id = ?'
            ],
            'product' => [
                'name' => 'Product',
                'sql' => 'SELECT product_name as v FROM {prefix}product WHERE id = ?'
            ],
            'row_types' => [
                'name' => 'InvoiceRowTypes',
                'values' => [
                    'all' => 'PrintInvoiceRowTypeAll',
                    'normal' => 'PrintInvoiceRowTypeNormal',
                    'reminder' => 'PrintInvoiceRowTypeReminder'
                ]
            ],
            'grouping' => [
                'name' => 'PrintGrouping',
                'values' => [
                    'state' => 'PrintGroupingState',
                    'month' => 'PrintGroupingMonth',
                    'client' => 'PrintGroupingClient',
                    'vat' => 'PrintGroupingVAT',
                    'product' => 'PrintGroupingProduct'
                ]
            ],
        ];
        $params = [];
        foreach (array_merge($_GET, $_POST) as $key => $value) {
            if (empty($value)
                || in_array($key, ['func', 'form', 'report', 'format', 'fields'])
                || strncmp($key, 'stateid_', 8) == 0
            ) {
                continue;
            }

            if (!isset($mappings[$key])) {
                $params[] = "$key: $value";
                continue;
            }
            $mapping = $mappings[$key];
            $param = Translator::translate($mapping['name']) . ': ';
            if (isset($mapping['values'])) {
                $param .= isset($mapping['values'][$value])
                    ? Translator::translate($mapping['values'][$value]) : $value;
            } elseif (isset($mapping['sql'])) {
                $rows = dbParamQuery($mapping['sql'], [$value]);
                $param .= $rows ? $rows[0]['v'] : '';
            } else {
                $param .= $value;
            }
            $params[] = $param;
        }

        $res = dbQueryCheck(
            'SELECT id, name FROM {prefix}invoice_state WHERE deleted=0'
            . ' ORDER BY order_no'
        );
        $states = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $stateId = $row['id'];
            if (getPostOrQuery("stateid_$stateId", false)) {
                $states[] = Translator::translate($row['name']);
            }
        }

        if ($states) {
            $params[] = Translator::translate('PrintReportStates') . ': '
                . implode(', ', $states);
        }

        return implode($html ? '<br/>' : "\n", $params);
    }

    /**
     * Add invoice states to the form
     *
     * @return void
     */
    protected function addInvoiceStateSelection()
    {
        ?>
        <div class="invoice-states">
            <div class="medium_label"><?php echo Translator::translate('PrintReportStates')?></div>
        <?php
        $strQuery = 'SELECT id, name, invoice_offer FROM {prefix}invoice_state WHERE deleted=0'
             . ' ORDER BY order_no';
        $intRes = dbQueryCheck($strQuery);
        $first = true;
        while ($row = mysqli_fetch_assoc($intRes)) {
            $intStateId = $row['id'];
            $strStateName = Translator::translate($row['name']);
            $strChecked = getPostOrQuery("stateid_$intStateId", $row['invoice_offer'] ? false : true) ? ' checked' : '';
            if (!$first) {
                echo "      <div class=\"medium_label\"></div>\n";
            }
            $first = false;
            ?>
            <div class="field">
                <label>
                    <input type="checkbox" id="state-<?php echo $intStateId?>" name="stateid_<?php echo $intStateId?>" value="1"<?php echo $strChecked?>>
                    <?php echo htmlspecialchars($strStateName)?>
                </label>
            </div>
            <?php
        }
        ?>
        </div>
        <?php
    }
}
