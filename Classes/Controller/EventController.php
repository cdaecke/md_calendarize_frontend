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

use Mediadreams\MdCalendarizeFrontend\Domain\Model\Event;
use Mediadreams\MdCalendarizeFrontend\Helper\SlugHelper;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * Class EventController
 * @package Mediadreams\MdCalendarizeFrontend\Controller
 */
class EventController extends EventBaseController
{
    /**
     * action accessDenied
     *
     * @return ResponseInterface
     */
    public function accessDeniedAction(): ResponseInterface
    {
        return $this->htmlResponse();
    }

    /**
     * action list
     *
     * @return ResponseInterface
     */
    public function listAction(): ResponseInterface
    {
        if ($this->feuserUid === 0) {
            return $this->redirect('accessDenied');
        }

        if ($this->feuserUid > 0) {
            $events = $this->eventRepository->findByMdUser($this->feuserUid);

            $this->assignPagination(
                $events,
                (int)$this->settings['paginate']['itemsPerPage'],
                (int)$this->settings['paginate']['maximumNumberOfLinks']
            );

            $this->view->assign('events', $events);
        }

        return $this->htmlResponse();
    }

    /**
     * action new
     *
     * @return ResponseInterface
     */
    public function newAction(): ResponseInterface
    {
        if ($this->feuserUid === 0) {
            return $this->redirect('accessDenied');
        }

        return $this->htmlResponse();
    }

    /**
     * action create
     *
     * @param \Mediadreams\MdCalendarizeFrontend\Domain\Model\Event $event
     * @TYPO3\CMS\Extbase\Annotation\Validate("Mediadreams\MdCalendarizeFrontend\Validator\EventValidator", param="event")
     * @return ResponseInterface
     */
    public function createAction(Event $event): ResponseInterface
    {
        $event->setMdUser($this->feuserUid);

        $this->eventRepository->add($event);

        // persist data in order to get insert id
        $persistenceManager = GeneralUtility::makeInstance(PersistenceManager::class);
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
            ContextualFeedbackSeverity::OK
        );

        return $this->redirect('list');
    }

    /**
     * action edit
     *
     * @param \Mediadreams\MdCalendarizeFrontend\Domain\Model\Event $event
     * @TYPO3\CMS\Extbase\Annotation\IgnoreValidation("event")
     * @return ResponseInterface
     */
    public function editAction(Event $event): ResponseInterface
    {
        $this->checkAccess($event);
        $this->view->assign('event', $event);

        return $this->htmlResponse();
    }

    /**
     * action update
     *
     * @param \Mediadreams\MdCalendarizeFrontend\Domain\Model\Event $event
     * @TYPO3\CMS\Extbase\Annotation\Validate("Mediadreams\MdCalendarizeFrontend\Validator\EventValidator", param="event")
     * @return void
     */
    public function updateAction(Event $event): ResponseInterface
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
            ContextualFeedbackSeverity::OK
        );

        return $this->redirect('list');
    }

    /**
     * action delete
     *
     * @param \Mediadreams\MdCalendarizeFrontend\Domain\Model\Event $event
     * @return void
     */
    public function deleteAction(Event $event): ResponseInterface
    {
        $this->checkAccess($event);

        // delete index objects
        $this->deleteIndexOfEvent($event->getUid());

        // delete event
        $this->eventRepository->remove($event);

        $this->addFlashMessage(
            LocalizationUtility::translate('controller.deleted', 'md_calendarize_frontend'),
            '',
            ContextualFeedbackSeverity::OK
        );

        return $this->redirect('list');
    }
}
