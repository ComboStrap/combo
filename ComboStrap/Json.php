<?php


namespace ComboStrap;


class Json
{
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

    public function normalized()
    {
        $jsonArray = $this->getJsonArray();
        return json_encode($jsonArray, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    public function toFrontMatterFormat()
    {
        $jsonArray = $this->getJsonArray();
        if (sizeof($jsonArray) === 0) {
            return "{}";
        }
        $jsonString = "";
        $this->flatRecursiveEncoding($jsonArray, $jsonString);

        return $jsonString;
    }

    private function flatRecursiveEncoding($jsonValue, &$jsonString, $level = 0)
    {
        /**
         * Open the root object
         */
        if ($level === 0) {
            $jsonString = "{" . DOKU_LF;
        }
        if (is_array($jsonValue)) {
            foreach ($jsonValue as $key => $value) {
                $tab = str_repeat(" ", ($level + 1) * 4);
                $jsonString .= $tab;
                $jsonEncodedKey = json_encode($key);
                if (is_array($value)) {
                    $parentLevel = $level + 1;
                    $jsonString .= "$jsonEncodedKey: [" . DOKU_LF;
                    $this->flatRecursiveEncoding($value, $jsonString, $parentLevel);
                    $jsonString .= $tab . "]" . DOKU_LF;
                } else {
                    $jsonEncodedValue = json_encode($value);
                    $jsonString .= "$jsonEncodedKey:$jsonEncodedValue," . DOKU_LF;
                }
            }
        } else {
            $jsonEncodedValue = json_encode($jsonValue);
            $jsonString .= "$jsonEncodedValue," . DOKU_LF;
        }
        /**
         * Close the object
         */
        if ($level === 0) {
            $jsonString .= "}" . DOKU_LF;
        }
    }

    public static function createFromString($jsonString): Json
    {
        return new Json($jsonString);
    }

    /**
     * @return mixed
     */
    public function toJsonObject()
    {
        $jsonString = $this->getJsonString();
        return json_decode($jsonString);

    }

    public function toArray()
    {
        return $this->getJsonArray();

    }

    public function toString()
    {
        return $this->normalized();
    }

    private function getJsonString()
    {
        if ($this->jsonString === null && $this->jsonArray !== null) {
            $this->jsonString = json_encode($this->jsonArray, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }
        return $this->jsonString;
    }

    private function getJsonArray()
    {
        if ($this->jsonArray === null && $this->jsonString !== null) {
            $this->jsonArray = json_decode($this->jsonString, true);
        }
        return $this->jsonArray;
    }


}
