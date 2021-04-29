<?php


// must be run within Dokuwiki
use ComboStrap\Background;
use ComboStrap\InternalMediaLink;
use ComboStrap\LinkUtility;
use ComboStrap\PluginUtility;
use ComboStrap\Position;
use ComboStrap\Tag;

if (!defined('DOKU_INC')) die();

/**
 * Implementation of a background
 *
 *
 * Cool calm example of moving square background
 * https://codepen.io/Lewitje/pen/BNNJjo
 * Particles.js
 * https://codepen.io/akey96/pen/oNgeQYX
 * Gradient positioning above a photo
 * https://codepen.io/uzoawili/pen/GypGOy
 * Fire flies
 * https://codepen.io/mikegolus/pen/Jegvym
 *
 * z-index:100 could also be on the front
 * https://developer.mozilla.org/en-US/docs/Web/CSS/CSS_Positioning/Understanding_z_index/Stacking_without_z-index
 * https://getbootstrap.com/docs/5.0/layout/z-index/
 */
class syntax_plugin_combo_background extends DokuWiki_Syntax_Plugin
{

    const TAG = "background";
    const TAG_SHORT = "bg";
    const ERROR = "error";

    /**
     * Function used in the special and enter tag
     * @param $match
     * @return array
     */
    private static function getAttributesAndAddBackgroundPrefix($match)
    {

        $attributes = PluginUtility::getTagAttributes($match);
        foreach ($attributes as $key => $attribute) {
            $newKey = strtolower("background-$key");
            $attributes[$newKey] = $attribute;
            unset($attributes[$key]);
        }
        return $attributes;

    }


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
     * How Dokuwiki will add P element
     *
     * * 'normal' - The plugin can be used inside paragraphs
     *  * 'block'  - Open paragraphs need to be closed before plugin output - block should not be inside paragraphs
     *  * 'stack'  - Special case. Plugin wraps other paragraphs. - Stacks can contain paragraphs
     *
     * @see DokuWiki_Syntax_Plugin::getPType()
     */
    function getPType()
    {
        /**
         * normal (and not block) is important to not create p_open calls
         */
        return 'normal';
    }

    /**
     * @return array
     * Allow which kind of plugin inside
     *
     * Array('baseonly','container', 'formatting', 'substition', 'protected', 'disabled', 'paragraphs')
     *
     * Return an array of one or more of the mode types {@link $PARSER_MODES} in Parser.php
     */
    function getAllowedTypes()
    {
        return array('baseonly', 'container', 'formatting', 'substition', 'protected', 'disabled', 'paragraphs');
    }

    public function accepts($mode)
    {
        if (!$this->getConf(syntax_plugin_combo_preformatted::CONF_PREFORMATTED_ENABLE)) {
            return PluginUtility::disablePreformatted($mode);
        } else {
            return true;
        }
    }

    function getSort()
    {
        return 201;
    }


    function connectTo($mode)
    {

        foreach ($this->getTags() as $tag) {
            $pattern = PluginUtility::getContainerTagPattern($tag);
            $this->Lexer->addEntryPattern($pattern, $mode, PluginUtility::getModeForComponent($this->getPluginComponent()));
            $emptyPattern = PluginUtility::getEmptyTagPattern($tag);
            $this->Lexer->addSpecialPattern($emptyPattern, $mode, PluginUtility::getModeForComponent($this->getPluginComponent()));
        }


    }


    function postConnect()
    {

        foreach ($this->getTags() as $tag) {
            $this->Lexer->addExitPattern('</' . $tag . '>', PluginUtility::getModeForComponent($this->getPluginComponent()));
        }

    }

