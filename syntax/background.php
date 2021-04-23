<?php


// must be run within Dokuwiki
use ComboStrap\LinkUtility;
use ComboStrap\RasterImageLink;
use ComboStrap\InternalMediaLink;
use ComboStrap\PluginUtility;
use ComboStrap\Tag;
use ComboStrap\TagAttributes;

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
     * Return a background array with background properties
     * from a media {@link InternalMediaLink::toCallStackArray()}
     * @param array $mediaCallStackArray
     * @return array
     */
    public static function toBackgroundCallStackArray(array $mediaCallStackArray)
    {
        $backgroundProperties = [];
        foreach ($mediaCallStackArray as $key => $property) {
            switch ($key) {
                case TagAttributes::LINKING_KEY:
                    /**
                     * Attributes not taken
                     */
                    break;
                case "src":
                    $backgroundProperties["background-image"] = $property;
                    break;
                case TagAttributes::CACHE_KEY:
                default:
                    /**
                     * Attributes taken
                     */
                    $backgroundProperties[$key] = $property;
                    break;

            }
        }
        return $backgroundProperties;
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
        return 'block';
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
                $defaultAttributes = array();
                $inlineAttributes = PluginUtility::getTagAttributes($match);
                $attributes = PluginUtility::mergeAttributes($inlineAttributes, $defaultAttributes);
                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::ATTRIBUTES => $attributes
                );

            case DOKU_LEXER_UNMATCHED :
                return PluginUtility::handleAndReturnUnmatchedData(self::TAG, $match, $handler);

            case DOKU_LEXER_EXIT :

                $tag = new Tag(self::TAG, array(), $state, $handler);
                $openingTag = $tag->getOpeningTag();
                $backgroundAttributes = $openingTag->getAttributes();
                $openingTag->removeAttributes(); // background has no rendering, saving space
                /**
                 * Collect the image
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
                    $image = InternalMediaLink::createFromCallStackArray($imageAttribute);
                    $backgroundImageAttribute = self::toBackgroundCallStackArray($image->toCallStackArray());
                    $backgroundAttributes = PluginUtility::mergeAttributes($backgroundAttributes, $backgroundImageAttribute);
                }

                $parent = $openingTag->getParent();
                if ($parent==null){
                    $error = "A background should have a parent";
                } else {
                    foreach($backgroundAttributes as $key => $value) {
                        $parent->setAttribute($key, $value);
                    }
                    $error= "";
                }
                /**
                 * Return state to not
                 * break the call stack state (enter, exit)
                 */
                return array(
                    PluginUtility::STATE => $state,
                    self::ERROR => $error,
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
            $state = $data[PluginUtility::STATE];
            switch ($state) {

                case DOKU_LEXER_EXIT :
                    $error = $data[self::ERROR];
                    if (!empty($error)){
                        $class = LinkUtility::TEXT_ERROR_CLASS;
                        $renderer->doc .="<p class=\"$class\"'>$error</p>";
                    }

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


}

