<?php
/**
 * DokuWiki Syntax Plugin Combostrap.
 *
 */

use ComboStrap\Bootstrap;
use ComboStrap\Call;
use ComboStrap\CallStack;
use ComboStrap\InternalMediaLink;
use ComboStrap\PluginUtility;
use ComboStrap\Site;
use ComboStrap\Tag;
use ComboStrap\TagAttributes;

if (!defined('DOKU_INC')) {
    die();
}

if (!defined('DOKU_PLUGIN')) {
    define('DOKU_PLUGIN', DOKU_INC . 'lib/plugins/');
}

require_once(__DIR__ . '/../class/PluginUtility.php');

/**
 * All DokuWiki plugins to extend the parser/rendering mechanism
 * need to inherit from this class
 *
 * The name of the class must follow a pattern (don't change it)
 * ie:
 *    syntax_plugin_PluginName_ComponentName
 */
class syntax_plugin_combo_card extends DokuWiki_Syntax_Plugin
{


    const TAG = 'card';


    /**
     * Key of the attributes that says if the card has an image illustration
     */
    const IMAGE_ILLUSTRATION_KEY = "imageIllustration";
    const CONF_ENABLE_SECTION_EDITING = "enableCardSectionEditing";


    /**
     * @var int a counter for an unknown card type
     */
    private $cardCounter = 0;
    private $sectionCounter = 0;


    /**
     * Syntax Type.
     *
     * Needs to return one of the mode types defined in $PARSER_MODES in parser.php
     * @see DokuWiki_Syntax_Plugin::getType()
     */
    function getType()
    {
        return 'container';
    }

    /**
     * @return array
     * Allow which kind of plugin inside
     *
     * ***************
     * This function has no effect because {@link SyntaxPlugin::accepts()}
     * is used
     * ***************
     */
    public function getAllowedTypes()
    {
        return array('container', 'formatting', 'substition', 'protected', 'disabled', 'paragraphs');
    }

    public function accepts($mode)
    {
        /**
         * header mode is disable to take over
         * and replace it with {@link syntax_plugin_combo_title}
         */
        if ($mode == "header") {
            return false;
        }

        /**
         * If preformatted is disable, we does not accept it
         */
        if (!$this->getConf(syntax_plugin_combo_preformatted::CONF_PREFORMATTED_ENABLE)) {
            return PluginUtility::disablePreformatted($mode);
        } else {
            return true;
        }
    }

    /**
     * How Dokuwiki will add P element
     *
     *  * 'normal' - The plugin output will be inside a paragraph (or another block element), no paragraphs will be inside
     *               No paragraph - the plugin can be used inside paragraphs (inline)
     *  * 'block'  - Open paragraphs need to be closed before plugin output -
     *               the plugin output will not start with a paragraph -
     *               block should not be inside paragraphs
     *               block will add a `eol` call at the beginning
     *  * 'stack'  - Open paragraphs will be closed before plugin output, the plugin output wraps other paragraphs
     *               Special case. Plugin wraps other paragraphs. - Stacks can contain paragraphs (ie paragraph will be added)
     *
     * @see https://www.dokuwiki.org/devel:syntax_plugins
     * @see DokuWiki_Syntax_Plugin::getPType()
     */
    function getPType()
    {
        return 'block';
    }

