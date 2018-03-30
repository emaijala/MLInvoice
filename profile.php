<?php
/**
 * Profile page
 *
 * PHP version 5
 *
 * Copyright (C) Ere Maijala 2018.
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
 * Profile page
 *
 * @category MLInvoice
 * @package  MLInvoice\Base
 * @author   Ere Maijala <ere@labs.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://labs.fi/mlinvoice.eng.php
 */
class Profile
{
    /**
     * Start the updater
     *
     * @return void
     */
    public function launch()
    {
        $messages = [];
        $errors = [];
        $user = getUserById($_SESSION['sesUSERID']);
        if (getPost('submit')) {
            $newData = [];
            if (($name = getPost('name')) && $name !== $user['name']) {
                $newData['name'] = $name;
            }
            if (($email = getPost('email')) && $email !== $user['email']) {
                $newData['email'] = $email;
            }

            if ($newData) {
                updateUser($_SESSION['sesUSERID'], $newData);
                $messages[] = Translator::translate('UserInformationSaved');
            }

            $oldPassword = getPost('oldpassword', '');
            $newPassword = getPost('newpassword', '');
            $newPassword2 = getPost('newpassword2', '');
            if ($oldPassword && $newPassword && $newPassword2) {
                if (!password_verify($oldPassword, $user['passwd'])
                    && md5($oldPassword) != $user['passwd']
                ) {
                    $errors[] = Translator::translate('CurrentPasswordInvalid');
                } else {
                    if ($newPassword !== $newPassword2) {
                        $errors[] = Translator::translate('NewPasswordsDontMatch');
                    } else {
                        updateUserPassword($_SESSION['sesUSERID'], $newPassword);
                        $messages[] = Translator::translate('PasswordChanged');
                    }
                }
            }
        }
        $user = getUserById($_SESSION['sesUSERID']);
        ?>
    <div class="pagewrapper ui-widget ui-widget-content profile">
        <div class="form_container">
            <?php foreach ($errors as $message) {?>
                <div class="message ui-corner-all ui-state-error">
                    <?php echo $message?>
                </div>
            <?php } ?>
            <?php foreach ($messages as $message) {?>
                <div class="message ui-corner-all ui-state-highlight">
                    <?php echo $message?>
                </div>
            <?php } ?>

          <h3><?php echo $user['name']?></h3>

          <form method="POST">
            <div class="medium_label">
                <?php echo Translator::translate('UserID')?>
            </div>
            <div class="static-field">
                <?php echo htmlentities($user['login'])?>
            </div>
            <div class="medium_label">
              <label for="name"><?php echo Translator::translate('Name')?></label>
            </div>
            <div class="field">
              <input type="text" name="name" id="name" value="<?php echo htmlentities($user['name'])?>">
            </div>
            <div class="medium_label">
              <label for="email"><?php echo Translator::translate('Email')?></label>
            </div>
            <div class="field">
              <input type="text" name="email" id="email" value="<?php echo htmlentities($user['email'])?>">
            </div>
            <div class="field_sep"></div>

            <div class="unlimited_label">
                <?php echo Translator::translate('PasswordChangeInstructions') ?>
            </div>
            <div class="medium_label">
              <label for="oldpassword"><?php echo Translator::translate('CurrentPassword')?></label>
            </div>
            <div class="field">
              <input type="password" name="oldpassword" id="oldpassword" value="">
            </div>
            <div class="medium_label">
              <label for="newpassword"><?php echo Translator::translate('NewPassword')?></label>
            </div>
            <div class="field">
              <input type="password" name="newpassword" id="newpassword" value="">
            </div>
            <div class="medium_label">
              <label for="newpassword2"><?php echo Translator::translate('ConfirmNewPassword')?></label>
            </div>
            <div class="field">
              <input type="password" name="newpassword2" id="newpassword2" value="">
            </div>

            <div class="unlimited_label">
              <input type="submit" name="submit" class="ui-button ui-corner-all" value="<?php echo Translator::translate('Save')?>">
            </div>

            <div class="ui-helper-clearfix"></div>
          </form>
        </div>
    </div>
        <?php
    }
}
