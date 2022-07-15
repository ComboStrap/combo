<?php

namespace ComboStrap;

/**
 * Return raw files.
 * The mime is determined by the path.
 */
class FetcherRawLocalPath extends IFetcherAbs implements IFetcherPath, IFetcherSource
{

    use FetcherTraitWikiPath;

    const SRC_QUERY_PARAMETER = "src";
    const NAME = "raw";


    public static function createFromPath(WikiPath $wikiPath): FetcherRawLocalPath
    {
        $fetcherRaw = self::createEmpty();
        $fetcherRaw->setSourcePath($wikiPath);
        return $fetcherRaw;
    }

    /**
     * Empty because a fetch is mostly build through an URL
     * @return FetcherRawLocalPath
     */
    public static function createEmpty(): FetcherRawLocalPath
    {
        return new FetcherRawLocalPath();
    }

    /**
     * @throws ExceptionBadArgument
     */
    public static function createLocalFromFetchUrl(Url $fetchUrl): FetcherRawLocalPath
    {
        $fetchRaw = FetcherRawLocalPath::createEmpty();
        $fetchRaw->buildFromUrl($fetchUrl);
        return $fetchRaw;
    }


    /**
     * @return Url - an URL to download the media
     * @throws ExceptionNotFound - if the file does not exists (no buster can be added)
     */
    function getFetchUrl(Url $url = null): Url
    {

        $url = parent::getFetchUrl($url);
        $this->addLocalPathParametersToFetchUrl($url, self::$MEDIA_QUERY_PARAMETER);
        return $url;

    }

    /**
     * @param TagAttributes $tagAttributes
     * @return FetcherRawLocalPath
     * @throws ExceptionBadArgument - if the media/id was not found
     * @throws ExceptionBadSyntax
     * @throws ExceptionNotExists
     * @throws ExceptionNotFound
     */
    public function buildFromTagAttributes(TagAttributes $tagAttributes): FetcherRawLocalPath
    {

        $this->buildOriginalPathFromTagAttributes($tagAttributes);
        parent::buildFromTagAttributes($tagAttributes);
        return $this;

    }

    function getFetchPath(): LocalPath
    {
        return $this->getSourcePath()->toLocalPath();
    }


    /**
     * Buster for the {@link IFetcher} interface
     * @throws ExceptionNotFound
     */
    function getBuster(): string
    {
        return FileSystems::getCacheBuster($this->getSourcePath());
    }


    public
    function getFetcherName(): string
    {
        return self::NAME;
    }
}
