<?php


namespace ComboStrap;


use syntax_plugin_combo_frontmatter;

class MetadataFrontmatterStore extends MetadataSingleArrayStore
{

    const NAME = "frontmatter";
    const CANONICAL = self::NAME;


    /**
     * MetadataFrontmatterStore constructor.
     * @param ResourceCombo $page
     * @param array $data
     */
    public function __construct(ResourceCombo $page, array $data)
    {
        parent::__construct($page,$data);
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


    public function createFromArray(ResourceCombo $page, array $jsonArray): MetadataFrontmatterStore
    {
        return new MetadataFrontmatterStore($page, $jsonArray);
    }

    /**
     * @throws ExceptionCombo
     */
    public static function createFromFrontmatter($page, $frontmatter = null): MetadataFrontmatterStore
    {
        if($frontmatter===null){
            return new MetadataFrontmatterStore($page, []);
        }
        $jsonArray = self::frontMatterMatchToAssociativeArray($frontmatter);
        if ($jsonArray === null) {
            throw new ExceptionCombo("The frontmatter is not valid");
        }
        return new MetadataFrontmatterStore($page, $jsonArray);
    }




    public function __toString()
    {
        return self::NAME;
    }


    public function getJsonString(): string
    {

        return self::toFrontmatterJsonString($this->getData());

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




}
