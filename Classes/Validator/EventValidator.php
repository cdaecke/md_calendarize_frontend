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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Extbase\Validation\Error;
use TYPO3\CMS\Extbase\Validation\Validator\AbstractValidator;

/**
 * Class EventValidator
 *
 * Usage example for controller action:
 * @TYPO3\CMS\Extbase\Annotation\Validate("Mediadreams\MdCalendarizeFrontend\Validator\EventValidator", param="event")
 *
 * @package Mediadreams\MdCalendarizeFrontend\Validator
 */
class EventValidator extends AbstractValidator
{

    /**
     * @param mixed $value
     * @return void
     */
    protected function isValid($value): void
    {
        $error = null;

        if (!$value instanceof Event) {
            throw new \LogicException('Model "Event" is needed for validation!');
        }

        // check title of event
        if ($value->getTitle() == '') {
            $error = GeneralUtility::makeInstance(
                Error::class,
                LocalizationUtility::translate('error.code.1593464351', 'md_calendarize_frontend'),
                1593464351
            );

            $this->result->forProperty('title')->addError($error);
        }

        // check start date for all items
        $calendarize = GeneralUtility::_POST()['tx_mdcalendarizefrontend_frontend']['event']['calendarize'];
        foreach ($calendarize as $key => $configItem) {
            if ($configItem['startDate'] == '') {
                $error = GeneralUtility::makeInstance(
                    Error::class,
                    'Please enter a start date for the event.',
                    1593465345
                );

                $this->result->forProperty('calendarize.'.$key.'.startDate')->addError($error);
            }
        }
    }
}
