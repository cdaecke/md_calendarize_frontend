<?php

declare(strict_types=1);

namespace Mediadreams\MdCalendarizeFrontend\Tests\Unit\Controller;

use Mediadreams\MdCalendarizeFrontend\Controller\EventBaseController;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Pagination\ArrayPaginator;
use TYPO3\CMS\Core\Pagination\PaginatorInterface;
use TYPO3\CMS\Core\Pagination\SlidingWindowPagination;
use TYPO3\CMS\Extbase\Mvc\ExtbaseRequestParameters;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Extbase\Mvc\RequestInterface;

#[CoversClass(EventBaseController::class)]
final class EventBaseControllerTest extends TestCase
{
    private TestableEventBaseController $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = (new \ReflectionClass(TestableEventBaseController::class))->newInstanceWithoutConstructor();
    }

    public function testCreatesSlidingWindowForFirstPage(): void
    {
        $pagination = $this->createPagination(1);

        self::assertSame([1, 2, 3, 4, 5], $pagination->getAllPageNumbers());
        self::assertNull($pagination->getPreviousPageNumber());
        self::assertSame(2, $pagination->getNextPageNumber());
        self::assertFalse($pagination->getHasLessPages());
        self::assertTrue($pagination->getHasMorePages());
    }

    public function testCreatesSlidingWindowForMiddlePage(): void
    {
        $pagination = $this->createPagination(5);

        self::assertSame([3, 4, 5, 6, 7], $pagination->getAllPageNumbers());
        self::assertSame(4, $pagination->getPreviousPageNumber());
        self::assertSame(6, $pagination->getNextPageNumber());
        self::assertTrue($pagination->getHasLessPages());
        self::assertTrue($pagination->getHasMorePages());
    }

    public function testCreatesSlidingWindowForLastPage(): void
    {
        $pagination = $this->createPagination(10);

        self::assertSame([6, 7, 8, 9, 10], $pagination->getAllPageNumbers());
        self::assertSame(9, $pagination->getPreviousPageNumber());
        self::assertNull($pagination->getNextPageNumber());
        self::assertTrue($pagination->getHasLessPages());
        self::assertFalse($pagination->getHasMorePages());
    }

    public function testPaginatorLimitsPageNumberToLastPage(): void
    {
        $pagination = $this->createPagination(999);

        self::assertSame(10, $pagination->getPaginator()->getCurrentPageNumber());
        self::assertSame([6, 7, 8, 9, 10], $pagination->getAllPageNumbers());
    }

    public function testUsesAtLeastOnePaginationLink(): void
    {
        $paginator = new ArrayPaginator(range(1, 100), 5, 10);

        $pagination = $this->subject->buildPagination($paginator, 0);

        self::assertSame(1, $pagination->getMaximumNumberOfLinks());
        self::assertSame([5], $pagination->getAllPageNumbers());
    }

    public function testReadsCurrentPageArgumentUsedByPaginationLinks(): void
    {
        $this->subject->setRequestForTest($this->createRequest(['currentPage' => '5']));

        self::assertSame(5, $this->subject->resolveCurrentPageNumber());
    }

    /**
     * @param array<string, mixed> $arguments
     */
    #[DataProvider('invalidCurrentPageArguments')]
    public function testFallsBackToFirstPageForInvalidCurrentPage(array $arguments): void
    {
        $this->subject->setRequestForTest($this->createRequest($arguments));

        self::assertSame(1, $this->subject->resolveCurrentPageNumber());
    }

    /**
     * @return iterable<string, array{array<string, mixed>}>
     */
    public static function invalidCurrentPageArguments(): iterable
    {
        yield 'missing' => [[]];
        yield 'zero' => [['currentPage' => '0']];
        yield 'negative' => [['currentPage' => '-1']];
        yield 'non-numeric' => [['currentPage' => 'invalid']];
        yield 'array' => [['currentPage' => ['1']]];
    }

    private function createPagination(int $currentPage): SlidingWindowPagination
    {
        $paginator = new ArrayPaginator(range(1, 100), $currentPage, 10);

        return $this->subject->buildPagination($paginator, 5);
    }

    /**
     * @param array<string, mixed> $arguments
     */
    private function createRequest(array $arguments): Request
    {
        $extbaseRequestParameters = new ExtbaseRequestParameters();
        $extbaseRequestParameters->setArguments($arguments);

        return new Request(
            (new ServerRequest())->withAttribute('extbase', $extbaseRequestParameters)
        );
    }
}

final class TestableEventBaseController extends EventBaseController
{
    public function setRequestForTest(RequestInterface $request): void
    {
        $this->request = $request;
    }

    public function resolveCurrentPageNumber(): int
    {
        return $this->getCurrentPageNumber();
    }

    public function buildPagination(
        PaginatorInterface $paginator,
        int $maximumNumberOfLinks
    ): SlidingWindowPagination {
        return $this->createPagination($paginator, $maximumNumberOfLinks);
    }
}
