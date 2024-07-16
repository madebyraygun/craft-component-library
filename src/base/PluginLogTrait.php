<?php

namespace madebyraygun\componentlibrary\base;

use Craft;
use craft\log\MonologTarget;
use madebyraygun\componentlibrary\Plugin;
use Monolog\Formatter\LineFormatter;
use Psr\Log\LogLevel;

trait PluginLogTrait
{
    public static function log(string $message, array $params = []): void
    {
        $message = Craft::t('component-library', $message, $params);
        Craft::info($message, __METHOD__);
    }

    public static function error(string $message, array $params = []): void
    {
        $message = Craft::t('component-library', $message, $params);
        Craft::error($message, __METHOD__);
    }

    public static function warning(string $message, array $params = []): void
    {
        $message = Craft::t('component-library', $message, $params);
        Craft::warning($message, __METHOD__);
    }

    public static function registerMonologTargetLevel($level): void
    {
        $target = new MonologTarget([
            'name' => Plugin::$pluginHandle,
            'categories' => ['madebyraygun\componentlibrary\*'],
            'level' => $level,
            'logContext' => false,
            'allowLineBreaks' => true,
            'logVars' => ['_GET', '_POST'],
            'formatter' => new LineFormatter(
                format: "%datetime% [%level_name%] %message%\n",
                dateFormat: 'Y-m-d H:i:s',
                allowInlineLineBreaks: true,
                ignoreEmptyContextAndExtra: true,
            ),
        ]);
        Craft::getLogger()->dispatcher->targets[] = $target;
    }

    public static function registerLogger(): void
    {
        // self::registerMonologTargetLevel(LogLevel::ERROR);
        self::registerMonologTargetLevel(LogLevel::INFO);
    }
}
