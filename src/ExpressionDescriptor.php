<?php
/**
 * Cron Expression Descriptor
 *
 * @link      https://github.com/panlatent/cron-expression-descriptor
 * @copyright Copyright (c) 2019 panlatent@gmail.com
 */

namespace Panlatent\CronExpressionDescriptor;

use DateTime;
use IntlDateFormatter;
use Panlatent\CronExpressionDescriptor\Enums\CronTimeUnitsEnum;
use Panlatent\CronExpressionDescriptor\Exceptions\ExpressionException;
use Panlatent\CronExpressionDescriptor\Utils\ArrayUtils;
use Panlatent\CronExpressionDescriptor\Utils\StringUtils;

/**
 * Class ExpressionDescriptor
 *
 * @package Panlatent\CronExpressionDescriptor
 * @author  Panlatent <panlatent@gmail.com>
 * @author  CaliforniaMountainSnake <CaliforniaMountainSnake1@yandex.ru>
 */
class ExpressionDescriptor
{
    use ArrayUtils;
    use StringUtils;

    // Properties
    // =========================================================================

    /**
     * @var string[]|null
     */
    protected $expression;

    /**
     * @var bool
     */
    protected $isUse24HourTimeFormat;

    /**
     * @var string[]
     */
    protected $defaultSpecialCharacters = ['/', '-', ',', '*'];

    /**
     * @var string
     */
    private $language;

    /**
     * @var string
     */
    private $locale;

    /*
     * @var bool
     */
    private $fallback;

    /**
     * @var callable|null
     */
    private $translator;

    /**
     * @var bool
     */
    private $intl;

    // Public Methods
    // =========================================================================

    /**
     * ExpressionDescriptor constructor.
     *
     * @param ExpressionParser|array|string $expression
     * @param string $locale
     * @param bool   $isUse24HourTimeFormat
     * @param bool   $fallback
     *
     * @throws ExpressionException
     */
    public function __construct($expression, string $locale = 'en_US', bool $isUse24HourTimeFormat = false, bool $fallback = true)
    {
        if ($expression instanceof ExpressionParser) {
            [$second, $minute, $hour, $day, $month, $week, $year] = $expression->parse();
        } elseif (is_array($expression)) {
            [$second, $minute, $hour, $day, $month, $week, $year] = $expression;
        } else {
            [$second, $minute, $hour, $day, $month, $week, $year] = (new ExpressionParser((string)$expression))->parse();
        }

        $this->expression = compact('second', 'minute', 'hour', 'day', 'month', 'week', 'year');
        $this->isUse24HourTimeFormat = $isUse24HourTimeFormat;

        // Intl extension receives a locale in en_US format, but we only have en.php file.
        $this->locale = str_replace('-', '_', $locale);
        $this->language = str_replace('_', '-', $locale);
        $this->fallback = $fallback;
        $this->intl = extension_loaded('intl');
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        $timeSegment = $this->getTimeOfDayDescription();
        $dayOfMonthDesc = $this->getDayOfMonthDescription();
        $monthDesc = $this->getMonthDescription();
        $dayOfWeekDesc = $this->getDayOfWeekDescription();
        $yearDesc = $this->getYearDescription();

        $description = $timeSegment . $dayOfMonthDesc . $dayOfWeekDesc . $monthDesc . $yearDesc;

        $description = str_replace([
            $this->translate('ComaEveryMinute'),
            $this->translate('ComaEveryHour'),
            $this->translate('ComaEveryDay'),
            ', ' . $this->translate('EveryMinute'),
            ', ' . $this->translate('EveryHour'),
            ', ' . $this->translate('EveryDay'),
            ', ' . $this->translate('EveryYear'),
        ], '', $description);

        return ucfirst(rtrim($description, ', '));
    }

