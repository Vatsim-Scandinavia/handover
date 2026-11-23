<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use RectorLaravel\Rector\ClassMethod\MigrateToSimplifiedAttributeRector;
use RectorLaravel\Rector\FuncCall\AppToResolveRector;
use RectorLaravel\Rector\MethodCall\AssertSeeToAssertSeeHtmlRector;
use RectorLaravel\Rector\StaticCall\CarbonToDateFacadeRector;
use RectorLaravel\Set\LaravelSetList;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/app',
        __DIR__.'/bootstrap',
        __DIR__.'/config',
        __DIR__.'/database',
        __DIR__.'/routes',
        __DIR__.'/tests',
    ])
    ->withSkip([
        __DIR__.'/bootstrap/cache',
        // Importing names here clashes with the `App` facade alias.
        __DIR__.'/config/app.php',
        // assertSeeHtml stops escaping, which changes what tests assert.
        AssertSeeToAssertSeeHtmlRector::class,
        // Style-only rewrites that add no type information.
        AppToResolveRector::class,
        CarbonToDateFacadeRector::class,
        // A token() accessor would shadow Passport's HasApiTokens::token().
        MigrateToSimplifiedAttributeRector::class => [__DIR__.'/app/User.php'],
    ])
    ->withCache(__DIR__.'/storage/framework/cache/rector')
    // Targets the minimum PHP version from composer.json.
    ->withPhpSets()
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        typeDeclarations: true,
        typeDeclarationDocblocks: true,
        earlyReturn: true,
        instanceOf: true,
    )
    ->withComposerBased(laravel: true)
    ->withSets([
        LaravelSetList::LARAVEL_CODE_QUALITY,
        LaravelSetList::LARAVEL_COLLECTION,
        LaravelSetList::LARAVEL_TYPE_DECLARATIONS,
    ])
    ->withImportNames(importShortClasses: false, removeUnusedImports: true);
