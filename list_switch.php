<?php
/*******************************************************************************
 MLInvoice: web-based invoicing application.
 Copyright (C) 2010-2015 Ere Maijala

 Portions based on:
 PkLasku : web-based invoicing software.
 Copyright (C) 2004-2008 Samu Reinikainen

 This program is free software. See attached LICENSE.

 *******************************************************************************/

/*******************************************************************************
 MLInvoice: web-pohjainen laskutusohjelma.
 Copyright (C) 2010-2015 Ere Maijala

 Perustuu osittain sovellukseen:
 PkLasku : web-pohjainen laskutusohjelmisto.
 Copyright (C) 2004-2008 Samu Reinikainen

 Tämä ohjelma on vapaa. Lue oheinen LICENSE.

 *******************************************************************************/
$strTable = '';
$strJoin = '';
$strListFilter = '';
$strGroupBy = '';
$levelsAllowed = [
    ROLE_USER,
    ROLE_BACKUPMGR
];
switch ($strList ? $strList : $strFunc) {

/***********************************************************************
 LISTS
 ***********************************************************************/
case 'company' :
case 'companies' :
    $strTable = '{prefix}company';
    $astrSearchFields = [
        [
            'name' => 'company_name',
            'type' => 'TEXT'
        ],
        [
            'name' => 'company_id',
            'type' => 'TEXT'
        ]
    ];
    $astrHiddenSearchField = [
        'name' => 'type_id',
        'type' => 'INT'
    ];
    $strPrimaryKey = 'id';
    $strDeletedField = 'deleted';
    $astrShowFields = [
        [
            'name' => 'company_name',
            'width' => 150,
            'type' => 'TEXT',
            'header' => $GLOBALS['locClientName'],
            'select' => true
        ],
        [
            'name' => 'company_id',
            'width' => 100,
            'type' => 'TEXT',
            'header' => $GLOBALS['locClientVATID'],
            'select' => true
        ],
        [
            'name' => 'inactive',
            'width' => 100,
            'type' => 'TEXT',
            'header' => $GLOBALS['locHeaderClientActive'],
            'mappings' => [
                '0' => $GLOBALS['locActive'],
                '1' => $GLOBALS['locInactive']
            ]
        ],
        [
            'name' => 'customer_no',
            'width' => 100,
            'type' => 'TEXT',
            'header' => $GLOBALS['locCustomerNr']
        ],
        [
            'name' => 'email',
            'width' => 150,
            'type' => 'TEXT',
            'header' => $GLOBALS['locEmail']
        ],
        [
            'name' => 'phone',
            'width' => 100,
            'type' => 'TEXT',
            'header' => $GLOBALS['locPhone']
        ],
        [
            'name' => 'gsm',
            'width' => 100,
            'type' => 'TEXT',
            'header' => $GLOBALS['locGSM']
        ]
    ];
    $strMainForm = 'company';
    $strTitle = $GLOBALS['locClients'];
    break;

case 'invoice' :
case 'archived_invoices' :
case 'invoices' :
    $levelsAllowed[] = ROLE_READONLY;

    $strListFilter = ($strFunc == 'archived_invoices') ? 'i.archived = 1' : 'i.archived = 0';
    $strTable = '{prefix}invoice i';
    $strJoin = 'LEFT OUTER JOIN {prefix}base b on i.base_id=b.id ' .
         'LEFT OUTER JOIN {prefix}company c on i.company_id=c.id ' .
         'LEFT OUTER JOIN {prefix}invoice_state s on i.state_id=s.id ';

    $strCountJoin = $strJoin;

    if (getSetting('invoice_display_vatless_price_in_list')) {
        $strJoin .= <<<EOT
LEFT OUTER JOIN (
  SELECT ir.invoice_id, ROUND(
    CASE WHEN ir.vat_included = 0
      THEN ir.price * (1 - IFNULL(ir.discount, 0) / 100) * ir.pcs
      ELSE ROUND(ir.price * (1 - IFNULL(ir.discount, 0) / 100) * ir.pcs, 2) / (1 + ir.vat / 100)
    END, 2) as row_total
  FROM {prefix}invoice_row ir
  WHERE ir.deleted = 0) it
  ON (it.invoice_id=i.id)
EOT;
    } else {
        $strJoin .= <<<EOT
LEFT OUTER JOIN (
  SELECT ir.invoice_id, ROUND(
    CASE WHEN ir.partial_payment = 0 THEN
      CASE WHEN ir.vat_included = 0
        THEN ROUND(ir.price * (1 - IFNULL(ir.discount, 0) / 100) * ir.pcs, 2) * (1 + ir.vat / 100)
        ELSE ir.price * (1 - IFNULL(ir.discount, 0) / 100) * ir.pcs
      END
    ELSE
      ir.price
    END, 2) as row_total
  FROM {prefix}invoice_row ir
  WHERE ir.deleted = 0) it
  ON (it.invoice_id=i.id)
EOT;
    }
    $astrSearchFields = [
        [
            'name' => 'i.invoice_no',
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
            'name' => 'i.invoice_date',
            'width' => 80,
            'type' => 'INTDATE',
            'order' => 'DESC',
            'header' => $GLOBALS['locHeaderInvoiceDate']
        ],
        [
            'name' => 'i.due_date',
            'width' => 80,
            'type' => 'INTDATE',
            'order' => 'DESC',
            'header' => $GLOBALS['locHeaderInvoiceDueDate']
        ],
        [
            'name' => 'i.invoice_no',
            'width' => 80,
            'type' => 'TEXT',
            'header' => $GLOBALS['locHeaderInvoiceNr']
        ],
        [
            'name' => 'b.name',
            'width' => 150,
            'type' => 'TEXT',
            'header' => $GLOBALS['locHeaderInvoiceBase']
        ],
        [
            'name' => 'c.company_name',
            'width' => 150,
            'type' => 'TEXT',
            'header' => $GLOBALS['locHeaderInvoiceClient']
        ],
        [
            'name' => 'i.name',
            'width' => 100,
            'type' => 'TEXT',
            'header' => $GLOBALS['locHeaderInvoiceName']
        ],
        [
            'name' => 's.name',
            'width' => 120,
            'type' => 'TEXT',
            'header' => $GLOBALS['locHeaderInvoiceState'],
            'translate' => true
        ],
        [
            'name' => 'i.ref_number',
            'width' => 100,
            'type' => 'TEXT',
            'header' => $GLOBALS['locHeaderInvoiceReference']
        ],
        [
            'name' => '.total_price',
            'sql' => 'SUM(it.row_total) as total_price',
            'width' => 80,
            'type' => 'CURRENCY',
            'header' => $GLOBALS['locHeaderInvoiceTotal']
        ]
    ];
    if (($strList ? $strList : $strFunc) == 'archived_invoices') {
        array_splice($astrShowFields, 2, 0,
            [
                [
                    'name' => 'i.payment_date',
                    'width' => 80,
                    'type' => 'INTDATE',
                    'order' => 'DESC',
                    'header' => $GLOBALS['locHeaderInvoicePaymentDate']
                ]
            ]);
    }
    $strGroupBy = 'i.id, i.deleted, i.invoice_date, i.due_date, i.invoice_no, b.name, c.company_name, i.name, s.name, i.ref_number';
    $strMainForm = 'invoice';
    $strTitle = $GLOBALS['locInvoices'];
    break;

/***********************************************************************
 SETTINGS
 ***********************************************************************/
case 'base' :
    $strTable = '{prefix}base';
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
            'name' => 'name',
            'width' => 200,
            'type' => 'TEXT',
            'header' => $GLOBALS['locBaseName']
        ],
        [
            'name' => 'company_id',
            'width' => 100,
            'type' => 'TEXT',
            'header' => $GLOBALS['locClientVATID']
        ],
        [
            'name' => 'contact_person',
            'width' => 150,
            'type' => 'TEXT',
            'header' => $GLOBALS['locContactPerson']
        ],
        [
            'name' => 'email',
            'width' => 150,
            'type' => 'TEXT',
            'header' => $GLOBALS['locEmail']
        ]
    ];
    // array('name');
    $strMainForm = 'base';
    $strTitle = $GLOBALS['locBases'];
    break;

