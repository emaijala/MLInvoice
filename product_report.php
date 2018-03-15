<?php
/**
 * Product report
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
require_once 'htmlfuncs.php';
require_once 'sqlfuncs.php';
require_once 'miscfuncs.php';
require_once 'datefuncs.php';
require_once 'translator.php';
require_once 'pdf.php';
require_once 'abstract_report.php';

/**
 * Product report
 *
 * @category MLInvoice
 * @package  MLInvoice\Reports
 * @author   Ere Maijala <ere@labs.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://labs.fi/mlinvoice.eng.php
 */
class ProductReport extends AbstractReport
{
    /**
     * Create the report form
     *
     * @return void
     */
    public function createReport()
    {
        $strReport = getRequest('report', '');

        if ($strReport) {
            $this->printReport();
            return;
        }

        $intBaseId = getRequest('base', false);
        $intCompanyId = getRequest('company', false);
        $intProductId = getRequest('product', false);
        $dateRange = getRequest('date', '');
        ?>

<script type="text/javascript">
  $(document).ready(function() {
    $('input[class~="hasDateRangePicker"]')
        .daterangepicker(<?php echo Translator::translate('DateRangePickerOptions')?>);
  });
  </script>

<div class="form_container ui-widget-content ui-helper-clearfix">
    <form method="get" id="params" name="params">
        <input name="func" type="hidden" value="reports"> <input name="form"
            type="hidden" value="product"> <input name="report" type="hidden"
            value="1">

        <div class="unlimited_label">
            <h1><?php echo Translator::translate('ProductReport')?></h1>
        </div>

        <div class="medium_label">
            <?php echo Translator::translate('InvoiceDateInterval')?>
        </div>
        <div class="field">
            <?php
            echo htmlFormElement(
                'date', 'TEXT', "$dateRange", 'medium hasDateRangePicker', '', 'MODIFY', false
            );
            ?>
        </div>

        <div class="medium_label"><?php echo Translator::translate('Biller')?></div>
        <div class="field">
            <?php
            echo htmlFormElement(
                'base', 'LIST', $intBaseId, 'medium',
                'SELECT id, name FROM {prefix}base WHERE deleted=0 ORDER BY name',
                'MODIFY', false
            );
            ?>
        </div>

        <div class="medium_label"><?php echo Translator::translate('Client')?></div>
        <div class="field">
            <?php
            echo htmlFormElement(
                'company', 'LIST', $intCompanyId, 'medium',
                'SELECT id, company_name FROM {prefix}company WHERE deleted=0 ORDER BY company_name',
                'MODIFY', false
            );
            ?>
        </div>

        <div class="medium_label"><?php echo Translator::translate('Product')?></div>
        <div class="field">
            <?php
            echo htmlFormElement(
                'product', 'LIST', $intProductId, 'medium',
                'SELECT id, product_name FROM {prefix}product WHERE deleted=0 ORDER BY product_name',
                'MODIFY', false
            );
            ?>
        </div>

        <div class="medium_label"><?php echo Translator::translate('PrintFormat')?></div>
        <div class="field">
            <label>
                <input type="radio" id="format-table" name="format" value="table" checked="checked">
                <?php echo Translator::translate('PrintFormatTable')?>
            </label>
        </div>
        <div class="medium_label"></div>
        <div class="field">
            <label>
                <input type="radio" id="format-html" name="format" value="html">
                <?php echo Translator::translate('PrintFormatHTML')?>
            </label>
        </div>
        <div class="medium_label"></div>
        <div class="field">
            <label>
                <input type="radio" id="format-pdf" name="format" value="pdf">
                <?php echo Translator::translate('PrintFormatPDF')?>
            </label>
        </div>
        <div class="field_sep"></div>

<?php
        $this->addInvoiceStateSelection();
?>

        <div class="unlimited_label">
            <a class="actionlink form-submit" href="#" data-form-target="">
                <?php echo Translator::translate('CreateReport')?>
            </a>
            <a class="actionlink form-submit" href="#" data-form-target="_blank">
                <?php echo Translator::translate('CreateReportInNewWindow')?>
            </a>
        </div>
    </form>
</div>
<?php
    }

