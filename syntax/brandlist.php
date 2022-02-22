<?php


require_once(__DIR__ . "/../ComboStrap/PluginUtility.php");

use ComboStrap\Brand;
use ComboStrap\BrandButton;
use ComboStrap\ExceptionCombo;
use ComboStrap\ExceptionComboRuntime;
use ComboStrap\Icon;
use ComboStrap\Page;
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

                $tagAttributes = TagAttributes::createFromTagMatch($match,
                    [
                        TagAttributes::TYPE_KEY => BrandButton::TYPE_BUTTON_BRAND
                    ],
                    BrandButton::TYPE_BUTTONS
                );
                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::ATTRIBUTES => $tagAttributes->toCallStackArray()
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
            try {
                $brandDictionary = Brand::getBrandDictionary();
                $brandNames = array_keys($brandDictionary);
                sort($brandNames);
            } catch (ExceptionCombo $e) {
                $renderer->doc .= "Error while creating the brand list. Error: {$e->getMessage()}";
                return false;
            }

            $variants = BrandButton::getVariants();

            $snippetManager = PluginUtility::getSnippetManager();
            $snippetManager->attachCssInternalStyleSheetForSlot("table");
            $html = <<<EOF
<table class="table table-non-fluid">
<thead>
    <tr>
    <th scope="col">
Brand Name
    </th>
EOF;
            foreach ($variants as $variant) {
                $iconType = ucfirst($variant[syntax_plugin_combo_brand::ICON_ATTRIBUTE]);
                $widgetType = ucfirst($variant[TagAttributes::TYPE_KEY]);
                $html .= <<<EOF
    <th scope="col">
$widgetType <br/> $iconType
    </th>
EOF;
            }

            $html .= <<<EOF
</tr>
</thead>
<tbody>
EOF;

            $tagAttributes = TagAttributes::createFromCallStackArray($data[PluginUtility::ATTRIBUTES]);
            $type = $tagAttributes->getType();
            foreach ($brandNames as $brandName) {

                try {

                    $brandButton = new BrandButton($brandName, $type);
                    if (!$brandButton->getBrand()->supportButtonType($type)) {
                        continue;
                    }
                    /**
                     * Begin row
                     */
                    $html .= "<tr>";

                    /**
                     * First column
                     */

                    $html .= "<td>" . ucfirst($brandName) . "</td>";


                    foreach ($variants as $variant) {
                        $iconType = $variant[syntax_plugin_combo_brand::ICON_ATTRIBUTE];
                        $widgetType = $variant[TagAttributes::TYPE_KEY];
                        $brandButton
                            ->setIconType($iconType)
                            ->setWidget($widgetType);
                        /**
                         * Second column
                         */
                        $html .= "<td>";
                        $page = null;
                        if ($type === BrandButton::TYPE_BUTTON_SHARE) {
                            $page = Page::createPageFromRequestedPage();
                        }
                        $html .= $brandButton->getLinkAttributes($page)->toHtmlEnterTag("a");

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
                        $snippetManager->attachCssInternalStyleSheetForSlot($brandButton->getStyleScriptIdentifier(), $brandButton->getStyle());
                        $html .= "</a></td>";
                    }

                    /**
                     * End row
                     */
                    $html .= "</tr>" . PHP_EOL;
                } catch (ExceptionCombo $e) {
                    $message = "Error while rendering the brand $brandName. Error: {$e->getMessage()}";
                    if (!PluginUtility::isDevOrTest()) {
                        $rowSpan = sizeof($variants)+1; // 1 for the brand column
                        $renderer->doc .= "<tr><td rowspan=\"$rowSpan\" class=\"text-danger\">$message</td></tr>";
                    } else {
                        throw new ExceptionComboRuntime($message, self::TAG, 0, $e);
                    }
                }
            }
            /**
             * End table
             */
            $html .= <<<EOF
</tbody>
</table>
EOF;

            $renderer->doc .= $html;


        }
        // unsupported $mode
        return false;
    }


}

