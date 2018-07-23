<?php
declare(strict_types=1);

namespace Tony\Task\Utils;

use Flintstone\Flintstone;
use Tony\Task\Console;
use Tony\Task\Struct\ProcessConfig;

class MemoryProfiler
{
    /**@var ProcessConfig $processConfig 进程相关配置信息 */
    private $processConfig;

    // 秒数计数
    private $secondCount;

    use SingletonImpl;


    public function aggregateProfiler(): void
    {
        // @todo 1.统计一个平均值 2.找出最大内存记录和最小内存记录
    }

    public function setRealTimeProfiler(): void
    {
        if ($this->processConfig->enableMemoryProfiler !== true)
        {
            return;
        }

        $this->secondCount++;
        if ($this->secondCount >= 10)
        {
            $info['execute_time']        = time();
            $info['before_memory_usage'] = memory_get_usage();
            $info['after_memory_usage']  = memory_get_usage();
            $info['max_memory_usage']    = memory_get_peak_usage();

            $this->getFlintStone('php-task')->set('memory_profiler', $info);
            $this->secondCount = 0;
        }
    }

    public function showRealTimeProfiler(): void
    {
        $pid = file_get_contents($this->processConfig->pidFile);

        $settingStore = $this->getFlintStone('php-task');
        $endTime      = new \DateTime('now');
        $startTime    = new \DateTime($this->getFlintStone('php-task')->get('start_time'));
        $uptime       = $endTime->diff($startTime)->format('%dd:%hh:%im:%ss');

        $memoryProfiler = $settingStore->get('memory_profiler');
        $memUsage       = 0;
        $memMax         = 0;

        if ($memoryProfiler !== false)
        {
            $memUsage = Tool::getInstance()->sizeConvert($memoryProfiler['after_memory_usage']);
            $memMax   = Tool::getInstance()->sizeConvert($memoryProfiler['max_memory_usage']);
        }

        Console::output('%P[Daemon is running]%n');
        Console::output('%g================================================================%n');
        Console::output("%g pid\t\tmemory\t\tmax memory\tuptime %n");
        Console::output("%g {$pid}\t\t{$memUsage}\t\t{$memMax}\t\t{$uptime} %n");
        Console::output('%g================================================================%n');
    }

    public function clear(): void
    {
        $this->getFlintStone('php-task')->flush();
    }

    public function setStartTime(): void
    {
        $this->getFlintStone('php-task')->set('start_time', date('Y-m-d H:i:s'));
    }

    public function setProcessConfig(ProcessConfig $processConfig): void
    {
        $this->processConfig = $processConfig;
    }


    protected function getFlintStone($database): Flintstone
    {
        $options = ['dir' => $this->processConfig->logPath];
        return new Flintstone($database, $options);
    }
}