<?php
/**
 * Front Matter implementation to add metadata
 *
 *
 * that enhance the metadata dokuwiki system
 * https://www.dokuwiki.org/metadata
 * that use the Dublin Core Standard
 * http://dublincore.org/
 * by adding the front matter markup specification
 * https://gerardnico.com/markup/front-matter
 *
 * Inspiration
 * https://github.com/dokufreaks/plugin-meta/blob/master/syntax.php
 * https://www.dokuwiki.org/plugin:semantic
 *
 * See also structured plugin
 * https://www.dokuwiki.org/plugin:data
 * https://www.dokuwiki.org/plugin:struct
 *
 */

use ComboStrap\Analytics;
use ComboStrap\ArrayUtility;
use ComboStrap\LogUtility;
use ComboStrap\MediaLink;
use ComboStrap\Page;
use ComboStrap\PluginUtility;
use ComboStrap\Publication;

require_once(__DIR__ . '/../ComboStrap/PluginUtility.php');


/**
 * All DokuWiki plugins to extend the parser/rendering mechanism
 * need to inherit from this class
 *
 * For a list of meta, see also https://ghost.org/docs/publishing/#api-data
 */
class syntax_plugin_combo_frontmatter extends DokuWiki_Syntax_Plugin
{
    const PARSING_STATE_EMPTY = "empty";
    const PARSING_STATE_ERROR = "error";
    const PARSING_STATE_SUCCESSFUL = "successful";
    const STATUS = "status";
    const CANONICAL = "frontmatter";
    const CONF_ENABLE_SECTION_EDITING = 'enableFrontMatterSectionEditing';

    /**
     * Used in the move plugin
     * !!! The two last word of the plugin class !!!
     */
    const COMPONENT = 'combo_' . self::CANONICAL;
    const START_TAG = '---json';
    const END_TAG = '---';
    const METADATA_IMAGE_CANONICAL = "metadata:image";
    const PATTERN = self::START_TAG . '.*?' . self::END_TAG;

    /**
     * @param $match
     * @return array|mixed - null if decodage problem, empty array if no json or an associative array
     */
    public static function frontMatterMatchToAssociativeArray($match)
    {
        // strip
        //   from start `---json` + eol = 8
        //   from end   `---` + eol = 4
        $jsonString = substr($match, 7, -3);

        // Empty front matter
        if (trim($jsonString) == "") {
            return [];
        }

        // Otherwise you get an object ie $arrayFormat-> syntax
        $arrayFormat = true;
        return json_decode($jsonString, $arrayFormat);
    }

    /**
     * Syntax Type.
     *
     * Needs to return one of the mode types defined in $PARSER_MODES in parser.php
     * @see https://www.dokuwiki.org/devel:syntax_plugins#syntax_types
     *
     * Return an array of one or more of the mode types {@link $PARSER_MODES} in Parser.php
     *
     * baseonly - run only in the base
     */
    function getType()
    {
        return 'baseonly';
    }

    public function getPType()
    {
        /**
         * This element create a section
         * element that is a div
         * that should not be in paragraph
         *
         * We make it a block
         */
        return "block";
    }


    /**
     * @see Doku_Parser_Mode::getSort()
     * Higher number than the teaser-columns
     * because the mode with the lowest sort number will win out
     */
    function getSort()
    {
        return 99;
    }

