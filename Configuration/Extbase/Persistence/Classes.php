<?php
declare(strict_types=1);

use Mediadreams\MdCalendarizeFrontend\Domain\Model\Category;
use Mediadreams\MdCalendarizeFrontend\Domain\Model\Event;
use Mediadreams\MdCalendarizeFrontend\Domain\Model\FrontendUser;
use Mediadreams\MdCalendarizeFrontend\Domain\Model\FrontendUserGroup;

return [
    Category::class => [
        'tableName' => 'sys_category',
    ],

    Event::class => [
        'tableName' => 'tx_calendarize_domain_model_event',
    ],

    FrontendUser::class => [
        'tableName' => 'fe_users',
    ],

    FrontendUserGroup::class => [
        'tableName' => 'fe_groups',
    ],
];
