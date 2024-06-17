<?php

namespace ComboStrap;


use ComboStrap\Meta\Field\PageH1;
use Doku_Renderer_metadata;
use Doku_Renderer_xhtml;
use renderer_plugin_combo_analytics;
use syntax_plugin_combo_webcode;

class HeadingTag
{

    /**
     * The type of the heading tag
     */
    public const DISPLAY_TYPES = ["d1", "d2", "d3", "d4", "d5", "d6"];
    public const HEADING_TYPES = ["h1", "h2", "h3", "h4", "h5", "h6"];
    public const SHORT_TYPES = ["1", "2", "3", "4", "5", "6"];

    /**
     * The type of the title tag
     * @deprecated
     */
    public const TITLE_DISPLAY_TYPES = ["0", "1", "2", "3", "4", "5", "6"];


    /**
     * An heading may be printed
     * as outline and should be in the toc
     */
    public const TYPE_OUTLINE = "outline";
    public const CANONICAL = "heading";

    public const SYNTAX_TYPE = 'baseonly';
    public const SYNTAX_PTYPE = 'block';
    /**
     * The section generation:
     *   - Dokuwiki section (ie div just after the heading)
     *   - or Combo section (ie section just before the heading)
     */
    public const CONF_SECTION_LAYOUT = 'section_layout';
    public const CONF_SECTION_LAYOUT_VALUES = [HeadingTag::CONF_SECTION_LAYOUT_COMBO, HeadingTag::CONF_SECTION_LAYOUT_DOKUWIKI];
    public const CONF_SECTION_LAYOUT_DEFAULT = HeadingTag::CONF_SECTION_LAYOUT_COMBO;
    public const LEVEL = 'level';
    public const CONF_SECTION_LAYOUT_DOKUWIKI = "dokuwiki";
    /**
     *  old tag
     */
    public const TITLE_TAG = "title";
    /**
     * New tag
     */
    public const HEADING_TAG = "heading";
    public const LOGICAL_TAG = self::HEADING_TAG;


    /**
     * The default level if not set
     * Not level 1 because this is the top level heading
     * Not level 2 because this is the most used level and we can confound with it
     */
    public const DEFAULT_LEVEL_TITLE_CONTEXT = "3";
    public const DISPLAY_BS_4_RESPONSIVE_SNIPPET_ID = "display-bs-4";
    /**
     * The attribute that holds only the text of the heading
     * (used to create the id and the text in the toc)
     */
    public const HEADING_TEXT_ATTRIBUTE = "heading_text";
    public const CONF_SECTION_LAYOUT_COMBO = "combo";


    /**
     * 1 because in test if used without any, this is
     * the first expected one in a outline
     */
    public const DEFAULT_LEVEL_OUTLINE_CONTEXT = "1";
    public const TYPE_TITLE = "title";
    public const TAGS = [HeadingTag::HEADING_TAG, HeadingTag::TITLE_TAG];
    /**
     * only available in 5
     */
    public const DISPLAY_TYPES_ONLY_BS_5 = ["d5", "d6"];

    /**
     * The label is the text that is generally used
     * in a TOC but also as default title for the page
     */
    public const PARSED_LABEL = "label";


    /**
     * A common function used to handle exit of headings
     * @param \Doku_Handler $handler
     * @return array
     */
    public static function handleExit(\Doku_Handler $handler): array
    {

        $callStack = CallStack::createFromHandler($handler);

        /**
         * Delete the last space if any
         */
        $callStack->moveToEnd();
        $previous = $callStack->previous();
        if ($previous->getState() == DOKU_LEXER_UNMATCHED) {
            $previous->setPayload(rtrim($previous->getCapturedContent()));
        }
        $callStack->next();

        /**
         * Get context data
         */
        $openingTag = $callStack->moveToPreviousCorrespondingOpeningCall();
        $openingAttributes = $openingTag->getAttributes(); // for level
        $context = $openingTag->getContext(); // for sectioning

        return array(
            PluginUtility::STATE => DOKU_LEXER_EXIT,
            PluginUtility::ATTRIBUTES => $openingAttributes,
            PluginUtility::CONTEXT => $context
        );
    }

