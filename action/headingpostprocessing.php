<?php


use ComboStrap\Call;
use ComboStrap\CallStack;
use ComboStrap\ExceptionCombo;
use ComboStrap\LogUtility;
use ComboStrap\MediaLink;
use ComboStrap\Page;
use ComboStrap\PluginUtility;
use ComboStrap\PageEdit;

class action_plugin_combo_headingpostprocessing extends DokuWiki_Action_Plugin
{


    /**
     * This section are not HTML
     * section, they are edit section
     * that delimits the edit area
     */
    const EDIT_SECTION_OPEN = 'section_open';
    const EDIT_SECTION_CLOSE = 'section_close';


    /**
     * @var int a counter that should at 0
     * at the end of the processing
     * +1 if an outline section was opened
     * -1 if an outline section was closed
     */
    private $outlineSectionBalance = 0;

    /**
     * Insert the HTML section
     * @param CallStack $callStack
     * @param Call $actualCall
     * @param int $actualLastPosition
     */
    private function openOutlineSection(CallStack $callStack, Call $actualCall, int $actualLastPosition)
    {
        if ($actualCall->getContext() == syntax_plugin_combo_heading::TYPE_OUTLINE) {
            $call = Call::createComboCall(
                syntax_plugin_combo_section::TAG,
                DOKU_LEXER_ENTER,
                array(),
                $actualLastPosition
            );
            $callStack->insertBefore($call);
            $this->outlineSectionBalance++;
        }
    }

    /**
     * Close the outline section if the levels difference
     * are from the same level or less
     * @param CallStack $callStack
     * @param Call|null $actualHeadingCall
     * @param int $previousLevel
     * @param int $actualLastPosition
     */
    private function closeOutlineSectionIfNeeded(CallStack $callStack, Call $actualHeadingCall, int $previousLevel, int $actualLastPosition)
    {
        $close = false;
        if ($actualHeadingCall->getContext() == syntax_plugin_combo_heading::TYPE_OUTLINE) {

            $actualLevel = intval($actualHeadingCall->getAttribute("level"));
            if ($actualLevel <= $previousLevel) {
                $close = true;
            }

            if ($close) {
                $this->closeOutlineSection($callStack, $actualLastPosition);
            }

        }

    }

    /**
     * @param CallStack $callStack
     * @param $handler
     * @param $position
     */
    private function closeEditSection(CallStack $callStack, $handler, $position)
    {

        $call = Call::createNativeCall(
            self::EDIT_SECTION_CLOSE,
            array(),
            $position
        );
        $callStack->insertBefore($call);
        $handler->setStatus('section', false);
    }

    /**
     * Technically add a div around the content below the heading
     * @param $callStack
     * @param $headingEntryCall
     * @param $handler
     */
    private
    static function openEditSection($callStack, $headingEntryCall, $handler)
    {

        $openSectionCall = Call::createNativeCall(
            self::EDIT_SECTION_OPEN,
            array($headingEntryCall->getAttribute(syntax_plugin_combo_headingatx::LEVEL)),
            $headingEntryCall->getFirstMatchedCharacterPosition()
        );
        $callStack->insertAfter($openSectionCall);
        $handler->setStatus('section', true);


    }

    public
    function register(\Doku_Event_Handler $controller)
    {
        /**
         * Found in {@link Doku_Handler::finalize()}
         *
         * Doc: https://www.dokuwiki.org/devel:event:parser_handler_done
         */
        $controller->register_hook(
            'PARSER_HANDLER_DONE',
            'AFTER',
            $this,
            '_post_process_heading',
            array()
        );

    }


