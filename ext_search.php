<?php
/**
 * Extended search
 *
 * PHP version 7
 *
 * Copyright (C) Samu Reinikainen 2004-2008
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

$strFunc = getPostOrQuery('func', '');
$strForm = getPostOrQuery('form', '');
if ($strFunc == 'open_invoices') {
    $strFunc = 'invoices';
}
$strList = getListFromFunc($strFunc);

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

$formConfig = getFormConfig($strForm, 'ext_search');

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
        && $field['type'] != 'HEADING'
        && $field['type'] != 'LABEL'
        && $field['type'] != 'HID_INT'
        && $field['type'] != 'HID_UUID'
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
    } elseif ($strControlType != 'LABEL' && $strControlType != 'HEADING') {
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
  <div class="container-fluid">
    <div class="form">
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
                    $field['options'] ?? null
                );
                ?>
              </td>
              <td>
                <?php
                    $inputName = 'delete_'
                    . $field['name'] . '_x';
                ?>
                <input type="hidden" name="<?php echo $inputName?>"value="0">
                <button type="button" class="btn btn-secondary btn-small form-submit" title="<?php echo Translator::translate('DelRow')?>"
                  data-set-field="<?php echo $inputName?>">X
                </button>
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
              <td colspan="4" class="ext-search-buttons">
                <input type="hidden" name="search_x" value="0">
                <button type="button" class="btn btn-secondary form-submit" data-set-field="search_x">
                    <?php echo Translator::translate('Search')?>
                </button>
                <button type="button" class="btn btn-secondary popup-close">
                    <?php echo Translator::translate('Close')?>
                </button>
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
              <td class="field"><input class="form-control medium" type="text"
                name="searchname" value="<?php echo $strSearchName?>">
              </td>
              <td colspan="2">
                <input type="hidden" name="save_x" value="0">
                <button type="button" class="btn btn-primary form-submit" data-set-field="save_x">
                  <?php echo Translator::translate('SaveSearch')?>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </form>
    </div>
  </div>
  <script>
    MLInvoice.Form.setupSelect2();
  </script>
</body>
</html>
