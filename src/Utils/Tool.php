<?php
declare(strict_types=1);

namespace Tony\Task\Utils;

class Tool
{
    use SingletonImpl;

    public function sizeConvert(int $size): string
    {
        $unit = ['b', 'kb', 'mb', 'gb', 'tb', 'pb'];
        return @round($size / pow(1024, ($i = floor(log($size, 1024)))), 2) . ' ' . $unit[$i];
    }
}