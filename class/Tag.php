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


require_once(__DIR__ . '/Call.php');

use Doku_Handler;
use dokuwiki\Extension\SyntaxPlugin;
use Exception;
use RuntimeException;


/**
 * Class Tag
 * @package ComboStrap
 * This is class that have tree like function on tag level
 * to match what's called a {@link Doku_Handler::$calls call}
 *
 * It's just a wrapper that adds tree functionality above the {@link CallStack}
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
     * @var Doku_Handler
     */
    private $handler;
    /**
     *
     * @var Call
     * The tag call may be null if this is the actual tag
     * in the handler process (ie not yet created in the stack)
     */
    private $tagCall;


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
     * @param Doku_Handler $handler - A reference to the dokuwiki handler
     * @param null $position - The position in the call stack of null if it's the HEAD tag (The tag that is created from the data of the {@link SyntaxPlugin::render()}
     */
    public function __construct($name, $attributes, $state, &$handler, $position = null)
    {
        $this->name = $name;


        $this->state = $state;
        /**
         * A temporary Call stack is created in the writer
         * for list, table, blockquote
         */
        $writerCalls = &$handler->getCallWriter()->calls;
        if (!empty($writerCalls)) {
            $this->calls = &$writerCalls;
        } else {
            $this->calls = &$handler->calls;
        }
        $this->handler = &$handler;
        if ($position != null) {
            $this->position = $position;
            /**
             * A shortcut access variable to the call of the tag
             */
            if (isset($this->calls[$this->position])) {
                $this->tagCall = new Call($this->calls[$this->position]);
                $this->attributes = $this->tagCall->getAttributes();
            }

        } else {
            // The tag is not yet in the stack
            $this->position = sizeof($this->calls);
            if ($attributes == null) {
                $this->attributes = array();
            } else {
                $this->attributes = $attributes;
            }
        }


    }


    /**
     * The lexer state
     *   DOKU_LEXER_ENTER = 1
     *   DOKU_LEXER_MATCHED = 2
     *   DOKU_LEXER_UNMATCHED = 3
     *   DOKU_LEXER_EXIT = 4
     *   DOKU_LEXER_SPECIAL = 5
     * @return mixed
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * From a call to a node
     * @param array $callArray
     * @param $position - the position in the call stack (ie in the array)
     * @return Tag
     */
    private function call2Tag(&$callArray, $position)
    {
        $call = new Call($callArray);

        $attributes = null;
        $data = $call->getData();
        if (isset($data[PluginUtility::ATTRIBUTES])) {
            $attributes = $data[PluginUtility::ATTRIBUTES];
        }

        $name = $call->getTagName();
        $state = $call->getState();

        /**
         * Getting attributes
         * If we don't have already the attributes
         * in the returned array of the handler,
         * (ie the full HTML was given for instance)
         * we parse the match again
         */
        if ($attributes == null
            && $call->getState() == DOKU_LEXER_ENTER
            && $name != 'preformatted' // preformatted does not have any attributes
        ) {
            $match = $call->getMatch();
            /**
             * If this is not a combo element, we got no match
             */
            if ($match != null) {
                $attributes = PluginUtility::getTagAttributes($match);
            }
        }

        return new Tag($name, $attributes, $state, $this->handler, $position);
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
        if ($this->getPreviousSibling() === null) {
            return false;
        } else {
            return true;
        }

    }

    /**
     * Return the parent node or false if root
     *
     * The node should not be in an exit node
     * (If this is the case, use the function {@link Tag::getOpeningTag()} first
     *
     * @return bool|Tag
     */
    public function getParent()
    {
        /**
         * Case when we start from the exit state element
         * We go first to the opening tag
         * because the algorithm is level based.
         */
        if ($this->state == DOKU_LEXER_EXIT) {

            return $this->getOpeningTag()->getParent();

        } else {

            /**
             * Start to the actual call minus one
             */
            $callStackPosition = $this->position - 1;
            $treeLevel = 0;

            /**
             * We are in a parent when the tree level is negative
             */
            while ($callStackPosition > 0) {

                $previousCallArray = $this->calls[$callStackPosition];
                $previousCall = new Call($previousCallArray);
                $parentCallState = $previousCall->getState();

                /**
                 * Add
                 * would become a parent on its enter state
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

                /**
                 * The breaking statement
                 */
                if ($treeLevel >= 0) {
                    $callStackPosition = $callStackPosition - 1;
                    unset($previousCallArray);
                } else {
                    break;
                }


            }
            if (isset($previousCallArray)) {
                return $this->call2Tag($previousCallArray, $callStackPosition);
            } else {
                return false;
            }
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
        if($this->tagCall!=null){
            return $this->tagCall->getTagName();
        } else {
            return $this->name;
        }
    }


    /**
     * @return string - the type attribute of the opening tag
     */
    public function getType()
    {

        if ($this->getState() == DOKU_LEXER_UNMATCHED) {
            LogUtility::msg("The unmatched tag (" . $this->name . ") does not have any attributes. Get its parent if you want the type", LogUtility::LVL_MSG_ERROR);
            return null;
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
            $call = new Call($this->calls[$i]);
            if ($call->getTagName() == "$tag") {
                return true;
            }

        }
        return false;

    }

    /**
     *
     * @return null|Tag - the sibling tag (in ascendant order) or null
     */
    public function getPreviousSibling()
    {

        $counter = $this->position - 1;
        $treeLevel = 0;
        while ($counter > 0) {

            $callArray = $this->calls[$counter];
            $call = new Call($callArray);
            $state = $call->getState();

            /**
             * Edge case
             */
            if($state==null){
                if($call->getTagName()=="acronym"){
                    // Acronym does not have an enter/exit state
                    //  this is a sibling
                    break;
                }
            }

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
                case DOKU_LEXER_UNMATCHED:
                    if (empty(trim($call->getContent()))) {
                        // An empty unmatched is not considered a sibling
                        // state = null will continue the loop
                        // we can't use a continue statement in a switch
                        $state = null;
                    }
                    break;
            }

            /*
             * Breaking conditions
             * If we get above or on the same level
             */
            if ($treeLevel <= 0
                && $state != null // eol state, strong close or empty tag
            ) {
                break;
            } else {
                $counter = $counter - 1;
                unset($callArray);
                continue;
            }

        }
        /**
         * Because we don't count tag without an state such as eol,
         * the tree level may be negative which means
         * that there is no sibling
         */
        if ($treeLevel == 0) {
            return self::call2Tag($callArray, $counter);
        }
        return null;


    }

    public function hasParent()
    {
        return $this->getParent() !== false;
    }


    /**
     * @return bool|Tag
     */
    public function getOpeningTag()
    {
        $descendantCounter = sizeof($this->calls) - 1;
        while ($descendantCounter > 0) {

            $previousCallArray = $this->calls[$descendantCounter];
            $previousCall = new Call($previousCallArray);
            $parentTagName = $previousCall->getTagName();
            $state = $previousCall->getState();
            if ($state === DOKU_LEXER_ENTER && $parentTagName === $this->getName()) {
                break;
            } else {
                $descendantCounter = $descendantCounter - 1;
                unset($previousCallArray);
            }

        }
        if (isset($previousCallArray)) {
            return $this->call2Tag($previousCallArray, $descendantCounter);
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
     *
     * @return Tag the first descendant that is not whitespace
     */
    public function getFirstMeaningFullDescendant()
    {
        $descendants = $this->getDescendants();
        $firstDescendant = $descendants[0];
        if ($firstDescendant->getState() == DOKU_LEXER_UNMATCHED && trim($firstDescendant->getContentRecursively()) == "") {
            return $descendants[1];
        } else {
            return $firstDescendant;
        }
    }

    /**
     * Descendant can only be run on enter tag
     * @return Tag[]
     */
    public function getDescendants()
    {

        if ($this->state != DOKU_LEXER_ENTER) {
            throw new RuntimeException("Descendant should be called on enter tag. Get the opening tag first if you are in a exit tag");
        }
        if (isset($this->position)) {
            $index = $this->position + 1;
        } else {
            throw new RuntimeException("It seems that we are at the end of the stack because the position is not set");
        }
        $descendants = array();
        while ($index <= sizeof($this->calls) - 1) {

            $childCallArray = $this->calls[$index];
            $childCall = new Call($childCallArray);
            $childTagName = $childCall->getTagName();
            $state = $childCall->getState();

            /**
             * We break when got to the exit tag
             */
            if ($state === DOKU_LEXER_EXIT && $childTagName === $this->getName()) {
                break;
            } else {
                /**
                 * We don't take the end of line
                 */
                if ($childCallArray[0] != "eol") {
                    /**
                     * We don't take text
                     */
                    //if ($state!=DOKU_LEXER_UNMATCHED) {
                    $descendants[] = self::call2Tag($childCallArray, $index);
                    //}
                }
                /**
                 * Close
                 */
                $index = $index + 1;
                unset($childCallArray);
            }

        }
        return $descendants;
    }

    /**
     * @param string $tagName
     * @return Tag|null
     */
    public function getDescendant($tagName)
    {
        $tags = $this->getDescendants();
        foreach ($tags as $tag) {
            if ($tag->getName() === $tagName &&
                (
                    $tag->getState() === DOKU_LEXER_ENTER
                    || $tag->getState() === DOKU_LEXER_MATCHED
                    || $tag->getState() === DOKU_LEXER_SPECIAL
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
        if ($this->tagCall != null) {

            return $this->tagCall->getMatch();
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
        if ($this->tagCall != null) {
            return $this->tagCall->getData();
        } else {
            return array();
        }
    }

    /**
     *
     * @return array|mixed - the context (generally a tag name)
     */
    public function getContext()
    {
        if ($this->tagCall != null) {
            $data = $this->tagCall->getData();
            return $data[PluginUtility::CONTEXT];
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
    public function getContentRecursively()
    {
        $content = "";
        $state = $this->getState();
        switch ($state) {
            case DOKU_LEXER_ENTER:
                $index = $this->position + 1;
                while ($index <= sizeof($this->calls) - 1) {

                    $currentCallArray = $this->calls[$index];
                    $currentCall = new Call($currentCallArray);
                    if (
                        $currentCall->getTagName() == $this->getName()
                        &&
                        $currentCall->getState() == DOKU_LEXER_EXIT
                    ) {
                        break;
                    } else {
                        $content .= $currentCall->getContent();
                        $index++;
                    }
                }
                break;
            case DOKU_LEXER_UNMATCHED:
            default:
                $content = $this->getContent();
                break;
        }


        return $content;

    }

    /**
     * Return true if the tag is the first sibling
     *
     *
     * @return boolean - true if this is the first sibling
     */
    public function isFirstMeaningFullSibling()
    {
        $sibling = $this->getPreviousSibling();
        if ($sibling == null) {
            return true;
        } else {
            /** Whitespace string */
            if ($sibling->getState() == DOKU_LEXER_UNMATCHED && trim($sibling->getContentRecursively()) == "") {
                $sibling = $sibling->getPreviousSibling();
            }
            if ($sibling == null) {
                return true;
            } else {
                return false;
            }
        }

    }

    /**
     * @param $key
     * @param $value
     * @return $this
     */
    public function addAttribute($key, $value)
    {
        $this->calls[$this->position][1][1][PluginUtility::ATTRIBUTES][$key] = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setContext($value)
    {
        $this->calls[$this->position][1][1][PluginUtility::CONTEXT] = $value;
        return $this;
    }

    /**
     * @param $key
     * @param $value
     * @return $this
     */
    public function setAttribute($key, $value)
    {
        $this->calls[$this->position][1][1][PluginUtility::ATTRIBUTES][$key] = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setType($value)
    {
        $this->calls[$this->position][1][1][PluginUtility::ATTRIBUTES]["type"] = $value;
        return $this;
    }

    public function getPosition()
    {
        return $this->position;
    }

    public function getContent()
    {
        if ($this->tagCall != null) {
            return $this->tagCall->getContent();
        } else {
            return null;
        }
    }

    public function getDocumentStartTag()
    {
        if (sizeof($this->calls) > 0) {
            return $this->call2Tag($this->calls[0], 0);
        } else {
            throw new RuntimeException("The stack is empty, there is no root tag");
        }
    }
}
