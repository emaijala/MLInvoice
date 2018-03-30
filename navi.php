<?php
/**
 * Navigation menu
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
        $astrNaviLinks = [
            [
                'href' => 'list=user',
                'text' => Translator::translate('Users'),
                'levels_allowed' => [
                    ROLE_ADMIN
                ]
            ],
            [
                'href' => 'list=invoice_state',
                'text' => Translator::translate('InvoiceStates'),
                'levels_allowed' => [
                    ROLE_ADMIN
                ]
            ],
            [
                'href' => 'list=row_type',
                'text' => Translator::translate('RowTypes'),
                'levels_allowed' => [
                    ROLE_ADMIN
                ]
            ],
            [
                'href' => 'list=delivery_terms',
                'text' => Translator::translate('DeliveryTerms'),
                'levels_allowed' => [
                    ROLE_ADMIN
                ]
            ],
            [
                'href' => 'list=delivery_method',
                'text' => Translator::translate('DeliveryMethods'),
                'levels_allowed' => [
                    ROLE_ADMIN
                ]
            ],
            [
                'href' => 'list=print_template',
                'text' => Translator::translate('PrintTemplates'),
                'levels_allowed' => [
                    ROLE_ADMIN
                ]
            ],
            [
                'href' => 'operation=dbdump',
                'text' => Translator::translate('BackupDatabase'),
                'levels_allowed' => [
                    ROLE_BACKUPMGR,
                    ROLE_ADMIN
                ]
            ],
            [
                'href' => 'operation=import',
                'text' => Translator::translate('ImportData'),
                'levels_allowed' => [
                    ROLE_ADMIN
                ]
            ],
            [
                'href' => 'operation=export',
                'text' => Translator::translate('ExportData'),
                'levels_allowed' => [
                    ROLE_ADMIN
                ]
            ],
            [
                'href' => 'operation=update',
                'text' => Translator::translate('Update'),
                'levels_allowed' => [
                    ROLE_ADMIN
                ]
            ]
        ];
        $strNewText = '';
        $strList = getRequest('list', '');
        switch ($strList) {
        case 'user' :
            $strNewText = Translator::translate('NewUser');
            break;
        case 'session_type' :
            $strNewText = Translator::translate('NewSessionType');
            break;
        case 'invoice_state' :
        case 'row_type' :
        case 'delivery_terms' :
        case 'delivery_method' :
        case 'print_template' :
            $strNewText = Translator::translate('AddNew');
            break;
        }
        if ($strNewText) {
            $strNewButton = "<br/><br/><a class=\"buttonlink new_button\""
                . " href=\"?func=system&amp;list=$strList&amp;form=$strList\">$strNewText</a>";
        }
        break;

    case 'settings':
        $astrNaviLinks = [
            [
                'href' => 'list=settings',
                'text' => Translator::translate('GeneralSettings'),
                'levels_allowed' => [
                    ROLE_ADMIN
                ]
            ],
            [
                'href' => 'list=base',
                'text' => Translator::translate('Bases'),
                'levels_allowed' => [
                    ROLE_USER,
                    ROLE_BACKUPMGR
                ]
            ],
            [
                'href' => 'list=product',
                'text' => Translator::translate('Products'),
                'levels_allowed' => [
                    ROLE_USER,
                    ROLE_BACKUPMGR
                ]
            ],
            [
                'href' => 'list=default_value',
                'text' => Translator::translate('DefaultValues'),
                'levels_allowed' => [
                    ROLE_USER,
                    ROLE_BACKUPMGR
                ]
            ]
        ];
        $strNewText = '';
        $form = getRequest('form', '');
        if (!$form) {
            $strList = getRequest('list', '');
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
            }
            if ($strNewText) {
                $strNewButton = "<br/><br/><a class=\"buttonlink\" "
                    . "href=\"?func=settings&amp;list=$strList&amp;form=$strList\">$strNewText</a>";
            }
        }
        break;

    case 'reports':
        $astrNaviLinks = [
            [
                'href' => 'form=invoice',
                'text' => Translator::translate('InvoiceReport'),
                'levels_allowed' => [
                    ROLE_READONLY,
                    ROLE_USER,
                    ROLE_BACKUPMGR
                ]
            ],
            [
                'href' => 'form=product',
                'text' => Translator::translate('ProductReport'),
                'levels_allowed' => [
                    ROLE_READONLY,
                    ROLE_USER,
                    ROLE_BACKUPMGR
                ]
            ],
            [
                'href' => 'form=product_stock',
                'text' => Translator::translate('ProductStockReport'),
                'levels_allowed' => [
                    ROLE_READONLY,
                    ROLE_USER,
                    ROLE_BACKUPMGR
                ]
            ],
            [
                'href' => 'form=accounting',
                'text' => Translator::translate('AccountingReport'),
                'levels_allowed' => [
                    ROLE_READONLY,
                    ROLE_USER,
                    ROLE_BACKUPMGR
                ]
            ]
        ];
        break;

    case 'companies':
        $blnShowSearch = true;
        $strOpenForm = 'company';
        $strFormName = 'company';
        $strFormSwitch = 'company';
        $astrNaviLinks = [];
        $strNewButton = '<a class="buttonlink" href="?func=companies&amp;form=company">'
             . Translator::translate('NewClient') . '</a>';
        break;

    case 'profile':
        $astrNaviLinks = [];
        break;

    default:
        $blnShowSearch = true;
        $strFormName = 'invoice';
        $astrNaviLinks = [];
        if ($strFunc == 'open_invoices') {
            $astrNaviLinks[] = [
                'href' => 'index.php?func=invoices',
                'text' => Translator::translate('DisplayAllInvoices'),
                'levels_allowed' => [
                    ROLE_USER,
                    ROLE_BACKUPMGR
                ]
            ];
        } elseif ($strFunc != 'archived_invoices') {
            $astrNaviLinks[] = [
                'href' => 'index.php?func=open_invoices',
                'text' => Translator::translate('DisplayOpenInvoices'),
                'levels_allowed' => [
                    ROLE_USER,
                    ROLE_BACKUPMGR
                ]
            ];
        }
        if ($strFunc != 'archived_invoices') {
            $strNewButton = ' <a class="buttonlink" href="?func=invoices&amp;form=invoice&amp;offer=1">' .
                 Translator::translate('NewOffer') . '</a>';
            $strNewButton .= '<a class="buttonlink" href="?func=invoices&amp;form=invoice">' .
                 Translator::translate('NewInvoice') . '</a>';
            $astrNaviLinks[] = [
                'href' => 'index.php?func=import_statement',
                'text' => Translator::translate('ImportAccountStatement'),
                'levels_allowed' => [
                    ROLE_USER,
                    ROLE_BACKUPMGR
                ]
            ];
        }
        break;
    }

    ?>
<script type="text/javascript">
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
    if ($astrNaviLinks || $blnShowSearch || $strNewButton) {
    ?>
    <div class="function_navi">
        <?php
        foreach ($astrNaviLinks as $link) {
            if (sesAccessLevel($link['levels_allowed']) || sesAdminAccess()) {
                if (strchr($link['href'], '?') === false) {
                    $strHref = "?func=$strFunc&amp;" . $link['href'];
                } else {
                    $strHref = $link['href'];
                }
                $class = '';
                if (strpos($link['href'], '?')) {
                    list(, $urlParams) = explode('?', $link['href'], 2);
                } else {
                    $urlParams = $link['href'];
                }
                parse_str($urlParams, $linkParts);
                if ((!isset($linkParts['func'])
                    || getRequest('func', '') == $linkParts['func']) && (!isset($linkParts['list'])
                    || getRequest('list', '') == $linkParts['list']) && (!isset($linkParts['form'])
                    || getRequest('form', '') == $linkParts['form']) && (!isset($linkParts['operation'])
                    || getRequest('operation', '') == $linkParts['operation'])
                ) {
                    $class = ' ui-state-highlight';
                }
                ?>
        <a class="buttonlink<?php echo $class?>"
            href="<?php echo $strHref?>"><?php echo $link['text']?></a>
    <?php
            }
        }
        if ($blnShowSearch) {
            ?>
        <a class="buttonlink" href="#"
            onClick="openSearchWindow('ext', event); return false;"><?php echo Translator::translate('ExtSearch')?></a>
        <a class="buttonlink" href="#"
            onClick="openSearchWindow('quick', event); return false;"><?php echo Translator::translate('QuickSearch')?></a>
    <?php
        }
        if (sesWriteAccess()) {
            echo "&nbsp; &nbsp; $strNewButton\n";
        }
        ?>
    </div>
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
            $arrNew[] = $item;
        }
    }
    $arrNew[] = [
        'title' => $title,
        'url' => $url,
        'level' => $level
    ];
    Memory::set('history', $arrNew);

    return $arrNew;
}
