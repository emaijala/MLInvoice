<?php
/**
 * Initial setup
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

/**
 * Initial setup
 *
 * @category MLInvoice
 * @package  MLInvoice\Base
 * @author   Ere Maijala <ere@labs.fi>
 * @license  https://opensource.org/licenses/GPL-2.0 GNU Public License 2.0
 * @link     http://github.com/emaijala/MLInvoice
 */
class Setup
{
    /**
     * Any error encountered
     *
     * @var string
     */
    protected $errorMsg = '';

    /**
     * Initialize database and settings
     *
     * @return void
     */
    public function initialSetup()
    {
        $formMessage = '';
        $setupComplete = false;
        list($host, $database, $username, $password, $prefix, $lang, $defaultlang)
            = $this->getConfigDefaults();
        $adminPassword = '';
        if (isset($_POST['host']) && isset($_POST['database'])
            && isset($_POST['username']) && isset($_POST['password'])
            && isset($_POST['prefix']) && isset($_POST['adminpass'])
            && isset($_POST['lang']) && isset($_POST['defaultlang'])
        ) {
            $host = $_POST['host'];
            $database = $_POST['database'];
            $username = $_POST['username'];
            $password = $_POST['password'];
            $prefix = $_POST['prefix'];
            $adminPassword = $_POST['adminpass'];
            $lang = $_POST['lang'];
            $defaultlang = $_POST['defaultlang'];

            if (empty($lang)) {
                $formMessage = 'At least one language must be selected';
            } else {
                $initParams = compact(
                    'host', 'database', 'username', 'password', 'prefix', 'lang', 'defaultlang'
                );
                $db = $this->initDatabaseConnection($initParams);
                if ($db === false) {
                    $formMessage = $this->errorMsg;
                    $this->errorMsg = '';
                } else {
                    $tablesExist = $this->checkDatabaseTables($db, $prefix);
                    if (!$tablesExist && empty($adminPassword)) {
                        $formMessage = 'Password for the admin user is required';
                    } else {
                        $result = copy(
                            __DIR__ . DIRECTORY_SEPARATOR . 'config.php.sample',
                            __DIR__ . DIRECTORY_SEPARATOR . 'config.php'
                        );
                        if (false === $result) {
                            $error = error_get_last();
                            $this->errorMsg = 'Failed to copy config.php.sample to config.php: '
                                . $error['message'];
                        } else {
                            if (!$this->updateConfig($initParams)) {
                                $this->errorMsg = 'Could not write to config.php';
                            } else {
                                if (!$tablesExist) {
                                    if ($this->createDatabaseTables($adminPassword)) {
                                        $setupComplete = true;
                                    }
                                } else {
                                    $setupComplete = true;
                                }
                            }
                        }
                    }
                }
            }
        }

        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Cache-Control: post-check=0, pre-check=0', false);
        // Date in the past
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        // always modified
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');

        if (isset($_SERVER['HTTP_USER_AGENT'])
            && strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== false
        ) {
            $xUACompatible = "  <meta http-equiv=\"X-UA-Compatible\" content=\"IE=edge\">\n";
        } else {
            $xUACompatible = '';
        }

        $css = [
            'jquery/css/theme/jquery-ui.min.css',
            'css/style.css'
        ];

        foreach ($css as &$style) {
            $style = '  <link rel="stylesheet" type="text/css" href="'
                . $style . '">';
        }
        $cssLinks = implode("\n", $css);

?>
<!DOCTYPE html>
<html lang="en-US">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <?php echo $xUACompatible?>    <title>Setup</title>
    <link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
    <?php echo $cssLinks?>
</head>
<body>
    <div class="pagewrapper ui-widget ui-widget-content login">
        <div id="maintabs" class="navi ui-widget-header ui-tabs">
            <ul class="ui-tabs-nav ui-helper-clearfix ui-corner-all">
                <li class="functionlink ui-state-default ui-corner-top ui-tabs-selected ui-state-active">
                    <a class="ui-tabs-anchor functionlink">Setup</a>
                </li>
            </ul>
        </div>

        <?php
        if ($setupComplete) {
        ?>
            <div class="message ui-widger ui-state-information">
                Setup complete. <a href="login.php">Continue to login</a>.
            </div>
            <br />
        <?php
        } elseif ($this->errorMsg) {
        ?>
            <div class="ui-widget" style="padding: 30px 30px 0 30px;">
                Please correct the following issues before trying to continue:
            </div>
            <div class="message ui-widget ui-state-error">
                <?php echo $this->errorMsg?>
            </div>
            <br />
        <?php
        } else {
        ?>
            <?php if ($formMessage) { ?>
                <div class="message ui-widget ui-state-error">
                    <?php echo $formMessage?>
                </div>
            <?php } ?>
            <div class="ui-widget form" style="padding: 30px;">
                <h1>Welcome</h1>
                <p>
                    Some initial settings are needed before continuing.
                </p>
                <form method="POST" autocomplete="off">
                    <div class="ui-widget ui-widget-content ui-corner-all" style="padding: 10px; margin-bottom: 10px;">
                        <p>
                            Please enter the following database connection settings.
                        </p>
                        <p>
                            <label>Database server host:<br>
                                <input type="text" name="host" value="<?php echo htmlentities($host)?>">
                            </label>
                        </p>
                        <p>
                            <label>Database name:<br>
                                <input type="text" name="database" value="<?php echo htmlentities($database)?>">
                            </label>
                        </p>
                        <p>
                            <label>Database username:<br>
                                <input type="text" name="username" value="<?php echo htmlentities($username)?>">
                            </label>
                        </p>
                        <p>
                            <label>Database password:<br>
                                <input type="password" name="password" value="<?php echo htmlentities($password)?>">
                            </label>
                        </p>
                        <p>
                            <label>Database table name prefix:<br>
                                <input type="text" name="prefix" value="<?php echo htmlentities($prefix)?>">
                            </label>
                        </p>
                    </div>
                    <div class="ui-widget ui-widget-content ui-corner-all" style="padding: 10px; margin-bottom: 10px;">
                        <p>
                            Please enter also a password for the 'admin' user that you can use to log in after setup is complete.
                        </p>
                        <p>
                            <label>Password for the 'admin' user (new databases only):<br>
                                <input type="password" name="adminpass" value="<?php echo htmlentities($adminPassword)?>">
                            </label>
                        </p>
                    </div>
                    <div class="ui-widget ui-widget-content ui-corner-all" style="padding: 10px">
                        <p>
                            Finally, please choose the user interface languages that can be selected on login.
                        </p>
                        <p>
                            <label>
                                <input type="checkbox" name="lang[fi-FI]"<?php echo isset($lang['fi-FI']) ? ' checked="checked"' : ''?>>
                                Suomi (Finnish)
                            </label>
                            <br>
                            <label>
                                <input type="checkbox" name="lang[en-US]"<?php echo isset($lang['en-US']) ? ' checked="checked"' : ''?>>
                                English
                            </label>
                            <br>
                            <label>
                                <input type="checkbox" name="lang[sv-FI]"<?php echo isset($lang['sv-FI']) ? ' checked="checked"' : ''?>>
                                Svenska (Swedish)
                            </label>
                        </p>
                        <p>
                            <label>Default Language:<br>
                                <select name="defaultlang">
                                    <option value="fi-FI"<?php echo $defaultlang === 'fi-FI' ? ' selected="selected"' : ''?>>Suomi (Finnish)</option>
                                    <option value="en-US"<?php echo $defaultlang === 'en-US' ? ' selected="selected"' : ''?>>English</option>
                                    <option value="sv-FI"<?php echo $defaultlang === 'sv-FI' ? ' selected="selected"' : ''?>>Svenska (Swedish)</option>
                                </select>
                            </label>
                        </p>
                    </div>
                    <p>
                        <button type="submit">Continue</button>
                    </p>
                </form>
            </div>
        <?php
        }
?>
        </div>
    </div>
</body>
<?php
    }

