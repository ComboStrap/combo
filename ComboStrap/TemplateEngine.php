<?php

namespace ComboStrap;

use ComboStrap\Tag\ShareTag;
use Handlebars\Context;
use Handlebars\Handlebars;
use Handlebars\Loader\FilesystemLoader;

class TemplateEngine
{


    /**
     * We use hbs and not html as extension because it permits
     * to have syntax highlighting in idea
     */
    const EXTENSION_HBS = "hbs";
    const CANONICAL = "theme";
    public const CONF_THEME_DEFAULT = "default";
    public const CONF_THEME = "combo-conf-005";


    private Handlebars $handleBarsForPage;
    /**
     * @var LocalPath[]
     */
    private array $templateSearchDirectories;
    /**
     * This path are wiki path because
     * they should be able to be accessed externally (fetched)
     * @var WikiPath[]
     */
    private array $componentCssSearchDirectories;

    /**
     * @var Handlebars for component
     */
    private Handlebars $handleBarsForComponents;


    static public function createForTheme(string $themeName): TemplateEngine
    {

        $handleBarsObjectId = "handlebar-theme-$themeName";
        $executionContext = ExecutionContext::getActualOrCreateFromEnv();

        try {
            return $executionContext->getRuntimeObject($handleBarsObjectId);
        } catch (ExceptionNotFound $e) {
            // not found
        }


        try {

            /**
             * Default
             */
            $default = self::CONF_THEME_DEFAULT;
            /**
             * @var WikiPath[] $componentsCssSearchDirectories
             */
            $componentsCssSearchDirectories = array(); // a list of directories where to search the component stylesheet
            $componentsHtmlSearchDirectories = array(); // a list of directories where to search the component html templates
            /**
             * @var LocalPath[] $templatesSearchDirectories
             */
            $templatesSearchDirectories = array(); // a list of directories where to search the template
            /**
             * @var LocalPath[] $partialSearchDirectories
             */
            $partialSearchDirectories = array(); // a list of directories where to search the partials
            if ($themeName !== $default) {
                $themeDirectory = self::getThemeHomeAsWikiPath()->resolve($themeName);
                $themePagesTemplateDirectory = $themeDirectory->resolve("pages:templates:")->toLocalPath();
                $themePagesPartialsDirectory = $themeDirectory->resolve("pages:partials:")->toLocalPath();
                $themeComponentsCssDirectory = $themeDirectory->resolve("components:css:");
                $themeComponentsHtmlDirectory = $themeDirectory->resolve("components:html:")->toLocalPath();
                if (PluginUtility::isTest()) {
                    try {
                        FileSystems::createDirectoryIfNotExists($themePagesTemplateDirectory);
                        FileSystems::createDirectoryIfNotExists($themePagesPartialsDirectory);
                    } catch (ExceptionCompile $e) {
                        throw new ExceptionRuntimeInternal($e);
                    }
                }

                if (FileSystems::exists($themePagesTemplateDirectory)) {
                    $templatesSearchDirectories[] = $themePagesTemplateDirectory;
                } else {
                    LogUtility::warning("The template theme directory ($themeDirectory) does not exists and was not taken into account");
                }
                if (FileSystems::exists($themePagesPartialsDirectory)) {
                    $partialSearchDirectories[] = $themePagesPartialsDirectory;
                } else {
                    LogUtility::warning("The partials theme directory ($themeDirectory) does not exists");
                }
                if (FileSystems::exists($themeComponentsCssDirectory)) {
                    $componentsCssSearchDirectories[] = $themeComponentsCssDirectory;
                }
                if (FileSystems::exists($themeComponentsHtmlDirectory)) {
                    $componentsHtmlSearchDirectories[] = $themeComponentsHtmlDirectory;
                }
            }

            /**
             * Default as last directory to search
             */
            $defaultTemplateDirectory = WikiPath::createComboResource(":theme:$default:pages:templates")->toLocalPath();
            $templatesSearchDirectories[] = $defaultTemplateDirectory;
            $partialSearchDirectories[] = WikiPath::createComboResource(":theme:$default:pages:partials")->toLocalPath();
            $componentsCssSearchDirectories[] = WikiPath::createComboResource(":theme:$default:components:css");
            $componentsHtmlSearchDirectories[] = WikiPath::createComboResource(":theme:$default:components:html")->toLocalPath();

            /**
             * Handlebars Page
             */
            $templatesSearchDirectoriesAsStringPath = array_map(function ($element) {
                return $element->toAbsoluteId();
            }, $templatesSearchDirectories);
            $partialSearchDirectoriesAsStringPath = array_map(function ($element) {
                return $element->toAbsoluteId();
            }, $partialSearchDirectories);
            $pagesTemplatesLoader = new FilesystemLoader($templatesSearchDirectoriesAsStringPath, ["extension" => self::EXTENSION_HBS]);
            $pagesPartialLoader = new FilesystemLoader($partialSearchDirectoriesAsStringPath, ["extension" => self::EXTENSION_HBS]);
            $handleBarsForPages = new Handlebars([
                "loader" => $pagesTemplatesLoader,
                "partials_loader" => $pagesPartialLoader
            ]);
            self::addHelper($handleBarsForPages);

            /**
             * Handlebars Html Component
             */
            $componentsHtmlSearchDirectoriesAsStringPath = array_map(function ($element) {
                return $element->toAbsoluteId();
            }, $componentsHtmlSearchDirectories);
            $componentsHtmlTemplatesLoader = new FilesystemLoader($componentsHtmlSearchDirectoriesAsStringPath, ["extension" => self::EXTENSION_HBS]);
            $handleBarsForComponents = new Handlebars([
                "loader" => $componentsHtmlTemplatesLoader,
                "partials_loader" => $componentsHtmlTemplatesLoader
            ]);

        } catch (ExceptionCast $e) {
            // should not happen as combo resource is a known directory but yeah
            throw ExceptionRuntimeInternal::withMessageAndError("Error while instantiating handlebars for page", $e);
        }


        $newPageTemplateEngine = new TemplateEngine();
        $newPageTemplateEngine->handleBarsForPage = $handleBarsForPages;
        $newPageTemplateEngine->handleBarsForComponents = $handleBarsForComponents;
        $newPageTemplateEngine->templateSearchDirectories = $templatesSearchDirectories;
        $newPageTemplateEngine->componentCssSearchDirectories = $componentsCssSearchDirectories;
        $executionContext->setRuntimeObject($handleBarsObjectId, $newPageTemplateEngine);
        return $newPageTemplateEngine;


    }

