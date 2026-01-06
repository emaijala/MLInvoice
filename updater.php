<?php
/**
 * Self-updating mechanism
 *
 * PHP version 8
 *
 * Copyright (C) Ere Maijala 2017-2024.
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

        $stage = getPostOrQuery('stage', 'preflight');
        $params = [];
        foreach (['backup', 'ignore_version_check', 'force_git'] as $param) {
            if (null !== ($val = getPostOrQuery($param))) {
                $params[$param] = $val;
            }
        }
        switch ($stage) {
        case 'preflight':
            unset($params['backup']);
            $this->preFlightCheck($params);
            break;
        case 'start':
            $this->startUpdate($params);
            break;
        case 'download':
            $this->downloadUpdate($params);
            break;
        case 'backup':
            $this->createBackup($params);
            break;
        case 'apply':
            $this->applyUpdate($params);
            break;
        case 4: // For back-compatibility with the older updater
        case 'database':
            $this->upgradeDatabase($params);
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
     * @param array $params Extra params
     *
     * @return bool
     */
    protected function preFlightCheck(array $params)
    {
        global $softwareVersion;

        $this->heading('CheckPrerequisitesHeading');

        if (!class_exists('ZipArchive')) {
            $this->error('PHP zip support not available, cannot continue');
            return false;
        }

        if (file_exists(__DIR__ . '/.git') && !getPostOrQuery('force_git')) {
            $this->error('CannotUpdateGitVersion');
            return false;
        }

        $iter = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(__DIR__)
        );
        $unwritables = [];
        // Note: Don't use iterator_to_array or such as it may use up too much memory.
        foreach ($iter as $path => $fileInfo) {
            $subPath = substr($path, strlen(__DIR__) + 1);
            if ('..' === $subPath || str_starts_with($subPath, '.git/')) {
                continue;
            }
            if (!is_writable($path)) {
                $unwritables[] = $subPath;
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
        if ($versionResult <= 0 && !getPostOrQuery('ignore_version_check')) {
            $this->message('LatestVersion');
            return false;
        }

        $this->message('ObsoleteFilesWillBeRemoved');

        $this->message(
            Translator::translate(
                'UpdatedVersionAvailable',
                [
                    '%%version%%' => $versionInfo['version'],
                    '%%currentversion%%' => $softwareVersion,
                    '%%date%%' => DateTime::createFromFormat('Y-m-d', $versionInfo['date'])
                        ->format(Translator::translate('DateFormat'))
                ]
            ),
            true
        );

        if (!empty($versionInfo['channel']) && $versionInfo['channel'] !== 'production') {
            $this->message(
                Translator::translate(
                    'UpdateFromChannel',
                    ['%%channel%%' => $versionInfo['channel']]
                ),
                true
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

        if (!empty($versionInfo['url'])) {
            $this->message(
                '<a href="' . htmlentities($versionInfo['url']) . '" target="_blank">'
                . Translator::Translate('UpdateInformation')
                . '</a>',
                true
            );
        }

        if ($versionResult === 1) {
            $this->message('UpdateMajorVersion');
        }

        $this->message('PrerequisitesOk', true);

        $this->startUpdatePrompt($params);

        return true;
    }

    /**
     * Start update
     *
     * @param array $params Extra params
     *
     * @return void
     */
    protected function startUpdate(array $params)
    {
        $this->heading('InstallUpdateHeading');

        $this->nextStage('DownloadingUpdate', $params, 'download');

        return true;
    }

    /**
     * Download update and prompt to continue if successful
     *
     * @param array $params Extra params
     *
     * @return bool
     */
    protected function downloadUpdate(array $params)
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
        if ($res <= 0 && !getPostOrQuery('ignore_version_check')) {
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
        if ($params['backup'] ?? false) {
            $this->nextStage('CreatingBackup', $params, 'backup');
        } else {
            $this->nextStage('ExtractingUpdate', $params, 'apply');
        }
    }

    /**
     * Create backup
     *
     * @param array $params Extra params
     *
     * @return bool
     */
    protected function createBackup(array $params)
    {
        $this->heading('InstallUpdateHeading');

        if (empty($_SESSION['update_file'])) {
            $this->error('Update file not defined');
            return false;
        }

        // Try to disable maximum execution time
        set_time_limit(0);

        $backupFile = null;
        if ($params['backup'] ?? false) {
            $backupDir = $this->getBackupDir();
            if (!file_exists($backupDir)) {
                if (!mkdir($backupDir)) {
                    $this->error("Could not create directory '$backupDir'");
                    return false;
                }
            }
            if (!copy(__DIR__ . DIRECTORY_SEPARATOR . 'htaccess-denyall', $backupDir . DIRECTORY_SEPARATOR . '.htaccess')) {
                $this->error("Could not copy .htaccess file to '$backupDir'");
                return false;
            }
            $backupFile = $this->getBackupFile();
            $backup = new ZipArchive();
            if ($backup->open($backupFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                $this->error("Could not create backup '$backupFile'");
                return false;
            }
            $iter = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator(__DIR__)
            );
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
            }
            if (!$backup->close()) {
                $this->error("Could not close '$backupFile' (final)");
                return false;
            }
        }

        $this->nextStage('ExtractingUpdate', $params, 'apply');
    }

    /**
     * Apply a downloaded update
     *
     * @param array $params Extra params
     *
     * @return bool
     */
    protected function applyUpdate(array $params)
    {
        $this->heading('InstallUpdateHeading');

        if (empty($_SESSION['update_file'])) {
            $this->error('Update file not defined');
            return false;
        }

        // Try to disable maximum execution time
        set_time_limit(0);

        [$res, $filesWritten] = $this->extractZip($_SESSION['update_file']);
        if (!$res) {
            $backupFile = $this->getBackupFile();
            if ($filesWritten && (!($params['backup'] ?? false ) || !$this->extractZip($backupFile))) {
                $this->error(
                    "Could not extract the update."
                    . " Also failed to restore files from backup '$backupFile'."
                    . ' The installation may be corrupted and may require manual'
                    . ' reinstallation.'
                );
            } else {
                $this->error('Could not extract the update. Original files have been restored.');
            }
            return false;
        }
        unlink($_SESSION['update_file']);
        $this->message('UpdateExtracted');

        $this->message('RemovingObsoleteFiles');
        $errors = [];
        if (!file_exists(__DIR__ . '/' . $this->obsoleteFilesList)) {
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
            $this->continuePrompt('ContinueToDatabaseUpgrade', $params, 'database');
        } else {
            $this->nextStage('UpgradingDatabase', $params, 'database');
        }
    }

    /**
     * Upgrade the database
     *
     * @param array $params Extra params
     *
     * @return bool
     */
    protected function upgradeDatabase(array $params)
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
        $this->continuePrompt('Continue', $params, 'preflight');

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
        $filesWritten = false;
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
     * @param string $msg    Message
     * @param bool   $simple Whether to display a plain message without alert style
     *
     * @return void
     */
    protected function message($msg, bool $simple = false)
    {
        $msg = Translator::translate($msg);
        if ($simple) {
            echo <<<EOT
<div class="form_container">
  <p>$msg</p>
</div>
EOT;
        } else {
            echo <<<EOT
<div class="form_container">
  <div class="alert alert-success message">
    $msg
  </div>
</div>
EOT;
        }
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
     * @param array  $params  Extra params
     * @param string $stage   Next stage
     *
     * @return void
     */
    protected function continuePrompt($message, array $params, string $stage)
    {
        $target = "index.php?func=system&operation=update&stage=$stage";
        if ($params) {
            $target .= '&' . http_build_query($params);
        }
        $message = Translator::translate($message);
        echo <<<EOT
<div class="form_container">
  <a role="button" class="btn btn-primary" href="$target">$message</a>
</div>
EOT;
    }

    /**
     * Prompt for start updating
     *
     * @param array $params Extra params
     *
     * @return void
     */
    protected function startUpdatePrompt(array $params)
    {
        $backupDescription = Translator::translate('UpdateBackupDescription');
        $createBackup = Translator::translate('UpdateCreateBackup');
        $message = Translator::translate('StartUpdate');
        $hiddenFields = '';
        foreach ($params as $name => $value) {
            $name = htmlspecialchars($name);
            $value = htmlspecialchars($value);
            $hiddenFields .= "    <input type=\"hidden\" name=\"$name\" value=\"$value\">\n";
        }
        getPostOrQuery('ignore_version_check')
            ? '<input type="hidden" name="ignore_version_check" value="1">'
            : '';
        echo <<<EOT
<div class="form_container">
  <form action="index.php">
    <input type="hidden" name="func" value="system">
    <input type="hidden" name="operation" value="update">
    <input type="hidden" name="stage" value="start">
$hiddenFields
    <p>$backupDescription</p>

    <div class="form-check mb-3">
      <input class="form-check-input" type="checkbox" name="backup" id="backup_field" value="1" checked>
      <label class="form-check-label" for="backup_field">$createBackup</label>
    </div>

    <button type="submit" class="btn btn-primary">$message</button>
  </form>
</div>
EOT;
    }

    /**
     * Redirect to next stage
     *
     * @param string $message Message
     * @param array  $params  Extra params
     * @param string $stage   Next stage
     *
     * @return void
     */
    protected function nextStage($message, array $params, string $stage)
    {
        $target = "index.php?func=system&operation=update&stage=$stage";
        if ($params) {
            $target .= '&' . http_build_query($params);
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

    /**
     * Get backup directory
     *
     * @return string
     */
    protected function getBackupDir(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR . 'backup';
    }

    /**
     * Get backup file
     *
     * @return string
     */
    protected function getBackupFile(): string
    {
        return $this->getBackupDir() . DIRECTORY_SEPARATOR . 'backup.zip';
    }
}
