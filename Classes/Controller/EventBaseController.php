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
use HDNET\Calendarize\Domain\Repository\RawIndexRepository;
use HDNET\Calendarize\Service\IndexerService;
use Mediadreams\MdCalendarizeFrontend\Domain\Model\Event;
use Mediadreams\MdCalendarizeFrontend\Domain\Model\FrontendUser;
use Mediadreams\MdCalendarizeFrontend\Domain\Repository\CategoryRepository;
use Mediadreams\MdCalendarizeFrontend\Domain\Repository\EventRepository;
use Mediadreams\MdCalendarizeFrontend\Domain\Repository\FrontendUserRepository;
use Mediadreams\MdCalendarizeFrontend\Helper\SlugHelper;
use Mediadreams\MdCalendarizeFrontend\Property\TypeConverter\TimestampConverter;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Pagination\PaginatorInterface;
use TYPO3\CMS\Core\Pagination\SlidingWindowPagination;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\ExtbaseRequestParameters;
use TYPO3\CMS\Extbase\Pagination\QueryResultPaginator;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Property\PropertyMappingConfiguration;
use TYPO3\CMS\Extbase\Property\PropertyMappingConfigurationInterface;
use TYPO3\CMS\Extbase\Property\TypeConverter\DateTimeConverter;
use TYPO3\CMS\Extbase\Property\TypeConverter\PersistentObjectConverter;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Page\PageInformation;

/**
 * Class EventBaseController
 */
class EventBaseController extends ActionController
{
    private const EVENT_REGISTER_KEY = 'Event';
    private const EVENT_TABLE = 'tx_calendarize_domain_model_event';

    /**
     * @var array<string, mixed> FeUser array
     */
    protected array $feUser = [];

    /**
     * @var int FeUser Uid
     */
    protected int $feuserUid = 0;

    protected ?FrontendUser $frontendUser = null;

