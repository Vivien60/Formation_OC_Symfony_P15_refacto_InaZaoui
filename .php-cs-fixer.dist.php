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
    ->setRules([
        '@Symfony'               => true,
        'binary_operator_spaces' => ['operators' => ['=>' => 'align_single_space']],
    ])
    ->setFinder($finder)
;
