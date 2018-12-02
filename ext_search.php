<?php
/**
 * Extended search
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
require_once 'form_config.php';

sesVerifySession();

require_once 'translator.php';

$strFunc = getRequest('func', '');
$strForm = getRequest('form', '');
if ($strFunc == 'open_invoices') {
    $strFunc = 'invoices';
}
$strList = $strFunc;

$blnSearch = getPost('search_x', false) ? true : false;
$blnSave = getPost('save_x', false) ? true : false;
$strFields = getPost('fields', false);
$strSearchField = getPost('searchfield', false);
$strSearchName = getPost('searchname', '');
if ($strSearchField !== false) {
    if ($strFields === false) {
        $strFields = $strSearchField;
    } else {
        $strFields .= ",$strSearchField";
    }
}

if ($strFields !== false) {
    $astrSelectedFields = explode(',', $strFields);
} else {
    $astrSelectedFields = [];
}

$formConfig = getFormConfig($strForm);

for ($j = 0; $j < count($astrSelectedFields); $j ++) {
    $tmpDelete = getPost('delete_' . $astrSelectedFields[$j] . '_x', false);
    if ($tmpDelete) {
        $astrSelectedFields[$j] = '';
    }
}

$strFields = implode(',', $astrSelectedFields);

foreach ($formConfig['fields'] as &$field) {
    if ($field['type'] == 'RESULT' && $field['name'] != '') {
        $field['type'] = 'TEXT';
    }
}

$listValues = [];
foreach ($formConfig['fields'] as $field) {
    if ($field['type'] != ''
        && $field['type'] != 'LABEL'
        && $field['type'] != 'HID_INT'
        && $field['type'] != 'IFORM'
        && $field['type'] != 'BUTTON'
        && $field['type'] != 'JSBUTTON'
        && $field['type'] != 'DROPDOWNMENU'
        && !in_array($field['name'], $astrSelectedFields, true)
    ) {
        $listValues[$field['name']] = str_replace(
            '<br>', ' ',
            Translator::translate($field['label'])
        );
    }
    $strControlType = $field['type'];
    $strControlName = $field['name'];

    if ($strControlType == 'IFORM' || $strControlType == 'BUTTON') {
        $astrValues[$strControlName] = '';
    } elseif ($strControlType != 'LABEL') {
        if ($strControlType == 'INTDATE') {
            $astrValues[$strControlName] = getPost($strControlName, '');
        } else {
            $astrValues[$strControlName] = getPost($strControlName, '');
        }
    }
}
$strListBox = htmlListBox('searchfield', $listValues, false, '', true);

$comparisonValues = [
    '=' => Translator::translate('SearchEqual'),
    '!=' => Translator::translate('SearchNotEqual'),
    '<' => Translator::translate('SearchLessThan'),
    '>' => Translator::translate('SearchGreaterThan')
];

$strOnLoad = '';
if ($blnSearch || $blnSave) {
    $strWhereClause = '';
    foreach ($formConfig['fields'] as $field) {
        $name = $field['name'];
        if (in_array($name, $astrSelectedFields, true)) {
            $strSearchOperator = getPost("operator_$name", '');
            if ($strSearchOperator) {
                $strSearchOperator = " $strSearchOperator ";
            }
            $strSearchMatch = getPost("searchmatch_$name", '=');

            // do LIKE || NOT LIKE search to elements with text or varchar datatype
            if ($field['type'] == 'TEXT' || $field['type'] == 'AREA') {
                if ($strSearchMatch == '=') {
                    $strSearchMatch = 'LIKE';
                } else {
                    $strSearchMatch = 'NOT LIKE';
                }
                $strSearchValue = "'%" . addcslashes($astrValues[$name], "'\\") . "%'";
            } elseif ($field['type'] == 'INT'
                || $field['type'] == 'LIST'
                || $field['type'] == 'SELECT'
                || $field['type'] == 'SEARCHLIST'
                || $field['type'] == 'TAGS'
            ) {
                $strSearchValue = $astrValues[$name];
            } elseif ($field['type'] == 'CHECK') {
                $strSearchValue = $astrValues[$name] ? 1 : 0;
            } elseif ($field['type'] == 'INTDATE') {
                $strSearchValue = dateConvDate2DBDate($astrValues[$name]);
            }
            if ($strSearchValue) {
                $tableAlias = $formConfig['tableAlias'];
                $strWhereClause .= "$strSearchOperator$tableAlias$name $strSearchMatch $strSearchValue";
            }
        }
    }

    $strWhereClause = urlencode($strWhereClause);
    if ($blnSearch) {
        $strLink = "index.php?func=$strFunc&where=$strWhereClause";
        $strOnLoad = "opener.location.href='$strLink'";
    }

    if ($blnSave && $strSearchName) {
        $strQuery = 'INSERT INTO {prefix}quicksearch(user_id, name, func, whereclause) ' .
             'VALUES (?, ?, ?, ?)';
        dbParamQuery(
            $strQuery,
            [
                $_SESSION['sesUSERID'],
                $strSearchName,
                $strFunc,
                $strWhereClause
            ]
        );
    } elseif ($blnSave && !$strSearchName) {
        $strOnLoad = "alert('" . Translator::translate('ErrorNoSearchName') . "')";
    }
}

echo htmlPageStart();
?>
<body onload="<?php echo $strOnLoad?>">
    <script type="text/javascript">
<!--
$(function() {
  $('input[class~="hasCalendar"]').datepicker();
});
-->
</script>
  <div class="form_container ui-widget-content">
    <div class="form ui-widget">
      <form method="post"
        action="ext_search.php?func=<?php echo $strFunc?>&amp;form=<?php echo $strForm?>"
        target="_self" name="search_form">
        <input type="hidden" name="fields" value="<?php echo $strFields?>">
        <table>
          <thead>
            <tr>
              <th class="sublabel">
                <?php echo Translator::translate('SearchField')?>
              </th>
              <th class="sublabel">&nbsp;</th>
              <th class="sublabel">
                <?php echo Translator::translate('SearchTerm')?>
              </th>
              <th></th>
            </tr>
          </thead>
        <tbody>
<?php

$fieldCount = 0;
foreach ($formConfig['fields'] as $field) {
    if (in_array($field['name'], $astrSelectedFields, true)) {
        $strSearchMatch = getPost(
            'searchmatch_' . $field['name'],
            '='
        );
        if ($field['style'] == 'xxlong') {
            $field['style'] = 'long';
        }

        if (++$fieldCount > 1) {
            $strSelectedOperator = getPost(
                'operator_' . $field['name'], 'AND'
            );
            $strOperator = htmlListBox(
                'operator_' . $field['name'],
                [
                    'AND' => Translator::translate('SearchAND'),
                    'OR' => Translator::translate('SearchOR')
                ], $strSelectedOperator
            );
            ?>
            <tr>
              <td colspan="4">
                <?php echo $strOperator?>
              </td>
            </tr>
            <?php
        }
        ?>
            <tr class="search_row">
              <td class="label">
                <?php echo Translator::translate($field['label'])?>
              </td>
              <td class="field">
                <?php echo htmlListBox('searchmatch_' . $field['name'], $comparisonValues, $strSearchMatch, '', 0)?>
              </td>
              <td class="field">
                <?php
                echo htmlFormElement(
                    $field['name'], $field['type'],
                    $astrValues[$field['name']],
                    $field['style'], $field['listquery'],
                    'MODIFY', $field['parent_key'], '', [], '',
                    isset($field['options']) ? $field['options'] : null
                );
                ?>
              </td>
              <td>
                <?php
                    $inputName = 'delete_'
                    . $field['name'] . '_x';
                ?>
                <input type="hidden" name="<?php echo $inputName?>"value="0">
                <a class="tinyactionlink form-submit" href="#" title="<?php echo Translator::translate('DelRow')?>"
                  data-set-field="<?php echo $inputName?>">X
                </a>
              </td>
            </tr>
        <?php
    }
}

?>
            <tr>
              <td class="label">
                <?php echo Translator::translate('SelectSearchField')?>
              </td>
              <td class="field" colspan="3">
                <?php echo $strListBox?>
              </td>
            </tr>
            <tr>
              <td colspan="4"
                style="text-align: center; padding-top: 8px; padding-bottom: 8px">
                <input type="hidden" name="search_x" value="0">
                <a class="actionlink form-submit" href="#"
                    data-set-field="search_x">
                    <?php echo Translator::translate('Search')?>
                </a>
                <a class="actionlink popup-close" href="#">
                    <?php echo Translator::translate('Close')?>
                </a>
              </td>
            </tr>
            <tr>
              <td class="sublabel" colspan="4">
                <?php echo Translator::translate('SearchSave')?>
              </td>
            </tr>
<?php
if ($blnSave && $strSearchName) {
    ?>
            <tr>
              <td colspan="4">
                <?php echo Translator::translate('SearchSaved')?>
              </td>
            </tr>
    <?php
}
?>
            <tr>
              <td class="label">
                <?php echo Translator::translate('SearchName')?>
              </td>
              <td class="field"><input class="medium" type="text"
                name="searchname" value="<?php echo $strSearchName?>">
              </td>
              <td colspan="2">
                <input type="hidden" name="save_x" value="0">
                <a class="actionlink form-submit" href="#" data-set-field="save_x">
                <?php echo Translator::translate('SaveSearch')?>
                </a>
              </td>
            </tr>
          </tbody>
        </table>
      </form>
    </div>
  </div>
</body>
</html>
