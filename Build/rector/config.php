<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Php80\Rector\Class_\ClassPropertyAssignToConstructorPromotionRector;
use Rector\TypeDeclaration\Rector\ClassMethod\AddVoidReturnTypeWhereNoReturnRector;
use Ssch\TYPO3Rector\CodeQuality\General\ExtEmConfRector;
use Ssch\TYPO3Rector\Configuration\Typo3Option;
use Ssch\TYPO3Rector\Set\Typo3LevelSetList;
use Ssch\TYPO3Rector\Set\Typo3SetList;
use Ssch\TYPO3Rector\TYPO312\v0\ExtbaseActionsWithRedirectMustReturnResponseInterfaceRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/../../Classes/',
        __DIR__ . '/../../Configuration/',
        __DIR__ . '/../../Tests/',
        __DIR__ . '/../../ext_emconf.php',
        __DIR__ . '/../../ext_localconf.php',
        __DIR__ . '/../../ext_tables.php',
    ])
    ->withSkip([
        ClassPropertyAssignToConstructorPromotionRector::class,
        ExtbaseActionsWithRedirectMustReturnResponseInterfaceRector::class,
        __DIR__ . '/../../.Build/',
    ])
    ->withPhpSets(php82: true)
    ->withComposerBased(phpunit: true)
    ->withSets([
        Typo3SetList::GENERAL,
        Typo3LevelSetList::UP_TO_TYPO3_14,
    ])
    ->withPHPStanConfigs([
        Typo3Option::PHPSTAN_FOR_RECTOR_PATH,
    ])
    ->withRules([
        AddVoidReturnTypeWhereNoReturnRector::class,
    ])
    ->withImportNames(true, true, false)
    ->withConfiguredRule(ExtEmConfRector::class, [
        ExtEmConfRector::PHP_VERSION_CONSTRAINT => '8.2.0-8.5.99',
        ExtEmConfRector::TYPO3_VERSION_CONSTRAINT => '14.3.0-14.3.99',
        ExtEmConfRector::ADDITIONAL_VALUES_TO_BE_REMOVED => [],
    ]);
