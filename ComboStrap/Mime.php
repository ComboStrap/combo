<?php


namespace ComboStrap;


use renderer_plugin_combo_analytics;

class Mime
{

    public const JSON = "application/json";
    public const HTML = "text/html";
    public const XHTML = "text/xhtml";
    const PLAIN_TEXT = "text/plain";
    public const SVG = "image/svg+xml";
    public const JAVASCRIPT = "text/javascript";
    const PNG = "image/png";
    const GIF = "image/gif";
    const JPEG = "image/jpeg";
    const BMP = "image/bmp";
    const WEBP = "image/webp";
    const CSS = "text/css";
    const MARKDOWN = "text/markdown";
    const PDF = "application/pdf";
    const BINARY_MIME = "application/octet-stream";
    public const RASTER_MIMES = [
        Mime::BMP,
        Mime::WEBP,
        Mime::JPEG,
        Mime::GIF,
        MIME::PNG
    ];

    const XML = "text/xml";
    const INSTRUCTIONS = "text/i";
    const META = "text/meta";
    const HANDLEBARS = "text/hbs";

    /**
     * @var array|null
     */
    private static ?array $knownTypes;

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

    /**
     * @throws ExceptionNotFound
     */
    public static function createFromExtension($extension): Mime
    {
        switch ($extension) {
            case FetcherSvg::EXTENSION:
                /**
                 * Svg is authorized when viewing but is not part
                 * of the {@link File::getKnownMime()}
                 */
                return new Mime(Mime::SVG);
            case "js":
                return new Mime(Mime::JAVASCRIPT);
            case renderer_plugin_combo_analytics::RENDERER_NAME_MODE:
            case Json::EXTENSION:
                return new Mime(Mime::JSON);
            case "md":
                return new Mime(Mime::MARKDOWN);
            case "txt":
                return new Mime(Mime::PLAIN_TEXT);
            case "xhtml":
                return new Mime(Mime::XHTML);
            case "html":
                return new Mime(Mime::HTML);
            case "png":
                return new Mime(Mime::PNG);
            case "css":
                return new Mime(Mime::CSS);
            case "jpg":
            case "jpeg":
                return new Mime(Mime::JPEG);
            case "webp":
                return new Mime(Mime::WEBP);
            case "bmp":
                return new Mime(Mime::BMP);
            case "gif":
                return new Mime(Mime::GIF);
            case "pdf":
                return new Mime(Mime::PDF);
            case MarkupRenderer::INSTRUCTION_EXTENSION:
                // text storage, array memory
                return new Mime(self::INSTRUCTIONS);
            case MarkupRenderer::METADATA_EXTENSION:
                // text storage, array memory
                return new Mime(self::META);
            case TemplateEngine::EXTENSION_HBS:
                // handlebars
                return new Mime(self::HANDLEBARS);
            default:
                $mtypes = getMimeTypes();
                $mimeString = $mtypes[$extension] ?? null;
                if ($mimeString === null) {
                    throw new ExceptionNotFound("No mime was found for the extension ($extension)");
                } else {
                    /**
                     * Delete the special dokuwiki character `!`
                     * that means that the media should be downloaded
                     */
                    if ($mimeString[0] === "!") {
                        $mimeString = substr($mimeString, 1);
                    }
                    return new Mime($mimeString);
                }

        }
    }

    public static function getJson(): Mime
    {
        try {
            return Mime::createFromExtension("json");
        } catch (ExceptionNotFound $e) {
            throw new ExceptionRuntime("Json is a known extension and should not throw. Error :{$e->getMessage()}");
        }
    }

    public static function getHtml(): Mime
    {
        try {
            return Mime::createFromExtension("html");
        } catch (ExceptionNotFound $e) {
            throw new ExceptionRuntime("Html is a known extension and should not throw. Error :{$e->getMessage()}");
        }
    }

    public static function getText(): Mime
    {
        try {
            return Mime::createFromExtension("txt");
        } catch (ExceptionNotFound $e) {
            throw new ExceptionRuntime("Txt is a known extension and should not throw. Error :{$e->getMessage()}");
        }
    }

    public static function getBinary(): Mime
    {
        return new Mime(self::BINARY_MIME);
    }

    public static function getXml()
    {
        return new Mime(self::XML);
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

    public function getExtension()
    {

        $secondPart = explode("/", $this->mime)[1];
        // case such as "image/svg+xml";
        return explode("+", $secondPart)[0];

    }

    public function isSupportedRasterImage(): bool
    {
        return in_array($this->mime, Mime::RASTER_MIMES);
    }


}
