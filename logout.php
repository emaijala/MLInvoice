<?php
/**
 * Logout page
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

// buffered, so we can redirect later if necessary
ini_set('implicit_flush', 'Off');
ob_start();

require_once 'config.php';
require_once 'sessionfuncs.php';
require_once 'htmlfuncs.php';
require_once 'sqlfuncs.php';

sesVerifySession();

require_once 'translator.php';

sesEndSession();

echo htmlPageStart();

?>

<body>
    <div class="pagewrapper ui-widget ui-widget-content">
        <div id="maintabs" class="navi ui-widget-header ui-tabs">
            <ul class="ui-tabs-nav ui-helper-clearfix ui-corner-all">
                <li class="functionlink ui-state-default ui-corner-top">
                    <a class="ui-tabs-anchor functionlink" href="login.php">
                        <?php echo Translator::translate('Login')?>
                    </a>
                </li>
            </ul>
        </div>

        <div class="ui-widget" style="padding: 30px;">

            <h1><?php echo Translator::translate('ThankYou')?></h1>
            <p>
<?php echo Translator::translate('SessionClosed')?>
</p>

            <p>
                <a href="login.php">
                    <?php echo Translator::translate('BackToLogin')?>
                </a>
            </p>

        </div>
    </div>
</body>
</html>
