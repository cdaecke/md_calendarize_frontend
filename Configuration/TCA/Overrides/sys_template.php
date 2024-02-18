<?php
defined('TYPO3') or die();

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(
    'md_calendarize_frontend',
    'Configuration/TypoScript',
    'Calendarize frontend'
);