    static public function createForString(): TemplateEngine
    {

        $handleBarsObjectId = "handlebar-string";
        $executionContext = ExecutionContext::getActualOrCreateFromEnv();

        try {
            return $executionContext->getRuntimeObject($handleBarsObjectId);
        } catch (ExceptionNotFound $e) {
            // not found
        }


        $handleBars = new Handlebars();

        self::addHelper($handleBars);

        $newPageTemplateEngine = new TemplateEngine();
        $newPageTemplateEngine->handleBarsForPage = $handleBars;
        $executionContext->setRuntimeObject($handleBarsObjectId, $newPageTemplateEngine);
        return $newPageTemplateEngine;


    }

    private static function addHelper(Handlebars $handleBars)
    {
        $handleBars->addHelper("share",
            function ($template, $context, $args, $source) {
                $knownType = ShareTag::getKnownTypes();
                $tagAttributes = TagAttributes::createFromTagMatch("<share $args/>", [], $knownType);
                return ShareTag::renderSpecialEnter($tagAttributes, DOKU_LEXER_SPECIAL);
            }
        );
        /**
         * Used in test
         */
        $handleBars->addHelper("echo",
            function ($template, $context, $args, $source) {
                return "echo";
            }
        );
        /**
         * Hierachical breadcrumb
         */
        $handleBars->addHelper("breadcrumb",
            function ($template, Context $context, $args, $source) {
                $knownType = BreadcrumbTag::TYPES;
                $default = BreadcrumbTag::getDefaultBlockAttributes();
                $tagAttributes = TagAttributes::createFromTagMatch("<breadcrumb $args/>", $default, $knownType);
                return BreadcrumbTag::toBreadCrumbHtml($tagAttributes);
            }
        );

        /**
         * Page Image
         */
        $handleBars->addHelper("page-image",
            function ($template, Context $context, $args, $source) {
                $knownType = PageImageTag::TYPES;
                $default = PageImageTag::getDefaultAttributes();
                $tagAttributes = TagAttributes::createFromTagMatch("<page-image $args/>", $default, $knownType);
                return PageImageTag::render($tagAttributes,[]);
            }
        );
    }

