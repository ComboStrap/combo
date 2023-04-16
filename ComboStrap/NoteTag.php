<?php

namespace ComboStrap;



/**
 * Implementation of a inline note
 * called an alert in <a href="https://getbootstrap.com/docs/4.0/components/badge/">bootstrap</a>
 *
 * Quickly created with a copy of a badge
 */
class NoteTag
{


    public const ATTRIBUTE_ROUNDED = "rounded";
    public const INOTE_CONF_DEFAULT_ATTRIBUTES_KEY = 'defaultInoteAttributes';
    public const TAG_INOTE = "inote";

    const KNOWN_TYPES = [
        \syntax_plugin_combo_note::WARNING_TYPE,
        \syntax_plugin_combo_note::IMPORTANT_TYPE,
        \syntax_plugin_combo_note::TIP_TYPE,
        \syntax_plugin_combo_note::INFO_TYPE,
    ];


    public static function renderEnterInlineNote(TagAttributes $tagAttributes): string
    {
        $tagAttributes->addClassName("badge");

        PluginUtility::getSnippetManager()->attachCssInternalStyleSheet(NoteTag::TAG_INOTE);

        $type = $tagAttributes->getValue(TagAttributes::TYPE_KEY);

        // Switch for the color
        switch ($type) {
            case "important":
                $type = "warning";
                break;
            case "warning":
                $type = "danger";
                break;
        }

        if ($type != "tip") {
            $bootstrapVersion = Bootstrap::getBootStrapMajorVersion();
            if ($bootstrapVersion == Bootstrap::BootStrapFiveMajorVersion) {
                /**
                 * We are using
                 */
                $tagAttributes->addClassName("alert-" . $type);
            } else {
                $tagAttributes->addClassName("badge-" . $type);
            }
        } else {
            if (!$tagAttributes->hasComponentAttribute("background-color")) {
                $tagAttributes->addStyleDeclarationIfNotSet("background-color", "#fff79f"); // lum - 195
                $tagAttributes->addClassName("text-dark");
            }
        }
        $rounded = $tagAttributes->getValueAndRemove(NoteTag::ATTRIBUTE_ROUNDED);
        if (!empty($rounded)) {
            $tagAttributes->addClassName("badge-pill");
        }

        return $tagAttributes->toHtmlEnterTag("span");
    }

    public static function renderClosingInlineNote(): string
    {
        return '</span>';
    }

}
