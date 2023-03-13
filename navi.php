<?php
/**
 * Navigation menu
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
require_once 'sqlfuncs.php';
require_once 'sessionfuncs.php';
require_once 'miscfuncs.php';
require_once 'memory.php';

/**
 * Create a function menu
 *
 * @param string $strFunc Function
 *
 * @return void
 */
function createFuncMenu($strFunc)
{
    $strHiddenTerm = '';
    $strNewButton = '';
    $strFormName = '';
    $strExtSearchTerm = '';
    $searchType = '';
    switch ($strFunc) {
    case 'system':
        $strNewText = '';
        $strList = getPostOrQuery('list', '');
        switch ($strList) {
        case 'user':
            $strNewText = Translator::translate('NewUser');
            break;
        case 'session_type':
            $strNewText = Translator::translate('NewSessionType');
            break;
        case 'invoice_state':
        case 'invoice_type':
        case 'row_type':
        case 'delivery_terms':
        case 'delivery_method':
        case 'print_template':
            $strNewText = Translator::translate('AddNew');
            break;
        }
        if ($strNewText) {
            $strNewButton = "<a role=\"button\" class=\"btn btn-secondary new_button\""
                . " href=\"?func=system&amp;list=$strList&amp;form=$strList\">$strNewText</a>";
        }
        break;

    case 'settings':
        $strNewText = '';
        $form = getPostOrQuery('form', '');
        if (!$form) {
            $strList = getPostOrQuery('list', '');
            switch ($strList) {
            case 'base':
                $strNewText = Translator::translate('NewBase');
                break;
            case 'product':
                $strNewText = Translator::translate('NewProduct');
                break;
            case 'default_value':
                $strNewText = Translator::translate('NewDefaultValue');
                break;
            case 'attachment':
                $strNewText = Translator::translate('NewAttachment');
                break;
            }
            if ($strNewText) {
                $strNewButton = '<a role="button" class="btn btn-secondary" '
                    . "href=\"?func=settings&amp;list=$strList&amp;form=$strList\">$strNewText</a>";
            }
        }
        break;

    case 'company':
        $searchType = 'company';
        $strOpenForm = 'company';
        $strFormName = 'company';
        $strFormSwitch = 'company';
        $strNewButton = '<a role="button" class="btn btn-secondary" href="?func=company&amp;form=company">'
            . Translator::translate('NewClient') . '</a>';
        break;

    case 'profile':
    case 'accounting_report':
    case 'invoice_report':
    case 'product_report':
    case 'product_stock_report':
    case 'search':
    case 'edit_searches':
        break;

    default:
        $searchType = 'import_statement' === $strFunc ? '' : 'invoice';
        $strFormName = 'invoice';
        if ($strFunc != 'archived_invoices' && $strFunc != 'import_statement') {
            $strNewButton = '<a role="button" class="btn btn-secondary" href="?func=invoices&amp;form=invoice">' .
                 Translator::translate('NewInvoice') . '</a>';
            $strNewButton .= '<a role="button" class="btn btn-secondary" href="?func=invoices&amp;form=invoice&amp;offer=1">' .
                Translator::translate('NewOffer') . '</a>';
        }
        break;
    }

    if ('results' === $strFunc) {
        $searchType = getQuery('type', 'invoice');
        $edit = preg_replace('/([\?&]func=)results/', "$1search", '?' . $_SERVER['QUERY_STRING']);
        $save = getSearchParamsFromRequest() ? ('?' . $_SERVER['QUERY_STRING'] . '&save=1') : false;
        ?>
        <div class="btn-set">
            <a role="button" class="btn btn-secondary" href="<?php echo htmlentities($edit) ?>">
                <?php echo Translator::translate('EditSearch')?>
            </a>
            <?php if ($save) { ?>
                <a role="button" class="btn btn-secondary dropdown-toggle" data-bs-toggle="dropdown" href="#">
                    <?php echo Translator::translate('SaveSearch')?>
                </a>
            <?php } ?>
            <div class="dropdown-menu">
                <form class="px-4 py-3">
                    <div class="mb-3">
                        <label for="search_name" class="form-label">
                            <?php echo Translator::translate('SearchName')?>
                        </label>
                        <input type="text" class="form-control" id="search_name" name="name">
                    </div>
                    <button type="submit" class="btn btn-primary" data-save-search>
                        <?php echo Translator::translate('Save')?>
                    </button>
                </form>
            </div>
            <a role="button" class="btn btn-secondary" href="?func=search&amp;type=<?php echo $searchType?>">
                <?php echo Translator::translate('NewSearch')?>
            </a>
            <?php createQuickSearchButton($searchType) ?>
        </div>
        <?php
    } elseif ($searchType) {
        ?>
        <div class="btn-set">
            <a role="button" class="btn btn-secondary" href="?func=search&amp;type=<?php echo $searchType?>">
                <?php echo Translator::translate('ExtSearch')?>
            </a>
            <?php createQuickSearchButton($searchType) ?>
        </div>
        <?php
    }
    if (sesWriteAccess() && 'start_page' === $strFunc) {
        ?>
        <div class="btn-set">
            <a role="button" class="btn btn-secondary" href="?func=import_statement">
                <?php echo Translator::translate('ImportAccountStatement')?>
            </a>
        </div>
        <?php
    }
    if (sesWriteAccess() && $strNewButton) {
        echo "<div class=\"btn-set\">$strNewButton</div>\n";
    }
}

/**
 * Update navigation history in session
 *
 * @param string $func  Function
 * @param string $title Entry title
 * @param string $url   Entry url
 * @param int    $level Entry level
 *
 * @return Updated history
 */
function updateNavigationHistory($func, $title, $url, $level)
{
    $arrNew = [];
    $history = Memory::get('history') ?: [];
    foreach ($history as $item) {
        if ($item['level'] < $level) {
            $item['active'] = false;
            $arrNew[] = $item;
        }
    }
    $arrNew[] = [
        'func' => $func,
        'title' => $title,
        'url' => $url,
        'level' => $level,
        'active' => true,
    ];
    Memory::set('history', $arrNew);

    return $arrNew;
}

/**
 * Create a quick search menu button
 *
 * @param string $type Search type
 *
 * @return void
 */
function createQuickSearchButton(string $type): void
{
    $searches = getQuickSearches($type);
    ?>
    <div class="dropdown">
        <a id="quick-search-menu" role="button" class="btn btn-secondary dropdown-toggle" href="#" data-bs-toggle="dropdown" aria-expanded="false">
            <?php echo Translator::translate('QuickSearch')?>
        </a>
        <ul class="dropdown-menu" aria-labelledby="quick-search-menu">
            <?php if (!$searches) { ?>
                <div class="m-2"><?php echo Translator::translate('NoSavedSearches')?></div>
            <?php } else { ?>
                <?php foreach ($searches as $search) { ?>
                    <li>
                        <a class="dropdown-item" href="?func=results&amp;search_id=<?php echo $search['id']?>">
                            <?php echo htmlentities($search['name']) ?>
                        </a>
                    </li>
                <?php } ?>
                <li>
                    <a class="dropdown-item" href="?func=edit_searches&amp;type=<?php echo htmlentities($type)?>">
                        <?php echo Translator::translate('EditSearches') ?>
                    </a>
                </li>
            <?php } ?>
        </ul>
    </div>
    <?php
}
