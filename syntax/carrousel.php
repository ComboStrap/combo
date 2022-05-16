<?php


use ComboStrap\Call;
use ComboStrap\CallStack;
use ComboStrap\ConditionalLength;
use ComboStrap\ExceptionCompile;
use ComboStrap\LogUtility;
use ComboStrap\MediaLink;
use ComboStrap\PluginUtility;
use ComboStrap\TagAttributes;

require_once(__DIR__ . '/../ComboStrap/PluginUtility.php');


/**
 * Carrousel
 *
 * We loved
 * https://github.com/OwlCarousel2/OwlCarousel2
 * but it's deprecated and
 * send us to
 * https://github.com/ganlanyuan/tiny-slider
 * But it used as gutter the padding not the margin (http://ganlanyuan.github.io/tiny-slider/demo/#gutter_wrapper)
 * Then we found
 * https://glidejs.com/
 *
 *
 *
 */
class syntax_plugin_combo_carrousel extends DokuWiki_Syntax_Plugin
{


    const TAG = 'carrousel';
    const CANONICAL = self::TAG;
    const ELEMENT_WIDTH_ATTRIBUTE = "element-width";

    const GLIDE_SLIDE_CLASS = "glide__slide";


    /**
     * To center the image inside a link in a carrousel
     */
    const MEDIA_CENTER_LINK_CLASS = "justify-content-center align-items-center d-flex";
    const ELEMENTS_MIN_ATTRIBUTE = "elements-min";
    const ELEMENTS_MIN_DEFAULT = 3;
    const CONTROL_ATTRIBUTE = "control";


    /**
     * Glide copy the HTML element and lozad does not see element that are not visible
     * The element non-visible are not processed by lozad
     * We set lazy loading to HTML loading attribute
     */
    private static function setLazyLoadToHtmlOnImageTagUntilTheEndOfTheStack(CallStack $callStack)
    {
        while ($actualCall = $callStack->next()) {
            if ($actualCall->getState() === DOKU_LEXER_SPECIAL && in_array($actualCall->getTagName(), Call::IMAGE_TAGS)) {
                $actualCall->addAttribute(
                    MediaLink::LAZY_LOAD_METHOD,
                    MediaLink::LAZY_LOAD_METHOD_HTML_VALUE
                );
            }
        }
    }


    function getType(): string
    {
        return 'container';
    }

    /**
     * How DokuWiki will add P element
     *
     *  * 'normal' - The plugin can be used inside paragraphs
     *  * 'block'  - Open paragraphs need to be closed before plugin output - block should not be inside paragraphs
     *  * 'stack'  - Special case. Plugin wraps other paragraphs. - Stacks can contain paragraphs
     *
     * @see DokuWiki_Syntax_Plugin::getPType()
     */
    function getPType(): string
    {
        return 'block';
    }

    /**
     * @return array
     * Allow which kind of plugin inside
     *
     * No one of array('baseonly','container', 'formatting', 'substition', 'protected', 'disabled', 'paragraphs')
     * because we manage self the content and we call self the parser
     *
     * Return an array of one or more of the mode types {@link $PARSER_MODES} in Parser.php
     */
    function getAllowedTypes(): array
    {
        return array('baseonly', 'container', 'formatting', 'substition', 'protected', 'disabled', 'paragraphs');
    }

    function getSort(): int
    {
        return 199;
    }

    public function accepts($mode): bool
    {
        return syntax_plugin_combo_preformatted::disablePreformatted($mode);
    }


    function connectTo($mode)
    {


        $pattern = PluginUtility::getContainerTagPattern(self::TAG);
        $this->Lexer->addEntryPattern($pattern, $mode, PluginUtility::getModeFromTag($this->getPluginComponent()));


    }


    function postConnect()
    {

        $this->Lexer->addExitPattern('</' . self::TAG . '>', PluginUtility::getModeFromTag($this->getPluginComponent()));


    }

