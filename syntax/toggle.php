<?php


use ComboStrap\Bootstrap;
use ComboStrap\BrandButton;
use ComboStrap\CallStack;
use ComboStrap\LogUtility;
use ComboStrap\PluginUtility;
use ComboStrap\TagAttributes;
use ComboStrap\Toggle;


class syntax_plugin_combo_toggle extends DokuWiki_Syntax_Plugin
{

    const TAG = "toggle";
    const CANONICAL = self::TAG;
    const WIDGET_ATTRIBUTE = "widget";


    /**
     * Syntax Type.
     *
     * Needs to return one of the mode types defined in $PARSER_MODES in parser.php
     * @see DokuWiki_Syntax_Plugin::getType()
     */
    function getType(): string
    {
        return 'substition';
    }

    /**
     * How Dokuwiki will add P element
     *
     *  * 'normal' - The plugin can be used inside paragraphs
     *  * 'block'  - Open paragraphs need to be closed before plugin output - block should not be inside paragraphs
     *  * 'stack'  - Special case. Plugin wraps other paragraphs. - Stacks can contain paragraphs
     *
     * @see DokuWiki_Syntax_Plugin::getPType()
     */
    function getPType(): string
    {
        // button or link
        return 'normal';
    }

    /**
     * @return array
     * Allow which kind of plugin inside
     *
     * array('container', 'baseonly', 'formatting', 'substition', 'protected', 'disabled', 'paragraphs')
     *
     */
    function getAllowedTypes(): array
    {
        return array('baseonly', 'formatting', 'substition', 'protected', 'disabled');
    }

    function getSort(): int
    {
        return 201;
    }

    public
    function accepts($mode): bool
    {
        return syntax_plugin_combo_preformatted::disablePreformatted($mode)
            && Toggle::disableEntity($mode);
    }


    /**
     * Create a pattern that will called this plugin
     *
     * @param string $mode
     * @see Doku_Parser_Mode::connectTo()
     */
    function connectTo($mode)
    {

        $pattern = PluginUtility::getContainerTagPattern(self::getTag());
        $this->Lexer->addEntryPattern($pattern, $mode, 'plugin_' . PluginUtility::PLUGIN_BASE_NAME . '_' . $this->getPluginComponent());


    }

    function postConnect()
    {

        $this->Lexer->addExitPattern('</' . self::getTag() . '>', 'plugin_' . PluginUtility::PLUGIN_BASE_NAME . '_' . $this->getPluginComponent());

    }

    function handle($match, $state, $pos, Doku_Handler $handler)
    {


        switch ($state) {

            case DOKU_LEXER_ENTER :

                /**
                 * Default parameters, type definition and parsing
                 */
                $defaultParameters[self::WIDGET_ATTRIBUTE] = BrandButton::WIDGET_BUTTON_VALUE;
                $knownTypes = syntax_plugin_combo_button::TYPES;
                $tagAttributes = TagAttributes::createFromTagMatch($match, $defaultParameters, $knownTypes)
                    ->setLogicalTag(self::TAG);

                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::ATTRIBUTES => $tagAttributes->toCallStackArray()
                );

            case DOKU_LEXER_UNMATCHED :
                return PluginUtility::handleAndReturnUnmatchedData(self::TAG, $match, $handler);

            case DOKU_LEXER_EXIT :

                return array(
                    PluginUtility::STATE => $state
                );


        }
        return array();

    }

    /**
     * Render the output
     * @param string $format
     * @param Doku_Renderer $renderer
     * @param array $data - what the function handle() return
     * @return boolean - rendered correctly? (however, returned value is not used at the moment)
     * @see DokuWiki_Syntax_Plugin::render()
     *
     *
     */
    function render($format, Doku_Renderer $renderer, $data): bool
    {

        if ($format === "xhtml") {
            $state = $data[PluginUtility::STATE];
            switch ($state) {
                case DOKU_LEXER_ENTER:

                    $tagAttributes = TagAttributes::createFromCallStackArray($data[PluginUtility::ATTRIBUTES])
                        ->setLogicalTag(self::CANONICAL);

                    $targetId = $tagAttributes->getValueAndRemoveIfPresent("target-id");
                    if ($targetId === null) {
                        $renderer->doc .= LogUtility::wrapInRedForHtml("The target id is mandatory");
                        return false;
                    }
                    /**
                     * Snippet
                     */
                    PluginUtility::getSnippetManager()->attachCssInternalStyleSheet(self::CANONICAL);

                    $bootstrapNamespace = "bs-";
                    if (Bootstrap::getBootStrapMajorVersion() == Bootstrap::BootStrapFourMajorVersion) {
                        $bootstrapNamespace = "";
                    }
                    /**
                     * Types
                     */
                    $type = $tagAttributes->getType();
                    if ($type !== null) {
                        $tagAttributes->addClassName("btn-$type");
                    }
                    /**
                     * Should be in link form for bootstrap
                     */
                    if (substr($targetId, 0, 1) != "#") {
                        $targetId = "#" . $targetId;
                    }
                    $tagAttributes->addComponentAttributeValue("data-{$bootstrapNamespace}toggle", "collapse");
                    $tagAttributes->addComponentAttributeValue("data-{$bootstrapNamespace}target", $targetId);

                    /**
                     * Aria
                     */
                    $toggleState = $tagAttributes->getValueAndRemove(Toggle::TOGGLE_STATE, Toggle::TOGGLE_STATE_COLLAPSED);
                    switch ($toggleState) {
                        case Toggle::TOGGLE_STATE_EXPANDED:
                            $tagAttributes->addComponentAttributeValue("aria-expanded", true);
                            break;
                        case Toggle::TOGGLE_STATE_COLLAPSED:
                            $tagAttributes->addComponentAttributeValue("aria-expanded", false);
                            $tagAttributes->addClassName("collapsed");
                            break;
                    }


                    $targetLabel = $tagAttributes->getValueAndRemoveIfPresent("targetLabel");
                    if ($targetLabel === null) {
                        $targetLabel = "Toggle $targetId";
                    }
                    $tagAttributes->addComponentAttributeValue("aria-label", $targetLabel);
                    $tagAttributes->addClassName("btn");
                    $renderer->doc .= $tagAttributes->toHtmlEnterTag("button");
                    break;
                case DOKU_LEXER_UNMATCHED:
                    $renderer->doc .= PluginUtility::renderUnmatched($data);
                    break;
                case DOKU_LEXER_EXIT:
                    $renderer->doc .= "</button>";
                    break;

            }
            return true;
        }

        // unsupported $mode
        return false;
    }

    public
    static function getTag(): string
    {
        return self::TAG;
    }


}

