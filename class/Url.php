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
    const ANCHOR_ATTRIBUTES = "anchor";

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

    /**
     * Parse a dokuwiki URL
     *
     * This function takes care of the
     * fact that a color can have a #
     * and of the special syntax for an image
     * @param $url
     * @return array
     * TODO ? return an URL object ?
     */
    public static function parseToArray($url)
    {

        $attributes =[];

        /**
         * Path
         */
        $questionMarkPosition = strpos($url, "?");
        $path = $url;
        $queryStringAndAnchor = null;
        if ($questionMarkPosition !== false) {
            $path = substr($url, 0, $questionMarkPosition);
            $queryStringAndAnchor = substr($url, $questionMarkPosition + 1);
        } else {
            // We may have only an anchor
            $hashTagPosition = strpos($url, "#");
            if ($hashTagPosition !== false) {
                $path = substr($url, 0, $hashTagPosition);
                $attributes[self::ANCHOR_ATTRIBUTES] = substr($url, $hashTagPosition + 1);
            }
        }
        $attributes[DokuPath::PATH_ATTRIBUTE] = $path;



        /**
         * Parsing Query string if any
         */
        if ($queryStringAndAnchor !== null) {

            while (strlen($queryStringAndAnchor) > 0) {

                /**
                 * Capture the token
                 * and reduce the text
                 */
                $questionMarkPos = strpos($queryStringAndAnchor, "&");
                if ($questionMarkPos !== false) {
                    $token = substr($queryStringAndAnchor, 0, $questionMarkPos);
                    $queryStringAndAnchor = substr($queryStringAndAnchor, $questionMarkPos + 1);
                } else {
                    $token = $queryStringAndAnchor;
                    $queryStringAndAnchor = "";
                }


                /**
                 * Sizing (wxh)
                 */
                $sizing = [];
                if (preg_match('/^([0-9]+)(?:x([0-9]+))?/', $token, $sizing)) {
                    $attributes[Dimension::WIDTH_KEY] = $sizing[1];
                    if (isset($sizing[2])) {
                        $attributes[Dimension::HEIGHT_KEY] = $sizing[2];
                    }
                    $token = substr($token, strlen($sizing[0]));
                    if ($token == "") {
                        // no anchor behind we continue
                        continue;
                    }
                }

                /**
                 * Linking
                 */
                $found = preg_match('/^(nolink|direct|linkonly|details)/i', $token, $matches);
                if ($found) {
                    $linkingValue = $matches[1];
                    $attributes[MediaLink::LINKING_KEY]=$linkingValue;
                    $token = substr($token, strlen($linkingValue));
                    if ($token == "") {
                        // no anchor behind we continue
                        continue;
                    }
                }

                /**
                 * Cache
                 */
                $found = preg_match('/^(nocache)/i', $token, $matches);
                if ($found) {
                    $cacheValue = "nocache";
                    $attributes[CacheMedia::CACHE_KEY]=$cacheValue;
                    $token = substr($token, strlen($cacheValue));
                    if ($token == "") {
                        // no anchor behind we continue
                        continue;
                    }
                }

                /**
                 * Anchor value after a single token case
                 */
                if (strpos($token, '#') === 0) {
                    $attributes[self::ANCHOR_ATTRIBUTES] = substr($token, 1);
                    continue;
                }

                /**
                 * Key, value
                 * explode to the first `=`
                 * in the anchor value, we can have one
                 *
                 * Ex with media.pdf#page=31
                 */
                list($key, $value) = explode("=", $token, 2);
                $lowerCaseKey = strtolower($key);

                /**
                 * Anchor
                 */
                if (($countHashTag = substr_count($value, "#")) >= 3) {
                    LogUtility::msg("The value ($value) of the key ($key) for the image ($path) has $countHashTag `#` characters and the maximum supported is 2.", LogUtility::LVL_MSG_ERROR, self::CANONICAL);
                    continue;
                }

                $anchorPosition = false;
                if ($lowerCaseKey === "color") {
                    /**
                     * Special case when color has one color value as hexadecimal #
                     * and the hashtag
                     */
                    if (strpos($value, '#') == 0) {
                        if (substr_count($value, "#") >= 2) {

                            /**
                             * The last one
                             */
                            $anchorPosition = strrpos($value, '#');
                        }
                        // no anchor then
                    } else {
                        // a color that is not hexadecimal can have an anchor
                        $anchorPosition = strpos($value, "#");
                    }
                } else {
                    // general case
                    $anchorPosition = strpos($value, "#");
                }
                if ($anchorPosition !== false) {
                    $attributes[self::ANCHOR_ATTRIBUTES] = substr($value, $anchorPosition + 1);
                    $value = substr($value, 0, $anchorPosition);
                }

                switch ($lowerCaseKey) {
                    case "w": // used in a link w=xxx
                        $attributes[Dimension::WIDTH_KEY] = $value;
                        break;
                    case "h": // used in a link h=xxxx
                        $attributes[Dimension::HEIGHT_KEY] = $value;
                        break;
                    default:
                        $attributes[$key] = $value;
                }


            }
        }
        return $attributes;
    }
}
