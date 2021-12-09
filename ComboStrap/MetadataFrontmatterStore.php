<?php


namespace ComboStrap;


use syntax_plugin_combo_frontmatter;

class MetadataFrontmatterStore implements MetadataStore
{

    const NAME = "frontmatter";


    /**
     * @var MetadataFrontmatterStore
     */
    private static $store;
    private $data;

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

    public function loadAsArray(ResourceCombo $page, array $jsonArray): MetadataFrontmatterStore
    {
        $path = $this->getArrayKey($page);
        $this->data[$path] = $jsonArray;
        return $this;
    }

    public static function getOrCreate(): MetadataFrontmatterStore
    {
        if (self::$store === null) {
            self::$store = new MetadataFrontmatterStore();
        }
        return self::$store;
    }

    public function set(Metadata $metadata)
    {
        $key = $this->getArrayKey($metadata->getResource());
        $this->data[$key][$metadata->getName()] = $metadata->toStoreValue();
    }

    public function get(Metadata $metadata, $default = null)
    {
        $key = $this->getArrayKey($metadata->getResource());
        $value = $this->data[$key][$metadata->getName()];
        if ($value !== null) {
            return $value;
        }
        return $default;
    }

    public function persist()
    {
        throw new ExceptionComboRuntime("Not yet implemented", self::NAME);
    }

    public function isTextBased(): bool
    {
        return true;
    }

    public function __toString()
    {
        return self::NAME;
    }

    /**
     * @param ResourceCombo $page
     * @return string the key of the storage array
     */
    private function getArrayKey(ResourceCombo $page): string
    {
        return $page->getPath()->toString();
    }

    public function getJsonString(ResourceCombo $page): string
    {

        return self::toFrontmatterJsonString($this->getMetadataArrayForPage($page));

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
            throw new ExceptionComboRuntime("The generated frontmatter json is no a valid json");
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

    /**
     * @throws ExceptionCombo if the string is not a valid frontmatter
     */
    public function loadAsString(Page $page, $jsonString)
    {
        $jsonArray = self::frontMatterMatchToAssociativeArray($jsonString);
        if ($jsonArray === null) {
            throw new ExceptionCombo("The frontmatter is not valid");
        }
        $this->loadAsArray($page, $jsonArray);
    }

    public function getMetadataArrayForPage(Page $page): array
    {

        $key = $this->getArrayKey($page);
        return $this->data[$key];

    }


    public function reset()
    {
        $this->data = [];
    }

    public function toFrontmatterString(Page $page): string
    {
        $frontmatterStartTag = syntax_plugin_combo_frontmatter::START_TAG;
        $frontmatterEndTag = syntax_plugin_combo_frontmatter::END_TAG;
        $array = $this->getMetadataArrayForPage($page);
        $jsonEncode = self::toFrontmatterJsonString($array);

        return <<<EOF
$frontmatterStartTag
$jsonEncode
$frontmatterEndTag
EOF;


    }

    public function unloadForPage(Page $page): MetadataFrontmatterStore
    {
        $path = $this->getArrayKey($page);
        unset($this->data[$path]);
        return $this;
    }
}
