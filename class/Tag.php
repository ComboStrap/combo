<?php
/**
 * Copyright (c) 2020. ComboStrap, Inc. and its affiliates. All Rights Reserved.
 *
 * This source code is licensed under the GPL license found in the
 * COPYING  file in the root directory of this source tree.
 *
 * @license  GPL 3 (https://www.gnu.org/licenses/gpl-3.0.en.html)
 * @author   ComboStrap <support@combostrap.com>
 *
 */

namespace ComboStrap;


use Doku_Handler;
use dokuwiki\Extension\SyntaxPlugin;
use Exception;


/**
 * Class Tag
 * @package ComboStrap
 * This is class that have tree like function on tag level
 * to match what's called a {@link Doku_Handler::$calls call}
 */
class Tag
{
    /**
     * The {@link Doku_Handler::$calls}
     * @var
     */
    private $calls;

    /**
     * The parsed attributes for the tag
     * @var
     */
    private $attributes;
    /**
     * The name of the tag
     * @var
     */
    private $name;
    /**
     * The lexer state
     * @var
     */
    private $state;
    /**
     * The position is the call stack
     * @var int
     */
    private $position;


    /**
     * Token constructor
     * A token represent a call of {@link \Doku_Handler}
     * It can be seen as a the counter part of the HTML tag.
     *
     * It has a state of:
     *   * {@link DOKU_LEXER_ENTER} (open),
     *   * {@link DOKU_LEXER_UNMATCHED} (unmatched content),
     *   * {@link DOKU_LEXER_EXIT} (closed)
     *
     * @param $name
     * @param $attributes
     * @param $state
     * @param $calls - A reference to the dokuwiki call stack - ie {@link Doku_Handler->calls}
     * @param null $position - The position in the call stack of null if it's the HEAD tag (The tag that is created from the data of the {@link SyntaxPlugin::render()}
     */
    public function __construct($name, $attributes, $state, &$calls, $position = null)
    {
        $this->name = $name;
        if ($attributes == null) {
            $this->attributes = array();
        } else {
            $this->attributes = $attributes;
        }
        $this->state = $state;
        $this->calls = &$calls;
        $this->position = $position;

    }


    /**
     * @param $call
     * @return mixed the data returned from the {@link DokuWiki_Syntax_Plugin::handle} (ie attributes, payload, ...)
     */
    private static function getDataFromCall($call)
    {
        return $call[1][1];
    }

    /**
     * @param $call
     * @return mixed the matched content from the {@link DokuWiki_Syntax_Plugin::handle}
     */
    private static function getContentFromCall($call)
    {
        $caller = $call[0];
        switch ($caller) {
            case "plugin":
                return self::getMatchFromCall($call);
            case "internallink":
                return '[[' . $call[1][0] . '|' . $call[1][1] . ']]';
            default:
                return "Unknown tag content for caller ($caller)";
        }
    }

    private static function getMatchFromCall($call)
    {
        return $call[1][3];
    }

    /**
     * @return mixed
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * From a call to a node
     * @param $call
     * @param $position - the position in the call stack (ie in the array)
     * @return Tag
     */
    private function call2Tag(&$call, $position)
    {
        $attributes = null;
        $data = self::getDataFromCall($call);
        if (isset($data[PluginUtility::ATTRIBUTES])) {
            $attributes = $data[PluginUtility::ATTRIBUTES];
        }

        /**
         * If we don't have already the attributes
         * in the returned array of the handler,
         * (ie the full HTML was given for instance)
         * we parse the match again
         */
        if ($attributes == null && self::getStateFromCall($call) == DOKU_LEXER_ENTER) {
            $match = self::getMatchFromCall($call);
            /**
             * If this is not a combo element, we got no match
             */
            if ($match != null) {
                $attributes = PluginUtility::getTagAttributes($match);
            }
        }
        $name = self::getTagNameFromCall($call);
        $state = self::getStateFromCall($call);
        return new Tag($name, $attributes, $state, $this->calls, $position);
    }

    /**
     * The parser state
     * @param $call
     * @return mixed
     * May be null (example eol, internallink, ...)
     */
    private static function getStateFromCall($call)
    {
        return $call[1][2];
    }

