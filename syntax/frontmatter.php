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
use ComboStrap\CallStack;
use ComboStrap\Canonical;
use ComboStrap\ExceptionBadSyntax;
use ComboStrap\ExceptionNotFound;
use ComboStrap\ExecutionContext;
use ComboStrap\MarkupRef;
use ComboStrap\MediaMarkup;
use ComboStrap\EditButton;
use ComboStrap\EditButtonManager;
use ComboStrap\EndDate;
use ComboStrap\ExceptionBadArgument;
use ComboStrap\ExceptionCompile;
use ComboStrap\ExceptionNotEnabled;
use ComboStrap\ExceptionRuntime;
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
use ComboStrap\MarkupPath;
use ComboStrap\PageDescription;
use ComboStrap\PageH1;
use ComboStrap\PageId;
use ComboStrap\PageImagePath;
use ComboStrap\PageImages;
use ComboStrap\PageKeywords;
use ComboStrap\PageLayoutName;
use ComboStrap\PagePath;
use ComboStrap\PagePublicationDate;
use ComboStrap\PageTitle;
use ComboStrap\PageType;
use ComboStrap\PluginUtility;
use ComboStrap\QualityDynamicMonitoringOverwrite;
use ComboStrap\Region;
use ComboStrap\ResourceName;
use ComboStrap\StartDate;
use ComboStrap\TagAttributes;

require_once(__DIR__ . '/../ComboStrap/PluginUtility.php');


/**
 * All DokuWiki plugins to extend the parser/rendering mechanism
 * need to inherit from this class
 *
 * For a list of meta, see also https://ghost.org/docs/publishing/#api-data
 */
class syntax_plugin_combo_frontmatter extends DokuWiki_Syntax_Plugin
{

    const PARSING_STATE_ERROR = 1;
    const PARSING_STATE_SUCCESSFUL = 0;

    const CANONICAL = "frontmatter";
    const TAG = "frontmatter";
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

