<?php
namespace Mediadreams\MdCalendarizeFrontend\Domain\Model;

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
 * Event
 */
class Event extends \HDNET\Calendarize\Domain\Model\Event
{
    /**
     * Frontend user, who created this entry
     *
     * @var \TYPO3\CMS\Extbase\Domain\Model\FrontendUser
     */
    protected $mdUser = null;

    /**
     * Returns the mdUser
     *
     * @return \TYPO3\CMS\Extbase\Domain\Model\FrontendUser $mdUser
     */
    public function getMdUser()
    {
        return $this->mdUser;
    }

    /**
     * Sets the mdUser
     *
     * @param \TYPO3\CMS\Extbase\Domain\Model\FrontendUser $mdUser
     * @return void
     */
    public function setMdUser($mdUser)
    {
        $this->mdUser = $mdUser;
    }

    /**
     * Get first calendarize item.
     *
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage|null
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
