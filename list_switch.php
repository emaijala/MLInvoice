<?php
/**
 * List config
 *
 * PHP version 5
 *
 * Copyright (C) 2004-2008 Samu Reinikainen
 * Copyright (C) 2010-2019 Ere Maijala
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
$strJoin = '';
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
    $strTable = '{prefix}company';
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
    $strTitle = 'Clients';
    break;

case 'invoice':
case 'archived_invoices':
case 'invoices':
case 'offer':
    $levelsAllowed[] = ROLE_READONLY;

    $strListFilter = 'archived_invoices' === $strList ? 'i.archived = 1'
        : 'i.archived = 0';
    $strTable = '{prefix}invoice i';
    $strJoin = 'LEFT OUTER JOIN {prefix}base b on i.base_id=b.id ' .
         'LEFT OUTER JOIN {prefix}company c on i.company_id=c.id ' .
         'LEFT OUTER JOIN {prefix}invoice_state s on i.state_id=s.id ';

    $strCountJoin = $strJoin;

    if (getSetting('invoice_display_vatless_price_in_list')) {
        $strJoin .= <<<EOT
LEFT OUTER JOIN (
  SELECT ir.invoice_id,
    CASE WHEN ir.vat_included = 0
      THEN (ir.price * (1 - IFNULL(ir.discount, 0) / 100)
        - IFNULL(ir.discount_amount, 0)) * ir.pcs
      ELSE (ir.price * (1 - IFNULL(ir.discount, 0) / 100)
        - IFNULL(ir.discount_amount, 0)) * ir.pcs / (1 + ir.vat / 100)
    END as row_total
  FROM {prefix}invoice_row ir
  WHERE ir.deleted = 0) it
  ON (it.invoice_id=i.id)
EOT;
    } else {
        $strJoin .= <<<EOT
LEFT OUTER JOIN (
  SELECT ir.invoice_id,
    CASE WHEN ir.partial_payment = 0 THEN
      CASE WHEN ir.vat_included = 0
        THEN (ir.price * (1 - IFNULL(ir.discount, 0) / 100)
          - IFNULL(ir.discount_amount, 0)) * ir.pcs * (1 + ir.vat / 100)
        ELSE (ir.price * (1 - IFNULL(ir.discount, 0) / 100)
          - IFNULL(ir.discount_amount, 0)) * ir.pcs
      END
    ELSE
      ir.price
    END as row_total
  FROM {prefix}invoice_row ir
  WHERE ir.deleted = 0) it
  ON (it.invoice_id=i.id)
EOT;
    }

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
            'name' => 'b.name',
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
            'header' => 'offer' === $strList ? 'HeaderOfferName' : 'HeaderInvoiceName'
        ],
        [
            'name' => 's.name',
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
            'name' => '.total_price',
            'sql' => 'SUM(it.row_total) as total_price',
            'width' => 80,
            'type' => 'CURRENCY',
            'header' => 'HeaderInvoiceTotal'
        ]
    ];
    $strGroupBy = 'i.id, i.deleted, i.invoice_date, i.due_date, i.invoice_no,'
        . ' b.name, c.company_name, i.name, s.name, i.ref_number';
    $strMainForm = 'invoice';
    $strTitle = 'Invoices';
    break;

/***********************************************************************
 SETTINGS
 ***********************************************************************/
case 'base':
    $strTable = '{prefix}base';
    $astrSearchFields = [
        [
            'name' => 'name',
            'type' => 'TEXT'
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
            'header' => 'BaseName'
        ],
        [
            'name' => 'company_id',
            'width' => 100,
            'type' => 'TEXT',
            'header' => 'ClientVATID'
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
    $strTitle = 'Bases';
    break;

case 'invoice_state':
    $strTable = '{prefix}invoice_state';
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
    $strTitle = 'InvoiceStates';
    break;

case 'invoice_type':
    $strTable = '{prefix}invoice_type';
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
    $strTitle = 'InvoiceTypes';
    break;

case 'product':
    $strTable = '{prefix}product';
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
            'width' => 150,
            'type' => 'TEXT',
            'header' => 'OrderNr'
        ],
        [
            'name' => 'product_code',
            'width' => 150,
            'type' => 'TEXT',
            'header' => 'ProductCode',
            'select' => true
        ],
        [
            'name' => 'product_name',
            'width' => 200,
            'type' => 'TEXT',
            'header' => 'ProductName',
            'select' => true
        ],
        [
            'name' => 'description',
            'width' => 200,
            'type' => 'TEXT',
            'header' => 'ProductDescription',
            'select' => true
        ],
        [
            'name' => 'product_group',
            'width' => 150,
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
            'width' => 100,
            'type' => 'CURRENCY',
            'header' => 'UnitPrice',
            'decimals' => getSetting('unit_price_decimals'),
            'select' => true
        ],
        [
            'name' => 'custom_price',
            'width' => 100,
            'type' => 'CURRENCY',
            'header' => 'ClientsPrice',
            'decimals' => getSetting('unit_price_decimals'),
            'virtual' => true,
            'select' => true,
            'sort' => false
        ],
        [
            'name' => 'discount',
            'width' => 100,
            'type' => 'CURRENCY',
            'header' => 'DiscountPct'
        ],
        [
            'name' => 'discount_amount',
            'width' => 100,
            'type' => 'CURRENCY',
            'header' => 'DiscountAmount',
            'decimals' => getSetting('unit_price_decimals')
        ],
        [
            'name' => 'stock_balance',
            'width' => 100,
            'type' => 'CURRENCY',
            'header' => 'StockBalance',
            'decimals' => 2,
            'select' => GetSetting('invoice_display_product_stock_in_selection')
        ]
    ];

    $strMainForm = 'product';
    $strTitle = 'Products';
    break;

case 'row_type':
    $strTable = '{prefix}row_type';
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
    $strTitle = 'RowTypes';
    break;

case 'delivery_terms':
    $strTable = '{prefix}delivery_terms';
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
    $strTitle = 'DeliveryTerms';
    break;

case 'delivery_method':
    $strTable = '{prefix}delivery_method';
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
    $strTitle = 'DeliveryMethod';
    break;

case 'print_template':
    $strTable = '{prefix}print_template';
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
    $strTitle = 'PrintTemplates';
    break;

case 'default_value':
    $strTable = '{prefix}default_value';
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
    $strTitle = 'DefaultValues';
    break;

case 'attachment':
    $strTable = '{prefix}attachment';
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
    $strTitle = 'Attachments';
    break;

case 'company_tag':
    $strTable = '{prefix}company_tag';
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
    $strTable = '{prefix}contact_tag';
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
    $strTable = '{prefix}session_type';
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
    $strTitle = 'SessionTypes';
    break;

case 'user':
    $levelsAllowed = [
        ROLE_ADMIN
    ];
    $strTable = '{prefix}users';
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
    $strTitle = 'Users';
    break;

default :
    break;
}
