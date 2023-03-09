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
            $partialsDirectory = WikiPath::createComboResource(":theme:$themeName")->toLocalPath()->toAbsoluteString();

            /**
             * Handlebars Files
             */
            $partialsLoader = new FilesystemLoader($partialsDirectory, ["extension" => self::EXTENSION_HBS]);
            $handleBars = new Handlebars([
                "loader" => $partialsLoader,
                "partials_loader" => $partialsLoader
            ]);

        } catch (ExceptionCast $e) {
            // should not happen as combo resource is a known directory but yeah
            throw ExceptionRuntimeInternal::withMessageAndError("Error while instantiating handlebars", $e);
        }
        self::addHelper($handleBars);

        $newPageTemplateEngine = new PageTemplateEngine();
        $newPageTemplateEngine->handleBars = $handleBars;
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


}
