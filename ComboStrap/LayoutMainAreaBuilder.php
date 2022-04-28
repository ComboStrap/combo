<?php

namespace ComboStrap;

use syntax_plugin_combo_box;
use syntax_plugin_combo_frontmatter;
use syntax_plugin_combo_toc;

/**
 * Class that handle the addition of the slots
 * in the primary slot (ie in the main content)
 */
class LayoutMainAreaBuilder
{


    const CANONICAL = "layout";


    public static function shouldMainAreaBeBuild(CallStack $callStack, Page $page): bool
    {


        if (!$page->isPrimarySlot()) {
            return false;
        }

        $layout = PageLayout::createFromPage($page)->getValueOrDefault();
        if ($layout === PageLayout::LANDING_LAYOUT_VALUE) {
            return false;
        }

        /**
         *
         * Page does not have the concept of content type
         * (ie page content, admin content, ...)
         *
         * We use ACT for this purpose.
         *
         * ACT only for show and preview
         * - not {@link RenderUtility::DYNAMIC_RENDERING}
         * - not 'admin'
         *
         * - not all other undetermined actions (
         */
        global $ACT;
        $localAct = $ACT;
        if ($localAct === null) {
            $localAct = "show";
            LogUtility::error("The action variable was not set. We have set it to be `show`", self::CANONICAL);
        }
        switch ($localAct) {
            case "show":
                return true;
            case "preview":
                /**
                 * preview only if it's the whole page
                 * ie no prefix, no suffix
                 */
                $prefix = $_REQUEST["prefix"];
                $suffix = $_REQUEST["suffix"];
                if (!($prefix === "." && $suffix === "")) {
                    return false;
                };
                /**
                 * Unfortunately, in edit/preview page
                 * {@link html_edit()}
                 * They use local markup file
                 * and parse them without context with
                 * {@link p_locale_xhtml()}
                 * We may have then: `dokuwiki/inc/lang/en/edit.txt`
                 * And we don't have any pointer than the callstack (Doku_Handler)
                 *
                 * It does not happen often, because the output is cached
                 *
                 * Because a preview will also cache the generated instructions
                 * a user that would preview a whole page would not get
                 * the header/footer
                 * This is not user friendly, we check them if the callstack is generated
                 * from the locale file used in the edit admin page
                 */
                $localEditFileNames = ["edit", "preview"];
                foreach ($localEditFileNames as $localEditFileName) {
                    $previewFile = LocalPath::createFromPath(localeFN($localEditFileName));
                    try {
                        $text = FileSystems::getContent($previewFile);
                    } catch (ExceptionNotFound $e) {
                        LogUtility::msg("The $localEditFileName file ($previewFile) was not found, the main slots (header/footer/side) were not added");
                        return false;
                    }
                    $capturedContent = CallStack::getFileContent($callStack, 10);
                    if (strpos($text, $capturedContent) !== false) {
                        return false;
                    }
                }
                return true;
            default:
            case RenderUtility::DYNAMIC_RENDERING:
            case "edit":
            case "admin":
            case "backlink":
                /**
                 * Not on edit page
                 * Not with dynamic content
                 *
                 * Not for all other action:
                 * https://www.dokuwiki.org/devel:action_modes
                 */
                return false;
        }
    }

