<?php

namespace ComboStrap;


use Doku_Renderer_xhtml;
use syntax_plugin_combo_code;

class Prism
{

    const SNIPPET_NAME = 'prism';
    /**
     * The class used to mark the added prism code
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
        $snippetManager->attachCssInternalStyleSheetForSlot(self::SNIPPET_NAME);

        /**
         * Javascript
         */
        $snippetManager->attachJavascriptLibraryForSlot(
            self::SNIPPET_NAME,
            "$BASE_PRISM_CDN/components/prism-core.min.js",
            "sha256-vlRYHThwdq55dA+n1BKQRzzLwFtH9VINdSI68+5JhpU=");
        $snippetManager->attachJavascriptLibraryForSlot(
            self::SNIPPET_NAME,
            "$BASE_PRISM_CDN/plugins/toolbar/prism-toolbar.min.js",
            "sha256-FyIVdIHL0+ppj4Q4Ft05K3wyCsYikpHIDGI7dcaBalU="
        );
        // https://prismjs.com/plugins/normalize-whitespace/
        $snippetManager->attachJavascriptLibraryForSlot(
            self::SNIPPET_NAME,
            "$BASE_PRISM_CDN/plugins/normalize-whitespace/prism-normalize-whitespace.min.js",
            "sha256-gBzABGbXfQYYnyr8xmDFjx6KGO9dBYuypG1QBjO76pY=");

        // https://prismjs.com/plugins/show-language/
        $snippetManager->attachJavascriptLibraryForSlot(
            self::SNIPPET_NAME,
            "$BASE_PRISM_CDN/plugins/show-language/prism-show-language.min.js",
            "sha256-Z3GTw2RIadLG7KyP/OYB+aAxVYzvg2PByKzYrJlA1EM=");
        // https://prismjs.com/plugins/command-line/
        $snippetManager->attachJavascriptLibraryForSlot(
            self::SNIPPET_NAME,
            "$BASE_PRISM_CDN/plugins/command-line/prism-command-line.min.js",
            "sha256-9WlakH0Upf3N8DDteHlbeKCHxSsljby+G9ucUCQNiU0=");

        //https://prismjs.com/plugins/line-numbers/
        $snippetManager->attachJavascriptLibraryForSlot(
            self::SNIPPET_NAME,
            "$BASE_PRISM_CDN/plugins/line-numbers/prism-line-numbers.min.js",
            "sha256-K837BwIyiXo5k/9fCYgqUyA14bN4/Ve9P2SIT0KmZD0=");

        // https://prismjs.com/plugins/download-button/-->
        $snippetManager->attachJavascriptLibraryForSlot(
            self::SNIPPET_NAME,
            "$BASE_PRISM_CDN/plugins/download-button/prism-download-button.min.js",
            "sha256-CQyVQ5ejeTshlzOS/eCiry40br9f4fQ9jb5e4qPl7ZA=");