    /**
     * Create a pattern that will called this plugin
     *
     * @param string $mode
     * @see Doku_Parser_Mode::connectTo()
     */
    function connectTo($mode)
    {
        if ($mode == "base") {
            // only from the top
            $this->Lexer->addSpecialPattern(self::PATTERN, $mode, PluginUtility::getModeFromTag($this->getPluginComponent()));
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

        if ($state == DOKU_LEXER_SPECIAL) {


            $jsonArray = self::frontMatterMatchToAssociativeArray($match);


            $result = [];
            // Decodage problem
            if ($jsonArray == null) {

                $result[self::STATUS] = self::PARSING_STATE_ERROR;
                $result[PluginUtility::PAYLOAD] = $match;

            } else {

                if (sizeof($jsonArray) === 0) {
                    return array(self::STATUS => self::PARSING_STATE_EMPTY);
                }

                $result[self::STATUS] = self::PARSING_STATE_SUCCESSFUL;

                $page = Page::createPageFromCurrentId();

                /**
                 * Published is an old alias for date published
                 */
                if (isset($jsonArray[Publication::OLD_META_KEY])) {
                    $jsonArray[Publication::DATE_PUBLISHED] = $jsonArray[Publication::OLD_META_KEY];
                    unset($jsonArray[Publication::OLD_META_KEY]);
                }

                if (isset($jsonArray[Page::OLD_REGION_PROPERTY])) {
                    $jsonArray[Page::REGION_META_PROPERTY] = $jsonArray[Page::OLD_REGION_PROPERTY];
                    unset($jsonArray[Page::OLD_REGION_PROPERTY]);
                }

                /**
                 * Upsert the meta
                 */
                $page->upsertMetadata($jsonArray);

                /**
                 * Return them
                 */
                $result[PluginUtility::ATTRIBUTES] = $jsonArray;

            }


            /**
             * End position is the length of the match + 1 for the newline
             */
            $newLine = 1;
            $endPosition = $pos + strlen($match) + $newLine;
            $result[PluginUtility::POSITION] = [$pos, $endPosition];

            return $result;
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

        switch ($format) {
            case 'xhtml':
                global $ID;
                /** @var Doku_Renderer_xhtml $renderer */

                $state = $data[self::STATUS];
                if ($state == self::PARSING_STATE_ERROR) {
                    $json = $data[PluginUtility::PAYLOAD];
                    LogUtility::msg("Front Matter: The json object for the page ($ID) is not valid. See the errors it by clicking on <a href=\"https://jsonformatter.curiousconcept.com/?data=" . urlencode($json) . "\">this link</a>.", LogUtility::LVL_MSG_ERROR);
                }

                /**
                 * Section
                 */
                list($startPosition, $endPosition) = $data[PluginUtility::POSITION];
                if (PluginUtility::getConfValue(self::CONF_ENABLE_SECTION_EDITING, 1)) {
                    $position = $startPosition;
                    $name = self::CANONICAL;
                    PluginUtility::startSection($renderer, $position, $name);
                    $renderer->finishSectionEdit($endPosition);
                }
                break;

            case renderer_plugin_combo_analytics::RENDERER_FORMAT:

                if ($data[self::STATUS] != self::PARSING_STATE_SUCCESSFUL) {
                    return false;
                }

                $notModifiableMeta = [
                    Analytics::PATH,
                    Analytics::DATE_CREATED,
                    Analytics::DATE_MODIFIED
                ];

                /** @var renderer_plugin_combo_analytics $renderer */
                $jsonArray = $data[PluginUtility::ATTRIBUTES];
                foreach ($jsonArray as $key => $value) {
                    if (!in_array($key, $notModifiableMeta)) {

                        $renderer->setMeta($key, $value);
                        if ($key === Page::IMAGE_META_PROPERTY) {
                            $this->updateImageStatistics($value, $renderer);
                        }

                    } else {
                        LogUtility::msg("The metadata ($key) cannot be set.", LogUtility::LVL_MSG_ERROR, self::CANONICAL);
                    }
                }
                break;

            case "metadata":

                /** @var Doku_Renderer_metadata $renderer */
                if ($data[self::STATUS] != self::PARSING_STATE_SUCCESSFUL) {
                    return false;
                }

                /**
                 * Register media in index
                 */
                $jsonArray = $data[PluginUtility::ATTRIBUTES];
                if (isset($jsonArray[Page::IMAGE_META_PROPERTY])) {
                    $value = $jsonArray[Page::IMAGE_META_PROPERTY];
                    $imageValues = [];
                    ArrayUtility::toFlatArray($imageValues, $value);
                    foreach ($imageValues as $imageValue) {
                        $media = MediaLink::createFromRenderMatch($imageValue);
                        $attributes = $media->toCallStackArray();
                        syntax_plugin_combo_media::registerImageMeta($attributes, $renderer);
                    }
                }

                /**
                 * Delete the controlled meta that are no more present in the frontmatter
                 * if they exists
                 * The managed meta with the exception of
                 * the {@link action_plugin_combo_metadescription::DESCRIPTION_META_KEY description}
                 * because it's already managed by dokuwiki in description['abstract']
                 */
                $managedMeta = [
                    Page::CANONICAL_PROPERTY,
                    Page::TYPE_META_PROPERTY,
                    Page::IMAGE_META_PROPERTY,
                    Page::REGION_META_PROPERTY,
                    Page::LANG_META_PROPERTY,
                    Analytics::TITLE,
                    syntax_plugin_combo_disqus::META_DISQUS_IDENTIFIER,
                    Publication::OLD_META_KEY,
                    Publication::DATE_PUBLISHED,
                    Analytics::NAME,
                    action_plugin_combo_metagoogle::JSON_LD_META_PROPERTY,
                    Page::LAYOUT_PROPERTY
                ];

                global $ID;
                $meta = p_read_metadata($ID);
                foreach ($managedMeta as $metaKey) {
                    if (!array_key_exists($metaKey, $jsonArray)) {
                        if (isset($meta['persistent'][$metaKey])) {
                            unset($meta['persistent'][$metaKey]);
                        }
                    }
                }
                p_save_metadata($ID, $meta);


                break;

        }
        return true;
    }



    private function updateImageStatistics($value, $renderer)
    {
        if (is_array($value)) {
            foreach ($value as $subImage) {
                $this->updateImageStatistics($subImage, $renderer);
            }
        } else {
            $media = MediaLink::createFromRenderMatch($value);
            $attributes = $media->toCallStackArray();
            syntax_plugin_combo_media::updateStatistics($attributes, $renderer);
        }
    }


}

