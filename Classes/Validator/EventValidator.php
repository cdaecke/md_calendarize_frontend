<?php
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
use TYPO3\CMS\Extbase\Validation\Error;
use TYPO3\CMS\Extbase\Validation\Validator\AbstractValidator;

/**
 * Usage example for controller action:
 * @TYPO3\CMS\Extbase\Annotation\Validate("Mediadreams\MdCalendarizeFrontend\Validator\EventValidator", param="event")
 */
class EventValidator extends AbstractValidator
{
    /**
     * Object Manager
     *
     * @var \TYPO3\CMS\Extbase\Object\ObjectManager
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $objectManager;

    /**
     * @param mixed $value
     * @return bool
     */
    protected function isValid($value): bool
    {
        $error = null;

        if (!$value instanceof Event) {
            throw new \LogicException('Model "Event" is needed for validation!');
        }

        // check title of event
        if ($value->getTitle() == '') {
            $error = $this->objectManager->get(
                Error::class,
                'Please enter a title for the event.',
                1593464351
            );

            $this->result->forProperty('title')->addError($error);
        }

        // check start date for all items
        $calendarize = GeneralUtility::_POST()['tx_mdcalendarizefrontend_frontend']['event']['calendarize'];
        foreach ($calendarize as $key => $configItem) {
            if ($configItem['startDate'] == '') {
                $error = $this->objectManager->get(
                    Error::class,
                    'Please enter a start date for the event.',
                    1593465345
                );

                $this->result->forProperty('calendarize.'.$key.'.startDate')->addError($error);
            }
        }

        if ($error) {
            return false;
        } else {
            return true;
        }
    }
}
