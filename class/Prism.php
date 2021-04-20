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
    const BASE_PRISM_CDN = "https://cdnjs.cloudflare.com/ajax/libs/prism/1.23.0/";
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
     * @var string[] https://cdnjs.cloudflare.com/ajax/libs/prism/1.23.0/themes/prism-{theme}.min.css
     *
     * or default
     *
     * https://cdnjs.cloudflare.com/ajax/libs/prism/1.23.0/themes/prism.min.css
     *
     * or
     *
     * https://github.com/PrismJS/prism-themes
     *
     * from https://cdnjs.com/libraries/prism
     */
    const THEMES_INTEGRITY = [
        Prism::PRISM_THEME => "sha512-tN7Ec6zAFaVSG3TpNAKtk4DOHNpSwKHxxrsiw4GHKESGPs5njn/0sMCUMl2svV4wo4BK/rCP7juYz+zx+l6oeQ==",
        "coy" => "sha512-CKzEMG9cS0+lcH4wtn/UnxnmxkaTFrviChikDEk1MAWICCSN59sDWIF0Q5oDgdG9lxVrvbENSV1FtjLiBnMx7Q==",
        "dark" => "sha512-Njdz7T/p6Ud1FiTMqH87bzDxaZBsVNebOWmacBjMdgWyeIhUSFU4V52oGwo3sT+ud+lyIE98sS291/zxBfozKw==",
        "funky" => "sha512-q59Usnbm/Dz3MeqoMEATHqIwozatJmXr/bFurDR7hpB5e2KxU+j2mp89Am9wq9jwZRaikpnKGHw4LP/Kr9soZQ==",
        "okaidia" => "sha512-mIs9kKbaw6JZFfSuo+MovjU+Ntggfoj8RwAmJbVXQ5mkAX5LlgETQEweFPI18humSPHymTb5iikEOKWF7I8ncQ==",
        "solarizedlight" => "sha512-fibfhB71IpdEKqLKXP/96WuX1cTMmvZioYp7T6I+lTbvJrrjEGeyYdAf09GHpFptF8toQ32woGZ8bw9+HjZc0A==",
        "tomorrow" => "sha512-vswe+cgvic/XBoF1OcM/TeJ2FW0OofqAVdCZiEYkd6dwGXthvkSFWOoGGJgS2CW70VK5dQM5Oh+7ne47s74VTg==",
        "twilight" => "sha512-akb4nfKzpmhujLUyollw5waBPeohuVf0Z5+cL+4Ngc4Db+V8szzx6ZTujguFjpmD076W8LImVIbOblmQ+vZMKA=="
    ];

    /**
     * The theme
     */
    const CONF_PRISM_THEME = "prismTheme";
    const PRISM_THEME_DEFAULT = "tomorrow";


    /**
     *
     * @param $theme
     *
     * Ter info: The theme of the default wiki is in the print.css file (search for code blocks)
     */
    public static function addSnippet($theme)
    {
        $BASE_PRISM_CDN = self::BASE_PRISM_CDN;
        $SCRIPT_ID = "prism";
        if ($theme == self::PRISM_THEME) {
            $themeStyleSheet = "prism.min.css";
        } else {
            $themeStyleSheet = "prism-$theme.min.css";
        }
        $themeIntegrity = self::THEMES_INTEGRITY[$theme];

        $tags = array();
        $tags['script'][] = array("src" => "$BASE_PRISM_CDN/components/prism-core.min.js");
        $tags['script'][] = array("src" => "$BASE_PRISM_CDN/plugins/autoloader/prism-autoloader.min.js");
        $tags['script'][] = array("src" => "$BASE_PRISM_CDN/plugins/toolbar/prism-toolbar.min.js");
        // https://prismjs.com/plugins/normalize-whitespace/
        $tags['script'][] = array("src" => "$BASE_PRISM_CDN/plugins/normalize-whitespace/prism-normalize-whitespace.min.js");
        // https://prismjs.com/plugins/show-language/
        $tags['script'][] = array("src" => "$BASE_PRISM_CDN/plugins/show-language/prism-show-language.min.js");
        // https://prismjs.com/plugins/command-line/
        $tags['script'][] = array("src" => "$BASE_PRISM_CDN/plugins/command-line/prism-command-line.min.js");
        //https://prismjs.com/plugins/line-numbers/
        $tags['script'][] = array("src" => "$BASE_PRISM_CDN/plugins/line-numbers/prism-line-numbers.min.js");
        // https://prismjs.com/plugins/download-button/-->
        $tags['script'][] = array(
            "src" => "$BASE_PRISM_CDN/plugins/download-button/prism-download-button.min.js",
            "integrity" => "sha512-rGJwSZEEYPBQjqYxrdg6Ug/6i763XQogKx+N/GF1rCGvfmhIlIUFxCjc4FmEdCu5dvovqxHsoe3IPMKP+KlgNQ==",
            "crossorigin" => "anonymous"
        );

        PluginUtility::getSnippetManager()->upsertTagsForBar($SCRIPT_ID, $tags);

        $javascriptCode = <<<EOD
document.addEventListener('DOMContentLoaded', (event) => {

    if (typeof self === 'undefined' || !self.Prism || !self.document) {
        return;
    }

    // Loading the css from https://cdnjs.com/libraries/prism
    const head = document.querySelector('head');
    const baseCdn = "$BASE_PRISM_CDN";
    const stylesheets = [
        ["themes/$themeStyleSheet", "$themeIntegrity"],
        ["plugins/toolbar/prism-toolbar.min.css","sha512-DSAA0ziYwggOJ3QyWFZhIaU8bSwQLyfnyIrmShRLBdJMtiYKT7Ju35ujBCZ6ApK3HURt34p2xNo+KX9ebQNEPQ=="],
        /*https://prismjs.com/plugins/command-line/*/
        ["plugins/command-line/prism-command-line.min.css","sha512-4Y1uID1tEWeqDdbb7452znwjRVwseCy9kK9BNA7Sv4PlMroQzYRznkoWTfRURSADM/SbfZSbv/iW5sNpzSbsYg=="],
        /*https://prismjs.com/plugins/line-numbers/*/
        ["plugins/line-numbers/prism-line-numbers.min.css","sha512-cbQXwDFK7lj2Fqfkuxbo5iD1dSbLlJGXGpfTDqbggqjHJeyzx88I3rfwjS38WJag/ihH7lzuGlGHpDBymLirZQ=="]
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
        PluginUtility::getSnippetManager()->upsertJavascriptForBar($SCRIPT_ID, $javascriptCode);

    }

    /**
     * Add the first block of prism
     * @param \Doku_Renderer_xhtml $renderer
     * @param TagAttributes $attributes
     * @param \DokuWiki_Syntax_Plugin $plugin
     */
    public static function htmlEnter(\Doku_Renderer_xhtml $renderer, $attributes, \DokuWiki_Syntax_Plugin $plugin)
    {

        /**
         * Display none, no rendering
         */
        $display = $attributes->getValueAndRemove("display");
        if ($display != null) {
            if ($display == "none") {
                return;
            }
        }

        $theme = $plugin->getConf(Prism::CONF_PRISM_THEME);
        /**
         * Add prism
         */
        Prism::addSnippet($theme);


        /**
         * Add HTML
         */
        $language = strtolower($attributes->getValueAndRemove(TagAttributes::TYPE_KEY));
        if ($language == "dw") {
            $language = "html";
        }
        /**
         * Language name mapping between the dokuwiki default
         * and prism
         */
        if ($language == "rsplus") {
            $language = "r";
        }
        if ($language == "dos") {
            $language = "batch";
        }
        if ($language == "apache") {
            $language = "apacheconf";
        }
        if ($language == "babel") {
            $language = "javascript";
        }

        StringUtility::addEolCharacterIfNotPresent($renderer->doc);
        $attributes->addClassName('language-' . $language);

        if ($attributes->hasComponentAttribute("line-numbers")) {
            $attributes->removeComponentAttribute("line-numbers");
            $attributes->addClassName('line-numbers');
        }

        /**
         * Pre element the bar
         */
        $preAttributes = TagAttributes::createEmpty();
        $addedClass = 'combo_' . $plugin->getPluginComponent();
        $preAttributes->addClassName($addedClass);
        $attributes->addClassName($addedClass);
        // Command line
        if ($attributes->hasComponentAttribute("prompt")) {
            $preAttributes->addClassName("command-line");
            $preAttributes->addHtmlAttributeValue("data-prompt", $attributes->getValueAndRemove("prompt"));
        } else {
            switch ($language) {
                case "bash":
                    $preAttributes->addClassName("command-line");
                    $preAttributes->addHtmlAttributeValue("data-prompt", $plugin->getConf(self::CONF_BASH_PROMPT));
                    break;
                case "batch":
                    $preAttributes->addClassName("command-line");
                    $batch = trim($plugin->getConf(self::CONF_BATCH_PROMPT));
                    if (!empty($batch)) {
                        if (!strpos($batch, -1) == ">") {
                            $batch .= ">";
                        }
                    }
                    $preAttributes->addHtmlAttributeValue("data-prompt", $batch);
                    break;
                case "powershell":
                    $preAttributes->addClassName("command-line");
                    $powerShell = trim($plugin->getConf(self::CONF_POWERSHELL_PROMPT));
                    if (!empty($powerShell)) {
                        if (!strpos($powerShell, -1) == ">") {
                            $powerShell .= ">";
                        }
                    }
                    $preAttributes->addHtmlAttributeValue("data-prompt", $powerShell);
                    break;
            }
        }

        // Download
        $preAttributes->addHtmlAttributeValue('data-download-link', true);
        if ($attributes->hasComponentAttribute(syntax_plugin_combo_code::FILE_PATH_KEY)) {
            $fileSrc = $attributes->getValueAndRemove(syntax_plugin_combo_code::FILE_PATH_KEY);
            $preAttributes->addHtmlAttributeValue('data-src', $fileSrc);
            $preAttributes->addHtmlAttributeValue('data-download-link-label', "Download " . $fileSrc);
        } else {
            $preAttributes->addHtmlAttributeValue('data-src', "file." . $language);
        }
        $htmlCode = $preAttributes->toHtmlEnterTag("pre") . DOKU_LF;

        /**
         * Code element
         */
        $htmlCode .= $attributes->toHtmlEnterTag('code') . DOKU_LF;

        /**
         * Return
         */
        $renderer->doc .= $htmlCode;

    }

    /**
     * @param Doku_Renderer_xhtml $renderer
     * @param TagAttributes $attributes
     */
    public static function htmlExit(\Doku_Renderer_xhtml $renderer, $attributes)
    {
        /**
         * Display none, no rendering
         */
        $display = $attributes->getValueAndRemove("display");
        if ($display != null) {
            if ($display == "none") {
                return;
            }
        }
        $renderer->doc .= '</code>' . DOKU_LF . '</pre>' . DOKU_LF;
    }


}