    /**
     * Transform the special heading atx call
     * in an enter and exit heading atx calls
     *
     * Add the section close / open
     *
     * Code extracted and adapted from the end of {@link Doku_Handler::header()}
     *
     * @param   $event Doku_Event
     */
    function _post_process_heading(&$event, $param)
    {
        /**
         * @var Doku_Handler $handler
         */
        $handler = $event->data;
        $callStack = CallStack::createFromHandler($handler);
        $callStack->moveToStart();

        /**
         * Close the section
         * for whatever reason, the section status is true
         * even if the sections are closed
         * We take the hypothesis that the sections are closed
         */
        $handler->setStatus('section', false);

        /**
         * Processing variable about the context
         */
        $actualHeadingParsingState = DOKU_LEXER_EXIT; // enter if we have entered a heading, exit otherwise
        $actualSectionState = null; // enter if we have created a section
        $headingEnterCall = null; // the enter call

        $headingText = ""; // text only content in the heading
        $previousHeadingLevel = 0; // A pointer to the actual heading level
        $headingComboCounter = 0; // The number of combo heading found (The first one that is not the first one should close)
        $headingTotalCounter = 0; // The number of combo heading found (The first one that is not the first one should close)

        $actualLastPosition = 0;
        while ($actualCall = $callStack->next()) {

            $tagName = $actualCall->getTagName();

            /**
             * Track the position in the file
             */
            $currentLastPosition = $actualCall->getLastMatchedCharacterPosition();
            if ($currentLastPosition > $actualLastPosition) {
                // the position in the stack is not always good
                $actualLastPosition = $currentLastPosition;
            }

            /**
             * Enter
             */
            switch ($tagName) {
                case syntax_plugin_combo_headingatx::TAG:
                    $actualCall->setState(DOKU_LEXER_ENTER);
                    $actualHeadingParsingState = DOKU_LEXER_ENTER;
                    $headingEnterCall = $callStack->getActualCall();
                    $headingComboCounter++;
                    $headingTotalCounter++;
                    $this->closeEditSectionIfNeeded($actualCall, $handler, $callStack, $actualSectionState, $headingComboCounter, $headingTotalCounter, $actualLastPosition);
                    $this->closeOutlineSectionIfNeeded($callStack, $actualCall, $previousHeadingLevel, $actualLastPosition);
                    $this->openOutlineSection($callStack, $actualCall, $actualLastPosition);
                    continue 2;
                case syntax_plugin_combo_heading::TAG:
                case syntax_plugin_combo_headingwiki::TAG:
                    if ($actualCall->getState() == DOKU_LEXER_ENTER) {
                        $actualHeadingParsingState = DOKU_LEXER_ENTER;
                        $headingEnterCall = $callStack->getActualCall();
                        $headingComboCounter++;
                        $headingTotalCounter++;
                        self::closeEditSectionIfNeeded($actualCall, $handler, $callStack, $actualSectionState, $headingComboCounter, $headingTotalCounter, $actualLastPosition);
                        self::closeOutlineSectionIfNeeded($callStack, $actualCall, $previousHeadingLevel, $actualLastPosition);
                        self::openOutlineSection($callStack, $actualCall, $actualLastPosition);
                        $previousHeadingLevel = $headingEnterCall->getAttribute("level");
                        continue 2;
                    }
                    break;
                case "header":
                    $headingTotalCounter++;
                    break;
            }


            /**
             * Close and Inside the heading description
             */
            if ($actualHeadingParsingState === DOKU_LEXER_ENTER) {

                switch ($actualCall->getTagName()) {

                    case syntax_plugin_combo_heading::TAG:
                    case syntax_plugin_combo_headingwiki::TAG:
                        if ($actualCall->getState() == DOKU_LEXER_EXIT) {
                            self::insertOpenSectionAfterAndCloseHeadingParsingStateAndNext(
                                $headingEnterCall,
                                $handler,
                                $callStack,
                                $actualSectionState,
                                $headingText,
                                $actualHeadingParsingState
                            );
                        } else {
                            // unmatched
                            self::addToTextHeading($headingText, $actualCall);
                        }
                        continue 2;

                    case "internalmedia":
                        // no link for media in heading
                        $actualCall->getCall()[1][6] = MediaLink::LINKING_NOLINK_VALUE;
                        continue 2;

                    case "header":
                        if (PluginUtility::getConfValue(syntax_plugin_combo_headingwiki::CONF_WIKI_HEADING_ENABLE, syntax_plugin_combo_headingwiki::CONF_DEFAULT_WIKI_ENABLE_VALUE) == 1) {
                            LogUtility::msg("The combo heading wiki is enabled, we should not see `header` calls in the call stack");
                        }
                        break;

                    case syntax_plugin_combo_media::TAG:
                        // no link for media in heading
                        $actualCall->addAttribute(MediaLink::LINKING_KEY, MediaLink::LINKING_NOLINK_VALUE);
                        continue 2;

                    default:
                        self::addToTextHeading($headingText, $actualCall);
                        continue 2;

                    case "p":
                        if ($headingEnterCall->getTagName() == syntax_plugin_combo_headingatx::TAG) {

                            /**
                             * Delete the p_enter / close
                             */
                            $callStack->deleteActualCallAndPrevious();

                            /**
                             * If this was a close tag
                             */
                            if ($actualCall->getComponentName() == "p_close") {

                                $callStack->next();

                                /**
                                 * Create the exit call
                                 * and open the section
                                 * Code extracted and adapted from the end of {@link Doku_Handler::header()}
                                 */
                                $callStack->insertBefore(
                                    Call::createComboCall(
                                        syntax_plugin_combo_headingatx::TAG,
                                        DOKU_LEXER_EXIT,
                                        $headingEnterCall->getAttributes()
                                    )
                                );
                                $callStack->previous();

                                /**
                                 * Close and section
                                 */
                                self::insertOpenSectionAfterAndCloseHeadingParsingStateAndNext(
                                    $headingEnterCall,
                                    $handler,
                                    $callStack,
                                    $actualSectionState,
                                    $headingText,
                                    $actualHeadingParsingState
                                );


                            }

                        }
                        continue 2;

                }


            }
            /**
             * when a heading of dokuwiki is mixed with
             * an atx heading, there is already a section close
             * at the end or in the middle
             */
            if ($actualCall->getComponentName() == self::EDIT_SECTION_CLOSE) {
                $actualSectionState = DOKU_LEXER_EXIT;
            }

        }

        /**
         * If the section was open by us or is still open, we close it
         *
         * We don't use the standard `section` key (ie  $handler->getStatus('section')
         * because it's open when we receive the handler
         * even if the `section_close` is present in the call stack
         *
         * We make sure that we close only what we have open
         */
        if ($actualSectionState == DOKU_LEXER_ENTER) {
            $this->closeEditSection($callStack, $handler, $actualLastPosition);
        }

        /**
         * Closing outline section
         */
        while ($this->outlineSectionBalance > 0) {
            $this->closeOutlineSection($callStack, $actualLastPosition);
        }

        /**
         * Not heading at all
         * No dynamic rendering (ie $ID is not null)
         */
        global $ID;
        if ($ID !== null) {

            $page = Page::createPageFromId($ID);
            if ($headingTotalCounter === 0 || $page->isSecondarySlot()) {
                try {
                    $tag = PageEdit::create("Slot Edit")->toTag();
                    if(!empty($tag)) { // page edit is not off
                        $sectionEditComment = Call::createComboCall(
                            syntax_plugin_combo_comment::TAG,
                            DOKU_LEXER_UNMATCHED,
                            array(),
                            Call::INLINE_DISPLAY, // don't trim
                            null,
                            $tag
                        );
                        $callStack->insertBefore($sectionEditComment);
                    }
                } catch (ExceptionCombo $e) {
                    LogUtility::msg("Error while adding the edit button. Error: {$e->getMessage()}");
                }
            }

        }


    }

