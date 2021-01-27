<?php

// Search form in a navbar


// must be run within Dokuwiki
use ComboStrap\PluginUtility;

if(!defined('DOKU_INC')) die();


class syntax_plugin_combo_search extends DokuWiki_Syntax_Plugin {

    function getType() {
        return 'substition';
    }

    function getPType() {
        return 'normal';
    }

    function getAllowedTypes() {
        return array();
    }

    function getSort() {
        return 201;
    }



    function connectTo($mode) {

        $this->Lexer->addSpecialPattern('<' . self::getTag() . '[^>]*>',$mode,'plugin_' . PluginUtility::PLUGIN_BASE_NAME . '_' . $this->getPluginComponent());

    }

    function handle($match, $state, $pos, Doku_Handler $handler) {

        switch ($state) {

            case DOKU_LEXER_SPECIAL :
                $init = array(
                    'ajax' => true,
                    'autocomplete' => true
                );
                $match = utf8_substr($match, strlen($this->getPluginComponent()) + 1, -1);
                $parameters = array_merge($init, PluginUtility::parse2HTMLAttributes($match));
                return array($state, $parameters);

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
            list($state,$parameters)=$data;
            switch ($state) {
                case DOKU_LEXER_SPECIAL :

                    global $lang;
                    global $ACT;
                    global $QUERY;

                    // don't print the search form if search action has been disabled
                    if (!actionOK('search')) return false;

                    $renderer->doc .= '<form action="' . wl() . '" accept-charset="utf-8" id="dw__search" method="get" role="search" class="search form-inline ';
                    if (array_key_exists("class", $parameters)) {
                        $renderer->doc .= ' '.$parameters["class"];
                    }
                    $renderer->doc .= '">' . DOKU_LF;
                    $renderer->doc .= '<input type="hidden" name="do" value="search" />';
                    $renderer->doc .=  '<label class="sr-only" for="search">Search Term</label>';
                    $renderer->doc .=  '<input type="text" tabindex="1"';
                    if ($ACT == 'search') $renderer->doc .= 'value="' . htmlspecialchars($QUERY) . '" ';
                    $renderer->doc .= 'placeholder="' . $lang['btn_search'] . '..." ';
                    if (!$parameters['autocomplete']) $renderer->doc .= 'autocomplete="off" ';
                    $renderer->doc .= 'id="qsearch__in" accesskey="f" name="id" class="edit form-control" title="[F]"/>';
                    if ($parameters['ajax']) $renderer->doc .= '<div id="qsearch__out" class="ajax_qsearch JSpopup"></div>';
                    $renderer->doc .= '</form>';
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

