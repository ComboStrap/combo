<?php

namespace ComboStrap;

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
        $handleBars->addHelper("echo",
            function ($template, $context, $args, $source) {
                return "echo";
            }
        );
        /**
         * Railbar is a helper
         * as the layout may be different
         * by page
         */
        $handleBars->addHelper("railbar",
            function ($template, $context, $args, $source) {
                $attributes = $context->get($args);
                $requestedPath = ExecutionContext::getActualOrCreateFromEnv()->getContextPath();
                return FetcherRailBar::createRailBar()
                    ->setRequestedPath($requestedPath)
                    ->setRequestedLayout($attributes)
                    ->getFetchString();
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
        if(isset($this->templateDirectory)){
            return $this->templateDirectory;
        }
        throw new ExceptionNotFound("No template directory");

    }


}
