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

            $imageHandler = imagecreate(1200, 600);
            try {

                $gdInfo = gd_info();
                /**
                 * Background
                 * The first call to  {@link imagecolorallocate} fills the background color in palette-based images
                 */
                imagecolorallocate($imageHandler, 255, 255, 255);

                /**
                 * Title
                 */
                $black = imagecolorallocate($imageHandler, 0, 0, 0);
                $string = $this->page->getTitleOrDefault();
                $fontSize = 30;
                $x = (imagesx($imageHandler) - 20 * strlen($string)) / 2;
                $y = 300;

                /**
                 * Font
                 * https://github.com/dompdf/php-font-lib
                 *
                 * linux: /usr/share/fonts or ~/.fonts
                 * UBUNTU_XFSTT="/usr/share/fonts/truetype/"
                 * RHL52_XFS="/usr/X11R6/lib/X11/fonts/ttfonts/"
                 * RHL6_XFSTT="/usr/X11R6/lib/X11/fonts/"
                 * DEBIAN_XFSTT="/usr/share/fonts/truetype/"
                 *
                 */
                $assignment = 'GDFONTPATH=' . realpath('.');
                putenv($assignment);
                $path = LocalPath::createFromPath('c:\windows\fonts\Arial.ttf');
                $fontFilename = $path->toPathString();
                $fontFilename = "Arial";
                imagettftext($imageHandler, $fontSize, 0, $x, $y, $black, $fontFilename, $string);

                /**
                 * Store
                 */
                switch ($extension) {
                    case "png":
                        if (!$gdInfo["PNG Support"]) {
                            throw new ExceptionBadState("The extension($extension) is not supported by the GD library", self::CANONICAL);
                        }
                        imagepng($imageHandler, $cache->cache);
                        break;
                    case "jpg":
                    case "jpeg":
                        if (!$gdInfo["JPEG Support"]) {
                            throw new ExceptionBadState("The extension($extension) is not supported by the GD library", self::CANONICAL);
                        }
                        imagejpeg($imageHandler, $cache->cache);
                        break;
                    case "webp":
                        if (!$gdInfo["WebP Support"]) {
                            throw new ExceptionBadState("The extension($extension) is not supported by the GD library", self::CANONICAL);
                        }
                        /**
                         * To True Color to avoid:
                         * `
                         * Fatal error: Paletter image not supported by webp
                         * `
                         */
                        imagepalettetotruecolor($imageHandler);
                        imagewebp($imageHandler, $cache->cache);
                        break;
                    default:
                        throw new ExceptionBadState("The extension($extension) is unknown or not yet supported", self::CANONICAL);
                }

            } finally {
                imagedestroy($imageHandler);
            }

        }
        return LocalPath::createFromPath($cache->cache);
    }

    public function setUseCache(bool $false): Vignette
    {
        $this->useCache = $false;
        return $this;
    }


}
