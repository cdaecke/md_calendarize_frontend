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
use Mediadreams\MdCalendarizeFrontend\Validator\EventValidator;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Extbase\Attribute\IgnoreValidation;
use TYPO3\CMS\Extbase\Attribute\Validate;

/**
 * Class EventController
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
        if ($this->frontendUser === null) {
            return $this->redirect('accessDenied');
        }

        $events = $this->eventRepository->findBy(['mdUser' => $this->frontendUser]);

        $this->assignPagination(
            $events,
            (int)$this->settings['paginate']['itemsPerPage'],
            (int)$this->settings['paginate']['maximumNumberOfLinks']
        );

        $this->view->assign('events', $events);

        return $this->htmlResponse();
    }

    /**
     * action new
     *
     * @return ResponseInterface
     */
    public function newAction(): ResponseInterface
    {
        if ($this->frontendUser === null) {
            return $this->redirect('accessDenied');
        }

        return $this->htmlResponse();
    }

    /**
     * action create
     *
     * @param Event $event
     * @return ResponseInterface
     */
    public function createAction(#[Validate(EventValidator::class)] Event $event): ResponseInterface
    {
        if ($this->frontendUser === null) {
            return $this->redirect('accessDenied');
        }

        if (!$event->_isNew()) {
            // Extbase resolves an "event[__identity]" argument to an existing record
            // regardless of allowed properties; without this guard, submitting the
            // identity of someone else's event here would reassign its ownership.
            $this->addFlashMessage(
                $this->translate('controller.access_error'),
                '',
                ContextualFeedbackSeverity::ERROR
            );

            return $this->redirect('list');
        }

        if (($response = $this->checkCalendarizeOwnership($event)) !== null) {
            return $response;
        }

        $event->setMdUser($this->frontendUser);

        $this->eventRepository->add($event);

        // Persist first so UID and PID are available for slug generation.
        $this->persistenceManager->persistAll();

        $slug = $this->slugHelper->getSlug($event, ['title' => $event->getTitle()], 'tx_calendarize_domain_model_event');
        $event->setSlug($slug);

        $this->eventRepository->update($event);

        $this->synchronizeIndex($event);

        $this->addFlashMessage(
            $this->translate('controller.created'),
            '',
            ContextualFeedbackSeverity::OK
        );

        return $this->redirect('list');
    }

    /**
     * action edit
     *
     * @param Event $event
     * @return ResponseInterface
     */
    public function editAction(#[IgnoreValidation] Event $event): ResponseInterface
    {
        if (($response = $this->checkAccess($event)) !== null) {
            return $response;
        }
        $this->view->assign('event', $event);

        return $this->htmlResponse();
    }

    /**
     * action update
     *
     * @param Event $event
     * @return ResponseInterface
     */
    public function updateAction(#[Validate(EventValidator::class)] Event $event): ResponseInterface
    {
        if (($response = $this->checkAccess($event)) !== null) {
            return $response;
        }

        if (($response = $this->checkCalendarizeOwnership($event)) !== null) {
            return $response;
        }

        $this->eventRepository->update($event);

        $this->synchronizeIndex($event);

        $this->addFlashMessage(
            $this->translate('controller.updated'),
            '',
            ContextualFeedbackSeverity::OK
        );

        return $this->redirect('list');
    }

    /**
     * action delete
     *
     * @param Event $event
     * @return ResponseInterface
     */
    public function deleteAction(Event $event): ResponseInterface
    {
        if (($response = $this->checkAccess($event)) !== null) {
            return $response;
        }

        // delete index objects
        $this->deleteIndexOfEvent($this->getEventUid($event));

        // delete event
        $this->eventRepository->remove($event);

        $this->addFlashMessage(
            $this->translate('controller.deleted'),
            '',
            ContextualFeedbackSeverity::OK
        );

        return $this->redirect('list');
    }
}
