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
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface;

/**
 * Class SlugHelper
 */
final readonly class SlugHelper
{
    public function __construct(private TcaSchemaFactory $tcaSchemaFactory) {}

    /**
     * Get unique slug for entry
     *
     * @param array<string, mixed> $recordData
     * @throws SiteNotFoundException
     */
    public function getSlug(
        DomainObjectInterface $object,
        array $recordData,
        string $tableName,
        string $fieldName = 'slug'
    ): string {
        $pid = $object->getPid();
        $uid = $object->getUid();
        if ($pid === null || $uid === null) {
            throw new \LogicException('A slug can only be generated for a persisted domain object.', 1787665432);
        }

        $fieldConfig = $this->tcaSchemaFactory
            ->get($tableName)
            ->getField($fieldName)
            ->getConfiguration();
        $slugService = GeneralUtility::makeInstance(
            CoreSlugHelper::class,
            $tableName,
            $fieldName,
            $fieldConfig
        );

        $slug = $slugService->generate($recordData, $pid);

        $state = RecordStateFactory::forName($tableName)
            ->fromArray($recordData, $pid, $uid);

        return $slugService->buildSlugForUniqueInSite($slug, $state);
    }
}
