<?php
declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: chendan
 * Date: 2018/6/16
 * Time: 16:23
 */

namespace Tony\Task\Job;

// 带优先级的任务类
use SplSubject;
use Tony\Task\Scheduler;

abstract class Job implements \SplObserver
{
    // 优先级属性
    protected $priority = 0;

    public function __construct(int $priority = 0)
    {
        $this->priority = $priority;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    /**
     * @param $subject
     * @throws \RuntimeException
     */
    public function update(SplSubject $subject): void
    {
        if (!$subject instanceof Scheduler)
        {
            throw new \RuntimeException('must instance of Scheduler');
        }

        $this->execute($subject);
    }

    abstract public function execute(Scheduler $subject): void;
}