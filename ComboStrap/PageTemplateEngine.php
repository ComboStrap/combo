<?php

namespace ComboStrap;

use ComboStrap\Tag\ShareTag;
use Handlebars\Context;
use Handlebars\Handlebars;
use Handlebars\Loader\FilesystemLoader;

class PageTemplateEngine
{


    /**
     * We use hbs and not html as extension because it permits
     * to have syntax highlighting in idea
     */
    const EXTENSION_HBS = "hbs";
    const CANONICAL = "theme";
    public const CONF_THEME_DEFAULT = "default";
    public const CONF_THEME = "combo-conf-005";


    private Handlebars $handleBars;
    /**
     * @var LocalPath[]
     */
    private array $templateSearchDirectories;
    /**
     * This path are wiki path because
     * they should be able to be accessed externally (fetched)
     * @var WikiPath[]
     */
    private array $componentSearchDirectories;

    static public function createForTheme(string $themeName): PageTemplateEngine
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
             * @var WikiPath[] $componentsSearchDirectories
             */
            $componentsSearchDirectories = array(); // a list of directories where to search the component stylesheet
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
                $themeTemplateDirectory = $themeDirectory->resolve("templates:")->toLocalPath();
                $themePartialsDirectory = $themeDirectory->resolve("partials:")->toLocalPath();
                $themeComponentsDirectory = $themeDirectory->resolve("components:");
                if (PluginUtility::isTest()) {
                    try {
                        FileSystems::createDirectoryIfNotExists($themeTemplateDirectory);
                        FileSystems::createDirectoryIfNotExists($themePartialsDirectory);
                    } catch (ExceptionCompile $e) {
                        throw new ExceptionRuntimeInternal($e);
                    }
                }

                if (FileSystems::exists($themeTemplateDirectory)) {
                    $templatesSearchDirectories[] = $themeTemplateDirectory;
                } else {
                    LogUtility::warning("The template theme directory ($themeDirectory) does not exists and was not taken into account");
                }
                if (FileSystems::exists($themePartialsDirectory)) {
                    $partialSearchDirectories[] = $themePartialsDirectory;
                } else {
                    LogUtility::warning("The partials theme directory ($themeDirectory) does not exists");
                }
                if (FileSystems::exists($themeComponentsDirectory)) {
                    $componentsSearchDirectories[] = $themeComponentsDirectory;
                }
            }

            /**
             * Default as last directory to search
             */
            $defaultTemplateDirectory = WikiPath::createComboResource(":theme:$default:templates")->toLocalPath();
            $templatesSearchDirectories[] = $defaultTemplateDirectory;
            $partialSearchDirectories[] = WikiPath::createComboResource(":theme:$default:partials")->toLocalPath();
            $componentsSearchDirectories[] = WikiPath::createComboResource(":theme:$default:components");

            /**
             * Handlebars Files
             */
            $templatesSearchDirectoriesAsStringPath = array_map(function ($element) {
                return $element->toAbsoluteId();
            }, $templatesSearchDirectories);
            $partialSearchDirectoriesAsStringPath = array_map(function ($element) {
                return $element->toAbsoluteId();
            }, $partialSearchDirectories);
            $templatesLoader = new FilesystemLoader($templatesSearchDirectoriesAsStringPath, ["extension" => self::EXTENSION_HBS]);
            $partialLoader = new FilesystemLoader($partialSearchDirectoriesAsStringPath, ["extension" => self::EXTENSION_HBS]);
            $handleBars = new Handlebars([
                "loader" => $templatesLoader,
                "partials_loader" => $partialLoader
            ]);

        } catch (ExceptionCast $e) {
            // should not happen as combo resource is a known directory but yeah
            throw ExceptionRuntimeInternal::withMessageAndError("Error while instantiating handlebars", $e);
        }
        self::addHelper($handleBars);

        $newPageTemplateEngine = new PageTemplateEngine();
        $newPageTemplateEngine->handleBars = $handleBars;
        $newPageTemplateEngine->templateSearchDirectories = $templatesSearchDirectories;
        $newPageTemplateEngine->componentSearchDirectories = $componentsSearchDirectories;
        $executionContext->setRuntimeObject($handleBarsObjectId, $newPageTemplateEngine);
        return $newPageTemplateEngine;


    }

    static public function createForString(): PageTemplateEngine
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

        $newPageTemplateEngine = new PageTemplateEngine();
        $newPageTemplateEngine->handleBars = $handleBars;
        $executionContext->setRuntimeObject($handleBarsObjectId, $newPageTemplateEngine);
        return $newPageTemplateEngine;


    }

    private static function addHelper(Handlebars $handleBars)
    {
        $handleBars->addHelper("share",
            function ($template, $context, $args, $source) {
                $attributes = $context->get($args);
                $knownType = ShareTag::getKnownTypes();
                $tagAttributes = TagAttributes::createFromTagMatch("<share $attributes/>", [], $knownType);
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
                $attributes = $context->get($args);
                $knownType = BreadcrumbTag::TYPES;
                $default = BreadcrumbTag::getDefaultBlockAttributes();
                $tagAttributes = TagAttributes::createFromTagMatch("<breadcrumb $attributes/>", $default, $knownType);
                return BreadcrumbTag::toBreadCrumbHtml($tagAttributes);
            }
        );
    }

    public static function createForDefaultTheme(): PageTemplateEngine
    {
        return self::createForTheme(self::CONF_THEME_DEFAULT);
    }

    public static function createFromContext(): PageTemplateEngine
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


    public function render(string $template, array $model): string
    {
        return $this->handleBars->render($template, $model);
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
            $this->handleBars->getLoader()->load($templateName);
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
    public function createTemplate(string $templateName, string $templateContent = null): PageTemplateEngine
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
        foreach ($this->componentSearchDirectories as $componentSearchDirectory) {
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


}
