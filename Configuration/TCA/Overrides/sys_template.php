<?php
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') or die();

ExtensionManagementUtility::addStaticFile(
    'md_calendarize_frontend',
    'Configuration/TypoScript',
    'Calendarize frontend'
);
