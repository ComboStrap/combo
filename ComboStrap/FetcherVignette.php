<?php /** @noinspection PhpComposerExtensionStubsInspection */

namespace ComboStrap;

use ComboStrap\Web\Url;
use ComboStrap\Web\UrlRewrite;

/**
 *
 * Vignette:
 * http://host/lib/exe/fetch.php?media=id:of:page.png&drive=page-vignette
 * where:
 *   * 'id:of:page' is the page wiki id
 *   * 'png' is the format (may be jpeg or webp)
 *
 * Example when running on Combo
 * http://combo.nico.lan/lib/exe/fetch.php?media=howto:getting_started:getting_started.png&drive=page-vignette
 * http://combo.nico.lan/lib/exe/fetch.php?media=howto:howto.webp&drive=page-vignette
 *
 *
 * Example/Inspiration in the real world:
 * https://lofi.limo/blog/images/write-html-right.png
 * https://opengraph.githubassets.com/6b85042cdc8e98725bd85a0e7b159c99104644fbf97402fded205ee4d2036ab9/ComboStrap/combo
 */
class FetcherVignette extends FetcherImage
{


    const CANONICAL = self::VIGNETTE_FETCHER_NAME;

    /**
     * For {@link UrlRewrite}, the property id
     * should be called media
     */
    const MEDIA_NAME_URL_ATTRIBUTE = "media";
    const PNG_EXTENSION = "png";
    const JPG_EXTENSION = "jpg";
    const JPEG_EXTENSION = "jpeg";
    const WEBP_EXTENSION = "webp";

    const VIGNETTE_FETCHER_NAME = "vignette";


    private ?MarkupPath $page = null;

    private Mime $mime;


    private string $buster;

    private WikiPath $pagePath;


    /**
     * @throws ExceptionNotFound - if the page does not exists
     * @throws ExceptionBadArgument - if the mime is not supported or the path of the page is not a wiki path
     */
    public static function createForPage(MarkupPath $page, Mime $mime = null): FetcherVignette
    {
        $fetcherVignette = new FetcherVignette();
        $fetcherVignette->setPage($page);
        if ($mime === null) {
            $mime = Mime::create(Mime::WEBP);
        }
        $fetcherVignette->setMime($mime);
        return $fetcherVignette;

    }

