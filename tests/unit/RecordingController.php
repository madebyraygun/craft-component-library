<?php

declare(strict_types=1);

namespace madebyraygun\componentlibrary\tests\unit;

use madebyraygun\componentlibrary\controllers\BrowserController;
use ReflectionClass;

/**
 * A browser controller that counts login redirects instead of ending the request.
 */
final class RecordingController extends BrowserController
{
    public int $loginRedirects = 0;

    // `true` is what the plugin declares; Craft's Controller::init() turns it into this constant.
    protected array|int|bool $allowAnonymous = self::ALLOW_ANONYMOUS_LIVE;

    public static function create(): self
    {
        $controller = (new ReflectionClass(self::class))->newInstanceWithoutConstructor();
        $controller->request = new class {
            public function getIsLivePreview(): bool
            {
                return false;
            }

            public function validateCsrfToken(): bool
            {
                return true;
            }

            public function getIsCpRequest(): bool
            {
                return false;
            }

            public function hasValidSiteToken(): bool
            {
                return false;
            }
        };

        return $controller;
    }

    protected function sendGuestToLogin(): void
    {
        $this->loginRedirects++;
    }
}
