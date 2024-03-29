<?php

namespace ComboStrap;


use Doku_Renderer_xhtml;
use syntax_plugin_combo_code;

/**
 * Concurrent: https://highlightjs.org/ used by remark powerpoint
 */
class Prism
{

    const SNIPPET_NAME = 'prism';
    /**
     * The class used to mark the added prism code
     * See: https://cdnjs.com/libraries/prism/
     */
    const BASE_PRISM_CDN = "https://cdnjs.cloudflare.com/ajax/libs/prism/1.25.0";
    /**
     * The default prompt for bash
     */
    const CONF_BASH_PROMPT = "bashPrompt";
    /**
     * The default prompt for batch (dos)
     */
    const CONF_BATCH_PROMPT = "batchPrompt";
    /**
     * The default prompt for powershell
     */
    const CONF_POWERSHELL_PROMPT = "powershellPrompt";

    /**
     * The default name of prism
     * It does not follow the naming of the theming
     */
    const PRISM_THEME = "prism";

    /**
     * @var string[] https://cdnjs.cloudflare.com/ajax/libs/prism/1.25.0/themes/prism-{theme}.min.css
     *
     * or default
     *
     * https://cdnjs.cloudflare.com/ajax/libs/prism/1.25.0/themes/prism.min.css
     *
     * or
     *
     * https://github.com/PrismJS/prism-themes
     *
     * from https://cdnjs.com/libraries/prism
     */
    const THEMES_INTEGRITY = [
        Prism::PRISM_THEME => "sha256-ko4j5rn874LF8dHwW29/xabhh8YBleWfvxb8nQce4Fc=",
        "coy" => "sha256-gkHLZLptZZHaBY+jqrRkAVzOGfMa4HBhSCJteem8wy8=",
        "dark" => "sha256-l+VX6V333ll/PXrjqG1W6DyZvDEw+50M7aAP6dcD7Qc=",
        "funky" => "sha256-l9GTgvTMmAvPQ6IlNCd/I2FQwXVlJCLbGId7z6QlOpo=",
        "okaidia" => "sha256-zzHVEO0xOoVm0I6bT9v5SgpRs1cYNyvEvHXW/1yCgqU=",
        "solarizedlight" => "sha256-Lr49DyE+/KstnLdBxqZBoDYgNi6ONfZyAZw3LDhxB9I=",
        "tomorrow" => "sha256-GxX+KXGZigSK67YPJvbu12EiBx257zuZWr0AMiT1Kpg=",
        "twilight" => "sha256-R7PF7y9XAuz19FB93NgH/WQUVGk30iytl7EwtETrypo="
    ];

    /**
     * The theme
     */
    const CONF_PRISM_THEME = "prismTheme";
    const PRISM_THEME_DEFAULT = "tomorrow";
    const SNIPPET_ID_AUTOLOADER = self::SNIPPET_NAME . "-autoloader";
    const LINE_NUMBERS_ATTR = "line-numbers";


