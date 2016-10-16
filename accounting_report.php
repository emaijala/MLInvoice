<?php
/*******************************************************************************
 MLInvoice: web-based invoicing application.
 Copyright (C) 2010-2016 Ere Maijala

 Portions based on:
 PkLasku : web-based invoicing software.
 Copyright (C) 2004-2008 Samu Reinikainen

 This program is free software. See attached LICENSE.

 *******************************************************************************/

/*******************************************************************************
 MLInvoice: web-pohjainen laskutusohjelma.
 Copyright (C) 2010-2016 Ere Maijala

 Perustuu osittain sovellukseen:
 PkLasku : web-pohjainen laskutusohjelmisto.
 Copyright (C) 2004-2008 Samu Reinikainen

 Tämä ohjelma on vapaa. Lue oheinen LICENSE.

 *******************************************************************************/
require_once 'invoice_report.php';

class AccountingReport extends InvoiceReport
{
    public function __construct()
    {
        $this->reportName = 'locAccountingReport';
    }

    protected function addLimitSelection()
    {
        $intBaseId = getRequest('base', false);
        $intCompanyId = getRequest('company', false);
        $dateRange = getRequest('date', '');
?>
            <div class="medium_label"><?php echo $GLOBALS['locDateInterval']?></div>
            <div class="field"><?php echo htmlFormElement('accounting_date', 'TEXT', $dateRange, 'medium hasDateRangePicker', '', 'MODIFY', false)?></div>

            <div class="medium_label"><?php echo $GLOBALS['locBiller']?></div>
            <div class="field"><?php echo htmlFormElement('base', 'LIST', $intBaseId, 'medium', 'SELECT id, name FROM {prefix}base WHERE deleted=0 ORDER BY name', 'MODIFY', false)?></div>

            <div class="medium_label"><?php echo $GLOBALS['locClient']?></div>
            <div class="field"><?php echo htmlFormElement('company', 'LIST', $intCompanyId, 'medium', 'SELECT id, company_name FROM {prefix}company WHERE deleted=0 ORDER BY company_name', 'MODIFY', false)?></div>
<?php
    }

    protected function addInvoiceStateSelection()
    {
    }

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
        $res = mysqli_param_query(
            'SELECT id FROM {prefix}invoice_state WHERE invoice_open=0'
            . ' AND invoice_unpaid=1 AND invoice_offer=0'
        );
        $unpaidStates = [];
        while ($row = mysqli_fetch_assoc($res)) {
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