    /**
     * Print the report
     *
     * @return void
     */
    protected function printReport()
    {
        $intStateID = getRequest('stateid', false);
        $intBaseId = getRequest('base', false);
        $intCompanyId = getRequest('company', false);
        $intProductId = getRequest('product', false);
        $format = getRequest('format', 'html');

        $dateRange = explode(' - ', getRequest('date', ''));
        $startDate = $dateRange[0];
        $endDate = isset($dateRange[1]) ? $dateRange[1] : $startDate;

        if ($startDate) {
            $startDate = dateConvDate2DBDate($startDate);
        }
        if ($endDate) {
            $endDate = dateConvDate2DBDate($endDate);
        }

        $arrParams = [];

        $strQuery = 'SELECT i.id ' . 'FROM {prefix}invoice i ' . 'WHERE i.deleted=0';

        if ($startDate) {
            $strQuery .= ' AND i.invoice_date >= ?';
            $arrParams[] = $startDate;
        }
        if ($endDate) {
            $strQuery .= ' AND i.invoice_date <= ?';
            $arrParams[] = $endDate;
        }
        if ($intBaseId) {
            $strQuery .= ' AND i.base_id = ?';
            $arrParams[] = $intBaseId;
        }
        if ($intCompanyId) {
            $strQuery .= ' AND i.company_id = ?';
            $arrParams[] = $intCompanyId;
        }

        $strQuery2 = '';
        $strQuery3 = 'SELECT id, name ' .
             'FROM {prefix}invoice_state WHERE deleted=0 ' . 'ORDER BY order_no';
        $intRes = dbQueryCheck($strQuery3);
        while ($row = mysqli_fetch_assoc($intRes)) {
            $intStateId = $row['id'];
            $strStateName = $row['name'];
            $strTemp = "stateid_$intStateId";
            $tmpSelected = getRequest($strTemp, false) ? true : false;
            if ($tmpSelected) {
                $strQuery2 .= ' i.state_id = ? OR ';
                $arrParams[] = $intStateId;
            }
        }
        if ($strQuery2) {
            $strQuery2 = ' AND (' . substr($strQuery2, 0, -3) . ')';
        }

        $strQuery .= "$strQuery2 ORDER BY invoice_no";

        if ($intProductId) {
            $strProductWhere = 'AND ir.product_id = ? ';
            $arrParams[] = $intProductId;
        } else {
            $strProductWhere = '';
        }

        $strProductQuery = 'SELECT p.id, p.product_code, p.product_name, ir.description, ' .
             'ir.vat, ir.pcs, t.name as unit, ir.price, ir.vat_included, ir.discount, ' .
             'ir.discount_amount ' .
             'FROM {prefix}invoice_row ir ' .
             'LEFT OUTER JOIN {prefix}product p ON p.id = ir.product_id ' .
             'LEFT OUTER JOIN {prefix}row_type t ON t.id = ir.type_id ' .
             "WHERE ir.deleted = 0 AND ir.partial_payment = 0 AND ir.invoice_id IN ($strQuery) $strProductWhere" .
             'ORDER BY p.id, ir.description, t.name, ir.vat';

        $this->printHeader($format, $startDate, $endDate);

        $totalSum = 0;
        $totalVAT = 0;
        $totalSumVAT = 0;
        $prevRow = false;
        $productCount = 0;
        $productSum = 0;
        $productVAT = 0;
        $productSumVAT = 0;
        $rows = dbParamQuery($strProductQuery, $arrParams);
        foreach ($rows as $row) {
            if ($prevRow !== false && ($prevRow['id'] != $row['id']
                || $prevRow['description'] != $row['description']
                || $prevRow['unit'] != $row['unit']
                || $prevRow['vat'] != $row['vat'])
            ) {
                $this->printRow(
                    $format, $prevRow['id'], $prevRow['product_code'],
                    $prevRow['product_name'], $prevRow['description'], $productCount,
                    $prevRow['unit'], $productSum, $prevRow['vat'], $productVAT,
                    $productSumVAT
                );
                $productCount = 0;
                $productSum = 0;
                $productVAT = 0;
                $productSumVAT = 0;
            }
            $prevRow = $row;

            $productCount += $row['pcs'];
            list($rowSum, $rowVAT, $rowSumVAT) = calculateRowSum($row);

            $productSum += $rowSum;
            $productVAT += $rowVAT;
            $productSumVAT += $rowSumVAT;

            $totalSum += $rowSum;
            $totalVAT += $rowVAT;
            $totalSumVAT += $rowSumVAT;
        }
        if ($prevRow !== false) {
            $this->printRow(
                $format, $prevRow['id'], $prevRow['product_code'],
                $prevRow['product_name'], $prevRow['description'], $productCount,
                $prevRow['unit'], $productSum, $prevRow['vat'], $productVAT,
                $productSumVAT
            );
        }

        $this->printTotals($format, $totalSum, $totalVAT, $totalSumVAT);
        $this->printFooter($format);
    }

