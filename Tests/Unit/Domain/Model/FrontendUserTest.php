<?php

declare(strict_types=1);

namespace Mediadreams\MdCalendarizeFrontend\Tests\Unit\Domain\Model;

use Mediadreams\MdCalendarizeFrontend\Domain\Model\FrontendUser;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(FrontendUser::class)]
final class FrontendUserTest extends TestCase
{
    public function testInitializeObjectInitializesStoragesWhenConstructorWasSkipped(): void
    {
        $subject = (new \ReflectionClass(FrontendUser::class))->newInstanceWithoutConstructor();

        $subject->initializeObject();

        self::assertCount(0, $subject->getUsergroup());
        self::assertCount(0, $subject->getImage());
    }

    public function testLastloginCanBeNull(): void
    {
        $subject = new FrontendUser();

        self::assertNull($subject->getLastlogin());
    }
}
