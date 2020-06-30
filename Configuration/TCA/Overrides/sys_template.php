<?php
defined('TYPO3_MODE') or die();

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(
    'md_calendarize_frontend',
    'Configuration/TypoScript',
    'Calendarize frontend'
);
