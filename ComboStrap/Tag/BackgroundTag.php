<?php

namespace ComboStrap\Tag;

use ComboStrap\BackgroundAttribute;
use ComboStrap\CallStack;
use ComboStrap\ColorRgb;
use ComboStrap\Dimension;
use ComboStrap\FetcherSvg;
use ComboStrap\IFetcherAbs;
use ComboStrap\LinkMarkup;
use ComboStrap\MarkupRef;
use ComboStrap\MediaMarkup;
use ComboStrap\PluginUtility;
use ComboStrap\Position;
use ComboStrap\TagAttributes;
use Doku_Renderer_metadata;
use syntax_plugin_combo_media;


/**
 * The {@link BackgroundTag background tag} does not render as HTML tag
 * but collects data to create a {@link BackgroundAttribute}
 * on the parent node
 *
 * Implementation of a background
 *
 *
 * Cool calm example of moving square background
 * https://codepen.io/Lewitje/pen/BNNJjo
 * Particles.js
 * https://codepen.io/akey96/pen/oNgeQYX
 * Gradient positioning above a photo
 * https://codepen.io/uzoawili/pen/GypGOy
 * Fire flies
 * https://codepen.io/mikegolus/pen/Jegvym
 *
 * z-index:100 could also be on the front
 * https://developer.mozilla.org/en-US/docs/Web/CSS/CSS_Positioning/Understanding_z_index/Stacking_without_z-index
 * https://getbootstrap.com/docs/5.0/layout/z-index/
 */
class BackgroundTag
{

    public const MARKUP_LONG = "background";
    public const MARKUP_SHORT = "bg";
    const LOGICAL_TAG = self::MARKUP_LONG;

    /**
     * Function used in the special and enter tag
     * @param TagAttributes $attributes
     */
    public static function handleEnterAndSpecial(TagAttributes $attributes)
    {

        $color = $attributes->getValueAndRemoveIfPresent(ColorRgb::COLOR);
        if ($color !== null) {
            $attributes->addComponentAttributeValue(BackgroundAttribute::BACKGROUND_COLOR, $color);
        }

    }

    /**
     * @param CallStack $callStack
     * @param TagAttributes $backgroundAttributes
     * @param $state
     * @return array
     */
    public static function setAttributesToParentAndReturnData(CallStack $callStack, TagAttributes $backgroundAttributes, $state): array
    {

        /**
         * The data array
         */
        $data = array();

        /**
         * Set the backgrounds attributes
         * to the parent
         * There is two state (special and exit)
         * Go to the opening call if in exit
         */
        if ($state == DOKU_LEXER_EXIT) {
            $callStack->moveToEnd();
           $openingCall =  $callStack->moveToPreviousCorrespondingOpeningCall();
        }
        $parentCall = $callStack->moveToParent();

        /** @noinspection PhpPointlessBooleanExpressionInConditionInspection */
        if ($parentCall != false) {
            if ($parentCall->getTagName() == BackgroundAttribute::BACKGROUNDS) {
                /**
                 * The backgrounds node
                 * (is already relative)
                 */
                $parentCall = $callStack->moveToParent();
            } else {
                /**
                 * Another parent node
                 * With a image background, the node should be relative
                 */
                if ($backgroundAttributes->hasComponentAttribute(BackgroundAttribute::BACKGROUND_IMAGE)) {
                    $parentCall->addAttribute(Position::POSITION_ATTRIBUTE, "relative");
                }
            }
            $backgrounds = $parentCall->getAttribute(BackgroundAttribute::BACKGROUNDS);
            if ($backgrounds == null) {
                $backgrounds = [$backgroundAttributes->toCallStackArray()];
            } else {
                $backgrounds[] = $backgroundAttributes->toCallStackArray();
            }
            $parentCall->addAttribute(BackgroundAttribute::BACKGROUNDS, $backgrounds);

        } else {
            $data[PluginUtility::EXIT_MESSAGE] = "A background should have a parent";
        }

        /**
         * Return the image data for the metadata
         * (Metadat is taken only from enter/exit)
         */
        if ($state === DOKU_LEXER_EXIT && isset($openingCall)) {
            // exit state
            $backgroundImage = $backgroundAttributes->getComponentAttributeValue(BackgroundAttribute::BACKGROUND_IMAGE);
            $openingCall->setAttribute(BackgroundAttribute::BACKGROUND_IMAGE, $backgroundImage);
        } else {
            // special state
            $data[PluginUtility::ATTRIBUTES] = $backgroundAttributes->toCallStackArray();
        }
        return $data;

    }

