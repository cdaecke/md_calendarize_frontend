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
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\InvalidArgumentValueException;

/**
 * Formats a timestamp or DateTimeInterface in UTC.
 *
 * Usage: `{timestamp -> md:utcTime(format: 'H:i')}`
 */
final class UtcTimeViewHelper extends AbstractViewHelper
{
    protected $escapeChildren = false;

    public function __construct(private readonly Context $context) {}

    public function initializeArguments(): void
    {
        $this->registerArgument('date', 'mixed', 'Either an object implementing DateTimeInterface or a string that is accepted by DateTime constructor');
        $this->registerArgument('format', 'string', 'Format String which is taken to format the Date/Time', false, '');
        $this->registerArgument('base', 'mixed', 'A base time (an object implementing DateTimeInterface or a string) used if $date is a relative date specification. Defaults to current time.');
    }

    public function render(): string
    {
        $format = $this->arguments['format'] ?? '';
        if (!is_string($format)) {
            throw new InvalidArgumentValueException('The format must be a string.', 1787668278);
        }
        if ($format === '') {
            $format = $this->getDefaultDateFormat();
        }

        $date = $this->renderChildren();
        if ($date === null) {
            return '';
        }

        $timestamp = $this->resolveTimestamp($date, $this->arguments['base'] ?? null);
        $utcDate = new \DateTimeImmutable('@' . $timestamp);

        return $utcDate->format($format);
    }

    public function getContentArgumentName(): string
    {
        return 'date';
    }

    private function resolveTimestamp(mixed $date, mixed $base): int
    {
        if ($date instanceof \DateTimeInterface) {
            return $date->getTimestamp();
        }

        if (is_int($date)) {
            return $date;
        }

        if (!is_string($date)) {
            throw new InvalidArgumentValueException('The date must be an integer, string or DateTimeInterface.', 1787668491);
        }

        $date = trim($date);
        if ($date === '') {
            $date = 'now';
        }
        if (MathUtility::canBeInterpretedAsInteger($date)) {
            return (int)$date;
        }

        $timestamp = strtotime($date, $this->resolveBaseTimestamp($base));
        if ($timestamp === false) {
            throw new InvalidArgumentValueException(
                '"' . $date . '" could not be converted to a timestamp.',
                1241722579
            );
        }

        return $timestamp;
    }

    private function resolveBaseTimestamp(mixed $base): int
    {
        if ($base === null) {
            $base = $this->context->getPropertyFromAspect('date', 'timestamp');
        }
        if ($base instanceof \DateTimeInterface) {
            return $base->getTimestamp();
        }
        if (is_int($base)) {
            return $base;
        }
        if (is_string($base)) {
            $base = trim($base);
            if (MathUtility::canBeInterpretedAsInteger($base)) {
                return (int)$base;
            }
            $timestamp = strtotime($base);
            if ($timestamp !== false) {
                return $timestamp;
            }
        }

        throw new InvalidArgumentValueException('The base date could not be converted to a timestamp.', 1787668816);
    }

    private function getDefaultDateFormat(): string
    {
        $typo3Configuration = $GLOBALS['TYPO3_CONF_VARS'] ?? null;
        if (!is_array($typo3Configuration)) {
            return 'Y-m-d';
        }

        $systemConfiguration = $typo3Configuration['SYS'] ?? null;
        if (!is_array($systemConfiguration)) {
            return 'Y-m-d';
        }

        $dateFormat = $systemConfiguration['ddmmyy'] ?? null;

        return is_string($dateFormat) && $dateFormat !== '' ? $dateFormat : 'Y-m-d';
    }
}
