<?php

namespace ComboStrap;


use syntax_plugin_combo_variable;

class PipelineTag
{


    public const CANONICAL = PipelineTag::TAG;
    public const TAG = "pipeline";

    public static function processExit(\Doku_Handler $handler)
    {
        $callstack = CallStack::createFromHandler($handler);
        $openingCall = $callstack->moveToPreviousCorrespondingOpeningCall();
        $script = "";
        while ($actual = $callstack->next()) {
            $script .= $actual->getCapturedContent();
        }
        $openingCall->addAttribute(PluginUtility::PAYLOAD, $script);
        $callstack->deleteAllCallsAfter($openingCall);

    }

    public static function renderEnterXhtml(TagAttributes $tagAttributes): string
    {
        $pipelineWithPossibleVariableExpression = $tagAttributes->getValue(PluginUtility::PAYLOAD);
        $pipelineExpression = syntax_plugin_combo_variable::replaceVariablesWithValuesFromContext($pipelineWithPossibleVariableExpression);
        try {
            return PipelineUtility::execute($pipelineExpression);
        } catch (ExceptionBadSyntax $e) {
            return LogUtility::wrapInRedForHtml($e->getMessage());
        }


    }
}
