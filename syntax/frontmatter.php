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

use ComboStrap\ArrayUtility;
use ComboStrap\DokuPath;
use ComboStrap\ExceptionCombo;
use ComboStrap\ExceptionComboRuntime;
use ComboStrap\LogUtility;
use ComboStrap\MediaLink;
use ComboStrap\Message;
use ComboStrap\Metadata;
use ComboStrap\Page;
use ComboStrap\PageId;
use ComboStrap\PageImages;
use ComboStrap\Path;
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
     * The update status for the update of the frontmatter
     */
    const UPDATE_EXIT_CODE_DONE = 000;
    const UPDATE_EXIT_CODE_NOT_ENABLED = 100;
    const UPDATE_EXIT_CODE_NOT_CHANGED = 200;
    const UPDATE_EXIT_CODE_ERROR = 500;


    /**
     * Update the frontmatter with the managed metadata
     * Used after a submit from the form
     * @param Page $page
     * @return Message
     */
    public static function updateFrontmatter(Page $page): Message
    {

        /**
         * Default update value for the frontmatter
         */
        $updateFrontMatter = PluginUtility::getConfValue(syntax_plugin_combo_frontmatter::CONF_ENABLE_FRONT_MATTER_ON_SUBMIT, syntax_plugin_combo_frontmatter::CONF_ENABLE_FRONT_MATTER_ON_SUBMIT_DEFAULT);
        $originalFrontMatterMetadata = [];

        /**
         * If a frontmatter exists already, we update it
         */
        $content = $page->getTextContent();
        $frontMatterStartTag = syntax_plugin_combo_frontmatter::START_TAG;
        if (strpos($content, $frontMatterStartTag) === 0) {

            /**
             * We update it
             */
            $updateFrontMatter = 1;

            /**
             * Extract the actual values
             */
            $pattern = syntax_plugin_combo_frontmatter::PATTERN;
            $split = preg_split("/($pattern)/ms", $content, 2, PREG_SPLIT_DELIM_CAPTURE);

            /**
             * The split normally returns an array
             * where the first element is empty followed by the frontmatter
             */
            $emptyString = array_shift($split);
            if (!empty($emptyString)) {
                return Message::createErrorMessage("The frontmatter is not the first element")
                    ->setStatus(self::UPDATE_EXIT_CODE_ERROR);
            }

            $frontMatterMatch = array_shift($split);
            $originalFrontMatterMetadata = syntax_plugin_combo_frontmatter::frontMatterMatchToAssociativeArray($frontMatterMatch);
            if ($originalFrontMatterMetadata === null) {
                return Message::createErrorMessage("The existing frontmatter is not a valid json.");
            }

            /**
             * Building the document again
             */
            $contentWithoutFrontMatter = "";
            while (($element = array_shift($split)) != null) {
                $contentWithoutFrontMatter .= $element;
            }
        } else {
            $contentWithoutFrontMatter = DOKU_LF . $content;
        }

        if ($updateFrontMatter === 0) {
            return Message::createInfoMessage("The frontmatter is not enabled")
                ->setStatus(self::UPDATE_EXIT_CODE_NOT_ENABLED);
        }

        /**
         * Page Id special
         */
        if (isset($originalFrontMatterMetadata[PageId::PAGE_ID_ATTRIBUTE])) {
            if ($page->getPageId() !== null) {
                $originalFrontMatterMetadata[PageId::PAGE_ID_ATTRIBUTE] = $page->getPageId();
            }
        }

        /**
         * Update the mutable data
         * (ie delete insert)
         */
        $nonDefaultMetadatasValuesInStorageFormat = $page->getNonDefaultMetadatasValuesInStorageFormat();
        $userDefinedMetadata = Metadata::deleteMutableMetadata($originalFrontMatterMetadata);
        $targetFrontMatterMetadata = array_merge($nonDefaultMetadatasValuesInStorageFormat, $userDefinedMetadata);
        ksort($targetFrontMatterMetadata);

        /**
         * Same ?
         */
        if ($originalFrontMatterMetadata === $targetFrontMatterMetadata) {
            return Message::createInfoMessage("The frontmatter are the same (no update)")
                ->setStatus(self::UPDATE_EXIT_CODE_NOT_CHANGED);
        }


        $targetFrontMatterJsonString = \ComboStrap\Json::createFromArray($targetFrontMatterMetadata)->toFrontMatterFormat();


        /**
         * Build the new document
         */
        $frontMatterEndTag = syntax_plugin_combo_frontmatter::END_TAG;
        $newPageContent = <<<EOF
$frontMatterStartTag
$targetFrontMatterJsonString
$frontMatterEndTag$contentWithoutFrontMatter
EOF;
        $page->upsertContent($newPageContent, "Metadata manager upsert");

        return Message::createInfoMessage("The frontmatter was changed")
            ->setStatus(self::UPDATE_EXIT_CODE_DONE);

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
     * @return array|null - null if decodage problem, empty array if no json or an associative array
     */
    public static function frontMatterMatchToAssociativeArray($match): ?array
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
            if ($jsonArray === null) {

                $result[self::STATUS] = self::PARSING_STATE_ERROR;
                $result[PluginUtility::PAYLOAD] = $match;
                return $result;
            }

            if (sizeof($jsonArray) === 0) {
                /**
                 * Empty string
                 * Rare case, we delete all mutable meta if present
                 */
                global $ID;
                $meta = p_read_metadata($ID);
                foreach (Metadata::MUTABLE_METADATA as $metaKey) {
                    if (!array_key_exists($metaKey, $jsonArray)) {
                        if (isset($meta['persistent'][$metaKey])) {
                            unset($meta['persistent'][$metaKey]);
                        }
                    }
                }
                p_save_metadata($ID, $meta);
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
                $frontMatterJsonArray = $data[PluginUtility::ATTRIBUTES];
                foreach ($frontMatterJsonArray as $key => $value) {

                    $renderer->setAnalyticsMetaForReporting($key, $value);
                    if ($key === PageImages::IMAGE_META_PROPERTY) {
                        $this->updateImageStatistics($value, $renderer);
                    }

                }
                break;

            case "metadata":

                global $ID;
                /** @var Doku_Renderer_metadata $renderer */
                if ($data[self::STATUS] === self::PARSING_STATE_ERROR) {
                    if (PluginUtility::isDevOrTest()) {
                        // fail if test
                        throw new ExceptionComboRuntime("Front Matter: The json object for the page ($ID) is not valid. ", LogUtility::LVL_MSG_ERROR);
                    }
                    return false;
                }

                /**
                 * Register media in index
                 */
                $page = Page::createPageFromId($ID);
                $frontMatterJsonArray = $data[PluginUtility::ATTRIBUTES];
                if (isset($frontMatterJsonArray[PageImages::IMAGE_META_PROPERTY])) {
                    $value = $frontMatterJsonArray[PageImages::IMAGE_META_PROPERTY];
                    try {
                        $pageImages = PageImages::createForPageWithDefaultStore($page)
                            ->buildFromPersistentFormat($value);
                        foreach ($pageImages->getAll() as $imageValue) {
                            $imagePath = $imageValue->getImage()->getPath()->getAbsolutePath();
                            $attributes = [Path::PATH_ATTRIBUTE => $imagePath];
                            if (media_isexternal($imagePath)) {
                                $attributes[MediaLink::MEDIA_DOKUWIKI_TYPE] = MediaLink::EXTERNAL_MEDIA_CALL_NAME;
                            } else {
                                $attributes[MediaLink::MEDIA_DOKUWIKI_TYPE] = MediaLink::INTERNAL_MEDIA_CALL_NAME;
                            }
                            syntax_plugin_combo_media::registerImageMeta($attributes, $renderer);
                        }
                    } catch (ExceptionCombo $e) {
                        LogUtility::msg($e->getMessage(), LogUtility::LVL_MSG_ERROR, $e->getCanonical());
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

