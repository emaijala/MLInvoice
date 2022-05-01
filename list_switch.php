<?php
/**
 * List config
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
$strTable = '';
$countJoins = [];
$displayJoins = [];
$strListFilter = '';
$strGroupBy = '';
$strDeletedField = '';
$levelsAllowed = [
    ROLE_USER,
    ROLE_BACKUPMGR
];
switch ($strList) {

/***********************************************************************
 LISTS
 ***********************************************************************/
case 'company':
    $strTable = 'company';
    $astrSearchFields = [
        [
            'name' => 'company_name',
            'type' => 'TEXT'
        ],
        [
            'name' => 'company_id',
            'type' => 'TEXT'
        ],
        [
            'name' => 'email',
            'type' => 'TEXT'
        ],
    ];
    $strPrimaryKey = 'id';
    $strDeletedField = 'deleted';
    $astrShowFields = [
        [
            'name' => 'id',
            'width' => 20,
            'type' => 'CHECKBOX',
            'order' => 'DESC',
            'header' => '<input class="cb-select-all" type="checkbox" value="">',
            'class' => 'cb-select-row',
            'sort' => false
        ],
        [
            'name' => 'company_name',
            'width' => 150,
            'type' => 'TEXT',
            'header' => 'ClientName',
            'select' => true
        ],
        [
            'name' => 'company_id',
            'width' => 100,
            'type' => 'TEXT',
            'header' => 'ClientVATID',
            'select' => true
        ],
        [
            'name' => 'inactive',
            'width' => 100,
            'type' => 'TEXT',
            'header' => 'HeaderClientActive',
            'mappings' => [
                '0' => 'Active',
                '1' => 'Inactive'
            ]
        ],
        [
            'name' => 'customer_no',
            'width' => 100,
            'type' => 'TEXT',
            'header' => 'CustomerNr'
        ],
        [
            'name' => 'email',
            'width' => 150,
            'type' => 'TEXT',
            'header' => 'Email'
        ],
        [
            'name' => 'phone',
            'width' => 100,
            'type' => 'TEXT',
            'header' => 'Phone'
        ],
        [
            'name' => 'gsm',
            'width' => 100,
            'type' => 'TEXT',
            'header' => 'GSM'
        ]
    ];
    $strMainForm = 'company';
    break;

