<?php
/**
 * Cron Expression Descriptor
 *
 * @link      https://github.com/panlatent/cron-expression-descriptor
 * @copyright Copyright (c) 2019 panlatent@gmail.com
 */

use Panlatent\CronExpressionDescriptor\Exceptions\ExpressionException;
use Panlatent\CronExpressionDescriptor\ExpressionDescriptor;
use PHPUnit\Framework\TestCase;

class ExpressionDescriptorTest extends TestCase
{
    /**
     * @throws ExpressionException
     */
    public function testGetDescription(): void
    {
        $rows = [
            '0,15,30,45 10,23 * 1,2,3,4,5,6,7,8,9,10,11,12 1,2,3,4,5,6,0' => 'Every 15 minutes, at 10:00 AM and 11:00 PM',

            '* * * * *' => 'Every minute',
            '*/1 * * * *' => 'Every minute',
            '0 0/1 * * * ?' => 'Every minute',
            '0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31,32,33,34,35,36,37,38,39,40,41,42,43,44,45,46,47,48,49,50,51,52,53,54,55,56,57,58,59 * * * *' => 'Every minute',

            '0 * * * *' => 'Every hour',
            '0 0 * * * ?' => 'Every hour',
            '0 0 0/1 * * ?' => 'Every hour',
            '0 0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23 * * *' => 'Every hour',

            '0 23 ? * MON-FRI' => 'At 11:00 PM, Monday through Friday',

            '* * * * * *' => 'Every second',
            '*/45 * * * * *' => 'Every 45 seconds',
            '*/5 * * * *' => 'Every 5 minutes',
            '0 0/10 * * * ?' => 'Every 10 minutes',
            '0 */5 * * * *' => 'Every 5 minutes',
            '30 11 * * 1-5' => 'At 11:30 AM, Monday through Friday',

            '30 11 * * *' => 'At 11:30 AM',
            '30 11 1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31 1,2,3,4,5,6,7,8,9,10,11,12 1,2,3,4,5,6,0' => 'At 11:30 AM',

            '0-10 11 * * *' => 'Every minute between 11:00 AM and 11:10 AM',
            '* * * 3 *' => 'Every minute, only in March',
            '* * * 3,6 *' => 'Every minute, only in March and June',
            '30 14,16 * * *' => 'At 02:30 PM and 04:30 PM',
            '30 6,14,16 * * *' => 'At 06:30 AM, 02:30 PM and 04:30 PM',
            '46 9 * * 1' => 'At 09:46 AM, only on Monday',
            '23 12 15 * *' => 'At 12:23 PM, on day 15 of the month',
            '23 12 * JAN *' => 'At 12:23 PM, only in January',
            '23 12 ? JAN *' => 'At 12:23 PM, only in January',
            '23 12 * JAN-FEB *' => 'At 12:23 PM, January through February',
            '23 12 * JAN-MAR *' => 'At 12:23 PM, January through March',
            '23 12 * * SUN' => 'At 12:23 PM, only on Sunday',

            '*/5 15 * * MON-FRI' => 'Every 5 minutes, between 03:00 PM and 03:59 PM, Monday through Friday',
            '0,5,10,15,20,25,30,35,40,45,50,55 15 * * MON-FRI' => 'Every 5 minutes, between 03:00 PM and 03:59 PM, Monday through Friday',

            '* * * * MON#3' => 'Every minute, on the third Monday of the month',
            '* * * * 4L' => 'Every minute, on the last Thursday of the month',
            '*/5 * L JAN *' => 'Every 5 minutes, on the last day of the month, only in January',
            '30 02 14 * * *' => 'At 02:02:30 PM',
            '5-10 * * * * *' => 'Seconds 5 through 10 past the minute',
            '5-10 30-35 10-12 * * *' => 'Seconds 5 through 10 past the minute, minutes 30 through 35 past the hour, between 10:00 AM and 12:59 PM',
            '30 */5 * * * *' => 'At 30 seconds past the minute, every 5 minutes',
            '0 30 10-13 ? * WED,FRI' => 'At 30 minutes past the hour, between 10:00 AM and 01:59 PM, only on Wednesday and Friday',
            '10 0/5 * * * ?' => 'At 10 seconds past the minute, every 5 minutes',
            '2-59/3 1,9,22 11-26 1-6 ?' => 'Every 3 minutes, minutes 2 through 59 past the hour, at 01:00 AM, 09:00 AM, and 10:00 PM, between day 11 and 26 of the month, January through June',
            '0 0 6 1/1 * ?' => 'At 06:00 AM',
            '0 5 0/1 * * ?' => 'At 5 minutes past the hour',
            '* * * * * * 2013' => 'Every second, only in 2013',
            '* * * * * 2013' => 'Every minute, only in 2013',
            '* * * * * 2013,2014' => 'Every minute, only in 2013 and 2014',
            '23 12 * JAN-FEB * 2013-2014' => 'At 12:23 PM, January through February, 2013 through 2014',
            '23 12 * JAN-MAR * 2013-2015' => 'At 12:23 PM, January through March, 2013 through 2015',
        ];

        foreach ($rows as $expr => $expected) {
            $this->assertEquals($expected, (new ExpressionDescriptor($expr, 'en_US'))->getDescription());
        }
    }

    public function testLocaleFormats(): void
    {
        $this->assertEquals('Every minute',
            (new ExpressionDescriptor('* * * * *', 'en'))->getDescription());

        $this->assertEquals('Every minute',
            (new ExpressionDescriptor('* * * * *', 'en_US'))->getDescription());

        $this->assertEquals('Every minute',
            (new ExpressionDescriptor('* * * * *', 'en-US'))->getDescription());
    }

    public function test24HoursFormat(): void
    {
        $this->assertEquals('Every 15 minutes, at 10:00 AM and 11:00 PM',
            (new ExpressionDescriptor('0,15,30,45 10,23 * 1,2,3,4,5,6,7,8,9,10,11,12 1,2,3,4,5,6,0'))
                ->getDescription());

        $this->assertEquals('Every 15 minutes, at 10:00 and 23:00',
            (new ExpressionDescriptor('0,15,30,45 10,23 * 1,2,3,4,5,6,7,8,9,10,11,12 1,2,3,4,5,6,0',
                'en_US', true))->getDescription());
    }
}
