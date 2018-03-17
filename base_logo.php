<?php
/**
 * Logo handling
 *
 * PHP version 5
 *
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
require_once 'miscfuncs.php';
require_once 'sessionfuncs.php';
require_once 'translator.php';
require_once 'htmlfuncs.php';

sesVerifySession();

$func = getRequest('func', 'view');
$baseId = getRequest('id', null);

if (!isset($baseId) || !is_numeric($baseId) || !isset($func)) {
    exit();
}

$messages = '';

if ($func == 'clear') {
    dbParamQuery(
        'UPDATE {prefix}base set logo_filename=null, logo_filesize=null, logo_filetype=null, logo_filedata=null WHERE id=?',
        [$baseId]
    );
    $messages .= Translator::translate('BaseLogoErased') . "<br>\n";
} elseif ($func == 'upload') {
    if ($_FILES['logo']['error'] != UPLOAD_ERR_OK) {
        $messages .= Translator::translate('ErrFileUploadFailed') . "<br>\n";
    } else {
        $imageInfo = getimagesize($_FILES['logo']['tmp_name']);
        if (!$imageInfo || !in_array($imageInfo['mime'], ['image/jpeg','image/png'])
        ) {
            $messages .= Translator::translate('ErrFileTypeInvalid') . "<br>\n";
        } else {
            $file = fopen($_FILES['logo']['tmp_name'], 'rb');
            if ($file === false) {
                die('Could not process file upload - temp file missing');
            }
            $fsize = filesize($_FILES['logo']['tmp_name']);
            $data = fread($file, $fsize);
            fclose($file);
            dbParamQuery(
                'UPDATE {prefix}base set logo_filename=?, logo_filesize=?, logo_filetype=?, logo_filedata=? WHERE id=?',
                [
                    $_FILES['logo']['name'],
                    $fsize,
                    $imageInfo['mime'],
                    $data,
                    $baseId
                ]
            );
            $messages .= Translator::translate('BaseLogoSaved') . ' (' .
                 fileSizeToHumanReadable($fsize) . ")<br>\n";
        }
    }
} elseif ($func == 'view') {
    $rows = dbParamQuery(
        'SELECT logo_filename, logo_filesize, logo_filetype, logo_filedata FROM {prefix}base WHERE id=?',
        [
            $baseId
        ]
    );
    if ($rows) {
        $row = $rows[0];
        if (isset($row['logo_filename']) && isset($row['logo_filesize'])
            && isset($row['logo_filetype']) && isset($row['logo_filedata'])
        ) {
            header('Content-length: ' . $row['logo_filesize']);
            header('Content-type: ' . $row['logo_filetype']);
            header('Content-Disposition: inline; filename=' . $row['logo_filename']);
            echo $row['logo_filedata'];
        }
    }
    exit();
}

$maxUploadSize = getMaxUploadSize();
$res = dbQueryCheck('SELECT @@max_allowed_packet');
$maxPacket = dbFetchValue($res);

if ($maxPacket < $maxUploadSize) {
    $maxFileSize = fileSizeToHumanReadable($maxPacket) . ' ' .
         Translator::translate('BaseLogoSizeDBLimited');
} else {
    $maxFileSize = fileSizeToHumanReadable($maxUploadSize);
}

echo htmlPageStart();
?>
<div class="form_container ui-widget-content">
    <div class="message"><?php echo $messages?></div>

    <div class="form ui-widget">
        <div style="margin-bottom: 10px">
            <img class="image" src="?func=view&amp;id=<?php echo $baseId?>">
        </div>
        <form id="form_upload" enctype="multipart/form-data"
            action="base_logo.php" method="POST">
            <input type="hidden" name="func" value="upload"> <input type="hidden"
                name="id" value="<?php echo $baseId?>">
            <div class="label" style="clear: both; margin-top: 10px; margin-bottom: 10px"><?php printf(Translator::translate('BaseLogo'), $maxFileSize)?></div>
            <div class="long">
                <input name="logo" type="file">
            </div>
            <div class="form_buttons" style="clear: both">
                <input type="submit"
                    value="<?php echo Translator::translate('BaseSaveLogo')?>">
            </div>
        </form>
        <form id="form_erase" enctype="multipart/form-data"
            action="base_logo.php" method="POST">
            <input type="hidden" name="func" value="clear"> <input type="hidden"
                name="id" value="<?php echo $baseId?>">
            <div class="form_buttons" style="clear: both">
                <input type="submit"
                    value="<?php echo Translator::translate('BaseEraseLogo')?>">
            </div>
        </form>
    </div>
</div>
</body>
</html>
