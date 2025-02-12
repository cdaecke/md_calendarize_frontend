<?php
declare(strict_types=1);

namespace Mediadreams\MdCalendarizeFrontend\Helper;

/***
 *
 * This file is part of the "Calendarize frontend" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 *  (c) 2021 Christoph Daecke <typo3@mediadreams.org>
 *
 ***/

use TYPO3\CMS\Core\DataHandling\Model\RecordStateFactory;
use TYPO3\CMS\Core\DataHandling\SlugHelper as CoreSlugHelper;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class SlugHelper
 * @package Mediadreams\MdCalendarizeFrontend\Helper
 */
class SlugHelper
{
    /**
     * Get unique slug for entry
     *
     * @param $obj
     * @param array $recordData
     * @param string $tableName
     * @param string $fieldName
     * @return string
     * @throws SiteNotFoundException
     */
    public function getSlug($obj, array $recordData, string $tableName, string $fieldName = 'slug'): string
    {
        $fieldConfig = $GLOBALS['TCA'][$tableName]['columns'][$fieldName]['config'];
        /** @var CoreSlugHelper $slugService */
        $slugService = GeneralUtility::makeInstance(CoreSlugHelper::class, $tableName, $fieldName, $fieldConfig);

        $slug = $slugService->generate($recordData, $obj->getPid());

        $state = RecordStateFactory::forName($tableName)
            ->fromArray($recordData, $obj->getPid(), $obj->getUid());

        $slug = $slugService->buildSlugForUniqueInSite($slug, $state);

        return $slug;
    }
}
