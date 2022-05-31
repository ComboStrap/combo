<?php

namespace ComboStrap;

use dokuwiki\Cache\Cache;

class Vignette
{

    const CANONICAL = "page-vignette";


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

    public function __construct(Page $page)
    {
        $this->page = $page;
        $this->mime = Mime::create(Mime::PNG);
    }

    public static function createForPage(Page $page): Vignette
    {
        return new Vignette($page);
    }

    /**
     * @throws ExceptionBadArgument - if this is not an image
     * @throws ExceptionNotFound - if the mime was not found
     */
    public function setExtension(string $extension): Vignette
    {

        $this->mime = Mime::createFromExtension($extension);
        if (!$this->mime->isImage()) {
            throw new ExceptionBadArgument("The extension ($extension) is not an image");
        }
        return $this;
    }

    /**
     * @throws ExceptionBadState - if the extension is not supported
     */
    public function getPath(): LocalPath
    {
        $extension = $this->mime->getExtension();
        $cache = new Cache($this->page->getPath()->toPathString(), ".vignette.{$extension}");
        if (!$cache->useCache() || $this->useCache === false) {

            $width = 1200;
            $height = 600;
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
                imagefill($vignetteImageHandler,0,0,$whiteGdColor);

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
                    throw new ExceptionBadState("Error while getting the muted color. Error: {$e->getMessage()}", self::CANONICAL);
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
                    $lineToPrint = Iso8601Date::createFromDateTime($this->page->getModifiedTime())->formatLocale(null, $locale);
                } catch (ExceptionBadSyntax $e) {
                    // should not happen
                    LogUtility::errorIfDevOrTest("Error while formatting the modified date. Error: {$e->getMessage()}", self::CANONICAL);
                    $lineToPrint = $this->page->getModifiedTime()->format('Y-m-d H:i:s');
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
                    imageAlphaBlending($targetLogoHandler, true);
                    imageSaveAlpha($targetLogoHandler, true);
                    imagecopy($vignetteImageHandler, $targetLogoHandler, 950, 130, 0, 0, $targetLogoWidth, imagesy($targetLogoHandler));

                } catch (ExceptionNotFound $e) {
                    // no logo installed, mime not found, extension not supported
                    LogUtility::error("An error has occurred while adding the logo to the vignette. Error: {$e->getMessage()}");
                }

                /**
                 * Store
                 */
                switch ($extension) {
                    case "png":
                        if (!$gdInfo["PNG Support"]) {
                            throw new ExceptionBadState("The extension($extension) is not supported by the GD library", self::CANONICAL);
                        }
                        imagetruecolortopalette($vignetteImageHandler, false, 255);
                        imagepng($vignetteImageHandler, $cache->cache);
                        break;
                    case "jpg":
                    case "jpeg":
                        if (!$gdInfo["JPEG Support"]) {
                            throw new ExceptionBadState("The extension($extension) is not supported by the GD library", self::CANONICAL);
                        }
                        imagejpeg($vignetteImageHandler, $cache->cache);
                        break;
                    case "webp":
                        if (!$gdInfo["WebP Support"]) {
                            throw new ExceptionBadState("The extension($extension) is not supported by the GD library", self::CANONICAL);
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
                        throw new ExceptionBadState("The extension($extension) is unknown or not yet supported", self::CANONICAL);
                }

            } finally {
                imagedestroy($vignetteImageHandler);
            }

        }
        return LocalPath::createFromPath($cache->cache);
    }

    public function setUseCache(bool $false): Vignette
    {
        $this->useCache = $false;
        return $this;
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
                /**
                 * What the fuck ?
                 * First comment at https://www.php.net/manual/en/function.imagecreatefrompng.php
                 * If you're trying to load a translucent png-24 image but are finding an absence of transparency (like it's black), you need to enable alpha channel AND save the setting
                 */
                imageAlphaBlending($gdLogo, true);
                imageSaveAlpha($gdLogo, true);
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
