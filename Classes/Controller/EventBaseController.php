<?php
declare(strict_types=1);

namespace Mediadreams\MdCalendarizeFrontend\Controller;

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
use TYPO3\CMS\Extbase\Property\TypeConverter\PersistentObjectConverter;
use GeorgRinger\NumberedPagination\NumberedPagination;
use HDNET\Calendarize\Domain\Model\Index;
use HDNET\Calendarize\Domain\Repository\IndexRepository;
use HDNET\Calendarize\Service\Url\SlugService;
use Mediadreams\MdCalendarizeFrontend\Domain\Model\Event;
use Mediadreams\MdCalendarizeFrontend\Domain\Model\FrontendUser;
use Mediadreams\MdCalendarizeFrontend\Domain\Repository\CategoryRepository;
use Mediadreams\MdCalendarizeFrontend\Domain\Repository\EventRepository;
use Mediadreams\MdCalendarizeFrontend\Domain\Repository\FrontendUserRepository;
use Mediadreams\MdCalendarizeFrontend\Property\TypeConverter\TimestampConverter;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Pagination\QueryResultPaginator;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Extbase\Property\TypeConverter\DateTimeConverter;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;

/**
 * Class EventBaseController
 * @package Mediadreams\MdCalendarizeFrontend\Controller
 */
class EventBaseController extends ActionController
{
    /**
     * @var array<string, mixed> FeUser array
     */
    protected array $feUser = [];

    /**
     * @var int FeUser Uid
     */
    protected int $feuserUid = 0;

    protected ?FrontendUser $frontendUser = null;

    /**
     * eventRepository
     *
     * @var EventRepository
     */
    protected EventRepository $eventRepository;

    /**
     * indexRepository
     *
     * @var IndexRepository
     */
    protected IndexRepository $indexRepository;

    /**
     * @var SlugService
     */
    protected SlugService $slugService;

    /**
     * EventBaseController constructor
     *
     * @param EventRepository $eventRepository
     * @param IndexRepository $indexRepository
     * @param SlugService $slugService
     */
    public function __construct(
        EventRepository $eventRepository,
        IndexRepository $indexRepository,
        SlugService $slugService,
        protected readonly FrontendUserRepository $frontendUserRepository,
    ) {
        $this->eventRepository = $eventRepository;
        $this->indexRepository = $indexRepository;
        $this->slugService = $slugService;
    }

    /**
     * Deactivate errorFlashMessage
     *
     * @return bool
     */
    public function getErrorFlashMessage(): bool
    {
        return false;
    }

    /**
     * Initializes the view and pass additional data to template
     */
    protected function initializeView(): void
    {
        // check if TypoScript is loaded
        if (!isset($this->settings['dateFormat'])) {
            $this->addFlashMessage(
                LocalizationUtility::translate('controller.typoscript_missing', 'md_calendarize_frontend'),
                '',
                ContextualFeedbackSeverity::ERROR
            );
        }

        $this->view->assignMultiple([
            'feUser' => $this->feUser,
            'contentObjectData' => $this->request->getAttribute('currentContentObject')->data
        ]);

        if (is_object($this->request->getAttribute('frontend.controller'))) {
            $this->view->assign('pageData', $this->request->getAttribute('frontend.page.information')->getPageRecord());
        }

        if (strlen($this->settings['parentCategory'] ?? '') > 0) {
            $categoryRepository = GeneralUtility::makeInstance(CategoryRepository::class);
            $categories = $categoryRepository->findBy(['parent' => $this->settings['parentCategory']]);

            // Assign categories to template
            $this->view->assign('categories', $categories);
        }
    }

