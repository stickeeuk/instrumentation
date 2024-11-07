<?php

declare(strict_types=1);

use Stickee\PhpCsFixerConfig;

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__ . '/src')
    ->in(__DIR__ . '/config')
    ->in(__DIR__ . '/tests')
    ->append([
        __DIR__ . '/.php-cs-fixer.dist.php',
    ]);

$overrideRules = [];

$config = PhpCsFixerConfig\Factory::fromRuleSet(
    new PhpCsFixerConfig\RuleSet\Php83(),
    $overrideRules
);

$config
    ->setFinder($finder)
    ->setCacheFile(__DIR__ . '/.php-cs-fixer.cache');

$config->setParallelConfig(PhpCsFixer\Runner\Parallel\ParallelConfigFactory::detect());

return $config;