    /**
     *
     * @throws ExceptionBadArgument
     */
    public function getFetchPath(): LocalPath
    {

        $extension = $this->mime->getExtension();
        $cache = new FetcherCache($this);

        /**
         * Building the cache dependencies
         */
        try {
            $cache->addFileDependency($this->page->getPathObject())
                ->addFileDependency(ClassUtility::getClassPath($this));
        } catch (\ReflectionException $e) {
            // It should not happen but yeah
            LogUtility::internalError("The path of the actual class cannot be determined", self::CANONICAL);
        }

        /**
         * Can we use the cache ?
         */
        if ($cache->isCacheUsable()) {
            return LocalPath::createFromPathObject($cache->getFile());
        }

        $width = $this->getIntrinsicWidth();
        $height = $this->getIntrinsicHeight();

        /**
         * Don't use {@link imagecreate()} otherwise
         * we get color problem while importing the logo
         */
        $vignetteImageHandler = imagecreatetruecolor($width, $height);
        try {

            /**
             * Background
             * The first call to  {@link imagecolorallocate} fills the background color in palette-based images
             */
            $whiteGdColor = imagecolorallocate($vignetteImageHandler, 255, 255, 255);
            imagefill($vignetteImageHandler, 0, 0, $whiteGdColor);

            /**
             * Common variable
             */
            $margin = 80;
            $x = $margin;
            $normalFont = Font::getLiberationSansFontRegularPath()->toAbsoluteId();
            $boldFont = Font::getLiberationSansFontBoldPath()->toAbsoluteId();
            try {
                $mutedRgb = ColorRgb::createFromString("gray");
                $blackGdColor = imagecolorallocate($vignetteImageHandler, 0, 0, 0);
                $mutedGdColor = imagecolorallocate($vignetteImageHandler, $mutedRgb->getRed(), $mutedRgb->getGreen(), $mutedRgb->getBlue());
            } catch (ExceptionCompile $e) {
                // internal error, should not happen
                throw new ExceptionBadArgument("Error while getting the muted color. Error: {$e->getMessage()}", self::CANONICAL);
            }

            /**
             * Category
             */
            try {
                $parentPage = $this->page->getParent();
                $yCategory = 120;
                $categoryFontSize = 40;
                $lineToPrint = $parentPage->getNameOrDefault();
                imagettftext($vignetteImageHandler, $categoryFontSize, 0, $x, $yCategory, $mutedGdColor, $normalFont, $lineToPrint);
            } catch (ExceptionNotFound $e) {
                // No parent
            }


            /**
             * Title
             */
            $title = trim($this->page->getTitleOrDefault());
            $titleFontSize = 55;
            $yTitleStart = 210;
            $yTitleActual = $yTitleStart;
            $lineSpace = 25;
            $words = explode(" ", $title);
            $maxCharacterByLine = 20;
            $actualLine = "";
            $lineCount = 0;
            $maxNumberOfLines = 3;
            $break = false;
            foreach ($words as $word) {
                $actualLength = strlen($actualLine);
                if ($actualLength + strlen($word) > $maxCharacterByLine) {
                    $lineCount = $lineCount + 1;
                    $lineToPrint = $actualLine;
                    if ($lineCount >= $maxNumberOfLines) {
                        $lineToPrint = $actualLine . "...";
                        $actualLine = "";
                        $break = true;
                    } else {
                        $actualLine = $word;
                    }
                    imagettftext($vignetteImageHandler, $titleFontSize, 0, $x, $yTitleActual, $blackGdColor, $boldFont, $lineToPrint);
                    $yTitleActual = $yTitleActual + $titleFontSize + $lineSpace;
                    if ($break) {
                        break;
                    }
                } else {
                    if ($actualLine === "") {
                        $actualLine = $word;
                    } else {
                        $actualLine = "$actualLine $word";
                    }
                }
            }
            if ($actualLine !== "") {
                imagettftext($vignetteImageHandler, $titleFontSize, 0, $x, $yTitleActual, $blackGdColor, $boldFont, $actualLine);
            }

            /**
             * Date
             */
            $yDate = $yTitleStart + 3 * ($titleFontSize + $lineSpace) + 2 * $lineSpace;
            $dateFontSize = 30;
            $mutedGdColor = imagecolorallocate($vignetteImageHandler, $mutedRgb->getRed(), $mutedRgb->getGreen(), $mutedRgb->getBlue());
            $locale = Locale::createForPage($this->page)->getValueOrDefault();
            try {
                $modifiedTimeOrDefault = $this->page->getModifiedTimeOrDefault();
            } catch (ExceptionNotFound $e) {
                LogUtility::errorIfDevOrTest("Error while getting the modified date. Error: {$e->getMessage()}", self::CANONICAL);
                $modifiedTimeOrDefault = new \DateTime();
            }
            try {
                $lineToPrint = Iso8601Date::createFromDateTime($modifiedTimeOrDefault)->formatLocale(null, $locale);
            } catch (ExceptionBadSyntax $e) {
                // should not happen
                LogUtility::errorIfDevOrTest("Error while formatting the modified date. Error: {$e->getMessage()}", self::CANONICAL);
                $lineToPrint = $modifiedTimeOrDefault->format('Y-m-d H:i:s');
            }
            imagettftext($vignetteImageHandler, $dateFontSize, 0, $x, $yDate, $mutedGdColor, $normalFont, $lineToPrint);

            /**
             * Logo
             */
            try {

                $imagePath = Site::getLogoAsRasterImage()->getSourcePath();
                $gdOriginalLogo = $this->getGdImageHandler($imagePath);
                $targetLogoWidth = 120;
                $targetLogoHandler = imagescale($gdOriginalLogo, $targetLogoWidth);
                imagecopy($vignetteImageHandler, $targetLogoHandler, 950, 130, 0, 0, $targetLogoWidth, imagesy($targetLogoHandler));

            } catch (ExceptionNotFound $e) {
                // no logo installed, mime not found, extension not supported
                LogUtility::warning("The vignette could not be created with your logo because of the following error: {$e->getMessage()}");
            }

            /**
             * Store
             */
            $fileStringPath = $cache->getFile()->toAbsolutePath()->toAbsoluteId();
            switch ($extension) {
                case self::PNG_EXTENSION:
                    imagetruecolortopalette($vignetteImageHandler, false, 255);
                    imagepng($vignetteImageHandler, $fileStringPath);
                    break;
                case self::JPG_EXTENSION:
                case self::JPEG_EXTENSION:
                    imagejpeg($vignetteImageHandler, $fileStringPath);
                    break;
                case self::WEBP_EXTENSION:
                    /**
                     * To True Color to avoid:
                     * `
                     * Fatal error: Palette image not supported by webp
                     * `
                     */
                    imagewebp($vignetteImageHandler, $fileStringPath);
                    break;
                default:
                    LogUtility::internalError("The possible mime error should have been caught in the setter");
            }

        } finally {
            imagedestroy($vignetteImageHandler);
        }


        return $cache->getFile();
    }

