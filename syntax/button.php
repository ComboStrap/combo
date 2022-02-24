<?php
/**
 * DokuWiki Syntax Plugin Combostrap.
 *
 */

use ComboStrap\CallStack;
use ComboStrap\ColorRgb;
use ComboStrap\MarkupRef;
use ComboStrap\PluginUtility;
use ComboStrap\Shadow;
use ComboStrap\Site;
use ComboStrap\Skin;
use ComboStrap\TagAttributes;
use ComboStrap\TextColor;

if (!defined('DOKU_INC')) {
    die();
}

if (!defined('DOKU_PLUGIN')) {
    define('DOKU_PLUGIN', DOKU_INC . 'lib/plugins/');
}


require_once(DOKU_PLUGIN . 'syntax.php');
require_once(DOKU_INC . 'inc/parserutils.php');
require_once(__DIR__ . '/../ComboStrap/PluginUtility.php');

/**
 * All DokuWiki plugins to extend the parser/rendering mechanism
 * need to inherit from this class
 *
 * The name of the class must follow a pattern (don't change it)
 * ie:
 *    syntax_plugin_PluginName_ComponentName
 *
 * !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
 * !!!!!!!!!!! The component name must be the name of the php file !!!
 * !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
 *
 * ===== For the Geek =====
  * This is not the [[https://www.w3.org/TR/wai-aria-practices/#button|Button as describe by the Web Specification]]
 * but a styling over a [[https://www.w3.org/TR/wai-aria-practices/#link|link]]
 *
 * ===== Documentation / Reference =====
 * https://material.io/components/buttons
 * https://getbootstrap.com/docs/4.5/components/buttons/
 */
class syntax_plugin_combo_button extends DokuWiki_Syntax_Plugin
{


    const TAG = "button";


    /**
     * Syntax Type.
     *
     * Needs to return one of the mode types defined in $PARSER_MODES in parser.php
     * @see DokuWiki_Syntax_Plugin::getType()
     */
    function getType(): string
    {
        return 'formatting';
    }

    /**
     * @return array
     * Allow which kind of plugin inside
     *
     * No one of array('container', 'formatting', 'substition', 'protected', 'disabled', 'paragraphs')
     * because we manage self the content and we call self the parser
     */
    public function getAllowedTypes(): array
    {
        return array('container', 'formatting', 'substition', 'protected', 'disabled', 'paragraphs');
    }

    public function accepts($mode): bool
    {

        return syntax_plugin_combo_preformatted::disablePreformatted($mode);

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
    function getPType()
    {
        return 'normal';
    }

    /**
     * @see Doku_Parser_Mode::getSort()
     * the mode with the lowest sort number will win out
     * the lowest in the tree must have the lowest sort number
     * No idea why it must be low but inside a teaser, it will work
     * https://www.dokuwiki.org/devel:parser#order_of_adding_modes_important
     */
    function getSort(): int
    {
        return 10;
    }

    /**
     * Create a pattern that will called this plugin
     *
     * @param string $mode
     * @see Doku_Parser_Mode::connectTo()
     */
    function connectTo($mode)
    {

        foreach (self::getTags() as $tag) {

            $pattern = PluginUtility::getContainerTagPattern($tag);
            $this->Lexer->addEntryPattern($pattern, $mode, 'plugin_' . PluginUtility::PLUGIN_BASE_NAME . '_' . $this->getPluginComponent());

        }

    }

    public function postConnect()
    {

        foreach (self::getTags() as $tag) {
            $this->Lexer->addExitPattern('</' . $tag . '>', 'plugin_' . PluginUtility::PLUGIN_BASE_NAME . '_' . $this->getPluginComponent());
        }


    }

    /**
     *
     * The handle function goal is to parse the matched syntax through the pattern function
     * and to return the result for use in the renderer
     * This result is always cached until the page is modified.
     * @param string $match
     * @param int $state
     * @param int $pos
     * @param Doku_Handler $handler
     * @return array|bool
     * @throws Exception
     * @see DokuWiki_Syntax_Plugin::handle()
     *
     */
    function handle($match, $state, $pos, Doku_Handler $handler)
    {

        switch ($state) {

            case DOKU_LEXER_ENTER:

                $types = [ColorRgb::PRIMARY_VALUE, ColorRgb::SECONDARY_VALUE, "success", "danger", "warning", "info", "light", "dark"];
                $defaultAttributes = array(
                    Skin::SKIN_ATTRIBUTE => Skin::FILLED_VALUE,
                    TagAttributes::TYPE_KEY => ColorRgb::PRIMARY_VALUE
                );
                $attributes = TagAttributes::createFromTagMatch($match, $defaultAttributes, $types);

                /**
                 * Note: Branding color (primary and secondary)
                 * are set with the {@link Skin}
                 */

                /**
                 * The parent
                 * to apply automatically styling in a bar
                 */
                $callStack = CallStack::createFromHandler($handler);
                $isInMenuBar = false;
                while ($parent = $callStack->moveToParent()) {
                    if ($parent->getTagName() === syntax_plugin_combo_menubar::TAG) {
                        $isInMenuBar = true;
                        break;
                    }
                }
                if ($isInMenuBar) {
                    if (!$attributes->hasAttribute("class") && !$attributes->hasAttribute("spacing")) {
                        $attributes->addComponentAttributeValue("spacing", "mr-2 mb-2 mt-2 mb-lg-0 mt-lg-0");
                    }
                }

                /**
                 * The context give set if this is a button
                 * or a link button
                 * The context is checked in the `exit` state
                 * Default context: This is not a link button
                 */
                $context = self::TAG;


                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::ATTRIBUTES => $attributes->toCallStackArray(),
                    PluginUtility::CONTEXT => $context
                );

            case DOKU_LEXER_UNMATCHED :

                return PluginUtility::handleAndReturnUnmatchedData(self::TAG, $match, $handler);


            case DOKU_LEXER_EXIT :
                $callStack = CallStack::createFromHandler($handler);
                $openingTag = $callStack->moveToPreviousCorrespondingOpeningCall();
                /**
                 * Button or link button
                 */
                $context = self::TAG;
                $descendant = $callStack->moveToFirstChildTag();
                if ($descendant !== false) {
                    if ($descendant->getTagName() === syntax_plugin_combo_link::TAG) {
                        $context = syntax_plugin_combo_link::TAG;
                    }
                }
                $openingTag->setContext($context);

                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::CONTEXT => $context
                );


        }

