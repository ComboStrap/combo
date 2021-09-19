<?php


namespace ComboStrap;


require_once(__DIR__ . "/PluginUtility.php");

/**
 * Class Image
 * @package ComboStrap
 * An image and its attribute
 * (ie a file and its transformation attribute if any such as
 * width, height, ...)
 */
abstract class Image extends Media
{


    const CANONICAL = "image";


    /**
     * Image constructor.
     * @param $absolutePath
     * @param TagAttributes|null $attributes - the attributes
     * @param string|null $rev
     */
    public function __construct($absolutePath, $rev = null, $attributes = null)
    {
        if ($attributes === null) {
            $this->attributes = TagAttributes::createEmpty(self::CANONICAL);
        }

        parent::__construct($absolutePath, $rev, $attributes);
    }


    public static function createImageFromAbsolutePath($imageIdFromMeta, $rev = null)
    {

        /**
         * Processing
         */
        $dokuPath = DokuPath::createMediaPathFromAbsolutePath($imageIdFromMeta, $rev);
        $mime = $dokuPath->getMime();

        if (substr($mime, 0, 5) == 'image') {
            if (substr($mime, 6) == "svg+xml") {

                return new ImageSvg($imageIdFromMeta, $rev);

            } else {

                return new ImageRaster($imageIdFromMeta, $rev);

            }
        } else {
            throw new \RuntimeException("The file ($imageIdFromMeta) has not been detected as beeing an image.", LogUtility::LVL_MSG_ERROR, self::CANONICAL);
        }

    }

    /**
     * Return a height value that is conform to the {@link Image::getAspectRatio()} of the image.
     *
     * @param int|null $requestedWidth - the width to derive the height from (in case the image is created for responsive lazy loading)
     * if not specified, the requested width and if not specified the intrinsic width
     * @param int|null $requestedHeight
     * @return int the height value attribute in a img
     *
     * Algorithm:
     *   * If the requested height given is not null, return the given height rounded
     *   * If the requested height is null, if the requested width is:
     *         * null: return the intrinsic / natural height
     *         * not null: return the height as being the width scaled down by the {@link Image::getAspectRatio()}
     */
    public function getHeightValueScaledDown(?int $requestedWidth, ?int $requestedHeight): int
    {

        if (!empty($requestedWidth) && !empty($requestedHeight)) {
            LogUtility::msg("The requested width ($requestedWidth) and the requested height ($requestedHeight) are not null. You can't scale an image in width and height. The width or the height should be null.", LogUtility::LVL_MSG_ERROR, self::CANONICAL);
        }

        $computedHeight = $requestedHeight;
        if (empty($requestedHeight)) {

            if (empty($requestedWidth)) {

                $computedHeight = $this->getHeight();

            } else {

                // Width is not empty
                // We derive the height from it
                if ($this->getAspectRatio() !== false) {
                    $computedHeight = $requestedWidth / $this->getAspectRatio();
                } else {
                    LogUtility::msg("The ratio of the image ($this) could not be calculated", LogUtility::LVL_MSG_ERROR, self::CANONICAL);
                }

            }
        }

        /**
         * Rounding to integer
         * The fetch.php file takes int as value for width and height
         * making a rounding if we pass a double (such as 37.5)
         * This is important because the security token is based on width and height
         * and therefore the fetch will failed
         *
         * And not directly {@link intval} because it will make from 3.6, 3 and not 4
         */
        return intval(round($computedHeight));

    }

    /**
     * Return a width value that is conform to the {@link Image::getAspectRatio()} of the image.
     *
     * @param int|null $requestedWidth - the requested width (may be null)
     * @param int|null $requestedHeight - the request height (may be null)
     * @return int - the width value attribute in a img (in CSS pixel that the image should takes)
     *
     * Algorithm:
     *   * If the requested width given is not null, return the given width
     *   * If the requested width is null, if the requested height is:
     *         * null: return the intrinsic / natural width
     *         * not null: return the width as being the height scaled down by the {@link Image::getAspectRatio()}
     */
    public function getWidthValueScaledDown(?int $requestedWidth, ?int $requestedHeight): int
    {

        if (!empty($requestedWidth) && !empty($requestedHeight)) {
            LogUtility::msg("The requested width ($requestedWidth) and the requested height ($requestedHeight) are not null. You can't scale an image in width and height. The width or the height should be null.", LogUtility::LVL_MSG_ERROR, self::CANONICAL);
        }

        $computedWidth = $requestedWidth;
        if (empty($requestedWidth)) {

            if (empty($requestedHeight)) {

                $computedWidth = $this->getWidth();

            } else {

                if ($this->getAspectRatio() !== false) {
                    $computedWidth = $this->getAspectRatio() * $requestedHeight;
                } else {
                    LogUtility::msg("The aspect ratio of the image ($this) could not be calculated", LogUtility::LVL_MSG_ERROR, self::CANONICAL);
                }

            }
        }
        /**
         * Rounding to integer
         * The fetch.php file takes int as value for width and height
         * making a rounding if we pass a double (such as 37.5)
         * This is important because the security token is based on width and height
         * and therefore the fetch will failed
         *
         * And this is also ask by the specification
         * a non-null positive integer
         * https://html.spec.whatwg.org/multipage/embedded-content-other.html#attr-dim-height
         *
         * And not {@link intval} because it will make from 3.6, 3 and not 4
         */
        return intval(round($computedWidth));
    }


