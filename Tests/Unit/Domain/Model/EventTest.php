<?php

declare(strict_types=1);

namespace Mediadreams\MdCalendarizeFrontend\Tests\Unit\Domain\Model;

use HDNET\Calendarize\Domain\Model\Configuration;
use Mediadreams\MdCalendarizeFrontend\Domain\Model\Event;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Event::class)]
final class EventTest extends TestCase
{
    public function testGetFirstCalendarizeReturnsNullForEmptyStorage(): void
    {
        $subject = new Event();

        self::assertNull($subject->getFirstCalendarize());
    }

    public function testGetFirstCalendarizeReturnsFirstConfiguration(): void
    {
        $subject = new Event();
        $firstConfiguration = new Configuration();
        $subject->addCalendarize($firstConfiguration);
        $subject->addCalendarize(new Configuration());

        self::assertSame($firstConfiguration, $subject->getFirstCalendarize());
    }
}
