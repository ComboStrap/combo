<?php

namespace ComboStrap;

use syntax_plugin_combo_brand;

class BrandListTag
{

    public const MARKUP = "brand-list";

    public static function render(TagAttributes $tagAttributes): string
    {
        try {
            $brandDictionary = Brand::getBrandDictionary();
            $brandNames = array_keys($brandDictionary);
            sort($brandNames);
        } catch (ExceptionCompile $e) {
            return "Error while creating the brand list. Error: {$e->getMessage()}";
        }

        $variants = BrandButton::getVariants();

        $snippetManager = PluginUtility::getSnippetManager();
        $snippetManager->attachCssInternalStyleSheet("table");
        $html = <<<EOF
<table class="table table-non-fluid">
<thead>
    <tr>
    <th scope="col">
Brand Name
    </th>
EOF;
        foreach ($variants as $variant) {
            $iconType = ucfirst($variant[BrandTag::ICON_ATTRIBUTE]);
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
                    $iconType = $variant[BrandTag::ICON_ATTRIBUTE];
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
                        $page = MarkupPath::createFromRequestedPage();
                    }
                    $brandTagAttributes = $brandButton->getHtmlAttributes($page);
                    $buttonTag = $brandButton->getHtmlElement($brandTagAttributes);
                    $html .= $brandTagAttributes->toHtmlEnterTag($buttonTag);

                    if ($brandButton->hasIcon()) {
                        $iconArrayAttributes = $brandButton->getIconAttributes();
                        $iconAttributes = TagAttributes::createFromCallStackArray($iconArrayAttributes);
                        $name = $iconAttributes->getValueAndRemoveIfPresent(FetcherSvg::NAME_ATTRIBUTE);
                        if ($name !== null) {
                            $html .= Icon::createFromName($name, $iconAttributes)->toHtml();
                        } else {
                            $html .= "Icon name is null for brand $brandName";
                        }
                    }
                    $snippetManager->attachCssInternalStyleSheet($brandButton->getStyleScriptIdentifier(), $brandButton->getStyle());
                    $html .= "</$buttonTag></td>";
                }

                /**
                 * End row
                 */
                $html .= "</tr>" . PHP_EOL;
            } catch (ExceptionCompile $e) {
                $message = "Error while rendering the brand $brandName. Error: {$e->getMessage()}";
                if (!PluginUtility::isDevOrTest()) {
                    $rowSpan = sizeof($variants) + 1; // 1 for the brand column
                    $html .= "<tr><td rowspan=\"$rowSpan\" class=\"text-danger\">$message</td></tr>";
                } else {
                    throw new ExceptionRuntime($message, BrandListTag::MARKUP, 0, $e);
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
        return $html;
    }
}
