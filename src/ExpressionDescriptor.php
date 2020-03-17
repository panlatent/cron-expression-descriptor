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

/**
 * Class ExpressionDescriptor
 *
 * @package Panlatent\CronExpressionDescriptor
 * @author Panlatent <panlatent@gmail.com>
 */
class ExpressionDescriptor
{
    // Properties
    // =========================================================================

    /**
     * @var string[]|null
     */
    protected $expression;

    /**
     * @var bool
     */
    protected $use24HourTimeFormat = false;

    /**
     * @var string[]
     */
    protected $defaultSpecialCharacters = ['/', '-', ',', '*'];

    /**
     * @var string
     */
    private $language;

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
     * CronExpression constructor.
     *
     * @param string $expression
     * @param string $language
     * @throws ExpressionException
     */
    public function __construct(string $expression, string $language = 'en')
    {
        list($second, $minute, $hour, $day, $month, $week, $year) = (new ExpressionParser($expression))->parse();

        $this->expression = compact('second', 'minute', 'hour', 'day', 'month', 'week', 'year');
        // $language variabel can also contain a locale but we only want the language Example: en-GB = en
        $this->language = explode("-", $language)[0];
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

        if (!StringHelper::contains($minute, $this->defaultSpecialCharacters) &&
            !StringHelper::contains($hour, $this->defaultSpecialCharacters) &&
            !StringHelper::contains($second, $this->defaultSpecialCharacters)) {
            $description = $this->translate('AtSpace') . self::formatTime($hour, $minute, $second);
        } elseif ($second == '' &&
            StringHelper::contains($minute, '-') &&
            !StringHelper::contains($minute, ',') &&
            !StringHelper::contains($hour, $this->defaultSpecialCharacters)) {
            //minute range in single hour (i.e. 0-10 11)
            $minuteParts = explode('-', $minute);
            $description = $this->translate('EveryMinuteBetween{0}And{1}', [
                self::formatTime($hour, $minuteParts[0]),
                self::formatTime($hour, $minuteParts[1]),
            ]);
        } elseif ($second == '' &&
            StringHelper::contains($hour, ',') &&
            !StringHelper::contains($hour, '-') &&
            !StringHelper::contains($minute, $this->defaultSpecialCharacters)) {
            //hours list with single minute (o.e. 30 6,14,16)
            $hourParts = explode(',', $hour);
            $description = $this->translate('At');
            for ($i = 0; $i < count($hourParts); $i++) {
                $description .= ' ' . self::formatTime($hourParts[$i], $minute);
                if ($i < (count($hourParts) - 2)) {
                    $description .= ',';
                }

                if ($i == count($hourParts) - 2) {
                    $description .= $this->translate("SpaceAnd");
                }
            }
        } else {
            //default time description
            $secondsDescription = $this->getSecondsDescription();
            $minutesDescription = $this->getMinutesDescription();
            $hoursDescription = $this->getHoursDescription();

            $description = $secondsDescription;

            if (strlen($description) > 0 && strlen($minutesDescription) > 0) {
                $description .= ', ';
            }
            $description .= $minutesDescription;

            if (strlen($description) > 0 && strlen($hoursDescription) > 0) {
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
        $description = $this->getSegmentDescription(
            $this->expression['second'],
            $this->translate('EverySecond'),
            function ($s) {
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
                    return $s == '0' ? ''
                        : ($s < 20)
                            ? $this->translate('At{0}SecondsPastTheMinute')
                            : $this->translate('At{0}SecondsPastTheMinuteGt20', [], false) ?? $this->translate('At{0}SecondsPastTheMinute');

                } else {
                    return $this->translate('At{0}SecondsPastTheMinute');
                }
            },
            function () {
                return $this->translate('ComaMin{0}ThroughMin{1}', [], false) ?? $this->translate('Coma{0}Through{1}');
            }
        );

        return $description;
    }


    /**
     * @return string
     */
    public function getMinutesDescription(): string
    {
        $description = $this->getSegmentDescription(
            $this->expression['minute'],
            $this->translate('EveryMinute'),
            function ($s) {
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
                    return $s == '0' ? '' : $this->translate('At{0}MinutesPastTheHour');
                } else {
                    return $this->translate('At{0}MinutesPastTheHour');
                }
            },
            function () {
                return $this->translate('Coma{0}Through{1}');
            }
        );

        return $description;
    }

