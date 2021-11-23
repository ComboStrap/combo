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
use ComboStrap\Metadata;
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
    const CONF_ENABLE_FRONT_MATTER_ON_SUBMIT = "enableFrontMatterOnSubmit";
    const CONF_ENABLE_FRONT_MATTER_ON_SUBMIT_DEFAULT = 0;

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
     * Update the frontmatter with the managed metadata
     * Used after a submit from the form
     * @param Page $page
     */
    public static function updateFrontmatter(Page $page)
    {

        /**
         * Default value
         */
        $updateFrontMatter = PluginUtility::getConfValue(syntax_plugin_combo_frontmatter::CONF_ENABLE_FRONT_MATTER_ON_SUBMIT, syntax_plugin_combo_frontmatter::CONF_ENABLE_FRONT_MATTER_ON_SUBMIT_DEFAULT);
        /**
         * If a frontmatter exists already, we update it
         */
        $content = $page->getContent();
        $frontMatterStartTag = syntax_plugin_combo_frontmatter::START_TAG;
        if (strpos($content, $frontMatterStartTag) === 0) {
            $updateFrontMatter = 1;
        }

        if ($updateFrontMatter === 0) {
            return;
        }

        $pattern = syntax_plugin_combo_frontmatter::PATTERN;
        $split = preg_split("/($pattern)/ms", $content, 2, PREG_SPLIT_DELIM_CAPTURE);

        /**
         * The split normally returns an array
         * where the first element is empty followed by the frontmatter
         */
        $emptyString = array_shift($split);
        if (!empty($emptyString)) {
            return;
        }

        $frontMatter = array_shift($split);

        $originalFrontMatterMetadata = syntax_plugin_combo_frontmatter::frontMatterMatchToAssociativeArray($frontMatter);
        $userDefinedMetadata = Metadata::deleteManagedMetadata($originalFrontMatterMetadata);
        $nonDefaultMetadatasValuesInStorageFormat = $page->getNonDefaultMetadatasValuesInStorageFormat();
        $targetFrontMatterMetadata = array_merge($nonDefaultMetadatasValuesInStorageFormat, $userDefinedMetadata);
        ksort($targetFrontMatterMetadata);
        $targetFrontMatterJsonString = json_encode($targetFrontMatterMetadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        /**
         * Building the document again
         */
        $restDocument = "";
        while (($element = array_shift($split)) != null) {
            $restDocument .= $element;
        }

        /**
         * Build the new document
         */
        $frontMatterEndTag = syntax_plugin_combo_frontmatter::END_TAG;
        $newPageContent = <<<EOF
$frontMatterStartTag
$targetFrontMatterJsonString
$frontMatterEndTag$restDocument
EOF;
        $page->upsertContent($newPageContent, "Metadata manager upsert");

    }


    private static function stripFrontmatterTag($match)
    {
        // strip
        //   from start `---json` + eol = 8
        //   from end   `---` + eol = 4
        return substr($match, 7, -3);
    }

    /**
     * @param $match
     * @return array|mixed - null if decodage problem, empty array if no json or an associative array
     */
    public static function frontMatterMatchToAssociativeArray($match)
    {
        $jsonString = self::stripFrontmatterTag($match);

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
    function getType(): string
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
            // Decode problem
            if ($jsonArray == null) {

                $result[self::STATUS] = self::PARSING_STATE_ERROR;
                $result[PluginUtility::PAYLOAD] = $match;
                return $result;
            }

            if (sizeof($jsonArray) === 0) {
                return array(self::STATUS => self::PARSING_STATE_EMPTY);
            }

            $result[self::STATUS] = self::PARSING_STATE_SUCCESSFUL;

            $page = Page::createPageFromGlobalDokuwikiId();

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
            $messages = $page->upsertMetadataFromAssociativeArray($jsonArray);
            foreach ($messages as $message) {
                $message->sendLogMsg();
            }

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
                global $ID;
                /** @var Doku_Renderer_xhtml $renderer */

                $state = $data[self::STATUS];
                if ($state == self::PARSING_STATE_ERROR) {
                    $json = self::stripFrontmatterTag($data[PluginUtility::PAYLOAD]);
                    LogUtility::msg("Front Matter: The json object for the page ($ID) is not valid. " . \ComboStrap\Json::getValidationLink($json), LogUtility::LVL_MSG_ERROR);
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


                /** @var renderer_plugin_combo_analytics $renderer */
                $jsonArray = $data[PluginUtility::ATTRIBUTES];
                foreach ($jsonArray as $key => $value) {
                    if (!in_array($key, Metadata::NOT_MODIFIABLE_METADATA)) {

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

