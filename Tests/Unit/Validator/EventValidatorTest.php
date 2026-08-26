<?php

declare(strict_types=1);

namespace Mediadreams\MdCalendarizeFrontend\Tests\Unit\Validator;

use Mediadreams\MdCalendarizeFrontend\Domain\Model\Event;
use Mediadreams\MdCalendarizeFrontend\Validator\EventValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Extbase\Validation\Error;

#[CoversClass(EventValidator::class)]
final class EventValidatorTest extends TestCase
{
    private EventValidator $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new class extends EventValidator {
            /**
             * @param array<array-key, mixed> $arguments
             */
            protected function translateErrorMessage(
                string $translateKey,
                string $extensionName = '',
                array $arguments = []
            ): string {
                return $translateKey;
            }
        };
    }

    public function testAcceptsValidEventArguments(): void
    {
        $event = new Event();
        $event->setTitle('TYPO3 event');
        $this->subject->setRequest($this->createRequest([
            '0' => ['startDate' => '25.08.2026'],
        ]));

        self::assertFalse($this->subject->validate($event)->hasErrors());
    }

    public function testAddsErrorsForEmptyTitleAndStartDate(): void
    {
        $event = new Event();
        $this->subject->setRequest($this->createRequest([
            '3' => ['startDate' => ''],
        ]));

        $result = $this->subject->validate($event);

        $titleError = $result->forProperty('title')->getFirstError();
        $startDateError = $result->forProperty('calendarize.3.startDate')->getFirstError();
        self::assertInstanceOf(Error::class, $titleError);
        self::assertSame(1593464351, $titleError->getCode());
        self::assertInstanceOf(Error::class, $startDateError);
        self::assertSame(1593465345, $startDateError->getCode());
    }

    public function testIgnoresUnexpectedRequestStructureWithoutFailing(): void
    {
        $event = new Event();
        $event->setTitle('TYPO3 event');
        $request = (new ServerRequest())->withParsedBody([
            'tx_mdcalendarizefrontend_frontend' => 'unexpected',
        ]);
        $this->subject->setRequest($request);

        self::assertFalse($this->subject->validate($event)->hasErrors());
    }

    public function testRejectsInvalidCalendarizeFieldType(): void
    {
        $event = new Event();
        $event->setTitle('TYPO3 event');
        $this->subject->setRequest($this->createRequest([
            '0' => ['startDate' => ['unexpected']],
        ]));

        $result = $this->subject->validate($event);

        $startDateError = $result->forProperty('calendarize.0.startDate')->getFirstError();
        self::assertInstanceOf(Error::class, $startDateError);
        self::assertSame(1593465345, $startDateError->getCode());
    }

    public function testRejectsMalformedCalendarizeItemUsingSafePropertyPath(): void
    {
        $event = new Event();
        $event->setTitle('TYPO3 event');
        $this->subject->setRequest($this->createRequest([
            'unexpected.key' => 'unexpected',
        ]));

        $result = $this->subject->validate($event);

        $startDateError = $result->forProperty('calendarize.0.startDate')->getFirstError();
        self::assertInstanceOf(Error::class, $startDateError);
        self::assertSame(1593465345, $startDateError->getCode());
    }

    public function testRejectsUnsupportedModel(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionCode(1787662765);

        $this->subject->validate(new \stdClass());
    }

    /**
     * @param array<array-key, mixed> $calendarizeItems
     */
    private function createRequest(array $calendarizeItems): ServerRequest
    {
        return (new ServerRequest())->withParsedBody([
            'tx_mdcalendarizefrontend_frontend' => [
                'event' => [
                    'calendarize' => $calendarizeItems,
                ],
            ],
        ]);
    }
}