case 'invoice':
case 'archived_invoices':
case 'archived_offers':
case 'invoice':
case 'offer':
    $levelsAllowed[] = ROLE_READONLY;

    $strListFilter = 'i.archived = 0';
    if ('archived_invoices' === $strList) {
        $strListFilter = 'i.archived = 1 AND i.state_id NOT IN (' . implode(',', getOfferStateIds()) . ')';
    } elseif ('archived_offers' === $strList) {
        $strListFilter = 'i.archived = 1 AND i.state_id IN (' . implode(',', getOfferStateIds()) . ')';
    }

    $strTable = 'invoice';
    $tableAlias = 'i';

    $countJoins = $displayJoins = [
        [
            'type' => 'LEFT OUTER',
            'table' => 'base',
            'alias' => 'b',
            'condition' => 'i.base_id = b.id',
        ],
        [
            'type' => 'LEFT OUTER',
            'table' => 'company',
            'alias' => 'c',
            'condition' => 'i.company_id = c.id',
        ],
        [
            'type' => 'LEFT OUTER',
            'table' => 'invoice_state',
            'alias' => 's',
            'condition' => 'i.state_id = s.id',
        ],
    ];

    $prefix = _DB_PREFIX_ . '_';
    $displayJoins[] = getInvoiceTotalJoinQuery();

    $intervalOptions = [
        '0' => Translator::translate('InvoiceIntervalNone'),
        '2' => Translator::translate('InvoiceIntervalMonth'),
        '3' => Translator::translate('InvoiceIntervalYear')
    ];
    for ($i = 4; $i <= 8; $i++) {
        $intervalOptions[(string)$i]
            = sprintf(Translator::translate('InvoiceIntervalMonths'), $i - 2);
    }

    $astrSearchFields = [
        [
            'name' => $strList === 'offer' ? 'i.id' : 'i.invoice_no',
            'type' => 'TEXT'
        ],
        [
            'name' => 'i.ref_number',
            'type' => 'TEXT'
        ],
        [
            'name' => 'i.name',
            'type' => 'TEXT'
        ],
        [
            'name' => 'b.name',
            'type' => 'TEXT'
        ],
        [
            'name' => 'c.company_name',
            'type' => 'TEXT'
        ]
    ];
    $strPrimaryKey = 'i.id';
    $strDeletedField = 'i.deleted';
    $astrShowFields = [
        [
            'name' => 'i.id',
            'width' => 20,
            'type' => 'CHECKBOX',
            'order' => 'DESC',
            'header' => '<input class="cb-select-all" type="checkbox" value="">',
            'class' => 'cb-select-row',
            'sort' => false
        ],
        [
            'name' => 'i.invoice_date',
            'width' => 80,
            'type' => 'INTDATE',
            'order' => 'DESC',
            'header' => 'HeaderInvoiceDate'
        ],
        [
            'name' => 'i.payment_date',
            'width' => 80,
            'type' => 'INTDATE',
            'order' => 'DESC',
            'header' => 'HeaderInvoicePaymentDate',
            'visible' => ($strList ? $strList : $strFunc) == 'archived_invoices'
        ],
        [
            'name' => 'i.due_date',
            'width' => 80,
            'type' => 'INTDATE',
            'order' => 'DESC',
            'header' => 'HeaderInvoiceDueDate'
        ],
        [
            'name' => $strList === 'offer' ? 'i.id' : 'i.invoice_no',
            'width' => 80,
            'type' => 'TEXT',
            'header' => 'HeaderInvoiceNr'
        ],
        [
            'name' => 'b.name base_name',
            'width' => 150,
            'type' => 'TEXT',
            'header' => 'HeaderInvoiceBase'
        ],
        [
            'name' => 'c.company_name',
            'width' => 150,
            'type' => 'TEXT',
            'header' => 'HeaderInvoiceClient'
        ],
        [
            'name' => 'i.name',
            'width' => 100,
            'type' => 'TEXT',
            'header' => in_array($strList, ['offer', 'archived_offers']) ? 'HeaderOfferName' : 'HeaderInvoiceName'
        ],
        [
            'name' => 'state',
            'sql' => 's.name state',
            'width' => 120,
            'type' => 'TEXT',
            'header' => 'HeaderInvoiceState',
            'translate' => true
        ],
        [
            'name' => 'i.interval_type',
            'width' => 60,
            'type' => 'TEXT',
            'header' => 'HeaderInvoiceIntervalType',
            'mappings' => $intervalOptions,
            'visible' => false,
        ],
        [
            'name' => 'i.next_interval_date',
            'width' => 60,
            'type' => 'INTDATE',
            'header' => 'HeaderInvoiceNextIntervalDate',
            'visible' => false,
        ],
        [
            'name' => 'i.ref_number',
            'width' => 100,
            'type' => 'TEXT',
            'header' => 'HeaderInvoiceReference'
        ],
        [
            'name' => 'i.reference',
            'width' => 100,
            'type' => 'TEXT',
            'header' => 'HeaderInvoiceClientsReference',
            'visible' => false
        ],
        [
            'name' => 'total_price',
            'sql' => 'SUM(it.row_total) as total_price',
            'width' => 80,
            'type' => 'CURRENCY',
            'header' => 'HeaderInvoiceTotal'
        ]
    ];
    $strGroupBy = 'i.id, i.deleted, i.invoice_date, i.due_date, i.invoice_no,'
        . ' b.name, c.company_name, i.name, s.name, i.ref_number';
    $strMainForm = 'invoice';
    break;

/***********************************************************************
 SETTINGS
 ***********************************************************************/
case 'base':
    $strTable = 'base';
    $astrSearchFields = [
        [
            'name' => 'name',
            'type' => 'TEXT',
        ],
        [
            'name' => 'company_id',
            'type' => 'TEXT'
        ],
        [
            'name' => 'contact_person',
            'type' => 'TEXT'
        ],
        [
            'name' => 'email',
            'type' => 'TEXT'
        ],
    ];
    $strPrimaryKey = 'id';
    $strDeletedField = 'deleted';
    $astrShowFields = [
        [
            'name' => 'name',
            'width' => 200,
            'type' => 'TEXT',
            'header' => 'BaseName',
            'select' => true,
        ],
        [
            'name' => 'company_id',
            'width' => 100,
            'type' => 'TEXT',
            'header' => 'ClientVATID',
            'select' => true,
        ],
        [
            'name' => 'contact_person',
            'width' => 150,
            'type' => 'TEXT',
            'header' => 'ContactPerson'
        ],
        [
            'name' => 'email',
            'width' => 150,
            'type' => 'TEXT',
            'header' => 'Email'
        ]
    ];
    $strMainForm = 'base';
    break;

