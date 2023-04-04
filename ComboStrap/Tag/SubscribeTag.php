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
use ComboStrap\Snippet;
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

    const SIDE_BY_SIDE_TYPE = "side-by-side";


    public static function renderEnterXhtml(TagAttributes $attributes): string
    {

        $executionContext = ExecutionContext::getActualOrCreateFromEnv();
        $snippetSystem = $executionContext->getSnippetSystem();
        $snippetSystem->attachJavascriptComboLibrary();
        $subscribeTag = self::LOGICAL_TAG;
        $snippetSystem->attachJavascriptFromComponentId($subscribeTag)
            ->setFormat(Snippet::IIFE_FORMAT);

        $success = TemplateForComponent::create($subscribeTag . "-success")->render([]);
        $data['list-value'] = $attributes->getValueAndRemove(self::LIST_ID_ATTRIBUTE);
        $data['list-name'] = "listGuid";
        $data['email-name'] = "subscriberEmail";
        $data['email-id'] = $executionContext->getIdManager()->generateNewHtmlIdForComponent("$subscribeTag-email");
        $data['action'] = "https://tower.combostrap.com/combo/api/v1.0/list/registration";
        $data['success-content'] = $success;
        try {
            $data['primary-color'] = $executionContext->getConfig()->getPrimaryColor()->toCssValue();
        } catch (ExceptionNotFound $e) {
            // none
        }
        $form = TemplateForComponent::create($subscribeTag . "-form")->render($data);
        return $attributes->toHtmlEnterTag("div") . $form . '</div>';
    }


}
