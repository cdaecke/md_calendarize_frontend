<?php
defined('TYPO3_MODE') || die();

$tmp_md_calendarize_frontend_columns = [

    'md_user' => [
        'exclude' => true,
        'label' => 'LLL:EXT:md_calendarize_frontend/Resources/Private/Language/locallang.xlf:tx_mdcalendarizefrontend_domain_model_event.md_user',
        'config' => [
            'type' => 'group',
            'internal_type' => 'db',
            'allowed' => 'fe_users',
            'foreign_table' => 'fe_users',
            'size' => 1,
            'minitems' => 0,
            'maxitems' => 1,
            'default' => 0,
            'eval' => 'int',
            'suggestOptions' => [
                'type' => 'suggest',
                'default' => [
                    'searchWholePhrase' => true
                ]
            ],
        ]
    ],

];

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns(
    'tx_calendarize_domain_model_event',
    $tmp_md_calendarize_frontend_columns
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
    'tx_calendarize_domain_model_event',
    '--div--;LLL:EXT:md_calendarize_frontend/Resources/Private/Language/locallang.xlf:tca.tab,
        md_user,
    '
);
