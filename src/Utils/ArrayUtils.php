<?php

namespace Panlatent\CronExpressionDescriptor\Utils;

use Panlatent\CronExpressionDescriptor\Enums\CronTimeUnitsEnum;

trait ArrayUtils
{
    /**
     * Detect interval, if a time unit represents as "0,15,30,45" string instead of "0/15" (example for hour).
     *
     * @param CronTimeUnitsEnum $_time_unit
     * @param string            $_expression_part
     *
     * @return int|null
     */
    protected function detectIntervalUnit(CronTimeUnitsEnum $_time_unit, string $_expression_part): ?int
    {
        $expressionParts = \explode(',', $_expression_part);
        $size = count($expressionParts);
        if ($size < 2) {
            return null;
        }

        $timeUnitMaxValue = $_time_unit->getTotalValue();
        $intervalUnit = $timeUnitMaxValue / $size;
        if (!is_int($intervalUnit)) {
            return null;
        }

        // Generate a test range for this n-unit for the given time-unit.
        $testRange = $this->createWeekRange(0, $size, $intervalUnit);

        /** @noinspection TypeUnsafeComparisonInspection */
        if ($expressionParts == $testRange) {
            return $intervalUnit;
        }

        return null;
    }


    /**
     * Create a range.
     * No, we can't use range().
     *
     * @param int $_min_value
     * @param int $_max_elements_count
     * @param int $_step
     *
     * @return array
     */
    protected function createWeekRange(int $_min_value, int $_max_elements_count, int $_step): array
    {
        $result = [];
        $currentValue = $_min_value;
        for ($i = 0; $i < $_max_elements_count; $i++) {
            $result[] = $currentValue;
            $currentValue += $_step;
        }

        return $result;
    }

    /**
     * @param array $_array
     * @param array $_values
     *
     * @return bool
     */
    protected function isContainsAllValues(array $_array, array $_values): bool
    {
        return !array_diff($_values, $_array);
    }
}
