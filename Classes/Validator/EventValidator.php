<?php

declare(strict_types=1);

namespace Mediadreams\MdCalendarizeFrontend\Validator;

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

use Mediadreams\MdCalendarizeFrontend\Domain\Model\Event;
use TYPO3\CMS\Extbase\Validation\Validator\AbstractValidator;

/**
 * Class EventValidator
 *
 * Usage example for controller action:
 * @TYPO3\CMS\Extbase\Annotation\Validate("Mediadreams\MdCalendarizeFrontend\Validator\EventValidator", param="event")
 */
class EventValidator extends AbstractValidator
{
    protected function isValid(mixed $value): void
    {
        if (!$value instanceof Event) {
            throw new \LogicException('Model "Event" is needed for validation.', 1787662765);
        }

        if ($value->getTitle() === '') {
            $this->addErrorForProperty(
                'title',
                $this->translateErrorMessage('error.code.1593464351', 'md_calendarize_frontend'),
                1593464351,
            );
        }

        foreach ($this->getSubmittedCalendarizeItems() as $key => $configItem) {
            $startDate = $configItem['startDate'] ?? null;
            if (!is_string($startDate) || trim($startDate) === '') {
                $this->addErrorForProperty(
                    'calendarize.' . $key . '.startDate',
                    'Please enter a start date for the event.',
                    1593465345,
                );
            }
        }
    }

    /**
     * @return array<int, array<array-key, mixed>>
     */
    private function getSubmittedCalendarizeItems(): array
    {
        $parsedBody = $this->request?->getParsedBody();
        if (!is_array($parsedBody)) {
            return [];
        }

        $pluginArguments = $parsedBody['tx_mdcalendarizefrontend_frontend'] ?? null;
        if (!is_array($pluginArguments)) {
            return [];
        }

        $eventArguments = $pluginArguments['event'] ?? null;
        if (!is_array($eventArguments)) {
            return [];
        }

        $calendarizeItems = $eventArguments['calendarize'] ?? null;
        if (!is_array($calendarizeItems)) {
            return [];
        }

        $submittedItems = [];
        $fallbackKey = 0;
        foreach ($calendarizeItems as $key => $item) {
            $itemKey = is_int($key)
                ? $key
                : filter_var($key, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
            if ($itemKey === false || $itemKey < 0 || array_key_exists($itemKey, $submittedItems)) {
                while (array_key_exists($fallbackKey, $submittedItems)) {
                    ++$fallbackKey;
                }
                $itemKey = $fallbackKey;
            }
            $submittedItems[$itemKey] = is_array($item) ? $item : [];
            ++$fallbackKey;
        }

        return $submittedItems;
    }
}
