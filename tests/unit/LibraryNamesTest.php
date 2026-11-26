<?php

declare(strict_types=1);

namespace madebyraygun\componentlibrary\tests\unit;

use Craft;
use madebyraygun\componentlibrary\controllers\BaseController;
use madebyraygun\componentlibrary\controllers\BrowserController;
use madebyraygun\componentlibrary\controllers\PreviewController;
use madebyraygun\componentlibrary\helpers\Common;
use madebyraygun\componentlibrary\helpers\Component;
use madebyraygun\componentlibrary\helpers\Context;
use madebyraygun\componentlibrary\helpers\Document;
use madebyraygun\componentlibrary\helpers\Library;
use madebyraygun\componentlibrary\helpers\Loader;
use madebyraygun\componentlibrary\Plugin;
use madebyraygun\componentlibrary\web\twig\TemplateLoader;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionClass;
use Throwable;
use yii\web\NotFoundHttpException;

final class LibraryNamesTest extends LibraryTestCase
{
    private const ACTIONS = [
        [PreviewController::class, 'actionIndex'],
        [BrowserController::class, 'actionPartialToolbar'],
        [BrowserController::class, 'actionPartialPreview'],
    ];

    /**
     * Names that are not a component or document in the library. `{site}` and `{root}` stand for
     * the fixture paths.
     *
     * @return array<string, array{string}>
     */
    public static function unknownNames(): array
    {
        return [
            'parent folder, template' => ['../templates/other'],
            'parent folder, template with extension' => ['../templates/other.twig'],
            'parent folder, markdown' => ['../notes.md'],
            'parent folder, markdown without extension' => ['../notes'],
            'parent folder, component folder' => ['../other/component'],
            'parent folder, text file' => ['../settings.txt'],
            'parent folder behind an alias' => ['@elements/../../templates/other'],
            'parent folder behind a real folder' => ['02-elements/button/../../../templates/other'],
            'parent folder after a variant marker' => ['@elements/button--x/../../../templates/other'],
            'many parent folders' => [str_repeat('../', 40) . 'etc/hosts'],
            'backslashes' => ['..\\templates\\other'],
            'mixed separators' => ['..\\/templates/other'],
            'dot segments' => ['./.././templates/other'],
            'doubled slashes' => ['..//templates//other'],
            'percent-encoded dots, as text' => ['%2e%2e/templates/other'],
            'twice-encoded dots, as text' => ['%252e%252e/templates/other'],
            'percent-encoded slash, as text' => ['..%2ftemplates%2fother'],
            'overlong UTF-8 dots, as text' => ["\xC0\xAE\xC0\xAE/templates/other"],
            'absolute path to a template' => ['{site}/templates/other.twig'],
            'absolute path to markdown' => ['{site}/notes.md'],
            'absolute path through the root' => ['{root}/../templates/other.twig'],
            'absolute system path' => ['/etc/hosts'],
            'php stream' => ['php://filter/resource=../templates/other.twig'],
            'phar stream' => ['phar://{site}/templates/other.twig'],
            'file URL' => ['file://{site}/templates/other.twig'],
            'null byte' => ["../templates/other\0.twig"],
            'null byte after a real component' => ["@elements/button\0/../../../templates/other"],
            'mixed case' => ['../Templates/OTHER'],
            'a directory named like a template' => ['folder.twig'],
            'a directory' => ['02-elements'],
            'the root itself' => ['.'],
            'the parent itself' => ['..'],
            'only an at sign' => ['@'],
            'one byte over the length limit' => [str_repeat('a', 513)],
            'very long name' => [str_repeat('a', 5000)],
            'very long parent chain' => [str_repeat('../', 2000) . 'templates/other'],
            'a PHP file in the library' => ['plain.php'],
            'a PHP file as a variant parent' => ['plain.php--x.twig'],
            'a PHP file in a subfolder as a variant parent' => ['sub/other.php--x.twig'],
            'a PHP file in a subfolder, backslash, as a variant parent' => ['sub\\other.php--x.twig'],
            'a text file as a variant parent' => ['sub\\notes.txt--x.twig'],
            'a file outside as a variant parent, backslash' => ['..\\settings.txt--x.twig'],
            'a PHP file outside as a variant parent, backslash' => ['..\\templates\\other.config.php--x.twig'],
            'a component config named directly' => ['02-elements/button/button.config.php'],
            'a component config as a variant parent' => ['02-elements\\button\\button.config.php--x.twig'],
            'something that does not exist anywhere' => ['@elements/nothing-here'],
        ];
    }

