<?php


namespace ComboStrap;


class Url
{

    /**
     * In HTML (not in css)
     * Because ampersands are used to denote HTML entities,
     * if you want to use them as literal characters, you must escape them as entities,
     * e.g.  &amp;.
     *
     * This URL encoding is mandatory for the {@link ml} function
     * when there is a width and use them not otherwise
     *
     * Thus, if you want to link to:
     * http://images.google.com/images?num=30&q=larry+bird
     * you need to encode (ie pass this parameter to the {@link ml} function:
     * http://images.google.com/images?num=30&amp;q=larry+bird
     *
     * https://daringfireball.net/projects/markdown/syntax#autoescape
     *
     */
    const URL_ENCODED_AND = '&amp;';

    /**
     * Used in dokuwiki syntax & in CSS attribute
     * (Css attribute value are then HTML encoded as value of the attribute)
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