    /**
     * @return string
     */
    public function getTimeOfDayDescription(): string
    {
        $second = $this->expression['second'];
        $minute = $this->expression['minute'];
        $hour = $this->expression['hour'];

        if (!$this->stringContains($minute, $this->defaultSpecialCharacters)
            && !$this->stringContains($hour, $this->defaultSpecialCharacters)
            && !$this->stringContains($second, $this->defaultSpecialCharacters)
        ) {
            // single minute and single hour
            $description = $this->translate('AtSpace') . $this->formatTime($hour, $minute, $second);
        } elseif ($second === '' && $this->stringContains($minute, '-')
            && !$this->stringContains($minute, ',')
            && !$this->stringContains($hour, $this->defaultSpecialCharacters)
        ) {
            //minute range in single hour (i.e. 0-10 11)
            $minuteParts = explode('-', $minute);
            $description = $this->translate('EveryMinuteBetween{0}And{1}', [
                $this->formatTime($hour, $minuteParts[0]),
                $this->formatTime($hour, $minuteParts[1]),
            ]);
        } elseif ($second === ''
            && $this->stringContains($hour, ',')
            && !$this->stringContains($hour, '-')
            && !$this->stringContains($minute, $this->defaultSpecialCharacters)
            && !$this->isEveryTimeUnit(CronTimeUnitsEnum::HOUR(), $hour)
        ) {
            //hours list with single minute (o.e. 30 6,14,16)
            $hourParts = explode(',', $hour);
            $description = $this->translate('At');

            foreach ($hourParts as $i => $hourPart) {
                $description .= ' ' . $this->formatTime($hourPart, $minute);
                if ($i < (count($hourParts) - 2)) {
                    $description .= ',';
                }

                if ($i === count($hourParts) - 2) {
                    $description .= $this->translate('SpaceAnd');
                }
            }
        } else {
            //default time description
            $secondsDescription = $this->getSecondsDescription();
            $minutesDescription = $this->getMinutesDescription();
            $hoursDescription = $this->getHoursDescription();

            $description = $secondsDescription;

            if ($description !== '' && $minutesDescription !== '') {
                $description .= ', ';
            }
            $description .= $minutesDescription;

            if ($description !== '' && $hoursDescription !== '') {
                $description .= ', ';
            }

            $description .= $hoursDescription;
        }

        return $description;
    }

    /**
     * @return string
     */
    public function getSecondsDescription(): string
    {
        return $this->getSegmentDescription(
            CronTimeUnitsEnum::SECOND(),
            $this->expression['second'],
            $this->translate('EverySecond'),
            static function ($s) {
                return $s;
            },
            function ($s) {
                return $this->translate('Every{0}Seconds', [$s]);
            },
            function () {
                return $this->translate('Seconds{0}Through{1}PastTheMinute');
            },
            function ($s) {
                if (is_int($s) || ctype_alnum($s)) {
                    if ($s === '0') {
                        return '';
                    }

                    return ($s < 20)
                        ? $this->translate('At{0}SecondsPastTheMinute')
                        : $this->translate('At{0}SecondsPastTheMinuteGt20', [], false) ??
                        $this->translate('At{0}SecondsPastTheMinute');

                }

                return $this->translate('At{0}SecondsPastTheMinute');
            },
            function () {
                return $this->translate('ComaMin{0}ThroughMin{1}', [], false) ?? $this->translate('Coma{0}Through{1}');
            }
        );
    }


    /**
     * @return string
     */
    public function getMinutesDescription(): string
    {
        return $this->getSegmentDescription(
            CronTimeUnitsEnum::MINUTE(),
            $this->expression['minute'],
            $this->translate('EveryMinute'),
            static function ($s) {
                return $s;
            },
            function ($s) {
                return $this->translate('Every{0}Minutes', [$s]);
            },
            function () {
                return $this->translate('Minutes{0}Through{1}PastTheHour');
            },
            function ($s) {
                if (ctype_alnum($s)) {
                    return $s === '0' ? '' : $this->translate('At{0}MinutesPastTheHour');
                }

                return $this->translate('At{0}MinutesPastTheHour');
            },
            function () {
                return $this->translate('Coma{0}Through{1}');
            }
        );
    }

