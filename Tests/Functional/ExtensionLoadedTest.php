<?php

declare(strict_types=1);

namespace Mediadreams\MdCalendarizeFrontend\Tests\Functional;

/***
 *
 * This file is part of the "Calendarize frontend" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 *  (c) 2020 Christoph Daecke <typo3@mediadreams.org>
 *
 ***/
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

#[CoversNothing]
final class ExtensionLoadedTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = ['lochmueller/calendarize', 'mediadreams/md_calendarize_frontend'];

    protected bool $initializeDatabase = false;

    #[Test]
    public function isLoaded(): void
    {
        self::assertTrue(
            ExtensionManagementUtility::isLoaded('md_calendarize_frontend'),
        );
    }
}
