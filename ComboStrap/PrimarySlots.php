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


    const CANONICAL = "slot";

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
    public static function addContentSlots(CallStack $callStack, &$tocCall, $page)
    {


        $isPrimarySlotWithHeaderAndFooter = $page->isPrimarySlot() && !$page->isRootHomePage();
        if (!$isPrimarySlotWithHeaderAndFooter) {
            return;
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
                break;
            case "preview":
                /**
                 * preview only if it's the whole page
                 * ie no prefix, no suffix
                 */
                $prefix = $_REQUEST["prefix"];
                $suffix = $_REQUEST["suffix"];
                if (!($prefix === "." && $suffix === "")) {
                    return;
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
                        return;
                    }
                    $capturedContent = CallStack::getFileContent($callStack, 10);
                    if (strpos($text, $capturedContent) !== false) {
                        return;
                    }
                }
                break;
            default:
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
                case Site::getPrimarySideSlotName():

                case Site::getPrimaryHeaderSlotName():
                    $callStack->moveToStart();
                    $actualCall = $callStack->next();
                    if ($actualCall->getTagName() === syntax_plugin_combo_frontmatter::TAG) {
                        $callStack->next();
                    }
                    $callStack->insertInstructionsFromNativeArrayAfterCurrentPosition($stack);
                    break;
                case Site::getPrimaryFooterSlotName():
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
