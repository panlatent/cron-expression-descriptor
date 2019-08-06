<?php
/**
 * Cron Expression Descriptor
 *
 * @link      https://github.com/panlatent/cron-expression-descriptor
 * @copyright Copyright (c) 2019 panlatent@gmail.com
 */

use Panlatent\CronExpressionDescriptor\ExpressionParser;
use PHPUnit\Framework\TestCase;

class ExpressionParserTest extends TestCase
{
    public function testParse()
    {
        $rows = [
            '* * * * MON#3' => ['', '*', '*', '*', '*', '1#3', ''],
            '5-10 * * * * *' => ['5-10', '*', '*', '*', '*', '*', ''],
        ];

        foreach ($rows as $expr => $expected) {
            $ret = (new ExpressionParser($expr))->parse();
            $this->assertEquals($expected, $ret);
        }
    }
}
