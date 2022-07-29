<?php


namespace ComboStrap;


use syntax_plugin_combo_frontmatter;

/**
 * A page represented as a frontmatter
 *
 * By giving a page to the constructor, you can:
 *   * change the content of the frontmatter
 *   * add a header/frontmatter to the content
 *   * and persist
 * MetadataFrontmatterStore
 * @package ComboStrap
 */
class MetadataFrontmatterStore extends MetadataSingleArrayStore
{

    const NAME = "frontmatter";
    const CANONICAL = self::NAME;

    /**
     * @var bool Do we have a frontmatter on the page
     */
    private $isPresent = false;
    /**
     * @var string
     */
    private $contentWithoutFrontMatter;

    /**
     * @throws ExceptionCompile
     */
    private function syncData()
    {

        /**
         * @var MarkupPath $resourceCombo
         */
        $resourceCombo = $this->getResource();

        /**
         * Resource Id special
         */
        $guidObject = $resourceCombo->getUidObject();
        if (
            !$this->hasProperty($guidObject::getPersistentName())
            &&
            $guidObject->getValue() !== null
        ) {
            $this->setFromPersistentName($guidObject::getPersistentName(), $guidObject->getValue());
        }

        /**
         * Read store
         */
        $dokuwikiStore = MetadataDokuWikiStore::getOrCreateFromResource($resourceCombo);
        $metaFilePath = $dokuwikiStore->getMetaFilePath();
        if ($metaFilePath !== null) {
            $metaModifiedTime = FileSystems::getModifiedTime($metaFilePath);
            $pageModifiedTime = FileSystems::getModifiedTime($resourceCombo->getPathObject());
            $diff = $pageModifiedTime->diff($metaModifiedTime);
            $secondDiff = intval($diff->format('%s'));
            if ($secondDiff > 0) {
                $resourceCombo->renderMetadataAndFlush();
            }
        }
        /**
         * Update the mutable data
         * (ie delete insert)
         */
        foreach (Metadata::MUTABLE_METADATA as $metaKey) {
            $metadata = Metadata::getForName($metaKey);
            if ($metadata === null) {
                $msg = "The metadata $metaKey should be defined";
                if (PluginUtility::isDevOrTest()) {
                    throw new ExceptionCompile($msg);
                } else {
                    LogUtility::msg($msg);
                }
            }
            $metadata
                ->setResource($resourceCombo)
                ->setReadStore($dokuwikiStore)
                ->setWriteStore($this);

            $sourceValue = $this->get($metadata);
            try {
                $targetValue = $metadata->getValue();
            } catch (ExceptionNotFound $e) {
                $targetValue = null;
            }
            try {
                $defaultValue = $metadata->getDefaultValue();
            } catch (ExceptionNotFound $e) {
                $defaultValue = null;
            }
            /**
             * Strict because otherwise the comparison `false == null` is true
             */
            $targetValueShouldBeStore = !in_array($targetValue, [$defaultValue, null], true);
            if ($targetValueShouldBeStore) {
                if ($sourceValue !== $targetValue) {
                    $this->set($metadata);
                }
            } else {
                if ($sourceValue !== null) {
                    $this->remove($metadata);
                }
            }
        }
    }

    /**
     * Update the frontmatter with the managed metadata
     * Used after a submit from the form
     * @return Message
     */
    public function sync(): Message
    {

        /**
         * Default update value for the frontmatter
         */
        $updateFrontMatter = Site::getConfValue(syntax_plugin_combo_frontmatter::CONF_ENABLE_FRONT_MATTER_ON_SUBMIT, syntax_plugin_combo_frontmatter::CONF_ENABLE_FRONT_MATTER_ON_SUBMIT_DEFAULT);


        if ($this->isPresent()) {
            $updateFrontMatter = 1;
        }


        if ($updateFrontMatter === 0) {
            return Message::createInfoMessage("The frontmatter is not enabled")
                ->setStatus(syntax_plugin_combo_frontmatter::UPDATE_EXIT_CODE_NOT_ENABLED);
        }

        try {
            $this->syncData();
        } catch (ExceptionCompile $e) {
            if (PluginUtility::isDevOrTest()) {
                throw new ExceptionRuntime("Error while synchronizing data in the frontmatter", self::CANONICAL, 1, $e);
            }
            return Message::createInfoMessage($e->getMessage())
                ->setStatus(syntax_plugin_combo_frontmatter::UPDATE_EXIT_CODE_ERROR);
        }


        /**
         * Same ?
         */
        if (!$this->hasStateChanged()) {
            return Message::createInfoMessage("The frontmatter are the same (no update)")
                ->setStatus(syntax_plugin_combo_frontmatter::UPDATE_EXIT_CODE_NOT_CHANGED);
        }

        $this->persist();

        return Message::createInfoMessage("The frontmatter was changed")
            ->setStatus(syntax_plugin_combo_frontmatter::UPDATE_EXIT_CODE_DONE);

    }


