<?php
/**
 * Accounting report
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
 * @package  MLInvoice\Reports
 * @author   Ere Maijala <ere@labs.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://labs.fi/mlinvoice.eng.php
 */
require_once 'invoice_report.php';

/**
 * Accounting report
 *
 * @category MLInvoice
 * @package  MLInvoice\Reports
 * @author   Ere Maijala <ere@labs.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://labs.fi/mlinvoice.eng.php
 */
class AccountingReport extends InvoiceReport
{
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->reportName = 'AccountingReport';
        $this->description = Translator::translate('AccountingReportDescription');
    }

    /**
     * Add limit selection to the form
     *
     * @return void
     */
    protected function addLimitSelection()
    {
        $intBaseId = getRequest('base', false);
        $intCompanyId = getRequest('company', false);
        $dateRange = getRequest('date', '');
?>
            <div class="medium_label">
                <?php echo Translator::translate('DateInterval')?>
            </div>
            <div class="field">
                <?php echo htmlFormElement('accounting_date', 'TEXT', $dateRange, 'medium hasDateRangePicker', '', 'MODIFY', false)?>
            </div>

            <div class="medium_label">
                <?php echo Translator::translate('Biller')?>
            </div>
            <div class="field">
                <?php echo htmlFormElement('base', 'LIST', $intBaseId, 'medium', 'SELECT id, name FROM {prefix}base WHERE deleted=0 ORDER BY name', 'MODIFY', false)?>
            </div>

            <div class="medium_label">
                <?php echo Translator::translate('Client')?>
            </div>
            <div class="field">
                <?php echo htmlFormElement('company', 'LIST', $intCompanyId, 'medium', 'SELECT id, company_name FROM {prefix}company WHERE deleted=0 ORDER BY company_name', 'MODIFY', false)?>
            </div>
<?php
    }

    /**
     * Add invoice state selection to the form
     *
     * @return void
     */
    protected function addInvoiceStateSelection()
    {
    }

    /**
     * Create the limit query
     *
     * @return array
     */
    protected function createLimitQuery()
    {
        list($query, $params) = parent::createLimitQuery();

        $dateRange = explode(' - ', getRequest('accounting_date', ''));
        $startDate = $dateRange[0];
        $endDate = isset($dateRange[1]) ? $dateRange[1] : $startDate;
        if ($startDate) {
            $query .= ' AND i.invoice_date >= ?';
            $params[] = dateConvDate2DBDate($startDate);
        }
        if ($endDate) {
            $query .= ' AND i.invoice_date <= ?';
            $params[] = dateConvDate2DBDate($endDate);
        }
        $rows = dbParamQuery(
            'SELECT id FROM {prefix}invoice_state WHERE invoice_open=0'
            . ' AND invoice_unpaid=1 AND invoice_offer=0'
        );
        $unpaidStates = [];
        foreach ($rows as $row) {
            $unpaidStates[] = $row['id'];
        }

        // Include invoices that are unpaid or have been paid after the date range
        $orQueries = [];
        if ($unpaidStates) {
            $orQueries[] = 'i.state_id IN (' . implode(',', $unpaidStates)
                . ')';
        }
        if ($endDate) {
            $orQueries[] = 'i.payment_date > ?';
            $params[] = dateConvDate2DBDate($endDate);
        }
        if ($orQueries) {
            $query .= ' AND (' . implode(' OR ', $orQueries) . ')';
        }

        return [$query, $params];
    }
}
