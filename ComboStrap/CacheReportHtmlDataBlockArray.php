<?php


namespace ComboStrap;


class CacheReportHtmlDataBlockArray
{

    const RESULT_STATUS = 'result';
    const DATE_MODIFIED = 'mtime';
    const CACHE_FILE = "file";

    /**
     * Used when the cache data report
     * are injected in the page in a json format
     */
    public const APPLICATION_COMBO_CACHE_JSON = "application/combo+cache+json";
    const DEPENDENCY_ATT = "dependency";


    /**
     * @return array - a array that will be transformed as json HTML data block
     * to be included in a HTML page in order to insert cache results in the html page
     */
    public static function getFromContext(): array
    {
        $cacheManager = ExecutionContext::getActualOrCreateFromEnv()
            ->getCacheManager();
        $cacheReporters = $cacheManager->getCacheResults();
        $htmlDataBlock = [];
        foreach ($cacheReporters as $cacheReporter) {

            $cacheResults = $cacheReporter->getResults();
            foreach ($cacheResults as $result) {

                $modifiedDate = "";
                if ($result->getPath() !== null) {
                    try {
                        $modifiedTime = FileSystems::getModifiedTime($result->getPath());
                        $modifiedDate = $modifiedTime->format(Iso8601Date::getFormat());
                    } catch (ExceptionNotFound $e) {
                        // the file exists
                    }
                }
                $mode = $result->getMode();
                $sourcePath = $result->getMarkupPath()->getPathObject();
                /**
                 * If this is not a wiki path, we try to transform it as wiki path
                 * to get a shorter path (ie id) in the report
                 */
                if (!($sourcePath instanceof WikiPath)) {
                    try {
                        $sourcePath = WikiPath::createFromPathObject($sourcePath);
                    } catch (ExceptionBadArgument $e) {
                        // could not be transformed as wiki path (missing a drive)
                    }
                }
                $cacheFile = $result->getPath();
                try {
                    $cacheFile = $cacheFile->toWikiPath();
                } catch (ExceptionBadArgument $e) {
                    LogUtility::error("Cache reporter: The cache file could not be transformed as a wiki path. Error: " . $e->getMessage());
                }


                $data = [
                    self::RESULT_STATUS => $result->getResult(),
                    self::DATE_MODIFIED => $modifiedDate,
                    self::CACHE_FILE => $cacheFile->toAbsoluteString()
                ];

                if ($mode === FetcherMarkup::XHTML_MODE) {
                    try {
                        $dependencies = FetcherMarkup::createXhtmlMarkupFetcherFromPath($sourcePath, $sourcePath)
                            ->getOutputCacheDependencies()
                            ->getDependencies();
                    } catch (ExceptionNotExists $e) {
                        continue;
                    }
                    $data[self::DEPENDENCY_ATT] = $dependencies;
                }

                $htmlDataBlock[$sourcePath->toAbsoluteString()][$mode] = $data;

            }

        }
        return $htmlDataBlock;
    }


    /**
     * An utility function to extract the cache data block from test responses
     * @param XmlDocument $xmlDom
     * @return mixed
     * @throws ExceptionCompile
     */
    public static function extractFromHtmlDom(XmlDocument $xmlDom)
    {
        $metaCacheMain = $xmlDom->querySelector('script[type="' . CacheReportHtmlDataBlockArray::APPLICATION_COMBO_CACHE_JSON . '"]');
        $cacheJsonTextValue = $metaCacheMain->getNodeValueWithoutCdata();
        return json_decode($cacheJsonTextValue, true);
    }
}