    /**
     * Get defaults from config.php.sample
     *
     * @return array host, database, username, password, prefix, languages, default
     * language
     */
    protected function getConfigDefaults()
    {
        $host = 'localhost';
        $database = 'mlinvoice';
        $username = 'mlinvoice';
        $password = '';
        $prefix = 'mlinvoice';
        $lang = [];
        $defaultlang = 'fi-FI';

        $config = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'config.php.sample');
        if (false === $config) {
            die('Could not read config.php.sample');
        }
        if (preg_match("/define\('_DB_SERVER_', '(.*?)'\)/", $config, $matches)) {
            $host = $matches[1];
        }
        if (preg_match("/define\('_DB_NAME_', '(.*?)'\)/", $config, $matches)) {
            $database = $matches[1];
        }
        if (preg_match("/define\('_DB_USERNAME_', '(.*?)'\)/", $config, $matches)) {
            $username = $matches[1];
        }
        if (preg_match("/define\('_DB_PASSWORD_', '(.*?)'\)/", $config, $matches)) {
            $password = $matches[1];
        }
        if (preg_match("/define\('_DB_PREFIX_', '(.*?)'\)/", $config, $matches)) {
            $prefix = $matches[1];
        }
        if (preg_match("/define\('_UI_LANGUAGE_SELECTION_', '(.*?)'\)/", $config, $matches)) {
            foreach (explode('|', $matches[1]) as $choice) {
                list($code) = explode('=', $choice);
                $lang[$code] = 'on';
            }
        }
        if (preg_match("/define\('_UI_LANGUAGE_', '(.*?)'\)/", $config, $matches)) {
            $defaultlang = $matches[1];
        }

