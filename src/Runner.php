<?php
declare(strict_types=1);

namespace Tony\Task;

use Flintstone\Flintstone;
use SplObjectStorage;
use Tony\Task\Struct\ProcessConfig;
use Tony\Task\Utils\MemoryProfiler;
use Tony\Task\Utils\ThrowableCatch;
use Tony\Task\Utils\Tool;

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

        if ($argc < 2)
        {
            Console::output("usage: {$argv[0]} start|stop|restart|status");
            return;
        }
        // 开启内存分析
        $this->enableMemoryProfiler();
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
                $this->profiler();
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

            $schedules    = $this->schedulers;
            $settingStore = $this->getFlintStone('php-task');
            $settingStore->set('start_time', date('Y-m-d H:i:s'));
            Daemon::work(['pid' => $this->processConfig->pidFile, 'stdout' => $this->processConfig->stdOut, 'stderr' => $this->processConfig->stdErr], function ($stdin, $stdout, $sterr) use ($schedules, $settingStore) {
                while (true)
                {
                    // do whatever it is daemons do
                    sleep(1); // sleep is good for you

                    // 循环处理每个定时器
                    foreach ($schedules as $schedule)
                    {
                        /** @var Scheduler $schedule */
                        if (!$schedule->getTimer()->isDue()) continue;

                        $info['execute_time']        = time();
                        $info['before_memory_usage'] = memory_get_usage();

                        // @TODO 任务长时间阻塞..会造成长时间资源阻塞么????
                        $schedule->notify();
                        $schedule->getTimer()->setExecTime(new \DateTime('now'));

                        $info['after_memory_usage'] = memory_get_usage();
                        $info['max_memory_usage']   = memory_get_peak_usage();

                        $key = 'memory_profiler' . $info['execute_time'];
                        $settingStore->set($key, $info);
                        $settingStore->set('memory_profiler', $info);
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
            $this->getFlintStone('php-task')->flush();
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

            $this->showProfiler($pidFile);
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
        // 由于ini_set设置xdebug配置参数无效，所以，暂时这里不做任何处理
        return;

        // 不开启分析
        if ($this->processConfig->enableMemoryProfiler !== true)
        {
            return;
        }

        // 没有加载分析模块
        if (\extension_loaded('xdebug') !== true)
        {
            throw  new \RuntimeException("xdebug extension not load. can't enable memory profiler");
        }

        // 开始分析
        MemoryProfiler::getInstance()->enable();
    }

    public function getFlintStone($database): Flintstone
    {
        $options = ['dir' => $this->processConfig->logPath];
        return new Flintstone($database, $options);
    }

    /**
     * @param $pidFile
     */
    protected function showProfiler($pidFile): void
    {
        $pid = file_get_contents($pidFile);

        $settingStore   = $this->getFlintStone('php-task');
        $endTime        = new \DateTime('now');
        $startTime      = new \DateTime($this->getFlintStone('php-task')->get('start_time'));
        $uptime         = $endTime->diff($startTime)->format('%dd:%hh:%im:%ss');
        $memoryProfiler = $settingStore->get('memory_profiler');
        $memUsage       = Tool::getInstance()->sizeConvert($memoryProfiler['after_memory_usage']);
        $memMax         = Tool::getInstance()->sizeConvert($memoryProfiler['max_memory_usage']);

        Console::output('%P[Daemon is running]%n');
        Console::output('%g================================================================%n');
        Console::output("%g pid\t\tmemory\t\tmax memory\tuptime %n");
        Console::output("%g {$pid}\t\t{$memUsage}\t\t{$memMax}\t\t{$uptime} %n");
        Console::output('%g================================================================%n');
    }

    private function profiler(): void
    {
        // @todo 1.统计一个平均值 2.找出最大内存记录和最小内存记录
    }
}