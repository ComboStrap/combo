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

/**
 * Class Call
 * @package ComboStrap
 *
 * A wrapper around what's called a call
 * which is an array of information such
 * the mode, the data
 *
 * The {@link CallStack} is the only syntax representation that
 * is available in DokuWiki
 */
class Call
{

    private $call;

    /**
     * Call constructor.
     * @param $call - the instruction array (ie called a call)
     */
    public function __construct(&$call)
    {
        $this->call = &$call;
    }

    /**
     * Return the tag name from a call array
     * (much more what's called the component name)
     * @return mixed|string
     */
    public function getTagName()
    {
        $mode = $this->call[0];
        if ($mode != "plugin") {

            /**
             * This is a standard dokuwiki node
             */
            $dokuWikiNodeName = $this->call[0];

            /**
             * The dokwuiki node name has also the open and close notion
             * We delete this is not in the doc and therefore not logical
             */
            $tagName = str_replace("_close", "", $dokuWikiNodeName);
            $tagName = str_replace("_open", "", $tagName);

        } else {

            /**
             * This is a plugin node
             *
             * The name of the tag is the last part
             * of the class
             * To make it unique
             */
            $state = $this->getState();
            switch ($state) {
                case DOKU_LEXER_MATCHED:
                    $tagName = PluginUtility::getTag($this->getContent());
                    break;
                default:

                    $pluginDokuData = $this->call[1];
                    $component = $pluginDokuData[0];
                    if (!is_array($component)) {
                        /**
                         * Tag name from class
                         */
                        $componentNames = explode("_", $component);
                        /**
                         * To take care of
                         * PHP Warning:  sizeof(): Parameter must be an array or an object that implements Countable
                         * in lib/plugins/combo/class/Tag.php on line 314
                         */
                        if (is_array($componentNames)) {
                            $tagName = $componentNames[sizeof($componentNames) - 1];
                        } else {
                            $tagName = $component;
                        }
                    } else {
                        // To resolve: explode() expects parameter 2 to be string, array given
                        LogUtility::msg("The call (" . print_r($this->call, true) . ") has an array and not a string as component (" . print_r($component, true) . "). Page: " . PluginUtility::getPageId(), LogUtility::LVL_MSG_ERROR);
                        $tagName = "";
                    }

            }
        }
        return $tagName;

    }


    /**
     * The parser state
     * @return mixed
     * May be null (example eol, internallink, ...)
     */
    public function getState()
    {
        $mode = $this->call[0];
        if ($mode != "plugin") {

            /**
             * There is no state because this is a standard
             * dokuwiki syntax found in {@link \Doku_Renderer_xhtml}
             * check if this is not a `...._close` or `...._open`
             * to derive the state
             */
            $mode = $this->call[0];
            $lastPositionSepName = strrpos($mode, "_");
            $closeOrOpen = substr($mode, $lastPositionSepName + 1);
            switch ($closeOrOpen) {
                case "open":
                    return DOKU_LEXER_ENTER;
                case "close":
                    return DOKU_LEXER_EXIT;
                default:
                    return null;
            }

        } else {
            // Plugin
            $returnedArray = $this->call[1];
            if (array_key_exists(2, $returnedArray)) {
                return $returnedArray[2];
            } else {
                return null;
            }
        }
    }

    /**
     * @return mixed the data returned from the {@link DokuWiki_Syntax_Plugin::handle} (ie attributes, payload, ...)
     */
    public function &getPluginData()
    {
        return $this->call[1][1];
    }

    /**
     * @return mixed the matched content from the {@link DokuWiki_Syntax_Plugin::handle}
     */
    public function getContent()
    {
        $caller = $this->call[0];
        switch ($caller) {
            case "plugin":
                return $this->getMatch();
            case "internallink":
                return '[[' . $this->call[1][0] . '|' . $this->call[1][1] . ']]';
            case "eol":
                return DOKU_LF;
            default:
                return "Unknown tag content for caller ($caller)";
        }
    }

    /**
     * @return mixed the text matched
     */
    public function getMatch()
    {
        return $this->call[1][3];
    }

    public function getAttributes()
    {

        $tagName = $this->getTagName();
        switch ($tagName) {
            case InternalMediaLink::INTERNAL_MEDIA:
                return $this->call[1];
            default:
                $data = $this->getPluginData();
                if (isset($data[PluginUtility::ATTRIBUTES])) {
                    return $data[PluginUtility::ATTRIBUTES];
                } else {
                    return null;
                }
        }
    }

    public function removeAttributes()
    {

        $data = &$this->getPluginData();
        if (isset($data[PluginUtility::ATTRIBUTES])) {
            unset($data[PluginUtility::ATTRIBUTES]);
        }

    }

    public function updateToPluginComponent($component, $state, $attributes = array())
    {
        if ($this->call[0] == "plugin") {
            $match = $this->call[1][3];
        } else {
            $this->call[0] = "plugin";
            $match = "";
        }
        $this->call[1] = array(
            0 => $component,
            1 => array(
                PluginUtility::ATTRIBUTES=>$attributes,
                PluginUtility::STATE=>$state,
            ),
            2 => $state,
            3 => $match
        );

    }



}
