<?php
defined('TYPO3_MODE') || die('Access denied.');

call_user_func(
    function()
    {

        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
            'Mediadreams.MdCalendarizeFrontend',
            'Frontend',
            [
                'Event' => 'list, new, create, edit, update, delete'
            ],
            // non-cacheable actions
            [
                'Event' => 'list, new, create, edit, update, delete'
            ]
        );

        $iconRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconRegistry::class);
        
        $iconRegistry->registerIcon(
            'md_calendarize_frontend-plugin-frontend',
            \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
            ['source' => 'EXT:md_calendarize_frontend/Resources/Public/Icons/user_plugin_frontend.svg']
        );
    }
);
