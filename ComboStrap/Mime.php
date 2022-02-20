<?php


namespace ComboStrap;


class Mime
{

    public const JSON = "application/json";
    public const HTML = "text/html";
    public const XHTML = self::HTML;
    const PLAIN_TEXT = "text/plain";
    const HEADER_CONTENT_TYPE = "Content-Type";
    public const SVG = "image/svg+xml";
    public const JAVASCRIPT = "text/javascript";
    const PNG = "image/png";
    const GIF = "image/gif";
    const JPEG = "image/jpeg";
    const BMP = "image/bmp";
    const WEBP = "image/webp";
    const CSS = "text/css";
    /**
     * @var array|null
     */
    private static $knownTypes;

    /**
     * @var string
     */
    private $mime;

    /**
     * Mime constructor.
     */
    public function __construct(string $mime)
    {
        if (trim($mime) === "") {
            LogUtility::msg("The mime should not be an empty string");
        }
        $this->mime = $mime;
    }

    public static function create(string $mime): Mime
    {
        return new Mime($mime);
    }

    public function __toString()
    {
        return $this->mime;
    }

    public function isKnown(): bool
    {

        if (self::$knownTypes === null) {
            self::$knownTypes = getMimeTypes();
        }
        return array_search($this->mime, self::$knownTypes) !== false;

    }

    public function isTextBased(): bool
    {
        if ($this->getFirstPart() === "text") {
            return true;
        }
        if (in_array($this->mime, [self::SVG, self::JSON])) {
            return true;
        }
        return false;
    }

    private function getFirstPart()
    {
        return explode("/", $this->mime)[0];
    }

    public function isImage(): bool
    {
        return substr($this->mime, 0, 5) === 'image';
    }

    public function toString(): string
    {
        return $this->__toString();
    }


}
