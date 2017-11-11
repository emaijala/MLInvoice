<?php
/*******************************************************************************
 MLInvoice: web-based invoicing application.
 Copyright (C) 2010-2017 Ere Maijala

 Portions based on:
 PkLasku : web-based invoicing software.
 Copyright (C) 2004-2008 Samu Reinikainen

 This program is free software. See attached LICENSE.

 *******************************************************************************/

/*******************************************************************************
 MLInvoice: web-pohjainen laskutusohjelma.
 Copyright (C) 2010-2017 Ere Maijala

 Perustuu osittain sovellukseen:
 PkLasku : web-pohjainen laskutusohjelmisto.
 Copyright (C) 2004-2008 Samu Reinikainen

 Tämä ohjelma on vapaa. Lue oheinen LICENSE.

 *******************************************************************************/
require_once 'htmlfuncs.php';
require_once 'sqlfuncs.php';
require_once 'miscfuncs.php';
require_once 'datefuncs.php';
require_once 'translator.php';
require_once 'pdf.php';
require_once 'abstract_report.php';

class ProductStockReport extends AbstractReport
{
    public function createReport()
    {
        $strReport = getRequest('report', '');

        if ($strReport) {
            $this->printReport();
            return;
        }

        $intProductId = getRequest('product', false);
        ?>

<div class="form_container ui-widget-content ui-helper-clearfix">
    <form method="get" id="params" name="params">
        <input name="func" type="hidden" value="reports"> <input name="form"
            type="hidden" value="product_stock"> <input name="report"
            type="hidden" value="1">

        <div class="unlimited_label">
            <h1><?php echo Translator::translate('ProductStockReport')?></h1>
        </div>

        <div class="medium_label"><?php echo Translator::translate('Product')?></div>
        <div class="field"><?php echo htmlFormElement('product', 'LIST', $intProductId, 'medium', 'SELECT id, product_name FROM {prefix}product WHERE deleted=0 ORDER BY product_name', 'MODIFY', FALSE)?></div>
        <div class="field_sep"></div>
        <div class="medium_label"></div>
        <div class="field">
            <input type="checkbox" id="purchase-price" name="purchase_price" value="1"> <label for="purchase-price"><?php echo Translator::translate('OnlyProductsWithPurchasePrice')?></label>
        </div>

        <div class="medium_label"><?php echo Translator::translate('PrintFormat')?></div>
        <div class="field">
            <input type="radio" id="format-table" name="format" value="table" checked="checked">
            <label for="format-table"><?php echo Translator::translate('PrintFormatTable')?></label>
        </div>
        <div class="medium_label"></div>
        <div class="field">
            <input type="radio" id="format-html" name="format" value="html"><label for="format-html"><?php echo Translator::translate('PrintFormatHTML')?></label>
        </div>
        <div class="medium_label"></div>
        <div class="field">
            <input type="radio" id="format-pdf" name="format" value="pdf"><label for="format-pdf"><?php echo Translator::translate('PrintFormatPDF')?></label>
        </div>
        <div class="field_sep"></div>

        <div class="unlimited_label">
            <a class="actionlink" href="#" onclick="var form = document.getElementById('params'); form.target = ''; form.submit(); return false;">
                <?php echo Translator::translate('CreateReport')?>
            </a>
            <a class="actionlink" href="#" onclick="var form = document.getElementById('params'); form.target = '_blank'; form.submit(); return false;">
                <?php echo Translator::translate('CreateReportInNewWindow')?>
            </a>
        </div>
    </form>
</div>
<?php
    }

    protected function printReport()
    {
        $intProductId = getRequest('product', FALSE);
        $format = getRequest('format', 'html');
        $purchasePrice = getRequest('purchase_price', false);

        $arrParams = [];

        $strQuery = 'SELECT * ' . 'FROM {prefix}product ' . 'WHERE deleted=0';

        if ($intProductId) {
            $strQuery .= ' AND id = ? ';
            $arrParams[] = $intProductId;
        }

        if ($purchasePrice) {
            $strQuery .= ' AND NOT (purchase_price IS NULL or purchase_price = 0)';
        }

        $this->printHeader($format);

        $stockValue = 0;
        $rows = db_param_query($strQuery, $arrParams);
        foreach ($rows as $row) {
            $this->printRow(
                $format, $row['id'], $row['product_code'], $row['product_name'],
                $row['purchase_price'], $row['unit_price'], $row['stock_balance']
            );
            $stockValue += $row['stock_balance'] * $row['purchase_price'];
        }
        $this->printTotals($format, $stockValue);
        $this->printFooter($format);
    }

