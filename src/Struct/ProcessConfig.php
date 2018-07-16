<?php
declare(strict_types=1);

namespace Tony\Task\Struct;

// 进程配置结构体
class ProcessConfig
{
    public $pidFile;
    public $stdIn;
    public $stdOut;
    public $stdErr;

    public $enableMemoryProfiler   = false; // 开启/关闭内存分析
    public $memoryProfilerDumpFile = '';    // 内存分析数据存储文件

    public $logPath;          // 异常日志记录目录
    public $logFileName;      // 异常日志记录文件

}