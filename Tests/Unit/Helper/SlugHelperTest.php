<?php

declare(strict_types=1);

namespace Mediadreams\MdCalendarizeFrontend\Tests\Unit\Helper;

use Mediadreams\MdCalendarizeFrontend\Domain\Model\Event;
use Mediadreams\MdCalendarizeFrontend\Helper\SlugHelper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;

#[CoversClass(SlugHelper::class)]
final class SlugHelperTest extends TestCase
{
    public function testRejectsUnpersistedDomainObject(): void
    {
        $subject = new SlugHelper(self::createStub(TcaSchemaFactory::class));

        $this->expectException(\LogicException::class);
        $this->expectExceptionCode(1787665432);

        $subject->getSlug(
            new Event(),
            ['title' => 'TYPO3 event'],
            'tx_calendarize_domain_model_event'
        );
    }
}