        return array();

    }

    /**
     * Render the output
     * @param string $format
     * @param Doku_Renderer $renderer
     * @param array $data - what the function handle() return'ed
     * @return boolean - rendered correctly? (however, returned value is not used at the moment)
     * @see DokuWiki_Syntax_Plugin::render()
     *
     *
     */
    function render($format, Doku_Renderer $renderer, $data): bool
    {

        switch ($format) {

            case 'xhtml':
            {

                /** @var Doku_Renderer_xhtml $renderer */

                /**
                 * CSS if dokuwiki class name for link
                 */
                if ($this->getConf(MarkupRef::CONF_USE_DOKUWIKI_CLASS_NAME, false)) {
                    PluginUtility::getSnippetManager()->attachCssInternalStyleSheetForSlot(self::TAG);
                }

                /**
                 * HTML
                 */
                $state = $data[PluginUtility::STATE];
                $callStackAttributes = $data[PluginUtility::ATTRIBUTES];
                $context = $data[PluginUtility::CONTEXT];
                switch ($state) {

                    case DOKU_LEXER_ENTER :

                        /**
                         * If this not a link button
                         * The context is set on the handle exit
                         */
                        if ($context == self::TAG) {
                            $tagAttributes = TagAttributes::createFromCallStackArray($callStackAttributes, self::TAG)
                                ->setDefaultStyleClassShouldBeAdded(false);
                            self::processButtonAttributesToHtmlAttributes($tagAttributes);
                            $tagAttributes->addOutputAttributeValue("type", "button");
                            $renderer->doc .= $tagAttributes->toHtmlEnterTag('button');
                        }
                        break;

                    case DOKU_LEXER_UNMATCHED:


                        /**
                         * If this is a button and not a link button
                         */
                        $renderer->doc .= PluginUtility::renderUnmatched($data);
                        break;

                    case DOKU_LEXER_EXIT :


                        /**
                         * If this is a button and not a link button
                         */
                        if ($context === self::TAG) {
                            $renderer->doc .= '</button>';
                        }

                        break;
                }
                return true;
            }

        }
        return false;
    }


    public static function getTags(): array
    {
        $elements[] = self::TAG;
        $elements[] = 'btn';
        return $elements;
    }

    /**
     * @param TagAttributes $tagAttributes
     */
    public static function processButtonAttributesToHtmlAttributes(TagAttributes &$tagAttributes)
    {
        # A button
        $btn = "btn";
        $tagAttributes->addClassName($btn);

        $type = $tagAttributes->getValue(TagAttributes::TYPE_KEY, "primary");
        $skin = $tagAttributes->getValue(Skin::SKIN_ATTRIBUTE, Skin::FILLED_VALUE);
        switch ($skin) {
            case "contained":
            {
                $tagAttributes->addClassName("$btn-$type");
                $tagAttributes->addComponentAttributeValue(Shadow::CANONICAL, true);
                break;
            }
            case "filled":
            {
                $tagAttributes->addClassName("$btn-$type");
                break;
            }
            case "outline":
            {
                $tagAttributes->addClassName("$btn-outline-$type");
                break;
            }
            case "text":
            {
                $tagAttributes->addClassName("$btn-link");
                $tagAttributes->addComponentAttributeValue(TextColor::TEXT_COLOR_ATTRIBUTE, $type);
                break;
            }
        }


        $sizeAttribute = "size";
        if ($tagAttributes->hasComponentAttribute($sizeAttribute)) {
            $size = $tagAttributes->getValueAndRemove($sizeAttribute);
            switch ($size) {
                case "lg":
                case "large":
                    $tagAttributes->addClassName("btn-lg");
                    break;
                case "sm":
                case "small":
                    $tagAttributes->addClassName("btn-sm");
                    break;
            }
        }
    }


}
