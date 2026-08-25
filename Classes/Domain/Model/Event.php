<?php
declare(strict_types=1);

namespace Mediadreams\MdCalendarizeFrontend\Domain\Model;

use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

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
 * Class Event
 * @package Mediadreams\MdCalendarizeFrontend\Domain\Model
 */
class Event extends \HDNET\Calendarize\Domain\Model\Event
{
    /**
     * Frontend user, who created this entry
     *
     * @var FrontendUser
     */
    protected $mdUser = null;

    /**
     * Returns the mdUser
     *
     * @return FrontendUser $mdUser
     */
    public function getMdUser()
    {
        return $this->mdUser;
    }

    /**
     * Sets the mdUser
     *
     * @param FrontendUser $mdUser
     * @return void
     */
    public function setMdUser($mdUser): void
    {
        $this->mdUser = $mdUser;
    }

    /**
     * Get first calendarize item.
     *
     * @return ObjectStorage|null
     */
    public function getFirstCalendarize()
    {
        $calendarize = $this->getCalendarize();
        if (!is_null($calendarize)) {
            $calendarize->rewind();
            return $calendarize->current();
        } else {
            return null;
        }
    }
}
