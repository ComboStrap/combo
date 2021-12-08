<?php


namespace ComboStrap;


class Json
{
    const FIELD_SEPARATOR = ",";
    const START_JSON = self::TYPE_OBJECT . DOKU_LF;
    const TYPE_OBJECT = "{";
    const PARENT_TYPE_ARRAY = "[";
    const TAB_SPACES_COUNTER = 4;
    const EXTENSION = "json";
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
     * @deprecated You should use the {@link MetadataFrontmatterStore::toFrontmatterJsonString()} instead
     * @return string
     */
    public function toFrontMatterFormat(): string
    {

        $jsonArray = $this->getJsonArray();
        return MetadataFrontmatterStore::toFrontmatterJsonString($jsonArray);

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