    /**
     * @return string
     */
    public function getHoursDescription(): string
    {
         return $this->getSegmentDescription(
            CronTimeUnitsEnum::HOUR(),
            $this->expression['hour'],

            $this->translate('EveryHour'),
            function ($s) {
                return $this->formatTime($s, '0');
            },
            function ($s) {
                return $this->translate('Every{0}Hours', [$s]);
            },
            function () {
                return $this->translate('Between{0}And{1}');
            },
            function () {
                return $this->translate('At{0}');
            },
            function () {
                return $this->translate('Coma{0}Through{1}');
            }
        );
    }

    /**
     * @return string
     */
    public function getDayOfWeekDescription(): string
    {
        // DOW is specified as * so we will not generate a description and defer to DOM part.
        // Otherwise, we could get a contradiction like "on day 1 of the month, every day"
        // or a dupe description like "every day, every day".
        return $this->getSegmentDescription(
            CronTimeUnitsEnum::WEEKDAY(),
            $this->expression['week'],
            $this->translate('ComaEveryDay'),
            function ($s) {
                $pos = strpos($s, '#');
                if ($pos !== false) {
                    $exp = substr($s, 0, $pos);
                } else {
                    $exp = ((strpos($s, 'L') !== false) ? str_replace('L', '', $s) : $s);
                }

                if ($this->intl) {
                    $fmt = new IntlDateFormatter($this->locale, IntlDateFormatter::FULL, IntlDateFormatter::FULL,
                        null, IntlDateFormatter::GREGORIAN, 'EEEE');
                    return $fmt->format((new DateTime("Sunday +{$exp} day"))->getTimestamp());
                }

                return $exp;
            },
            function ($s) {
                return $this->translate('ComaEvery{0}DaysOfTheWeek', ['0' => $s]);
            },
            function () {
                return $this->translate('Coma{0}Through{1}');
            },
            function ($s) {
                $format = null;
                if (($pos = strpos($s, '#')) !== false) {
                    $dayOfWeekOfMonthNumber = substr($s, $pos + 1);
                    $dayOfWeekOfMonthDescription = null;
                    switch ($dayOfWeekOfMonthNumber) {
                        case '1':
                            $dayOfWeekOfMonthDescription = $this->translate('First');
                            break;
                        case '2':
                            $dayOfWeekOfMonthDescription = $this->translate('Second');
                            break;
                        case '3':
                            $dayOfWeekOfMonthDescription = $this->translate('Third');
                            break;
                        case '4':
                            $dayOfWeekOfMonthDescription = $this->translate('Fourth');
                            break;
                        case '5':
                            $dayOfWeekOfMonthDescription = $this->translate('Fifth');
                            break;
                    }
                    $format = $this->translate('ComaOnThe') . $dayOfWeekOfMonthDescription
                        . $this->translate('Space{0}OfTheMonth');
                } elseif (strpos($s, 'L') !== false) {
                    $format = $this->translate('ComaOnTheLast{0}OfTheMonth');
                } else {
                    $format = $this->translate('ComaOnlyOn{0}');
                }

                return $format;
            },
            function () {
                return $this->translate('Coma{0}Through{1}');
            }
        );
    }

    /**
     * @return string
     */
    public function getMonthDescription(): string
    {
        return $this->getSegmentDescription(
            CronTimeUnitsEnum::MONTH(),
            $this->expression['month'],
            '',
            function ($s) {
                $datetime = new DateTime("$s/01");
                if ($this->intl) {
                    $fmt = new IntlDateFormatter($this->locale, IntlDateFormatter::FULL, IntlDateFormatter::FULL,
                        null, IntlDateFormatter::GREGORIAN, 'LLLL');
                    return $fmt->format($datetime->getTimestamp());
                }

                return $datetime->format('F');
            },
            function ($s) {
                return $this->translate('ComaEvery{0}Months', ['0' => $s]);
            },
            function () {
                return $this->translate('Coma{0}Through{1}');
            },
            function () {
                return $this->translate('ComaOnlyIn{0}');
            },
            function () {
                return $this->translate('Coma{0}Through{1}');
            }
        );
    }

