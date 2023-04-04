<?php

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__)
;

return (new PhpCsFixer\Config())
    ->setRules([
        '@Symfony' => true,
        'date_time_immutable' => true,
        'declare_strict_types' => true,
        'strict_comparison' => true,
        'return_assignment' => true,
    ])
    ->setRiskyAllowed(true)
    ->setFinder($finder)
;