    /**
     * @return array<string, array{string}>
     */
    public static function linkedNames(): array
    {
        return [
            'linked folder' => ['linked-folder/component'],
            'linked folder, full file' => ['linked-folder/component/component.twig'],
            'linked folder, markdown' => ['linked-folder/readme.md'],
            'linked template' => ['@elements/linked'],
            'linked template, with extension' => ['02-elements/linked.twig'],
            'linked markdown in the docs folder' => ['@docs/linked'],
            'through a link back to the root' => ['02-elements/loop/02-elements/linked'],
        ];
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function components(): array
    {
        return [
            'alias' => ['@elements/button', '02-elements/button/button.twig'],
            'folder path' => ['02-elements/button', '02-elements/button/button.twig'],
            'full file' => ['02-elements/button/button.twig', '02-elements/button/button.twig'],
            'at sign and folder' => ['@02-elements/button', '02-elements/button/button.twig'],
            'variant from the config' => ['@elements/button--outline', '02-elements/button/button.twig'],
            'variant with its own file' => ['@elements/button--close', '02-elements/button/button--close.twig'],
            'nested folders' => ['@elements/form/inputs/checkbox', '02-elements/form/inputs/checkbox/checkbox.twig'],
            'nested folders, full file' => ['02-elements/form/inputs/checkbox/checkbox.twig', '02-elements/form/inputs/checkbox/checkbox.twig'],
            'backslash separators' => ['02-elements\\form\\inputs\\checkbox', '02-elements/form/inputs/checkbox/checkbox.twig'],
        ];
    }

    #[DataProvider('unknownNames')]
    public function testAnUnknownNameResolvesToNothing(string $name): void
    {
        $this->assertResolvesToNothing($this->fill($name));
    }

    #[DataProvider('linkedNames')]
    public function testALinkOutOfTheLibraryIsNotFollowed(string $name): void
    {
        $this->addLinks();

        $this->assertResolvesToNothing($name);
    }

    #[DataProvider('unknownNames')]
    public function testAnUnknownNameIs404OnEveryAction(string $name): void
    {
        Craft::$app->request->name = $this->fill($name);

        foreach (self::ACTIONS as [$class, $action]) {
            self::assertInstanceOf(NotFoundHttpException::class, $this->thrownBy($class, $action), $class . '::' . $action);
        }
        self::assertFileDoesNotExist($this->marker);
    }

    public function testThe404IsTheSameWhateverTheName(): void
    {
        $this->addLinks();
        $answers = [];
        foreach (['../templates/other', '../templates/no-such-file', '@elements/no-such-component', 'linked-folder/component', 'plain.php--x.twig', "bad\0name"] as $name) {
            Craft::$app->request->name = $name;
            foreach (self::ACTIONS as [$class, $action]) {
                $thrown = $this->thrownBy($class, $action);
                self::assertInstanceOf(NotFoundHttpException::class, $thrown);
                $answers[] = [$thrown->getMessage(), $thrown->statusCode, $thrown->getCode()];
            }
        }

        self::assertCount(1, array_unique($answers, SORT_REGULAR));
        self::assertSame(['', 404, 0], $answers[0]);
    }

    /**
     * @return array<string, array{mixed}>
     */
    public static function namesThatAreNotStrings(): array
    {
        return [
            'missing' => [null],
            'empty' => [''],
            'array' => [['../templates/other']],
            'nested array' => [['a' => ['b']]],
        ];
    }

    #[DataProvider('namesThatAreNotStrings')]
    public function testThePreviewIs404WithoutAName(mixed $name): void
    {
        Craft::$app->request->name = $name;

        self::assertInstanceOf(NotFoundHttpException::class, $this->thrownBy(PreviewController::class, 'actionIndex'));
    }

    #[DataProvider('unknownNames')]
    public function testTheBrowserPageIs404AndShowsNothingForAnUnknownName(string $name): void
    {
        Craft::$app->request->name = $this->fill($name);
        $controller = RecordingController::create();
        $controller->request = Craft::$app->request;

        $controller->actionIndex();

        self::assertSame(404, Craft::$app->response->statusCode);
        self::assertCount(1, $controller->rendered);
        $variables = $controller->rendered[0]['variables'];
        self::assertNull($variables['toolbar']);
        self::assertFalse($variables['current']['exists']);
        $shown = (string)json_encode($variables, JSON_PARTIAL_OUTPUT_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE);
        self::assertStringNotContainsString(self::OUTSIDE, $shown);
        self::assertStringNotContainsString(json_encode($this->site), $shown === '' ? 'x' : str_replace((string)json_encode(Craft::$app->request->name), '""', $shown));
        self::assertFileDoesNotExist($this->marker);
    }

    public function testTheBrowserPageIs200ForAKnownComponent(): void
    {
        Craft::$app->request->name = '@elements/button';
        $controller = RecordingController::create();
        $controller->request = Craft::$app->request;

        $controller->actionIndex();

        self::assertSame(200, Craft::$app->response->statusCode);
        self::assertSame('02-elements/button/button.twig', $controller->rendered[0]['variables']['toolbar']['file']);
        self::assertTrue($controller->rendered[0]['variables']['current']['exists']);
    }

    #[DataProvider('components')]
    public function testAComponentResolvesToItsFile(string $name, string $file): void
    {
        self::assertTrue(Loader::componentExists($name));
        self::assertSame($this->root . '/' . $file, Plugin::$plugin->componentProvider->resolveHandlePath($name));
        self::assertNotNull(Library::getUiToolbarContext($name));
    }

    public function testDocumentsResolveToTheirFiles(): void
    {
        $provider = Plugin::$plugin->componentProvider;

        self::assertSame($this->root . '/docs/index.md', $provider->resolveHandlePath('@docs/index'));
        self::assertSame($this->root . '/docs/guide/setup.md', $provider->resolveHandlePath('@docs/guide/setup'));
        self::assertSame($this->root . '/docs/guide/setup.md', $provider->resolveHandlePath('docs/guide/setup.md'));
        self::assertSame('# Welcome', $provider->getDocumentContext('@docs/index')['content']);
        self::assertSame('# Button', Library::getDocContents('@elements/button'));
    }

    /**
     * Handles are lower-cased, so a folder with capitals is only found where the disk ignores case.
     */
    public function testAFolderWithCapitalsResolvesOnlyWhereTheDiskIgnoresCase(): void
    {
        $lowerCased = $this->root . '/02-elements/banner/banner.twig';

        self::assertSame(file_exists($lowerCased) ? $lowerCased : null, Plugin::$plugin->componentProvider->resolveHandlePath('@elements/Banner'));
    }

    /**
     * Everything after the first dot in a name is read as an extension, so a dotted folder does not
     * resolve to its own template.
     */
    public function testADottedNameDoesNotResolveToItsOwnFile(): void
    {
        $provider = Plugin::$plugin->componentProvider;

        self::assertNull($provider->resolveHandlePath('02-elements/card.v2/card.v2.twig'));
        self::assertSame($this->root . '/02-elements/card/card.twig', $provider->resolveHandlePath('@elements/card.v2'));
    }

    public function testConfigIsReadOnlyFromAConfigFileBesideATemplate(): void
    {
        $provider = Plugin::$plugin->componentProvider;

        self::assertSame(['label' => 'Go'], $provider->getComponentContext('@elements/button'));
        self::assertSame(['label' => 'Out'], $provider->getComponentContext('@elements/button--outline'));
        self::assertSame(['title' => 'Card'], $provider->getComponentContext('@elements/card'));
        foreach (['plain.php', 'sub/other.php', 'sub/notes.txt', '02-elements/button/button.config.php', '02-elements/button/readme.md'] as $file) {
            self::assertSame(['configType' => '', 'configPath' => '', 'configExists' => false], Component::getConfigParts($this->root . '/' . $file), $file);
        }
        self::assertSame('php', Component::getConfigParts($this->root . '/02-elements/button/button.twig')['configType']);
        self::assertFileDoesNotExist($this->marker);
    }

    public function testALinkedConfigFileIsNotRead(): void
    {
        $this->addLinks();

        self::assertTrue(Loader::componentExists('@elements/card'));
        self::assertSame(['title' => 'Card'], Plugin::$plugin->componentProvider->getComponentContext('@elements/card'));
        self::assertFileDoesNotExist($this->marker);
    }

    public function testADocsFolderBesideTheLibraryIsAllowedAndNothingElseIs(): void
    {
        $this->write('docs/guide.md', '# Guide');
        $this->useLibrary(['docs' => $this->site . '/docs']);

        self::assertTrue(Common::isFileInsideLibrary($this->site . '/docs/guide.md'));
        self::assertTrue(Loader::documentExists('../docs/guide'));
        self::assertFalse(Loader::documentExists('../notes'));
        self::assertFalse(Loader::documentExists('../docs/../notes'));
        self::assertFalse(Common::isInsideLibrary($this->site . '/docs-other/x.md'));
    }

    public function testAFolderWhoseNameStartsLikeTheRootIsNotTheRoot(): void
    {
        $this->write('library-other/page.twig', self::OUTSIDE);

        self::assertFalse(Common::isInsideLibrary($this->root . '-other/page.twig'));
        self::assertFalse(Common::isFileInsideLibrary($this->root . '-other/page.twig'));
        self::assertFalse(Loader::componentExists('../library-other/page'));
        self::assertFalse(Common::isInsideLibrary($this->root));
    }

    public function testALibraryRootThatIsALinkStillWorks(): void
    {
        $this->addLinks();
        symlink($this->root, $this->site . '/library-link');
        $this->useLibrary(['root' => $this->site . '/library-link', 'docs' => $this->site . '/library-link/docs']);

        self::assertTrue(Loader::componentExists('@elements/button'));
        self::assertFalse(Loader::componentExists('../templates/other'));
        self::assertFalse(Loader::componentExists('@elements/linked'));
    }

    public function testTheTreeListsOnlyTheLibrary(): void
    {
        $this->addLinks();
        $trees = [Library::getLibraryTree(), Library::getDocumentsTree()];
        $handles = array_column(Library::getSearchIndexFromTrees($trees), 'includeName');

        foreach (['@02-elements/button', '@02-elements/button--outline', '@02-elements/button--close', '@02-elements/card', '@02-elements/form/inputs/checkbox', '@docs/index', '@docs/guide/setup'] as $handle) {
            self::assertContains($handle, $handles);
        }
        $listed = (string)json_encode($trees);
        foreach (['linked', 'loop', self::OUTSIDE] as $word) {
            self::assertStringNotContainsString($word, $listed);
        }
        self::assertFileDoesNotExist($this->marker);
    }

    public function testNoAbsolutePathIsShown(): void
    {
        $trees = [Library::getLibraryTree(), Library::getDocumentsTree()];
        $toolbar = Library::getUiToolbarContext('@elements/button');
        $shown = (string)json_encode([$trees, Library::getSearchIndexFromTrees($trees), $toolbar['file'], $toolbar['contents']], JSON_UNESCAPED_SLASHES);

        self::assertStringNotContainsString($this->site, $shown);
        self::assertSame('02-elements/button/button.twig', $toolbar['file']);
        self::assertStringNotContainsString('templatePath', (string)file_get_contents(dirname(__DIR__, 2) . '/src/templates/_partials/toolbar_info.twig'));
    }

    public function testARenderErrorDoesNotShowAnAbsolutePath(): void
    {
        $view = StubView::create();
        $view->failure = 'Unable to read ' . $this->root . '/02-elements/button/button.twig, included from ' . $this->site . '/templates/page.twig';
        Craft::$app->view = $view;

        $compiled = Library::getUiToolbarContext('@elements/button')['contents']['compiled_component'];

        self::assertSame('Error: Unable to read 02-elements/button/button.twig, included from templates/page.twig', $compiled);
        self::assertStringNotContainsString($this->site, $compiled);
    }

    public function testTheTemplateLoaderServesLibraryHandlesOnly(): void
    {
        $this->addLinks();
        $view = StubView::create();
        $loader = new TemplateLoader($view);

        $source = $loader->getSourceContext('@elements/button');
        self::assertSame('<a class="button">{{ label }}</a>', $source->getCode());
        self::assertSame($this->root . '/02-elements/button/button.twig', $source->getPath());
        self::assertSame($this->root . '/02-elements/button/button.twig', $loader->getCacheKey('@elements/button'));

        foreach (['@elements/../../templates/other', '@../templates/other', '@elements/linked', '@linked-folder/component', '@plain.php--x.twig', '@..\\settings.txt--x.twig'] as $name) {
            $thrown = null;
            try {
                $loader->getSourceContext($name);
            } catch (Throwable $e) {
                $thrown = $e;
            }
            self::assertNotNull($thrown, $name);
            self::assertStringNotContainsString(self::OUTSIDE, $thrown->getMessage());
        }
        self::assertSame([], $view->resolved);
        self::assertFileDoesNotExist($this->marker);
    }

    public function testANameMayBeAsLongAsTheLimitAndNoLonger(): void
    {
        self::assertTrue(Common::isUsableHandle(str_repeat('a', Common::MAX_HANDLE_LENGTH)));
        self::assertFalse(Common::isUsableHandle(str_repeat('a', Common::MAX_HANDLE_LENGTH + 1)));
        self::assertSame(512, Common::MAX_HANDLE_LENGTH);
    }

    public function testARequestNameAlwaysBecomesALibraryHandle(): void
    {
        self::assertSame('@elements/button', Common::libraryHandle('@elements/button'));
        self::assertSame('@02-elements/button', Common::libraryHandle('02-elements/button'));
        self::assertSame('@index', Common::libraryHandle('index'));
    }

    private function assertResolvesToNothing(string $name): void
    {
        $provider = Plugin::$plugin->componentProvider;
        $component = Component::parseComponentParts($name);
        $document = Document::parseDocumentParts($name);

        self::assertFalse(Loader::componentExists($name));
        self::assertFalse(Loader::documentExists($name));
        self::assertFalse(Loader::handleExists($name));
        self::assertSame('', Loader::handleType($name));
        self::assertNull($provider->resolveHandlePath($name));
        self::assertNull($provider->getDocumentContext($name));
        self::assertSame([], $provider->getComponentContext($name));
        self::assertNull($provider->getComponentSettings($name));
        self::assertNull(Library::getUiToolbarContext($name));
        self::assertFalse($document->valid);
        foreach ([$component->templatePath, $component->configPath, $component->docPath, $document->docPath] as $path) {
            self::assertTrue($path === '' || str_starts_with($path, $this->root . '/'), 'not in the library: ' . $path);
        }
        self::assertSame('', $component->configPath);
        self::assertSame([], Context::readConfigFile($name));
        self::assertSame('', Library::getContextContents($name, false));
        self::assertSame('', Library::getComponentContents($name, false));
        self::assertStringNotContainsString(self::OUTSIDE, Library::getDocContents($name));
        self::assertStringNotContainsString(self::OUTSIDE, (string)json_encode([$component, $document, Context::getComponentConfig($name)], JSON_PARTIAL_OUTPUT_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE));
        self::assertFileDoesNotExist($this->marker);
    }

    /**
     * @param class-string<BaseController> $class
     */
    private function thrownBy(string $class, string $action): ?Throwable
    {
        $controller = (new ReflectionClass($class))->newInstanceWithoutConstructor();
        $controller->request = Craft::$app->request;
        try {
            $controller->$action();
        } catch (Throwable $e) {
            return $e;
        }

        return null;
    }

    private function fill(string $name): string
    {
        return str_replace(['{site}', '{root}'], [$this->site, $this->root], $name);
    }
}
