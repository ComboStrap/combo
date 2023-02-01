<?php

namespace ComboStrap;

/**
 * Class syntax_plugin_combo_hr
 * [[https://www.w3.org/TR/2011/WD-html5-author-20110809/the-hr-element.html|W3c reference]]
 * [[https://www.digitala11y.com/separator-role/|Separator role]]
 * [[https://material.io/components/dividers|Divider]]
 *
 * HR is a void element and support both syntax
 * https://dev.w3.org/html5/html-author/#void-elements-0
 */
class HrTag
{

    const TAG = "hr";

    public static function render(TagAttributes $tagAttributes): string
    {
        return $tagAttributes->toHtmlEmptyTag("hr");
    }

}
