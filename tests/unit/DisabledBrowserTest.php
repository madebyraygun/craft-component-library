<?php

declare(strict_types=1);

namespace madebyraygun\componentlibrary\tests\unit;

use craft\events\RegisterUrlRulesEvent;
use craft\web\UrlManager;
use madebyraygun\componentlibrary\controllers\BaseController;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionClass;
use ReflectionMethod;
use Throwable;
use yii\base\Action;
use yii\base\Event;
use yii\base\InvalidRouteException;
use yii\base\Module;

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
     * The stand-in application cannot run an action, so anything other than the guard's own
     * exception means the request got further than it should.
     *
     * @param class-string<BaseController> $class
     */
    #[DataProvider('controllers')]
    public function testEveryActionIsRefusedWhenTheBrowserIsDisabled(string $class): void
    {
        foreach ([false, true] as $requiresLogin) {
            foreach ([true, false] as $guest) {
                $this->pluginWith(['enabled' => false, 'requiresLogin' => $requiresLogin], $guest);
                $controller = (new ReflectionClass($class))->newInstanceWithoutConstructor();

                self::assertInstanceOf(InvalidRouteException::class, $this->thrownBy(static fn() => $controller->beforeAction(null)));
            }
        }
    }

    /**
     * Craft turns an InvalidRouteException into its "Page not found." 404 and reports the
     * exception underneath it, so both must match what an unknown route produces.
     */
    public function testADisabledBrowserLooksLikeARouteThatDoesNotExist(): void
    {
        $unknownRoute = $this->thrownBy(static fn() => (new Module(''))->runAction(self::REQUESTED_ROUTE));
        $this->pluginWith(['enabled' => false]);
        $disabled = $this->thrownBy(static fn() => RecordingController::create()->beforeAction(null));

        self::assertInstanceOf(InvalidRouteException::class, $unknownRoute);
        self::assertInstanceOf(InvalidRouteException::class, $disabled);
        self::assertSame(
            [$unknownRoute::class, $unknownRoute->getMessage(), $unknownRoute->getCode(), $unknownRoute->getName()],
            [$disabled::class, $disabled->getMessage(), $disabled->getCode(), $disabled->getName()],
        );
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
            'enabled' => [['enabled' => true, 'requiresLogin' => false]],
            'enabled not set' => [['requiresLogin' => false]],
            'enabled is null' => [['enabled' => null, 'requiresLogin' => false]],
        ];
    }

    /**
     * @param array<string, mixed>|null $browser
     */
    #[DataProvider('enabledSettings')]
    public function testAnEnabledBrowserRunsTheAction(?array $browser): void
    {
        $this->pluginWith($browser);
        $controller = RecordingController::create();

        self::assertTrue($controller->beforeAction(new Action('index', $controller)));
        self::assertSame(0, $controller->loginRedirects);
    }

    /**
     * @return array<string, array{array<string, mixed>|null, bool, int}>
     */
    public static function logins(): array
    {
        return [
            'guest, login required' => [['requiresLogin' => true], true, 1],
            'guest, login required by default' => [[], true, 1],
            'guest, no browser settings at all' => [null, true, 1],
            'guest, login not required' => [['requiresLogin' => false], true, 0],
            'logged in, login required' => [['requiresLogin' => true], false, 0],
            'logged in, login not required' => [['requiresLogin' => false], false, 0],
        ];
    }

    /**
     * @param array<string, mixed>|null $browser
     */
    #[DataProvider('logins')]
    public function testOnlyAGuestIsSentToTheLoginAndOnlyWhenRequired(?array $browser, bool $guest, int $redirects): void
    {
        $this->pluginWith($browser, $guest);
        $controller = RecordingController::create();

        self::assertTrue($controller->beforeAction(new Action('index', $controller)));
        self::assertSame($redirects, $controller->loginRedirects);
    }

    public function testADisabledBrowserNeverAsksForALogin(): void
    {
        $this->pluginWith(['enabled' => false, 'requiresLogin' => true]);
        $controller = RecordingController::create();

        self::assertInstanceOf(InvalidRouteException::class, $this->thrownBy(static fn() => $controller->beforeAction(null)));
        self::assertSame(0, $controller->loginRedirects);
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
        foreach ($rules as $pattern => $route) {
            self::assertTrue($pattern === 'library' || str_starts_with($pattern, 'library/'), $pattern);
            self::assertMatchesRegularExpression('#^component-library/(browser|preview)(/[a-z-]+)?$#', $route);
        }
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

    private function thrownBy(callable $call): ?Throwable
    {
        try {
            $call();
        } catch (Throwable $e) {
            return $e;
        }

        return null;
    }
}