    /**
     *
     * This function will add header / footer / sidekick of
     * the primary/main page into its stack
     *
     *  * We don't create compound text file because we would lost the position of the token in the file
     * and the edit section would be broken
     *  * We parse the header/footer and add them to the callstack
     *
     * @param CallStack $mainCallStack - a callstack
     * @param $tocData - the toc data
     * @return void
     */
    public static function buildMainArea(CallStack $mainCallStack, $tocData, Page $page)
    {


        /**
         * Get the main slots
         */
        $mainHeaderCallStack = null;
        $mainHeaderHasNormalHeading = false; // normal heading is an heading that is not in a template
        $mainHeaderHasTemplateHeading = false; // an heading in a template
        foreach ($page->getChildren() as $child) {
            $name = $child->getPath()->getLastName();

            try {
                $childInstructions = $child->getInstructionsDocument()->getOrProcessContent();
            } catch (ExceptionNotFound $e) {
                LogUtility::msg("The instructions content of the $name slot was not found, we were unable to add it to the main content", LogUtility::LVL_MSG_ERROR, self::CANONICAL);
                continue;
            }
            $childCallStack = CallStack::createFromInstructions($childInstructions);
            switch ($name) {
                case Site::getPrimaryHeaderSlotName():
                    $mainHeaderCallStack = $childCallStack;
                    $childCallStack->moveToStart();
                    while ($actualCall = $mainHeaderCallStack->next()) {
                        if (in_array($actualCall->getTagName(), \action_plugin_combo_headingpostprocessing::HEADING_TAGS)) {
                            $mainHeaderHasNormalHeading = true;
                            break;
                        }
                        if ($actualCall->getTagName() === \syntax_plugin_combo_template::TAG && $actualCall->getState() === DOKU_LEXER_EXIT) {
                            $instructions = $actualCall->getPluginData(\syntax_plugin_combo_template::CALLSTACK);
                            $templateCallStack = CallStack::createFromInstructions($instructions);
                            $templateCallStack->moveToStart();
                            while ($actualTemplateCall = $templateCallStack->next()) {
                                if (in_array($actualTemplateCall->getTagName(), \action_plugin_combo_headingpostprocessing::HEADING_TAGS)) {
                                    $mainHeaderHasTemplateHeading = true;
                                    break;
                                }
                            }
                        }
                    }
                    break;
                default:
                    LogUtility::error("The slot ($name) was not expected", self::CANONICAL);
                    continue 2;
            }


            /**
             * Delete the start, end call and frontmatter
             * and capture the toc if any
             */
            $childCallStack->moveToStart();
            while ($actualCall = $childCallStack->next()) {
                $tagName = $actualCall->getTagName();
                switch ($tagName) {
                    case syntax_plugin_combo_toc::TAG:
                    case CallStack::DOCUMENT_START:
                    case CallStack::DOCUMENT_END:
                    case syntax_plugin_combo_frontmatter::TAG:
                        if ($tagName === syntax_plugin_combo_toc::TAG) {
                            $tocData = $actualCall;
                        }
                        $childCallStack->deleteActualCallAndPrevious();
                        break;
                }
            }

        }


        /**
         * Split the main content
         *   - Extract the frontmatter calls (should not be deleted)
         *   - Get the first heading instructions
         *   - Get the header from the main callstack (may be deleted)
         *   - Get the content
         */
        $frontMatterCall = null;
        $mainContentFirstHeadingInstructions = [];
        $mainContentHeaderInstructionsWithoutHeading1 = [];
        $mainContentContentInstructions = [];
        $heading2Found = false;
        $actualHeadingLevel = 0;

        $mainCallStack->moveToStart();
        while ($actualCall = $mainCallStack->next()) {

            /**
             * Collect frontmatter
             */
            if ($actualCall->getTagName() == syntax_plugin_combo_frontmatter::TAG) {
                $frontMatterCall = $mainCallStack->deleteActualCallAndPrevious();
                continue;
            }

            /**
             * Heading processing
             */
            if (in_array($actualCall->getTagName(), \action_plugin_combo_headingpostprocessing::HEADING_TAGS)) {

                $level = $actualCall->getAttribute(\syntax_plugin_combo_heading::LEVEL);
                if ($level !== null) {
                    $actualHeadingLevel = $level;
                }
                if (in_array($actualCall->getState(), [DOKU_LEXER_ENTER, DOKU_LEXER_SPECIAL])) {
                    $context = $actualCall->getContext();
                    if ($context !== \syntax_plugin_combo_heading::TYPE_OUTLINE) {
                        break;
                    }
                }
                switch ($actualHeadingLevel) {
                    case 1:
                        /**
                         * Collect the heading 1
                         */
                        $mainContentFirstHeadingInstructions[] = $actualCall->getInstructionCall();
                        continue 2;
                    default:
                    case 2:
                        /**
                         * The content between level 1 and level 2 is the header
                         */
                        $heading2Found = true;
                        break;
                }

            }
            if (!$heading2Found) {
                $mainContentHeaderInstructionsWithoutHeading1[] = $actualCall->getInstructionCall();
            } else {
                $mainContentContentInstructions[] = $actualCall->getInstructionCall();
            }

        }
        if (!$heading2Found) {
            $mainContentContentInstructions = $mainContentHeaderInstructionsWithoutHeading1;
            $mainContentHeaderInstructionsWithoutHeading1 = [];
        }


        /**
         * Combining the areas
         */
        $mainCallStack->empty();

        /**
         * Header building
         */
        $headerHtmlTag = "header";
        $mainCallStack->appendCallAtTheEnd(Call::createComboCall(
            syntax_plugin_combo_box::TAG,
            DOKU_LEXER_ENTER,
            ["id" => "main-header", "tag" => $headerHtmlTag]
        ));
        if ($frontMatterCall !== null) {
            /**
             * Adding the front matter edit button if any in the header
             * to not get problem with the layout grid
             */
            $mainCallStack->appendCallAtTheEnd($frontMatterCall);
        }
        if ($mainHeaderCallStack !== null) {
            $mainCallStack->appendAtTheEndFromNativeArrayInstructions($mainHeaderCallStack->getStack());
        }
        if (!$mainHeaderHasNormalHeading && !$mainHeaderHasTemplateHeading && sizeof($mainContentFirstHeadingInstructions) > 0) {
            /**
             * The heading is in the main content header
             *
             * The H1 heading should not go into the main content
             * but into the main header
             * because we rely on the html structure for
             * {@link \action_plugin_combo_outlinenumbering::getCssOutlineNumberingRuleFor()}
             */
            $mainCallStack->appendAtTheEndFromNativeArrayInstructions($mainContentFirstHeadingInstructions);
        }
        if (sizeof($mainContentHeaderInstructionsWithoutHeading1) > 0) {
            /**
             * Add the main content header without the heading
             */
            $mainCallStack->appendAtTheEndFromNativeArrayInstructions($mainContentHeaderInstructionsWithoutHeading1);
        }
        /**
         * Close the main-header div
         */
        $mainCallStack->appendCallAtTheEnd(Call::createComboCall(
            syntax_plugin_combo_box::TAG,
            DOKU_LEXER_EXIT,
            ["tag" => $headerHtmlTag]
        ));

        /**
         * TOC
         */
        if ($tocData !== null) {
            $tocCall = Call::createComboCall(
                syntax_plugin_combo_toc::TAG,
                DOKU_LEXER_SPECIAL,
                [syntax_plugin_combo_toc::TOC_ATTRIBUTE => $tocData]
            );
            $mainCallStack->appendCallAtTheEnd($tocCall);
        }


        /**
         * Main Content
         * Wrap the main instructions in a div
         */
        $mainCallStack->appendCallAtTheEnd(Call::createComboCall(
            syntax_plugin_combo_box::TAG,
            DOKU_LEXER_ENTER,
            ["id" => "main-content"]
        ));
        if (sizeof($mainContentContentInstructions) > 0) {
            $mainCallStack->appendAtTheEndFromNativeArrayInstructions($mainContentContentInstructions);
        }
        /**
         * Close the main-content div
         */
        $mainCallStack->appendCallAtTheEnd(Call::createComboCall(
            syntax_plugin_combo_box::TAG,
            DOKU_LEXER_EXIT
        ));


    }