        return [$host, $database, $username, $password, $prefix, $lang, $defaultlang];
    }

    /**
     * Check if basic config exists
     *
     * @return bool
     */
    protected function configExists()
    {
        return file_exists(__DIR__ . DIRECTORY_SEPARATOR . 'config.php');
    }

    /**
     * Update config file with the given parameters
     *
     * @param array $params Parameters to update
     *
     * @return bool
     */
    protected function updateConfig($params)
    {
        $filename = __DIR__ . DIRECTORY_SEPARATOR . 'config.php';
        $config = file_get_contents($filename);
        if (false === $config) {
            return false;
        }
        $fields = [
            '_DB_SERVER_' => 'host',
            '_DB_NAME_' => 'database',
            '_DB_USERNAME_' => 'username',
            '_DB_PASSWORD_' => 'password',
            '_DB_PREFIX_' => 'prefix',
            '_UI_LANGUAGE_' => 'defaultlang'
        ];
        foreach ($fields as $key => $value) {
            if (isset($params[$value])) {
                $newVal = str_replace('\'', '\\\'', $params[$value]);
                $config = preg_replace("/\/*define\('$key'\s*,\s*'.*?'\);/", "define('$key', '$newVal');", $config);
            }
        }
        if (!empty($params['lang'])) {
            $langStrings = [
                'en-US' => 'In English',
                'fi-FI' => 'Suomeksi',
                'sv-FI' => 'På svenska'
            ];
            $choices = [];
            foreach ($params['lang'] as $lang => $set) {
                $choices[] = "$lang=" . (isset($langStrings[$lang]) ? $langStrings[$lang] : $lang);
            }
            $newVal = implode('|', $choices);
            $config = preg_replace(
                "/\/*define\('_UI_LANGUAGE_SELECTION_'\s*,\s*'.*?'\);/",
                "define('_UI_LANGUAGE_SELECTION_', '$newVal');",
                $config
            );
        }
        return file_put_contents($filename, $config) !== false;
    }

    /**
     * Initialize database connection
     *
     * @param array $settings Settings to use (overrides config.php if defined)
     *
     * @return object|false
     */
    protected function initDatabaseConnection($settings = [])
    {
        if (isset($settings['host']) && isset($settings['database'])
            && isset($settings['username']) && isset($settings['password'])
        ) {
            $host = $settings['host'];
            $database = $settings['database'];
            $username = $settings['username'];
            $password = $settings['password'];
        } else {
            include_once 'config.php';
            $host = _DB_SERVER_;
            $database = _DB_NAME_;
            $username = _DB_USERNAME_;
            $password = _DB_PASSWORD_;
        }

        $db = mysqli_connect($host, $username, $password);

        if (mysqli_connect_errno()) {
            $this->errorMsg = mysqli_connect_error();
            return false;
        }
        if (!mysqli_select_db($db, $database)) {
            $this->errorMsg = mysqli_error($db);
            return false;
        }
        if (false === mysqli_query($db, 'SET NAMES \'utf8\'')) {
            $this->errorMsg = mysqli_error($db);
            return false;
        }
        if (false === mysqli_query($db, 'SET AUTOCOMMIT=1')) {
            $this->errorMsg = mysqli_error($db);
            return false;
        }
        return $db;
    }

    /**
     * Check if database tables exist
     *
     * @param object $db     Database connection
     * @param string $prefix Table name prefix
     *
     * @return bool
     */
    protected function checkDatabaseTables($db, $prefix)
    {
        $res = mysqli_query($db, "SHOW TABLES LIKE '" . $prefix . "_%'");
        if (false === $res) {
            $this->errorMsg = mysqli_error($db);
            return false;
        }
        if (mysqli_fetch_row($res)) {
            return true;
        }
        return false;
    }

    /**
     * Create database tables
     *
     * @param string $adminPassword Password for the admin user
     *
     * @return bool
     */
    protected function createDatabaseTables($adminPassword = '')
    {
        include_once 'config.php';
        $db = $this->initDatabaseConnection();

        $createCommands = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'create_database.sql');
        $createCommands = str_replace('mlinvoice_', _DB_PREFIX_ . '_', $createCommands);

        $res = mysqli_multi_query($db, $createCommands);
        if (false === $res) {
            $this->errorMsg = mysqli_error($db);
            return false;
        }
        while (mysqli_more_results($db)) {
            mysqli_next_result($db);
        }

        if ('' !== $adminPassword) {
            $res = mysqli_query(
                $db,
                'UPDATE '  . _DB_PREFIX_ . "_users SET passwd = '"
                . password_hash($adminPassword, PASSWORD_DEFAULT) . "' WHERE "
                . "login = 'admin'"
            );
            if (false === $res) {
                die(mysqli_error($db));
            }
        }

        return true;
    }
}