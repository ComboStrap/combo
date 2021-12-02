<?php


namespace ComboStrap;


class Json
{
    const FIELD_SEPARATOR = ",";
    const START_JSON = self::TYPE_OBJECT . DOKU_LF;
    const TYPE_OBJECT = "{";
    const PARENT_TYPE_ARRAY = "[";
    const TAB_SPACES_COUNTER = 4;
    private $jsonString;
    /**
     * @var array
     */
    private $jsonArray;

    /**
     * Json constructor.
     */
    public function __construct($jsonValue)
    {
        if (is_array($jsonValue)) {
            $this->jsonArray = $jsonValue;
        } else {
            $this->jsonString = $jsonValue;
        }
    }

    public static function createEmpty(): Json
    {
        return new Json("");
    }

    public static function createFromArray(array $actual): Json
    {
        return new Json($actual);
    }

    public static function getValidationLink(string $json): string
    {
        return "See the errors it by clicking on <a href=\"https://jsonformatter.curiousconcept.com/?data=" . urlencode($json) . "\">this link</a>";
    }

    /**
     * Used to make diff
     * @return false|string
     */
    public function toNormalizedJsonString()
    {
        return json_encode($this->getJsonArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    /**
     * This formatting make the object on one line for a list of object
     * making the frontmatter compacter (one line, one meta)
     * @return string
     */
    public function toFrontMatterFormat(): string
    {
        $jsonArray = $this->getJsonArray();
        if (sizeof($jsonArray) === 0) {
            return "{}";
        }
        $jsonString = "";
        $this->flatRecursiveEncoding($jsonArray, $jsonString);

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

    private function flatRecursiveEncoding(array $jsonProperty, &$jsonString, $level = 0, $endOfFieldCharacter = DOKU_LF, $type = self::TYPE_OBJECT, $parentType = self::TYPE_OBJECT)
    {
        /**
         * Open the root object
         */
        if ($type === self::TYPE_OBJECT) {
            $jsonString .= "{";
        } else {
            $jsonString .= "[";
        }

        /**
         * Level indentation
         */
        $levelSpaceIndentation = str_repeat(" ", ($level + 1) * self::TAB_SPACES_COUNTER);

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
            if ($isFirstProperty && $parentType !== self::PARENT_TYPE_ARRAY) {
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
                    $childType = self::TYPE_OBJECT;
                    $childEndOField = "";
                } else {
                    /**
                     * Array
                     */
                    $jsonString .= "$jsonEncodedKey: ";
                    $childType = self::TYPE_OBJECT;
                    if ($value[0] !== null) {
                        $childType = self::PARENT_TYPE_ARRAY;
                    }
                    $childEndOField = $endOfFieldCharacter;
                }
                $this->flatRecursiveEncoding($value, $jsonString, $childLevel, $childEndOField, $childType, $type);

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
        $closingLevelSpaceIndentation = str_repeat(" ", $level * self::TAB_SPACES_COUNTER);
        if ($type === self::TYPE_OBJECT) {
            if ($parentType !== self::PARENT_TYPE_ARRAY) {
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

    public
    static function createFromString($jsonString): Json
    {
        return new Json($jsonString);
    }

    /**
     * @return mixed
     */
    public
    function toJsonObject()
    {
        $jsonString = $this->getJsonString();
        return json_decode($jsonString);

    }

    public
    function toArray()
    {
        return $this->getJsonArray();

    }

    public
    function toPrettyJsonString()
    {
        return $this->toNormalizedJsonString();
    }

    private
    function getJsonString()
    {
        if ($this->jsonString === null && $this->jsonArray !== null) {
            $this->jsonString = json_encode($this->jsonArray, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }
        return $this->jsonString;
    }

    private
    function getJsonArray()
    {
        if ($this->jsonArray === null && $this->jsonString !== null) {
            $this->jsonArray = json_decode($this->jsonString, true);
        }
        return $this->jsonArray;
    }

    public
    function toMinifiedJsonString()
    {
        if ($this->jsonString === null && $this->jsonArray !== null) {
            $this->jsonString = json_encode($this->jsonArray);
        }
        return $this->jsonString;
    }


}
