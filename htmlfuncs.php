<?php
/**
 * HTML functions
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
require_once 'list.php';
require_once 'settings.php';
require_once 'sqlfuncs.php';

/**
 * Create HTTP headers and HTML page start
 *
 * @param string $strTitle        Page title
 * @param array  $arrExtraScripts Extra scripts to add
 *
 * @return string HTML content
 */
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
    if (isset($_SERVER['HTTP_USER_AGENT'])
        && strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== false
    ) {
        $xUACompatible = "  <meta http-equiv=\"X-UA-Compatible\" content=\"IE=edge\">\n";
    } else {
        $xUACompatible = '';
    }
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
        'ConfirmDelete',
        'UnsavedData',
        'Close'
    ];

    $res = dbQueryCheck(
        'SELECT name FROM {prefix}invoice_state WHERE deleted=0'
    );
    while ($row = dbFetchValue($res)) {
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
    $res = dbQueryCheck('SELECT * FROM {prefix}print_template WHERE id=2');
    if ($row = mysqli_fetch_assoc($res)) {
        if (!$row['deleted'] && !$row['inactive']) {
            $dispatchNotePrintStyle = $row['new_window'] ? 'openwindow' : 'redirect';
        }
    }

    $res = dbQueryCheck(
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
  MLInvoice.init();
  $.datepicker.setDefaults($datePickerOptions);
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
    $user = !empty($_SESSION['sesUSERID']) ? getUserById($_SESSION['sesUSERID'])
        : [];
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
        || sesAccessLevel($button['levels_allowed']) || sesAdminAccess()
    ) {
        echo "      $strButton\n";
    }
}
?>
                <li id="profile-link">
                  <a href="index.php?func=profile">
                    <?php echo $user && $user['name'] ? $user['name'] : Translator::translate('Profile'); ?>
                  </a>
                </li>
              </ul>
            </div>
<?php
}

/**
 * Create Html-listbox
 *
 * @param string $strName                  Listbox name
 * @param array  $astrValues               Listbox values => descriptions
 * @param string $strSelected              Selected value
 * @param string $strStyle                 Style
 * @param bool   $blnSubmitOnChange        Whether to submit the form when value is
 *                                         changed
 * @param bool   $blnShowEmpty             Whether to show "empty" value
 * @param string $astrAdditionalAttributes Any additional attributes
 * @param bool   $translate                Whether the options are translated
 *
 * @return string HTML
 */
