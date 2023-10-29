/* global MLInvoice, $, bootstrap, Sortable, moment */
MLInvoice.addModule('Search', function mlinvoiceSearch() {
  let formConfig = null;
  let groupCount = 0;

  function addSearchGroup() {
    addGroup();
    return false;
  }

  function addGroup() {
    let template = document.getElementById('template_group');
    let newGroup = template.content.cloneNode(true);
    let groupNode = newGroup.querySelector('.group');
    groupNode.dataset.group = ++groupCount;
    groupNode.querySelector('.operator').name = 's_op' + groupCount;
    document.querySelector('#search_groups').appendChild(newGroup);
    updateGroupOperatorVisibility();
    return groupNode;
  }

  function deleteSearchGroup() {
    this.closest('.group').remove();
    updateGroupOperatorVisibility();
    return false;
  }

  function deleteSearchField() {
    let group = this.closest('.group');
    this.closest('.field').remove();
    updateFieldOperatorVisibility(group);
    return false;
  }

  function addSearchField() {
    let $select = $(this);
    let $group = $select.closest('.group');
    let field = $select.val();
    addField($group.get(0), field, 'eq');
    $select.val('');
    return false;
  }

  function addField(group, field, selectedComparison) {
    let groupNum = group.dataset.group;
    let $fields = $(group).find('.fields');
    let fieldNum = group.querySelectorAll('.fields .field').length + 1;
    let fieldConfig = MLInvoice.Search.getFormConfig().fields[field];

    let $fieldDiv = $('<div class="mb-2 field">')
      .appendTo($fields);
    let $div = $('<div class="mb-2 field-contents">')
      .appendTo($fieldDiv);
    let fieldId = 'field-' + groupNum + '-' + fieldNum;
    $('<label class="form-label" for="' + fieldId + '">')
      .text(fieldConfig.label)
      .appendTo($div);
    let $input = null;
    let $comparison = null;
    let comparisons = {
      eq: { label: MLInvoice.translate('SearchEqual') },
      ne: { label: MLInvoice.translate('SearchNotEqual') }
    };
    if ('INT' === fieldConfig['type'] || 'INTDATE' === fieldConfig['type']) {
      comparisons.lt = { label: '<', title: MLInvoice.translate('SearchLessThan') };
      comparisons.lte = { label: '<=', title: MLInvoice.translate('SearchLessThanOrEqual') };
      comparisons.gt = { label: '>', title: MLInvoice.translate('SearchGreaterThan') };
      comparisons.gte = { label: '>=', title: MLInvoice.translate('SearchGreaterThanOrEqual') };
    } else if ('CHECK' === fieldConfig['type']) {
        delete comparisons.ne;
    }
    switch (fieldConfig['type']) {
      case 'TEXT':
      case 'AREA':
        $input = $('<input type="text" class="form-control medium">');
        break;
      case 'INT':
        $input = $('<input type="text" class="form-control medium">');
        break;
      case 'INTDATE':
        $input = $('<input type="date" class="form-control date">');
        break;
      case 'SEARCHLIST':
        $input = $('<select class="select2-container js-searchlist" id="' + fieldId + '" value="" data-show-empty="1">')
          .data('list-query', fieldConfig.listquery);
        break;
      case 'SELECT':
      case 'LIST':
        $input = $('<select class="form-control medium">');
        $('<option>').attr('value', '').text('-').appendTo($input);
        Object.getOwnPropertyNames(fieldConfig['options']).forEach(
          function addOption(key) {
            $('<option>').attr('value', key).text(fieldConfig['options'][key]).appendTo($input);
          }
        );
        break;
      case 'CHECK':
        $input = $('<select class="form-control medium">');
        $('<option>').attr('value', '0').prop('selected', 'selected').text(MLInvoice.translate('Unselected')).appendTo($input);
        $('<option>').attr('value', '1').prop('selected', 'selected').text(MLInvoice.translate('Selected')).appendTo($input);
        break;
    }
    if (null !== $input) {
      $('<input type="hidden">')
        .attr('name', 's_type' + groupNum + '[]')
        .val(field)
        .appendTo($div);
      let $row = $('<div class="row">').appendTo($div);
      let $compCol = $('<div class="col-auto">').appendTo($row);
      $comparison = $('<select class="form-select short">')
        .attr('name', 's_cmp' + groupNum + '[]')
        .appendTo($compCol);
      Object.getOwnPropertyNames(comparisons).forEach(
        function addCmpOption(key) {
          let $opt = $('<option>').attr('value', key).text(comparisons[key].label).appendTo($comparison);
          if (typeof comparisons[key].title !== 'undefined') {
            $opt.attr('title', comparisons[key].title);
          }
          if (key === selectedComparison) {
            $opt.attr('selected', 'selected');
          }
        }
      );
      $input.attr('name', 's_field' + groupNum + '[]');
      let $inputCol = $('<div class="col">').appendTo($row);
      $input.appendTo($inputCol);
      $deleteContainer = $('<div class="buttons">')
        .appendTo($fieldDiv);
      $('<a role="button" class="btn btn-outline-primary btn-sm delete-field">')
        .html('<i class="icon-minus"></i>')
        .attr('title', MLInvoice.translate('DelRow'))
        .attr('aria-label', MLInvoice.translate('DelRow'))
        .appendTo($deleteContainer);
      MLInvoice.Form.setupSelect2($div);
    }
    updateFieldOperatorVisibility(group);

    return $input.get(0);
  }

  function updateFieldOperatorVisibility(group) {
    group.querySelector('.field-operator').classList.toggle('hidden', group.querySelectorAll('.field').length < 2);
  }

  function updateGroupOperatorVisibility() {
    document.querySelector('.group-operator').classList.toggle('hidden', document.querySelectorAll('.group').length < 2);
  }

  function initSearchForm(formConfig, searchGroups) {
    this.formConfig = formConfig;
    $('#search_form').on('change', '.add-search-field', addSearchField);
    $('#search_form').on('click', '#add_group', addSearchGroup);
    $('#search_form').on('click', '.delete-group', deleteSearchGroup);
    $('#search_form').on('click', '.delete-field', deleteSearchField);

    if (0 === searchGroups.length) {
      addGroup();
    } else {
      searchGroups.forEach(function handleGroup(group) {
        let groupElem = addGroup();
        groupElem.querySelector('.field-operator .operator').value = group.operator;
        group.fields.forEach(function handleField(field) {
          let fieldElem = addField(groupElem, field.name, field.comparison);
          if ('SEARCHLIST' === formConfig.fields[field.name].type) {
            $(fieldElem).select2('val', field.value);
          } else {
            fieldElem.value = field.value;
          }
        });
      });
    }
  }

  function getFormConfig() {
    return this.formConfig;
  }

  return {
    initSearchForm: initSearchForm,
    getFormConfig: getFormConfig
  };
});
