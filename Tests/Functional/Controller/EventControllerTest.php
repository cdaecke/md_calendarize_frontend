<?php

declare(strict_types=1);

namespace Mediadreams\MdCalendarizeFrontend\Tests\Functional\Controller;

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
use Mediadreams\MdCalendarizeFrontend\Controller\EventController;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequestContext;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

#[CoversClass(EventController::class)]
final class EventControllerTest extends FunctionalTestCase
{
    private const UID_OF_PAGE = 1;
    private const UID_OF_OWN_EVENT = 1;
    private const UID_OF_OTHER_EVENT = 2;
    private const PLUGIN_NAMESPACE = 'tx_mdcalendarizefrontend_frontend';

    protected array $testExtensionsToLoad = ['lochmueller/calendarize', 'mediadreams/md_calendarize_frontend'];

    protected array $coreExtensionsToLoad = ['fluid_styled_content'];

    protected array $pathsToLinkInTestInstance = [
        'typo3conf/ext/md_calendarize_frontend/Tests/Functional/Controller/Fixtures/Sites/' => 'typo3conf/sites',
    ];

    protected array $configurationToUseInTestInstance = [
        'FE' => [
            'cacheHash' => [
                'enforceValidation' => false,
            ],
        ],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/Fixtures/Database/SiteStructure.csv');
        $this->setUpFrontendRootPage(1, [
            'constants' => [
                'EXT:fluid_styled_content/Configuration/TypoScript/constants.typoscript',
                'EXT:md_calendarize_frontend/Configuration/TypoScript/constants.typoscript',
            ],
            'setup' => [
                'EXT:fluid_styled_content/Configuration/TypoScript/setup.typoscript',
                'EXT:md_calendarize_frontend/Configuration/TypoScript/setup.typoscript',
                'EXT:md_calendarize_frontend/Tests/Functional/Controller/Fixtures/TypoScript/Setup/Rendering.typoscript',
            ],
        ]);

        $this->importCSVDataSet(__DIR__ . '/Fixtures/Database/EventController/ContentElement.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/Database/EventController/FrontendUser.csv');
    }

    #[Test]
    public function listActionRendersOnlyEventsOwnedByLoggedInUser(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/Database/EventController/EventOwnedByLoggedInUser.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/Database/EventController/EventOwnedByOtherUser.csv');

        $html = $this->getHtmlWithLoggedInUser([
            self::PLUGIN_NAMESPACE . '[action]' => 'list',
            self::PLUGIN_NAMESPACE . '[controller]' => 'Event',
        ]);

        self::assertStringContainsString('My Event', $html);
        self::assertStringNotContainsString('Other Event', $html);
    }

    #[Test]
    public function listActionOrdersEventsByUidDescending(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/Database/EventController/EventOwnedByLoggedInUser.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/Database/EventController/SecondEventOwnedByLoggedInUser.csv');

        $html = $this->getHtmlWithLoggedInUser([
            self::PLUGIN_NAMESPACE . '[action]' => 'list',
            self::PLUGIN_NAMESPACE . '[controller]' => 'Event',
        ]);

        $positionOfHigherUid = strpos($html, 'Newest Event');
        $positionOfLowerUid = strpos($html, 'My Event');
        self::assertIsInt($positionOfHigherUid);
        self::assertIsInt($positionOfLowerUid);
        self::assertLessThan($positionOfLowerUid, $positionOfHigherUid);
    }

    #[Test]
    public function newActionRendersOnlyChildrenOfConfiguredParentCategory(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/Database/EventController/Categories.csv');

        $html = $this->getHtmlWithLoggedInUser([
            self::PLUGIN_NAMESPACE . '[action]' => 'new',
            self::PLUGIN_NAMESPACE . '[controller]' => 'Event',
        ]);

        self::assertStringContainsString('Child Category One', $html);
        self::assertStringContainsString('Child Category Two', $html);
        self::assertStringNotContainsString('Unrelated Category', $html);
        self::assertStringNotContainsString('Parent Category<', $html);

        // "Child Category Two" has the lower sorting value, so it must render first
        // despite its higher uid - proves CategoryRepository's defaultOrderings is applied.
        $positionOfCategoryTwo = strpos($html, 'Child Category Two');
        $positionOfCategoryOne = strpos($html, 'Child Category One');
        self::assertIsInt($positionOfCategoryTwo);
        self::assertIsInt($positionOfCategoryOne);
        self::assertLessThan($positionOfCategoryOne, $positionOfCategoryTwo);
    }