    /**
     * initializeAction
     */
    public function initializeAction(): void
    {
        parent::initializeAction();

        $frontendUserAuthentication = $this->request->getAttribute('frontend.user');
        $this->feUser = $frontendUserAuthentication instanceof FrontendUserAuthentication
            && is_array($frontendUserAuthentication->user)
            ? $frontendUserAuthentication->user
            : [];
        $this->feuserUid = (int)($this->feUser['uid'] ?? 0);
        if ($this->feuserUid > 0) {
            $frontendUser = $this->frontendUserRepository->findByIdentifier($this->feuserUid);
            if ($frontendUser instanceof FrontendUser) {
                $this->frontendUser = $frontendUser;
            }
        }

        if (isset($this->arguments['event'])) {
            $this->arguments['event']->getPropertyMappingConfiguration()->skipProperties('mdUser');
            $args = $this->request->getArguments();

            if (
                (
                    $args['action'] === 'create'
                    || $args['action'] === 'update'
                ) &&
                isset($args['event']['calendarize'])
            ) {
                // property mapper configuration
                $propertyMappingConfiguration = $this->arguments['event']
                    ->getPropertyMappingConfiguration()
                    ->getConfigurationFor('calendarize');

                foreach ($args['event']['calendarize'] as $key => $items) {
                    $propertyMappingConfiguration->allowProperties($key);
                    $propertyMappingConfiguration->allowProperties($key . '.*')->allowAllProperties();
                    $propertyMappingConfiguration->forProperty($key)->allowAllProperties();
                    $propertyMappingConfiguration->forProperty($key . '.*')->allowAllProperties();
                    $propertyMappingConfiguration->forProperty($key)->setTypeConverterOption(
                        PersistentObjectConverter::class,
                        PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED,
                        true
                    );

                    if ($items['startTime'] == '') {
                        $args['event']['calendarize'][$key]['startTime'] = 0;
                    }

                    if ($items['endTime'] == '') {
                        $args['event']['calendarize'][$key]['endTime'] = 0;
                    }

                    $this->request->getAttributes()['extbase']->setArguments($args);

                    // set configuration for date
                    $propertyMappingConfiguration
                        ->getConfigurationFor($key)
                        ->forProperty('startDate')
                        ->setTypeConverterOption(
                            DateTimeConverter::class,
                            DateTimeConverter::CONFIGURATION_DATE_FORMAT,
                            $this->settings['dateFormat']
                        );

                    $propertyMappingConfiguration
                        ->getConfigurationFor($key)
                        ->forProperty('endDate')
                        ->setTypeConverterOption(
                            DateTimeConverter::class,
                            DateTimeConverter::CONFIGURATION_DATE_FORMAT,
                            $this->settings['dateFormat']
                        );

                    $propertyMappingConfiguration
                        ->getConfigurationFor($key)
                        ->forProperty('endDate')
                        ->setTypeConverterOption(
                            DateTimeConverter::class,
                            DateTimeConverter::CONFIGURATION_DATE_FORMAT,
                            $this->settings['dateFormat']
                        );

                    if ($items['startTime'] != '') {
                        $propertyMappingConfiguration
                            ->getConfigurationFor($key)
                            ->forProperty('startTime')
                            ->setTypeConverter(GeneralUtility::makeInstance(TimestampConverter::class))
                            ->setTypeConverterOption(
                                TimestampConverter::class,
                                TimestampConverter::CONFIGURATION_DATE_FORMAT,
                                $this->settings['timeFormat']
                            );
                    }

                    if ($items['endTime'] != '') {
                        $propertyMappingConfiguration
                            ->getConfigurationFor($key)
                            ->forProperty('endTime')
                            ->setTypeConverter(GeneralUtility::makeInstance(TimestampConverter::class))
                            ->setTypeConverterOption(
                                TimestampConverter::class,
                                TimestampConverter::CONFIGURATION_DATE_FORMAT,
                                $this->settings['timeFormat']
                            );
                    }
                }
            } else {
                if ($args['action'] === 'update' && isset($args['event'])) {
                    // no "calendarize" item was provided -> remove all
                    $args['event']['calendarize'] = null;
                    $this->request->getAttributes()['extbase']->setArguments($args);
                }
            }
        }
    }

    /**
     * Check, if record belongs to user
     * If record does not belong to user, redirect to list action
     *
     * @param Event $record
     * @return ResponseInterface|null
     */
    protected function checkAccess(Event $record): ?ResponseInterface
    {
        if ($this->frontendUser === null || $record->getMdUser()?->getUid() !== $this->frontendUser->getUid()) {
            $this->addFlashMessage(
                LocalizationUtility::translate('controller.access_error', 'md_calendarize_frontend'),
                '',
                ContextualFeedbackSeverity::ERROR
            );

            return $this->redirect('list');
        }

        return null;
    }

