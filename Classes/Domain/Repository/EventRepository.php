<?php
declare(strict_types=1);

namespace Mediadreams\MdCalendarizeFrontend\Domain\Repository;

/***
 *
 * This file is part of the "Calendarize frontend" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 *  (c) 2020 Christoph Daecke <typo3@mediadreams.org>
 *
 ***/

/**
 * Class EventRepository
 * @package Mediadreams\MdCalendarizeFrontend\Domain\Repository
 */
class EventRepository extends \HDNET\Calendarize\Domain\Repository\EventRepository
{
    /**
     * Default orderings
     *
     */
    protected $defaultOrderings = [
        'uid' => \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_DESCENDING,
    ];
}