function htmlListBox($strName, $astrValues, $strSelected, $strStyle = '',
    $blnSubmitOnChange = false, $blnShowEmpty = true, $astrAdditionalAttributes = '',
    $translate = false
) {

    $strOnChange = '';
    if ($blnSubmitOnChange) {
        $strOnChange = " onchange='this.form.submit();'";
    }
    if ($astrAdditionalAttributes) {
        $astrAdditionalAttributes = " $astrAdditionalAttributes";
    }
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

/**
 * Create a listbox from an SQL query
 *
 * @param string $strName                  Element name
 * @param string $strQuery                 SQL query to get the list contents
 * @param string $strSelected              Selected value
 * @param string $strStyle                 CSS style
 * @param bool   $blnSubmitOnChange        Whether to submit the form when a value is
 *                                         selected
 * @param string $astrAdditionalAttributes Additional element attributes
 * @param bool   $translate                Whether to translate the choices
 *
 * @return string
 */
function htmlSQLListBox($strName, $strQuery, $strSelected, $strStyle = '',
    $blnSubmitOnChange = false, $astrAdditionalAttributes = '', $translate = false
) {

    $astrValues = [];
    $intRes = dbQueryCheck($strQuery);
    while ($row = mysqli_fetch_row($intRes)) {
        $astrValues[$row[0]] = $row[1];
    }
    $showEmpty = true;
    if (strstr($strStyle, ' noemptyvalue')) {
        $strStyle = str_replace(' noemptyvalue', '', $strStyle);
        $showEmpty = false;
    }
    $strListBox = htmlListBox(
        $strName, $astrValues, $strSelected, $strStyle,
        $blnSubmitOnChange, $showEmpty, $astrAdditionalAttributes, $translate
    );

    return $strListBox;
}

/**
 * Get the value for the specified option
 *
 * @param string $strQuery    SQL query
 * @param string $strSelected Selected option
 *
 * @return string
 */
function getSQLListBoxSelectedValue($strQuery, $strSelected)
{
    $intRes = dbQueryCheck($strQuery);
    while ($row = mysqli_fetch_row($intRes)) {
        if ($row[0] == $strSelected) {
            return $row[1];
        }
    }
    return '';
}

/**
 * Get the value for the specified option of a search list
 *
 * @param string $strQuery    SQL query
 * @param string $strSelected Selected option
 *
 * @return string
 */
function getSearchListSelectedValue($strQuery, $strSelected)
{
    parse_str($strQuery, $params);
    $result = json_decode(
        createJSONSelectList($params['table'], 0, 1, '', '', $strSelected), true
    );
    return isset($result['records'][0]['text']) ? $result['records'][0]['text'] : '';
}

/**
 * Get the value for the specified option
 *
 * @param array $options  Options
 * @param array $selected Selected option
 *
 * @return string
 */
function getListBoxSelectedValue($options, $selected)
{
    if (isset($options[$selected])) {
        return $options[$selected];
    }
    return '';
}

/**
 * Create a form element
 *
 * @param string $strName                  Element name
 * @param string $strType                  Element type
 * @param string $strValue                 Element value
 * @param string $strStyle                 Element style
 * @param string $strListQuery             Query for list element
 * @param string $strMode                  Edit mode
 * @param string $strParentKey             Parent record ID
 * @param string $strTitle                 Element title
 * @param array  $astrDefaults             Unused TODO: remove
 * @param array  $astrAdditionalAttributes Additional HTML attributes
 * @param array  $options                  Options for a listbox or drop-down menu
 *
 * @return string
 */
function htmlFormElement($strName, $strType, $strValue, $strStyle, $strListQuery = '',
    $strMode = 'MODIFY', $strParentKey = null, $strTitle = '', $astrDefaults = [],
    $astrAdditionalAttributes = '', $options = null
) {

    if ($astrAdditionalAttributes) {
        $astrAdditionalAttributes = " $astrAdditionalAttributes";
    }
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
        $hideZero = false;
        if (strstr($strStyle, ' hidezerovalue')) {
            $strStyle = str_replace(' hidezerovalue', '', $strStyle);
            $hideZero = true;
        }
        if ($hideZero && $strValue == 0) {
            $strValue = '';
        }
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
        $res = dbQueryCheck($strListQuery);
        $strFormElement = htmlspecialchars(
            dbFetchValue($res)
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
                $strFormElement = htmlSQLListBox(
                    $strName, $strListQuery, $strValue,
                    $strStyle, false, $astrAdditionalAttributes, $translate
                );
            }
        } else {
            $strFormElement = "<input type=\"text\" class=\"$strStyle\" " .
                 "id=\"$strName\" name=\"$strName\" value=\"" .
                htmlspecialchars(
                    getSQLListBoxSelectedValue($strListQuery, $strValue, $translate)
                ) .
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
                    getSearchListSelectedValue($strListQuery, $strValue, false)
                ) .
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
            $strFormElement = htmlListBox(
                $strName, $options, $strValue, $strStyle,
                false, $astrAdditionalAttributes, $translate
            );
        } else {
            $strFormElement = "<input type=\"text\" class=\"$strStyle\" " .
                "id=\"$strName\" name=\"$strName\" value=\"" . htmlspecialchars(
                    getListBoxSelectedValue($options, $strValue, $translate)
                ) .
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
            if ($strValue) {
                $strListQuery = str_replace('_ID_', $strValue, $strListQuery);
            }
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
