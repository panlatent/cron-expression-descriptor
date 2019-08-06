<?php
/**
 * Cron Expression Descriptor
 *
 * @link      https://github.com/panlatent/cron-expression-descriptor
 * @copyright Copyright (c) 2019 panlatent@gmail.com
 */

namespace Panlatent\CronExpressionDescriptor;

/**
 * Class StringHelper
 *
 * @package Panlatent\CronExpressionDescriptor
 * @author Panlatent <panlatent@gmail.com>
 */
class StringHelper
{
    // Static Methods
    // =========================================================================

    /**
     * @param string $string
     * @param array|string|null $specialCharacters
     * @return bool
     */
    public static function contains(string $string, $specialCharacters): bool
    {
        if (is_array($specialCharacters)) {
            foreach ($specialCharacters as $character) {
                if (($pos = strpos($string, $character)) !== false) {
                    return true;
                }
            }
        } else {
            return strpos($string, $specialCharacters) !== false;
        }

        return false;
    }
}