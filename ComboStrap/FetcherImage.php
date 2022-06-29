<?php

namespace ComboStrap;

/**
 * Image request / response
 *
 * with requested attribute (ie a file and its transformation attribute if any such as
 * width, height, ...)
 *
 * Image may be generated that's why they don't extends {@link FetcherLocalPath}.
 * Image that depends on a source file use the {@link FetcherTraitLocalPath} and extends {@link FetcherLocalImage}
 *
 */
abstract class FetcherImage extends FetcherAbs
{

    const TOK = "tok";
    const CANONICAL = "image";


    private ?int $requestedWidth = null;
    private ?int $requestedHeight = null;

    private ?string $requestedRatio = null;
    private ?float $requestedRatioAsFloat = null;


    /**
     * Image Fetch constructor.
     *
     */
    public function __construct()
    {
        /**
         * Image can be generated, ie {@link FetcherVignette}, {@link FetcherSnapshot}
         */
    }


    /**
     * @param Url|null $url
     *
     */
    public function getFetchUrl(Url $url = null): Url
    {
        $url = parent::getFetchUrl($url);

        try {
            $requestedWidth = $this->getRequestedWidth();
            $url->addQueryParameterIfNotPresent(Dimension::WIDTH_KEY_SHORT, $requestedWidth);
        } catch (ExceptionNotFound $e) {
            // no width ok
            $requestedWidth = null;
        }

        try {
            $requestedHeight = $this->getRequestedHeight();
            $url->addQueryParameterIfNotPresent(Dimension::HEIGHT_KEY_SHORT, $requestedHeight);
        } catch (ExceptionNotFound $e) {
            // no height ok
            $requestedHeight = null;
        }

        try {
            $ratio = $this->getRequestedAspectRatio();
            $url->addQueryParameterIfNotPresent(Dimension::RATIO_ATTRIBUTE, $ratio);
        } catch (ExceptionNotFound $e) {
            // no width ok
        }

        /**
         * Dokuwiki Conformance
         */
        if ($this instanceof FetcherLocalImage) {

            $url->addQueryParameter(FetcherImage::TOK, $this->getTok());


        }

        return $url;
    }

    /**
     * The tok is supposed to counter a DDOS attack when
     * with or height are requested
     *
     *
     * @throws ExceptionNotFound
     */
    public function getTok(): string
    {
        /**
         * Dokuwiki Compliance
         */
        if (!($this instanceof FetcherLocalImage)) {
            throw new ExceptionNotFound("No tok for non local image");
        }
        try {
            $requestedWidth = $this->getRequestedWidth();
        } catch (ExceptionNotFound $e) {
            $requestedWidth = null;
        }
        try {
            $requestedHeight = $this->getRequestedHeight();
        } catch (ExceptionNotFound $e) {
            $requestedHeight = null;
        }
        if ($requestedWidth !== null || $requestedHeight !== null) {

            $id = $this->getOriginalPath()->getWikiId();
            return media_get_token($id, $requestedWidth, $requestedHeight);

        }
        throw new ExceptionNotFound("No tok needed");
    }