    public function getPType(): string
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
     * @return array
     * @see DokuWiki_Syntax_Plugin::handle()
     *
     */
    function handle($match, $state, $pos, Doku_Handler $handler): array
    {

        $result = [];

        try {
            $wikiPath = ExecutionContext::getActualOrCreateFromEnv()->getExecutingWikiPath();
            $parsedPage = MarkupPath::createPageFromPathObject($wikiPath);
        } catch (ExceptionCompile $e) {
            LogUtility::error("The global ID is unknown, we couldn't get the requested page", self::CANONICAL);
            return [];
        }
        try {

            $frontMatterStore = MetadataFrontmatterStore::createFromFrontmatterString($parsedPage, $match);
            $result[PluginUtility::EXIT_CODE] = self::PARSING_STATE_SUCCESSFUL;
        } catch (ExceptionCompile $e) {
            // Decode problem
            $result[PluginUtility::EXIT_CODE] = self::PARSING_STATE_ERROR;
            $result[PluginUtility::EXIT_MESSAGE] = $match;
            return $result;
        }


        $targetStore = MetadataDokuWikiStore::getOrCreateFromResource($parsedPage);
        $frontMatterData = $frontMatterStore->getData();

        $transfer = MetadataStoreTransfer::createForPage($parsedPage)
            ->fromStore($frontMatterStore)
            ->toStore($targetStore)
            ->setMetadatas($frontMatterData)
            ->validate();

        $messages = $transfer->getMessages();
        $validatedMetadatas = $transfer->getValidatedMetadatas();
        $renderMetadata = [];
        foreach ($validatedMetadatas as $metadataObject) {
            $renderMetadata[$metadataObject::getPersistentName()] = $metadataObject->toStoreValue();
        }

        foreach ($messages as $message) {
            $message->sendToLogUtility();
        }

        /**
         * Return them for metadata rendering
         */
        $result[PluginUtility::ATTRIBUTES] = $renderMetadata;
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

        try {
            $executingPath = ExecutionContext::getActualOrCreateFromEnv()
                ->getExecutingWikiPath();
        } catch (ExceptionNotFound $e) {
            // markup string rendering
            return false;
        }
        switch ($format) {
            case 'xhtml':

                /** @var Doku_Renderer_xhtml $renderer */
                $exitCode = $data[PluginUtility::EXIT_CODE];
                if ($exitCode == self::PARSING_STATE_ERROR) {
                    $json = MetadataFrontmatterStore::stripFrontmatterTag($data[PluginUtility::EXIT_MESSAGE]);
                    LogUtility::error("Front Matter: The json object for the page ($executingPath) is not valid. " . \ComboStrap\Json::getValidationLink($json), self::CANONICAL);
                }
                return true;

            case renderer_plugin_combo_analytics::RENDERER_FORMAT:

                if ($data[PluginUtility::EXIT_CODE] !== self::PARSING_STATE_SUCCESSFUL) {
                    return true;
                }

                /** @var renderer_plugin_combo_analytics $renderer */
                $frontMatterJsonArray = $data[PluginUtility::ATTRIBUTES];
                foreach ($frontMatterJsonArray as $key => $value) {

                    $renderer->setAnalyticsMetaForReporting($key, $value);
                    if ($key === PageImages::PROPERTY_NAME) {
                        $this->updateImageStatistics($value, $renderer);
                    }

                }
                return true;

            case "metadata":


                /** @var Doku_Renderer_metadata $renderer */
                if ($data[PluginUtility::EXIT_CODE] === self::PARSING_STATE_ERROR) {
                    if (PluginUtility::isTest()) {
                        // fail if test
                        throw new ExceptionRuntime("Front Matter: The json object for the page () is not valid.", LogUtility::LVL_MSG_ERROR);
                    }
                    return false;
                }

                /**
                 * Empty string
                 * Rare case, we delete all mutable meta if present
                 */
                $frontmatterData = $data[PluginUtility::ATTRIBUTES];
                if (sizeof($frontmatterData) === 0) {
                    foreach (Metadata::MUTABLE_METADATA as $metaKey) {
                        if ($metaKey === PageDescription::PROPERTY_NAME) {
                            // array
                            continue;
                        }
                        // runtime
                        if ($renderer->meta[$metaKey]) {
                            unset($renderer->meta[$metaKey]);
                        }
                        // persistent
                        if ($renderer->persistent[$metaKey]) {
                            unset($renderer->persistent[$metaKey]);
                        }
                    }
                    return true;
                }

                /**
                 * Meta update
                 * (The {@link p_get_metadata()} starts {@link p_render_metadata()}
                 * and stores them if there is any diff
                 */
                foreach ($frontmatterData as $metaKey => $metaValue) {

                    $renderer->meta[$metaKey] = $metaValue;

                    /**
                     * Persistence is just a duplicate of the meta (ie current)
                     *
                     * Why from https://www.dokuwiki.org/devel:metadata#metadata_persistence
                     * The persistent array holds ****duplicates****
                     * as the {@link p_get_metadata()} returns only `current` data
                     * which should not be cleared during the rendering process.
                     */
                    $renderer->persistent[$metaKey] = $metaValue;

                }

                /**
                 * Database update
                 */
                $page = MarkupPath::createPageFromPathObject($executingPath);
                if ($page->exists()) {
                    try {
                        $databasePage = $page->getDatabasePage();
                        $databasePage->replicateMetaAttributes();
                    } catch (Exception $e) {
                        if (PluginUtility::isDevOrTest()) {
                            /** @noinspection PhpUnhandledExceptionInspection */
                            throw $e;
                        }
                        $message = Message::createErrorMessage($e->getMessage());
                        if ($e instanceof ExceptionCompile) {
                            $message->setCanonical($e->getCanonical());
                        }
                        $message->sendToLogUtility();
                    }

                }

                /**
                 * Register media in index
                 */
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
                        $dokuwikiId = $imageValue->getImagePath()->getWikiId();
                        $attributes = [MarkupRef::REF_ATTRIBUTE => ":$dokuwikiId"];
                        try {
                            syntax_plugin_combo_media::registerImageMeta($attributes, $renderer);
                        } catch (\Exception $e) {
                            LogUtility::internalError("The image registration did not work. Error: {$e->getMessage()}");
                        }
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
        try {
            $media = MediaMarkup::createFromRef($path);
        } catch (ExceptionBadArgument|ExceptionNotFound|ExceptionBadSyntax $e) {
            LogUtility::internalError("The media image statistics could not be created. The media markup could not be instantiated with the path ($path). Error:{$e->getMessage()}");
            return;
        }

        $attributes = $media->toCallStackArray();
        syntax_plugin_combo_media::updateStatistics($attributes, $renderer);

    }


}

