<?php
declare(strict_types=1);

namespace Tony\Task\Utils;

/**
 * 通用单例模块声明
 * Trait Singleton
 */
trait SingletonImpl
{
    private static $instance;

    /**
     * @return self
     */
    public static function getInstance(): self
    {
        if (!self::$instance)
        {
            self::$instance = new self;
        }

        return self::$instance;
    }

    private function __construct()
    {
    }

    private function __clone()
    {
    }
}