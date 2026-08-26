<?php

declare(strict_types=1);

namespace Mediadreams\MdCalendarizeFrontend\Property;

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
use Mediadreams\MdCalendarizeFrontend\Property\TypeConverter\TimestampConverter;
use TYPO3\CMS\Extbase\Property\PropertyMappingConfiguration;
use TYPO3\CMS\Extbase\Property\PropertyMappingConfigurationInterface;
use TYPO3\CMS\Extbase\Property\TypeConverter\DateTimeConverter;
use TYPO3\CMS\Extbase\Property\TypeConverter\PersistentObjectConverter;

/**
 * Configures Extbase property mapping for the "event" argument's nested
 * "calendarize" sub-form items (repeatable date/time rows).
 */
final readonly class EventArgumentPropertyMappingConfigurator
{
    public function __construct(private TimestampConverter $timestampConverter) {}

    /**
     * @param array<array-key, mixed> $eventArguments Raw "event" argument sub-array from the request
     * @return array<array-key, mixed> The (possibly mutated) "event" argument sub-array
     */
    public function configure(
        PropertyMappingConfiguration $eventPropertyMappingConfiguration,
        string $action,
        array $eventArguments,
        string $dateFormat,
        string $timeFormat,
    ): array {
        $eventPropertyMappingConfiguration->skipProperties('mdUser');

        $calendarizeItems = $eventArguments['calendarize'] ?? null;

        if (($action === 'create' || $action === 'update') && is_array($calendarizeItems)) {
            $eventArguments['calendarize'] = $this->configureCalendarizeItems(
                // forProperty() (unlike getConfigurationFor()) always links the sub-configuration
                // into the parent's configuration graph, so the mutations below are guaranteed to
                // be seen by the property mapper regardless of what trusted-properties already set up.
                $eventPropertyMappingConfiguration->forProperty('calendarize'),
                $calendarizeItems,
                $dateFormat,
                $timeFormat,
            );
        } elseif ($action === 'update') {
            $eventArguments['calendarize'] = null;
        }

        return $eventArguments;
    }

    /**
     * @param array<array-key, mixed> $calendarizeItems
     * @return array<array-key, mixed>
     */
    private function configureCalendarizeItems(
        PropertyMappingConfigurationInterface $calendarizePropertyMappingConfiguration,
        array $calendarizeItems,
        string $dateFormat,
        string $timeFormat,
    ): array {
        foreach ($calendarizeItems as $key => $items) {
            if (!is_array($items)) {
                continue;
            }

            $itemKey = (string)$key;
            if ($itemKey === '') {
                continue;
            }

            $calendarizePropertyMappingConfiguration->allowProperties($itemKey);
            $itemConfiguration = $calendarizePropertyMappingConfiguration->forProperty($itemKey);
            $itemConfiguration->allowProperties(
                '__identity',
                'startDate',
                'endDate',
                'startTime',
                'endTime',
                'openEndTime',
                'allDay',
                'type',
                'handling',
                'state',
                'day',
            );
            $itemConfiguration->setTypeConverterOption(
                PersistentObjectConverter::class,
                PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED,
                true
            );

            $startTime = $items['startTime'] ?? '';
            $endTime = $items['endTime'] ?? '';
            if ($startTime === '') {
                $items['startTime'] = 0;
            }
            if ($endTime === '') {
                $items['endTime'] = 0;
            }
            $calendarizeItems[$key] = $items;

            $itemConfiguration
                ->forProperty('startDate')
                ->setTypeConverterOption(
                    DateTimeConverter::class,
                    DateTimeConverter::CONFIGURATION_DATE_FORMAT,
                    $dateFormat
                );

            $itemConfiguration
                ->forProperty('endDate')
                ->setTypeConverterOption(
                    DateTimeConverter::class,
                    DateTimeConverter::CONFIGURATION_DATE_FORMAT,
                    $dateFormat
                );

            if ($startTime !== '') {
                $this->configureTimestampConverter($itemConfiguration->forProperty('startTime'), $timeFormat);
            }

            if ($endTime !== '') {
                $this->configureTimestampConverter($itemConfiguration->forProperty('endTime'), $timeFormat);
            }
        }

        return $calendarizeItems;
    }

    private function configureTimestampConverter(
        PropertyMappingConfigurationInterface $configuration,
        string $timeFormat,
    ): void {
        if (!$configuration instanceof PropertyMappingConfiguration) {
            throw new \LogicException('Expected a concrete property mapping configuration.', 1787654591);
        }

        $configuration
            ->setTypeConverter($this->timestampConverter)
            ->setTypeConverterOption(
                TimestampConverter::class,
                TimestampConverter::CONFIGURATION_DATE_FORMAT,
                $timeFormat
            );
    }
}