    public function isChildOf($tag)
    {
        $componentNode = $this->getParent();
        return $componentNode !== false ? $componentNode->getName() === $tag : false;
    }

    /**
     * To determine if there is no content
     * between the child and the parent
     * @return bool
     */
    public function hasSiblings()
    {
        if ($this->getSibling() === null) {
            return false;
        } else {
            return true;
        }

    }

    /**
     * Return the parent node or false if root
     * @return bool|Tag
     */
    public function getParent()
    {
        if (isset($this->position)) {
            $descendantCounter = $this->position;
        } else {
            $descendantCounter = sizeof($this->calls) - 1;
        }
        $treeLevel = 0;

        /**
         * Case when we start from the exit state element
         * We put -1 in the level when we encounter the opening tag
         * to not get it
         */
        if ($this->state == DOKU_LEXER_EXIT) {
            $treeLevel = +1;
        }

        while ($descendantCounter > 0) {

            $parentCall = $this->calls[$descendantCounter];
            $parentCallState = self::getStateFromCall($parentCall);


            /**
             * The breaking statement
             */
            if (
                $parentCallState == null // Special tag such as EOL, internal link
                || $parentCallState == DOKU_LEXER_UNMATCHED
                || $parentCallState != DOKU_LEXER_ENTER
                || $treeLevel != 0) {
                $descendantCounter = $descendantCounter - 1;
                unset($parentCall);
            } else {
                break;
            }

            /**
             * After the breaking condition, otherwise a sibling would become a parent
             * on its enter state
             */
            switch ($parentCallState) {
                case DOKU_LEXER_ENTER:
                    $treeLevel = $treeLevel - 1;
                    break;
                case DOKU_LEXER_EXIT:
                    /**
                     * When the tag has a sibling with an exit tag
                     */
                    $treeLevel = $treeLevel + 1;
                    break;
            }


        }
        if (isset($parentCall)) {
            return $this->call2Tag($parentCall, $descendantCounter);
        } else {
            return false;
        }
    }

    /**
     * Return an attribute of the node or null if it does not exist
     * @param string $name
     * @return string the attribute value
     */
    public function getAttribute($name)
    {
        if (isset($this->attributes)) {
            return $this->attributes[$name];
        } else {
            return null;
        }

    }

    /**
     * Return all attributes
     * @return string[] the attributes
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * @return mixed - the name of the element (ie the opening tag)
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Return the tag name from a call array
     * (much more what's called the component name)
     * @param $call
     * @return mixed|string
     */
    static function getTagNameFromCall($call)
    {
        $state = self::getStateFromCall($call);
        $tagName = null;
        switch ($state) {
            case DOKU_LEXER_MATCHED:
                $tagName = PluginUtility::getTag(self::getContentFromCall($call));
                break;
            default:
                $component = $call[1][0];
                $componentNames = explode("_", $component);
                $tagName = $componentNames[sizeof($componentNames) - 1];
        }
        return $tagName;

    }


    /**
     * @return string - the type attribute of the opening tag
     */
    public function getType()
    {
        if ($this->getState() == DOKU_LEXER_UNMATCHED) {
            return $this->getOpeningTag()->getType();
        } else {
            return $this->getAttribute("type");
        }
    }

    /**
     * @param $tag
     * @return int
     */
    public function isDescendantOf($tag)
    {

        for ($i = sizeof($this->calls) - 1; $i >= 0; $i--) {
            if (self::getTagNameFromCall($this->calls[$i]) == "$tag") {
                return true;
            }

        }
        return false;

    }

    /**
     *
     * @return null|Tag - the sibling tag (in ascendant order) or null
     */
    public function getSibling()
    {
        if (isset($this->position)) {
            $counter = $this->position - 1;
        } else {
            $counter = sizeof($this->calls) - 1;
        }
        $treeLevel = 0;
        while ($counter > 0) {

            $call = $this->calls[$counter];
            $state = self::getStateFromCall($call);

            /**
             * Before the breaking condition
             * to take the case when the first call is an exit
             */
            switch ($state) {
                case DOKU_LEXER_ENTER:
                    $treeLevel = $treeLevel - 1;
                    break;
                case DOKU_LEXER_EXIT:
                    /**
                     * When the tag has a sibling with an exit tag
                     */
                    $treeLevel = $treeLevel + 1;
                    break;
            }

            /*
             * Breaking conditions
             * If we get above or on the same level
             */
            if ($treeLevel <= 0
                && $state != null // eol state
            ) {
                break;
            } else {
                $counter = $counter - 1;
                unset($call);
                continue;
            }

        }
        if ($treeLevel == 0) {
            return self::call2Tag($call, $counter);
        }
        return null;


    }

