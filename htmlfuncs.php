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

require_once 'list.php';
require_once 'settings.php';
require_once 'sqlfuncs.php';

function htmlPageStart($strTitle = '', $arrExtraScripts = [])
{
    // These are to prevent browser & proxy caching
    // HTTP/1.1
    header('Cache-Control: no-store, no-cache, must-revalidate');
    header('Cache-Control: post-check=0, pre-check=0', false);
    // Date in the past
    header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
    // always modified
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');

    $charset = (_CHARSET_ == 'UTF-8') ? 'UTF-8' : 'ISO-8859-15';
    if (isset($_SERVER['HTTP_USER_AGENT']) &&
         strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== false)
        $xUACompatible = "  <meta http-equiv=\"X-UA-Compatible\" content=\"IE=edge\">\n";
    else
        $xUACompatible = '';
    $theme = defined('_UI_THEME_LOCATION_') ? _UI_THEME_LOCATION_ : 'jquery/css/theme/jquery-ui.min.css';
    $lang = isset($_SESSION['sesLANG']) ? $_SESSION['sesLANG'] : 'fi-FI';
    $datePickerOptions = Translator::translate('DatePickerOptions');

    $scripts = [
        'jquery/js/jquery-2.2.4.min.js',
        'jquery/js/jquery.json-2.3.min.js',
        'jquery/js/jquery.cookie.js',
        'jquery/js/jquery-ui.min.js',
        'datatables/jquery.dataTables.min.js',
        'jquery/js/jquery.floatingmessage.js',
        'js/date.js',
        "js/date-$lang.js",
        'jquery/js/jquery.daterangepicker.js',
        'js/mlinvoice.js',
        'js/functions.js',
        'select2/select2.min.js'
    ];

    if (file_exists("select2/select2_locale_$lang.js")) {
        $scripts[] = "select2/select2_locale_$lang.js";
    }

    $scripts = array_merge($scripts, $arrExtraScripts);
    foreach ($scripts as &$script) {
        $script = '  <script type="text/javascript" src="'
            . addFileTimestamp($script) . '"></script>';
    }
    $scriptLinks = implode("\n", $scripts);

    $css = [
        $theme,
        'jquery/css/ui.daterangepicker.css',
        'datatables/buttons.dataTables.min.css',
        'select2/select2.css',
        'css/style.css'
    ];

    foreach ($css as &$style) {
        $style = '  <link rel="stylesheet" type="text/css" href="'
            . addFileTimestamp($style) . '">';
    }
    $cssLinks = implode("\n", $css);

    $favicon = addFileTimestamp('favicon.ico');

    $strTitle = $strTitle ? _PAGE_TITLE_ . " - $strTitle" : _PAGE_TITLE_;

    $translations = [
        'DecimalSeparator',
        'InvoiceDateNonCurrent',
        'InvoiceNumberNotDefined',
        'InvoiceRefNumberTooShort',
        'InvoicesTotal',
        'NoYTJResultsFound',
        'SearchYTJPrompt',
        'SettingDispatchNotes',
        'ThousandSeparator',
        'ForSelected',
        'Delete',
        'Modify',
        'ModifySelectedRows',
        'Modified',
        'ProductWeight',
        'FutureDateEntered',
        'RecordSaved',
        'RecordDeleted',
        'ConfirmDelete'
    ];

    $res = mysqli_query_check(
        'SELECT name FROM {prefix}invoice_state WHERE deleted=0'
    );
    while ($row = mysqli_fetch_value($res)) {
        $translations[] = $row;
    }

    if (getSetting('check_updates')) {
        $translations = array_merge(
            $translations,
            [
                'UpdateAvailable',
                'UpdateAvailableTitle',
                'UpdateInformation',
                'UpdateNow'
            ]
        );
    }

    $jsTranslations = [];
    foreach ($translations as $translation) {
        $translated = Translator::translate($translation);
        if ($translated != $translation) {
            $jsTranslations[$translation] = $translated;
        }
    }

    $jsTranslations = json_encode($jsTranslations);

    $dispatchNotePrintStyle = 'none';
    $res = mysqli_query_check('SELECT * FROM {prefix}print_template WHERE id=2');
    if ($row = mysqli_fetch_assoc($res)) {
        if (!$row['deleted'] && !$row['inactive']) {
            $dispatchNotePrintStyle = $row['new_window'] ? 'openwindow' : 'redirect';
        }
    }

    $res = mysqli_query_check(
        'SELECT id FROM {prefix}invoice_state WHERE invoice_offer=1'
    );
    while ($row = mysqli_fetch_assoc($res)) {
        $offerStatuses[] = $row['id'];
    }
    $offerStatuses = json_encode($offerStatuses);

    $keepAlive = getSetting('session_keepalive') ? 'true' : 'false';
    $lang = Translator::translate('HTMLLanguageCode');
    $currencyDecimals = getSetting('unit_price_decimals');

    $strHtmlStart = <<<EOT
<!DOCTYPE html>
<html lang="$lang">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=$charset">
$xUACompatible  <title>$strTitle</title>
  <link rel="shortcut icon" href="$favicon" type="image/x-icon">
$cssLinks
$scriptLinks
  <script type="text/javascript">
MLInvoice.addTranslations($jsTranslations);
MLInvoice.setDispatchNotePrintStyle('$dispatchNotePrintStyle');
MLInvoice.setOfferStatuses($offerStatuses);
MLInvoice.setKeepAlive($keepAlive);
MLInvoice.setCurrencyDecimals($currencyDecimals);
$(document).ready(function() {
  $.datepicker.setDefaults($datePickerOptions);
  $('a.actionlink').not('.ui-state-disabled').button();
  $('a.tinyactionlink').button();
  $('a.buttonlink').button();
  $('a.formbuttonlink').button();
  $('#maintabs ul li').hover(
    function () {
      $(this).addClass('ui-state-hover');
    },
    function () {
      $(this).removeClass('ui-state-hover');
    }
  );
});
  </script>
</head>
EOT;

    return $strHtmlStart;
}

