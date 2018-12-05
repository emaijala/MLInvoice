<?php
/**
 * List configuration
 *
 * PHP version 5
 *
 * Copyright (C) 2018 Ere Maijala
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
 * Get list configuration
 *
 * @param string $list List name
 *
 * @return array
 */
function getListConfig($list)
{
    $strList = $list;
    include 'list_switch.php';

    return $strTable ? [
        'title' => isset($locTitle) ? $locTitle : '',
        'accessLevels' => $levelsAllowed,
        'table' => $strTable,
        'displayJoin' => $strJoin,
        'countJoin' => isset($strCountJoin) ? $strCountJoin : null,
        'groupBy' => $strGroupBy,
        'listFilter' => $strListFilter,
        'primaryKey' => $strPrimaryKey,
        'deletedField' => $strDeletedField,
        'fields' => $astrShowFields,
        'searchFields' => isset($astrSearchFields) ? $astrSearchFields : null,
        'mainForm' => $strMainForm,
    ] : [];
}