    /**
     * @return string
     */
    public function getDayOfMonthDescription(): string
    {
        $description = null;
        $expression = $this->expression['day'];

        switch ($expression) {
            case 'L':
                $description = $this->translate('ComaOnTheLastDayOfTheMonth');
                break;
            case 'WL':
            case 'LW':
                $description = $this->translate('ComaOnTheLastWeekdayOfTheMonth');
                break;
            default:
                if (preg_match('#(\\d{1,2}W)|(W\\d{1,2})#', $expression, $match)) {
                    $dayNumber = (int)str_replace('W', '', $match[1]);
                    $dayString = $dayNumber === 1 ? $this->translate('FirstWeekday')
                        : $this->translate('WeekdayNearestDay{0}', ['0' => $dayNumber]);
                    $description = $this->translate('ComaOnThe{0}OfTheMonth', ['0' => $dayString]);
                    break;
                }

                // Handle "last day offset" (i.e. L-5:  "5 days before the last day of the month")
                if (preg_match('#L-(\\d{1,2})#', $expression, $match)) {
                    $offSetDays = $match[1];
                    $description = $this->translate('CommaDaysBeforeTheLastDayOfTheMonth', $offSetDays);
                    break;
                }

                $description = $this->getSegmentDescription(
                    CronTimeUnitsEnum::DAY(),
                    $expression,
                    $this->translate('ComaEveryDay'),
                    static function ($s) {
                        return $s;
                    },
                    function ($s) {
                        return $this->translate($s === '1' ? 'ComaEveryDay' : 'ComaEvery{0}Days');
                    },
                    function () {
                        return $this->translate('ComaBetweenDay{0}And{1}OfTheMonth');
                    },
                    function () {
                        return $this->translate('ComaOnDay{0}OfTheMonth');
                    },
                    function () {
                        return $this->translate('ComaX0ThroughX1');
                    });
                break;
        }

        return $description;
    }

    /**
     * @return string
     */
    public function getYearDescription(): string
    {
        return $this->getSegmentDescription(
            CronTimeUnitsEnum::YEAR(),
            $this->expression['year'], '',
            static function ($s) {
                if (preg_match('#^\d+$#', $s)) {
                    return (new DateTime("$s-01-01"))->format('Y');
                }

                return $s;
            },
            function ($s) {
                return $this->translate('ComaEvery{0}Years', [$s]);
            },
            function () {
                return $this->translate('Coma{0}Through{1}');
            },
            function () {
                return $this->translate('ComaOnlyIn{0}');
            },
            function () {
                return $this->translate('Coma{0}Through{1}');
            }
        );
    }

    // Protected Methods
    // =========================================================================

