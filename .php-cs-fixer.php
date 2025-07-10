<?php

declare(strict_types=1);

$finder = PhpCsFixer\Finder::create()
    ->in([
        __DIR__ . '/app',
        __DIR__ . '/config',
        __DIR__ . '/database',
        __DIR__ . '/routes',
        __DIR__ . '/tests',
    ])
    ->name('*.php')
    ->notName('*.blade.php')
    ->ignoreDotFiles(true)
    ->ignoreVCS(true)
    ->exclude([
        'bootstrap/cache',
        'storage',
        'vendor',
        'node_modules',
    ]);

return (new PhpCsFixer\Config())
    ->setRules([
        '@PSR2' => true,
        '@PSR12' => true,
        '@PHP80Migration' => true,
        '@PHP81Migration' => true,
        '@PHP82Migration' => true,
        
        // Array notation
        'array_syntax' => ['syntax' => 'short'],
        'array_indentation' => true,
        'trim_array_spaces' => true,
        'trailing_comma_in_multiline' => ['elements' => ['arrays', 'arguments', 'parameters']],
        'no_trailing_comma_in_singleline' => true,
        
        // Binary operators
        'binary_operator_spaces' => [
            'default' => 'single_space',
            'operators' => [
                '=>' => 'align_single_space_minimal',
                '=' => 'single_space',
            ],
        ],
        
        // Blank lines
        'blank_line_after_namespace' => true,
        'blank_line_after_opening_tag' => true,
        'blank_line_before_statement' => [
            'statements' => ['return', 'throw', 'try', 'if', 'for', 'foreach', 'while', 'switch', 'case', 'default'],
        ],
        'no_extra_blank_lines' => [
            'tokens' => ['extra', 'throw', 'use', 'use_trait', 'switch', 'case', 'default'],
        ],
        'single_blank_line_at_eof' => true,
        'single_blank_line_before_namespace' => true,
        
        // Braces
        'braces' => [
            'allow_single_line_closure' => true,
            'position_after_functions_and_oop_constructs' => 'next',
            'position_after_control_structures' => 'same',
            'position_after_anonymous_constructs' => 'same',
        ],
        
        // Casts
        'cast_spaces' => ['space' => 'single'],
        'lowercase_cast' => true,
        'short_scalar_cast' => true,
        
        // Classes
        'class_attributes_separation' => [
            'elements' => [
                'method' => 'one',
                'property' => 'one',
                'const' => 'one',
                'trait_import' => 'none',
            ],
        ],
        'class_definition' => [
            'single_line' => true,
            'single_item_single_line' => true,
            'multi_line_extends_each_single_line' => true,
            'space_before_parenthesis' => false,
        ],
        'single_class_element_per_statement' => ['elements' => ['property']],
        'visibility_required' => ['elements' => ['method', 'property']],
        
        // Comments
        'single_line_comment_style' => ['comment_types' => ['hash']],
        'no_trailing_whitespace_in_comment' => true,
        'comment_to_phpdoc' => true,
        
        // Concatenation
        'concat_space' => ['spacing' => 'one'],
        
        // Control structures
        'elseif' => true,
        'no_unneeded_control_parentheses' => [
            'statements' => ['break', 'clone', 'continue', 'echo_print', 'return', 'switch_case', 'yield'],
        ],
        'no_unneeded_curly_braces' => ['namespaces' => true],
        'yoda_style' => ['equal' => false, 'identical' => false, 'less_and_greater' => false],
        
        // Doctrine
        'doctrine_annotation_braces' => true,
        'doctrine_annotation_indentation' => true,
        'doctrine_annotation_spaces' => true,
        
        // Functions
        'function_declaration' => ['closure_function_spacing' => 'one'],
        'function_typehint_space' => true,
        'method_argument_space' => [
            'on_multiline' => 'ensure_fully_multiline',
            'keep_multiple_spaces_after_comma' => false,
        ],
        'no_spaces_after_function_name' => true,
        'return_type_declaration' => ['space_before' => 'none'],
        'single_line_throw' => false,
        
        // Imports
        'fully_qualified_strict_types' => true,
        'global_namespace_import' => [
            'import_classes' => false,
            'import_constants' => false,
            'import_functions' => false,
        ],
        'group_import' => true,
        'no_leading_import_slash' => true,
        'no_unused_imports' => true,
        'ordered_imports' => [
            'sort_algorithm' => 'alpha',
            'imports_order' => ['class', 'function', 'const'],
        ],
        'single_import_per_statement' => true,
        'single_line_after_imports' => true,
        
        // Language constructs
        'declare_equal_normalize' => ['space' => 'none'],
        'declare_strict_types' => true,
        'dir_constant' => true,
        'echo_tag_syntax' => ['format' => 'long'],
        'include' => true,
        'is_null' => true,
        'list_syntax' => ['syntax' => 'short'],
        'logical_operators' => true,
        'magic_constant_casing' => true,
        'magic_method_casing' => true,
        'native_function_casing' => true,
        'no_alias_functions' => true,
        'no_closing_tag' => true,
        'no_empty_phpdoc' => true,
        'no_empty_statement' => true,
        'no_mixed_echo_print' => ['use' => 'echo'],
        'no_short_bool_cast' => true,
        'no_unset_cast' => true,
        'normalize_index_brace' => true,
        'object_operator_without_whitespace' => true,
        'ternary_operator_spaces' => true,
        'unary_operator_spaces' => true,
        
        // Namespaces
        'no_leading_namespace_whitespace' => true,
        
        // Operators
        'increment_style' => ['style' => 'post'],
        'new_with_braces' => true,
        'not_operator_with_successor_space' => true,
        'standardize_not_equals' => true,
        
        // PHPDoc
        'align_multiline_comment' => true,
        'no_blank_lines_after_phpdoc' => true,
        'no_empty_phpdoc' => true,
        'phpdoc_add_missing_param_annotation' => true,
        'phpdoc_align' => ['align' => 'vertical'],
        'phpdoc_annotation_without_dot' => true,
        'phpdoc_indent' => true,
        'phpdoc_inline_tag_normalizer' => true,
        'phpdoc_no_access' => true,
        'phpdoc_no_alias_tag' => true,
        'phpdoc_no_empty_return' => true,
        'phpdoc_no_package' => true,
        'phpdoc_no_useless_inheritdoc' => true,
        'phpdoc_order' => true,
        'phpdoc_order_by_value' => true,
        'phpdoc_return_self_reference' => true,
        'phpdoc_scalar' => true,
        'phpdoc_separation' => true,
        'phpdoc_single_line_var_spacing' => true,
        'phpdoc_summary' => true,
        'phpdoc_to_comment' => true,
        'phpdoc_trim' => true,
        'phpdoc_trim_consecutive_blank_line_separation' => true,
        'phpdoc_types' => true,
        'phpdoc_types_order' => ['null_adjustment' => 'always_last'],
        'phpdoc_var_annotation_correct_order' => true,
        'phpdoc_var_without_name' => true,
        
        // Semicolons
        'multiline_whitespace_before_semicolons' => ['strategy' => 'no_multi_line'],
        'no_empty_statement' => true,
        'no_singleline_whitespace_before_semicolons' => true,
        'semicolon_after_instruction' => true,
        'space_after_semicolon' => ['remove_in_empty_for_expressions' => true],
        
        // Strings
        'escape_implicit_backslashes' => true,
        'heredoc_to_nowdoc' => true,
        'simple_to_complex_string_variable' => true,
        'single_quote' => ['strings_containing_single_quote_chars' => false],
        
        // Whitespace
        'array_indentation' => true,
        'compact_nullable_typehint' => true,
        'heredoc_indentation' => ['indentation' => 'start_plus_one'],
        'indentation_type' => true,
        'line_ending' => true,
        'method_chaining_indentation' => true,
        'no_spaces_around_offset' => ['positions' => ['inside', 'outside']],
        'no_spaces_inside_parenthesis' => true,
        'no_trailing_whitespace' => true,
        'no_whitespace_in_blank_line' => true,
        'types_spaces' => true,
        
        // Switch
        'switch_case_semicolon_to_colon' => true,
        'switch_case_space' => true,
        'switch_continue_to_break' => true,
    ])
    ->setFinder($finder)
    ->setLineEnding("\n")
    ->setIndent('    ')
    ->setUsingCache(true)
    ->setCacheFile(__DIR__ . '/.php-cs-fixer.cache');