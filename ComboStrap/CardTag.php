<?php

namespace ComboStrap;

use Doku_Renderer_xhtml;
use syntax_plugin_combo_cardbody;
use syntax_plugin_combo_fragment;
use syntax_plugin_combo_header;
use syntax_plugin_combo_masonry;

/**
 * * Horizontal Card
 * https://getbootstrap.com/docs/4.3/components/card/#horizontal
 *
 * https://material.io/components/cards
 * [[https://getbootstrap.com/docs/5.0/components/card/|Bootstrap card]]
 */
class CardTag
{

    public const CANONICAL = CardTag::LOGICAL_TAG;
    public const CARD_TAG = 'card';
    public const CONF_ENABLE_SECTION_EDITING = "enableCardSectionEditing";
    const TEASER_TAG = 'teaser';
    const LOGICAL_TAG = self::CARD_TAG;


    public static function handleEnter(TagAttributes $tagAttributes, \Doku_Handler $handler): array
    {

        /** A card without context */
        $tagAttributes->addClassName("card");

        /**
         * Context
         */
        $callStack = CallStack::createFromHandler($handler);
        $parent = $callStack->moveToParent();
        $context = null;
        if ($parent !== false) {
            $context = $parent->getTagName();
            if ($context === syntax_plugin_combo_fragment::TAG) {
                $parent = $callStack->moveToParent();
                if ($parent !== false) {
                    $context = $parent->getTagName();
                }
            }
        }

        $returnedArray = array(
            PluginUtility::CONTEXT => $context
        );

        $id = ExecutionContext::getActualOrCreateFromEnv()
            ->getIdManager()
            ->generateNewHtmlIdForComponent(CardTag::CARD_TAG);
        $returnedArray[TagAttributes::ID_KEY] = $id;

        return $returnedArray;
    }

    public static function handleExit(\Doku_Handler $handler, $pos, $match): array
    {
        $callStack = CallStack::createFromHandler($handler);

        /**
         * Check and add a scroll toggle if the
         * card is constrained by height
         */
        Dimension::addScrollToggleOnClickIfNoControl($callStack);

        // Processing
        $callStack->moveToEnd();
        $previousOpening = $callStack->moveToPreviousCorrespondingOpeningCall();
        /**
         * Do we have an illustrative image ?
         *
         * Because the image is considered an inline component
         * We need to be careful to not wrap it into
         * a paragraph (when the {@link syntax_plugin_combo_para::fromEolToParagraphUntilEndOfStack() process is kicking)
         */
        while ($actualCall = $callStack->next()) {

            if ($actualCall->isUnMatchedEmptyCall()) {
                continue;
            }

            $tagName = $actualCall->getTagName();
            $imageTag = "image";
            if (in_array($tagName, Call::IMAGE_TAGS)) {
                $tagName = $imageTag;
            }
            switch ($tagName) {
                case $imageTag:
                    $actualCall->addClassName("card-img-top");
                    $actualCall->setType(FetcherSvg::ILLUSTRATION_TYPE);
                    $actualCall->addAttribute(MediaMarkup::LINKING_KEY, MediaMarkup::LINKING_NOLINK_VALUE);
                    if (!$actualCall->hasAttribute(Dimension::RATIO_ATTRIBUTE)) {
                        $actualCall->addAttribute(Dimension::RATIO_ATTRIBUTE, "16:9");
                    }
                    $actualCall->setDisplay(Call::BlOCK_DISPLAY);
                    // an image should stretch into the card
                    $actualCall->addCssStyle("max-width", "100%");
                    break 2;
                case "eol":
                    break;
                default:
                    break 2;

            }

        }
        /**
         * If there is an Header
         * go to the end
         */
        if ($actualCall->getTagName() === syntax_plugin_combo_header::TAG && $actualCall->getState() === DOKU_LEXER_ENTER) {
            while ($actualCall = $callStack->next()) {
                if (
                    $actualCall->getTagName() === syntax_plugin_combo_header::TAG
                    && $actualCall->getState() === DOKU_LEXER_EXIT) {
                    break;
                }
            }
        }
        /**
         * Insert card-body
         */
        $bodyCall = Call::createComboCall(
            syntax_plugin_combo_cardbody::TAG,
            DOKU_LEXER_ENTER
        );
        $insertBodyAfterThisCalls = PluginUtility::mergeAttributes(Call::IMAGE_TAGS, [syntax_plugin_combo_header::TAG]);
        if (in_array($actualCall->getTagName(), $insertBodyAfterThisCalls)) {

            $callStack->insertAfter($bodyCall);

        } else {
            /**
             * Body was reached
             */
            $callStack->insertBefore($bodyCall);
            /**
             * Previous because the next function (EOL processing)
             * should start from previous
             */
            $callStack->previous();
        }

        /**
         * Process the body
         */
        $callStack->insertEolIfNextCallIsNotEolOrBlock();
        $callStack->processEolToEndStack([TagAttributes::CLASS_KEY => "card-text"]);

        /**
         * Insert the card body exit
         */
        $callStack->insertBefore(
            Call::createComboCall(
                syntax_plugin_combo_cardbody::TAG,
                DOKU_LEXER_EXIT
            )
        );


        /**
         * File Section editing
         */
        if (Site::getConfValue(CardTag::CONF_ENABLE_SECTION_EDITING, 1)) {
            /**
             * +1 to go at the line ?
             */
            $endPosition = $pos + strlen($match) + 1;
            $position = $previousOpening->getFirstMatchedCharacterPosition();
            $id = $previousOpening->getIdOrDefault();
            $editButtonCall = EditButton::create("Edit Card $id")
                ->setStartPosition($position)
                ->setEndPosition($endPosition)
                ->toComboCallComboFormat();
            $callStack->moveToEnd();
            $callStack->insertBefore($editButtonCall);

        }

        return array(PluginUtility::CONTEXT => $previousOpening->getContext());
    }

    public static function renderEnterXhtml(TagAttributes $attributes, Doku_Renderer_xhtml $renderer, array $data): string
    {
        /**
         * Add the CSS
         */
        $snippetManager = PluginUtility::getSnippetManager();
        $snippetManager->attachCssInternalStyleSheet(CardTag::CARD_TAG);


        $context = $data[PluginUtility::CONTEXT];
        if ($context === syntax_plugin_combo_masonry::TAG) {
            syntax_plugin_combo_masonry::addColIfBootstrap5AndCardColumns($renderer, $context);
        }

        /**
         * Card
         */
        return $attributes->toHtmlEnterTag("div") ;
    }

    public static function handleExitXhtml(array $data, Doku_Renderer_xhtml $renderer)
    {
        /**
         * End card
         */
        $renderer->doc .= "</div>" . DOKU_LF;

        /**
         * End Masonry column if any
         * {@link syntax_plugin_combo_masonry::addColIfBootstrap5AndCardColumns()}
         */
        $context = $data[PluginUtility::CONTEXT];
        if ($context === syntax_plugin_combo_masonry::TAG) {
            syntax_plugin_combo_masonry::endColIfBootstrap5AnCardColumns($renderer, $context);
        }
    }
}
