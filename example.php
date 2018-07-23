<?php
declare(strict_types=1);

include 'vendor/autoload.php';

use Tony\Task\Job\Job;
use Tony\Task\Runner;
use Tony\Task\Scheduler;
use Tony\Task\Struct\ProcessConfig;

class Demo extends Job
{
    public function execute(Scheduler $subject): void
    {
        /**@var Scheduler $subject */
        $expression = $subject->getTimer()->getExpression();
        error_log(date('Y-m-d H:i:s') . "\t{$expression}\n", 3, '/tmp/php-task.log');
    }
}

$processConfig          = new ProcessConfig();
$processConfig->pidFile = __DIR__ . '/php-task.pid';
$processConfig->stdErr  = '/dev/null';
$processConfig->stdOut  = __DIR__ . '/log_err.txt';
$processConfig->stdIn   = '/dev/null';

$processConfig->logPath              = __DIR__;
$processConfig->logFileName          = 'log_err.txt';
$processConfig->enableMemoryProfiler = true;

$schedule = new Scheduler();
$schedule->getTimer()->everyMinute();
$schedule->attach(new Demo());

$schedule5 = new Scheduler();
$schedule5->getTimer()->everyFiveMinutes();
$schedule5->attach(new Demo());

$schedule30 = new Scheduler();
$schedule30->getTimer()->everyThirtyMinutes();
$schedule30->attach(new Demo());


$schedules = new SplObjectStorage();
$schedules->attach($schedule);
$schedules->attach($schedule5);
$schedules->attach($schedule30);

$runner = new Runner($processConfig);
$runner->setSchedulers($schedules);

$runner->run();