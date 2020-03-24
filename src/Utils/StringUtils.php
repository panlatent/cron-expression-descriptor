<?php

namespace Panlatent\CronExpressionDescriptor\Utils;

trait StringUtils
{
    /**
     * @param string            $string
     * @param array|string|null $specialCharacters
     *
     * @return bool
     * @author Panlatent <panlatent@gmail.com>
     */
    public function stringContains(string $string, $specialCharacters): bool
    {
        if (is_array($specialCharacters)) {
            foreach ($specialCharacters as $character) {
                if (strpos($string, $character) !== false) {
                    return true;
                }
            }
        } else {
            return strpos($string, $specialCharacters) !== false;
        }

        return false;
    }
}
