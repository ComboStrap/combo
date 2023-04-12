<?php

namespace ComboStrap\Tag;

use ComboStrap\CallStack;
use ComboStrap\Dimension;
use ComboStrap\Display;
use ComboStrap\ExceptionBadState;
use ComboStrap\ExceptionCompile;
use ComboStrap\ExceptionNotFound;
use ComboStrap\ExecutionContext;
use ComboStrap\FetcherMarkup;
use ComboStrap\FetcherMarkupWebcode;
use ComboStrap\FetcherRawLocalPath;
use ComboStrap\LogUtility;
use ComboStrap\PluginUtility;
use ComboStrap\TagAttribute\StyleAttribute;
use ComboStrap\TagAttributes;
use ComboStrap\WikiPath;
use syntax_plugin_combo_code;
use syntax_plugin_combo_codemarkdown;

class WebCodeTag
{

    public const TAG = 'webcode';
    /**
     * The tag that have codes
     */
    public const CODE_TAGS = array(
        syntax_plugin_combo_code::CODE_TAG,
        "plugin_combo_code",
        syntax_plugin_combo_codemarkdown::TAG
    );
    /**
     * The attribute names in the array
     */
    public const CODES_ATTRIBUTE = "codes";
    public const EXTERNAL_RESOURCES_ATTRIBUTE_DISPLAY = 'externalResources';
    public const USE_CONSOLE_ATTRIBUTE = "useConsole";
    public const RENDERING_ONLY_RESULT_DEPRECATED = "onlyresult";
    public const CANONICAL = WebCodeTag::TAG;
    public const DOKUWIKI_LANG = 'dw';
    public const FRAMEBORDER_ATTRIBUTE = "frameborder";
    /**
     * @deprecated for type
     */
    public const RENDERING_MODE_ATTRIBUTE = 'renderingmode';
    public const MARKIS = [WebCodeTag::MARKI_LANG, WebCodeTag::DOKUWIKI_LANG];
    public const EXTERNAL_RESOURCES_ATTRIBUTE_KEY = 'externalresources';
    /**
     * Marki code
     */
    public const MARKI_LANG = 'marki';
    public const IFRAME_BOOLEAN_ATTRIBUTE = "iframe";
    const STORY_TYPE = "story";
    const RESULT_TYPE = "result";
    const INJECT_TYPE = "inject";

    public static function getClass(): string
    {
        return StyleAttribute::addComboStrapSuffix(WebCodeTag::TAG);
    }

    public static function getKnownTypes(): array
    {
        return [self::STORY_TYPE, self::RESULT_TYPE, self::INJECT_TYPE];
    }

    public static function getDefaultAttributes(): array
    {
        $defaultAttributes = array();
        $defaultAttributes[Dimension::WIDTH_KEY] = '100%';
        // 'type': no default to see if it was set because the default now is dependent on the content
        // 'height' is set by the javascript if not set
        // 'width' and 'scrolling' gets their natural value
        return $defaultAttributes;
    }

