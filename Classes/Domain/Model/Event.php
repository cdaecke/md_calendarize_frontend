<?php

declare(strict_types=1);

namespace Mediadreams\MdCalendarizeFrontend\Domain\Model;

use HDNET\Calendarize\Domain\Model\Configuration;

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
 */
class Event extends \HDNET\Calendarize\Domain\Model\Event
{
    /**
     * Frontend user, who created this entry
     *
     * @var FrontendUser|null
     */
    protected ?FrontendUser $mdUser = null;

    /**
     * Returns the mdUser
     *
     * @return FrontendUser|null
     */
    public function getMdUser(): ?FrontendUser
    {
        return $this->mdUser;
    }

    /**
     * Sets the mdUser
     *
     * @param FrontendUser $mdUser
     */
    public function setMdUser(FrontendUser $mdUser): void
    {
        $this->mdUser = $mdUser;
    }

    public function getFirstCalendarize(): ?Configuration
    {
        foreach ($this->getCalendarize() as $configuration) {
            return $configuration;
        }

        return null;
    }
}
