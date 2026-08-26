<?php

use HDNET\Calendarize\Domain\Model\Event;
use Mediadreams\MdCalendarizeFrontend\Controller\EventController;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

defined('TYPO3') || die('Access denied.');

call_user_func(
    static function (): void {
        ExtensionUtility::configurePlugin(
            'MdCalendarizeFrontend',
            'Frontend',
            [
                EventController::class => 'list, new, create, edit, update, delete, accessDenied',
            ],
            // non-cacheable actions
            [
                EventController::class => 'list, new, create, edit, update, delete',
            ],
        );

        /**
         * Extend ext:calendarize model
         */
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][Event::class] = [
            'className' => \Mediadreams\MdCalendarizeFrontend\Domain\Model\Event::class,
        ];
    }
);
