<?php


use ComboStrap\Call;
use ComboStrap\CallStack;
use ComboStrap\PluginUtility;
use ComboStrap\TagAttributes;


if (!defined('DOKU_INC')) die();

/**
 * Class syntax_plugin_combo_heading
 * Heading HTML super set
 *
 * It contains also all heading utility class
 *
 * Taking over {@link \dokuwiki\Parsing\ParserMode\Header}
 */
class syntax_plugin_combo_heading extends DokuWiki_Syntax_Plugin
{


    const TAG = "heading";
    const OLD_TITLE_TAG = "title"; // old tag
    const TAGS = [self::TAG, self::OLD_TITLE_TAG];

    /**
     * Header pattern that we expect ie  ==== Hello =====
     * Found in {@link \dokuwiki\Parsing\ParserMode\Header}
     * One modification is that it permits one `=` to get the h6
     */
    const HEADING_PATTERN = '[ \t]*={1,}[^\n]+={1,}[ \t]*(?=\n)';

    const TITLE = 'title';
    const LEVEL = 'level';
    const DISPLAY_BS_4 = "display-bs-4";
    const ALL_TYPES = ["h1", "h2", "h3", "h4", "h5", "h6", "d1", "d2", "d3", "d4"];
    const DISPLAY_TYPES = ["d1", "d2", "d3", "d4"];
    /**
     * An heading may be printed
     * as outline and should be in the toc
     */
    const TYPE_OUTLINE = "outline";
    const HEADING_TYPES = ["h1", "h2", "h3", "h4", "h5", "h6"];
    /**
     * The attribute that holds only the text of the heading
     * (used to create the id and the text in the toc)
     */
    const HEADING_TEXT_ATTRIBUTE = "heading_text";
    const TYPE_TITLE = "title";

    /**
     * @param Call|bool $parent
     * @return string the type of heading
     */
    public static function getHeadingType($parent)
    {
        if ($parent != false && $parent->getComponentName() != "section_open") {
            return self::TYPE_TITLE;
        } else {
            return self::TYPE_OUTLINE;
        }
    }

    /**
     * Reduce the end of the input string
     * to the first opening tag without the ">"
     * and returns the closing tag
     *
     * @param $input
     * @return array - the heading attributes as a string
     */
    public static function reduceToFirstOpeningTagAndReturnAttributes(&$input)
    {
        // the variable that will capture the attribute string
        $headingStartTagString = "";
        // Set to true when the heading tag has completed
        $endHeadingParsed = false;
        // The closing character `>` indicator of the start and end tag
        // true when found
        $endTagClosingCharacterParsed = false;
        $startTagClosingCharacterParsed = false;
        // We start from the edn
        $position = strlen($input) - 1;
        // tag attributes
        $tagAttributes = [];
        while ($position > 0) {
            $character = $input[$position];

            if ($character == "<") {
                if (!$endHeadingParsed) {
                    // We are at the beginning of the ending tag
                    $endHeadingParsed = true;
                } else {
                    // We have delete all character until the heading start tag
                    // add the last one and exit
                    $headingStartTagString = $character . $headingStartTagString;
                    break;
                }
            }

            if ($character == ">") {
                if (!$endTagClosingCharacterParsed) {
                    // We are at the beginning of the ending tag
                    $endTagClosingCharacterParsed = true;
                } else {
                    // We have delete all character until the heading start tag
                    $startTagClosingCharacterParsed = true;
                }
            }

            if ($startTagClosingCharacterParsed) {
                $headingStartTagString = $character . $headingStartTagString;
            }


            // position --
            $position--;

        }
        $input = substr($input, 0, $position);


        return PluginUtility::getTagAttributes($headingStartTagString);

    }

