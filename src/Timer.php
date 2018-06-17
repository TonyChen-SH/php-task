<?php
declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: chendan
 * Date: 2018/6/15
 * Time: 22:47
 */

namespace Tony\Task;

use Cron\CronExpression;

class Timer
{
    // @ 每天几点
    // 表达式
    /**
     *   *    *    *    *    *
     *   -    -    -    -    -
     *   |    |    |    |    |
     *   |    |    |    |    |
     *   |    |    |    |    +----- day of week (0 - 7) (Sunday=0 or 7)
     *   |    |    |    +---------- month (1 - 12)
     *   |    |    +--------------- day of month (1 - 31)
     *   |    +-------------------- hour (0 - 23)
     *   +------------------------- min (0 - 59)
     */
    private $expression = '* * * * *';

    /**@var \DateTime $execTime 记录调度器执行时间 */
    private $execTime;

    public function __construct()
    {
        $this->setTimezone();

        $defaultExecTime = new \DateTime('2012-10-10 00:00:00', new \DateTimeZone(date_default_timezone_get()));
        $this->setExecTime($defaultExecTime);
    }

    public function everyMinute(): self
    {
        return $this->spliceIntoPosition(1, '*/1');
    }

    public function everyFiveMinutes(): self
    {
        return $this->spliceIntoPosition(1, '*/5');
    }

    public function everyTenMinutes(): self
    {
        return $this->spliceIntoPosition(1, '*/10');
    }

    public function everyFifteenMinutes(): self
    {
        return $this->spliceIntoPosition(1, '*/15');
    }

    public function everyThirtyMinutes(): self
    {
        return $this->spliceIntoPosition(1, '*/30');
    }

    public function hourly(): self
    {
        return $this->spliceIntoPosition(2, '*/1');
    }

    public function hourlyAt($offset): void
    {
    }

    /**
     * @return \DateTime
     * @throws \RuntimeException
     */
    public function nextRunDate(): \DateTime
    {
        return CronExpression::factory($this->getExpression())->getNextRunDate();
    }

    /**
     * 是否满足时间条件
     * @throws \RuntimeException
     */
    public function isDue(): bool
    {
        // 由于isDue是按分钟算的，这样就会导致60秒≤内都会满足执行条件.
        // 所有触发条件是当前时间满足，且执行时间不满足才行
        $nextRunTimestamp = CronExpression::factory($this->expression)->getNextRunDate('now', 0, true)->getTimestamp();
        $execTime         = \DateTime::createFromFormat('Y-m-d H:i', $this->getExecTime()->format('Y-m-d H:i'));

        if ($nextRunTimestamp === $execTime->getTimestamp())
        {
            return false;
        }

        // 当前时间是不是满足触发时间
        return CronExpression::factory($this->expression)->isDue();
    }

    protected function spliceIntoPosition(int $position, string $value): self
    {
        $segments = explode(' ', $this->expression);

        $segments[$position - 1] = $value;

        $this->setExpression(implode(' ', $segments));
        return $this;
    }

    public function getExpression(): string
    {
        return $this->expression;
    }

    public function setExpression(string $expression): void
    {
        $this->expression = $expression;
    }

    public function getExecTime(): \DateTime
    {
        return $this->execTime;
    }

    public function setExecTime(\DateTime $execTime): void
    {
        $this->execTime = $execTime;
    }

    private function setTimezone(): void
    {
        // 计算服务器当前时区
        $timezone = $this->calcServerTimezone();
        date_default_timezone_set($timezone);
    }

    // 计算时区名称，如： Asia/Shanghai
    private function calcServerTimezone(): string
    {
        $curTimezone = date_default_timezone_get();
        if ($curTimezone !== 'UTC')
        {
            return $curTimezone;
        }

        $utcDateTime      = new \DateTime('now');
        $curLinuxDateTime = new \DateTime(shell_exec('date "+%Y-%m-%d %H:%M:%S"'));

        $offset = ($curLinuxDateTime->getTimestamp() - $utcDateTime->getTimestamp());

        return timezone_name_from_abbr('', $offset, 0);
    }
}