/**
 * Create main tabs
 *
 * @param string $func Current function
 *
 * @return void
 */
function htmlMainTabs($func)
{
    $normalMenuRights = [
        ROLE_READONLY,
        ROLE_USER,
        ROLE_BACKUPMGR
    ];
    $astrMainButtons = [
        [
            'name' => 'invoice',
            'title' => 'ShowInvoiceNavi',
            'action' => 'open_invoices',
            'levels_allowed' => [
                ROLE_READONLY,
                ROLE_USER,
                ROLE_BACKUPMGR
            ]
        ],
        [
            'name' => 'archive',
            'title' => 'ShowArchiveNavi',
            'action' => 'archived_invoices',
            'levels_allowed' => [
                ROLE_READONLY,
                ROLE_USER,
                ROLE_BACKUPMGR
            ]
        ],
        [
            'name' => 'company',
            'title' => 'ShowClientNavi',
            'action' => 'companies',
            'levels_allowed' => [
                ROLE_USER,
                ROLE_BACKUPMGR
            ]
        ],
        [
            'name' => 'reports',
            'title' => 'ShowReportNavi',
            'action' => 'reports',
            'levels_allowed' => [
                ROLE_READONLY,
                ROLE_USER,
                ROLE_BACKUPMGR
            ]
        ],
        [
            'name' => 'settings',
            'title' => 'ShowSettingsNavi',
            'action' => 'settings',
            'action' => 'settings',
            'levels_allowed' => [
                ROLE_USER,
                ROLE_BACKUPMGR
            ]
        ],
        [
            'name' => 'system',
            'title' => 'ShowSystemNavi',
            'action' => 'system',
            'levels_allowed' => [
                ROLE_BACKUPMGR,
                ROLE_ADMIN
            ]
        ],
        [
            'name' => 'logout',
            'title' => 'Logout',
            'action' => 'logout',
            'levels_allowed' => null
        ]
    ];

?>
            <div id="maintabs" class="navi ui-widget-header ui-tabs">
                <ul class="ui-tabs-nav ui-helper-clearfix ui-corner-all">
<?php
foreach ($astrMainButtons as $button) {
    $strButton = '<li class="functionlink ui-state-default ui-corner-top';
    if ($button['action'] == $func
        || ($button['action'] == 'open_invoices' && $func == 'invoices')
    ) {
        $strButton .= ' ui-tabs-selected ui-state-active';
    }
    $strButton .= '"><a class="ui-tabs-anchor functionlink" href="index.php?func='
        . $button['action'] . '">';
    $strButton .= Translator::translate($button['title']) . '</a></li>';

    if (!isset($button['levels_allowed'])
        ||  sesAccessLevel($button['levels_allowed']) || sesAdminAccess()
    ) {
        echo "      $strButton\n";
    }
}
?>
                </ul>
            </div>
<?php
}