    /**
     * @throws ExceptionBadArgument
     */
    public function buildFromTagAttributes(TagAttributes $tagAttributes): FetcherImage
    {

        $requestedWidth = $tagAttributes->getValueAndRemove(Dimension::WIDTH_KEY);
        if ($requestedWidth === null) {
            $requestedWidth = $tagAttributes->getValueAndRemove(Dimension::WIDTH_KEY_SHORT);
        }
        if ($requestedWidth !== null) {
            try {
                $requestedWidthInt = DataType::toInteger($requestedWidth);
            } catch (ExceptionBadArgument $e) {
                throw new ExceptionBadArgument("The width value ($requestedWidth) is not a valid integer", FetcherImage::CANONICAL, 0, $e);
            }
            $this->setRequestedWidth($requestedWidthInt);
        }

        $requestedHeight = $tagAttributes->getValueAndRemove(Dimension::HEIGHT_KEY);
        if ($requestedHeight === null) {
            $requestedHeight = $tagAttributes->getValueAndRemove(Dimension::HEIGHT_KEY_SHORT);
        }
        if ($requestedHeight !== null) {
            try {
                $requestedHeightInt = DataType::toInteger($requestedHeight);
            } catch (ExceptionBadArgument $e) {
                throw new ExceptionBadArgument("The height value ($requestedHeight) is not a valid integer", FetcherImage::CANONICAL, 0, $e);
            }
            $this->setRequestedHeight($requestedHeightInt);
        }

        $requestedRatio = $tagAttributes->getValueAndRemove(Dimension::RATIO_ATTRIBUTE);
        if ($requestedRatio !== null) {
            try {
                $this->setRequestedAspectRatio($requestedRatio);
            } catch (ExceptionBadSyntax $e) {
                throw new ExceptionBadArgument("The requested ratio ($requestedRatio) is not a valid value ({$e->getMessage()})", FetcherImage::CANONICAL, 0, $e);
            }
        }
        parent::buildFromTagAttributes($tagAttributes);
        return $this;
    }


    /**
     * For a raster image, the internal width
     * for a svg, the defined viewBox
     *
     * @return int in pixel
     */
    public

    abstract function getIntrinsicWidth(): int;

    /**
     * For a raster image, the internal height
     * for a svg, the defined `viewBox` value
     *
     * This is needed to calculate the {@link MediaLink::getTargetRatio() target ratio}
     * and pass them to the img tag to avoid layout shift
     *
     * @return int in pixel
     */
    public abstract function getIntrinsicHeight(): int;

    /**
     * The Aspect ratio as explained here
     * https://html.spec.whatwg.org/multipage/embedded-content-other.html#attr-dim-height
     * @return float
     * false if the image is not supported
     *
     * It's needed for an img tag to set the img `width` and `height` that pass the
     * {@link MediaLink::checkWidthAndHeightRatioAndReturnTheGoodValue() check}
     * to avoid layout shift
     *
     */
    public function getIntrinsicAspectRatio(): float
    {

        return $this->getIntrinsicWidth() / $this->getIntrinsicHeight();

    }

    /**
     * The Aspect ratio of the target image (may be the original or the an image scaled down)
     *
     * https://html.spec.whatwg.org/multipage/embedded-content-other.html#attr-dim-height
     * @return float
     * false if the image is not supported
     *
     * It's needed for an img tag to set the img `width` and `height` that pass the
     * {@link MediaLink::checkWidthAndHeightRatioAndReturnTheGoodValue() check}
     * to avoid layout shift
     *
     */
    public function getTargetAspectRatio()
    {

        return $this->getTargetWidth() / $this->getTargetHeight();

    }

    /**
     * Return the requested aspect ratio requested
     * with the property
     * or if the width and height were specified.
     *
     * The Aspect ratio as explained here
     * https://html.spec.whatwg.org/multipage/embedded-content-other.html#attr-dim-height
     * @return float
     *
     *
     * It's needed for an img tag to set the img `width` and `height` that pass the
     * {@link MediaLink::checkWidthAndHeightRatioAndReturnTheGoodValue() check}
     * to avoid layout shift
     * @throws ExceptionNotFound
     */
    public function getCalculatedRequestedAspectRatioAsFloat(): float
    {

        if ($this->requestedRatioAsFloat !== null) {
            return $this->requestedRatioAsFloat;
        }

        /**
         * Note: requested weight and width throw a `not found` if width / height == 0
         * No division by zero then
         */
        return $this->getRequestedWidth() / $this->getRequestedHeight();


    }


