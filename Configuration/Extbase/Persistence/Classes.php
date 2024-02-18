<?php
declare(strict_types=1);

return [
    \Mediadreams\MdCalendarizeFrontend\Domain\Model\Category::class => [
        'tableName' => 'sys_category',
    ],

    \Mediadreams\MdCalendarizeFrontend\Domain\Model\Event::class => [
        'tableName' => 'tx_calendarize_domain_model_event',
    ],

    \Mediadreams\MdCalendarizeFrontend\Domain\Model\FrontendUser::class => [
        'tableName' => 'fe_users',
    ],

    \Mediadreams\MdCalendarizeFrontend\Domain\Model\FrontendUserGroup::class => [
        'tableName' => 'fe_groups',
    ],
];
