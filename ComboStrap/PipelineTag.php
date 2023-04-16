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
            /**
             * Pipeline is the only inline tag that
             * should be protected
             * As it's deprecated we don't create a special
             * syntax plugin for it
             * We just do this hack where we capture the double quote opening
             */
            $actualName = $actual->getTagName();
            switch ($actualName) {
                case "doublequoteopening":
                case "doublequoteclosing":
                    $script .= '"';
                    continue 2;
                case "xmlinlinetag":
                    $script .= $actual->getCapturedContent();
                    continue 2;
                default:
                    LogUtility::warning("The content tag with the name ($actualName) is unknown, the captured content may be not good.",self::CANONICAL);
                    $script .= $actual->getCapturedContent();

            }

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