    /**
     * Giving width and height, check that the aspect ratio is the same
     * than the target one
     * @param $height
     * @param $width
     */
    public
    function checkLogicalRatioAgainstTargetRatio($width, $height)
    {
        /**
         * Check of height and width dimension
         * as specified here
         *
         * This is about the intrinsic dimension but we have the notion of target dimension
         *
         * https://html.spec.whatwg.org/multipage/embedded-content-other.html#attr-dim-height
         */
        try {
            $targetRatio = $this->getTargetAspectRatio();
        } catch (ExceptionCompile $e) {
            LogUtility::msg("Unable to check the target ratio because it returns this error: {$e->getMessage()}");
            return;
        }
        if (!(
            $height * $targetRatio >= $width - 1
            &&
            $height * $targetRatio <= $width + 1
        )) {
            // check the second statement
            if (!(
                $width / $targetRatio >= $height - 1
                &&
                $width / $targetRatio <= $height + 1
            )) {

                /**
                 * Programmatic error from the developer
                 */
                $imgTagRatio = $width / $height;
                LogUtility::msg("Internal Error: The width ($width) and height ($height) calculated for the image ($this) does not pass the ratio test. They have a ratio of ($imgTagRatio) while the target dimension ratio is ($targetRatio)");

            }
        }
    }


    /**
     * The logical height is the calculated height of the target image
     * specified in the query parameters
     *
     * For instance,
     *   * with `200`, the target image has a {@link FetcherTraitImage::getTargetWidth() logical width} of 200 and a {@link FetcherTraitImage::getTargetHeight() logical height} that is scaled down by the {@link FetcherTraitImage::getIntrinsicAspectRatio() instrinsic ratio}
     *   * with ''0x20'', the target image has a {@link FetcherTraitImage::getTargetHeight() logical height} of 20 and a {@link FetcherTraitImage::getTargetWidth() logical width} that is scaled down by the {@link FetcherTraitImage::getIntrinsicAspectRatio() instrinsic ratio}
     *
     * The doc is {@link https://www.dokuwiki.org/images#resizing}
     *
     *
     * @return int
     */
    public function getTargetHeight(): int
    {

        try {
            return $this->getRequestedHeight();
        } catch (ExceptionNotFound $e) {
            // no height
        }

        /**
         * Scaled down by width
         */
        try {
            $width = $this->getRequestedWidth();
            try {
                $ratio = $this->getCalculatedRequestedAspectRatioAsFloat();
            } catch (ExceptionNotFound $e) {
                $ratio = $this->getIntrinsicAspectRatio();
            }
            return self::round($width / $ratio);
        } catch (ExceptionNotFound $e) {
            // no width
        }


        /**
         * Scaled down by ratio
         */
        try {
            $ratio = $this->getCalculatedRequestedAspectRatioAsFloat();
            [$croppedWidth, $croppedHeight] = $this->getCroppingDimensionsWithRatio($ratio);
            return $croppedHeight;
        } catch (ExceptionNotFound $e) {
            // no requested aspect ratio
        }

        return $this->getIntrinsicHeight();

    }

    /**
     * The logical width is the width of the target image calculated from the requested dimension
     *
     * For instance,
     *   * with `200`, the target image has a {@link FetcherTraitImage::getTargetWidth() logical width} of 200 and a {@link FetcherTraitImage::getTargetHeight() logical height} that is scaled down by the {@link FetcherTraitImage::getIntrinsicAspectRatio() instrinsic ratio}
     *   * with ''0x20'', the target image has a {@link FetcherTraitImage::getTargetHeight() logical height} of 20 and a {@link FetcherTraitImage::getTargetWidth() logical width} that is scaled down by the {@link FetcherTraitImage::getIntrinsicAspectRatio() instrinsic ratio}
     *
     * The doc is {@link https://www.dokuwiki.org/images#resizing}
     * @return int
     */
    public function getTargetWidth(): int
    {

        try {
            return $this->getRequestedWidth();
        } catch (ExceptionNotFound $e) {
            // no requested width
        }

        /**
         * Scaled down by Height
         */
        try {
            $height = $this->getRequestedHeight();
            try {
                $ratio = $this->getCalculatedRequestedAspectRatioAsFloat();
            } catch (ExceptionNotFound $e) {
                $ratio = $this->getIntrinsicAspectRatio();
            }
            return self::round($ratio * $height);
        } catch (ExceptionNotFound $e) {
            // no requested height
        }


        /**
         * Scaled down by Ratio
         */
        try {
            $ratio = $this->getCalculatedRequestedAspectRatioAsFloat();
            [$logicalWidthWithRatio, $logicalHeightWithRatio] = $this->getCroppingDimensionsWithRatio($ratio);
            return $logicalWidthWithRatio;
        } catch (ExceptionNotFound $e) {
            // no ratio requested
        }

        return $this->getIntrinsicWidth();

    }

