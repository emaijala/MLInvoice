<?php
/**
 * Quick search
 *
 * PHP version 5
 *
 * Copyright (C) 2004-2008 Samu Reinikainen
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
require_once 'htmlfuncs.php';
require_once 'sqlfuncs.php';
require_once 'sessionfuncs.php';
require_once 'miscfuncs.php';
require_once 'datefuncs.php';

sesVerifySession();

require_once 'translator.php';

$strFunc = getRequest('func', '');
if ($strFunc == 'open_invoices') {
    $strFunc = 'invoices';
}

$strQuery = 'SELECT * FROM {prefix}quicksearch ' . 'WHERE func=? AND user_id=? ' .
     'ORDER BY name';
$rows = dbParamQuery($strQuery, [$strFunc, $_SESSION['sesUSERID']]);

foreach ($rows as $row) {
    $intId = $row['id'];
    $blnDelete = getPost('delete_' . $intId . '_x', false) ? true : false;
    if ($blnDelete && $intId) {
        $strDelQuery = 'DELETE FROM {prefix}quicksearch ' . 'WHERE id=?';
        dbParamQuery($strDelQuery, [$intId]);
    }
}

echo htmlPageStart();
?>

<body>
    <div class="form_container ui-widget-content">
        <div class="form ui-widget">
            <form method="post"
                action="quick_search.php?func=<?php echo $strFunc?>" target="_self"
                name="search_form">
                <table style="width: 100%">
                    <tr>
                        <td class="sublabel" colspan="4">
    <?php echo Translator::translate('LabelQuickSearch')?><br> <br>
                        </td>
                    </tr>
<?php
$rows = dbParamQuery($strQuery, [$strFunc, $_SESSION['sesUSERID']]);
foreach ($rows as $row) {
    $intID = $row['id'];
    $strName = $row['name'];
    $strFunc = $row['func'];
    $strWhereClause = $row['whereclause'];
    $strLink = "index.php?func=$strFunc&where=$strWhereClause";
    $strOnClick = "opener.location.href='$strLink'";
    ?>
                    <tr class="search_row">
                        <td class="label"><a href="quick_search.php"
                            onClick="<?php echo $strOnClick?>; return false;"><?php echo $strName?></a>
                        </td>
                        <td>
                            <input type="hidden" name="delete_<?php echo $intID?>_x" value="0">
                            <a class="tinyactionlink form-submit" href="#" data-set-field="delete_<?php echo $intID?>_x"> X </a>
                        </td>
                    </tr>
<?php
}
if (!isset($intID)) {
    ?>
<tr>
                        <td class="label">
        <?php echo Translator::translate('NoQuickSearches')?>
    </td>
                    </tr>
<?php
}
?>
</table>

                <center>
                    <table>
                        <tr>
                            <td>
                              <a class="actionlink popup-close" href="#">
                                <?php echo Translator::translate('Close')?>
                              </a>
                            </td>
                        </tr>
                    </table>
                </center>
            </form>
        </div>
    </div>
</body>
</html>