case 'invoice_state' :
    $strTable = '{prefix}invoice_state';
    $astrSearchFields = [
        [
            'name' => "name'",
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
            'header' => $GLOBALS['locOrderNr']
        ],
        [
            'name' => 'name',
            'width' => 450,
            'type' => 'TEXT',
            'header' => $GLOBALS['locStatus'],
            'translate' => true
        ]
    ];
    // array('order_no','name');
    $strMainForm = 'invoice_state';
    $strTitle = $GLOBALS['locInvoiceStates'];
    break;

case 'product' :
    $strTable = '{prefix}product';
    $astrSearchFields = [
        [
            'name' => 'product_code',
            'type' => 'TEXT'
        ],
        [
            'name' => 'product_name',
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
            'header' => $GLOBALS['locOrderNr']
        ],
        [
            'name' => 'product_code',
            'width' => 150,
            'type' => 'TEXT',
            'header' => $GLOBALS['locProductCode'],
            'select' => true
        ],
        [
            'name' => 'product_name',
            'width' => 200,
            'type' => 'TEXT',
            'header' => $GLOBALS['locProductName'],
            'select' => true
        ],
        [
            'name' => 'product_group',
            'width' => 150,
            'type' => 'TEXT',
            'header' => $GLOBALS['locProductGroup']
        ],
        [
            'name' => 'unit_price',
            'width' => 100,
            'type' => 'CURRENCY',
            'header' => $GLOBALS['locUnitPrice'],
            'decimals' => getSetting('unit_price_decimals')
        ],
        [
            'name' => 'stock_balance',
            'width' => 100,
            'type' => 'CURRENCY',
            'header' => $GLOBALS['locStockBalance'],
            'decimals' => 2
        ]
    ];

    $strMainForm = 'product';
    $strTitle = $GLOBALS['locProducts'];
    break;

