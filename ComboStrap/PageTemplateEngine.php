<?php

namespace ComboStrap;

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
    const DEFAULT_THEME = "default";
    const CANONICAL = "handlebars";


    private Handlebars $handleBars;
    private LocalPath $templateDirectory;

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
            $templatesDirectory = WikiPath::createComboResource(":theme:$themeName:templates")->toLocalPath();
            $partialDirectory = WikiPath::createComboResource(":theme:$themeName:partials")->toLocalPath();

            /**
             * Handlebars Files
             */
            $templatesLoader = new FilesystemLoader($templatesDirectory->toAbsoluteString(), ["extension" => self::EXTENSION_HBS]);
            $partialLoader = new FilesystemLoader($partialDirectory->toAbsoluteString(), ["extension" => self::EXTENSION_HBS]);
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
        $newPageTemplateEngine->templateDirectory = $templatesDirectory;
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
                return ShareTag::render($tagAttributes, DOKU_LEXER_SPECIAL);
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


    public function render(string $template, array $model): string
    {
        return $this->handleBars->render($template, $model);
    }

    /**
     * @throws ExceptionNotFound
     */
    public function getTemplatesDirectory(): LocalPath
    {
        if (isset($this->templateDirectory)) {
            return $this->templateDirectory;
        }
        throw new ExceptionNotFound("No template directory");

    }


}
