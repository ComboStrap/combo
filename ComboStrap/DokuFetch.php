<?php

namespace ComboStrap;

class DokuFetch implements Fetch
{

    private DokuPath $path;

    public function __construct(DokuPath $dokuPath)
    {
        $this->path = $dokuPath;
    }

    public static function createFromPath(DokuPath $dokuPath): DokuFetch
    {
        return new DokuFetch($dokuPath);
    }


    /**
     * @return Url - an URL to download the media
     * @throws ExceptionNotFound
     */
    function getFetchUrl(): Url
    {
        /**
         * For dokuwiki implementation, see {@link ml()}
         */
        return Url::createFetchUrl()
            ->addQueryCacheBuster($this->getBuster())
            ->addQueryMediaParameter( $this->path->getDokuwikiId())
            ->addQueryParameter(DokuPath::DRIVE_ATTRIBUTE, $this->path->getDrive());

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
}
