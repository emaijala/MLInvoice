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
require_once 'sqlfuncs.php';
require_once 'localize.php';
require_once 'pdf.php';

abstract class AbstractReport
{
    protected $pdf = null;

    public abstract function createReport();

    protected function getParamsStr($html)
    {
        $mappings = [
            'date' => ['name' => 'locInvoiceDateInterval'],
            'accounting_date' => ['name' => 'locDateInterval'],
            'row_date' => ['name' => 'locInvoiceRowDateInterval'],
            'payment_date' => ['name' => 'locPaymentDateInterval'],
            'base' => [
                'name' => 'locBiller',
                'sql' => 'SELECT name FROM {prefix}base WHERE id = ?'
            ],
            'company' => [
                'name' => 'locClient',
                'sql' => 'SELECT company_name FROM {prefix}company WHERE id = ?'
            ],
            'product' => [
                'name' => 'locProduct',
                'sql' => 'SELECT product_name FROM {prefix}product WHERE id = ?'
            ],
            'row_types' => [
                'name' => 'locInvoiceRowTypes',
                'values' => [
                    'all' => 'locPrintInvoiceRowTypeAll',
                    'normal' => 'locPrintInvoiceRowTypeNormal',
                    'reminder' => 'locPrintInvoiceRowTypeReminder'
                ]
            ],
            'grouping' => [
                'name' => 'locPrintGrouping',
                'values' => [
                    'state' => 'locPrintGroupingState',
                    'month' =>'locPrintGroupingMonth',
                    'client' =>'locPrintGroupingCliet',
                    'vat' => 'locPrintGroupingVAT'
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
            $param = $GLOBALS[$mapping['name']] . ': ';
            if (isset($mapping['values'])) {
                $param .= isset($mapping['values'][$value])
                    ? $GLOBALS[$mapping['values'][$value]] : $value;
            } elseif (isset($mapping['sql'])) {
                $res = mysqli_param_query($mapping['sql'], [$value]);
                if ($res) {
                    $param .= mysqli_fetch_value($res);
                } else {
                    $param .= $res;
                }
            } else {
                $param .= $value;
            }
            $params[] = $param;
        }

        $res = mysqli_query_check(
            'SELECT id, name FROM {prefix}invoice_state WHERE deleted=0'
            . ' ORDER BY order_no'
        );
        $states = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $stateId = $row['id'];
            if (getRequest("stateid_$stateId", false)) {
                $states[] = isset($GLOBALS['loc' . $row['name']])
                    ? $GLOBALS['loc' . $row['name']] : $row['name'];
            }
        }

        if ($states) {
            $params[] = $GLOBALS['locPrintReportStates'] . ': '
                . implode(', ', $states);
        }

        return implode($html ? '<br/>' : "\n", $params);
    }
}
