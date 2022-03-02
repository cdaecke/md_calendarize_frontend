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

use GeorgRinger\NumberedPagination\NumberedPagination;
use HDNET\Calendarize\Domain\Model\Index;
use HDNET\Calendarize\Domain\Repository\IndexRepository;
use HDNET\Calendarize\Service\Url\SlugService;
use Mediadreams\MdCalendarizeFrontend\Domain\Model\Event;
use Mediadreams\MdCalendarizeFrontend\Domain\Repository\CategoryRepository;
use Mediadreams\MdCalendarizeFrontend\Domain\Repository\EventRepository;
use Mediadreams\MdCalendarizeFrontend\Property\TypeConverter\TimestampConverter;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Pagination\QueryResultPaginator;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Extbase\Property\TypeConverter\DateTimeConverter;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * Class EventBaseController
 * @package Mediadreams\MdCalendarizeFrontend\Controller
 */
class EventBaseController extends ActionController
{
    /**
     * eventRepository
     *
     * @var EventRepository
     */
    protected $eventRepository = null;

    /**
     * indexRepository
     *
     * @var IndexRepository
     */
    protected $indexRepository = null;

    /**
     * @var SlugService
     */
    protected $slugService;

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
        SlugService $slugService
    ) {
        $this->eventRepository = $eventRepository;
        $this->indexRepository = $indexRepository;
        $this->slugService = $slugService;
    }

    /**
     * Deactivate errorFlashMessage
     *
     * @return bool|string
     */
    public function getErrorFlashMessage()
    {
        return false;
    }

    /**
     * Initializes the view and pass additional data to template
     *
     * @param \TYPO3\CMS\Extbase\Mvc\View\ViewInterface $view The view to be initialized
     */
    protected function initializeView(\TYPO3\CMS\Extbase\Mvc\View\ViewInterface $view)
    {
        // check if user is logged in
        if (!$this->feuserUid) {
            $this->addFlashMessage(
                LocalizationUtility::translate('controller.not_loggedin', 'md_calendarize_frontend'),
                '',
                AbstractMessage::ERROR
            );

            if ($this->actionMethodName !== 'listAction') {
                $this->addFlashMessage(
                    LocalizationUtility::translate('controller.please_login', 'md_calendarize_frontend'),
                    '',
                    AbstractMessage::ERROR
                );

                $this->redirect('list');
            }
        } else {
            if (!isset($this->settings['dateFormat'])) { // check if TypoScript is loaded
                $this->addFlashMessage(
                    LocalizationUtility::translate('controller.typoscript_missing', 'md_calendarize_frontend'),
                    '',
                    AbstractMessage::ERROR
                );
            }

            $view->assignMultiple([
                'feUser' => $this->feUser,
                'contentObjectData' => $this->configurationManager->getContentObject()->data
            ]);

            if (is_object($GLOBALS['TSFE'])) {
                $view->assign('pageData', $GLOBALS['TSFE']->page);
            }
        }

        if (strlen($this->settings['parentCategory']) > 0) {
            $categoryRepository = GeneralUtility::makeInstance(CategoryRepository::class);
            $categories = $categoryRepository->findByParent($this->settings['parentCategory']);

            // Assign categories to template
            $view->assign('categories', $categories);
        }

        parent::initializeView($view);
    }

    /**
     * initializeAction
     *
     * @throws \TYPO3\CMS\Core\Context\Exception\AspectNotFoundException
     */
    public function initializeAction()
    {
        parent::initializeAction();

        if (isset($this->arguments['event'])) {
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
                        'TYPO3\CMS\Extbase\Property\TypeConverter\PersistentObjectConverter',
                        \TYPO3\CMS\Extbase\Property\TypeConverter\PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED,
                        true
                    );

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

                    $propertyMappingConfiguration
                        ->getConfigurationFor($key)
                        ->forProperty('startTime')
                        ->setTypeConverter(GeneralUtility::makeInstance(TimestampConverter::class))
                        ->setTypeConverterOption(
                            TimestampConverter::class,
                            TimestampConverter::CONFIGURATION_DATE_FORMAT,
                            $this->settings['timeFormat']
                        );

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
            } else {
                if ($args['action'] === 'update' && isset($args['event'])) {
                    // no "calendarize" item was provided -> remove all
                    $args['event']['calendarize'] = null;
                    $this->request->setArguments($args);
                }
            }
        }

        // get fe_user id
        $this->feUser = $GLOBALS['TSFE']->fe_user->user;
        $this->feuserUid = (int)$this->feUser['uid'];
    }

    /**
     * Check, if news record belongs to user
     * If news record does not belong to user, redirect to list action
     *
     * @param \Mediadreams\MdNewsfrontend\Domain\Model\News $newsRecord
     * @return void
     */
    protected function checkAccess(\Mediadreams\MdCalendarizeFrontend\Domain\Model\Event $record)
    {
        if ($record->getMdUser()->getUid() != $this->feuserUid) {
            $this->addFlashMessage(
                LocalizationUtility::translate('controller.access_error', 'md_calendarize_frontend'),
                '',
                AbstractMessage::ERROR
            );

            $this->redirect('list');
        }
    }

    /**
     * Set data for index repository
     *
     * @param Event $event The event object
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
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
                $queryBuilder->expr()->eq('foreign_uid',
                    $queryBuilder->createNamedParameter($eventUid, \PDO::PARAM_INT))
            )
            ->execute();
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
        $reflectionClass = new \ReflectionClass(get_class($obj));
        $arr = array();
        foreach ($reflectionClass->getProperties() as $prop) {
            $prop->setAccessible(true);

            $val = '';
            if ($prop->getName() === 'startDate' && !empty($prop->getValue($obj))) {
                $val = $prop->getValue($obj)->format('Y-m-d');
            } else {
                $val = $prop->getValue($obj);
            }

            $arr[$this->getDecamelized($prop->getName())] = $val;
            $prop->setAccessible(false);
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
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\NoSuchArgumentException
     */
    protected function assignPagination($items, $itemsPerPage = 10, $maximumNumberOfLinks = 5)
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
