<?php


namespace ComboStrap;


class Url
{

    /**
     * This URL encoding is mandatory for the {@link ml} function
     * when there is a width and use them not otherwise
     */
    const URL_ENCODED_AND = '&amp;';
    /**
     * Used in dokuwiki syntax
     */
    const URL_AND = "&";

    /**
     * @param $queryParameters
     * @return array of key value (if there is no value, the returned value is null)
     */
    public static function queryParametersToArray($queryParameters)
    {
        $parameters = [];
        // explode return an array with null if the string is empty
        if (!empty($queryParameters)) {
            $queryParameters = StringUtility::explodeAndTrim($queryParameters, self::URL_AND);
            foreach ($queryParameters as $parameter) {
                $equalCharacterPosition = strpos($parameter, "=");
                if ($equalCharacterPosition !== false) {
                    $parameterProp = explode("=", $parameter);
                    $key = $parameterProp[0];
                    $value = $parameterProp[1];
                    $parameters[$key] = $value;
                } else {
                    $parameters[$parameter] = null;
                }
            }
        }
        return $parameters;
    }
}