    /**
     * @param string $context
     * @param TagAttributes $tagAttributes
     * @param Doku_Renderer_xhtml $renderer
     * @param integer $pos
     */
    public static function renderOpeningTag($context, $tagAttributes, &$renderer, $pos = null)
    {

        /**
         * Variable
         */
        $type = $tagAttributes->getType();

        /**
         * Display class if any
         */
        $displayClass = null;

        /**
         * Level determination
         */
        $level = $tagAttributes->getValueAndRemove(syntax_plugin_combo_heading::LEVEL);
        if ($level == null) {
            /**
             * Old title type
             * from 1 to 4 to set the display heading
             */
            if (is_integer($type) && $type != 0) {
                $level = $type;
                $displayClass = "display-$level";
            }
            /**
             * Still null, check the type
             */
            if ($level == null) {
                if (in_array($type, self::ALL_TYPES)) {
                    $level = substr($type, 1);
                }
            }
            /**
             * Still null, default to level 3
             * Not level 1 because this is the top level heading
             * Not level 2 because this is the most used level and we can confound with it
             */
            if ($level == null) {
                $level = "3";
            }
        }

        /**
         * Display Heading
         * https://getbootstrap.com/docs/5.0/content/typography/#display-headings
         */
        if (in_array($type, self::DISPLAY_TYPES)) {
            $displayClass = "display-$level";
            if (\ComboStrap\Bootstrap::getBootStrapMajorVersion() == "4") {
                /**
                 * Make Bootstrap display responsive
                 */
                PluginUtility::getSnippetManager()->attachCssSnippetForBar(syntax_plugin_combo_heading::DISPLAY_BS_4);
            }
        }
        if ($displayClass != null) {
            $tagAttributes->addClassName($displayClass);
        }

        /**
         * Heading class
         * https://getbootstrap.com/docs/5.0/content/typography/#headings
         * Works on 4 and 5
         */
        if (in_array($type, self::HEADING_TYPES)) {
            $tagAttributes->addClassName($type);
        }

        /**
         * Card title Context class
         * TODO: should move to card
         */
        if (in_array($context, [syntax_plugin_combo_blockquote::TAG, syntax_plugin_combo_card::TAG])) {
            $tagAttributes->addClassName("card-title");
        }

        if ($context == self::TYPE_OUTLINE) {

            /**
             * Calling the {@link Doku_Renderer_xhtml::header()}
             * with the captured text to be Dokuwiki Template compatible
             * It will create the toc and the section editing
             */
            if ($tagAttributes->hasComponentAttribute(self::HEADING_TEXT_ATTRIBUTE)) {
                $tocText = $tagAttributes->getValueAndRemove(self::HEADING_TEXT_ATTRIBUTE);
            } else {
                $tocText = "Heading Text Not found";
                \ComboStrap\LogUtility::msg("The heading text was not found for the toc");
            }
            $renderer->header($tocText, $level, $pos);
            $attributes = syntax_plugin_combo_heading::reduceToFirstOpeningTagAndReturnAttributes($renderer->doc);
            foreach ($attributes as $key => $value) {
                $tagAttributes->addComponentAttributeValue($key, $value);
            }

        }

        /**
         * In dokuwiki, the description is called the `title`
         * We make sure that we don't have any side effect
         */
        $tagAttributes->removeComponentAttributeIfPresent(syntax_plugin_combo_heading::TITLE);

        /**
         * Printing
         */
        $renderer->doc .= $tagAttributes->toHtmlEnterTag("h$level");

    }

    /**
     * @param TagAttributes $tagAttributes
     * @return string
     */
    public static function renderClosingTag($tagAttributes)
    {
        $level = $tagAttributes->getValueAndRemove(syntax_plugin_combo_heading::LEVEL);

        return "</h$level>" . DOKU_LF;
    }


    function getType()
    {
        return 'formatting';
    }

    /**
     *
     * How Dokuwiki will add P element
     *
     *  * 'normal' - The plugin can be used inside paragraphs (inline)
     *  * 'block'  - Open paragraphs need to be closed before plugin output - block should not be inside paragraphs
     *  * 'stack'  - Special case. Plugin wraps other paragraphs. - Stacks can contain paragraphs
     *
     * @see DokuWiki_Syntax_Plugin::getPType()
     *
     * This is the equivalent of inline or block for css
     */
    function getPType()
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
    function getAllowedTypes()
    {
        return array('formatting', 'substition', 'protected', 'disabled', 'paragraphs');
    }

    /**
     * Less than {@link \dokuwiki\Parsing\ParserMode\Header::getSort()}
     * @return int
     */
    function getSort()
    {
        return 49;
    }


