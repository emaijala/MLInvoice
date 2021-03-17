<?php
/**
 * Form configuration
 *
 * PHP version 7
 *
 * Copyright (C) Ere Maijala 2018-2021
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

/**
 * Get form configuration
 *
 * @param string $form Form name
 * @param string $func Current function
 *
 * @return array
 */
function getFormConfig($form, $func)
{
    $strForm = $form;
    $strFunc = $func;
    include 'form_switch.php';

    return [
        'type' => $strForm,
        'title' => isset($locTitle) ? $locTitle : '',
        'readOnly' => $readOnlyForm,
        'accessLevels' => $levelsAllowed,
        'table' => $strTable,
        'parentKey' => isset($strParentKey) ? $strParentKey : null,
        'tableAlias' => $strListTableAlias,
        'copyLink' => $copyLinkOverride,
        'extraButtons' => $extraButtons,
        'fields' => $astrFormElements,
        'dataAttrs' => $formDataAttrs,
        'searchFields' => isset($astrSearchFields) ? $astrSearchFields : null,
        'addressAutocomplete' => $addressAutocomplete,
        'clearAfterRowAdded' => $clearRowValuesAfterAdd,
        'onAfterRowAdded' => $onAfterRowAdded,
        'popupHTML' => isset($popupHTML) ? $popupHTML : '',
        'buttonGroups' => isset($buttonGroups) ? $buttonGroups : [],
    ];
}
