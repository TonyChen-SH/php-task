<?php
declare(strict_types=1);

namespace Tony\Task\Utils;

use Psr\Log\LogLevel;
use Tony\Task\Struct\ProcessConfig;

class ThrowableCatch
{
    /**@var ProcessConfig $processConfig */
    protected $processConfig;

    use SingletonImpl;

    public function registerExceptionHandler(ProcessConfig $processConfig): void
    {
        $this->setProcessConfig($processConfig);
        set_exception_handler([$this, 'exceptionHandler']);
    }

    public function exceptionHandler(\Throwable $exception): void
    {
        $exceptionStr = $this->getExceptionTraceAsString($exception);

        $logger = new Logger($this->getProcessConfig()->logPath, LogLevel::DEBUG, ['filename' => $this->getProcessConfig()->logFileName]);
        $logger->error($exceptionStr);
    }

    // 获取完整的异常信息
    // http://stackoverflow.com/questions/1949345/how-can-i-get-the-full-string-of-php-s-gettraceasstring
    public function getExceptionTraceAsString(\Throwable $exception): string
    {
        $rtn   = '';
        $count = 0;
        $rtn   .= $exception->getMessage() . "\n";
        foreach ($exception->getTrace() as $frame)
        {
            $argString = '';
            if (isset($frame['args']))
            {
                $args = [];
                foreach ($frame['args'] as $arg)
                {
                    if (\is_string($arg))
                    {
                        $args[] = "'" . $arg . "'";
                    } else if (\is_array($arg))
                    {
                        $args[] = 'Array';
                    } else if (null === $arg)
                    {
                        $args[] = 'NULL';
                    } else if (\is_bool($arg))
                    {
                        $args[] = $arg ? 'true' : 'false';
                    } else if (\is_object($arg))
                    {
                        $args[] = \get_class($arg);
                    } else if (\is_resource($arg))
                    {
                        $args[] = get_resource_type($arg);
                    } else
                    {
                        $args[] = $arg;
                    }
                }
                $argString = implode(', ', $args);
            }
            $rtn .= sprintf("#%s %s(%s): %s(%s)\n",
                $count,
                $frame['file'],
                $frame['line'],
                $frame['function'],
                $argString);
            $count++;
        }

        return $rtn;
    }

    public function getProcessConfig(): ProcessConfig
    {
        return $this->processConfig;
    }

    public function setProcessConfig(ProcessConfig $processConfig): void
    {
        $this->processConfig = $processConfig;
    }
}