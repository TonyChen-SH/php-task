<?php
declare(strict_types=1);

namespace Tony\Task;

/**
 * Class Daemon
 * @see     https://github.com/nramenta/clio
 * @package Tony\Task
 */
class Daemon
{
    /**
     * Daemonize a Closure object.
     *
     * @throws \Exception
     *
     * @param array    $options  Set of options
     * @param callable $callable Closure object to daemonize
     *
     * @return void, throws an Exception otherwise
     */
    public static function work(array $options, callable $callable): void
    {
        if (!\extension_loaded('pcntl'))
        {
            throw new \RuntimeException('pcntl extension required');
        }
        if (!\extension_loaded('posix'))
        {
            throw new \RuntimeException('posix extension required');
        }
        if (!isset($options['pid']))
        {
            throw new \RuntimeException('pid not specified');
        }
        $options += [
            'stdin'  => '/dev/null',
            'stdout' => '/dev/null',
            'stderr' => 'php://stdout',
        ];
        if (($lock = @fopen($options['pid'], 'cb+')) === false)
        {
            throw new \RuntimeException('unable to open pid file ' . $options['pid']);
        }
        if (!flock($lock, LOCK_EX | LOCK_NB))
        {
            throw new \RuntimeException('could not acquire lock for ' . $options['pid']);
        }
        switch ($pid = pcntl_fork())
        {
            case -1:
                throw new \RuntimeException('unable to fork');
            case 0:
                break;
            default:
                fseek($lock, 0);
                ftruncate($lock, 0);
                fwrite($lock, (string)$pid);
                fflush($lock);
                return;
        }
        if (posix_setsid() === -1)
        {
            throw new \RuntimeException('failed to setsid');
        }

        // 先关闭再打开，就可以做到定向的作用
        // https://hk.saowen.com/a/c718d756ad077be09757b73845045fca67afa5d5e02c58b5605661baebcc2ad8
        fclose(STDIN);
        fclose(STDOUT);
        fclose(STDERR);
        if (!($stdin = fopen($options['stdin'], 'rb')))
        {
            throw new \RuntimeException('failed to open STDIN ' . $options['stdin']);
        }
        if (!($stdout = fopen($options['stdout'], 'wb')))
        {
            throw new \RuntimeException('failed to open STDOUT ' . $options['stdout']);
        }
        if (!($stderr = fopen($options['stderr'], 'wb')))
        {
            throw new \RuntimeException('failed to open STDERR ' . $options['stderr']);
        }

        pcntl_signal(SIGTSTP, SIG_IGN);
        pcntl_signal(SIGTTOU, SIG_IGN);
        pcntl_signal(SIGTTIN, SIG_IGN);
        pcntl_signal(SIGHUP, SIG_IGN);
        //call_user_func($callable, $stdin, $stdout, $stderr);
        $callable($stdin, $stdout, $stderr);
    }

    /**
     * Checks whether a daemon process specified by its PID file is running.
     *
     * @throws \Exception
     *
     * @param string $file Daemon PID file
     *
     * @return bool True if the daemon is still running, false otherwise
     */
    public static function isRunning($file): bool
    {
        if (!\extension_loaded('posix'))
        {
            throw new \RuntimeException('posix extension required');
        }
        if (!is_readable($file))
        {
            return false;
        }
        if (($lock = @fopen($file, 'c+')) === false)
        {
            throw new \RuntimeException('unable to open pid file ' . $file);
        }
        if (flock($lock, LOCK_EX | LOCK_NB))
        {
            return false;
        }

        flock($lock, LOCK_UN);
        return true;
    }

    /**
     * Kills a daemon process specified by its PID file.
     *
     * @throws \Exception
     *
     * @param string $file   Daemon PID file
     * @param bool   $delete Flag to delete PID file after killing
     *
     * @return bool True on success, false otherwise
     */
    public static function kill($file, $delete = false): ?bool
    {
        if (!\extension_loaded('posix'))
        {
            throw new \RuntimeException('posix extension required');
        }
        if (!is_readable($file))
        {
            throw new \RuntimeException('unreadable pid file ' . $file);
        }
        if (($lock = @fopen($file, 'cb+')) === false)
        {
            throw new \RuntimeException('unable to open pid file ' . $file);
        }
        if (flock($lock, LOCK_EX | LOCK_NB))
        {
            flock($lock, LOCK_UN);
            throw new \RuntimeException('process not running');
        }
        $pid = fgets($lock);

        if (posix_kill((int)$pid, SIGTERM))
        {
            if ($delete) unlink($file);
            return true;
        }

        return false;
    }
}