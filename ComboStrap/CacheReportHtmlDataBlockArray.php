<?php


namespace ComboStrap;


class CacheReportHtmlDataBlockArray
{

    const RESULT_STATUS = 'result';
    const DATE_MODIFIED = 'mtime';
    /**
     * Used when the cache data report
     * are injected in the page in a json format
     */
    public const APPLICATION_COMBO_CACHE_JSON = "application/combo+cache+json";

    /**
     * @return array - a array that will be transformed as json HTML data block
     * to be included in a HTML page in order to insert cache results in the html page
     */
    public static function get(): array
    {
        $cacheReporters = CacheManager::getOrCreate()->getCacheResults();
        $htmlDataBlock = [];
        foreach ($cacheReporters as $cacheReporter) {

            foreach ($cacheReporter->getResults() as $result) {

                $modifiedDate = "";
                if ($result->getPath() !== null) {
                    $modifiedDate = FileSystems::getModifiedTime($result->getPath())->format(Iso8601Date::getFormat());
                }
                $htmlDataBlock[$result->getSlotId()][$result->getMode()] = [
                    self::RESULT_STATUS => $result->getResult(),
                    self::DATE_MODIFIED => $modifiedDate
                ];
            }

        }
        return $htmlDataBlock;
    }
}
