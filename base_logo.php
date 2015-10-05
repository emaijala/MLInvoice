<?php
/*******************************************************************************
 MLInvoice: web-based invoicing application.
 Copyright (C) 2010-2015 Ere Maijala
 
 This program is free software. See attached LICENSE.
 
 *******************************************************************************/

/*******************************************************************************
 MLInvoice: web-pohjainen laskutusohjelma.
 Copyright (C) 2010-2015 Ere Maijala
 
 Tämä ohjelma on vapaa. Lue oheinen LICENSE.
 
 *******************************************************************************/
require_once 'sqlfuncs.php';
require_once 'miscfuncs.php';
require_once 'sessionfuncs.php';
require_once 'localize.php';
require_once 'htmlfuncs.php';

sesVerifySession();

$func = getRequest('func', 'view');
$baseId = getRequest('id', null);

if (!isset($baseId) || !is_numeric($baseId) || !isset($func))
    exit();

$messages = '';

if ($func == 'clear') {
    mysqli_param_query(
        'UPDATE {prefix}base set logo_filename=null, logo_filesize=null, logo_filetype=null, logo_filedata=null WHERE id=?', 
        [
            $baseId
        ]);
    $messages .= $GLOBALS['locBaseLogoErased'] . "<br>\n";
} elseif ($func == 'upload') {
    if ($_FILES['logo']['error'] != UPLOAD_ERR_OK) {
        $messages .= $GLOBALS['locErrFileUploadFailed'] . "<br>\n";
    } else {
        $imageInfo = getimagesize($_FILES['logo']['tmp_name']);
        if (!$imageInfo || !in_array($imageInfo['mime'], 
            [
                'image/jpeg', 
                'image/png'
            ])) {
            $messages .= $GLOBALS['locErrFileTypeInvalid'] . "<br>\n";
        } else {
            $file = fopen($_FILES['logo']['tmp_name'], 'rb');
            if ($file === FALSE)
                die('Could not process file upload - temp file missing');
            $fsize = filesize($_FILES['logo']['tmp_name']);
            $data = fread($file, $fsize);
            fclose($file);
            mysqli_param_query(
                'UPDATE {prefix}base set logo_filename=?, logo_filesize=?, logo_filetype=?, logo_filedata=? WHERE id=?', 
                [
                    $_FILES['logo']['name'], 
                    $fsize, 
                    $imageInfo['mime'], 
                    $data, 
                    $baseId
                ]);
            $messages .= $GLOBALS['locBaseLogoSaved'] . ' (' .
                 fileSizeToHumanReadable($fsize) . ")<br>\n";
        }
    }
} elseif ($func == 'view') {
    $res = mysqli_param_query(
        'SELECT logo_filename, logo_filesize, logo_filetype, logo_filedata FROM {prefix}base WHERE id=?', 
        [
            $baseId
        ]);
    if ($row = mysqli_fetch_assoc($res)) {
        if (isset($row['logo_filename']) && isset($row['logo_filesize']) &&
             isset($row['logo_filetype']) && isset($row['logo_filedata'])) {
            header('Content-length: ' . $row['logo_filesize']);
            header('Content-type: ' . $row['logo_filetype']);
            header('Content-Disposition: inline; filename=' . $row['logo_filename']);
            echo $row['logo_filedata'];
        }
    }
    exit();
}

$maxUploadSize = getMaxUploadSize();
$row = mysqli_fetch_array(mysqli_query_check('SELECT @@max_allowed_packet'));
$maxPacket = $row[0];

if ($maxPacket < $maxUploadSize)
    $maxFileSize = fileSizeToHumanReadable($maxPacket) . ' ' .
         $GLOBALS['locBaseLogoSizeDBLimited'];
else
    $maxFileSize = fileSizeToHumanReadable($maxUploadSize);

echo htmlPageStart(_PAGE_TITLE_);
?>
<div class="form">
	<div class="message"><?php echo $messages?></div>

	<div class="form_container ui-widget-content">
		<div style="margin-bottom: 10px">
			<img class="image" src="?func=view&amp;id=<?php echo $baseId?>">
		</div>
		<form id="form_upload" enctype="multipart/form-data"
			action="base_logo.php" method="POST">
			<input type="hidden" name="func" value="upload"> <input type="hidden"
				name="id" value="<?php echo $baseId?>">
			<div class="label" style="clear: both; margin-top: 10px"><?php printf($GLOBALS['locBaseLogo'], $maxFileSize)?></div>
			<div class="long">
				<input name="logo" type="file">
			</div>
			<div class="form_buttons" style="clear: both">
				<input type="submit"
					value="<?php echo $GLOBALS['locBaseSaveLogo']?>">
			</div>
		</form>
		<form id="form_erase" enctype="multipart/form-data"
			action="base_logo.php" method="POST">
			<input type="hidden" name="func" value="clear"> <input type="hidden"
				name="id" value="<?php echo $baseId?>">
			<div class="form_buttons" style="clear: both">
				<input type="submit"
					value="<?php echo $GLOBALS['locBaseEraseLogo']?>">
			</div>
		</form>
	</div>
</div>
</body>
</html>
