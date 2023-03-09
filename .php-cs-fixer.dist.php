<?php

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__)
;

return (new PhpCsFixer\Config())
    ->setRules([
        'date_time_immutable' => true,
        'declare_strict_types' => true,
        'strict_comparison' => true,
        'phpdoc_to_comment' => true,
        'return_assignment' => true,
    ])
    ->setRiskyAllowed(true)
    ->setFinder($finder)
;
