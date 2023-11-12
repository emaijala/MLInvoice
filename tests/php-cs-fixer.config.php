<?php

$finder = PhpCsFixer\Finder::create()
    ->exclude('node_modules')
    ->exclude('tests/_output')
    ->exclude('tests/Support')
    ->notName('c3.php')
    ->in(__DIR__ . '/..');

$rules = [
    'align_multiline_comment' => true,
    'array_syntax' => ['syntax' => 'short'],
    'binary_operator_spaces' => [
        'default' => 'single_space',
        'operators' => ['=' => null, '=>' => null],
    ],
    'blank_line_after_namespace' => true,
    'blank_lines_before_namespace' => false,
    //'braces' => true,
    'cast_spaces' => ['space' => 'none'],
    'class_attributes_separation' => ['elements' => ['method' => 'one', 'property' => 'one']],
    'concat_space' => ['spacing' => 'one'],
    'constant_case' => ['case' => 'lower'],
    'elseif' => true,
    'encoding' => true,
    'ereg_to_preg' => true,
    'full_opening_tag' => true,
    'function_declaration' => true,
    'type_declaration_spaces' => true,
    'indentation_type' => true,
    'is_null' => true,
    'line_ending' => true,
    'linebreak_after_opening_tag' => true,
    'list_syntax' => ['syntax' => 'short'],
    'lowercase_cast' => true,
    'lowercase_keywords' => true,
    'magic_constant_casing' => true,
    //'method_argument_space' => true,
    'native_function_casing' => true,
    'no_blank_lines_after_class_opening' => true,
    'no_blank_lines_after_phpdoc' => true,
    'no_closing_tag' => true,
    'no_empty_comment' => true,
    'no_empty_phpdoc' => true,
    'no_empty_statement' => true,
    'no_extra_blank_lines' => true,
    'no_leading_import_slash' => true,
    'no_leading_namespace_whitespace' => true,
    'no_mixed_echo_print' => true,
    'no_singleline_whitespace_before_semicolons' => true,
    'no_spaces_after_function_name' => true,
    'no_spaces_around_offset' => true,
    'no_trailing_whitespace' => true,
    'no_trailing_whitespace_in_comment' => true,
    'no_unneeded_control_parentheses' => true,
    'no_unneeded_braces' => true,
    'no_unused_imports' => true,
    'no_useless_return' => true,
    'no_whitespace_before_comma_in_array' => true,
    'no_whitespace_in_blank_line' => true,
    'non_printable_character' => true,
    'ordered_imports' => true,
    'phpdoc_no_access' => true,
    'php_unit_dedicate_assert_internal_type' => true,
    'php_unit_expectation' => true,
    'php_unit_method_casing' => true,
    'php_unit_mock' => true,
    'php_unit_no_expectation_annotation' => true,
    'pow_to_exponentiation' => true,
    'single_blank_line_at_eof' => true,
    'single_class_element_per_statement' => true,
    'single_import_per_statement' => true,
    'single_line_after_imports' => true,
    'short_scalar_cast' => true,
    'spaces_inside_parentheses' => false,
    'standardize_not_equals' => true,
    'switch_case_semicolon_to_colon' => true,
    'switch_case_space' => true,
    'ternary_operator_spaces' => true,
    'ternary_to_null_coalescing' => true,
    'visibility_required' => true,
];

$cacheDir = __DIR__ . '/../.php_cs_cache';
if (!is_dir($cacheDir)) {
    mkdir($cacheDir);
}

$config = new PhpCsFixer\Config();
return $config->setCacheFile($cacheDir . '/.code.cache')
    ->setRiskyAllowed(true)
    ->setRules($rules)
    ->setFinder($finder);
