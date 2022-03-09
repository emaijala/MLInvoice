<?php
/**
 * Search
 *
 * PHP version 7
 *
 * Copyright (C) Ere Maijala 2022
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
require_once 'htmlfuncs.php';
require_once 'sqlfuncs.php';
require_once 'sessionfuncs.php';
require_once 'miscfuncs.php';
require_once 'datefuncs.php';
require_once 'form_config.php';

/**
 * Search
 *
 * @category MLInvoice
 * @package  MLInvoice\Base
 * @author   Ere Maijala <ere@labs.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://labs.fi/mlinvoice.eng.php
 */
class Search
{
    /**
     * Display search form
     *
     * @return void
     */
    function launch()
    {
        $form = getPostOrQuery('form', '');
        if (!$form) {
            return;
        }
        $formConfig = getFormConfig($form, 'ext_search');
        $formConfig['fields'] = array_map(
            function ($field) {
                if (isset($field['label'])) {
                    $field['label'] = Translator::translate($field['label']);
                }
                return $field;
            },
            $formConfig['fields']
        );
        $listValues = [];
        foreach ($formConfig['fields'] as $field) {
            if (in_array($field['type'], $formConfig['searchFieldTypes'])) {
                $listValues[$field['name']] = str_replace(
                    '<br>', ' ',
                    Translator::translate($field['label'])
                );
            }
        }

        $join = getQuery('join', 'AND');
        ?>

<div role="search">
  <form id="search_form" method="GET">
    <div class="mb-2 p-2 group-join hidden">
      <label for="join" class="form-label"><?php echo Translator::translate('GroupHandlingMethod')?></label>
      <select id="join" name="join" class="form-select">
        <option value="AND"<?php echo 'AND' === $join ? ' selected' : ''?>><?php echo Translator::translate('AllGroups')?></option>
        <option value="OR"<?php echo 'OR' === $join ? ' selected' : ''?>><?php echo Translator::translate('AnyGroup')?></option>
      </select>
    </div>
    <div id="search_groups">
      <template id="template_group">
        <div class="card mb-4 group">
          <div class="card-header">
            <?php echo Translator::translate('Hakuryhmä')?>
            <a href="#" role="button" class="btn btn-outline-primary btn-sm delete-group"
              title="<?php echo Translator::translate('DeleteSearchGroup')?>"
              aria-title="<?php echo Translator::translate('DeleteSearchGroup')?>"
            >
              <i class="icon-minus"></i>
            </a>
          </div>
          <div class="card-body">
            <div class="fields">
            </div>
            <div class="mb-2 mt-4">
            <?php echo htmlListBox('searchfield1', $listValues, '', 'form-select add-search-field', false, 'SelectSearchField'); ?>
            </div>
            <div class="field-join mt-4 mb-4 hidden">
              <select class="form-select join">
                <option value="AND" selected><?php echo Translator::translate('AllFieldsMustMatch')?></option>
                <option value="OR"><?php echo Translator::translate('AnyFieldMustMatch')?></option>
                <option value="NOT"><?php echo Translator::translate('NoneOfTheFieldsMustMatch')?></option>
              </select>
            </div>
            <div class="mb-2">
            </div>
          </div>
        </div>
      </template>
    </div>
    <div class="mb-2 p-2 group-add">
      <a href="#" role="button" class="btn btn-outline-primary" id="add_group">
        <i class="icon-plus"></i><?php echo Translator::translate('AddSearchGroup')?>
      </a>
    </div>
    <div class="mb-2 p-2 search-buttons">
      <a href="#" role="button" class="btn btn-primary form-submit" id="search">
        <?php echo Translator::translate('Search')?>
      </a>
    </div>
  </form>
</div>

<script>
  let formConfig = <?php echo json_encode($formConfig); ?>;
  let groupCount = 0;

  function addGroup() {
    let template = document.getElementById('template_group');
    let templateGroup = template.content.querySelector('div.group');
    let newGroup = template.content.cloneNode(true);
    let groupNode = newGroup.querySelector('.group');
    groupNode.dataset.group = ++groupCount;
    console.log(groupNode.querySelector('.join'));
    groupNode.querySelector('.join').name = 'join' + groupCount;
    document.querySelector('#search_groups').appendChild(newGroup);
    updateGroupJoinVisibility();
    return false;
  }

  function deleteGroup() {
    this.closest('.group').remove();
    updateGroupJoinVisibility();
    return false;
  }

  function deleteSearchField() {
    let group = this.closest('.group');
    this.closest('.field').remove();
    updateFieldJoinVisibility(group);
    return false;
  }

  function addSearchField() {
    let group = this.closest('.group');
    let groupNum = group.dataset.group;
    let $select = $(this);
    let $fields = $select.parents('.group').find('.fields');
    let field = $select.val();
    let fieldNum = group.querySelectorAll('input').length + group.querySelectorAll('select').length + 1;
    let fieldConfig = formConfig.fields[field];
    $select.val('');

    let $fieldDiv = $('<div class="mb-2 field">')
      .appendTo($fields);
    let $div = $('<div class="mb-2 field-contents">')
      .appendTo($fieldDiv);
    let fieldId = 'field-' + groupNum + '-' + fieldNum;
    $('<label class="form-label" for="' + fieldId + '">')
      .text(fieldConfig.label)
      .appendTo($div);
    let $input = null;
    switch (fieldConfig['type']) {
      case 'TEXT':
      case 'INT':
        $input = $('<input type="text" class="form-control medium">');
        break;
      case 'INTDATE':
        $input = $('<input type="date" class="form-control date">');
        break;
      case 'SEARCHLIST':
        $input = $('<input type="text" class="select2-container select2" id="' + fieldId + '" value="" data-show-empty="1">')
          .data('query', fieldConfig.listquery);
        break;
    }
    if (null !== $input) {
      $input.attr('name', 'field' + groupNum + '[]');
      $input.appendTo($div);
      $('<input type="hidden">')
        .attr('name', 'type' + groupNum + '[]')
        .val(field)
        .appendTo($div);
      $deleteContainer = $('<div class="buttons">')
        .appendTo($fieldDiv);
      $('<a role="button" class="btn btn-outline-primary btn-sm delete-field">')
        .html('<i class="icon-minus"></i>')
        .attr('title', MLInvoice.translate('DelRow'))
        .attr('aria-label', MLInvoice.translate('DelRow'))
        .appendTo($deleteContainer);
      MLInvoice.Form.setupSelect2($div);
    }
    updateFieldJoinVisibility(group);
    return false;
  }

  function updateFieldJoinVisibility(group) {
    group.querySelector('.field-join').classList.toggle('hidden', group.querySelectorAll('.field').length < 2);
  }

  function updateGroupJoinVisibility() {
    document.querySelector('.group-join').classList.toggle('hidden', document.querySelectorAll('.group').length < 2);
  }

  function initExtendedSearch() {
    $('#search_form').on(
      'change',
      '.add-search-field',
      addSearchField
    );
    $('#search_form').on(
      'click',
      '#add_group',
      addGroup
    );
    $('#search_form').on(
      'click',
      '.delete-group',
      deleteGroup
    );
    $('#search_form').on(
      'click',
      '.delete-field',
      deleteSearchField
    );

    addGroup();
  }

  initExtendedSearch();
</script>

        <?php
    }
}
