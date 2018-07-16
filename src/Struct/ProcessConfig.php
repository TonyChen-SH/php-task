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

    public $enableMemoryProfile = false; // 开启/关闭内存分析
    public $memoryProfileFile   = '';    // 内存分析数据存储文件
}