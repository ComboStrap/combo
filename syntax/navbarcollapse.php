<?php
/**
 * Copyright (c) 2020. ComboStrap, Inc. and its affiliates. All Rights Reserved.
 *
 * This source code is licensed under the GPL license found in the
 * COPYING  file in the root directory of this source tree.
 *
 * @license  GPL 3 (https://www.gnu.org/licenses/gpl-3.0.en.html)
 * @author   ComboStrap <support@combostrap.com>
 *
 */

use ComboStrap\HtmlUtility;
use ComboStrap\LinkUtility;
use ComboStrap\NavBarUtility;
use ComboStrap\PluginUtility;


require_once(__DIR__ . '/../class/PluginUtility.php');
require_once(__DIR__ . '/../class/NavBarUtility.php');


/**
 *
 * See https://getbootstrap.com/docs/4.0/components/collapse/
 *
 * The name of the class must follow a pattern (don't change it) ie syntax_plugin_PluginName_ComponentName
 */
class syntax_plugin_combo_navbarcollapse extends DokuWiki_Syntax_Plugin
{
    const TAG = 'collapse';
    const COMPONENT = 'navbarcollapse';

    /**
     * Syntax Type.
     *
     * Needs to return one of the mode types defined in $PARSER_MODES in parser.php
     * @see DokuWiki_Syntax_Plugin::getType()
     */
    function getType()
    {
        return 'container';
    }

    /**
     * @return array
     * Allow which kind of plugin inside
     *
     * No one of array('container', 'baseonly', 'formatting', 'substition', 'protected', 'disabled', 'paragraphs')
     * because we manage self the content and we call self the parser
     */
    public function getAllowedTypes()
    {
        return array('container', 'formatting',  'substition', 'protected', 'disabled', 'paragraphs');
    }

    /**
     * We don't accept link as substition
     * @param string $mode
     * @return bool
     */
//    public function accepts($mode)
//    {
//        $position = strpos($mode, 'link');
//        if ($position === false){
//            return parent::accepts($mode);
//        } else {
//            return false;
//        }
//
//    }


    /**
     * How Dokuwiki will add P element
     *
     * * 'normal' - The plugin can be used inside paragraphs
     *  * 'block'  - Open paragraphs need to be closed before plugin output - block should not be inside paragraphs
     *  * 'stack'  - Special case. Plugin wraps other paragraphs. - Stacks can contain paragraphs
     *
     * @see DokuWiki_Syntax_Plugin::getPType()
     */
    function getPType()
    {
        return 'normal';
    }

    /**
     * @see Doku_Parser_Mode::getSort()
     *
     * the mode with the lowest sort number will win out
     * the container (parent) must then have a lower number than the child
     */
    function getSort()
    {
        return 100;
    }

    /**
     * Create a pattern that will called this plugin
     *
     * @param string $mode
     * @see Doku_Parser_Mode::connectTo()
     */
    function connectTo($mode)
    {
        // Only inside a navbar
        if ($mode == PluginUtility::getModeForComponent(syntax_plugin_combo_navbar::TAG)) {
            $pattern = PluginUtility::getContainerTagPattern(self::TAG);
            $this->Lexer->addEntryPattern($pattern, $mode, 'plugin_' . PluginUtility::PLUGIN_BASE_NAME . '_' . $this->getPluginComponent());
            $this->Lexer->addPattern(LinkUtility::LINK_PATTERN, PluginUtility::getModeForComponent($this->getPluginComponent()));
        }

    }

    public function postConnect()
    {

        $this->Lexer->addExitPattern('</' . self::TAG . '>', 'plugin_' . PluginUtility::PLUGIN_BASE_NAME . '_' . $this->getPluginComponent());

    }

    /**
     *
     * The handle function goal is to parse the matched syntax through the pattern function
     * and to return the result for use in the renderer
     * This result is always cached until the page is modified.
     * @param string $match
     * @param int $state
     * @param int $pos
     * @param Doku_Handler $handler
     * @return array|bool
     * @see DokuWiki_Syntax_Plugin::handle()
     *
     */
    function handle($match, $state, $pos, Doku_Handler $handler)
    {

        switch ($state) {

            case DOKU_LEXER_ENTER:

                $tagAttributes = PluginUtility::getTagAttributes($match);
                return array($state, $tagAttributes);

            case DOKU_LEXER_UNMATCHED :

                return array($state, $match);

            case DOKU_LEXER_MATCHED:

                $linkAttributes = LinkUtility::getAttributes($match);
                return array($state, $linkAttributes);

            case DOKU_LEXER_EXIT :

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
    function render($format, Doku_Renderer $renderer, $data)
    {
        list($state, $payload) = $data;
        switch ($format) {
            case 'xhtml':
                /** @var Doku_Renderer_xhtml $renderer */

                switch ($state) {

                    case DOKU_LEXER_ENTER :

                        $attributes = $payload;

                        // The button is the hamburger menu that will be shown
                        $idElementToCollapse = 'navbarcollapse';
                        $renderer->doc .= '<button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#' . $idElementToCollapse . '" aria-controls="navbar" aria-expanded="false" aria-label="Toggle navigation"';
                        if (array_key_exists("order", $attributes)) {
                            $renderer->doc .= ' style="order:' . $attributes["order"];
                            unset($attributes["order"]);
                        }
                        $renderer->doc .= '"><span class="navbar-toggler-icon"></span></button>' . DOKU_LF;


                        $classValue = "collapse navbar-collapse";
                        if (array_key_exists("class", $attributes)) {
                            $attributes["class"] .= " {$classValue}";
                        } else {
                            $attributes["class"] = "{$classValue}";
                        }
                        $renderer->doc .= '<div id="' . $idElementToCollapse . '" '.PluginUtility::array2HTMLAttributes($attributes).'>';

                        // All element below will collapse
                        break;

                    case DOKU_LEXER_UNMATCHED:
                        $renderer->doc .= NavBarUtility::text(PluginUtility::escape($payload));
                        break;

                    case DOKU_LEXER_MATCHED:

                        /**
                         * Shortcut for a link in a {@link syntax_plugin_combo_navbargroup}
                         */
                        $html = LinkUtility::renderAsAnchorElement($renderer,$payload);
                        $renderer->doc .= '<div class="navbar-nav">'.NavBarUtility::switchDokuwiki2BootstrapClass($html).'</div>';
                        break;

                    case DOKU_LEXER_EXIT :

                        $renderer->doc .= '</div>' . DOKU_LF;
                        break;
                }
                return true;
            case 'metadata':

                /**
                 * Keep track of the backlinks ie meta['relation']['references']
                 * @var Doku_Renderer_metadata $renderer
                 */
                if ($state == DOKU_LEXER_SPECIAL) {
                    LinkUtility::handleMetadata($renderer, $data);
                }
                return true;
                break;
        }
        return false;
    }


    public static function getElementName()
    {
        return PluginUtility::getTagName(get_called_class());
    }


}