case 'invoice_state':
    $strTable = 'invoice_state';
    $astrSearchFields = [
        [
            'name' => 'name',
            'type' => 'TEXT'
        ]
    ];
    $strPrimaryKey = 'id';
    $strDeletedField = 'deleted';
    $astrShowFields = [
        [
            'name' => 'order_no',
            'width' => 150,
            'type' => 'TEXT',
            'header' => 'OrderNr'
        ],
        [
            'name' => 'name',
            'width' => 450,
            'type' => 'TEXT',
            'header' => 'Status',
            'pretranslate' => true
        ]
    ];
    // array('order_no','name');
    $strMainForm = 'invoice_state';
    break;

case 'invoice_type':
    $strTable = 'invoice_type';
    $astrSearchFields = [
        [
            'name' => 'identifier',
            'type' => 'TEXT'
        ],
        [
            'name' => 'name',
            'type' => 'TEXT'
        ]
    ];
    $strPrimaryKey = 'id';
    $strDeletedField = 'deleted';
    $astrShowFields = [
        [
            'name' => 'order_no',
            'width' => 150,
            'type' => 'TEXT',
            'header' => 'OrderNr'
        ],
        [
            'name' => 'identifier',
            'width' => 150,
            'type' => 'TEXT',
            'header' => 'Identifier',
            'select' => true
        ],
        [
            'name' => 'name',
            'width' => 450,
            'type' => 'TEXT',
            'header' => 'Name',
            'select' => true
        ]
    ];
    $strMainForm = 'invoice_type';
    break;

case 'product':
    $strTable = 'product';
    $astrSearchFields = [
        [
            'name' => 'product_code',
            'type' => 'TEXT'
        ],
        [
            'name' => 'product_name',
            'type' => 'TEXT'
        ],
        [
            'name' => 'description',
            'type' => 'TEXT'
        ],
        [
            'name' => 'product_group',
            'type' => 'TEXT'
        ],
    ];
    $strPrimaryKey = 'id';
    $strDeletedField = 'deleted';
    $astrShowFields = [
        [
            'name' => 'id',
            'width' => 20,
            'type' => 'CHECKBOX',
            'order' => 'DESC',
            'header' => '<input class="cb-select-all" type="checkbox" value="">',
            'class' => 'cb-select-row',
            'sort' => false
        ],
        [
            'name' => 'order_no',
            'type' => 'TEXT',
            'header' => 'OrderNr'
        ],
        [
            'name' => 'product_code',
            'type' => 'TEXT',
            'header' => 'ProductCode',
            'select' => true
        ],
        [
            'name' => 'product_name',
            'type' => 'TEXT',
            'header' => 'ProductName',
            'select' => true
        ],
        [
            'name' => 'description',
            'type' => 'TEXT',
            'header' => 'ProductDescription',
            'select' => true,
            'visible' => false,
        ],
        [
            'name' => 'product_group',
            'type' => 'TEXT',
            'header' => 'ProductGroup',
            'select' => true
        ],
        [
            'name' => 'vendor',
            'width' => 0,
            'type' => 'HIDDEN',
            'header' => '',
            'select' => true
        ],
        [
            'name' => 'vendors_code',
            'width' => 0,
            'type' => 'HIDDEN',
            'header' => '',
            'select' => true
        ],
        [
            'name' => 'unit_price',
            'type' => 'CURRENCY',
            'header' => 'UnitPrice',
            'decimals' => getSetting('unit_price_decimals'),
            'select' => true
        ],
        [
            'name' => 'custom_price',
            'type' => 'CURRENCY',
            'header' => 'ClientsPrice',
            'decimals' => getSetting('unit_price_decimals'),
            'virtual' => true,
            'select' => true,
            'sort' => false
        ],
        [
            'name' => 'discount',
            'type' => 'CURRENCY',
            'header' => 'DiscountPct'
        ],
        [
            'name' => 'discount_amount',
            'type' => 'CURRENCY',
            'header' => 'DiscountAmount',
            'decimals' => getSetting('unit_price_decimals')
        ],
        [
            'name' => 'stock_balance',
            'type' => 'CURRENCY',
            'header' => 'StockBalance',
            'decimals' => 2,
            'select' => GetSetting('invoice_display_product_stock_in_selection')
        ]
    ];

    $strMainForm = 'product';
    break;

