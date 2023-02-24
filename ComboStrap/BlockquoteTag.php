<?php
/**
 * DokuWiki Syntax Plugin Combostrap.
 * Implementation of https://getbootstrap.com/docs/5.0/content/typography/#blockquotes
 *
 */

namespace ComboStrap;


use Doku_Handler;
use Doku_Renderer_xhtml;
use syntax_plugin_combo_cite;
use syntax_plugin_combo_header;
use syntax_plugin_combo_link;


/**
 * All DokuWiki plugins to extend the parser/rendering mechanism
 * need to inherit from this class
 *
 * The name of the class must follow a pattern (don't change it)
 * ie:
 *    syntax_plugin_PluginName_ComponentName
 */
class BlockquoteTag
{

    const TAG = "blockquote";

    /**
     * When the blockquote is a tweet
     */
    const TWEET = "tweet";
    const TWEET_SUPPORTED_LANG = array("en", "ar", "bn", "cs", "da", "de", "el", "es", "fa", "fi", "fil", "fr", "he", "hi", "hu", "id", "it", "ja", "ko", "msa", "nl", "no", "pl", "pt", "ro", "ru", "sv", "th", "tr", "uk", "ur", "vi", "zh-cn", "zh-tw");
    const CONF_TWEET_WIDGETS_THEME = "twitter:widgets:theme";
    const CONF_TWEET_WIDGETS_BORDER = "twitter:widgets:border-color";
    const TYPO_TYPE = "typo";
    const CARD_TYPE = "card";


    /**
     * @var mixed|string
     */
    static public $type = self::CARD_TYPE;


    static function handleEnter($handler): array
    {
        /**
         * Parent
         */
        $callStack = CallStack::createFromHandler($handler);
        $context = null;
        $parent = $callStack->moveToParent();
        if ($parent !== false) {
            $context = $parent->getTagName();
            if ($context === FragmentTag::FRAGMENT_TAG) {
                $parent = $callStack->moveToParent();
                if ($parent !== false) {
                    $context = $parent->getTagName();
                }
            }
        }

        return array(PluginUtility::CONTEXT => $context);

    }


    static public function handleExit(Doku_Handler $handler): array
    {
        $callStack = CallStack::createFromHandler($handler);

        /**
         * Check and add a scroll toggle if the
         * blockquote is constrained by height
         */
        Dimension::addScrollToggleOnClickIfNoControl($callStack);


        /**
         * Pre-parsing:
         *    Cite: A cite should be wrapped into a footer
         *          This should happens before the p processing because we
         *          are adding a {@link BoxTag} which is a stack
         *    Tweet blockquote: If a link has tweet link status, this is a tweet blockquote
         */
        $callStack->moveToEnd();
        $openingTag = $callStack->moveToPreviousCorrespondingOpeningCall();
        $tweetUrlFound = false;
        while ($actualCall = $callStack->next()) {
            if ($actualCall->getTagName() == syntax_plugin_combo_cite::TAG) {
                switch ($actualCall->getState()) {
                    case DOKU_LEXER_ENTER:
                        // insert before
                        $callStack->insertBefore(Call::createComboCall(
                            BoxTag::TAG,
                            DOKU_LEXER_ENTER,
                            array(
                                "class" => "blockquote-footer",
                                BoxTag::HTML_TAG_ATTRIBUTE => "footer"
                            ),
                            null,
                            null,
                            null,
                            null,
                            \syntax_plugin_combo_xmlblocktag::TAG
                        ));
                        break;
                    case DOKU_LEXER_EXIT:
                        // insert after
                        $callStack->insertAfter(Call::createComboCall(
                            BoxTag::TAG,
                            DOKU_LEXER_EXIT,
                            array(
                                BoxTag::HTML_TAG_ATTRIBUTE => "footer"
                            ),
                            null,
                            null,
                            null,
                            null,
                            \syntax_plugin_combo_xmlblocktag::TAG
                        ));
                        break;
                }
            }
            if (
                $actualCall->getTagName() == syntax_plugin_combo_link::TAG
                && $actualCall->getState() == DOKU_LEXER_ENTER
            ) {
                $ref = $actualCall->getAttribute(syntax_plugin_combo_link::MARKUP_REF_ATTRIBUTE);
                if (StringUtility::match($ref, "https:\/\/twitter.com\/[^\/]*\/status\/.*")) {
                    $tweetUrlFound = true;
                }
            }
        }
        if ($tweetUrlFound) {
            $context = BlockquoteTag::TWEET;
            $type = $context;
            $openingTag->setType($context);
            $openingTag->setContext($context);
        }

        /**
         * Because we can change the type above to tweet
         * we set them after
         */
        $type = $openingTag->getType();
        $context = $openingTag->getContext();
        if ($context === null) {
            $context = $type;
        }
        $attributes = $openingTag->getAttributes();

        /**
         * Create the paragraph
         */
        $callStack->moveToPreviousCorrespondingOpeningCall();
        $callStack->insertEolIfNextCallIsNotEolOrBlock(); // eol is mandatory to have a paragraph if there is only content
        $paragraphAttributes["class"] = "blockquote-text";
        if ($type == self::TYPO_TYPE) {
            $bootstrapVersion = Bootstrap::getBootStrapMajorVersion();
            if ($bootstrapVersion == Bootstrap::BootStrapFourMajorVersion) {
                // As seen here https://getbootstrap.com/docs/4.0/content/typography/#blockquotes
                $paragraphAttributes["class"] .= " mb-0";
                // not on 5 https://getbootstrap.com/docs/5.0/content/typography/#blockquotes
            }
        }
        $callStack->processEolToEndStack($paragraphAttributes);

        /**
         * Wrap the blockquote into a card
         *
         * In a blockquote card, a blockquote typo is wrapped around a card
         *
         * We add then:
         *   * at the body location: a card body start and a blockquote typo start
         *   * at the end location: a card end body and a blockquote end typo
         */
        if ($type == self::CARD_TYPE) {

            $callStack->moveToPreviousCorrespondingOpeningCall();
            $callEnterTypeCall = Call::createComboCall(
                self::TAG,
                DOKU_LEXER_ENTER,
                array(TagAttributes::TYPE_KEY => self::TYPO_TYPE),
                $context,
                null,
                null,
                null,
                \syntax_plugin_combo_xmlblocktag::TAG
            );
            $cardBodyEnterCall = CardTag::createCardBodyEnterCall($context);
            $firstChild = $callStack->moveToFirstChildTag();

            if ($firstChild !== false) {
                if ($firstChild->getTagName() == syntax_plugin_combo_header::TAG) {
                    $callStack->moveToNextSiblingTag();
                }
                // Head: Insert card body
                $callStack->insertBefore($cardBodyEnterCall);
                // Head: Insert Blockquote typo
                $callStack->insertBefore($callEnterTypeCall);

            } else {
                // No child
                // Move back
                $callStack->moveToEnd();;
                $callStack->moveToPreviousCorrespondingOpeningCall();
                // Head: Insert Blockquote typo
                $callStack->insertAfter($callEnterTypeCall);
                // Head: Insert card body
                $callStack->insertAfter($cardBodyEnterCall);
            }


            /**
             * End
             */
            // Insert the card body exit
            $callStack->moveToEnd();
            $callStack->insertBefore(
                Call::createComboCall(
                    self::TAG,
                    DOKU_LEXER_EXIT,
                    array("type" => self::TYPO_TYPE),
                    $context,
                    null,
                    null,
                    null,
                    \syntax_plugin_combo_xmlblocktag::TAG
                )
            );
            $callStack->insertBefore(CardTag::createCardBodyExitCall());
        }

        return array(
            PluginUtility::CONTEXT => $context,
            PluginUtility::ATTRIBUTES => $attributes
        );
    }

