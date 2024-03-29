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
use syntax_plugin_combo_media;

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
 *   * {@link CallStack::previous()}
 *   * `MoveTo`. example: {@link CallStack::moveToPreviousCorrespondingOpeningCall()}
 *
 *
 */
class CallStack
{

    const TAG_STATE = [DOKU_LEXER_SPECIAL, DOKU_LEXER_ENTER];

    const CANONICAL = "support";

    /**
     * The type of callstack
     *   * main is the normal
     *   * writer is when there is a temporary call stack from the writer
     */
    const CALLSTACK_WRITER = "writer";
    const CALLSTACK_MAIN = "main";
    public const MESSAGE_PREFIX_CALLSTACK_NOT_CONFORM = "Your DokuWiki installation is too old or a writer plugin does not conform";
    const DOCUMENT_START = "document_start";
    const DOCUMENT_END = "document_end";

    private $handler;

    /**
     * @var array the call stack
     */
    private $callStack = [];

    /**
     * A pointer to keep the information
     * if we have gone to far in the stack
     * (because you lost the fact that you are outside
     * the boundary, ie you can do a {@link \prev}` after that a {@link \next} return false
     * @var bool
     * If true, we are at the offset: end of th array + 1
     */
    private $endWasReached = false;
    /**
     * If true, we are at the offset: start of th array - 1
     * You can use {@link CallStack::next()}
     * @var bool
     */
    private $startWasReached = false;


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

        /**
         * A temporary Call stack is created in the writer
         * for list, table, blockquote
         *
         * But third party plugin can overwrite the writer
         * to capture the call
         *
         * See the
         * https://www.dokuwiki.org/devel:parser#handler_token_methods
         * for an example with a list component
         *
         */
        $headErrorMessage = self::MESSAGE_PREFIX_CALLSTACK_NOT_CONFORM;
        if (!method_exists($handler, 'getCallWriter')) {
            $class = get_class($handler);
            LogUtility::msg("$headErrorMessage. The handler ($class) provided cannot manipulate the callstack (ie the function getCallWriter does not exist).", LogUtility::LVL_MSG_ERROR);
            return;
        }
        $callWriter = $handler->getCallWriter();

        /**
         * Check the calls property
         */
        $callWriterClass = get_class($callWriter);
        $callsPropertyFromCallWriterExists = true;
        try {
            $rp = new \ReflectionProperty($callWriterClass, "calls");
            if ($rp->isPrivate()) {
                LogUtility::msg("$headErrorMessage. The call writer ($callWriterClass) provided cannot manipulate the callstack (ie the calls of the call writer are private).", LogUtility::LVL_MSG_ERROR);
                return;
            }
        } catch (\ReflectionException $e) {
            $callsPropertyFromCallWriterExists = false;
        }

        /**
         * The calls
         */
        if ($callsPropertyFromCallWriterExists) {

            // $this->callStackType = self::CALLSTACK_WRITER;

            $writerCalls = &$callWriter->calls;
            $this->callStack = &$writerCalls;


        } else {

            // $this->callStackType = self::CALLSTACK_MAIN;

            /**
             * Check the calls property of the handler
             */
            $handlerClass = get_class($handler);
            try {
                $rp = new \ReflectionProperty($handlerClass, "calls");
                if ($rp->isPrivate()) {
                    LogUtility::msg("$headErrorMessage. The handler ($handlerClass) provided cannot manipulate the callstack (ie the calls of the handler are private).", LogUtility::LVL_MSG_ERROR);
                    return;
                }
            } catch (\ReflectionException $e) {
                LogUtility::msg("$headErrorMessage. The handler ($handlerClass) provided cannot manipulate the callstack (ie the handler does not have any calls property).", LogUtility::LVL_MSG_ERROR);
                return;
            }

            /**
             * Initiate the callstack
             */
            $this->callStack = &$handler->calls;


        }

