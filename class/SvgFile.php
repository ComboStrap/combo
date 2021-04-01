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


class SvgFile extends XmlFile
{


    const CANONICAL = "svg";




    public function getSvg($attributes = array())
    {


        // Set the name attribute for test selection
        if (isset($attributes["name"])) {
            $this->setAttribute('data-name', $attributes["name"]);
            unset($attributes["name"]);
        }

        // Width
        $widthName = "width";
        $widthValue = "24px";
        if (array_key_exists($widthName, $attributes)) {
            $widthValue = $attributes[$widthName];
            unset($attributes[$widthName]);
        }
        $this->setAttribute($widthName, $widthValue);

        // Height
        $heightName = "height";
        $heightValue = "24px";
        if (array_key_exists($heightName, $attributes)) {
            $heightValue = $attributes[$heightName];
            unset($attributes[$heightName]);
        }
        $this->setAttribute($heightName, $heightValue);




        // Add fill="currentColor" to all path descendant element
        if ($namespace != "") {
            $pathsXml = $mediaSvgXml->xpath("//$namespace:path");
            foreach ($pathsXml as $pathXml):
                XmlUtility::setAttribute("fill", "currentColor", $pathXml);
            endforeach;
        }

        // for line item such as feather (https://github.com/feathericons/feather#2-use)
        // fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"

        // FYI: For whatever reason if you add a border the line icon are neater
        // PluginUtility::addStyleProperty("border","1px solid transparent",$attributes);

        // Process the style
        PluginUtility::processStyle($attributes);

        foreach ($attributes as $name => $value) {
            $mediaSvgXml->addAttribute($name, $value);
        }
        return XmlUtility::asHtml($mediaSvgXml);

    }




}
