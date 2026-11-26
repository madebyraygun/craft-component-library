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
    public const REQUESTED_ROUTE = 'component-library/browser';

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
    protected function pluginWith(?array $browser, bool $guest = true): Plugin
    {
        $plugin = (new ReflectionClass(Plugin::class))->newInstanceWithoutConstructor();
        if ($browser !== null) {
            $plugin->setSettings(['browser' => $browser]);
        }
        Craft::$app = new class($plugin, $guest) {
            /** @var array<class-string, object> */
            public array $loadedModules;

            public string $requestedRoute = PluginTestCase::REQUESTED_ROUTE;

            public object $user;

            public ?object $request = null;

            public ?object $response = null;

            public ?object $view = null;

            public string $charset = 'UTF-8';

            public string $language = 'en-US';

            public function __construct(Plugin $plugin, bool $guest)
            {
                $this->loadedModules = [Plugin::class => $plugin];
                $this->user = new class($guest) {
                    public function __construct(private bool $guest)
                    {
                    }

                    public function getIsGuest(): bool
                    {
                        return $this->guest;
                    }
                };
            }

            public function getUser(): object
            {
                return $this->user;
            }

            public function getErrorHandler(): object
            {
                return (object)['exception' => null];
            }

            public function getIsLive(): bool
            {
                return true;
            }

            public function getPlugins(): object
            {
                return new class($this->loadedModules[Plugin::class]) {
                    public function __construct(private object $plugin)
                    {
                    }

                    public function getPlugin(string $handle): object
                    {
                        return $this->plugin;
                    }
                };
            }

            public function getView(): ?object
            {
                return $this->view;
            }

            public function getI18n(): object
            {
                return new class {
                    /**
                     * @param array<string, mixed> $params
                     */
                    public function translate(string $category, string $message, array $params, string $language): string
                    {
                        return strtr($message, array_combine(array_map(static fn($key) => '{' . $key . '}', array_keys($params)), array_map('strval', $params)));
                    }
                };
            }
        };

        return $plugin;
    }
}
