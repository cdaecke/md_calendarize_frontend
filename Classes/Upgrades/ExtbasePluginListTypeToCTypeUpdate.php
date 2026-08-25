<?php
declare(strict_types=1);

namespace Mediadreams\MdCalendarizeFrontend\Upgrades;

use TYPO3\CMS\Core\Attribute\UpgradeWizard;
use TYPO3\CMS\Core\Upgrades\AbstractListTypeToCTypeUpdate;

#[UpgradeWizard('mdCalendarizeFrontend_extbasePluginListTypeToCTypeUpdate')]
final class ExtbasePluginListTypeToCTypeUpdate extends AbstractListTypeToCTypeUpdate
{
    protected function getListTypeToCTypeMapping(): array
    {
        return ['mdcalendarizefrontend_frontend' => 'mdcalendarizefrontend_frontend'];
    }

    public function getTitle(): string
    {
        return 'EXT:md_calendarize_frontend: Migrate list_type plugins to CType';
    }

    public function getDescription(): string
    {
        return 'This wizard migrates the switchableControllerActions in all existing ' .
            'plugins to the new list types. The permissions in BE groups are updated as well to allow all new ' .
            'list types where necessary';
    }
}