    public static function handleExit(\Doku_Handler $handler): array
    {
        /**
         * Capture all codes
         */
        $codes = array();
        /**
         * Does the javascript contains a console statement
         */
        $useConsole = false;

        /**
         * Callstack
         */
        $callStack = CallStack::createFromHandler($handler);
        $openingTag = $callStack->moveToPreviousCorrespondingOpeningCall();
        $type = $openingTag->getType();
        $renderingMode = $openingTag->getAttribute(WebCodeTag::RENDERING_MODE_ATTRIBUTE);
        if ($renderingMode !== null) {
            LogUtility::warning("The `renderingmode` attribute has been deprecated for the webcode `type` attribute.");
            if ($type === null) {
                $type = strtolower($renderingMode);
            }
        }
        if ($type === WebCodeTag::RENDERING_ONLY_RESULT_DEPRECATED) {
            LogUtility::warning("The `type` value (" . self::RENDERING_ONLY_RESULT_DEPRECATED . ") should be replaced by (" . self::RESULT_TYPE . ")");
            $type = WebCodeTag::RESULT_TYPE;
        }

        /**
         * The mime (ie xml,html, ...) and code content are in two differents
         * call. To be able to set the content to the good type
         * we keep a trace of it
         */
        $actualCodeType = "";

        /**
         * Loop
         */
        while ($actualTag = $callStack->next()) {


            $tagName = $actualTag->getTagName();
            if (in_array($tagName, WebCodeTag::CODE_TAGS)) {

                /**
                 * Only result or inject mode, we don't display the code
                 * on all node (enter, exit and unmatched)
                 */
                if (in_array($type, [WebCodeTag::RESULT_TYPE, self::INJECT_TYPE])) {
                    $actualTag->addAttribute(Display::DISPLAY, Display::DISPLAY_NONE_VALUE);
                }

                switch ($actualTag->getState()) {

                    case DOKU_LEXER_ENTER:
                        // Get the code (The content between the code nodes)
                        // We ltrim because the match gives us the \n at the beginning and at the end
                        $actualCodeType = strtolower(trim($actualTag->getType()));

                        // Xml is html
                        if ($actualCodeType === 'xml') {
                            $actualCodeType = 'html';
                        }

                        // markdown, dokuwiki is marki
                        if (in_array($actualCodeType, ['md', 'markdown', 'dw'])) {
                            $actualCodeType = WebCodeTag::MARKI_LANG;
                        }

                        // The code for a language may be scattered in multiple block
                        if (!isset($codes[$actualCodeType])) {
                            $codes[$actualCodeType] = "";
                        }

                        continue 2;

                    case DOKU_LEXER_UNMATCHED:

                        $codeContent = $actualTag->getPluginData()[PluginUtility::PAYLOAD];

                        if (empty($actualCodeType)) {
                            LogUtility::msg("The type of the code should not be null for the code content " . $codeContent, LogUtility::LVL_MSG_WARNING, WebCodeTag::TAG);
                            continue 2;
                        }

                        // Append it
                        $codes[$actualCodeType] = $codes[$actualCodeType] . $codeContent;

                        // Check if a javascript console function is used, only if the flag is not set to true
                        if (!$useConsole) {
                            if (in_array($actualCodeType, array('babel', 'javascript', 'html', 'xml'))) {
                                // if the code contains 'console.'
                                $result = preg_match('/' . 'console\.' . '/is', $codeContent);
                                if ($result) {
                                    $useConsole = true;
                                }
                            }
                        }
                        // Reset
                        $actualCodeType = "";
                        break;

                }
            }

        }

        /**
         * By default, markup code
         * is rendered inside the page
         * We got less problem such as iframe overflow
         * due to lazy loading, such as relative link, ...
         */
        if (
            array_key_exists(WebCodeTag::MARKI_LANG, $codes)
            && count($codes) === 1
            && $openingTag->getAttribute(WebCodeTag::IFRAME_BOOLEAN_ATTRIBUTE) === null
            && $openingTag->getType() === null
        ) {
            $openingTag->setType(self::INJECT_TYPE);
        }

        return [
            WebCodeTag::CODES_ATTRIBUTE => $codes,
            WebCodeTag::USE_CONSOLE_ATTRIBUTE => $useConsole,
            PluginUtility::ATTRIBUTES => $openingTag->getAttributes()
        ];
    }

