<?php
/**
 * Self-updating mechanism
 *
 * PHP version 7
 *
 * Copyright (C) Ere Maijala 2017-2021
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
require_once 'translator.php';
require_once 'config.php';
require_once 'miscfuncs.php';
require_once 'sessionfuncs.php';
require_once 'version.php';

/**
 * Self-updating mechanism
 *
 * @category MLInvoice
 * @package  MLInvoice\Base
 * @author   Ere Maijala <ere@labs.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://labs.fi/mlinvoice.eng.php
 */
class Updater
{
    /**
     * Update stage
     *
     * @var int
     */
    protected $stage;

    /**
     * File that contains the list of obsolete files
     *
     * @var string
     */
    protected $obsoleteFilesList = 'obsolete_files.txt';

    /**
     * Start the updater
     *
     * @return void
     */
    public function launch()
    {
        if (!sesAdminAccess()) {
            $this->error(Translator::translate('NoAccess'));
            return false;
        }

        $this->stage = (int)getPostOrQuery('stage', 0);
        switch ($this->stage) {
        case 0:
            $this->preFlightCheck();
            break;
        case 1:
            $this->startUpdate();
            break;
        case 2:
            $this->downloadUpdate();
            break;
        case 3:
            $this->applyUpdate();
            break;
        case 4:
            $this->upgradeDatabase();
            break;
        }
    }

    /**
     * Check for updates and return update information if an update is available
     *
     * @return array
     */
    public function checkForUpdates()
    {
        global $softwareVersion;

        $versionInfo = $this->getVersionInfo();
        if (!$versionInfo) {
            return false;
        }
        $res = $this->compareVersionNumber(
            $versionInfo['version'], $softwareVersion
        );
        if ($res <= 0) {
            return [];
        }
        if ($res === 1) {
            $versionInfo['majorUpdate'] = true;
        }
        return $versionInfo;
    }

    /**
     * Check that write permissions exist so that the update can be done, zip
     * functions are available and there is an update available.
     *
     * @return bool
     */
    protected function preFlightCheck()
    {
        global $softwareVersion;

        $this->heading('CheckPrerequisitesHeading');

        if (!class_exists('ZipArchive')) {
            $this->error('PHP zip support not available, cannot continue');
            return false;
        }

        if (file_exists(__DIR__ . '/.git')) {
            $this->error('CannotUpdateGitVersion');
            return false;
        }

        $iter = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(__DIR__)
        );
        $unwritables = [];
        foreach ($iter as $path => $fileInfo) {
            $path = substr($path, strlen(__DIR__) + 1);
            if ('..' === $path) {
                continue;
            }
            if (!is_writable($path)) {
                $unwritables[] = $path;
            }
        }
        if ($unwritables) {
            $this->error(
                Translator::Translate('UpdaterMissingWriteAccess') . ':<br><br>'
                . implode('<br>', $unwritables)
            );
            return false;
        }

        $versionInfo = $this->getVersionInfo();
        if (!$versionInfo) {
            $this->error('UpdateInfoRetrievalFailed');
            return false;
        }
        $versionResult = $this->compareVersionNumber(
            $versionInfo['version'], $softwareVersion
        );
        if ($versionResult <= 0) {
            $this->message('LatestVersion');
            return false;
        }

        $this->message(
            Translator::translate(
                'UpdatedVersionAvailable',
                [
                    '%%version%%' => $versionInfo['version'],
                    '%%currentversion%%' => $softwareVersion
                ]
            )
        );

        if (!empty($versionInfo['channel'])
            && $versionInfo['channel'] !== 'production'
        ) {
            $this->message(
                Translator::translate(
                    'UpdateFromChannel',
                    ['%%channel%%' => _UPDATE_CHANNEL_]
                )
            );
        }

        if (!empty($versionInfo['requirements']['phpVersion'])) {
            $res = version_compare(
                PHP_VERSION,
                $versionInfo['requirements']['phpVersion']
            );
            if ($res < 0) {
                $this->error(
                    Translator::translate(
                        'UpdatePHPHigherVersionRequired',
                        [
                            '%%currentVersion%%' => PHP_VERSION,
                            '%%requiredVersion%%'
                                => $versionInfo['requirements']['phpVersion']
                        ]
                    )
                );
                return false;
            }
        }

