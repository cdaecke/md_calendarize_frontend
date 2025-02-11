<?php

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;


defined('TYPO3') || die('Access denied.');

$pluginSignature = \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
    'MdCalendarizeFrontend',
    'Frontend',
    'LLL:EXT:md_calendarize_frontend/Resources/Private/Language/locallang.xlf:mdcalendarizefrontend_frontend.name',
    'md_calendarize_frontend-plugin-frontend',
    null,
    'LLL:EXT:md_calendarize_frontend/Resources/Private/Language/locallang.xlf:mdcalendarizefrontend_frontend.description',
);

ExtensionManagementUtility::addToAllTCAtypes(
    'tt_content',
    '--div--;LLL:EXT:md_calendarize_frontend/Resources/Private/Language/locallang.xlf:tt_content.tab.configuration,pages,',
    $pluginSignature,
    'after:subheader',
);
