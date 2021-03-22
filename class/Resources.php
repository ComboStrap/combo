<?php
/**
 * Copyright (c) 2021. ComboStrap, Inc. and its affiliates. All Rights Reserved.
 *
 * This source code is licensed under the GPL license found in the
 * COPYING  file in the root directory of this source tree.
 *
 * @license  GPL 3 (https://www.gnu.org/licenses/gpl-3.0.en.html)
 * @author   ComboStrap <support@combostrap.com>
 *
 */

namespace ComboStrap;


class Resources
{

    /**
     * Where are all resources
     */
    const RESOURCES_DIRECTORY_NAME = "resources";
    const SNIPPET_DIRECTORY_NAME = "snippet";


    public static function getImagesDirectory()
    {
        return self::getResourcesDirectory() . '/images';
    }

    public static function getSnippetResourceDirectory()
    {
        return self::getResourcesDirectory() . "/snippet";
    }

    private static function getResourcesDirectory()
    {
        return DOKU_PLUGIN . PluginUtility::PLUGIN_BASE_NAME . "/" . self::RESOURCES_DIRECTORY_NAME;
    }

}
