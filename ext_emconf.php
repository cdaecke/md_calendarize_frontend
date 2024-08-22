<?php

/***************************************************************
 * Extension Manager/Repository config file for ext: "md_calendarize_frontend"
 *
 * Auto generated by Extension Builder 2020-06-17
 *
 * Manual updates:
 * Only the data in the array - anything else is removed by next write.
 * "version" and "dependencies" must not be touched!
 ***************************************************************/

$EM_CONF[$_EXTKEY] = [
    'title' => 'Calendarize frontend',
    'description' => 'This extension enables frontend users to add ext:calendarize items in the frontend.',
    'category' => 'plugin',
    'author' => 'Christoph Daecke',
    'author_email' => 'typo3@mediadreams.org',
    'state' => 'stable',
    'uploadfolder' => 0,
    'createDirs' => '',
    'clearCacheOnLoad' => 0,
    'version' => '3.0.1',
    'constraints' => [
        'depends' => [
            'typo3' => '12.4.0-12.4.99',
            'calendarize' => '12.0.0-13.99.99',
            'numbered_pagination' => '1.0.1-2.99.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