    /**
     * @see Doku_Parser_Mode::getSort()
     * Higher number than the teaser-columns
     * because the mode with the lowest sort number will win out
     */
    function getSort()
    {
        return 200;
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
            $this->Lexer->addEntryPattern($pattern, $mode, PluginUtility::getModeForComponent($this->getPluginComponent()));
        }


    }

    public function postConnect()
    {

        foreach (self::getTags() as $tag) {
            $this->Lexer->addExitPattern('</' . $tag . '>', PluginUtility::getModeForComponent($this->getPluginComponent()));
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
     * @see DokuWiki_Syntax_Plugin::handle()
     *
     */
    function handle($match, $state, $pos, Doku_Handler $handler)
    {

        switch ($state) {

            case DOKU_LEXER_ENTER:

                $attributes = PluginUtility::getTagAttributes($match);
                $tag = new Tag(self::TAG, $attributes, $state, $handler);

                $parentTag = $tag->getParent();
                if ($parentTag == null) {
                    $context = self::TAG;
                } else {
                    $context = $parentTag->getName();
                }


                $this->cardCounter++;
                $id = $this->cardCounter;

                /** A card without context */
                PluginUtility::addClass2Attributes("card", $attributes);


                if (!in_array("id", $attributes)) {
                    $attributes["id"] = $context . $id;
                }

                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::ATTRIBUTES => $attributes,
                    PluginUtility::CONTEXT => $context,
                    PluginUtility::POSITION => $pos
                );

            case DOKU_LEXER_UNMATCHED :

                return PluginUtility::handleAndReturnUnmatchedData(self::TAG, $match, $handler);


            case DOKU_LEXER_EXIT :

                $callStack = CallStack::createFromHandler($handler);
                $openingCall = $callStack->moveToPreviousCorrespondingOpeningCall();
                $firstOpeningChild = $callStack->moveToFirstChildTag();
                if ($firstOpeningChild !== false) {
                    if ($firstOpeningChild->getTagName() == syntax_plugin_combo_media::TAG) {
                        $imageAttributes = $firstOpeningChild->getAttributes();
                        $openingCall->addAttribute(syntax_plugin_combo_card::IMAGE_ILLUSTRATION_KEY, $imageAttributes);
                        $callStack->deleteActualCallAndNext();
                    }
                }
                $context = $openingCall->getContext();

                /**
                 * Section editing
                 * +1 to go at the line ?
                 */
                $endPosition = $pos + strlen($match) + 1;

                // Transform eol to paragraph
                $callStack->moveToEnd();
                $callStack->moveToPreviousCorrespondingOpeningCall();
                $callStack->processEolToEndStack("card-text");

                // Insert the card body enter
                $callStack->moveToEnd();
                $callStack->moveToPreviousCorrespondingOpeningCall();
                $firstChild = $callStack->moveToFirstChildTag();
                if ($firstChild !== false) {
                    if ($firstChild->getTagName() == syntax_plugin_combo_header::TAG) {
                        $callStack->moveToNextSiblingTag();
                    }
                }
                $callStack->insertBefore(
                    Call::createCall(
                        syntax_plugin_combo_cardbody::TAG,
                        DOKU_LEXER_ENTER
                    )
                );

                // Insert the card body exit
                $callStack->moveToEnd();
                $callStack->insertBefore(
                    Call::createCall(
                        syntax_plugin_combo_cardbody::TAG,
                        DOKU_LEXER_EXIT
                    )
                );

                // close
                $callStack->closeAndResetPointer();

                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::CONTEXT => $context,
                    PluginUtility::POSITION => $endPosition
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
    function render($format, Doku_Renderer $renderer, $data)
    {

        if ($format == 'xhtml') {

            /** @var Doku_Renderer_xhtml $renderer */
            $attributes = $data[PluginUtility::ATTRIBUTES];
            $state = $data[PluginUtility::STATE];
            switch ($state) {
                case DOKU_LEXER_ENTER:

                    /**
                     * Tag Attributes
                     */
                    $tagAttributes = TagAttributes::createFromCallStackArray($attributes);

                    /**
                     * Section (Edit button)
                     */
                    if (PluginUtility::getConfValue(self::CONF_ENABLE_SECTION_EDITING, 1)) {
                        $position = $data[PluginUtility::POSITION];
                        $this->sectionCounter++;
                        $name = "section" . self::TAG . $this->sectionCounter;
                        PluginUtility::startSection($renderer, $position, $name);
                    }

                    /**
                     * Bootstrap five does not include masonry
                     * directly, we need to add a column
                     * and we close it here
                     */
                    $bootstrapVersion = Bootstrap::getBootStrapMajorVersion();
                    $context = $tagAttributes->getValueAndRemove(PluginUtility::CONTEXT);
                    if ($bootstrapVersion == Bootstrap::BootStrapFiveMajorVersion && $context == syntax_plugin_combo_cardcolumns::TAG) {
                        $renderer->doc .= '<div class="col-sm-6 col-lg-4 mb-4">';
                    }

                    /**
                     * Illustrations
                     * (Must come before the next {@link TagAttributes::toHtmlEnterTag()} of the enter tag
                     * to remove the image illustration attribute
                     */
                    $imageIllustration = $tagAttributes->getValueAndRemove(self::IMAGE_ILLUSTRATION_KEY, array());

                    /**
                     * Card
                     */
                    $renderer->doc .= $tagAttributes->toHtmlEnterTag("div") . DOKU_LF;

                    /**
                     * Image
                     */
                    if (sizeof($imageIllustration) > 0) {
                        $image = InternalMediaLink::createFromCallStackArray($imageIllustration);
                        $image->getTagAttributes()->addClassName("card-img-top");
                        $renderer->doc .= $image->renderMediaTag();
                    }

                    break;

                case DOKU_LEXER_EXIT:

                    /**
                     * End Massonry
                     */
                    $context = $data[PluginUtility::CONTEXT];
                    switch ($context) {
                        case syntax_plugin_combo_cardcolumns::TAG:
                            /**
                             * Bootstrap five does not include masonry
                             * directly, we need to add a column
                             * and we close it here
                             */
                            $bootstrapVersion = Bootstrap::getBootStrapMajorVersion();
                            if ($bootstrapVersion == Bootstrap::BootStrapFiveMajorVersion) {
                                $renderer->doc .= '</div>';
                            }
                            break;
                    }
                    /**
                     * End section
                     */
                    if (PluginUtility::getConfValue(self::CONF_ENABLE_SECTION_EDITING, 1)) {
                        $renderer->finishSectionEdit($data[PluginUtility::POSITION]);
                    }

                    /**
                     * End card
                     */
                    $renderer->doc .= "</div>" . DOKU_LF;

                    break;

                case DOKU_LEXER_UNMATCHED:

                    /**
                     * Render
                     */
                    $renderer->doc .= PluginUtility::renderUnmatched($data);
                    break;


            }

            return true;
        }
        return false;
    }


    public
    static function getTags()
    {
        $elements[] = self::TAG;
        $elements[] = 'teaser';
        return $elements;
    }


}