    /**
     * Print only any error
     */
    public static function renderExitSpecialHtml($data): string
    {

        if (isset($data[PluginUtility::EXIT_MESSAGE])) {
            $class = LinkMarkup::TEXT_ERROR_CLASS;
            $error = $data[PluginUtility::EXIT_MESSAGE];
            return "<p class=\"$class\">$error</p>" . DOKU_LF;
        }

        return "";
    }

    public static function handleExit($handler): array
    {
        $callStack = CallStack::createFromHandler($handler);
        $openingTag = $callStack->moveToPreviousCorrespondingOpeningCall();
        $backgroundAttributes = TagAttributes::createFromCallStackArray($openingTag->getAttributes())
            ->setLogicalTag(BackgroundTag::LOGICAL_TAG);

        /**
         * if the media syntax of Combo is not used, try to retrieve the media of dokuwiki
         */
        $imageTag = [syntax_plugin_combo_media::TAG, MediaMarkup::INTERNAL_MEDIA_CALL_NAME];

        /**
         * Collect the image if any
         */
        while ($actual = $callStack->next()) {

            $tagName = $actual->getTagName();
            if (in_array($tagName, $imageTag)) {
                $imageAttribute = $actual->getAttributes();
                if ($tagName == syntax_plugin_combo_media::TAG) {
                    $backgroundImageAttribute = BackgroundAttribute::fromMediaToBackgroundImageStackArray($imageAttribute);

                    /**
                     * Hack for tile svg
                     */
                    $fill = $openingTag->getAttribute(BackgroundAttribute::BACKGROUND_FILL);
                    if ($fill === FetcherSvg::TILE_TYPE) {
                        $ref = $backgroundImageAttribute[MarkupRef::REF_ATTRIBUTE];
                        if (!str_contains($ref, TagAttributes::TYPE_KEY) && str_contains($ref, "svg")) {
                            if (str_contains($ref, "?")) {
                                $ref = "$ref&type=$fill";
                            } else {
                                $ref = "$ref?type=$fill";
                            }
                        }
                        $backgroundImageAttribute[MarkupRef::REF_ATTRIBUTE] = $ref;
                    }
                } else {
                    /**
                     * As seen in {@link Doku_Handler::media()}
                     */
                    $backgroundImageAttribute = [
                        MediaMarkup::MEDIA_DOKUWIKI_TYPE => MediaMarkup::INTERNAL_MEDIA_CALL_NAME,
                        MediaMarkup::DOKUWIKI_SRC => $imageAttribute[0],
                        Dimension::WIDTH_KEY => $imageAttribute[3],
                        Dimension::HEIGHT_KEY => $imageAttribute[4],
                        IFetcherAbs::CACHE_KEY => $imageAttribute[5]
                    ];
                }
                $backgroundAttributes->addComponentAttributeValue(BackgroundAttribute::BACKGROUND_IMAGE, $backgroundImageAttribute);
                $callStack->deleteActualCallAndPrevious();
            }
        }
        return BackgroundTag::setAttributesToParentAndReturnData($callStack, $backgroundAttributes, DOKU_LEXER_EXIT);
    }

    public static function renderEnterTag(): string
    {
        /**
         * background is printed via the {@link BackgroundAttribute::processBackgroundAttributes()}
         */
        return "";
    }

    public static function renderMeta(array $data, Doku_Renderer_metadata $renderer)
    {

        $attributes = $data[PluginUtility::ATTRIBUTES];
        if (isset($attributes[BackgroundAttribute::BACKGROUND_IMAGE])) {
            $image = $attributes[BackgroundAttribute::BACKGROUND_IMAGE];
            syntax_plugin_combo_media::registerImageMeta($image, $renderer);
        }

    }
}
