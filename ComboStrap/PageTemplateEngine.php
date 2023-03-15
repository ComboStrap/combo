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
    public const CONF_THEME = "combo-conf-004";


    private Handlebars $handleBars;
    private LocalPath $templateDirectoryForJsAndCss;

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
            $templatesSearchDirectories = array(); // a list of directories where to search the template
            $partialSearchDirectories = array(); // a list of directories where to search the partials
            if ($themeName !== $default) {
                $themeDirectory = self::getThemeHome()->resolve($themeName);
                $themeTemplateDirectory = $themeDirectory->resolve("templates");
                $themePartialsDirectory = $themeDirectory->resolve("partials");
                if (PluginUtility::isTest()) {
                    try {
                        FileSystems::createDirectoryIfNotExists($themeTemplateDirectory);
                        FileSystems::createDirectoryIfNotExists($themePartialsDirectory);
                    } catch (ExceptionCompile $e) {
                        throw new ExceptionRuntimeInternal($e);
                    }
                }

                if (FileSystems::exists($themeTemplateDirectory)) {
                    $templatesSearchDirectories[] = $themeTemplateDirectory->toAbsoluteString();
                    $themeTemplateDirectoryForJsAndCss = $themeTemplateDirectory;
                } else {
                    LogUtility::warning("The template theme directory ($themeDirectory) does not exists and was not taken into account");
                }
                if (FileSystems::exists($themePartialsDirectory)) {
                    $partialSearchDirectories[] = $themePartialsDirectory->toAbsoluteString();
                } else {
                    LogUtility::warning("The partials theme directory ($themeDirectory) does not exists");
                }

            }

            /**
             * Default as last directory to search
             */
            $defaultTemplateDirectory = WikiPath::createComboResource(":theme:$default:templates")->toLocalPath();
            $templatesSearchDirectories[] = $defaultTemplateDirectory->toAbsoluteString();
            $partialSearchDirectories[] = WikiPath::createComboResource(":theme:$default:partials")->toLocalPath()->toAbsoluteString();
            if (!isset($themeTemplateDirectoryForJsAndCss)) {
                $themeTemplateDirectoryForJsAndCss = $defaultTemplateDirectory;
            }
            /**
             * Handlebars Files
             */
            $templatesLoader = new FilesystemLoader($templatesSearchDirectories, ["extension" => self::EXTENSION_HBS]);
            $partialLoader = new FilesystemLoader($partialSearchDirectories, ["extension" => self::EXTENSION_HBS]);
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
        $newPageTemplateEngine->templateDirectoryForJsAndCss = $themeTemplateDirectoryForJsAndCss;
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
        $directories = FileSystems::getChildrenContainer(self::getThemeHome());
        foreach ($directories as $directory) {
            try {
                $theme[] = $directory->getLastName();
            } catch (ExceptionNotFound $e) {
                LogUtility::internalError("The theme home is not the root file system", self::CANONICAL,$e);
            }
        }
        return $theme;
    }

    /**
     * @return LocalPath - where the theme should be stored
     */
    private static function getThemeHome(): LocalPath
    {
        return ExecutionContext::getActualOrCreateFromEnv()->getConfig()->getDataDirectory()->resolve("combo")->resolve("theme");
    }


    public function render(string $template, array $model): string
    {
        return $this->handleBars->render($template, $model);
    }

    /**
     * @throws ExceptionNotFound
     */
    public function getTemplatesDirectory(): LocalPath
    {
        if (isset($this->templateDirectoryForJsAndCss)) {
            return $this->templateDirectoryForJsAndCss;
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
     * @param string $templateName
     * @return $this
     * @throws ExceptionNotFound - if there is no template directory (ie string template engine)
     */
    public function createTemplate(string $templateName): PageTemplateEngine
    {
        $templateFile = $this->getTemplatesDirectory()->resolve($templateName . "." . self::EXTENSION_HBS);
        $html = <<<EOF
<html lang="en">
<head><title>Title</title></head>
<body>
<p>Test template</p>
</body>
</html>
EOF;

        FileSystems::setContent($templateFile, $html);
        return $this;
    }


}
