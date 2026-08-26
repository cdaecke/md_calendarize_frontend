<?php

declare(strict_types=1);

namespace Mediadreams\MdCalendarizeFrontend\Tests\Unit\Property\TypeConverter;

use Mediadreams\MdCalendarizeFrontend\Property\TypeConverter\TimestampConverter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Extbase\Property\Exception\InvalidPropertyMappingConfigurationException;
use TYPO3\CMS\Extbase\Property\PropertyMappingConfigurationInterface;
use TYPO3\CMS\Extbase\Validation\Error;

#[CoversClass(TimestampConverter::class)]
final class TimestampConverterTest extends TestCase
{
    private TimestampConverter $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new TimestampConverter();
    }

    public function testConvertsTimeToSecondsSinceMidnight(): void
    {
        $configuration = $this->createConfiguration('H:i');

        self::assertSame(45_000, $this->subject->convertFrom('12:30', 'int', [], $configuration));
    }

    public function testReturnsNullForEmptyInput(): void
    {
        self::assertNull($this->subject->convertFrom('', 'int'));
    }

    public function testKeepsIntegerInput(): void
    {
        self::assertSame(45_000, $this->subject->convertFrom(45_000, 'int'));
    }

    public function testReturnsValidationErrorForInvalidTime(): void
    {
        $configuration = $this->createConfiguration('H:i');

        $result = $this->subject->convertFrom('25:61', 'int', [], $configuration);

        self::assertInstanceOf(Error::class, $result);
        self::assertSame(1307719788, $result->getCode());
    }

    public function testRejectsMissingConfiguration(): void
    {
        $this->expectException(InvalidPropertyMappingConfigurationException::class);

        $this->subject->convertFrom('12:30', 'int');
    }

    private function createConfiguration(string $dateFormat): PropertyMappingConfigurationInterface
    {
        $configuration = self::createStub(PropertyMappingConfigurationInterface::class);
        $configuration
            ->method('getConfigurationValue')
            ->willReturn($dateFormat);

        return $configuration;
    }
}
