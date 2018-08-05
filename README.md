## Structure
![uml](media/uml.png)

## Installation
Install the latest version with

```bash
$ composer require tonychen/php-task
```
windows下忽略依赖安装
```bash
$ composer require tonychen/php-task --ignore-platform-reqs
```

## Basic Usage
```php
<?php

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
$processConfig->pidFile = '/tmp/php-task.pid';
$processConfig->stdErr  = '/dev/null';
$processConfig->stdOut  = '/dev/null';
$processConfig->stdIn   = '/dev/null';

$schedule = new Scheduler();
$schedule->getTimer()->everyMinute();
$schedule->attach(new Demo());

// 5分钟执行一次
$schedule5 = new Scheduler();
$schedule5->getTimer()->everyFiveMinutes();
$schedule5->attach(new Demo());

// 30分钟执行一次
$schedule30 = new Scheduler();
$schedule30->getTimer()->everyThirtyMinutes();
$schedule30->attach(new Demo());

// 自定义事件表达式
// 2个小时执行一次
$schedule2Hours = new Scheduler();
$schedule2Hours->getTimer()->setExpression("* */2 * * *");
$schedule2Hours->attach(new Demo());

$schedules = new SplObjectStorage();
$schedules->attach($schedule);
$schedules->attach($schedule5);
$schedules->attach($schedule30);
$schedules->attach($schedule2Hours);

$runner = new Runner();
$runner->setProcessConfig($processConfig);
$runner->setSchedulers($schedules);

$runner->run();
```

```bash
# 启动进程任务
php example.php start

# 结束进程任务
php example.php stop

# 查看进程任务的在线时间、内存使用状态、进程id
php example.php status
```
![进程状态](media/task-status.png)

## 文件日志结果(/tmp/php-task.log)
```php
2018-06-17 15:06:05	*/1 * * * *
2018-06-17 15:07:00	*/1 * * * *
2018-06-17 15:08:00	*/1 * * * *
2018-06-17 15:09:00	*/1 * * * *
2018-06-17 15:10:00	*/1 * * * *
2018-06-17 15:10:00	*/5 * * * *
2018-06-17 15:11:00	*/1 * * * *
2018-06-17 15:12:00	*/1 * * * *
2018-06-17 15:13:00	*/1 * * * *
2018-06-17 15:14:00	*/1 * * * *
2018-06-17 15:15:00	*/1 * * * *
2018-06-17 15:15:00	*/5 * * * *
2018-06-17 15:16:00	*/1 * * * *
2018-06-17 15:17:00	*/1 * * * *
2018-06-17 15:18:00	*/1 * * * *
2018-06-17 15:19:00	*/1 * * * *
2018-06-17 15:27:38	*/1 * * * *
2018-06-17 15:28:00	*/1 * * * *
2018-06-17 15:29:00	*/1 * * * *
2018-06-17 15:30:00	*/1 * * * *
2018-06-17 15:30:00	*/5 * * * *
2018-06-17 15:30:00	*/30 * * * *
2018-06-17 15:31:00	*/1 * * * *
2018-06-17 15:32:00	*/1 * * * *
```

## 特性
- 自动识别时区,不同时区的服务器再也不用手动修改时区
- 可以设置Job的执行优先级.

## 表达式说明
```php
*    *    *    *    *
-    -    -    -    -
|    |    |    |    |
|    |    |    |    |
|    |    |    |    +----- day of week (0 - 7) (Sunday=0 or 7)
|    |    |    +---------- month (1 - 12)
|    |    +--------------- day of month (1 - 31)
|    +-------------------- hour (0 - 23)
+------------------------- min (0 - 59)
```

### TODO LIST
- [ ] 完整的项目案例
- [ ] 测试用例
- [ ] 内存探测的做稳定了，用一段时间，如果没有新增功能，发布一个0.x系列的正式版本
- [ ] 再往后去，在功能上做一个1.0版本的规划，发布1.0版本

#### 参考文章
- https://segmentfault.com/a/1190000005979154
- http://hejunhao.me/archives/470
