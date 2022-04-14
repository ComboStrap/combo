<?php


use ComboStrap\Call;
use ComboStrap\CallStack;
use ComboStrap\ExceptionCompile;
use ComboStrap\ExceptionNotFound;
use ComboStrap\LayoutManager;
use ComboStrap\LogUtility;
use ComboStrap\MediaLink;
use ComboStrap\Page;
use ComboStrap\EditButton;
use ComboStrap\PluginUtility;

class action_plugin_combo_headingpostprocessing extends DokuWiki_Action_Plugin
{


    /**
     * This section are not HTML
     * section, they are edit section
     * that delimits the edit area
     */
    const EDIT_SECTION_OPEN = 'section_open';
    const EDIT_SECTION_CLOSE = 'section_close';
    const HEADING_TAGS = [
        syntax_plugin_combo_heading::TAG,
        syntax_plugin_combo_headingatx::TAG,
        syntax_plugin_combo_headingwiki::TAG
    ];

    /**
     * The toc attribute that will store
     * the toc data between loop in the callstack
     * @var Call|null
     */
    private $tocData;
    /**
     * @var array an array to make sure that the id are unique
     */
    private $tocUniqueId = [];


    /**
     * @var int a counter that should at 0
     * at the end of the processing
     * +1 if an outline section was opened
     * -1 if an outline section was closed
     */
    private $outlineSectionBalance = 0;
    /**
     * The position in the text file
     * when a section was started
     * @var int[]
     */
    private $outlineSectionOpenTagPosition;


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
            $this->outlineSectionOpenTagPosition[] = $actualCall->getFirstMatchedCharacterPosition();
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
    private function closeSection(CallStack $callStack, $handler, $position)
    {

        $wikiHeadingEnabled = syntax_plugin_combo_headingwiki::isEnabled();
        if (!$wikiHeadingEnabled) {
            $call = Call::createNativeCall(
                self::EDIT_SECTION_CLOSE,
                array(),
                $position
            );
            $callStack->insertBefore($call);
        }
        $handler->setStatus('section', false);
    }

    /**
     * Technically add a div around the content below the heading
     * @param $callStack
     * @param $headingEntryCall
     * @param $handler
     */
    private
    static function openSection($callStack, $headingEntryCall, $handler)
    {

        $headingWikiEnabled = syntax_plugin_combo_headingwiki::isEnabled();
        if (!$headingWikiEnabled) {
            $openSectionCall = Call::createNativeCall(
                self::EDIT_SECTION_OPEN,
                array($headingEntryCall->getAttribute(syntax_plugin_combo_heading::LEVEL)),
                $headingEntryCall->getFirstMatchedCharacterPosition()
            );
            $callStack->insertAfter($openSectionCall);
            $callStack->next();
        }
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
     * Build the toc
     * And create the main are
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
         * Reset static
         * When running test, the class are not shutdown
         * We reset the static toc
         * Because we may parse secondary slot
         * We reset only on primary slots
         */
        try {
            $pageParsed = Page::createPageFromGlobalDokuwikiId();
        } catch (ExceptionNotFound $e) {
            LogUtility::msg("The running id is not set. We can't post process the page with heading and table of content");
            return;
        }

        if ($pageParsed->isPrimarySlot()) {
            $this->tocData = null;
            $this->tocUniqueId = [];
        }


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
                            $this->insertOpenSectionAfterAndCloseHeadingParsingStateAndNext(
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
                        $actualCall->getInstructionCall()[1][6] = MediaLink::LINKING_NOLINK_VALUE;
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
                                $this->insertOpenSectionAfterAndCloseHeadingParsingStateAndNext(
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
            $this->closeSection($callStack, $handler, $actualLastPosition);
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
            global $ACT;
            if (
                (
                    $headingTotalCounter === 0
                || $page->isSecondarySlot()
                )
                && $ACT === "show" // not dynamic in case of webcode or other
            ) {

                try {
                    $tag = EditButton::create("Slot Edit")->toTag();
                    if (!empty($tag)) { // page edit is not off
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
                } catch (ExceptionCompile $e) {
                    LogUtility::msg("Error while adding the edit button. Error: {$e->getMessage()}");
                }

            }

        }

        /**
         * Main Slots and TOC to the primary slots
         */
        if (LayoutManager::shouldMainAreaBeBuild($callStack, $pageParsed)) {

            LayoutManager::buildMainArea($callStack, $this->tocData, $pageParsed);

        }


    }

    /**
     * @param Call $headingEntryCall
     * @param Doku_Handler $handler
     * @param CallStack $callStack
     * @param $actualSectionState
     * @param $headingText
     * @param $actualHeadingParsingState
     */
    private function insertOpenSectionAfterAndCloseHeadingParsingStateAndNext(Call &$headingEntryCall, Doku_Handler &$handler, CallStack &$callStack, &$actualSectionState, &$headingText, &$actualHeadingParsingState)
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

            $level = $headingEntryCall->getAttribute("level");

            $id = $headingEntryCall->getAttribute("id");
            if ($id === null) {
                $id = sectionID($headingText, $this->tocUniqueId);
                $headingEntryCall->addAttribute("id", $id);
            }

            /**
             * TOC Data
             */
            $this->tocData[] = [
                'link' => "#$id",
                'title' => $headingText,
                'type' => 'ul',
                'level' => $level
            ];


            /**
             * Insert an entry call
             */
            self::openSection($callStack, $headingEntryCall, $handler);

            $actualSectionState = DOKU_LEXER_ENTER;

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
            if ($headingComboCounter === 1 && $headingTotalCounter !== 1) {
                /**
                 * If this is the first combo heading
                 * We need to close the previous to open
                 * this one
                 */
                $close = true;
            }
            if ($close) {
                self::closeSection($callStack, $handler, $lastActualPosition);
                $actualSectionState = DOKU_LEXER_EXIT;
            }
        }
    }

    /**
     * @param $headingText
     * @param Call $call
     */
    private
    static function addToTextHeading(&$headingText, Call $call)
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

    /**
     * @param CallStack $callStack
     * @param $position
     * @return void
     */
    private function closeOutlineSection(CallStack $callStack, $position)
    {
        $start = array_pop($this->outlineSectionOpenTagPosition);
        $call = $callStack->getActualCall();
        $end = null;
        if ($call !== null) {
            $end = $call->getFirstMatchedCharacterPosition();
        }
        $openSectionCall = Call::createComboCall(
            syntax_plugin_combo_section::TAG,
            DOKU_LEXER_EXIT,
            array(PluginUtility::POSITION => [$start, $end]),
            null,
            null,
            null,
            $position
        );
        $callStack->insertBefore($openSectionCall);
        $this->outlineSectionBalance--;
    }
}
