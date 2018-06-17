<?php
/**
 * Created by PhpStorm.
 * User: chendan
 * Date: 2018/6/16
 * Time: 11:39
 */

namespace Tony\Task\Struct;

// 进程配置结构体
class ProcessConfig
{
    public $pidFile;
    public $stdIn;
    public $stdOut;
    public $stdErr;
}