    /**
     * @return string
     */
    public function getHoursDescription(): string
    {
        $expression = $this->expression['hour'];
        $description = $this->getSegmentDescription($expression,
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

        return $description;
    }

    /**
     * @return string
     */
    public function getDayOfWeekDescription(): string
    {
        if ($this->expression['week'] == "*") {
            // DOW is specified as * so we will not generate a description and defer to DOM part.
            // Otherwise, we could get a contradiction like "on day 1 of the month, every day"
            // or a dupe description like "every day, every day".
            $description = '';
        } else {
            $description = $this->getSegmentDescription(
                $this->expression['week'],
                $this->translate('ComaEveryDay'),
                function ($s) {
                    $exp = ($pos = strpos($s, '#')) !== false ? substr($s, 0, $pos)
                        : ((strpos($s, 'L') !== false) ? str_replace('L', '', $s) : $s);

                    if ($this->intl) {
                        $fmt = new IntlDateFormatter($this->language, IntlDateFormatter::FULL, IntlDateFormatter::FULL, null, IntlDateFormatter::GREGORIAN, 'EEEE');
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
                                $dayOfWeekOfMonthDescription = $this->translate("Fifth");
                                break;
                        }
                        $format = $this->translate('ComaOnThe') . $dayOfWeekOfMonthDescription . $this->translate('Space{0}OfTheMonth');
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

        return $description;
    }

    /**
     * @return string
     */
    public function getMonthDescription(): string
    {
        $description = $this->getSegmentDescription($this->expression['month'],
            '',
            function ($s) {
                $datetime = new DateTime("$s/01");
                if ($this->intl) {
                    $fmt = new IntlDateFormatter($this->language, IntlDateFormatter::FULL, IntlDateFormatter::FULL, null, IntlDateFormatter::GREGORIAN, 'LLLL');
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

        return $description;
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
                    $dayString = $dayNumber == 1 ? $this->translate('FirstWeekday') : $this->translate('WeekdayNearestDay{0}', ['0' => $dayNumber]);
                    $description = $this->translate('ComaOnThe{0}OfTheMonth', ['0' => $dayString]);
                    break;
                } else {
                    // Handle "last day offset" (i.e. L-5:  "5 days before the last day of the month")
                    if (preg_match('#L-(\\d{1,2})#', $expression, $match)) {
                        $offSetDays = $match[1];
                        $description = $this->translate('CommaDaysBeforeTheLastDayOfTheMonth', $offSetDays);
                        break;
                    } else {
                        $description = $this->getSegmentDescription($expression,
                            $this->translate('ComaEveryDay'),
                            function ($s) {
                                return $s;
                            },
                            function ($s) {
                                return $this->translate($s == '1' ? 'ComaEveryDay' : 'ComaEvery{0}Days');
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
                }
        }

        return $description;
    }

    /**
     * @return string
     */
    public function getYearDescription(): string
    {
        $description = $this->getSegmentDescription($this->expression['year'], '',
            function ($s) {
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

        return $description;
    }

    // Protected Methods
    // =========================================================================

    /**
     * @param string $expression
     * @param string $allDescription
     * @param callable $getSingleItemDescription
     * @param callable $getIntervalDescriptionFormat
     * @param callable $getBetweenDescriptionFormat
     * @param callable $getDescriptionFormat
     * @param callable $getRangeFormat
     * @return string|null
     */
    protected function getSegmentDescription(string $expression,
                                             string $allDescription,
                                             callable $getSingleItemDescription,
                                             callable $getIntervalDescriptionFormat,
                                             callable $getBetweenDescriptionFormat,
                                             callable $getDescriptionFormat,
                                             callable $getRangeFormat)
    {
        $description = null;
        if ($expression == '') {
            return '';
        }

        if ($expression == '*') {
            $description = $allDescription;
        } elseif (!StringHelper::contains($expression, ['/', '-', ','])) {
            $description = strtr($getDescriptionFormat($expression), ['{0}' => $getSingleItemDescription($expression)]);
        } elseif (StringHelper::contains($expression, '/')) {
            $segments = explode('/', $expression);
            $description = strtr($getIntervalDescriptionFormat($segments[1]), ['{0}' => $getSingleItemDescription($segments[1])]);

            //interval contains 'between' piece (i.e. 2-59/3 )
            if (strpos($segments[0], '-')) {
                $betweenSegmentDescription = $this->generateBetweenSegmentDescription($segments[0], $getBetweenDescriptionFormat, $getSingleItemDescription);

                if (strpos($betweenSegmentDescription, ',') !== 0) {
                    $description .= ', ';
                }

                $description .= $betweenSegmentDescription;
            } elseif (StringHelper::contains($segments[0], ['*', ',']) === false) {
                $rangeItemDescription = strtr($getDescriptionFormat($segments[0]), ['{0}' => $getSingleItemDescription($segments[0])]);
                //remove any leading comma
                $rangeItemDescription = str_replace(', ', '', $rangeItemDescription);

                $description .= $this->translate('CommaStarting{0}', ['0' => $rangeItemDescription]);
            }
        } elseif (StringHelper::contains($expression, ',')) {
            $segments = explode(',', $expression);

            $descriptionContent = '';

            for ($i = 0; $i < count($segments); $i++) {
                if ($i > 0 && count($segments) > 2) {
                    $descriptionContent .= ',';

                    if ($i < count($segments) - 1) {
                        $descriptionContent .= ' ';
                    }
                }

                if ($i > 0 && count($segments) > 1 && ($i == count($segments) - 1 || count($segments) == 2)) {
                    $descriptionContent .= $this->translate('SpaceAndSpace');
                }

                if (strpos($segments[$i], '-') !== false) {
                    $betweenSegmentDescription = $this->generateBetweenSegmentDescription($segments[$i], $getRangeFormat, $getSingleItemDescription);

                    //remove any leading comma
                    $betweenSegmentDescription = str_replace(', ', '', $betweenSegmentDescription);

                    $descriptionContent .= $betweenSegmentDescription;
                } else {
                    $descriptionContent .= $getSingleItemDescription($segments[$i]);
                }
            }

            $description = strtr($getDescriptionFormat($expression), ['{0}' => $descriptionContent]);
        } elseif (StringHelper::contains($expression, '-')) {
            $description = $this->generateBetweenSegmentDescription($expression, $getBetweenDescriptionFormat, $getSingleItemDescription);
        }

        return $description;
    }

    /**
     * @param string $betweenExpression
     * @param callable $getBetweenDescriptionFormat
     * @param callable $getSingleItemDescription
     * @return string
     */
    protected function generateBetweenSegmentDescription(string $betweenExpression, callable $getBetweenDescriptionFormat, callable $getSingleItemDescription): string
    {
        $description = '';
        $betweenSegments = explode('-', $betweenExpression);
        $betweenSegment1Description = $getSingleItemDescription($betweenSegments[0]);
        $betweenSegment2Description = $getSingleItemDescription($betweenSegments[1]);
        $betweenSegment2Description = str_replace(':00', ':59', $betweenSegment2Description);
        $betweenDescriptionFormat = $getBetweenDescriptionFormat($betweenExpression);
        $description .= strtr($betweenDescriptionFormat, ['{0}' => $betweenSegment1Description, '{1}' => $betweenSegment2Description]);

        return $description;
    }

    /**
     * @param string $hour
     * @param string $minute
     * @param string $second
     * @return string
     */
    protected function formatTime(string $hour, string $minute, string $second = ''): string
    {
        $period = '';

        if (!$this->use24HourTimeFormat) {
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

        if ($second != '') {
            $second = ':' . str_pad($second, 2, '0', STR_PAD_LEFT);
        }

        return sprintf('%s:%s%s%s', str_pad($hour, 2, '0', STR_PAD_LEFT), str_pad($minute, 2, '0', STR_PAD_LEFT), $second, $period);
    }

    /**
     * @param string $source
     * @param array $params
     * @param bool $forceTranslate
     * @return string|null
     */
    protected function translate(string $source, array $params = [], bool $forceTranslate = true)
    {
        if ($this->translator !== null) {
            return call_user_func($this->translator, $source, $params);
        }

        static $translations = [];
        if (!isset($translations[$this->language])) {
            $translations[$this->language] = require dirname(__DIR__) . '/languages/' . $this->language . '.php';
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
}