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

use TYPO3\CMS\Extbase\Property\TypeConverter\AbstractTypeConverter;

/**
 * Class TimestampConverter
 * @package Mediadreams\MdCalendarizeFrontend\Property\TypeConverter
 */
class TimestampConverter extends AbstractTypeConverter
{
    /**
     * @var string
     */
    const CONFIGURATION_DATE_FORMAT = 'dateFormat';

    /**
     * Converts $source to a int using the configured dateFormat
     *
     * @param string|int|array $source the string to be converted to a \DateTime object
     * @param string $targetType must be "DateTime"
     * @param array $convertedChildProperties not used currently
     * @param \TYPO3\CMS\Extbase\Property\PropertyMappingConfigurationInterface $configuration
     * @return int|mixed|\TYPO3\CMS\Extbase\Error\Error|\TYPO3\CMS\Extbase\Validation\Error|null
     */
    public function convertFrom(
        $source,
        $targetType,
        array $convertedChildProperties = array(),
        \TYPO3\CMS\Extbase\Property\PropertyMappingConfigurationInterface $configuration = null
    ) {
        if (empty($source)) {
            return null;
        }

        $dateFormat = $configuration->getConfigurationValue(
            \Mediadreams\MdCalendarizeFrontend\Property\TypeConverter\TimestampConverter::class,
            self::CONFIGURATION_DATE_FORMAT
        );

        $dateObj = \DateTime::createFromFormat($dateFormat, $source);
        if ($dateObj === false || $dateObj->format($dateFormat) != $source) {
            return new \TYPO3\CMS\Extbase\Validation\Error('The time "%s" was not recognized (for format "%s").',
                1307719788, [$source, $dateFormat]);
        }

        $date = new \DateTime("1970-01-01 $source", new \DateTimeZone('UTC'));
        return (int)$date->getTimestamp();
    }
}
