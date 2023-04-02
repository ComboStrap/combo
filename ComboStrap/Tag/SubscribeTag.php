<?php

namespace ComboStrap\Tag;

use action_plugin_combo_instructionspostprocessing;
use ComboStrap\CallStack;
use ComboStrap\ContainerTag;
use ComboStrap\DataType;
use ComboStrap\EditButton;
use ComboStrap\ExceptionBadArgument;
use ComboStrap\ExceptionNotFound;
use ComboStrap\ExecutionContext;
use ComboStrap\Site;
use ComboStrap\Template\TemplateForComponent;
use ComboStrap\TemplateEngine;
use ComboStrap\TagAttribute\Hero;
use ComboStrap\LogUtility;
use ComboStrap\PluginUtility;
use ComboStrap\SiteConfig;
use ComboStrap\TagAttributes;

/**
 *
 */
class SubscribeTag
{


    const LOGICAL_TAG = "subscribe";
    const LIST_ID_ATTRIBUTE = "list-id";


    public static function renderEnterXhtml(TagAttributes $attributes): string
    {

        $executionContext = ExecutionContext::getActualOrCreateFromEnv();
        $snippetSystem = $executionContext->getSnippetSystem();
        $snippetSystem->attachJavascriptComboLibrary();
        $snippetSystem->attachJavascriptFromComponentId(self::LOGICAL_TAG);

        $success = TemplateForComponent::create(self::LOGICAL_TAG . "-success")->render([]);
        $data['list-value'] = $attributes->getValueAndRemove(self::LIST_ID_ATTRIBUTE);
        $data['list-name'] = "listGuid";
        $data['action'] = "https://tower.combostrap.com/combo/api/v1.0/list/registration";
        $data['success-content'] = $success;
        $data['primary-color'] = Site::getPrimaryColor()->toCssValue();
        $form = TemplateForComponent::create(self::LOGICAL_TAG . "-form")->render($data);
        return $attributes->toHtmlEnterTag("div") . $form . '</div>';
    }


}
