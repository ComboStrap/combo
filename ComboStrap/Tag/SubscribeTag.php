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



    public static function handleEnter(TagAttributes $tagAttributes): array
    {

        return [];

    }

    public static function handleExit(\Doku_Handler $handler, int $pos, string $match): array
    {



        return array(

        );
    }

    public static function renderEnterXhtml(TagAttributes $attributes, array $data): string
    {

        return "";
    }


}
