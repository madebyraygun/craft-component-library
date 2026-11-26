<?php

declare(strict_types=1);

namespace madebyraygun\componentlibrary\tests\unit;

use craft\web\Response;
use madebyraygun\componentlibrary\controllers\BrowserController;
use ReflectionClass;

/**
 * A browser controller that records login redirects and rendered templates instead of acting on them.
 */
final class RecordingController extends BrowserController
{
    public int $loginRedirects = 0;

    /** @var array<int, array{template: string, variables: array<string, mixed>}> */
    public array $rendered = [];

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

    /**
     * @param array<string, mixed> $variables
     */
    public function renderPluginTemplate(string $template, array $variables = [], ?string $templateMode = null): Response
    {
        $this->rendered[] = ['template' => $template, 'variables' => $variables + ['current' => $this->getCurrentSelection()]];

        return new Response();
    }
}
