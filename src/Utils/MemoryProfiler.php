<?php
declare(strict_types=1);

namespace Tony\Task\Utils;

// 配置xdebug 内存分析
class MemoryProfiler
{
    use SingletonImpl;

    public function enable(): void
    {
        $this->trigger()
            ->setOutputName()
            ->aggregate();

        // $iniSetList = ini_get_all('xdebug');
        // $a          = $iniSetList;
    }

    public function aggregate(): MemoryProfiler
    {
        ini_set('xdebug.profiler_aggregate', '1');
        return $this;
    }

    public function trigger(string $value = 'task'): MemoryProfiler
    {
        ini_set('xdebug.profiler_enable_trigger', '1');
        ini_set('xdebug.profiler_enable_trigger_value', $value);
        return $this;
    }

    // 设置输出格式
    // 参考https://xdebug.org/docs/all_settings#trace_output_name
    public function setOutputName(string $fileName = 'cachegrind.out.%p'): MemoryProfiler
    {
        ini_set('xdebug.profiler_output_name', $fileName);
        return $this;
    }
}