    public function isPresent(): bool
    {
        return $this->isPresent;
    }

    /**
     * MetadataFrontmatterStore constructor.
     * @param ResourceCombo $page
     * @param array|null $data
     */
    public function __construct(ResourceCombo $page, array $data = null)
    {
        parent::__construct($page, $data);
    }

    /**
     * @param $match
     * @return array|null - null if decodage problem, empty array if no json or an associative array
     * @deprecated used {@link MetadataFrontmatterStore::loadAsString()} instead
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

    public static function stripFrontmatterTag($match)
    {
        // strip
        //   from start `---json` + eol = 8
        //   from end   `---` + eol = 4
        return substr($match, 7, -3);
    }


    public static function getOrCreateFromResource(ResourceCombo $resourceCombo): MetadataStore
    {
        return new MetadataFrontmatterStore($resourceCombo, null);
    }

    public static function createFromArray(ResourceCombo $page, array $jsonArray): MetadataFrontmatterStore
    {
        return new MetadataFrontmatterStore($page, $jsonArray);
    }

    /**
     * @throws ExceptionBadSyntax
     */
    public static function createFromFrontmatterString($page, $frontmatter = null): MetadataFrontmatterStore
    {
        if ($frontmatter === null) {
            return new MetadataFrontmatterStore($page, []);
        }
        $jsonArray = self::frontMatterMatchToAssociativeArray($frontmatter);
        if ($jsonArray === null) {
            throw new ExceptionBadSyntax("The frontmatter is not valid");
        }
        return new MetadataFrontmatterStore($page, $jsonArray);
    }

