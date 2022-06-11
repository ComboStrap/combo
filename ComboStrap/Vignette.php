<?php

namespace ComboStrap;

use dokuwiki\Cache\Cache;

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
class Vignette extends ImageRasterFetch
{

    const CANONICAL = "page-vignette";
    public const DRIVE = "page-vignette";


    /**
     * @var Page
     */
    private $page;
    /**
     * @var Mime
     */
    private $mime;
    /**
     * @var bool
     */
    private $useCache;


    /**
     *
     * @throws ExceptionBadArgument
     */
    public function __construct(Page $page, Mime $mime = null)
    {
        $this->page = $page;
        $this->mime = $mime;
        if ($mime === null) {
            $this->mime = Mime::create(Mime::WEBP);
        }
        $path = $this->getPhysicalPath();
        parent::__construct($path);
    }

    /**
     * @throws ExceptionBadArgument - if the mime is not supported
     */
    public static function createForPage(Page $page, Mime $mime = null): Vignette
    {
        return new Vignette($page, $mime);
    }


    /**
     * @throws ExceptionBadArgument - if the vignette extension is not supported
     */
    public function getPhysicalPath(): LocalPath
    {

        $extension = $this->mime->getExtension();
        $cache = new Cache($this->page->getPath()->toPathString(), ".vignette.{$extension}");

        /**
         * Building the cache dependencies
         */
        $fileDependencies = [
            $this->page->getPath()->toLocalPath()->toPathString(),
            PluginUtility::getPluginInfoFile()->toPathString()
        ];
        try {
            $fileDependencies[] = ClassUtility::getClassPath($this)->toPathString();
        } catch (\ReflectionException $e) {
            // It should not happen but yeah
            LogUtility::error("The path of the actual class cannot be determined", self::CANONICAL);
        }

        /**
         * Can we use the cache ?
         */
        if ($cache->useCache(['files' => $fileDependencies]) && $this->useCache === true) {
            return LocalPath::createFromPath($cache->cache);
        }

        try {
            $width = $this->getIntrinsicWidth();
            $height = $this->getIntrinsicHeight();
        } catch (ExceptionCompile $e) {
            throw new ExceptionRuntime("Internal error. Width and height of a vignette could not be known");
        }

        /**
         * Don't use {@link imagecreate()} otherwise
         * we get color problem while importing the logo
         */
        $vignetteImageHandler = imagecreatetruecolor($width, $height);
        try {

            $gdInfo = gd_info();

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
            $normalFont = Font::getLiberationSansFontRegularPath()->toPathString();
            $boldFont = Font::getLiberationSansFontBoldPath()->toPathString();
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
            $parentPage = $this->page->getParentPage();
            if ($parentPage !== null) {
                $yCategory = 120;
                $categoryFontSize = 40;
                $lineToPrint = $parentPage->getNameOrDefault();
                imagettftext($vignetteImageHandler, $categoryFontSize, 0, $x, $yCategory, $mutedGdColor, $normalFont, $lineToPrint);
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
                $imagePath = Site::getLogoAsRasterImage()->getPath();
                if ($imagePath instanceof DokuPath) {
                    $imagePath = $imagePath->toLocalPath();
                }
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
            switch ($extension) {
                case "png":
                    if (!$gdInfo["PNG Support"]) {
                        throw new ExceptionBadArgument("The extension ($extension) is not supported by the GD library", self::CANONICAL);
                    }
                    imagetruecolortopalette($vignetteImageHandler, false, 255);
                    imagepng($vignetteImageHandler, $cache->cache);
                    break;
                case "jpg":
                case "jpeg":
                    if (!$gdInfo["JPEG Support"]) {
                        throw new ExceptionBadArgument("The extension ($extension) is not supported by the GD library", self::CANONICAL);
                    }
                    imagejpeg($vignetteImageHandler, $cache->cache);
                    break;
                case "webp":
                    if (!$gdInfo["WebP Support"]) {
                        throw new ExceptionBadArgument("The extension ($extension) is not supported by the GD library", self::CANONICAL);
                    }
                    /**
                     * To True Color to avoid:
                     * `
                     * Fatal error: Palette image not supported by webp
                     * `
                     */
                    imagewebp($vignetteImageHandler, $cache->cache);
                    break;
                default:
                    throw new ExceptionBadArgument("The extension ($extension) is unknown or not yet supported", self::CANONICAL);
            }

        } finally {
            imagedestroy($vignetteImageHandler);
        }


        return LocalPath::createFromPath($cache->cache);
    }

    public function setUseCache(bool $false): Vignette
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
    private function getGdImageHandler(Path $imagePath)
    {
        $extension = FileSystems::getMime($imagePath)->getExtension();

        switch ($extension) {
            case "png":
                $gdLogo = imagecreatefrompng($imagePath->toPathString());
                break;
            case "jpg":
            case "jpeg":
                $gdLogo = imagecreatefromjpeg($imagePath->toPathString());
                break;
            case "webp":
                $gdLogo = imagecreatefromwebp($imagePath->toPathString());
                break;
            default:
                throw new ExceptionNotFound("Extension ($extension) is not a supported image format to load as Gd image", self::CANONICAL);
        }
        return $gdLogo;
    }


}
