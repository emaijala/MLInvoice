<?php
/**
 * Show an attachment
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

 // buffered, so we can redirect later if necessary
ini_set('implicit_flush', 'Off');
ob_start();

require_once 'sessionfuncs.php';

sesVerifySession();

require_once 'vendor/autoload.php';
require_once 'sqlfuncs.php';
require_once 'translator.php';
require_once 'datefuncs.php';
require_once 'miscfuncs.php';

$id = getRequest('id', false);
$type = getRequest('type', false);

$attachment = 'invoice' === $type ? getInvoiceAttachment($id) : getAttachment($id);
if ($attachment) {
    header('Content-Type: ' . $attachment['mimetype']);
    header('Content-Length: ' . $attachment['filesize']);
}

echo $attachment['filedata'];