    /**
     * For a raster image, the internal width
     * for a svg, the defined viewBox
     *
     * This is needed to calculate the {@link MediaLink::getTargetRatio() target ratio}
     * and pass them to the img tag to avoid layout shift
     *
     * @return mixed
     */
    public abstract function getWidth();

    /**
     * For a raster image, the internal height
     * for a svg, the defined `viewBox` value
     *
     * This is needed to calculate the {@link MediaLink::getTargetRatio() target ratio}
     * and pass them to the img tag to avoid layout shift
     *
     * @return mixed
     */
    public abstract function getHeight();

    /**
     * The Aspect ratio as explained here
     * https://html.spec.whatwg.org/multipage/embedded-content-other.html#attr-dim-height
     * @return float|int|false
     * false if the image is not supported
     *
     * It's needed for an img tag to set the img `width` and `height` that pass the
     * {@link MediaLink::checkWidthAndHeightRatioAndReturnTheGoodValue() check}
     * to avoid layout shift
     */
    public function getAspectRatio()
    {

        if ($this->getHeight() == null || $this->getWidth() == null) {
            return false;
        } else {
            return $this->getWidth() / $this->getHeight();
        }
    }

    /**
     * @return bool if this is raster image, false if this is a vector image
     */
    public function isRaster(): bool
    {
        if ($this->getMime() === ImageSvg::MIME) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Giving width and height, check that the aspect ratio is the same
     * than the intrinsic one
     * @param $height
     * @param $width
     */
    public
    function checkLogicalRatioAgainstIntrinsicRatio($width, $height)
    {
        /**
         * Check of height and width dimension
         * as specified here
         * https://html.spec.whatwg.org/multipage/embedded-content-other.html#attr-dim-height
         */
        $targetRatio = $this->getAspectRatio();
        if (!(
            $height * $targetRatio >= $width - 0.5
            &&
            $height * $targetRatio <= $width + 0.5
        )) {
            // check the second statement
            if (!(
                $width / $targetRatio >= $height - 0.5
                &&
                $width / $targetRatio <= $height + 0.5
            )) {
                if (
                    !empty($width)
                    && !empty($height)
                ) {
                    /**
                     * The user has asked for a width and height
                     */
                    $width = round($height * $targetRatio);
                    LogUtility::msg("The width ($height) and height ($width) specified on the image ($this) does not follow the natural ratio as <a href=\"https://html.spec.whatwg.org/multipage/embedded-content-other.html#attr-dim-height\">required by HTML</a>. The width was then set to ($width).", LogUtility::LVL_MSG_INFO, self::CANONICAL);
                } else {
                    /**
                     * Programmatic error from the developer
                     */
                    $imgTagRatio = $width / $height;
                    LogUtility::msg("Internal Error: The width ($width) and height ($height) calculated for the image ($this) does not pass the ratio test. They have a ratio of ($imgTagRatio) while the natural dimension ratio is ($targetRatio)");
                }
            }
        }
    }

    /**
     * The Url
     * @return mixed
     */
    public abstract function getAbsoluteUrl();

    /**
     *
     * The alternate text (the title in Dokuwiki media term)
     * @return null
     */
    public function getAlt()
    {
        return $this->getTitle();
    }

    public
    function getRequestedHeight()
    {
        $requestedHeight = $this->attributes->getValue(Dimension::HEIGHT_KEY);
        if (!empty($requestedHeight)) {
            // it should not be bigger than the media Height
            $mediaHeight = $this->getHeight();
            if (!empty($mediaHeight)) {
                if ($requestedHeight > $mediaHeight) {
                    LogUtility::msg("For the image ($this), the requested height of ($requestedHeight) can not be bigger than the intrinsic height of ($mediaHeight). The height was then set to its natural height ($mediaHeight)", LogUtility::LVL_MSG_ERROR, self::CANONICAL);
                    $requestedHeight = $mediaHeight;
                }
            }
        }
        return $requestedHeight;

    }

    /**
     * The requested width
     */
    public
    function getRequestedWidth()
    {
        $requestedWidth = $this->attributes->getValue(Dimension::WIDTH_KEY);
        if (!empty($requestedWidth)) {
            // it should not be bigger than the media Height
            $mediaWidth = $this->getWidth();
            if (!empty($mediaWidth)) {
                if ($requestedWidth > $mediaWidth) {
                    global $ID;
                    if ($ID != "wiki:syntax") {
                        // There is a bug in the wiki syntax page
                        // {{wiki:dokuwiki-128.png?200x50}}
                        // https://forum.dokuwiki.org/d/19313-bugtypo-how-to-make-a-request-to-change-the-syntax-page-on-dokuwikii
                        LogUtility::msg("For the image ($this), the requested width of ($requestedWidth) can not be bigger than the intrinsic width of ($mediaWidth). The width was then set to its natural width ($mediaWidth)", LogUtility::LVL_MSG_ERROR, self::CANONICAL);
                    }
                    $requestedWidth = $mediaWidth;
                }
            }
        }
        return $requestedWidth;

    }


}