    public function __construct(
        protected readonly EventRepository $eventRepository,
        protected readonly FrontendUserRepository $frontendUserRepository,
        protected readonly CategoryRepository $categoryRepository,
        protected readonly TimestampConverter $timestampConverter,
        protected readonly PersistenceManagerInterface $persistenceManager,
        protected readonly SlugHelper $slugHelper,
        private readonly IndexerService $indexerService,
        private readonly RawIndexRepository $rawIndexRepository,
    ) {}

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
                $this->translate('controller.typoscript_missing'),
                '',
                ContextualFeedbackSeverity::ERROR
            );
        }

        $currentContentObject = $this->request->getAttribute('currentContentObject');
        $this->view->assign('feUser', $this->feUser);
        if ($currentContentObject instanceof ContentObjectRenderer) {
            $this->view->assign('contentObjectData', $currentContentObject->data);
        }

        $pageInformation = $this->request->getAttribute('frontend.page.information');
        if ($pageInformation instanceof PageInformation) {
            $this->view->assign('pageData', $pageInformation->getPageRecord());
        }

        if (strlen($this->settings['parentCategory'] ?? '') > 0) {
            $categories = $this->categoryRepository->findBy(['parent' => $this->settings['parentCategory']]);

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
                )
                && isset($args['event']['calendarize'])
            ) {
                // property mapper configuration
                $propertyMappingConfiguration = $this->arguments['event']
                    ->getPropertyMappingConfiguration()
                    ->getConfigurationFor('calendarize');

                foreach ($args['event']['calendarize'] as $key => $items) {
                    if (!is_array($items)) {
                        continue;
                    }

                    $itemKey = (string)$key;
                    if ($itemKey === '') {
                        continue;
                    }
                    $propertyMappingConfiguration->allowProperties($itemKey);
                    $itemConfiguration = $propertyMappingConfiguration->forProperty($itemKey);
                    $itemConfiguration->allowProperties(
                        '__identity',
                        'startDate',
                        'endDate',
                        'startTime',
                        'endTime',
                        'openEndTime',
                        'allDay',
                        'type',
                        'handling',
                        'state',
                        'day',
                    );
                    $itemConfiguration->setTypeConverterOption(
                        PersistentObjectConverter::class,
                        PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED,
                        true
                    );

                    $startTime = $items['startTime'] ?? '';
                    $endTime = $items['endTime'] ?? '';
                    if ($startTime === '') {
                        $args['event']['calendarize'][$key]['startTime'] = 0;
                    }

                    if ($endTime === '') {
                        $args['event']['calendarize'][$key]['endTime'] = 0;
                    }

                    $extbaseRequestParameters = $this->request->getAttribute('extbase');
                    if ($extbaseRequestParameters instanceof ExtbaseRequestParameters) {
                        $extbaseRequestParameters->setArguments($args);
                    }

                    // set configuration for date
                    $itemConfiguration
                        ->forProperty('startDate')
                        ->setTypeConverterOption(
                            DateTimeConverter::class,
                            DateTimeConverter::CONFIGURATION_DATE_FORMAT,
                            $this->settings['dateFormat']
                        );

                    $itemConfiguration
                        ->forProperty('endDate')
                        ->setTypeConverterOption(
                            DateTimeConverter::class,
                            DateTimeConverter::CONFIGURATION_DATE_FORMAT,
                            $this->settings['dateFormat']
                        );

                    if ($startTime !== '') {
                        $this->configureTimestampConverter($itemConfiguration->forProperty('startTime'));
                    }

                    if ($endTime !== '') {
                        $this->configureTimestampConverter($itemConfiguration->forProperty('endTime'));
                    }
                }
            } else {
                if ($args['action'] === 'update' && isset($args['event'])) {
                    // no "calendarize" item was provided -> remove all
                    $args['event']['calendarize'] = null;
                    $extbaseRequestParameters = $this->request->getAttribute('extbase');
                    if ($extbaseRequestParameters instanceof ExtbaseRequestParameters) {
                        $extbaseRequestParameters->setArguments($args);
                    }
                }
            }
        }
    }

    private function configureTimestampConverter(PropertyMappingConfigurationInterface $configuration): void
    {
        if (!$configuration instanceof PropertyMappingConfiguration) {
            throw new \LogicException('Expected a concrete property mapping configuration.', 1787654591);
        }

        $configuration
            ->setTypeConverter($this->timestampConverter)
            ->setTypeConverterOption(
                TimestampConverter::class,
                TimestampConverter::CONFIGURATION_DATE_FORMAT,
                $this->settings['timeFormat']
            );
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
                $this->translate('controller.access_error'),
                '',
                ContextualFeedbackSeverity::ERROR
            );

            return $this->redirect('list');
        }

        return null;
    }

    protected function synchronizeIndex(Event $event): void
    {
        $this->persistenceManager->persistAll();
        $this->indexerService->reindex(self::EVENT_REGISTER_KEY, self::EVENT_TABLE, $this->getEventUid($event));
    }

    protected function deleteIndexOfEvent(int $eventUid): void
    {
        $this->rawIndexRepository->deleteByIdentifier([
            'unique_register_key' => self::EVENT_REGISTER_KEY,
            'foreign_table' => self::EVENT_TABLE,
            'foreign_uid' => $eventUid,
        ]);
    }

    protected function getEventUid(Event $event): int
    {
        $uid = $event->getUid();
        if ($uid === null) {
            throw new \LogicException('Calendarize indices require a persisted event.', 1787669112);
        }

        return $uid;
    }

    protected function translate(string $key): string
    {
        return LocalizationUtility::translate($key, 'md_calendarize_frontend') ?? $key;
    }

    /**
     * Assign pagination to current view object
     *
     * @param QueryResultInterface<int, DomainObjectInterface> $items
     */
    protected function assignPagination(
        QueryResultInterface $items,
        int $itemsPerPage = 10,
        int $maximumNumberOfLinks = 5
    ): void {
        $paginator = new QueryResultPaginator(
            $items,
            $this->getCurrentPageNumber(),
            $itemsPerPage
        );

        $pagination = $this->createPagination($paginator, $maximumNumberOfLinks);

        $this->view->assign('pagination', [
            'paginator' => $paginator,
            'pagination' => $pagination,
        ]);
    }

    protected function getCurrentPageNumber(): int
    {
        if (!$this->request->hasArgument('currentPage')) {
            return 1;
        }

        $currentPage = $this->request->getArgument('currentPage');
        if (is_int($currentPage)) {
            return max(1, $currentPage);
        }
        if (is_string($currentPage) && ctype_digit($currentPage)) {
            return max(1, (int)$currentPage);
        }

        return 1;
    }

    protected function createPagination(
        PaginatorInterface $paginator,
        int $maximumNumberOfLinks
    ): SlidingWindowPagination {
        return new SlidingWindowPagination($paginator, max(1, $maximumNumberOfLinks));
    }
}
