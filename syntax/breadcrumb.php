<?php


use ComboStrap\DokuPath;
use ComboStrap\ExceptionCombo;
use ComboStrap\PluginUtility;


/**
 *
 */
class syntax_plugin_combo_breadcrumb extends DokuWiki_Syntax_Plugin
{

    const TAG = "breadcrumb";


    public const CANONICAL = "breadcrumb-hierarchical";

    /**
     * Hierarchical breadcrumbs (you are here)
     *
     * This will return the Hierarchical breadcrumbs.
     *
     * Config:
     *    - $conf['youarehere'] must be true
     *    - add $lang['youarehere'] if $printPrefix is true
     *
     * Metadata comes from here
     * https://developers.google.com/search/docs/data-types/breadcrumb
     *
     * @return string
     * @throws ExceptionCombo
     */
    public static function toBreadCrumbHtml(): string
    {


        // print intermediate namespace links
        $htmlOutput = '<div class="branch rplus">' . PHP_EOL;

        // Breadcrumb head
        $htmlOutput .= '<nav aria-label="breadcrumb">' . PHP_EOL;
        $htmlOutput .= '<ol class="breadcrumb">' . PHP_EOL;

        // Home
        $htmlOutput .= '<li class="breadcrumb-item">' . PHP_EOL;
        $page = \ComboStrap\Site::getHomePageName();
        $markupRef = \ComboStrap\MarkupRef::createFromPageId($page);
        $pageNameNotEmpty = $markupRef->getInternalPage()->getNameOrDefault();
        $htmlOutput .= $markupRef->toAttributes(self::CANONICAL)->toHtmlEnterTag("a")
            . $pageNameNotEmpty
            . "</a>";
        $htmlOutput .= '</li>' . PHP_EOL;

        // Print the parts if there is more than one
        global $ID;
        $idParts = explode(':', $ID);
        $countPart = count($idParts);
        if ($countPart > 1) {

            // Print the parts without the last one ($count -1)
            $pagePart = "";
            $currentParts = [];
            for ($i = 0; $i < $countPart - 1; $i++) {

                $currentPart = $idParts[$i];
                $currentParts[] = $currentPart;

                /**
                 * We pass the value to the page variable
                 * because the resolve part will change it
                 *
                 * resolve will also resolve to the home page
                 */

                $page = implode(DokuPath::PATH_SEPARATOR, $currentParts) . ":";
                $exist = null;
                resolve_pageid(getNS($ID), $page, $exist, "", true);

                $htmlOutput .= '<li class="breadcrumb-item">';
                // html_wikilink because the page has the form pagename: and not pagename:pagename
                if ($exist) {
                    $markupRef = \ComboStrap\MarkupRef::createFromPageId($page);
                    $htmlOutput .=
                        $markupRef->toAttributes(self::CANONICAL)->toHtmlEnterTag("a")
                        . $markupRef->getInternalPage()->getNameOrDefault()
                        . "</a>";
                } else {
                    $htmlOutput .= ucfirst($currentPart);
                }

                $htmlOutput .= '</li>' . PHP_EOL;

            }
        }

        // close the breadcrumb
        $htmlOutput .= '</ol>' . PHP_EOL;
        $htmlOutput .= '</nav>' . PHP_EOL;
        $htmlOutput .= "</div>" . PHP_EOL;


        return $htmlOutput;

    }

    /**
     * Syntax Type.
     *
     * Needs to return one of the mode types defined in {@link $PARSER_MODES} in parser.php
     * @see DokuWiki_Syntax_Plugin::getType()
     */
    function getType(): string
    {
        return 'substition';
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
    function getAllowedTypes(): array
    {
        return array();
    }


    function getSort()
    {
        return 201;
    }


    function connectTo($mode)
    {

        $pattern = PluginUtility::getEmptyTagPattern(self::TAG);
        $this->Lexer->addSpecialPattern($pattern, $mode, PluginUtility::getModeFromTag($this->getPluginComponent()));

    }


    function handle($match, $state, $pos, Doku_Handler $handler): array
    {

        $attributes = PluginUtility::getTagAttributes($match);
        return array(
            PluginUtility::STATE => $state,
            PluginUtility::ATTRIBUTES => $attributes
        );

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
        switch ($format) {

            case 'xhtml':
                $state = $data[PluginUtility::STATE];
                if($state===DOKU_LEXER_SPECIAL) {
                    $renderer->doc .= self::toBreadCrumbHtml();
                }
                return true;
        }
        return false;

    }


}