    #[Test]
    public function createActionPersistsEventOwnedByLoggedInUserWithSubmittedCalendarizeItem(): void
    {
        $trustedProperties = $this->getTrustedPropertiesFromNewForm();

        $this->executeRequestWithLoggedInUser([
            self::PLUGIN_NAMESPACE . '[action]' => 'create',
            self::PLUGIN_NAMESPACE . '[controller]' => 'Event',
            self::PLUGIN_NAMESPACE . '[__trustedProperties]' => $trustedProperties,
            self::PLUGIN_NAMESPACE . '[event][title]' => 'Brand New Event',
            self::PLUGIN_NAMESPACE . '[event][calendarize][0][startDate]' => '01.09.2026',
            self::PLUGIN_NAMESPACE . '[event][calendarize][0][startTime]' => '10:00',
            self::PLUGIN_NAMESPACE . '[event][calendarize][0][endTime]' => '',
            self::PLUGIN_NAMESPACE . '[event][calendarize][0][allDay]' => '0',
            self::PLUGIN_NAMESPACE . '[event][calendarize][0][openEndTime]' => '0',
            self::PLUGIN_NAMESPACE . '[event][calendarize][0][type]' => 'time',
            self::PLUGIN_NAMESPACE . '[event][calendarize][0][handling]' => 'include',
            self::PLUGIN_NAMESPACE . '[event][calendarize][0][state]' => 'default',
            self::PLUGIN_NAMESPACE . '[event][calendarize][0][day]' => 'weekday',
        ]);

        $this->assertCSVDataSet(__DIR__ . '/Fixtures/Assertions/EventController/Create/CreatedEventOwnedByLoggedInUser.csv');
    }

    #[Test]
    public function createActionRejectsAlreadyPersistedEventToPreventOwnershipTakeover(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/Database/EventController/EventOwnedByOtherUser.csv');

        // A bare scalar "event" argument resolves straight to the existing record by
        // identity - no __trustedProperties needed, same as deleteAction's single-identity
        // argument. This is the actual shape of the exploit, not a full property submission.
        $response = $this->executeRequestWithLoggedInUser([
            self::PLUGIN_NAMESPACE . '[action]' => 'create',
            self::PLUGIN_NAMESPACE . '[controller]' => 'Event',
            self::PLUGIN_NAMESPACE . '[event]' => (string)self::UID_OF_OTHER_EVENT,
        ]);

        self::assertRedirectsToListAction($response);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/Assertions/EventController/Create/EventUnchangedAfterRejectedCreateHijack.csv');
    }

    #[Test]
    public function updateActionPersistsNewTitleAndLeavesOwnerAndPidUnchanged(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/Database/EventController/EventOwnedByLoggedInUser.csv');
        $trustedProperties = $this->getTrustedPropertiesFromEditForm(self::UID_OF_OWN_EVENT);

        $this->executeRequestWithLoggedInUser([
            self::PLUGIN_NAMESPACE . '[action]' => 'update',
            self::PLUGIN_NAMESPACE . '[controller]' => 'Event',
            self::PLUGIN_NAMESPACE . '[__trustedProperties]' => $trustedProperties,
            self::PLUGIN_NAMESPACE . '[event][__identity]' => (string)self::UID_OF_OWN_EVENT,
            self::PLUGIN_NAMESPACE . '[event][title]' => 'Updated Title',
        ]);

        $this->assertCSVDataSet(__DIR__ . '/Fixtures/Assertions/EventController/Update/UpdatedEventWithNewTitle.csv');
    }

    #[Test]
    public function updateActionFromNonOwnerRedirectsToListAndLeavesEventUnchanged(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/Database/EventController/EventOwnedByLoggedInUser.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/Database/EventController/EventOwnedByOtherUser.csv');
        // The trusted-properties token only encodes allowed field *names*, not a specific
        // record - reusing the token from the logged-in user's own edit form is valid.
        $trustedProperties = $this->getTrustedPropertiesFromEditForm(self::UID_OF_OWN_EVENT);

        $response = $this->executeRequestWithLoggedInUser([
            self::PLUGIN_NAMESPACE . '[action]' => 'update',
            self::PLUGIN_NAMESPACE . '[controller]' => 'Event',
            self::PLUGIN_NAMESPACE . '[__trustedProperties]' => $trustedProperties,
            self::PLUGIN_NAMESPACE . '[event][__identity]' => (string)self::UID_OF_OTHER_EVENT,
            self::PLUGIN_NAMESPACE . '[event][title]' => 'Hijacked Title',
        ]);

        self::assertRedirectsToListAction($response);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/Assertions/EventController/Update/EventUnchangedAfterRejectedUpdate.csv');
    }

