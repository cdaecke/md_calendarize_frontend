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

use Mediadreams\MdCalendarizeFrontend\Domain\Model\Event;
use Mediadreams\MdCalendarizeFrontend\Helper\SlugHelper;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * EventController
 */
class EventController extends EventBaseController
{

    /**
     * action list
     *
     * @return void
     */
    public function listAction()
    {
        if ($this->feuserUid > 0) {
            $events = $this->eventRepository->findByMdUser($this->feuserUid);

            $this->assignPagination(
                $events,
                (int)$this->settings['paginate']['itemsPerPage'],
                (int)$this->settings['paginate']['maximumNumberOfLinks']
            );

            $this->view->assign('events', $events);
        }
    }

    /**
     * action new
     *
     * @return void
     */
    public function newAction()
    {

    }

    /**
     * action create
     *
     * @param \Mediadreams\MdCalendarizeFrontend\Domain\Model\Event $event
     * @TYPO3\CMS\Extbase\Annotation\Validate("Mediadreams\MdCalendarizeFrontend\Validator\EventValidator", param="event")
     * @return void
     */
    public function createAction(Event $event)
    {
        $event->setMdUser($this->feuserUid);

        $this->eventRepository->add($event);

        // persist data in order to get insert id
        $persistenceManager = $this->objectManager->get(PersistenceManager::class);
        $persistenceManager->persistAll();

        /** @var SlugHelper $slugHelper */
        $slugHelper = GeneralUtility::makeInstance(SlugHelper::class);
        $slug = $slugHelper->getSlug($event, ['title' => $event->getTitle()], 'tx_calendarize_domain_model_event');
        $event->setSlug($slug);

        $this->eventRepository->update($event);

        $this->setIndexObjects($event);

        $this->addFlashMessage(
            LocalizationUtility::translate('controller.created', 'md_calendarize_frontend'),
            '',
            \TYPO3\CMS\Core\Messaging\AbstractMessage::OK
        );

        $this->redirect('list');
    }

    /**
     * action edit
     *
     * @param \Mediadreams\MdCalendarizeFrontend\Domain\Model\Event $event
     * @TYPO3\CMS\Extbase\Annotation\IgnoreValidation("event")
     * @return void
     */
    public function editAction(Event $event)
    {
        $this->checkAccess($event);
        $this->view->assign('event', $event);
    }

    /**
     * action update
     *
     * @param \Mediadreams\MdCalendarizeFrontend\Domain\Model\Event $event
     * @TYPO3\CMS\Extbase\Annotation\Validate("Mediadreams\MdCalendarizeFrontend\Validator\EventValidator", param="event")
     * @return void
     */
    public function updateAction(Event $event)
    {
        $this->checkAccess($event);

        foreach ($event->getCalendarize() as $item) {
            if (!$item->getStartTime()) {
                $item->setStartTime(0);
            }

            if (!$item->getEndTime()) {
                $item->setEndTime(0);
            }
        }

        $this->eventRepository->update($event);

        // delete index objects
        $this->deleteIndexOfEvent($event->getUid());

        // write index objects
        $this->setIndexObjects($event);

        $this->addFlashMessage(
            LocalizationUtility::translate('controller.updated', 'md_calendarize_frontend'),
            '',
            \TYPO3\CMS\Core\Messaging\AbstractMessage::OK
        );

        $this->redirect('list');
    }

    /**
     * action delete
     *
     * @param \Mediadreams\MdCalendarizeFrontend\Domain\Model\Event $event
     * @return void
     */
    public function deleteAction(Event $event)
    {
        $this->checkAccess($event);

        // delete index objects
        $this->deleteIndexOfEvent($event->getUid());

        // delete event
        $this->eventRepository->remove($event);

        $this->addFlashMessage(
            LocalizationUtility::translate('controller.deleted', 'md_calendarize_frontend'),
            '',
            \TYPO3\CMS\Core\Messaging\AbstractMessage::OK
        );

        $this->redirect('list');
    }

}
