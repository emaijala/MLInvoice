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
require_once 'sessionfuncs.php';
require_once 'miscfuncs.php';
require_once 'datefuncs.php';

sesVerifySession();

require_once 'translator.php';

$strFunc = getRequest('func', '');
$strForm = getRequest('form', '');
if ($strFunc == 'open_invoices')
    $strFunc = 'invoices';
$strList = $strFunc;

$blnSearch = getPost('search_x', FALSE) ? TRUE : FALSE;
$blnSave = getPost('save_x', FALSE) ? TRUE : FALSE;
$strFields = getPost('fields', FALSE);
$strSearchField = getPost('searchfield', FALSE);
$strSearchName = getPost('searchname', '');
if ($strSearchField !== FALSE) {
    if ($strFields === FALSE) {
        $strFields = $strSearchField;
    } else {
        $strFields .= ",$strSearchField";
    }
}

if ($strFields !== FALSE) {
    $astrSelectedFields = explode(',', $strFields);
} else {
    $astrSelectedFields = [];
}

require 'form_switch.php';

for ($j = 0; $j < count($astrSelectedFields); $j ++) {
    $tmpDelete = getPost('delete_' . $astrSelectedFields[$j] . '_x', FALSE);
    if ($tmpDelete) {
        $astrSelectedFields[$j] = '';
    }
}

$strFields = implode(',', $astrSelectedFields);

for ($j = 0; $j < count($astrFormElements); $j ++) {
    if ($astrFormElements[$j]['type'] == 'RESULT' &&
         $astrFormElements[$j]['name'] != '') {
        $astrFormElements[$j]['type'] = 'TEXT';
    }
}

$listValues = [];
for ($j = 0; $j < count($astrFormElements); $j ++) {
    if ($astrFormElements[$j]['type'] != '' &&
        $astrFormElements[$j]['type'] != 'LABEL' &&
        $astrFormElements[$j]['type'] != 'HID_INT' &&
        $astrFormElements[$j]['type'] != 'IFORM' &&
        $astrFormElements[$j]['type'] != 'BUTTON' &&
        $astrFormElements[$j]['type'] != 'JSBUTTON' &&
        $astrFormElements[$j]['type'] != 'DROPDOWNMENU' &&
        !in_array($astrFormElements[$j]['name'], $astrSelectedFields, true)
    ) {
        $listValues[$astrFormElements[$j]['name']] = str_replace('<br>', ' ',
            Translator::translate($astrFormElements[$j]['label']));
    }
    $strControlType = $astrFormElements[$j]['type'];
    $strControlName = $astrFormElements[$j]['name'];

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
    for ($j = 0; $j < count($astrFormElements); $j ++) {
        $elem = $astrFormElements[$j];
        $name = $elem['name'];
        if (in_array($name, $astrSelectedFields, true)) {
            $strSearchOperator = getPost("operator_$name", '');
            if ($strSearchOperator)
                $strSearchOperator = " $strSearchOperator ";
            $strSearchMatch = getPost("searchmatch_$name", '=');

            // do LIKE || NOT LIKE search to elements with text or varchar datatype
            if ($elem['type'] == 'TEXT' || $elem['type'] == 'AREA') {
                if ($strSearchMatch == '=') {
                    $strSearchMatch = 'LIKE';
                } else {
                    $strSearchMatch = 'NOT LIKE';
                }
                $strSearchValue = "'%" . addcslashes($astrValues[$name], "'\\") . "%'";
            } elseif ($astrFormElements[$j]['type'] == 'INT'
                || $astrFormElements[$j]['type'] == 'LIST'
                || $astrFormElements[$j]['type'] == 'SELECT'
                || $astrFormElements[$j]['type'] == 'SEARCHLIST'
                || $astrFormElements[$j]['type'] == 'TAGS'
            ) {
                $strSearchValue = $astrValues[$name];
            } elseif ($astrFormElements[$j]['type'] == 'CHECK') {
                $strSearchValue = $astrValues[$name] ? 1 : 0;
            } elseif ($astrFormElements[$j]['type'] == 'INTDATE') {
                $strSearchValue = dateConvDate2DBDate($astrValues[$name]);
            }
            if ($strSearchValue) {
                $strWhereClause .= "$strSearchOperator$strListTableAlias$name $strSearchMatch $strSearchValue";
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
        db_param_query(
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
                            <th class="sublabel">&nbsp;

                            </th>
                            <th class="sublabel">
    <?php echo Translator::translate('SearchTerm')?>






                            </th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
<?php

$fieldCount = 0;
for ($j = 0; $j < count($astrFormElements); $j ++) {
    if (in_array($astrFormElements[$j]['name'], $astrSelectedFields, true)) {
        $strSearchMatch = getPost('searchmatch_' . $astrFormElements[$j]['name'],
            '=');
        if ($astrFormElements[$j]['style'] == 'xxlong') {
            $astrFormElements[$j]['style'] = 'long';
        }

        if (++$fieldCount > 1) {
            $strSelectedOperator = getPost(
                'operator_' . $astrFormElements[$j]['name'], 'AND');
            $strOperator = htmlListBox('operator_' . $astrFormElements[$j]['name'],
                [
                    'AND' => Translator::translate('SearchAND'),
                    'OR' => Translator::translate('SearchOR')
                ], $strSelectedOperator);
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
    <?php echo Translator::translate($astrFormElements[$j]['label'])?>
  </td>
                            <td class="field">
    <?php echo htmlListBox('searchmatch_' . $astrFormElements[$j]['name'], $comparisonValues, $strSearchMatch, '', 0)?>
  </td>
                            <td class="field">
    <?php echo htmlFormElement($astrFormElements[$j]['name'], $astrFormElements[$j]['type'], $astrValues[$astrFormElements[$j]['name']], $astrFormElements[$j]['style'], $astrFormElements[$j]['listquery'], 'MODIFY', $astrFormElements[$j]['parent_key'], '', [], '', isset($astrFormElements[$j]['options']) ? $astrFormElements[$j]['options'] : NULL)?>
  </td>
                            <td><input type="hidden"
                                name="delete_<?php echo $astrFormElements[$j]['name']?>_x"
                                value="0"> <a class="tinyactionlink" href="#"
                                title="<?php echo Translator::translate('DelRow')?>"
                                onclick="self.document.forms[0].delete_<?php echo $astrFormElements[$j]['name']?>_x.value=1; self.document.forms[0].submit(); return false;">
                                    X </a></td>
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
                                <input type="hidden" name="search_x" value="0"> <a
                                class="actionlink" href="#"
                                onclick="self.document.forms[0].search_x.value=1; self.document.forms[0].submit(); return false;"><?php echo Translator::translate('Search')?></a>
                                <a class="actionlink" href="#"
                                onclick="self.close(); return false;"><?php echo Translator::translate('Close')?></a>
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
                                name="searchname" value="<?php echo $strSearchName?>"></td>
                            <td colspan="2"><input type="hidden" name="save_x" value="0"> <a
                                class="actionlink" href="#"
                                onclick="self.document.forms[0].save_x.value=1; self.document.forms[0].submit(); return false;"><?php echo Translator::translate('SaveSearch')?></a>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </form>
        </div>
    </div>
</body>
</html>
