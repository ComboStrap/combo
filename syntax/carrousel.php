<?php


use ComboStrap\CallStack;
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
                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::ATTRIBUTES => $tagAttributes->toCallStackArray()
                );

            case DOKU_LEXER_UNMATCHED :

                return PluginUtility::handleAndReturnUnmatchedData(self::TAG, $match, $handler);


            case DOKU_LEXER_EXIT :

                $callStack = CallStack::createFromHandler($handler);
                $callStack->moveToPreviousCorrespondingOpeningCall();
                $actualCall = $callStack->moveToFirstChildTag();
                if ($actualCall !== false) {
                    $actualCall->addClassName("glide__slide");
                    while ($actualCall = $callStack->moveToNextSiblingTag()) {
                        $actualCall->addClassName("glide__slide");
                    }
                }
                return array(
                    PluginUtility::STATE => $state
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
                    $renderer->doc .= <<<EOF
<div class="carrousel-combo glide">
  <div class="slider__track glide__track" data-glide-el="track">
    <ul class="slider__slides glide__slides">
EOF;

                    /**
                     * Snippet
                     */
                    $snippetManager = PluginUtility::getSnippetManager();
                    $snippetId = self::TAG;

                    $snippetManager->attachCssSnippetForSlot($snippetId);
                    $snippetManager->attachJavascriptSnippetForSlot($snippetId);
                    // https://www.jsdelivr.com/package/npm/@glidejs/glide
                    $snippetManager->attachTagsForSlot($snippetId)->setTags(
                        array(
                            "script" =>
                                [
                                    array(
                                        "src" => "https://cdn.jsdelivr.net/npm/@glidejs/glide@3.5.2/dist/glide.min.js",
                                        "integrity" => "sha256-cXguqBvlUaDoW4nGjs4YamNC2mlLGJUOl64bhts/ztU=",
                                        "crossorigin" => "anonymous"
                                    )
                                ],
                            "link" =>
                                [
                                    array(
                                        "rel" => "stylesheet",
                                        "href" => "https://cdn.jsdelivr.net/npm/@glidejs/glide@3.5.2/dist/css/glide.core.css",
                                        "integrity" => "sha256-LJrGIMqDkLqFClxG6hffz/yOcRxFtp+iFfIIhSEA8lk=",
                                        "crossorigin" => "anonymous"
                                    )
                                ]
                        ));
                    // to customized
                    // https://cdn.jsdelivr.net/npm/@glidejs/glide@3.5.2/dist/css/glide.theme.css
                    $snippetManager->attachTagsForSlot($snippetId . "-theme")
                        ->setCritical(false)
                        ->setTags(
                            array("link" =>
                                [
                                    array(
                                        "rel" => "stylesheet",
                                        "href" => "https://cdn.jsdelivr.net/npm/@glidejs/glide@3.5.2/dist/css/glide.theme.min.css",
                                        "integrity" => "sha256-GgTH00L+A55Lmho3ZMp7xhGf6UYkv8I/8wLyhLLDXjo=",
                                        "crossorigin" => "anonymous"
                                    )
                                ]
                            )
                        );
                    break;

                case DOKU_LEXER_UNMATCHED :

                    $renderer->doc .= PluginUtility::renderUnmatched($data);
                    break;

                case DOKU_LEXER_EXIT :

                    $renderer->doc .= <<<EOF
</ul>
  </div>
  <div data-glide-el="controls">
    <button class="glide__arrow glide__arrow--left" data-glide-dir="<">
      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24">
        <path d="M0 12l10.975 11 2.848-2.828-6.176-6.176H24v-3.992H7.646l6.176-6.176L10.975 1 0 12z"></path>
      </svg>
    </button>
    <button class="glide__arrow glide__arrow--right" data-glide-dir=">">
      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24">
        <path d="M13.025 1l-2.847 2.828 6.176 6.176h-16.354v3.992h16.354l-6.176 6.176 2.847 2.828 10.975-11z"></path>
      </svg>
    </button>
  </div>
  <div class="glide__bullets" data-glide-el="controls[nav]">
      <button class="glide__bullet glide__bullet--active" data-glide-dir="=0"></button>
      <button class="glide__bullet" data-glide-dir="=1"></button>
      <button class="glide__bullet" data-glide-dir="=2"></button>
      <button class="glide__bullet" data-glide-dir="=3"></button>
      <button class="glide__bullet" data-glide-dir="=4"></button>
      <button class="glide__bullet" data-glide-dir="=5"></button>
      <button class="glide__bullet" data-glide-dir="=6"></button>
      <button class="glide__bullet" data-glide-dir="=7"></button>
      <button class="glide__bullet" data-glide-dir="=8"></button>
      <button class="glide__bullet" data-glide-dir="=9"></button>
  </div>
</div>
EOF;

                    break;

            }
            return true;
        }


        // unsupported $mode
        return false;

    }


}

