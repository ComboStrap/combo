<?php


require_once(__DIR__ . '/../vendor/autoload.php');

// must be run within Dokuwiki
use ComboStrap\BlockquoteTag;
use ComboStrap\HeadingTag;
use ComboStrap\PluginUtility;
use ComboStrap\XmlTagProcessing;


/**
 * The xml block tag (non-empty) pattern
 */
class syntax_plugin_combo_xmlblocktag extends DokuWiki_Syntax_Plugin
{
    /**
     * Should be the same than the last name of the class
     */
    const TAG = "xmlblocktag";


    /**
     * The Syntax Type determines which syntax may be nested
     *
     * Needs to return one of the mode types defined in $PARSER_MODES in parser.php
     * @see DokuWiki_Syntax_Plugin::getType()
     * See https://www.dokuwiki.org/devel:syntax_plugins#syntax_types
     */
    function getType(): string
    {
        /**
         * Choice between container, formatting and substition
         *
         * Icon had 'substition' and can still have other mode inside (ie tooltip)
         * We choose substition then
         *
         * For heading, title, it was `baseonly` because
         * Heading disappear when a table is just before because the {@link HeadingTag::SYNTAX_TYPE}  was `formatting`
         * The table was then accepting it and was deleting it at completion because there was no end of cell character (ie `|`)
         *
         */
        return 'substition';
    }

    /**
     * @param string $mode
     * @return bool
     * Allowed type
     */
    public function accepts($mode): bool
    {
        /**
         * header mode is disable to take over
         * and replace it with {@link syntax_plugin_combo_headingwiki}
         */
        if ($mode == "header") {
            return false;
        }

        return syntax_plugin_combo_preformatted::disablePreformatted($mode);

    }

    /**
     * How Dokuwiki will add P element
     *
     *  * 'normal' - Inline (dokuwiki will not close an ongoing p)
     *  * 'block' - Block (dokuwiki does not not create p inside and close an open p)
     *  * 'stack' - Block (dokuwiki create p inside)
     *
     * @see DokuWiki_Syntax_Plugin::getPType()
     */
    function getPType(): string
    {
        /**
         * Ptype is the driver of the {@link \dokuwiki\Parsing\Handler\Block::process()}
         * that creates the P tag.
         *
         * Works with block and stack for now
         * Not with `normal` as if dokuwiki has created a p
         * and that is encounters a block, it will close the p inside the stack unfortunately
         * (You can try with {@link BlockquoteTag}
         *
         * For box, not stack, otherwise it creates p
         * and as box is used mostly for layout purpose, it breaks the
         * {@link \ComboStrap\Align} flex css attribute
         *
         * For Cardbody, block value was !important! as
         * it will not create an extra paragraph after it encounters a block
         *
         * For {@link \ComboStrap\GridTag},
         * not stack, otherwise you get extra p's
         * and it will fucked up the flex layout
         */
        return 'block';
    }

    /**
     * @return array the kind of plugin that are allowed inside (ie an array of
     * <a href="https://www.dokuwiki.org/devel:syntax_plugins#syntax_types">mode type</a>
     * ie
     * * array('container', 'formatting', 'substition', 'protected', 'disabled', 'paragraphs')
     * * array of one or more of the mode types {@link $PARSER_MODES} in Parser.php
     */
    public function getAllowedTypes(): array
    {
        /**
         * Tweak: `paragraphs` is not in the allowed type
         */
        return array('container', 'formatting', 'substition', 'protected', 'disabled');
    }


    function getSort(): int
    {
        return 999;
    }


    function connectTo($mode)
    {

        // One pattern to not compete between pattern
        // this pattern ensure that the tag
        // (ie that `accordion` will not intercept also the tag `accordionitem`)


        $pattern = XmlTagProcessing::START_TAG_PATTERN;
        $this->Lexer->addEntryPattern($pattern, $mode, PluginUtility::getModeFromTag($this->getPluginComponent()));

    }

    public function postConnect()
    {

        $this->Lexer->addExitPattern('</[\w-]+>', PluginUtility::getModeFromTag($this->getPluginComponent()));

    }


    function handle($match, $state, $pos, Doku_Handler $handler): array
    {
        return XmlTagProcessing::handleStatic($match, $state, $pos, $handler, $this);
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

        return XmlTagProcessing::renderStatic($format, $renderer, $data, $this);

    }


}

