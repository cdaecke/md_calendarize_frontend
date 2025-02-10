<?php
defined('TYPO3') || die('Access denied.');

call_user_func(
    function()
    {
        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
            'MdCalendarizeFrontend',
            'Frontend',
            [
                \Mediadreams\MdCalendarizeFrontend\Controller\EventController::class => 'list, new, create, edit, update, delete, accessDenied'
            ],
            // non-cacheable actions
            [
                \Mediadreams\MdCalendarizeFrontend\Controller\EventController::class => 'list, new, create, edit, update, delete'
            ],
            'list_type'
        );

        /**
         * Extend ext:calendarize model
         */
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\HDNET\Calendarize\Domain\Model\Event::class] = [
            'className' => \Mediadreams\MdCalendarizeFrontend\Domain\Model\Event::class
        ];
    }
);
