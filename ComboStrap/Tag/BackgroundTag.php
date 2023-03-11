<?php

namespace ComboStrap\Tag;

use ComboStrap\BackgroundAttribute;
use ComboStrap\CallStack;
use ComboStrap\ColorRgb;
use ComboStrap\LinkMarkup;
use ComboStrap\PluginUtility;
use ComboStrap\Position;
use ComboStrap\TagAttributes;


/**
 * The {@link BackgroundTag background tag} does not render as HTML tag
 * but collects data to create a {@link BackgroundAttribute}
 * on the parent node
 */
class BackgroundTag
{

    public const MARKUP_LONG = "background";
    public const MARKUP_SHORT = "bg";

    /**
     * Function used in the special and enter tag
     * @param TagAttributes $attributes
     */
    public static function modifyColorAttributes(TagAttributes $attributes)
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
            $callStack->moveToPreviousCorrespondingOpeningCall();
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
         */
        $data[PluginUtility::ATTRIBUTES] = $backgroundAttributes->toCallStackArray();
        $data[PluginUtility::STATE] = $state;
        return $data;

    }

    /**
     * Print only any error
     */
    public static function renderHtml($data): string
    {

        if (isset($data[PluginUtility::EXIT_MESSAGE])) {
            $class = LinkMarkup::TEXT_ERROR_CLASS;
            $error = $data[PluginUtility::EXIT_MESSAGE];
            return "<p class=\"$class\">$error</p>" . DOKU_LF;
        }

        return "";
    }
}
