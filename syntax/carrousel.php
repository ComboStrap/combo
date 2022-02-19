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
    const BULLET_COUNT = "bullet-count";

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
                                $firstTemplateEnterTag->addClassName(self::GLIDE_SLIDE_CLASS);
                            }
                            // Lazy load
                            $templateCallStack->moveToStart();
                            self::setLazyLoadToHtmlOnImageTagUntilTheEndOfTheStack($templateCallStack);
                            $templateEndCall->setPluginData(syntax_plugin_combo_template::CALLSTACK, $templateCallStack->getStack());
                        }
                    } else {
                        $actualCall->addClassName(self::GLIDE_SLIDE_CLASS);
                        $childrenCount = 1;
                        while ($actualCall = $callStack->moveToNextSiblingTag()) {
                            $actualCall->addClassName(self::GLIDE_SLIDE_CLASS);
                            $childrenCount++;
                        }
                        // Lazy load
                        $callStack->moveToEnd();
                        $callStack->moveToPreviousCorrespondingOpeningCall();
                        self::setLazyLoadToHtmlOnImageTagUntilTheEndOfTheStack($callStack);
                    }
                }
                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::ATTRIBUTES => $openingCall->getAttributes(),
                    self::BULLET_COUNT => $childrenCount
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

                    $slideMinimalWidth = $tagAttributes->getValueAndRemoveIfPresent(self::ELEMENT_WIDTH_ATTRIBUTE);
                    $slideMinimalWidthData = "";
                    try {
                        if ($slideMinimalWidth !== null) {
                            $slideMinimalWidth = Dimension::toPixelValue($slideMinimalWidth);
                            $slideMinimalWidthData = "data-" . self::ELEMENT_WIDTH_ATTRIBUTE . "=\"$slideMinimalWidth\"";
                        }
                    } catch (ExceptionCombo $e) {
                        $slideMinimalWidth = 200;
                        LogUtility::msg("The minimal width value ($slideMinimalWidth) is not a valid value. Error: {$e->getMessage()}");
                    }

                    $renderer->doc .= <<<EOF
<div class="carrousel-combo glide" $slideMinimalWidthData>
  <div class="glide__track" data-glide-el="track">
    <div class="glide__slides">
EOF;

                    /**
                     * Snippet
                     */
                    $snippetManager = PluginUtility::getSnippetManager();
                    $snippetId = self::TAG;

                    $snippetManager->attachCssSnippetForSlot($snippetId);
                    $snippetManager->attachJavascriptSnippetForSlot($snippetId);
                    // https://www.jsdelivr.com/package/npm/@glidejs/glide
                    $tags["link"][] =
                        [
                            "rel" => "stylesheet",
                            "href" => "https://cdn.jsdelivr.net/npm/@glidejs/glide@3.5.2/dist/css/glide.core.min.css",
                            "integrity" => "sha256-bmdlmBAVo1Q6XV2cHiyaBuBfe9KgYQhCrfQmoRq8+Sg=",
                            "crossorigin" => "anonymous"
                        ];

                    if (PluginUtility::isDevOrTest()) {

                        $tags["script"][] = [
                            "src" => "https://cdn.jsdelivr.net/npm/@glidejs/glide@3.5.2/dist/glide.js",
                            "integrity" => "sha256-zkYoJ1XwwGA4FbdmSdTz28y5PtHT8O/ZKzUAuQsmhKg=",
                            "crossorigin" => "anonymous"
                        ];

                    } else {

                        $tags["script"][] = [
                            "src" => "https://cdn.jsdelivr.net/npm/@glidejs/glide@3.5.2/dist/glide.min.js",
                            "integrity" => "sha256-cXguqBvlUaDoW4nGjs4YamNC2mlLGJUOl64bhts/ztU=",
                            "crossorigin" => "anonymous"
                        ];

                    }
                    $snippetManager->attachTagsForSlot($snippetId)->setTags($tags);

                    // Theme customized from the below official theme
                    // https://cdn.jsdelivr.net/npm/@glidejs/glide@3.5.2/dist/css/glide.theme.css
                    $snippetManager->attachCssSnippetForSlot($snippetId)
                        ->setCritical(false);
                    break;

                case DOKU_LEXER_UNMATCHED :

                    $renderer->doc .= PluginUtility::renderUnmatched($data);
                    break;

                case DOKU_LEXER_EXIT :

                    $renderer->doc .= <<<EOF
</div>
  </div>
EOF;

                    $tagAttributes = TagAttributes::createFromCallStackArray($data[PluginUtility::ATTRIBUTES]);
                    $control = $tagAttributes->getValue(self::CONTROL_ATTRIBUTE);
                    if ($control !== "none") {
                        $escapedLessThan = PluginUtility::htmlEncode("|<");
                        $escapedGreaterThan = PluginUtility::htmlEncode("|>");


                        $renderer->doc .= <<<EOF
<div>
  <div class="d-none d-sm-block" data-glide-el="controls">
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
                        $elementCount = $data[self::BULLET_COUNT];
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
            return true;
        }


        // unsupported $mode
        return false;

    }


}