    public static function headingDisplayNone(CallStack $callStack, Page $page)
    {

        $mainHeaderPage = $page->getPrimaryHeaderPage();
        if ($mainHeaderPage === null) {
            return;
        }
        try {
            $mainHeaderCallStack = CallStack::createFromInstructions($mainHeaderPage->getInstructionsDocument()->getOrProcessContent());
        } catch (ExceptionNotFound $e) {
            return;
        }
        $mainHeaderHasTemplateHeading = false;
        $mainHeaderCallStack->moveToStart();
        while ($actualCall = $mainHeaderCallStack->next()) {
            if (in_array($actualCall->getTagName(), \action_plugin_combo_headingpostprocessing::HEADING_TAGS)) {
                $mainHeaderHasNormalHeading = true;
                break;
            }
            if ($actualCall->getTagName() === \syntax_plugin_combo_template::TAG && $actualCall->getState() === DOKU_LEXER_EXIT) {
                $instructions = $actualCall->getPluginData(\syntax_plugin_combo_template::CALLSTACK);
                $templateCallStack = CallStack::createFromInstructions($instructions);
                $templateCallStack->moveToStart();
                while ($actualTemplateCall = $templateCallStack->next()) {
                    if (in_array($actualTemplateCall->getTagName(), \action_plugin_combo_headingpostprocessing::HEADING_TAGS)) {
                        $mainHeaderHasTemplateHeading = true;
                        break;
                    }
                }
            }
        }
        if (!$mainHeaderHasTemplateHeading) {
            return;
        }
        $callStack->moveToStart();
        while ($actualCall = $callStack->next()) {
            if (in_array($actualCall->getTagName(), \action_plugin_combo_headingpostprocessing::HEADING_TAGS)) {
                try {
                    $level = DataType::toInteger($actualCall->getAttribute(\syntax_plugin_combo_heading::LEVEL));
                    if ($level === 1) {
                        $callStack->deleteActualCallAndPrevious();
                    }
                    return;
                } catch (ExceptionBadArgument $e) {
                    return;
                }
            }
        }

    }
}