    #[Test]
    public function editActionFromNonOwnerRedirectsToList(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/Database/EventController/EventOwnedByOtherUser.csv');

        $response = $this->executeRequestWithLoggedInUser([
            self::PLUGIN_NAMESPACE . '[action]' => 'edit',
            self::PLUGIN_NAMESPACE . '[controller]' => 'Event',
            self::PLUGIN_NAMESPACE . '[event]' => (string)self::UID_OF_OTHER_EVENT,
        ]);

        self::assertRedirectsToListAction($response);
    }

    #[Test]
    public function deleteActionFromOwnerRemovesEvent(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/Database/EventController/EventOwnedByLoggedInUser.csv');

        $this->executeRequestWithLoggedInUser([
            self::PLUGIN_NAMESPACE . '[action]' => 'delete',
            self::PLUGIN_NAMESPACE . '[controller]' => 'Event',
            self::PLUGIN_NAMESPACE . '[event]' => (string)self::UID_OF_OWN_EVENT,
        ]);

        $this->assertCSVDataSet(__DIR__ . '/Fixtures/Assertions/EventController/Delete/DeletedEvent.csv');
    }

    #[Test]
    public function deleteActionFromNonOwnerRedirectsToListAndLeavesEventUnchanged(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/Database/EventController/EventOwnedByOtherUser.csv');

        $response = $this->executeRequestWithLoggedInUser([
            self::PLUGIN_NAMESPACE . '[action]' => 'delete',
            self::PLUGIN_NAMESPACE . '[controller]' => 'Event',
            self::PLUGIN_NAMESPACE . '[event]' => (string)self::UID_OF_OTHER_EVENT,
        ]);

        self::assertRedirectsToListAction($response);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/Assertions/EventController/Delete/EventUnchangedAfterRejectedDelete.csv');
    }

    /**
     * @param array<string, string> $queryParameters
     * @param positive-int $userUid
     */
    private function getHtmlWithLoggedInUser(array $queryParameters = [], int $userUid = 1): string
    {
        return (string)$this->executeRequestWithLoggedInUser($queryParameters, $userUid)->getBody();
    }

    /**
     * @param array<string, string> $queryParameters
     * @param positive-int $userUid
     */
    private function executeRequestWithLoggedInUser(array $queryParameters = [], int $userUid = 1): ResponseInterface
    {
        $request = (new InternalRequest())
            ->withPageId(self::UID_OF_PAGE)
            ->withQueryParameters($queryParameters);

        $context = (new InternalRequestContext())->withFrontendUserId($userUid);

        return $this->executeFrontendSubRequest($request, $context);
    }

    private function getTrustedPropertiesFromNewForm(): string
    {
        $html = $this->getHtmlWithLoggedInUser([
            self::PLUGIN_NAMESPACE . '[action]' => 'new',
            self::PLUGIN_NAMESPACE . '[controller]' => 'Event',
        ]);

        return $this->getTrustedPropertiesFromHtml($html);
    }

    /**
     * @param positive-int $eventUid
     */
    private function getTrustedPropertiesFromEditForm(int $eventUid): string
    {
        $html = $this->getHtmlWithLoggedInUser([
            self::PLUGIN_NAMESPACE . '[action]' => 'edit',
            self::PLUGIN_NAMESPACE . '[controller]' => 'Event',
            self::PLUGIN_NAMESPACE . '[event]' => (string)$eventUid,
        ]);

        return $this->getTrustedPropertiesFromHtml($html);
    }

    private function getTrustedPropertiesFromHtml(string $html): string
    {
        $matches = [];
        preg_match('/__trustedProperties]" value="([a-zA-Z0-9&{};:,_\[\]]+)"/', $html, $matches);
        if (!isset($matches[1])) {
            throw new \RuntimeException('Could not fetch trustedProperties from returned HTML.', 1756000001);
        }

        return html_entity_decode($matches[1]);
    }

    private static function assertRedirectsToListAction(ResponseInterface $response): void
    {
        self::assertSame(303, $response->getStatusCode());
        self::assertStringContainsString(
            self::PLUGIN_NAMESPACE . '%5Baction%5D=list',
            $response->getHeaderLine('Location'),
        );
    }
}