    /**
     * Set data for index repository
     *
     * @param Event $event The event object
     */
    protected function setIndexObjects(Event $event): void
    {
        // Generate slug
        $neededItems = [];
        foreach ($event->getCalendarize() as $key => $item) {
            $itemKey = ['key' => $key];
            $neededItems[] = array_merge($this->objectToArray($item), $itemKey);
        }

        $slugs = $this->slugService->generateSlugForItems(
            'Event',
            $this->objectToArray($event),
            $neededItems
        );

        $itemsWithSlug = [];
        foreach ($neededItems as $key => $value) {
            $itemsWithSlug[$value['key']] = array_merge($value, $slugs[$key] ?? []);
        }

        // Save items
        foreach ($event->getCalendarize() as $key => $items) {
            /** @var $indexObject \HDNET\Calendarize\Domain\Model\Index */
            $indexObject = GeneralUtility::makeInstance(Index::class);
            $indexObject->setForeignUid($event->getUid());
            $indexObject->setUniqueRegisterKey('Event');
            $indexObject->setForeignTable('tx_calendarize_domain_model_event');
            $indexObject->setState($items->getState());
            $indexObject->setAllDay($items->isAllDay());
            $indexObject->setOpenEndTime($items->isOpenEndTime());
            $indexObject->setStartDate($items->getStartDate());

            // get unique slug
            $slug = $this->slugService->makeSlugUnique($itemsWithSlug[$key]);
            $indexObject->setSlug($slug);

            if (!empty($items->getEndDate())) {
                $indexObject->setEndDate($items->getEndDate());
            } else {
                $indexObject->setEndDate($items->getStartDate());
            }

            if (!empty($items->getStartTime())) {
                $indexObject->setStartTime($items->getStartTime());
            }

            if (!empty($items->getEndTime())) {
                $indexObject->setEndTime($items->getEndTime());
            }

            $this->indexRepository->add($indexObject);

            // persist data in order to get correct slug for next item
            $persistenceManager = GeneralUtility::makeInstance(PersistenceManager::class);
            $persistenceManager->persistAll();
        }
    }

    /**
     * Delete index objects of an event
     *
     * @param int $eventUid
     * @return mixed
     */
    protected function deleteIndexOfEvent(int $eventUid)
    {
        // delete index objects
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_calendarize_domain_model_index');

        return $queryBuilder
            ->delete('tx_calendarize_domain_model_index')
            ->where(
                $queryBuilder->expr()->eq(
                    'foreign_uid',
                    $queryBuilder->createNamedParameter($eventUid, Connection::PARAM_INT)
                )
            )
            ->executeStatement();
    }

    /**
     * Convert an object to an array
     *
     * @param object $obj
     * @return array
     * @throws \ReflectionException
     */
    protected function objectToArray(object $obj): array
    {
        $reflectionClass = new \ReflectionClass($obj::class);
        $arr = [];
        foreach ($reflectionClass->getProperties() as $prop) {
            $val = '';
            if ($prop->getName() === 'startDate' && !empty($prop->getValue($obj))) {
                $val = $prop->getValue($obj)->format('Y-m-d');
            } else {
                $val = $prop->getValue($obj);
            }

            $arr[$this->getDecamelized($prop->getName())] = $val;
        }

        return $arr;
    }


    /**
     * Get a camel case string decamelized, eg. "startDate" will become "start_date"
     *
     * @param string $str
     * @return string
     */
    protected function getDecamelized(string $str): string
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $str));
    }

    /**
     * Assign pagination to current view object
     *
     * @param $items
     * @param int $itemsPerPage
     * @param int $maximumNumberOfLinks
     */
    protected function assignPagination($items, int $itemsPerPage = 10, int $maximumNumberOfLinks = 5): void
    {
        $currentPage = $this->request->hasArgument('currentPage') ? (int)$this->request->getArgument('currentPage') : 1;

        $paginator = new QueryResultPaginator(
            $items,
            $currentPage,
            $itemsPerPage
        );

        $pagination = new NumberedPagination(
            $paginator,
            $maximumNumberOfLinks
        );

        $this->view->assign('pagination', [
            'paginator' => $paginator,
            'pagination' => $pagination,
        ]);
    }
}
