<?php

namespace ComboStrap;

use syntax_plugin_combo_box;
use syntax_plugin_combo_frontmatter;
use syntax_plugin_combo_toc;

/**
 * Class that handle the addition of the slots
 * in the primary slot (ie in the main content)
 */
class LayoutManager
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
         * ACT only for show and preview
         *
         * - may be null with ajax call
         * - not {@link RenderUtility::DYNAMIC_RENDERING}
         * - not 'admin'
         *
         */
        global $ACT;
        switch ($ACT) {
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
        $sideCallStack = null;
        $headerCallStack = null;
        $footerCallStack = null;
        foreach ($page->getChildren() as $child) {
            $name = $child->getPath()->getLastName();

            try {
                $childInstructions = $child->getInstructionsDocument()->getOrProcessContent();
            } catch (ExceptionNotFound $e) {
                LogUtility::msg("The instructions content of the $name slot was not found, we were unable to add it to the main content", LogUtility::LVL_MSG_ERROR, self::CANONICAL);
                continue;
            }
            $childCallStack = CallStack::createFromInstructions($childInstructions);
            $childCallStack->moveToStart();

            switch ($name) {
                case Site::getPrimarySideSlotName():
                    $id = "main-side";
                    $tag = "aside";
                    $sideCallStack = $childCallStack;
                    break;
                case Site::getPrimaryHeaderSlotName():
                    $id = "main-header";
                    $tag = "header";
                    $headerCallStack = $childCallStack;
                    break;
                case Site::getPrimaryFooterSlotName():
                    $id = "main-footer";
                    $tag = "footer";
                    $footerCallStack = $childCallStack;
                    break;
                default:
                    LogUtility::error("The slot ($name) was not expected", self::CANONICAL);
                    continue 2;
            }

            /**
             * Wrap the instructions in a div
             */
            $childCallStack->insertAfter(Call::createComboCall(
                syntax_plugin_combo_box::TAG,
                DOKU_LEXER_ENTER,
                ["class" => $name, "id" => $id, "tag" => $tag]
            ));

            /**
             * Delete the start and end call
             * and capture the toc if any
             */
            while ($actualCall = $childCallStack->next()) {
                $tagName = $actualCall->getTagName();
                switch ($tagName) {
                    case syntax_plugin_combo_toc::TAG:
                        $tocData = $actualCall;
                        continue 2;
                    case CallStack::DOCUMENT_START:
                    case CallStack::DOCUMENT_END:
                        $childCallStack->deleteActualCallAndPrevious();
                        continue 2;
                }
            }
            /**
             * Close the element
             */
            $childCallStack->appendCallAtTheEnd(Call::createComboCall(
                syntax_plugin_combo_box::TAG,
                DOKU_LEXER_EXIT,
                ["tag" => $tag]
            ));

        }

        /**
         * Get:
         *   * the header from the main callstack
         *   * the frontmatter if any
         *
         */
        $mainCallStack->moveToStart();
        $headerInstructionStack = null;
        $breakingHeadingFound = false;
        $frontMatterCall = null;
        while ($actualCall = $mainCallStack->next()) {
            if (in_array($actualCall->getTagName(), \action_plugin_combo_headingpostprocessing::HEADING_TAGS)
                && in_array($actualCall->getState(), [DOKU_LEXER_ENTER, DOKU_LEXER_SPECIAL])) {
                $context = $actualCall->getContext();
                if ($context !== \syntax_plugin_combo_heading::TYPE_OUTLINE) {
                    break;
                }
                $level = $actualCall->getAttribute(\syntax_plugin_combo_heading::LEVEL);
                if ($level !== 1) {
                    if ($level === 2) {
                        $breakingHeadingFound = true;
                    }
                    break;
                }
            }
            if ($actualCall->getTagName() === syntax_plugin_combo_frontmatter::TAG) {
                $frontMatterCall = $mainCallStack->deleteActualCallAndPrevious();
            } else {
                $headerInstructionStack[] = $actualCall->getInstructionCall();
            }
        }
        if ($breakingHeadingFound) {
            if ($headerCallStack === null) {
                $headerCallStack = CallStack::createFromInstructions($headerInstructionStack);
            }
            // delete the header in the main callStack
            if ($actualCall !== null) {
                /**
                 * If the previous call is a section delete from
                 */
                $previousCall = $mainCallStack->previous();
                if($previousCall->getTagName()===\syntax_plugin_combo_section::TAG) {
                    $mainCallStack->deleteAllCallsBefore($previousCall);
                } else {
                    $mainCallStack->deleteAllCallsBefore($actualCall);
                }
            }
        }


        /**
         * Combining the areas
         */
        $mainCallStack->moveToStart();
        if ($frontMatterCall !== null) {
            $mainCallStack->insertAfter($frontMatterCall);
            $mainCallStack->next();
        }
        /**
         * Wrap the instructions in a div
         */
        $mainCallStack->insertAfter(Call::createComboCall(
            syntax_plugin_combo_box::TAG,
            DOKU_LEXER_ENTER,
            ["id" => "main-content"]
        ));

        /**
         * Header and toc
         * TOC being after the header
         */
        $tocCall = null;
        if ($tocData !== null) {
            $tocCall = Call::createComboCall(
                syntax_plugin_combo_toc::TAG,
                DOKU_LEXER_SPECIAL,
                [syntax_plugin_combo_toc::TOC_ATTRIBUTE => $tocData]
            );
        }
        if ($headerCallStack !== null) {
            if ($tocCall !== null) {
                $headerCallStack->appendCallAtTheEnd($tocCall);
            }
            $mainCallStack->insertAfterFromNativeArrayInstructions($headerCallStack->getStack());
        } else {
            if ($tocCall !== null) {
                $mainCallStack->insertAfter($tocCall);
            }
        }

        /**
         * Append side and footer
         */
        if ($sideCallStack !== null) {
            $mainCallStack->appendAtTheEndFromNativeArrayInstructions($sideCallStack->getStack());
        }

        if ($footerCallStack !== null) {
            $mainCallStack->appendAtTheEndFromNativeArrayInstructions($footerCallStack->getStack());
        }

        /**
         * Close main-content
         */
        $mainCallStack->moveToEnd();
        $mainCallStack->insertBefore(Call::createComboCall(
            syntax_plugin_combo_box::TAG,
            DOKU_LEXER_EXIT
        ));


    }
}