    public function hasParent()
    {
        return $this->getParent() !== false;
    }

    public function getOpeningTag()
    {
        $descendantCounter = sizeof($this->calls) - 1;
        while ($descendantCounter > 0) {

            $parentCall = $this->calls[$descendantCounter];
            $parentTagName = self::getTagNameFromCall($parentCall);
            $state = self::getStateFromCall($parentCall);
            if ($state === DOKU_LEXER_ENTER && $parentTagName === $this->getName()) {
                break;
            } else {
                $descendantCounter = $descendantCounter - 1;
                unset($parentCall);
            }

        }
        if (isset($parentCall)) {
            return $this->call2Tag($parentCall, $descendantCounter);
        } else {
            return false;
        }
    }

    /**
     * @return bool
     * @throws Exception - if the tag is not an exit tag
     */
    public function hasDescendants()
    {

        if (sizeof($this->getDescendants()) > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Descendant can only be run on enter tag
     * @return Tag[]
     * @throws Exception
     */
    public function getDescendants()
    {

        if ($this->state != DOKU_LEXER_ENTER) {
            throw new Exception("Descendant should be called on enter tag. Get the opening tag first if you are in a exit tag");
        }
        if (isset($this->position)) {
            $index = $this->position + 1;
        } else {
            throw new Exception("It seems that we are at the end of the stack because the position is not set");
        }
        $descendants = array();
        while ($index <= sizeof($this->calls) - 1) {

            $childCall = $this->calls[$index];
            $childTagName = self::getTagNameFromCall($childCall);
            $state = self::getStateFromCall($childCall);

            /**
             * We break when got to the exit tag
             */
            if ($state === DOKU_LEXER_EXIT && $childTagName === $this->getName()) {
                break;
            } else {
                /**
                 * We don't take the end of line
                 */
                if ($childCall[0] != "eol") {
                    $descendants[] = self::call2Tag($childCall, $index);
                }
                /**
                 * Close
                 */
                $index = $index + 1;
                unset($childCall);
            }

        }
        return $descendants;
    }

    /**
     * @param string $tagName
     * @return Tag|null
     * @throws Exception
     */
    public function getDescendant($tagName)
    {
        $tags = $this->getDescendants();
        foreach ($tags as $tag) {
            if ($tag->getName() === $tagName &&
                (
                    $tag->getState() === DOKU_LEXER_ENTER
                    || $tag->getState() === DOKU_LEXER_MATCHED
                )
            ) {
                return $tag;
            }
        }
        return null;
    }

    /**
     * Returned the matched content for this tag
     */
    public function getMatchedContent()
    {
        if ($this->position != null) {
            return self::getMatchFromCall($this->calls[$this->position]);
        } else {
            return null;
        }
    }

    /**
     *
     * @return array|mixed - the data
     */
    public function getData()
    {
        if ($this->position != null) {
            return self::getDataFromCall($this->calls[$this->position]);
        } else {
            return array();
        }
    }


    /**
     * Return the content of a tag (the string between this tag)
     * This function is generally called after a function that goes up on the stack
     * such as {@link getDescendant}
     * @return string
     */
    public function getContent()
    {
        $content = "";
        $index = $this->position + 1;
        while ($index <= sizeof($this->calls) - 1) {


            $currentCall = $this->calls[$index];
            if (
                self::getTagNameFromCall($currentCall) == $this->getName()
                &&
                self::getStateFromCall($currentCall) == DOKU_LEXER_EXIT
            ) {
                break;
            } else {
                $content .= self::getContentFromCall($currentCall);
                $index++;
            }
        }


        return $content;

    }
}
