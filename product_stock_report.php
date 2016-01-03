<?php
/*******************************************************************************
 MLInvoice: web-based invoicing application.
 Copyright (C) 2010-2015 Ere Maijala

 Portions based on:
 PkLasku : web-based invoicing software.
 Copyright (C) 2004-2008 Samu Reinikainen

 This program is free software. See attached LICENSE.

 *******************************************************************************/

/*******************************************************************************
 MLInvoice: web-pohjainen laskutusohjelma.
 Copyright (C) 2010-2015 Ere Maijala

 Perustuu osittain sovellukseen:
 PkLasku : web-pohjainen laskutusohjelmisto.
 Copyright (C) 2004-2008 Samu Reinikainen

 Tämä ohjelma on vapaa. Lue oheinen LICENSE.

 *******************************************************************************/
require_once 'htmlfuncs.php';
require_once 'sqlfuncs.php';
require_once 'miscfuncs.php';
require_once 'datefuncs.php';
require_once 'localize.php';
require_once 'pdf.php';

class ProductStockReport
{
    protected $pdf = null;

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
            <h1><?php echo $GLOBALS['locProductStockReport']?></h1>
        </div>

        <div class="medium_label"><?php echo $GLOBALS['locProduct']?></div>
        <div class="field"><?php echo htmlFormElement('product', 'LIST', $intProductId, 'medium', 'SELECT id, product_name FROM {prefix}product WHERE deleted=0 ORDER BY product_name', 'MODIFY', FALSE)?></div>
        <div class="field_sep"></div>
        <div class="medium_label"></div>
        <div class="field">
            <input type="checkbox" id="purchase-price" name="purchase_price" value="1"> <label for="purchase-price"><?php echo $GLOBALS['locOnlyProductsWithPurchasePrice']?></label>
        </div>

        <div class="medium_label"><?php echo $GLOBALS['locPrintFormat']?></div>
        <div class="field">
            <input type="radio" id="format-html" name="format" value="html" checked="checked"><label for="format-html"><?php echo $GLOBALS['locPrintFormatHTML']?></label>
        </div>
        <div class="medium_label"></div>
        <div class="field">
            <input type="radio" id="format-pdf" name="format" value="pdf"><label for="format-pdf"><?php echo $GLOBALS['locPrintFormatPDF']?></label>
        </div>
        <div class="field_sep"></div>

        <div class="medium_label">
            <a class="actionlink" href="#" onclick="document.getElementById('params').submit(); return false;"><?php echo $GLOBALS['locCreateReport']?></a>
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
        $intRes = mysqli_param_query($strQuery, $arrParams);
        while ($row = mysqli_fetch_assoc($intRes)) {
            $this->printRow($format, $row['product_code'], $row['product_name'],
                $row['purchase_price'], $row['unit_price'], $row['stock_balance']);
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
            $pdf->headerRight = $GLOBALS['locReportPage'];
            $pdf->printHeaderOnFirstPage = true;
            $pdf->AddPage();
            $pdf->SetAutoPageBreak(TRUE, 15);

            $pdf->setY(10);
            $pdf->SetFont('Helvetica', 'B', 12);
            $pdf->Cell(100, 5, $GLOBALS['locProductStockReport'], 0, 1, 'L');

            $pdf->SetFont('Helvetica', 'B', 8);
            $pdf->Cell(50, 10, date($GLOBALS['locDateFormat']), 0, 1, 'L');
            $pdf->Cell(15, 4, $GLOBALS['locCode'], 0, 0, 'L');
            $pdf->Cell(40, 4, $GLOBALS['locProduct'], 0, 0, 'L');
            $pdf->Cell(25, 4, $GLOBALS['locUnitPrice'], 0, 0, 'R');
            $pdf->Cell(25, 4, $GLOBALS['locPurchasePrice'], 0, 0, 'R');
            $pdf->Cell(25, 4, $GLOBALS['locStockBalance'], 0, 0, 'R');
            $pdf->Cell(25, 4, $GLOBALS['locStockValue'], 0, 1, 'R');
            $this->pdf = $pdf;
            return;
        }
        ?>
<div class="report">
    <table>
        <tr>
            <th class="label">
            <?php echo $GLOBALS['locCode']?>
        </th>
            <th class="label">
            <?php echo $GLOBALS['locProduct']?>
        </th>
            <th class="label" style="text-align: right">
            <?php echo $GLOBALS['locUnitPrice']?>
        </th>
            <th class="label" style="text-align: right">
            <?php echo $GLOBALS['locPurchasePrice']?>
        </th>
            <th class="label" style="text-align: right">
            <?php echo $GLOBALS['locStockBalance']?>
        </th>
            <th class="label" style="text-align: right">
            <?php echo $GLOBALS['locStockValue']?>
        </th>
        </tr>
<?php
    }

    protected function printRow($format, $strCode, $strProduct, $purchasePrice,
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
            <td class="input">
            <?php echo $strProduct?>
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
            $pdf->line($pdf->getX() + $sumPos, $pdf->getY(),
                $pdf->getX() + $rowWidth, $pdf->getY());
            $pdf->setY($pdf->getY() + 1);
            $pdf->Cell($sumPos, 4, $GLOBALS['locTotal'], 0, 0, 'R');
            $pdf->Cell(25, 4, miscRound2Decim($stockValue), 0, 1, 'R');
            return;
        }

        $colSpan = 5;
        ?>
    <tr>
    <?php if ($colSpan > 0) { ?>
        <td class="input total_sum" colspan="<?php echo $colSpan?>"
                style="text-align: right">
            <?php echo $GLOBALS['locTotal']?>
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
    </table>
</div>
<?php
    }
}