    public static function createForDefaultTheme(): TemplateEngine
    {
        return self::createForTheme(self::CONF_THEME_DEFAULT);
    }

    public static function createFromContext(): TemplateEngine
    {
        $theme = ExecutionContext::getActualOrCreateFromEnv()
            ->getConfig()
            ->getTheme();
        return self::createForTheme($theme);
    }

    public static function getThemes(): array
    {
        $theme = [self::CONF_THEME_DEFAULT];
        $directories = FileSystems::getChildrenContainer(self::getThemeHomeAsWikiPath());
        foreach ($directories as $directory) {
            try {
                $theme[] = $directory->getLastName();
            } catch (ExceptionNotFound $e) {
                LogUtility::internalError("The theme home is not the root file system", self::CANONICAL, $e);
            }
        }
        return $theme;
    }

    /**
     * @return WikiPath - where the theme should be stored
     */
    private static function getThemeHomeAsWikiPath(): WikiPath
    {
        return WikiPath::getComboCustomThemeHomeDirectory();
    }


    public function renderWebPage(string $template, array $model): string
    {
        return $this->handleBarsForPage->render($template, $model);
    }

    public function renderWebComponent(string $template, array $model): string
    {
        return $this->handleBarsForComponents->render($template, $model);
    }

    /**
     * @return LocalPath[]
     * @throws ExceptionNotFound
     */
    public function getTemplateSearchDirectories(): array
    {
        if (isset($this->templateSearchDirectories)) {
            return $this->templateSearchDirectories;
        }
        throw new ExceptionNotFound("No template directory as this is not a file engine");

    }

    public function templateExists(string $templateName): bool
    {
        try {
            $this->handleBarsForPage->getLoader()->load($templateName);
            return true;
        } catch (\Exception $e) {
            return false;
        }

    }

    /**
     * Create a file template (used mostly for test purpose)
     * @param string $templateName - the name (without extension)
     * @param string|null $templateContent - the content
     * @return $this
     */
    public function createTemplate(string $templateName, string $templateContent = null): TemplateEngine
    {

        if (count($this->templateSearchDirectories) !== 2) {
            // only one, this is the default, we need two
            throw new ExceptionRuntimeInternal("We can create a template only in a custom theme directory");
        }
        $theme = $this->templateSearchDirectories[0];
        $templateFile = $theme->resolve($templateName . "." . self::EXTENSION_HBS);
        if ($templateContent === null) {
            $templateContent = <<<EOF
<html lang="en">
<head><title>{{ title }}</title></head>
<body>
<p>Test template</p>
</body>
</html>
EOF;
        }
        FileSystems::setContent($templateFile, $templateContent);
        return $this;
    }

    /**
     * @throws ExceptionNotFound
     */
    public function searchTemplateByName(string $name): LocalPath
    {
        foreach ($this->templateSearchDirectories as $templateSearchDirectory) {
            $file = $templateSearchDirectory->resolve($name);
            if (FileSystems::exists($file)) {
                return $file;
            }
        }
        throw new ExceptionNotFound("No file named $name found");
    }


    public function getComponentStylePathByName(string $nameWithExtenson): WikiPath
    {
        $file = null;
        foreach ($this->componentCssSearchDirectories as $componentSearchDirectory) {
            $file = $componentSearchDirectory->resolve($nameWithExtenson);
            if (FileSystems::exists($file)) {
                return $file;
            }
        }
        /**
         * We return the last one that should be the default theme
         */
        return $file;
    }

    public function getComponentTemplatePathByName(string $LOGICAL_TAG)
    {

    }


}
