<?php
/**
 * Created by PhpStorm.
 * User: chendan
 * Date: 2018/6/16
 * Time: 09:40
 */

use Tony\Task\Timer;

class TimerTest extends \Codeception\Test\Unit
{
    /**@var Timer $timer */
    private $timer;

    public function _before()
    {
        $this->timer = new Timer();
    }

    public function testIsDue(): void
    {
        self::assertFalse($this->timer->everyFiveMinutes()->isDue());
        self::assertTrue($this->timer->everyMinute()->isDue());
    }

    public function testHourly(): void
    {
        self::assertEquals('* */1 * * *', $this->timer->hourly()->getExpression());
    }

    public function testEveryFifteenMinutes(): void
    {
        self::assertEquals('*/15 * * * *', $this->timer->everyFifteenMinutes()->getExpression());
    }

    public function testEveryTenMinutes(): void
    {
        self::assertEquals('*/10 * * * *', $this->timer->everyTenMinutes()->getExpression());
    }

    public function testHourlyAt(): void
    {

    }

    public function testEveryFiveMinutes(): void
    {
        self::assertEquals('*/5 * * * *', $this->timer->everyFiveMinutes()->getExpression());
    }

    public function testEveryThirtyMinutes(): void
    {
        self::assertEquals('*/30 * * * *', $this->timer->everyThirtyMinutes()->getExpression());
    }

    public function testEveryMinute(): void
    {
        self::assertEquals('*/1 * * * *', $this->timer->everyMinute()->getExpression());
    }

    public function testNextRunDate(): void
    {

    }
}
