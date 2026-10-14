<?php

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__)
    ->exclude('var')
    ->notPath([
        'config/bundles.php',
        'config/reference.php',
    ])
;

return (new PhpCsFixer\Config())
    ->registerCustomFixers(new PhpCsFixerCustomFixers\Fixers())
    ->setRiskyAllowed(true)
    ->setRules([
        '@Symfony' => true,
        '@PHP8x5Migration' => true,
        'no_unused_imports' => true,
        'yoda_style' => true,
        'array_syntax' => ['syntax' => 'short'],
        'no_superfluous_phpdoc_tags' => ['allow_hidden_params' => true, 'remove_inheritdoc' => true],
        'new_expression_parentheses' => false,
        'is_null' => true,
        'void_return' => true,
        'php_unit_method_casing' => ['case' => 'camel_case'],
        PhpCsFixerCustomFixers\Fixer\ClassConstantUsageFixer::name() => true,
        PhpCsFixerCustomFixers\Fixer\MultilineCommentOpeningClosingAloneFixer::name() => true,
        PhpCsFixerCustomFixers\Fixer\NoCommentedOutCodeFixer::name() => true,
        PhpCsFixerCustomFixers\Fixer\NoDoctrineMigrationsGeneratedCommentFixer::name() => true,
        PhpCsFixerCustomFixers\Fixer\NoDuplicatedArrayKeyFixer::name() => true,
        PhpCsFixerCustomFixers\Fixer\NoDuplicatedImportsFixer::name() => true,
        PhpCsFixerCustomFixers\Fixer\NoImportFromGlobalNamespaceFixer::name() => true,
        PhpCsFixerCustomFixers\Fixer\NoPhpStormGeneratedCommentFixer::name() => true,
        PhpCsFixerCustomFixers\Fixer\NoSuperfluousConcatenationFixer::name() => true,
        PhpCsFixerCustomFixers\Fixer\NoUselessCommentFixer::name() => true,
        PhpCsFixerCustomFixers\Fixer\NoUselessDirnameCallFixer::name() => true,
        PhpCsFixerCustomFixers\Fixer\NoUselessDoctrineRepositoryCommentFixer::name() => true,
        PhpCsFixerCustomFixers\Fixer\PhpUnitNoUselessReturnFixer::name() => true,
    ])
    ->setFinder($finder)
;
