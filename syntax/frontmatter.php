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

use ComboStrap\Aliases;
use ComboStrap\CacheExpirationFrequency;
use ComboStrap\Canonical;
use ComboStrap\EndDate;
use ComboStrap\ExceptionCombo;
use ComboStrap\ExceptionComboRuntime;
use ComboStrap\FileSystems;
use ComboStrap\Lang;
use ComboStrap\LdJson;
use ComboStrap\LogUtility;
use ComboStrap\LowQualityPageOverwrite;
use ComboStrap\MediaLink;
use ComboStrap\Message;
use ComboStrap\Metadata;
use ComboStrap\MetadataDokuWikiStore;
use ComboStrap\MetadataFrontmatterStore;
use ComboStrap\MetadataStoreTransfer;
use ComboStrap\Page;
use ComboStrap\PageH1;
use ComboStrap\PageId;
use ComboStrap\PageImagePath;
use ComboStrap\PageImages;
use ComboStrap\PageKeywords;
use ComboStrap\PageLayout;
use ComboStrap\PagePath;
use ComboStrap\PagePublicationDate;
use ComboStrap\PageTitle;
use ComboStrap\PageType;
use ComboStrap\PluginUtility;
use ComboStrap\QualityDynamicMonitoringOverwrite;
use ComboStrap\Region;
use ComboStrap\ResourceName;
use ComboStrap\StartDate;

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

            $result = [];
            $page = Page::createPageFromGlobalDokuwikiId();
            try {
                $frontMatterStore = MetadataFrontmatterStore::createFromFrontmatterString($page, $match);
                $result[self::STATUS] = self::PARSING_STATE_SUCCESSFUL;
            } catch (ExceptionCombo $e) {
                // Decode problem
                $result[self::STATUS] = self::PARSING_STATE_ERROR;
                $result[PluginUtility::PAYLOAD] = $match;
                return $result;
            }

            /**
             * Empty string
             * Rare case, we delete all mutable meta if present
             */
            $frontmatterData = $frontMatterStore->getData();
            if ($frontmatterData === null) {
                global $ID;
                $meta = p_read_metadata($ID);
                foreach (Metadata::MUTABLE_METADATA as $metaKey) {
                    if (isset($meta['persistent'][$metaKey])) {
                        unset($meta['persistent'][$metaKey]);
                    }
                }
                p_save_metadata($ID, $meta);
                return array(self::STATUS => self::PARSING_STATE_EMPTY);
            }


            /**
             * Sync
             */
            $targetStore = MetadataDokuWikiStore::getOrCreateFromResource($page);
            $transfer = MetadataStoreTransfer::createForPage($page)
                ->fromStore($frontMatterStore)
                ->toStore($targetStore)
                ->process($frontmatterData);

            $messages = $transfer->getMessages();
            $dataForRenderer = $transfer->getNormalizedDataArray();


            /**
             * Database update
             */
            try {
                $databasePage = $page->getDatabasePage();
                $databasePage->replicateMetaAttributes();
            } catch (Exception $e) {
                $message = Message::createErrorMessage($e->getMessage());
                if ($e instanceof ExceptionCombo) {
                    $message->setCanonical($e->getCanonical());
                }
                $messages[] = $message;
            }


            foreach ($messages as $message) {
                $message->sendLogMsg();
            }

            /**
             * Return them for metadata rendering
             */
            $result[PluginUtility::ATTRIBUTES] = $dataForRenderer;

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
                    $json = MetadataFrontmatterStore::stripFrontmatterTag($data[PluginUtility::PAYLOAD]);
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
                    if ($key === PageImages::PROPERTY_NAME) {
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
                        throw new ExceptionComboRuntime("Front Matter: The json object for the page ($ID) is not valid.", LogUtility::LVL_MSG_ERROR);
                    }
                    return false;
                }

                /**
                 * Register media in index
                 */
                $page = Page::createPageFromId($ID);
                $frontMatterJsonArray = $data[PluginUtility::ATTRIBUTES];
                if (isset($frontMatterJsonArray[PageImages::getPersistentName()])) {
                    $value = $frontMatterJsonArray[PageImages::getPersistentName()];

                    /**
                     * @var PageImages $pageImages
                     */
                    $pageImages = PageImages::createForPage($page)
                        ->buildFromStoreValue($value);
                    $pageImagesObject = $pageImages->getValueAsPageImages();
                    foreach ($pageImagesObject as $imageValue) {
                        $imagePath = $imageValue->getImage()->getPath()->toAbsolutePath()->toString();
                        $attributes = [PagePath::PROPERTY_NAME => $imagePath];
                        if (media_isexternal($imagePath)) {
                            $attributes[MediaLink::MEDIA_DOKUWIKI_TYPE] = MediaLink::EXTERNAL_MEDIA_CALL_NAME;
                        } else {
                            $attributes[MediaLink::MEDIA_DOKUWIKI_TYPE] = MediaLink::INTERNAL_MEDIA_CALL_NAME;
                        }
                        syntax_plugin_combo_media::registerImageMeta($attributes, $renderer);
                    }

                }

                break;

        }
        return true;
    }


    private function updateImageStatistics($value, $renderer)
    {
        if (is_array($value) && sizeof($value) > 0) {
            $firstKey = array_keys($value)[0];
            if (is_numeric($firstKey)) {
                foreach ($value as $subImage) {
                    $this->updateImageStatistics($subImage, $renderer);
                }
                return;
            }
        }

        /**
         * Code below is fucked up
         */
        $path = $value;
        if (is_array($value) && isset($value[PageImagePath::getPersistentName()])) {
            $path = $value[PageImagePath::getPersistentName()];
        }
        $media = MediaLink::createFromRenderMatch($path);
        $attributes = $media->toCallStackArray();
        syntax_plugin_combo_media::updateStatistics($attributes, $renderer);

    }


}

