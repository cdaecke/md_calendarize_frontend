<?php
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

use HDNET\Calendarize\Domain\Model\Index;
use Mediadreams\MdCalendarizeFrontend\Domain\Model\Event;
use Mediadreams\MdCalendarizeFrontend\Property\TypeConverter\TimestampConverter;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Domain\Repository\CategoryRepository;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Property\TypeConverter\DateTimeConverter;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * EventController
 */
class EventBaseController extends ActionController
{
    /**
     * eventRepository
     *
     * @var \Mediadreams\MdCalendarizeFrontend\Domain\Repository\EventRepository
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $eventRepository = null;

    /**
     * indexRepository
     *
     * @var \HDNET\Calendarize\Domain\Repository\IndexRepository
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $indexRepository = null;

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
                LocalizationUtility::translate('controller.not_loggedin','md_calendarize_frontend'),
                '',
                AbstractMessage::ERROR
            );
        } else if (!isset($this->settings['dateFormat'])) { // check if TypoScript is loaded
            $this->addFlashMessage(
                LocalizationUtility::translate('controller.typoscript_missing','md_calendarize_frontend'),
                '',
                AbstractMessage::ERROR
            );
        }

        if ( strlen($this->settings['parentCategory']) > 0 ) {
            $categoryRepository = $this->objectManager->get(CategoryRepository::class);
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
            if ( isset($args['event']['calendarize']) ) {

                // set configuration for date
                $dateConfig = $this->arguments['event']
                    ->getPropertyMappingConfiguration()
                    ->getConfigurationFor('calendarize');

                foreach ($args['event']['calendarize'] as $key => $items) {
                    $dateConfig
                        ->getConfigurationFor($key)
                        ->forProperty('startDate')
                        ->setTypeConverterOption(
                            DateTimeConverter::class,
                            DateTimeConverter::CONFIGURATION_DATE_FORMAT,
                            $this->settings['dateFormat']
                        );

                    $dateConfig
                        ->getConfigurationFor($key)
                        ->forProperty('endDate')
                        ->setTypeConverterOption(
                            DateTimeConverter::class,
                            DateTimeConverter::CONFIGURATION_DATE_FORMAT,
                            $this->settings['dateFormat']
                        );

                    $dateConfig
                        ->getConfigurationFor($key)
                        ->forProperty('endDate')
                        ->setTypeConverterOption(
                            DateTimeConverter::class,
                            DateTimeConverter::CONFIGURATION_DATE_FORMAT,
                            $this->settings['dateFormat']
                        );

                    $dateConfig
                        ->getConfigurationFor($key)
                        ->forProperty('startTime')
                        ->setTypeConverter($this->objectManager->get(TimestampConverter::class))
                        ->setTypeConverterOption(
                            TimestampConverter::class,
                            TimestampConverter::CONFIGURATION_DATE_FORMAT,
                            $this->settings['timeFormat']
                        );

                    $dateConfig
                        ->getConfigurationFor($key)
                        ->forProperty('endTime')
                        ->setTypeConverter($this->objectManager->get(TimestampConverter::class))
                        ->setTypeConverterOption(
                            TimestampConverter::class,
                            TimestampConverter::CONFIGURATION_DATE_FORMAT,
                            $this->settings['timeFormat']
                        );
                }
            }
        }

        // get fe_user id
        $context = GeneralUtility::makeInstance(Context::class);
        $this->feuserUid = $context->getPropertyFromAspect('frontend.user', 'id');
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
                LocalizationUtility::translate('controller.access_error','md_calendarize_frontend'),
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
        foreach ($event->getCalendarize() as $items) {
            /** @var $indexObject \HDNET\Calendarize\Domain\Model\Index */
            $indexObject = $this->objectManager->get(Index::class);
            $indexObject->setForeignUid($event->getUid());
            $indexObject->setUniqueRegisterKey('Event');
            $indexObject->setForeignTable('tx_calendarize_domain_model_event');
            $indexObject->setState($items->getState());
            $indexObject->setAllDay($items->isAllDay());
            $indexObject->setOpenEndTime($items->isOpenEndTime());
            $indexObject->setStartDate($items->getStartDate());

            if ( !empty($items->getEndDate()) ) {
                $indexObject->setEndDate($items->getEndDate());
            } else {
                $indexObject->setEndDate($items->getStartDate());
            }

            if ( !empty($items->getStartTime()) ) {
                $indexObject->setStartTime($items->getStartTime());
            }

            if ( !empty($items->getEndTime()) ) {
                $indexObject->setEndTime($items->getEndTime());
            }

            $this->indexRepository->add($indexObject);
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
                $queryBuilder->expr()->eq('foreign_uid', $queryBuilder->createNamedParameter($eventUid, \PDO::PARAM_INT))
            )
            ->execute();
    }
}