    /**
     * Print header
     *
     * @param string $format    Format
     * @param string $startDate Start date
     * @param string $endDate   End date
     *
     * @return void
     */
    protected function printHeader($format, $startDate, $endDate)
    {
        if ($format == 'pdf') {
            ob_end_clean();
            $pdf = new PDF('P', 'mm', 'A4', _CHARSET_ == 'UTF-8', _CHARSET_, false);
            $pdf->setTopMargin(20);
            $pdf->headerRight = Translator::translate('ReportPage');
            $pdf->printHeaderOnFirstPage = true;
            $pdf->AddPage();
            $pdf->SetAutoPageBreak(true, 15);

            $pdf->setY(10);
            $pdf->SetFont('Helvetica', 'B', 12);
            $pdf->Cell(100, 15, Translator::translate('ProductReport'), 0, 1, 'L');

            $pdf->SetFont('Helvetica', '', 8);
            $pdf->MultiCell(180, 5, $this->getParamsStr(false), 0, 'L');
            $pdf->setY($pdf->getY() + 5);

            $pdf->SetFont('Helvetica', 'B', 8);
            $pdf->Cell(15, 4, Translator::translate('Code'), 0, 0, 'L');
            $pdf->Cell(40, 4, Translator::translate('Product'), 0, 0, 'L');
            $pdf->Cell(25, 4, Translator::translate('PCS'), 0, 0, 'R');
            $pdf->Cell(25, 4, Translator::translate('Unit'), 0, 0, 'R');
            $pdf->Cell(25, 4, Translator::translate('VATLess'), 0, 0, 'R');
            $pdf->Cell(15, 4, Translator::translate('VATPercent'), 0, 0, 'R');
            $pdf->Cell(25, 4, Translator::translate('VATPart'), 0, 0, 'R');
            $pdf->Cell(25, 4, Translator::translate('WithVAT'), 0, 1, 'R');
            $this->pdf = $pdf;
            return;
        }
        ?>
  <div class="report">
    <table class="report-table">
      <tr>
        <td>
          <div class="unlimited_label">
            <strong><?php echo Translator::translate('ProductReport')?></strong>
          </div>
        </td>
      </tr>
      <tr>
        <td><?php echo $this->getParamsStr(true) ?></td>
      </tr>
    </table>

    <table class="report-table<?php echo $format == 'table' ? ' datatable' : '' ?>">
      <thead>
        <tr>
            <th class="label">
            <?php echo Translator::translate('Code')?>
            </th>
            <th class="label">
            <?php echo Translator::translate('Product')?>
            </th>
            <th class="label" style="text-align: right">
            <?php echo Translator::translate('PCS')?>
            </th>
            <th class="label" style="text-align: right">
            <?php echo Translator::translate('Unit')?>
            </th>
            <th class="label" style="text-align: right">
            <?php echo Translator::translate('VATLess')?>
            </th>
            <th class="label" style="text-align: right">
            <?php echo str_replace(' ', '&nbsp;', Translator::translate('VATPercent'))?>
            </th>
            <th class="label" style="text-align: right">
            <?php echo Translator::translate('VATPart')?>
            </th>
            <th class="label" style="text-align: right">
            <?php echo Translator::translate('WithVAT')?>
            </th>
        </tr>
      </thead>
      <tbody>
<?php
    }