    /**
     * @return int|null
     * @throws ExceptionNotFound - if no requested width was asked
     */
    public function getRequestedWidth(): int
    {
        if ($this->requestedWidth === null) {
            throw new ExceptionNotFound("No width was requested");
        }
        if ($this->requestedWidth === 0) {
            throw new ExceptionNotFound("Width 0 was requested");
        }
        return $this->requestedWidth;
    }

    /**
     * @return int
     * @throws ExceptionNotFound - if no requested height was asked
     */
    public function getRequestedHeight(): int
    {
        if ($this->requestedHeight === null) {
            throw new ExceptionNotFound("Height not requested");
        }
        if ($this->requestedHeight === 0) {
            throw new ExceptionNotFound("Height 0 requested");
        }
        return $this->requestedHeight;
    }

    /**
     * Rounding to integer
     * The fetch.php file takes int as value for width and height
     * making a rounding if we pass a double (such as 37.5)
     * This is important because the security token is based on width and height
     * and therefore the fetch will failed
     *
     * And not directly {@link intval} because it will make from 3.6, 3 and not 4
     *
     * And this is also ask by the specification
     * a non-null positive integer
     * https://html.spec.whatwg.org/multipage/embedded-content-other.html#attr-dim-height
     *
     */
    public static function round(float $param): int
    {
        return intval(round($param));
    }


    /**
     *
     * Return the width and height of the image
     * after applying a ratio (16x9, 4x3, ..)
     *
     * The new dimension will apply to:
     *   * the viewBox for svg
     *   * the physical dimension for raster image
     *
     */
    public function getCroppingDimensionsWithRatio(float $targetRatio): array
    {

        /**
         * Trying to crop on the width
         */
        $logicalWidth = $this->getIntrinsicWidth();
        $logicalHeight = $this->round($logicalWidth / $targetRatio);
        if ($logicalHeight > $this->getIntrinsicHeight()) {
            /**
             * Cropping by height
             */
            $logicalHeight = $this->getIntrinsicHeight();
            $logicalWidth = $this->round($targetRatio * $logicalHeight);
        }
        return [$logicalWidth, $logicalHeight];

    }


    public function setRequestedWidth(int $requestedWidth): FetcherImage
    {
        $this->requestedWidth = $requestedWidth;
        return $this;
    }

    public function setRequestedHeight(int $requestedHeight): FetcherImage
    {
        $this->requestedHeight = $requestedHeight;
        return $this;
    }

    /**
     * @throws ExceptionBadSyntax
     */
    public function setRequestedAspectRatio(string $requestedRatio): FetcherImage
    {
        $this->requestedRatio = $requestedRatio;
        $this->requestedRatioAsFloat = Dimension::convertTextualRatioToNumber($requestedRatio);
        return $this;
    }


    public function __toString()
    {
        return get_class($this);
    }


    public function hasHeightRequested(): bool
    {
        try {
            $this->getRequestedHeight();
            return true;
        } catch (ExceptionNotFound $e) {
            return false;
        }
    }

    public function hasAspectRatioRequested(): bool
    {
        try {
            $this->getCalculatedRequestedAspectRatioAsFloat();
            return true;
        } catch (ExceptionNotFound $e) {
            return false;
        }

    }


    /**
     * @throws ExceptionNotFound
     */
    public function getRequestedAspectRatio(): string
    {
        if ($this->requestedRatio === null) {
            throw new ExceptionNotFound("No ratio was specified");
        }
        return $this->requestedRatio;
    }


}