        $javascriptCode = <<<EOD
window.addEventListener('load', (event) => {

    if (typeof self === 'undefined' || !self.Prism || !self.document) {
        return;
    }

    // Loading the css from https://cdnjs.com/libraries/prism
    const head = document.querySelector('head');
    const baseCdn = "$BASE_PRISM_CDN";
    const stylesheets = [
        ["themes/$themeStyleSheet", "$themeIntegrity"],
        ["plugins/toolbar/prism-toolbar.css","sha256-kK4/JIYJUKI4Zdg9ZQ7FYyRIqeWPfYKi5QZHO2n/lJI="],
        /*https://prismjs.com/plugins/command-line/*/
        ["plugins/command-line/prism-command-line.css","sha256-UvoA9bIYCYQkCMTYG5p2LM8ZpJmnC4G8k0oIc89nuQA="],
        /*https://prismjs.com/plugins/line-numbers/*/
        ["plugins/line-numbers/prism-line-numbers.css","sha256-ye8BkHf2lHXUtqZ18U0KI3xjJ1Yv7P8lvdKBt9xmVJM="]
    ];

    stylesheets.forEach(stylesheet => {
            let link = document.createElement('link');
            link.rel="stylesheet"
            link.href=baseCdn+"/"+stylesheet[0];
            link.integrity=stylesheet[1];
            link.crossOrigin="anonymous";
            head.append(link);
        }
    )


    Prism.plugins.NormalizeWhitespace.setDefaults({
        'remove-trailing': true,
        'remove-indent': true,
        'left-trim': true,
        'right-trim': true,
    });

    if (!Prism.plugins.toolbar) {
        console.warn('Copy to Clipboard plugin loaded before Toolbar plugin.');

        return;
    }

    let ClipboardJS = window.ClipboardJS || undefined;

    if (!ClipboardJS && typeof require === 'function') {
        ClipboardJS = require('clipboard');
    }

    const callbacks = [];

    if (!ClipboardJS) {
        const script = document.createElement('script');
        const head = document.querySelector('head');

        script.onload = function() {
            ClipboardJS = window.ClipboardJS;

            if (ClipboardJS) {
                while (callbacks.length) {
                    callbacks.pop()();
                }
            }
        };

        script.src = 'https://cdnjs.cloudflare.com/ajax/libs/clipboard.js/2.0.0/clipboard.min.js';
        head.appendChild(script);
    }

    Prism.plugins.toolbar.registerButton('copy-to-clipboard', function (env) {
        var linkCopy = document.createElement('button');
        linkCopy.textContent = 'Copy';
        linkCopy.setAttribute('type', 'button');

        var element = env.element;

        if (!ClipboardJS) {
            callbacks.push(registerClipboard);
        } else {
            registerClipboard();
        }

        return linkCopy;

        function registerClipboard() {
            var clip = new ClipboardJS(linkCopy, {
                'text': function () {
                    return element.textContent;
                }
            });

            clip.on('success', function() {
                linkCopy.textContent = 'Copied!';

                resetText();
            });
            clip.on('error', function () {
                linkCopy.textContent = 'Press Ctrl+C to copy';

                resetText();
            });
        }

        function resetText() {
            setTimeout(function () {
                linkCopy.textContent = 'Copy';
            }, 5000);
        }
    });

});
EOD;
        $snippetManager->attachInternalJavascriptForSlot(self::SNIPPET_NAME, $javascriptCode);

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
        $theme = $plugin->getConf(Prism::CONF_PRISM_THEME);
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

        if (in_array($language, \syntax_plugin_combo_webcode::MARKIS)) {
            // Marki is not fully markdown
            // because it accepts space in super set html container and
            // prism will highlight them as indented code
            $language = "html";
        }
        /**
         * Language name mapping between the dokuwiki default
         * and prism
         */
        switch ($language) {
            case "rsplus":
                $language = "r";
                break;
            case "dos":
                $language = "batch";
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
        if ($attributes->hasComponentAttribute("line-numbers")) {
            $attributes->removeComponentAttribute("line-numbers");
            $attributes->addClassName('line-numbers');
        }


        // Command line
        if ($attributes->hasComponentAttribute("prompt")) {
            $attributes->addClassName("command-line");
            $attributes->addOutputAttributeValue("data-prompt", $attributes->getValueAndRemove("prompt"));
        } else {
            switch ($language) {
                case "bash":
                    $attributes->addClassName("command-line");
                    $attributes->addOutputAttributeValue("data-prompt", $plugin->getConf(self::CONF_BASH_PROMPT));
                    break;
                case "batch":
                    $attributes->addClassName("command-line");
                    $batch = trim($plugin->getConf(self::CONF_BATCH_PROMPT));
                    if (!empty($batch)) {
                        if (!strpos($batch, -1) == ">") {
                            $batch .= ">";
                        }
                    }
                    $attributes->addOutputAttributeValue("data-prompt", $batch);
                    break;
                case "powershell":
                    $attributes->addClassName("command-line");
                    $powerShell = trim($plugin->getConf(self::CONF_POWERSHELL_PROMPT));
                    if (!empty($powerShell)) {
                        if (!strpos($powerShell, -1) == ">") {
                            $powerShell .= ">";
                        }
                    }
                    $attributes->addOutputAttributeValue("data-prompt", $powerShell);
                    break;
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
            ->attachJavascriptLibraryForSlot(
                self::SNIPPET_ID_AUTOLOADER,
                self::BASE_PRISM_CDN . "/plugins/autoloader/prism-autoloader.min.js"
            );
    }


}