    function handle($match, $state, $pos, Doku_Handler $handler)
    {

        switch ($state) {

            case DOKU_LEXER_ENTER :

                /**
                 * Get and Add the background prefix
                 */
                $attributes = self::getAttributesAndAddBackgroundPrefix($match);
                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::ATTRIBUTES => $attributes
                );

            case DOKU_LEXER_SPECIAL :
                $attributes = self::getAttributesAndAddBackgroundPrefix($match);
                $tag = new Tag(self::TAG, $attributes, $state, $handler);
                return $this->setAttributesToParentAndReturnData($tag, $attributes, $state);

            case DOKU_LEXER_UNMATCHED :
                return PluginUtility::handleAndReturnUnmatchedData(self::TAG, $match, $handler);

            case DOKU_LEXER_EXIT :

                $tag = new Tag(self::TAG, array(), $state, $handler);
                $openingTag = $tag->getOpeningTag();
                $backgroundAttributes = $openingTag->getAttributes();

                /**
                 * Collect the image if any
                 */
                $callImage = $openingTag->getDescendant(syntax_plugin_combo_media::TAG);
                if ($callImage == null) {
                    /**
                     * if the media of Combo is not used, try to retrieve the media of dokuwiki
                     * @var $callImage
                     */
                    $callImage = $openingTag->getDescendant(InternalMediaLink::INTERNAL_MEDIA);
                }
                if ($callImage != null) {
                    $callImage->deleteCall();
                    $imageAttribute = $callImage->getAttributes();
                    $backgroundImageAttribute = Background::fromMediaToBackgroundImageStackArray($imageAttribute);
                    $backgroundAttributes[Background::BACKGROUND_IMAGE] = $backgroundImageAttribute;
                }

                return $this->setAttributesToParentAndReturnData($openingTag, $backgroundAttributes, $state);


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
            $state = $data[PluginUtility::STATE];
            switch ($state) {

                case DOKU_LEXER_ENTER:
                    /**
                     * background is print via the {@link Background::processBackgroundAttributes()}
                     */
                    break;
                case DOKU_LEXER_EXIT :
                case DOKU_LEXER_SPECIAL :
                    /**
                     * Print any error
                     */
                    if (isset($data[self::ERROR])) {
                        $class = LinkUtility::TEXT_ERROR_CLASS;
                        $error = $data[self::ERROR];
                        $renderer->doc .= "<p class=\"$class\">$error</p>" . DOKU_LF;
                    }
                    break;
                case DOKU_LEXER_UNMATCHED:
                    /**
                     * In case anyone put text where it should not
                     */
                    $renderer->doc .= PluginUtility::renderUnmatched($data);
                    break;

            }
            return true;
        }

        // unsupported $mode
        return false;
    }

    private function getTags()
    {
        return [self::TAG, self::TAG_SHORT];
    }

    /**
     * @param Tag $openingTag
     * @param array $backgroundAttributes
     * @param $state
     * @return array
     */
    public function setAttributesToParentAndReturnData(Tag $openingTag, array $backgroundAttributes, $state)
    {

        /**
         * The data array
         */
        $data = array(
            PluginUtility::STATE => $state
        );

        /**
         * Set the backgrounds attributes
         * to the parent
         */
        $parentTag = $openingTag->getParent();

        if ($parentTag != null) {
            if ($parentTag->getName() == Background::BACKGROUNDS) {
                /**
                 * The backgrounds node
                 * (is already relative)
                 */
                $parentTag = $parentTag->getParent();
            } else {
                /**
                 * Another parent node
                 * With a image background, the node should be relative
                 */
                if (isset($backgroundAttributes[Background::BACKGROUND_IMAGE])){
                    $parentTag->setAttributeIfNotPresent(Position::POSITION_ATTRIBUTE, "relative");
                }
            }
            $backgrounds = $parentTag->getAttribute(Background::BACKGROUNDS);
            if ($backgrounds == null) {
                $backgrounds = [$backgroundAttributes];
            } else {
                $backgrounds[] = $backgroundAttributes;
            }
            $parentTag->setAttribute(Background::BACKGROUNDS, $backgrounds);

        } else {
            $data[self::ERROR] = "A background should have a parent";
        }

        /**
         * Return state to keep the call stack structure
         */
        return $data;
    }


}