    protected function printHeader($format)
    {
        if ($format == 'pdf') {
            ob_end_clean();
            $pdf = new PDF('P', 'mm', 'A4', _CHARSET_ == 'UTF-8', _CHARSET_, false);
            $pdf->setTopMargin(20);
            $pdf->headerRight = Translator::translate('ReportPage');
            $pdf->printHeaderOnFirstPage = true;
            $pdf->AddPage();
            $pdf->SetAutoPageBreak(TRUE, 15);

            $pdf->setY(10);
            $pdf->SetFont('Helvetica', 'B', 12);
            $pdf->Cell(100, 10, Translator::translate('ProductStockReport'), 0, 1, 'L');

            $pdf->SetFont('Helvetica', 'B', 8);
            $pdf->Cell(50, 10, date(Translator::translate('DateFormat')), 0, 1, 'L');

            if ($params = $this->getParamsStr(false)) {
                $pdf->SetFont('Helvetica', '', 8);
                $pdf->MultiCell(180, 5, $params, 0, 'L');
                $pdf->setY($pdf->getY() + 5);
            }

            $pdf->SetFont('Helvetica', 'B', 8);
            $pdf->Cell(15, 4, Translator::translate('Code'), 0, 0, 'L');
            $pdf->Cell(40, 4, Translator::translate('Product'), 0, 0, 'L');
            $pdf->Cell(25, 4, Translator::translate('UnitPrice'), 0, 0, 'R');
            $pdf->Cell(25, 4, Translator::translate('PurchasePrice'), 0, 0, 'R');
            $pdf->Cell(25, 4, Translator::translate('StockBalance'), 0, 0, 'R');
            $pdf->Cell(25, 4, Translator::translate('StockValue'), 0, 1, 'R');
            $this->pdf = $pdf;
            return;
        }
        ?>
  <div class="report">
    <table class="report-table">
      <tr>
        <td>
          <div class="unlimited_label">
            <strong><?php echo Translator::translate('ProductStockReport')?></strong>
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
            <?php echo Translator::translate('UnitPrice')?>
          </th>
          <th class="label" style="text-align: right">
            <?php echo Translator::translate('PurchasePrice')?>
          </th>
          <th class="label" style="text-align: right">
            <?php echo Translator::translate('StockBalance')?>
          </th>
          <th class="label" style="text-align: right">
            <?php echo Translator::translate('StockValue')?>
          </th>
        </tr>
      </thead>
      <tbody>
<?php
    }

    protected function printRow($format, $id, $strCode, $strProduct, $purchasePrice,
        $unitPrice, $stockBalance)
    {
        if ($format == 'pdf') {
            if (!$strProduct)
                $strProduct = '-';

            $pdf = $this->pdf;
            $pdf->SetFont('Helvetica', '', 8);
            $pdf->setY($pdf->getY() + 1);
            $cells = $pdf->MultiCell(16, 3, $strCode, 0, 'L', false, 0);
            $nameX = 25;
            $pdf->setX($nameX + 40);
            $pdf->Cell(25, 3, miscRound2Decim($unitPrice), 0, 0, 'R');
            $pdf->Cell(25, 3, miscRound2Decim($purchasePrice), 0, 0, 'R');
            $pdf->Cell(25, 3, miscRound2Decim($stockBalance), 0, 0, 'R');
            $pdf->Cell(25, 3, miscRound2Decim($stockBalance * $purchasePrice), 0, 0,
                'R');
            $pdf->setX($nameX);
            $cells2 = $pdf->MultiCell(40, 3, $strProduct, 0, 'L');
            if ($cells > $cells2) {
                $pdf->setY($pdf->getY() + ($cells - $cells2) * 3);
            }
            return;
        }
        if (!$strProduct)
            $strProduct = '&ndash;';
        else
            $strProduct = htmlspecialchars($strProduct);
        ?>
      <tr>
        <td class="input">
            <?php echo $strCode?>
        </td>
        <td class="input" data-sort="<?php echo $strProduct?>">
            <a href="index.php?func=settings&list=product&form=product&id=<?php echo htmlspecialchars($id)?>"><?php echo $strProduct?></a>
        </td>
        <td class="input" style="text-align: right">
            <?php echo miscRound2Decim($unitPrice)?>
        </td>
        <td class="input" style="text-align: right">
            <?php echo miscRound2Decim($purchasePrice)?>
        </td>
        <td class="input" style="text-align: right">
            <?php echo miscRound2Decim($stockBalance)?>
        </td>
        <td class="input" style="text-align: right">
            <?php echo miscRound2Decim($stockBalance * $purchasePrice)?>
        </td>
      </tr>
<?php
    }

    protected function printTotals($format, $stockValue)
    {
        if ($format == 'pdf') {
            $pdf = $this->pdf;
            if ($pdf->getY() > $pdf->getPageHeight() - 7 - 15)
                $pdf->AddPage();
            $pdf->SetFont('Helvetica', '', 8);
            $pdf->setLineWidth(0.2);

            $sumPos = 130;
            $rowWidth = 150;

            $pdf = $this->pdf;
            $pdf->SetFont('Helvetica', 'B', 8);
            $pdf->setY($pdf->getY() + 1);
            $pdf->Cell($sumPos, 4, Translator::translate('Total'), 0, 0, 'R');
            $pdf->Cell(25, 4, miscRound2Decim($stockValue), 0, 1, 'R');
            return;
        }

        if ($format != 'html') {
            return;
        }

        $colSpan = 5;
        ?>
      <tr>
    <?php if ($colSpan > 0) { ?>
        <td class="input total_sum" colspan="<?php echo $colSpan?>"
                style="text-align: right">
            <?php echo Translator::translate('Total')?>
        </td>
    <?php } ?>
        <td class="input total_sum" style="text-align: right">
            &nbsp;<?php echo miscRound2Decim($stockValue)?>
        </td>
      </tr>
<?php
    }

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
        </tr>
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

        $([5]).each(function(i, column) {
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
            $(api.column(column).footer()).html('<div style="float: right"><?php echo Translator::translate('VisiblePage') ?>&nbsp;' + pageTotal + '</div><br><div style="float: right"><?php echo Translator::translate('Total') ?>&nbsp;' + total + '</div>');
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
