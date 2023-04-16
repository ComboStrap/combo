<?php

namespace ComboStrap;


use Doku_Handler;
use Doku_Renderer;


/**
 *
 * TODO: when level 3 of grid
 *   https://developer.mozilla.org/en-US/docs/Web/CSS/CSS_Grid_Layout/Masonry_Layout
 *
 */
class MasonryTag
{
    public const TEASER_COLUMNS_TAG = 'teaser-columns';
    /**
     * The Tag constant should be the exact same last name of the class
     * This is how we recognize a tag in the {@link \ComboStrap\CallStack}
     */
    public const MASONRY_TAG = "masonry";
    public const LOGICAL_TAG = "masonry";
    /**
     * The syntax tags
     */
    public const CARD_COLUMNS_TAG = "card-columns";
    /**
     * Same as commercial
     * https://isotope.metafizzy.co/
     *
     */
    public const MASONRY_SCRIPT_ID = "masonry";


    /**
     * In Bootstrap5, to support card-columns, we need masonry javascript and
     * a column
     * We close it as seen here:
     * https://getbootstrap.com/docs/5.0/examples/masonry/
     *
     * The column is open with the function {@link MasonryTag::addColIfBootstrap5AndCardColumns()}
     * @param Doku_Renderer $renderer
     * @param $context
     */
    public static function endColIfBootstrap5AnCardColumns(Doku_Renderer $renderer, $context)
    {

        /**
         * Bootstrap five does not include masonry
         * directly, we need to add a column
         * and we close it here
         */
        $bootstrapVersion = Bootstrap::getBootStrapMajorVersion();
        if ($bootstrapVersion == Bootstrap::BootStrapFiveMajorVersion) {
            $renderer->doc .= '</div>';
        }

    }

    public static function getSyntaxTags(): array
    {
        return array(self::MASONRY_TAG, self::CARD_COLUMNS_TAG, self::TEASER_COLUMNS_TAG);
    }

    /**
     * @param $renderer
     * @param $context Doku_Renderer
     *
     * Bootstrap five does not include masonry
     * directly, we need to add a column around the children {@link syntax_plugin_combo_card} and
     * {@link BlockquoteTag}
     * https://getbootstrap.com/docs/5.0/examples/masonry/
     *
     * The column is open with the function {@link syntax_plugin_combo_masonry::endColIfBootstrap5AndCardColumns()}
     *
     * TODO: do it programmatically by adding call with {@link \ComboStrap\CallStack}
     */
    public static function addColIfBootstrap5AndCardColumns(&$renderer, $context)
    {
        $bootstrapVersion = Bootstrap::getBootStrapMajorVersion();
        if ($bootstrapVersion == Bootstrap::BootStrapFiveMajorVersion && $context == MasonryTag::MASONRY_TAG) {
            $renderer->doc .= '<div class="col-sm-6 col-lg-4 mb-4">';
        }
    }

    public static function handleExit(Doku_Handler $handler)
    {
        /**
         * When the masonry is used in an iterator, the direct
         * context is lost
         */
        $callStack = CallStack::createFromHandler($handler);
        $callStack->moveToPreviousCorrespondingOpeningCall();
        while ($actualCall = $callStack->next()) {
            if (
                in_array($actualCall->getTagName(), [CardTag::CARD_TAG, BlockquoteTag::TAG])
                && in_array($actualCall->getState(), [DOKU_LEXER_ENTER, DOKU_LEXER_EXIT])
            ) {
                $actualCall->setContext(MasonryTag::MASONRY_TAG);
            }
        }

    }

    public static function renderEnterTag(): string
    {
        $bootstrapVersion = Bootstrap::getBootStrapMajorVersion();
        switch ($bootstrapVersion) {
            case 5:
                // No support for 5, we use Bs with their example
                // https://getbootstrap.com/docs/5.0/examples/masonry/
                // https://masonry.desandro.com/layout.html#responsive-layouts
                // https://masonry.desandro.com/extras.html#bootstrap
                // https://masonry.desandro.com/#initialize-with-vanilla-javascript
                PluginUtility::getSnippetManager()->attachRemoteJavascriptLibrary(
                    MasonryTag::MASONRY_SCRIPT_ID,
                    "https://cdn.jsdelivr.net/npm/masonry-layout@4.2.2/dist/masonry.pkgd.min.js",
                    "sha384-GNFwBvfVxBkLMJpYMOABq3c+d3KnQxudP/mGPkzpZSTYykLBNsZEnG2D9G/X/+7D"
                );
                PluginUtility::getSnippetManager()->attachJavascriptFromComponentId(MasonryTag::MASONRY_SCRIPT_ID);
                $masonryClass = MasonryTag::MASONRY_SCRIPT_ID;
                return "<div class=\"row $masonryClass\">";
            default:
                return '<div class="card-columns">' ;
        }
    }

    public static function renderExitHtml(): string
    {
        return '</div>';
    }
}

