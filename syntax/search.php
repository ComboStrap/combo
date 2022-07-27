<?php


use ComboStrap\Bootstrap;
use ComboStrap\MarkupPath;
use ComboStrap\PluginUtility;

/**
 * Command menu / Command Palettes :
 *   * https://uiw.tf/cmdk
 *   * https://tailwindui.com/components/application-ui/navigation/command-palettes
 */
class syntax_plugin_combo_search extends DokuWiki_Syntax_Plugin
{

    const SNIPPET_ID = "search";
    const COMBO_DEBOUNCE = "combo-debounce";
    const COMBO_SEARCH_BOX = "combo-search-box";

    function getType(): string
    {
        return 'substition';
    }

    function getPType(): string
    {
        return 'normal';
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

        $this->Lexer->addSpecialPattern('<' . self::getTag() . '[^>]*>', $mode, 'plugin_' . PluginUtility::PLUGIN_BASE_NAME . '_' . $this->getPluginComponent());

    }

    function handle($match, $state, $pos, Doku_Handler $handler): array
    {

        switch ($state) {

            case DOKU_LEXER_SPECIAL :
                $init = array(
                    'ajax' => true,
                    'autocomplete' => false
                );
                $match = substr($match, strlen($this->getPluginComponent()) + 1, -1);
                $parameters = array_merge($init, PluginUtility::parseAttributes($match));
                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::ATTRIBUTES => $parameters
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
                case DOKU_LEXER_SPECIAL :

                    global $lang;
                    global $ACT;
                    global $QUERY; // $QUERY = $INPUT->str('q')

                    // don't print the search form if search action has been disabled
                    // if (!actionOK('search')) return false;

                    /**
                     * Add the debounce dependency first
                     */
                    PluginUtility::getSnippetManager()->attachJavascriptFromComponentId(self::COMBO_DEBOUNCE);
                    PluginUtility::getSnippetManager()->attachJavascriptFromComponentId(self::COMBO_SEARCH_BOX);

                    /**
                     * Doku Base is not defined when the
                     * {@link \ComboStrap\TplUtility::CONF_DISABLE_BACKEND_JAVASCRIPT}
                     * is used
                     */
                    $dokuBase = DOKU_BASE;
                    PluginUtility::getSnippetManager()->attachJavascriptFromComponentId(self::SNIPPET_ID, "var DOKU_BASE='$dokuBase';");
                    PluginUtility::getSnippetManager()->attachJavascriptFromComponentId(self::SNIPPET_ID);

                    $parameters = $data[PluginUtility::ATTRIBUTES];
                    $extraClass = "";
                    if (array_key_exists("class", $parameters)) {
                        $extraClass = $parameters["class"];
                    }

                    $id = MarkupPath::createFromRequestedPage()->getWikiId();
                    $inputSearchId = 'internal-search-box';

                    // https://getbootstrap.com/docs/5.0/getting-started/accessibility/#visually-hidden-content
                    //
                    $visuallyHidden = "sr-only";
                    $bootStrapVersion = Bootstrap::getBootStrapMajorVersion();
                    if ($bootStrapVersion === Bootstrap::BootStrapFiveMajorVersion) {
                        $visuallyHidden = "visually-hidden";
                    }
                    $valueKeyProp = "";
                    if ($ACT == 'search') $valueKeyProp = ' value="' . htmlspecialchars($QUERY) . '" ';
                    $browserAutoComplete = 'on';
                    if (!$parameters['autocomplete']) $browserAutoComplete = 'off';
                    $action = wl();
                    $renderer->doc .= <<<EOF
<form
    id="dw__search"
    action="$action"
    accept-charset="utf-8"
    method="get"
    role="search"
    class="search form-inline $extraClass"
    >
<input type="hidden" name="do" value="search" />
<input type="hidden" name="id" value="$id" />
<label class="$visuallyHidden" for="$inputSearchId">Search Term</label>
<input class="edit form-control" type="text" id="$inputSearchId"  name="q" $valueKeyProp placeholder="{$lang['btn_search']}... (Alt+Shift+F)" autocomplete="$browserAutoComplete" accesskey="f" title="[F]"/>
</form>
EOF;
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