        if ($versionResult === 1) {
            $this->message('UpdateMajorVersion');
        }

        if (!empty($versionInfo['url'])) {
            $this->message(
                '<a href="' . htmlentities($versionInfo['url']) . '" target="_blank">'
                . Translator::Translate('UpdateInformation')
                . '</a>'
            );
        }

        $this->message('ObsoleteFilesWillBeRemoved');

        $this->message('PrerequisitesOk');

        $this->continuePrompt('StartUpdate');

        return true;
    }

    /**
     * Start update
     *
     * @return void
     */
    protected function startUpdate()
    {
        global $softwareVersion;

        $this->heading('InstallUpdateHeading');

        $this->nextStage('DownloadingUpdate');

        return true;
    }

    /**
     * Download update and prompt to continue if successful
     *
     * @return bool
     */
    protected function downloadUpdate()
    {
        global $softwareVersion;

        $this->heading('InstallUpdateHeading');

        $versionInfo = $this->getVersionInfo();
        if (!$versionInfo) {
            return false;
        }
        $res = $this->compareVersionNumber(
            $versionInfo['version'], $softwareVersion
        );
        if ($res <= 0) {
            $this->message('LatestVersion');
            return false;
        }
        if (empty($versionInfo['package']) || empty($versionInfo['checksum'])) {
            $this->message('IncompleteUpdateInformation');
            return false;
        }

        // Try to disable maximum execution time
        set_time_limit(0);

        $filename = tempnam(sys_get_temp_dir(), 'mlinvoice') . '.zip';
        $client = new GuzzleHttp\Client($GLOBALS['mlinvoice_http_config'] ?? []);
        try {
            $res = $client->get(
                $versionInfo['package'],
                ['sink' => $filename]
            );
        } catch (Exception $e) {
            $this->error(
                "Could not fetch file {$versionInfo['package']}: " . $e->getMessage()
            );
            return false;
        }
        if ($res->getStatusCode() !== 200) {
            $this->error(
                "Could not fetch file {$versionInfo['package']}: "
                . $res->getStatusCode() . ': ' . $res->getReasonPhrase()
            );
            return false;
        }

        $sha1 = sha1_file($filename);

        if ($sha1 !== $versionInfo['checksum']) {
            $this->error(
                'Checksum of downloaded file does not match. Please try again.'
            );
            return false;
        }

        $_SESSION['update_file'] = $filename;

        $this->message('UpdateDownloaded');
        $this->nextStage('ExtractingUpdate');
    }

    /**
     * Apply a downloaded update
     *
     * @return bool
     */
    protected function applyUpdate()
    {
        $this->heading('InstallUpdateHeading');

        if (empty($_SESSION['update_file'])) {
            $this->error('Update file not defined');
            return false;
        }

        // Try to disable maximum execution time
        set_time_limit(0);

        $backupDir = __DIR__ . DIRECTORY_SEPARATOR . 'backup';
        if (!file_exists($backupDir)) {
            if (!mkdir($backupDir)) {
                $this->error("Could not create directory '$backupDir'");
                return false;
            }
        }
        $backupFile = $backupDir . DIRECTORY_SEPARATOR . 'backup.zip';
        if (file_exists($backupFile)) {
            if (!unlink($backupFile)) {
                $this->error("Could not remove old backup '$backupFile'");
                return false;
            }
        }

        $backup = new ZipArchive();
        if ($backup->open($backupFile, ZipArchive::CREATE) !== true) {
            $this->error("Could not create backup '$backupFile'");
            return false;
        }
        $iter = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(__DIR__)
        );
        $i = 0;
        foreach ($iter as $path => $fileInfo) {
            $path = substr($path, strlen(__DIR__) + 1);
            if ('.' === $path || '..' === $path
                || 'backup' === $path || strncmp($path, 'backup/', 7) === 0
            ) {
                continue;
            }
            if ($fileInfo->isDir()) {
                if (!$backup->addEmptyDir($path)) {
                    $backup->close();
                    $this->error("Could not add '$path' to backup '$backupFile'");
                    return false;
                }
            } else {
                if (!$backup->addFile($path)) {
                    $backup->close();
                    $this->error("Could not add '$path' to backup '$backupFile'");
                    return false;
                }
            }
            if (++$i >= 100) {
                if (!$backup->close()) {
                    $this->error("Could not close '$backupFile' (intermediate)");
                    return false;
                }
                if ($backup->open($backupFile) !== true) {
                    $this->error("Could not reopen '$backupFile'");
                    return false;
                }
                $i = 0;
            }
        }
        if (!$backup->close()) {
            $this->error("Could not close '$backupFile' (final)");
            return false;
        }

        [$res, $filesWritten] = $this->extractZip($_SESSION['update_file']);
        if (!$res) {
            if ($filesWritten && !$this->extractZip($backupFile)) {
                $this->error(
                    "Could not extract the update."
                    . " Also failed to restore files from backup '$backupFile'."
                    . ' The installation may be corrupted and may require manual'
                    . ' reinstallation.'
                );
            } else {
                $this->error(
                    "Could not extract the update."
                    . " Original files have been restored."
                );
            }
            return false;
        }
        unlink($_SESSION['update_file']);
        $this->message('UpdateExtracted');

        $this->message('RemovingObsoleteFiles');
        $errors = [];
        if (!file_exists(__DIR__ . '/' . $this->obsoleteFilesList)) {
            $this->error();
            $errors[] = 'Obsolete files list missing';
        } else {
            $obsoleteFiles = explode(
                "\n",
                file_get_contents(__DIR__ . '/' . $this->obsoleteFilesList)
            );
            foreach ($obsoleteFiles as $file) {
                $file = trim($file);
                if ('' === $file) {
                    continue;
                }
                $absFile = __DIR__ . "/$file";
                if (file_exists($absFile) && !@unlink($absFile)) {
                    $errors[] = "Could not remove '$absFile'";
                }
            }
            if (!$errors) {
                $this->message('ObsoleteFilesRemoved');
            }
        }

        if ($errors) {
            $this->error(implode('<br>', $errors));
            $this->continuePrompt('ContinueToDatabaseUpgrade');
        } else {
            $this->nextStage('UpgradingDatabase');
        }
    }

    /**
     * Upgrade the database
     *
     * @return bool
     */
    protected function upgradeDatabase()
    {
        $this->heading('UpdateDatabaseHeading');

        // Try to disable maximum execution time
        set_time_limit(0);

        $result = true;
        switch (verifyDatabase()) {
        case 'OK':
            $this->message('NoDatabaseUpgradeNeeded');
            $this->message('UpdateSuccessful');
            break;
        case 'UPGRADED':
            $this->message('DatabaseUpgraded');
            $this->message('UpdateSuccessful');
            break;
        case 'FAILED':
            $this->error('DatabaseUpgradeFailed');
            $result = false;
        }
        $this->continuePrompt('Continue');

        return $result;
    }

    /**
     * Extract a zip file over our current files
     *
     * @param string $zipFile File name
     *
     * @return array [bool, bool] Success and whether any files were written
     */
    protected function extractZip($zipFile)
    {
        $zip = new ZipArchive();
        if ($zip->open($zipFile) !== true) {
            $this->error("Could not open file '$zipFile'");
            return [false, false];
        }
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $filename = $zip->getNameIndex($i);
            // Strip any leading directory
            $destFile = preg_replace('/^mlinvoice\//', '', $filename);
            if ('' === $destFile || substr($destFile, -4) === '/../') {
                continue;
            }
            if (substr($destFile, -1) === '/') {
                $chars = substr($destFile, 0, -3) === '/./' ? 3 : 1;
                $dir = __DIR__ . DIRECTORY_SEPARATOR . substr($destFile, 0, -$chars);
                if (!is_dir($dir)) {
                    if (file_exists($dir)) {
                        unlink($dir);
                    }
                    if (!mkdir($dir)) {
                        $this->error("Could not create directory '$dir'");
                        return [false, $filesWritten];
                    }
                    $filesWritten = true;
                }
                continue;
            }
            $destPath = __DIR__ . DIRECTORY_SEPARATOR . $destFile;

            $res = file_put_contents($destPath, $zip->getFromIndex($i));
            if (false === $res) {
                $zip->close();
                return [false, $filesWritten];
            }
            $filesWritten = true;
        }
        $zip->close();

        return [true, $filesWritten];
    }

    /**
     * Retrieve array of information about the latest version or false if not
     * successful.
     *
     * @return array|false
     */
    protected function getVersionInfo()
    {
        global $softwareVersion;

        $address = defined('_UPDATE_ADDRESS_') ? _UPDATE_ADDRESS_
            : 'https://www.labs.fi/mlinvoice_version.php';
        $address .= strpos($address, '?') === false ? '?' : '&';
        $address .= http_build_query(
            [
                'channel' => defined('_UPDATE_CHANNEL_')
                    ? _UPDATE_CHANNEL_ : 'production',
                'version' => $softwareVersion
            ]
        );

        $client = new GuzzleHttp\Client($GLOBALS['mlinvoice_http_config'] ?? []);
        try {
            $res = $client->get($address);
        } catch (Exception $e) {
            $this->error("Could not fetch file '$address': " . $e->getMessage());
            return false;
        }
        if ($res->getStatusCode() !== 200) {
            $this->error(
                "Could not fetch file '$address': " . $res->getStatusCode() . ': '
                . $res->getReasonPhrase()
            );
            return false;
        }
        $body = (string)$res->getBody();
        $versionInfo = json_decode($body, true);
        if (!is_array($versionInfo)) {
            $this->error('Could not parse version info: ' . $body);
            return false;
        }
        $versionInfo['currentVersion'] = $softwareVersion;
        return $versionInfo;
    }

    /**
     * Display a heading
     *
     * @param string $str Heading
     *
     * @return void
     */
    protected function heading($str)
    {
        $str = Translator::translate($str);
        echo "<div class=\"form_container\"><h2>$str</h2></div>";
    }

    /**
     * Display a message
     *
     * @param string $msg Message
     *
     * @return void
     */
    protected function message($msg)
    {
        $msg = Translator::translate($msg);
        echo <<<EOT
<div class="form_container">
  <div class="alert alert-success message">
    $msg
  </div>
</div>
EOT;
    }

    /**
     * Display an error
     *
     * @param string $msg Error message
     *
     * @return void
     */
    protected function error($msg)
    {
        $msg = Translator::translate($msg);
        echo <<<EOT
<div class="form_container">
  <div class="alert alert-danger message">
    $msg
  </div>
</div>
EOT;
    }

    /**
     * Prompt for next stage
     *
     * @param string $message Prompt message
     *
     * @return void
     */
    protected function continuePrompt($message)
    {
        $target = 'index.php';
        if ($this->stage !== 4) {
            $target .= '?func=system&operation=update&stage=' . ($this->stage + 1);
        }
        $message = Translator::translate($message);
        echo <<<EOT
<div class="form_container">
  <a role="button" class="btn btn-primary" href="$target">$message</a>
</div>
EOT;
    }

    /**
     * Redirect to next stage
     *
     * @param string $message Message
     *
     * @return void
     */
    protected function nextStage($message)
    {
        $target = 'index.php';
        if ($this->stage !== 4) {
            $target .= '?func=system&operation=update&stage=' . ($this->stage + 1);
        }
        $this->message($message);
        echo <<<EOT
<script>
    $(document).ready(function () {
        setTimeout(function () { window.location = '$target';\u{a0}}, 2000);
    });
</script>
EOT;
    }

    /**
     * Compare two version numbers and return a positive number if v1 is higher than
     * v2, a negative number if v1 is lower than v2 and 0 if the versions are equal.
     * The returned number signifies the level of change: 1 = major release,
     * 2 = minor release, 3 = bugfix release.
     *
     * @param string $v1 First version number
     * @param string $v2 Second version number
     *
     * @return int
     */
    protected function compareVersionNumber($v1, $v2)
    {
        $v1Arr = explode('.', $v1);
        $v2Arr = explode('.', $v2);
        while (count($v1Arr) < 3) {
            $v1Arr[] = 0;
        }
        while (count($v2Arr) < 3) {
            $v2Arr[] = 0;
        }

        if (\Composer\Semver\Comparator::EqualTo($v1, $v2)) {
            return 0;
        }
        if ($v1Arr[0] != $v2Arr[0]) {
            $result = 1;
        } elseif ($v1Arr[1] != $v2Arr[1]) {
            $result = 2;
        } else {
            $result = 3;
        }
        if (\Composer\Semver\Comparator::LessThan($v1, $v2)) {
            $result = -$result;
        }

        return $result;
    }
}
