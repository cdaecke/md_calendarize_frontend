<?php
declare(strict_types=1);

namespace Mediadreams\MdCalendarizeFrontend\ViewHelpers;

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

use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Exception;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithContentArgumentAndRenderStatic;

/**
 * Formats an object implementing :php:`\DateTimeInterface`.
 * Copy of core viewhelper `DateViewHelper` with the difference, that it returns the dat in UTC
 *
 * Examples
 * ========
 *
 * Defaults
 * --------
 *
 * ::
 *
 *    <f:format.date>{dateObject}</f:format.date>
 *
 * ``1980-12-13``
 * Depending on the current date.
 *
 * Custom date format
 * ------------------
 *
 * ::
 *
 *    <f:format.date format="H:i">{dateObject}</f:format.date>
 *
 * ``01:23``
 * Depending on the current time.
 *
 * Relative date with given time
 * -----------------------------
 *
 * ::
 *
 *    <f:format.date format="Y" base="{dateObject}">-1 year</f:format.date>
 *
 * ``2016``
 * Assuming dateObject is in 2017.
 *
 * strtotime string
 * ----------------
 *
 * ::
 *
 *    <f:format.date format="d.m.Y - H:i:s">+1 week 2 days 4 hours 2 seconds</f:format.date>
 *
 * ``13.12.1980 - 21:03:42``
 * Depending on the current time, see https://www.php.net/manual/function.strtotime.php.
 *
 * Localized dates using strftime date format
 * ------------------------------------------
 *
 * ::
 *
 *    <f:format.date format="%d. %B %Y">{dateObject}</f:format.date>
 *
 * ``13. Dezember 1980``
 * Depending on the current date and defined locale. In the example you see the 1980-12-13 in a german locale.
 *
 * Inline notation
 * ---------------
 *
 * ::
 *
 *    {f:format.date(date: dateObject)}
 *
 * ``1980-12-13``
 * Depending on the value of ``{dateObject}``.
 *
 * Inline notation (2nd variant)
 * -----------------------------
 *
 * ::
 *
 *    {dateObject -> f:format.date()}
 *
 * ``1980-12-13``
 * Depending on the value of ``{dateObject}``.
 */
class UtcTimeViewHelper extends AbstractViewHelper
{
    /**
     * Needed as child node's output can return a DateTime object which can't be escaped
     *
     * @var bool
     */
    protected $escapeChildren = false;

    /**
     * Initialize arguments
     */
    public function initializeArguments()
    {
        $this->registerArgument('date', 'mixed', 'Either an object implementing DateTimeInterface or a string that is accepted by DateTime constructor');
        $this->registerArgument('format', 'string', 'Format String which is taken to format the Date/Time', false, '');
        $this->registerArgument('base', 'mixed', 'A base time (an object implementing DateTimeInterface or a string) used if $date is a relative date specification. Defaults to current time.');
    }

    /**
     * @return string
     * @throws \TYPO3\CMS\Core\Context\Exception\AspectNotFoundException
     */
    public function render(): string
    {
        $format = $this->arguments['format'];
        $base = $this->arguments['base'] ?? GeneralUtility::makeInstance(Context::class)->getPropertyFromAspect('date', 'timestamp');

        if (is_string($base)) {
            $base = trim($base);
        }

        if ($format === '') {
            $format = $GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'] ?: 'Y-m-d';
        }

        $date = $this->renderChildren();
        if ($date === null) {
            return '';
        }

        if (is_string($date)) {
            $date = trim($date);
        }

        if ($date === '') {
            $date = 'now';
        }

        if (!$date instanceof \DateTimeInterface) {
            try {
                $base = $base instanceof \DateTimeInterface ? $base->format('U') : strtotime((MathUtility::canBeInterpretedAsInteger($base) ? '@' : '') . $base);
                $dateTimestamp = strtotime((MathUtility::canBeInterpretedAsInteger($date) ? '@' : '') . $date, $base);
                $date = new \DateTime('@' . $dateTimestamp);
                $date->setTimezone(new \DateTimeZone('UTC'));
            } catch (\Exception $exception) {
                throw new Exception('"' . $date . '" could not be parsed by \DateTime constructor: ' . $exception->getMessage(), 1241722579);
            }
        }

        if (str_contains($format, '%')) {
            return date($format, (int) $date->format('U'));
        }

        return $date->format($format);
    }
}