    /**
     *
     * @param $theme
     *
     * Ter info: The theme of the default wiki is in the print.css file (search for code blocks)
     */
    public static function addSnippet($theme)
    {
        $BASE_PRISM_CDN = self::BASE_PRISM_CDN;

        if ($theme == self::PRISM_THEME) {
            $themeStyleSheet = "prism.min.css";
        } else {
            $themeStyleSheet = "prism-$theme.min.css";
        }
        $themeIntegrity = self::THEMES_INTEGRITY[$theme];

        /**
         * We miss a bottom margin
         * as a paragraph
         */
        $snippetManager = PluginUtility::getSnippetManager();
        $snippetManager->attachCssInternalStyleSheet(self::SNIPPET_NAME);

        /**
         * Javascript
         */
        $snippetManager->attachRemoteJavascriptLibraryFromLiteral(
            self::SNIPPET_NAME,
            "$BASE_PRISM_CDN/components/prism-core.min.js",
            "sha256-vlRYHThwdq55dA+n1BKQRzzLwFtH9VINdSI68+5JhpU=");
        $snippetManager->attachRemoteJavascriptLibraryFromLiteral(
            self::SNIPPET_NAME,
            "$BASE_PRISM_CDN/plugins/toolbar/prism-toolbar.min.js",
            "sha256-FyIVdIHL0+ppj4Q4Ft05K3wyCsYikpHIDGI7dcaBalU="
        );
        $snippetManager->attachRemoteCssStyleSheetFromLiteral(
            self::SNIPPET_NAME,
            "$BASE_PRISM_CDN/plugins/toolbar/prism-toolbar.css",
            "sha256-kK4/JIYJUKI4Zdg9ZQ7FYyRIqeWPfYKi5QZHO2n/lJI="
        );
        // https://prismjs.com/plugins/normalize-whitespace/
        $snippetManager->attachRemoteJavascriptLibraryFromLiteral(
            self::SNIPPET_NAME,
            "$BASE_PRISM_CDN/plugins/normalize-whitespace/prism-normalize-whitespace.min.js",
            "sha256-gBzABGbXfQYYnyr8xmDFjx6KGO9dBYuypG1QBjO76pY=");
        // https://prismjs.com/plugins/copy-to-clipboard/
        $snippetManager->attachRemoteJavascriptLibraryFromLiteral(
            self::SNIPPET_NAME,
            "$BASE_PRISM_CDN/plugins/copy-to-clipboard/prism-copy-to-clipboard.min.js",
            "sha512-pUNGXbOrc+Y3dm5z2ZN7JYQ/2Tq0jppMDOUsN4sQHVJ9AUQpaeERCUfYYBAnaRB9r8d4gtPKMWICNhm3tRr4Fg==");
        // https://prismjs.com/plugins/show-language/
        $snippetManager->attachRemoteJavascriptLibraryFromLiteral(
            self::SNIPPET_NAME,
            "$BASE_PRISM_CDN/plugins/show-language/prism-show-language.min.js",
            "sha256-Z3GTw2RIadLG7KyP/OYB+aAxVYzvg2PByKzYrJlA1EM=");
        // https://prismjs.com/plugins/command-line/
        $snippetManager->attachRemoteJavascriptLibraryFromLiteral(
            self::SNIPPET_NAME,
            "$BASE_PRISM_CDN/plugins/command-line/prism-command-line.min.js",
            "sha256-9WlakH0Upf3N8DDteHlbeKCHxSsljby+G9ucUCQNiU0=");
        $snippetManager->attachRemoteCssStyleSheetFromLiteral(
            self::SNIPPET_NAME,
            "$BASE_PRISM_CDN/plugins/command-line/prism-command-line.css",
            "sha256-UvoA9bIYCYQkCMTYG5p2LM8ZpJmnC4G8k0oIc89nuQA="
        );
        // https://prismjs.com/plugins/line-highlight/
        $snippetManager->attachRemoteJavascriptLibraryFromLiteral(
            self::SNIPPET_NAME,
            "$BASE_PRISM_CDN/plugins/line-highlight/prism-line-highlight.min.js",
            "sha512-O5GVPBZIURR9MuNiCjSa1wNTL3w91tojKlgCXmOjWDT5a3+9Ms+wGsTkBO93PI3anfdajkJD0sJiS6qdQq7jRA==");
        $snippetManager->attachRemoteCssStyleSheetFromLiteral(
            self::SNIPPET_NAME,
            "$BASE_PRISM_CDN/plugins/line-highlight/prism-line-highlight.min.css",
            "sha512-nXlJLUeqPMp1Q3+Bd8Qds8tXeRVQscMscwysJm821C++9w6WtsFbJjPenZ8cQVMXyqSAismveQJc0C1splFDCA=="
        );
        //https://prismjs.com/plugins/line-numbers/
        $snippetManager->attachRemoteJavascriptLibraryFromLiteral(
            self::SNIPPET_NAME,
            "$BASE_PRISM_CDN/plugins/line-numbers/prism-line-numbers.min.js",
            "sha256-K837BwIyiXo5k/9fCYgqUyA14bN4/Ve9P2SIT0KmZD0=");
        $snippetManager->attachRemoteJavascriptLibraryFromLiteral(
            self::SNIPPET_NAME,
            "$BASE_PRISM_CDN/plugins/line-numbers/prism-line-numbers.css",
            "sha256-ye8BkHf2lHXUtqZ18U0KI3xjJ1Yv7P8lvdKBt9xmVJM="
        );

        // https://prismjs.com/plugins/download-button/-->
        $snippetManager->attachRemoteJavascriptLibraryFromLiteral(
            self::SNIPPET_NAME,
            "$BASE_PRISM_CDN/plugins/download-button/prism-download-button.min.js",
            "sha256-CQyVQ5ejeTshlzOS/eCiry40br9f4fQ9jb5e4qPl7ZA=");

        // Loading the theme
        $snippetManager->attachRemoteCssStyleSheetFromLiteral(
            self::SNIPPET_NAME,
            "$BASE_PRISM_CDN/themes/$themeStyleSheet",
            $themeIntegrity
        );

        $javascriptCode = <<<EOD
window.addEventListener('load', (event) => {

    Prism.plugins.NormalizeWhitespace.setDefaults({
        'remove-trailing': true,
        'remove-indent': true,
        'left-trim': true,
        'right-trim': true,
    });

});
EOD;
        $snippetManager->attachJavascriptFromComponentId(self::SNIPPET_NAME, $javascriptCode);

    }