    /**
     * @param CallStack $callStack
     * @return string
     */
    public static function getContext(CallStack $callStack): string
    {

        /**
         * If the heading is inside a component,
         * it's a title heading, otherwise it's a outline heading
         *
         * (Except for {@link syntax_plugin_combo_webcode} that can wrap several outline heading)
         *
         * When the parent is empty, a section_open (ie another outline heading)
         * this is a outline
         */
        $parent = $callStack->moveToParent();
        if ($parent && $parent->getTagName() === Tag\WebCodeTag::TAG) {
            $parent = $callStack->moveToParent();
        }
        if ($parent && $parent->getComponentName() !== "section_open") {
            $headingType = self::TYPE_TITLE;
        } else {
            $headingType = self::TYPE_OUTLINE;
        }

        switch ($headingType) {
            case HeadingTag::TYPE_TITLE:

                $context = $parent->getTagName();
                break;

            case HeadingTag::TYPE_OUTLINE:

                $context = HeadingTag::TYPE_OUTLINE;
                break;

            default:
                LogUtility::msg("The heading type ($headingType) is unknown");
                $context = "";
                break;
        }
        return $context;
    }

    /**
     * @param $data
     * @param Doku_Renderer_metadata $renderer
     */
    public static function processHeadingEnterMetadata($data, Doku_Renderer_metadata $renderer)
    {

        $state = $data[PluginUtility::STATE];
        if (!in_array($state, [DOKU_LEXER_ENTER, DOKU_LEXER_SPECIAL])) {
            return;
        }
        /**
         * Only outline heading metadata
         * Not component heading
         */
        $context = $data[PluginUtility::CONTEXT];
        if ($context === self::TYPE_OUTLINE) {

            $callStackArray = $data[PluginUtility::ATTRIBUTES];
            $tagAttributes = TagAttributes::createFromCallStackArray($callStackArray);
            $text = $tagAttributes->getValue(HeadingTag::HEADING_TEXT_ATTRIBUTE);
            if ($text !== null) {
                $text = trim($text);
            }
            $level = $tagAttributes->getValue(HeadingTag::LEVEL);
            $pos = 0; // mandatory for header but not for metadata, we set 0 to make the code analyser happy
            $renderer->header($text, $level, $pos);

            if ($level === 1) {
                $parsedLabel = $tagAttributes->getValue(self::PARSED_LABEL);
                $renderer->meta[PageH1::H1_PARSED] = $parsedLabel;
            }

        }


    }

    public static function processMetadataAnalytics(array $data, renderer_plugin_combo_analytics $renderer)
    {

        $state = $data[PluginUtility::STATE];
        if ($state !== DOKU_LEXER_ENTER) {
            return;
        }
        /**
         * Only outline heading metadata
         * Not component heading
         */
        $context = $data[PluginUtility::CONTEXT] ?? null;
        if ($context === self::TYPE_OUTLINE) {
            $callStackArray = $data[PluginUtility::ATTRIBUTES];
            $tagAttributes = TagAttributes::createFromCallStackArray($callStackArray);
            $text = $tagAttributes->getValue(HeadingTag::HEADING_TEXT_ATTRIBUTE);
            $level = $tagAttributes->getValue(HeadingTag::LEVEL);
            $renderer->header($text, $level, 0);
        }

    }