function htmlListBox($strName, $astrValues, $strSelected, $strStyle = '',
    $blnSubmitOnChange = FALSE, $blnShowEmpty = TRUE, $astrAdditionalAttributes = '',
    $translate = false)
{
    /********************************************************************
     Function : htmlListBox
     Create Html-listbox

     Args :
     $strName (string): listbox name
     $astrValues (stringarray): listbox values => descriptions
     $strSelected (string): selected value

     Return : $strListBox (string) : listbox element

     Todo :
     ********************************************************************/
    $strOnChange = '';
    if ($blnSubmitOnChange) {
        $strOnChange = " onchange='this.form.submit();'";
    }
    if ($astrAdditionalAttributes)
        $astrAdditionalAttributes = " $astrAdditionalAttributes";
    $strListBox = "<select class=\"$strStyle\" id=\"$strName\" name=\"$strName\"{$strOnChange}{$astrAdditionalAttributes}>\n";
    if ($blnShowEmpty) {
        $strListBox .= '<option value=""' . ($strSelected ? '' : ' selected') .
             "> - </option>\n";
    }

    foreach ($astrValues as $value => $desc) {
        $strSelect = $strSelected == $value ? ' selected' : '';
        if ($translate) {
            $desc = Translator::translate($desc);
        }
        $strListBox .= '<option value="' . htmlspecialchars($value) . "\"$strSelect>" .
             htmlspecialchars($desc) . "</option>\n";
    }
    $strListBox .= "</select>\n";

    return $strListBox;
}

function htmlSQLListBox($strName, $strQuery, $strSelected, $strStyle = '',
    $blnSubmitOnChange = FALSE, $astrAdditionalAttributes = '', $translate = false)
{
    $astrValues = [];
    $intRes = mysqli_query_check($strQuery);
    while ($row = mysqli_fetch_row($intRes)) {
        $astrValues[$row[0]] = $row[1];
    }
    $showEmpty = TRUE;
    if (strstr($strStyle, ' noemptyvalue')) {
        $strStyle = str_replace(' noemptyvalue', '', $strStyle);
        $showEmpty = FALSE;
    }
    $strListBox = htmlListBox($strName, $astrValues, $strSelected, $strStyle,
        $blnSubmitOnChange, $showEmpty, $astrAdditionalAttributes, $translate);

    return $strListBox;
}

// Get the value for the specified option
function getSQLListBoxSelectedValue($strQuery, $strSelected)
{
    $intRes = mysqli_query_check($strQuery);
    while ($row = mysqli_fetch_row($intRes)) {
        if ($row[0] == $strSelected)
            return $row[1];
    }
    return '';
}

// Get the value for the specified option
function getSearchListSelectedValue($strQuery, $strSelected)
{
    parse_str($strQuery, $params);
    $result = json_decode(
        createJSONSelectList($params['table'], 0, 1, '', '', $strSelected), true
    );
    return isset($result['records'][0]['text']) ? $result['records'][0]['text'] : '';
}

// Get the value for the specified option
function getListBoxSelectedValue($options, $selected)
{
    if (isset($options[$selected]))
        return $options[$selected];
    return '';
}

