<?php
declare(strict_types=1);

namespace Tony\Task;

use SplObjectStorage;
use Tony\Task\Struct\ProcessConfig;
use Tony\Task\Utils\MemoryProfiler;
use Tony\Task\Utils\ThrowableCatch;

class Runner extends Daemon
{
    /**@var SplObjectStorage $schedulers 任务调度器集合 */
    public $schedulers;

    /**@var ProcessConfig $processConfig 进程相关配置信息 */
    private $processConfig;

    public function __construct(ProcessConfig $processConfig)
    {
        $this->setProcessConfig($processConfig);
    }

    // 执行一次所有任务
    public function once(): void
    {
        foreach ($this->schedulers as $scheduler)
        {
            /** @var Scheduler $scheduler */
            $scheduler->notify();
        }
    }

    public function run(): void
    {
        global $argv, $argc;

        if (PHP_SAPI !== 'cli')
        {
            return;
        }

        if ($argc < 2)
        {
            Console::output("usage: {$argv[0]} start|stop|restart|status");
            return;
        }
        // 异常记录
        ThrowableCatch::getInstance()->registerExceptionHandler($this->processConfig);

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
            case 'profiler':
                MemoryProfiler::getInstance()->aggregateProfiler();
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
                return;
            }

            $schedules = $this->schedulers;
            MemoryProfiler::getInstance()->setStartTime();
            Daemon::work(['pid' => $this->processConfig->pidFile, 'stdout' => $this->processConfig->stdOut, 'stderr' => $this->processConfig->stdErr], function ($stdin, $stdout, $sterr) use ($schedules) {
                while (true)
                {
                    // do whatever it is daemons do
                    sleep(1); // sleep is good for you

                    MemoryProfiler::getInstance()->setRealTimeProfiler();

                    // 循环处理每个定时器
                    foreach ($schedules as $schedule)
                    {
                        /** @var Scheduler $schedule */
                        if (!$schedule->getTimer()->isDue()) continue;

                        $this->beforeExecuteJob();

                        // @TODO 任务长时间阻塞..会造成长时间资源阻塞么????
                        $schedule->notify();
                        $schedule->getTimer()->setExecTime(new \DateTime('now'));

                        $this->afterExecuteJob();
                        // 执行一次垃圾回收
                        //gc_collect_cycles();
                        //xdebug_start_gcstats();
                    }
                }
            }
            );
            Console::output('%g[OK]%n');
        } catch (\Exception $ex)
        {
            Console::output('%n');

            Console::error($ex->getMessage());
            throw  $ex;
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
                return;
            }

            Daemon::kill($this->processConfig->pidFile, true);
            MemoryProfiler::getInstance()->clear();
            Console::output('%g[OK]%n');
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
            $pidFile = $this->processConfig->pidFile;
            if (!Daemon::isRunning($pidFile))
            {
                Console::output('%y[Daemon not running]%n');
                return;
            }

            MemoryProfiler::getInstance()->showRealTimeProfiler();
        } catch (\Exception $ex)
        {
            Console::output('%n');
            Console::output($ex->getMessage());
            Console::output($ex->getTraceAsString());
        }
    }

    public function getProcessConfig(): ProcessConfig
    {
        return $this->processConfig;
    }

    public function setProcessConfig(ProcessConfig $processConfig): void
    {
        $this->processConfig = $processConfig;
        MemoryProfiler::getInstance()->setProcessConfig($processConfig);
    }

    public function setSchedulers(\SplObjectStorage $schedulers): void
    {
        $this->schedulers = $schedulers;
    }

    private function beforeExecuteJob(): void
    {
        // $info['execute_time']        = time();
        // $info['before_memory_usage'] = memory_get_usage();
        // $info['after_memory_usage'] = memory_get_usage();
        // $info['max_memory_usage']   = memory_get_peak_usage();
        //
        // $key = 'memory_profiler' . $info['execute_time'];
        // $settingStore->set($key, $info);
        // $settingStore->set('memory_profiler', $info);
    }

    private function afterExecuteJob(): void
    {
        // $info['execute_time']        = time();
        // $info['before_memory_usage'] = memory_get_usage();
        // $info['after_memory_usage'] = memory_get_usage();
        // $info['max_memory_usage']   = memory_get_peak_usage();
        //
        // $key = 'memory_profiler' . $info['execute_time'];
        // $settingStore->set($key, $info);
        // $settingStore->set('memory_profiler', $info);
    }
}