    function connectTo($mode)
    {
        /**
         * Title regexp
         */

        $this->Lexer->addSpecialPattern(self::HEADING_PATTERN, $mode, PluginUtility::getModeForComponent($this->getPluginComponent()));


        /**
         * Title tag
         */
        foreach (self::TAGS as $tag) {
            $this->Lexer->addEntryPattern(PluginUtility::getContainerTagPattern($tag), $mode, PluginUtility::getModeForComponent($this->getPluginComponent()));
        }
    }

    public function postConnect()
    {
        foreach (self::TAGS as $tag) {
            $this->Lexer->addExitPattern("</" . self::TAG . ">", PluginUtility::getModeForComponent($this->getPluginComponent()));
        }
    }


    function handle($match, $state, $pos, Doku_Handler $handler)
    {

        switch ($state) {


            case DOKU_LEXER_ENTER :

                $tagAttributes = TagAttributes::createFromTagMatch($match);
                $callStack = CallStack::createFromHandler($handler);
                $parent = $callStack->moveToParent();
                $context = "";
                if ($parent != false) {
                    $context = $parent->getTagName();
                }

                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::ATTRIBUTES => $tagAttributes->toCallStackArray(),
                    PluginUtility::CONTEXT => $context
                );

            case DOKU_LEXER_UNMATCHED :

                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::PAYLOAD => PluginUtility::htmlEncode($match),
                );

            case DOKU_LEXER_EXIT :

                $callStack = CallStack::createFromHandler($handler);
                $openingTag = $callStack->moveToPreviousCorrespondingOpeningCall();

                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::CONTEXT => $openingTag->getContext(),
                    PluginUtility::ATTRIBUTES => $openingTag->getAttributes()

                );

            /**
             * Title regexp
             */
            case DOKU_LEXER_SPECIAL :

                $attributes = self::parseWikiHeading($match);
                $callStack = CallStack::createFromHandler($handler);

                $parentTag = $callStack->moveToParent();
                if ($parentTag == false) {
                    $context = "";
                } else {
                    $context = $parentTag->getTagName();
                }


                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::ATTRIBUTES => $attributes,
                    PluginUtility::CONTEXT => $context
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
    function render($format, Doku_Renderer $renderer, $data)
    {

        if ($format == 'xhtml') {

            /** @var Doku_Renderer_xhtml $renderer */
            $state = $data[PluginUtility::STATE];
            switch ($state) {

                case DOKU_LEXER_SPECIAL:
                    /**
                     * The short title ie ( === title === )
                     */
                    $callStackArray = $data[PluginUtility::ATTRIBUTES];
                    $tagAttributes = TagAttributes::createFromCallStackArray($callStackArray);
                    $context = $data[PluginUtility::CONTEXT];
                    $title = $tagAttributes->getValueAndRemove(self::TITLE);
                    self::renderOpeningTag($context, $tagAttributes, $renderer);
                    $renderer->doc .= PluginUtility::htmlEncode($title);
                    $renderer->doc .= self::renderClosingTag($tagAttributes);
                    break;
                case DOKU_LEXER_ENTER:
                    $parentTag = $data[PluginUtility::CONTEXT];
                    $attributes = $data[PluginUtility::ATTRIBUTES];
                    $tagAttributes = TagAttributes::createFromCallStackArray($attributes);
                    self::renderOpeningTag($parentTag, $tagAttributes, $renderer);
                    break;
                case DOKU_LEXER_UNMATCHED:
                    $renderer->doc .= PluginUtility::renderUnmatched($data);
                    break;
                case DOKU_LEXER_EXIT:
                    $attributes = $data[PluginUtility::ATTRIBUTES];
                    $tagAttributes = TagAttributes::createFromCallStackArray($attributes);
                    $renderer->doc .= self::renderClosingTag($tagAttributes);
                    break;

            }
        }
        // unsupported $mode
        return false;
    }

    public
    static function parseWikiHeading($match)
    {
        $title = trim($match);
        $level = 7 - strspn($title, '=');
        if ($level < 1) $level = 1;
        $title = trim($title, '=');
        $title = trim($title);
        $parameters[self::TITLE] = $title;
        $parameters[self::LEVEL] = $level;
        return $parameters;
    }

}

