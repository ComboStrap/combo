<?php

namespace ComboStrap;

class DokuFetch extends FetchAbs
{

    public const MEDIA_QUERY_PARAMETER = "media";
    const SRC_QUERY_PARAMETER = "src";
    private DokuPath $path;


    public static function createFromPath(DokuPath $dokuPath): DokuFetch
    {
        return self::createEmpty()->setDokuPath($dokuPath);
    }

    /**
     * Empty because a fetch is mostly build through an URL
     * @return DokuFetch
     */
    public static function createEmpty(): DokuFetch
    {
        return new DokuFetch();
    }


    /**
     * @return Url - an URL to download the media
     */
    function getFetchUrl(Url $url = null): Url
    {
        /**
         * For dokuwiki implementation, see {@link ml()}
         * We still use the {@link DokuFetch::MEDIA_QUERY_PARAMETER}
         * to be Dokuwiki Compatible even if we can serve from other drive know
         */
        $url = parent::getFetchUrl($url)
            ->addQueryParameter(DokuFetch::MEDIA_QUERY_PARAMETER, $this->path->getDokuwikiId());
        if ($this->path->getDrive() !== DokuPath::MEDIA_DRIVE) {
            $url->addQueryParameter(DokuPath::DRIVE_ATTRIBUTE, $this->path->getDrive());
        }
        return $url;

    }

    function getFetchPath(): DokuPath
    {
        return $this->path;
    }


    /**
     * Buster for the {@link Fetch} interface
     * @throws ExceptionNotFound
     */
    function getBuster(): string
    {
        $time = FileSystems::getModifiedTime($this->path);
        return strval($time->getTimestamp());
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

    public function setDokuPath(DokuPath $dokuPath): DokuFetch
    {
        $this->path = $dokuPath;
        return $this;
    }

    /**
     * @throws ExceptionBadArgument
     */
    public function buildFromUrl(Url $url): DokuFetch
    {

        $id = $url->getQueryPropertyValue(self::MEDIA_QUERY_PARAMETER);
        if ($id === null) {
            $id = $url->getQueryPropertyValue(self::SRC_QUERY_PARAMETER);
            if ($id === null) {
                throw new ExceptionBadArgument("The (" . self::MEDIA_QUERY_PARAMETER . " or " . self::SRC_QUERY_PARAMETER . ") query property is mandatory and was not present in the URL ($url)");
            }
        }
        $drive = $url->getQueryPropertyValueOrDefault(DokuPath::DRIVE_ATTRIBUTE, DokuPath::MEDIA_DRIVE);
        $rev = $url->getQueryPropertyValue(DokuPath::REV_ATTRIBUTE);
        $this->path = DokuPath::create($id, $drive, $rev);
        return $this;

    }


}
