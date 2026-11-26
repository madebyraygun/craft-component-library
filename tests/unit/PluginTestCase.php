<?php

declare(strict_types=1);

namespace madebyraygun\componentlibrary\tests\unit;

use Craft;
use madebyraygun\componentlibrary\Plugin;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use yii\base\Event;

/**
 * Runs the plugin against a stand-in application, so no Craft install or database is needed.
 */
abstract class PluginTestCase extends TestCase
{
    private mixed $previousApp = null;

    protected function setUp(): void
    {
        $this->previousApp = Craft::$app;
    }

    protected function tearDown(): void
    {
        Event::offAll();
        Craft::$app = $this->previousApp;
    }

    /**
     * @param array<string, mixed>|null $browser the `browser` settings, or null to leave them unset
     */
    protected function pluginWith(?array $browser): Plugin
    {
        $plugin = (new ReflectionClass(Plugin::class))->newInstanceWithoutConstructor();
        if ($browser !== null) {
            $plugin->setSettings(['browser' => $browser]);
        }
        Craft::$app = new class($plugin) {
            /** @var array<class-string, object> */
            public array $loadedModules;

            public function __construct(Plugin $plugin)
            {
                $this->loadedModules = [Plugin::class => $plugin];
            }
        };

        return $plugin;
    }
}
