<?php
declare(strict_types=1);

namespace Tony\Task;

use SplObjectStorage;
use Tony\Task\Struct\ProcessConfig;

class Runner extends Daemon
{
    /**@var SplObjectStorage $schedulers 任务调度器集合 */
    public $schedulers;

    /**@var ProcessConfig $processConfig 进程相关配置信息 */
    private $processConfig;

    public function setSchedulers(\SplObjectStorage $schedulers): void
    {
        $this->schedulers = $schedulers;
    }

    // 执行一次所有任务
    public function once(): void
    {
        $this->enableMemoryProfiler();
        foreach ($this->schedulers as $scheduler)
        {
            /** @var Scheduler $scheduler */
            $scheduler->notify();
        }
        $this->memoryProfilerDump();
    }

    public function getProcessConfig(): ProcessConfig
    {
        return $this->processConfig;
    }

    public function setProcessConfig(ProcessConfig $processConfig): void
    {
        $this->processConfig = $processConfig;
    }

    public function run(): void
    {
        global $argv, $argc;

        if (PHP_SAPI !== 'cli')
        {
            return;
        }

        if ($argc !== 2)
        {
            Console::output("usage: {$argv[0]} start|stop|restart|status");
            return;
        }

        switch ($argv[1])
        {
            case 'start':
                $this->start();
                break;
            case 'stop':
                $this->stop();
                break;
            case 'restart':
                $this->restart();
                break;
            case 'status':
                $this->status();
                break;
            case 'once':
                $this->once();
                break;
            default:
                Console::output('Unknown action.');
                break;
        }
    }

    protected function start(): void
    {
        Console::stdout('Starting...');
        try
        {
            if (Daemon::isRunning($this->processConfig->pidFile))
            {
                Console::output('%y[Already Running]%n');
            } else
            {
                $schedules = $this->schedulers;
                Daemon::work(['pid' => $this->processConfig->pidFile, 'stdout' => $this->processConfig->stdOut, 'stderr' => $this->processConfig->stdErr], function ($stdin, $stdout, $sterr) use ($schedules) {
                    $this->enableMemoryProfiler();

                    while (true)
                    {
                        // do whatever it is daemons do
                        sleep(1); // sleep is good for you

                        // 循环处理每个定时器
                        foreach ($schedules as $schedule)
                        {
                            /** @var Scheduler $schedule */
                            if (!$schedule->getTimer()->isDue()) continue;

                            // @TODO 任务长时间阻塞..会造成长时间资源阻塞么????
                            $schedule->notify();
                            $schedule->getTimer()->setExecTime(new \DateTime('now'));
                            // 执行一次垃圾回收
                            //gc_collect_cycles();
                            //xdebug_start_gcstats();
                        }
                    }

                    $this->memoryProfilerDump();
                }
                );
                Console::output('%g[OK]%n');
            }
        } catch (\Exception $ex)
        {
            Console::output('%n');
            Console::error($ex->getMessage());
            Console::output($ex->getTraceAsString());
        }
    }

    protected function stop(): void
    {
        Console::stdout('Stopping... ');
        try
        {
            if (!Daemon::isRunning($this->processConfig->pidFile))
            {
                Console::output('%y[Daemon not running]%n');
            } else
            {
                Daemon::kill($this->processConfig->pidFile, true);
                Console::output('%g[OK]%n');
            }
        } catch (\Exception $ex)
        {
            Console::output('%n');
            Console::output($ex->getMessage());
            Console::output($ex->getTraceAsString());
        }
    }

    protected function restart(): void
    {
        $this->stop();
        $this->start();
    }

    protected function status(): void
    {
        try
        {
            if (!Daemon::isRunning($this->processConfig->pidFile))
            {
                Console::output('%y[Daemon not running]%n');
                return;
            }
            Console::output('%g[Daemon is running]%n');
        } catch (\Exception $ex)
        {
            Console::output('%n');
            Console::output($ex->getMessage());
            Console::output($ex->getTraceAsString());
        }
    }

    // 检测并开启内存分析
    protected function enableMemoryProfiler(): void
    {
        // 不开启分析
        if ($this->processConfig->enableMemoryProfiler !== true)
        {
            return;
        }

        // 没有加载分析模块
        if ($this->processConfig->enableMemoryProfiler === true && !\extension_loaded('memprof'))
        {
            throw  new \RuntimeException("memprof extension not load. can't enable memory profiler");
        }

        // 开始分析
        memprof_enable();

    }

    // 输入分析数据
    protected function memoryProfilerDump()
    {
        if ($this->processConfig->enableMemoryProfiler === true && extension_loaded('memprof') === true)
        {
            memprof_dump_callgrind(fopen($this->processConfig->memoryProfilerDumpFile, 'wb'));
        }
    }
}