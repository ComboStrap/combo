<?php

use ComboStrap\ContextManager;
use ComboStrap\ExceptionBadSyntax;
use ComboStrap\PipelineUtility;
use ComboStrap\PluginUtility;
use ComboStrap\Template;


/**
 *
 *
 *
 *
 *
 */
class syntax_plugin_combo_variable extends DokuWiki_Syntax_Plugin
{


    const TAG = "variable";


    const CANONICAL = self::TAG;
    const PREFIX_LONG = '${';
    const PREFIX_SHORT = '$';
    const ENTRY_PATTERN_SHORT = self::DOLLAR_ESCAPE . self::PREFIX_SHORT . "[A-Za-z0-9_]+";
    const ENTRY_PATTERN_LONG = self::DOLLAR_ESCAPE . self::PREFIX_LONG . "[^}\r\n]+}";
    const EXPRESSION_ATTRIBUTE = "expression";
    const DOLLAR_ESCAPE = '\\';

    public static function isVariable($ref): bool
    {
        return substr($ref, 0, 1) === syntax_plugin_combo_variable::PREFIX_SHORT;
    }

    /**
     * Template rendering will be context based
     * (first step to delete the template tag)
     * @param string $string
     * @return string
     */
    public static function replaceVariablesWithValuesFromContext(string $string): string
    {

        $metadata = ContextManager::getOrCreate()->getContextData();
        return Template::create($string)->setProperties($metadata)->render();
    }


    public function getSort(): int
    {
        return 200;
    }

    public function getType(): string
    {
        return 'substition';
    }


    /**
     *
     * How Dokuwiki will add P element
     *
     *  * 'normal' - The plugin can be used inside paragraphs (inline)
     *  * 'block'  - Open paragraphs need to be closed before plugin output - block should not be inside paragraphs
     *  * 'stack'  - Special case. Plugin wraps other paragraphs. - Stacks can contain paragraphs
     *
     * @see DokuWiki_Syntax_Plugin::getPType()
     *
     * This is the equivalent of inline or block for css
     */
    public function getPType(): string
    {
        return 'normal';
    }

    /**
     * @return array
     * Allow which kind of plugin inside
     *
     * No one of array('baseonly','container', 'formatting', 'substition', 'protected', 'disabled', 'paragraphs')
     * because we manage self the content and we call self the parser
     *
     * Return an array of one or more of the mode types {@link $PARSER_MODES} in Parser.php
     */
    function getAllowedTypes(): array
    {
        return array();
    }


    public function connectTo($mode)
    {

        $this->Lexer->addSpecialPattern(self::ENTRY_PATTERN_SHORT, $mode, PluginUtility::getModeFromTag($this->getPluginComponent()));
        $this->Lexer->addSpecialPattern(self::ENTRY_PATTERN_LONG, $mode, PluginUtility::getModeFromTag($this->getPluginComponent()));

    }


    /**
     * Handle the syntax
     *
     * At the end of the parser, the `section_open` and `section_close` calls
     * are created in {@link action_plugin_combo_headingpostprocessing}
     * and the text inside for the toc is captured
     *
     * @param string $match
     * @param int $state
     * @param int $pos
     * @param Doku_Handler $handler
     * @return array
     */
    public function handle($match, $state, $pos, Doku_Handler $handler): array
    {
        if ($state == DOKU_LEXER_SPECIAL) {
            $lengthLongPrefix = strlen(self::PREFIX_LONG);
            /**
             * Recreating a pipeline expression
             */
            if (substr($match, 0, $lengthLongPrefix) === self::PREFIX_LONG) {
                $expression = trim(substr($match, $lengthLongPrefix, -1));
                if(!in_array($expression[0],PipelineUtility::QUOTES_CHARACTERS)){
                    $expression = "\${$expression}";
                }
            } else {
                $expression = "\"$match\"";
            }
            return [
                self::EXPRESSION_ATTRIBUTE => $expression,
                PluginUtility::STATE => $state
            ];
        }
        return array();
    }

    public function render($format, $renderer, $data): bool
    {

        switch ($format) {
            case "xhtml":
            {
                /**
                 * @var Doku_Renderer_xhtml $renderer
                 */
                $state = $data[PluginUtility::STATE];
                if ($state === DOKU_LEXER_SPECIAL) {
                    $expression = $data[self::EXPRESSION_ATTRIBUTE];
                    try {
                        $execute = PipelineUtility::execute($expression);
                    } catch (ExceptionBadSyntax $e) {
                        $renderer->doc .= $e->getMessage();
                        return false;
                    }
                    $renderer->doc .= $execute;
                    return true;
                }
                break;
            }
        }
        return false;
    }


}
