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

use TYPO3\CMS\Extbase\Persistence\QueryInterface;

/**
 * Class CategoryRepository
 * @package Mediadreams\MdCalendarizeFrontend\Domain\Repository
 */
class CategoryRepository extends \TYPO3\CMS\Extbase\Domain\Repository\CategoryRepository
{
    protected $defaultOrderings = [
        'sorting' => QueryInterface::ORDER_ASCENDING,
        'uid' => QueryInterface::ORDER_ASCENDING,
    ];
}
