<?php
$finder = PhpCsFixer\Finder::create()
    ->files()
    ->name('*.php')
    ->in(__DIR__ . '/src')
    ->in(__DIR__ . '/tests')
;
return PhpCsFixer\Config::create()
    ->setUsingCache(true)
    ->setRiskyAllowed(true)
    ->setRules([
        // default rules
        '@PSR2' => true,
        // Alias
        // ArrayNotation
        'array_syntax' => ['syntax' => 'short'],
        'trailing_comma_in_multiline_array' => true,
        // Basic
        // Casing
        // CastNotation
        'cast_spaces' => true,
        // ClassNotation
        // Comment
        'no_empty_comment' => true,
        // ControlStructure
        'no_useless_else' => true,
        // DoctrineAnnotation
        // FunctionNotation
        'return_type_declaration' => ['space_before' => 'one'],
        // Import
        'no_leading_import_slash' => true,
        'no_unused_imports' => true,
        'ordered_imports' => true,
        // LanguageConstruct
        'declare_equal_normalize' => ['space' => 'single'],
        'dir_constant' => true,
        // ListNotation
        // NamespaceNotation
        // Naming
        'no_homoglyph_names' => true,
        // Operator
        'binary_operator_spaces' => true,
        'concat_space' => ['spacing' => 'one'],
        // PhpTag
        'blank_line_after_opening_tag' => true,
        // PhpUnit
        // Phpdoc
        'phpdoc_align' => true,
        'no_empty_phpdoc' => true,
        'phpdoc_scalar' => true,
        'phpdoc_separation' => true,
        'phpdoc_types_order' => ['null_adjustment' => 'always_last', 'sort_algorithm' => 'none'],
        // ReturnNotation
        // Semicolon
        'no_empty_statement' => true,
        // Strict
        // StringNotation
        // Whitespace
        'blank_line_before_statement' => ['statements' => ['declare', 'return']],
        'method_chaining_indentation' => true,
        'no_extra_consecutive_blank_lines' => true,
        'no_spaces_around_offset' => true,
        'no_whitespace_in_blank_line' => true,
    ])
    ->setFinder($finder)
;
