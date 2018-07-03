<?php

// http://cs.sensiolabs.org/#usage
$finder = PhpCsFixer\Finder::create()
    // Ignore dependencies
    ->exclude('vendor')
    ->exclude('wordpress')
    // Include everything else
    ->in(__DIR__)
;

return PhpCsFixer\Config::create()
    ->setRules([
        // If you're curious what these do, use `php-cs-fixer describe <key>`
        '@PSR1' => true,
        '@PSR2' => true,
        'align_multiline_comment' => ['comment_type' => 'phpdocs_only'],
        'array_syntax' => ['syntax' => 'short'],
        'binary_operator_spaces' => true,
        'blank_line_after_opening_tag' => true,
        'cast_spaces' => ['space' => 'single'],
        'class_attributes_separation' => true,
        'class_attributes_separation' => true,
        'compact_nullable_typehint' => true,
        'concat_space' => ['spacing' => 'one'],
        'declare_equal_normalize' => ['space' => 'single'],
        'function_typehint_space' => true,
        'include' => true,
        'linebreak_after_opening_tag' => true,
        'list_syntax' => ['syntax' => 'short'],
        'lowercase_cast' => true,
        'magic_constant_casing' => true,
        'method_chaining_indentation' => true,
        'method_separation' => true,
        'native_function_casing' => true,
        'new_with_braces' => true,
        'no_blank_lines_after_class_opening' => true,
        'no_blank_lines_after_phpdoc' => true,
        'no_blank_lines_before_namespace' => true,
        'no_empty_phpdoc' => true,
        'no_empty_statement' => true,
        'no_leading_import_slash' => true,
        'no_leading_namespace_whitespace' => true,
        'no_mixed_echo_print' => true,
        'no_multiline_whitespace_around_double_arrow' => true,
        'no_short_bool_cast' => true,
        'no_spaces_around_offset' => true,
        'no_superfluous_elseif' => true,
        'no_trailing_comma_in_singleline_array' => true,
        'no_unneeded_control_parentheses' => true,
        'no_unneeded_curly_braces' => true,
        'no_unneeded_final_method' => true,
        'no_unused_imports' => true,
        'no_useless_else' => true,
        'no_useless_return' => true,
        'no_whitespace_before_comma_in_array' => true,
        'no_whitespace_in_blank_line' => true,
        'normalize_index_brace' => true,
        'normalize_index_brace' => true,
        'not_operator_with_successor_space' => false,
        'not_operator_with_successor_space' => false,
        'object_operator_without_whitespace' => true,
        'ordered_imports' => ['sortAlgorithm' => 'alpha'],
        'phpdoc_add_missing_param_annotation' => ['only_untyped' => false],
        'phpdoc_align' => ['align' => 'left'],
        'phpdoc_indent' => true,
        'phpdoc_order' => true,
        'phpdoc_scalar' => true,
        'phpdoc_separation' => false, // If only this worked with @psalm-*
        'phpdoc_single_line_var_spacing' => true,
        'phpdoc_summary' => true,
        'phpdoc_to_comment' => false,
        'phpdoc_trim' => true,
        'phpdoc_trim_consecutive_blank_line_separation' => true,
        'phpdoc_types' => true,
        'phpdoc_var_without_name' => true,
        'return_type_declaration' => true,
        'semicolon_after_instruction' => true,
        'short_scalar_cast' => true,
        'single_line_comment_style' => true,
        'single_quote' => true,
        'ternary_operator_spaces' => true,
        'trailing_comma_in_multiline_array' => true,
        'trim_array_spaces' => true,
        'whitespace_after_comma_in_array' => true,
        'yoda_style' => false,
    ])
    ->setFinder($finder)
    ->setCacheFile(__DIR__ . '/.php_cs.cache')
;
