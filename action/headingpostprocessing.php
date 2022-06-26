<?php


use ComboStrap\Call;
use ComboStrap\CallStack;
use ComboStrap\DataType;
use ComboStrap\DynamicRender;
use ComboStrap\ExceptionCompile;
use ComboStrap\ExceptionNotFound;
use ComboStrap\LayoutMainAreaBuilder;
use ComboStrap\LogUtility;
use ComboStrap\MediaLink;
use ComboStrap\Outline;
use ComboStrap\Page;
use ComboStrap\EditButton;
use ComboStrap\PluginUtility;
use ComboStrap\Site;
use ComboStrap\TocUtility;

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

    const CANONICAL = Outline::CANONICAL;

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
     * A Stack of start call used
     * to create {@link EditButton}
     * We capture the call because they are mostly heading
     * and the heading label is known at the end tag
     *
     * The call is popped off to create an edit section
     * @var Call[]
     */
    private $editButtonStartCalls;


    /**
     * Insert the HTML section
     * @param CallStack $callStack
     * @param Call $actualCall
     * @param int $actualLastPosition
     */
    private function openOutlineSection(CallStack $callStack, Call $actualCall, int $actualLastPosition)
    {
        if ($actualCall->getContext() == syntax_plugin_combo_heading::TYPE_OUTLINE) {
            try {
                $level = DataType::toInteger($actualCall->getAttribute(syntax_plugin_combo_heading::LEVEL));
            } catch (ExceptionCompile $e) {
                LogUtility::error("The level in the call ($actualCall) is not an integer", self::CANONICAL);
                return;
            }
            if ($level === 1) {
                /**
                 * no root section
                 * because we will delete the callstack
                 * until the header content if present
                 * It's easier and don't mess with the outline
                 *
                 * You also don't need to have a edit button document
                 * on the whole document
                 */
                return;
            }
            $this->outlineSectionBalance++;

            $call = Call::createComboCall(
                syntax_plugin_combo_section::TAG,
                DOKU_LEXER_ENTER,
                array(syntax_plugin_combo_heading::LEVEL => $level),
                $actualLastPosition
            );
            $callStack->insertBefore($call);

            $this->editButtonStartCalls[] = $actualCall;
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
         * Reset static
         * When running test, the class are not shutdown
         * We reset the static toc
         * Because we may parse secondary slot
         * We reset only on primary slots
         */
        try {
            $pageParsed = Page::createPageFromGlobalDokuwikiId();
        } catch (ExceptionNotFound $e) {
            LogUtility::msg("The running global ID is not set. We can't post process the page with heading and table of content");
            return;
        }

        if (!$pageParsed->isPrimarySlot()) {
            return;
        }

        /**
         * @var Doku_Handler $handler
         */
        $handler = $event->data;
        $callStack = CallStack::createFromHandler($handler);
        $outline = Outline::createFromCallStack($callStack);

        global $ACT;
        switch ($ACT) {
            case DynamicRender::DYNAMIC_RENDERING:
                // no outline or edit button for dynamic rendering
                // but closing of atx heading
                $handler->calls = $outline->toDynamicInstructionCalls();
                return;
            case "show":
                break;
            default:
                // No outline if not show (ie admin, edit, ...)
                return;
        }

        if (Site::getTemplate() !== Site::STRAP_TEMPLATE_NAME) {
            $handler->calls = $outline->toDefaultTemplateInstructionCalls();
        } else {
            $handler->calls = $outline->toHtmlSectionOutlineCalls();
        }

        /**
         * TOC
         */
        $toc = $outline->getTocDokuwikiFormat();
        if (TocUtility::shouldTocBePrinted($toc)) {
            global $TOC;
            $TOC = $toc;
        }


        /**
         * Main Slots and TOC to the primary slots
         */
//        if (LayoutMainAreaBuilder::shouldMainAreaBeBuild($callStack, $pageParsed)) {
//            LayoutMainAreaBuilder::headingDisplayNone($callStack, $pageParsed);
//            //LayoutMainAreaBuilder::buildMainArea($callStack, $this->tocData, $pageParsed);
//        }

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
            syntax_plugin_combo_heading::processHeadingMetadataH1($level, $headingText);

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
     * @deprecated for {@link \ComboStrap\OutlineSection::getLabel()}
     */
    public
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

        if ($this->outlineSectionBalance <= 0) {
            return;
        }


        /**
         * Edit button
         */
        $startCall = array_pop($this->editButtonStartCalls);
        $enabled = Site::isSectionEditingEnabled();
        if ($startCall !== null && $enabled) {
            $text = $startCall->getAttribute(syntax_plugin_combo_heading::HEADING_TEXT_ATTRIBUTE);
            $end = null;
            if (!$callStack->isAtEnd()) {
                $call = $callStack->getActualCall();
                $end = $call->getFirstMatchedCharacterPosition();
            }
            $editButton = EditButton::create("Edit the section $text")
                ->setStartPosition($startCall->getFirstMatchedCharacterPosition())
                ->setEndPosition($end);
            $callStack->insertBefore($editButton->toComboCallComboFormat());
        }


        $openSectionCall = Call::createComboCall(
            syntax_plugin_combo_section::TAG,
            DOKU_LEXER_EXIT,
            [],
            null,
            null,
            $position
        );
        $callStack->insertBefore($openSectionCall);
        $this->outlineSectionBalance--;
    }
}
