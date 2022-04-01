<?php

namespace ComboStrap;

use syntax_plugin_combo_box;
use syntax_plugin_combo_frontmatter;
use syntax_plugin_combo_toc;

/**
 * Class that handle the addition of the slots
 * in the primary slot (ie in the main content)
 */
class PrimarySlots
{


    const CANONICAL = "primary-slots";

    /**
     *
     * This function will add header / footer / sidekick of
     * the primary/main page into its stack
     *
     *  * We don't create compound text file because we would lost the position of the token in the file
     * and the edit section would be broken
     *  * We parse the header/footer and add them to the callstack
     *
     * @param CallStack $callStack - a callstack
     * @param $tocCall - the toc call if found
     * @return void
     */
    public static function process(CallStack $callStack, &$tocCall)
    {

        /**
         * Be sure that this is a primary page
         * and that the act is show/preview (and not {@link \ComboStrap\RenderUtility::DYNAMIC_RENDERING}
         *
         */
        try {
            $page = Page::createPageFromGlobalDokuwikiId();
        } catch (ExceptionNotFound $e) {
            LogUtility::msg("The running id was not found, we were unable to add the main footer/header", LogUtility::LVL_MSG_ERROR, self::CANONICAL);
            return;
        }
        /**
         * ACT only for show and preview
         *
         * - may be null with ajax call
         * - not {@link RenderUtility::DYNAMIC_RENDERING}
         * - not 'admin'
         */


        $isPrimarySlotWithHeaderAndFooter = $page->isPrimarySlot() && !$page->isRootHomePage();
        if (!$isPrimarySlotWithHeaderAndFooter) {
            return;
        }

        global $ACT;

        if (!in_array($ACT, ["show", "preview"])) {
            return;
        }


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

            /**
             * Wrap the instructions in a div
             */
            $childCallStack->insertAfter(Call::createComboCall(
                syntax_plugin_combo_box::TAG,
                DOKU_LEXER_ENTER,
                ["class" => $name]
            ));

            /**
             * Delete the start and end call
             * and capture the toc if any
             */
            while ($actualCall = $childCallStack->next()) {
                $tagName = $actualCall->getTagName();
                switch ($tagName) {
                    case syntax_plugin_combo_toc::TAG:
                        $tocCall = $actualCall;
                        continue 2;
                    case CallStack::DOCUMENT_START:
                    case CallStack::DOCUMENT_END:
                        $childCallStack->deleteActualCallAndPrevious();
                        continue 2;
                }
            }
            /**
             * Close the div box
             */
            $childCallStack->appendCallAtTheEnd(Call::createComboCall(
                syntax_plugin_combo_box::TAG,
                DOKU_LEXER_EXIT
            ));
            $stack = $childCallStack->getStack();
            switch ($name) {
                case Site::SLOT_MAIN_HEADER_NAME:
                    $callStack->moveToStart();
                    $actualCall = $callStack->next();
                    if ($actualCall->getTagName() !== syntax_plugin_combo_frontmatter::TAG) {
                        $callStack->next();
                    }
                    $callStack->insertInstructionsFromNativeArrayAfterCurrentPosition($stack);
                    break;
                case Site::SLOT_MAIN_FOOTER_NAME:
                    $stack = $childCallStack->getStack();
                    $callStack->appendInstructionsFromNativeArrayAtTheEnd($stack);
                    break;
                default:
                    LogUtility::msg("The child ($child) of the page ($page) is unknown and was not added in the markup");
                    break;
            }
        }

    }
}
