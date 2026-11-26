<?php

declare(strict_types=1);

namespace madebyraygun\componentlibrary\tests\unit;

use craft\events\RegisterUrlRulesEvent;
use craft\web\UrlManager;
use madebyraygun\componentlibrary\controllers\BaseController;
use madebyraygun\componentlibrary\controllers\BrowserController;
use madebyraygun\componentlibrary\controllers\PreviewController;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionClass;
use ReflectionMethod;
use yii\base\Event;
use yii\web\NotFoundHttpException;

final class DisabledBrowserTest extends PluginTestCase
{
    /**
     * @return array<string, array{class-string<BaseController>}>
     */
    public static function controllers(): array
    {
        $controllers = [];
        foreach (glob(dirname(__DIR__, 2) . '/src/controllers/*Controller.php') ?: [] as $file) {
            $name = basename($file, '.php');
            /** @var class-string<BaseController> $class */
            $class = 'madebyraygun\\componentlibrary\\controllers\\' . $name;
            $controllers[$name] = [$class];
        }

        return $controllers;
    }

    public function testEveryControllerIsFound(): void
    {
        self::assertSame(['BaseController', 'BrowserController', 'PreviewController'], array_keys(self::controllers()));
    }

    /**
     * The stand-in application has no user or request, so reaching the login check or the action
     * would fail with an Error instead of the 404.
     *
     * @param class-string<BaseController> $class
     */
    #[DataProvider('controllers')]
    public function testEveryActionIs404WhenTheBrowserIsDisabled(string $class): void
    {
        $this->pluginWith(['enabled' => false, 'requiresLogin' => false]);
        $controller = (new ReflectionClass($class))->newInstanceWithoutConstructor();

        $this->expectException(NotFoundHttpException::class);
        $controller->beforeAction(null);
    }

    /**
     * @param class-string<BaseController> $class
     */
    #[DataProvider('controllers')]
    public function testDisabledWinsOverTheLoginRequirement(string $class): void
    {
        $this->pluginWith(['enabled' => false, 'requiresLogin' => true]);
        $controller = (new ReflectionClass($class))->newInstanceWithoutConstructor();

        $this->expectException(NotFoundHttpException::class);
        $controller->beforeAction(null);
    }

    /**
     * @param class-string<BaseController> $class
     */
    #[DataProvider('controllers')]
    public function testNoControllerSkipsTheCheck(string $class): void
    {
        self::assertTrue($class === BaseController::class || is_subclass_of($class, BaseController::class));
        self::assertSame(BaseController::class, (new ReflectionMethod($class, 'beforeAction'))->getDeclaringClass()->getName());
    }

    /**
     * @return array<string, array{array<string, mixed>|null}>
     */
    public static function enabledSettings(): array
    {
        return [
            'enabled' => [['enabled' => true]],
            'enabled not set' => [[]],
            'no browser settings at all' => [null],
        ];
    }

    /**
     * @param array<string, mixed>|null $browser
     */
    #[DataProvider('enabledSettings')]
    public function testAnEnabledBrowserPassesTheCheck(?array $browser): void
    {
        $this->pluginWith($browser);
        $controller = (new ReflectionClass(BrowserController::class))->newInstanceWithoutConstructor();

        (new ReflectionMethod(BaseController::class, 'requireBrowserEnabled'))->invoke($controller);
        $this->addToAssertionCount(1);
    }

    public function testNoSiteUrlRulesAreRegisteredWhenTheBrowserIsDisabled(): void
    {
        self::assertSame([], $this->siteUrlRules(['enabled' => false]));
    }

    public function testSiteUrlRulesAreRegisteredUnderTheConfiguredPath(): void
    {
        $rules = $this->siteUrlRules(['enabled' => true, 'path' => 'library']);

        self::assertSame('component-library/browser', $rules['library']);
        self::assertSame('component-library/preview', $rules['library/preview']);
        self::assertCount(7, $rules);
        foreach ($rules as $route) {
            self::assertMatchesRegularExpression('#^component-library/(browser|preview)(/[a-z-]+)?$#', $route);
        }
    }

    public function testThePreviewControllerIsCovered(): void
    {
        self::assertTrue(is_subclass_of(PreviewController::class, BaseController::class));
    }

    /**
     * @param array<string, mixed> $browser
     * @return array<string, string>
     */
    private function siteUrlRules(array $browser): array
    {
        $plugin = $this->pluginWith($browser);
        (new ReflectionMethod($plugin, 'installEventListeners'))->invoke($plugin);
        $event = new RegisterUrlRulesEvent();
        Event::trigger(UrlManager::class, UrlManager::EVENT_REGISTER_SITE_URL_RULES, $event);

        return $event->rules;
    }
}
