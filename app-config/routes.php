<?php

/**
 * Route Configuration
 *
 * PHP version 8
 *
 * Copyright (C) Ere Maijala 2026.
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

declare(strict_types=1);

use MLInvoice\Action\HomeAction;
use MLInvoice\Action\InvoiceAction;
use MLInvoice\Action\JsonAction;
use MLInvoice\Action\LoginAction;
use MLInvoice\Action\LogoutAction;
use Slim\App;

return function (App $app) {
    $app->get('/', HomeAction::class)
        ->setName('home');
    $app->map(['GET', 'POST'], '/login', LoginAction::class)
        ->setName('login');
    $app->map(['GET', 'POST'], '/login/recover', RecoverAction::class)
        ->setName('recover');
    $app->get('/logout', LogoutAction::class)
        ->setName('logout');

    $app->map(['GET', 'POST'], '/json', JsonAction::class)
        ->setName('json');

    $app->get('/search/invoices', SearchAction::class)
        ->setName('search-invoices');
    $app->get('/search/invoices/results', SearchAction::class)
        ->setName('search-invoices-results');
    $app->get('/invoices', HomeAction::class)
        ->setName('invoices');
    $app->get('/invoices/archived', HomeAction::class)
        ->setName('invoices-archived');
    $app->get('/invoices/{id}', InvoiceAction::class)
        ->setName('invoice');
    $app->get('/recurring-invoices', HomeAction::class)
        ->setName('recurring-invoices');
    $app->get('/recurring-invoices/{id}', HomeAction::class)
        ->setName('recurring-invoice');
    $app->get('/offers', HomeAction::class)
        ->setName('offers');
    $app->get('/offers/archived', HomeAction::class)
        ->setName('offers-archived');
    $app->get('/offers/new', HomeAction::class)
        ->setName('offers-new');
    $app->get('/import-statement', HomeAction::class)
        ->setName('import-statement');
    $app->get('/search/invoice', HomeAction::class)
        ->setName('search-invoice');
    $app->get('/companies', HomeAction::class)
        ->setName('companies');
    $app->get('/reports/accounting', HomeAction::class)
        ->setName('reports-accounting');
    $app->get('/reports/invoice', HomeAction::class)
        ->setName('reports-invoice');
    $app->get('/reports/product', HomeAction::class)
        ->setName('reports-product');
    $app->get('/reports/product-stock', HomeAction::class)
        ->setName('reports-product-stock');
    $app->get('/settings/general', HomeAction::class)
        ->setName('settings-general');
    $app->get('/settings/bases', HomeAction::class)
        ->setName('settings-bases');
    $app->get('/settings/products', HomeAction::class)
        ->setName('settings-products');
    $app->get('/settings/default-values', HomeAction::class)
        ->setName('settings-default-values');
    $app->get('/settings/attachments', HomeAction::class)
        ->setName('settings-attachments');
    $app->get('/settings/searches-invoice', HomeAction::class)
        ->setName('settings-searches-invoice');
    $app->get('/system/users', HomeAction::class)
        ->setName('system-users');
    $app->get('/system/invoice-states', HomeAction::class)
        ->setName('system-invoice-states');
    $app->get('/system/invoice-types', HomeAction::class)
        ->setName('system-invoice-types');
    $app->get('/system/row-types', HomeAction::class)
        ->setName('system-row-types');
    $app->get('/system/delivery-terms', HomeAction::class)
        ->setName('system-delivery-terms');
    $app->get('/system/delivery-methods', HomeAction::class)
        ->setName('system-delivery-methods');
    $app->get('/system/print-templates', HomeAction::class)
        ->setName('system-print-templates');
    $app->get('/system/backup', HomeAction::class)
        ->setName('system-backup');
    $app->get('/system/import', HomeAction::class)
        ->setName('system-import');
    $app->get('/system/export', HomeAction::class)
        ->setName('system-export');
    $app->get('/system/update', HomeAction::class)
        ->setName('system-update');
};
