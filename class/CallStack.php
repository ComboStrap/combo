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


use Doku_Handler;
use dokuwiki\Extension\SyntaxPlugin;
use dokuwiki\Parsing\Parser;

/**
 * Class CallStack
 * @package ComboStrap
 *
 * This is a class that manipulate the call stack.
 *
 * A call stack is composed of call (ie array)
 * A tag is a call that has a state {@link DOKU_LEXER_ENTER} or {@link DOKU_LEXER_SPECIAL}
 * An opening call is a call with the {@link DOKU_LEXER_ENTER}
 * An closing call is a call with the {@link DOKU_LEXER_EXIT}
 *
 * You can move on the stack with the function:
 *   * {@link CallStack::next()}
 *   * {@link CallStack::prev()}
 *   * `MoveTo`. example: {@link CallStack::moveToPreviousCorrespondingOpeningCall()}
 *
 *
 */
class CallStack
{

    const TAG_STATE = [DOKU_LEXER_SPECIAL, DOKU_LEXER_ENTER];

    private $handler;

    /**
     * The max key of the calls
     * @var int|null
     */
    private $maxIndex;
    /**
     * @var array the call stack
     */
    private $callStack;

    /**
     * A pointer to keep the information
     * if we have gone to far in the stack
     * (because you lost the fact that you are outside
     * the boundary, ie you can do a `prev` after that a `next` return false
     * @var bool
     */
    private $endWasReached = false;

    /**
     * A callstack is a pointer implementation to manipulate
     * the {@link Doku_Handler::$calls call stack of the handler}
     *
     * When you create a callstack object, the pointer
     * is located at the end.
     *
     * If you want to reset the pointer, you need
     * to call the {@link CallStack::closeAndResetPointer()} function
     *
     * @param \Doku_Handler
     */
    public function __construct(&$handler)
    {
        $this->handler = $handler;
        $this->maxIndex = ArrayUtility::array_key_last($handler->calls);
        $this->callStack = &$handler->calls;
        end($this->callStack);
    }

    /**
     * Reset the pointer
     */
    public function closeAndResetPointer()
    {
        reset($this->callStack);
    }

    /**
     * Delete from the call stack
     * @param $calls
     * @param $start
     * @param $end
     */
    public static function deleteCalls(&$calls, $start, $end)
    {
        for ($i = $start; $i <= $end; $i++) {
            unset($calls[$i]);
        }
    }

    /**
     * @param array $calls
     * @param integer $position
     * @param array $callStackToInsert
     */
    public static function insertCallStackUpWards(&$calls, $position, $callStackToInsert)
    {

        array_splice($calls, $position, 0, $callStackToInsert);

    }

    /**
     * A callstack pointer based implementation
     * that starts at the end
     * @param Doku_Handler $handler
     * @return CallStack
     */
    public static function createFromHandler(\Doku_Handler &$handler)
    {
        return new CallStack($handler);
    }


    /**
     * @param $dokuTest
     * @return Doku_Handler
     */
    public static function &createHandler($dokuTest)
    {
        $modes = p_get_parsermodes();
        $handler = new Doku_Handler();
        $parser = new Parser($handler);

        //add modes to parser
        foreach ($modes as $mode) {
            $parser->addMode($mode['mode'], $mode['obj']);
        }
        $parser->parse($dokuTest);
        return $handler;
    }

