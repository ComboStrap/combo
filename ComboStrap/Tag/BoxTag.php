<?php

namespace ComboStrap\Tag;


use ComboStrap\Call;
use ComboStrap\CallStack;
use ComboStrap\Dimension;
use ComboStrap\LogUtility;
use ComboStrap\PluginUtility;
use ComboStrap\TagAttribute\Align;
use ComboStrap\TagAttributes;
use Doku_Handler;

/**
 * Class syntax_plugin_combo_box
 * Implementation of a div
 * It permits also to dynamically add html element from other component
 * via the {@link Call::createComboCall()}
 *
 */
class BoxTag
{

    const TAG = "box";


    // the logical tag applied (class)
    const LOGICAL_TAG_ATTRIBUTE = "logical-tag";
    const LOGICAL_TAG_DEFAUT = self::TAG;
    // the html tag
    const HTML_TAG_ATTRIBUTE = "html-tag";
    const DEFAULT_HTML_TAG = "div";
    // Tag that may make external http requests are not authorized
    const NON_AUTHORIZED_HTML_TAG = ["script", "style", "img", "video"];

    public static function handleEnter(TagAttributes $attributes)
    {
        $tag = $attributes->getValue(self::HTML_TAG_ATTRIBUTE);
        if (in_array($tag, self::NON_AUTHORIZED_HTML_TAG)) {
            LogUtility::error("The html tag ($tag) is not authorized.");
            $attributes->setComponentAttributeValue(self::HTML_TAG_ATTRIBUTE, self::DEFAULT_HTML_TAG);
        }
    }


    static function handleExit(Doku_Handler $handler): array
    {

        $callStack = CallStack::createFromHandler($handler);

        /**
         * Check children align
         */
        $openingTag = $callStack->moveToPreviousCorrespondingOpeningCall();
        $align = $openingTag->getAttribute(Align::ALIGN_ATTRIBUTE);
        if ($align !== null && strpos($align, "children") !== false) {

            /**
             * Scan to see the type of content
             * children can be use against one inline element or more block element
             * Not against more than one inline children element
             *
             * Retrieve the number of inline element
             * ie enter, special and box unmatched
             */
            $inlineTagFounds = [];
            while ($actual = $callStack->next()) {

                switch ($actual->getState()) {
                    case DOKU_LEXER_EXIT:
                        continue 2;
                    case DOKU_LEXER_UNMATCHED:
                        if ($actual->getTagName() !== self::TAG) {
                            continue 2;
                        } else {
                            // Not a problem is the text are only space
                            if (trim($actual->getCapturedContent()) === "") {
                                continue 2;
                            }
                        }
                }
                if ($actual->getDisplay() == Call::INLINE_DISPLAY) {
                    $tagName = $actual->getTagName();
                    if ($actual->getTagName() === self::TAG && $actual->getState() === DOKU_LEXER_UNMATCHED) {
                        $tagName = "$tagName text";
                    }
                    $inlineTagFounds[] = $tagName;
                }
            }
            if (count($inlineTagFounds) > 1) {
                // You can't use children align value against inline
                LogUtility::warning("The `children` align attribute ($align) on the box component was apply against more than one inline elements (ie " . implode(", ", $inlineTagFounds) . "). If you don't get what you want use a text align value such as `text-center`");
            }
        }

        /**
         * Add a scroll toggle if the
         * box is constrained by height
         */
        Dimension::addScrollToggleOnClickIfNoControl($callStack);

        /**
         *
         */
        return array(
            PluginUtility::ATTRIBUTES => $openingTag->getAttributes()
        );


    }

    static public function renderEnterXhtml(TagAttributes $tagAttributes): string
    {
        $htmlTagName = $tagAttributes->getValueAndRemove(self::HTML_TAG_ATTRIBUTE, self::DEFAULT_HTML_TAG);
        $logicalTag = $tagAttributes->getValueAndRemove(self::LOGICAL_TAG_ATTRIBUTE);
        if ($logicalTag !== null) {
            $tagAttributes->setLogicalTag($logicalTag);
        }
        return $tagAttributes->toHtmlEnterTag($htmlTagName);
    }


    static function renderExitXhtml(TagAttributes $tagAttributes): string
    {
        $tagName = $tagAttributes->getValueAndRemove(self::HTML_TAG_ATTRIBUTE, self::DEFAULT_HTML_TAG);
        return "</$tagName>";
    }


}