    /**
     * @param $headingEntryCall
     * @param $handler
     * @param CallStack $callStack
     * @param $actualSectionState
     * @param $headingText
     * @param $actualHeadingParsingState
     */
    private
    static function insertOpenSectionAfterAndCloseHeadingParsingStateAndNext(&$headingEntryCall, &$handler, CallStack &$callStack, &$actualSectionState, &$headingText, &$actualHeadingParsingState)
    {
        /**
         * We are no more in a heading
         */
        $actualHeadingParsingState = DOKU_LEXER_EXIT;

        /**
         * Outline ?
         * Update the text and open a section
         */
        if ($headingEntryCall->getContext() === syntax_plugin_combo_heading::TYPE_OUTLINE) {

            /**
             * Update the entering call with the text capture
             */
            /**
             * Check the text
             */
            if (empty($headingText)) {
                LogUtility::msg("The heading text for the entry call ($headingEntryCall) is empty");
            }
            $headingEntryCall->addAttribute(syntax_plugin_combo_heading::HEADING_TEXT_ATTRIBUTE, $headingText);

            /**
             * Insert an entry call
             */
            self::openEditSection($callStack, $headingEntryCall, $handler);

            $actualSectionState = DOKU_LEXER_ENTER;
            $callStack->next();

        }

        /**
         * Reset
         * Important: If this is not an outline header, we need to reset it
         * otherwise it comes in the {@link \ComboStrap\TocUtility::renderToc()}
         */
        $headingText = "";



    }

    /**
     * @param Call $actualCall
     * @param $handler
     * @param $callStack
     * @param $actualSectionState
     * @param $headingComboCounter
     * @param $headingTotalCounter
     * @param $lastActualPosition - the last actual position in the file of the character
     */
    private function closeEditSectionIfNeeded(Call &$actualCall, &$handler, &$callStack, &$actualSectionState, $headingComboCounter, $headingTotalCounter, $lastActualPosition)
    {
        if ($actualCall->getContext() == syntax_plugin_combo_heading::TYPE_OUTLINE) {
            $close = $handler->getStatus('section');
            if ($headingComboCounter == 1 && $headingTotalCounter != 1) {
                /**
                 * If this is the first combo heading
                 * We need to close the previous to open
                 * this one
                 */
                $close = true;
            }
            if ($close) {
                self::closeEditSection($callStack, $handler, $lastActualPosition);
                $actualSectionState = DOKU_LEXER_EXIT;
            }
        }
    }

    /**
     * @param $headingText
     * @param Call $call
     */
    private
    static function addToTextHeading(&$headingText, $call)
    {
        if ($call->isTextCall()) {
            // Building the text for the toc
            // only cdata for now
            // no image, ...
            if ($headingText != "") {
                $headingText .= " ";
            }
            $headingText .= trim($call->getCapturedContent());
        }
    }

    private function closeOutlineSection($callStack, $position)
    {
        $openSectionCall = Call::createComboCall(
            syntax_plugin_combo_section::TAG,
            DOKU_LEXER_EXIT,
            array(),
            null,
            null,
            null,
            $position
        );
        $callStack->insertBefore($openSectionCall);
        $this->outlineSectionBalance--;
    }
}