    /**
     * @param CronTimeUnitsEnum $timeUnit
     * @param string            $expressionPart
     * @param string            $allDescription
     * @param callable          $getSingleItemDescription
     * @param callable          $getIntervalDescriptionFormat
     * @param callable          $getBetweenDescriptionFormat
     * @param callable          $getDescriptionFormat
     * @param callable          $getRangeFormat
     *
     * @return string|null
     */
    protected function getSegmentDescription(
        CronTimeUnitsEnum $timeUnit,
        string $expressionPart,
        string $allDescription,
        callable $getSingleItemDescription,
        callable $getIntervalDescriptionFormat,
        callable $getBetweenDescriptionFormat,
        callable $getDescriptionFormat,
        callable $getRangeFormat
    ): ?string {
        $description = null;
        if ($expressionPart === '') {
            return '';
        }

        if ($this->isEveryTimeUnit($timeUnit, $expressionPart)) {
            // Every time unit
            $description = $allDescription;
        } elseif (!$this->stringContains($expressionPart, ['/', '-', ','])) {
            $description = strtr($getDescriptionFormat($expressionPart),
                ['{0}' => $getSingleItemDescription($expressionPart)]);
        } elseif (!$this->stringContains($expressionPart, ['/', '-'])
            && (($everyNUnit = $this->detectIntervalUnit($timeUnit, $expressionPart)) !== null)
        ) {
            // Interval via the set of values that multiple to some number.
            $description = strtr($getIntervalDescriptionFormat($everyNUnit),
                ['{0}' => $getSingleItemDescription($everyNUnit)]);
        } elseif ($this->stringContains($expressionPart, '/')) {
            // Interval via "/"
            $segments = explode('/', $expressionPart);

            $description = strtr($getIntervalDescriptionFormat($segments[1]),
                ['{0}' => $getSingleItemDescription($segments[1])]);

            //interval contains 'between' piece (i.e. 2-59/3 )
            if (strpos($segments[0], '-')) {
                $betweenSegmentDescription = $this->generateBetweenSegmentDescription($segments[0],
                    $getBetweenDescriptionFormat, $getSingleItemDescription);

                if (strpos($betweenSegmentDescription, ',') !== 0) {
                    $description .= ', ';
                }

                $description .= $betweenSegmentDescription;
            } elseif ($this->stringContains($segments[0], ['*', ',']) === false) {
                $rangeItemDescription = strtr($getDescriptionFormat($segments[0]),
                    ['{0}' => $getSingleItemDescription($segments[0])]);
                //remove any leading comma
                $rangeItemDescription = str_replace(', ', '', $rangeItemDescription);

                $description .= $this->translate('CommaStarting{0}', ['0' => $rangeItemDescription]);
            }
        } elseif ($this->stringContains($expressionPart, ',')) {
            $segments = explode(',', $expressionPart);

            $descriptionContent = '';

            foreach ($segments as $i => $segment) {
                if ($i > 0 && count($segments) > 2) {
                    $descriptionContent .= ',';

                    if ($i < count($segments) - 1) {
                        $descriptionContent .= ' ';
                    }
                }

                if ($i > 0 && count($segments) > 1 && ($i === count($segments) - 1 || count($segments) === 2)) {
                    $descriptionContent .= $this->translate('SpaceAndSpace');
                }

                if (strpos($segment, '-') !== false) {
                    $betweenSegmentDescription = $this->generateBetweenSegmentDescription($segment,
                        $getRangeFormat, $getSingleItemDescription);

                    //remove any leading comma
                    $betweenSegmentDescription = str_replace(', ', '', $betweenSegmentDescription);

                    $descriptionContent .= $betweenSegmentDescription;
                } else {
                    $descriptionContent .= $getSingleItemDescription($segments[$i]);
                }
            }

            $description = strtr($getDescriptionFormat($expressionPart), ['{0}' => $descriptionContent]);
        } elseif ($this->stringContains($expressionPart, '-')) {
            $description = $this->generateBetweenSegmentDescription($expressionPart, $getBetweenDescriptionFormat,
                $getSingleItemDescription);
        }

        return $description;
    }

    /**
     * @param string   $betweenExpression
     * @param callable $getBetweenDescriptionFormat
     * @param callable $getSingleItemDescription
     *
     * @return string
     */
    protected function generateBetweenSegmentDescription(
        string $betweenExpression,
        callable $getBetweenDescriptionFormat,
        callable $getSingleItemDescription
    ): string {
        $description = '';
        $betweenSegments = explode('-', $betweenExpression);
        $betweenSegment1Description = $getSingleItemDescription($betweenSegments[0]);
        $betweenSegment2Description = $getSingleItemDescription($betweenSegments[1]);
        $betweenSegment2Description = str_replace(':00', ':59', $betweenSegment2Description);
        $betweenDescriptionFormat = $getBetweenDescriptionFormat($betweenExpression);
        $description .= strtr($betweenDescriptionFormat,
            ['{0}' => $betweenSegment1Description, '{1}' => $betweenSegment2Description]);

        return $description;
    }

