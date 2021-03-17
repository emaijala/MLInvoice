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
    $blnShowSearch = false;
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
        $blnShowSearch = true;
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
        break;

    default:
        $blnShowSearch = 'import_statement' !== $strFunc;
        $strFormName = 'invoice';
        if ($strFunc != 'archived_invoices' && $strFunc != 'import_statement') {
            $strNewButton = '<a role="button" class="btn btn-secondary" href="?func=invoices&amp;form=invoice">' .
                 Translator::translate('NewInvoice') . '</a>';
            $strNewButton .= '<a role="button" class="btn btn-secondary" href="?func=invoices&amp;form=invoice&amp;offer=1">' .
                Translator::translate('NewOffer') . '</a>';
        }
        break;
    }

    ?>
<script>
  function openSearchWindow(mode, event) {
      x = event.screenX;
      y = event.screenY;
      if( mode == 'ext' ) {
          strLink = 'ext_search.php?func=<?php echo $strFunc?>&form=<?php echo $strFormName?>';
          strLink = strLink + '<?php echo $strExtSearchTerm?>';
          height = '400';
          width = '600';
          windowname = 'ext';
      }
      if( mode == 'quick' ) {
          strLink = 'quick_search.php?func=<?php echo $strFunc?>';
          height = '400';
          width = '250';
          windowname = 'quicksearch';
      }

      var win = window.open(
          strLink, windowname,
          'height='+height+',width='+width+',screenX=' + x + ',screenY=' + y
          + ',left=' + x + ',top=' + y
          + ',menubar=no,scrollbars=yes,status=no,toolbar=no'
      );
      win.focus();

      return true;
  }
</script>
    <?php
    if ($blnShowSearch || $strNewButton) {
        ?>
        <?php
        if ($blnShowSearch) {
            ?>
            <div class="btn-set">
                <a role="button" class="btn btn-secondary" href="#" onClick="openSearchWindow('ext', event); return false;">
                    <?php echo Translator::translate('ExtSearch')?>
                </a>
                <a role="button" class="btn btn-secondary" href="#" onClick="openSearchWindow('quick', event); return false;">
                    <?php echo Translator::translate('QuickSearch')?>
                </a>
            </div>
            <?php
        }
        if (sesWriteAccess() && $strNewButton) {
            echo "<div class=\"btn-set\">$strNewButton</div>\n";
        }
        ?>
        <?php
    }
}

/**
 * Update navigation history in session
 *
 * @param string $title Entry title
 * @param string $url   Entry url
 * @param int    $level Entry level
 *
 * @return Updated history
 */
function updateNavigationHistory($title, $url, $level)
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
        'title' => $title,
        'url' => $url,
        'level' => $level,
        'active' => true,
    ];
    Memory::set('history', $arrNew);

    return $arrNew;
}
