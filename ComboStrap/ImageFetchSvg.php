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
    private CacheMedia $fetchCache;


    /**
     * @throws ExceptionBadArgument - if the path is not local
     */
    public function __construct($path, $tagAttributes = null)
    {
        $this->path = DokuPath::createFromPath($path);
        $this->fetchCache = new CacheMedia($path, $tagAttributes);
        parent::__construct($this->path, $tagAttributes);

    }


    /**
     * @var SvgDocument
     */
    private $svgDocument;

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

            $this->svgDocument = SvgDocument::createSvgDocumentFromPath($this->getPath());

        }
        return $this->svgDocument;
    }

    /**
     *
     * @return Url - the fetch url
     *
     */
    public function getFetchUrl(): Url
    {

        $fetchUrl = Url::createFetchUrl()
            ->addQueryMediaParameter($this->path->getDokuwikiId())
            ->addQueryParameter(DokuPath::DRIVE_ATTRIBUTE, $this->path->getDrive());


        $attributes = $this->getAttributes();
        $componentAttributes = $attributes->getComponentAttributes();
        foreach ($componentAttributes as $name => $value) {

            if (!in_array(strtolower($name), MediaLink::NON_URL_ATTRIBUTES)) {
                $newName = $name;

                /**
                 * Width and Height
                 * permits to create SVG of the asked size
                 *
                 * This is a little bit redundant with the
                 * {@link Dimension::processWidthAndHeight()}
                 * `max-width and width` styling property
                 * but you may use them outside of HTML.
                 */
                switch ($name) {
                    case Dimension::WIDTH_KEY:
                        $newName = "w";
                        try {
                            $value = ConditionalLength::createFromString($value)->toPixelNumber();
                        } catch (ExceptionCompile $e) {
                            LogUtility::msg("Error while converting the width value ($value) into pixel. Error: {$e->getMessage()}", LogUtility::LVL_MSG_ERROR, self::CANONICAL);
                            continue 2;
                        }
                        break;
                    case Dimension::HEIGHT_KEY:
                        $newName = "h";
                        try {
                            $value = ConditionalLength::createFromString($value)->toPixelNumber();
                        } catch (ExceptionCompile $e) {
                            LogUtility::msg("Error while converting the height value ($value) into pixel. Error: {$e->getMessage()}", LogUtility::LVL_MSG_ERROR, self::CANONICAL);
                            continue 2;
                        }
                        break;
                }

                if ($newName == CacheMedia::CACHE_KEY && $value == CacheMedia::CACHE_DEFAULT_VALUE) {
                    // This is the default
                    // No need to add it
                    continue;
                }

                if (!empty($value)) {
                    $fetchUrl->addQueryParameter($newName, trim($value));
                }
            }

        }

        $fetchUrl->addQueryCacheBuster($this->getBuster());

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

        global $ACT;
        if (PluginUtility::isDev() && $ACT === "preview") {
            // in dev mode, don't cache
            $isCacheUsable = false;
        } else {
            $isCacheUsable = $this->fetchCache->isCacheUsable();
        }
        if (!$isCacheUsable) {
            $svgDocument = $this->getSvgDocument();
            $content = $svgDocument->getXmlText($this->getAttributes());
            $this->fetchCache->storeCache($content);
        }
        return $this->fetchCache->getFile();

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

        $media = $url->getQueryPropertyValue(Url::MEDIA_QUERY_PARAMETER);
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
}