    public function setUseCache(bool $false): FetcherVignette
    {
        $this->useCache = $false;
        return $this;
    }

    public function getIntrinsicWidth(): int
    {
        return 1200;
    }

    public function getIntrinsicHeight(): int
    {
        return 600;
    }


    /**
     * @throws ExceptionNotFound - unknown mime or unknown extension
     */
    private function getGdImageHandler(WikiPath $imagePath)
    {
        // the gd function needs a local path, not a wiki path
        $imagePath = $imagePath->toLocalPath();
        $extension = FileSystems::getMime($imagePath)->getExtension();

        switch ($extension) {
            case self::PNG_EXTENSION:
                return imagecreatefrompng($imagePath->toAbsoluteId());
            case self::JPG_EXTENSION:
            case self::JPEG_EXTENSION:
                return imagecreatefromjpeg($imagePath->toAbsoluteId());
            case self::WEBP_EXTENSION:
                return imagecreatefromwebp($imagePath->toAbsoluteId());
            default:
                throw new ExceptionNotFound("Bad mime should have been caught by the setter");
        }

    }


    function getFetchUrl(Url $url = null): Url
    {

        $vignetteNameValue = $this->pagePath->getWikiId() . "." . $this->mime->getExtension();
        return parent::getFetchUrl($url)
            ->addQueryParameter(self::MEDIA_NAME_URL_ATTRIBUTE, $vignetteNameValue);

    }


    function getBuster(): string
    {
        return $this->buster;
    }


    public function getMime(): Mime
    {
        return $this->mime;
    }

    /**
     * @throws ExceptionBadArgument
     * @throws ExceptionNotFound
     */
    public function buildFromTagAttributes(TagAttributes $tagAttributes): FetcherVignette
    {

        $vignette = $tagAttributes->getValueAndRemove(self::MEDIA_NAME_URL_ATTRIBUTE);
        if ($vignette === null && $this->page === null) {
            throw new ExceptionBadArgument("The vignette query property is mandatory when the vignette was created without page.");
        }

        if ($vignette !== null) {
            $lastPoint = strrpos($vignette, ".");
            $extension = substr($vignette, $lastPoint + 1);
            $wikiId = substr($vignette, 0, $lastPoint);
            $this->setPage(MarkupPath::createMarkupFromId($wikiId));
            if (!FileSystems::exists($this->page->getPathObject())) {
                throw new ExceptionNotFound("The page does not exists");
            }
            try {
                $this->setMime(Mime::createFromExtension($extension));
            } catch (ExceptionNotFound $e) {
                throw new ExceptionBadArgument("The vignette mime is unknown. Error: {$e->getMessage()}");
            }
        }

        parent::buildFromTagAttributes($tagAttributes);
        return $this;

    }


    public function getFetcherName(): string
    {
        return self::VIGNETTE_FETCHER_NAME;
    }

    /**
     * @throws ExceptionNotFound
     * @throws ExceptionBadArgument - if the markup path is not
     */
    public function setPage(MarkupPath $page): FetcherVignette
    {
        $this->page = $page;
        $path = $this->page->getPathObject();
        if (!($path instanceof WikiPath)) {
            if ($path instanceof LocalPath) {
                $path = $path->toWikiPath();
            } else {
                throw new ExceptionBadArgument("The path of the markup file is not a wiki path and could not be transformed.");
            }
        }
        $this->pagePath = $path;
        $this->buster = FileSystems::getCacheBuster($path);
        return $this;
    }

    /**
     * @throws ExceptionBadArgument
     */
    public function setMime(Mime $mime): FetcherVignette
    {
        $this->mime = $mime;
        $gdInfo = gd_info();
        $extension = $mime->getExtension();
        switch ($extension) {
            case self::PNG_EXTENSION:
                if (!$gdInfo["PNG Support"]) {
                    throw new ExceptionBadArgument("The extension ($extension) is not supported by the GD library", self::CANONICAL);
                }
                break;
            case self::JPG_EXTENSION:
            case self::JPEG_EXTENSION:
                if (!$gdInfo["JPEG Support"]) {
                    throw new ExceptionBadArgument("The extension ($extension) is not supported by the GD library", self::CANONICAL);
                }
                break;
            case self::WEBP_EXTENSION:
                if (!$gdInfo["WebP Support"]) {
                    throw new ExceptionBadArgument("The extension ($extension) is not supported by the GD library", self::CANONICAL);
                }
                break;
            default:
                throw new ExceptionBadArgument("The mime ($mime) is not supported");
        }
        return $this;
    }

    public function getLabel(): string
    {
        return ResourceName::getFromPath($this->pagePath);
    }
}