    /**
     *
     * The handle function goal is to parse the matched syntax through the pattern function
     * and to return the result for use in the renderer
     * This result is always cached until the page is modified.
     * @param string $match
     * @param int $state
     * @param int $pos - byte position in the original source file
     * @param Doku_Handler $handler
     * @return array
     * @see DokuWiki_Syntax_Plugin::handle()
     *
     */
    function handle($match, $state, $pos, Doku_Handler $handler): array
    {

        switch ($state) {

            case DOKU_LEXER_ENTER :

                $defaultAttributes = [];
                $tagAttributes = TagAttributes::createFromTagMatch($match, $defaultAttributes);
                $callStack = CallStack::createFromHandler($handler);
                $parent = $callStack->moveToParent();
                $context = null;
                if ($parent !== false) {
                    $context = $parent->getTagName();
                }
                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::ATTRIBUTES => $tagAttributes->toCallStackArray(),
                    PluginUtility::CONTEXT => $context
                );

            case DOKU_LEXER_UNMATCHED :

                return PluginUtility::handleAndReturnUnmatchedData(self::TAG, $match, $handler);


            case DOKU_LEXER_EXIT :

                $callStack = CallStack::createFromHandler($handler);
                $openingCall = $callStack->moveToPreviousCorrespondingOpeningCall();
                $actualCall = $callStack->moveToFirstChildTag();
                if ($actualCall !== false) {
                    if ($actualCall->getTagName() === syntax_plugin_combo_template::TAG) {
                        $templateEndCall = $callStack->moveToNextCorrespondingExitTag();
                        $templateCallStackInstructions = $templateEndCall->getPluginData(syntax_plugin_combo_template::CALLSTACK);
                        if ($templateCallStackInstructions !== null) {
                            $templateCallStack = CallStack::createFromInstructions($templateCallStackInstructions);
                            // Lazy load
                            $templateCallStack->moveToStart();
                            self::setLazyLoadToHtmlOnImageTagUntilTheEndOfTheStack($templateCallStack);
                            $templateEndCall->setPluginData(syntax_plugin_combo_template::CALLSTACK, $templateCallStack->getStack());
                        }
                    } else {
                        // Lazy load
                        $callStack->moveToEnd();
                        $callStack->moveToPreviousCorrespondingOpeningCall();
                        self::setLazyLoadToHtmlOnImageTagUntilTheEndOfTheStack($callStack);
                    }
                }
                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::ATTRIBUTES => $openingCall->getAttributes()
                );


        }
        return array();

    }

    /**
     * Render the output
     * @param string $format
     * @param Doku_Renderer $renderer
     * @param array $data - what the function handle() return'ed
     * @return boolean - rendered correctly? (however, returned value is not used at the moment)
     * @see DokuWiki_Syntax_Plugin::render()
     *
     *
     */
    function render($format, Doku_Renderer $renderer, $data): bool
    {


        if ($format == 'xhtml') {

            /** @var Doku_Renderer_xhtml $renderer */
            $state = $data [PluginUtility::STATE];
            switch ($state) {
                case DOKU_LEXER_ENTER :

                    $tagAttributes = TagAttributes::createFromCallStackArray($data[PluginUtility::ATTRIBUTES], self::TAG);

                    /**
                     * Control
                     */
                    $control = $tagAttributes->getValueAndRemoveIfPresent(self::CONTROL_ATTRIBUTE);
                    if ($control !== null) {
                        $tagAttributes->addComponentAttributeValue("data-" . self::CONTROL_ATTRIBUTE, $control);
                    }

                    /**
                     * Element Min
                     */
                    $elementsMin = $tagAttributes->getValueAndRemoveIfPresent(self::ELEMENTS_MIN_ATTRIBUTE, self::ELEMENTS_MIN_DEFAULT);
                    $tagAttributes->addComponentAttributeValue("data-" . self::ELEMENTS_MIN_ATTRIBUTE, $elementsMin);

                    /**
                     * Minimal Width
                     */
                    $slideMinimalWidth = $tagAttributes->getValueAndRemoveIfPresent(self::ELEMENT_WIDTH_ATTRIBUTE);
                    if ($slideMinimalWidth !== null) {
                        try {
                            $slideMinimalWidth = ConditionalLength::createFromString($slideMinimalWidth)->toPixelNumber();
                            $tagAttributes->addComponentAttributeValue("data-" . self::ELEMENT_WIDTH_ATTRIBUTE, $slideMinimalWidth);
                        } catch (ExceptionCompile $e) {
                            LogUtility::msg("The minimal width value ($slideMinimalWidth) is not a valid value. Error: {$e->getMessage()}");
                        }
                    }
                    $renderer->doc .= $tagAttributes->toHtmlEnterTag("div");


                    $snippetManager = PluginUtility::getSnippetManager();
                    $snippetId = self::TAG;

                    // Theme customized from the below official theme
                    // https://cdn.jsdelivr.net/npm/@glidejs/glide@3.5.2/dist/css/glide.theme.css
                    $snippetManager->attachCssInternalStyleSheetForSlot($snippetId)
                        ->setCritical(false);

                    /**
                     * The dependency first
                     */
                    $snippetManager->attachInternalJavascriptForSlot("combo-loader");
                    $snippetManager->attachInternalJavascriptForSlot($snippetId);

                    break;

                case DOKU_LEXER_UNMATCHED :

                    $renderer->doc .= PluginUtility::renderUnmatched($data);
                    break;

                case DOKU_LEXER_EXIT :

                    $renderer->doc .= "</div>";
                    break;

            }
            return true;
        }


        // unsupported $mode
        return false;

    }


}