    /**
     * Print a row
     *
     * @param string $format         Format
     * @param int    $id             Record ID
     * @param string $strCode        Product code
     * @param string $strProduct     Product name
     * @param string $strDescription Product description
     * @param int    $intCount       Count
     * @param string $strUnit        Unit
     * @param int    $intSum         Sum
     * @param int    $intVATPercent  VAT percent
     * @param int    $intVAT         VAT
     * @param int    $intSumVAT      Sum including VAT
     *
     * @return void
     */
    protected function printRow($format, $id, $strCode, $strProduct, $strDescription,
        $intCount, $strUnit, $intSum, $intVATPercent, $intVAT, $intSumVAT
    ) {

        if ($strDescription) {
            if ($format == 'html' && mb_strlen($strDescription, 'UTF-8') > 20) {
                $strDescription = mb_substr($strDescription, 0, 17, 'UTF-8') . '...';
            }
            if ($strProduct) {
                $strProduct .= " ($strDescription)";
            } else {
                $strProduct = $strDescription;
            }
        }

        if ($strUnit) {
            $strUnit = Translator::translate($strUnit);
        }

        if ($format == 'pdf') {
            if (!$strProduct) {
                $strProduct = '-';
            }

            $pdf = $this->pdf;
            $pdf->SetFont('Helvetica', '', 8);
            $pdf->setY($pdf->getY() + 1);
            $cells = $pdf->MultiCell(16, 3, $strCode, 0, 'L', false, 0);
            $nameX = 25;
            $pdf->setX($nameX + 40);
            $pdf->Cell(25, 3, miscRound2Decim($intCount), 0, 0, 'R');
            $pdf->Cell(25, 3, $strUnit, 0, 0, 'R');
            $pdf->Cell(25, 3, miscRound2Decim($intSum), 0, 0, 'R');
            $pdf->Cell(15, 3, miscRound2Decim($intVATPercent, 1), 0, 0, 'R');
            $pdf->Cell(25, 3, miscRound2Decim($intVAT), 0, 0, 'R');
            $pdf->Cell(25, 3, miscRound2Decim($intSumVAT), 0, 0, 'R');
            $pdf->setX($nameX);
            $cells2 = $pdf->MultiCell(40, 3, $strProduct, 0, 'L');
            if ($cells > $cells2) {
                $pdf->setY($pdf->getY() + ($cells - $cells2) * 3);
            }
            return;
        }
        if (!$strProduct) {
            $strProduct = '&ndash;';
        } else {
            $strProduct = htmlspecialchars($strProduct);
        }
        ?>
    <tr>
            <td class="input">
            <?php echo $strCode?>
        </td>
            <td class="input" data-sort="<?php echo $strProduct?>">
            <?php if (null !== $id) { ?>
                <a href="index.php?func=settings&list=product&form=product&id=<?php echo htmlspecialchars($id)?>">
                    <?php echo $strProduct?>
                </a>
            <?php } else { ?>
                <?php echo $strProduct?>
            <?php } ?>
        </td>
            <td class="input" style="text-align: right">
            <?php echo miscRound2Decim($intCount)?>
        </td>
            <td class="input" style="text-align: left">
            <?php echo htmlspecialchars($strUnit)?>
        </td>
            <td class="input" style="text-align: right">
            <?php echo miscRound2Decim($intSum)?>
        </td>
            <td class="input" style="text-align: right">
            <?php echo miscRound2Decim($intVATPercent, 1)?>
        </td>
            <td class="input" style="text-align: right">
            <?php echo miscRound2Decim($intVAT)?>
        </td>
            <td class="input" style="text-align: right">
            <?php echo miscRound2Decim($intSumVAT)?>
        </td>
        </tr>
<?php
    }