    /**
     * Tag is of an iframe (Web code) or a div (wiki markup)
     */
    public static function renderExit(TagAttributes $tagAttributes, array $data)
    {

        $codes = $data[WebCodeTag::CODES_ATTRIBUTE];

        $type = $tagAttributes->getType();
        if ($type === null) {
            $type = self::STORY_TYPE;
        }

        /**
         * Rendering mode is used in handle exit, we delete it
         * to not get it in the HTML output
         */
        $tagAttributes->removeComponentAttributeIfPresent(WebCodeTag::RENDERING_MODE_ATTRIBUTE);

        // Create the real output of webcode
        if (sizeof($codes) == 0) {
            return false;
        }


        // Css
        $snippetSystem = PluginUtility::getSnippetManager();
        $snippetSystem->attachCssInternalStyleSheet(WebCodeTag::TAG);
        $snippetSystem->attachJavascriptFromComponentId(WebCodeTag::TAG);

        // Mermaid code ?
        if (array_key_exists(MermaidTag::MERMAID_CODE, $codes)) {
            $mermaidCode = "";
            foreach ($codes as $codeKey => $code) {
                if ($codeKey !== MermaidTag::MERMAID_CODE) {
                    LogUtility::error("The code type ($codeKey) was mixed with mermaid code in a webcode and this is not yet supported. The code was skipped");
                    continue;
                }
                $mermaidCode .= $code;
            }
            $tagAttributes->addComponentAttributeValue(MermaidTag::MARKUP_CONTENT_ATTRIBUTE, $mermaidCode);
            return MermaidTag::renderEnter($tagAttributes);
        }

        /**
         * Dokuwiki Code
         * (Just HTML)
         */
        if (array_key_exists(WebCodeTag::MARKI_LANG, $codes)) {

            $markupCode = $codes[WebCodeTag::MARKI_LANG];

            if ($type === self::INJECT_TYPE) {
                /**
                 * the div is to be able to apply some CSS
                 * such as don't show editbutton on webcode
                 */
                $html = $tagAttributes->toHtmlEnterTag("div");
                try {
                    $contextPath = ExecutionContext::getActualOrCreateFromEnv()
                        ->getContextPath();
                    $html .= FetcherMarkup::confChild()
                        ->setRequestedMarkupString($markupCode)
                        ->setDeleteRootBlockElement(false)
                        ->setIsDocument(false)
                        ->setRequestedContextPath($contextPath)
                        ->setRequestedMimeToXhtml()
                        ->build()
                        ->getFetchString();
                } catch (ExceptionCompile $e) {
                    $html .= $e->getMessage();
                    LogUtility::log2file("Error while rendering webcode", LogUtility::LVL_MSG_ERROR, WebCodeTag::CANONICAL, $e);
                }
                $html .= "</div>";
                return $html;
            }

            /**
             * Iframe output
             */
            $tagAttributes->removeComponentAttribute(WebCodeTag::IFRAME_BOOLEAN_ATTRIBUTE);

            if (!$tagAttributes->hasAttribute(TagAttributes::NAME_ATTRIBUTE)) {
                $tagAttributes->addOutputAttributeValueIfNotEmpty(TagAttributes::NAME_ATTRIBUTE, "WebCode iFrame");
            }
            try {
                $url = FetcherMarkupWebcode::createFetcherMarkup($markupCode)
                    ->getFetchUrl()
                    ->toString();
                $tagAttributes->addOutputAttributeValue("src", $url);
            } catch (ExceptionBadState $e) {
                // The markup is provided, we shouldn't have a bad state
                LogUtility::internalError("We were unable to set the iframe URL. Error:{$e->getMessage()}", WebCodeTag::CANONICAL);
            }
            return self::finishIframe($tagAttributes);


        }


        /**
         * Js Html Css language
         */
        if ($type === self::INJECT_TYPE) {
            $htmlToInject = self::getCss($codes);
            return $htmlToInject . self::getBodyHtmlAndJavascript($codes, false);
        }

        /** @noinspection JSUnresolvedLibraryURL */

        $headIFrame = <<<EOF
<meta http-equiv="content-type" content="text/html; charset=UTF-8"/>
<link id="normalize" rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/normalize/3.0.3/normalize.min.css"/>
EOF;


        // External Resources such as css stylesheet or js
        $externalResources = [];
        if ($tagAttributes->hasComponentAttribute(WebCodeTag::EXTERNAL_RESOURCES_ATTRIBUTE_KEY)) {
            LogUtility::warning("The (" . WebCodeTag::EXTERNAL_RESOURCES_ATTRIBUTE_KEY . ") has been deprecated. You should put your script/link in a code block with the `display` attribute set to `none`.");
            $resources = $tagAttributes->getValueAndRemove(WebCodeTag::EXTERNAL_RESOURCES_ATTRIBUTE_KEY);
            $externalResources = explode(",", $resources);
        }

        // Babel Preprocessor, if babel is used, add it to the external resources
        if (array_key_exists('babel', $codes)) {
            $babelMin = "https://unpkg.com/babel-standalone@6/babel.min.js";
            // a load of babel invoke it (be sure to not have it twice
            if (!(array_key_exists($babelMin, $externalResources))) {
                $externalResources[] = $babelMin;
            }
        }

        // Add the external resources
        foreach ($externalResources as $externalResource) {
            $pathInfo = pathinfo($externalResource);
            $fileExtension = $pathInfo['extension'];
            switch ($fileExtension) {
                case 'css':
                    $headIFrame .= "<link rel=\"stylesheet\" type=\"text/css\" href=\"$externalResource\"/>";
                    break;
                case 'js':
                    $headIFrame .= "<script type=\"text/javascript\" src=\"$externalResource\"></script>";
                    break;
            }
        }

        // WebConsole style sheet
        $webcodeClass = WebCodeTag::getClass();
        $cssUrl = FetcherRawLocalPath::createFromPath(WikiPath::createComboResource("webcode:webcode-iframe.css"))->getFetchUrl()->toHtmlString();
        $headIFrame .= "<link class='$webcodeClass' rel=\"stylesheet\" type=\"text/css\" href=\"$cssUrl\"/>";

        // A little margin to make it neater
        // that can be overwritten via cascade
        $headIFrame .= "<style class=\"$webcodeClass\">body { margin:10px } /* default margin */</style>";

        // The css
        $headIFrame .= self::getCss($codes);

        // The javascript console script should be first to handle console.log in the content
        $useConsole = $data[WebCodeTag::USE_CONSOLE_ATTRIBUTE];
        if ($useConsole) {
            $url = FetcherRawLocalPath::createFromPath(WikiPath::createComboResource("webcode:webcode-console.js"))->getFetchUrl()->toHtmlString();
            $headIFrame .= <<<EOF
<script class="$webcodeClass" type="text/javascript" src="$url"></script>
EOF;
        }
        $body = self::getBodyHtmlAndJavascript($codes, $useConsole);
        $iframeSrcValue = <<<EOF
<html lang="en">
<head>
<title>Made by WebCode</title>
$headIFrame
</head>
<body>
$body
</body>
</html>
EOF;
        $tagAttributes->addOutputAttributeValue("srcdoc", $iframeSrcValue);

        // Code bar with button
        // Credits bar
        $bar = '<div class="webcode-bar">';
        $bar .= '<div class="webcode-bar-item">' . PluginUtility::getDocumentationHyperLink(WebCodeTag::TAG, "Rendered by WebCode", false) . '</div>';
        $bar .= '<div class="webcode-bar-item">' . self::addJsFiddleButton($codes, $externalResources, $useConsole, $tagAttributes->getValue("name")) . '</div>';
        $bar .= '</div>';

        return self::finishIframe($tagAttributes, $bar);


    }