        $this->moveToEnd();


    }

    public
    static function createFromMarkup($markup): CallStack
    {

        $handler = \ComboStrap\Parser::parseMarkupToHandler($markup);
        return self::createFromHandler($handler);

    }

    public static function createEmpty(): CallStack
    {
        $emptyHandler = new class extends \Doku_Handler {
            public $calls = [];

            public function getCallWriter(): object
            {
                return new class {
                    public $calls = array();
                };
            }
        };
        return new CallStack($emptyHandler);
    }

    public static function createFromInstructions(?array $callStackArray): CallStack
    {
        return CallStack::createEmpty()
            ->appendAtTheEndFromNativeArrayInstructions($callStackArray);

    }

    /**
     * @param CallStack $callStack
     * @param int $int
     * @return string - the content of the call stack as if it was in the file
     */
    public static function getFileContent(CallStack $callStack, int $int): string
    {
        $callStack->moveToStart();
        $capturedContent = "";
        while (strlen($capturedContent) < $int && ($actualCall = $callStack->next()) != false) {
            $actualCapturedContent = $actualCall->getCapturedContent();
            if ($actualCapturedContent !== null) {
                $capturedContent .= $actualCapturedContent;
            }
        }
        return $capturedContent;
    }


    /**
     * Reset the pointer
     */
    public
    function closeAndResetPointer()
    {
        reset($this->callStack);
    }

    /**
     * Delete from the call stack
     * @param $calls
     * @param $start
     * @param $end
     */
    public
    static function deleteCalls(&$calls, $start, $end)
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
    public
    static function insertCallStackUpWards(&$calls, $position, $callStackToInsert)
    {

        array_splice($calls, $position, 0, $callStackToInsert);

    }

    /**
     * A callstack pointer based implementation
     * that starts at the end
     * @param mixed|Doku_Handler $handler - mixed because we test if the handler passed is not the good one (It can happen with third plugin)
     * @return CallStack
     */
    public
    static function createFromHandler(&$handler): CallStack
    {
        return new CallStack($handler);
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
     * @param array $attributes - the attributes in an callstack array form passed to the paragraph
     */
    public
    function processEolToEndStack(array $attributes = [])
    {

        \syntax_plugin_combo_para::fromEolToParagraphUntilEndOfStack($this, $attributes);

    }

    /**
     * Delete the call where the pointer is
     * And go to the previous position
     *
     * This function can be used in a next loop
     *
     * @return Call the deleted call
     */
    public
    function deleteActualCallAndPrevious(): ?Call
    {

        $actualCall = $this->getActualCall();

        $offset = $this->getActualOffset();
        array_splice($this->callStack, $offset, 1, []);

        /**
         * Move to the next element (array splice reset the pointer)
         * if there is a eol as, we delete it
         * otherwise we may end up with two eol
         * and this is an empty paragraph
         */
        $this->moveToOffset($offset);
        if (!$this->isPointerAtEnd()) {
            if ($this->getActualCall()->getTagName() == 'eol') {
                array_splice($this->callStack, $offset, 1, []);
            }
        }

        /**
         * Move to the previous element
         */
        $this->moveToOffset($offset - 1);

        return $actualCall;

    }

    /**
     * @return Call|null - get a reference to the actual call
     * This function returns a {@link Call call} object
     * by reference, meaning that every update will also modify the element
     * in the stack
     */
    public
    function getActualCall(): ?Call
    {
        if ($this->endWasReached) {
            LogUtility::msg("The actual call cannot be ask because the end of the stack was reached", LogUtility::LVL_MSG_ERROR, self::CANONICAL);
            return null;
        }
        if ($this->startWasReached) {
            LogUtility::msg("The actual call cannot be ask because the start of the stack was reached", LogUtility::LVL_MSG_ERROR, self::CANONICAL);
            return null;
        }
        $actualCallKey = key($this->callStack);
        $actualCallArray = &$this->callStack[$actualCallKey];
        return new Call($actualCallArray, $actualCallKey);

    }

    /**
     * put the pointer one position further
     * false if at the end
     * @return false|Call
     */
    public
    function next()
    {
        if ($this->startWasReached) {
            $this->startWasReached = false;
            $result = reset($this->callStack);
            if ($result === false) {
                return false;
            } else {
                try {
                    return $this->getActualCall();
                } catch (ExceptionCompile $e) {
                    // should not happen because we check that we are not at the start/end of the stack
                    LogUtility::msg($e->getMessage());
                    return false;
                }
            }
        } else {
            $next = next($this->callStack);
            if ($next === false) {
                $this->endWasReached = true;
                return false;
            } else {
                try {
                    return $this->getActualCall();
                } catch (ExceptionCompile $e) {
                    // should not happen because we check that we are at the start/end of the stack
                    LogUtility::msg($e->getMessage());
                    return false;
                }
            }
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
    public
    function moveToPreviousCorrespondingOpeningCall()
    {

        /**
         * Edge case
         */
        if (empty($this->callStack)) {
            return false;
        }

        if (!$this->endWasReached) {
            $actualCall = $this->getActualCall();
            $actualState = $actualCall->getState();
            if ($actualState != DOKU_LEXER_EXIT) {
                /**
                 * Check if we are at the end of the stack
                 */
                LogUtility::msg("You are not at the end of stack and you are not on a opening tag, you can't ask for the opening tag." . $actualState, LogUtility::LVL_MSG_ERROR, "support");
                return false;
            }
        }
        $level = 0;
        while ($actualCall = $this->previous()) {

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


    /**
     * @return Call|false the previous call or false if there is no more previous call
     */
    public
    function previous()
    {
        if ($this->endWasReached) {
            $this->endWasReached = false;
            $return = end($this->callStack);
            if ($return == false) {
                // empty array (first call on the stack)
                return false;
            } else {
                return $this->getActualCall();
            }
        } else {
            $prev = prev($this->callStack);
            if ($prev === false) {
                $this->startWasReached = true;
                return $prev;
            } else {
                return $this->getActualCall();
            }
        }

    }

    /**
     * Return the first enter or special child call (ie a tag)
     * @return Call|false
     */
    public
    function moveToFirstChildTag()
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

    /**
     * The end is the one after the last element
     */
    public
    function moveToEnd()
    {
        if ($this->startWasReached) {
            $this->startWasReached = false;
        }
        end($this->callStack);
        return $this->next();
    }

    /**
     * On the same level
     */
    public
    function moveToNextSiblingTag()
    {

        /**
         * Edge case
         */
        if (empty($this->callStack)) {
            return false;
        }

        if($this->endWasReached){
            return false;
        }

        $actualCall = $this->getActualCall();
        $enterState = $actualCall->getState();
        if (!in_array($enterState, CallStack::TAG_STATE)) {
            LogUtility::msg("A next sibling can be asked only from a tag call. The state is $enterState", LogUtility::LVL_MSG_ERROR, "support");
            return false;
        }
        $level = 0;
        while ($this->next()) {

            $actualCall = $this->getActualCall();
            $state = $actualCall->getState();
            switch ($state) {
                case DOKU_LEXER_ENTER:
                    $level++;
                    break;
                case DOKU_LEXER_SPECIAL:
                    if ($enterState === DOKU_LEXER_SPECIAL) {
                        break;
                    } else {
                        // ENTER TAG
                        continue 2;
                    }
                case DOKU_LEXER_EXIT:
                    $level--;
                    break;
            }

            if ($level == 0 && in_array($state, self::TAG_STATE)) {
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
     * @return Call the inserted call
     */
    public
    function insertBefore(Call $call): Call
    {
        if ($this->endWasReached) {

            $this->callStack[] = $call->toCallArray();

        } else {

            $offset = $this->getActualOffset();
            array_splice($this->callStack, $offset, 0, [$call->toCallArray()]);
            // array splice reset the pointer
            // we move it to the actual element (ie the key is offset +1)
            try {
                $targetOffset = $offset + 1;
                $this->moveToOffset($targetOffset);
            } catch (ExceptionBadArgument $e) {
                /**
                 * We don't throw because we should be able to add before at any index
                 */
                if (PluginUtility::isDevOrTest()) {
                    LogUtility::error("Unable to move the callback pointer to the offset ($targetOffset)", self::CANONICAL);
                }
            }

        }
        return $call;
    }

    /**
     * Move pointer by offset
     * @param $offset
     * @throws ExceptionBadArgument
     */
    private
    function moveToOffset($offset)
    {
        if ($offset < 0) {
            if ($offset === -1) {
                $this->moveToStart();
                return;
            }
            throw new ExceptionBadArgument("The offset value of ($offset) is off limit");
        }
        $this->resetPointer();
        for ($i = 0; $i < $offset; $i++) {
            $result = $this->next();
            if ($result === false) {
                break;
            }
        }
    }

    /**
     * Move pointer by key
     * @param $targetKey
     */
    public function moveToKey($targetKey)
    {
        $this->resetPointer();
        for ($i = 0; $i < $targetKey; $i++) {
            next($this->callStack);
        }
        $actualKey = key($this->callStack);
        if ($actualKey != $targetKey) {
            LogUtility::msg("The target key ($targetKey) is not equal to the actual key ($actualKey). The moveToKey was not successful");
        }
    }

    /**
     * Insert After. The pointer stays at the current location.
     * If you need to process the call that you just
     * inserted, you may want to call {@link CallStack::next()}
     * @param Call $call
     * @return void - next to go the inserted element
     */
    public
    function insertAfter(Call $call): void
    {
        $actualKey = key($this->callStack);
        if ($actualKey !== null) {
            $offset = array_search($actualKey, array_keys($this->callStack), true);
            array_splice($this->callStack, $offset + 1, 0, [$call->toCallArray()]);
            // array splice reset the pointer
            // we move it to the actual element
            $this->moveToKey($actualKey);
            return;
        }

        if ($this->endWasReached === true) {
            $this->callStack[] = $call->toCallArray();
            return;
        }
        if ($this->startWasReached === true) {
            // since 4+
            array_unshift($this->callStack, $call->toCallArray());
            $this->previous();
            return;
        }
        LogUtility::msg("Callstack: Actual key is null, we can't insert after null");


    }

    public
    function getActualKey()
    {
        return key($this->callStack);
    }

    /**
     * Insert an EOL call if the next call is not an EOL
     * This is to enforce an new paragraph
     */
    public
    function insertEolIfNextCallIsNotEolOrBlock()
    {
        if (!$this->isPointerAtEnd()) {
            $nextCall = $this->next();
            if ($nextCall != false) {
                if ($nextCall->getTagName() != "eol" && $nextCall->getDisplay() != "block") {
                    $this->insertBefore(
                        Call::createNativeCall("eol")
                    );
                    // move on the eol
                    $this->previous();
                }
                // move back
                $this->previous();
            }
        }
    }

    private
    function isPointerAtEnd()
    {
        return $this->endWasReached;
    }

    public
    function &getHandler()
    {
        return $this->handler;
    }

    /**
     * Return The offset (not the key):
     *   * starting at 0 for the first element
     *   * 1 for the second ...
     *
     * @return false|int|string
     */
    private
    function getActualOffset()
    {
        $actualKey = key($this->callStack);
        return array_search($actualKey, array_keys($this->callStack), true);
    }

    private
    function resetPointer()
    {
        reset($this->callStack);
        $this->endWasReached = false;
    }

    public
    function moveToStart()
    {
        $this->resetPointer();
        return $this->previous();
    }

    /**
     * @return Call|false the parent call or false if there is no parent
     * If you are on an {@link DOKU_LEXER_EXIT} state, you should
     * call first the {@link CallStack::moveToPreviousCorrespondingOpeningCall()}
     */
    public function moveToParent()
    {

        /**
         * Case when we start from the exit state element
         * We go first to the opening tag
         * because the algorithm is level based.
         *
         * When the end is reached, there is no call
         * (this not the {@link end php end} but one further
         */
        if (!$this->endWasReached && !$this->startWasReached && $this->getActualCall()->getState() == DOKU_LEXER_EXIT) {

            $this->moveToPreviousCorrespondingOpeningCall();

        }


        /**
         * We are in a parent when the tree level is negative
         */
        $treeLevel = 0;
        while ($actualCall = $this->previous()) {

            /**
             * Add
             * would become a parent on its enter state
             */
            $actualCallState = $actualCall->getState();
            switch ($actualCallState) {
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
            if ($treeLevel < 0) {
                break;
            }

        }
        return $actualCall;


    }

    /**
     * Delete the anchor link to the image (ie the lightbox)
     *
     * This is used in navigation and for instance
     * in heading
     */
    public function processNoLinkOnImageToEndStack()
    {
        while ($this->next()) {
            $actualCall = $this->getActualCall();
            if ($actualCall->getTagName() == syntax_plugin_combo_media::TAG) {
                $actualCall->addAttribute(MediaMarkup::LINKING_KEY, MediaMarkup::LINKING_NOLINK_VALUE);
            }
        }
    }

    /**
     * Append instructions to the callstack (ie at the end)
     * @param array $instructions
     * @return CallStack
     */
    public function appendAtTheEndFromNativeArrayInstructions(array $instructions): CallStack
    {
        array_splice($this->callStack, count($this->callStack), 0, $instructions);
        return $this;
    }

    /**
     * @param array $instructions
     * @return $this
     * The key is the actual
     */
    public function insertAfterFromNativeArrayInstructions(array $instructions): CallStack
    {
        $offset = null;
        $actualKey = $this->getActualKey();
        if ($actualKey !== null) {
            $offset = $actualKey + 1;
        }
        array_splice($this->callStack, $offset, 0, $instructions);
        if ($actualKey !== null) {
            $this->moveToKey($actualKey);
        }
        return $this;
    }

    /**
     * @param Call $call
     */
    public function appendCallAtTheEnd(Call $call)
    {
        $this->callStack[] = $call->toCallArray();
    }

    public function moveToPreviousSiblingTag()
    {
        /**
         * Edge case
         */
        if (empty($this->callStack)) {
            return false;
        }

        $enterState = null;
        if (!$this->endWasReached) {
            $actualCall = $this->getActualCall();
            $enterState = $actualCall->getState();
            if (!in_array($enterState, CallStack::TAG_STATE)) {
                LogUtility::msg("A previous sibling can be asked only from a tag call. The state is $enterState", LogUtility::LVL_MSG_ERROR, "support");
                return false;
            }
        }
        $level = 0;
        while ($actualCall = $this->previous()) {

            $state = $actualCall->getState();
            switch ($state) {
                case DOKU_LEXER_ENTER:
                    $level++;
                    break;
                case DOKU_LEXER_SPECIAL:
                    if ($enterState === DOKU_LEXER_SPECIAL) {
                        break;
                    } else {
                        continue 2;
                    }
                case DOKU_LEXER_EXIT:
                    $level--;
                    break;
                default:
                    // cdata
                    continue 2;
            }

            if ($level == 0 && in_array($state, self::TAG_STATE)) {
                break;
            }
        }
        if ($level == 0 && !$this->startWasReached) {
            return $this->getActualCall();
        } else {
            return false;
        }
    }

    /**
     * Delete all calls after the passed call
     *
     * It's used in syntax generator that:
     *   * capture the children callstack at the end,
     *   * delete it
     *   * and use it to generate more calls.
     *
     * @param Call $call
     */
    public function deleteAllCallsAfter(Call $call)
    {
        $key = $call->getKey();
        $offset = array_search($key, array_keys($this->callStack), true);
        if ($offset !== false) {
            /**
             * We delete from the next
             * {@link array_splice()} delete also the given offset
             */
            array_splice($this->callStack, $offset + 1);
        } else {
            LogUtility::msg("The call ($call) could not be found in the callStack. We couldn't therefore delete the calls after");
        }

    }

    /**
     * @param Call[] $calls
     */
    public function appendInstructionsFromCallObjects($calls)
    {
        foreach ($calls as $call) {
            $this->appendCallAtTheEnd($call);
        }

    }

    /**
     *
     * @return int|mixed - the last position on the callstack
     * If you are at the end of the callstack after a full parsing,
     * this should be the length of the string of the page
     */
    public function getLastCharacterPosition()
    {
        $offset = $this->getActualOffset();

        $lastEndPosition = 0;
        $this->moveToEnd();
        while ($actualCall = $this->previous()) {
            // p_open and p_close have always a position value of 0
            $lastEndPosition = $actualCall->getLastMatchedCharacterPosition();
            if ($lastEndPosition !== 0) {
                break;
            }
        }
        if ($offset == null) {
            $this->moveToEnd();
        } else {
            $this->moveToOffset($offset);
        }
        return $lastEndPosition;

    }

    public function getStack(): array
    {
        return $this->callStack;
    }

    public function moveToFirstEnterTag()
    {

        while ($actualCall = $this->next()) {

            if ($actualCall->getState() === DOKU_LEXER_ENTER) {
                return $this->getActualCall();
            }
        }
        return false;

    }

    /**
     * Move the pointer to the corresponding exit call
     * and return it or false if not found
     * @return Call|false
     */
    public function moveToNextCorrespondingExitTag()
    {
        /**
         * Edge case
         */
        if (empty($this->callStack)) {
            return false;
        }

        /**
         * Check if we are on an enter tag
         */
        $actualCall = $this->getActualCall();
        if ($actualCall === null) {
            LogUtility::msg("You are not on the stack (start or end), you can't ask for the corresponding exit call", LogUtility::LVL_MSG_ERROR, self::CANONICAL);
            return false;
        }
        $actualState = $actualCall->getState();
        if ($actualState != DOKU_LEXER_ENTER) {
            LogUtility::msg("You are not on an enter tag ($actualState). You can't ask for the corresponding exit call .", LogUtility::LVL_MSG_ERROR, self::CANONICAL);
            return false;
        }

        $level = 0;
        while ($actualCall = $this->next()) {

            $state = $actualCall->getState();
            switch ($state) {
                case DOKU_LEXER_ENTER:
                    $level++;
                    break;
                case DOKU_LEXER_EXIT:
                    $level--;
                    break;
            }
            if ($level < 0) {
                break;
            }

        }
        if ($level < 0) {
            return $actualCall;
        } else {
            return false;
        }

    }

    public function moveToCall(Call $call): ?Call
    {
        $targetKey = $call->getKey();
        $actualKey = $this->getActualKey();
        if ($actualKey === null) {
            if ($this->endWasReached) {
                $actualKey = sizeof($this->callStack);
            }
            if ($this->startWasReached) {
                $actualKey = -1;
            }
        }
        $diff = $targetKey - $actualKey;
        for ($i = 0; $i < abs($diff); $i++) {
            if ($diff > 0) {
                $this->next();
            } else {
                $this->previous();
            }
        }
        if ($this->endWasReached) {
            return null;
        }
        if ($this->startWasReached) {
            return null;
        }
        return $this->getActualCall();
    }


    /**
     * Delete all call before (Don't delete the passed call)
     * @param Call $call
     * @return void
     */
    public function deleteAllCallsBefore(Call $call)
    {
        $key = $call->getKey();
        $offset = array_search($key, array_keys($this->callStack), true);
        if ($offset !== false) {
            /**
             * We delete from the next
             * {@link array_splice()} delete also the given offset
             */
            array_splice($this->callStack, 0, $offset);
        } else {
            LogUtility::msg("The call ($call) could not be found in the callStack. We couldn't therefore delete the before");
        }

    }

    public function isAtEnd(): bool
    {
        return $this->endWasReached;
    }

    public function empty()
    {
        $this->callStack = [];
    }

    /**
     * @return Call[]
     */
    public function getChildren(): array
    {
        $children = [];
        $firstChildTag = $this->moveToFirstChildTag();
        if ($firstChildTag == false) {
            return $children;
        }
        $children[] = $firstChildTag;
        while ($actualCall = $this->moveToNextSiblingTag()) {
            $children[] = $actualCall;
        }
        return $children;
    }

    public function appendCallsAtTheEnd(array $calls)
    {
        foreach($calls as $call){
            $this->appendCallAtTheEnd($call);
        }
    }


}
