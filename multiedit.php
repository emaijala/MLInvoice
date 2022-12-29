<?php
/**
 * Multi-record edit
 *
 * PHP version 7
 *
 * Copyright (C) Ere Maijala 2018-2019.
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
require_once 'form_config.php';

/**
 * Multi-record edit
 *
 * @category MLInvoice
 * @package  MLInvoice\Base
 * @author   Ere Maijala <ere@labs.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://labs.fi/mlinvoice.eng.php
 */
class MultiEdit
{
    /**
     * View the editor
     *
     * @return void
     */
    public function launch()
    {
        $ids = getPost('id');

        if (empty($ids)) {
            die('Invalid request');
        }

        $strForm = getPostOrQuery('form', '');
        $list = getPostOrQuery('list', '');

        $formConfig = getFormConfig($strForm, 'multiedit');

        $messages = [];
        $errors = [];

        if (getPost('submit')) {
            // Collect changes
            $changes = [];
            foreach (getPost('') as $field => $value) {
                if (strncmp($field, 'select_', 7) !== 0) {
                    continue;
                }
                $field = substr($field, 7);
                $changes[$field] = getPost($field);
            }

            if (empty($changes)) {
                $errors[] = Translator::translate('NoFieldsSelectedForEditing');
            } else {
                // Execute changes
                $changeCount = 0;
                foreach ((array)$ids as $id) {
                    $fieldData = $changes;
                    $result = saveFormData(
                        $formConfig['table'], $id, $formConfig['fields'], $fieldData, $warnings,
                        '', null, false, true
                    );
                    if (true !== $result) {
                        $error = $warnings ? $warnings
                            : (Translator::translate('ErrValueMissing') . ': ' . $res);
                        $idLink = '<a href="?form=' . $strForm . '?id=' . htmlentities($id) . '" target="_blank">'
                            . htmlentities($id) . '</a>';
                        $errors[] = Translator::Translate('RecordUpdateFailed', ['%%id%%' => $idLink, '%%error%%' => $error]);
                    } else {
                        $changeCount++;
                    }
                }
                $messages[] = Translator::Translate('RecordsUpdated', ['%%count%%' => $changeCount]);
            }
        } else {
            $maxInput = ini_get('max_input_vars');
            if ($maxInput) {
                $paramCount = 0;
                foreach ($_GET as $param) {
                    $paramCount += count((array)$param);
                }
                foreach ($_POST as $param) {
                    $paramCount += count((array)$param);
                }
                if ($paramCount >= $maxInput) {
                    $errors[] = Translator::Translate('RecordsMayBeMissingInUpdate');
                }
            }
        }

        ?>
    <div class="pagewrapper profile mb-4">
        <div class="form_container multi-edit-form">
          <h1><?php echo Translator::translate('EditingMultiple', ['%%count%%' => count($ids)])?></h1>

          <?php
            foreach ($errors as $message) {
                ?>
            <div class="message alert alert-danger">
                <?php echo $message?>
            </div>
                <?php
            }
            foreach ($messages as $message) {
                ?>
            <div class="message alert alert-success">
                <?php echo $message?>
            </div>
                <?php
            } ?>

          <div class="unlimited_label">
              <?php echo Translator::translate('EditMultipleInstructions')?>
          </div>
          <form method="POST">
              <input type="hidden" name="list" value="<?php echo htmlentities($list)?>">
              <input type="hidden" name="form" value="<?php echo htmlentities($strForm)?>">
              <input type="hidden" name="func" value="multiedit">
        <?php
        foreach ($formConfig['fields'] as $elem) {
            if ($elem['type'] === false || !empty($elem['read_only'])) {
                continue;
            }
            $style = $elem['style'] !== '' ? ' ' . $elem['style'] : '';

            if (preg_match('/\bhidden\b/', $style)) {
                continue;
            }

            switch ($elem['type']) {
            case 'HEADING':
                ?>
                <div class="unlimited_label">
                    <h2><?php echo Translator::translate($elem['label'])?></h2>
                </div>
                <?php
                break;
            case 'LABEL':
                ?>
                <div class="unlimited_label">
                    <?php echo Translator::translate($elem['label'])?>
                </div>
                <?php
                break;

            case 'TEXT':
            case 'AREA':
            case 'INT':
            case 'INTDATE':
            case 'CHECK':
            case 'LIST':
            case 'SEARCHLIST':
                ?>
                <div class="tiny_label">
                   <input type="checkbox" id="select_<?php echo $elem['name']?>" name="select_<?php echo $elem['name']?>">
                </div>
                <div class="medium_label attached">
                  <label for="select_<?php echo $elem['name']?>"><?php echo Translator::translate($elem['label'])?></label>
                </div>
                <div class="field <?php echo $elem['style']?>">
                    <?php echo htmlFormElement(
                        $elem['name'], $elem['type'], '',
                        str_replace('noemptyvalue', '', $elem['style']), $elem['listquery'], 'MODIFY',
                        $elem['parent_key'] ?? '', '', [],
                        $elem['elem_attributes'] ?? '',
                        $elem['options'] ?? null
                    );?>
                </div>
                <?php
                break;
            }
        }
        ?>
            <div class="unlimited_label">
              <input type="submit" name="submit" class="btn btn-primary" value="<?php echo Translator::translate('Save')?>">
            </div>
        <?php
        // Put id's last so that if the parameter list is truncated, it won't affect other parameters.
        foreach ((array)$ids as $id) {
            ?>
            <input type="hidden" name="id[]" value="<?php echo htmlentities($id)?>">
            <?php
        }
        ?>

            <div class="clearfix"></div>
          </form>
        </div>
    </div>

    <script>
    $(document).ready(function() {
        $('input[type="text"],input[type="date"],input[type="hidden"],input[type="checkbox"]:not(.cb-select-row):not(.cb-select-all),select:not(.dropdownmenu),textarea')
        .change(function() {
            var name = $(this).attr('name');
            $('#select_' + name).prop('checked', true);
        });
        MLInvoice.Form.setupSelect2();
        MLInvoice.Form.setupMarkdownEditor();
        $('textarea.markdown').each(function() {
            var name = $(this).attr('name');
            $(this).data('mde').codemirror.on('change', function() {
                $('#select_' + name).prop('checked', true);
            });
        });
    });
    </script>

        <?php
    }
}