    /**
     * @param array $codes the array containing the codes
     * @param array $externalResources the attributes of a call (for now the externalResources)
     * @param bool $useConsole
     * @param null $snippetTitle
     * @return string the HTML form code
     *
     * Specification, see http://doc.jsfiddle.net/api/post.html
     */
    public static function addJsFiddleButton($codes, $externalResources, $useConsole = false, $snippetTitle = null): string
    {

        $postURL = "https://jsfiddle.net/api/post/library/pure/"; //No Framework


        if ($useConsole) {
            // If their is a console.log function, add the Firebug Lite support of JsFiddle
            // Seems to work only with the Edge version of jQuery
            // $postURL .= "edge/dependencies/Lite/";
            // The firebug logging is not working anymore because of 404

            // Adding them here
            // The firebug resources for the console.log features
            try {
                $externalResources[] = FetcherRawLocalPath::createFromPath(WikiPath::createComboResource(':firebug:firebug-lite.css'))->getFetchUrl()->toString();
                $externalResources[] = FetcherRawLocalPath::createFromPath(WikiPath::createComboResource(':firebug:firebug-lite-1.2.js'))->getFetchUrl()->toString();
            } catch (ExceptionNotFound $e) {
                LogUtility::internalError("We were unable to add the firebug css and js. Error: {$e->getMessage()}", WebCodeTag::CANONICAL);
            }

        }

        // The below code is to prevent this JsFiddle bug: https://github.com/jsfiddle/jsfiddle-issues/issues/726
        // The order of the resources is not guaranteed
        // We pass then the resources only if their is one resources
        // Otherwise we pass them as a script element in the HTML.
        if (count($externalResources) <= 1) {
            $externalResourcesInput = '<input type="hidden" name="resources" value="' . implode(",", $externalResources) . '"/>';
        } else {
            $codes['html'] .= "\n\n\n\n\n<!-- The resources -->\n";
            $codes['html'] .= "<!-- They have been added here because their order is not guarantee through the API. -->\n";
            $codes['html'] .= "<!-- See: https://github.com/jsfiddle/jsfiddle-issues/issues/726 -->\n";
            foreach ($externalResources as $externalResource) {
                if ($externalResource !== "") {
                    $extension = pathinfo($externalResource)['extension'];
                    switch ($extension) {
                        case "css":
                            $codes['html'] .= "<link href=\"$externalResource\" rel=\"stylesheet\"/>\n";
                            break;
                        case "js":
                            $codes['html'] .= "<script src=\"$externalResource\"></script>\n";
                            break;
                        default:
                            $codes['html'] .= "<!-- " . $externalResource . " -->\n";
                    }
                }
            }
        }

        $jsCode = $codes['javascript'];
        $jsPanel = 0; // language for the js specific panel (0 = JavaScript)
        if (array_key_exists('babel', $codes)) {
            $jsCode = $codes['babel'];
            $jsPanel = 3; // 3 = Babel
        }

        // Title and description
        global $ID;
        $pageTitle = tpl_pagetitle($ID, true);
        if (!$snippetTitle) {

            $snippetTitle = "Code from " . $pageTitle;
        }
        $description = "Code from the page '" . $pageTitle . "' \n" . wl($ID, $absolute = true);
        return '<form  method="post" action="' . $postURL . '" target="_blank">' .
            '<input type="hidden" name="title" value="' . htmlentities($snippetTitle) . '"/>' .
            '<input type="hidden" name="description" value="' . htmlentities($description) . '"/>' .
            '<input type="hidden" name="css" value="' . htmlentities($codes['css']) . '"/>' .
            '<input type="hidden" name="html" value="' . htmlentities("<!-- The HTML -->" . $codes['html']) . '"/>' .
            '<input type="hidden" name="js" value="' . htmlentities($jsCode) . '"/>' .
            '<input type="hidden" name="panel_js" value="' . htmlentities($jsPanel) . '"/>' .
            '<input type="hidden" name="wrap" value="b"/>' .  //javascript no wrap in body
            $externalResourcesInput .
            '<button>Try the code</button>' .
            '</form>';

    }

