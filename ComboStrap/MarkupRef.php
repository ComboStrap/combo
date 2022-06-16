<?php

namespace ComboStrap;

use syntax_plugin_combo_variable;

class MarkupRef
{
    public const WINDOWS_SHARE_URI = 'windowsShare';
    public const LOCAL_URI = 'local';
    public const EMAIL_URI = 'email';
    public const WEB_URI = 'external';
    /**
     * Type of link
     */
    public const INTERWIKI_URI = 'interwiki';
    public const WIKI_URI = 'internal';
    public const VARIABLE_URI = 'internal_template';

    /**
     * The type of markup ref (ie media or link)
     */
    private string $type;
    const MEDIA_TYPE = "media";
    const LINK_TYPE = "link";

    private string $refScheme;
    public const EXTERNAL_MEDIA_CALL_NAME = "external";


    private string $ref;
    private Url $url;

    private ?DokuPath $path;


    /**
     * @throws ExceptionBadSyntax
     * @throws ExceptionBadArgument
     */
    public function __construct($ref, $type)
    {
        $this->ref = $ref;
        $this->type = $type;

        $this->url = Url::createEmpty();

        $ref = trim($ref);

        /**
         * Email validation pattern
         * E-Mail (pattern below is defined in inc/mail.php)
         *
         * Example:
         * [[support@combostrap.com?subject=hallo world]]
         * [[support@combostrap.com]]
         */
        $emailRfc2822 = "0-9a-zA-Z!#$%&'*+/=?^_`{|}~-";
        $emailPattern = '[' . $emailRfc2822 . ']+(?:\.[' . $emailRfc2822 . ']+)*@(?i:[0-9a-z][0-9a-z-]*\.)+(?i:[a-z]{2,63})';
        if (preg_match('<' . $emailPattern . '>', $ref)) {
            $this->refScheme = self::EMAIL_URI;
            $this->url = Url::createFromString("mailto:$ref");
            return;
        }

        /**
         * Case when the URL is just a full conform URL
         *
         * Example: `https://` or `ftp://`
         *
         * Other scheme are not yet recognized
         * because it can also be a wiki id
         * For instance, `mailto:` is also a valid page
         *
         * same as {@link media_isexternal()}  check only http / ftp scheme
         */
        if (preg_match('#^([a-z0-9\-.+]+?)://#i', $ref)) {
            try {
                $this->url = Url::createFromString($ref);
                $this->refScheme = self::WEB_URI;
                return;
            } catch (ExceptionBadSyntax $e) {
                throw new ExceptionBadSyntax("The url string is not valid URL ($ref)");
            }
        }

        /**
         * Windows share link
         */
        if (preg_match('/^\\\\\\\\[^\\\\]+?\\\\/u', $ref)) {
            $this->refScheme = self::WINDOWS_SHARE_URI;
            $this->url = LocalPath::createFromPath($ref)->getUrl();
            return;
        }

        /**
         * Only Fragment (also known as local link)
         */
        if (preg_match('!^#.+!', $ref)) {
            $this->refScheme = self::LOCAL_URI;
            $this->url = Url::createEmpty()->setFragment($ref);
            return;
        }

        /**
         * Interwiki ?
         */
        if (preg_match('/^[a-zA-Z0-9\.]+>/u', $ref)) {

            $this->refScheme = MarkupRef::INTERWIKI_URI;

            $interWikiPosition = strpos($ref, ">");

            $this->wiki = strtolower(substr($ref, 0, $interWikiPosition));
            $refProcessing = substr($refProcessing, $interWikiPosition + 1);
            $this->ref = $ref;
            return;

        }


        /**
         * It can be a link with a ref template
         */
        if (syntax_plugin_combo_variable::isVariable($ref)) {
            $this->refScheme = MarkupRef::VARIABLE_URI;
            return;
        }

        /**
         * Doku Path
         * We parse it
         */
        $this->refScheme = MarkupRef::WIKI_URI;
        $this->url = Url::createEmpty();
        $questionMarkPosition = strpos($ref, "?");
        $httpHostOrPath = $ref;
        $queryStringAndAnchorOriginal = null;
        if ($questionMarkPosition !== false) {
            $httpHostOrPath = substr($ref, 0, $questionMarkPosition);
            $queryStringAndAnchorOriginal = substr($ref, $questionMarkPosition + 1);
        } else {
            // We may have only an anchor
            $hashTagPosition = strpos($ref, "#");
            if ($hashTagPosition !== false) {
                $httpHostOrPath = substr($ref, 0, $hashTagPosition);
                $this->url->setFragment(substr($ref, $hashTagPosition + 1));
            }
        }

        /**
         * Scheme
         */

        /**
         * We transform it as if it was a fetch URL
         */
        $this->refScheme = DokuFs::SCHEME;
        switch ($type) {
            case self::MEDIA_TYPE:
                $this->path = DokuPath::createMediaPathFromId($httpHostOrPath);
                break;
            case self::LINK_TYPE:
                $this->path = DokuPath::createPagePathFromId($httpHostOrPath);
                break;
            default:
                throw new ExceptionBadArgument("The ref type ($type) is unknown");
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
                    $this->url->addQueryParameter(MediaMarkup::LINKING_KEY, $linkingValue);
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

    public
    static function createMediaFromRef($refProcessing): MarkupRef
    {
        return new MarkupRef($refProcessing, self::MEDIA_TYPE);
    }

    public
    static function createLinkFromRef($refProcessing): MarkupRef
    {
        return new MarkupRef($refProcessing, self::LINK_TYPE);
    }

    public
    function getUrl(): Url
    {
        return $this->url;
    }

    /**
     * @throws ExceptionNotFound
     */
    public
    function getPath(): DokuPath
    {
        if ($this->path === null) {
            throw new ExceptionNotFound("No path was found");
        }
        return $this->path;
    }

    public
    function getRef(): string
    {
        return $this->ref;
    }
}