    /**
     * @param string $context
     * @param TagAttributes $tagAttributes
     * @param Doku_Renderer_xhtml $renderer
     * @param int|null $pos - null if the call was generated
     * @return void
     */
    public static function processRenderEnterXhtml(string $context, TagAttributes $tagAttributes, Doku_Renderer_xhtml &$renderer, ?int $pos)
    {

        /**
         * All correction that are dependent
         * on the markup (ie title or heading)
         * are done in the {@link self::processRenderEnterXhtml()}
         */

        /**
         * Variable
         */
        $type = $tagAttributes->getType();

        /**
         * Old syntax deprecated
         */
        if ($type === "0") {
            if ($context === self::TYPE_OUTLINE) {
                $type = 'h' . self::DEFAULT_LEVEL_OUTLINE_CONTEXT;
            } else {
                $type = 'h' . self::DEFAULT_LEVEL_TITLE_CONTEXT;
            }
        }
        /**
         * Label is for the TOC
         */
        $tagAttributes->removeAttributeIfPresent(self::PARSED_LABEL);


        /**
         * Level
         */
        $level = $tagAttributes->getValueAndRemove(HeadingTag::LEVEL);

        /**
         * Display Heading
         * https://getbootstrap.com/docs/5.0/content/typography/#display-headings
         */
        if ($context !== self::TYPE_OUTLINE && $type === null) {
            /**
             * if not an outline, a display
             */
            $type = "h$level";
        }
        if (in_array($type, self::DISPLAY_TYPES)) {

            $displayClass = "display-$level";

            if (Bootstrap::getBootStrapMajorVersion() == Bootstrap::BootStrapFourMajorVersion) {
                /**
                 * Make Bootstrap display responsive
                 */
                PluginUtility::getSnippetManager()->attachCssInternalStyleSheet(HeadingTag::DISPLAY_BS_4_RESPONSIVE_SNIPPET_ID);

                if (in_array($type, self::DISPLAY_TYPES_ONLY_BS_5)) {
                    $displayClass = "display-4";
                    LogUtility::msg("Bootstrap 4 does not support the type ($type). Switch to " . PluginUtility::getDocumentationHyperLink(Bootstrap::CANONICAL, "bootstrap 5") . " if you want to use it. The display type was set to `d4`", LogUtility::LVL_MSG_WARNING, self::CANONICAL);
                }

            }
            $tagAttributes->addClassName($displayClass);
        }

        /**
         * Heading class
         * https://getbootstrap.com/docs/5.0/content/typography/#headings
         * Works on 4 and 5
         */
        if (in_array($type, self::HEADING_TYPES)) {
            $tagAttributes->addClassName($type);
        }

        /**
         * Card title Context class
         * TODO: should move to card
         */
        if (in_array($context, [BlockquoteTag::TAG, CardTag::CARD_TAG])) {
            $tagAttributes->addClassName("card-title");
        }

        $executionContext = ExecutionContext::getActualOrCreateFromEnv();

        /**
         * Add an outline class to be able to style them at once
         *
         * The context is by default the parent name or outline.
         */
        $snippetManager = SnippetSystem::getFromContext();
        if ($context === self::TYPE_OUTLINE) {

            $tagAttributes->addClassName(Outline::getOutlineHeadingClass());

            $snippetManager->attachCssInternalStyleSheet(self::TYPE_OUTLINE);

            // numbering
            try {

                $enable = $executionContext
                    ->getConfig()
                    ->getValue(Outline::CONF_OUTLINE_NUMBERING_ENABLE, Outline::CONF_OUTLINE_NUMBERING_ENABLE_DEFAULT);
                if ($enable) {
                    $snippet = $snippetManager->attachCssInternalStyleSheet(Outline::OUTLINE_HEADING_NUMBERING);
                    if (!$snippet->hasInlineContent()) {
                        $css = Outline::getCssNumberingRulesFor(Outline::OUTLINE_HEADING_NUMBERING);
                        $snippet->setInlineContent($css);
                    }
                }
            } catch (ExceptionBadSyntax $e) {
                LogUtility::internalError("An error has occurred while trying to add the outline heading numbering stylesheet.", self::CANONICAL, $e);
            } catch (ExceptionNotEnabled $e) {
                // ok
            }

            /**
             * Anchor on id
             */
            $snippetManager = PluginUtility::getSnippetManager();
            try {
                $snippetManager->attachRemoteJavascriptLibrary(
                    Outline::OUTLINE_ANCHOR,
                    "https://cdn.jsdelivr.net/npm/anchor-js@4.3.0/anchor.min.js",
                    "sha256-LGOWMG4g6/zc0chji4hZP1d8RxR2bPvXMzl/7oPZqjs="
                );
            } catch (ExceptionBadArgument|ExceptionBadSyntax $e) {
                // The url has a file name. this error should not happen
                LogUtility::internalError("Unable to add anchor. Error:{$e->getMessage()}", Outline::OUTLINE_ANCHOR);
            }
            $snippetManager->attachJavascriptFromComponentId(Outline::OUTLINE_ANCHOR);

        }
        $snippetManager->attachCssInternalStyleSheet(HeadingTag::HEADING_TAG);

        /**
         * Not a HTML attribute
         */
        $tagAttributes->removeComponentAttributeIfPresent(self::HEADING_TEXT_ATTRIBUTE);

        /**
         * Two headings 1 are shown
         *
         * We delete the heading 1 in the instructions
         * if the template has a content header
         *
         * The instructions may not be reprocessed after upgrade for instance
         * when the installation is done manually
         *
         * To avoid to have two headings, we set a display none if this is the case
         *
         * Note that this should only apply on the document and not on a partial but yeah
         * We go that h1 is not used in partials.
         */
        if ($level === 1) {

            $hasMainHeaderElement = TemplateForWebPage::create()
                ->setRequestedContextPath(ExecutionContext::getActualOrCreateFromEnv()->getContextPath())
                ->hasElement(TemplateSlot::MAIN_HEADER_ID);

            // fuck: template should be a runtime parameters and is not
            $executingAction = ExecutionContext::getActualOrCreateFromEnv()->getExecutingAction();
            if ($executingAction === "combo_" . FetcherPageBundler::NAME) {
                $hasMainHeaderElement = false;
            }

            if ($hasMainHeaderElement) {
                $tagAttributes->addClassName("d-none");
            }

        }

        /**
         * Printing
         */
        $tag = self::getTagFromContext($context, $level);
        $renderer->doc .= $tagAttributes->toHtmlEnterTag($tag);

    }

