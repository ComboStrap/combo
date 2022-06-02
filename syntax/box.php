<?php


// must be run within Dokuwiki
use ComboStrap\Align;
use ComboStrap\Call;
use ComboStrap\CallStack;
use ComboStrap\Dimension;
use ComboStrap\LogUtility;
use ComboStrap\PluginUtility;
use ComboStrap\TagAttributes;

if (!defined('DOKU_INC')) die();

/**
 * Class syntax_plugin_combo_box
 * Implementation of a div
 *
 */
class syntax_plugin_combo_box extends DokuWiki_Syntax_Plugin
{

    const TAG = "box";
    const TAG_ATTRIBUTE = "tag";
    const DEFAULT_TAG = "div";
    // Tag that may make external http requests are not authorized
    const NON_AUTHORIZED_TAG = ["script", "style", "img", "video"];

    /**
     * Syntax Type.
     *
     * Needs to return one of the mode types defined in $PARSER_MODES in parser.php
     * @see DokuWiki_Syntax_Plugin::getType()
     */
    function getType(): string
    {
        return 'container';
    }

    /**
     * How Dokuwiki will add P element
     *
     *  * 'normal' - Inline
     *  * 'block' - Block (p are not created inside)
     *  * 'stack' - Block (p can be created inside)
     *
     * @see DokuWiki_Syntax_Plugin::getPType()
     */
    function getPType(): string
    {
        /**
         * Not stack, otherwise it creates p
         * and as box is used mostly for layout purpose, it breaks the
         * {@link \ComboStrap\Align} flex css attribute
         */
        return 'block';
    }

    /**
     * @return array
     * Allow which kind of plugin inside
     *
     * Array('baseonly','container', 'formatting', 'substition', 'protected', 'disabled', 'paragraphs')
     *
     * Return an array of one or more of the mode types {@link $PARSER_MODES} in Parser.php
     */
    function getAllowedTypes(): array
    {
        return array('container', 'formatting', 'substition', 'protected', 'disabled', 'paragraphs');
    }

    public function accepts($mode): bool
    {

        return syntax_plugin_combo_preformatted::disablePreformatted($mode);

    }

    function getSort(): int
    {
        return 201;
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

    function handle($match, $state, $pos, Doku_Handler $handler)
    {

        switch ($state) {

            case DOKU_LEXER_ENTER :
                $defaultAttributes[self::TAG_ATTRIBUTE] = self::DEFAULT_TAG;
                $knownTypes = [];
                $attributes = TagAttributes::createFromTagMatch($match, $defaultAttributes, $knownTypes);
                $tag = $attributes->getValue(self::TAG_ATTRIBUTE);
                if (in_array($tag, self::NON_AUTHORIZED_TAG)) {
                    LogUtility::error("The html tag ($tag) is not authorized.");
                    $attributes->setComponentAttributeValue(self::TAG_ATTRIBUTE, self::DEFAULT_TAG);
                }
                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::ATTRIBUTES => $attributes->toCallStackArray()
                );

            case DOKU_LEXER_UNMATCHED :
                return PluginUtility::handleAndReturnUnmatchedData(self::TAG, $match, $handler);

            case DOKU_LEXER_EXIT :

                $callStack = CallStack::createFromHandler($handler);

                /**
                 * Check children align
                 */
                $openingTag = $callStack->moveToPreviousCorrespondingOpeningCall();
                $align = $openingTag->getAttribute(Align::ALIGN_ATTRIBUTE);
                if ($align !== null && strpos($align, "children") !== false) {

                    /**
                     * Scan to see the type of content
                     * children can be use against one inline element or more block element
                     * Not against more than one inline children element
                     *
                     * Retrieve the number of inline element
                     * ie enter, special and box unmatched
                     */
                    $inlineTagFounds = [];
                    while ($actual = $callStack->next()) {

                        switch ($actual->getState()) {
                            case DOKU_LEXER_EXIT:
                                continue 2;
                            case DOKU_LEXER_UNMATCHED:
                                if ($actual->getTagName() !== self::TAG) {
                                    continue 2;
                                } else {
                                    // Not a problem is the text are only space
                                    if (trim($actual->getCapturedContent()) === "") {
                                        continue 2;
                                    }
                                }
                        }
                        if ($actual->getDisplay() == Call::INLINE_DISPLAY) {
                            $tagName = $actual->getTagName();
                            if ($actual->getTagName() === self::TAG && $actual->getState() === DOKU_LEXER_UNMATCHED) {
                                $tagName = "$tagName text";
                            }
                            $inlineTagFounds[] = $tagName;
                        }
                    }
                    if (count($inlineTagFounds) > 1) {
                        // You can't use children align value against inline
                        LogUtility::warning("The `children` align attribute ($align) on the box component was apply against more than one inline elements (ie " . implode(", ", $inlineTagFounds) . "). If you don't get what you want use a text align value such as `text-center`");
                    }
                }

                /**
                 * Add a scroll toggle if the
                 * box is constrained by height
                 */
                Dimension::addScrollToggleOnClickIfNoControl($callStack);

                /**
                 *
                 */
                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::ATTRIBUTES => $openingTag->getAttributes()
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
            $state = $data[PluginUtility::STATE];
            switch ($state) {
                case DOKU_LEXER_ENTER :
                    $attributes = $data[PluginUtility::ATTRIBUTES];
                    $tagAttributes = TagAttributes::createFromCallStackArray($attributes, self::TAG);
                    $tagName = $tagAttributes->getValueAndRemove(self::TAG_ATTRIBUTE, self::DEFAULT_TAG);
                    $renderer->doc .= $tagAttributes->toHtmlEnterTag($tagName) . DOKU_LF;
                    break;

                case DOKU_LEXER_UNMATCHED :
                    $renderer->doc .= PluginUtility::renderUnmatched($data);
                    break;

                case DOKU_LEXER_EXIT :
                    $attributes = $data[PluginUtility::ATTRIBUTES];
                    $tagAttributes = TagAttributes::createFromCallStackArray($attributes, self::TAG);
                    $tagName = $tagAttributes->getValueAndRemove(self::TAG_ATTRIBUTE, self::DEFAULT_TAG);
                    $renderer->doc .= "</$tagName>";
                    break;
            }
            return true;
        }

        // unsupported $mode
        return false;
    }


}

