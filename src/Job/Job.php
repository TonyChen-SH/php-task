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
}