    /**
     * @throws ExceptionBadSyntax - if the content has a syntax problem
     * @throws ExceptionNotFound - if the page does not exist
     */
    public static function createFromPage(MarkupPath $page): MetadataFrontmatterStore
    {
        $content = FileSystems::getContent($page->getPathObject());
        $frontMatterStartTag = syntax_plugin_combo_frontmatter::START_TAG;
        if (strpos($content, $frontMatterStartTag) === 0) {

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
                throw new ExceptionBadSyntax("The frontmatter is not the first element");
            }

            $frontMatterMatch = array_shift($split);
            /**
             * Building the document again
             */
            $contentWithoutFrontMatter = "";
            while (($element = array_shift($split)) != null) {
                $contentWithoutFrontMatter .= $element;
            }

            return MetadataFrontmatterStore::createFromFrontmatterString($page, $frontMatterMatch)
                ->setIsPresent(true)
                ->setContentWithoutFrontMatter($contentWithoutFrontMatter);

        }
        return (new MetadataFrontmatterStore($page))
            ->setIsPresent(false)
            ->setContentWithoutFrontMatter($content);

    }


    public function __toString()
    {
        return self::NAME;
    }


    public function getJsonString(): string
    {

        $jsonArray = $this->getData();
        ksort($jsonArray);
        return self::toFrontmatterJsonString($jsonArray);

    }

    /**
     * This formatting make the object on one line for a list of object
     * making the frontmatter compacter (one line, one meta)
     * @param $jsonArray
     * @return string
     */
    public static function toFrontmatterJsonString($jsonArray): string
    {

        if (sizeof($jsonArray) === 0) {
            return "{}";
        }
        $jsonString = "";
        self::jsonFlatRecursiveEncoding($jsonArray, $jsonString);

        /**
         * Double Guard (frontmatter should be quick enough)
         * to support this overhead
         */
        $decoding = json_decode($jsonString);
        if ($decoding === null) {
            throw new ExceptionRuntime("The generated frontmatter json is no a valid json");
        }
        return $jsonString;
    }

    private static function jsonFlatRecursiveEncoding(array $jsonProperty, &$jsonString, $level = 0, $endOfFieldCharacter = DOKU_LF, $type = Json::TYPE_OBJECT, $parentType = Json::TYPE_OBJECT)
    {
        /**
         * Open the root object
         */
        if ($type === Json::TYPE_OBJECT) {
            $jsonString .= "{";
        } else {
            $jsonString .= "[";
        }

        /**
         * Level indentation
         */
        $levelSpaceIndentation = str_repeat(" ", ($level + 1) * Json::TAB_SPACES_COUNTER);

        /**
         * Loop
         */
        $elementCounter = 0;
        foreach ($jsonProperty as $key => $value) {

            $elementCounter++;

            /**
             * Close the previous property
             */
            $isFirstProperty = $elementCounter === 1;
            if ($isFirstProperty && $parentType !== Json::PARENT_TYPE_ARRAY) {
                // go the line if this is not a list of object
                $jsonString .= DOKU_LF;
            }
            if (!$isFirstProperty) {
                $jsonString .= ",$endOfFieldCharacter";
            }
            if ($endOfFieldCharacter === DOKU_LF) {
                $tab = $levelSpaceIndentation;
            } else {
                $tab = " ";
            }
            $jsonString .= $tab;

            /**
             * Recurse
             */
            $jsonEncodedKey = json_encode($key);
            if (is_array($value)) {
                $childLevel = $level + 1;
                if (is_numeric($key)) {
                    /**
                     * List of object
                     */
                    $childType = Json::TYPE_OBJECT;
                    $childEndOField = "";
                } else {
                    /**
                     * Array
                     */
                    $jsonString .= "$jsonEncodedKey: ";
                    $childType = Json::TYPE_OBJECT;
                    if ($value[0] !== null) {
                        $childType = Json::PARENT_TYPE_ARRAY;
                    }
                    $childEndOField = $endOfFieldCharacter;
                }
                self::jsonFlatRecursiveEncoding($value, $jsonString, $childLevel, $childEndOField, $childType, $type);

            } else {
                /**
                 * Single property
                 */
                $jsonEncodedValue = json_encode($value);
                $jsonString .= "$jsonEncodedKey: $jsonEncodedValue";

            }

        }

        /**
         * Close the object or array
         */
        $closingLevelSpaceIndentation = str_repeat(" ", $level * Json::TAB_SPACES_COUNTER);
        if ($type === Json::TYPE_OBJECT) {
            if ($parentType !== Json::PARENT_TYPE_ARRAY) {
                $jsonString .= DOKU_LF . $closingLevelSpaceIndentation;
            } else {
                $jsonString .= " ";
            }
            $jsonString .= "}";
        } else {
            /**
             * The array is not going one level back
             */
            $jsonString .= DOKU_LF . $closingLevelSpaceIndentation . "]";
        }
    }


    public function toFrontmatterString(): string
    {
        $frontmatterStartTag = syntax_plugin_combo_frontmatter::START_TAG;
        $frontmatterEndTag = syntax_plugin_combo_frontmatter::END_TAG;
        $jsonArray = $this->getData();
        ksort($jsonArray);
        $jsonEncode = self::toFrontmatterJsonString($jsonArray);

        return <<<EOF
$frontmatterStartTag
$jsonEncode
$frontmatterEndTag
EOF;


    }

    private function setIsPresent(bool $bool): MetadataFrontmatterStore
    {
        $this->isPresent = $bool;
        return $this;
    }

    public function persist()
    {
        if ($this->contentWithoutFrontMatter === null) {
            LogUtility::msg("The content without frontmatter should have been set. Did you you use the createFromPage constructor");
            return $this;
        }
        $newPageContent = $this->toMarkup();
        $resourceCombo = $this->getResource();
        if ($resourceCombo instanceof MarkupPath) {
            $resourceCombo->upsertContent($newPageContent, "Metadata frontmatter store upsert");
        }
        return $this;
    }

    private function setContentWithoutFrontMatter(string $contentWithoutFrontMatter): MetadataFrontmatterStore
    {
        $this->contentWithoutFrontMatter = $contentWithoutFrontMatter;
        return $this;
    }


    /**
     * @return string - the new markup (ie the new frontmatter and the markup)
     */
    public function toMarkup(): string
    {

        $targetFrontMatterJsonString = $this->toFrontmatterString();

        /**
         * EOL for the first frontmatter
         */
        $sep = "";
        if (strlen($this->contentWithoutFrontMatter) > 0) {
            $firstChar = $this->contentWithoutFrontMatter[0];
            if (!in_array($firstChar, ["\n", "\r"])) {
                $sep = "\n";
            }
        }

        /**
         * Build the new document
         */
        return <<<EOF
$targetFrontMatterJsonString$sep$this->contentWithoutFrontMatter
EOF;

    }

    public function getContentWithoutFrontMatter(): string
    {
        return $this->contentWithoutFrontMatter;
    }


}
