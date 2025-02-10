<?php
declare(strict_types=1);

namespace Mediadreams\MdCalendarizeFrontend\Upgrades;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\CMS\Install\Attribute\UpgradeWizard;
use TYPO3\CMS\Install\Updates\AbstractListTypeToCTypeUpdate;

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