// Create form element
function htmlFormElement($strName, $strType, $strValue, $strStyle, $strListQuery = '',
    $strMode = 'MODIFY', $strParentKey = NULL, $strTitle = '', $astrDefaults = [],
    $astrAdditionalAttributes = '', $options = NULL)
{
    if ($astrAdditionalAttributes)
        $astrAdditionalAttributes = " $astrAdditionalAttributes";
    $strFormElement = '';
    $readOnly = $strMode == 'MODIFY' ? '' : ' readonly="readonly"';
    $disabled = $strMode == 'MODIFY' ? '' : ' disabled="disabled"';

    switch ($strType) {
    case 'TEXT':
        if (strstr($strStyle, 'hasDateRangePicker')) {
            $autocomplete = ' autocomplete="off"';
        } else {
            $autocomplete = '';
        }

        $strFormElement = "<input type=\"text\" class=\"$strStyle\"$autocomplete " .
             "id=\"$strName\" name=\"$strName\" value=\"" .
             htmlspecialchars($strValue) . "\"$astrAdditionalAttributes$readOnly>\n";
        break;

    case 'PASSWD':
        $strFormElement = "<input type=\"password\" class=\"$strStyle\" " .
             "id=\"$strName\" name=\"$strName\" value=\"\"$astrAdditionalAttributes$readOnly>\n";
        break;

    case 'CHECK':
        $strValue = $strValue ? 'checked' : '';
        $strFormElement = "<input type=\"checkbox\" id=\"$strName\" name=\"$strName\" value=\"1\" " .
             htmlspecialchars($strValue) . "$astrAdditionalAttributes$disabled>\n";
        break;

    case 'RADIO':
        $strChecked = $strValue ? 'checked' : '';
        $strFormElement = "<input type=\"radio\" id=\"$strName\" name=\"$strName\" value=\"" .
             htmlspecialchars($strValue) . "\"$astrAdditionalAttributes$disabled>\n";
        break;

    case 'INT':
        $hideZero = FALSE;
        if (strstr($strStyle, ' hidezerovalue')) {
            $strStyle = str_replace(' hidezerovalue', '', $strStyle);
            $hideZero = TRUE;
        }
        if ($hideZero && $strValue == 0)
            $strValue = '';
        $strFormElement = "<input type=\"text\" class=\"$strStyle\" " .
             "id=\"$strName\" name=\"$strName\" value=\"" .
             htmlspecialchars($strValue) . "\"$astrAdditionalAttributes$readOnly>\n";
        break;

    case 'INTDATE':
        $strFormElement = "<input type=\"text\" class=\"$strStyle hasCalendar\" " .
             "id=\"$strName\" name=\"$strName\" value=\"" .
             htmlspecialchars($strValue) . "\"$astrAdditionalAttributes$readOnly>\n";
        break;

    case 'HID_INT':
    case 'HID_UUID':
        $strFormElement = '<input type="hidden" ' .
             "id=\"$strName\" name=\"$strName\" value=\"" .
             htmlspecialchars($strValue) . "\">\n";
        break;

    case 'AREA':
        $strFormElement = '<textarea rows="24" cols="80" class="' . $strStyle . '" ' .
             'id="' . $strName . '" name="' . $strName .
             "\"$astrAdditionalAttributes$readOnly>" . $strValue . "</textarea>\n";
        break;

    case 'RESULT':
        $strListQuery = str_replace('_ID_', $strValue, $strListQuery);
        $res = mysqli_query_check($strListQuery);
        $strFormElement = htmlspecialchars(
            mysqli_fetch_value($res)
        ) . "\n";
        break;

    case 'LIST':
        $translate = false;
        if (strstr($strStyle, ' translated')) {
            $translate = true;
            $strStyle = str_replace(' translated', '', $strStyle);
        }

        if ($strMode == 'MODIFY') {
            if (is_array($strListQuery)) {
                $showEmpty = true;
                if (strstr($strStyle, ' noemptyvalue')) {
                    $showEmpty = false;
                    $strStyle = str_replace(' noemptyvalue', '', $strStyle);
                }
                $strFormElement = htmlListBox(
                    $strName, $strListQuery, $strValue, $strStyle, false, $showEmpty,
                    $astrAdditionalAttributes, $translate
                );

            } else {
                $strFormElement = htmlSQLListBox($strName, $strListQuery, $strValue,
                    $strStyle, false, $astrAdditionalAttributes, $translate);
            }
        } else {
            $strFormElement = "<input type=\"text\" class=\"$strStyle\" " .
                 "id=\"$strName\" name=\"$strName\" value=\"" .
                 htmlspecialchars(
                    getSQLListBoxSelectedValue($strListQuery, $strValue, $translate)) .
                 "\"$astrAdditionalAttributes$readOnly>\n";
        }
        break;

    case 'SEARCHLIST':
        if ($strMode == 'MODIFY') {
            $showEmpty = '1';
            if (strstr($strStyle, ' noemptyvalue')) {
                $strStyle = str_replace(' noemptyvalue', '', $strStyle);
                $showEmpty = '0';
            }
            $strValue = htmlspecialchars($strValue);
            $onChange = $astrAdditionalAttributes ? trim($astrAdditionalAttributes) : '';
            $encodedQuery = htmlspecialchars($strListQuery);
            $strFormElement = <<<EOT
<input type="hidden" class="$strStyle select2" id="$strName" name="$strName" value="$strValue" data-query="$encodedQuery" data-show-empty="$showEmpty" data-on-change="$onChange"/>
EOT;
        } else {
            $strFormElement = "<input type=\"text\" class=\"$strStyle\" " .
                 "id=\"$strName\" name=\"$strName\" value=\"" .
                 htmlspecialchars(
                    getSearchListSelectedValue($strListQuery, $strValue, false)) .
                 "\"$astrAdditionalAttributes$readOnly>\n";
        }
        break;
    case 'SELECT':
        $translate = false;
        if (strstr($strStyle, ' translated')) {
            $translate = true;
            $strStyle = str_replace(' translated', '', $strStyle);
        }
        if ($strMode == 'MODIFY') {
            $strFormElement = htmlListBox($strName, $options, $strValue, $strStyle,
                false, $astrAdditionalAttributes, $translate);
        } else {
            $strFormElement = "<input type=\"text\" class=\"$strStyle\" " .
                 "id=\"$strName\" name=\"$strName\" value=\"" . htmlspecialchars(
                    getListBoxSelectedValue($options, $strValue, $translate)) .
                 "\"$astrAdditionalAttributes$readOnly>\n";
        }
        break;
    case 'TAGS':
        if ($strMode == 'MODIFY') {
            $showEmpty = '1';
            if (strstr($strStyle, 'noemptyvalue ')) {
                $strStyle = str_replace('noemptyvalue ', '', $strStyle);
                $showEmpty = '0';
            }
            $strValue = htmlspecialchars($strValue);
            $onChange = $astrAdditionalAttributes ? trim($astrAdditionalAttributes) : '';
            $encodedQuery = htmlspecialchars($strListQuery);
            $strFormElement = <<<EOT
<input type="hidden" class="$strStyle select2 tags" id="$strName" name="$strName" value="$strValue" data-query="$encodedQuery" data-show-empty="$showEmpty" data-on-change="$onChange"/>
EOT;
        } else {
            $strFormElement = "<input type=\"text\" class=\"$strStyle\" " .
                 "id=\"$strName\" name=\"$strName\" value=\"" .
                 htmlspecialchars($strValue) .
                 "\"$astrAdditionalAttributes$readOnly>\n";
        }
        break;

    case 'BUTTON':
        $strListQuery = str_replace('_ID_', $strValue, $strListQuery);
        switch ($strStyle) {
        case 'custom' :
            $strListQuery = str_replace("'", '', $strListQuery);
            $strHref = $strListQuery;
            $strOnClick = '';
            break;

        case 'redirect':
            $strHref = '#';
            $strOnClick = "onclick=\"save_record('$strListQuery', 'redirect'); return false;\"";
            break;

        case 'openwindow':
            $strHref = '#';
            $strOnClick = "onclick=\"save_record('$strListQuery', 'openwindow'); return false;\"";
            break;

        default:
            switch ($strStyle) {
            case 'tiny' :
                $strHW = 'height=1,width=1,';
                break;
            case 'small' :
                $strHW = 'height=200,width=200,';
                break;
            case 'medium' :
                $strHW = 'height=400,width=400,';
                break;
            case 'large' :
                $strHW = 'height=600,width=600,';
                break;
            case 'xlarge' :
                $strHW = 'height=800,width=650,';
                break;
            case 'full' :
                $strHW = '';
                break;
            default :
                $strHW = '';
                break;
            }
            $strHref = '#';
            $strOnClick = 'onclick="window.open(' . $strListQuery . ",'" . $strHW .
                 'menubar=no,scrollbars=no,' .
                 "status=no,toolbar=no'); return false;\"";
            break;
        }
        $strFormElement = "<a class=\"formbuttonlink\" href=\"$strHref\" $strOnClick$astrAdditionalAttributes>" .
             htmlspecialchars(Translator::translate($strTitle)) . "</a>\n";
        break;

    case 'JSBUTTON':
        if (strstr($strListQuery, '_ID_') && !$strValue) {
            $strFormElement = Translator::translate('SaveFirst');
        } else {
            if ($strValue)
                $strListQuery = str_replace('_ID_', $strValue, $strListQuery);
            $strOnClick = "onClick=\"$strListQuery\"";
            $strFormElement = "<a class=\"formbuttonlink\" href=\"#\" $strOnClick$astrAdditionalAttributes>" .
                 htmlspecialchars(Translator::translate($strTitle)) . "</a>\n";
        }
        break;

    case 'DROPDOWNMENU':
        if (strstr($strListQuery, '_ID_') && !$strValue) {
            $strFormElement = Translator::translate('SaveFirst');
        } else {
            $menuTitle = htmlspecialchars(Translator::translate($strTitle));
            $menuItems = '';
            foreach ($options as $option) {
                $strListQuery = str_replace('_ID_', $strValue, $option['listquery']);
                $menuItems .= '<li onClick="' . $strListQuery . '"><div>' . Translator::translate($option['label']) . '</div></li>';
            }
            $strFormElement = <<<EOT
<ul class="dropdownmenu" $astrAdditionalAttributes>
  <li>$menuTitle
    <ul>
      $menuItems
    </ul>
  </li>
</ul>
EOT;
        }
        break;

    case 'IMAGE':
        $strListQuery = str_replace('_ID_', $strValue, $strListQuery);
        $strFormElement = "<img class=\"$strStyle\" src=\"$strListQuery\" title=\"" .
             htmlspecialchars(Translator::translate($strTitle)) . "\"></div>\n";
        break;

    default :
        $strFormElement = "&nbsp;\n";
    }

    return $strFormElement;
}
