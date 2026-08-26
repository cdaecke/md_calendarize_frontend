<?php

declare(strict_types=1);

namespace Mediadreams\MdCalendarizeFrontend\Tests\Functional\Helper;

/***
 *
 * This file is part of the "Calendarize frontend" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 *  (c) 2021 Christoph Daecke <typo3@mediadreams.org>
 *
 ***/
use Mediadreams\MdCalendarizeFrontend\Domain\Model\Event;
use Mediadreams\MdCalendarizeFrontend\Helper\SlugHelper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

#[CoversClass(SlugHelper::class)]
final class SlugHelperTest extends FunctionalTestCase
{
    private const TABLE_NAME = 'tx_calendarize_domain_model_event';

    protected array $testExtensionsToLoad = ['lochmueller/calendarize', 'mediadreams/md_calendarize_frontend'];

    protected array $pathsToLinkInTestInstance = [
        'typo3conf/ext/md_calendarize_frontend/Tests/Functional/Helper/Fixtures/Sites/' => 'typo3conf/sites',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/Fixtures/Database/SiteStructure.csv');
    }

    #[Test]
    public function getSlugGeneratesSlugFromTitle(): void
    {
        $subject = $this->get(SlugHelper::class);

        $slug = $subject->getSlug(
            $this->createPersistedEvent(1, 1),
            ['title' => 'My New Event'],
            self::TABLE_NAME,
        );

        self::assertSame('my-new-event', $slug);
    }

    #[Test]
    public function getSlugAppendsNumericSuffixWhenSlugAlreadyExists(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/Database/ExistingEventWithSlug.csv');

        $subject = $this->get(SlugHelper::class);

        $slug = $subject->getSlug(
            $this->createPersistedEvent(2, 1),
            ['title' => 'TYPO3 event'],
            self::TABLE_NAME,
        );

        self::assertSame('typo3-event-1', $slug);
    }

    /**
     * @param positive-int $uid
     * @param positive-int $pid
     */
    private function createPersistedEvent(int $uid, int $pid): Event
    {
        $event = new Event();
        $event->_setProperty('uid', $uid);
        $event->setPid($pid);

        return $event;
    }
}
