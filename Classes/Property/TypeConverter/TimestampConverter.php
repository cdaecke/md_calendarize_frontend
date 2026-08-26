<?php

declare(strict_types=1);

namespace Mediadreams\MdCalendarizeFrontend\Property\TypeConverter;

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
use TYPO3\CMS\Extbase\Property\Exception\InvalidPropertyMappingConfigurationException;
use TYPO3\CMS\Extbase\Property\PropertyMappingConfigurationInterface;
use TYPO3\CMS\Extbase\Property\TypeConverter\AbstractTypeConverter;
use TYPO3\CMS\Extbase\Validation\Error;

/**
 * Class TimestampConverter
 */
final class TimestampConverter extends AbstractTypeConverter
{
    public const CONFIGURATION_DATE_FORMAT = 'dateFormat';

    /**
     * Converts $source to a int using the configured dateFormat
     *
     * @param array<array-key, mixed> $convertedChildProperties
     */
    public function convertFrom(
        $source,
        string $targetType,
        array $convertedChildProperties = [],
        ?PropertyMappingConfigurationInterface $configuration = null
    ): int|Error|null {
        if ($source === null || $source === '') {
            return null;
        }

        if (is_int($source)) {
            return $source;
        }

        if (!is_string($source)) {
            return new Error('The time value has an unsupported type.', 1787656227);
        }

        if ($configuration === null) {
            throw new InvalidPropertyMappingConfigurationException(
                'The time converter requires a property mapping configuration.',
                1787656298
            );
        }

        $dateFormat = $configuration->getConfigurationValue(
            TimestampConverter::class,
            self::CONFIGURATION_DATE_FORMAT
        );
        if (!is_string($dateFormat) || $dateFormat === '') {
            throw new InvalidPropertyMappingConfigurationException(
                'The time converter requires a non-empty date format.',
                1787656349
            );
        }

        $parseFormat = str_starts_with($dateFormat, '!') ? $dateFormat : '!' . $dateFormat;
        $displayFormat = str_starts_with($dateFormat, '!') ? substr($dateFormat, 1) : $dateFormat;
        $date = \DateTimeImmutable::createFromFormat($parseFormat, $source, new \DateTimeZone('UTC'));
        $dateErrors = \DateTimeImmutable::getLastErrors();
        if (
            $date === false
            || ($dateErrors !== false && ($dateErrors['warning_count'] > 0 || $dateErrors['error_count'] > 0))
            || $date->format($displayFormat) !== $source
        ) {
            return new Error(
                'The time "%s" was not recognized (for format "%s").',
                1307719788,
                [$source, $dateFormat]
            );
        }

        return $date->getTimestamp();
    }
}
