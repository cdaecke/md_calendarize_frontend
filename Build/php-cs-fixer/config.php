<?php

declare(strict_types=1);

use PhpCsFixer\Runner\Parallel\ParallelConfigFactory;
use TYPO3\CodingStandards\CsFixerConfig;

$config = CsFixerConfig::create();
$config->setParallelConfig(ParallelConfigFactory::detect());
$config->getFinder()
    ->in(__DIR__ . '/../../Classes')
    ->in(__DIR__ . '/../../Configuration')
    ->in(__DIR__ . '/../../Tests')
    ->append([
        __DIR__ . '/../../ext_emconf.php',
        __DIR__ . '/../../ext_localconf.php',
    ]);

return $config;