    public static function renderEnterXhtml(TagAttributes $tagAttributes, $data, $renderer): string
    {
        /**
         * Add the CSS
         */
        $snippetManager = PluginUtility::getSnippetManager();
        $snippetManager->attachCssInternalStyleSheet(self::TAG);

        /**
         * Create the HTML
         */
        $type = $tagAttributes->getType();
        switch ($type) {
            case self::TYPO_TYPE:

                $tagAttributes->addClassName("blockquote");
                $cardTags = [CardTag::CARD_TAG, MasonryTag::MASONRY_TAG];
                if (in_array($data[PluginUtility::CONTEXT], $cardTags)) {
                    // As seen here: https://getbootstrap.com/docs/5.0/components/card/#header-and-footer
                    // A blockquote in a card
                    // This context is added dynamically when the blockquote is a card type
                    $tagAttributes->addClassName("mb-0");
                }
                return $tagAttributes->toHtmlEnterTag("blockquote");

            case self::TWEET:

                try {
                    PluginUtility::getSnippetManager()
                        ->attachRemoteJavascriptLibrary(self::TWEET, "https://platform.twitter.com/widgets.js")
                        ->addHtmlAttribute("id", "twitter-wjs");
                } catch (ExceptionBadArgument|ExceptionBadSyntax $e) {
                    LogUtility::internalError("It should not happen as the url is written by ons (ie is a literal)", self::TAG, $e);
                }

                $tagAttributes->addClassName("twitter-tweet");

                $tweetAttributesNames = ["cards", "dnt", "conversation", "align", "width", "theme", "lang"];
                foreach ($tweetAttributesNames as $tweetAttributesName) {
                    if ($tagAttributes->hasComponentAttribute($tweetAttributesName)) {
                        $value = $tagAttributes->getValueAndRemove($tweetAttributesName);
                        $tagAttributes->addOutputAttributeValue("data-" . $tweetAttributesName, $value);
                    }
                }

                return $tagAttributes->toHtmlEnterTag("blockquote");

            case self::CARD_TYPE:
            default:

                /**
                 * Wrap with column
                 */
                $context = $data[PluginUtility::CONTEXT];
                if ($context === MasonryTag::MASONRY_TAG) {
                    MasonryTag::addColIfBootstrap5AndCardColumns($renderer, $context);
                }

                /**
                 * Starting the card
                 */
                $tagAttributes->addClassName(self::CARD_TYPE);
                return $tagAttributes->toHtmlEnterTag("div") . DOKU_LF;
            /**
             * The card body and blockquote body
             * of the example (https://getbootstrap.com/docs/4.0/components/card/#header-and-footer)
             * are added via call at
             * the {@link DOKU_LEXER_EXIT} state of {@link BlockquoteTag::handle()}
             */
        }
    }


    static function renderExitXhtml(TagAttributes $tagAttributes, Doku_Renderer_xhtml $renderer, array $data)
    {
        // Because we can have several unmatched on a line we don't know if
        // there is a eol
        StringUtility::addEolCharacterIfNotPresent($renderer->doc);
        $type = $tagAttributes->getValue(TagAttributes::TYPE_KEY);
        switch ($type) {
            case self::CARD_TYPE:
                $renderer->doc .= "</div>";
                break;
            case self::TWEET:
            case self::TYPO_TYPE:
            default:
                $renderer->doc .= "</blockquote>";
                break;
        }

        /**
         * Closing the masonry column
         * (Only if this is a card blockquote)
         */
        if ($type == CardTag::CARD_TAG) {
            $context = $data[PluginUtility::CONTEXT];
            if ($context === MasonryTag::MASONRY_TAG) {
                MasonryTag::endColIfBootstrap5AnCardColumns($renderer, $context);
            }
        }

    }


}