    /**
     * Print totals
     *
     * @param string $format       Format
     * @param int    $intTotSum    Total sum
     * @param int    $intTotVAT    Total VAT
     * @param int    $intTotSumVAT Total sum including VAT
     *
     * @return void
     */
    protected function printTotals($format, $intTotSum, $intTotVAT, $intTotSumVAT)
    {
        if ($format == 'pdf') {
            $pdf = $this->pdf;
            $pdf->SetFont('Helvetica', 'B', 8);
            $pdf->setY($pdf->getY() + 3);
            $pdf->Cell(55, 3, Translator::translate('Total'), 0, 0, 'L');
            $pdf->Cell(25, 3, '', 0, 0, 'L');
            $pdf->Cell(25, 3, '', 0, 0, 'L');
            $pdf->Cell(25, 3, miscRound2Decim($intTotSum), 0, 0, 'R');
            $pdf->Cell(15, 3, '', 0, 0, 'L');
            $pdf->Cell(25, 3, miscRound2Decim($intTotVAT), 0, 0, 'R');
            $pdf->Cell(25, 3, miscRound2Decim($intTotSumVAT), 0, 1, 'R');
            return;
        }

        if ($format != 'html') {
            return;
        }

        ?>
    <tr>
            <td class="input total_sum">
            <?php echo Translator::translate('Total')?>
        </td>
            <td class="input total_sum" style="text-align: right">&nbsp;</td>
            <td class="input total_sum" style="text-align: right">&nbsp;</td>
            <td class="input total_sum" style="text-align: right">&nbsp;</td>
            <td class="input total_sum" style="text-align: right">
            <?php echo miscRound2Decim($intTotSum)?>
        </td>
            <td class="input total_sum" style="text-align: right">&nbsp;</td>
            <td class="input total_sum" style="text-align: right">
            <?php echo miscRound2Decim($intTotVAT)?>
        </td>
            <td class="input total_sum" style="text-align: right">
            <?php echo miscRound2Decim($intTotSumVAT)?>
        </td>
        </tr>
<?php
    }

    /**
     * Print footer
     *
     * @param string $format Format
     *
     * @return void
     */
    protected function printFooter($format)
    {
        if ($format == 'pdf') {
            $pdf = $this->pdf;
            $pdf->Output('report.pdf', 'I');
            return;
        }
        ?>
      </tbody>
      <tfoot>
        <tr>
          <td></td>
          <td></td>
          <td></td>
          <td></td>
          <td></td>
          <td></td>
          <td></td>
          <td></td>
      </tfoot>
    </table>
  </div>
        <?php
        if ($format == 'table') {
        ?>
<script type="text/javascript">
var table = $('.report-table.datatable').DataTable({
    'language': {
        <?php echo Translator::translate('TableTexts')?>
    },
    'pageLength': 50,
    'jQueryUI': true,
    'pagingType': 'full_numbers',
    'footerCallback': function (row, data, start, end, display) {
        var api = this.api(), data;

        $([4, 6, 7]).each(function(i, column) {
            // Total over all pages
            var total = api
                .column(column)
                .data()
                .reduce(function (a, b) {
                    return intVal(a) + intVal(b);
                }, 0);


            // Total over this page
            var pageTotal = api
                .column(column, { page: 'current'})
                .data()
                .reduce(function (a, b) {
                    return intVal(a) + intVal(b);
                }, 0);

            // Update footer
            pageTotal = MLInvoice.formatCurrency(pageTotal/100);
            total = MLInvoice.formatCurrency(total/100);
            $(api.column(column).footer()).html(
                '<div style="float: right"><?php echo Translator::translate('VisiblePage') ?>&nbsp;'
                + pageTotal
                + '</div><br><div style="float: right"><?php echo Translator::translate('Total') ?>&nbsp;'
                + total + '</div>'
            );
        });
    }
});

var buttons = new $.fn.dataTable.Buttons(table, {
    buttons: [
        'copy', 'csv', 'excel', 'pdf'
    ]
});

table.buttons().container().appendTo($('.fg-toolbar', table.table().container()));
</script>
<?php
        }
    }
}
