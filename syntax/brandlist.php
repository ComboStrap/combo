<?php


require_once(__DIR__ . "/../ComboStrap/PluginUtility.php");

use ComboStrap\BrandButton;
use ComboStrap\ExceptionCombo;
use ComboStrap\Icon;
use ComboStrap\PluginUtility;
use ComboStrap\TagAttributes;


/**
 * Just to output the list of brand
 * Not a real tag
 */
class syntax_plugin_combo_brandlist extends DokuWiki_Syntax_Plugin
{


    const TAG = "brand-list";

    function getType()
    {
        return 'substition';
    }

    /**
     * How Dokuwiki will add P element
     *
     *  * 'normal' - The plugin can be used inside paragraphs (inline)
     *  * 'block'  - Open paragraphs need to be closed before plugin output - block should not be inside paragraphs
     *  * 'stack'  - Special case. Plugin wraps other paragraphs. - Stacks can contain paragraphs
     *
     * @see DokuWiki_Syntax_Plugin::getPType()
     */
    function getPType(): string
    {
        return 'block';
    }

    function getAllowedTypes(): array
    {
        return array();
    }

    function getSort(): int
    {
        return 201;
    }


    function connectTo($mode)
    {

        $this->Lexer->addSpecialPattern(PluginUtility::getEmptyTagPattern(self::TAG), $mode, PluginUtility::getModeFromTag($this->getPluginComponent()));

    }


    function handle($match, $state, $pos, Doku_Handler $handler): array
    {

        switch ($state) {


            case DOKU_LEXER_SPECIAL :


                return array(PluginUtility::STATE => $state);


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
            try {
                $brandDictionary = BrandButton::getBrandDictionary();
                $brandNames = array_keys($brandDictionary);
            } catch (ExceptionCombo $e) {
                $renderer->doc .= "Error while creating the brand list. Error: {$e->getMessage()}";
                return false;
            }
            $html = "";
            $snippetManager = PluginUtility::getSnippetManager();
            foreach ($brandNames as $brandName) {

                if(in_array($brandName,["email","newsletter"])){
                    continue;
                }
                try {

                    $brandButton = BrandButton::createBrandButton($brandName);
                    $html .= ucfirst($brandName);
                    $html .= $brandButton->getLinkAttributes()->toHtmlEnterTag("a");

                    if ($brandButton->hasIcon()) {
                        $iconArrayAttributes = $brandButton->getIconAttributes();
                        $iconAttributes = TagAttributes::createFromCallStackArray($iconArrayAttributes);
                        $name = $iconAttributes->getValueAndRemoveIfPresent(syntax_plugin_combo_icon::ICON_NAME_ATTRIBUTE);
                        if ($name !== null) {
                            $html .= Icon::create($name, $iconAttributes)->render();
                        } else {
                            $html .= "Icon name is null for brand $brandName";
                        }
                    }
                    $snippetManager->attachCssSnippetForSlot($brandButton->getStyleScriptIdentifier(),$brandButton->getStyle());
                    $html .= "</a><br/>\n";
                } catch (ExceptionCombo $e) {
                    $renderer->doc .= "Error while rendering the brand $brandName. Error: {$e->getMessage()}\n";
                }
            }
            $renderer->doc .= $html;


        }
        // unsupported $mode
        return false;
    }


}

