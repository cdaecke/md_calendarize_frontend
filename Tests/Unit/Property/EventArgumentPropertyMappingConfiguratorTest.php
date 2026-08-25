<?php

declare(strict_types=1);

namespace Mediadreams\MdCalendarizeFrontend\Tests\Unit\Property;

use Mediadreams\MdCalendarizeFrontend\Property\EventArgumentPropertyMappingConfigurator;
use Mediadreams\MdCalendarizeFrontend\Property\TypeConverter\TimestampConverter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Extbase\Property\PropertyMappingConfiguration;
use TYPO3\CMS\Extbase\Property\TypeConverter\DateTimeConverter;
use TYPO3\CMS\Extbase\Property\TypeConverter\PersistentObjectConverter;

#[CoversClass(EventArgumentPropertyMappingConfigurator::class)]
final class EventArgumentPropertyMappingConfiguratorTest extends TestCase
{
    private const DATE_FORMAT = 'd.m.Y';
    private const TIME_FORMAT = 'H:i';

    private TimestampConverter $timestampConverter;
    private EventArgumentPropertyMappingConfigurator $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->timestampConverter = new TimestampConverter();
        $this->subject = new EventArgumentPropertyMappingConfigurator($this->timestampConverter);
    }

    public function testSkipsMdUserPropertyRegardlessOfAction(): void
    {
        $configuration = new PropertyMappingConfiguration();

        $this->subject->configure($configuration, 'list', [], self::DATE_FORMAT, self::TIME_FORMAT);

        self::assertTrue($configuration->shouldSkip('mdUser'));
    }

    public function testConfiguresFullCalendarizeItemForCreateAction(): void
    {
        $configuration = new PropertyMappingConfiguration();
        $eventArguments = [
            'calendarize' => [
                '0' => [
                    'startDate' => '01.09.2026',
                    'endDate' => '',
                    'startTime' => '10:00',
                    'endTime' => '12:00',
                    'allDay' => '0',
                    'openEndTime' => '0',
                    'type' => 'time',
                    'handling' => 'include',
                    'state' => 'default',
                    'day' => 'weekday',
                ],
            ],
        ];

        $result = $this->subject->configure($configuration, 'create', $eventArguments, self::DATE_FORMAT, self::TIME_FORMAT);

        // untouched, since both times were provided
        $item = self::calendarizeItem($result, '0');
        self::assertSame('10:00', $item['startTime']);
        self::assertSame('12:00', $item['endTime']);

        $calendarizeConfiguration = $configuration->forProperty('calendarize');
        self::assertTrue($calendarizeConfiguration->shouldMap('0'));

        $itemConfiguration = $calendarizeConfiguration->forProperty('0');
        foreach (['__identity', 'startDate', 'endDate', 'startTime', 'endTime', 'openEndTime', 'allDay', 'type', 'handling', 'state', 'day'] as $property) {
            self::assertTrue($itemConfiguration->shouldMap($property), sprintf('Expected property "%s" to be allowed.', $property));
        }
        self::assertTrue(
            $itemConfiguration->getConfigurationValue(
                PersistentObjectConverter::class,
                PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED
            )
        );

        foreach (['startDate', 'endDate'] as $dateProperty) {
            self::assertSame(
                self::DATE_FORMAT,
                $itemConfiguration->forProperty($dateProperty)->getConfigurationValue(
                    DateTimeConverter::class,
                    DateTimeConverter::CONFIGURATION_DATE_FORMAT
                )
            );
        }

        foreach (['startTime', 'endTime'] as $timeProperty) {
            $timePropertyConfiguration = $itemConfiguration->forProperty($timeProperty);
            self::assertSame($this->timestampConverter, $timePropertyConfiguration->getTypeConverter());
            self::assertSame(
                self::TIME_FORMAT,
                $timePropertyConfiguration->getConfigurationValue(
                    TimestampConverter::class,
                    TimestampConverter::CONFIGURATION_DATE_FORMAT
                )
            );
        }
    }

    /**
     * @param array<string, mixed> $item
     */
    #[DataProvider('emptyTimeValueProvider')]
    public function testNormalizesEmptyTimeStringsToZeroAndSkipsTimestampConverterWiring(array $item): void
    {
        $configuration = new PropertyMappingConfiguration();
        $eventArguments = ['calendarize' => ['0' => $item]];

        $result = $this->subject->configure($configuration, 'create', $eventArguments, self::DATE_FORMAT, self::TIME_FORMAT);
        $resultItem = self::calendarizeItem($result, '0');

        if ($item['startTime'] === '') {
            self::assertSame(0, $resultItem['startTime']);
            $startTimeConfiguration = $configuration->forProperty('calendarize')->forProperty('0')->forProperty('startTime');
            self::assertNull($startTimeConfiguration->getTypeConverter());
        }

        if ($item['endTime'] === '') {
            self::assertSame(0, $resultItem['endTime']);
            $endTimeConfiguration = $configuration->forProperty('calendarize')->forProperty('0')->forProperty('endTime');
            self::assertNull($endTimeConfiguration->getTypeConverter());
        }
    }

    /**
     * @return iterable<string, array{array<string, mixed>}>
     */
    public static function emptyTimeValueProvider(): iterable
    {
        yield 'empty startTime only' => [['startDate' => '01.09.2026', 'startTime' => '', 'endTime' => '12:00']];
        yield 'empty endTime only' => [['startDate' => '01.09.2026', 'startTime' => '10:00', 'endTime' => '']];
        yield 'both empty' => [['startDate' => '01.09.2026', 'startTime' => '', 'endTime' => '']];
    }

    /**
     * @param array<string, mixed> $eventArguments
     */
    #[DataProvider('updateWithoutItemsProvider')]
    public function testNullsCalendarizeOnUpdateWithoutItems(array $eventArguments): void
    {
        $configuration = new PropertyMappingConfiguration();

        $result = $this->subject->configure($configuration, 'update', $eventArguments, self::DATE_FORMAT, self::TIME_FORMAT);

        self::assertArrayHasKey('calendarize', $result);
        self::assertNull($result['calendarize']);
    }

    /**
     * @return iterable<string, array{array<string, mixed>}>
     */
    public static function updateWithoutItemsProvider(): iterable
    {
        yield 'no calendarize key' => [['title' => 'Foo']];
        yield 'calendarize explicitly null' => [['title' => 'Foo', 'calendarize' => null]];
        yield 'calendarize not an array' => [['title' => 'Foo', 'calendarize' => '']];
    }

    public function testNoopForCreateActionWithoutCalendarizeKey(): void
    {
        $configuration = new PropertyMappingConfiguration();
        $eventArguments = ['title' => 'Foo'];

        $result = $this->subject->configure($configuration, 'create', $eventArguments, self::DATE_FORMAT, self::TIME_FORMAT);

        self::assertSame(['title' => 'Foo'], $result);
    }

    public function testNoopForNonCreateOrUpdateAction(): void
    {
        $configuration = new PropertyMappingConfiguration();
        $eventArguments = ['calendarize' => ['0' => ['startDate' => '01.09.2026', 'startTime' => '10:00', 'endTime' => '12:00']]];

        $result = $this->subject->configure($configuration, 'list', $eventArguments, self::DATE_FORMAT, self::TIME_FORMAT);

        self::assertSame($eventArguments, $result);
    }

    public function testSkipsNonArrayCalendarizeItemsDefensively(): void
    {
        $configuration = new PropertyMappingConfiguration();
        $eventArguments = [
            'calendarize' => [
                '0' => 'not-an-array',
                '1' => ['startDate' => '01.09.2026', 'startTime' => '', 'endTime' => ''],
            ],
        ];

        $result = $this->subject->configure($configuration, 'create', $eventArguments, self::DATE_FORMAT, self::TIME_FORMAT);

        self::assertSame('not-an-array', self::calendarizeValue($result, '0'));
        $item = self::calendarizeItem($result, '1');
        self::assertSame(0, $item['startTime']);
        self::assertSame(0, $item['endTime']);
        self::assertFalse($configuration->forProperty('calendarize')->shouldMap('0'));
    }

    public function testConfiguresMultipleItemsIndependently(): void
    {
        $configuration = new PropertyMappingConfiguration();
        $eventArguments = [
            'calendarize' => [
                '0' => ['startDate' => '01.09.2026', 'startTime' => '10:00', 'endTime' => '12:00'],
                '1' => ['startDate' => '02.09.2026', 'startTime' => '', 'endTime' => ''],
            ],
        ];

        $result = $this->subject->configure($configuration, 'update', $eventArguments, self::DATE_FORMAT, self::TIME_FORMAT);

        $calendarizeConfiguration = $configuration->forProperty('calendarize');
        self::assertTrue($calendarizeConfiguration->shouldMap('0'));
        self::assertTrue($calendarizeConfiguration->shouldMap('1'));

        self::assertSame('10:00', self::calendarizeItem($result, '0')['startTime']);
        self::assertSame(0, self::calendarizeItem($result, '1')['startTime']);

        self::assertSame(
            $this->timestampConverter,
            $calendarizeConfiguration->forProperty('0')->forProperty('startTime')->getTypeConverter()
        );
        self::assertNull(
            $calendarizeConfiguration->forProperty('1')->forProperty('startTime')->getTypeConverter()
        );
    }

    /**
     * @param array<array-key, mixed> $result
     */
    private static function calendarizeValue(array $result, int|string $key): mixed
    {
        $calendarize = $result['calendarize'];
        self::assertIsArray($calendarize);

        return $calendarize[$key];
    }

    /**
     * @param array<array-key, mixed> $result
     * @return array<array-key, mixed>
     */
    private static function calendarizeItem(array $result, int|string $key): array
    {
        $item = self::calendarizeValue($result, $key);
        self::assertIsArray($item);

        return $item;
    }
}
