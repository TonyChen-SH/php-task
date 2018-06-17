<?php
declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: chendan
 * Date: 2018/6/16
 * Time: 16:08
 */

namespace Tony\Task;

use Tony\Task\Job\Job;

class Scheduler implements \SplSubject
{
    // 执行的脚本
    private $jobs = [];
    // 执行脚本的优先级
    private $sorts = [];
    /**@var Timer $timer 时间管理器 */
    private $timer;


    public function __construct()
    {
        $this->timer = new Timer();
    }

    public function attach(\SplObserver $job): void
    {
        /**@var Job $job */
        $jobHashKey = spl_object_hash($job);

        $this->jobs[$jobHashKey]  = $job;
        $this->sorts[$jobHashKey] = $job->getPriority();
        arsort($this->sorts);
    }

    public function detach(\SplObserver $job): void
    {
        $jobHashKey = spl_object_hash($job);
        unset($this->jobs[$jobHashKey], $this->sorts[$jobHashKey]);
    }

    public function notify(): void
    {
        foreach ($this->sorts as $jobHashKey => $priorityVal)
        {
            $this->jobs[$jobHashKey]->update($this);
        }
    }

    public function getTimer(): Timer
    {
        return $this->timer;
    }
}