    /**
     * @param string $hour
     * @param string $minute
     * @param string $second
     *
     * @return string
     */
    protected function formatTime(string $hour, string $minute, string $second = ''): string
    {
        $period = '';

        if (!$this->isUse24HourTimeFormat) {
            $period = $this->translate($hour >= 12 ? 'PMPeriod' : 'AMPeriod', [], false);

            if (!empty($period)) {
                $period = ' ' . $period;
            }

            if ($hour > 12) {
                $hour -= 12;
            } elseif ($hour == 0) {
                $hour = 12;
            }
        }

        if ($second !== '') {
            $second = ':' . str_pad($second, 2, '0', STR_PAD_LEFT);
        }

        return sprintf('%s:%s%s%s', str_pad($hour, 2, '0', STR_PAD_LEFT), str_pad($minute, 2, '0', STR_PAD_LEFT),
            $second, $period);
    }

    /**
     * @param string $source
     * @param array  $params
     * @param bool   $forceTranslate
     *
     * @return string|null
     */
    protected function translate(string $source, array $params = [], bool $forceTranslate = true): ?string
    {
        if ($this->translator !== null) {
            return call_user_func($this->translator, $source, $params);
        }

        static $translations = [];
        if (!isset($translations[$this->language])) {
            $translations[$this->language] = $this->load($this->language);
        }

        if (!isset($translations[$this->language][$source])) {
            return $forceTranslate ? $source : null;
        }

        if (empty($params)) {
            return $translations[$this->language][$source];
        }

        foreach ($params as $k => $v) {
            $params["{{$k}}"] = $v;
            unset($params[$k]);
        }

        return strtr($translations[$this->language][$source], $params);
    }

    /**
     * @param CronTimeUnitsEnum $_time_unit
     * @param string            $_expression_part
     *
     * @return bool
     */
    protected function isEveryTimeUnit(CronTimeUnitsEnum $_time_unit, string $_expression_part): bool
    {
        $parts = explode(',', $_expression_part);
        if ($_expression_part === '*') {
            return true;
        }

        switch ((string)$_time_unit) {
            case CronTimeUnitsEnum::MINUTE: // No break
            case CronTimeUnitsEnum::SECOND:
                return $this->isContainsAllValues($parts, range(0, 59));
            case CronTimeUnitsEnum::HOUR:
                return $this->isContainsAllValues($parts, range(0, 23));
            case CronTimeUnitsEnum::DAY:
                return $this->isContainsAllValues($parts, range(1, 31));
            case CronTimeUnitsEnum::WEEKDAY:
                return $this->isContainsAllValues($parts, range(0, 6))
                    || $this->isContainsAllValues($parts, range(1, 7))
                    || $this->isContainsAllValues($parts, ['MON', 'TUE', 'WED', 'THU', 'FRI', 'SAT', 'SUN']);
            case CronTimeUnitsEnum::MONTH:
                return $this->isContainsAllValues($parts, range(1, 12))
                    || $this->isContainsAllValues($parts,
                        ['JAN', 'FEB', 'MAR', 'APR', 'MAY', 'JUN', 'JUL', 'AUG', 'SEP', 'OCT', 'NOV', 'DEC']);
        }

        return false;
    }

    /*
     * @param string $language
     * @return array
     */
    private function load(string $language)
    {
        $file = dirname(__DIR__) . '/languages/' . $language . '.php';
        if (is_file($file)) {
            return require $file;
        }

        if (($pos = strpos($language, '-')) !== false && $this->fallback) {
            $file = dirname(__DIR__) . '/languages/' . substr($language, 0, $pos) . '.php';
            if (is_file($file)) {
                return require $file;
            }
        }

        return [];
    }
}
