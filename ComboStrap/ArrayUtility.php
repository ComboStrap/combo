<?php


namespace ComboStrap;


use DateTime;

class ArrayUtility
{

    /**
     * Print recursively an array as an HTML list
     * @param array $toPrint
     * @param string $content - [Optional] - append to this variable if given
     * @return string - an array as an HTML list or $content if given as variable
     */
    public static function formatAsHtmlList(array $toPrint, &$content = ""): string
    {
        /**
         * Sort it on the key
         */
        ksort($toPrint);

        $content .= '<ul>';
        foreach ($toPrint as $key => $value) {
            if (is_array($value)) {
                $content .= '<li>' . $key . ' : ';
                self::formatAsHtmlList($value, $content);
                $content .= '</li>';
            } else {
                if (preg_match('/date|created|modified/i', $key) && is_numeric($value)) {
                    $value =  date(DATE_ATOM, $value);
                }
                $stringValue = var_export($value, true);
                $content .= '<li>' . $key . ' : ' . $stringValue . '</li>';
            }
        }
        $content .= '</ul>';
        return $content;
    }

    /**
     * Delete from an array recursively key
     * that match the regular expression
     * @param array $array
     * @param $pattern
     */
    public static function filterArrayByKey(array &$array, $pattern)
    {
        foreach ($array as $key => &$value) {
            if (preg_match('/' . $pattern . '/i', $key)) {
                unset($array[$key]);
            }
            if (is_array($value)) {
                self::filterArrayByKey($value, $pattern);
            }
        }
    }

    public static function addIfNotSet(array &$array, $key, $value)
    {
        if (!isset($array[$key])) {
            $array[$key] = $value;
        }
    }

    /**
     * @param $array
     * @return int|string|null - the last key of an array
     * There is a method {@link array_key_last()} but this is only available on 7.3
     * This function will also reset the internal pointer
     */
    public static function array_key_last(&$array)
    {
        // move the internal pointer to the end of the array
        end($array);
        $key = key($array);
        // By default, the pointer is on the first element
        reset($array);
        return $key;
    }

    /**
     * @param array $flatArray - the returned flat array
     * @param array|string $value - the value to return as a flat array
     */
    public static function toFlatArray(array &$flatArray, $value)
    {
        if (is_array($value)) {
            foreach ($value as $subImageValue) {
                self::toFlatArray($flatArray, $subImageValue);
            }
        } else {
            $flatArray[] = $value;
        }
    }

    /**
     * @param array $default
     * @param array $overwrite
     * @return array
     */
    public static function mergeByValue(array $default, array $overwrite): array
    {
        return array_merge($default,$overwrite);
    }

    public static function formatAsString(array $array): string
    {
        return Json::createFromArray($array)->toPrettyJsonString();
    }
}