case 'row_type':
    $strTable = 'row_type';
    $astrSearchFields = [
        [
            'name' => 'name',
            'type' => 'TEXT'
        ]
    ];
    $strPrimaryKey = 'id';
    $strDeletedField = 'deleted';
    $astrShowFields = [
        [
            'name' => 'order_no',
            'width' => 150,
            'type' => 'TEXT',
            'header' => 'OrderNr'
        ],
        [
            'name' => 'name',
            'width' => 450,
            'type' => 'TEXT',
            'header' => 'RowType',
            'pretranslate' => true
        ]
    ];
    $strMainForm = 'row_type';
    break;

case 'delivery_terms':
    $strTable = 'delivery_terms';
    $astrSearchFields = [
        [
            'name' => 'name',
            'type' => 'TEXT'
        ]
    ];
    $strPrimaryKey = 'id';
    $strDeletedField = 'deleted';
    $astrShowFields = [
        [
            'name' => 'order_no',
            'width' => 150,
            'type' => 'TEXT',
            'header' => 'OrderNr'
        ],
        [
            'name' => 'name',
            'width' => 450,
            'type' => 'TEXT',
            'header' => 'DeliveryTerms'
        ]
    ];
    $strMainForm = 'delivery_terms';
    break;

case 'delivery_method':
    $strTable = 'delivery_method';
    $astrSearchFields = [
        [
            'name' => 'name',
            'type' => 'TEXT'
        ]
    ];
    $strPrimaryKey = 'id';
    $strDeletedField = 'deleted';
    $astrShowFields = [
        [
            'name' => 'order_no',
            'width' => 150,
            'type' => 'TEXT',
            'header' => 'OrderNr'
        ],
        [
            'name' => 'name',
            'width' => 450,
            'type' => 'TEXT',
            'header' => 'DeliveryMethod'
        ]
    ];
    $strMainForm = 'delivery_method';
    break;

case 'print_template':
    $strTable = 'print_template';
    $astrSearchFields = [
        [
            'name' => 'name',
            'type' => 'TEXT'
        ],
        [
            'name' => 'filename',
            'type' => 'TEXT'
        ],
    ];
    $strPrimaryKey = 'id';
    $strDeletedField = 'deleted';
    $astrShowFields = [
        [
            'name' => 'order_no',
            'width' => 150,
            'type' => 'TEXT',
            'header' => 'OrderNr'
        ],
        [
            'name' => 'type',
            'width' => 150,
            'type' => 'TEXT',
            'header' => 'PrintTemplateType',
            'mappings' => [
                'invoice' => 'PrintTemplateTypeInvoice',
                'offer' => 'PrintTemplateTypeOffer'
            ]
        ],
        [
            'name' => 'name',
            'width' => 200,
            'type' => 'TEXT',
            'header' => 'PrintTemplateName',
            'pretranslate' => true
        ],
        [
            'name' => 'inactive',
            'width' => 100,
            'type' => 'TEXT',
            'header' => 'HeaderPrintTemplateActive',
            'mappings' => [
                '0' => 'Active',
                '1' => 'Inactive'
            ]
        ],
        [
            'name' => 'filename',
            'width' => 200,
            'type' => 'TEXT',
            'header' => 'PrintTemplateFileName'
        ],
        [
            'name' => 'parameters',
            'width' => 150,
            'type' => 'TEXT',
            'header' => 'PrintTemplateParameters'
        ]
    ];
    $strMainForm = 'print_template';
    break;