    private static function finishIframe(TagAttributes $tagAttributes, string $bar = ""): string
    {
        /**
         * The iframe does not have any width
         * By default, we set it to 100% and it can be
         * constraint with the `width` attributes that will
         * set a a max-width
         */
        $tagAttributes->addStyleDeclarationIfNotSet("width", "100%");

        /**
         * FrameBorder
         */
        $frameBorder = $tagAttributes->getValueAndRemoveIfPresent(WebCodeTag::FRAMEBORDER_ATTRIBUTE);
        if ($frameBorder !== null && $frameBorder == 0) {
            $tagAttributes->addStyleDeclarationIfNotSet("border", "none");
        }

        $iFrameHtml = $tagAttributes->toHtmlEnterTag("iframe") . '</iframe>';
        return "<div class=\"webcode-wrapper\">" . $iFrameHtml . $bar . '</div>';
    }

    /**
     * Return the body
     * @param $codes - the code to apply
     * @param $useConsole - if the console area should be printed
     * @return string - the html and javascript
     */
    private static function getBodyHtmlAndJavascript($codes, $useConsole): string
    {

        $body = "";
        if (array_key_exists('html', $codes)) {
            // The HTML code
            $body .= $codes['html'];
        }
        // The javascript console area is based at the end of the HTML document
        if ($useConsole) {

            $body .= <<<EOF
<!-- WebCode Console -->
<div class="webcode-console-wrapper">
    <p class="webConsoleTitle">Console Output:</p>
    <div id="webCodeConsole"></div>
</div>
EOF;
        }
        // The javascript comes at the end because it may want to be applied on previous HTML element
        // as the page load in the IO order, javascript must be placed at the end
        if (array_key_exists('javascript', $codes)) {
            /**
             * The user should escapes the following character * <, >, ", ', \, and &.
             * because they will interfere with the HTML parser
             *
             * The user should write `<\/script>` and note `</script>`
             */
            // The Javascript code
            $body .= '<script class="webcode-javascript" type="text/javascript">' . $codes['javascript'] . '</script>';
        }
        if (array_key_exists('babel', $codes)) {
            // The Babel code
            $body .= '<script type="text/babel">' . $codes['babel'] . '</script>';
        }
        return $body;

    }

    private static function getCss($codes): string
    {
        if (array_key_exists('css', $codes)) {
            return '<style>' . $codes['css'] . '</style>';
        };
        return "";
    }
}