    /**
     * Process the EOL call to the end of stack
     * replacing them with paragraph call
     *
     * A sort of {@link Block::process()} but only from a tag
     * to the end of the current stack
     *
     * This function is used basically in the {@link DOKU_LEXER_EXIT}
     * state of {@link SyntaxPlugin::handle()} to create paragraph
     * with the class given as parameter
     *
     * @param $class - the class of the paragraph
     */
    public function processEolToEndStack($class = "")
    {

        /**
         * The attributes passed to the paragraph
         */
        $attributes = array("class" => $class);

        /**
         * The syntax plugin that implements the paragraph
         * ie {@link \syntax_plugin_combo_eol}
         * We will transform the eol with a call to this syntax plugin
         * to create the paragraph
         */
        $paragraphComponent = "combo_eol";

        /**
         * The running variables
         */
        $paragraphIsOpen = false; // A pointer to see if the paragraph is open
        while ($this->next()) {

            $actualCall = $this->getActualCall();
            if ($actualCall->getTagName() === "eol") {

                /**
                 * Next Call
                 */
                $nextCall = $this->next();
                $this->prev();
                if ($nextCall === false) {
                    $nextDisplay = "last";
                    $nextCall = null;
                } else {
                    $nextDisplay = $nextCall->getDisplay();
                }


                /**
                 * Processing
                 */
                if (!$paragraphIsOpen) {

                    switch ($nextDisplay) {
                        case Call::BlOCK_DISPLAY:
                            $this->deleteActualCallAndNext();
                            break;
                        case Call::INLINE_DISPLAY:
                            $paragraphIsOpen = true;
                            $actualCall->updateToPluginComponent(
                                $paragraphComponent,
                                DOKU_LEXER_ENTER,
                                $attributes
                            );
                            break;
                        case "eol":
                            /**
                             * Empty line
                             */
                            $actualCall->updateToPluginComponent(
                                $paragraphComponent,
                                DOKU_LEXER_ENTER,
                                $attributes
                            );
                            $nextCall->updateToPluginComponent(
                                $paragraphComponent,
                                DOKU_LEXER_EXIT
                            );
                            $this->next();
                            break;
                        default:
                            LogUtility::msg("The eol action for the combination enter / " . $nextDisplay . " was not implemented", LogUtility::LVL_MSG_ERROR, self::CANONICAL);
                            break;
                    }
                } else {
                    /**
                     * Paragraph is open
                     */
                    switch ($nextDisplay) {
                        case "eol":
                            /**
                             * Empty line
                             */
                            $actualCall->updateToPluginComponent(
                                $paragraphComponent,
                                DOKU_LEXER_EXIT
                            );
                            $nextCall->updateToPluginComponent(
                                $paragraphComponent,
                                DOKU_LEXER_ENTER,
                                $attributes
                            );
                            $this->next();
                            break;
                        case Call::INLINE_DISPLAY:
                            // A space
                            $actualCall->updateEolToSpace();
                            break;
                        case Call::BlOCK_DISPLAY:
                        case "last";
                            $actualCall->updateToPluginComponent(
                                $paragraphComponent,
                                DOKU_LEXER_EXIT
                            );
                            break;
                        default:
                            LogUtility::msg("The display for a open paragraph (" . $nextDisplay . ") is not implemented", LogUtility::LVL_MSG_ERROR, self::CANONICAL);
                            break;
                    }
                }

            }


        }
    }

    /**
     * Delete the call where the pointer is
     * And go to the next position
     */
    public function deleteActualCallAndNext()
    {
        $key = key($this->callStack);
        // unset is
        unset($this->callStack[$key]);
        // if there is a eol, we delete it
        // otherwise we may end up with two eol
        // and this is an empty paragraph
        if ($this->getActualCall()->getTagName() == 'eol') {
            $key = key($this->callStack);
            unset($this->callStack[$key]);
        }
    }

    /**
     * @return Call - get a reference to the actual call
     * This function returns a {@link Call call} object
     * by reference, meaning that every update will also modify the element
     * in the stack
     */
    public function getActualCall()
    {
        $actualCallKey = key($this->callStack);
        $actualCallArray = &$this->callStack[$actualCallKey];
        return new Call($actualCallArray);
    }

    /**
     * put the pointer one position further
     * false if at the end
     * @return false|Call
     */
    public function next()
    {
        $next = next($this->callStack);
        if ($next === false) {
            $this->endWasReached = true;
            return $next;
        } else {
            return $this->getActualCall();
        }
    }

