<?php

namespace ComboStrap;

/**
 * Return raw files
 */
class FetchRaw extends FetchAbs
{

    public const MEDIA_QUERY_PARAMETER = "media";
    const SRC_QUERY_PARAMETER = "src";
    const RAW = "raw";
    private DokuPath $path;


    public static function createFromPath(DokuPath $dokuPath): FetchRaw
    {
        return self::createEmpty()->setOriginalPath($dokuPath);
    }

    /**
     * Empty because a fetch is mostly build through an URL
     * @return FetchRaw
     */
    public static function createEmpty(): FetchRaw
    {
        return new FetchRaw();
    }

    /**
     * @throws ExceptionBadArgument
     */
    public static function createFetcherFromFetchUrl(Url $fetchUrl): FetchRaw
    {
        $fetchRaw = FetchRaw::createEmpty();
        $fetchRaw->buildFromUrl($fetchUrl);
        return $fetchRaw;
    }


    /**
     * @return Url - an URL to download the media
     */
    function getFetchUrl(Url $url = null): Url
    {
        /**
         * For dokuwiki implementation, see {@link ml()}
         * We still use the {@link FetchRaw::MEDIA_QUERY_PARAMETER}
         * to be Dokuwiki Compatible even if we can serve from other drive know
         */
        $url = parent::getFetchUrl($url)
            ->addQueryParameterIfNotActualSameValue(FetchRaw::MEDIA_QUERY_PARAMETER, $this->path->getDokuwikiId());
        if ($this->path->getDrive() !== DokuPath::MEDIA_DRIVE) {
            $url->addQueryParameter(DokuPath::DRIVE_ATTRIBUTE, $this->path->getDrive());
        }
        try {
            $rev = $this->path->getRevision();
            $url->addQueryParameter(DokuPath::REV_ATTRIBUTE, $rev);
        } catch (ExceptionNotFound $e) {
            // ok no rev
        }

        return $url;

    }

    function getFetchPath(): LocalPath
    {
        return $this->path->toLocalPath();
    }


    /**
     * Buster for the {@link Fetch} interface
     * @throws ExceptionNotFound
     */
    function getBuster(): string
    {
        return FileSystems::getCacheBuster($this->path);
    }


    function acceptsFetchUrl(Url $url): bool
    {

        if ($url->hasProperty(DokuPath::DRIVE_ATTRIBUTE)) {
            return true;
        }
        return false;

    }

    /**
     * @throws ExceptionNotFound
     */
    public function getMime(): Mime
    {
        return FileSystems::getMime($this->path);
    }

    public function setOriginalPath(DokuPath $dokuPath): FetchRaw
    {
        $this->path = $dokuPath;
        return $this;
    }

    /**
     * @throws ExceptionBadArgument - if the media was not found
     */
    public function buildFromTagAttributes(TagAttributes $tagAttributes): FetchRaw
    {

        $id = $tagAttributes->getValueAndRemove(self::MEDIA_QUERY_PARAMETER);
        if ($id === null) {
            $id = $tagAttributes->getValueAndRemove(self::SRC_QUERY_PARAMETER);
        }
        if ($id === null) {
            throw new ExceptionBadArgument("The (" . self::MEDIA_QUERY_PARAMETER . " or " . self::SRC_QUERY_PARAMETER . ") query property is mandatory and was not defined");
        }
        $drive = $tagAttributes->getValueAndRemove(DokuPath::DRIVE_ATTRIBUTE, DokuPath::MEDIA_DRIVE);
        $rev = $tagAttributes->getValueAndRemove(DokuPath::REV_ATTRIBUTE);
        $this->path = DokuPath::create($id, $drive, $rev);

        parent::buildFromTagAttributes($tagAttributes);
        return $this;

    }

    public function getOriginalPath(): DokuPath
    {
        return $this->path;
    }


    public
    function getName(): string
    {
        return self::RAW;
    }
}
