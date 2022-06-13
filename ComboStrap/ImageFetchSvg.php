<?php


namespace ComboStrap;

/**
 * Class ImageSvg
 * @package ComboStrap
 *
 * Svg image fetch processing that can output:
 *   * an URL for an HTTP request
 *   * an SvgFile for an HTTP response or any further processing
 *
 */
class ImageFetchSvg extends ImageFetch
{

    const EXTENSION = "svg";
    const CANONICAL = "svg";

    const PRESERVE_ASPECT_RATIO_KEY = "preserveAspectRatio";
    /**
     * @var DokuPath
     */
    private DokuPath $path;

    /**
     * @var SvgDocument
     */
    private $svgDocument;
    private ?ColorRgb $color = null;
    private string $buster;
    private ?string $preserveAspectRatio;

    public static function createEmpty(): ImageFetchSvg
    {
        return new ImageFetchSvg();
    }

    /**
     *
     * @throws ExceptionCompile
     */
    public function getIntrinsicWidth(): int
    {
        return $this->getSvgDocument()->getMediaWidth();
    }

    /**
     * @throws ExceptionCompile
     */
    public function getIntrinsicHeight(): int
    {
        return $this->getSvgDocument()->getMediaHeight();
    }


    /**
     * @throws ExceptionBadSyntax - content is not svg
     * @throws ExceptionNotFound - path does not exist
     */
    protected function getSvgDocument(): SvgDocument
    {
        /**
         * We build the svg document later because the file may not exist
         * (Case with icon for instance where they are downloaded if they don't exist)
         *
         */
        if ($this->svgDocument === null) {
            /**
             * The svg document throw an error if the file does not exist or is not valid
             */

            $this->svgDocument = SvgDocument::createSvgDocumentFromPath($this->path);

        }
        return $this->svgDocument;
    }

    /**
     *
     * @return Url - the fetch url
     *
     */
    public function getFetchUrl(Url $url = null): Url
    {
        $url = parent::getFetchUrl($url);
        $url = DokuFetch::createFromPath($this->path)->getFetchUrl($url);
        try {
            $url->addQueryParameter(ColorRgb::COLOR, $this->getRequestedColor()->toCssValue());
        } catch (ExceptionNotFound $e) {
            // no color ok
        }
        try {
            $url->addQueryParameter(self::PRESERVE_ASPECT_RATIO_KEY, $this->getRequestedPreserveAspectRatio());
        } catch (ExceptionNotFound $e) {
            // no preserve ratio ok
        }
        $this->addCommonImageQueryParameterToUrl($url);
        return $url;

    }

    public function getRequestedPreserveAspectRatio()
    {
        if ($this->preserveAspectRatio === null) {
            throw new ExceptionNotFound("No preserve Aspect Ratio was requested");
        }
        return $this->preserveAspectRatio;
    }

    /**
     * Return the svg file transformed by the attributes
     * from cache if possible. Used when making a fetch with the URL
     * @return LocalPath
     * @throws ExceptionBadSyntax - the file is not a svg file
     * @throws ExceptionNotFound - the file was not found
     */
    public function getFetchPath(): LocalPath
    {

        /**
         * Generated svg file cache init
         */
        $fetchCache = FetchCache::createFrom($this);
        $files[] = $this->path->toAbsolutePath()->toPathString();
        $files[] = Site::getComboHome()->resolve("ComboStrap")->resolve("SvgDocument.php");
        $files[] = Site::getComboHome()->resolve("ComboStrap")->resolve("XmlDocument.php");
        $files = array_merge(Site::getConfigurationFiles(), $files); // svg generation depends on configuration
        foreach ($files as $file) {
            $fetchCache->addFileDependency($file);
        }

        global $ACT;
        if (PluginUtility::isDev() && $ACT === "preview") {
            // in dev mode, don't cache
            $isCacheUsable = false;
        } else {
            $isCacheUsable = $fetchCache->isCacheUsable();
        }
        if (!$isCacheUsable) {
            $svgDocument = $this->getSvgDocument();
            $content = $svgDocument->getXmlText($this->getAttributes());
            $fetchCache->storeCache($content);
        }
        return $fetchCache->getFile();

    }

    /**
     * The buster is not based on file but the cache file
     * because the cache is configuration dependent
     *
     * It the user changes the configuration, the svg file is generated
     * again and the browser cache should be deleted (ie the buster regenerated)
     *
     * {@link ResourceCombo::getBuster()}
     * @return string
     *
     */
    public
    function getBuster(): string
    {
        return $this->buster;
    }


    function acceptsFetchUrl(Url $url): bool
    {

        $media = $url->getQueryPropertyValue(DokuFetch::MEDIA_QUERY_PARAMETER);
        $dokuPath = DokuPath::createMediaPathFromId($media);
        try {
            $mime = FileSystems::getMime($dokuPath);
        } catch (ExceptionNotFound $e) {
            return false;
        }
        if ($mime->toString() === Mime::SVG) {
            return true;
        }
        return false;
    }

    public function getMime(): Mime
    {
        return Mime::create(Mime::SVG);
    }

    /**
     * @return DokuPath - the path of the original svg
     */
    public function getPath(): Path
    {
        return $this->path;
    }

    /**
     * @throws ExceptionBadArgument - for any bad argument
     * @throws ExceptionNotFound - if the svg file was not found
     */
    public function buildFromUrl(Url $url): ImageFetchSvg
    {
        parent::buildFromUrl($url);
        $this->path = DokuFetch::createEmpty()->buildFromUrl($url)->getFetchPath();
        $this->buster = FileSystems::getCacheBuster($this->getPath());
        $this->buildSharedImagePropertyFromTagAttributes($url);
        $color = $url->getQueryPropertyValue(ColorRgb::COLOR);
        if ($color !== null) {
            // we can't have an hex in an url, we will see if this is encoded ;?
            $this->setRequestedColor(ColorRgb::createFromString($color));
        }
        $preserveAspectRatio = $url->getQueryPropertyValue(self::PRESERVE_ASPECT_RATIO_KEY);
        if ($preserveAspectRatio !== null) {
            $this->setRequestedPreserveAspectRatio($preserveAspectRatio);
        }
        return $this;
    }

    public function setRequestedColor(ColorRgb $color): ImageFetchSvg
    {
        $this->color = $color;
        return $this;
    }

    /**
     * @throws ExceptionNotFound
     */
    public function getRequestedColor(): ColorRgb
    {
        if ($this->color === null) {
            throw new ExceptionNotFound("No requested color");
        }
        return $this->color;
    }

    /**
     * @param string $preserveAspectRatio - the aspect ratio of the svg
     * @return $this
     */
    public function setRequestedPreserveAspectRatio(string $preserveAspectRatio): ImageFetchSvg
    {
        $this->preserveAspectRatio = $preserveAspectRatio;
        return $this;
    }


}
