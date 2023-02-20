<?php

namespace ComboStrap;




class DropDownTag
{

    public const TAG = "dropdown";

    public static function renderEnterXhtml(TagAttributes $tagAttributes): string
    {

        $dropDownId = ExecutionContext::getActualOrCreateFromEnv()
            ->getIdManager()
            ->generateNewHtmlIdForComponent(DropDownTag::TAG);

        $name = $tagAttributes->getValueAndRemoveIfPresent("name","unknown name");
        $tagAttributes->addClassName("nav-item");
        $tagAttributes->addClassName("dropdown");

        /**
         * New namespace for data attribute
         */
        $bootstrapNameSpace = Bootstrap::getDataNamespace();
        $dataToggleAttribute = "data{$bootstrapNameSpace}-toggle";
        $liHtml = $tagAttributes->toHtmlEnterTag("li");
        return <<<EOF
$liHtml
    <a id="$dropDownId" href="#" class="nav-link dropdown-toggle active" {$dataToggleAttribute}="dropdown" role="button" aria-haspopup="true" aria-expanded="false" title="$name">$name</a>
    <div class="dropdown-menu" aria-labelledby="$dropDownId">
EOF;

    }

    public static function renderExitXhtml(): string
    {
        return '</div></li>';
    }
}
