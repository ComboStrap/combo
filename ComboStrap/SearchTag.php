<?php

namespace ComboStrap;

/**
 * Search Tag Implementation
 *
 * See also Command menu / Command Palettes :
 *   * https://uiw.tf/cmdk
 *   * https://tailwindui.com/components/application-ui/navigation/command-palettes
 */
class SearchTag
{

    const TAG = "search";
    public const COMBO_SEARCH_BOX = "combo-search-box";
    public const SNIPPET_ID = "search";
    public const COMBO_DEBOUNCE = "combo-debounce";
    const CANONICAL = "search";

    public static function render(TagAttributes $tagAttributes): string
    {
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

        $extraClass = $tagAttributes->getClass("");

        try {
            $id = WikiPath::createRequestedPagePathFromRequest()->getWikiId();
        } catch (ExceptionNotFound $e) {
            LogUtility::error($e->getMessage(), self::CANONICAL,$e);;
            $id = "not_found";
        }
        $inputSearchId = 'internal-search-box';

        // https://getbootstrap.com/docs/5.0/getting-started/accessibility/#visually-hidden-content
        //
        $visuallyHidden = "sr-only";
        $bootStrapVersion = Bootstrap::getFromContext()->getMajorVersion();
        if ($bootStrapVersion == Bootstrap::BootStrapFiveMajorVersion) {
            $visuallyHidden = "visually-hidden";
        }
        $valueKeyProp = "";
        if ($ACT == 'search') $valueKeyProp = ' value="' . htmlspecialchars($QUERY) . '" ';
        $browserAutoComplete = 'on';
        if (!$tagAttributes->getBooleanValue('autocomplete')) {
            $browserAutoComplete = 'off';
        }
        $tagClass = StyleUtility::addComboStrapSuffix(self::TAG);
        $action = wl();
        return <<<EOF
<form
    id="dw__search"
    action="$action"
    accept-charset="utf-8"
    method="get"
    role="search"
    class="$tagClass form-inline $extraClass"
    >
<input type="hidden" name="do" value="search" />
<input type="hidden" name="id" value="$id" />
<label class="$visuallyHidden" for="$inputSearchId">Search Term</label>
<input class="edit form-control" type="text" id="$inputSearchId"  name="q" $valueKeyProp placeholder="{$lang['btn_search']}... (Alt+Shift+F)" autocomplete="$browserAutoComplete" accesskey="f" title="[F]"/>
</form>
EOF;
    }

}
