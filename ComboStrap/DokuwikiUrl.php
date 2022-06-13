<?php


namespace ComboStrap;

/**
 * Parse a wiki URL that you can found in the first part of a media
 *
 * This class takes care of the fact
 * that a color can have a # and of the special syntax for an image
 *
 * TODO: Merge with {@link MarkupRef}
 */
class DokuwikiUrl
{

    /**
     * In HTML (not in css)
     *
     * Because ampersands are used to denote HTML entities,
     * if you want to use them as literal characters, you must escape them as entities,
     * e.g.  &amp;.
     *
     * In HTML, Browser will do the translation for you if you give an URL
     * not encoded but testing library may not and refuse them
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
    const AMPERSAND_URL_ENCODED_FOR_HTML = '&amp;';

    /**
     * Used in dokuwiki syntax & in CSS attribute
     * (Css attribute value are then HTML encoded as value of the attribute)
     */
    const AMPERSAND_CHARACTER = "&";
    const ANCHOR_ATTRIBUTES = "anchor";

    private Url $url;

    /**
     * Url constructor.
     */
    public function __construct($urlString)
    {

        $this->url = Url::createEmpty();

        $urlString = trim($urlString);

        /**
         * Easy case when the URL is just a conform URL
         */
        if (media_isexternal($urlString)) {
            try {
                $this->url = Url::createFromString($urlString);
                return;
            } catch (ExceptionBadSyntax $e) {
                LogUtility::internalError("The url string is not valid URL ($urlString)");
            }
        }


        /**
         * Path
         */
        $questionMarkPosition = strpos($urlString, "?");
        $httpHostOrPath = $urlString;
        $queryStringAndAnchorOriginal = null;
        if ($questionMarkPosition !== false) {
            $httpHostOrPath = substr($urlString, 0, $questionMarkPosition);
            $queryStringAndAnchorOriginal = substr($urlString, $questionMarkPosition + 1);
        } else {
            // We may have only an anchor
            $hashTagPosition = strpos($urlString, "#");
            if ($hashTagPosition !== false) {
                $httpHostOrPath = substr($urlString, 0, $hashTagPosition);
                $this->url->setFragment(substr($urlString, $hashTagPosition + 1));
            }
        }

        /**
         * Scheme
         */
        if (link_isinterwiki($httpHostOrPath)) {
            $this->url->setScheme(InterWikiPath::scheme);
            $this->url->setPath($httpHostOrPath);
        } else {
            $this->url->setScheme(DokuFs::SCHEME);
            $this->url->setPath($httpHostOrPath);
        }


        /**
         * Parsing Query string if any
         */
        if ($queryStringAndAnchorOriginal !== null) {

            /**
             * The value $queryStringAndAnchorOriginal
             * is kept to create the original queryString
             * at the end if we found an anchor
             *
             * We parse token by token because we allow a hashtag for a hex color
             */
            $queryStringAndAnchorProcessing = $queryStringAndAnchorOriginal;
            while (strlen($queryStringAndAnchorProcessing) > 0) {

                /**
                 * Capture the token
                 * and reduce the text
                 */
                $questionMarkPos = strpos($queryStringAndAnchorProcessing, "&");
                if ($questionMarkPos !== false) {
                    $token = substr($queryStringAndAnchorProcessing, 0, $questionMarkPos);
                    $queryStringAndAnchorProcessing = substr($queryStringAndAnchorProcessing, $questionMarkPos + 1);
                } else {
                    $token = $queryStringAndAnchorProcessing;
                    $queryStringAndAnchorProcessing = "";
                }


                /**
                 * Sizing (wxh)
                 */
                $sizing = [];
                if (preg_match('/^([0-9]+)(?:x([0-9]+))?/', $token, $sizing)) {
                    $this->url->addQueryParameter(Dimension::WIDTH_KEY, $sizing[1]);
                    if (isset($sizing[2])) {
                        $this->url->addQueryParameter(Dimension::HEIGHT_KEY, $sizing[2]);
                    }
                    $token = substr($token, strlen($sizing[0]));
                    if ($token === "") {
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
                    $this->url->addQueryParameter(MediaLink::LINKING_KEY, $linkingValue);
                    $token = substr($token, strlen($linkingValue));
                    if ($token == "") {
                        // no anchor behind we continue
                        continue;
                    }
                }

                /**
                 * Cache
                 */
                $noCacheValue = FetchAbs::NOCACHE_VALUE;
                $found = preg_match('/^(' . $noCacheValue . ')/i', $token, $matches);
                if ($found) {
                    $this->url->addQueryParameter(FetchAbs::CACHE_KEY, $noCacheValue);
                    $token = substr($token, strlen($noCacheValue));
                    if ($token == "") {
                        // no anchor behind we continue
                        continue;
                    }
                }

                /**
                 * Anchor value after a single token case
                 */
                if (strpos($token, '#') === 0) {
                    $this->url->setFragment(substr($token, 1));
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

                /**
                 * Case of an anchor after a boolean attribute (ie without =)
                 * at the end
                 */
                $anchorPosition = strpos($key, '#');
                if ($anchorPosition !== false) {
                    $this->url->setFragment(substr($key, $anchorPosition + 1));
                    $key = substr($key, 0, $anchorPosition);
                }

                /**
                 * Test Anchor on the value
                 */
                if ($value != null) {
                    if (($countHashTag = substr_count($value, "#")) >= 3) {
                        LogUtility::msg("The value ($value) of the key ($key) for the link ($httpHostOrPath) has $countHashTag `#` characters and the maximum supported is 2.", LogUtility::LVL_MSG_ERROR);
                        continue;
                    }
                } else {
                    /**
                     * Boolean attribute
                     * (null does not make it)
                     */
                    $value = null;
                }

                $anchorPosition = false;
                $lowerCaseKey = strtolower($key);
                if ($lowerCaseKey === TextColor::CSS_ATTRIBUTE) {
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
                    $this->url->setFragment(substr($value, $anchorPosition + 1));
                    $value = substr($value, 0, $anchorPosition);
                }

                switch ($lowerCaseKey) {
                    case "w": // used in a link w=xxx
                        $this->url->addQueryParameter(Dimension::WIDTH_KEY, $value);
                        break;
                    case "h": // used in a link h=xxxx
                        $this->url->addQueryParameter(Dimension::HEIGHT_KEY, $value);
                        break;
                    default:
                        $this->url->addQueryParameter($key, $value);
                        break;
                }

            }


        }

    }


    public static function createFromUrl($dokuwikiUrl): DokuwikiUrl
    {
        return new DokuwikiUrl($dokuwikiUrl);
    }


    public function toUrl(): Url
    {
        return $this->url;
    }


}
