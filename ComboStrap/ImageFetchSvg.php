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
    /**
     * @var DokuPath
     */
    private DokuPath $path;
    private FetchCache $fetchCache;


    /**
     * @var SvgDocument
     */
    private $svgDocument;
    private ColorRgb $color;

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
     * @throws ExceptionNotFound
     */
    public function getFetchUrl(): Url
    {

        $fetchUrl = DokuFetch::createFromPath($this->path)->getFetchUrl();
        $this->addCommonImageQueryParameterToUrl($fetchUrl);
        return $fetchUrl;

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
        try {
            $time = FileSystems::getModifiedTime($this->fetchCache->getFile());
        } catch (ExceptionNotFound $e) {
            LogUtility::internalError("The cache file should exists. Actual time used instead as buster");
            $time = new \DateTime();
        }
        return strval($time->getTimestamp());
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
     * @throws ExceptionBadArgument
     */
    public function buildFromUrl(Url $url): ImageFetchSvg
    {
        $this->path = DokuFetch::createEmpty()->buildFromUrl($url)->getFetchPath();
        $this->buildSharedImagePropertyFromTagAttributes($url);
        $color = $url->getQueryPropertyValue(ColorRgb::COLOR);
        if($color!==null){
            // we can't have an hex in an url, we will see if this is encoded ;?
            $this->setColor(ColorRgb::createFromString($color));
        }
        return $this;
    }

    public function setColor(ColorRgb $color): ImageFetchSvg
    {
        $this->color = $color;
        return $this;
    }


}
