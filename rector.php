<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\PHPUnit\Set\PHPUnitSetList;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/config',
        __DIR__.'/phpstan',
        __DIR__.'/public',
        __DIR__.'/src',
        __DIR__.'/tests',
    ])
    ->withPhpSets()
    ->withImportNames(importShortClasses: false, removeUnusedImports: true)
    ->withAttributesSets()
    ->withSymfonyContainerXml(
        __DIR__.'/var/cache/dev/App_KernelDevDebugContainer.xml'
    )
    ->withSets([
        PHPUnitSetList::PHPUNIT_120,
    ])
    ->withTypeCoverageLevel(0)
    ->withDeadCodeLevel(0)
    ->withCodeQualityLevel(0);