case 'row_type' :
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
            'header' => $GLOBALS['locOrderNr']
        ],
        [
            'name' => 'name',
            'width' => 450,
            'type' => 'TEXT',
            'header' => $GLOBALS['locRowType'],
            'translate' => true
        ]
    ];
    $strMainForm = 'row_type';
    $strTitle = $GLOBALS['locRowTypes'];
    break;

case 'delivery_terms' :
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
            'header' => $GLOBALS['locOrderNr']
        ],
        [
            'name' => 'name',
            'width' => 450,
            'type' => 'TEXT',
            'header' => $GLOBALS['locDeliveryTerms']
        ]
    ];
    $strMainForm = 'delivery_terms';
    $strTitle = $GLOBALS['locDeliveryTerms'];
    break;

case 'delivery_method' :
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
            'header' => $GLOBALS['locOrderNr']
        ],
        [
            'name' => 'name',
            'width' => 450,
            'type' => 'TEXT',
            'header' => $GLOBALS['locDeliveryMethod']
        ]
    ];
    $strMainForm = 'delivery_method';
    $strTitle = $GLOBALS['locDeliveryMethod'];
    break;

case 'print_template' :
    $strTable = '{prefix}print_template';
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
            'header' => $GLOBALS['locOrderNr']
        ],
        [
            'name' => 'type',
            'width' => 150,
            'type' => 'TEXT',
            'header' => $GLOBALS['locPrintTemplateType'],
            'mappings' => [
                'invoice' => $GLOBALS['locPrintTemplateTypeInvoice']
            ]
        ],
        [
            'name' => 'name',
            'width' => 200,
            'type' => 'TEXT',
            'header' => $GLOBALS['locPrintTemplateName'],
            'translate' => true
        ],
        [
            'name' => 'inactive',
            'width' => 100,
            'type' => 'TEXT',
            'header' => $GLOBALS['locHeaderPrintTemplateActive'],
            'mappings' => [
                '0' => $GLOBALS['locActive'],
                '1' => $GLOBALS['locInactive']
            ]
        ],
        [
            'name' => 'filename',
            'width' => 200,
            'type' => 'TEXT',
            'header' => $GLOBALS['locPrintTemplateFileName']
        ],
        [
            'name' => 'parameters',
            'width' => 150,
            'type' => 'TEXT',
            'header' => $GLOBALS['locPrintTemplateParameters']
        ]
    ];
    $strMainForm = 'print_template';
    $strTitle = $GLOBALS['locPrintTemplates'];
    break;

/***********************************************************************
 SYSTEM
 ***********************************************************************/
case 'session_type' :
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
            'header' => $GLOBALS['locOrderNr']
        ],
        [
            'name' => 'name',
            'width' => 450,
            'type' => 'TEXT',
            'header' => $GLOBALS['locSessionType'],
            'translate' => true
        ]
    ];
    $strMainForm = 'session_type';
    $strTitle = $GLOBALS['locSessionTypes'];
    break;

case 'user' :
    $levelsAllowed = [
        99
    ];
    $strTable = '{prefix}users';
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
            'name' => 'name',
            'width' => 350,
            'type' => 'TEXT',
            'header' => $GLOBALS['locUserName']
        ],
        [
            'name' => 'login',
            'width' => 250,
            'type' => 'TEXT',
            'header' => $GLOBALS['locLoginName']
        ]
    ];
    $strMainForm = 'user';
    $strTitle = $GLOBALS['locUsers'];
    break;

default :
    break;
}