    /**
     * Add the first block of prism
     * @param \Doku_Renderer_xhtml $renderer
     * @param TagAttributes $attributes
     * @param \DokuWiki_Syntax_Plugin $plugin
     */
    public static function htmlEnter(\Doku_Renderer_xhtml $renderer, \DokuWiki_Syntax_Plugin $plugin, $attributes = null)
    {

        if ($attributes == null) {
            $attributes = TagAttributes::createEmpty();
        }

        /**
         * Display none, no rendering
         */
        $display = $attributes->getValueAndRemove("display");
        if ($display != null) {
            if ($display == "none") {
                return;
            }
        }


        /**
         * Add prism theme
         */
        $theme = $plugin->getConf(Prism::CONF_PRISM_THEME, Prism::PRISM_THEME_DEFAULT);
        Prism::addSnippet($theme);

        /**
         * Logical tag
         */
        $logicalTag = $plugin->getPluginComponent();
        if ($attributes->getLogicalTag() != null) {
            $logicalTag = $attributes->getLogicalTag();
        }
        // for the https://combostrap.com/styling/userstyle
        $attributes->setLogicalTag($logicalTag . "-container");

        /**
         * The child element (code) of the `pre` element
         * The container is the passed `attributes`
         * We can then constrained in height ...
         * It contains the language
         */
        $codeAttributes = TagAttributes::createEmpty($logicalTag);
        $codeAttributes->setType($attributes->getType());
        $language = $attributes->getValue(TagAttributes::TYPE_KEY);
        if ($language == null) {
            // Prism does not have any default language
            // There is a bug has it tried to download the txt javascript
            // but without language, there is no styling
            $language = "txt";
        } else {
            $language = strtolower($language);
            Prism::addAutoloaderSnippet();
        }

        if (in_array($language, Tag\WebCodeTag::MARKIS)) {
            // Marki is not fully markdown
            // because it accepts space in super set html container and
            // prism will highlight them as indented code
            $language = "html";
        }
        /**
         * Language name mapping between the syntax name and prism
         */
        switch ($language) {
            case "rsplus":
                $language = "r";
                break;
            case "dos":
            case "bat":
                $language = "batch";
                break;
            case "grok":
                $language = "regex";
                break;
            case "jinja":
                // https://github.com/PrismJS/prism/issues/759
                $language = "twig";
                break;
            case "apache":
                $language = "apacheconf";
                break;
            case "babel":
                $language = "jsx";
                break;
            case "antlr":
                $language = "g4";
                break;

        }

        StringUtility::addEolCharacterIfNotPresent($renderer->doc);
        $codeAttributes->addClassName('language-' . $language);
        /**
         * Code element
         * Don't put a fucking EOL after it
         * Otherwise it fucked up the output as the text below a code tag is printed
         */
        $codeHtml = $codeAttributes->toHtmlEnterTag('code');
        $attributes->addHtmlAfterEnterTag($codeHtml);


        /**
         * Pre Element
         * Line numbers
         */
        $lineNumberEnabled = false;
        if ($attributes->hasComponentAttribute(self::LINE_NUMBERS_ATTR)) {
            $attributes->removeComponentAttribute(self::LINE_NUMBERS_ATTR);
            $attributes->addClassName("line-numbers");
            $lineNumberEnabled = true;
        }


        /**
         * Command line prompt
         * (Line element and prompt cannot be chosen together
         * otherwise they endup on top of each other)
         */
        if (!$lineNumberEnabled) {
            if ($attributes->hasComponentAttribute("prompt")) {
                $promptValue = $attributes->getValueAndRemove("prompt");
                // prompt may be the empty string
                if (!empty($promptValue)) {
                    $attributes->addClassName("command-line");
                    $attributes->addOutputAttributeValue("data-prompt", $promptValue);
                }
            } else {
                /**
                 * Default prompt
                 */
                switch ($language) {
                    case "bash":
                        $prompt = $plugin->getConf(self::CONF_BASH_PROMPT);
                        break;
                    case "batch":
                        $prompt = trim($plugin->getConf(self::CONF_BATCH_PROMPT));
                        if (!empty($prompt)) {
                            if (!strpos($prompt, -1) == ">") {
                                $prompt .= ">";
                            }
                        }
                        break;
                    case "powershell":
                        $prompt = trim($plugin->getConf(self::CONF_POWERSHELL_PROMPT));
                        if (!empty($prompt)) {
                            if (!strpos($prompt, -1) == ">") {
                                $prompt .= ">";
                            }
                        }
                        break;
                }
                if(!empty($prompt)) {
                    $attributes->addClassName("command-line");
                    $attributes->addOutputAttributeValue("data-prompt", $prompt);
                }
            }
        }

        /**
         * Line highlight
         */
        if ($attributes->hasComponentAttribute("line-highlight")) {
            $lineHiglight = $attributes->getValueAndRemove("line-highlight");
            if(!empty($lineHiglight)) {
                $attributes->addOutputAttributeValue('data-line', $lineHiglight);
            }
        }

        // Download
        $attributes->addOutputAttributeValue('data-download-link', true);
        if ($attributes->hasComponentAttribute(syntax_plugin_combo_code::FILE_PATH_KEY)) {
            $fileSrc = $attributes->getValueAndRemove(syntax_plugin_combo_code::FILE_PATH_KEY);
            $attributes->addOutputAttributeValue('data-src', $fileSrc);
            $attributes->addOutputAttributeValue('data-download-link-label', "Download " . $fileSrc);
        } else {
            $fileName = "file." . $language;
            $attributes->addOutputAttributeValue('data-src', $fileName);
        }
        /**
         * No end of line after the pre, please, otherwise we get a new line
         * in the code output
         */
        $htmlCode = $attributes->toHtmlEnterTag("pre");


        /**
         * Return
         */
        $renderer->doc .= $htmlCode;

    }

    /**
     * @param Doku_Renderer_xhtml $renderer
     * @param TagAttributes $attributes
     */
    public static function htmlExit(\Doku_Renderer_xhtml $renderer, $attributes = null)
    {

        if ($attributes != null) {
            /**
             * Display none, no rendering
             */
            $display = $attributes->getValueAndRemove("display");
            if ($display != null) {
                if ($display == "none") {
                    return;
                }
            }
        }
        $renderer->doc .= '</code>' . DOKU_LF . '</pre>' . DOKU_LF;
    }

    /**
     * The autoloader try to download all language
     * Even the one such as txt that does not exist
     * This function was created to add it conditionally
     */
    private static function addAutoloaderSnippet()
    {
        PluginUtility::getSnippetManager()
            ->attachRemoteJavascriptLibrary(
                self::SNIPPET_ID_AUTOLOADER,
                self::BASE_PRISM_CDN . "/plugins/autoloader/prism-autoloader.min.js"
            );
    }


}
