<?php

namespace ComboStrap;

use ComboStrap\Web\Url;

/**
 * Static class of the fetcher system
 */
class FetcherSystem
{

    /**
     *
     * @param Url $fetchUrl
     * @return IFetcherPath
     * @throws ExceptionBadArgument
     * @throws ExceptionNotFound
     * @throws ExceptionInternal
     */
    public static function createPathFetcherFromUrl(Url $fetchUrl): IFetcherPath
    {

        try {
            $fetcherName = $fetchUrl->getQueryPropertyValue(IFetcher::FETCHER_KEY);
            try {
                $fetchers = ClassUtility::getObjectImplementingInterface(IFetcherPath::class);
            } catch (\ReflectionException $e) {
                throw new ExceptionInternal("We could read fetch classes via reflection Error: {$e->getMessage()}");
            }
            foreach ($fetchers as $fetcher) {
                /**
                 * @var IFetcherPath $fetcher
                 */
                if ($fetcher->getFetcherName() === $fetcherName) {
                    $fetcher->buildFromUrl($fetchUrl);
                    return $fetcher;
                }
            }
        } catch (ExceptionNotFound $e) {
            // no fetcher property
        }


        try {
            $fetchDoku = FetcherRawLocalPath::createLocalFromFetchUrl($fetchUrl);
            $dokuPath = $fetchDoku->getSourcePath();
        } catch (ExceptionBadArgument $e) {
            throw new ExceptionNotFound("No fetcher could be matched to the url ($fetchUrl)");
        }
        try {
            $mime = FileSystems::getMime($dokuPath);
        } catch (ExceptionNotFound $e) {
            LogUtility::warning("Warning: The mime is unknown for the path ($dokuPath).", LogUtility::SUPPORT_CANONICAL, $e);
            $mime = new Mime(Mime::BINARY_MIME);
        }
        switch ($mime->toString()) {
            case Mime::SVG:
                return FetcherSvg::createSvgFromFetchUrl($fetchUrl);
            default:
                if ($mime->isImage()) {
                    return FetcherRaster::createRasterFromFetchUrl($fetchUrl);
                } else {
                    return $fetchDoku;
                }
        }

    }

    /**
     * @throws ExceptionInternal - if we can't reflect the class
     * @throws ExceptionNotFound - if the fetcher is unknown
     * @throws ExceptionBadArgument - if the fetcher is not set
     */
    public static function createFetcherStringFromUrl(Url $fetchUrl): IFetcherString
    {

        try {
            $fetcherName = $fetchUrl->getQueryPropertyValue(IFetcher::FETCHER_KEY);
        } catch (ExceptionNotFound $e) {
            throw new ExceptionBadArgument("No fetcher name found");
        }
        try {
            $fetchers = ClassUtility::getObjectImplementingInterface(IFetcherString::class);
        } catch (\ReflectionException $e) {
            throw new ExceptionInternal("We could read fetch classes via reflection Error: {$e->getMessage()}");
        }
        foreach ($fetchers as $fetcher) {
            /**
             * @var IFetcherString $fetcher
             */
            if ($fetcher->getFetcherName() === $fetcherName) {
                $fetcher->buildFromUrl($fetchUrl);
                return $fetcher;
            }
        }
        throw new ExceptionNotFound("No fetcher found with the name ($fetcherName)");

    }

}
