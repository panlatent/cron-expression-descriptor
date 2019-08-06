<?php
/**
 * Cron Expression Descriptor
 *
 * @link      https://github.com/panlatent/cron-expression-descriptor
 * @copyright Copyright (c) 2019 panlatent@gmail.com
 */

namespace Panlatent\CronExpressionDescriptor;

use DateTime;

/**
 * Class ExpressionParser
 *
 * @package Panlatent\CronExpressionDescriptor
 * @author Panlatent <panlatent@gmail.com>
 */
class ExpressionParser
{
    // Properties
    // =========================================================================

    /**
     * @var string
     */
    protected $expression;

    /**
     * @var bool
     */
    protected $dayOfWeekStartIndexZero = true;

    // Public Methods
    // =========================================================================

    /**
     * ExpressionParser constructor.
     *
     * @param string $expression
     * @param array $options
     */
    public function __construct(string $expression, array $options = [])
    {
        $this->expression = $expression;

        foreach ($options as $property => $value) {
            $this->$property = $value;
        }
    }

    /**
     * @return array
     * @throws ExpressionException
     */
    public function parse(): array
    {
        if ($this->expression == '') {
            throw new ExpressionException('Invalid cron expression');
        }

        $expressionParts = explode(' ', $this->expression);
        $count = count($expressionParts);

        if ($count < 5) {
            throw new ExpressionException("Expression only has {$count} parts.  At least 5 part are required.");
        } elseif ($count == 5) {
            // 5 part cron so shift array past seconds element
            $parsed = array_slice($expressionParts, 0, 5);
            array_unshift($parsed, '');
            array_push($parsed, '');
        } elseif ($count == 6) {
            // If last element ends with 4 digits, a year element has been supplied and no seconds element
            if (preg_match('#\d{4}$#', $expressionParts[5])) {
                $parsed = array_slice($expressionParts, 0, 6);
                array_unshift($parsed, '');
            } else {
                $parsed = $expressionParts;
                array_push($parsed, '');
            }
        } elseif ($count == 7) {
            $parsed = $expressionParts;
        } else {
            throw new ExpressionException("Expression has too many parts ({$count}).  Expression must not have more than 7 parts.");
        }

        $this->normalizeExpression($parsed);

        return $parsed;
    }

    // Protected Methods
    // =========================================================================

    /**
     * @param array $expressionParts
     */
    protected function normalizeExpression(array &$expressionParts)
    {
        // Convert ? to * only for DOM and DOW
        $expressionParts[3] = str_replace('?', '*', $expressionParts[3]);
        $expressionParts[5] = str_replace('?', '*', $expressionParts[5]);

        // Convert 0/, 1/ to */
        for ($i = 0; $i < 3; ++$i) {
            if (strncmp($expressionParts[$i], "0/", 2) === 0) {
                $expressionParts[$i] = str_replace('0/', '*/', $expressionParts[$i]);
            }
        }

        for ($i = 3; $i < 7; ++$i) {
            if (strncmp($expressionParts[$i], "1/", 2) === 0) {
                $expressionParts[$i] = str_replace('1/', '*/', $expressionParts[$i]);
            }
        }

        // Adjust DOW based on dayOfWeekStartIndexZero option
        $expressionParts[5] = preg_replace_callback('?(^\d)|([^#/\s]\d)+?', function ($m) use ($expressionParts) {
            $dowDigits = preg_replace('#\D#', '', $m[0]); // extract digit part (i.e. if "-2" or ",2", just take 2)
            $dowDigitsAdjusted = $dowDigits;

            if ($this->dayOfWeekStartIndexZero) {
                // "7" also means Sunday so we will convert to "0" to normalize it
                if ($dowDigits == '7') {
                    $dowDigitsAdjusted = '0';
                }
            } else {
                // If dayOfWeekStartIndexZero==false, Sunday is specified as 1 and Saturday is specified as 7.
                // To normalize, we will shift the  DOW number down so that 1 becomes 0, 2 becomes 1, and so on.
                $dowDigitsAdjusted = $dowDigits - 1;
            }

            return str_replace($dowDigits, $dowDigitsAdjusted, $m[0]);
        }, $expressionParts[5]);

        // Convert DOM '?' to '*'
        if ($expressionParts[3] == '?') {
            $expressionParts[3] = '*';
        }

        // Convert SUN-SAT format to 0-6 format
        foreach (['SUN', 'MON', 'TUE', 'WED', 'THU', 'FRI', 'SAT'] as $i => $currentDayOfWeekDescription) {
            $expressionParts[5] = preg_replace("#{$currentDayOfWeekDescription}#i", $i, $expressionParts[5]);
        }

        // Convert JAN-DEC format to 1-12 format
        for ($i = 1; $i <= 12; $i++) {
            $currentMonthDescription = strtoupper((new DateTime())->setDate(date('Y'), $i, 1)->format('M'));
            $expressionParts[4] = preg_replace("#{$currentMonthDescription}#i", $i, $expressionParts[4]);
        }

        // Convert 0 second to (empty)
        if ($expressionParts[0] == '0') {
            $expressionParts[0] = '';
        }

        // If time interval is specified for seconds or minutes and next time part is single item, make it a "self-range" so
        // the expression can be interpreted as an interval 'between' range.
        //     For example:
        //     0-20/3 9 * * * => 0-20/3 9-9 * * * (9 => 9-9)
        //     */5 3 * * * => */5 3-3 * * * (3 => 3-3)
        if (!StringHelper::contains($expressionParts[2], ['*', '-', ',', '/']) &&
            (preg_match('#[*/]#', $expressionParts[1]) || preg_match('#[*/]#', $expressionParts[0]))) {
            $expressionParts[2] .= "-{$expressionParts[2]}";
        }

        // Loop through all parts and apply global normalization
        for ($i = 0; $i < count($expressionParts); ++$i) {
            // convert all '*/1' to '*'
            if ($expressionParts[$i] == '*/1') {
                $expressionParts[$i] = '*';
            }

            /* Convert Month,DOW,Year step values with a starting value (i.e. not '*') to between expressions.
               This allows us to reuse the between expression handling for step values.
               For Example:
                - month part '3/2' will be converted to '3-12/2' (every 2 months between March and December)
                - DOW part '3/2' will be converted to '3-6/2' (every 2 days between Tuesday and Saturday)
            */

            if (StringHelper::contains($expressionParts[$i], "/") &&
                !StringHelper::contains($expressionParts[$i], ['*', '-', ','])) {
                $stepRangeThrough = null;
                switch ($i) {
                    case 4:
                        $stepRangeThrough = '12';
                        break;
                    case 5:
                        $stepRangeThrough = '6';
                        break;
                    case 6:
                        $stepRangeThrough = '9999';
                        break;
                    default:
                        $stepRangeThrough = null;
                        break;
                }

                if ($stepRangeThrough != null) {
                    $parts = explode('/', $expressionParts[$i]);
                    $expressionParts[$i] = sprintf("{%s}-{%s}/{%s}", $parts[0], $stepRangeThrough, $parts[1]);
                }
            }
        }
    }
}