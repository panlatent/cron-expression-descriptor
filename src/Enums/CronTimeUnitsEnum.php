<?php

namespace Panlatent\CronExpressionDescriptor\Enums;

use MyCLabs\Enum\Enum;

/**
 * Class represents a cron time unit.
 */
class CronTimeUnitsEnum extends Enum
{
    public const SECOND = 'second';
    public const MINUTE = 'minute';
    public const HOUR = 'hour';
    public const DAY = 'day';
    public const WEEKDAY = 'weekday';
    public const MONTH = 'month';
    public const YEAR = 'year';

    /**
     * Get total count of units in time unit.
     *
     * @return int|null
     */
    public function getTotalValue(): ?int
    {
        switch ($this->value) {
            case self::SECOND:
                return 60;
            case self::MINUTE:
                return 60;
            case self::HOUR:
                return 24;
            case self::DAY:
                return 31;
            case self::WEEKDAY:
                return 7;
            case self::MONTH:
                return 12;
            default:
            case self::YEAR:
                return null;
        }
    }

    //--------------------------------------------------------------------------
    // These methods are just for IDE autocomplete and not are mandatory.
    //--------------------------------------------------------------------------
    public static function SECOND(): self
    {
        return new self (self::SECOND);
    }

    public static function MINUTE(): self
    {
        return new self (self::MINUTE);
    }

    public static function HOUR(): self
    {
        return new self (self::HOUR);
    }

    public static function DAY(): self
    {
        return new self (self::DAY);
    }

    public static function WEEKDAY(): self
    {
        return new self (self::WEEKDAY);
    }

    public static function MONTH(): self
    {
        return new self (self::MONTH);
    }

    public static function YEAR(): self
    {
        return new self (self::YEAR);
    }
}
