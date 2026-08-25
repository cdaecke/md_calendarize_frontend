<?php

declare(strict_types=1);

namespace Mediadreams\MdCalendarizeFrontend\Tests\Unit\ViewHelpers;

use Mediadreams\MdCalendarizeFrontend\ViewHelpers\UtcTimeViewHelper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Context\Context;
use TYPO3Fluid\Fluid\Core\ViewHelper\InvalidArgumentValueException;

#[CoversClass(UtcTimeViewHelper::class)]
final class UtcTimeViewHelperTest extends TestCase
{
    public function testFormatsSecondsSinceMidnightInUtc(): void
    {
        $subject = $this->createSubject(45_000, 'H:i');

        self::assertSame('12:30', $subject->render());
    }

    public function testFormatsDateTimeInUtc(): void
    {
        $date = new \DateTimeImmutable('2026-08-25 12:30:00+02:00');
        $subject = $this->createSubject($date, 'H:i');

        self::assertSame('10:30', $subject->render());
    }

    public function testSupportsFluidDateContentArgument(): void
    {
        $subject = new UtcTimeViewHelper(new Context());

        self::assertSame('date', $subject->getContentArgumentName());
    }

    public function testRejectsUnsupportedDateType(): void
    {
        $subject = $this->createSubject([], 'H:i');

        $this->expectException(InvalidArgumentValueException::class);
        $this->expectExceptionCode(1787668491);

        $subject->render();
    }

    private function createSubject(mixed $date, string $format): UtcTimeViewHelper
    {
        $subject = new UtcTimeViewHelper(new Context());
        $subject->setArguments([
            'date' => $date,
            'format' => $format,
        ]);

        return $subject;
    }
}
