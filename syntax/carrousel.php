<?php


use ComboStrap\Call;
use ComboStrap\CallStack;
use ComboStrap\Dimension;
use ComboStrap\ExceptionCombo;
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
    const CONTROL_ATTRIBUTE = "control";
    const GLIDE_SLIDE_CLASS = "glide__slide";

    /**
     * The number of element
     * (we get it by scanning the element or
     * via the {@link syntax_plugin_combo_iterator} that set it up)
     */
    const ELEMENT_COUNT = "bullet-count";

    /**
     * To center the image inside a link in a carrousel
     */
    const MEDIA_CENTER_LINK_CLASS = "justify-content-center align-items-center d-flex";
    const ELEMENTS_MIN_ATTRIBUTE = "elements-min";
    const ELEMENTS_MIN_DEFAULT = 3;

    private static function isCarrousel($data, TagAttributes $tagAttributes): bool
    {
        $elementCount = $data[self::ELEMENT_COUNT];
        $elementWidth = $tagAttributes->getValue(self::ELEMENT_WIDTH_ATTRIBUTE);
        if ($elementWidth !== null) {
            $elementsMin = $tagAttributes->getValue(self::ELEMENTS_MIN_ATTRIBUTE, self::ELEMENTS_MIN_DEFAULT);
            if ($elementCount < $elementsMin) {
                return false;
            }
        }
        return true;
    }


    private static function madeChildElementCarrouselAware(?Call $childCarrouselElement)
    {
        $tagName = $childCarrouselElement->getTagName();
        if ($tagName === syntax_plugin_combo_media::TAG) {
            $childCarrouselElement->setAttribute(syntax_plugin_combo_media::LINK_CLASS_ATTRIBUTE, self::GLIDE_SLIDE_CLASS . " " . self::MEDIA_CENTER_LINK_CLASS);
        } else {
            $childCarrouselElement->addClassName(self::GLIDE_SLIDE_CLASS);
        }

    }

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
                $tagAttributes = TagAttributes::createFromTagMatch($match);
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
                $childrenCount = null;
                if ($actualCall !== false) {
                    if ($actualCall->getTagName() === syntax_plugin_combo_template::TAG) {
                        $templateEndCall = $callStack->moveToNextCorrespondingExitTag();
                        $templateCallStackInstructions = $templateEndCall->getPluginData(syntax_plugin_combo_template::CALLSTACK);
                        if ($templateCallStackInstructions !== null) {
                            $templateCallStack = CallStack::createFromInstructions($templateCallStackInstructions);
                            // The glide class
                            $templateCallStack->moveToStart();
                            $firstTemplateEnterTag = $templateCallStack->moveToFirstEnterTag();
                            if ($firstTemplateEnterTag !== false) {
                                self::madeChildElementCarrouselAware($firstTemplateEnterTag);
                            }
                            // Lazy load
                            $templateCallStack->moveToStart();
                            self::setLazyLoadToHtmlOnImageTagUntilTheEndOfTheStack($templateCallStack);
                            $templateEndCall->setPluginData(syntax_plugin_combo_template::CALLSTACK, $templateCallStack->getStack());
                        }
                    } else {
                        self::madeChildElementCarrouselAware($actualCall);
                        $childrenCount = 1;
                        while ($actualCall = $callStack->moveToNextSiblingTag()) {
                            self::madeChildElementCarrouselAware($actualCall);
                            $childrenCount++;
                        }
                        $openingCall->setPluginData(self::ELEMENT_COUNT, $childrenCount);
                        // Lazy load
                        $callStack->moveToEnd();
                        $callStack->moveToPreviousCorrespondingOpeningCall();
                        self::setLazyLoadToHtmlOnImageTagUntilTheEndOfTheStack($callStack);
                    }
                }
                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::ATTRIBUTES => $openingCall->getAttributes(),
                    self::ELEMENT_COUNT => $childrenCount,
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

                    $slideMinimalWidth = $tagAttributes->getValue(self::ELEMENT_WIDTH_ATTRIBUTE);
                    $slideMinimalWidthData = "";
                    if ($slideMinimalWidth !== null) {
                        try {
                            $slideMinimalWidth = Dimension::toPixelValue($slideMinimalWidth);
                            $slideMinimalWidthData = "data-" . self::ELEMENT_WIDTH_ATTRIBUTE . "=\"$slideMinimalWidth\"";
                        } catch (ExceptionCombo $e) {
                            $slideMinimalWidth = 250;
                            LogUtility::msg("The minimal width value ($slideMinimalWidth) is not a valid value. Error: {$e->getMessage()}");
                        }
                    }
                    $snippetManager = PluginUtility::getSnippetManager();
                    $snippetId = self::TAG;
                    $carrouselClass = "carrousel-combo";
                    $isCarrousel = self::isCarrousel($data, $tagAttributes);
                    if ($isCarrousel) {

                        $renderer->doc .= <<<EOF
<div class="$carrouselClass glide" $slideMinimalWidthData>
  <div class="glide__track" data-glide-el="track">
    <div class="glide__slides">
EOF;

                        $snippetManager->attachCssInternalStyleSheetForSlot($snippetId);
                        // https://www.jsdelivr.com/package/npm/@glidejs/glide
                        $snippetManager->attachCssExternalStyleSheetForSlot($snippetId,
                            "https://cdn.jsdelivr.net/npm/@glidejs/glide@3.5.2/dist/css/glide.core.min.css",
                            "sha256-bmdlmBAVo1Q6XV2cHiyaBuBfe9KgYQhCrfQmoRq8+Sg="
                        );
                        if (PluginUtility::isDev()) {

                            $javascriptSnippet = $snippetManager->attachJavascriptLibraryForSlot($snippetId,
                                "https://cdn.jsdelivr.net/npm/@glidejs/glide@3.5.2/dist/glide.js",
                                "sha256-zkYoJ1XwwGA4FbdmSdTz28y5PtHT8O/ZKzUAuQsmhKg="
                            );

                        } else {
                            $javascriptSnippet = $snippetManager->attachJavascriptLibraryForSlot($snippetId,
                                "https://cdn.jsdelivr.net/npm/@glidejs/glide@3.5.2/dist/glide.min.js",
                                "sha256-cXguqBvlUaDoW4nGjs4YamNC2mlLGJUOl64bhts/ztU="
                            );
                        }
                        $javascriptSnippet->setDoesManipulateTheDomOnRun(false);

                        // Theme customized from the below official theme
                        // https://cdn.jsdelivr.net/npm/@glidejs/glide@3.5.2/dist/css/glide.theme.css
                        $snippetManager->attachCssInternalStyleSheetForSlot($snippetId)
                            ->setCritical(false);
                    } else {
                        // gutter is done with the margin because we don't wrap the child in a cell container.
                        $renderer->doc .= <<<EOF
<div class="$carrouselClass row justify-content-center" $slideMinimalWidthData>
EOF;
                    }
                    $snippetManager->attachInternalJavascriptForSlot($snippetId);
                    break;

                case DOKU_LEXER_UNMATCHED :

                    $renderer->doc .= PluginUtility::renderUnmatched($data);
                    break;

                case DOKU_LEXER_EXIT :

                    $tagAttributes = TagAttributes::createFromCallStackArray($data[PluginUtility::ATTRIBUTES]);
                    $isCarrousel = self::isCarrousel($data, $tagAttributes);

                    switch ($isCarrousel) {
                        case false:
                            // grid
                            $renderer->doc .= "</div>";
                            break;
                        default:
                        case true:
                            $renderer->doc .= <<<EOF
</div>
  </div>
EOF;

                            $tagAttributes = TagAttributes::createFromCallStackArray($data[PluginUtility::ATTRIBUTES]);
                            $control = $tagAttributes->getValue(self::CONTROL_ATTRIBUTE);
                            if ($control !== "none") {
                                // move per view
                                // https://github.com/glidejs/glide/issues/346#issuecomment-1046137773
                                $escapedLessThan = PluginUtility::htmlEncode("|<");
                                $escapedGreaterThan = PluginUtility::htmlEncode("|>");

                                $minimumWidth = $tagAttributes->getValue(self::ELEMENT_WIDTH_ATTRIBUTE);
                                $classDontShowOnSmallDevice = "";
                                if ($minimumWidth !== null) {
                                    // not a one by one (not a gallery)
                                    $classDontShowOnSmallDevice = "class=\"d-none d-sm-block\"";
                                }
                                $renderer->doc .= <<<EOF
<div>
  <div $classDontShowOnSmallDevice data-glide-el="controls">
    <button class="glide__arrow glide__arrow--left" data-glide-dir="$escapedLessThan">
      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24">
        <path d="M0 12l10.975 11 2.848-2.828-6.176-6.176H24v-3.992H7.646l6.176-6.176L10.975 1 0 12z"></path>
      </svg>
    </button>
    <button class="glide__arrow glide__arrow--right" data-glide-dir="$escapedGreaterThan">
      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24">
        <path d="M13.025 1l-2.847 2.828 6.176 6.176h-16.354v3.992h16.354l-6.176 6.176 2.847 2.828 10.975-11z"></path>
      </svg>
    </button>
  </div>
  <div class="glide__bullets d-none d-sm-block" data-glide-el="controls[nav]">
EOF;
                                $elementCount = $data[self::ELEMENT_COUNT];
                                for ($i = 0; $i < $elementCount; $i++) {
                                    $activeClass = "";
                                    if ($i === 0) {
                                        $activeClass = " glide__bullet--activeClass";
                                    }
                                    $renderer->doc .= <<<EOF
    <button class="glide__bullet{$activeClass}" data-glide-dir="={$i}"></button>
EOF;
                                }
                                $renderer->doc .= <<<EOF
  </div>
</div>
EOF;
                            }
                            $renderer->doc .= "</div>";
                            break;

                    }


                    break;

            }
            return true;
        }


        // unsupported $mode
        return false;

    }


}