case 'default_value':
    $strTable = 'default_value';
    $astrSearchFields = [
        [
            'name' => 'name',
            'type' => 'TEXT'
        ],
        [
            'name' => 'content',
            'type' => 'TEXT'
        ],
        [
            'name' => 'additional',
            'type' => 'TEXT'
        ],
    ];
    $strPrimaryKey = 'id';
    $strDeletedField = 'deleted';
    $astrShowFields = [
        [
            'name' => 'order_no',
            'width' => 150,
            'type' => 'TEXT',
            'header' => 'OrderNr'
        ],
        [
            'name' => 'type',
            'width' => 150,
            'type' => 'TEXT',
            'header' => 'DefaultValueType',
            'mappings' => [
                'info' => 'Info',
                'foreword' => 'Foreword',
                'afterword' => 'Afterword',
                'email' => 'Email'
            ]
        ],
        [
            'name' => 'name',
            'width' => 450,
            'type' => 'TEXT',
            'header' => 'Name',
            'select' => true
        ]
    ];
    $strMainForm = 'default_value';
    break;

case 'attachment':
    $strTable = 'attachment';
    $astrSearchFields = [
        [
            'name' => 'name',
            'type' => 'TEXT'
        ],
        [
            'name' => 'description',
            'type' => 'TEXT'
        ],
        [
            'name' => 'filename',
            'type' => 'TEXT'
        ],
    ];
    $strPrimaryKey = 'id';
    $astrShowFields = [
        [
            'name' => 'order_no',
            'width' => 150,
            'type' => 'TEXT',
            'header' => 'OrderNr'
        ],
        [
            'name' => 'name',
            'width' => 200,
            'type' => 'TEXT',
            'header' => 'Name'
        ],
        [
            'name' => 'date',
            'width' => 100,
            'type' => 'INTDATE',
            'header' => 'Date'
        ],
        [
            'name' => 'filename',
            'width' => 200,
            'type' => 'TEXT',
            'header' => 'File'
        ],
        [
            'name' => 'filesize',
            'width' => 200,
            'type' => 'INT',
            'callback' => 'fileSizeToHumanReadable',
            'header' => 'HeaderFileSize'
        ]
    ];
    $strMainForm = 'attachment';
    break;

case 'company_tag':
    $strTable = 'company_tag';
    $astrSearchFields = [
        [
            'name' => 'tag',
            'type' => 'TEXT'
        ]
    ];
    $strPrimaryKey = 'id';
    $astrShowFields = [
        [
            'name' => 'tag',
            'width' => 450,
            'type' => 'TEXT',
            'header' => '',
            'select' => true
        ]
    ];
    $strMainForm = 'company';
    break;

case 'contact_tag':
    $strTable = 'contact_tag';
    $astrSearchFields = [
        [
            'name' => 'tag',
            'type' => 'TEXT'
        ]
    ];
    $strPrimaryKey = 'id';
    $astrShowFields = [
        [
            'name' => 'tag',
            'width' => 450,
            'type' => 'TEXT',
            'header' => '',
            'select' => true
        ]
    ];
    break;

/***********************************************************************
 SYSTEM
 ***********************************************************************/
case 'session_type':
    $levelsAllowed = [
        99
    ];
    $strTable = 'session_type';
    $astrSearchFields = [
        [
            'name' => 'name',
            'type' => 'TEXT'
        ]
    ];
    $strPrimaryKey = 'id';
    $strDeletedField = 'deleted';
    $astrShowFields = [
        [
            'name' => 'order_no',
            'width' => 150,
            'type' => 'TEXT',
            'header' => 'OrderNr'
        ],
        [
            'name' => 'name',
            'width' => 450,
            'type' => 'TEXT',
            'header' => 'SessionType',
            'pretranslate' => true
        ]
    ];
    $strMainForm = 'session_type';
    break;

case 'user':
    $levelsAllowed = [
        ROLE_ADMIN
    ];
    $strTable = 'users';
    $astrSearchFields = [
        [
            'name' => 'name',
            'type' => 'TEXT'
        ],
        [
            'name' => 'email',
            'type' => 'TEXT'
        ],
        [
            'name' => 'login',
            'type' => 'TEXT'
        ],
    ];
    $strPrimaryKey = 'id';
    $strDeletedField = 'deleted';
    $astrShowFields = [
        [
            'name' => 'name',
            'width' => 350,
            'type' => 'TEXT',
            'header' => 'UserName'
        ],
        [
            'name' => 'login',
            'width' => 250,
            'type' => 'TEXT',
            'header' => 'LoginName'
        ],
        [
            'name' => 'email',
            'width' => 250,
            'type' => 'TEXT',
            'header' => 'Email'
        ],
    ];
    $strMainForm = 'user';
    break;

default:
    break;
}
