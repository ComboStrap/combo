<?php

// implementation of
// https://developer.mozilla.org/en-US/docs/Web/HTML/Element/cite

// must be run within Dokuwiki
use ComboStrap\PluginUtility;

if(!defined('DOKU_INC')) die();


class syntax_plugin_combo_brand extends DokuWiki_Syntax_Plugin {

    /**
     * Syntax Type.
     *
     * Needs to return one of the mode types defined in $PARSER_MODES in parser.php
     * @see DokuWiki_Syntax_Plugin::getType()
     */
    function getType() {
        return 'formatting';
    }

    /**
     * How Dokuwiki will add P element
     *
     *  * 'normal' - The plugin can be used inside paragraphs
     *  * 'block'  - Open paragraphs need to be closed before plugin output - block should not be inside paragraphs
     *  * 'stack'  - Special case. Plugin wraps other paragraphs. - Stacks can contain paragraphs
     *
     * @see DokuWiki_Syntax_Plugin::getPType()
     */
    function getPType() {
        return 'normal';
    }

    /**
     * @return array
     * Allow which kind of plugin inside
     *
     * array('container', 'baseonly', 'formatting', 'substition', 'protected', 'disabled', 'paragraphs')
     *
     */
    function getAllowedTypes() {
        return array('container', 'baseonly', 'formatting', 'substition', 'protected', 'disabled');
    }

    function getSort() {
        return 201;
    }


    /**
     * Create a pattern that will called this plugin
     *
     * @see Doku_Parser_Mode::connectTo()
     * @param string $mode
     */
    function connectTo($mode) {

        $pattern = PluginUtility::getContainerTagPattern(self::getTag());
        $this->Lexer->addEntryPattern($pattern, $mode, 'plugin_' . PluginUtility::PLUGIN_BASE_NAME . '_' . $this->getPluginComponent());

    }

    function postConnect() {

        $this->Lexer->addExitPattern('</' . self::getTag() . '>', 'plugin_' . PluginUtility::PLUGIN_BASE_NAME . '_' . $this->getPluginComponent());

    }

    function handle($match, $state, $pos, Doku_Handler $handler) {

        switch ($state) {

            case DOKU_LEXER_ENTER :
                $match = substr($match, strlen($this->getPluginComponent()) + 1, -1);
                $parameters = PluginUtility::parse2HTMLAttributes($match);
                return array($state, $parameters);

            case DOKU_LEXER_UNMATCHED :
                return array ($state, $match);

            case DOKU_LEXER_EXIT :

                // Important otherwise we don't get an exit in the render
                return array($state, '');


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
    function render($format, Doku_Renderer $renderer, $data){

        if ($format == 'xhtml') {

            /** @var Doku_Renderer_xhtml $renderer */
            list($state, $parameters) = $data;
            switch ($state) {
                case DOKU_LEXER_ENTER :
                    $renderer->doc .= '<a href="'.wl().'" accesskey="h"';

                    $title = ' title="';
                    if (array_key_exists("title", $parameters)) {
                        $title .= $parameters["title"];
                    } else {
                        global $conf;
                        $title .= $conf['title'];
                    }
                    $title .='"';
                    $renderer->doc .= $title;

                    $renderer->doc .= ' class="navbar-brand';
                    if (array_key_exists("class", $parameters)) {
                        $renderer->doc .= ' '.hsc($parameters["class"]);
                    }
                    $renderer->doc .= '"';
                    if (array_key_exists("style", $parameters)) {
                        $renderer->doc .= ' style="'.hsc($parameters["style"]).'"';
                    }
                    $renderer->doc .= '>';
                    break;

                case DOKU_LEXER_UNMATCHED :
                    // What about:
                    //   * the title of the website ? $conf['title']
                    //   * the logo ? $logo = tpl_getMediaFile(array(':wiki:logo.png', ':logo.png', 'images/logo.png'), false, $logoSize);
                    $renderer->doc .= $renderer->_xmlEntities($parameters);
                    break;

                case DOKU_LEXER_EXIT :
                    $renderer->doc .= '</a>';
                    break;
            }
            return true;
        }

        // unsupported $mode
        return false;
    }

    public static function getTag()
    {
        list(/* $t */, /* $p */, /* $n */, $c) = explode('_', get_called_class(), 4);
        return (isset($c) ? $c : '');
    }

}