    /**
     * @param TagAttributes $tagAttributes
     * @param string $context
     * @return string
     */
    public
    static function renderClosingTag(TagAttributes $tagAttributes, string $context): string
    {
        $level = $tagAttributes->getValueAndRemove(HeadingTag::LEVEL);
        if ($level == null) {
            LogUtility::msg("The level is mandatory when closing a heading", self::CANONICAL);
        }
        $tag = self::getTagFromContext($context, $level);

        return "</$tag>";
    }

    /**
     * Reduce the end of the input string
     * to the first opening tag without the ">"
     * and returns the closing tag
     *
     * @param $input
     * @return array - the heading attributes as a string
     */
    public
    static function reduceToFirstOpeningTagAndReturnAttributes(&$input)
    {
        // the variable that will capture the attribute string
        $headingStartTagString = "";
        // Set to true when the heading tag has completed
        $endHeadingParsed = false;
        // The closing character `>` indicator of the start and end tag
        // true when found
        $endTagClosingCharacterParsed = false;
        $startTagClosingCharacterParsed = false;
        // We start from the edn
        $position = strlen($input) - 1;
        while ($position > 0) {
            $character = $input[$position];

            if ($character == "<") {
                if (!$endHeadingParsed) {
                    // We are at the beginning of the ending tag
                    $endHeadingParsed = true;
                } else {
                    // We have delete all character until the heading start tag
                    // add the last one and exit
                    $headingStartTagString = $character . $headingStartTagString;
                    break;
                }
            }

            if ($character == ">") {
                if (!$endTagClosingCharacterParsed) {
                    // We are at the beginning of the ending tag
                    $endTagClosingCharacterParsed = true;
                } else {
                    // We have delete all character until the heading start tag
                    $startTagClosingCharacterParsed = true;
                }
            }

            if ($startTagClosingCharacterParsed) {
                $headingStartTagString = $character . $headingStartTagString;
            }


            // position --
            $position--;

        }
        $input = substr($input, 0, $position);

        if (!empty($headingStartTagString)) {
            return PluginUtility::getTagAttributes($headingStartTagString);
        } else {
            LogUtility::msg("The attributes of the heading are empty and this should not be possible");
            return [];
        }


    }

    public
    static function handleEnter(\Doku_Handler $handler, TagAttributes $tagAttributes, string $markupTag): array
    {
        /**
         * Context determination
         */
        $callStack = CallStack::createFromHandler($handler);
        $context = HeadingTag::getContext($callStack);

        /**
         * Level is mandatory (for the closing tag)
         */
        $level = $tagAttributes->getValue(HeadingTag::LEVEL);
        if ($level === null) {

            /**
             * Old title type
             * from 1 to 4 to set the display heading
             */
            $type = $tagAttributes->getType();
            if (is_numeric($type) && $type != 0) {
                $level = $type;
                if ($markupTag === self::TITLE_TAG) {
                    $type = "d$level";
                } else {
                    $type = "h$level";
                }
                $tagAttributes->setType($type);
            }
            /**
             * Still null, check the type
             */
            if ($level == null) {
                if (in_array($type, HeadingTag::getAllTypes())) {
                    $level = substr($type, 1);
                }
            }
            /**
             * Still null, default level
             */
            if ($level == null) {
                if ($context === HeadingTag::TYPE_OUTLINE) {
                    $level = HeadingTag::DEFAULT_LEVEL_OUTLINE_CONTEXT;
                } else {
                    $level = HeadingTag::DEFAULT_LEVEL_TITLE_CONTEXT;
                }
            }
            /**
             * Set the level
             */
            $tagAttributes->addComponentAttributeValue(HeadingTag::LEVEL, $level);
        }
        return [PluginUtility::CONTEXT => $context];
    }

    public
    static function getAllTypes(): array
    {
        return array_merge(
            self::DISPLAY_TYPES,
            self::HEADING_TYPES,
            self::SHORT_TYPES,
            self::TITLE_DISPLAY_TYPES
        );
    }

    /**
     * @param string $context
     * @param int $level
     * @return string
     */
    private static function getTagFromContext(string $context, int $level): string
    {
        if ($context === self::TYPE_OUTLINE) {
            return "h$level";
        } else {
            return "div";
        }
    }

    /**
     * Level 1 has the page id as id to be able to
     * concatenate them in a {@link \ComboStrap\FetcherPageBundler}
     * and to not loose the link
     * (Too complicated for now, to do it in the {@link \ComboStrap\Outline}
     * @throws ExceptionNotFound - when the executing id was not found
     */
    public static function getIdForLevel1()
    {
        $executingWikiId = ExecutionContext::getActualOrCreateFromEnv()->getExecutingWikiId();
        $check = false;
        return sectionID($executingWikiId, $check);

    }


}