    /**
     *
     * From an exit call, move the corresponding Opening call
     *
     * This is used mostly in {@link SyntaxPlugin::handle()} from a {@link DOKU_LEXER_EXIT}
     * to retrieve the {@link DOKU_LEXER_ENTER} call
     *
     * @return bool|Call
     */
    public function moveToPreviousCorrespondingOpeningCall()
    {

        $actualCall = $this->getActualCall();
        $actualState = $actualCall->getState();
        if ($actualState != DOKU_LEXER_EXIT) {
            /**
             * Check if we are at the end of the stack
             * In this case, we start at next
             */
            $next = $this->next();
            if ($next !== false) {
                LogUtility::msg("You are not at the end of stack and you are not on a opening tag, you can't ask for the opening tag." . $actualState, LogUtility::LVL_MSG_ERROR, "support");
                return false;
            }
        }
        $level = 0;
        while ($this->prev()) {

            $actualCall = $this->getActualCall();
            $state = $actualCall->getState();
            switch ($state) {
                case DOKU_LEXER_ENTER:
                    $level++;
                    break;
                case DOKU_LEXER_EXIT:
                    $level--;
                    break;
            }
            if ($level > 0) {
                break;
            }

        }
        if ($level > 0) {
            return $actualCall;
        } else {
            return false;
        }
    }

    private function prev()
    {
        if ($this->endWasReached) {
            $this->endWasReached = false;
            return end($this->callStack);
        } else {
            return prev($this->callStack);
        }
    }

    /**
     * Return the first enter or special child call (ie a tag)
     * @return Call|false
     */
    public function moveToFirstChildTag()
    {
        $found = false;
        while ($this->next()) {

            $actualCall = $this->getActualCall();
            $state = $actualCall->getState();
            switch ($state) {
                case DOKU_LEXER_ENTER:
                case DOKU_LEXER_SPECIAL:
                    $found = true;
                    break 2;
                case DOKU_LEXER_EXIT:
                    break 2;
            }

        }
        if ($found) {
            return $this->getActualCall();
        } else {
            return false;
        }


    }

    public function moveToEnd()
    {
        end($this->callStack);
    }

    /**
     * On the same level
     */
    public function moveToNextSiblingTag()
    {
        $actualCall = $this->getActualCall();
        $actualState = $actualCall->getState();
        if (!in_array($actualState, CallStack::TAG_STATE)) {
            LogUtility::msg("A next sibling can be asked only from a tag call. The state is " . $actualState, LogUtility::LVL_MSG_ERROR, "support");
            return false;
        }
        $level = 0;
        while ($this->next()) {

            $actualCall = $this->getActualCall();
            $state = $actualCall->getState();
            switch ($state) {
                case DOKU_LEXER_ENTER:
                case DOKU_LEXER_SPECIAL:
                    $level++;
                    break;
                case DOKU_LEXER_EXIT:
                    $level--;
                    break;
            }

            if ($level == 0) {
                break;
            }
        }
        if ($level == 0 && !$this->endWasReached) {
            return $this->getActualCall();
        } else {
            return false;
        }
    }

    /**
     * @param Call $call
     */
    public function insertBefore($call)
    {
        $actualKey = key($this->callStack);
        $offset = array_search($actualKey, array_keys($this->callStack), true);
        array_splice($this->callStack, $offset, 0, [$call->toCallArray()]);
        // array splice reset the pointer
        // we move it to the actual element (ie +1)
        $this->moveToKey($actualKey + 1);
    }

    /**
     * Move pointer
     * @param $targetKey
     */
    private function moveToKey($targetKey)
    {
        reset($this->callStack);
        for ($i = 0; $i < $targetKey; $i++) {
            next($this->callStack);
        }
        $actualKey = key($this->callStack);
        if ($actualKey !=$targetKey){
            LogUtility::msg("The target key ($targetKey) is not equal to the actual key ($actualKey). The moveToKey was not successful");
        }
    }

    /**
     * @param Call $call
     */
    public function insertAfter($call)
    {
        $actualKey = key($this->callStack);
        $offset = array_search($actualKey, array_keys($this->callStack), true);
        array_splice($this->callStack, $offset+1, 0, [$call->toCallArray()]);
        // array splice reset the pointer
        // we move it to the actual element
        $this->moveToKey($actualKey);
    }
}
